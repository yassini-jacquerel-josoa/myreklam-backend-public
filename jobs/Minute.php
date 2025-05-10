<?php
/**
 * Tâches planifiées à la minute
 * Ce script doit être exécuté toutes les minutes via un cron job
 * Exemple: * * * * * php /path/to/Minute.php
 */

include_once(__DIR__ . "/../db.php");
include_once(__DIR__ . "/../packages/NotificationBrevoAndWeb.php");
include_once(__DIR__ . "/../logger.php");

// Initialisation
$conn = $GLOBALS['conn']; // Utiliser la connexion globale définie dans db.php
$notificationManager = new NotificationBrevoAndWeb($conn);

// Log du démarrage
log_info("Démarrage des tâches minutes", "MINUTE_JOBS");

try {
    // 1. Traitement des notifications en attente
    processPendingNotifications($conn, $notificationManager);
    
    // 2. Vérification des événements imminents (dans moins d'une heure)
    checkImmediateEvents($conn, $notificationManager);

    // Log de fin d'exécution
    log_info("Fin des tâches minutes avec succès", "MINUTE_JOBS");
    
} catch (Exception $e) {
    // Log d'erreur
    log_error("Erreur lors de l'exécution des tâches minutes: " . $e->getMessage(), "MINUTE_JOBS", ["stack_trace" => $e->getTraceAsString()]);
}

/**
 * Traite les notifications en attente d'envoi
 */
function processPendingNotifications($conn, $notificationManager) {
    // Récupérer les notifications en attente d'envoi
    $query = "SELECT * FROM notification_queue WHERE status = 'pending' ORDER BY created_at ASC LIMIT 20";
    $statement = $conn->prepare($query);
    $statement->execute();
    
    $pendingNotifications = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    // Nombre de notifications trouvées
    $count = count($pendingNotifications);
    log_info("Traitement des notifications en attente: $count notifications trouvées", "PENDING_NOTIFICATIONS");
    
    if ($count === 0) {
        return; // Pas de notifications en attente, on quitte la fonction
    }
    
    // Traiter chaque notification
    foreach ($pendingNotifications as $notification) {
        try {
            // Marquer la notification comme en cours de traitement
            $updateQuery = "UPDATE notification_queue SET status = 'processing', processed_at = NOW() WHERE id = :id";
            $updateStatement = $conn->prepare($updateQuery);
            $updateStatement->bindParam(':id', $notification['id']);
            $updateStatement->execute();
            
            // Traiter la notification en fonction de son type
            $success = false;
            
            switch ($notification['type']) {
                case 'event_reminder':
                    // Rappel d'événement
                    $success = $notificationManager->sendNotificationAdEvenementsRappel(
                        $notification['user_id'], 
                        $notification['target_id']
                    );
                    break;
                    
                case 'ad_expired':
                    // Notification d'annonce expirée
                    $success = $notificationManager->sendNotificationAdDeleted(
                        $notification['user_id'], 
                        $notification['target_id']
                    );
                    break;
                    
                case 'alert':
                    // Alerte sur critères de recherche
                    $success = $notificationManager->sendNotificationAlert(
                        $notification['user_id'], 
                        $notification['target_id']
                    );
                    break;
                    
                // Ajouter d'autres types si nécessaire
                
                default:
                    // Type inconnu
                    log_error("Type de notification inconnu", "PENDING_NOTIFICATIONS", [
                        "notification_id" => $notification['id'],
                        "type" => $notification['type']
                    ]);
                    break;
            }
            
            // Mettre à jour le statut de la notification
            $finalStatus = $success ? 'sent' : 'failed';
            $updateQuery = "UPDATE notification_queue SET status = :status, processed_at = NOW() WHERE id = :id";
            $updateStatement = $conn->prepare($updateQuery);
            $updateStatement->bindParam(':status', $finalStatus);
            $updateStatement->bindParam(':id', $notification['id']);
            $updateStatement->execute();
            
            log_info("Notification traitée", "PENDING_NOTIFICATIONS", [
                "notification_id" => $notification['id'],
                "status" => $finalStatus
            ]);
            
        } catch (Exception $e) {
            // Marquer la notification comme échouée
            $updateQuery = "UPDATE notification_queue SET status = 'failed', processed_at = NOW() WHERE id = :id";
            $updateStatement = $conn->prepare($updateQuery);
            $updateStatement->bindParam(':id', $notification['id']);
            $updateStatement->execute();
            
            log_error("Erreur lors du traitement de la notification", "PENDING_NOTIFICATIONS", [
                "notification_id" => $notification['id'],
                "error" => $e->getMessage()
            ]);
        }
    }
    
    log_info("Traitement des notifications en attente terminé", "PENDING_NOTIFICATIONS");
}

/**
 * Vérifie les événements qui commencent dans moins d'une heure et envoie des rappels immédiats
 */
function checkImmediateEvents($conn, $notificationManager) {
    // Heure actuelle
    $currentDateTime = date('Y-m-d H:i:s');
    // Une heure plus tard
    $oneHourLater = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Récupérer tous les événements qui commencent dans la prochaine heure
    $query = "SELECT a.id as ad_id, a.userId, a.title, a.startDate, a.startHours, a.address, a.price, cp.user_id as participant_id 
              FROM ads a 
              JOIN conversation_participants cp ON a.id = cp.offre_id 
              WHERE a.category = 'evenements' 
              AND CONCAT(a.startDate, ' ', a.startHours) BETWEEN :currentDateTime AND :oneHourLater 
              AND a.deletedat IS NULL";
    
    $statement = $conn->prepare($query);
    $statement->bindParam(':currentDateTime', $currentDateTime);
    $statement->bindParam(':oneHourLater', $oneHourLater);
    $statement->execute();
    
    $imminentEvents = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    // Nombre d'événements trouvés
    $count = count($imminentEvents);
    log_info("Événements imminents: $count événements trouvés", "IMMINENT_EVENTS");
    
    if ($count === 0) {
        return; // Pas d'événements imminents, on quitte la fonction
    }
    
    // Envoyer un rappel immédiat à chaque participant
    foreach ($imminentEvents as $event) {
        try {
            // Créer une notification dans la base de données
            $notifId = generateGUID();
            $query = 'INSERT INTO "notifications" ("id", "user_id", "content", "type", "is_read", "return_url") 
                     VALUES (:id, :user_id, :content, :type, :is_read, :return_url)';
            $statement = $conn->prepare($query);
            
            $type = "event_imminent";
            $is_read = 0;
            $content = "L'événement \"" . $event['title'] . "\" commence dans moins d'une heure !";
            
            $statement->bindParam(':id', $notifId);
            $statement->bindParam(':user_id', $event['participant_id']);
            $statement->bindParam(':content', $content);
            $statement->bindParam(':type', $type);
            $statement->bindParam(':is_read', $is_read);
            $statement->bindParam(':return_url', '/evenement/' . $event['ad_id']);
            
            $statement->execute();
            
            log_info("Notification d'événement imminent envoyée", "IMMINENT_EVENTS", [
                "event_id" => $event['ad_id'],
                "participant_id" => $event['participant_id'],
                "title" => $event['title']
            ]);
        } catch (Exception $e) {
            log_error("Erreur lors de l'envoi de la notification d'événement imminent", "IMMINENT_EVENTS", [
                "event_id" => $event['ad_id'], 
                "participant_id" => $event['participant_id'],
                "error" => $e->getMessage()
            ]);
        }
    }
    
    log_info("Traitement des événements imminents terminé", "IMMINENT_EVENTS");
}

/**
 * Génère un GUID unique
 */
function generateGUID() {
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
