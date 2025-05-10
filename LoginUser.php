<?php

// Bloquer l'accès direct depuis un navigateur en renvoyant une erreur 404
if ($_SERVER['REQUEST_METHOD'] === 'GET' && basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    http_response_code(404);
    exit;
}
// Inclure la connexion à la base de données
include("./db.php");
include("./packages/NotificationBrevoAndWeb.php");

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
$data = $_POST['Data']; // Données à insérer ou mettre à jour $newPassword
$page = $_POST['Page'];
$category = $_POST['Category'];
$searchbar = strtolower($_POST['Searchbar']); // Mettre le terme de recherche en minuscule token  getTableColumns
$email = $_POST["userEmail"];
$password = $_POST["userPassword"];
$token = $_POST["token"];
$newPassword = $_POST["newPassword"];
$tableName = $_POST["tableName"];


if (
    $method !== 'create' && $method !== 'readAll' && $method !== 'read' && $method !== 'resetPassword' && $method !== 'updatePassword'
    && $method !== 'update'  && $method !== 'delete'  && $method !== 'paginate'  && $method !== 'verify'   && $method !== 'readAllByUserId'
    && $method !== 'paginateSize'  && $method !== 'searchbar'  && $method !== 'readByName' && $method !== 'getTableColumns'
    && $method !== 'deleteAccount' && $method !== 'deleteFile'
) {
    http_response_code(404);
    exit;
}



// Fonction pour définir le type de contenu JSON
function setJsonHeader()
{
    header('Content-Type: application/json');
}

// Fonction pour créer un enregistrement
// function createRecord($conn, $email, $password) {

// $query = 'SELECT * FROM "Profile" where email = :email';
// $statement = $conn->prepare($query);
//        $statement->bindParam(':email', $email);
//        $statement->execute();
//        $result = $statement->fetch(PDO::FETCH_ASSOC);
//        setJsonHeader();
//            if ($result) {
//             echo json_encode(array("status" => "error","message" => "Un utilisateur avec cette adresse mail existe déjà. S'il s'agit de vous veuillez vous connecter."));

//            } else {
//                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
//                $query0 = "INSERT INTO Profile (email, password) VALUES ($email, $hashed_password)";

//                $statement0 = $conn->prepare($query0);

//                $result0 = $statement0->execute();
//                setJsonHeader();
//                if ($result0) {
//                    echo json_encode(array("status" => "success", "message" => "Record successfully created"));
//                } else {
//                    $errorInfo = $statement->errorInfo();
//                    //http_response_code(401); // Erreur de serveur interne
//                    echo json_encode(array(
//                        "status" => "failure",
//                        "message" => "Failed to create record",
//                        "error" => $errorInfo // Message d'erreur détaillé
//                    ));
//                }
//            }



// }

