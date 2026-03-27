<?php
namespace App\Controllers;
use App\Models\OfferModel;
use App\Models\AdminModel;

class HomeController extends Controller {
    public function index() {
        $offerModel = new OfferModel();
        $mes_favoris = isset($_SESSION['id_user']) ? $offerModel->getWishlistIdsByUser($_SESSION['id_user']) : [];
        $this->render('index.twig', [
            'dernieres_offres' => $offerModel->searchOffers([], 3, 0),
            'wishlist' => $mes_favoris
        ]);
    }

    public function aPropos() { $this->render('a-propos.twig'); }
    public function mentionsLegales() { $this->render('legal/mentions-legales.twig'); }
    public function confidentialite() { $this->render('legal/confidentialite.twig'); }
    public function contact() { $this->render('legal/contact.twig'); }
    
    public function profil($id = null) {
        // Sécurité de base
        if (!isset($_SESSION['id_user'])) { header('Location: /login'); exit; }

        $adminModel = new \App\Models\AdminModel();
        $offerModel = new \App\Models\OfferModel();

        // --- LOGIQUE DE CIBLE ---
        // Si on a un ID ET qu'on est Admin(1) ou Pilote(2), on regarde la cible.
        // Sinon, on se regarde soi-même.
        $target_id = ($id && in_array($_SESSION['role'], [1, 2])) ? $id : $_SESSION['id_user'];
        
        $user_profile = $adminModel->getUtilisateurById($target_id);
        
        // Si l'utilisateur n'existe pas
        if (!$user_profile) {
            http_response_code(404);
            die("<h2 style='color:white; text-align:center; margin-top:50px;'>Utilisateur introuvable.</h2>");
        }

        // Est-ce qu'on est sur notre propre profil ?
        $is_owner = ($target_id == $_SESSION['id_user']);
        
        // --- PRÉPARATION DES DONNÉES ---
        $data = [
            'user_profile' => $user_profile,
            'is_owner' => $is_owner,
            // On peut voir les candidatures si c'est un étudiant (Rôle 3) ET (soit c'est nous, soit on est admin/pilote)
            'can_view_applications' => ($user_profile['id_role'] == 3 && ($is_owner || in_array($_SESSION['role'], [1, 2])))
        ];

        // --- CHARGEMENT SPÉCIFIQUE ---
        if ($user_profile['id_role'] == 3) {
            // C'est un Étudiant : on charge ses candidatures
            $data['candidatures'] = $offerModel->getCandidaturesByUser($target_id);
            // On ne voit la wishlist que si c'est notre propre profil
            if ($is_owner) { 
                $data['wishlist_offres'] = $offerModel->getWishlistOffersByUser($target_id); 
            }
        } elseif ($user_profile['id_role'] == 2) {
            // C'est un Pilote : on charge ses étudiants assignés (limite 50)
            $data['mes_etudiants'] = $adminModel->getUtilisateursByRoleFiltered(3, ['id_pilote' => $target_id], 50, 0);
        }

        // --- RENDU ---
        // Pense bien à vérifier que ton fichier est bien dans src/Views/profile/utilisateur.twig !
        $this->render('profile/utilisateur.twig', $data);
    }
    public function updateAvatar() {
        // Sécurité : il faut être connecté
        if (!isset($_SESSION['id_user'])) { header('Location: /login'); exit; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            
            $adminModel = new \App\Models\AdminModel();
            $user = $adminModel->getUtilisateurById($_SESSION['id_user']);
            
            // --- 1. SUPPRESSION DE L'ANCIENNE PHOTO ---
            if (!empty($user['photo_path'])) {
                // On récupère le chemin complet sur le serveur (ex: /home/cesitox/www/uploads/avatars/photo.jpg)
                $oldFilePath = $_SERVER['DOCUMENT_ROOT'] . $user['photo_path'];
                
                // On vérifie que le fichier existe ET qu'il se trouve bien dans le dossier uploads (sécurité)
                if (file_exists($oldFilePath) && strpos($user['photo_path'], '/uploads/') !== false) {
                    unlink($oldFilePath); // 🗑️ Boum, supprimé !
                }
            }

            // --- 2. ENREGISTREMENT DE LA NOUVELLE PHOTO ---
            // On s'assure que le dossier existe
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // On vérifie l'extension (pour éviter qu'un pirate envoie un fichier .php)
            $fileExtension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($fileExtension, $allowedExtensions)) {
                // On crée un nom unique (ex: avatar_3_168493029.jpg)
                $newFileName = 'avatar_' . $_SESSION['id_user'] . '_' . time() . '.' . $fileExtension;
                $destination = $uploadDir . $newFileName;

                // On déplace le fichier téléchargé
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
                    // On met à jour la base de données
                    $photoPath = '/uploads/avatars/' . $newFileName;
                    
                    // Connexion directe pour aller plus vite (ou tu peux rajouter une méthode dans AdminModel)
                    $db = \App\Models\Database::getConnection();
                    $stmt = $db->prepare("UPDATE Utilisateur SET photo_path = ? WHERE id_user = ?");
                    $stmt->execute([$photoPath, $_SESSION['id_user']]);
                }
            }
        }
        
        // On redirige vers le profil une fois terminé
        header('Location: /profil');
        exit;
    }
}