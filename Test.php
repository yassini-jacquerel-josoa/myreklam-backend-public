<?php

include_once(__DIR__ . "/packages/NotificationBrevoAndWeb.php");

$userId = $_POST['userId'];

$notification = new NotificationBrevoAndWeb($conn);
$notification->sendNotificationSubscriptionFree($userId);

// header
header('Content-Type: application/json');

// response
echo json_encode([
    'status' => 'success',
    'message' => 'Notification envoyée avec succès',
    'templateId' => $notification->getTemplateId("registration-professional-free")
]);

?>