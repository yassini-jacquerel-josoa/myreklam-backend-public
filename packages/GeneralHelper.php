<?php

include("./db.php");
include("./logger.php");

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
            $ad['title'] = $ad['title'];
        }

        if(isset($ad['images'])) {
            $ad['images'] = json_decode($ad['images'], true);
        }
        

        return $ad;
    }
}
