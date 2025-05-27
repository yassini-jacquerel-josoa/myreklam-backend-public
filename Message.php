<?php

// Bloquer l'accès direct depuis un navigateur en renvoyant une erreur 404
if ($_SERVER['REQUEST_METHOD'] === 'GET' && basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    http_response_code(404);
    exit;
}
// Inclure la connexion à la base de données
require_once './SendMail.php';
include_once(__DIR__ . "/db.php");
include_once(__DIR__ . "/packages/NotificationBrevoAndWeb.php");


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

// Récupérer les données du formulaire
$method = $_POST['Method']; // "create", "read", "update" ou "delete"
$idConversation = $_POST['idConversation'];
// $idMessage = $_POST['idMessage'];  
$userId = $_POST['senderId'];
$owner_id = trim($_POST['senderId']);
$receiver_id = $_POST['receiverId'];
$offre_id = $_POST['offreId'];
$data = $_POST['Data'];
$page = $_POST['Page'];
$content = $_POST['message'];
$searchbar = strtolower($_POST['Searchbar']);


$statusSent = "sent";
$statusRead = "read";

$uploadDir = __DIR__ . '/assets/image/conversation/';







if ($method == 'create') {

    try {
        if (!$idConversation) {
            http_response_code(500); // Erreur interne
            echo json_encode(["status" => "failure", "message" => "idConversation manquant"]);
        }
        $conn->beginTransaction(); // Démarrage de la transaction

        // Vérifier si l'utilisateur a déjà démarré une conversation sur cette offre
        $query = "
                SELECT * FROM \"conversations\" WHERE id = :id 
                ";

        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $idConversation);

        $statement->execute();

        $existingConversation = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$existingConversation) {
            http_response_code(500); // Forbidden (Accès interdit)
            echo json_encode([
                "status" => "failure",
                "message" => "Conversation introuvable",
                $existingConversation
            ]);
            exit;
        }

        $query = "SELECT * FROM \"ads\" WHERE id = :offre_id AND deletedat IS NULL";
        $statement = $conn->prepare($query);
        $statement->bindValue(':offre_id', $existingConversation["offre_id"]);
        $statement->execute();

        $offre = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$offre) {
            http_response_code(500); // Erreur interne
            echo json_encode([
                "status" => "failure",
                "message" => "Offre introuvable"
            ]);
            exit;
        }


        $dataConversationParticipants = [
            "id" => generateGUID(),
            "conversation_id" => $idConversation,
            "user_id" => $receiver_id,
            "owner_id" => $owner_id,
            "joined_at" => date("Y-m-d h:i:s"),
        ];


        // Insertion des participants
        $query = "
                INSERT INTO \"conversation_participants\" (id , conversation_id, user_id, owner_id, joined_at)
                VALUES (:id , :conversation_id, :user_id, :owner_id, :joined_at)
            ";
        $statement = $conn->prepare($query);
        $statement->execute($dataConversationParticipants);

        if (!$statement->rowCount()) {
            throw new Exception("Erreur lors de l'ajout des participants à la conversation.");
        }

        $dataMessage = [
            "id" => generateGUID(),
            "conversation_id" => $idConversation,
            "sender_id" => $owner_id,
            "receiver_id" => $receiver_id,
            "content" => $content,
            "status" => $statusSent,
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s"),
        ];

        // Insertion du message
        $query = "
                INSERT INTO \"messages\" (id ,conversation_id, sender_id, content, status, created_at, updated_at , receiver_id)
                VALUES (:id , :conversation_id, :sender_id, :content, :status, :created_at, :updated_at , :receiver_id)
            ";
        $statement = $conn->prepare($query);
        $statement->execute($dataMessage);

        $idMessage = $dataMessage['id'];
        if (!$idMessage) {
            throw new Exception("Erreur lors de la création du message.");
        }


        if (!empty($_POST["attachments"]) && is_array($_POST["attachments"])) {
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true); // Créer le répertoire s'il n'existe pas
            }
            if (!is_writable($uploadDir)) {
                throw new Exception("Le dossier n'est pas accessible en écriture.");
            }
            foreach ($_POST['attachments'] as $attachment) {
                if (!empty($attachment["file_url"])) {
                    $base64String = $attachment["file_url"];
                    $fileType = $attachment["file_type"] ?? 'jpg'; // Par défaut, type JPG si non spécifié
                    $file_name = $attachment["file_name"] ?? ''; // Par défaut, type JPG si non spécifié


                    // Extraire l'extension du fichier (ex: "png" à partir de "image/png")
                    $fileExtension = explode('/', $fileType)[1];

                    // Générer un nom de fichier unique
                    $timestamp = date('Ymd_His');
                    $randomString = bin2hex(random_bytes(5)); // Génère une chaîne aléatoire
                    $fileName = $timestamp . '_' . $randomString . '.' . $fileExtension;

                    // Chemin complet pour l'enregistrement local
                    $filePath = $uploadDir . $fileName;

                    // Chemin relatif pour la base de données
                    $relativePath = '/assets/image/conversation/' . $fileName;

                    if (strpos($base64String, 'base64,') !== false) {
                        $base64String = explode('base64,', $base64String)[1];
                    }

                    // Décoder et enregistrer le fichier sur le disque
                    $decodedFile = base64_decode($base64String);
                    if ($decodedFile === false) {
                        echo ("Impossible de décoder le fichier Base64.");
                        exit;
                    }

                    $result = file_put_contents($filePath, $decodedFile);
                    if ($result === false) {
                        echo ("Impossible d'enregistrer le fichier sur le disque.");
                        exit;
                    }

                    // Préparer les données pour la base de données
                    $dataAttachment = [
                        "id" => generateGUID(),
                        "message_id" => $idMessage,
                        "file_url" => $relativePath,
                        "file_type" => $fileType,
                        "file_name" => $file_name,
                        "created_at" => date("Y-m-d h:i:s"),
                    ];

                    // Insertion dans la table des pièces jointes
                    $query = "
                                INSERT INTO \"attachments\" (id, message_id, file_url, file_type, file_name, created_at)
                                VALUES (:id, :message_id, :file_url, :file_type, :file_name, :created_at)
                            ";
                    $statement = $conn->prepare($query);
                    $statement->execute($dataAttachment);
                }
            }
        }

        $sender = getPseudoUser($conn, $owner_id);
        $receiver = getPseudoUser($conn, $receiver_id);

        // Envoyer un email
        sendMail($receiver["email"], $receiver["pseudo"], $offre['userId'] == $owner_id ? 8 : 7, ["sender" => $sender["pseudo"], "value" => "Acusation de reception de message"]);

        // Ajouter une notification pour le message reçu
        $notificationManager = new NotificationBrevoAndWeb($conn);
        $notificationManager->sendNotificationAdMessage($receiver_id, $offre['id'], $owner_id, $content);

        $conn->commit(); // Validation de la transaction
        echo json_encode(["status" => "success"]);
    } catch (\Throwable $th) {
        http_response_code(500); // Erreur interne
        echo json_encode([
            "status" => "failure",
            "message" => $th->getMessage(),
        ]);
        exit;
    }
}


