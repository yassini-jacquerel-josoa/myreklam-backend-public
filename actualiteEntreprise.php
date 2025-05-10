<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    http_response_code(404);
    exit;
}

include('./db.php');
include('./packages/NotificationBrevoAndWeb.php');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

function generateGUID() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function setJsonHeader() {
    header('Content-Type: application/json');
}

function isJson($string) {
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

// 📸 Gestion d'image
// function handleImageUpload($inputName = 'urlphoto') {
//     if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
//         return null;
//     }

//     $targetDir = '/img/actualites/';
//     if (!is_dir($targetDir)) {
//         mkdir($targetDir, 0777, true);
//     }

//     $ext = pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION);
//     $newName = uniqid('photo_') . '.' . $ext;
//     $targetFile = $targetDir . $newName;

//     if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $targetFile)) {
//         return $targetFile;
//     }

//     return null;
// }

function handleImageUpload($inputName = 'urlphoto') {
    if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // ✅ Utilise un chemin absolu basé sur le dossier courant du script
    $targetDir = __DIR__ . '/img/actualites/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true); // Crée le dossier s'il n'existe pas
    }

    $ext = pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION);
    $newName = uniqid('photo_') . '.' . $ext;
    $targetFile = $targetDir . $newName;

    if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $targetFile)) {
        // ✅ Retourne le chemin relatif pour l'enregistrement (ex: "/img/actualites/photo_xxx.png")
        return '/img/actualites/' . $newName;
    }

    return null;
}


// 🔁 Action switch
$method = $_POST['Method'] ?? $_GET['Method'] ?? null;

switch ($method) {
    case 'CREATE':
        createActualite($conn);
        break;
    case 'READ':
        readActualites($conn);
        break;
    case 'UPDATE':
        updateActualite($conn);
        break;
    case 'DELETE':
        deleteActualite($conn);
        break;
    default:
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Méthode non supportée"]);
        break;
}

// 🧾 CREATE
function createActualite($conn) {
    setJsonHeader();

    $id = generateGUID();
    $userId = $_POST['userId'] ?? null;
    $title = $_POST['title'] ?? null;
    $url = $_POST['url'] ?? null;

    $photoPath = handleImageUpload();

    if (!$userId || !$title) {
        echo json_encode(["status" => "error", "message" => "Champs obligatoires manquants."]);
        return;
    }

    $stmt = $conn->prepare('INSERT INTO actualiteEntreprise (id, userId, title, url, urlphoto) VALUES (:id, :userId, :title, :url, :urlphoto)');
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':userId', $userId);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':url', $url);
    $stmt->bindParam(':urlphoto', $photoPath);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Actualité créée avec succès.", "id" => $id]);
    } else {
        echo json_encode(["status" => "error", "message" => "Échec de la création.", "error" => $stmt->errorInfo()]);
    }
}

// 📖 READ
function readActualites($conn) {
    setJsonHeader();

    $userId = $_POST['userId'] ?? null;

    if ($userId) {
        $stmt = $conn->prepare('SELECT * FROM actualiteEntreprise WHERE userId = :userId ORDER BY createdAt DESC');
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
    } else {
        $stmt = $conn->query('SELECT * FROM actualiteEntreprise ORDER BY createdAt DESC');
    }
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($result);
}

// ✏️ UPDATE
function updateActualite($conn) {
    setJsonHeader();

    $id = $_POST['id'] ?? null;
    if (!$id) {
        echo json_encode(["status" => "error", "message" => "ID requis pour la mise à jour."]);
        return;
    }

    $title = $_POST['title'] ?? null;
    $url = $_POST['url'] ?? null;
    $photoPath = handleImageUpload();

    $query = 'UPDATE actualiteEntreprise SET title = :title, url = :url';
    if ($photoPath) {
        $query .= ', urlphoto = :urlphoto';
    }
    $query .= ' WHERE id = :id';

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':url', $url);
    if ($photoPath) {
        $stmt->bindParam(':urlphoto', $photoPath);
    }
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Actualité mise à jour avec succès."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Échec de la mise à jour.", "error" => $stmt->errorInfo()]);
    }
}

// ❌ DELETE
function deleteActualite($conn) {
    setJsonHeader();

    $id = $_POST['id'] ?? null;
    if (!$id) {
        echo json_encode(["status" => "error", "message" => "ID requis pour la suppression."]);
        return;
    }

    $stmt = $conn->prepare('DELETE FROM actualiteEntreprise WHERE id = :id');
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Actualité supprimée avec succès."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Échec de la suppression.", "error" => $stmt->errorInfo()]);
    }
}
