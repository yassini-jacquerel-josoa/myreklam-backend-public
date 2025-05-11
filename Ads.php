<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Bloquer l'accès direct depuis un navigateur en renvoyant une erreur 404
if ($_SERVER['REQUEST_METHOD'] === 'GET' && basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    http_response_code(404);
    exit;
}
// Inclure la connexion à la base de données
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



// Récupérer les données du formulaire
$method = $_POST['Method']; // "create", "read", "update" ou "delete"
 
if (
    $method !== 'create' && $method !== 'readAll' && $method !== 'read' && $method !== 'updateAnnonce'
    && $method !== 'update'  && $method !== 'delete'  && $method !== 'paginate'  && $method !== 'addColumnsToProfileTable'
    && $method !== 'paginateSize'  && $method !== 'searchbar'  && $method !== 'readByName'  && $method !== 'readAdsByCriteria' && $method !== 'updateAds' && $method !== 'updateNumberViewAds'
) {
    http_response_code(404);
    exit;
}



// Fonction pour définir le type de contenu JSON
function setJsonHeader()
{
    header('Content-Type: application/json');
}
 
function logToFile($message)
{
    $logFile = __DIR__ . '/log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
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

function createAd($conn)
{
    try {
        logToFile("Début de la fonction createAd.");

        // Générer un ID unique pour l'annonce
        $id = generateGUID();

        // Préparer les champs à insérer dynamiquement
        $fields = ['id' => $id];
        $columns = ['id'];
        $placeholders = [':id'];

        // Parcourir les données de la requête POST
        foreach ($_POST as $key => $value) {
            if ($key !== 'Method' && $value !== null && $value !== '') { // Ajouter uniquement les champs non vides
                $fields[$key] = $value;
                $columns[] = '"' . $key . '"'; // Échapper les noms de colonnes
                $placeholders[] = ':' . $key;
            }
        }

        // Encoder correctement les champs JSONB et TEXT[]
        foreach ($fields as $key => $value) {
            if (is_array($value)) {
                if (isAssocArray($value)) {
                    // Convertir les tableaux associatifs en JSON pour JSONB
                    $fields[$key] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    // Convertir les tableaux indexés en format TEXT[]
                    $escapedValues = array_map(function ($item) {
                        return '"' . str_replace('"', '\\"', $item) . '"';
                    }, $value);
                    $fields[$key] = '{' . implode(',', $escapedValues) . '}';
                }
            }
        }

        // Construire la requête d'insertion
        $query = 'INSERT INTO "ads" (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $statement = $conn->prepare($query);

        // Lier les paramètres
        foreach ($fields as $key => $value) {
            // Lier les champs JSONB avec le type correspondant
            if (is_string($value) && isJson($value)) {
                $statement->bindValue(':' . $key, $value, PDO::PARAM_STR);
            } else {
                $statement->bindValue(':' . $key, $value);
            }
        }

        // Exécuter la requête
        $result = $statement->execute();

        setJsonHeader();
        if ($result) {

            if (!empty($_POST['userId'])) {
                $coinEvents = new EventCoinsFacade($conn);
                $coinEvents->publishAd($_POST['userId']);
                
                // Envoyer une notification selon la catégorie de l'annonce
                $notificationManager = new NotificationBrevoAndWeb($conn);
                $category = $_POST['category'] ?? '';
                
                switch ($category) {
                    case 'bon_plans':
                        $notificationManager->sendNotificationAdBonPlan($_POST['userId'], $id);
                        break;
                    case 'emplois':
                        $notificationManager->sendNotificationAdEmplois($_POST['userId'], $id);
                        break;
                    case 'evenements':
                        $notificationManager->sendNotificationAdEvenements($_POST['userId'], $id);
                        break;
                    case 'formations':
                        $notificationManager->sendNotificationAdFormation($_POST['userId'], $id);
                        break;
                    case 'demandes':
                        $notificationManager->sendNotificationAdDemandes($_POST['userId'], $id);
                        break;
                }
            }

            echo json_encode([
                "status" => "success",
                "message" => "Annonce créée avec succès.",
                "id" => $id
            ]);
        } else {
            echo json_encode([
                "status" => "failure",
                "message" => "Échec de la création de l'annonce.",
                "error" => $statement->errorInfo()
            ]);
        }
    } catch (Exception $e) {
        setJsonHeader();
        echo json_encode([
            "status" => "error",
            "message" => "Erreur du serveur.",
            "details" => $e->getMessage()
        ]);
    }
}

/**
 * Vérifie si un tableau est associatif.
 */
function isAssocArray(array $array)
{
    return array_keys($array) !== range(0, count($array) - 1);
}

/**
 * Vérifie si une chaîne est un JSON valide.
 */
function isJson($string)
{
    json_decode($string);
    return (json_last_error() === JSON_ERROR_NONE);
}






function updateAd($conn)
{
    try {
        // Récupérer les données envoyées via POST
        $id = $_POST['id'] ?? null;

        // Vérifier si l'ID est fourni
        if (!$id) {
            throw new Exception("L'ID de l'annonce est requis.");
        }

        // Supprimer l'ID des données à mettre à jour
        unset($_POST['id']);

        // Filtrer les colonnes non nulles
        $fieldsToUpdate = [];
        $params = ['id' => $id];

        foreach ($_POST as $column => $value) {
            if ($column !== 'Method' && $value !== null) {
                $fieldsToUpdate[] = "\"$column\" = :$column";
                $params[$column] = $value;
            }
        }

        // Vérifier s'il y a des champs à mettre à jour
        if (empty($fieldsToUpdate)) {
            throw new Exception("Aucune donnée à mettre à jour.");
        }

        // Construire la requête
        $query = 'UPDATE "ads" SET ' . implode(', ', $fieldsToUpdate) . ' WHERE "id" = :id AND "deletedat" IS NULL';
        $statement = $conn->prepare($query);

        // Exécuter la requête
        $result = $statement->execute($params);

        setJsonHeader();
        if ($result) {
            echo json_encode([
                "status" => "success",
                "message" => "Annonce mise à jour avec succès.",
                "updated_fields" => array_keys($_POST)
            ]);
        } else {
            echo json_encode([
                "status" => "failure",
                "message" => "Échec de la mise à jour de l'annonce.",
                "error" => $statement->errorInfo()
            ]);
        }
    } catch (Exception $e) {
        setJsonHeader();
        echo json_encode([
            "status" => "error",
            "message" => "Erreur du serveur.",
            "details" => $e->getMessage()
        ]);
    }
}





// Fonction pour lire tout les  enregistrements
function readAds($conn)
{
    try {
        // Construire la requête pour récupérer tous les enregistrements, triés par ID descendant
        $query = 'SELECT * FROM "ads" WHERE "deletedat" IS NULL ORDER BY "id" DESC';
        $statement = $conn->prepare($query);

        // Exécuter la requête
        $statement->execute();

        // Récupérer les résultats
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        // Définir les en-têtes JSON
        setJsonHeader();

        // Vérifier si des résultats existent
        if ($result) {
            echo json_encode([
                "status" => "success",
                "ads" => $result // Inclure les données récupérées dans la réponse
            ]);
        } else {
            http_response_code(404); // Pas trouvé
            echo json_encode([
                "status" => "failure",
                "message" => "No records found."
            ]);
        }
    } catch (Exception $e) {
        // Gérer les erreurs serveur
        setJsonHeader();
        http_response_code(500); // Erreur de serveur interne
        echo json_encode([
            "status" => "error",
            "message" => "An error occurred while fetching the ads.",
            "details" => $e->getMessage()
        ]);
    }
}



function readRecordsSearch($conn, $searchbar)
{
    $query = 'SELECT * FROM "Ad" WHERE LOWER("title") LIKE :searchbar  ORDER BY "createdAt" DESC';
    $statement = $conn->prepare($query);
    $statement->bindValue(':searchbar', '%' . $searchbar . '%'); // Ajouter les % pour une recherche partielle
    $statement->execute();
    $result = $statement->fetchAll(PDO::FETCH_ASSOC);

    $query1 = 'SELECT * FROM "AdType" ORDER BY "id" DESC';
    $statement1 = $conn->prepare($query1);
    $statement1->execute();
    $result1 = $statement1->fetchAll(PDO::FETCH_ASSOC);
    setJsonHeader();

    if ($result) {
        echo json_encode(array("status" => "success", "ad" => $result, "adType" => $result1));
    } else {
        http_response_code(500); // Erreur de serveur interne
        echo json_encode(array("status" => "failure", "message" => "Failed to get records"));
    }
}

// Fonction pour pagination
function readRecordsPaginate($conn, $page)
{
    $query = 'SELECT * FROM "Reservation" ORDER BY "createdAt" DESC LIMIT 10 OFFSET (:page - 1) * 10';
    $statement = $conn->prepare($query);
    $statement->bindParam(':page', $page);
    $statement->execute();
    $result = $statement->fetchAll(PDO::FETCH_ASSOC);
    setJsonHeader();
    if ($result) {
        echo json_encode(array("status" => "success", "data" => $result));
    } else {
        http_response_code(500); // Erreur de serveur interne
        echo json_encode(array("status" => "failure", "message" => "Failed to get records"));
    }
}

// Fonction pour total de page et records
function readRecordsPaginateSize($conn)
{
    $query = 'SELECT COUNT(*) as totalRows, CEILING(COUNT(*) / 10) as totalPages FROM "Reservation"';
    $statement = $conn->prepare($query);
    $statement->execute();
    $result = $statement->fetchAll(PDO::FETCH_ASSOC);
    setJsonHeader();
    if ($result) {
        echo json_encode(array("status" => "success", "data" => $result));
    } else {
        http_response_code(500); // Erreur de serveur interne
        echo json_encode(array("status" => "failure", "message" => "Failed to get records"));
    }
}

// Fonction pour lire un enregistrement 
function readRecord($conn, $id)
{
    $query = 'SELECT * FROM "userInfo" WHERE userid = :id';
    $statement = $conn->prepare($query);
    $statement->bindParam(':id', $id);
    $statement->execute();
    $result = $statement->fetch(PDO::FETCH_ASSOC);
    setJsonHeader();
    if ($result) {
        echo json_encode([
            "status" => "success",
            "userData" => $result
        ]);
    } else {
        http_response_code(500); // Erreur de serveur interne
        echo json_encode([
            "status" => "failure",
            "message" => "Failed to get record"
        ]);
    }
} 

function readAdsByCriteria($conn)
{
    try {
        // Récupérer les données POST
        foreach ($_POST as $key => $value) {
            if ($key !== 'Method' && !empty($value)) { // Ajouter uniquement les champs non vides et ignorer le paramètre 'Method'
                $fields[$key] = $value;
                $columns[] = $key;
                $placeholders[] = ':' . $key;
            }
        }

        // Vérifier si des critères ont été envoyés
        if (empty($fields)) {
            http_response_code(400); // Mauvaise requête
            echo json_encode(["status" => "error", "message" => "Invalid or missing criteria in request body."]);
            return;
        }

        $query = 'SELECT * FROM ads WHERE 1=1 AND "deletedat" IS NULL '; // Base de la requête
        $params = [];

        // Ajouter les conditions dynamiques
        foreach ($fields as $column => $value) {
            if (!is_null($value)) { // Ignorer les valeurs nulles
                if ($column === 'category' && strtolower($value) === 'espace') {
                    $query .= ' AND ("category" = :formations OR "category" = :emplois)';
                    $params['formations'] = 'formations';
                    $params['emplois'] = 'emplois';
                } else {
                    // Ignorer la casse en utilisant LOWER pour les deux côtés
                    $query .= ' AND LOWER("' . $column . '") LIKE LOWER(:' . $column . ')';
                    $params[$column] = '%' . strtolower($value) . '%';
                }
            }
        }
        logToFile("Requête SQL générée : " . $query);
        logToFile("Paramètres de la requête : " . json_encode($params));
        // Préparer et exécuter la requête
        $statement = $conn->prepare($query);
        foreach ($params as $param => $value) {
            $statement->bindValue(':' . $param, $value);
        }

        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        setJsonHeader();
        if ($result) {
            echo json_encode(["status" => "success", "ads" => $result]);
        } else {
            // http_response_code(404); // Aucun résultat
            // echo json_encode(["status" => "failure", "message" => "No ads found matching the criteria.", "ads" => [] ]);
            echo json_encode(["status" => "success", "ads" => []]);
        }
    } catch (Exception $e) {
        setJsonHeader();
        http_response_code(500); // Erreur interne
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}

function readRecordByName($conn, $category)
{
    $query = 'SELECT * FROM "AdType" where name = :name';
    $statement = $conn->prepare($query);
    $statement->bindParam(':name', $category);
    $statement->execute();
    $result = $statement->fetch(PDO::FETCH_ASSOC);
    setJsonHeader();
    if ($result) {
        echo json_encode(array("status" => "success", "adType" => $result));
    } else {
        http_response_code(500); // Erreur de serveur interne
        echo json_encode(array("status" => "failure", "message" => "Failed to get record"));
    }
}


// Fonction pour mettre à jour un enregistrement
function updateRecord($conn, $id, $data)
{
    $columns = array_keys($data);
    $values = array_values($data);

    $quotedColumns = array_map(function ($column) {
        return "\"$column\"";
    }, $columns);

    $setClause = "";
    for ($i = 0; $i < count($quotedColumns); $i++) {
        $setClause .= $quotedColumns[$i] . " = ?";
        if ($i < count($quotedColumns) - 1) {
            $setClause .= ", ";
        }
    }

    $query = "UPDATE \"Reservation\" SET $setClause WHERE uid = ?";

    $statement = $conn->prepare($query);

    // Boucler à travers les valeurs et les lier aux placeholders
    for ($i = 0; $i < count($values); $i++) {
        $statement->bindValue(($i + 1), $values[$i]);
    }
    // Lier la valeur de l'ID à la fin
    $statement->bindValue(count($values) + 1, $id);

    $result = $statement->execute();
    setJsonHeader();

    // Retourner une réponse JSON en fonction du résultat de l'exécution
    if ($result) {
        echo json_encode(array("status" => "success", "message" => "Record successfully update"));
    } else {
        http_response_code(500); // Erreur de serveur interne
        echo json_encode(array("status" => "failure", "message" => "Failed to update record"));
    }
}


// Fonction pour supprimer un enregistrement
function deleteRecord($conn, $id)
{
    // Récupérer l'ID de l'utilisateur avant de supprimer l'annonce
    $query = "SELECT userId FROM \"ads\" WHERE id = :id";
    $statement = $conn->prepare($query);
    $statement->bindParam(':id', $id);
    $statement->execute();
    $ad = $statement->fetch(PDO::FETCH_ASSOC);
    $userId = $ad['userId'] ?? null;
    
    $query = "UPDATE \"ads\" SET \"deletedat\" = CURRENT_TIMESTAMP WHERE id = :id";
    $statement = $conn->prepare($query);
    $statement->bindParam(':id', $id);
    $result = $statement->execute();

    // Envoyer une notification de suppression si l'userId existe
    if ($result && $userId) {
        $notificationManager = new NotificationBrevoAndWeb($conn);
        $notificationManager->sendNotificationAdDeleted($userId, $id);
    }

    setJsonHeader();
    // Retourner une réponse JSON en fonction du résultat de l'exécution
    if ($result) {
        echo json_encode(array("status" => "success", "message" => "Record successfully delete"));
    } else {
        http_response_code(500); // Erreur de serveur interne
        echo json_encode(array("status" => "failure", "message" => "Failed to delete record"));
    }
}

function addColumnsToProfileTable($conn)
{
    try {
        // Ajout des colonnes 'token' et 'isVerified' si elles n'existent pas
        $query = '
                ALTER TABLE "userInfo" 
                ADD COLUMN IF NOT EXISTS facebook TEXT,
                ADD COLUMN IF NOT EXISTS instagram TEXT,
                ADD COLUMN IF NOT EXISTS x TEXT,
                ADD COLUMN IF NOT EXISTS linkedin TEXT,
                ADD COLUMN IF NOT EXISTS youtube TEXT,
                ADD COLUMN IF NOT EXISTS tiktok TEXT,
                ADD COLUMN IF NOT EXISTS snapchade TEXT;
            ';

        // Exécution de la requête
        $conn->exec($query);

        // Retourner un message de succès
        echo json_encode(array("status" => "success", "message" => "Colonnes ajoutées avec succès"));
    } catch (PDOException $e) {
        // Gestion des erreurs
        echo json_encode(array("status" => "error", "message" => "Erreur lors de l'ajout des colonnes : " . $e->getMessage()));
    }
}




// Vérifier la méthode et appeler la fonction appropriée 
//updateOrganization($conn, $id, $name = null, $logo = null, $website = null, $activityId = null, $addressId = null, $description = null, $siret = null, $phone = null)
if ($method == 'create') { 

    // Vérifier si le fichier photoProfil est présent
    if (isset($_FILES['photoProfil']) && $_FILES['photoProfil']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['photoProfil']['tmp_name'];
        $fileName = $_FILES['photoProfil']['name'];
        $fileSize = $_FILES['photoProfil']['size'];
        $fileType = $_FILES['photoProfil']['type'];

        $fileName = $_FILES['photoProfil']['name'];
        $fileTmpName = $_FILES['photoProfil']['tmp_name'];
        $fileSize = $_FILES['photoProfil']['size'];
        $fileError = $_FILES['photoProfil']['error'];
        // Récupérer l'extension du fichier
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

        // Générer un nouveau nom pour l'image avec la fonction generateGUID
        $newFileName = generateGUID() . '.' . $fileExtension;

        // Définir le chemin du dossier où l'image sera sauvegardée
        $uploadDir = __DIR__ . '/img/'; // Dossier 'img' situé au même niveau que ce fichier PHP
        $uploadPath = $uploadDir . $newFileName;

 
        if (move_uploaded_file($fileTmpName, $uploadPath)) { 
            $photoProfilUrl = '/img/' . $newFileName; // L'URL accessible via le web (dossier public 'img')


            // Ajouter le reste des données à l'insertion dans la base
            createAd($conn);
        } else {
            echo json_encode(["status" => "failure", "message" => "Échec du téléchargement de l'image."]);
            exit();
        }
    } else {
        // echo json_encode(["status" => "failure", "message" => "Aucune image envoyée."]);
        // exit();
        createAd($conn);
    }
} elseif ($method == 'readAll') {
    readAds($conn);
} elseif ($method == 'addColumnsToProfileTable') {
    addColumnsToProfileTable($conn);
} elseif ($method == 'updateAnnonce') {
    $userId = $_POST['userId'] ?? null;
    $pseudo = $_POST['pseudo'] ?? null;
    $telephone = $_POST['telephone'] ?? null;
    $siret = $_POST['siret'] ?? null;
    $nomSociete = $_POST['name'] ?? null;
    $activite = $_POST['activity'] ?? null;
    $telephone = $_POST['tel'] ?? null;
    $adresse = $_POST['address'] ?? null;
    $ville = $_POST['ville'] ?? null;
    $codePostal = $_POST['codePostal'] ?? null;
    $pays = $_POST['pays'] ?? null;
    // $profileType = $_POST['siret'] ? "professionnel" : "particulier";

    logToFile("Données POST reçues : " . json_encode($_POST));
    logToFile("Fichiers reçus : " . json_encode($_FILES));
    // Vérifier si le fichier photoProfil est présent
    if (isset($_FILES['photoProfil']) && $_FILES['photoProfil']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['photoProfil']['tmp_name'];
        $fileName = $_FILES['photoProfil']['name'];
        $fileSize = $_FILES['photoProfil']['size'];
        $fileType = $_FILES['photoProfil']['type'];

        $fileName = $_FILES['photoProfil']['name'];
        $fileTmpName = $_FILES['photoProfil']['tmp_name'];
        $fileSize = $_FILES['photoProfil']['size'];
        $fileError = $_FILES['photoProfil']['error'];
        // Récupérer l'extension du fichier
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

        // Générer un nouveau nom pour l'image avec la fonction generateGUID
        $newFileName = generateGUID() . '.' . $fileExtension;

        // Définir le chemin du dossier où l'image sera sauvegardée
        $uploadDir = __DIR__ . '/img/'; // Dossier 'img' situé au même niveau que ce fichier PHP
        $uploadPath = $uploadDir . $newFileName;


        // Définir un répertoire de destination pour le fichier téléchargé
        // $uploadDir = 'img/'; // Répertoire où les photos seront enregistrées
        // $filePath = $uploadDir . basename($fileName);

        // Déplacer le fichier téléchargé dans le répertoire cible
        // if (move_uploaded_file($fileTmpPath, $filePath)) {
        if (move_uploaded_file($fileTmpName, $uploadPath)) {
            // Enregistrer l'URL de l'image dans la base de données
            // $photoProfilUrl = $filePath;
            $photoProfilUrl = '/img/' . $newFileName; // L'URL accessible via le web (dossier public 'img')


            // Ajouter le reste des données à l'insertion dans la base
            // createUserInfo($conn, $userId, $profileType, $pseudo, $photoProfilUrl, $telephone, $siret, $nomSociete, $activite, $adresse, $ville, $codePostal, $pays);
            updateAd($conn);
        } else {
            echo json_encode(["status" => "failure", "message" => "Échec du téléchargement de l'image."]);
            exit();
        }
    } else {
        // echo json_encode(["status" => "failure", "message" => "Aucune image envoyée."]);
        // exit();
        updateAd($conn);
    }
    // updateUserInfo($conn, $id, $userId , $profileType, $pseudo, $photoProfilUrl, $telephone, $siret, $nomSociete, $activite, $adresse, $ville, $codePostal, $pays);
} elseif ($method == 'readAdsByCriteria') {
    // readRecordsByUserId($conn, $id);
    readAdsByCriteria($conn);
} elseif ($method == 'read') {
    readRecord($conn, $id);
} elseif ($method == 'readByName') {
    readRecordByName($conn, $category);
} elseif ($method == 'update') {
    updateRecord($conn, $id, $data);
} elseif ($method == 'updateNumberViewAds') {

    $id = $_POST['id'] ?? null;
    $number_view = $_POST['number_view'] ?? null;

    $query = "UPDATE ads SET number_view = :number_view WHERE id = :id AND deletedat IS NULL";

    $statement = $conn->prepare($query);
    $statement->bindParam(':number_view', $number_view);
    $statement->bindParam(':id', $id);
    $statement->execute();


    setJsonHeader();
    echo json_encode(["status" => "success", "message" => "Record successfully update"]);
} elseif ($method == 'delete') {
    $id = $_POST['id'] ?? null;
    deleteRecord($conn, $id);
} elseif ($method == 'paginate') {
    readRecordsPaginate($conn, $page);
} elseif ($method == 'paginateSize') {
    readRecordsPaginateSize($conn);
} else if ($method == 'searchbar') {
    readRecordsSearch($conn, $searchbar);
}
