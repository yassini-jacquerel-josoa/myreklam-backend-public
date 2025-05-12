<?php


echo "getTemplateParams : 0";
 

include_once(__DIR__ . "/packages/NotificationBrevoAndWeb.php");

echo "getTemplateParams : 1";

$userId = $_POST['userId'];

echo "getTemplateParams : 2";

try {
    $notification = new NotificationBrevoAndWeb($conn);

    echo "getTemplateParams : 3";

    echo json_encode([ 
        'getTemplateParams' => $notification->getTemplateParams(19),
        // 'sendNotificationBrevo' => $notification->sendNotificationBrevo([
        //     'email' => 'jooyassini@gmail.com',
        //     'templateId' => 19,
        //     'params' => [
        //         'username' => 'JOOYASSINI',
        //     ]
        // ])
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
