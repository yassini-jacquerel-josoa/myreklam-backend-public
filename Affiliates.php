<?php

// Bloquer l'accès direct depuis un navigateur en renvoyant une erreur 404
if ($_SERVER['REQUEST_METHOD'] === 'GET' && basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    http_response_code(404);
    exit;
}

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



$method = $_POST['Method'];

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

// Fonction pour définir le type de contenu JSON
function setJsonHeader()
{
    header('Content-Type: application/json');
}
$user_id = $_POST['user_id'];
$refParrain = $_POST['refParrain'];

// Insertion dans la table affiliates
if ($method == 'create_affiliate') {


    $referred_id = $_POST['referred_id'];

    echo "navy aty";

    create_affiliate($user_id, $refParrain);
}

// Insertion dans la table user_affiliate_codes
if ($method == 'create_user_affiliate_code') {
    create_user_affiliate_code($user_id);
}


// Exemple d'utilisation de la fonction
if ($method == 'get_referred_ids') {
    try {
        $user_id = $_POST['user_id'];

        // Récupérer les referred_id liés au user_id
        $referredIds = getReferredIdsByUserId($user_id);

        // Retourner les résultats en JSON
        echo json_encode(["status" => "success", "referred_ids" => $referredIds]);
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

if ($method == 'get_affiliate_code') {
    try {
        $user_id = $_POST['user_id'];

        // Récupérer le affiliate_code lié au user_id
        $affiliate_code = getAffiliateCodeByUserId($user_id);

        // Retourner le résultat en JSON
        if ($affiliate_code !== false) {
            echo json_encode(["status" => "success", "affiliate_code" => $affiliate_code]);
        } else {
            echo json_encode(["status" => "failure", "message" => "No affiliate code found for this user"]);
        }
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}


if ($method == 'get_user_by_affiliate_code') {
    try {
        $affiliate_code = $_POST['affiliate_code'];

        // Récupérer le user_id lié à l'affiliate_code
        $user_id = getUserByAffiliateCode($affiliate_code);

        if ($user_id !== false) {
            echo json_encode(["status" => "success", "user_id" => $user_id]);
        } else {
            echo json_encode(["status" => "failure", "message" => "No user found for this affiliate code"]);
        }
    } catch (\Throwable $th) {
        http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}

// Fonction pour récupérer tous les referred_id liés à un user_id
function getReferredIdsByUserId($user_id)
{

    global $conn;
    // WHERE user_id = :user_id
    try {
        // Requête pour sélectionner les referred_id liés au user_id
        // $query = "SELECT * FROM \"affiliates\" WHERE user_id = :user_id ";
        $query = "SELECT ui.profiletype AS referred_profile_type, ui.pseudo AS referred_pseudo , ui.nomsociete AS referred_nomsociete,   a.*
        FROM \"affiliates\" a
        INNER JOIN \"userInfo\" ui ON a.referred_id = ui.userid
        WHERE a.user_id = :user_id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':user_id', $user_id);
        $statement->execute();


    
        // Récupérer tous les résultats sous forme de tableau
        $referredIds = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $referredIds;
    } catch (\Throwable $th) {
        throw $th; // Propager l'exception pour la gestion des erreurs
    }
}


function generateAffiliateCode()
{
    global $conn;
    do {
        // Générer un code de 8 chiffres
        $affiliate_code = str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);

        // Vérifier si le code existe déjà dans la table user_affiliate_codes
        $checkQuery = "SELECT id FROM \"user_affiliate_codes\" WHERE affiliate_code = :affiliate_code";
        $checkStatement = $conn->prepare($checkQuery);
        $checkStatement->bindValue(':affiliate_code', $affiliate_code);
        $checkStatement->execute();

        // Si le code n'existe pas, on sort de la boucle
        if ($checkStatement->rowCount() == 0) {
            break;
        }
    } while (true); // Continuer à générer jusqu'à ce qu'un code unique soit trouvé

    return $affiliate_code;
}

function getAffiliateCodeByUserId($user_id)
{
    global $conn;
    try {
        // Requête pour sélectionner le affiliate_code lié au user_id
        $query = "SELECT affiliate_code  FROM \"user_affiliate_codes\" WHERE user_id = :user_id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':user_id', $user_id);
        $statement->execute();

        // Récupérer le résultat sous forme de valeur unique
        $affiliate_code = $statement->fetchColumn();

        return $affiliate_code;
    } catch (\Throwable $th) {
        throw $th; // Propager l'exception pour la gestion des erreurs
    }
}

function create_user_affiliate_code($user_id)
{
    global $conn;
    try {


        $affiliate_code = generateAffiliateCode();
        $created_at = date('Y-m-d H:i:s');

        // Vérifier si l'utilisateur a déjà un code d'affiliation
        $checkQuery = "SELECT id FROM \"user_affiliate_codes\" WHERE user_id = :user_id";
        $checkStatement = $conn->prepare($checkQuery);
        $checkStatement->bindValue(':user_id', $user_id);
        $checkStatement->execute();

        if ($checkStatement->rowCount() > 0) {

            echo 4444;
            // L'utilisateur a déjà un code d'affiliation
            return ["status" => "failure", "message" => "User already has an affiliate code"];
        }

        // Générer un nouvel ID
        $id = generateGUID();

        // Insérer le nouveau code d'affiliation
        $query = "INSERT INTO \"user_affiliate_codes\" (id, user_id, affiliate_code, created_at) VALUES (:id, :user_id, :affiliate_code, :created_at)";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $id);
        $statement->bindValue(':user_id', $user_id);
        $statement->bindValue(':affiliate_code', $affiliate_code);
        $statement->bindValue(':created_at', $created_at);
        $statement->execute();

        return ["status" => "success", "message" => "User affiliate code created successfully"];
    } catch (\Throwable $th) {

        return ["status" => "failure", "message" => $th->getMessage()];
    }
}

function create_affiliate($referred_id, $affiliate_code)
{
    global $conn;
    try {

        $user_id = null;

        if ($affiliate_code) {
            $user_id = getUserByAffiliateCode($affiliate_code);
        } else {

            return;
        }


        $default_type = "none";
        $commission = 0;

        $query = 'SELECT profiletype, pseudo FROM "userInfo" WHERE userid = :id';
        $statement = $conn->prepare($query);
        $statement->bindParam(':id', $referred_id);
        $statement->execute();
        $resultUserInfoReffered = $statement->fetch(PDO::FETCH_ASSOC);

        if ($resultUserInfoReffered && $resultUserInfoReffered["profiletype"]) {
            $default_type = $resultUserInfoReffered["profiletype"];
        }
        $eventCoins = [];

        $eventName = "sponsor_individual";
        if ($default_type != 'none') {
            if ($default_type == "professionnel") {
                $eventName = "sponsor_company";
                $query = "SELECT * FROM \"event_coins\" WHERE slug = 'sponsor_company' ";
                $statement = $conn->prepare($query);
                $statement->execute();
                $eventCoins = $statement->fetch(PDO::FETCH_ASSOC);
            } else {
                $query = "SELECT * FROM \"event_coins\" WHERE slug = 'sponsor_individual'";
                $statement = $conn->prepare($query);
                $statement->execute();
                $eventCoins = $statement->fetch(PDO::FETCH_ASSOC);
            }

            $commission = $eventCoins["coins"];
        }


        $created_at = date('Y-m-d H:i:s');

        // Vérifier si le referred_id existe déjà dans la table affiliates
        $checkQuery = "SELECT * FROM \"affiliates\" WHERE referred_id = :referred_id";
        $checkStatement = $conn->prepare($checkQuery);
        $checkStatement->bindValue(':referred_id', $referred_id);
        $checkStatement->execute();

        if ($checkStatement->rowCount() > 0) {
            // Le referred_id existe déjà, on ne peut pas l'ajouter

            $AffiliateReffered = $checkStatement->fetch(PDO::FETCH_ASSOC);

            $query = "UPDATE \"affiliates\" SET commission = :commission  WHERE id = :id";
            $statement = $conn->prepare($query);
            $statement->bindValue(':id', $AffiliateReffered["id"]);
            $statement->bindValue(':commission', $commission);

            $statement->execute();


            echo json_encode(["status" => "success", "message" => "Affiliate created successfully"]);

            exit;
        }

        if (!$user_id) {
            echo json_encode(["status" => "failure", "message" => "code de parrainage n'est pas valide"]);
            exit;
        }
        $id = generateGUID();

        $query = "INSERT INTO \"affiliates\" (id, user_id, referred_id, commission, status, created_at) VALUES (:id, :user_id, :referred_id, :commission, :status, :created_at)";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $id);
        $statement->bindValue(':user_id', $user_id);
        $statement->bindValue(':referred_id', $referred_id);
        $statement->bindValue(':commission', $commission);
        $statement->bindValue(':status', true);
        $statement->bindValue(':created_at', $created_at);
        $statement->execute();


        handleEventCoin($user_id, $eventName);
        
        // Notifications pour le parrainage
        $notificationManager = new NotificationBrevoAndWeb($conn);
        
        // Récupérer les informations des utilisateurs
        $query = "SELECT * FROM \"userInfo\" WHERE userid = :user_id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':user_id', $user_id);
        $statement->execute();
        $parrainInfo = $statement->fetch(PDO::FETCH_ASSOC);
        
        $query = "SELECT * FROM \"userInfo\" WHERE userid = :user_id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':user_id', $referred_id);
        $statement->execute();
        $filleulInfo = $statement->fetch(PDO::FETCH_ASSOC);
        
        // Notification pour le parrain
        if ($parrainInfo) {
            // Créer notification pour le parrain
            $query = 'INSERT INTO "notifications" ("id", "user_id", "content", "type", "is_read", "return_url") 
                      VALUES (:id, :user_id, :content, :type, :is_read, :return_url)';
            $statement = $conn->prepare($query);
            
            $notifId = generateGUID();
            $type = "info";
            $is_read = 0;
            $content = "Félicitations ! " . ($filleulInfo ? $filleulInfo['pseudo'] : 'Un utilisateur') . " a utilisé votre code de parrainage.";
            
            $statement->bindParam(':id', $notifId);
            $statement->bindParam(':user_id', $user_id);
            $statement->bindParam(':content', $content);
            $statement->bindParam(':type', $type);
            $statement->bindParam(':is_read', $is_read);
            $statement->bindParam(':return_url', '/profil/parrainage');
            
            $statement->execute();
        }
        
        echo json_encode(["status" => "success", "message" => "Affiliate created successfully"]);
    } catch (\Throwable $th) {
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }
}


