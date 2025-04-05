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

 
// Fonction pour définir le type de contenu JSON
function setJsonHeader()
{
    header('Content-Type: application/json');
}
 