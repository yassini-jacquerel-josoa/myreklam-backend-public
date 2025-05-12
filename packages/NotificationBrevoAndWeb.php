<?php

include_once(__DIR__ . "/../db.php");
include_once(__DIR__ . "/../logger.php");
include_once(__DIR__ . "/GeneralHelper.php");

if (!class_exists('NotificationBrevoAndWeb')) {
    class NotificationBrevoAndWeb
    {
        public PDO $conn;

        // Mapping des templates et leurs variables
        public array $templates = [
            [
                "templateId" => 1,
                "templateSlug" => "ad-deleted",
                "variables" => ['username', 'ad.title ', 'ad.category']
            ],
            [
                "templateId" => 2,
                "templateSlug" => "welcome",
                "variables" => ['username']
            ],
            [
                "templateId" => 3,
                "templateSlug" => "ad-published",
                "variables" => ['username', 'ad.title', 'ad.category']
            ],
            [
                "templateId" => 4,
                "templateName" => "Mail via site",
                "templateSlug" => "mail-via-site",
                "variables" => ['username', 'mail_name', 'sender_email', 'recipient_email']
            ],
            [
                "templateId" => 5,
                "templateName" => "Résilisation",
                "templateSlug" => "resiliation",
                "variables" => ['plan_name', 'resiliation_date']
            ],
            [
                "templateId" => 6,
                "templateName" => "Changement de plan",
                "templateSlug" => "plan-change",
                "variables" => []
            ],
            [
                "templateId" => 7,
                "templateSlug" => "new-message",
                "variables" => ['username', 'sender', 'message_content']
            ],
            [
                "templateId" => 8,
                "templateName" => "Message reçu pour une annonce",
                "templateSlug" => "ad-message-received",
                "variables" => ['username', 'sender', 'ad.title', 'value', 'link']
            ],
            [
                "templateId" => 9,
                "templateName" => "Nouveau commentaire",
                "templateSlug" => "new-comment",
                "variables" => ['username', 'comment.username', 'ad.title', 'comment.content']
            ],
            [
                "templateId" => 10,
                "templateName" => "Réponse à un commentaire",
                "templateSlug" => "comment-reply",
                "variables" => ['username', 'ad.title', 'comment.username', 'comment.content', 'response.content', 'link']
            ],
            [
                "templateId" => 11,
                "templateSlug" => "account-created",
                "variables" => ['username']
            ],
            [
                "templateId" => 12,
                "templateName" => "suppression compte particulier",
                "templateSlug" => "delete-account-personal",
                "variables" => ['username']
            ],
            [
                "templateId" => 13,
                "templateName" => "suppression compte professionnel gratuit",
                "templateSlug" => "delete-account-professional",
                "variables" => ['username']
            ],
            [
                "templateId" => 14,
                "templateName" => "Annonce mise en ligne",
                "templateSlug" => "ad-created",
                "variables" => ['username', 'ad.title', 'ad.type', 'link']
            ],
            [
                "templateId" => 15,
                "templateName" => "Information postulant",
                "templateSlug" => "applicant-info",
                "variables" => ['username', 'ad.type', 'ad.title', 'organization.name']
            ],
            [
                "templateId" => 16,
                "templateName" => "Candidature reçue",
                "templateSlug" => "application-received",
                "variables" => ['username', 'applicant.username', 'ad.title']
            ],
            [
                "templateId" => 17,
                "templateName" => "Mail évènement",
                "templateSlug" => "event-registration",
                "variables" => ['username', 'event', 'organizator', 'dateStart', 'hoursStart', 'address', 'price', 'link']
            ],
            [
                "templateId" => 18,
                "templateName" => "Mail rappel évènement",
                "templateSlug" => "event-reminder",
                "variables" => ['username', 'ad.title', 'ad.organizator', 'startDate', 'startHours', 'localisation', 'price']
            ],
            [
                "templateId" => 19,
                "templateName" => "Inscription professionnel gratuit",
                "templateSlug" => "registration-professional-free",
                "variables" => ['username']
            ],
            [
                "templateId" => 20,
                "templateName" => "Inscription professionnel mensuel",
                "templateSlug" => "registration-professional-monthly",
                "variables" => ['username']
            ],
            [
                "templateId" => 21,
                "templateName" => "Inscription professionnel annuel",
                "templateSlug" => "registration-professional-yearly",
                "variables" => ['username']
            ],
            [
                "templateId" => 22,
                "templateName" => "Connexion",
                "templateSlug" => "login",
                "variables" => ['link']
            ],
            [
                "templateId" => 23,
                "templateName" => "Signalement",
                "templateSlug" => "report",
                "variables" => ['username']
            ],
            [
                "templateId" => 24,
                "templateName" => "Bannissement",
                "templateSlug" => "ban",
                "variables" => ['username', 'reason', 'endDate']
            ],
            [
                "templateId" => 25,
                "templateName" => "Mot de passe oublié",
                "templateSlug" => "forgot-password",
                "variables" => ['link']
            ],
            [
                "templateId" => 26,
                "templateSlug" => "general-notification",
                "variables" => []
            ],
            [
                "templateId" => 27,
                "templateName" => "Mot de passe modifié avec succès !",
                "templateSlug" => "password-changed",
                "variables" => []
            ],
            [
                "templateId" => 28,
                "templateName" => "",
                "templateSlug" => "empty-template",
                "variables" => []
            ],
            [
                "templateId" => 29,
                "templateName" => "Information postulant FORMATION",
                "templateSlug" => "formation-applicant-info",
                "variables" => ['username', 'ad.type', 'ad.title', 'organization.name']
            ],
            [
                "templateId" => 30,
                "templateName" => "Candidature reçue FORMATION particulier",
                "templateSlug" => "formation-application-received-personal",
                "variables" => ['username', 'ad.type', 'ad.title']
            ],
            [
                "templateId" => 31,
                "templateName" => "Candidature reçue FORMATION professionnel",
                "templateSlug" => "formation-application-received-professional",
                "variables" => ['username', 'applicant.username', 'ad.title',]
            ],
            [
                "templateId" => 32,
                "templateName" => "Alerte Annonce correspondant reçu",
                "templateSlug" => "alert-ad-match",
                "variables" => ['username', 'link']
            ],
        ];

        public function __construct(PDO $connection)
        {
            $this->conn = $connection;
            log_info("Connection initialisée   =>", "SYSTEM");
        }

        public function getTemplateId($templateSlug): int | null
        {
            log_info("Recherche du template ID pour le slug : " . $templateSlug, "SYSTEM");
            foreach ($this->templates as $template) {
                if (isset($template['templateSlug']) && $template['templateSlug'] === $templateSlug) {
                    log_info("Template trouvé : " . $template['templateId'], "SYSTEM");
                    return $template['templateId'];
                }
            }
            log_info("Aucun template trouvé pour le slug : " . $templateSlug, "SYSTEM");
            return null;
        }

        public function getTemplateParams($templateSlug): array | null
        {
            log_info("Recherche des params pour le slug : " . $templateSlug, "SYSTEM");
            foreach ($this->templates as $template) {
                if (isset($template['templateSlug']) && $template['templateSlug'] === $templateSlug) {
                    log_info("Params trouvés : " . $template['params'], "SYSTEM");
                    return $template['variables'];
                }
            }
            log_info("Aucun template trouvé pour le slug : " . $templateSlug, "SYSTEM");
            return null;
        }

        // candidature "emplois" : celui a poster l'offre
        public function sendNotificationAdEmploisCandidature($userId, $adId): bool
        {
            $userInfo = $this->getUserInfo($userId);
            $ad = GeneralHelper::getFormatedAd($adId);

            if (empty($userInfo) || empty($ad) || empty($ad['title']) || empty($ad['category'])) {
                log_info("Informations de l'utilisateur ou de l'annonce non trouvées", "SEND_NOTIFICATION_AD_EMPLOIS_CANDIDATURE", ["userId" => $userId, "ad" => $ad]);
                return false;
            }

            if ($ad['category'] !== 'emplois') {
                log_info("L'annonce n'est pas une annonce emplois", "SEND_NOTIFICATION_AD_EMPLOIS_CANDIDATURE", ["userId" => $userId, "ad" => $ad]);
                return false;
            }

            $resultWeb = $this->sendNotificationWeb([
                'user_id' => $userId,
                'content' => 'Vous avez reçu une candidature pour votre annonce : ' . $ad['title'],
                'return_url' => '/mes-annonces'
            ]);

            $resultBrevo = $this->sendNotificationBrevo([
                'email' => $userInfo['email'],
                'templateId' => $this->getTemplateId("application-received"),
                'params' => [
                    'username' => $userInfo['username'],
                    'applicant.username' => 'Candidat', // Nécessite de récupérer l'info du candidat
                    'ad.title' => $ad['title']
                ]
            ]);

            return $resultWeb || $resultBrevo;
        }

        // candidature "emplois" : celui qui postule
        public function sendNotificationAdEmploisPostulant($userId, $adId, $offreUserId): bool
        {
            $userInfo = $this->getUserInfo($userId);
            $ad = GeneralHelper::getFormatedAd($adId);
            $offreUserInfo = $this->getUserInfo($offreUserId);

            if (empty($userInfo) || empty($ad) || empty($ad['title']) || empty($ad['category']) || empty($offreUserInfo)) {
                log_info("Informations manquantes", "SEND_NOTIFICATION_AD_EMPLOIS_POSTULANT", ["userId" => $userId, "ad" => $ad, "offreUserInfo" => $offreUserInfo]);
                return false;
            }

            if ($ad['category'] !== 'emplois') {
                log_info("L'annonce n'est pas une annonce emplois", "SEND_NOTIFICATION_AD_EMPLOIS_POSTULANT", ["userId" => $userId, "ad" => $ad]);
                return false;
            }

            $resultWeb = $this->sendNotificationWeb([
                'user_id' => $userId,
                'content' => 'Votre candidature a été envoyée pour : ' . $ad['title'],
                'return_url' => '/mes-candidatures'
            ]);

            $resultBrevo = $this->sendNotificationBrevo([
                'email' => $userInfo['email'],
                'templateId' => $this->getTemplateId("applicant-info"),
                'params' => [
                    'username' => $userInfo['username'],
                    'ad.type' => strtolower($ad['categoryLabel']),
                    'ad.title' => $ad['title'],
                    'organization.name' => $offreUserInfo['username']
                ]
            ]);

            return $resultWeb || $resultBrevo;
        }

        // participation "evenements" : celui qui participe
        public function sendNotificationAdEvenementsParticipant($userId, $adId): bool
        {
            $userInfo = $this->getUserInfo($userId);
            $ad = GeneralHelper::getFormatedAd($adId);

            if (empty($userInfo) || empty($ad) || empty($ad['title']) || empty($ad['category'])) {
                log_info("Informations manquantes", "SEND_NOTIFICATION_AD_EVENEMENTS_PARTICIPANT", ["userId" => $userId, "ad" => $ad]);
                return false;
            }

            if ($ad['category'] !== 'evenements') {
                log_info("L'annonce n'est pas une annonce evenements", "SEND_NOTIFICATION_AD_EVENEMENTS_PARTICIPANT", ["userId" => $userId, "ad" => $ad]);
                return false;
            }

            $resultWeb = $this->sendNotificationWeb([
                'user_id' => $userId,
                'content' => 'Vous participez à l\'événement : ' . $ad['title'],
                'return_url' => '/mes-participations'
            ]);

            $resultBrevo = $this->sendNotificationBrevo([
                'email' => $userInfo['email'],
                'templateId' => $this->getTemplateId("event-registration"),
                'params' => [
                    'username' => $userInfo['username'],
                    'event' => $ad['title'],
                    'organizator' => $ad['organizator'] ?? 'Organisateur',
                    'dateStart' => $ad['startDate'] ?? 'Date de début',
                    'hoursStart' => $ad['startHours'] ?? 'Heure de début',
                    'address' => $ad['address'] ?? 'Adresse',
                    'price' => $ad['price'] ?? 'Gratuit',
                    'link' => '/evenement/' . $adId
                ]
            ]);

            return $resultWeb || $resultBrevo;
        }

        // rappel de participation 24h avant l'evenement
        public function sendNotificationAdEvenementsRappel($userId, $adId): bool
        {
            $userInfo = $this->getUserInfo($userId);
            $ad = GeneralHelper::getFormatedAd($adId);

            if (empty($userInfo) || empty($ad) || empty($ad['title']) || empty($ad['category'])) {
                log_info("Informations manquantes", "SEND_NOTIFICATION_AD_EVENEMENTS_RAPPEL", ["userId" => $userId, "ad" => $ad]);
                return false;
            }

            if ($ad['category'] !== 'evenements') {
                log_info("L'annonce n'est pas une annonce evenements", "SEND_NOTIFICATION_AD_EVENEMENTS_RAPPEL", ["userId" => $userId, "ad" => $ad]);
                return false;
            }

            $resultWeb = $this->sendNotificationWeb([
                'user_id' => $userId,
                'content' => 'Rappel : L\'événement ' . $ad['title'] . ' a lieu demain !',
                'return_url' => '/mes-participations'
            ]);

            $resultBrevo = $this->sendNotificationBrevo([
                'email' => $userInfo['email'],
                'templateId' => $this->getTemplateId("event-reminder"),
                'params' => [
                    'username' => $userInfo['username'],
                    'ad.title' => $ad['title'],
                    'ad.organizator' => $ad['organizator'] ?? 'Organisateur',
                    'startDate' => $ad['startDate'] ?? 'Date de début',
                    'startHours' => $ad['startHours'] ?? 'Heure de début',
                    'localisation' => $ad['address'] ?? 'Adresse',
                    'price' => $ad['price'] ?? 'Gratuit'
                ]
            ]);

            return $resultWeb || $resultBrevo;
        }

        // candidature formation : celui qui postule
        public function sendNotificationAdFormationPostulant($userId, $adId, $offreUserId): bool
        {
            $userInfo = $this->getUserInfo($userId);
            $ad = GeneralHelper::getFormatedAd($adId);
            $offreUserInfo = $this->getUserInfo($offreUserId);

            if (empty($userInfo) || empty($ad) || empty($ad['title']) || empty($ad['category']) || empty($offreUserInfo)) {
                log_info("Informations manquantes", "SEND_NOTIFICATION_AD_FORMATION_POSTULANT", ["userId" => $userId, "ad" => $ad, "offreUserInfo" => $offreUserInfo]);
                return false;
            }

            if ($ad['category'] !== 'formations') {
                log_info("L'annonce n'est pas une annonce formations", "SEND_NOTIFICATION_AD_FORMATION_POSTULANT", ["userId" => $userId, "ad" => $ad]);
                return false;
            }

            $resultWeb = $this->sendNotificationWeb([
                'user_id' => $userId,
                'content' => 'Votre candidature a été envoyée pour la formation : ' . $ad['title'],
                'return_url' => '/mes-candidatures'
            ]);

            $resultBrevo = $this->sendNotificationBrevo([
                'email' => $userInfo['email'],
                'templateId' => $this->getTemplateId("formation-applicant-info"),
                'params' => [
                    'username' => $userInfo['username'],
                    'ad.type' => strtolower($ad['categoryLabel']),
                    'ad.title' => $ad['title'],
                    'organization.name' => $offreUserInfo['username']
                ]
            ]);

            return $resultWeb || $resultBrevo;
        }

        // candidature reçue par un particulier "formation"
        public function sendNotificationAdFormationCandidatureParticulier($userId, $adId): bool
        {
            $userInfo = $this->getUserInfo($userId);
            $ad = GeneralHelper::getFormatedAd($adId);

            if (empty($userInfo) || empty($ad) || empty($ad['title']) || empty($ad['category'])) {
                log_info("Informations manquantes", "SEND_NOTIFICATION_AD_FORMATION_CANDIDATURE_PARTICULIER", ["userId" => $userId, "ad" => $ad]);
                return false;
            }

            if ($ad['category'] !== 'formations' || $userInfo['profiletype'] !== 'particulier') {
                log_info("L'annonce n'est pas une annonce formations ou l'utilisateur n'est pas un particulier", "SEND_NOTIFICATION_AD_FORMATION_CANDIDATURE_PARTICULIER", ["userId" => $userId, "ad" => $ad]);
                return false;
            }

            $resultWeb = $this->sendNotificationWeb([
                'user_id' => $userId,
                'content' => 'Vous avez reçu une candidature pour votre formation : ' . $ad['title'],
                'return_url' => '/mes-annonces'
            ]);

            $resultBrevo = $this->sendNotificationBrevo([
                'email' => $userInfo['email'],
                'templateId' => $this->getTemplateId("formation-application-received-personal"),
                'params' => [
                    'username' => $userInfo['username'],
                    'ad.type' => strtolower($ad['categoryLabel']),
                    'ad.title' => $ad['title']
                ]
            ]);

            return $resultWeb || $resultBrevo;
        }

        // candidature reçue par un professionnel "formation"
        public function sendNotificationAdFormationCandidatureProfessionnel($userId, $adId, $applicantId): bool
        {
            $userInfo = $this->getUserInfo($userId);
            $ad = GeneralHelper::getFormatedAd($adId);
            $applicantInfo = $this->getUserInfo($applicantId);

            if (empty($userInfo) || empty($ad) || empty($ad['title']) || empty($ad['category']) || empty($applicantInfo)) {
                log_info("Informations manquantes", "SEND_NOTIFICATION_AD_FORMATION_CANDIDATURE_PROFESSIONNEL", ["userId" => $userId, "ad" => $ad, "applicantInfo" => $applicantInfo]);
                return false;
            }

            if ($ad['category'] !== 'formations' || $userInfo['profiletype'] !== 'professionnel') {
                log_info("L'annonce n'est pas une annonce formations ou l'utilisateur n'est pas un professionnel", "SEND_NOTIFICATION_AD_FORMATION_CANDIDATURE_PROFESSIONNEL", ["userId" => $userId, "ad" => $ad]);
                return false;
            }

            $resultWeb = $this->sendNotificationWeb([
                'user_id' => $userId,
                'content' => 'Vous avez reçu une candidature pour votre formation : ' . $ad['title'],
                'return_url' => '/mes-annonces'
            ]);

            $resultBrevo = $this->sendNotificationBrevo([
                'email' => $userInfo['email'],
                'templateId' => $this->getTemplateId("formation-application-received-professional"),
                'params' => [
                    'username' => $userInfo['username'],
                    'applicant.username' => $applicantInfo['username'],
                    'ad.title' => $ad['title']
                ]
            ]);

            return $resultWeb || $resultBrevo;
        }

        // Méthode pour envoyer une notification d'inscription
        public function sendNotificationRegistration($userId, $planType = null): bool
        {
            $userInfo = $this->getUserInfo($userId);

            if (empty($userInfo)) {
                log_info("Informations de l'utilisateur non trouvées", "SEND_NOTIFICATION_REGISTRATION", ["userId" => $userId]);
                return false;
            }

            $resultWeb = $this->sendNotificationWeb([
                'user_id' => $userId,
                'content' => 'Bienvenue sur MyReklam ! Complétez votre profil pour avoir plus de visibilité.',
                'return_url' => '/profile'
            ]);

            $templateId = null;
            if ($planType === 'free') {
                $templateId = $this->getTemplateId("registration-professional-free");
            } elseif ($planType === 'monthly') {
                $templateId = $this->getTemplateId("registration-professional-monthly");
            } elseif ($planType === 'yearly') {
                $templateId = $this->getTemplateId("registration-professional-yearly");
            } else {
                $templateId = $this->getTemplateId("registration-professional-free"); // Par défaut
            }

            $resultBrevo = $this->sendNotificationBrevo([
                'email' => $userInfo['email'],
                'templateId' => $templateId,
                'params' => [
                    'username' => $userInfo['username']
                ]
            ]);

            return $resultWeb || $resultBrevo;
        }

        // Méthode pour envoyer une notification de suppression de compte particulier
        public function sendNotificationDeleteAccountParticulier($userId): bool
        {
            $userInfo = $this->getUserInfo($userId);

            if (empty($userInfo) || $userInfo['profiletype'] !== 'particulier') {
                log_info("Informations de l'utilisateur non trouvées ou l'utilisateur n'est pas un particulier", "SEND_NOTIFICATION_DELETE_ACCOUNT_PARTICULIER", ["userId" => $userId]);
                return false;
            }

            $resultBrevo = $this->sendNotificationBrevo([
                'email' => $userInfo['email'],
                'templateId' => $this->getTemplateId("delete-account-personal"),
                'params' => [
                    'username' => $userInfo['username']
                ]
            ]);

            return $resultBrevo;
        }

        // Méthode pour envoyer une notification de suppression de compte professionnel
        public function sendNotificationDeleteAccountProfessionnel($userId): bool
        {
            $userInfo = $this->getUserInfo($userId);

            if (empty($userInfo) || $userInfo['profiletype'] !== 'professionnel') {
                log_info("Informations de l'utilisateur non trouvées ou l'utilisateur n'est pas un professionnel", "SEND_NOTIFICATION_DELETE_ACCOUNT_PROFESSIONNEL", ["userId" => $userId]);
                return false;
            }

            $resultBrevo = $this->sendNotificationBrevo([
                'email' => $userInfo['email'],
                'templateId' => $this->getTemplateId("delete-account-professional"),
                'params' => [
                    'username' => $userInfo['username']
                ]
            ]);

            return $resultBrevo;
        }

        // Méthode pour envoyer une notification de changement d'abonnement
        public function sendNotificationChangePlan($userId, $newPlan): bool
        {
            $userInfo = $this->getUserInfo($userId);

            if (empty($userInfo)) {
                log_info("Informations de l'utilisateur non trouvées", "SEND_NOTIFICATION_CHANGE_PLAN", ["userId" => $userId]);
                return false;
            }

            $resultBrevo = $this->sendNotificationBrevo([
                'email' => $userInfo['email'],
                'templateId' => $this->getTemplateId("plan-change"),
                'params' => []
            ]);

            return $resultBrevo;
        }

        // Méthode pour envoyer une notification de résiliation d'abonnement
        public function sendNotificationCancelSubscription($userId, $planName, $resiliationDate): bool
        {
            $userInfo = $this->getUserInfo($userId);

            if (empty($userInfo)) {
                log_info("Informations de l'utilisateur non trouvées", "SEND_NOTIFICATION_CANCEL_SUBSCRIPTION", ["userId" => $userId]);
                return false;
            }

            $resultWeb = $this->sendNotificationWeb([
                'user_id' => $userId,
                'content' => 'Votre abonnement a été résilié. Fin de votre abonnement: ' . $resiliationDate,
                'return_url' => '/abonnement'
            ]);

            $resultBrevo = $this->sendNotificationBrevo([
                'email' => $userInfo['email'],
                'templateId' => $this->getTemplateId("resiliation"),
                'params' => [
                    'plan_name' => $planName,
                    'resiliation_date' => $resiliationDate
                ]
            ]);

            return $resultWeb || $resultBrevo;
        }

        // Méthode pour envoyer une notification d'ajout d'avis
        public function sendNotificationReview($userId, $reviewerId, $content): bool
        {
            $userInfo = $this->getUserInfo($userId);
            $reviewerInfo = $this->getUserInfo($reviewerId);

            if (empty($userInfo) || empty($reviewerInfo)) {
                log_info("Informations des utilisateurs non trouvées", "SEND_NOTIFICATION_REVIEW", ["userId" => $userId, "reviewerId" => $reviewerId, "userInfo" => $userInfo, "reviewerInfo" => $reviewerInfo]);
                return false;
            }

            $resultBrevo = $this->sendNotificationBrevo([
                'email' => $userInfo['email'],
                'templateId' => $this->getTemplateId("new-comment"),
                'params' => [
                    'username' => $userInfo['username'],
                    'comment.username' => $reviewerInfo['username'],
                    'comment.content' => $content
                ]
            ]);

            return $resultBrevo;
        }

        // Méthode pour envoyer une notification d'alerte (match avec des critères de recherche)
        public function sendNotificationAlert($userId, $alertId): bool
        {
            $userInfo = $this->getUserInfo($userId);

            if (empty($userInfo)) {
                log_info("Informations de l'utilisateur non trouvées", "SEND_NOTIFICATION_ALERT", ["userId" => $userId, "userInfo" => $userInfo]);
                return false;
            }

            $resultBrevo = $this->sendNotificationBrevo([
                'email' => $userInfo['email'],
                'templateId' => $this->getTemplateId("alert-ad-match"),
                'params' => [
                    'username' => $userInfo['username'],
                    'link' => '/alerte/' . $alertId
                ]
            ]);

            return $resultBrevo;
        }

        // Méthode pour envoyer une notification de message reçu pour une annonce
        public function sendNotificationAdMessage($userId, $adId, $senderId, $message): bool
        {
            $userInfo = $this->getUserInfo($userId);
            $ad = GeneralHelper::getFormatedAd($adId);
            $senderInfo = $this->getUserInfo($senderId);

            if (empty($userInfo) || empty($ad) || empty($ad['title']) || empty($senderInfo)) {
                log_info("Informations manquantes", "SEND_NOTIFICATION_AD_MESSAGE", ["userId" => $userId, "ad" => $ad, "senderInfo" => $senderInfo]);
                return false;
            }

            $resultWeb = $this->sendNotificationWeb([
                'user_id' => $userId,
                'content' => 'Vous avez reçu un message de ' . $senderInfo['username'] . ' concernant votre annonce: ' . $ad['title'],
                'return_url' => '/messages'
            ]);

            $resultBrevo = $this->sendNotificationBrevo([
                'email' => $userInfo['email'],
                'templateId' => $this->getTemplateId("ad-message-received"),
                'params' => [
                    'username' => $userInfo['username'],
                    'sender' => $senderInfo['username'],
                    'ad.title' => $ad['title'],
                    'value' => $message,
                    'link' => '/messages'
                ]
            ]);

            return $resultWeb || $resultBrevo;
        }

        // Méthode pour envoyer une notification lors de la suppression d'annonce supprimée
        public function sendNotificationAdDeleted($userId, $adId): bool
        {
            $userInfo = $this->getUserInfo($userId);
            $ad = GeneralHelper::getFormatedAd($adId);

            if (empty($userInfo) || empty($ad) || empty($ad['title']) || empty($ad['category'])) {
                log_info("Informations de l'utilisateur ou de l'annonce non trouvées", "SEND_NOTIFICATION_AD_DELETED", ["userId" => $userId, "ad" => $ad, "userInfo" => $userInfo]);
                return false;
            }

            $resultWeb = $this->sendNotificationWeb([
                'user_id' => $userId,
                'content' => 'Votre annonce a été supprimée ' . $ad['title'] . ' dans la catégorie ' . strtolower($ad['categoryLabel']),
                'return_url' => '/mes-annonces'
            ]);

            $resultBrevo = $this->sendNotificationBrevo([
                'email' => $userInfo['email'],
                'templateId' => $this->getTemplateId("ad-deleted"),
                'params' => [
                    'username' => $userInfo['username'],
                    'ad.title' => $ad['title'],
                    'ad.category' => strtolower($ad['categoryLabel'])
                ]
            ]);

            return $resultWeb || $resultBrevo;
        }

        // Méthode pour envoyer une notification de souscription gratuite
        public function sendNotificationSubscriptionFree($userId): bool
        {
            $userInfo = $this->getUserInfo($userId);

            if (empty($userInfo)) {
                log_info("Informations de l'utilisateur non trouvées", "SEND_NOTIFICATION_SUBSCRIPTION_FREE", ["userId" => $userId]);
                return false;
            }

            // - complete profile (pour la premier inscription) MyReklam
            $resultWeb = $this->sendNotificationWeb([
                'user_id' => $userId,
                'content' => 'Bienvenue sur MyReklam, vous pouvez compléter votre profil pour avoir de my\'s ',
                'return_url' => '/dashboard'
            ]);
            $resultBrevo = $this->sendNotificationBrevo([
                'email' => $userInfo['email'],
                'templateId' => $this->getTemplateId("registration-professional-free"),
                'params' => [
                    'username' => $userInfo['username'],
                ]
            ]);

            return $resultWeb || $resultBrevo;
        }

        // Méthode pour récupérer les informations de l'utilisateur
        public function getUserInfo($userId): array | null
        {
            try {
                $query = 'SELECT * FROM "users" WHERE "Id" = :id';
                $statement = $this->conn->prepare($query);
                $statement->bindParam(':id', $userId);
                $statement->execute();
                $user = $statement->fetch(PDO::FETCH_ASSOC);

                if (empty($user)) {
                    log_info("Utilisateur non trouvé", "GET_USER", ["userId" => $userId]);
                    return null;
                }

                $query = 'SELECT * FROM "userInfo" WHERE userid = :id';
                $statement = $this->conn->prepare($query);
                $statement->bindParam(':id', $userId);
                $statement->execute();
                $userInfo = $statement->fetch(PDO::FETCH_ASSOC);

                if (empty($userInfo)) {
                    log_info("Informations de l'utilisateur non trouvées", "GET_USER_INFO", ["userId" => $userId]);
                    return null;
                }

                $userInfo['email'] = $user['Email'];
                $userInfo['username'] =  $userInfo['profiletype'] == 'particulier' ? $userInfo['pseudo'] : $userInfo['nomsociete'];

                return $userInfo;
            } catch (Exception $e) {
                log_info("Exception lors de la récupération des informations de l'utilisateur", "GET_USER_INFO", ["message" => $e->getMessage()]);
                return null;
            }
        }

        // Méthodes send Noitifcation web
        public function sendNotificationWeb(array $data = []): bool
        {
            try {
                if (empty($data['user_id']) || empty($data['content'])) {
                    log_info("User ID ou content manquant", "SEND_NOTIFICATION_WEB", ["data" => $data]);
                    return false;
                }

                $query = 'INSERT INTO "notifications" ("id", "user_id", "content", "type", "is_read", "return_url", "metadata") VALUES (:id, :user_id, :content, :type, :is_read, :return_url, :metadata)';
                $statement = $this->conn->prepare($query);

                $id = $this->generateGUID();

                // defaut value
                if (!isset($data['type'])) {
                    $data['type'] = "info";
                }
                if (!isset($data['is_read'])) {
                    $data['is_read'] = $data['is_read'] ? 1 : 0;
                }
                if (!isset($data['return_url'])) {
                    $data['return_url'] = null;
                }
                if (!isset($data['metadata'])) {
                    $data['metadata'] = null;
                }

                $statement->bindParam(':id', $id);
                $statement->bindParam(':user_id', $data['user_id']);
                $statement->bindParam(':content', $data['content']);
                $statement->bindParam(':type', $data['type']);
                $statement->bindParam(':is_read', $data['is_read']);
                $statement->bindParam(':return_url', $data['return_url']);
                $statement->bindParam(':metadata', $data['metadata']);

                $result = $statement->execute();

                if ($result) {
                    log_info("Notification web envoyée avec succès", "SEND_NOTIFICATION_WEB", ["user_id" => $data['user_id']]);
                    return true;
                }

                log_info("Échec de l'envoi de la notification web", "SEND_NOTIFICATION_WEB", ["user_id" => $data['user_id']]);
                return false;
            } catch (Exception $e) {
                echo "Exception lors de l'envoi de la notification web" . $e->getMessage();
                log_info("Exception lors de l'envoi de la notification web", "SEND_NOTIFICATION_WEB", ["message" => $e->getMessage()]);
                return false;
            }
        }

        // Méthodes send Noitifcation brevo
        public function sendNotificationBrevo(array $data = []): bool
        {
            log_info("Envoi de la notification brevo", "SEND_NOTIFICATION_BREVO", ["data" => $data]);
            try {
                $email = $data['email'] ?? '';
                $name = $data['name'] ?? '';
                $templateId = $data['templateId'] ?? null;
                $paramsData = $data['params'] ?? [];

                log_info("Email", "SEND_NOTIFICATION_BREVO", ["email" => $email]);
                log_info("TemplateId", "SEND_NOTIFICATION_BREVO", ["templateId" => $templateId]);
                log_info("ParamsData", "SEND_NOTIFICATION_BREVO", ["paramsData" => $paramsData]);

                if (empty($email) || empty($templateId) || empty($paramsData)) {
                    log_info("Email ou templateId manquant", "SEND_NOTIFICATION_BREVO", ["email" => $email, "templateId" => $templateId]);
                    return false;
                }

                log_info("sendNotificationBrevo 0");

                // Configure API key authorization
                $config = SendinBlue\Client\Configuration::getDefaultConfiguration()
                    ->setApiKey('api-key', $_ENV['SENDINBLUE_API_KEY']);

                log_info("sendNotificationBrevo 1");

                // Vérification si le templateId est valide
                if (!array_key_exists($templateId, $this->templates)) {
                    log_info("TemplateId non reconnu", "SEND_NOTIFICATION_BREVO", ["templateId" => $templateId]);
                    return false;
                }

                log_info("sendNotificationBrevo 2");

                // Construction des paramètres requis
                $params = [];
                if (isset($this->getTemplateParams($templateId)) && is_array($this->getTemplateParams($templateId))) {
                    foreach ($this->getTemplateParams($templateId) as $variable) {
                        if (isset($paramsData[$variable])) {
                            $params[$variable] = $paramsData[$variable];
                        }
                    }
                } 

                log_info("sendNotificationBrevo 3");

                // Initialize API instance
                $apiInstance = new SendinBlue\Client\Api\TransactionalEmailsApi(
                    new GuzzleHttp\Client(),
                    $config
                );

                log_info("sendNotificationBrevo 4");

                // Configuration de l'email
                $sendSmtpEmail = new \SendinBlue\Client\Model\SendSmtpEmail([
                    'to' => [['email' => $email, 'name' => $name ?: 'Utilisateur']],
                    'templateId' => $templateId,
                    'params' => $params,
                    'headers' => ['X-Mailin-custom' => 'custom_header_1:custom_value_1|custom_header_2:custom_value_2']
                ]);

                log_info("sendNotificationBrevo 5");

                // Envoi de l'email
                $result = $apiInstance->sendTransacEmail($sendSmtpEmail);

                log_info("sendNotificationBrevo 6");

                if ($result && $result->getMessageId()) {
                    log_info("Email envoyé avec succès", "SEND_NOTIFICATION_BREVO", ["email" => $email, "templateId" => $templateId]);
                    return true;
                } else {
                    log_info("Échec de l'envoi d'email", "SEND_NOTIFICATION_BREVO", ["email" => $email, "templateId" => $templateId]);
                    return false;
                }
            } catch (Exception $e) {
                log_info("Exception lors de l'envoi d'email", "SEND_NOTIFICATION_BREVO", ["message" => $e->getMessage()]);
                return false;
            }
        }

        // Méthodes internes
        public function generateGUID(): string
        {
            $guid = bin2hex(random_bytes(16));
            log_debug("GUID généré", "GENERATE_GUID", ["guid" => $guid]);
            return $guid;
        }

        // creation annonce formation
        public function sendNotificationAdFormation($userId, $adId): bool
        {
            $userInfo = $this->getUserInfo($userId);
            $ad = GeneralHelper::getFormatedAd($adId);

            if (empty($userInfo) || empty($ad) || empty($ad['title']) || empty($ad['category'])) {
                log_info("Informations de l'utilisateur ou de l'annonce non trouvées", "SEND_NOTIFICATION_AD_FORMATION", ["userId" => $userId, "ad" => $ad, "userInfo" => $userInfo]);
                return false;
            }

            if ($ad['category'] !== 'formations') {
                log_info("L'annonce n'est pas une annonce formations", "SEND_NOTIFICATION_AD_FORMATION", ["userId" => $userId, "ad" => $ad, "userInfo" => $userInfo]);
                return false;
            }

            $resultWeb = $this->sendNotificationWeb([
                'user_id' => $userId,
                'content' => 'Votre annonce a été créée',
                'return_url' => '/mes-annonces'
            ]);

            $resultBrevo = $this->sendNotificationBrevo([
                'email' => $userInfo['email'],
                'templateId' => $this->getTemplateId("ad-created"),
                'params' => [
                    'username' => $userInfo['username'],
                    'ad.title' => $ad['title'],
                    'ad.type' => strtolower($ad['categoryLabel']),
                    'link' => '/annonce/' . $adId
                ]
            ]);

            return $resultWeb || $resultBrevo;
        }

        // creation annonce evenements
        public function sendNotificationAdEvenements($userId, $adId): bool
        {
            $userInfo = $this->getUserInfo($userId);
            $ad = GeneralHelper::getFormatedAd($adId);

            if (empty($userInfo) || empty($ad) || empty($ad['title']) || empty($ad['category'])) {
                log_info("Informations de l'utilisateur ou de l'annonce non trouvées", "SEND_NOTIFICATION_AD_EVENEMENTS", ["userId" => $userId, "ad" => $ad]);
                return false;
            }

            if ($ad['category'] !== 'evenements') {
                log_info("L'annonce n'est pas une annonce evenements", "SEND_NOTIFICATION_AD_EVENEMENTS", ["userId" => $userId, "ad" => $ad]);
                return false;
            }

            $resultWeb = $this->sendNotificationWeb([
                'user_id' => $userId,
                'content' => 'Votre annonce a été créée',
                'return_url' => '/mes-annonces'
            ]);

            $resultBrevo = $this->sendNotificationBrevo([
                'email' => $userInfo['email'],
                'templateId' => $this->getTemplateId("ad-created"),
                'params' => [
                    'username' => $userInfo['username'],
                    'ad.title' => $ad['title'],
                    'ad.type' => strtolower($ad['categoryLabel']),
                    'link' => '/annonce/' . $adId
                ]
            ]);

            return $resultWeb || $resultBrevo;
        }

        // creation annonce demandes
        public function sendNotificationAdDemandes($userId, $adId): bool
        {
            $userInfo = $this->getUserInfo($userId);
            $ad = GeneralHelper::getFormatedAd($adId);

            if (empty($userInfo) || empty($ad) || empty($ad['title']) || empty($ad['category'])) {
                log_info("Informations de l'utilisateur ou de l'annonce non trouvées", "SEND_NOTIFICATION_AD_DEMANDES", ["userId" => $userId, "ad" => $ad]);
                return false;
            }

            if ($ad['category'] !== 'demandes') {
                log_info("L'annonce n'est pas une annonce demandes", "SEND_NOTIFICATION_AD_DEMANDES", ["userId" => $userId, "ad" => $ad]);
                return false;
            }

            $resultWeb = $this->sendNotificationWeb([
                'user_id' => $userId,
                'content' => 'Votre annonce a été créée',
                'return_url' => '/mes-annonces'
            ]);

            $resultBrevo = $this->sendNotificationBrevo([
                'email' => $userInfo['email'],
                'templateId' => $this->getTemplateId("ad-created"),
                'params' => [
                    'username' => $userInfo['username'],
                    'ad.title' => $ad['title'],
                    'ad.type' => strtolower($ad['categoryLabel']),
                    'link' => '/annonce/' . $adId
                ]
            ]);

            return $resultWeb || $resultBrevo;
        }

        // creation annonce emplois
        public function sendNotificationAdEmplois($userId, $adId): bool
        {
            $userInfo = $this->getUserInfo($userId);
            $ad = GeneralHelper::getFormatedAd($adId);

            if (empty($userInfo) || empty($ad) || empty($ad['title']) || empty($ad['category'])) {
                log_info("Informations de l'utilisateur ou de l'annonce non trouvées", "SEND_NOTIFICATION_AD_EMPLOIS", ["userId" => $userId, "ad" => $ad]);
                return false;
            }

            if ($ad['category'] !== 'emplois') {
                log_info("L'annonce n'est pas une annonce emplois", "SEND_NOTIFICATION_AD_EMPLOIS", ["userId" => $userId, "ad" => $ad]);
                return false;
            }

            $resultWeb = $this->sendNotificationWeb([
                'user_id' => $userId,
                'content' => 'Votre annonce a été créée',
                'return_url' => '/mes-annonces'
            ]);

            $resultBrevo = $this->sendNotificationBrevo([
                'email' => $userInfo['email'],
                'templateId' => $this->getTemplateId("ad-created"),
                'params' => [
                    'username' => $userInfo['username'],
                    'ad.title' => $ad['title'],
                    'ad.type' => strtolower($ad['categoryLabel']),
                    'link' => '/annonce/' . $adId
                ]
            ]);

            return $resultWeb || $resultBrevo;
        }

        // creation annonce bon plan
        public function sendNotificationAdBonPlan($userId, $adId): bool
        {
            $userInfo = $this->getUserInfo($userId);
            $ad = GeneralHelper::getFormatedAd($adId);

            if (empty($userInfo) || empty($ad) || empty($ad['title']) || empty($ad['category'])) {
                log_info("Informations de l'utilisateur ou de l'annonce non trouvées", "SEND_NOTIFICATION_AD_BON_PLAN", ["userId" => $userId, "ad" => $ad]);
                return false;
            }

            if ($ad['category'] !== 'bon_plans') {
                log_info("L'annonce n'est pas une annonce bon plan", "SEND_NOTIFICATION_AD_BON_PLAN", ["userId" => $userId, "ad" => $ad]);
                return false;
            }

            $resultWeb = $this->sendNotificationWeb([
                'user_id' => $userId,
                'content' => 'Votre annonce a été créée',
                'return_url' => '/mes-annonces'
            ]);

            $resultBrevo = $this->sendNotificationBrevo([
                'email' => $userInfo['email'],
                'templateId' => $this->getTemplateId("ad-created"),
                'params' => [
                    'username' => $userInfo['username'],
                    'ad.title' => $ad['title'],
                    'ad.type' => strtolower($ad['categoryLabel']),
                    'link' => '/annonce/' . $adId
                ]
            ]);

            return $resultWeb || $resultBrevo;
        }
    }
}
