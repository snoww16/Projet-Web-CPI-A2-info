<?php
namespace App\Controllers;
use App\Models\AdminModel;
use App\Models\OfferModel;

class ProfileController extends Controller {
    
    public function myProfile() {
        if (!isset($_SESSION['id_user'])) { header('Location: /login'); exit; }
        $this->viewProfile($_SESSION['id_user']);
    }

    public function viewProfile($id) {
        if (!isset($_SESSION['id_user'])) { header('Location: /login'); exit; }
        
        $adminModel = new AdminModel();
        $user = $adminModel->getUtilisateurById($id);
        
        if (!$user) { http_response_code(404); echo "<h1>Utilisateur introuvable.</h1>"; exit; }

        if ($_SESSION['role'] == 3 && $_SESSION['id_user'] != $id) {
            die("Accès refusé. Vous ne pouvez voir que votre profil.");
        }

        $offerModel = new OfferModel();
        $candidatures = []; $wishlist_offres = []; $mes_etudiants = [];
        $is_owner = ($_SESSION['id_user'] == $id);

        if ($user['id_role'] == 3) {
            $can_view_applications = ($_SESSION['role'] != 1); 
            if ($can_view_applications) { $candidatures = $offerModel->getCandidaturesByUser($id, 5, 0); }
            if ($is_owner) { $wishlist_offres = $offerModel->getWishlistOffersByUser($id, 5, 0); }
        } elseif ($user['id_role'] == 2) {
            $mes_etudiants = $adminModel->getStudentsByPilote($id);
        }

        // CORRECTION : On récupère les IDs pour le coeur rouge du _card.twig
        $wishlist_ids = isset($_SESSION['id_user']) ? $offerModel->getWishlistIdsByUser($_SESSION['id_user']) : [];

        $this->render('profile/utilisateur.twig', [
            'user_profile' => $user,
            'is_owner' => $is_owner,
            'candidatures' => $candidatures,
            'wishlist_offres' => $wishlist_offres, // <-- Les offres complètes
            'wishlist' => $wishlist_ids,           // <-- Les IDs pour le coeur
            'mes_etudiants' => $mes_etudiants,
            'can_view_applications' => $can_view_applications ?? false
        ]);
    }

    public function updateAvatar() {
        if (!isset($_SESSION['id_user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /profil'); exit; }
        
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $uploadDir = __DIR__ . '/../../public/uploads/avatars/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $filename = 'avatar_' . $_SESSION['id_user'] . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $filename);
                
                $path = '/uploads/avatars/' . $filename;
                (new AdminModel())->updateAvatar($_SESSION['id_user'], $path);
                $_SESSION['photo_path'] = $path;
            }
        }
        header('Location: /profil'); exit;
    }
}