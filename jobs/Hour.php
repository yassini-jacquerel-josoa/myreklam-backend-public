<?php
/**
 * Tâches planifiées horaires
 * Ce script doit être exécuté toutes les heures via un cron job
 * Exemple: 0 * * * * php /path/to/Hour.php
 */

include_once(__DIR__ . "/../db.php");
include_once(__DIR__ . "/../packages/NotificationBrevoAndWeb.php");
include_once(__DIR__ . "/../logger.php");

// Initialisation
$conn = $GLOBALS['conn']; // Utiliser la connexion globale définie dans db.php
$notificationManager = new NotificationBrevoAndWeb($conn);

// Log du démarrage
log_info("Démarrage des tâches horaires", "HOURLY_JOBS");

try {
    // 1. Vérification des alertes pour les nouvelles annonces
    checkAlertsForNewAds($conn, $notificationManager);
    
    // 2. Traitement des annonces expirées
    handleExpiredAds($conn, $notificationManager);

    // Log de fin d'exécution
    log_info("Fin des tâches horaires avec succès", "HOURLY_JOBS");
    
} catch (Exception $e) {
    // Log d'erreur
    log_error("Erreur lors de l'exécution des tâches horaires: " . $e->getMessage(), "HOURLY_JOBS", ["stack_trace" => $e->getTraceAsString()]);
}

/**
 * Vérifie les nouvelles annonces qui correspondent aux critères d'alerte des utilisateurs
 */
function checkAlertsForNewAds($conn, $notificationManager) {
    // Définir la fenêtre de temps (annonces créées dans la dernière heure)
    $lastHour = date('Y-m-d H:i:s', strtotime('-1 hour'));
    
    // Récupérer toutes les nouvelles annonces
    $query = "SELECT * FROM ads WHERE createdat >= :lastHour AND deletedat IS NULL";
    $statement = $conn->prepare($query);
    $statement->bindParam(':lastHour', $lastHour);
    $statement->execute();
    
    $newAds = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    // Nombre d'annonces trouvées
    $count = count($newAds);
    log_info("Vérification des alertes: $count nouvelles annonces trouvées", "ALERT_CHECK");
    
    if ($count === 0) {
        return; // Pas de nouvelles annonces, on quitte la fonction
    }
    
    // Récupérer toutes les alertes actives
    $alertQuery = "SELECT * FROM user_alerts WHERE status = 'active'";
    $alertStatement = $conn->prepare($alertQuery);
    $alertStatement->execute();
    
    $alerts = $alertStatement->fetchAll(PDO::FETCH_ASSOC);
    
    // Nombre d'alertes trouvées
    $alertCount = count($alerts);
    log_info("Vérification des alertes: $alertCount alertes actives trouvées", "ALERT_CHECK");
    
    if ($alertCount === 0) {
        return; // Pas d'alertes actives, on quitte la fonction
    }
    
    // Pour chaque annonce, vérifier si elle correspond aux critères d'une alerte
    foreach ($newAds as $ad) {
        foreach ($alerts as $alert) {
            // Vérifier si l'annonce correspond aux critères de l'alerte
            if (adMatchesAlertCriteria($ad, $alert)) {
                try {
                    // Envoyer une notification
                    $notificationManager->sendNotificationAlert($alert['user_id'], $alert['id']);
                    
                    // Créer une notification dans la base de données
                    $notifId = generateGUID();
                    $query = 'INSERT INTO "notifications" ("id", "user_id", "content", "type", "is_read", "return_url") 
                             VALUES (:id, :user_id, :content, :type, :is_read, :return_url)';
                    $statement = $conn->prepare($query);
                    
                    $type = "alert";
                    $is_read = 0;
                    $content = "Une nouvelle annonce correspond à votre alerte \"" . $alert['name'] . "\": " . $ad['title'];
                    
                    $statement->bindParam(':id', $notifId);
                    $statement->bindParam(':user_id', $alert['user_id']);
                    $statement->bindParam(':content', $content);
                    $statement->bindParam(':type', $type);
                    $statement->bindParam(':is_read', $is_read);
                    $statement->bindParam(':return_url', '/annonce/' . $ad['id']);
                    
                    $statement->execute();
                    
                    log_info("Notification d'alerte envoyée", "ALERT_CHECK", [
                        "alert_id" => $alert['id'],
                        "user_id" => $alert['user_id'],
                        "ad_id" => $ad['id']
                    ]);
                } catch (Exception $e) {
                    log_error("Erreur lors de l'envoi de la notification d'alerte", "ALERT_CHECK", [
                        "alert_id" => $alert['id'],
                        "user_id" => $alert['user_id'],
                        "ad_id" => $ad['id'],
                        "error" => $e->getMessage()
                    ]);
                }
            }
        }
    }
    
    log_info("Traitement des alertes terminé", "ALERT_CHECK");
}

