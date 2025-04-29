<?php

include("./db.php");
include("./logger.php");

class NotificationBrevoAndWeb
{
    public PDO $conn;

    // Mapping des templates et leurs variables
    public array $templates = [
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

        // - complete profile (pour la premier inscription) MyReklam
        $resultWeb = $this->sendNotificationWeb([
            'user_id' => $userId,
            'content' => 'Bienvenue sur MyReklam, vous pouvez compléter votre profil pour avoir de my\'s ',
            'return_url' => '/dashboard'
        ]);
        $resultBrevo = $this->sendNotificationBrevo([
            'email' => $userInfo['email'],
            'templateId' => 19,
            'params' => [
                'username' => $userInfo['profiletype'] == 'particulier' ? $userInfo['pseudo'] : $userInfo['nomsociete']
            ]
        ]);

        echo json_encode([
            "message" => "sendNotificationSubscriptionFree",
            "resultWeb" => $resultWeb,
            "resultBrevo" => $resultBrevo
        ]) . "\n";

        return $resultWeb || $resultBrevo;
    }

    // Méthode pour récupérer les informations de l'utilisateur
    private function getUserInfo($userId): array | null
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
        try {
            if (empty($data['user_id']) || empty($data['content'])) {
                echo "User ID ou content manquant";
                log_error("User ID ou content manquant", "SEND_NOTIFICATION_WEB", ["data" => $data]);
                return false;
            }
            $query = 'INSERT INTO "notifications" ("id", "user_id", "content", "type", "is_read", "return_url", "created_at", "updated_at", "metadata") VALUES (:id, :user_id, :content, :type, :is_read, :return_url, :created_at, :updated_at, :metadata)';
            $statement = $this->conn->prepare($query);
            $statement->bindParam(':id', $this->generateGUID());
            echo "User ID ou content manquant 1";
            $statement->bindParam(':user_id', $data['user_id']);
            echo "User ID ou content manquant 2";
            $statement->bindParam(':content', $data['content']);
            echo "User ID ou content manquant 3";
            $statement->bindParam(':type', isset($data['type']) ? $data['type'] : "info");
            echo "User ID ou content manquant 4";
            $statement->bindParam(':is_read', isset($data['is_read']) ? $data['is_read'] : false);
            echo "User ID ou content manquant 5";
            $statement->bindParam(':return_url', isset($data['return_url']) ? $data['return_url'] : null);
            echo "User ID ou content manquant 6";
            $statement->bindParam(':metadata', isset($data['metadata']) ? $data['metadata'] : null);
            echo "User ID ou content manquant 7";
            $result = $statement->execute();
            echo "User ID ou content manquant 8";

            return $result;
        } catch (Exception $e) {
            echo "Exception lors de l'envoi de la notification web" . $e->getMessage();
            log_error("Exception lors de l'envoi de la notification web", "SEND_NOTIFICATION_WEB", ["message" => $e->getMessage()]);
            return false;
        }
    }

    // Méthodes send Noitifcation brevo
    private function sendNotificationBrevo(array $data = []): bool
    {
        try {
            $email = $data['email'] ?? '';
            $name = $data['name'] ?? '';
            $templateId = $data['templateId'] ?? null;
            $paramsData = $data['params'] ?? [];

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

    // Méthodes internes
    private function generateGUID(): string
    {
        $guid = bin2hex(random_bytes(16));
        log_debug("GUID généré", "GENERATE_GUID", ["guid" => $guid]);
        return $guid;
    }
}