if ($method == 'start_conversation') {

    try {

        if (!$offre_id || !$owner_id) {
            http_response_code(500); // Erreur interne
            echo json_encode([
                "status" => "failure",
                "message" => " Offre introuvable ",
            ]);
            exit;
        };

        // id = '$offre_id'

        $query = "SELECT * FROM \"ads\" WHERE id = :offre_id AND deletedat IS NULL";
        $statement = $conn->prepare($query);
        $statement->bindValue(':offre_id', $offre_id);
        $statement->execute();

        $offre = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$offre) {
            http_response_code(500); // Erreur interne
            echo json_encode([
                "status" => "failure",
                "message" => "Offre introuvable"
            ]);
            exit;
        }

        // Vérifier si l'utilisateur essaie de démarrer une conversation sur l'offre qu'il a créée
        if ($offre['userId'] == $owner_id) {
            http_response_code(403); // Forbidden (Accès interdit)
            echo json_encode([
                "status" => "failure",
                "message" => "Vous ne pouvez pas démarrer une conversation sur votre propre offre."
            ]);
            exit;
        }

        // Vérifier si l'utilisateur a déjà démarré une conversation sur cette offre
        $query = "
            SELECT id FROM \"conversations\" WHERE owner_id = :owner_id AND offre_id = :offre_id
            ";
        $statement = $conn->prepare($query);
        $statement->bindValue(':owner_id', $owner_id);
        $statement->bindValue(':offre_id', $offre_id);
        $statement->execute();

        $existingConversation = $statement->fetch(PDO::FETCH_ASSOC);

        if ($existingConversation) {
            http_response_code(403); // Forbidden (Accès interdit)
            echo json_encode([
                "status" => "failure",
                "message" => "Vous avez déjà démarré une conversation pour cette offre."
            ]);
            exit;
        }

        $dataConversation = [
            "id" => generateGUID(),
            "created_at" => date("Y-m-d h:i:s"),
            "updated_at" => date("Y-m-d h:i:s"),
            "owner_id" => $owner_id,
            "offre_id" => $offre_id
        ];

        $query = "
            INSERT INTO \"conversations\" (id , created_at, updated_at, owner_id , offre_id)
            VALUES (:id , :created_at, :updated_at, :owner_id , :offre_id)
            RETURNING id
        ";
        $statement = $conn->prepare($query);
        $statement->execute($dataConversation);

        // Récupération de l'ID inséré
        $idConversation = $statement->fetchColumn();


        if (!$idConversation) {
            throw new Exception("Erreur lors de l'insertion de la conversation.");
        }

        $dataConversationParticipants = [
            "id" => generateGUID(),
            "conversation_id" => $idConversation,
            "user_id" => $offre['userId'],
            "owner_id" => $owner_id,
            "joined_at" => date("Y-m-d h:i:s"),
        ];

        // Insertion des participants
        $query = "
            INSERT INTO \"conversation_participants\" (id ,conversation_id, user_id, owner_id, joined_at)
            VALUES (:id , :conversation_id, :user_id, :owner_id, :joined_at)
        ";
        $statement = $conn->prepare($query);
        $statement->execute($dataConversationParticipants);

        // Récupération des détails de la conversation
        $query = "
            SELECT * 
            FROM \"conversations\" 
            WHERE id = :id
        ";
        $statement = $conn->prepare($query);
        $statement->execute(['id' => $idConversation]);
        $conversation = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$conversation) {
            throw new Exception("Erreur lors de la récupération de la conversation insérée.");
        }

        // Retourner les détails de la conversation
        echo json_encode([
            "status" => "success",
            "conversation" => $conversation,
        ]);
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
        if ($idConversation) {
            // Vérifier si la conversation existe
            $query = "SELECT * FROM \"conversations\" WHERE id = :id";
            $statement = $conn->prepare($query);
            $statement->bindValue(':id', $idConversation);
            $statement->execute();
            $existingConversation = $statement->fetch(PDO::FETCH_ASSOC);

            if (!$existingConversation) {
                http_response_code(404);
                echo json_encode([
                    "status" => "failure",
                    "message" => "Conversation introuvable"
                ]);
                exit;
            }

            // Démarrer une transaction
            $conn->beginTransaction();

            // Vérifier si l'utilisateur a déjà une entrée de suppression
            $checkQuery = "
                SELECT * FROM \"conversation_deleted\" 
                WHERE conversation_id = :conversationId 
                AND participant_user_id = :userId
            ";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->execute([
                'conversationId' => $idConversation,
                'userId' => $userId
            ]);
            $existingDeletion = $checkStmt->fetch(PDO::FETCH_ASSOC);

            $currentDate = date("Y-m-d H:i:s");

            if ($existingDeletion) {
                // Mettre à jour la date de suppression existante
                $updateQuery = "
                    UPDATE \"conversation_deleted\"
                    SET deleted_until = :deletedUntil
                    WHERE id = :id
                ";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->execute([
                    'deletedUntil' => $currentDate,
                    'id' => $existingDeletion['id']
                ]);
            } else {
                // Créer une nouvelle entrée de suppression
                $insertQuery = "
                    INSERT INTO \"conversation_deleted\" 
                    (id, conversation_id, participant_user_id, deleted_until) 
                    VALUES (:deleteId, :idConversation, :participantUserId, :deletedUntil)
                ";
                $insertStmt = $conn->prepare($insertQuery);
                $insertStmt->execute([
                    'deleteId' => generateGUID(),
                    'idConversation' => $idConversation,
                    'participantUserId' => $userId,
                    'deletedUntil' => $currentDate
                ]);
            }

            // Valider la transaction
            $conn->commit();

            echo json_encode([
                "status" => "success",
                "message" => "Conversation masquée avec succès"
            ]);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "failure", "message" => "Conversation not found"]);
        }
    } catch (\Throwable $th) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

