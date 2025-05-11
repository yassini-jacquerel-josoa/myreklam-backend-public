<?php
 
// Bloquer l'accès direct depuis un navigateur en renvoyant une erreur 404
if ($_SERVER['REQUEST_METHOD'] === 'GET' && basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    http_response_code(404);
    exit;
}
// Inclure la connexion à la base de données


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

include_once("./db.php");
 
$method = $_POST['Method']; // "create", "read", "update" ou "delete"
$type = $_POST['type'];  
$is_read = $_POST['is_read'] ? 1 : 0;  
$userId = $_POST['userId'];  
$metadata = $_POST['metadata'];  
$return_url = $_POST['return_url'];
$page = $_POST['Page'];
$content = htmlspecialchars($_POST['content'], ENT_QUOTES, 'UTF-8'); 
$searchbar = strtolower($_POST['Searchbar']);  
 
$statusSent = "sent";
$statusRead = "read";

$uploadDir = __DIR__ . '/assets/image/conversation/';


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


if ($method == 'create') {

    try {
        $dataNotification = [
            "id"=> generateGUID(),
            "user_id" => $userId,
            "content" => $content,
            "type" => $type,
            "is_read" => $is_read,
            "metadata" => $metadata,
            "return_url" => $return_url,
            "created_at" => date("Y-m-d h:i:s"),
            "updated_at" => date("Y-m-d h:i:s"),
        ];

        
        // Insertion des notification
        $query = "
            INSERT INTO \"notifications\" ( id , user_id, content, type , is_read , metadata , return_url, created_at , updated_at)
            VALUES (:id , :user_id, :content, :type , :is_read , :metadata , :return_url , :created_at , :updated_at)
        ";
        $statement = $conn->prepare($query);
        $statement->execute($dataNotification);
     
        if (!$statement->rowCount()) {
            throw new Exception("Erreur lors de l'ajout des participants à la notification.");

            exit;
        }

        echo json_encode(["status" => "success"]);
    } catch (\Throwable $th) {
        if ($conn->inTransaction()) {
            $conn->rollBack(); // Annulation de la transaction en cas d'erreur
        }

        http_response_code(500); // Erreur interne
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'update') {
    try {
        // Récupérer l'ID de la notification à mettre à jour
        $notificationId = $_POST['notificationId'];

        // Données à mettre à jour
        $dataNotification = [
            "content" => $content,
            "type" => $type,
            "is_read" => $is_read,
            "metadata" => $metadata,
            "return_url" => $return_url,
            "updated_at" => date("Y-m-d H:i:s"),
            "notificationId" => $notificationId,
        ];

        // Requête SQL pour mettre à jour la notification
        $query = "
            UPDATE notifications
            SET content = :content,
                type = :type,
                is_read = :is_read,
                metadata = :metadata,
                return_url = :return_url,
                updated_at = :updated_at
            WHERE id = :notificationId
        ";
        $statement = $conn->prepare($query);
        $statement->execute($dataNotification);

        if (!$statement->rowCount()) {
            throw new Exception("Aucune notification trouvée pour la mise à jour.");
        }

        echo json_encode(["status" => "success", "message" => "Notification mise à jour avec succès."]);
    } catch (\Throwable $th) {
        if ($conn->inTransaction()) {
            $conn->rollBack(); // Annulation de la transaction en cas d'erreur
        }

        http_response_code(500); // Erreur interne
        echo json_encode([
            "status" => "failure",
            "message" => $th->getMessage(),
        ]);
    }
}

if ($method == 'delete') {
    try {
        // Récupérer l'ID de la notification à supprimer
        $notificationId = $_POST['notificationId'];
 
        // Requête SQL pour supprimer la notification
        $query = "DELETE FROM notifications WHERE id = :notificationId AND user_id = :userId ";
        $statement = $conn->prepare($query);
        $statement->execute(["notificationId" => $notificationId , "userId" => $userId]);

        if (!$statement->rowCount()) {
            throw new Exception("Aucune notification trouvée pour la suppression.");
        }

        echo json_encode(["status" => "success", "message" => "Notification supprimée avec succès."]);
    } catch (\Throwable $th) {
        if ($conn->inTransaction()) {
            $conn->rollBack(); // Annulation de la transaction en cas d'erreur
        }

        http_response_code(500); // Erreur de serveur interne
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}
if ($method == 'delete_all') {
    try {
        // Récupérer l'ID de la notification à supprimer
        $notificationId = $_POST['notificationId'];

        // Requête SQL pour supprimer la notification
        $query = "DELETE FROM notifications WHERE user_id = :userId ";
        $statement = $conn->prepare($query);
        $statement->execute(["userId" => $userId]);

        if (!$statement->rowCount()) {
            throw new Exception("Aucune notification trouvée pour la suppression.");
        }

        echo json_encode(["status" => "success", "message" => "Notification supprimée avec succès."]);
    } catch (\Throwable $th) {
        if ($conn->inTransaction()) {
            $conn->rollBack(); // Annulation de la transaction en cas d'erreur
        }

        http_response_code(500); // Erreur de serveur interne
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'get_notification') {
        
    $page = filter_var($_POST["page"] ?? 1, FILTER_VALIDATE_INT);
    $pageSize = filter_var($_POST["pageSize"] ?? 20, FILTER_VALIDATE_INT);
    try {
        // Calculer l'offset pour la pagination
        $offset = ($page - 1) * $pageSize;

        // Requête SQL pour récupérer les notifications
        $query = "
            SELECT * FROM notifications
            WHERE user_id = :userId
            ORDER BY created_at DESC
            LIMIT :pageSize OFFSET :offset
        ";
        $statement = $conn->prepare($query);
        $statement->execute([
            "userId" => $userId,
            "pageSize" => $pageSize,
            "offset" => $offset,
        ]);

        $notifications = $statement->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["status" => "success", "data" => $notifications]);
    } catch (\Throwable $th) {
        http_response_code(500); // Erreur de serveur interne
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

 
// count notification not read for one user

if ($method == 'count_notification') {
    try {
        // Requête SQL pour compter les notifications non lues
        $query = "
            SELECT COUNT(*) as unread_count
            FROM notifications
            WHERE user_id = :userId AND is_read = false
        ";
        $statement = $conn->prepare($query);
        $statement->execute(["userId" => $userId]);

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        echo json_encode(["status" => "success", "unread_count" => $result['unread_count']]);
    } catch (\Throwable $th) {
        if ($conn->inTransaction()) {
            $conn->rollBack(); // Annulation de la transaction en cas d'erreur
        }

        http_response_code(500); // Erreur de serveur interne
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}


// update column is_read to read one notification

if ($method == 'read') {
    try {
        // Récupérer l'ID de la notification à marquer comme lue
        $notificationId = $_POST['notificationId'];

        // Requête SQL pour mettre à jour la notification
        $query = "
            UPDATE notifications
            SET is_read = true
            WHERE id = :notificationId AND user_id = :userId
        ";
        $statement = $conn->prepare($query);
        $statement->execute(["notificationId" => $notificationId , "userId" => $userId]);

        if (!$statement->rowCount()) {
            throw new Exception("Aucune notification trouvée pour la mise à jour.");
        }

        echo json_encode(["status" => "success", "message" => "Notification marquée comme lue."]);
    } catch (\Throwable $th) {
        if ($conn->inTransaction()) {
            $conn->rollBack(); // Annulation de la transaction en cas d'erreur
        }

        http_response_code(500); // Erreur de serveur interne
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

// update column is_read to read for notification user if not read

if ($method == 'read_all') {
    try {
        // Requête SQL pour marquer toutes les notifications comme lues
        $query = "
            UPDATE notifications
            SET is_read = true
            WHERE user_id = :userId AND is_read = false
        ";
        $statement = $conn->prepare($query);
        $statement->execute(["userId" => $userId]);

        echo json_encode(["status" => "success", "message" => "Toutes les notifications ont été marquées comme lues."]);
    } catch (\Throwable $th) {
        if ($conn->inTransaction()) {
            $conn->rollBack(); // Annulation de la transaction en cas d'erreur
        }

        http_response_code(500); // Erreur de serveur interne
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}
 


if ($method == 'count_notification_all') {
    try {
        // Requête SQL pour compter les notifications non lues
        $query = "
            SELECT COUNT(*) as count
            FROM notifications
            WHERE user_id = :userId 
        ";
        $statement = $conn->prepare($query);
        $statement->execute(["userId" => $userId]);

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        echo json_encode(["status" => "success", "count" => $result['count']]);
    } catch (\Throwable $th) {
        if ($conn->inTransaction()) {
            $conn->rollBack(); // Annulation de la transaction en cas d'erreur
        }

        http_response_code(500); // Erreur de serveur interne
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}