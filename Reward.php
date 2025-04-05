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
$idReward = $_POST['id'];
 

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
 
// Fonction pour définir le type de contenu JSON
function setJsonHeader()
{
    header('Content-Type: application/json');
}
 