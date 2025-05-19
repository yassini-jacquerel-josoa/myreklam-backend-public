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
            // Vérifier si l'utilisateur a déjà démarré une conversation sur cette offre
            $query = "SELECT * FROM \"conversations\" WHERE id = :id";
            $statement = $conn->prepare($query);
            $statement->bindValue(':id', $idConversation);
            $statement->execute();
            $existingConversation = $statement->fetch(PDO::FETCH_ASSOC);

            if (!$existingConversation) {
                http_response_code(404); // Not Found
                echo json_encode([
                    "status" => "failure",
                    "message" => "Conversation introuvable"
                ]);
                exit;
            }

            // Démarrer une transaction
            $conn->beginTransaction();

            // Insérer une trace dans conversation_deleted
            $insertQuery = "
                INSERT INTO \"conversation_deleted\" (id, conversation_id, participant_user_id, date) 
                VALUES (:deleteId, :idConversation, :participantUserId, :deleteDate)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->execute([
                'deleteId' => generateGUID(),
                'idConversation' => $idConversation,
                'participantUserId' => $userId,
                'deleteDate' => date("Y-m-d H:i:s")
            ]);

            // Tout s'est bien passé, on valide la transaction
            $conn->commit();

            echo json_encode([
                "status" => "success",
                "message" => "Conversation masquée avec succès"
            ]);
        } else {
            http_response_code(404); // Ressource non trouvée
            echo json_encode(["status" => "failure", "message" => "Conversation not found"]);
        }
    } catch (\Throwable $th) {
        // Annuler la transaction en cas d'erreur
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        http_response_code(500); // Erreur de serveur interne
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
    $search = $_POST["search"] ?? ""; // Recherche par nom
    $page = filter_var($_POST["page"] ?? 1, FILTER_VALIDATE_INT);
    $pageSize = filter_var($_POST["pageSize"] ?? 20, FILTER_VALIDATE_INT);
    $userId = filter_var($owner_id ?? 1);

    // Validation des données
    $page = $page > 0 ? $page : 1;
    $pageSize = $pageSize > 0 ? $pageSize : 20;

    try {

        // Calcul de l'offset
        $offset = ($page - 1) * $pageSize;

        // 1. Récupération des conversations
        $query = "
            SELECT c.id AS conversation_id, c.offre_id
            FROM \"conversations\" c
            LEFT JOIN \"conversation_participants\" cp ON cp.conversation_id = c.id
            WHERE (c.owner_id = :myId OR cp.user_id = :myId)
              AND NOT EXISTS (
                  SELECT 1
                  FROM \"conversation_deleted\" cd
                  WHERE cd.conversation_id = c.id
                    AND cd.participant_user_id = :myId
              )
            GROUP BY c.id, c.offre_id
            ORDER BY c.updated_at DESC
            LIMIT :pageSize OFFSET :offset
            ";
        $statement = $conn->prepare($query);
        $statement->bindValue(':myId', $userId);
        $statement->bindValue(':pageSize', $pageSize);
        $statement->bindValue(':offset', $offset);
        $statement->execute();
        $conversations = $statement->fetchAll(PDO::FETCH_ASSOC);

        $uniqueConversations = [];

        foreach ($conversations as $conversation) {
            $conversationId = $conversation['conversation_id'];
            if (!isset($uniqueConversations[$conversationId])) {
                $uniqueConversations[$conversationId] = $conversation;
            }
        }


        // 2. Pour chaque conversation, récupérer le dernier message, les participants et l'offre
        foreach ($uniqueConversations as $conversation) {
            // 2.1 Dernier message
            $query = "
                SELECT id, content, status, created_at
                FROM \"messages\"
                WHERE conversation_id = :conversationId
                ORDER BY created_at DESC
                LIMIT 1
            ";
            $statement = $conn->prepare($query);
            $statement->bindValue(':conversationId', $conversation['conversation_id']);
            $statement->execute();
            $message = $statement->fetch(PDO::FETCH_ASSOC);

            $attachments = [];

            if ($message) {
                // 2.1.1 Récupération des pièces jointes associées au message
                $query = "
                    SELECT * FROM \"attachments\"
                    WHERE message_id = :messageId
                ";
                $statement = $conn->prepare($query);
                $statement->bindValue(':messageId', $message['id']);
                $statement->execute();
                $attachments = $statement->fetchAll(PDO::FETCH_ASSOC);
            }

            // 2.2 Interlocuteur
            $query = "
                SELECT *
                FROM \"userInfo\"   u WHERE u.userid = :interlocutorId
                ";

            // Définir l'interlocuteur (utiliser cp.user_id ou cp.owner_id en fonction de la logique)
            $interlocutorId = getInterlocutorId($conversation['conversation_id'], $userId);  // Ajoutez une fonction pour obtenir l'interlocuteur
            $statement = $conn->prepare($query);
            $statement->bindValue(':interlocutorId', $interlocutorId);
            $statement->execute();
            $interlocutor = $statement->fetch(PDO::FETCH_ASSOC);
            $userInfo = null;

            $query0 = 'SELECT * FROM "users" WHERE "Id" = :interlocutorId;';

            $statement = $conn->prepare($query0);
            $statement->bindValue(':interlocutorId', $interlocutorId);
            $statement->execute();
            $userInfo = $statement->fetch(PDO::FETCH_ASSOC);


            $interlocutorUserName = "Pseudo";
            // Si l'interlocuteur est trouvé dans userInfo, on peut alors chercher son pseudo ou nom
            if ($interlocutor) {
                if (isset($interlocutor['profiletype']) && $interlocutor['profiletype'] === "professionnel") {
                    $interlocutorUserName = $interlocutor['nomsociete'] ?? $interlocutor['pseudo'];
                } else {
                    $interlocutorUserName = $interlocutor['pseudo'];
                }
            } else {
                // Vérification de l'email pour définir le nom d'utilisateur
                if ($userInfo && isset($userInfo['Email'])) {
                    $interlocutorUserName = explode('@', $userInfo['Email'])[0];
                }
            }




            // 2.3 Offre
            $query = "
                SELECT *
                FROM \"ads\"
                WHERE id = :offreId AND deletedat IS NULL
            ";
            $statement = $conn->prepare($query);
            $statement->bindValue(':offreId', $conversation['offre_id']);
            $statement->execute();
            $offer = $statement->fetch(PDO::FETCH_ASSOC);
            // echo json_encode([$conversation, $offer, $conversation['offre_id']]);
            // Combinez les données obtenues dans une réponse
            $conversationData[] = [
                "id" => $conversation['conversation_id'],
                "message" => $message ? [
                    "id" => $message['id'],
                    "content" => $message['content'],
                    "status" => $message['status'],
                    "created_at" => $message['created_at'],
                    "attachments" => $attachments
                ] : null,
                "interlocutor" => $interlocutor ? [
                    "id" => $interlocutor['userid'],
                    "username" => $interlocutorUserName,
                    "photo" => $interlocutor['photoprofilurl']
                ] : [
                    "id" => $userInfo['Id'],
                    "username" => $interlocutorUserName,
                    "photo" => null
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

        // 3. Retour de la réponse JSON
        echo json_encode(["status" => "success", "conversations" => $conversationData]);
    } catch (\Throwable $th) {
        http_response_code(500); // Erreur de serveur interne
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'get_message') {
    // Récupération et validation des paramètres
    $page = filter_var($_POST["page"] ?? 1, FILTER_VALIDATE_INT);
    $pageSize = filter_var($_POST["pageSize"] ?? 20, FILTER_VALIDATE_INT);
    $idConversation = filter_var($_POST["idConversation"] ?? null);

    // Vérification des paramètres
    $page = max(1, $page);
    $pageSize = max(1, $pageSize);

    if (!$idConversation) {
        http_response_code(400);
        echo json_encode(["status" => "failure", "message" => "Conversation ID is required"]);
        exit;
    }

    try {




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
                "message" => "Conversation introuvable"
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


        // Calcul de l'offset
        $offset = ($page - 1) * $pageSize;

        // 1. Récupération de l'interlocuteur depuis conversation_participants
        $queryInterlocutor = "
            SELECT owner_id, user_id 
            FROM \"conversation_participants\"
            WHERE conversation_id = :idConversation
            LIMIT 1;
        ";

        $statementInterlocutor = $conn->prepare($queryInterlocutor);
        $statementInterlocutor->bindValue(':idConversation', $idConversation, PDO::PARAM_STR);
        $statementInterlocutor->execute();
        $interlocutor = $statementInterlocutor->fetch(PDO::FETCH_ASSOC);

        if (!$interlocutor) {
            http_response_code(404);
            echo json_encode(["status" => "failure", "message" => "Conversation not found"]);
            exit;
        }

        // Définition de l'ID de l'interlocuteur
        $interlocutorId = ($interlocutor['user_id'] === $owner_id) ? $interlocutor['owner_id'] : $interlocutor['user_id'];

        if (!$interlocutorId) {
            http_response_code(404);
            echo json_encode(["status" => "failure", "message" => "Interlocutor not found"]);
            exit;
        }

        // 2.2 Interlocuteur
        $query = "
                SELECT *
                FROM \"userInfo\"   u WHERE u.userid = :interlocutorId
                ";

        $statement = $conn->prepare($query);
        $statement->bindValue(':interlocutorId', $interlocutorId);
        $statement->execute();
        $interlocutor = $statement->fetch(PDO::FETCH_ASSOC);
        $userInfo = null;

        $query0 = 'SELECT * FROM "users" WHERE "Id" = :interlocutorId;';

        $statement = $conn->prepare($query0);
        $statement->bindValue(':interlocutorId', $interlocutorId);
        $statement->execute();
        $userInfo = $statement->fetch(PDO::FETCH_ASSOC);

        $interlocutorUserName = "Pseudo";
        // Si l'interlocuteur est trouvé dans userInfo, on peut alors chercher son pseudo ou nom
        if ($interlocutor) {
            if (isset($interlocutor['profiletype']) && $interlocutor['profiletype'] === "professionnel") {
                $interlocutorUserName = $interlocutor['nomsociete'] ?? $interlocutor['pseudo'];
            } else {
                $interlocutorUserName = $interlocutor['pseudo'];
            }
        } else {
            // Vérification de l'email pour définir le nom d'utilisateur
            if ($userInfo && isset($userInfo['Email'])) {
                $interlocutorUserName = explode('@', $userInfo['Email'])[0];
            }
        }



        // 4. Récupération des messages de la conversation
        $queryMessages = "
            SELECT id, sender_id, receiver_id, content, status, created_at
            FROM \"messages\"
            WHERE conversation_id = :idConversation
            ORDER BY created_at ASC
            LIMIT :pageSize OFFSET :offset;
        ";

        $statementMessages = $conn->prepare($queryMessages);
        $statementMessages->bindValue(':idConversation', $idConversation, PDO::PARAM_STR);
        $statementMessages->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);
        $statementMessages->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statementMessages->execute();

        $messages = $statementMessages->fetchAll(PDO::FETCH_ASSOC);

        // 5. Mise à jour du statut des messages et récupération des pièces jointes
        foreach ($messages as &$message) {
            if ($message["status"] === $statusSent && $owner_id == $message["receiver_id"]) {
                $idMessage = $message["id"];

                // Mise à jour du statut en "lu"
                $updateQuery = "
                    UPDATE \"messages\"
                    SET status = :statusRead
                    WHERE id = :idMessage
                ";
                $updateStatement = $conn->prepare($updateQuery);
                $updateStatement->bindValue(':statusRead', $statusRead, PDO::PARAM_STR);
                $updateStatement->bindValue(':idMessage', $idMessage, PDO::PARAM_STR);
                $updateStatement->execute();

                $message["status"] = $statusRead;
            }

            // Récupération des pièces jointes
            $queryFile = "
                SELECT * FROM \"attachments\"
                WHERE message_id = :idMessage;
            ";
            $statementFile = $conn->prepare($queryFile);
            $statementFile->bindValue(':idMessage', $message["id"], PDO::PARAM_STR);
            $statementFile->execute();
            $attachments = $statementFile->fetchAll(PDO::FETCH_ASSOC);

            $message["attachments"] = $attachments;
        }

        // 6. Structuration de la réponse JSON
        $response = [
            "interlocutor" => [
                "id" => $interlocutorId,
                "username" => $interlocutorUserName,
                "photo" => $interlocutor['photoprofilurl']
            ],
            "messages" => $messages,
            "announcement" => $offre ? [
                "id" => $offre['id'],
                "name" => $offre['category'] == "demandes" ? $offre['inquiryTitle'] : $offre['title'],
                "status" => "valid"
            ] : [
                "id" => null,
                "name" => null,
                "status" => "deleted"
            ]

        ];

        setJsonHeader();
        echo json_encode(["status" => "success", "data" => $response]);
    } catch (\Throwable $th) {
        // Gestion des erreurs
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

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
