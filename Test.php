<?php

include_once("./packages/NotificationBrevoAndWeb.php");

$userId = $_POST['userId'];

$notification = new NotificationBrevoAndWeb($conn);
$notification->sendNotificationSubscriptionFree($userId);

echo json_encode([
    'status' => 'success',
    'message' => 'Notification envoyée avec succès'
]);

?>