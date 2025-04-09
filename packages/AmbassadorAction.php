<?php

include("./db.php");
include("./logger.php");

class EventCoinsFacade
{
    private PDO $conn;

    public function __construct(PDO $connection)
    {
        $this->conn = $connection;
        log_info("EventCoinsFacade initialisée   =>", "SYSTEM");
    }

    // Méthodes publiques principales
    public function completeProfile(string $userId): bool
    {
        if (!$this->checkProfileConditions($userId)) {
            log_warning("Les conditions du profil ne sont pas remplies", "PROCESS_EVENT", ["user_id" => $userId]);
            return false;
        }

        return $this->processEvent($userId, 'complete_profile');
    }

    public function shareAdSocialMedia(string $userId): bool
    { 
        return $this->processEvent($userId, 'share_ad_social_media');
    }

    public function postGoogleReview(string $userId): bool
    {
        return $this->processEvent($userId, 'post_google_review');
    }

    public function followSocialMedia(string $userId): bool
    {
        return $this->processEvent($userId, 'follow_social_media');
    }

    public function subscribeNewsletter(string $userId): bool
    {
        return $this->processEvent($userId, 'subscribe_newsletter');
    }




    public function addComment(string $userId): bool
    {
        return $this->processEvent($userId, 'comment_ad', true);
    }

    public function publishAd(string $userId): bool
    {
        return $this->processEvent($userId, 'publish_ad', true);
    }

    public function applyJobTraining(string $userId): bool
    {
        return $this->processEvent($userId, 'apply_job_training', true);
    }

    public function attendEvent(string $userId): bool
    {
        return $this->processEvent($userId, 'attend_event', true);
    }

    public function leaveReviewCompany(string $userId): bool
    {
        return $this->processEvent($userId, 'leave_review_company', true);
    }




    // Méthodes de base pour les événements
    private function processEvent(string $userId, string $eventSlug, bool $isRecursive = false): bool
    {
        if ($this->hasAlreadyProcessedEvent($userId, $eventSlug) && !$isRecursive) {
            log_info("L'événement a déjà été traité", "PROCESS_EVENT", [
                "user_id" => $userId,
                "event_slug" => $eventSlug
            ]);
            return false;
        }

        $result = $this->addEventCoins($userId, $eventSlug);

        if (! $result) {
            log_error("Échec du traitement de l'événement", "PROCESS_EVENT", [
                "user_id" => $userId,
                "event_slug" => $eventSlug
            ]);
        }

        return $result;
    }

    // Méthodes internes
    private function generateGUID(): string
    {
        $guid = bin2hex(random_bytes(16));
        log_debug("GUID généré", "GENERATE_GUID", ["guid" => $guid]);
        return $guid;
    }

