<?php

// Bloquer l'accès direct depuis un navigateur en renvoyant une erreur 404
if ($_SERVER['REQUEST_METHOD'] === 'GET' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    http_response_code(404);
    exit;
}

// Inclure la connexion à la base de données
require_once __DIR__ . '/db.php';

// Autoriser les requêtes depuis n'importe quel domaine
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Si la méthode n'est pas POST, retourner un message simple et quitter
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // 405 Method Not Allowed
    exit;
}

// Fonction pour définir le type de contenu JSON
function setJsonHeader() {
    header('Content-Type: application/json');
}

// Fonction pour retourner une réponse JSON standardisée
function jsonResponse($status, $message = '', $data = []) {
    setJsonHeader();
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Récupérer les données POST
$method = $_POST['Method'] ?? null;
$userId = $_POST['userId'] ?? null;

// Vérifier que les données nécessaires sont présentes
if (!$method || !$userId) {
    jsonResponse('failure', 'Missing required parameters');
}

// Traitement de la méthode 'start_conversation'
if ($method === 'get_info') {
    try {
        // Récupérer les informations de l'utilisateur
        $query = 'SELECT profiletype, pseudo FROM "userInfo" WHERE userid = :id';
        $statement = $conn->prepare($query);
        $statement->bindParam(':id', $userId);
        $statement->execute();
        $resultUserInfo = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$resultUserInfo) {
            jsonResponse('failure', 'Utilisateur introuvable');
        }
 
        // Récupérer les informations d'abonnement
        $query = 'SELECT typeabo, dateabo FROM "abonnement" WHERE userid = :id ORDER BY dateabo DESC';
        $statement = $conn->prepare($query);
        $statement->bindParam(':id', $userId);
        $statement->execute();
        $resultAbonnement = $statement->fetch(PDO::FETCH_ASSOC);

        if ($resultAbonnement) {
            $dateAbonnement = new DateTime($resultAbonnement['dateabo']);
            $dateAujourdhui = new DateTime();
        
            if ($resultAbonnement['typeabo'] == 'annuel') {
                // Vérifier si l'abonnement annuel est toujours valide
                $dateAbonnement->modify('+1 year');
                if ($dateAujourdhui > $dateAbonnement) {
                    $resultAbonnement = null; // L'abonnement a expiré
                }
            } else  {
                // if ($resultAbonnement['typeabo'] == 'mensuel')
                // Vérifier si l'abonnement mensuel est toujours valide
                $dateAbonnement->modify('+1 month');
                if ($dateAujourdhui > $dateAbonnement) {
                    $resultAbonnement = null; // L'abonnement a expiré
                }
            }
        }
        

        // Retourner les données
        jsonResponse('success', '', [
            'subscription' => $resultAbonnement ?: null,
            'user' => $resultUserInfo
        ]);

    } catch (\Throwable $th) {
        // Gestion des erreurs
        if ($conn->inTransaction()) {
            $conn->rollBack(); // Annulation de la transaction en cas d'erreur
        }

        jsonResponse('failure', $th->getMessage());
    }
}