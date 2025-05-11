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
    if (!isset($json_obj->priceId) || !isset($json_obj->price) || !isset($json_obj->successUrl) || !isset($json_obj->cancelUrl)) {
        throw new Exception('Invalid input data');
    }

    $price_id = $json_obj->priceId;
    $price =  $json_obj->price;
    $success_url = $json_obj->successUrl;
    $cancel_url = $json_obj->cancelUrl;
    $customer_email = $json_obj->customerEmail;

    // Vérifier ou créer un client Stripe
    $customer = \Stripe\Customer::create([
        'email' => $customer_email
    ]);

    // Créer une session Stripe
    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price' => $price_id,
            'quantity' => 1,
        ]],
        'mode' => 'subscription',
        'customer' => $customer->id, // Associer le client
        'success_url' => $success_url,
        'cancel_url' => $cancel_url,
    ]);

    // Répondre avec l'ID de la session
    echo json_encode(['id' => $checkout_session->id,
    'customerId' => $customer->id]);
} catch (Exception $e) {
    // Gestion des erreurs
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
