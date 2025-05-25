<?php

// Bloquer l'accès direct depuis un navigateur en renvoyant une erreur 404
if ($_SERVER['REQUEST_METHOD'] === 'GET' && basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    http_response_code(404);
    exit;
}

include_once(__DIR__ . "/db.php");

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
$idEventCoin = $_POST['idEventCoin'];
$idHistoryCoin = $_POST['idHistoryCoin'];

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

//  History Coins

if ($method == 'get_history_coins') {
    try {
        $userid = $_POST['userid']; // Récupérer l'ID de l'utilisateur
        // Jointure avec la table `users` pour récupérer des informations supplémentaires
        $query = "
            SELECT ec.*
            FROM history_coins hc
            JOIN event_coins ec ON hc.eventname = ec.slug
            WHERE hc.userid = :userid
        ";
        $statement = $conn->prepare($query);
        $statement->bindValue(':userid', $userId);
        $statement->execute();
        $historyCoins = $statement->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["status" => "success", "history_coins" => $historyCoins, "query" => $statement->queryString]);
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
            SELECT hc.*, ec.title AS eventTitle 
            FROM history_coins hc
            JOIN \"userInfo\" u ON hc.userid = u.userid
            LEFT JOIN event_coins ec ON hc.eventname = ec.slug
            ORDER BY hc.createdat DESC
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

        $query = "INSERT INTO history_coins (id, userid, valuecoin, eventname, description, createdat, generateby) VALUES (:id, :userid, :valuecoin, :eventname, :description, :createdat, :generateby)";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $id);
        $statement->bindValue(':userid', $userId);
        $statement->bindValue(':valuecoin', $valueCoin);
        $statement->bindValue(':eventname', $eventName);
        $statement->bindValue(':description', $description);
        $statement->bindValue(':createdat', $createdAt);
        $statement->bindValue(':generateby', $generateBy);
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

        $query = "UPDATE history_coins SET userid = :userid, valuecoin = :valuecoin, eventname = :eventname, description = :description, createdat = :createdat, generateby = :generateby WHERE id = :id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $idHistoryCoin);
        $statement->bindValue(':userid', $userId);
        $statement->bindValue(':valuecoin', $valueCoin);
        $statement->bindValue(':eventname', $eventName);
        $statement->bindValue(':description', $description);
        $statement->bindValue(':createdat', $createdAt);
        $statement->bindValue(':generateby', $generateBy);
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
