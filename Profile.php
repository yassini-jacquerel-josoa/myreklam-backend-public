<?php

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
    $data = $_POST['Data']; // Données à insérer ou mettre à jour
    $page = $_POST['Page'];
    $category = $_POST['Category'];
    $searchbar = strtolower($_POST['Searchbar']); // Mettre le terme de recherche en minuscule 

    $username = isset($_POST['username']) ? trim($_POST['username']) : null;
    $website = isset($_POST['website']) ? trim($_POST['website']) : null;
    $organizationId = isset($_POST['organizationId']) ? trim($_POST['organizationId']) : null;
    $customerId = isset($_POST['customerId']) ? trim($_POST['customerId']) : null;
    $email = isset($_POST['email']) ? trim($_POST['email']) : null;
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
    $status = isset($_POST['status']) ? intval($_POST['status']) : null;
    $userId = isset($_POST['userId']) ? trim($_POST['userId']) : null;
    $permission = isset($_POST['permission']) ? intval($_POST['permission']) : null;
    $roleId = isset($_POST['roleId']) ? trim($_POST['roleId']) : null;
    $banId = isset($_POST['banId']) ? trim($_POST['banId']) : null;
    $password = isset($_POST['password']) ? trim($_POST['password']) : null;
    $token = isset($_POST['token']) ? trim($_POST['token']) : null;
    $isverified = isset($_POST['isverified']) ? intval($_POST['isverified']) : null;

    
    // $username = $_POST['username'];
    // $website = $_POST['website'];
    // $organizationId = $_POST['organizationId'];
    // $customerId = isset($_POST['customerId']) ? trim($_POST['customerId']) : null;
    // $email = isset($_POST['email']) ? trim($_POST['email']) : null;
    // $phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
    // $status = isset($_POST['status']) ? intval($_POST['status']) : null;
    // $userId = isset($_POST['userId']) ? trim($_POST['userId']) : null;
    // $permission = isset($_POST['permission']) ? intval($_POST['permission']) : null;
    // $roleId = isset($_POST['roleId']) ? trim($_POST['roleId']) : null;
    // $banId = isset($_POST['banId']) ? trim($_POST['banId']) : null;
    // $password = isset($_POST['password']) ? trim($_POST['password']) : null;
    // $token = isset($_POST['token']) ? trim($_POST['token']) : null;
    // $isverified = isset($_POST['isverified']) ? intval($_POST['isverified']) : null;


    // $username = isset($_POST['username']) ? trim($_POST['username']) : (isset($_POST['Username']) ? trim($_POST['Username']) : null);
    // $phone = isset($_POST['phone']) ? trim($_POST['phone']) : (isset($_POST['Phone']) ? trim($_POST['Phone']) : null);
    // $id = isset($_POST['id']) ? trim($_POST['id']) : (isset($_POST['Id']) ? trim($_POST['Id']) : null);

    
   
    if ($method !== 'create' && $method !== 'readAll' && $method !== 'read' && $method !== 'updateProfile'
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
function createRecord($conn, $data) {
    $columns = array_keys($data);
    $values = array_values($data);
    
    $quotedColumns = array_map(function($column) {
    return "\"$column\"";
}, $columns);

$columnsString = implode(", ", $quotedColumns);

    $placeholders = implode(", ", array_fill(0, count($columns), "?"));
    
    $query = "INSERT INTO \"Reservation\" ($columnsString) VALUES ($placeholders)";
    
    $statement = $conn->prepare($query);
    
    // Boucler à travers les valeurs et les lier aux placeholders
    for ($i = 0; $i < count($values); $i++) {
        $statement->bindValue(($i + 1), $values[$i]);
    }
    
    
    $result = $statement->execute();
    setJsonHeader();
    if ($result) {
        echo json_encode(array("status" => "success", "message" => "Record successfully created"));
    } else {
        $errorInfo = $statement->errorInfo();
        //http_response_code(401); // Erreur de serveur interne
        echo json_encode(array(
            "status" => "failure",
            "message" => "Failed to create record",
            "error" => $errorInfo // Message d'erreur détaillé
        ));
    }
}

function logToFile($message) {
    $logFile = __DIR__ . '/log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

function updateProfile($conn, $id, $username = null, $website = null, $organizationId = null, $customerId = null, $email = null, $phone = null, $status = null, $userId = null, $permission = null, $roleId = null, $banId = null, $password = null, $token = null, $isverified = null) {
    try {
        $namePost = $_POST['username'];
        logToFile("Début de la mise à jour du ptofil. ID: $id");
        logToFile("Contenu de \$_POST : " . json_encode($_POST));
        logToFile("Paramètres extraits : username = $username, phone = $phone; namePost = $namePost ");

        // echo json_encode([
        //     "status" => "success",
        //     "message" => "Profil mis à jour avec succès.",
        //     "postdataa" => json_encode($_POST)
        // ]);

        // Vérifier si l'ID est fourni
        if (!$id) {
            throw new Exception("L'ID du profil est requis.");
        }

        $fieldsToUpdate = [];
        $params = ['id' => $id];

        // Construire les champs à mettre à jour dynamiquement
        if ($username !== null) {
            $fieldsToUpdate[] = '"username" = :username';
            $params['username'] = $username;
        }
        if ($website !== null) {
            $fieldsToUpdate[] = '"website" = :website';
            $params['website'] = $website;
        }
        if ($organizationId !== null) {
            $fieldsToUpdate[] = '"organizationId" = :organizationId';
            $params['organizationId'] = $organizationId;
        }
        if ($customerId !== null) {
            $fieldsToUpdate[] = '"customerId" = :customerId';
            $params['customerId'] = $customerId;
        }
        if ($email !== null) {
            $fieldsToUpdate[] = '"email" = :email';
            $params['email'] = $email;
        }
        if ($phone !== null) {
            $fieldsToUpdate[] = '"phone" = :phone';
            $params['phone'] = $phone;
        }
        if ($status !== null) {
            $fieldsToUpdate[] = '"status" = :status';
            $params['status'] = $status;
        }
        if ($userId !== null) {
            $fieldsToUpdate[] = '"userId" = :userId';
            $params['userId'] = $userId;
        }
        if ($permission !== null) {
            $fieldsToUpdate[] = '"permission" = :permission';
            $params['permission'] = $permission;
        }
        if ($roleId !== null) {
            $fieldsToUpdate[] = '"roleId" = :roleId';
            $params['roleId'] = $roleId;
        }
        if ($banId !== null) {
            $fieldsToUpdate[] = '"banId" = :banId';
            $params['banId'] = $banId;
        }
        if ($password !== null) {
            $fieldsToUpdate[] = '"password" = :password';
            $params['password'] = $password;
        }
        if ($token !== null) {
            $fieldsToUpdate[] = '"token" = :token';
            $params['token'] = $token;
        }
        if ($isverified !== null) {
            $fieldsToUpdate[] = '"isverified" = :isverified';
            $params['isverified'] = $isverified;
        }

        // Vérifier s'il y a des champs à mettre à jour
        if (empty($fieldsToUpdate)) {
            throw new Exception("Aucune donnée à mettre à jour.");
        }

        // Construire la requête
        $query = 'UPDATE "Profile" SET ' . implode(', ', $fieldsToUpdate) . ' WHERE id = :id';
        $statement = $conn->prepare($query);

        // Exécuter la requête
        $result = $statement->execute($params);

        setJsonHeader();

        if ($result) {
            echo json_encode([
                "status" => "success",
                "message" => "Profil mis à jour avec succès.",
                "updated_fields" => array_keys($params)
            ]);
        } else {
            echo json_encode([
                "status" => "failure",
                "message" => "Échec de la mise à jour du profil.",
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
    //updateProfile($conn, $id, $username = null, $website = null, $organizationId = null, $customerId = null, $email = null, $phone = null, $status = null, $userId = null, $permission = null, $roleId = null, $banId = null, $password = null, $token = null, $isverified = null)
    if ($method == 'create') {
        createRecord($conn, $data);
    }elseif($method == 'readAll'){
        readRecords($conn);
    } elseif($method == 'updateProfile'){
        updateProfile($conn, $id, $username, $website, $organizationId, $customerId, $email, $phone, $status, $userId, $permission, $roleId, $banId, $password, $token, $isverified);
    } elseif($method == 'addColumnsToProfileTable') {
        addColumnsToProfileTable($conn);
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
