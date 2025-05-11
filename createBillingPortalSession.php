<?php

// Bloquer l'accès direct depuis un navigateur en renvoyant une erreur 404
if ($_SERVER['REQUEST_METHOD'] === 'GET' && basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    http_response_code(404);
    exit;
}

// Inclure la connexion à la base de données
include_once("./db.php");

// En-têtes CORS
header("Access-Control-Allow-Origin: *"); // Autoriser tous les domaines (peut être restreint pour plus de sécurité)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Gérer les requêtes OPTIONS (pré-vérification CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // Pas de contenu pour OPTIONS
    exit;
}

// Stripe SDK
require 'vendor/autoload.php';
\Stripe\Stripe::setApiKey('sk_test_51KEdn8LjQlGQsbAnaApChiGcpV1NcpfX7nJSFzZGBnnlRPqb5P5FlNVCtLhK0uqO26wddWEVNI9KQWpHtOlX9P0g009pMas4Tn');

header('Content-Type: application/json');

try {
    // Récupérer le JSON de la requête
    $json_str = file_get_contents('php://input');
    $json_obj = json_decode($json_str);

    // Vérifier que les données nécessaires sont présentes
    if (!isset($json_obj->method) || $json_obj->method !== 'createBillingPortalSession') {
        throw new Exception('Invalid or missing method.');
    }

    if (!isset($json_obj->customerId) || !isset($json_obj->returnUrl)) {
        throw new Exception('Invalid input data: customerId and returnUrl are required.');
    }

    $customerId = $json_obj->customerId;
    $returnUrl = $json_obj->returnUrl;

    // Créer une session de portail de facturation
    $session = \Stripe\BillingPortal\Session::create([
        'customer' => $customerId,
        'return_url' => $returnUrl,
    ]);

    // Répondre avec l'URL de la session créée
    echo json_encode([
        "status" => "success",
        "url" => $session->url,
    ]);

} catch (Exception $e) {
    // Gestion des erreurs
    http_response_code(500);
    echo json_encode([
        "status" => "failure",
        "error" => $e->getMessage(),
    ]);
}
