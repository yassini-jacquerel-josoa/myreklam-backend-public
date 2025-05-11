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
    include_once(__DIR__ . "/db.php");

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
    $id = $_POST['Id']; // ID de l'enregistrement à modifier ou supprimer
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
    // $searchbar = strtolower($_POST['Searchbar']); // Mettre le terme de recherche en minuscule 

   
    if ($method !== 'create' && $method !== 'readAll' && $method !== 'read' && $method !== 'updateOrganization'
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

function createRecord($conn, $siret, $name, $activityId,$addressId) {
    try {
        logToFile("Début de la fonction createRecord. SIRET: $siret, Name: $name, ActivityID: $activityId");
        
        // Vérifier si l'utilisateur existe déjà
        $query = 'SELECT * FROM "Organization" WHERE siret = :siret';
        $statement = $conn->prepare($query);
        $statement->bindParam(':siret', $siret);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
 logToFile("Résultat de la requête de vérification: ". print_r($result, true));
        setJsonHeader();
        if ($result) {
            echo json_encode(array(
                "status" => "error",
                "message" => "Une entreprise avec ce SIRET mail existe déjà. S'il s'agit de vous, veuillez vous connecter ou nous contacter."
            ));
        } else {
            // Hasher le mot de passe
            // $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $id = generateGUID();
            $part1 = generateGUID();
            $part2 = generateGUID();
            $token = $part1. '-'. $id. '-'. $part2;
            // Requête d'insertion
            // $query0 = 'INSERT INTO "Profile" (id, email, password) VALUES (:id, :email, :password)';
            // $statement0 = $conn->prepare($query0);
            // $statement->bindParam(':id', $id);
            // $statement0->bindParam(':email', $email);
            // $statement0->bindParam(':password', $hashed_password);
            $roleName = 'user';
            $query0 = $activityId !== null 
                ? 'INSERT INTO "Organization" (id, siret, name, "activityId","addressId") VALUES (:id, :siret, :name, :activityId, :addressId)'
                : 'INSERT INTO "Organization" (id, siret, name) VALUES (:id, :siret, :name)';

            $statement0 = $conn->prepare($query0);
            $statement0->bindParam(':id', $id);
            $statement0->bindParam(':siret', $siret);
            $statement0->bindParam(':name', $name);
            $statement0->bindParam(':addressId', $addressId);

            if ($activityId !== null) {
                $statement0->bindParam(':activityId', $activityId);
            }
            // $query0 = 'INSERT INTO "Organization" (id, siret, name, activityId) VALUES (:id, :siret, :name, :activityId )';
            // $statement0 = $conn->prepare($query0);
            // $statement0->bindParam(':id', $id);
            // $statement0->bindParam(':siret', $siret);
            // $statement0->bindParam(':name', $name);
            // $statement0->bindParam(':activityId', $activityId);
            $result0 = $statement0->execute();
            logToFile("Résultat de la requête d'insertion: ". print_r($result0, true));

            // $result0 = $statement0->execute();

            if ($result0) {
                // Vérifier si l'organisation a bien "t" créée
        $query01 = 'SELECT * FROM "Organization" WHERE id = :id';
        $statement01 = $conn->prepare($query01);
        $statement01->bindParam(':id', $id);
        $statement01->execute();
        $result01 = $statement01->fetch(PDO::FETCH_ASSOC);
        logToFile("Résultat de la requête de vérification de l'organisation: ". print_r($result01, true));
        if ($result01) {
            echo json_encode(array(
                    "status" => "success",
                    "message" => "Entrprise créé avec succès",
                    "id" => $result01['id']
                ));
        } 

                // echo json_encode(array(
                //     "status" => "success",
                //     "message" => "Utilisateur créé avec succès"
                // ));
            } else {
                echo json_encode(array(
                    "status" => "failure",
                    "message" => "Échec de la création de l'entreprise",
                    "error" => $statement0->errorInfo()
                ));
            }
        }
    } catch (Exception $e) {
        setJsonHeader();
        echo json_encode(array(
            "status" => "error",
            "message" => "Erreur du serveur",
            "details" => $e->getMessage()
        ));
    }
}

function updateOrganization($conn, $id, $name = null, $logo = null, $website = null, $activityId = null, $addressId = null, $description = null, $siret = null, $phone = null) {
    try {
        // Vérifier si l'ID est fourni
        if (!$id) {
            throw new Exception("L'ID de l'organisation est requis.");
        }

        $fieldsToUpdate = [];
        $params = ['id' => $id];

        // Construire les champs à mettre à jour dynamiquement
        if ($name !== null) {
            $fieldsToUpdate[] = '"name" = :name';
            $params['name'] = $name;
        }
        if ($logo !== null) {
            $fieldsToUpdate[] = '"logo" = :logo';
            $params['logo'] = $logo;
        }
        if ($website !== null) {
            $fieldsToUpdate[] = '"website" = :website';
            $params['website'] = $website;
        }
        if ($activityId !== null) {
            $fieldsToUpdate[] = '"activityId" = :activityId';
            $params['activityId'] = $activityId;
        }
        if ($addressId !== null) {
            $fieldsToUpdate[] = '"addressId" = :addressId';
            $params['addressId'] = $addressId;
        }
        if ($description !== null) {
            $fieldsToUpdate[] = '"description" = :description';
            $params['description'] = $description;
        }
        if ($siret !== null) {
            $fieldsToUpdate[] = '"siret" = :siret';
            $params['siret'] = $siret;
        }
        if ($phone !== null) {
            $fieldsToUpdate[] = '"phone" = :phone';
            $params['phone'] = $phone;
        }

        // Vérifier s'il y a des champs à mettre à jour
        if (empty($fieldsToUpdate)) {
            throw new Exception("Aucune donnée à mettre à jour.");
        }

        // Construire la requête
        $query = 'UPDATE "Organization" SET ' . implode(', ', $fieldsToUpdate) . ' WHERE id = :id';
        $statement = $conn->prepare($query);

        // Exécuter la requête
        $result = $statement->execute($params);

        setJsonHeader();

        if ($result) {
            echo json_encode([
                "status" => "success",
                "message" => "Organisation mise à jour avec succès.",
                "updated_fields" => array_keys($params)
            ]);
        } else {
            echo json_encode([
                "status" => "failure",
                "message" => "Échec de la mise à jour de l'organisation.",
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
        $query = 'SELECT * FROM "Profile" ORDER BY "id" DESC';
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
        $query = 'SELECT * FROM "Ad" where id = :id';
        $statement = $conn->prepare($query);
        $statement->bindParam(':id', $id);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        setJsonHeader();
        if ($result) {
        echo json_encode(array("status" => "success","data" => $result));
        }else {
        http_response_code(500); // Erreur de serveur interne
        echo json_encode(array("status" => "failure", "message" => "Failed to get record"));
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
                ALTER TABLE "Profile" 
                ADD COLUMN IF NOT EXISTS token TEXT,
                ADD COLUMN IF NOT EXISTS isVerified INT DEFAULT 0 CHECK (isVerified IN (0, 1));
            ';
    
            // Exécution de la requête
            $conn->exec($query);
    
            // Retourner un message de succès
            echo json_encode(array("status" => "success", "message" => "Colonnes 'token' et 'isVerified' ajoutées avec succès"));
        } catch (PDOException $e) {
            // Gestion des erreurs
            echo json_encode(array("status" => "error", "message" => "Erreur lors de l'ajout des colonnes : " . $e->getMessage()));
        }
    }
    



    // Vérifier la méthode et appeler la fonction appropriée 
    //updateOrganization($conn, $id, $name = null, $logo = null, $website = null, $activityId = null, $addressId = null, $description = null, $siret = null, $phone = null)
    if ($method == 'create') {
        createRecord($conn, $siret, $name, $activityId, $addressId);
    }elseif($method == 'readAll'){
        readRecords($conn);
    } elseif($method == 'addColumnsToProfileTable') {
        addColumnsToProfileTable($conn);
    } elseif($method == 'updateOrganization') {
    updateOrganization($conn, $id, $name, $logo, $website, $activityId, $addressId, $description, $siret, $phone);
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
