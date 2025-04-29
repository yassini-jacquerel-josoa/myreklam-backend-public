<?php

include("./db.php");
include("./logger.php");

class NotificationBrevoAndWeb
{
    private PDO $conn;

    // Mapping des templates et leurs variables
    private array $templates = [
        1 => ['username', 'ad.category'],
        2 => ['username'],
        3 => ['username', 'ad.title', 'ad.category'],
        4 => ['username', 'mail_name', 'sender_email', 'recipient_email'],
        5 => ['plan_name', 'resiliation_date'],
        6 => [],
        7 => ['username', 'sender', 'message_content'],
        8 => ['username', 'sender', 'ad.title', 'message_content'],
        9 => ['username', 'comment.username', 'ad.title', 'comment.content'],
        10 => ['username', 'ad.title', 'comment.username', 'comment.content', 'response.content'],
        12 => ['username'],
        13 => ['username'],
        14 => ['username', 'ad.title', 'ad.type'],
        15 => ['username', 'ad.type', 'ad.title', 'organization.name'],
        16 => ['username', 'applicant.username', 'ad.title'],
        17 => ['username', 'event', 'organizator', 'dateStart', 'hoursStart', 'address', 'price'],
        18 => ['username', 'ad.title', 'ad.organizator', 'startDate', 'startHours', 'localisation', 'price'],
        19 => ['username'],
        20 => ['username'],
        21 => ['username'],
        22 => ['link'],
        23 => ['username'],
        24 => ['username', 'endDate'],
        25 => [],
        27 => []
    ];

    public function __construct(PDO $connection)
    {
        $this->conn = $connection;
        log_info("Connection initialisée   =>", "SYSTEM");
    }

    public function sendNotificationSubscriptionFree($userId): bool
    {
        $userInfo = $this->getUserInfo($userId);

        if (empty($userInfo)) {
            log_info("Informations de l'utilisateur non trouvées", "SEND_NOTIFICATION_SUBSCRIPTION_FREE", ["userId" => $userId]);
            return false;
        }

        $data = [
            'email' => $userInfo['email'],
            'templateId' => 19,
            'params' => [
                'username' => $userInfo['profiletype'] == 'particulier' ? $userInfo['pseudo'] : $userInfo['nomsociete']
            ]
        ];

        $resultWeb = $this->sendNotificationWeb($data);
        $resultBrevo = $this->sendNotificationBrevo($data);

        echo json_encode([
            "message" => "sendNotificationSubscriptionFree",
            "resultWeb" => $resultWeb,
            "resultBrevo" => $resultBrevo
        ]);

        return $resultWeb || $resultBrevo;
    }

    // Méthode pour récupérer les informations de l'utilisateur
    public function getUserInfo($userId): array | null
    {
        try {
            $query = 'SELECT * FROM "users" WHERE "Id" = :id';
            $statement = $this->conn->prepare($query);
            $statement->bindParam(':id', $userId);
            $statement->execute();
            $user = $statement->fetch(PDO::FETCH_ASSOC);

            if (empty($user)) {
                log_error("Utilisateur non trouvé", "GET_USER", ["userId" => $userId]);
                return null;
            }

            $query = 'SELECT * FROM "userInfo" WHERE userid = :id';
            $statement = $this->conn->prepare($query);
            $statement->bindParam(':id', $userId);
            $statement->execute();
            $userInfo = $statement->fetch(PDO::FETCH_ASSOC);

            if (empty($userInfo)) {
                log_error("Informations de l'utilisateur non trouvées", "GET_USER_INFO", ["userId" => $userId]);
                return null;
            }

            $userInfo['email'] = $user['Email'];
            $userInfo['username'] =  $userInfo['profiletype'] == 'particulier' ? $userInfo['pseudo'] : $userInfo['nomsociete'];
 
            return $userInfo;
        } catch (Exception $e) {
            log_error("Exception lors de la récupération des informations de l'utilisateur", "GET_USER_INFO", ["message" => $e->getMessage()]);
            return null;
        }
    }

    // Méthodes send Noitifcation web
    private function sendNotificationWeb(array $data = []): bool
    {
        return true;
    }

    // Méthodes send Noitifcation brevo
    private function sendNotificationBrevo(array $data = []): bool
    {
        echo json_encode([
            "message" => "sendNotificationBrevo",
            "email" => $data['email'],
            "name" => $data['name'],
            "templateId" => $data['templateId'],
            "params" => $data['params']
        ]);
        try {
            $email = $data['email'] ?? '';
            $name = $data['name'] ?? '';
            $templateId = $data['templateId'] ?? null;
            $paramsData = $data['params'] ?? [];

            log_info("Données à envoyer", "SEND_NOTIFICATION_BREVO", ["email" => $email, "templateId" => $templateId, "params" => $paramsData]);

            if (empty($email) || empty($templateId) || empty($paramsData)) {
                log_error("Email ou templateId manquant", "SEND_NOTIFICATION_BREVO", ["email" => $email, "templateId" => $templateId]);
                return false;
            }

            // Configure API key authorization
            $config = SendinBlue\Client\Configuration::getDefaultConfiguration()
                ->setApiKey('api-key', $_ENV['SENDINBLUE_API_KEY']);

            // Vérification si le templateId est valide
            if (!array_key_exists($templateId, $this->templates)) {
                log_error("TemplateId non reconnu", "SEND_NOTIFICATION_BREVO", ["templateId" => $templateId]);
                return false;
            }

            // Construction des paramètres requis
            $params = [];
            foreach ($this->templates[$templateId] as $variable) {
                if (isset($paramsData[$variable])) {
                    $params[$variable] = $paramsData[$variable];
                }
            }

            // Initialize API instance
            $apiInstance = new SendinBlue\Client\Api\TransactionalEmailsApi(
                new GuzzleHttp\Client(),
                $config
            );

            // Configuration de l'email
            $sendSmtpEmail = new \SendinBlue\Client\Model\SendSmtpEmail([
                'to' => [['email' => $email, 'name' => $name ?: 'Utilisateur']],
                'templateId' => $templateId,
                'params' => $params,
                'headers' => ['X-Mailin-custom' => 'custom_header_1:custom_value_1|custom_header_2:custom_value_2']
            ]);

            log_info("Email à envoyer", "SEND_NOTIFICATION_BREVO", ["email" => $email, "templateId" => $templateId, "params" => $params]);

            // Envoi de l'email
            $result = $apiInstance->sendTransacEmail($sendSmtpEmail);

            if ($result && $result->getMessageId()) {
                log_info("Email envoyé avec succès", "SEND_NOTIFICATION_BREVO", ["email" => $email, "templateId" => $templateId]);
                return true;
            } else {
                log_error("Échec de l'envoi d'email", "SEND_NOTIFICATION_BREVO", ["email" => $email, "templateId" => $templateId]);
                return false;
            }
        } catch (Exception $e) {
            log_error("Exception lors de l'envoi d'email", "SEND_NOTIFICATION_BREVO", ["message" => $e->getMessage()]);
            return false;
        }
    }
}
