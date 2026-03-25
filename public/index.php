<?php
session_start();
ini_set('display_errors', 1); error_reporting(E_ALL);

// 1. On charge Twig
require_once __DIR__ . '/../vendor/autoload.php';

// 2. On charge manuellement tes fichiers de base de données (pour éviter les erreurs d'autoloading)
require_once __DIR__ . '/../src/Models/Database.php';
require_once __DIR__ . '/../src/Models/Model.php';
require_once __DIR__ . '/../src/Models/OfferModel.php';
require_once __DIR__ . '/../src/Models/AuthModel.php';

// On utilise le modèle
use App\Models\OfferModel;

// 3. Configuration de Twig
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../src/Views');
$twig = new \Twig\Environment($loader, []);

// 4. On récupère l'URL
$request = $_SERVER['REQUEST_URI'];
$parsed_url = parse_url($request);
$path = $parsed_url['path'];

$twig->addGlobal('session', $_SESSION);

// ---------------------------------------------------------
// LE ROUTEUR
// ---------------------------------------------------------
switch ($path) {
    
    case '/':
        echo $twig->render('index.twig');
        break;

    case '/offres':
        $offerModel = new OfferModel();
        $filters = $_GET; 
        $vraies_offres = $offerModel->searchOffers($filters);
        
        $mes_favoris = $offerModel->getWishlistIdsByUser($_SESSION['id_user']);
        
        echo $twig->render('offers/recherche.twig', [
            'offres' => $vraies_offres,
            'queryParams' => $filters,
            'wishlist' => $mes_favoris // On l'envoie à Twig
        ]);
        break;
    case '/wishlist/toggle':
        if (isset($_GET['id'])) {
            $offerModel = new OfferModel();
            // On utilise notre faux utilisateur connecté pour liker l'offre
            $offerModel->toggleWishlist($_SESSION['id_user'], $_GET['id']);
        }
        // Redirection invisible vers la page précédente
        header('Location: ' . $_SERVER['HTTP_REFERER']); 
        break;

    case '/login':
        // Si l'utilisateur a cliqué sur le bouton "Se connecter"
        if (isset($_SESSION['id_user'])) {
            header('Location: /offres');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // On vérifie que les champs ont bien été envoyés pour éviter le crash
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $authModel = new \App\Models\AuthModel();
            $user = $authModel->login($email, $password);
            
            if ($user) {
                // CONNEXION RÉUSSIE
                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['nom'] = $user['nom'];
                $_SESSION['prenom'] = $user['prenom'];
                $_SESSION['role'] = $user['id_role'];
                
                $redirectUrl = $_SESSION['redirect_to'] ?? '/offres';
                // On efface la mémoire pour la prochaine fois
                unset($_SESSION['redirect_to']);
                header('Location: ' . $redirectUrl);
                exit;
            } else {
                // ÉCHEC : On renvoie la page de login AVEC le message d'erreur
                echo $twig->render('auth/login.twig', ['error' => 'Adresse e-mail ou mot de passe incorrect.']);
            }
        } else {
            // S'il arrive juste sur la page sans avoir cliqué
            echo $twig->render('auth/login.twig');
        }
        break;
    // --- GESTION DU LOGOUT ---
    case '/logout':
        session_destroy();
        header('Location: /'); // NOUVEAU : Renvoie vers la Home Page !
        exit;
        break;

    case '/admin':
        echo $twig->render('admin/dashboard.twig');
        break;

    default:
        // 1. PAGE DETAILS (Lecture seule)
        if (preg_match('/^\/offre\/([0-9]+)$/', $path, $matches)) {
            $offre_id = $matches[1];
            $offerModel = new \App\Models\OfferModel();
            $vraie_offre = $offerModel->getOfferById($offre_id);
            
            if ($vraie_offre) {
                echo $twig->render('offers/details.twig', ['offre' => $vraie_offre]);
            } else {
                http_response_code(404);
                echo "<h1>Erreur 404 - Offre introuvable.</h1>";
            }
            break;
        }

        // 2. PAGE POSTULER (Le formulaire de candidature)
        if (preg_match('/^\/postuler\/([0-9]+)$/', $path, $matches)) {
            $offre_id = $matches[1];
            
            // Sécurité : Si l'étudiant n'est pas connecté, on le renvoie vers le login !
            if (!isset($_SESSION['id_user'])) {
                // NOUVEAU : On sauvegarde l'URL exacte où il voulait aller
                $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
                header('Location: /login');
                exit;
            }

            $offerModel = new \App\Models\OfferModel();
            $vraie_offre = $offerModel->getOfferById($offre_id);
            
            if ($vraie_offre) {
                echo $twig->render('offers/postuler.twig', ['offre' => $vraie_offre]);
            } else {
                http_response_code(404);
                echo "<h1>Erreur 404 - Offre introuvable.</h1>";
            }
            break;
        }
}