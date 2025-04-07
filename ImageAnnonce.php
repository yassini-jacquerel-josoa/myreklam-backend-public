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
include("./db.php");
include("./logger.php");

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
// $id = $_POST['Id']; // ID de l'enregistrement à modifier ou supprimer
// $siret = $_POST['Siret']; // Données à insérer ou mettre à jour
// $page = $_POST['Page'];
// $category = $_POST['Category'];
$name = isset($_POST['name']) ? trim($_POST['name']) : null;
$logo = isset($_POST['logo']) ? trim($_POST['logo']) : null;
$website = isset($_POST['website']) ? trim($_POST['website']) : null;
$activityId = isset($_POST['activityId']) ? trim($_POST['activityId']) : null;
$addressId = isset($_POST['addressId']) ? trim($_POST['addressId']) : null;
$description = isset($_POST['description']) ? trim($_POST['description']) : null;
$siret = isset($_POST['Siret']) ? trim($_POST['Siret']) : null;
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;

$line1 = isset($_POST['line1']) ? trim($_POST['line1']) : null;
$line2 = isset($_POST['line2']) ? trim($_POST['line2']) : null;
$line3 = isset($_POST['line3']) ? trim($_POST['line3']) : null;
$zipcode = isset($_POST['zipcode']) ? trim($_POST['zipcode']) : null;
$city = isset($_POST['city']) ? trim($_POST['city']) : null;
$country = isset($_POST['country']) ? trim($_POST['country']) : null;
$placeId = isset($_POST['placeId']) ? trim($_POST['placeId']) : null;
$lat = isset($_POST['lat']) ? trim($_POST['lat']) : null;
$lng = isset($_POST['lng']) ? trim($_POST['lng']) : null;

$id = isset($_POST['Id']) ? trim($_POST['Id']) : null;
$userId = isset($_POST['userId']) ? trim($_POST['userId']) : null;
$profileType = isset($_POST['profileType']) ? trim($_POST['profileType']) : null;
$pseudo = isset($_POST['pseudo']) ? trim($_POST['pseudo']) : null;
$photoProfilUrl = isset($_POST['photoProfilUrl']) ? trim($_POST['photoProfilUrl']) : null;
$telephone = isset($_POST['telephone']) ? trim($_POST['telephone']) : null;
$siret = isset($_POST['siret']) ? trim($_POST['siret']) : null;
$nomSociete = isset($_POST['nomSociete']) ? trim($_POST['nomSociete']) : null;
$activite = isset($_POST['activite']) ? trim($_POST['activite']) : null;
$adresse = isset($_POST['adresse']) ? trim($_POST['adresse']) : null;
$ville = isset($_POST['ville']) ? trim($_POST['ville']) : null;
$codePostal = isset($_POST['codePostal']) ? trim($_POST['codePostal']) : null;
$pays = isset($_POST['pays']) ? trim($_POST['pays']) : null;
$facebook = isset($_POST['facebook']) ? trim($_POST['facebook']) : null;
$instagram = isset($_POST['instagram']) ? trim($_POST['instagram']) : null;
$x = isset($_POST['x']) ? trim($_POST['x']) : null;
$linkedin = isset($_POST['linkedin']) ? trim($_POST['linkedin']) : null;
$youtube = isset($_POST['youtube']) ? trim($_POST['youtube']) : null;
$tiktok = isset($_POST['tiktok']) ? trim($_POST['tiktok']) : null;
$snapchat = isset($_POST['snapchat']) ? trim($_POST['snapchat']) : null;


$validMethods = ['create', 'createImageAnnoncesByUrls', 'readAll', 'read', 'updateUserInfo', 
               'update', 'delete', 'paginate', 'addColumnsToProfileTable',
               'paginateSize', 'searchbar', 'readByName', 'readAllByAnnonceId'];

