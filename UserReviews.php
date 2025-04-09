<?php


// Bloquer l'accès direct depuis un navigateur en renvoyant une erreur 404
if ($_SERVER['REQUEST_METHOD'] === 'GET' && basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    http_response_code(404);
    exit;
}

// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");


include("./db.php");

// Si la méthode n'est pas POST, retourner un message simple et quitter
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(404);
    exit;
}

 
// Récupération de la méthode
$method = $_POST['Method'] ?? null;

if (!$method) {
    echo json_encode(["status" => "error", "message" => "Paramètre 'Method' manquant"]);
    exit;
}

try {
    switch ($method) {

        // Créer un avis
        case "create_user_reviews":
           
            $userId = $_POST['userId'] ?? null;
            $authorId = $_POST['authorId'] ?? null;
            $rating = $_POST['rating'] ?? null;
            $comment = $_POST['comment'] ?? '';

            if (!$userId || !$authorId || !$rating) {
                echo json_encode(["status" => "error", "message" => "Champs requis manquants"]);
                exit;
            }

            $stmt = $conn->prepare("INSERT INTO user_reviews (user_id, author_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$userId, $authorId, $rating, $comment]);

            echo json_encode(["status" => "success", "message" => "Avis ajouté avec succès"]);
            break;

        // Lire les avis d'un utilisateur
        case "get_user_reviews":
            $userId = $_POST['userId'] ?? null;
            if (!$userId) {
                echo json_encode(["status" => "error", "message" => "Paramètre 'userId' manquant"]);
                exit;
            }

            $stmt = $conn->prepare("SELECT * FROM user_reviews WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$userId]);
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(["status" => "success", "reviews" => $reviews]);
            break;

        // Modifier un avis
        case "update_user_reviews":
          
            $id = $_POST['id'] ?? null;
            $rating = $_POST['rating'] ?? null;
            $comment = $_POST['comment'] ?? '';

            if (!$id || !$rating) {
                echo json_encode(["status" => "error", "message" => "Champs requis manquants"]);
                exit;
            }

            $stmt = $conn->prepare("UPDATE user_reviews SET rating = ?, comment = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$rating, $comment, $id]);

            echo json_encode(["status" => "success", "message" => "Avis mis à jour"]);
            break;

        // Supprimer un avis
        case "delete_user_reviews":
            $id = $_POST['id'] ?? null;
            if (!$id) {
                echo json_encode(["status" => "error", "message" => "Paramètre 'id' manquant"]);
                exit;
            }

            $stmt = $conn->prepare("DELETE FROM user_reviews WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(["status" => "success", "message" => "Avis supprimé"]);
            break;

        default:
            echo json_encode(["status" => "error", "message" => "Méthode inconnue"]);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Erreur : " . $e->getMessage()]);
}