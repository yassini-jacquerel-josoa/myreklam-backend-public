<?php
require_once(__DIR__ . '/vendor/autoload.php');

use Google\Client;
use Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$client = new Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']); // L'URL où Google redirigera après connexion
$client->addScope(['email', 'profile']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idToken = $_POST['idToken'];

    try {
        $payload = $client->verifyIdToken($idToken);
        if ($payload) {
            // Informations utilisateur
            $userEmail = $payload['email'];
            $userName = $payload['name'];

            // Logique de votre application (inscription ou connexion)
            $response = [
                'status' => 'success',
                'message' => 'Authentification réussie',
                'user' => [
                    'email' => $userEmail,
                    'name' => $userName
                ]
            ];
        } else {
            throw new Exception('Jeton invalide');
        }
    } catch (Exception $e) {
        $response = [
            'status' => 'error',
            'message' => 'Erreur lors de la vérification du jeton : ' . $e->getMessage()
        ];
    }

    echo json_encode($response);
}
?>
