<?php

require_once(__DIR__ . '/vendor/autoload.php');

// Charger les variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

function sendMail($email, $name, $templateId, $additionalParams = [], $link = null) {
    // Configure API key authorization
    $config = SendinBlue\Client\Configuration::getDefaultConfiguration()
        ->setApiKey('api-key', $_ENV['SENDINBLUE_API_KEY']);
    
    // Initialize API instance
    $apiInstance = new SendinBlue\Client\Api\TransactionalEmailsApi(
        new GuzzleHttp\Client(),
        $config
    );

    // Mapping des templates et leurs variables
    $templates = [
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
    
    // Vérification si le templateId est valide
    if (!array_key_exists($templateId, $templates)) {
        return ["status" => "error", "message" => "TemplateId non reconnu."];
    }
    
    // Construction des paramètres requis
    $params = [];
    foreach ($templates[$templateId] as $variable) {
        if (isset($additionalParams[$variable])) {
            $params[$variable] = $additionalParams[$variable];
        }
    }
    
    // Ajout des variables communes
    $params['username'] = $name;
    $params['link'] = $link ?? '';
    
    // Configuration de l'email
    $sendSmtpEmail = new \SendinBlue\Client\Model\SendSmtpEmail([
        'to' => [['email' => $email, 'name' => $name ?: 'Utilisateur']],
        'templateId' => $templateId,
        'params' => $params,
        'headers' => ['X-Mailin-custom' => 'custom_header_1:custom_value_1|custom_header_2:custom_value_2']
    ]);

    try {
        // Envoi de l'email
        $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
        
        if ($result && $result->getMessageId()) {
            return ["status" => "success", "message" => "Email envoyé avec succès"];
        } else {
            return ["status" => "error", "message" => "L'email n'a pas été envoyé. Vérifiez la configuration."];
        }
    } catch (Exception $e) {
        return ["status" => "error", "message" => "Exception : " . $e->getMessage()];
    }
}

// Traitement des requêtes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, "Email", FILTER_VALIDATE_EMAIL);
    $name = filter_input(INPUT_POST, "Name");
    $templateId = filter_input(INPUT_POST, "TemplateId", FILTER_VALIDATE_INT);
    $link = filter_input(INPUT_POST, "Link", FILTER_SANITIZE_URL);
    if($email && $name && $templateId){
        if (!$templateId) {
            echo json_encode(["status" => "error", "message" => "TemplateId invalide ou manquant."]);
            exit;
        }
        
        $additionalParams = $_POST;
        unset($additionalParams['Email'], $additionalParams['Name'], $additionalParams['TemplateId']);
        
        $response = sendMail($email, $name, $templateId, $additionalParams , $link);
        echo json_encode($response);
    }
   
}
