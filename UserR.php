<?php


// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


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

            // Vérifier $userId et $authorId sont egaux
            if ($userId == $authorId) {
                echo json_encode(["status" => "error", "message" => "L'utilisateur ne peut pas s'évaluer lui-même"]);
                exit;
            }

            // Vérifier si l'utilisateur a dejà un avis
            $stmt = $conn->prepare("SELECT COUNT(*) FROM user_reviews WHERE user_id = ? AND author_id = ?");
            $stmt->execute([$userId, $authorId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result['COUNT(*)'] > 0) {
                echo json_encode(["status" => "error", "message" => "L'utilisateur a déjà un avis"]);
                exit;
            }

            $id = generateGUID();
            
            $stmt = $conn->prepare("INSERT INTO user_reviews (id, user_id, author_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$id, $userId, $authorId, $rating, $comment]);

            echo json_encode(["status" => "success", "message" => "Avis ajouté avec succès"]);
            break;

        // Lire les avis d'un utilisateur
        case "get_user_reviews":
            $userId = $_POST['userId'] ?? null;
            if (!$userId) {
                echo json_encode(["status" => "error", "message" => "Paramètre 'userId' manquant"]);
                exit;
            }

            // Récupérer les avis avec user_name et user_avatar
            $stmt = $conn->prepare("SELECT user_reviews.*, ui.profiletype, ui.pseudo, ui.nomsociete, ui.photoprofilurl FROM user_reviews JOIN \"userInfo\" ui ON user_reviews.author_id = ui.userid WHERE user_reviews.user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$userId]);
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $review_ids = array_column($reviews, 'id');
            // mapper tout les id en 'id'
            $review_ids = array_map(function($id) {
                return "'" . $id . "'";
            }, $review_ids);

            // Récupérer les réponses des avis [les id sont en string]
            $stmt = $conn->prepare("SELECT * FROM review_replies WHERE review_id IN (" . implode(',', $review_ids) . ") ORDER BY created_at DESC");
            $stmt->execute();
            $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Ajouter les réponses aux avis
            foreach ($reviews as &$review) {
                $review['replies'] = array_filter($replies, function($reply) use ($review) {
                    return $reply['review_id'] === $review['id'];
                });
            }

            echo json_encode(["status" => "success", "reviews" => $reviews]);
            break;

        // Modifier un avis
        // case "update_user_reviews":

        //     $id = $_POST['id'] ?? null;
        //     $rating = $_POST['rating'] ?? null;
        //     $comment = $_POST['comment'] ?? '';

        //     if (!$id || !$rating) {
        //         echo json_encode(["status" => "error", "message" => "Champs requis manquants"]);
        //         exit;
        //     }

        //     $stmt = $conn->prepare("UPDATE user_reviews SET rating = ?, comment = ?, updated_at = NOW() WHERE id = ?");
        //     $stmt->execute([$rating, $comment, $id]);

        //     echo json_encode(["status" => "success", "message" => "Avis mis à jour"]);
        //     break;
            

        case "add_reply":
            $reviewId = $_POST['reviewId'] ?? null;
            $userId = $_POST['userId'] ?? null;
            $comment = $_POST['comment'] ?? '';

            if (!$reviewId || !$userId || !$comment) {
                echo json_encode(["status" => "error", "message" => "Champs requis manquants"]);
                exit;
            }

            // Vérifier si l'avis existe
            $stmt = $conn->prepare("SELECT * FROM user_reviews WHERE id = ?");
            $stmt->execute([$reviewId]);
            $review = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$review) {
                echo json_encode(["status" => "error", "message" => "L'avis n'existe pas"]);
                exit;
            }

            // Vérifier si l'utilisateur est le propriétaire de l'avis
            if ($review['user_id'] != $userId) {
                echo json_encode(["status" => "error", "message" => "Vous n'êtes pas autorisé à ajouter une réponse à cet avis"]);
                exit;
            }

            $id = generateGUID();

            $stmt = $conn->prepare("INSERT INTO review_replies (id, review_id, user_id, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$id, $reviewId, $userId, $comment]);

            echo json_encode(["status" => "success", "message" => "Réponse ajoutée"]);
            break;

        // Supprimer un avis
        // case "delete_user_reviews":
        //     $id = $_POST['id'] ?? null;
        //     if (!$id) {
        //         echo json_encode(["status" => "error", "message" => "Paramètre 'id' manquant"]);
        //         exit;
        //     }

        //     $stmt = $conn->prepare("DELETE FROM user_reviews WHERE id = ?");
        //     $stmt->execute([$id]);

        //     echo json_encode(["status" => "success", "message" => "Avis supprimé"]);
        //     break;

        // default:
        //     echo json_encode(["status" => "error", "message" => "Méthode inconnue"]);
        //     break;
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Erreur : " . $e->getMessage()]);
}
