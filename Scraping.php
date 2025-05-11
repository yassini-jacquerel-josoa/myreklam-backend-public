<?php

// Bloquer l'accès direct depuis un navigateur en renvoyant une erreur 404
if ($_SERVER['REQUEST_METHOD'] === 'GET' && basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    http_response_code(404);
    exit;
}

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
require_once __DIR__ . '/vendor/autoload.php';  


use Goutte\Client; 


$client = new Client(); 



$url = $_POST['url'] ?? null;

$data = [
    "title" => "",
    "price" => "",
    "description" => "",
    "images" => [],
];

if (!$url) {
    echo json_encode(["status" => "success", "data" => $data]);
    exit;
}

// Validation de l'URL
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(["status" => "success", "data" => $data]);
    exit;
}

// Vérification du schéma de l'URL (http ou https)
$parsed_url = parse_url($url);
if (!in_array($parsed_url['scheme'], ['http', 'https'])) {
    echo json_encode(["status" => "success", "data" => $data]);
    exit;
}

// Vérification de l'existence du domaine
if (!checkdnsrr($parsed_url['host'], 'A')) {
    echo json_encode(["status" => "success", "data" => $data]);
    exit;
}

$title = null;
$price = null;
$description = null;

// Définir les mots-clés pour chaque élément
$classe_price = [
    "price", "prix", "cost", "amount", "tarif", "total", "subtotal", "final-price",
    "discount", "offer-price", "promo-price", "current-price", "best-price", "regular-price",
    "sale-price", "product-price", "new-price", "old-price", "final-cost", "current-cost",
    "discounted-price", "price-tag", "price-value", "net-price", "gross-price", "unit-price",
    "selling-price", "retail-price", "deal-price", "checkout-price", "actual-price","thread-price"
];


$classe_title = [
    "product-title-word-break","productTitle", 
    "title", "product-title", "name", "headline", "product-name", "item-title",
    "product-header", "product-label", "listing-title", "offer-title", "main-title",
    "title-text", "article-title", "headline-text", "product-heading", "main-heading",
    "product-name-label", "title-info", "item-name", "page-title", "name-tag",
    "product-display-name", "name-header", "description-title", "main-product-title",
    "top-title", "short-title", "detailed-title", "title-main", "title-product", "product_full_title"
];


$classe_description = [
    "description", "desc", "details", "info", "summary", "product-info", "product-description",
    "listing-description", "offer-description", "item-desc", "desc-text", "details-text",
    "specifications", "product-specs", "product-details", "info-text", "features",
    "full-description", "long-description", "short-description", "overview", "main-description",
    "detail-info", "item-details", "about-product", "highlight", "product-highlights",
    "key-features", "more-info", "additional-info", "info-box", "product-summary"
];



try {
    $crawler = $client->request('GET', $url);

    $crawler->filter('*')->each(function ($node) use (&$price, &$title, &$description, $classe_price, $classe_title, $classe_description) {
        // Récupérer les classes de l'élément
        $classes = $node->attr('class');

        if ($classes) {
            // Convertir en tableau (si plusieurs classes sont présentes)
            $classArray = explode(' ', $classes);

            foreach ($classArray as $class) {
                // Vérifier si la classe correspond aux mots-clés de prix
                foreach ($classe_price as $keyword) {
                    if (!$price && stripos($class, $keyword) !== false) {
                        $raw_price = trim($node->text());
                        $clean_price = preg_replace('/[^0-9,.]/', '', $raw_price);
                        $clean_price = str_replace(',', '.', $clean_price);
                        if (preg_match('/\d/', $clean_price)) {
                            $price = floatval($clean_price);
                        }
                        break;
                    } 
                     
                }
                // Vérifier si la classe correspond aux mots-clés de titre
                foreach ($classe_title as $keyword) {
                    if (!$title && stripos($class, $keyword) !== false) {
                        $title = trim($node->text());
                        break;
                    }
                }
                // Vérifier si la classe correspond aux mots-clés de description
                foreach ($classe_description as $keyword) {
                    if (!$description && stripos($class, $keyword) !== false) {
                        $description = trim($node->text());
                        break;
                    }
                }
            }
        }
    });

    if (!$title && $crawler->filter('h1')->text()) {
        $title = $crawler->filter('h1')->text();
    }

    if (!$title) {
        $title = "Titre non disponible";
    }

    if (!$description) {
        $description = "Description non disponible";
    }
    if (!$price) {
        $price = 0;
    }

    // Récupérer toutes les images présentes sur la page
    $images = $crawler->filter('img')->each(function ($node) use ($parsed_url) {
        $src = $node->attr('src');
        if ($src) {
            if (!filter_var($src, FILTER_VALIDATE_URL)) {
                $src = $parsed_url['scheme'] . '://' . $parsed_url['host'] . '/' . ltrim($src, '/');
            }
            return $src;
        }
        return null;
    });

    // Nettoyer les images null
    $images = array_filter($images);

    $data = [
        "title" => $title,
        "price" => $price,
        "description" => $description,
        "images" => array_values($images)
    ];

    echo json_encode(["status" => "success", "data" => $data]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" =>$e->getMessage() ?? "An error occurred while processing the URL"]);
}