function logToFile($message)
{
    $logFile = __DIR__ . '/log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}


function createRecord($conn, $email, $password)
{
    try {
        // Vérifier si l'utilisateur existe déjà
        $query = 'SELECT * FROM "users" WHERE "Email" = :email';
        $statement = $conn->prepare($query);
        $statement->bindParam(':email', $email);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        setJsonHeader();
        if ($result) {
            echo json_encode(array(
                "status" => "error",
                "message" => "Un utilisateur avec cette adresse mail existe déjà. S'il s'agit de vous, veuillez vous connecter."
            ));
        } else {
            // Hasher le mot de passe
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $id = generateGUID();
            $part1 = generateGUID();
            $part2 = generateGUID();
            $token = $part1 . '-' . $id . '-' . $part2;

            // Requête d'insertion
            $query0 = 'INSERT INTO "users" ("Id", "Email", "Password", "Token", "isVerified") 
                       VALUES (:id, :email, :password, :token, 0)';
            $statement0 = $conn->prepare($query0);
            $statement0->bindParam(':id', $id);
            $statement0->bindParam(':email', $email);
            $statement0->bindParam(':password', $hashed_password);
            $statement0->bindParam(':token', $token);
            $result0 = $statement0->execute();

            if ($result0) {
                // Récupérer l'utilisateur nouvellement créé
                $query01 = 'SELECT * FROM "users" WHERE "Email" = :email';
                $statement01 = $conn->prepare($query01);
                $statement01->bindParam(':email', $email);
                $statement01->execute();
                $result01 = $statement01->fetch(PDO::FETCH_ASSOC);

                if ($result01) {
                    $name = "Utilisateur";
                    $link = $_POST["Link"];
                    $link = $link . '/verify?token=' . $token . '&email=' . urlencode($email);

                    // Préparation des données pour la requête POST
                    $postData = array(
                        'Email' => $email,
                        'Name' => $name,
                        'Link' => $link,
                        'TemplateId' => 22
                    );

                    // Envoi de la requête POST vers SendMail.php
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "http://test.myreklam.fr/v2/SendMail.php");
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                    $response = curl_exec($ch);

                    if (curl_errno($ch)) {
                        // Gestion des erreurs cURL
                        echo json_encode(array("status" => "error", "message" => "Erreur lors de l'envoi de l'email : " . curl_error($ch)));
                    } else {
                        // Optionnel : Vérifiez la réponse du fichier SendMail.php
                        $responseData = json_decode($response, true);
                        if (isset($responseData['status']) && $responseData['status'] === "success") {
                            // Envoyer une notification de bienvenue
                            // $notificationManager = new NotificationBrevoAndWeb($conn);
                            // $notificationManager->sendNotificationRegistration($result01['Id']);
                            
                            echo json_encode(array(
                                "status" => "success",
                                "message" => "Utilisateur créé avec succès et email envoyé.",
                                "id" => $result01['Id']
                            ));
                        } else {
                            echo json_encode(array("status" => "error", "message" => "Échec de l'envoi de l'email", "response" => $responseData));
                        }
                    }

                    curl_close($ch);
                }
            } else {
                echo json_encode(array(
                    "status" => "failure",
                    "message" => "Échec de la création de l'utilisateur",
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

function forgotPassword($conn, $email)
{
    try {
        // Vérifier si l'utilisateur existe
        $query = 'SELECT * FROM "users" WHERE "Email" = :email';
        $statement = $conn->prepare($query);
        $statement->bindParam(':email', $email);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        setJsonHeader();

        if (!$result) {
            // Utilisateur non trouvé
            echo json_encode(array(
                "status" => "error",
                "message" => "Aucun utilisateur trouvé avec cette adresse email."
            ));
            return;
        }

        // Générer un token unique
        $id = generateGUID();
        $part1 = generateGUID();
        $part2 = generateGUID();
        $resetToken = $part1 . '-' . $id . '-' . $part2;

        // Mettre à jour le token de réinitialisation dans la base de données
        $queryUpdate = 'UPDATE "users" SET "Token" = :reset_token WHERE "Email" = :email';
        $statementUpdate = $conn->prepare($queryUpdate);
        $statementUpdate->bindParam(':reset_token', $resetToken);
        $statementUpdate->bindParam(':email', $email);
        $resultUpdate = $statementUpdate->execute();

        if ($resultUpdate) {
            // Préparer le lien de réinitialisation
            $link = $_POST["Link"] . '?token=' . $resetToken . '&email=' . urlencode($email);

            // Préparation des données pour l'email
            $postData = array(
                'Email' => $email,
                'Name' => $result['Email'], // Nom ou email à inclure dans l'email
                'Link' => $link, // Lien de réinitialisation
                'TemplateId' => '25' // ID du template d'email pour le mot de passe oublié
            );

            // Envoi de la requête POST vers SendMail.php
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://test.myreklam.fr/test/SendMail.php");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                // Erreur d'envoi de l'email
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Erreur lors de l'envoi de l'email : " . curl_error($ch)
                ));
            } else {
                // Vérifier la réponse du fichier SendMail.php
                $responseData = json_decode($response, true);
                if (isset($responseData['status']) && $responseData['status'] === "success") {
                    echo json_encode(array(
                        "status" => "success",
                        "message" => "Email de réinitialisation envoyé avec succès."
                    ));
                } else {
                    echo json_encode(array(
                        "status" => "error",
                        "message" => "Échec de l'envoi de l'email de réinitialisation."
                    ));
                }
            }

            curl_close($ch);
        } else {
            echo json_encode(array(
                "status" => "failure",
                "message" => "Échec de la mise à jour du token de réinitialisation.",
                "error" => $statementUpdate->errorInfo()
            ));
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

function resetPassword($conn, $email, $newPassword, $token)
{
    try {
        // Vérifier si l'utilisateur existe et si le token est valide
        $query = 'SELECT * FROM "users" WHERE "Email" = :email AND "Token" = :token';
        $statement = $conn->prepare($query);
        $statement->bindParam(':email', $email);
        $statement->bindParam(':token', $token);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        setJsonHeader();

        if (!$result) {
            // Utilisateur ou token invalide
            echo json_encode(array(
                "status" => "error",
                "message" => "Lien de réinitialisation invalide ou expiré."
            ));
            return;
        }

        // Hacher le nouveau mot de passe
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        // Mettre à jour le mot de passe et réinitialiser le token
        $queryUpdate = 'UPDATE "users" SET "Password" = :password, "Token" = NULL WHERE "Email" = :email';
        $statementUpdate = $conn->prepare($queryUpdate);
        $statementUpdate->bindParam(':password', $hashedPassword);
        $statementUpdate->bindParam(':email', $email);
        $resultUpdate = $statementUpdate->execute();

        if ($resultUpdate) {
            echo json_encode(array(
                "status" => "success",
                "message" => "Mot de passe mis à jour avec succès."
            ));
        } else {
            echo json_encode(array(
                "status" => "failure",
                "message" => "Échec de la mise à jour du mot de passe.",
                "error" => $statementUpdate->errorInfo()
            ));
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



// Fonction pour lire tout les  enregistrements
function readRecords($conn)
{
    $query = 'SELECT * FROM "TrainingType" ORDER BY "id" DESC';
    $statement = $conn->prepare($query);
    $statement->execute();
    $result = $statement->fetchAll(PDO::FETCH_ASSOC);
    setJsonHeader();
    if ($result) {
        echo json_encode(array("status" => "success", "trainingType" => $result));
    } else {
        http_response_code(500); // Erreur de serveur interne
        echo json_encode(array("status" => "failure", "message" => "Failed to get records"));
    }
}

function getTableColumns($conn, $tableName)
{
    try {
        // Requête pour obtenir les colonnes, leurs types et si elles sont nullable
        $query = "
                SELECT 
                    column_name, 
                    data_type, 
                    is_nullable
                FROM information_schema.columns
                WHERE table_name = :tableName
                ORDER BY ordinal_position;
            ";
        $statement = $conn->prepare($query);
        $statement->bindParam(':tableName', $tableName, PDO::PARAM_STR);
        $statement->execute();
        $columns = $statement->fetchAll(PDO::FETCH_ASSOC);

        setJsonHeader();
        if ($columns) {
            echo json_encode(array(
                "status" => "success",
                "columns" => $columns
            ));
        } else {
            echo json_encode(array(
                "status" => "error",
                "message" => "Aucune colonne trouvée pour la table spécifiée."
            ));
        }
    } catch (Exception $e) {
        setJsonHeader();
        echo json_encode(array(
            "status" => "error",
            "message" => "Erreur lors de la récupération des colonnes.",
            "details" => $e->getMessage()
        ));
    }
}

function verifyEmailAndToken($conn, $email, $token)
{
    try {
        // Préparer la requête pour vérifier la correspondance email et token
        $query = 'SELECT "Id" FROM "users" WHERE "Email" = :email AND "Token" = :token';
        $statement = $conn->prepare($query);
        $statement->bindParam(':email', $email);
        $statement->bindParam(':token', $token);

        // Exécuter la requête
        $statement->execute();

        // Récupérer le résultat
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        // Vérifier si un enregistrement est trouvé
        if ($result) {
            // Mettre à jour le token à une chaîne vide et isVerified à 1
            $updateQuery = 'UPDATE "users" SET "Token" = :token, "isVerified" = :isVerified WHERE "Id" = :id';
            $updateStatement = $conn->prepare($updateQuery);
            $emptyToken = '';
            $isVerified = 1;
            $updateStatement->bindParam(':token', $emptyToken);
            $updateStatement->bindParam(':isVerified', $isVerified);
            $updateStatement->bindParam(':id', $result['Id']);
            $updateStatement->execute();

            echo json_encode(array("status" => "success", "message" => "Bienvenue", "id" => $result['Id']));
        } else {
            // echo json_encode(array("status" => "error", "message" => "Aucun enregistrement trouvé avec cet email et token"));
            echo json_encode(array("status" => "error", "message" => "Le lien d'inscription est invalide ou expiré."));
        }
    } catch (PDOException $e) {
        // Gestion des erreurs
        echo json_encode(array("status" => "error", "message" => "Erreur lors de la vérification : " . $e->getMessage()));
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
function readRecord($conn, $email, $password)
{
    // Requête pour sélectionner l'utilisateur par email
    $query = 'SELECT * FROM "users" WHERE "Email" = :email';
    $statement = $conn->prepare($query);
    $statement->bindParam(':email', $email);
    $statement->execute();
    $result = $statement->fetch(PDO::FETCH_ASSOC);

    setJsonHeader();

    if ($result) {
        // Vérification du mot de passe
        if (password_verify($password, $result['Password'])) {
            // Connexion réussie
            echo json_encode(array(
                "status" => "success",
                "message" => "Connexion réussie",
                "isVerified" => $result['isVerified'],
                "id" => $result['Id']
            ));
        } else {
            // Mot de passe incorrect
            echo json_encode(array("status" => "error", "message" => "Mot de passe incorrect"));
        }
    } else {
        // Utilisateur non trouvé
        echo json_encode(array("status" => "error", "message" => "Aucun compte associé à cette adresse email. Veuillez en créer un ou nous contacter"));
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
function deleteRecord($conn, $email)
{
    try {
        // Préparation de la requête pour supprimer l'utilisateur par email
        $query = 'DELETE FROM "users" WHERE "Email" = :email';
        $statement = $conn->prepare($query);
        $statement->bindParam(':email', $email);

        // Exécution de la requête
        $statement->execute();

        setJsonHeader();

        if ($statement->rowCount() > 0) {
            // L'utilisateur a été supprimé avec succès
            echo json_encode(array("status" => "success", "message" => "Utilisateur supprimé avec succès"));
        } else {
            // Aucun utilisateur trouvé avec cet email
            echo json_encode(array("status" => "error", "message" => "Utilisateur non trouvé"));
        }
    } catch (Exception $e) {
        // Gestion des erreurs
        setJsonHeader();
        echo json_encode(array("status" => "error", "message" => "Erreur lors de la suppression : " . $e->getMessage()));
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


function readRecordsByUserId($conn, $id)
{
    // Requête SQL pour rechercher les enregistrements dans la table "users" en fonction de l'Id
    $query = 'SELECT * FROM "users" WHERE LOWER("Id") LIKE :id';
    $statement = $conn->prepare($query);

    // Ajouter les pourcentages (%) pour permettre une recherche partielle
    $statement->bindValue(':id', '%' . strtolower($id) . '%');

    try {
        // Exécuter la requête préparée
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        // Définir le type de réponse en JSON
        setJsonHeader();

        // Retourner les données ou un message d'échec
        if ($result) {
            echo json_encode(array("status" => "success", "profile" => $result));
        } else {
            http_response_code(404); // Pas trouvé
            echo json_encode(array("status" => "failure", "message" => "No records found."));
        }
    } catch (PDOException $e) {
        // Gérer les erreurs d'exécution
        http_response_code(500); // Erreur interne du serveur
        echo json_encode(array("status" => "failure", "message" => "Database error: " . $e->getMessage()));
    }
}


// Fonction pour supprimer un enregistrement
// function deleteRecord($conn, $id) {
//     $query = "DELETE FROM \"Profile\" WHERE uid = :id";
//     $statement = $conn->prepare($query);
//     $statement->bindParam(':id', $id);
//     $result = $statement->execute();


// setJsonHeader();
// // Retourner une réponse JSON en fonction du résultat de l'exécution
// if ($result) {
//     echo json_encode(array("status" => "success", "message" => "Record successfully delete"));
// } else {
//     http_response_code(500); // Erreur de serveur interne
//     echo json_encode(array("status" => "failure", "message" => "Failed to delete record"));
// }
// } getTableColumns($conn, $tableName)


// Vérifier la méthode et appeler la fonction appropriée updatePassword
if ($method == 'create') {
    createRecord($conn, $email, $password);
} elseif ($method == 'readAllByUserId') {
    readRecordsByUserId($conn, $id);
} elseif ($method == 'readAll') {
    readRecords($conn);
} elseif ($method == 'getTableColumns') {
    getTableColumns($conn, $tableName);
} elseif ($method == 'resetPassword') {
    forgotPassword($conn, $email);
} elseif ($method == 'updatePassword') {
    resetPassword($conn, $email, $newPassword, $token);
} elseif ($method == 'verify') {
    verifyEmailAndToken($conn, $email, $token);
} elseif ($method == 'read') {
    readRecord($conn, $email, $password);
} elseif ($method == 'readByName') {
    readRecordByName($conn, $category);
} elseif ($method == 'update') {
    updateRecord($conn, $id, $data);
} elseif ($method == 'delete') {
    // deleteRecord($conn, $id);
    deleteRecord($conn, $email);
} elseif ($method == 'deleteAccount') {
    $userId = $_POST['userId'];

    if (empty($userId)) {
        setJsonHeader();
        echo json_encode(["status" => "error", "message" => "Invalid ID"]);
        exit;
    }

    try {
        // Début de la transaction
        $conn->beginTransaction();

        // Vérifier si l'utilisateur existe
        $query = 'SELECT * FROM "userInfo" WHERE userid = :userId';
        $statement = $conn->prepare($query);
        $statement->bindValue(':userId', $userId, PDO::PARAM_STR);
        $statement->execute();
        $userInfo = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$userInfo) {
            setJsonHeader();
            echo json_encode(["status" => "error", "message" => "UserInfo not found"]);
            exit;
        }

        $query = 'SELECT * FROM "users" WHERE "Id" = :userId';
        $statement = $conn->prepare($query);
        $statement->bindValue(':userId', $userId);
        $statement->execute();
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            setJsonHeader();
            echo json_encode(["status" => "error", "message" => "User not found"]);
            exit;
        }

        // Supprimer les fichiers d'images
        function deleteFileIfExists($filePath)
        {
            if (!empty($filePath) && file_exists($filePath)) {
                if (!unlink($filePath)) {
                    // error_log("Impossible de supprimer le fichier : " . $filePath);
                }
            }
        }

        // Supprimer images de l'utilisateur
        $query = 'SELECT urlimg FROM "imageDiapo" WHERE userid = :userId';
        $statement = $conn->prepare($query);
        $statement->bindValue(':userId', $userId);
        $statement->execute();
        $images = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($images as $img) {
            deleteFileIfExists($img['urlimg']);
        }

        // Supprimer les annonces et leurs images
        $query = 'SELECT id FROM "ads" WHERE "userId" = :userId AND "deletedat" IS NULL';
        $statement = $conn->prepare($query);
        $statement->bindValue(':userId', $userId);
        $statement->execute();
        $ads = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($ads as $ad) {
            $query = 'SELECT urlimg FROM "imageAnnonce" WHERE annonceid = :annonceid';
            $statement = $conn->prepare($query);
            $statement->bindValue(':annonceid', $ad['id']);
            $statement->execute();
            $images = $statement->fetchAll(PDO::FETCH_ASSOC);

            foreach ($images as $img) {
                deleteFileIfExists($img['urlimg']);
            }

            // Supprimer les images de l'annonce
            $query = "DELETE FROM \"imageAnnonce\" WHERE annonceid = :annonceid";
            $statement = $conn->prepare($query);
            $statement->bindValue(':annonceid', $ad['id']);
            $statement->execute();
        }

        // Supprimer les annonces
        $query = "UPDATE \"ads\" SET \"deletedat\" = CURRENT_TIMESTAMP WHERE \"userId\" = :userId";
        $statement = $conn->prepare($query);
        $statement->bindValue(':userId', $userId);
        $statement->execute();

        // Supprimer les notifications
        $query = "DELETE FROM \"notifications\" WHERE user_id = :userId";
        $statement = $conn->prepare($query);
        $statement->bindValue(':userId', $userId);
        $statement->execute();

        // Supprimer les données dans d'autres tables
        $tablesToDeleteFrom = ['user_coins', 'history_coins', 'favoris', 'imageDiapo'];
        foreach ($tablesToDeleteFrom as $table) {
            $query = "DELETE FROM \"$table\" WHERE userid = :userId";
            $statement = $conn->prepare($query);
            $statement->bindValue(':userId', $userId);
            $statement->execute();
        }

        // Supprimer l'utilisateur
        $query = 'DELETE FROM "userInfo" WHERE userid = :userId';
        $statement = $conn->prepare($query);
        $statement->bindValue(':userId', $userId);
        $statement->execute();

        $query = 'DELETE FROM "users" WHERE "Id" = :userId';
        $statement = $conn->prepare($query);
        $statement->bindValue(':userId', $userId);
        $statement->execute();

        // Commit transaction
        $conn->commit();

        // Envoyer une notification de suppression de compte
        $notificationManager = new NotificationBrevoAndWeb($conn);
        if ($userInfo['profiletype'] == 'particulier') {
            $notificationManager->sendNotificationDeleteAccountParticulier($userId);
        } else if ($userInfo['profiletype'] == 'professionnel') {
            $notificationManager->sendNotificationDeleteAccountProfessionnel($userId);
        }

        setJsonHeader();
        echo json_encode(["status" => "success", "message" => "Account deleted successfully"]);
    } catch (Exception $e) {
        $conn->rollBack();
        setJsonHeader();
        echo json_encode(["status" => "error", "message" => "An error occurred: " . $e->getMessage()]);
    }
} elseif ($method == 'deleteFile') {

    $filePath = "./img/05afb5b1-0701-4764-9c60-dd6cd832d5ff.png";

    // Supprimer les fichiers liés aux images de l'utilisateur 
    function deleteFileIfExists($filePath)
    {
        if (file_exists($filePath)) {
            unlink($filePath);

            setJsonHeader();
            echo json_encode(["status" => "success", "message" => "IN Image delelted"]);
            exit;
        } else {
            setJsonHeader();
            echo json_encode(["status" => "error", "message" => "Image not found"]);
            exit;
        }
    }

    deleteFileIfExists($filePath);

    setJsonHeader();
    echo json_encode(["status" => "success", "message" => "Image deleted successfully"]);
    exit;
} elseif ($method == 'paginate') {
    readRecordsPaginate($conn, $page);
} elseif ($method == 'paginateSize') {
    readRecordsPaginateSize($conn);
} else if ($method == 'searchbar') {
    readRecordsSearch($conn, $searchbar);
}