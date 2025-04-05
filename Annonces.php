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

    $publishadresse = isset($_POST['publishadresse']) ? trim($_POST['publishadresse']) : null;
    $publishname = isset($_POST['publishname']) ? trim($_POST['publishname']) : null;
    $publishactivity = isset($_POST['publishactivity']) ? trim($_POST['publishactivity']) : null;
    $publishtelephone = isset($_POST['publishtelephone']) ? trim($_POST['publishtelephone']) : null;
    
    $title = isset($_POST['title']) ? trim($_POST['title']) : null;
$description = isset($_POST['description']) ? trim($_POST['description']) : null;
$dealType = isset($_POST['dealType']) ? trim($_POST['dealType']) : null;
$dealCategory = isset($_POST['dealCategory']) ? trim($_POST['dealCategory']) : null;
$showAddress = isset($_POST['showAddress']) ? trim($_POST['showAddress']) : null;
$brand = isset($_POST['brand']) ? trim($_POST['brand']) : null;
$initialPrice = isset($_POST['initialPrice']) ? trim($_POST['initialPrice']) : null;
$discountValue = isset($_POST['discountValue']) ? trim($_POST['discountValue']) : null;
$discountType = isset($_POST['discountType']) ? trim($_POST['discountType']) : null;
$finalPrice = isset($_POST['finalPrice']) ? trim($_POST['finalPrice']) : null;
$website = isset($_POST['website']) ? trim($_POST['website']) : null;
$isOnline = isset($_POST['isOnline']) ? trim($_POST['isOnline']) : null;
$shippingCost = isset($_POST['shippingCost']) ? trim($_POST['shippingCost']) : null;
$startDate = isset($_POST['startDate']) ? trim($_POST['startDate']) : null;
$endDate = isset($_POST['endDate']) ? trim($_POST['endDate']) : null;
$messageId = isset($_POST['messageId']) ? trim($_POST['messageId']) : null;
$userId = isset($_POST['userId']) ? trim($_POST['userId']) : null;
$adresse = isset($_POST['adressse']) ? trim($_POST['adressse']) : null;
$codePostal = isset($_POST['codepostal']) ? trim($_POST['codepostal']) : null;
$ville = isset($_POST['ville']) ? trim($_POST['ville']) : null;
$pays = isset($_POST['pays']) ? trim($_POST['pays']) : null;

    
    // $searchbar = strtolower($_POST['Searchbar']); // Mettre le terme de recherche en minuscule 

   
    if ($method !== 'create' && $method !== 'readAll' && $method !== 'read' && $method !== 'updateAnnonce'
    && $method !== 'update'  && $method !== 'delete'  && $method !== 'paginate'  && $method !== 'addColumnsToProfileTable'
    && $method !== 'paginateSize'  && $method !== 'searchbar'  && $method !== 'readByName'  && $method !== 'readAllByUserId') {
        http_response_code(404);
        exit;
    }

    
    
    // Fonction pour définir le type de contenu JSON
    function setJsonHeader() {
        header('Content-Type: application/json');
    }

// Fonction pour créer un enregistrement
// function createRecord($conn, $data) {
//     $columns = array_keys($data);
//     $values = array_values($data);
    
//     $quotedColumns = array_map(function($column) {
//     return "\"$column\"";
// }, $columns);

// $columnsString = implode(", ", $quotedColumns);

//     $placeholders = implode(", ", array_fill(0, count($columns), "?"));
    
//     $query = "INSERT INTO \"Reservation\" ($columnsString) VALUES ($placeholders)";
    
//     $statement = $conn->prepare($query);
    
//     // Boucler à travers les valeurs et les lier aux placeholders
//     for ($i = 0; $i < count($values); $i++) {
//         $statement->bindValue(($i + 1), $values[$i]);
//     }
    
    
//     $result = $statement->execute();
//     setJsonHeader();
//     if ($result) {
//         echo json_encode(array("status" => "success", "message" => "Record successfully created"));
//     } else {
//         $errorInfo = $statement->errorInfo();
//         //http_response_code(401); // Erreur de serveur interne
//         echo json_encode(array(
//             "status" => "failure",
//             "message" => "Failed to create record",
//             "error" => $errorInfo // Message d'erreur détaillé
//         ));
//     }
// }

