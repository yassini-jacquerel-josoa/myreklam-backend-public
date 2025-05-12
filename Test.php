<?php

 

include_once(__DIR__ . "/packages/NotificationBrevoAndWeb.php");
 
$userId = $_POST['userId']; 

try {
    $notification = new NotificationBrevoAndWeb($conn);
    $notification->sendNotificationSubscriptionFree($userId);
 
    echo json_encode([
        'status' => 'success',
        'message' => 'Notification envoyée avec succès', 
    ]);

} catch (\Throwable $th) {
    echo json_encode([
        'status' => 'error',
        'message' => $th->getMessage()
    ]);
}
