<?php

// Bloquer l'accès direct depuis un navigateur en renvoyant une erreur 404
if ($_SERVER['REQUEST_METHOD'] === 'GET' && basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    http_response_code(404);
    exit;
}

include("./db.php");

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


    // try {
    //     $id = generateGUID();
    //     $userId = $_POST['userId'];
    //     $value = $_POST['value'];
    //     $updateAt = $_POST['updateAt'];
    //     $lastConversionAt = $_POST['lastConversionAt'];

    //     $query = "INSERT INTO user_coins (id, userId, value, updateAt, lastConversionAt) VALUES (:id, :userId, :value, :updateAt, :lastConversionAt)";
    //     $statement = $conn->prepare($query);
    //     $statement->bindValue(':id', $id);
    //     $statement->bindValue(':userId', $userId);
    //     $statement->bindValue(':value', $value);
    //     $statement->bindValue(':updateAt', $updateAt);
    //     $statement->bindValue(':lastConversionAt', $lastConversionAt);
    //     $statement->execute();

    //     echo json_encode(["status" => "success", "message" => "User coin created successfully"]);
    // } catch (\Throwable $th) {
    //     http_response_code(500);
    //     echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    // }
}

if ($method == 'update_user_coin') {
    try {

        $userId = $_POST['userId'];
        $value = $_POST['value'];
        $updateAt = $_POST['updateAt'];
        $lastConversionAt = $_POST['lastConversionAt'];

        $query = "UPDATE user_coins SET userId = :userId, value = :value, updateAt = :updateAt, lastConversionAt = :lastConversionAt WHERE id = :id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $idUserCoin);
        $statement->bindValue(':userId', $userId);
        $statement->bindValue(':value', $value);
        $statement->bindValue(':updateAt', $updateAt);
        $statement->bindValue(':lastConversionAt', $lastConversionAt);
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

    echo 2;
    try {


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

        $query = "SELECT * FROM event_coins WHERE slug=:slug";
        $statement = $conn->prepare($query);
        $statement->bindValue(':slug', $eventName);
        $statement->execute();
        $eventCoins = $statement->fetch(PDO::FETCH_ASSOC);
 
        if (!$eventCoins) {
            http_response_code(500);
            echo json_encode(["status" => "failure 2", "message" => "event coin introuvable"]);
            exit;
        }

        echo 33;

        $valueCoin = $eventCoins["coins"];
        $description = $eventCoins["description"];
        $generateBy = "web";
        // Générer un GUID pour l'historique
        $historyId = generateGUID();
        $createdAt = date('Y-m-d H:i:s'); // Date actuelle

        // 1. Vérifier si l'utilisateur a déjà une entrée dans `user_coins`
        $query = "SELECT id, value FROM user_coins WHERE userId = :userId";
        $statement = $conn->prepare($query);
        $statement->bindValue(':userId', $userId);
        $statement->execute();
        $userCoin = $statement->fetch(PDO::FETCH_ASSOC);
        echo 44;
        if ($userCoin) {
            // Mettre à jour les points de l'utilisateur
            $newValue = floatval($userCoin['value']) + floatval($valueCoin);
            $updateQuery = "UPDATE user_coins SET value = :value, updateAt = :updateAt WHERE id = :id";
            $updateStatement = $conn->prepare($updateQuery);
            $updateStatement->bindValue(':value', $newValue);
            $updateStatement->bindValue(':updateAt', $createdAt);
            $updateStatement->bindValue(':id', $userCoin['id']);
            $updateStatement->execute();
        } else {
            // Créer une nouvelle entrée pour l'utilisateur
            $userCoinId = generateGUID();
            $insertQuery = "INSERT INTO user_coins (id, userId, value, updateAt, lastConversionAt) VALUES (:id, :userId, :value, :updateAt, :lastConversionAt)";
            $insertStatement = $conn->prepare($insertQuery);
            $insertStatement->bindValue(':id', $userCoinId);
            $insertStatement->bindValue(':userId', $userId);
            $insertStatement->bindValue(':value', $valueCoin);
            $insertStatement->bindValue(':updateAt', $createdAt);
            $insertStatement->bindValue(':lastConversionAt', $createdAt); // Initialiser avec la même date
            $insertStatement->execute();
        }

        // 2. Ajouter une entrée dans `history_coins`
        $historyQuery = "INSERT INTO history_coins (id, userId, valueCoin, eventName, description, createdAt, generateBy) VALUES (:id, :userId, :valueCoin, :eventName, :description, :createdAt, :generateBy)";
        $historyStatement = $conn->prepare($historyQuery);
        $historyStatement->bindValue(':id', $historyId);
        $historyStatement->bindValue(':userId', $userId);
        $historyStatement->bindValue(':valueCoin', $valueCoin);
        $historyStatement->bindValue(':eventName', $eventName);
        $historyStatement->bindValue(':description', $description);
        $historyStatement->bindValue(':createdAt', $createdAt);
        $historyStatement->bindValue(':generateBy', $generateBy);
        $historyStatement->execute();

        // Retourner une réponse de succès
        echo json_encode(["status" => "success", "message" => "Event coin handled successfully"]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}


// Fonction pour définir le type de contenu JSON
function setJsonHeader()
{
    header('Content-Type: application/json');
}
