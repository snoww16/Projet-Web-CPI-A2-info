<?php
session_start();
ini_set('display_errors', 1); error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../src/Views');
$twig = new \Twig\Environment($loader, []);

// On nettoie l'URL (ex: "/login?error=1" devient "/login")
$request = $_SERVER['REQUEST_URI'];
$parsed_url = parse_url($request);
$path = $parsed_url['path'];

// Fausse variable pour simuler la connexion (à remplacer par $_SESSION plus tard)
// Mets 'true' pour voir le menu connecté, 'false' pour le menu déconnecté
$is_logged_in = false; 

// On prépare les données globales envoyées à TOUTES les vues Twig
$twig->addGlobal('is_logged_in', $is_logged_in);

// ---------------------------------------------------------
// LE ROUTEUR PRINCIPAL
// ---------------------------------------------------------
// ... début du fichier index.php ...

switch ($path) {
    
    // 1. NOUVELLE ROUTE : La Home Page !
    case '/':
        echo $twig->render('index.twig');
        break;

    // 2. La page de recherche (Celle qui marche déjà)
    case '/offres':
        echo $twig->render('offers/recherche.twig', [
            'offres' => [
                ['id' => 1, 'titre' => 'Stage Développeur Web', 'ville' => 'Toulouse', 'description' => 'Missions : intégration, API.'],
                ['id' => 2, 'titre' => 'Stage Data / BI', 'ville' => 'Lyon', 'description' => 'Missions : reporting.'],
            ]
        ]);
        break;

    // 3. La page de connexion
    case '/login':
        echo $twig->render('auth/login.twig');
        break;

    // 4. L'espace Administration (Ton ancien "page création entité")
    case '/admin':
        // Sécurité factice : redirige si pas connecté
        // if(!$is_logged_in) { header('Location: /login'); exit; }
        echo $twig->render('admin/dashboard.twig');
        break;

    // 5. La page de détails d'une offre pour postuler (ex: /offre/1)
    default:
        // Si l'URL commence par /offre/ (ex: /offre/1)
        if (preg_match('/^\/offre\/([0-9]+)$/', $path, $matches)) {
            $offre_id = $matches[1];
            // On simule qu'on a trouvé l'offre dans la BDD
            echo $twig->render('offers/postuler.twig', [
                'offre' => ['id' => $offre_id, 'titre' => 'Stage de folie !', 'entreprise_nom' => 'Tech Corp', 'description_detaillee' => 'Un stage incroyable où tu vas coder en PHP MVC.']
            ]);
            break;
        }

        // Si aucune route ne correspond
        http_response_code(404);
        echo "<h1>Erreur 404 - Page introuvable</h1>";
        break;
}
