<?php

/**
 * Tâches planifiées quotidiennes
 * Ce script doit être exécuté une fois par jour via un cron job
 * Exemple: 0 0 * * * php /path/to/Day.php
 */

include_once(__DIR__ . "/../db.php");
include_once(__DIR__ . "/../packages/NotificationBrevoAndWeb.php");
include_once(__DIR__ . "/../logger.php");

$conn = $GLOBALS['conn']; // Utiliser la connexion globale définie dans db.php
$notificationManager = new NotificationBrevoAndWeb($conn);

// Log du démarrage
log_info("Démarrage des tâches quotidiennes", "DAILY_JOBS");

try {
    // 1. Rappels des événements qui ont lieu demain
    sendEventReminders($conn, $notificationManager);

    // 2. Notification pour les annonces qui expirent dans les 3 jours
    notifyExpiringAds($conn, $notificationManager);

    // 3. Notification pour les abonnements qui expirent dans les 7 jours
    notifyExpiringSubscriptions($conn, $notificationManager);

    // Log de fin d'exécution
    log_info("Fin des tâches quotidiennes avec succès", "DAILY_JOBS");
} catch (Exception $e) {
    // Log d'erreur
    log_error("Erreur lors de l'exécution des tâches quotidiennes: " . $e->getMessage(), "DAILY_JOBS", ["stack_trace" => $e->getTraceAsString()]);
}

/**
 * Envoie des rappels pour les événements qui ont lieu demain
 */
if (!function_exists('sendEventReminders')) {
    function sendEventReminders($conn, $notificationManager)
    {
        // Date de demain
        $tomorrowDate = date('Y-m-d', strtotime('+1 day'));

        // Récupérer tous les événements qui ont lieu demain
        $query = "SELECT a.id as ad_id, a.userId, a.title, a.startDate, a.startHours, a.address, a.price, cp.user_id as participant_id 
              FROM ads a 
              JOIN conversation_participants cp ON a.id = cp.offre_id 
              WHERE a.category = 'evenements' 
              AND DATE(a.startDate) = :tomorrowDate 
              AND a.deletedat IS NULL";

        $statement = $conn->prepare($query);
        $statement->bindParam(':tomorrowDate', $tomorrowDate);
        $statement->execute();

        $events = $statement->fetchAll(PDO::FETCH_ASSOC);

        // Nombre d'événements trouvés
        $count = count($events);
        log_info("Rappel d'événements: $count événements trouvés pour demain", "EVENT_REMINDERS");

        // Envoyer un rappel à chaque participant
        foreach ($events as $event) {
            try {
                // Rappel au participant
                $notificationManager->sendNotificationAdEvenementsRappel($event['participant_id'], $event['ad_id']);

                log_info("Rappel d'événement envoyé", "EVENT_REMINDERS", [
                    "event_id" => $event['ad_id'],
                    "participant_id" => $event['participant_id'],
                    "title" => $event['title']
                ]);
            } catch (Exception $e) {
                log_error("Erreur lors de l'envoi du rappel d'événement", "EVENT_REMINDERS", [
                    "event_id" => $event['ad_id'],
                    "participant_id" => $event['participant_id'],
                    "error" => $e->getMessage()
                ]);
            }
        }

        log_info("Traitement des rappels d'événements terminé", "EVENT_REMINDERS");
    }
}

/**
 * Notifie les utilisateurs dont les annonces expirent dans les 3 jours
 */
