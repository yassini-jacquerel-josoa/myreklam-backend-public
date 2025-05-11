<?php

// Bloquer l'accès direct depuis un navigateur en renvoyant une erreur 404
if ($_SERVER['REQUEST_METHOD'] === 'GET' && basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    http_response_code(404);
    exit;
}
 
include_once("./db.php");

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
$idReward = $_POST['idReward'];
$idEventCoin = $_POST['idEventCoin'];
$idHistoryCoin = $_POST['idHistoryCoin'];
$idUserCoin = $_POST['idUserCoin'];
$idAmbassadorStatus = $_POST['idAmbassadorStatus'];

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
 
//  reward
 
if ($method == 'get_rewards') {
    try {
        $query = "SELECT * FROM rewards";
        $statement = $conn->prepare($query);
        $statement->execute();
        $rewards = $statement->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["status" => "success", "rewards" => $rewards]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'create_reward') {
    try {
        $id = generateGUID();
        $hexColor = $_POST['hexColor'];
        $valueMys = $_POST['valueMys'];
        $valueEuro = $_POST['valueEuro'];
        $isSpecial = $_POST['isSpecial'];
        $status = $_POST['status'];

        $query = "INSERT INTO rewards (id, hexColor, valueMys, valueEuro, isSpecial, status) VALUES (:id, :hexColor, :valueMys, :valueEuro, :isSpecial, :status)";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $id);
        $statement->bindValue(':hexColor', $hexColor);
        $statement->bindValue(':valueMys', $valueMys);
        $statement->bindValue(':valueEuro', $valueEuro);
        $statement->bindValue(':isSpecial', $isSpecial);
        $statement->bindValue(':status', $status);
        $statement->execute();

        echo json_encode(["status" => "success", "message" => "Reward created successfully"]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'update_reward') {
    try {
       
        $hexColor = $_POST['hexColor'];
        $valueMys = $_POST['valueMys'];
        $valueEuro = $_POST['valueEuro'];
        $isSpecial = $_POST['isSpecial'];
        $status = $_POST['status'];

        $query = "UPDATE rewards SET hexColor = :hexColor, valueMys = :valueMys, valueEuro = :valueEuro, isSpecial = :isSpecial, status = :status WHERE id = :id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $idReward);
        $statement->bindValue(':hexColor', $hexColor);
        $statement->bindValue(':valueMys', $valueMys);
        $statement->bindValue(':valueEuro', $valueEuro);
        $statement->bindValue(':isSpecial', $isSpecial);
        $statement->bindValue(':status', $status);
        $statement->execute();

        echo json_encode(["status" => "success", "message" => "Reward updated successfully"]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}


if ($method == 'delete_reward') {
    try { 
        $query = "DELETE FROM rewards WHERE id = :id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $idReward);
        $statement->execute();

        echo json_encode(["status" => "success", "message" => "Reward deleted successfully"]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

//  Ambassador Status


if ($method == 'get_ambassador_status') {
    try {
        $query = "SELECT * FROM ambassador_status";
        $statement = $conn->prepare($query);
        $statement->execute();
        $ambassadorStatus = $statement->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["status" => "success", "ambassador_status" => $ambassadorStatus]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'create_ambassador_status') {
    try {
        $id = generateGUID();
        $raise = $_POST['raise'];
        $title = $_POST['title'];
        $valueMys = $_POST['valueMys'];
        $star = $_POST['star'];
        $hexPrimaryColor = $_POST['hexPrimaryColor'];
        $hexSecondaryColor = $_POST['hexSecondaryColor'];
        $hexLightColor = $_POST['hexLightColor'];
        $minCoins = $_POST['minCoins'];
        $maxCoins = $_POST['maxCoins'];
        $status = $_POST['status'];

        $query = "INSERT INTO ambassador_status (id, raise, title, valueMys, star, hexPrimaryColor, hexSecondaryColor, hexLightColor, minCoins, maxCoins, status) VALUES (:id, :raise, :title, :valueMys, :star, :hexPrimaryColor, :hexSecondaryColor, :hexLightColor, :minCoins, :maxCoins, :status)";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $id);
        $statement->bindValue(':raise', $raise);
        $statement->bindValue(':title', $title);
        $statement->bindValue(':valueMys', $valueMys);
        $statement->bindValue(':star', $star);
        $statement->bindValue(':hexPrimaryColor', $hexPrimaryColor);
        $statement->bindValue(':hexSecondaryColor', $hexSecondaryColor);
        $statement->bindValue(':hexLightColor', $hexLightColor);
        $statement->bindValue(':minCoins', $minCoins);
        $statement->bindValue(':maxCoins', $maxCoins);
        $statement->bindValue(':status', $status);
        $statement->execute();

        echo json_encode(["status" => "success", "message" => "Ambassador status created successfully"]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}


if ($method == 'update_ambassador_status') {
    try {
        
        $raise = $_POST['raise'];
        $title = $_POST['title'];
        $valueMys = $_POST['valueMys'];
        $star = $_POST['star'];
        $hexPrimaryColor = $_POST['hexPrimaryColor'];
        $hexSecondaryColor = $_POST['hexSecondaryColor'];
        $hexLightColor = $_POST['hexLightColor'];
        $minCoins = $_POST['minCoins'];
        $maxCoins = $_POST['maxCoins'];
        $status = $_POST['status'];

        $query = "UPDATE ambassador_status SET raise = :raise, title = :title, valueMys = :valueMys, star = :star, hexPrimaryColor = :hexPrimaryColor, hexSecondaryColor = :hexSecondaryColor, hexLightColor = :hexLightColor, minCoins = :minCoins, maxCoins = :maxCoins, status = :status WHERE id = :id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $idAmbassadorStatus);
        $statement->bindValue(':raise', $raise);
        $statement->bindValue(':title', $title);
        $statement->bindValue(':valueMys', $valueMys);
        $statement->bindValue(':star', $star);
        $statement->bindValue(':hexPrimaryColor', $hexPrimaryColor);
        $statement->bindValue(':hexSecondaryColor', $hexSecondaryColor);
        $statement->bindValue(':hexLightColor', $hexLightColor);
        $statement->bindValue(':minCoins', $minCoins);
        $statement->bindValue(':maxCoins', $maxCoins);
        $statement->bindValue(':status', $status);
        $statement->execute();

        echo json_encode(["status" => "success", "message" => "Ambassador status updated successfully"]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'delete_ambassador_status') {
    try {
      
        $query = "DELETE FROM ambassador_status WHERE id = :id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $idAmbassadorStatus);
        $statement->execute();

        echo json_encode(["status" => "success", "message" => "Ambassador status deleted successfully"]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}


//  Event Coins

if ($method == 'get_event_coins') {
    try {
        $query = "SELECT * FROM event_coins";
        $statement = $conn->prepare($query);
        $statement->execute();
        $eventCoins = $statement->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["status" => "success", "event_coins" => $eventCoins]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'create_event_coin') {
    try {
        $id = generateGUID();
        $slug = $_POST['slug'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $coins = $_POST['coins'];
        $status = $_POST['status'];

        $query = "INSERT INTO event_coins (id, slug, title, description, coins, status) VALUES (:id, :slug, :title, :description, :coins, :status)";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $id);
        $statement->bindValue(':slug', $slug);
        $statement->bindValue(':title', $title);
        $statement->bindValue(':description', $description);
        $statement->bindValue(':coins', $coins);
        $statement->bindValue(':status', $status);
        $statement->execute();

        echo json_encode(["status" => "success", "message" => "Event coin created successfully"]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'update_event_coin') {
    try {
     
        $slug = $_POST['slug'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $coins = $_POST['coins'];
        $status = $_POST['status'];

        $query = "UPDATE event_coins SET slug = :slug, title = :title, description = :description, coins = :coins, status = :status WHERE id = :id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $idEventCoin);
        $statement->bindValue(':slug', $slug);
        $statement->bindValue(':title', $title);
        $statement->bindValue(':description', $description);
        $statement->bindValue(':coins', $coins);
        $statement->bindValue(':status', $status);
        $statement->execute();

        echo json_encode(["status" => "success", "message" => "Event coin updated successfully"]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'delete_event_coin') {
    try {
        
        $query = "DELETE FROM event_coins WHERE id = :id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $idEventCoin);
        $statement->execute();

        echo json_encode(["status" => "success", "message" => "Event coin deleted successfully"]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

//  User Coins

if ($method == 'get_user_coins') {
    try {
        $userId = $_POST['userId']; // Récupérer l'ID de l'utilisateur

        // Jointure avec la table `users` pour récupérer des informations supplémentaires
        $query = "
            SELECT uc.*, u.username, u.email 
            FROM user_coins uc
            JOIN users u ON uc.userId = u.id
            WHERE uc.userId = :userId
        ";
        $statement = $conn->prepare($query);
        $statement->bindValue(':userId', $userId);
        $statement->execute();
        $userCoins = $statement->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["status" => "success", "user_coins" => $userCoins]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'create_user_coin') {
    try {
        $id = generateGUID();
        $userId = $_POST['userId'];
        $value = $_POST['value'];
        $updateAt = $_POST['updateAt'];
        $lastConversionAt = $_POST['lastConversionAt'];

        $query = "INSERT INTO user_coins (id, userId, value, updateAt, lastConversionAt) VALUES (:id, :userId, :value, :updateAt, :lastConversionAt)";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $id);
        $statement->bindValue(':userId', $userId);
        $statement->bindValue(':value', $value);
        $statement->bindValue(':updateAt', $updateAt);
        $statement->bindValue(':lastConversionAt', $lastConversionAt);
        $statement->execute();

        echo json_encode(["status" => "success", "message" => "User coin created successfully"]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
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


//  History Coins

if ($method == 'get_history_coins') {
    try {
        $userId = $_POST['userId']; // Récupérer l'ID de l'utilisateur

        // Jointure avec la table `users` pour récupérer des informations supplémentaires
        $query = "
            SELECT hc.*, u.username, u.email 
            FROM history_coins hc
            JOIN users u ON hc.userId = u.id
            WHERE hc.userId = :userId
        ";
        $statement = $conn->prepare($query);
        $statement->bindValue(':userId', $userId);
        $statement->execute();
        $historyCoins = $statement->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["status" => "success", "history_coins" => $historyCoins]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'get_all_history_coins') {
    try {
        // Récupérer le numéro de page depuis la requête POST
        $page = isset($_POST['page']) ? (int)$_POST['page'] : 1; // Par défaut, page 1
        $limit = 20; // Nombre d'éléments par page

        // Calculer l'offset
        $offset = ($page - 1) * $limit;

        // Requête SQL avec pagination
        $query = "
            SELECT hc.*, u.username, u.email, ec.title AS eventTitle 
            FROM history_coins hc
            JOIN users u ON hc.userId = u.id
            LEFT JOIN event_coins ec ON hc.eventName = ec.slug
            ORDER BY hc.createdAt DESC
            LIMIT :limit OFFSET :offset
        ";
        $statement = $conn->prepare($query);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        $historyCoins = $statement->fetchAll(PDO::FETCH_ASSOC);

        // Récupérer le nombre total d'entrées pour la pagination
        $totalQuery = "SELECT COUNT(*) AS total FROM history_coins";
        $totalStatement = $conn->prepare($totalQuery);
        $totalStatement->execute();
        $totalResult = $totalStatement->fetch(PDO::FETCH_ASSOC);
        $totalEntries = (int)$totalResult['total'];

        // Calculer le nombre total de pages
        $totalPages = ceil($totalEntries / $limit);

        // Retourner les résultats avec les informations de pagination
        echo json_encode([
            "status" => "success",
            "history_coins" => $historyCoins,
            "pagination" => [
                "current_page" => $page,
                "total_pages" => $totalPages,
                "total_entries" => $totalEntries,
                "entries_per_page" => $limit
            ]
        ]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'create_history_coin') {
    try {
        $id = generateGUID();
        $userId = $_POST['userId'];
        $valueCoin = $_POST['valueCoin'];
        $eventName = $_POST['eventName'];
        $description = $_POST['description'];
        $createdAt = $_POST['createdAt'];
        $generateBy = $_POST['generateBy'];

        $query = "INSERT INTO history_coins (id, userId, valueCoin, eventName, description, createdAt, generateBy) VALUES (:id, :userId, :valueCoin, :eventName, :description, :createdAt, :generateBy)";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $id);
        $statement->bindValue(':userId', $userId);
        $statement->bindValue(':valueCoin', $valueCoin);
        $statement->bindValue(':eventName', $eventName);
        $statement->bindValue(':description', $description);
        $statement->bindValue(':createdAt', $createdAt);
        $statement->bindValue(':generateBy', $generateBy);
        $statement->execute();

        echo json_encode(["status" => "success", "message" => "History coin created successfully"]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'update_history_coin') {
    try {
   
        $userId = $_POST['userId'];
        $valueCoin = $_POST['valueCoin'];
        $eventName = $_POST['eventName'];
        $description = $_POST['description'];
        $createdAt = $_POST['createdAt'];
        $generateBy = $_POST['generateBy'];

        $query = "UPDATE history_coins SET userId = :userId, valueCoin = :valueCoin, eventName = :eventName, description = :description, createdAt = :createdAt, generateBy = :generateBy WHERE id = :id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $idHistoryCoin);
        $statement->bindValue(':userId', $userId);
        $statement->bindValue(':valueCoin', $valueCoin);
        $statement->bindValue(':eventName', $eventName);
        $statement->bindValue(':description', $description);
        $statement->bindValue(':createdAt', $createdAt);
        $statement->bindValue(':generateBy', $generateBy);
        $statement->execute();

        echo json_encode(["status" => "success", "message" => "History coin updated successfully"]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'delete_history_coin') {
    try {
        $query = "DELETE FROM history_coins WHERE id = :id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $idHistoryCoin);
        $statement->execute();

        echo json_encode(["status" => "success", "message" => "History coin deleted successfully"]);
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
 