if (!in_array($method, $validMethods)) {
    log_info("Méthode non valide détectée", "Sécurité", $method);
    http_response_code(404);
    echo json_encode([
        "status" => "error",
        "message" => "Méthode non autorisée"
    ]);
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


function createImageAnnonce($conn, $annonceId = null, $urlImg = null, $isDeleted = 0)
{
    try {
        logToFile("Début de la fonction createImageAnnonce. annonceId: $annonceId, urlImg: $urlImg");

        // Générer un ID unique pour l'image de l'annonce
        $id = generateGUID();

        // Construire la requête d'insertion
        $query = 'INSERT INTO "imageAnnonce" (id, annonceid, urlimg, isdeleted) VALUES (:id, :annonceid, :urlimg, :isdeleted)';
        $statement = $conn->prepare($query);

        // Lier les paramètres
        $statement->bindValue(':id', $id);
        $statement->bindValue(':annonceid', $annonceId);
        $statement->bindValue(':urlimg', $urlImg);
        $statement->bindValue(':isdeleted', $isDeleted);

        // Exécuter la requête
        $result = $statement->execute();

        if ($result) {
            logToFile("Image ajoutée avec succès : $urlImg");
            return [
                "status" => "success",
                "message" => "Image ajoutée avec succès.",
                "id" => $id
            ];
        } else {
            logToFile("Échec de l'ajout de l'image. Erreur : " . json_encode($statement->errorInfo()));
            return [
                "status" => "failure",
                "message" => "Échec de l'ajout de l'image.",
                "error" => $statement->errorInfo()
            ];
        }
    } catch (Exception $e) {
        logToFile("Erreur dans createImageAnnonce : " . $e->getMessage());
        return [
            "status" => "error",
            "message" => "Erreur du serveur.",
            "details" => $e->getMessage()
        ];
    }
}


function updateUserInfo($conn, $id, $userId = null, $profileType = null, $pseudo = null, $photoProfilUrl = null, $telephone = null, $siret = null, $nomSociete = null, $activite = null, $adresse = null, $ville = null, $codePostal = null, $pays = null, $facebook = null, $x = null, $instagram = null, $linkedin = null, $youtube = null, $tiktok = null, $snapchat = null)
{
    try {
        // Vérifier si l'ID est fourni
        if (!$id) {
            throw new Exception("L'ID de l'utilisateur est requis.");
        }

        $fieldsToUpdate = [];
        $params = ['id' => $id];

        // Construire les champs à mettre à jour dynamiquement
        if ($userId !== null) {
            $fieldsToUpdate[] = '"userid" = :userId';
            $params['userId'] = $userId;
        }
        if ($profileType !== null) {
            $fieldsToUpdate[] = '"profileType" = :profileType';
            $params['profiletype'] = $profileType;
        }
        if ($pseudo !== null) {
            $fieldsToUpdate[] = '"pseudo" = :pseudo';
            $params['pseudo'] = $pseudo;
        }
        if ($photoProfilUrl !== null) {
            $fieldsToUpdate[] = '"photoprofilurl" = :photoprofilurl';
            $params['photoprofilurl'] = $photoProfilUrl;
        }
        if ($telephone !== null) {
            $fieldsToUpdate[] = '"telephone" = :telephone';
            $params['telephone'] = $telephone;
        }
        if ($siret !== null) {
            $fieldsToUpdate[] = '"siret" = :siret';
            $params['siret'] = $siret;
        }
        if ($nomSociete !== null) {
            $fieldsToUpdate[] = '"nomsociete" = :nomsociete';
            $params['nomsociete'] = $nomSociete;
        }
        if ($activite !== null) {
            $fieldsToUpdate[] = '"activite" = :activite';
            $params['activite'] = $activite;
        }
        if ($adresse !== null) {
            $fieldsToUpdate[] = '"adresse" = :adresse';
            $params['adresse'] = $adresse;
        }
        if ($ville !== null) {
            $fieldsToUpdate[] = '"ville" = :ville';
            $params['ville'] = $ville;
        }
        if ($codePostal !== null) {
            $fieldsToUpdate[] = '"codepostal" = :codepostal';
            $params['codepostal'] = $codePostal;
        }
        if ($pays !== null) {
            $fieldsToUpdate[] = '"pays" = :pays';
            $params['pays'] = $pays;
        }
        if ($facebook !== null) {
            $fieldsToUpdate[] = '"facebook" = :facebook';
            $params['facebook'] = $facebook;
        }
        if ($instagram !== null) {
            $fieldsToUpdate[] = '"instagram" = :instagram';
            $params['instagram'] = $instagram;
        }
        if ($x !== null) {
            $fieldsToUpdate[] = '"x" = :x';
            $params['x'] = $x;
        }
        if ($linkedin !== null) {
            $fieldsToUpdate[] = '"linkedin" = :linkedin';
            $params['linkedin'] = $linkedin;
        }
        if ($youtube !== null) {
            $fieldsToUpdate[] = '"youtube" = :youtube';
            $params['youtube'] = $youtube;
        }
        if ($tiktok !== null) {
            $fieldsToUpdate[] = '"tiktok" = :tiktok';
            $params['tiktok'] = $tiktok;
        }
        if ($snapchat !== null) {
            $fieldsToUpdate[] = '"snapchat" = :snapchat';
            $params['snapchat'] = $snapchat;
        }
        // Vérifier s'il y a des champs à mettre à jour
        if (empty($fieldsToUpdate)) {
            throw new Exception("Aucune donnée à mettre à jour.");
        }

        // Construire la requête
        $query = 'UPDATE "userInfo" SET ' . implode(', ', $fieldsToUpdate) . ' WHERE "id" = :id';
        $statement = $conn->prepare($query);

        // Exécuter la requête
        $result = $statement->execute($params);

        setJsonHeader();
        if ($result) {
            echo json_encode([
                "status" => "success",
                "message" => "Informations utilisateur mises à jour avec succès.",
                "updated_fields" => array_keys($params)
            ]);
        } else {
            echo json_encode([
                "status" => "failure",
                "message" => "Échec de la mise à jour des informations utilisateur.",
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
function readRecords($conn)
{
    $query = 'SELECT * FROM "Profile" ORDER BY "id" DESC';
    $statement = $conn->prepare($query);
    $statement->execute();
    $result = $statement->fetchAll(PDO::FETCH_ASSOC);
    setJsonHeader();
    if ($result) {
        echo json_encode(array("status" => "success", "adType" => $result));
    } else {
        http_response_code(500); // Erreur de serveur interne
        echo json_encode(array("status" => "failure", "message" => "Failed to get records"));
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

function readRecordsByAnnonceId($conn, $id)
{
    // Requête pour sélectionner les enregistrements de la table 'imageDiapo' en fonction de 'userid'
    $query = 'SELECT * FROM "imageAnnonce" WHERE LOWER("annonceid") LIKE :id '; // Vérification que 'isDeleted' est NULL
    $statement = $conn->prepare($query);
    $statement->bindValue(':id', '%' . strtolower($id) . '%'); // Utilisation de strtolower pour la recherche insensible à la casse
    $statement->execute();

    $result = $statement->fetchAll(PDO::FETCH_ASSOC);

    setJsonHeader(); // Assurez-vous que cette fonction est définie ailleurs pour envoyer les bons headers JSON
    if ($result) {
        echo json_encode(array("status" => "success", "images" => $result)); // Renommer 'profile' en 'images' pour correspondre au contexte
    } else {
        http_response_code(200); // Erreur de serveur interne
        echo json_encode(array("status" => "failure", "message" => "Failed to get records"));
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
    $query = "DELETE FROM \"Reservation\" WHERE uid = :id";
    $statement = $conn->prepare($query);
    $statement->bindParam(':id', $id);
    $result = $statement->execute();


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


if ($method == 'create') {
    // Récupérer les données POST et les fichiers envoyés
    $annonceId = $_POST['annonceId'] ?? null;
    $isDeleted = 0; // Par défaut, isDeleted est 0
    logToFile("Données POST reçues : " . json_encode($_POST));
    logToFile("Fichiers reçus : " . json_encode($_FILES));

    // Initialiser un tableau pour collecter les résultats
    $uploadResults = [];
    $successCount = 0;
    $failureCount = 0;

    // Vérifier si des fichiers ont été envoyés avec le champ "media"
    if (isset($_FILES['media']) && is_array($_FILES['media']['name'])) {
        $validImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'tiff', 'svg', 'heif', 'heic'];
        $validMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/bmp',
            'image/webp',
            'image/tiff',
            'image/svg+xml',
            'image/heif',
            'image/heic'
        ];

        // Boucle sur tous les fichiers reçus
        foreach ($_FILES['media']['name'] as $key => $fileName) {
            $fileTmpPath = $_FILES['media']['tmp_name'][$key];
            $fileType = mime_content_type($fileTmpPath);
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // Vérifier le type MIME du fichier
            if (!in_array($fileType, $validMimeTypes)) {
                $failureCount++;
                $uploadResults[] = [
                    "status" => "failure",
                    "message" => "Type de fichier non supporté.",
                    "file" => $fileName
                ];
                continue;
            }

            // Vérifier si l'extension est valide
            if (!in_array($fileExtension, $validImageExtensions)) {
                $failureCount++;
                $uploadResults[] = [
                    "status" => "failure",
                    "message" => "Extension de fichier non valide.",
                    "file" => $fileName
                ];
                continue;
            }

            // Générer un nouveau nom pour le fichier
            $newFileName = generateGUID() . '.' . $fileExtension;

            // Définir le répertoire de destination pour les images
            $uploadDir = __DIR__ . '/img/ads/';

            // Assurez-vous que le répertoire existe
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $uploadPath = $uploadDir . $newFileName;

            // Déplacer le fichier dans le répertoire cible
            if (move_uploaded_file($fileTmpPath, $uploadPath)) {
                // Enregistrer l'URL du fichier pour l'insertion en base de données
                $urlImg = str_replace(__DIR__, '', $uploadPath); // URL relative

                // Appeler la fonction pour insérer l'image dans la table imageAnnonce
                $result = createImageAnnonce($conn, $annonceId, $urlImg, $isDeleted);
                if ($result['status'] === 'success') {
                    $successCount++;
                } else {
                    $failureCount++;
                }

                // Ajouter le résultat dans le tableau des résultats
                $uploadResults[] = $result;
            } else {
                $failureCount++;
                $uploadResults[] = [
                    "status" => "failure",
                    "message" => "Échec du téléchargement du fichier.",
                    "file" => $fileName
                ];
            }
        }

        // Retourner un seul message pour toutes les images
        setJsonHeader();
        if ($failureCount === 0) {
            echo json_encode([
                "status" => "success",
                "message" => "Toutes les images ont été téléchargées avec succès.",
                "uploaded" => $successCount
            ]);
        } elseif ($successCount > 0) {
            echo json_encode([
                "status" => "partial_success",
                "message" => "Certaines images ont été téléchargées avec succès.",
                "uploaded" => $successCount,
                "failed" => $failureCount,
                "details" => $uploadResults
            ]);
        } else {
            echo json_encode([
                "status" => "failure",
                "message" => "Aucune image n'a été téléchargée.",
                "failed" => $failureCount,
                "details" => $uploadResults
            ]);
        }
        exit();
    } else {
        // Si aucun fichier n'est envoyé, gérer cela comme une erreur /img/ads/logo_2.png
        $urlImg = "/img/ads/logo_2.png";
        createImageAnnonce($conn, $annonceId, $urlImg, $isDeleted);
        setJsonHeader();
        echo json_encode([
            "status" => "success",
            "message" => "Image par défaut ajoutée avec succès."
        ]);
        exit();
    }
} elseif ($method == 'createImageAnnoncesByUrls') {
    $urls = $_POST['urls'] ?? [];
    $annonceId = $_POST['annonceId'] ?? null;

    log_info("Données POST reçues : " . json_encode($_POST));

    if(empty($urls) || !is_array($urls)){
        setJsonHeader();
        http_response_code(400);
        echo json_encode([
            "status" => "failure",
            "message" => "Aucune URL fournie."
        ]);
        exit();
    }
    if(empty($annonceId)){
        setJsonHeader();
        http_response_code(400);
        echo json_encode([
            "status" => "failure",
            "message" => "Aucune annonceId fournie."
        ]);
        exit();
    }

    foreach ($urls as $url) {
        $url = trim($url);
        if (!empty($url)) {
            createImageAnnonce($conn, $annonceId, $url);
        }
    }
    setJsonHeader();
    echo json_encode([
        "status" => "success",
        "message" => "Toutes les images ont été téléchargées avec succès.",
        "uploaded" => count($urls)
    ]);
    exit();
} elseif ($method == 'addColumnsToProfileTable') {
} elseif ($method == 'readAll') {
    readRecords($conn);
} elseif ($method == 'addColumnsToProfileTable') {
    addColumnsToProfileTable($conn);
} elseif ($method == 'updateUserInfo') {
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
            updateUserInfo($conn, $id, $userId, $profileType, $pseudo, $photoProfilUrl, $telephone, $siret, $nomSociete, $activite, $adresse, $ville, $codePostal, $pays, $facebook, $x, $instagram, $linkedin, $youtube, $tiktok, $snapchat);
        } else {
            echo json_encode(["status" => "failure", "message" => "Échec du téléchargement de l'image."]);
            exit();
        }
    } else {
        // echo json_encode(["status" => "failure", "message" => "Aucune image envoyée."]);
        // exit();
        updateUserInfo($conn, $id, $userId, $profileType, $pseudo, $photoProfilUrl, $telephone, $siret, $nomSociete, $activite, $adresse, $ville, $codePostal, $pays, $facebook, $x, $instagram, $linkedin, $youtube, $tiktok, $snapchat);
    }
    // updateUserInfo($conn, $id, $userId , $profileType, $pseudo, $photoProfilUrl, $telephone, $siret, $nomSociete, $activite, $adresse, $ville, $codePostal, $pays);
} elseif ($method == 'readAllByAnnonceId') {
    readRecordsByAnnonceId($conn, $id);
} elseif ($method == 'read') {
    readRecord($conn, $id);
} elseif ($method == 'readByName') {
    readRecordByName($conn, $category);
} elseif ($method == 'update') {
    updateRecord($conn, $id, $data);
} elseif ($method == 'delete') {
    deleteRecord($conn, $id);
} elseif ($method == 'paginate') {
    readRecordsPaginate($conn, $page);
} elseif ($method == 'paginateSize') {
    readRecordsPaginateSize($conn);
} else if ($method == 'searchbar') {
    readRecordsSearch($conn, $searchbar);
}