if (!function_exists('notifyExpiringAds')) {
    function notifyExpiringAds($conn, $notificationManager)
    {
        // Date dans 3 jours
        $expirationDate = date('Y-m-d', strtotime('+3 days'));

        // Récupérer toutes les annonces qui expirent dans 3 jours
        $query = "SELECT id, userId, title, category FROM ads 
              WHERE expirationDate = :expirationDate 
              AND deletedat IS NULL";

        $statement = $conn->prepare($query);
        $statement->bindParam(':expirationDate', $expirationDate);
        $statement->execute();

        $expiringAds = $statement->fetchAll(PDO::FETCH_ASSOC);

        // Nombre d'annonces trouvées
        $count = count($expiringAds);
        log_info("Annonces expirant bientôt: $count annonces trouvées", "EXPIRING_ADS");

        // Notifier chaque propriétaire d'annonce
        foreach ($expiringAds as $ad) {
            try {
                // Créer une notification dans la base de données
                $notifId = generateGUID();
                $query = 'INSERT INTO "notifications" ("id", "user_id", "content", "type", "is_read", "return_url") 
                     VALUES (:id, :user_id, :content, :type, :is_read, :return_url)';
                $statement = $conn->prepare($query);

                $type = "expiration";
                $is_read = 0;
                $content = "Votre annonce \"" . $ad['title'] . "\" expire dans 3 jours. Vous pouvez la renouveler dans votre espace annonces.";

                $statement->bindParam(':id', $notifId);
                $statement->bindParam(':user_id', $ad['userId']);
                $statement->bindParam(':content', $content);
                $statement->bindParam(':type', $type);
                $statement->bindParam(':is_read', $is_read);
                $statement->bindParam(':return_url', '/mes-annonces');

                $statement->execute();

                log_info("Notification d'expiration envoyée", "EXPIRING_ADS", [
                    "ad_id" => $ad['id'],
                    "user_id" => $ad['userId'],
                    "title" => $ad['title']
                ]);
            } catch (Exception $e) {
                log_error("Erreur lors de l'envoi de la notification d'expiration", "EXPIRING_ADS", [
                    "ad_id" => $ad['id'],
                    "user_id" => $ad['userId'],
                    "error" => $e->getMessage()
                ]);
            }
        }

        log_info("Traitement des annonces expirant bientôt terminé", "EXPIRING_ADS");
    }
}

/**
 * Notifie les utilisateurs dont l'abonnement expire dans les 7 jours
 */
if (!function_exists('notifyExpiringSubscriptions')) {
    function notifyExpiringSubscriptions($conn, $notificationManager)
    {
        // Date dans 7 jours
        $expirationDate = date('Y-m-d', strtotime('+7 days'));

        // Récupérer tous les abonnements qui expirent dans 7 jours
        $query = "SELECT ui.userid, ui.plan, ui.subscription_end_date, u.Email 
              FROM userInfo ui
              JOIN users u ON ui.userid = u.Id 
              WHERE ui.profiletype = 'professionnel' 
              AND ui.subscription_status = 'active'
              AND DATE(ui.subscription_end_date) = :expirationDate";

        $statement = $conn->prepare($query);
        $statement->bindParam(':expirationDate', $expirationDate);
        $statement->execute();

        $expiringSubscriptions = $statement->fetchAll(PDO::FETCH_ASSOC);

        // Nombre d'abonnements trouvés
        $count = count($expiringSubscriptions);
        log_info("Abonnements expirant bientôt: $count abonnements trouvés", "EXPIRING_SUBSCRIPTIONS");

        // Notifier chaque utilisateur
        foreach ($expiringSubscriptions as $subscription) {
            try {
                // Créer une notification dans la base de données
                $notifId = generateGUID();
                $query = 'INSERT INTO "notifications" ("id", "user_id", "content", "type", "is_read", "return_url") 
                     VALUES (:id, :user_id, :content, :type, :is_read, :return_url)';
                $statement = $conn->prepare($query);

                $type = "subscription";
                $is_read = 0;
                $content = "Votre abonnement " . $subscription['plan'] . " expire dans 7 jours. Pour continuer à profiter de nos services, veuillez le renouveler.";

                $statement->bindParam(':id', $notifId);
                $statement->bindParam(':user_id', $subscription['userid']);
                $statement->bindParam(':content', $content);
                $statement->bindParam(':type', $type);
                $statement->bindParam(':is_read', $is_read);
                $statement->bindParam(':return_url', '/abonnement');

                $statement->execute();

                log_info("Notification d'expiration d'abonnement envoyée", "EXPIRING_SUBSCRIPTIONS", [
                    "user_id" => $subscription['userid'],
                    "plan" => $subscription['plan'],
                    "expiration" => $subscription['subscription_end_date']
                ]);
            } catch (Exception $e) {
                log_error("Erreur lors de l'envoi de la notification d'expiration d'abonnement", "EXPIRING_SUBSCRIPTIONS", [
                    "user_id" => $subscription['userid'],
                    "error" => $e->getMessage()
                ]);
            }
        }

        log_info("Traitement des abonnements expirant bientôt terminé", "EXPIRING_SUBSCRIPTIONS");
    }
}

/**
 * Génère un GUID unique
 */
if (!function_exists('generateGUID')) {
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
}
