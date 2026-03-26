<?php
session_start();
ini_set('display_errors', 1); error_reporting(E_ALL);

// =========================================================
// 1. INITIALISATION ET CHARGEMENT
// =========================================================

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Models/Database.php';
require_once __DIR__ . '/../src/Models/Model.php';
require_once __DIR__ . '/../src/Models/OfferModel.php';
require_once __DIR__ . '/../src/Models/AuthModel.php';
require_once __DIR__ . '/../src/Models/AdminModel.php';
require_once __DIR__ . '/../src/Models/EntrepriseModel.php';

use App\Models\OfferModel;

$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../src/Views');
$twig = new \Twig\Environment($loader, []);

$twig->addGlobal('session', $_SESSION);

$request = $_SERVER['REQUEST_URI'];
$parsed_url = parse_url($request);
$path = $parsed_url['path'];

$liste_domaines = [
    'Informatique', 'Réseaux', 'Data', 'Sécurité', 'Design', 'Gestion', 
    'BTP', 'Santé', 'Finance', 'Commerce', 'Marketing', 'Ressources Humaines', 
    'Ingénierie', 'Mécanique', 'Autre'
];

$liste_niveaux = [
    'Collège (3ème)', 
    'Lycée / Bac', 
    'Bac+2 (BTS/DUT)', 
    'Bac+3 (Licence/Bachelor)', 
    'Bac+4/5 (Master/Ingénieur)'
];

// =========================================================
// 2. LE ROUTEUR (Aiguillage des pages)
// =========================================================

