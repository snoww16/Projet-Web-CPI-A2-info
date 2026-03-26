<?php
namespace App\Controllers;
use App\Models\AdminModel;
use App\Models\OfferModel;

class AdminController extends Controller {
    
    private function checkAccess($allowPilote = true) {
        if (!isset($_SESSION['id_user']) || $_SESSION['role'] == 3) { header('Location: /'); exit; }
        if (!$allowPilote && $_SESSION['role'] == 2) { die("Accès refusé."); }
    }

    public function dashboard() {
        $this->checkAccess();
        $this->render('admin/dashboard.twig');
    }

    public function updateStatutEtudiant() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new AdminModel())->updateStatutRecherche($_POST['id_user'], $_POST['statut']);
            header('Location: ' . $_SERVER['HTTP_REFERER']); exit;
        }
    }

    public function liste($type) {
        $this->checkAccess($type !== 'pilotes');
        
        $limit = 15; $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; $offset = ($page - 1) * $limit;
        $filters = $_GET; $queryString = '';
        foreach ($filters as $key => $value) { if ($key !== 'page' && !empty($value)) { $queryString .= '&' . urlencode($key) . '=' . urlencode($value); } }

        $adminModel = new AdminModel();
        $donnees = []; $totalItems = 0; $villes = [];

        if ($type === 'etudiants') { $donnees = $adminModel->getUtilisateursByRoleFiltered(3, $filters, $limit, $offset); $totalItems = $adminModel->countUtilisateursByRoleFiltered(3, $filters); } 
        elseif ($type === 'pilotes') { $donnees = $adminModel->getUtilisateursByRoleFiltered(2, $filters, $limit, $offset); $totalItems = $adminModel->countUtilisateursByRoleFiltered(2, $filters); } 
        elseif ($type === 'entreprises') { $donnees = $adminModel->getEntreprises($limit, $offset); $totalItems = $adminModel->countEntreprises(); } 
        elseif ($type === 'offres') { 
            $offerModel = new \App\Models\OfferModel(); 
            $donnees = $offerModel->searchOffers($filters, $limit, $offset); 
            $totalItems = $offerModel->countOffers($filters); 
            $villes = $offerModel->getVilles();
        }

        $this->render('admin/liste.twig', ['type' => $type, 'items' => $donnees, 'queryParams' => $filters, 'currentPage' => $page, 'totalPages' => ceil($totalItems / $limit), 'domaines' => $this->domaines, 'niveaux' => $this->niveaux, 'villes' => $villes, 'queryString' => $queryString]);
    }

    public function creer($type) {
        $this->checkAccess($type !== 'pilotes');
        $adminModel = new AdminModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($type === 'entreprises') { $adminModel->creerEntreprise($_POST['nom'], $_POST['description'], $_POST['email'], $_POST['telephone']); } 
            elseif ($type === 'offres') { $adminModel->creerOffre($_POST['titre'], $_POST['description'], $_POST['remuneration'], $_POST['date_debut'], $_POST['entreprise'], $_POST['ville'], $_POST['duree'], $_POST['type_contrat'], $_POST['domaine'], $_POST['niveau_requis']); } 
            elseif ($type === 'etudiants') { $adminModel->creerUtilisateur($_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['password'], 3); } 
            elseif ($type === 'pilotes') { $adminModel->creerUtilisateur($_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['password'], 2); }
            header("Location: /admin/" . $type); exit;
        }

        $entreprises = ($type === 'offres') ? $adminModel->getEntreprises(1000, 0) : [];
        $this->render('admin/creer.twig', ['type' => $type, 'entreprises' => $entreprises, 'domaines' => $this->domaines, 'niveaux' => $this->niveaux]);
    }

    public function modifier($type, $id) {
        $this->checkAccess($type !== 'pilotes');
        $adminModel = new AdminModel();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($type === 'offres') {
                $adminModel->modifierOffre($id, $_POST['titre'], $_POST['description'], $_POST['remuneration'], $_POST['date_debut'], $_POST['entreprise'], $_POST['ville'], $_POST['duree'], $_POST['type_contrat'], $_POST['domaine'], $_POST['niveau_requis']);
                header("Location: /offre/" . $id); exit;
            }
            header("Location: /admin/" . $type); exit;
        }

        $donnees = ($type === 'offres') ? (new OfferModel())->getOfferById($id) : null;
        $entreprises = ($type === 'offres') ? $adminModel->getEntreprises(1000, 0) : [];
        $this->render('admin/creer.twig', ['type' => $type, 'donnees' => $donnees, 'entreprises' => $entreprises, 'domaines' => $this->domaines, 'niveaux' => $this->niveaux, 'is_edit' => true]);
    }

    public function supprimer($type, $id) {
        $this->checkAccess($type !== 'pilotes');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminModel = new AdminModel();
            if ($type === 'etudiants' || $type === 'pilotes') { $adminModel->supprimerUtilisateur($id); } 
            elseif ($type === 'entreprises') { $adminModel->supprimerEntreprise($id); } 
            elseif ($type === 'offres') { $adminModel->supprimerOffre($id); }
            header('Location: ' . $_SERVER['HTTP_REFERER']); exit;
        }
    }
}