<?php

 

include_once(__DIR__ . "/packages/NotificationBrevoAndWeb.php");
 
$userId = $_POST['userId']; 

try {
    $notification = new NotificationBrevoAndWeb($conn);
 
    echo json_encode([ 
        'getTemplateParams' => $notification->getTemplateParams(19),
        'sendNotificationBrevo' => $notification->sendNotificationBrevo([
            'email' => 'jooyassini@gmail.com',
            'templateId' => 19,
            'params' => [
                'username' => 'JOOYASSINI',
            ]
        ])
    ]);

    echo "getTemplateParams : 4";

    // $notification->sendNotificationSubscriptionFree($userId);

    // // response
    // echo json_encode([
    //     'status' => 'success',
    //     'message' => 'Notification envoyée avec succès',
    //     // 'templateId' => $notification->getTemplateId("registration-professional-free")
    // ]);

} catch (\Throwable $th) {
    echo json_encode([
        'status' => 'error',
        'message' => $th->getMessage()
    ]);
}
