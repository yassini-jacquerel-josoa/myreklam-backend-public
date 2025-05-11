<?php

// Bloquer l'accès direct depuis un navigateur en renvoyant une erreur 404
if ($_SERVER['REQUEST_METHOD'] === 'GET' && basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    http_response_code(404);
    exit;
}

include_once("./db.php");
include_once("./packages/AmbassadorAction.php");
include_once("./packages/NotificationBrevoAndWeb.php");

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
$idDocumentFile = $_POST['id'];

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

if ($method == 'get_document_files') {
    try {
        $user_id = $_POST['user_id'];

        if (!$user_id) {
            throw new Exception("L'utilisateur n'est pas spécifié.");
        }

        $query = "SELECT * FROM document_files WHERE user_id = :user_id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':user_id', $user_id);
        $statement->execute();
        $documents = $statement->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["status" => "success", "document_files" => $documents]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'create_document_file') {
    try {
        $id = generateGUID();
        $user_id = $_POST['user_id'];
        $name = $_POST['name'];
        $category = $_POST['category'];
        $url = $_POST['url'];
        $show_public = $_POST['show_public'];
        $created_at = date('Y-m-d H:i:s');
        $updated_at = $created_at;

        $query = "INSERT INTO document_files (id, user_id, name, category, url, show_public, created_at, updated_at) 
                  VALUES (:id, :user_id, :name, :category, :url, :show_public, :created_at, :updated_at)";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $id);
        $statement->bindValue(':user_id', $user_id);
        $statement->bindValue(':name', $name);
        $statement->bindValue(':category', $category);
        $statement->bindValue(':url', $url);
        $statement->bindValue(':show_public', $show_public);
        $statement->bindValue(':created_at', $created_at);
        $statement->bindValue(':updated_at', $updated_at);
        $statement->execute();

        echo json_encode(["status" => "success", "message" => "Document file créé avec succès"]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'update_document_file') {
    try {
        $user_id = $_POST['user_id'];
        $name = $_POST['name'];
        $category = $_POST['category'];
        $url = $_POST['url'];
        $show_public = $_POST['show_public'];
        $updated_at = date('Y-m-d H:i:s');

        $query = "UPDATE document_files SET 
                  user_id = :user_id, 
                  name = :name, 
                  category = :category, 
                  url = :url, 
                  show_public = :show_public,
                  updated_at = :updated_at 
                  WHERE id = :id AND user_id = :user_id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $idDocumentFile);
        $statement->bindValue(':user_id', $user_id);
        $statement->bindValue(':name', $name);
        $statement->bindValue(':category', $category);
        $statement->bindValue(':url', $url);
        $statement->bindValue(':show_public', $show_public);
        $statement->bindValue(':updated_at', $updated_at);
        $statement->execute();

        echo json_encode(["status" => "success", "message" => "Document file mis à jour avec succès"]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'delete_document_file') {
    try {
        $user_id = $_POST['user_id'];

        if (!$user_id) {
            throw new Exception("L'utilisateur n'est pas spécifié.");
        }

        $query = "DELETE FROM document_files WHERE id = :id AND user_id = :user_id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $idDocumentFile);
        $statement->bindValue(':user_id', $user_id);
        $statement->execute();

        $rowsAffected = $statement->rowCount();

        if ($rowsAffected === 0) {
            echo json_encode(["status" => "failure", "message" => "Document non trouvé ou vous n'avez pas les droits"]);
        } else {
            echo json_encode(["status" => "success", "message" => "Document file supprimé avec succès"]);
        }
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'get_ad_document_files') {
    try {
        $ad_id = $_POST['ad_id'];

        if (!$ad_id) {
            throw new Exception("L'annonce n'est pas spécifiée.");
        }

        $query = "SELECT df.* 
                  FROM ad_document_files adf
                  JOIN document_files df ON adf.document_file_id = df.id
                  WHERE adf.ad_id = :ad_id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':ad_id', $ad_id);
        $statement->execute();
        $documents = $statement->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["status" => "success", "document_files" => $documents]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'get_application_documents') {
    try {
        $application_id = $_POST['application_id'];

        if (!$application_id) {
            throw new Exception("L'ID de candidature n'est pas spécifié.");
        }

        $query = "SELECT df.* 
                  FROM ad_application_document_files aadf
                  JOIN document_files df ON aadf.document_file_id = df.id
                  WHERE aadf.ad_application_id = :application_id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':application_id', $application_id);
        $statement->execute();
        $documents = $statement->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["status" => "success", "documents" => $documents]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'create_application') {
    try {
        $id = generateGUID();
        $ad_id = $_POST['ad_id'];
        $interested_user_id = $_POST['interested_user_id'];
        $interested_participant = $_POST['interested_participant'] ?? 1;
        $created_at = date('Y-m-d H:i:s');
        $updated_at = $created_at;

        // recuperer l'annonce
        $query = "SELECT * FROM ads WHERE id = :ad_id AND deletedat IS NULL";
        $statement = $conn->prepare($query);
        $statement->bindValue(':ad_id', $ad_id);
        $statement->execute();
        $ad = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$ad) {
            throw new Exception("L'annonce n'existe pas.");
        }

        // Vérifier si l'utilisateur a déjà postulé à cette annonce
        $query = "SELECT COUNT(*) as count FROM ad_applications WHERE ad_id = :ad_id AND interested_user_id = :interested_user_id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':ad_id', $ad_id);
        $statement->bindValue(':interested_user_id', $interested_user_id);
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        if ($result[0]['count'] > 0) {
            throw new Exception("Vous avez déjà postulé à cette annonce.");
        }

        $query = "INSERT INTO ad_applications 
                  (id, ad_id, interested_user_id, created_at, updated_at, interested_participant) 
                  VALUES (:id, :ad_id, :interested_user_id, :created_at, :updated_at, :interested_participant)";

        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $id);
        $statement->bindValue(':ad_id', $ad_id);
        $statement->bindValue(':interested_user_id', $interested_user_id);
        $statement->bindValue(':created_at', $created_at);
        $statement->bindValue(':updated_at', $updated_at);
        $statement->bindValue(':interested_participant', $interested_participant, PDO::PARAM_INT);
        $statement->execute();

        // Envoyer des notifications en fonction du type d'annonce
        $notificationManager = new NotificationBrevoAndWeb($conn);
        
        if ($ad['category'] == "emplois") {
            $coinEvents = new EventCoinsFacade($conn);
            $coinEvents->applyJobTraining($interested_user_id);
            
            // Notification au postulant
            $notificationManager->sendNotificationAdEmploisPostulant($interested_user_id, $ad_id, $ad['userId']);
            // Notification au créateur de l'annonce
            $notificationManager->sendNotificationAdEmploisCandidature($ad['userId'], $ad_id);
        }
        
        if ($ad['category'] == "formations") {
            $coinEvents = new EventCoinsFacade($conn);
            $coinEvents->applyJobTraining($interested_user_id);
            
            // Notification au postulant
            $notificationManager->sendNotificationAdFormationPostulant($interested_user_id, $ad_id, $ad['userId']);
            
            // Récupérer le type de profil du créateur de l'annonce
            $query = "SELECT profiletype FROM userInfo WHERE userid = :user_id";
            $statement = $conn->prepare($query);
            $statement->bindValue(':user_id', $ad['userId']);
            $statement->execute();
            $userInfo = $statement->fetch(PDO::FETCH_ASSOC);
            
            // Envoyer la notification appropriée selon le type de profil
            if ($userInfo && $userInfo['profiletype'] == 'particulier') {
                $notificationManager->sendNotificationAdFormationCandidatureParticulier($ad['userId'], $ad_id);
            } else if ($userInfo && $userInfo['profiletype'] == 'professionnel') {
                $notificationManager->sendNotificationAdFormationCandidatureProfessionnel($ad['userId'], $ad_id, $interested_user_id);
            }
        }
        
        if ($ad['category'] == "evenements") {
            // Notification au participant
            $notificationManager->sendNotificationAdEvenementsParticipant($interested_user_id, $ad_id);
            
            // Planifier un rappel pour 24h avant l'événement
            // Note: ceci devrait idéalement être fait avec un système de tâches planifiées/cron
            // mais pour l'exemple, nous allons simplement ajouter un indicateur dans la BD
            if (!empty($ad['startDate'])) {
                $eventDate = new DateTime($ad['startDate']);
                $currentDate = new DateTime();
                $interval = $currentDate->diff($eventDate);
                
                // Si l'événement est dans plus de 24h, enregistrer pour rappel ultérieur
                if ($interval->days > 1) {
                    // Code pour enregistrer le rappel (à implémenter selon votre système)
                }
            }
        }
        
        echo json_encode([
            "status" => "success",
            "message" => "Candidature créée avec succès",
            "application_id" => $id
        ]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'add_document_to_application') {
    try {
        $id = generateGUID();
        $application_id = $_POST['application_id'];
        $document_file_id = $_POST['document_file_id'];

        $query = "INSERT INTO ad_application_document_files 
                  (id, ad_application_id, document_file_id) 
                  VALUES (:id, :application_id, :document_file_id)";

        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $id);
        $statement->bindValue(':application_id', $application_id);
        $statement->bindValue(':document_file_id', $document_file_id);
        $statement->execute();

        echo json_encode([
            "status" => "success",
            "message" => "Document ajouté à la candidature avec succès"
        ]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'update_application_status') {
    try {
        $application_id = $_POST['application_id'];
        $interested_participant = $_POST['interested_participant'];
        $updated_at = date('Y-m-d H:i:s');

        $query = "UPDATE ad_applications SET 
                  interested_participant = :interested_participant,
                  updated_at = :updated_at 
                  WHERE id = :application_id";

        $statement = $conn->prepare($query);
        $statement->bindValue(':application_id', $application_id);
        $statement->bindValue(':interested_participant', $interested_participant, PDO::PARAM_INT);
        $statement->bindValue(':updated_at', $updated_at);
        $statement->execute();

        echo json_encode([
            "status" => "success",
            "message" => "Statut de candidature mis à jour"
        ]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'remove_document_from_application') {
    try {
        $application_id = $_POST['application_id'];
        $document_file_id = $_POST['document_file_id'];

        $query = "DELETE FROM ad_application_document_files 
                  WHERE ad_application_id = :application_id 
                  AND document_file_id = :document_file_id";

        $statement = $conn->prepare($query);
        $statement->bindValue(':application_id', $application_id);
        $statement->bindValue(':document_file_id', $document_file_id);
        $statement->execute();

        $rowsAffected = $statement->rowCount();

        if ($rowsAffected === 0) {
            echo json_encode([
                "status" => "failure",
                "message" => "Document non trouvé dans cette candidature"
            ]);
        } else {
            echo json_encode([
                "status" => "success",
                "message" => "Document retiré de la candidature"
            ]);
        }
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'delete_application') {
    try {
        $application_id = $_POST['application_id'];
        $user_id = $_POST['user_id'];

        // D'abord supprimer les documents associés
        $query = "DELETE FROM ad_application_document_files 
                  WHERE ad_application_id = :application_id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':application_id', $application_id);
        $statement->execute();

        // Puis supprimer la candidature
        $query = "DELETE FROM ad_applications 
                  WHERE id = :application_id AND interested_user_id = :user_id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':application_id', $application_id);
        $statement->bindValue(':user_id', $user_id);
        $statement->execute();

        $rowsAffected = $statement->rowCount();

        if ($rowsAffected === 0) {
            echo json_encode([
                "status" => "failure",
                "message" => "Candidature non trouvée ou vous n'avez pas les droits"
            ]);
        } else {
            echo json_encode([
                "status" => "success",
                "message" => "Candidature et documents associés supprimés"
            ]);
        }
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'get_application_ads_by_interested_user_id') {
    try {
        $interested_user_id = $_POST['interested_user_id'];

        if (!$interested_user_id) {
            throw new Exception("L'utilisateur n'est pas spécifié.");
        }

        // Joindre les applications avec les annonces "ads"
        $query = "SELECT ads.*, ad_applications.* 
                  FROM ad_applications
                  JOIN ads ON ad_applications.ad_id = ads.id 
                  WHERE interested_user_id = :interested_user_id AND ads.deletedat IS NULL";
        $statement = $conn->prepare($query);
        $statement->bindValue(':interested_user_id', $interested_user_id);
        $statement->execute();
        $applications = $statement->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["status" => "success", "applications" => $applications]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'get_application_ads_by_ad_id') {
    try {
        $ad_id = $_POST['ad_id'];

        if (!$ad_id) {
            throw new Exception("L'annonce n'est pas spécifiée.");
        }

        // Première requête : récupérer les applications
        $queryApplications = 'SELECT * FROM ad_applications WHERE ad_id = :ad_id';
        $statementApplications = $conn->prepare($queryApplications);
        $statementApplications->bindValue(':ad_id', $ad_id);
        $statementApplications->execute();
        $applications = $statementApplications->fetchAll(PDO::FETCH_ASSOC);

        $formattedData = [];

        foreach ($applications as $application) {
            // Deuxième requête : récupérer les infos de l'annonce
            $queryAd = 'SELECT * FROM ads WHERE id = :ad_id AND deletedat IS NULL';
            $statementAd = $conn->prepare($queryAd);
            $statementAd->bindValue(':ad_id', $application['ad_id']);
            $statementAd->execute();
            $ad = $statementAd->fetch(PDO::FETCH_ASSOC);

            // Troisième requête : récupérer les infos utilisateur
            $queryUser = 'SELECT * FROM "userInfo" WHERE userid = :user_id';
            $statementUser = $conn->prepare($queryUser);
            $statementUser->bindValue(':user_id', $application['interested_user_id']);
            $statementUser->execute();
            $userInfo = $statementUser->fetch(PDO::FETCH_ASSOC);

            // Quatrième requête : récupérer les documents
            $queryDocuments = 'SELECT * FROM document_files WHERE id IN (SELECT document_file_id FROM ad_application_document_files WHERE ad_application_id = :application_id)';
            $statementDocuments = $conn->prepare($queryDocuments);
            $statementDocuments->bindValue(':application_id', $application['id']);
            $statementDocuments->execute();
            $documents = $statementDocuments->fetchAll(PDO::FETCH_ASSOC);

            // Formater les données
            $formattedData[] = [
                'application' => $application,
                'ad' => $ad,
                'userInfo' => $userInfo,
                'documents' => $documents
            ];
        }


        echo json_encode(["status" => "success", "applications" => $formattedData]);
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
