<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Charger les variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

header('Content-Type: application/json');

try {
    $isAnnual = $_POST['isAnnual']; // Détermine si c'est un abonnement annuel ou mensuel
    $priceId = $isAnnual ? 'price_annual_id' : 'price_monthly_id';
    $price = $_POST['price'];

    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'mode' => 'subscription',
        'line_items' => [[
            'price' => $priceId,
            'quantity' => 1,
        ]],
        'success_url' => 'https://your-frontend-url.com/success?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'https://your-frontend-url.com/cancel',
    ]);

    echo json_encode(['url' => $session->url]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