/**
 * Vérifie si une annonce correspond aux critères d'une alerte
 */
function adMatchesAlertCriteria($ad, $alert) {
    $matches = true;
    
    // Vérifier la catégorie
    if (!empty($alert['category']) && $ad['category'] != $alert['category']) {
        $matches = false;
    }
    
    // Vérifier les mots-clés
    if (!empty($alert['keywords'])) {
        $keywords = explode(',', $alert['keywords']);
        $keywordMatch = false;
        
        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            if (!empty($keyword) && (
                stripos($ad['title'], $keyword) !== false ||
                (isset($ad['description']) && stripos($ad['description'], $keyword) !== false)
            )) {
                $keywordMatch = true;
                break;
            }
        }
        
        if (!$keywordMatch) {
            $matches = false;
        }
    }
    
    // Vérifier la localisation
    if (!empty($alert['location']) && isset($ad['address']) && stripos($ad['address'], $alert['location']) === false) {
        $matches = false;
    }
    
    // Vérifier le prix minimum
    if (!empty($alert['min_price']) && isset($ad['price']) && floatval($ad['price']) < floatval($alert['min_price'])) {
        $matches = false;
    }
    
    // Vérifier le prix maximum
    if (!empty($alert['max_price']) && isset($ad['price']) && floatval($ad['price']) > floatval($alert['max_price'])) {
        $matches = false;
    }
    
    return $matches;
}

/**
 * Traite les annonces qui ont expiré
 */
function handleExpiredAds($conn, $notificationManager) {
    // Date actuelle
    $currentDate = date('Y-m-d');
    
    // Récupérer toutes les annonces qui ont expiré mais qui ne sont pas encore marquées comme supprimées
    $query = "SELECT id, userId, title FROM ads 
              WHERE expirationDate < :currentDate 
              AND deletedat IS NULL";
    
    $statement = $conn->prepare($query);
    $statement->bindParam(':currentDate', $currentDate);
    $statement->execute();
    
    $expiredAds = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    // Nombre d'annonces trouvées
    $count = count($expiredAds);
    log_info("Annonces expirées: $count annonces trouvées", "EXPIRED_ADS");
    
    // Marquer chaque annonce comme expirée et notifier le propriétaire
    foreach ($expiredAds as $ad) {
        try {
            // Marquer l'annonce comme expirée
            $updateQuery = "UPDATE ads SET expired = true WHERE id = :id";
            $updateStatement = $conn->prepare($updateQuery);
            $updateStatement->bindParam(':id', $ad['id']);
            $updateStatement->execute();
            
            // Créer une notification dans la base de données
            $notifId = generateGUID();
            $query = 'INSERT INTO "notifications" ("id", "user_id", "content", "type", "is_read", "return_url") 
                     VALUES (:id, :user_id, :content, :type, :is_read, :return_url)';
            $statement = $conn->prepare($query);
            
            $type = "expiration";
            $is_read = 0;
            $content = "Votre annonce \"" . $ad['title'] . "\" a expiré. Vous pouvez la republier depuis votre espace annonces.";
            
            $statement->bindParam(':id', $notifId);
            $statement->bindParam(':user_id', $ad['userId']);
            $statement->bindParam(':content', $content);
            $statement->bindParam(':type', $type);
            $statement->bindParam(':is_read', $is_read);
            $statement->bindParam(':return_url', '/mes-annonces');
            
            $statement->execute();
            
            log_info("Annonce marquée comme expirée et notification envoyée", "EXPIRED_ADS", [
                "ad_id" => $ad['id'],
                "user_id" => $ad['userId'],
                "title" => $ad['title']
            ]);
        } catch (Exception $e) {
            log_error("Erreur lors du traitement de l'annonce expirée", "EXPIRED_ADS", [
                "ad_id" => $ad['id'], 
                "user_id" => $ad['userId'],
                "error" => $e->getMessage()
            ]);
        }
    }
    
    log_info("Traitement des annonces expirées terminé", "EXPIRED_ADS");
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
