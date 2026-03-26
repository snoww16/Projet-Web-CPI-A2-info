<?php
namespace App\Controllers;
use App\Models\OfferModel;
use App\Models\EntrepriseModel;

class OfferController extends Controller {

    public function index() {
        $offerModel = new OfferModel();
        $limit = 15;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $offset = ($page - 1) * $limit;
        $filters = $_GET; 
        
        $vraies_offres = $offerModel->searchOffers($filters, $limit, $offset);
        $totalItems = $offerModel->countOffers($filters);
        $totalPages = ceil($totalItems / $limit);
        
        $mes_favoris = isset($_SESSION['id_user']) ? $offerModel->getWishlistIdsByUser($_SESSION['id_user']) : [];
        $statistiques = isset($_SESSION['id_user']) ? $offerModel->getStatistiquesGlobales() : [];
        $villes = $offerModel->getVilles();
        
        $queryString = '';
        foreach ($filters as $key => $value) { if ($key !== 'page' && !empty($value)) { $queryString .= '&' . urlencode($key) . '=' . urlencode($value); } }
        
        $_SESSION['last_search_url'] = $_SERVER['REQUEST_URI'];
        
        $this->render('offers/recherche-stage.twig', [
            'offres' => $vraies_offres, 'queryParams' => $filters, 'wishlist' => $mes_favoris,
            'currentPage' => $page, 'totalPages' => $totalPages, 'queryString' => $queryString,
            'domaines' => $this->domaines, 'niveaux' => $this->niveaux, 'stats' => $statistiques,
            'villes' => $villes
        ]);
    }

    public function details($id) {
        $offerModel = new OfferModel();
        $vraie_offre = $offerModel->getOfferById($id);
        if ($vraie_offre) {
            $entrepriseModel = new EntrepriseModel();
            $evaluations = $entrepriseModel->getEvaluationsByEntreprise($vraie_offre['id_entreprise']);
            $this->render('offers/details.twig', ['offre' => $vraie_offre, 'evaluations' => $evaluations]);
        } else {
            http_response_code(404); echo "<h1>Erreur 404 - Offre introuvable.</h1>";
        }
    }

    public function postuler($id) {
        if (!isset($_SESSION['id_user'])) { $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI']; header('Location: /login'); exit; }
        if ($_SESSION['role'] != 3) { http_response_code(403); echo "<h1>Erreur 403 - Réservé aux étudiants.</h1>"; exit; }

        $offerModel = new OfferModel();
        $vraie_offre = $offerModel->getOfferById($id);
        if (!$vraie_offre) { http_response_code(404); echo "<h1>Erreur 404 - Offre introuvable.</h1>"; exit; }

        $deja_postule = $offerModel->hasUserApplied($id, $_SESSION['id_user']);
        $erreur = null; $succes = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$deja_postule) {
            $uploadDir = __DIR__ . '/../../public/uploads/candidatures/';
            $cv_path = ''; $lm_path = '';

            if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
                if (strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION)) !== 'pdf') { $erreur = "Le CV doit être au format PDF."; }
                else {
                    $cv_name = 'cv_' . $_SESSION['id_user'] . '_' . $id . '_' . uniqid() . '.pdf';
                    move_uploaded_file($_FILES['cv']['tmp_name'], $uploadDir . $cv_name);
                    $cv_path = '/uploads/candidatures/' . $cv_name;
                }
            } else { $erreur = "Le CV est obligatoire."; }

            if (!$erreur && isset($_FILES['lm']) && $_FILES['lm']['error'] === UPLOAD_ERR_OK) {
                if (strtolower(pathinfo($_FILES['lm']['name'], PATHINFO_EXTENSION)) !== 'pdf') { $erreur = "La lettre de motivation doit être au format PDF."; }
                else {
                    $lm_name = 'lm_' . $_SESSION['id_user'] . '_' . $id . '_' . uniqid() . '.pdf';
                    move_uploaded_file($_FILES['lm']['tmp_name'], $uploadDir . $lm_name);
                    $lm_path = '/uploads/candidatures/' . $lm_name;
                }
            } else if (!$erreur) { $erreur = "La lettre de motivation est obligatoire."; }

            if (!$erreur) {
                $offerModel->applyForOffer($id, $_SESSION['id_user'], $cv_path, $lm_path, $_POST['message'] ?? '');
                $succes = true; $deja_postule = true; 
            }
        }
        $this->render('offers/postuler.twig', ['offre' => $vraie_offre, 'deja_postule' => $deja_postule, 'erreur' => $erreur, 'succes' => $succes]);
    }

    public function mesCandidatures() {
        if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 3) { header('Location: /'); exit; }
        $offerModel = new OfferModel();
        $limit = 10; $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; $offset = ($page - 1) * $limit;
        $candidatures = $offerModel->getCandidaturesByUser($_SESSION['id_user'], $limit, $offset);
        $this->render('offers/mes-candidatures.twig', ['candidatures' => $candidatures, 'currentPage' => $page, 'totalPages' => ceil($offerModel->countCandidaturesByUser($_SESSION['id_user']) / $limit), 'queryString' => '']);
    }

    public function mesFavoris() {
        if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 3) { header('Location: /'); exit; }
        $offerModel = new OfferModel();
        $limit = 10; $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; $offset = ($page - 1) * $limit;
        $offres = $offerModel->getWishlistOffersByUser($_SESSION['id_user'], $limit, $offset);
        $this->render('offers/wishlist.twig', ['offres' => $offres, 'wishlist' => $offerModel->getWishlistIdsByUser($_SESSION['id_user']), 'currentPage' => $page, 'totalPages' => ceil($offerModel->countWishlistOffersByUser($_SESSION['id_user']) / $limit), 'queryString' => '']);
    }

    public function toggleWishlist() {
        if (isset($_GET['id']) && isset($_SESSION['id_user']) && $_SESSION['role'] == 3) {
            (new OfferModel())->toggleWishlist($_SESSION['id_user'], $_GET['id']);
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']); exit;
    }
}