function logToFile($message) {
    $logFile = __DIR__ . '/log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

function generateGUID() {
    if (function_exists('com_create_guid')) {
        return trim(com_create_guid(), '{}');
    } else {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

function createAnnonce($conn, $title = null, $description = null, $createdAt = null, $updatedAt = null, $dealType = null, $dealCategory = null, $showAddress = null, $brand = null, $initialPrice = null, $discountValue = null, $discountType = null, $finalPrice = null, $website = null, $isOnline = null, $shippingCost = null, $startDate = null, $endDate = null, $messageId = null, $userId = null, $adresse = null, $codePostal = null, $ville = null, $pays = null) {
    try {
        logToFile("Début de la fonction createAnnonce.");

        // Générer un ID unique pour l'annonce
        $id = generateGUID();

        // Préparer les champs à insérer dynamiquement
        $fields = ['id' => $id];
        $columns = ['id'];
        $placeholders = [':id'];

        if ($title !== null) {
            $fields['title'] = $title;
            $columns[] = 'title';
            $placeholders[] = ':title';
        }
        if ($description !== null) {
            $fields['description'] = $description;
            $columns[] = 'description';
            $placeholders[] = ':description';
        }
        if ($createdAt !== null) {
            $fields['createdAt'] = $createdAt;
            $columns[] = 'createdAt';
            $placeholders[] = ':createdAt';
        }
        if ($updatedAt !== null) {
            $fields['updatedAt'] = $updatedAt;
            $columns[] = 'updatedAt';
            $placeholders[] = ':updatedAt';
        }
        if ($dealType !== null) {
            $fields['dealType'] = $dealType;
            $columns[] = 'dealType';
            $placeholders[] = ':dealType';
        }
        if ($dealCategory !== null) {
            $fields['dealCategory'] = $dealCategory;
            $columns[] = 'dealCategory';
            $placeholders[] = ':dealCategory';
        }
        if ($showAddress !== null) {
            $fields['showAddress'] = $showAddress;
            $columns[] = 'showAddress';
            $placeholders[] = ':showAddress';
        }
        if ($brand !== null) {
            $fields['brand'] = $brand;
            $columns[] = 'brand';
            $placeholders[] = ':brand';
        }
        if ($initialPrice !== null) {
            $fields['initialPrice'] = $initialPrice;
            $columns[] = 'initialPrice';
            $placeholders[] = ':initialPrice';
        }
        if ($discountValue !== null) {
            $fields['discountValue'] = $discountValue;
            $columns[] = 'discountValue';
            $placeholders[] = ':discountValue';
        }
        if ($discountType !== null) {
            $fields['discountType'] = $discountType;
            $columns[] = 'discountType';
            $placeholders[] = ':discountType';
        }
        if ($finalPrice !== null) {
            $fields['finalPrice'] = $finalPrice;
            $columns[] = 'finalPrice';
            $placeholders[] = ':finalPrice';
        }
        if ($website !== null) {
            $fields['website'] = $website;
            $columns[] = 'website';
            $placeholders[] = ':website';
        }
        if ($isOnline !== null) {
            $fields['isOnline'] = $isOnline;
            $columns[] = 'isOnline';
            $placeholders[] = ':isOnline';
        }
        if ($shippingCost !== null) {
            $fields['shippingCost'] = $shippingCost;
            $columns[] = 'shippingCost';
            $placeholders[] = ':shippingCost';
        }
        if ($startDate !== null) {
            $fields['startDate'] = $startDate;
            $columns[] = 'startDate';
            $placeholders[] = ':startDate';
        }
        if ($endDate !== null) {
            $fields['endDate'] = $endDate;
            $columns[] = 'endDate';
            $placeholders[] = ':endDate';
        }
        if ($messageId !== null) {
            $fields['messageId'] = $messageId;
            $columns[] = 'messageId';
            $placeholders[] = ':messageId';
        }
        if ($userId !== null) {
            $fields['userId'] = $userId;
            $columns[] = 'userId';
            $placeholders[] = ':userId';
        }
        if ($adresse !== null) {
            $fields['adresse'] = $adresse;
            $columns[] = 'adresse';
            $placeholders[] = ':adresse';
        }
        if ($codePostal !== null) {
            $fields['codepostal'] = $codePostal;
            $columns[] = 'codepostal';
            $placeholders[] = ':codepostal';
        }
        if ($ville !== null) {
            $fields['ville'] = $ville;
            $columns[] = 'ville';
            $placeholders[] = ':ville';
        }
        if ($pays !== null) {
            $fields['pays'] = $pays;
            $columns[] = 'pays';
            $placeholders[] = ':pays';
        }

        // Construire la requête d'insertion
        $query = 'INSERT INTO "annonces" (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $statement = $conn->prepare($query);

        // Lier les paramètres
        foreach ($fields as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }

        // Exécuter la requête
        $result = $statement->execute();

        setJsonHeader();
        if ($result) {
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



function updateAnnonce($conn, $id, $title = null, $description = null, $createdAt = null, $updatedAt = null, $dealType = null, $dealCategory = null, $showAddress = null, $brand = null, $initialPrice = null, $discountValue = null, $discountType = null, $finalPrice = null, $website = null, $isOnline = null, $shippingCost = null, $startDate = null, $endDate = null, $messageId = null, $userId = null, $adresse = null, $codepostal = null, $ville = null, $pays = null) {
    try {
        // Vérifier si l'ID est fourni
        if (!$id) {
            throw new Exception("L'ID de l'annonce est requis.");
        }

        $fieldsToUpdate = [];
        $params = ['id' => $id];

        // Construire les champs à mettre à jour dynamiquement
        if ($title !== null) {
            $fieldsToUpdate[] = '"title" = :title';
            $params['title'] = $title;
        }
        if ($description !== null) {
            $fieldsToUpdate[] = '"description" = :description';
            $params['description'] = $description;
        }
        if ($createdAt !== null) {
            $fieldsToUpdate[] = '"createdAt" = :createdAt';
            $params['createdAt'] = $createdAt;
        }
        if ($updatedAt !== null) {
            $fieldsToUpdate[] = '"updatedAt" = :updatedAt';
            $params['updatedAt'] = $updatedAt;
        }
        if ($dealType !== null) {
            $fieldsToUpdate[] = '"dealType" = :dealType';
            $params['dealType'] = $dealType;
        }
        if ($dealCategory !== null) {
            $fieldsToUpdate[] = '"dealCategory" = :dealCategory';
            $params['dealCategory'] = $dealCategory;
        }
        if ($showAddress !== null) {
            $fieldsToUpdate[] = '"showAddress" = :showAddress';
            $params['showAddress'] = $showAddress;
        }
        if ($brand !== null) {
            $fieldsToUpdate[] = '"brand" = :brand';
            $params['brand'] = $brand;
        }
        if ($initialPrice !== null) {
            $fieldsToUpdate[] = '"initialPrice" = :initialPrice';
            $params['initialPrice'] = $initialPrice;
        }
        if ($discountValue !== null) {
            $fieldsToUpdate[] = '"discountValue" = :discountValue';
            $params['discountValue'] = $discountValue;
        }
        if ($discountType !== null) {
            $fieldsToUpdate[] = '"discountType" = :discountType';
            $params['discountType'] = $discountType;
        }
        if ($finalPrice !== null) {
            $fieldsToUpdate[] = '"finalPrice" = :finalPrice';
            $params['finalPrice'] = $finalPrice;
        }
        if ($website !== null) {
            $fieldsToUpdate[] = '"website" = :website';
            $params['website'] = $website;
        }
        if ($isOnline !== null) {
            $fieldsToUpdate[] = '"isOnline" = :isOnline';
            $params['isOnline'] = $isOnline;
        }
        if ($shippingCost !== null) {
            $fieldsToUpdate[] = '"shippingCost" = :shippingCost';
            $params['shippingCost'] = $shippingCost;
        }
        if ($startDate !== null) {
            $fieldsToUpdate[] = '"startDate" = :startDate';
            $params['startDate'] = $startDate;
        }
        if ($endDate !== null) {
            $fieldsToUpdate[] = '"endDate" = :endDate';
            $params['endDate'] = $endDate;
        }
        if ($messageId !== null) {
            $fieldsToUpdate[] = '"messageId" = :messageId';
            $params['messageId'] = $messageId;
        }
        if ($userId !== null) {
            $fieldsToUpdate[] = '"userId" = :userId';
            $params['userId'] = $userId;
        }
        if ($adresse !== null) {
            $fieldsToUpdate[] = '"adresse" = :adresse';
            $params['adresse'] = $adresse;
        }
        if ($codepostal !== null) {
            $fieldsToUpdate[] = '"codepostal" = :codepostal';
            $params['codepostal'] = $codepostal;
        }
        if ($ville !== null) {
            $fieldsToUpdate[] = '"ville" = :ville';
            $params['ville'] = $ville;
        }
        if ($pays !== null) {
            $fieldsToUpdate[] = '"pays" = :pays';
            $params['pays'] = $pays;
        }

        // Vérifier s'il y a des champs à mettre à jour
        if (empty($fieldsToUpdate)) {
            throw new Exception("Aucune donnée à mettre à jour.");
        }

        // Construire la requête
        $query = 'UPDATE "annonces" SET ' . implode(', ', $fieldsToUpdate) . ' WHERE "id" = :id';
        $statement = $conn->prepare($query);

        // Exécuter la requête
        $result = $statement->execute($params);

        setJsonHeader();
        if ($result) {
            echo json_encode([
                "status" => "success",
                "message" => "Annonce mise à jour avec succès.",
                "updated_fields" => array_keys($params)
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
    function readRecords($conn) {
        $query = 'SELECT * FROM "annonces" ORDER BY "id" DESC';
        $statement = $conn->prepare($query);
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        setJsonHeader();
        if ($result) {
        echo json_encode(array("status" => "success","adType" => $result));
        }else {
        http_response_code(500); // Erreur de serveur interne
        echo json_encode(array("status" => "failure", "message" => "Failed to get records"));
    }
    }
    
   
function readRecordsSearch($conn, $searchbar) {
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
    function readRecordsPaginate($conn,$page) {
        $query = 'SELECT * FROM "Reservation" ORDER BY "createdAt" DESC LIMIT 10 OFFSET (:page - 1) * 10';
        $statement = $conn->prepare($query);
        $statement->bindParam(':page', $page);
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        setJsonHeader();
        if ($result) {
        echo json_encode(array("status" => "success","data" => $result));
        }else {
        http_response_code(500); // Erreur de serveur interne
        echo json_encode(array("status" => "failure", "message" => "Failed to get records"));
    }
    }
    
     // Fonction pour total de page et records
    function readRecordsPaginateSize($conn) {
        $query = 'SELECT COUNT(*) as totalRows, CEILING(COUNT(*) / 10) as totalPages FROM "Reservation"';
        $statement = $conn->prepare($query);
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        setJsonHeader();
        if ($result) {
        echo json_encode(array("status" => "success","data" => $result));
        }else {
        http_response_code(500); // Erreur de serveur interne
        echo json_encode(array("status" => "failure", "message" => "Failed to get records"));
    }
    }

        // Fonction pour lire un enregistrement 
        function readRecord($conn, $id) {
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

    function readRecordsByUserId($conn, $id) {
        $query = 'SELECT * FROM "Profile" where userId like :id';
        $query = 'SELECT * FROM "Profile" WHERE LOWER("userId") LIKE :id';
        $statement = $conn->prepare($query);
        $statement->bindValue(':id', '%' . $id . '%'); // Ajouter les % pour une recherche partielle
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);


        // $statement = $conn->prepare($query);
        // $statement->bindParam(':id', $id);
        // $statement->execute();
        // $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        setJsonHeader();
        if ($result) {
        echo json_encode(array("status" => "success","profile" => $result));
        }else {
        http_response_code(500); // Erreur de serveur interne
        echo json_encode(array("status" => "failure", "message" => "Failed to get record"));
    }
    }

    
    function readRecordByName($conn, $category) {
        $query = 'SELECT * FROM "AdType" where name = :name';
        $statement = $conn->prepare($query);
        $statement->bindParam(':name', $category);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        setJsonHeader();
        if ($result) {
        echo json_encode(array("status" => "success","adType" => $result));
        }else {
        http_response_code(500); // Erreur de serveur interne
        echo json_encode(array("status" => "failure", "message" => "Failed to get record"));
    }
    }
    
    
    // Fonction pour mettre à jour un enregistrement
    function updateRecord($conn, $id, $data) {
        $columns = array_keys($data);
        $values = array_values($data);
        
        $quotedColumns = array_map(function($column) {
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
    function deleteRecord($conn, $id) {
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

    function addColumnsToProfileTable($conn) {
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
        // createUserInfo($conn, $userId, $profileType, $pseudo, $photoProfilUrl, $telephone, $siret, $nomSociete, $activite, $adresse, $ville, $codePostal, $pays);
        
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
    $profileType = $_POST['siret'] ? "professionnel" : "particulier";

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
            createAnnonce($conn, $title, $description, $createdAt, $updatedAt, $dealType, $dealCategory, $showAddress, $brand, $initialPrice, $discountValue, $discountType, $finalPrice, $website, $isOnline, $shippingCost, $startDate, $endDate, $messageId, $userId, $adresse, $codePostal, $ville, $pays);
        } else {
            echo json_encode(["status" => "failure", "message" => "Échec du téléchargement de l'image."]);
            exit();
        }
    } else {
        // echo json_encode(["status" => "failure", "message" => "Aucune image envoyée."]);
        // exit();
        createAnnonce($conn, $title, $description, $createdAt, $updatedAt, $dealType, $dealCategory, $showAddress, $brand, $initialPrice, $discountValue, $discountType, $finalPrice, $website, $isOnline, $shippingCost, $startDate, $endDate, $messageId, $userId, $adresse, $codePostal, $ville, $pays);

    }
    
    }elseif($method == 'readAll'){
        readRecords($conn);
    } elseif($method == 'addColumnsToProfileTable') {
        addColumnsToProfileTable($conn);
    } elseif($method == 'updateAnnonce') {
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
            updateAnnonce($conn, $id, $title, $description, $createdAt, $updatedAt, $dealType, $dealCategory, $showAddress, $brand, $initialPrice, $discountValue, $discountType, $finalPrice, $website, $isOnline, $shippingCost, $startDate, $endDate, $messageId, $userId, $adresse, $codepostal, $ville, $pays);
        } else {
            echo json_encode(["status" => "failure", "message" => "Échec du téléchargement de l'image."]);
            exit();
        }
    } else {
        // echo json_encode(["status" => "failure", "message" => "Aucune image envoyée."]);
        // exit();
        updateAnnonce($conn, $id, $title, $description, $createdAt, $updatedAt, $dealType, $dealCategory, $showAddress, $brand, $initialPrice, $discountValue, $discountType, $finalPrice, $website, $isOnline, $shippingCost, $startDate, $endDate, $messageId, $userId, $adresse, $codepostal, $ville, $pays);

    }
        // updateUserInfo($conn, $id, $userId , $profileType, $pseudo, $photoProfilUrl, $telephone, $siret, $nomSociete, $activite, $adresse, $ville, $codePostal, $pays);
    } elseif($method == 'readAllByUserId'){
        readRecordsByUserId($conn, $id);
    }  elseif ($method == 'read') {
        readRecord($conn, $id);
        
    } elseif ($method == 'readByName') {
        readRecordByName($conn, $category);
        
    } elseif ($method == 'update') {
        updateRecord($conn, $id, $data);
    } elseif ($method == 'delete') {
        deleteRecord($conn, $id);
    }elseif($method == 'paginate'){
        readRecordsPaginate($conn,$page);
    }elseif($method == 'paginateSize'){
        readRecordsPaginateSize($conn);
    }else if($method == 'searchbar'){
        readRecordsSearch($conn, $searchbar);
    }
?>
