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
include("./packages/AmbassadorAction.php");

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Si la méthode n'est pas POST, retourner un message simple et quitter
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(404);
    exit;
}

// Récupérer les données du formulaire
$method = $_POST['Method'];

if (
    $method !== 'create' && $method !== 'readAll' && $method !== 'read' && $method !== 'getCommentairesByAnnonceId'
    && $method !== 'update'  && $method !== 'delete'  && $method !== 'paginate'  && $method !== 'addColumnsToProfileTable'
    && $method !== 'paginateSize'  && $method !== 'searchbar'  && $method !== 'readByName'  && $method !== 'readAdsByCriteria'
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

function createRecord($conn)
{
    try {
        logToFile("Début de la fonction createRecord.");

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
        $query = 'INSERT INTO "commentaires" (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
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

            if (!empty($_POST['userid'])) {
                $coinEvents = new EventCoinsFacade($conn);
                $coinEvents->addComment($_POST['userid']);
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






// Fonction pour lire tout les  enregistrements
function readRecords($conn)
{
    try {
        // Construire la requête pour récupérer tous les enregistrements, triés par ID descendant
        $query = 'SELECT * FROM "commentaires" ORDER BY "id" DESC';
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
                "commentaires" => $result // Inclure les données récupérées dans la réponse
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
            "message" => "An error occurred while fetching the commentaires.",
            "details" => $e->getMessage()
        ]);
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

        $query = 'SELECT * FROM commentaires WHERE 1=1'; // Base de la requête
        $params = [];

        // Ajouter les conditions dynamiques
        foreach ($fields as $column => $value) {
            if (!is_null($value)) { // Ignorer les valeurs nulles
                // Ignorer la casse en utilisant LOWER pour les deux côtés
                $query .= ' AND LOWER("' . $column . '") LIKE LOWER(:' . $column . ')';
                $params[$column] = '%' . strtolower($value) . '%';
            }
        }

        // Préparer et exécuter la requête
        $statement = $conn->prepare($query);
        foreach ($params as $param => $value) {
            $statement->bindValue(':' . $param, $value);
        }

        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        setJsonHeader();
        if ($result) {
            echo json_encode(["status" => "success", "commentaires" => $result]);
        } else {
            // http_response_code(404); // Aucun résultat
            // echo json_encode(["status" => "failure", "message" => "No commentaires found matching the criteria.", "commentaires" => [] ]);
            echo json_encode(["status" => "success", "commentaires" => []]);
        }
    } catch (Exception $e) {
        setJsonHeader();
        http_response_code(500); // Erreur interne
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
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
        $query = 'UPDATE "commentaires" SET ' . implode(', ', $fieldsToUpdate) . ' WHERE "id" = :id';
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

// Fonction pour récupérer les commentaires par annonceId
function getCommentairesByAnnonceId($conn)
{
    $annonceId = $_POST['annonceid'] ?? null;

    if (!$annonceId) {
        echo json_encode(["status" => "failure", "message" => "L'ID de l'annonce est requis."]);
        return;
    }

    $query = "SELECT commentaires.*, ui.pseudo, ui.nomsociete, ui.photoprofilurl FROM \"commentaires\" JOIN \"userInfo\" ui ON \"commentaires\".\"userid\" = ui.\"userid\" WHERE \"annonceid\" = :annonceid";
    $statement = $conn->prepare($query);
    $statement->bindParam(':annonceid', $annonceId);
    $result = $statement->execute();

    setJsonHeader();
    if ($result) {
        echo json_encode(["status" => "success", "commentaires" => $result]);
    } else {
        echo json_encode(["status" => "failure", "message" => "No commentaires found matching the criteria.", "commentaires" => [] ]);
    }
}

// Fonction pour supprimer un enregistrement
function deleteRecord($conn, $id)
{
    $query = "DELETE FROM \"commentaires\" WHERE id = :id";
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


if ($method == 'create') {
    createRecord($conn);
} elseif ($method == 'readAll') {
    readRecords($conn);
} elseif ($method == 'readAdsByCriteria') {
    readAdsByCriteria($conn);
} elseif ($method == 'getCommentairesByAnnonceId') {
    getCommentairesByAnnonceId($conn);
} elseif ($method == 'read') {
    readRecord($conn, $id);
} elseif ($method == 'delete') {
    deleteRecord($conn, $id);
}
