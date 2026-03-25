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
require_once __DIR__ . '/../src/Models/AdminModel.php';

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
            header('Location: /home');
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
                
                $redirectUrl = $_SESSION['redirect_to'] ?? '/';
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

    // --- ESPACE ADMINISTRATION ---
    case '/admin':
        // SÉCURITÉ : On bloque ceux qui ne sont pas connectés ou qui sont Étudiants (Rôle 1)
        if (!isset($_SESSION['id_user']) || $_SESSION['role'] == 3) {
            header('Location: /');
            exit;
        }
        // On affiche le tableau de bord
        echo $twig->render('admin/dashboard.twig');
        break;

    // --- LISTE GÉNÉRIQUE DES ENTITÉS (Étudiants, Entreprises, etc.) ---
    case (preg_match('/^\/admin\/(etudiants|pilotes|entreprises|offres)$/', $path, $matches) ? true : false):
        if (!isset($_SESSION['id_user']) || $_SESSION['role'] == 3) {
            header('Location: /');
            exit;
        }
        
        $type_entite = $matches[1]; // Contient "etudiants", "pilotes", etc.

        // SÉCURITÉ MATRICE : Un pilote (2) ne peut pas voir la liste des pilotes
        if ($type_entite === 'pilotes' && $_SESSION['role'] == 2) {
            header('Location: /admin');
            exit;
        }

        // ICI : Plus tard, tu appelleras tes vrais modèles (ex: $adminModel->getAllEtudiants())
        // Pour l'instant, on simule de fausses données pour tester l'affichage
        $adminModel = new \App\Models\AdminModel();
        $donnees_reelles = [];

        // On va chercher les bonnes données en fonction de l'URL
        if ($type_entite === 'etudiants') {
            $donnees_reelles = $adminModel->getUtilisateursByRole(3); // 3 = Étudiants
        } elseif ($type_entite === 'pilotes') {
            $donnees_reelles = $adminModel->getUtilisateursByRole(2); // 2 = Pilotes
        } elseif ($type_entite === 'entreprises') {
            $donnees_reelles = $adminModel->getEntreprises();
        } elseif ($type_entite === 'offres') {
            $donnees_reelles = $adminModel->getOffres();
        }

        echo $twig->render('admin/liste.twig', [
            'type' => $type_entite,
            'items' => $donnees_reelles // On envoie les vraies données issues de la BDD !
        ]);
        break;

    // --- FORMULAIRE DE CRÉATION D'UNE ENTITÉ ---
    case (preg_match('/^\/admin\/(etudiants|pilotes|entreprises|offres)\/creer$/', $path, $matches) ? true : false):
        // 1. SÉCURITÉ DE BASE : Être connecté et ne pas être étudiant (1)
        if (!isset($_SESSION['id_user']) || $_SESSION['role'] == 3) {
            header('Location: /');
            exit;
        }
        
        $type_entite = $matches[1];

        // 2. SÉCURITÉ MATRICE : Bloquer les pilotes (2) qui essaient de créer un autre pilote (SFx13)
        if ($type_entite === 'pilotes' && $_SESSION['role'] == 2) {
            die("Accès refusé : Vous n'avez pas les droits pour créer un compte Pilote.");
        }

        // 3. TRAITEMENT DU FORMULAIRE (Quand on clique sur Envoyer)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            $adminModel = new \App\Models\AdminModel();
            
            if ($type_entite === 'entreprises') {
                $adminModel->creerEntreprise($_POST['nom'], $_POST['description'], $_POST['email'], $_POST['telephone']);
            } 
            elseif ($type_entite === 'offres') {
                $adminModel->creerOffre($_POST['titre'], $_POST['description'], $_POST['remuneration'], $_POST['date_debut']);
            }
            elseif ($type_entite === 'etudiants') {
                $adminModel->creerUtilisateur($_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['password'], 3); // 3 = Rôle Étudiant
            }
            elseif ($type_entite === 'pilotes') {
                $adminModel->creerUtilisateur($_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['password'], 2); // 2 = Rôle Pilote
            }

            // Redirection vers la liste après la création réussie dans la BDD
            header("Location: /admin/" . $type_entite);
            exit;
        }

        // 4. AFFICHAGE DU FORMULAIRE (Méthode GET)
        echo $twig->render('admin/creer.twig', [
            'type' => $type_entite
        ]);
        break;

        // --- SUPPRESSION D'UNE ENTITÉ ---
    case (preg_match('/^\/admin\/(etudiants|pilotes|entreprises|offres)\/supprimer\/([0-9]+)$/', $path, $matches) ? true : false):
        // SÉCURITÉ : Bloquer les étudiants (Rôle 3)
        if (!isset($_SESSION['id_user']) || $_SESSION['role'] == 3) {
            header('Location: /');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type_entite = $matches[1];
            $id = $matches[2];
            $adminModel = new \App\Models\AdminModel();

            // SÉCURITÉ : Bloquer les pilotes (2) qui essaient de supprimer un autre pilote
            if ($type_entite === 'pilotes' && $_SESSION['role'] == 2) {
                die("Accès refusé : Vous ne pouvez pas supprimer un pilote.");
            }

            // On exécute la suppression selon le type
            if ($type_entite === 'etudiants' || $type_entite === 'pilotes') {
                $adminModel->supprimerUtilisateur($id);
            } elseif ($type_entite === 'entreprises') {
                $adminModel->supprimerEntreprise($id);
            } elseif ($type_entite === 'offres') {
                $adminModel->supprimerOffre($id);
            }

            // REDIRECTION : On le renvoie exactement sur la page d'où il vient (HTTP_REFERER)
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
        break;

        // --- GESTION DE L'INSCRIPTION (S'inscrire) ---
    case '/register':
        // S'il est déjà connecté, on le renvoie à l'accueil
        if (isset($_SESSION['id_user'])) {
            header('Location: /');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            $authModel = new \App\Models\AuthModel();
            $success = $authModel->register($nom, $prenom, $email, $password);
            
            if ($success) {
                // Inscription réussie ! On le connecte directement pour une meilleure UX
                $user = $authModel->login($email, $password);
                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['nom'] = $user['nom'];
                $_SESSION['prenom'] = $user['prenom'];
                $_SESSION['role'] = $user['id_role'];
                
                header('Location: /'); // On l'envoie sur la page d'accueil
                exit;
            } else {
                // L'email existe déjà
                echo $twig->render('auth/register.twig', ['error' => 'Cette adresse e-mail est déjà utilisée.']);
            }
        } else {
            echo $twig->render('auth/register.twig');
        }
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