switch ($path) {

    case '/':
        $offerModel = new \App\Models\OfferModel();
        $dernieres_offres = $offerModel->searchOffers([], 3, 0);

        $mes_favoris = [];
        if (isset($_SESSION['id_user'])) {
            $mes_favoris = $offerModel->getWishlistIdsByUser($_SESSION['id_user']);
        }

        echo $twig->render('index.twig', [
            'dernieres_offres' => $dernieres_offres,
            'wishlist' => $mes_favoris
        ]);
        break;

    case '/a-propos':
        echo $twig->render('a-propos.twig');
        break;

    case '/offres':
        $offerModel = new OfferModel();
        
        $limit = 15;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * $limit;

        $filters = $_GET; 
        
        $vraies_offres = $offerModel->searchOffers($filters, $limit, $offset);
        $totalItems = $offerModel->countOffers($filters);
        $totalPages = ceil($totalItems / $limit);

        $mes_favoris = [];
        if (isset($_SESSION['id_user'])) {
            $mes_favoris = $offerModel->getWishlistIdsByUser($_SESSION['id_user']);
        }

        $queryString = '';
        foreach ($filters as $key => $value) {
            if ($key !== 'page' && !empty($value)) {
                $queryString .= '&' . urlencode($key) . '=' . urlencode($value);
            }
        }

        $statistiques = [];
        if (isset($_SESSION['id_user'])) { 
            $statistiques = $offerModel->getStatistiquesGlobales();
        }
        
        $_SESSION['last_search_url'] = $_SERVER['REQUEST_URI'];
        
        // UNIQUE APPEL À TWIG AVEC TOUTES LES DONNÉES (Stats incluses)
        echo $twig->render('offers/recherche-stage.twig', [
            'offres' => $vraies_offres,
            'queryParams' => $filters,
            'wishlist' => $mes_favoris,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'queryString' => $queryString,
            'domaines' => $liste_domaines,
            'niveaux' => $liste_niveaux,
            'stats' => $statistiques
        ]);
        break;

    case '/entreprises':
        $entrepriseModel = new \App\Models\EntrepriseModel();
        
        $limit = 15; 
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * $limit; 

        $filters = $_GET;
        
        $entreprises = $entrepriseModel->searchEntreprises($filters, $limit, $offset);
        $totalItems = $entrepriseModel->countEntreprises($filters);
        $totalPages = ceil($totalItems / $limit); 

        $queryString = '';
        foreach ($filters as $key => $value) {
            if ($key !== 'page' && !empty($value)) {
                $queryString .= '&' . urlencode($key) . '=' . urlencode($value);
            }
        }

        echo $twig->render('entreprises/recherche-entreprise.twig', [
            'entreprises' => $entreprises,
            'queryParams' => $filters,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'queryString' => $queryString
        ]);
        break;

    case '/wishlist/toggle':
        if (isset($_GET['id']) && isset($_SESSION['id_user']) && $_SESSION['role'] == 3) {
            $offerModel = new OfferModel();
            $offerModel->toggleWishlist($_SESSION['id_user'], $_GET['id']);
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']); 
        break;

    case '/profil':
        if (!isset($_SESSION['id_user'])) {
            header('Location: /login');
            exit;
        }

        $offerModel = new OfferModel();
        
        $candidatures_resume = [];
        $wishlist_resume = [];
        $total_candidatures = 0;
        $total_wishlist = 0;

        if ($_SESSION['role'] == 3) {
            $toutes_candidatures = $offerModel->getCandidaturesByUser($_SESSION['id_user']);
            $toutes_favorites = $offerModel->getWishlistOffersByUser($_SESSION['id_user']);
            
            $candidatures_resume = array_slice($toutes_candidatures, 0, 3);
            $wishlist_resume = array_slice($toutes_favorites, 0, 3);
            $total_candidatures = count($toutes_candidatures);
            $total_wishlist = count($toutes_favorites);
        }

        echo $twig->render('profil.twig', [
            'candidatures_resume' => $candidatures_resume,
            'wishlist_resume' => $wishlist_resume,
            'total_candidatures' => $total_candidatures,
            'total_wishlist' => $total_wishlist
        ]);
        break;
        
    case '/mes-favoris':
        if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 3) { 
            header('Location: /');
            exit;
        }

        $limit = 10;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * $limit;

        $offerModel = new OfferModel();
        
        $offres_favorites = $offerModel->getWishlistOffersByUser($_SESSION['id_user'], $limit, $offset);
        $totalItems = $offerModel->countWishlistOffersByUser($_SESSION['id_user']);
        $totalPages = ceil($totalItems / $limit);
        
        $mes_favoris_ids = $offerModel->getWishlistIdsByUser($_SESSION['id_user']);

        echo $twig->render('offers/wishlist.twig', [
            'offres' => $offres_favorites,
            'wishlist' => $mes_favoris_ids,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'queryString' => '' 
        ]);
        break;
        
    case '/mes-candidatures':
        if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 3) { 
            header('Location: /');
            exit;
        }
        
        $limit = 10; 
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * $limit;

        $offerModel = new OfferModel();
        
        $candidatures = $offerModel->getCandidaturesByUser($_SESSION['id_user'], $limit, $offset);
        $totalItems = $offerModel->countCandidaturesByUser($_SESSION['id_user']);
        $totalPages = ceil($totalItems / $limit);
        
        echo $twig->render('offers/mes-candidatures.twig', [
            'candidatures' => $candidatures,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'queryString' => ''
        ]);
        break;

    case '/login':
        if (isset($_SESSION['id_user'])) {
            header('Location: /');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $authModel = new \App\Models\AuthModel();
            $user = $authModel->login($email, $password);
            
            if ($user) {
                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['nom'] = $user['nom'];
                $_SESSION['prenom'] = $user['prenom'];
                $_SESSION['role'] = $user['id_role'];
                
                $redirectUrl = $_SESSION['redirect_to'] ?? '/';
                unset($_SESSION['redirect_to']);
                header('Location: ' . $redirectUrl);
                exit;
            } else {
                echo $twig->render('auth/login.twig', ['error' => 'Adresse e-mail ou mot de passe incorrect.']);
            }
        } else {
            if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], '/login') === false) {
                $_SESSION['redirect_to'] = $_SERVER['HTTP_REFERER'];
            }
            echo $twig->render('auth/login.twig');
        }
        break;

    case '/logout':
        session_destroy();
        header('Location: /');
        exit;
        break;

    case '/entreprise/evaluer':
        if (!isset($_SESSION['id_user']) || !in_array($_SESSION['role'], [1, 2])) {
            header('Location: /');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $entrepriseModel = new \App\Models\EntrepriseModel();
            $entrepriseModel->ajouterEvaluation($_POST['id_entreprise'], $_SESSION['id_user'], $_POST['note'], $_POST['commentaire']);
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
        break;

    case '/admin':
        if (!isset($_SESSION['id_user']) || $_SESSION['role'] == 3) {
            header('Location: /');
            exit;
        }
        echo $twig->render('admin/dashboard.twig');
        break;

    case '/mentions-legales':
        echo $twig->render('legal/mentions-legales.twig');
        break;

    case '/politique-confidentialite':
        echo $twig->render('legal/confidentialite.twig');
        break;

    case '/contact':
        echo $twig->render('legal/contact.twig');
        break;

    // --- 1. LISTE DES ENTITÉS ---
    case (preg_match('/^\/admin\/(etudiants|pilotes|entreprises|offres)$/', $path, $matches) ? true : false):
        if (!isset($_SESSION['id_user']) || $_SESSION['role'] == 3) {
            header('Location: /');
            exit;
        }
        
        $type_entite = $matches[1];

        if ($type_entite === 'pilotes' && $_SESSION['role'] == 2) {
            header('Location: /admin');
            exit;
        }

        $limit = 15;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * $limit;

        $adminModel = new \App\Models\AdminModel();
        $donnees_reelles = [];
        $totalItems = 0;

        if ($type_entite === 'etudiants') {
            $donnees_reelles = $adminModel->getUtilisateursByRole(3, $limit, $offset);
            $totalItems = $adminModel->countUtilisateursByRole(3);
        } elseif ($type_entite === 'pilotes') {
            $donnees_reelles = $adminModel->getUtilisateursByRole(2, $limit, $offset);
            $totalItems = $adminModel->countUtilisateursByRole(2);
        } elseif ($type_entite === 'entreprises') {
            $donnees_reelles = $adminModel->getEntreprises($limit, $offset);
            $totalItems = $adminModel->countEntreprises();
        } elseif ($type_entite === 'offres') {
            $donnees_reelles = $adminModel->getOffres($limit, $offset);
            $totalItems = $adminModel->countOffres();
        }

        $totalPages = ceil($totalItems / $limit);

        echo $twig->render('admin/liste.twig', [
            'type' => $type_entite,
            'items' => $donnees_reelles,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'domaines' => $liste_domaines,
            'niveaux' => $liste_niveaux,
            'queryString' => '' 
        ]);
        break;

    // --- 2. CRÉATION D'UNE ENTITÉ ---
    case (preg_match('/^\/admin\/(etudiants|pilotes|entreprises|offres)\/creer$/', $path, $matches) ? true : false):
        if (!isset($_SESSION['id_user']) || $_SESSION['role'] == 3) {
            header('Location: /');
            exit;
        }
        
        $type_entite = $matches[1];

        if ($type_entite === 'pilotes' && $_SESSION['role'] == 2) {
            die("Accès refusé : Vous n'avez pas les droits pour créer un compte Pilote.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminModel = new \App\Models\AdminModel();
            
            if ($type_entite === 'entreprises') {
                $adminModel->creerEntreprise($_POST['nom'], $_POST['description'], $_POST['email'], $_POST['telephone']);
            } elseif ($type_entite === 'offres') {
                $adminModel->creerOffre($_POST['titre'], $_POST['description'], $_POST['remuneration'], $_POST['date_debut'], $_POST['entreprise'], $_POST['ville'], $_POST['duree'], $_POST['type_contrat'], $_POST['domaine']);
            } elseif ($type_entite === 'etudiants') {
                $adminModel->creerUtilisateur($_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['password'], 3);
            } elseif ($type_entite === 'pilotes') {
                $adminModel->creerUtilisateur($_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['password'], 2);
            }

            header("Location: /admin/" . $type_entite);
            exit;
        }

        $liste_entreprises = [];
        if ($type_entite === 'offres') {
            $adminModel = new \App\Models\AdminModel();
            $liste_entreprises = $adminModel->getEntreprises(1000, 0); 
        }

        echo $twig->render('admin/creer.twig', [
            'type' => $type_entite,
            'entreprises' => $liste_entreprises,
            'domaines' => $liste_domaines,
            'niveaux' => $liste_niveaux
        ]);
        break;

    // --- 3. SUPPRESSION D'UNE ENTITÉ ---
    case (preg_match('/^\/admin\/(etudiants|pilotes|entreprises|offres)\/supprimer\/([0-9]+)$/', $path, $matches) ? true : false):
        if (!isset($_SESSION['id_user']) || $_SESSION['role'] == 3) {
            header('Location: /');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type_entite = $matches[1];
            $id = $matches[2];
            
            if ($type_entite === 'pilotes' && $_SESSION['role'] == 2) {
                die("Accès refusé : Vous ne pouvez pas supprimer un pilote.");
            }

            $adminModel = new \App\Models\AdminModel();
            if ($type_entite === 'etudiants' || $type_entite === 'pilotes') {
                $adminModel->supprimerUtilisateur($id);
            } elseif ($type_entite === 'entreprises') {
                $adminModel->supprimerEntreprise($id);
            } elseif ($type_entite === 'offres') {
                $adminModel->supprimerOffre($id);
            }

            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
        break;

    // --- 4. MODIFIER UNE ENTITÉ ---
    case (preg_match('/^\/admin\/(etudiants|pilotes|entreprises|offres)\/modifier\/([0-9]+)$/', $path, $matches) ? true : false):
        if (!isset($_SESSION['id_user']) || $_SESSION['role'] == 3) {
            header('Location: /');
            exit;
        }

        $type_entite = $matches[1];
        $id = $matches[2];
        $adminModel = new \App\Models\AdminModel();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($type_entite === 'offres') {
                $adminModel->modifierOffre($id, $_POST['titre'], $_POST['description'], $_POST['remuneration'], $_POST['date_debut'], $_POST['entreprise'], $_POST['ville'], $_POST['duree'], $_POST['type_contrat'], $_POST['domaine']);
                
                header("Location: /offre/" . $id);
                exit;
            }
            
            header("Location: /admin/" . $type_entite);
            exit;
        }

        $donnees_existantes = null;
        $liste_entreprises = [];
        
        if ($type_entite === 'offres') {
            $offerModel = new \App\Models\OfferModel();
            $donnees_existantes = $offerModel->getOfferById($id); 
            $liste_entreprises = $adminModel->getEntreprises(1000, 0); 
        }

        echo $twig->render('admin/creer.twig', [
            'type' => $type_entite,
            'donnees' => $donnees_existantes,
            'entreprises' => $liste_entreprises,
            'domaines' => $liste_domaines,
            'niveaux' => $liste_niveaux,
            'is_edit' => true 
        ]);
        break;
    
    // ---------------------------------------------------------
    // ROUTES DYNAMIQUES (Détails Offres et Postuler)
    // ---------------------------------------------------------

    default:
        if (preg_match('/^\/offre\/([0-9]+)$/', $path, $matches)) {
            $offre_id = $matches[1];
            $offerModel = new \App\Models\OfferModel();
            $vraie_offre = $offerModel->getOfferById($offre_id);
            
            if ($vraie_offre) {
                // CHARGEMENT DES ÉVALUATIONS AJOUTÉ ICI
                $entrepriseModel = new \App\Models\EntrepriseModel();
                $evaluations = $entrepriseModel->getEvaluationsByEntreprise($vraie_offre['id_entreprise']);

                echo $twig->render('offers/details.twig', [
                    'offre' => $vraie_offre,
                    'evaluations' => $evaluations
                ]);
            } else {
                http_response_code(404);
                echo "<h1>Erreur 404 - Offre introuvable.</h1>";
            }
            break;
        }

        if (preg_match('/^\/postuler\/([0-9]+)$/', $path, $matches)) {
            $offre_id = $matches[1];
            
            if (!isset($_SESSION['id_user'])) {
                $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
                header('Location: /login');
                exit;
            }

            if ($_SESSION['role'] != 3) {
                http_response_code(403);
                echo "<h1>Erreur 403 - Seuls les étudiants peuvent postuler aux offres.</h1>";
                break;
            }

            $offerModel = new \App\Models\OfferModel();
            $vraie_offre = $offerModel->getOfferById($offre_id);
            
            if (!$vraie_offre) {
                http_response_code(404);
                echo "<h1>Erreur 404 - Offre introuvable.</h1>";
                break;
            }

            $deja_postule = $offerModel->hasUserApplied($offre_id, $_SESSION['id_user']);
            $erreur = null;
            $succes = false;

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$deja_postule) {
                
                $uploadDir = __DIR__ . '/uploads/candidatures/';
                
                $cv_path = '';
                $lm_path = '';

                if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
                    if ($ext !== 'pdf') { $erreur = "Le CV doit être au format PDF."; }
                    else {
                        $cv_name = 'cv_' . $_SESSION['id_user'] . '_' . $offre_id . '_' . uniqid() . '.pdf';
                        move_uploaded_file($_FILES['cv']['tmp_name'], $uploadDir . $cv_name);
                        $cv_path = '/uploads/candidatures/' . $cv_name;
                    }
                } else {
                    $erreur = "Le CV est obligatoire.";
                }

                if (!$erreur && isset($_FILES['lm']) && $_FILES['lm']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['lm']['name'], PATHINFO_EXTENSION));
                    if ($ext !== 'pdf') { $erreur = "La lettre de motivation doit être au format PDF."; }
                    else {
                        $lm_name = 'lm_' . $_SESSION['id_user'] . '_' . $offre_id . '_' . uniqid() . '.pdf';
                        move_uploaded_file($_FILES['lm']['tmp_name'], $uploadDir . $lm_name);
                        $lm_path = '/uploads/candidatures/' . $lm_name;
                    }
                } else if (!$erreur) {
                    $erreur = "La lettre de motivation est obligatoire.";
                }

                $message = isset($_POST['message']) ? trim($_POST['message']) : '';

                if (!$erreur) {
                    $offerModel->applyForOffer($offre_id, $_SESSION['id_user'], $cv_path, $lm_path, $message);
                    $succes = true;
                    $deja_postule = true; 
                }
            }

            echo $twig->render('offers/postuler.twig', [
                'offre' => $vraie_offre,
                'deja_postule' => $deja_postule,
                'erreur' => $erreur,
                'succes' => $succes
            ]);
            break;
        }
        
        http_response_code(404);
        echo "<h1>Erreur 404 - Page introuvable</h1>";
        break;
}