<?php
header("Content-Type: application/json");

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

// Configuration du dossier d'upload
$uploadDir = "./uploads/";

// Vérifier si le dossier existe, sinon le créer
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Vérifier la méthode HTTP
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "POST") {
    // ➤ UPLOAD DE FICHIER
    if (isset($_FILES["file"])) {
        $originalName = $_FILES["file"]["name"]; // Nom original
        $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION); // Extension
        $newFileName = uniqid("file_", true) . "." . $fileExtension; // Nouveau nom unique
        $filePath = $uploadDir . $newFileName;

        // Déplacer le fichier uploadé
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $filePath)) {
            echo json_encode([
                "success" => true,
                "message" => "Fichier uploadé avec succès.",
                "original_name" => $originalName,
                "new_name" => $newFileName, // Retourne uniquement le nom du fichier
                "url"=>"uploads/".$newFileName
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Échec de l'upload."]);
        }
    } 
    // ➤ TÉLÉCHARGEMENT DE FICHIER À PARTIR D'UNE URL
    elseif (isset($_POST["fileUrl"])) {
        $fileUrl = $_POST["fileUrl"];
        $originalName = basename($fileUrl);
        $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);
        $newFileName = uniqid("file_", true) . "." . $fileExtension;
        $filePath = $uploadDir . $newFileName;
        
        // Télécharger le fichier
        $fileContent = @file_get_contents($fileUrl);
        
        if ($fileContent !== false) {
            // Enregistrer le fichier
            if (file_put_contents($filePath, $fileContent)) {
                echo json_encode([
                    "success" => true,
                    "message" => "Fichier téléchargé avec succès depuis l'URL.",
                    "original_name" => $originalName,
                    "new_name" => $newFileName,
                    "url" => "uploads/".$newFileName
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "Échec de l'enregistrement du fichier."]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Impossible de télécharger le fichier depuis l'URL fournie."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Aucun fichier envoyé."]);
    }
} elseif ($method === "DELETE") {
    // ➤ SUPPRESSION DE FICHIER
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (isset($data["url"])) {
        $filePath = basename($data["url"]); // Récupérer uniquement le nom du fichier 

        // Vérifier si le fichier existe avant de le supprimer
        if (file_exists($filePath)) {
            unlink($filePath);
            echo json_encode(["success" => true, "message" => "Fichier supprimé avec succès."]);
        } else {
            echo json_encode(["success" => false, "message" => "Fichier introuvable."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Nom du fichier manquant."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Méthode non autorisée."]);
}
?>