function getInterlocutorId($conversationId, $userId)
{
    global $conn;

    // Recherche dans la table conversation_participants pour trouver l'autre participant
    $query = "
        SELECT user_id, owner_id
        FROM \"conversation_participants\"
        WHERE conversation_id = :conversationId
    ";
    $statement = $conn->prepare($query);
    $statement->bindValue(':conversationId', $conversationId);
    $statement->execute();
    $participants = $statement->fetchAll(PDO::FETCH_ASSOC);

    // Déterminer l'interlocuteur : si l'utilisateur est le propriétaire, l'autre utilisateur est l'interlocuteur
    foreach ($participants as $participant) {
        if ($participant['owner_id'] == $userId) {
            return $participant['user_id']; // L'interlocuteur est l'utilisateur participant
        } elseif ($participant['user_id'] == $userId) {
            return $participant['owner_id']; // L'interlocuteur est le propriétaire
        }
    }

    // Si aucun interlocuteur n'est trouvé, retourner null
    return null;
}


if ($method == 'get_conversation') {
    $search = $_POST["search"] ?? "";
    $page = filter_var($_POST["page"] ?? 1, FILTER_VALIDATE_INT);
    $pageSize = filter_var($_POST["pageSize"] ?? 20, FILTER_VALIDATE_INT);
    $userId = filter_var($owner_id ?? 1);

    $page = $page > 0 ? $page : 1;
    $pageSize = $pageSize > 0 ? $pageSize : 20;
    $offset = ($page - 1) * $pageSize;

    try {
        // Récupération des conversations avec la nouvelle logique de suppression
        $query = "
            SELECT c.id AS conversation_id, c.offre_id, c.updated_at,
                   cd.deleted_until AS user_deleted_until
            FROM \"conversations\" c
            LEFT JOIN \"conversation_participants\" cp ON cp.conversation_id = c.id
            LEFT JOIN \"conversation_deleted\" cd ON cd.conversation_id = c.id AND cd.participant_user_id = :myId
            WHERE c.owner_id = :myId 
            GROUP BY c.id, c.offre_id, c.updated_at, cd.deleted_until
            ORDER BY c.updated_at DESC
            LIMIT :pageSize OFFSET :offset
        ";
        
        $statement = $conn->prepare($query);
        $statement->bindValue(':myId', $userId);
        $statement->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        $conversations = $statement->fetchAll(PDO::FETCH_ASSOC);

        $conversationData = [];

        foreach ($conversations as $conversation) {
            // Récupérer le dernier message après la date de suppression (si elle existe)
            $deletedUntil = $conversation['user_deleted_until'] ?? null;
            
            if ($deletedUntil) {
                $queryLastMessage = "
                    SELECT m.id, m.content, m.status, m.created_at
                    FROM \"messages\" m
                    WHERE m.conversation_id = :conversationId
                    AND m.created_at > :deletedUntil
                    ORDER BY m.created_at DESC
                    LIMIT 1
                ";
                $stmtLastMessage = $conn->prepare($queryLastMessage);
                $stmtLastMessage->bindValue(':conversationId', $conversation['conversation_id']);
                $stmtLastMessage->bindValue(':deletedUntil', $deletedUntil);
            } else {
                $queryLastMessage = "
                    SELECT m.id, m.content, m.status, m.created_at
                    FROM \"messages\" m
                    WHERE m.conversation_id = :conversationId
                    ORDER BY m.created_at DESC
                    LIMIT 1
                ";
                $stmtLastMessage = $conn->prepare($queryLastMessage);
                $stmtLastMessage->bindValue(':conversationId', $conversation['conversation_id']);
            }
            
            $stmtLastMessage->execute();
            $message = $stmtLastMessage->fetch(PDO::FETCH_ASSOC);

            // Si aucun message n'est visible, sauter cette conversation
            if (!$message) {
                continue;
            }

            // Récupérer les pièces jointes
            $attachments = [];
            if ($message) {
                $queryAttachments = "
                    SELECT * FROM \"attachments\"
                    WHERE message_id = :messageId
                ";
                $stmtAttachments = $conn->prepare($queryAttachments);
                $stmtAttachments->bindValue(':messageId', $message['id']);
                $stmtAttachments->execute();
                $attachments = $stmtAttachments->fetchAll(PDO::FETCH_ASSOC);
            }

            // Récupérer l'interlocuteur
            $interlocutorId = getInterlocutorId($conversation['conversation_id'], $userId);
            $interlocutor = null;
            $userInfo = null;

            if ($interlocutorId) {
                $queryInterlocutor = "
                    SELECT * FROM \"userInfo\" WHERE userid = :interlocutorId
                ";
                $stmtInterlocutor = $conn->prepare($queryInterlocutor);
                $stmtInterlocutor->bindValue(':interlocutorId', $interlocutorId);
                $stmtInterlocutor->execute();
                $interlocutor = $stmtInterlocutor->fetch(PDO::FETCH_ASSOC);

                $queryUserInfo = 'SELECT * FROM "users" WHERE "Id" = :interlocutorId';
                $stmtUserInfo = $conn->prepare($queryUserInfo);
                $stmtUserInfo->bindValue(':interlocutorId', $interlocutorId);
                $stmtUserInfo->execute();
                $userInfo = $stmtUserInfo->fetch(PDO::FETCH_ASSOC);
            }

            $interlocutorUserName = "Pseudo";
            if ($interlocutor) {
                if (isset($interlocutor['profiletype']) && $interlocutor['profiletype'] === "professionnel") {
                    $interlocutorUserName = $interlocutor['nomsociete'] ?? $interlocutor['pseudo'];
                } else {
                    $interlocutorUserName = $interlocutor['pseudo'];
                }
            } elseif ($userInfo && isset($userInfo['Email'])) {
                $interlocutorUserName = explode('@', $userInfo['Email'])[0];
            }

            // Récupérer l'offre
            $offer = null;
            if ($conversation['offre_id']) {
                $queryOffer = "
                    SELECT * FROM \"ads\"
                    WHERE id = :offreId AND deletedat IS NULL
                ";
                $stmtOffer = $conn->prepare($queryOffer);
                $stmtOffer->bindValue(':offreId', $conversation['offre_id']);
                $stmtOffer->execute();
                $offer = $stmtOffer->fetch(PDO::FETCH_ASSOC);
            }

            $conversationData[] = [
                "id" => $conversation['conversation_id'],
                "message" => $message ? [
                    "id" => $message['id'],
                    "content" => $message['content'],
                    "status" => $message['status'],
                    "created_at" => $message['created_at'],
                    "attachments" => $attachments
                ] : null,
                "interlocutor" => [
                    "id" => $interlocutorId,
                    "username" => $interlocutorUserName,
                    "photo" => $interlocutor['photoprofilurl'] ?? null
                ],
                "announcement" => $offer ? [
                    "id" => $offer['id'],
                    "name" => $offer['category'] == "demandes" ? $offer['inquiryTitle'] : $offer['title'],
                    "status" => "valid"
                ] : [
                    "id" => null,
                    "name" => null,
                    "status" => "deleted"
                ]
            ];
        }

        echo json_encode(["status" => "success", "conversations" => $conversationData]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'get_message') {
    $page = filter_var($_POST["page"] ?? 1, FILTER_VALIDATE_INT);
    $pageSize = filter_var($_POST["pageSize"] ?? 20, FILTER_VALIDATE_INT);
    $idConversation = filter_var($_POST["idConversation"] ?? null);
    $userId = filter_var($owner_id ?? null);

    $page = max(1, $page);
    $pageSize = max(1, $pageSize);

    if (!$idConversation || !$userId) {
        http_response_code(400);
        echo json_encode(["status" => "failure", "message" => "Conversation ID and User ID are required"]);
        exit;
    }

    try {
        // Vérifier si la conversation existe
        $queryConversation = "
            SELECT * FROM \"conversations\" WHERE id = :id
        ";
        $stmtConversation = $conn->prepare($queryConversation);
        $stmtConversation->bindValue(':id', $idConversation);
        $stmtConversation->execute();
        $existingConversation = $stmtConversation->fetch(PDO::FETCH_ASSOC);

        if (!$existingConversation) {
            http_response_code(404);
            echo json_encode(["status" => "failure", "message" => "Conversation introuvable"]);
            exit;
        }

        // Récupérer la date jusqu'à laquelle l'utilisateur a supprimé les messages
        $queryDeletion = "
            SELECT deleted_until FROM \"conversation_deleted\"
            WHERE conversation_id = :conversationId AND participant_user_id = :userId
        ";
        $stmtDeletion = $conn->prepare($queryDeletion);
        $stmtDeletion->bindValue(':conversationId', $idConversation);
        $stmtDeletion->bindValue(':userId', $userId);
        $stmtDeletion->execute();
        $deletionInfo = $stmtDeletion->fetch(PDO::FETCH_ASSOC);
        $deletedUntil = $deletionInfo['deleted_until'] ?? null;

        // Récupérer l'interlocuteur
        $interlocutorId = getInterlocutorId($idConversation, $userId);
        $interlocutor = null;
        $userInfo = null;

        if ($interlocutorId) {
            $queryInterlocutor = "
                SELECT * FROM \"userInfo\" WHERE userid = :interlocutorId
            ";
            $stmtInterlocutor = $conn->prepare($queryInterlocutor);
            $stmtInterlocutor->bindValue(':interlocutorId', $interlocutorId);
            $stmtInterlocutor->execute();
            $interlocutor = $stmtInterlocutor->fetch(PDO::FETCH_ASSOC);

            $queryUserInfo = 'SELECT * FROM "users" WHERE "Id" = :interlocutorId';
            $stmtUserInfo = $conn->prepare($queryUserInfo);
            $stmtUserInfo->bindValue(':interlocutorId', $interlocutorId);
            $stmtUserInfo->execute();
            $userInfo = $stmtUserInfo->fetch(PDO::FETCH_ASSOC);
        }

        $interlocutorUserName = "Pseudo";
        if ($interlocutor) {
            if (isset($interlocutor['profiletype']) && $interlocutor['profiletype'] === "professionnel") {
                $interlocutorUserName = $interlocutor['nomsociete'] ?? $interlocutor['pseudo'];
            } else {
                $interlocutorUserName = $interlocutor['pseudo'];
            }
        } elseif ($userInfo && isset($userInfo['Email'])) {
            $interlocutorUserName = explode('@', $userInfo['Email'])[0];
        }

        // Récupérer l'offre
        $offer = null;
        if ($existingConversation['offre_id']) {
            $queryOffer = "
                SELECT * FROM \"ads\"
                WHERE id = :offreId AND deletedat IS NULL
            ";
            $stmtOffer = $conn->prepare($queryOffer);
            $stmtOffer->bindValue(':offreId', $existingConversation['offre_id']);
            $stmtOffer->execute();
            $offer = $stmtOffer->fetch(PDO::FETCH_ASSOC);
        }

        // Récupérer les messages (seulement ceux après la date de suppression si elle existe)
        $offset = ($page - 1) * $pageSize;
        
        if ($deletedUntil) {
            $queryMessages = "
                SELECT id, sender_id, receiver_id, content, status, created_at
                FROM \"messages\"
                WHERE conversation_id = :conversationId
                AND created_at > :deletedUntil
                ORDER BY created_at DESC
                LIMIT :pageSize OFFSET :offset
            ";
            $stmtMessages = $conn->prepare($queryMessages);
            $stmtMessages->bindValue(':conversationId', $idConversation);
            $stmtMessages->bindValue(':deletedUntil', $deletedUntil);
            $stmtMessages->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);
            $stmtMessages->bindValue(':offset', $offset, PDO::PARAM_INT);
        } else {
            $queryMessages = "
                SELECT id, sender_id, receiver_id, content, status, created_at
                FROM \"messages\"
                WHERE conversation_id = :conversationId
                ORDER BY created_at DESC
                LIMIT :pageSize OFFSET :offset
            ";
            $stmtMessages = $conn->prepare($queryMessages);
            $stmtMessages->bindValue(':conversationId', $idConversation);
            $stmtMessages->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);
            $stmtMessages->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        
        $stmtMessages->execute();
        $messages = $stmtMessages->fetchAll(PDO::FETCH_ASSOC);

        // Mettre à jour le statut des messages non lus et récupérer les pièces jointes
        foreach ($messages as &$message) {
            if ($message["status"] === $statusSent && $userId == $message["receiver_id"]) {
                $updateQuery = "
                    UPDATE \"messages\"
                    SET status = :statusRead
                    WHERE id = :messageId
                ";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bindValue(':statusRead', $statusRead);
                $updateStmt->bindValue(':messageId', $message['id']);
                $updateStmt->execute();
                
                $message["status"] = $statusRead;
            }

            // Récupérer les pièces jointes
            $queryAttachments = "
                SELECT * FROM \"attachments\"
                WHERE message_id = :messageId
            ";
            $stmtAttachments = $conn->prepare($queryAttachments);
            $stmtAttachments->bindValue(':messageId', $message['id']);
            $stmtAttachments->execute();
            $message["attachments"] = $stmtAttachments->fetchAll(PDO::FETCH_ASSOC);
        }

        // Inverser l'ordre pour avoir les plus anciens en premier
        $messages = array_reverse($messages);

        // Préparer la réponse
        $response = [
            "interlocutor" => [
                "id" => $interlocutorId,
                "username" => $interlocutorUserName,
                "photo" => $interlocutor['photoprofilurl'] ?? null
            ],
            "messages" => $messages,
            "announcement" => $offer ? [
                "id" => $offer['id'],
                "name" => $offer['category'] == "demandes" ? $offer['inquiryTitle'] : $offer['title'],
                "status" => "valid"
            ] : [
                "id" => null,
                "name" => null,
                "status" => "deleted"
            ]
        ];

        echo json_encode(["status" => "success", "data" => $response]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}


if ($method == 'count_messages') {
    try {
        if (!$owner_id) {
            http_response_code(400); // Mauvaise requête
            echo json_encode(["status" => "failure", "message" => "ID is required"]);
            exit;
        }


        // Récupération des messages
        $query = "
            SELECT * 
            FROM \"messages\" 
            WHERE receiver_id = :ownerId AND status = :statusSent
        ";
        $statement = $conn->prepare($query);
        $statement->bindValue(':ownerId', $owner_id); // Correction du paramètre
        $statement->bindValue(':statusSent', $statusSent);

        $statement->execute();

        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        setJsonHeader();  // Assure-toi que cette fonction est définie pour définir le bon header

        if ($result) {
            // Si des messages sont trouvés, retourne les messages
            echo json_encode(["status" => "success", "count" => count($result) ?? 0]);
        } else {
            echo json_encode(["status" => "failure", "count" => 0]);
        }
    } catch (\Throwable $th) {
        // Gestion des erreurs
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        http_response_code(500); // Erreur de serveur interne
        echo json_encode(["status" => "failure", "count" => 0, "message" => $th->getMessage()]);
    }
}

function arrayToString($data)
{
    $columnsString = [];
    foreach ($data as $key => $value) {
        $columnsString[] = "$key = $value";
    }

    return implode(",", $columnsString);
}

// Fonction pour définir le type de contenu JSON
function setJsonHeader()
{
    header('Content-Type: application/json');
}




function getPseudoUser($conn, $userId)
{
    $pseudo = "";

    // Première requête : vérifier si le pseudo existe dans userInfo
    $query = 'SELECT * FROM "userInfo" WHERE userid = :id';
    $statement = $conn->prepare($query);
    $statement->bindParam(':id', $userId);
    $statement->execute();
    $resultUserInfo = $statement->fetch(PDO::FETCH_ASSOC);

    $query1 = 'SELECT * FROM "userInfo" WHERE id = :id';
    $statement1 = $conn->prepare($query1);
    $statement1->bindParam(':id', $userId);
    $statement1->execute();
    $result = $statement1->fetch(PDO::FETCH_ASSOC);

    print_r($result);
    $email = $result["email"];

    if (!$resultUserInfo || !$resultUserInfo["pseudo"]) {

        if ($result && isset($result["email"])) {

            $pseudo = explode('@', $email)[0];
        }
    } else {
        $pseudo = $resultUserInfo['nomsociete'] ??  $resultUserInfo["pseudo"];
    }

    return ["pseudo" => $pseudo, "email" => $email];
}

if ($method == "get_pseudo") {
    try {


        $resultat = getPseudoUser($conn, $userId);
        print_r($resultat);
    } catch (\Throwable $th) {
        echo $th->getMessage();
    }
}
