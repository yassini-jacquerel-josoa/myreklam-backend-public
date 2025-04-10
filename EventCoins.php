<?php


include("./db.php");

$method = $_POST['Method'];
$idEventCoin = $_POST['id'];

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
 
if ($method == 'get_event_coins') {
    try {
        $query = "SELECT * FROM event_coins WHERE status = true ORDER BY rank ASC";
        $statement = $conn->prepare($query);
        $statement->execute();
        $eventCoins = $statement->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["status" => "success", "event_coins" => $eventCoins]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}


if ($method == 'verify_event_coin_already_validate') {
    try {
        $userid = $_POST['userid'];
        $slug = $_POST['slug'];

        $query = "SELECT * FROM history_coins WHERE userid = :userid AND slug = :slug";
        $statement = $conn->prepare($query);
        $statement->bindValue(':userid', $userid);
        $statement->bindValue(':slug', $slug);
        $statement->execute();
        $historyCoins = $statement->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["status" => "success", "history_coins" => $historyCoins]);
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
        $rank = $_POST['rank'];
        $icon = $_POST['icon'];
        $coins = $_POST['coins'];
        $status = $_POST['status'];
        if (!$slug || !$title || !$coins) {
            http_response_code(500);
            echo json_encode(["status" => "failure", "message" => "Donne manquant"]);
        }
        $query = "INSERT INTO event_coins (id, slug, title, description, coins, status , rank , icon) VALUES (:id, :slug, :title, :description, :coins, :status , :rank , :icon)";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $id);
        $statement->bindValue(':slug', $slug);
        $statement->bindValue(':title', $title);
        $statement->bindValue(':description', $description);
        $statement->bindValue(':coins', $coins);
        $statement->bindValue(':status', $status);
        $statement->bindValue(':rank', $rank);
        $statement->bindValue(':icon', $icon);
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

        $rank = $_POST['rank'];
        $icon = $_POST['icon'];

        if (!$idEventCoin) {
            http_response_code(500);
            echo json_encode(["status" => "failure", "message" => "Donne manquant"]);
        }

        $query = "UPDATE event_coins SET slug = :slug, title = :title, description = :description, coins = :coins, icon = :icon , rank = :rank, status = :status WHERE slug = :slug";
        $statement = $conn->prepare($query);
        $statement->bindValue(':slug', $slug);
        $statement->bindValue(':title', $title);
        $statement->bindValue(':description', $description);
        $statement->bindValue(':coins', $coins);
        $statement->bindValue(':status', $status);
        $statement->bindValue(':rank', $rank);
        $statement->bindValue(':icon', $icon);
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


// Fonction pour définir le type de contenu JSON
function setJsonHeader()
{
    header('Content-Type: application/json');
}
