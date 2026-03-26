<?php
namespace App\Controllers;
use App\Models\EntrepriseModel;

class EntrepriseController extends Controller {
    
    public function index() {
        $entrepriseModel = new EntrepriseModel();
        $limit = 15;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $offset = ($page - 1) * $limit; 
        $filters = $_GET;
        
        $entreprises = $entrepriseModel->searchEntreprises($filters, $limit, $offset);
        $totalPages = ceil($entrepriseModel->countEntreprises($filters) / $limit); 

        $queryString = '';
        foreach ($filters as $key => $value) { if ($key !== 'page' && !empty($value)) { $queryString .= '&' . urlencode($key) . '=' . urlencode($value); } }

        $this->render('entreprises/recherche-entreprise.twig', ['entreprises' => $entreprises, 'queryParams' => $filters, 'currentPage' => $page, 'totalPages' => $totalPages, 'queryString' => $queryString]);
    }

    public function evaluer() {
        if (!isset($_SESSION['id_user']) || !in_array($_SESSION['role'], [1, 2])) { header('Location: /'); exit; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new EntrepriseModel())->ajouterEvaluation($_POST['id_entreprise'], $_SESSION['id_user'], $_POST['note'], $_POST['commentaire']);
            header('Location: ' . $_SERVER['HTTP_REFERER']); exit;
        }
    }
}