    private function hasAlreadyProcessedEvent(string $userId, string $slug): bool
    {
        try {
            log_debug("Vérification des doublons d'événement", "CHECK_DUPLICATE", [
                "user_id" => $userId,
                "event_slug" => $slug
            ]);

            $stmt = $this->conn->prepare('
                SELECT 1 FROM history_coins 
                WHERE userId = :userId AND eventName = :eventName
                LIMIT 1
            ');
            $stmt->bindValue(':userId', $userId, PDO::PARAM_STR);
            $stmt->bindValue(':eventName', $slug, PDO::PARAM_STR);
            $stmt->execute();

            $exists = (bool)$stmt->fetchColumn();

            log_debug("Résultat de la vérification des doublons", "CHECK_DUPLICATE", [
                "user_id" => $userId,
                "event_slug" => $slug,
                "exists" => $exists
            ]);

            return $exists;
        } catch (Exception $e) {
            log_error("Erreur lors de la vérification des doublons", "CHECK_DUPLICATE", [
                "user_id" => $userId,
                "error" => $e->getMessage()
            ]);
            return true;
        }
    }

    private function checkUserExists(string $userId): bool
    {
        try {
            log_debug("Vérification de l'existence de l'utilisateur", "CHECK_USER_EXISTS", [
                "user_id" => $userId
            ]);

            $stmt = $this->conn->prepare('SELECT 1 FROM user_coins WHERE userid = :id LIMIT 1');
            $stmt->bindValue(':id', $userId, PDO::PARAM_STR);
            $stmt->execute();

            $exists = (bool)$stmt->fetchColumn();

            log_debug("Résultat de la vérification de l'utilisateur", "CHECK_USER_EXISTS", [
                "user_id" => $userId,
                "exists" => $exists
            ]);

            return $exists;
        } catch (Exception $e) {
            log_error("Erreur lors de la vérification de l'utilisateur", "CHECK_USER_EXISTS", [
                "user_id" => $userId,
                "error" => $e->getMessage()
            ]);
            return false;
        }
    }

    private function addEventCoins(string $userId, string $slug): bool
    {
        try {
            log_info("Début d'ajout de coins", "ADD_COINS", [
                "user_id" => $userId,
                "event_slug" => $slug
            ]);

            // Get event coins value
            $stmt = $this->conn->prepare('SELECT coins FROM event_coins WHERE slug = :slug');
            $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
            $stmt->execute();

            $eventCoin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (empty($eventCoin['coins'])) {
                log_warning("Aucun coin trouvé pour l'événement", "ADD_COINS", [
                    "event_slug" => $slug
                ]);
                return false;
            }

            $coinsToAdd = (int)$eventCoin['coins'];
            $currentDate = date('Y-m-d H:i:s');

            // Vérifier d'abord si l'utilisateur existe déjà
            $userExists = $this->checkUserExists($userId);

            if ($userExists) {
                // Mise à jour si l'utilisateur existe
                $query = '
                    UPDATE user_coins 
                    SET value = value + :value, 
                        updateat = :updateat 
                    WHERE userid = :id
                ';
            } else {
                // Insertion si l'utilisateur n'existe pas
                $query = '
                    INSERT INTO user_coins 
                        (userid, value, updateat) 
                    VALUES 
                        (:id, :value, :updateat)
                ';
            }

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id', $userId, PDO::PARAM_STR);
            $stmt->bindValue(':value', $coinsToAdd, PDO::PARAM_INT);
            $stmt->bindValue(':updateat', $currentDate);
            $stmt->execute();

            // Record in history
            $historyQuery = "
                INSERT INTO history_coins 
                    (id, userId, valueCoin, eventName, description, createdAt, generateBy) 
                VALUES 
                    (:id, :userId, :valueCoin, :eventName, :description, :createdAt, :generateBy)
            ";

            $historyId = $this->generateGUID();
            $historyStmt = $this->conn->prepare($historyQuery);
            $historyStmt->bindValue(':id', $historyId, PDO::PARAM_STR);
            $historyStmt->bindValue(':userId', $userId, PDO::PARAM_STR);
            $historyStmt->bindValue(':valueCoin', $coinsToAdd, PDO::PARAM_INT);
            $historyStmt->bindValue(':eventName', $slug, PDO::PARAM_STR);
            $historyStmt->bindValue(':description', 'Coins added for: ' . $slug, PDO::PARAM_STR);
            $historyStmt->bindValue(':createdAt', $currentDate);
            $historyStmt->bindValue(':generateBy', 'system_event', PDO::PARAM_STR);

            $result = $historyStmt->execute();

            if ($result) {
                log_info("Coins ajoutés avec succès", "ADD_COINS", [
                    "user_id" => $userId,
                    "coins_added" => $coinsToAdd,
                    "history_id" => $historyId
                ]);
            } else {
                log_error("Échec de l'ajout des coins", "ADD_COINS", [
                    "user_id" => $userId
                ]);
            }

            return $result;
        } catch (Exception $e) {
            log_error("Erreur lors de l'ajout des coins", "ADD_COINS", [
                "user_id" => $userId,
                "error" => $e->getMessage()
            ]);
            return false;
        }
    }

    private function checkProfileConditions(string $userId): bool
    {
        try {
            log_debug("Vérification des conditions du profil", "CHECK_PROFILE", [
                "user_id" => $userId
            ]);

            $stmt = $this->conn->prepare('SELECT * FROM "userInfo" WHERE userid = :id');
            $stmt->bindValue(':id', $userId, PDO::PARAM_STR);
            $stmt->execute();

            $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (empty($userInfo)) {
                log_warning("Profil utilisateur non trouvé", "CHECK_PROFILE", [
                    "user_id" => $userId
                ]);
                return false;
            }   

            $requiredFields = $userInfo['profiletype'] == "particulier"
                ? ['pseudo', 'telephone']
                : ['nomsociete', 'activite', 'telephone', 'adresse', 'codepostal', 'ville', 'pays'];

            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (empty($userInfo[$field])) {
                    $missingFields[] = $field;
                }
            }

            // au moins un des champs validés
            $minRequiredFields = ['instagram', 'linkedin', 'facebook', 'tiktok', 'snapchat', 'youtube', 'x'];

            $hasAtLeastOneSocialMedia = false;
            foreach ($minRequiredFields as $field) {
                if (!empty($userInfo[$field])) {
                    $hasAtLeastOneSocialMedia = true;
                    break;
                }
            }
 
            if (!empty($missingFields) || !$hasAtLeastOneSocialMedia) {
                log_info("Champs de profil manquants", "CHECK_PROFILE", [
                    "user_id" => $userId,
                    "missing_fields" => $missingFields,
                    "profile_type" => $userInfo['profiletype']
                ]);
                return false;
            }

            return true;
        } catch (Exception $e) {
            log_error("Erreur lors de la vérification du profil", "CHECK_PROFILE", [
                "user_id" => $userId,
                "error" => $e->getMessage()
            ]);
            return false;
        }
    } 

}
