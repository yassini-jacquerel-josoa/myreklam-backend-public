<?php

include_once("./db.php");
include_once("./logger.php");

class GeneralHelper
{
    public static PDO $conn;
    
    public function __construct(PDO $connection)
    {
        self::$conn = $connection;
    }

    public static function getFormatedAd($adId)
    {
        $query = 'SELECT * FROM "ads" WHERE "id" = :id';
        $statement = self::$conn->prepare($query);
        $statement->bindParam(':id', $adId);
        $statement->execute();
        $ad = $statement->fetch(PDO::FETCH_ASSOC);

        if (empty($ad)) {
            log_error("Annonce non trouvée", "GET_AD", ["adId" => $adId]);
            return null;
        }

        if(isset($ad['category']) && in_array($ad['category'], ['bon_plans', 'emplois', 'formations', 'evenements'])) {
            $ad['title'] = $ad['title'];
        }

        if(isset($ad['category']) && $ad['category'] == 'demandes') {
            $ad['title'] = $ad['inquiryTitle'];
        }

        // categorie label
        if(isset($ad['category']) && $ad['category'] == 'demandes') {
            $ad['categoryLabel'] = 'Demande';
        }

        if(isset($ad['category']) && $ad['category'] == 'formations') {
            $ad['categoryLabel'] = 'Formation';
        }

        if(isset($ad['category']) && $ad['category'] == 'evenements') {
            $ad['categoryLabel'] = 'Événement';
        }

        if(isset($ad['category']) && $ad['category'] == 'emplois') {
            $ad['categoryLabel'] = 'Emploi';
        }

        if(isset($ad['category']) && $ad['category'] == 'bon_plans') {
            $ad['categoryLabel'] = 'Bon plan';
        }
        

        return $ad;
    }

    public static function getFormatedComment($commentId)
    {
        $query = 'SELECT * FROM "commentaires" WHERE "id" = :id';
        $statement = self::$conn->prepare($query);
        $statement->bindParam(':id', $commentId);
        $statement->execute();
        $comment = $statement->fetch(PDO::FETCH_ASSOC);

        if (empty($comment)) {
            log_error("Commentaire non trouvé", "GET_COMMENT", ["commentId" => $commentId]);
            return null;
        }

        if(isset($comment['commentaire'])) {
            $comment['content'] = html_entity_decode($comment['commentaire']);
        }

        if(isset($comment['userid'])) {
            $user = self::getUserInfo($comment['userid']);
            $comment['username'] = $user['username'];
        }

        return $comment;
    }

    
    // Méthode pour récupérer les informations de l'utilisateur
    public static function getUserInfo($userId): array | null
    {
        try {
            $query = 'SELECT * FROM "users" WHERE "Id" = :id';
            $statement = self::$conn->prepare($query);
            $statement->bindParam(':id', $userId);
            $statement->execute();
            $user = $statement->fetch(PDO::FETCH_ASSOC);

            if (empty($user)) {
                log_error("Utilisateur non trouvé", "GET_USER", ["userId" => $userId]);
                return null;
            }

            $query = 'SELECT * FROM "userInfo" WHERE userid = :id';
            $statement = self::$conn->prepare($query);
            $statement->bindParam(':id', $userId);
            $statement->execute();
            $userInfo = $statement->fetch(PDO::FETCH_ASSOC);

            if (empty($userInfo)) {
                log_error("Informations de l'utilisateur non trouvées", "GET_USER_INFO", ["userId" => $userId]);
                return null;
            }

            $userInfo['email'] = $user['Email'];
            $userInfo['username'] =  $userInfo['profiletype'] == 'particulier' ? $userInfo['pseudo'] : $userInfo['nomsociete'];

            return $userInfo;
        } catch (Exception $e) {
            log_error("Exception lors de la récupération des informations de l'utilisateur", "GET_USER_INFO", ["message" => $e->getMessage()]);
            return null;
        }
    }

}