function getUserByAffiliateCode($affiliate_code)
{
    global $conn;
    try {
        // Requête pour récupérer l'utilisateur lié à l'affiliate_code
        $query = "SELECT user_id FROM \"user_affiliate_codes\" WHERE affiliate_code = :affiliate_code";
        $statement = $conn->prepare($query);
        $statement->bindValue(':affiliate_code', $affiliate_code);
        $statement->execute();

        // Récupérer le résultat sous forme de valeur unique
        $user_id = $statement->fetchColumn();



        return $user_id ? $user_id : false;
    } catch (\Throwable $th) {
        throw $th; // Propager l'exception pour la gestion des erreurs
    }
}




function handleEventCoin($userId, $eventName): bool
{
    global $conn; // Assurez-vous que la connexion à la base de données est disponible

    try {


        $query = "SELECT * FROM \"userInfo\" WHERE userId=:id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', $userId);
        $statement->execute();
        $userExist = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$userExist) {
            http_response_code(500);
            echo json_encode(["status" => "failure", "message" => "Utilisateur introuvable"]);
            exit;
        }

        $query = "SELECT * FROM event_coins WHERE slug=:slug";
        $statement = $conn->prepare($query);
        $statement->bindValue(':slug', $eventName);
        $statement->execute();
        $eventCoins = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$eventCoins) {
            http_response_code(500);
            echo json_encode(["status" => "failure 2", "message" => "event coin introuvable"]);
            exit;
        }

        $valueCoin = $eventCoins["coins"];
        $description = $eventCoins["description"];
        $generateBy = "web";
        // Générer un GUID pour l'historique
        $historyId = generateGUID();
        $createdAt = date('Y-m-d H:i:s'); // Date actuelle

        // 1. Vérifier si l'utilisateur a déjà une entrée dans `user_coins`
        $query = "SELECT id, value FROM user_coins WHERE userId = :userId";
        $statement = $conn->prepare($query);
        $statement->bindValue(':userId', $userId);
        $statement->execute();
        $userCoin = $statement->fetch(PDO::FETCH_ASSOC);

        if ($userCoin) {
            // Mettre à jour les points de l'utilisateur
            $newValue = floatval($userCoin['value']) + floatval($valueCoin);
            $updateQuery = "UPDATE user_coins SET value = :value, updateat = :updateat WHERE id = :id";
            $updateStatement = $conn->prepare($updateQuery);
            $updateStatement->bindValue(':value', $newValue);
            $updateStatement->bindValue(':updateat', $createdAt);
            $updateStatement->bindValue(':id', $userCoin['id']);
            $updateStatement->execute();
        } else {
            // Créer une nouvelle entrée pour l'utilisateur
            $userCoinId = generateGUID();
            $insertQuery = "INSERT INTO user_coins (id, userid, value, updateat, lastconversionat) VALUES (:id, :userid, :value, :updateat, :lastconversionat)";
            $insertStatement = $conn->prepare($insertQuery);
            $insertStatement->bindValue(':id', $userCoinId);
            $insertStatement->bindValue(':userid', $userId);
            $insertStatement->bindValue(':value', $valueCoin);
            $insertStatement->bindValue(':updateat', $createdAt);
            $insertStatement->bindValue(':lastconversionat', $createdAt); // Initialiser avec la même date
            $insertStatement->execute();
        }

        // 2. Ajouter une entrée dans `history_coins`
        $historyQuery = "INSERT INTO history_coins (id, userid, valuecoin, eventname, description, createdat, generateby) VALUES (:id, :userid, :valuecoin, :eventname, :description, :createdat, :generateby)";
        $historyStatement = $conn->prepare($historyQuery);
        $historyStatement->bindValue(':id', $historyId);
        $historyStatement->bindValue(':userid', $userId);
        $historyStatement->bindValue(':valuecoin', $valueCoin);
        $historyStatement->bindValue(':eventname', $eventName);
        $historyStatement->bindValue(':description', $description);
        $historyStatement->bindValue(':createdat', $createdAt);
        $historyStatement->bindValue(':generateby', $generateBy);
        $historyStatement->execute();

        return true;

        // Retourner une réponse de succès
        // echo json_encode(["status" => "success", "message" => "Event coin handled successfully"]);
    } catch (\Throwable $th) {
        // http_response_code(500);
        echo json_encode(["status" => "failure", "message" => $th->getMessage()]);
    }

    return false;
}
