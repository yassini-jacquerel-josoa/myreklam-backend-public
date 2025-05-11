<?php

// Bloquer l'accès direct depuis un navigateur en renvoyant une erreur 404
if ($_SERVER['REQUEST_METHOD'] === 'GET' && basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    http_response_code(404);
    exit;
}
    // Inclure la connexion à la base de données
    include_once("./db.php");

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

   
    if ($method !== 'create' && $method !== 'readAll' && $method !== 'read'
    && $method !== 'update'  && $method !== 'delete'  && $method !== 'paginate'
    && $method !== 'paginateSize'  && $method !== 'searchbar'  && $method !== 'readByName') {
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


    
    // Fonction pour lire tout les  enregistrements
    function readRecords($conn) {
        $query = 'SELECT * FROM "EventType" ORDER BY "id" DESC';
        $statement = $conn->prepare($query);
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        setJsonHeader();
        if ($result) {
        echo json_encode(array("status" => "success","eventType" => $result));
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

    // Vérifier la méthode et appeler la fonction appropriée
    if ($method == 'create') {
        createRecord($conn, $data);
    }elseif($method == 'readAll'){
        readRecords($conn);
    } elseif ($method == 'read') {
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
