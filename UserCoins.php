<?php

// Bloquer l'accès direct depuis un navigateur en renvoyant une erreur 404
if ($_SERVER['REQUEST_METHOD'] === 'GET' && basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    http_response_code(404);
    exit;
}

include_once(__DIR__ . "/db.php");
include_once(__DIR__ . "/packages/NotificationBrevoAndWeb.php");
include_once(__DIR__ . "/packages/AmbassadorAction.php");

// Autoriser les requêtes depuis n'importe quel domaine
header("Access-Control-Allow-Origin: *");
// La requête est une pré-vérification CORS, donc retourner les en-têtes appropriés sans exécuter le reste du script
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Si la méthode n'est pas POST, retourner un message simple et quitter
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(404);
    exit;
}

$method = $_POST['Method'];
$idUserCoin = $_POST['id'];

function generateGUID()
{
    if (function_exists('com_create_guid')) {
        return trim(com_create_guid(), '{}');
    } else {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}

//  User Coins

if ($method == 'get_user_coins') {
    try {
        $userId = $_POST['userId']; // Récupérer l'ID de l'utilisateur

        // Jointure avec la table `users` pour récupérer des informations supplémentaires
        $query = "
            SELECT uc.* 
            FROM user_coins uc
            JOIN \"userInfo\"  u ON uc.userId = u.userId
            WHERE uc.userId = :userId
        ";
        $statement = $conn->prepare($query);
        $statement->bindValue(':userId', $userId);
        $statement->execute();
        $userCoins = $statement->fetch(PDO::FETCH_ASSOC);

        echo json_encode(["status" => "success", "user_coins" => $userCoins]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'create_user_coin') {

    $userId = $_POST['userId'];
    $eventName = $_POST['eventName'];

    handleEventCoin($userId, $eventName);
 
}

if ($method == 'update_user_coin') {
    try {

        $userId = $_POST['userId'];
        $value = $_POST['value'];
        $updateAt = $_POST['updateAt'];
        $lastConversionAt = $_POST['lastConversionAt'];

        $query = "UPDATE user_coins SET userid = :userid, value = :value, updateat = :updateat, lastconversionat = :lastconversionat WHERE id = :id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $idUserCoin);
        $statement->bindValue(':userid', $userId);
        $statement->bindValue(':value', $value);
        $statement->bindValue(':updateat', $updateAt);
        $statement->bindValue(':lastconversionat', $lastConversionAt);
        $statement->execute();

        echo json_encode(["status" => "success", "message" => "User coin updated successfully"]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'delete_user_coin') {
    try {

        $query = "DELETE FROM user_coins WHERE id = :id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $idUserCoin);
        $statement->execute();

        echo json_encode(["status" => "success", "message" => "User coin deleted successfully"]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}



function handleEventCoin($userId, $eventName)
{
    global $conn; // Assurez-vous que la connexion à la base de données est disponible

    try {
        // Utilisation de la classe EventCoinsFacade pour gérer les événements de coins
        $eventCoinsFacade = new EventCoinsFacade($conn);
        
        // Vérifier si l'utilisateur existe
        $query = "SELECT * FROM \"userInfo\" WHERE userId=:id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $userId);
        $statement->execute();
        $userExist = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$userExist) {
            http_response_code(500);
            echo json_encode(["status" => "failure", "message" => "Utilisateur introuvable"]);
            exit;
        }
        
        // Traitement de l'événement selon son type
        $result = false;
        
        switch ($eventName) {
            case 'complete_profile':
                $result = $eventCoinsFacade->completeProfile($userId);
                break;
            case 'share_ad_social_media':
                $result = $eventCoinsFacade->shareAdSocialMedia($userId);
                break;
            case 'post_google_review':
                $result = $eventCoinsFacade->postGoogleReview($userId);
                break;
            case 'follow_social_media':
                $result = $eventCoinsFacade->followSocialMedia($userId);
                break;
            case 'subscribe_newsletter':
                $result = $eventCoinsFacade->subscribeNewsletter($userId);
                break;
            case 'comment_ad':
                $result = $eventCoinsFacade->addComment($userId);
                break;
            case 'publish_ad':
                $result = $eventCoinsFacade->publishAd($userId);
                break;
            case 'apply_job_training':
                $result = $eventCoinsFacade->applyJobTraining($userId);
                break;
            case 'attend_event':
                $result = $eventCoinsFacade->attendEvent($userId);
                break;
            case 'leave_review_company':
                $result = $eventCoinsFacade->leaveReviewCompany($userId);
                break;
            default:
                // Fallback au comportement original pour les événements non gérés par EventCoinsFacade
                $query = "SELECT * FROM event_coins WHERE slug=:slug";
                $statement = $conn->prepare($query);
                $statement->bindValue(':slug', $eventName);
                $statement->execute();
                $eventCoins = $statement->fetch(PDO::FETCH_ASSOC);
         
                if (!$eventCoins) {
                    http_response_code(500);
                    echo json_encode(["status" => "failure", "message" => "event coin introuvable"]);
                    exit;
                }

                $valueCoin = $eventCoins["coins"];
                $description = $eventCoins["description"];
                $generateBy = "web";
                // Générer un GUID pour l'historique
                $historyId = generateGUID();
                $createdAt = date('Y-m-d H:i:s'); // Date actuelle

                // 1. Vérifier si l'utilisateur a déjà une entrée dans `user_coins`
                $query = "SELECT id, value FROM user_coins WHERE userid = :userid";
                $statement = $conn->prepare($query);
                $statement->bindValue(':userid', $userId);
                $statement->execute();
                $userCoin = $statement->fetch(PDO::FETCH_ASSOC);

                if ($userCoin) {
                    // Mettre à jour les points de l'utilisateur
                    $newValue = floatval($userCoin['value']) + floatval($valueCoin);
                    $updateQuery = "UPDATE user_coins SET value = :value, updateat = :updateat WHERE id = :id";
                    $updateStatement = $conn->prepare($updateQuery);
                    $updateStatement->bindValue(':value', $newValue);
                    $updateStatement->bindValue(':updateat', $createdAt);
                    $updateStatement->bindValue(':id', $userCoin['id']);
                    $updateStatement->execute();
                } else {
                    // Créer une nouvelle entrée pour l'utilisateur
                    $userCoinId = generateGUID();
                    $insertQuery = "INSERT INTO user_coins (id, userid, value, updateat, lastconversionat) VALUES (:id, :userid, :value, :updateat, :lastconversionat)";
                    $insertStatement = $conn->prepare($insertQuery);
                    $insertStatement->bindValue(':id', $userCoinId);
                    $insertStatement->bindValue(':userid', $userId);
                    $insertStatement->bindValue(':value', $valueCoin);
                    $insertStatement->bindValue(':updateat', $createdAt);
                    $insertStatement->bindValue(':lastconversionat', $createdAt); // Initialiser avec la même date
                    $insertStatement->execute();
                }

                // 2. Ajouter une entrée dans `history_coins`
                $historyQuery = "INSERT INTO history_coins (id, userid, valuecoin, eventname, description, createdat, generateby) VALUES (:id, :userid, :valuecoin, :eventname, :description, :createdat, :generateby)";
                $historyStatement = $conn->prepare($historyQuery);
                $historyStatement->bindValue(':id', $historyId);
                $historyStatement->bindValue(':userid', $userId);
                $historyStatement->bindValue(':valuecoin', $valueCoin);
                $historyStatement->bindValue(':eventname', $eventName);
                $historyStatement->bindValue(':description', $description);
                $historyStatement->bindValue(':createdat', $createdAt);
                $historyStatement->bindValue(':generateby', $generateBy);
                $historyStatement->execute();
                
                $result = true;
                break;
        }

        if ($result) {
            // Récupérer les informations de l'événement pour la notification
            $query = "SELECT coins, description FROM event_coins WHERE slug=:slug";
            $statement = $conn->prepare($query);
            $statement->bindValue(':slug', $eventName);
            $statement->execute();
            $eventCoins = $statement->fetch(PDO::FETCH_ASSOC);
            
            if ($eventCoins) {
                $valueCoin = $eventCoins["coins"];
                $description = $eventCoins["description"];
                
                // Envoyer une notification à l'utilisateur pour l'informer des coins gagnés
                $notificationManager = new NotificationBrevoAndWeb($conn);
                
                // Créer une notification dans la base de données
                $notifId = generateGUID();
                $query = 'INSERT INTO "notifications" ("id", "user_id", "content", "type", "is_read", "return_url") VALUES (:id, :user_id, :content, :type, :is_read, :return_url)';
                $statement = $conn->prepare($query);
                
                $type = "coins";
                $is_read = 0;
                $content = "Vous avez gagné " . $valueCoin . " coins pour l'action: " . $description;
                
                $statement->bindParam(':id', $notifId);
                $statement->bindParam(':user_id', $userId);
                $statement->bindParam(':content', $content);
                $statement->bindParam(':type', $type);
                $statement->bindParam(':is_read', $is_read);
                $statement->bindParam(':return_url', '/profil/coins');
                
                $statement->execute();
            }
            
            // Retourner une réponse de succès
            echo json_encode(["status" => "success", "message" => "Event coin handled successfully"]);
        } else {
            echo json_encode(["status" => "failure", "message" => "Impossible de traiter l'événement"]);
        }
    } catch (\Throwable $th) {
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }

    return false;
}


// Fonction pour définir le type de contenu JSON
function setJsonHeader()
{
    header('Content-Type: application/json');
}
