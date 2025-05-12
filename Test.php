<?php

include_once(__DIR__ . "/packages/NotificationBrevoAndWeb.php");

$userId = $_POST['userId'];

try {
    $notification = new NotificationBrevoAndWeb($conn);
    $notification->sendNotificationSubscriptionFree($userId);

    // response
    echo json_encode([
        'status' => 'success',
        'message' => 'Notification envoyée avec succès',
        'templateId' => $notification->getTemplateId("registration-professional-free")
    ]);
} catch (\Throwable $th) {
    echo json_encode([
        'status' => 'error',
        'message' => $th->getMessage()
    ]);
}
