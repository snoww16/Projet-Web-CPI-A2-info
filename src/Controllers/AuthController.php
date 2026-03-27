<?php
namespace App\Controllers;
use App\Models\AuthModel;

class AuthController extends Controller {
    public function login() {
        if (isset($_SESSION['id_user'])) { 
            header('Location: /'); 
            exit; 
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            // On utilise ton AdminModel pour chercher l'utilisateur
            $adminModel = new \App\Models\AdminModel();
            $user = $adminModel->getUtilisateurByEmail($email);
            
            // LA MAGIE EST ICI : password_verify décode le hash de la BDD et le compare !
            if ($user && password_verify($password, $user['mot_de_passe'])) {
                
                // --- CONNEXION RÉUSSIE ---
                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['nom'] = $user['nom'];
                $_SESSION['prenom'] = $user['prenom'];
                $_SESSION['role'] = $user['id_role'];
                
                // Optionnel : Forcer la page de première connexion si c'est le cas
                if ($user['premiere_connexion'] == 1) {
                    header('Location: /premiere-connexion');
                    exit;
                }
                
                $redirectUrl = $_SESSION['redirect_to'] ?? '/';
                unset($_SESSION['redirect_to']);
                header('Location: ' . $redirectUrl); 
                exit;

            } else {
                // --- ÉCHEC ---
                return $this->render('auth/login.twig', ['error' => 'Adresse e-mail ou mot de passe incorrect.']);
            }
        } 
        
        // On mémorise la page d'origine SEULEMENT si ce n'est pas une page liée au mot de passe
        if (isset($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];
            if (strpos($referer, '/login') === false && 
                strpos($referer, '/reset-password') === false && 
                strpos($referer, '/forgot-password') === false) {
                $_SESSION['redirect_to'] = $referer;
            }
        }
        
        $this->render('auth/login.twig');
    }

    // Dans AuthController.php
public function forcePasswordChange() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pwd1 = $_POST['new_password'];
        $pwd2 = $_POST['confirm_password'];

        // C'est ICI qu'on vérifie la correspondance exacte
        if ($pwd1 !== $pwd2) {
            return $this->render('auth/first_login.twig', [
                'error' => 'Les deux mots de passe ne sont pas identiques (vérifiez les majuscules).'
            ]);
        }

        // Si c'est bon, on appelle le modèle
        $adminModel = new \App\Models\AdminModel();
        $adminModel->updatePasswordAndFirstLogin($_SESSION['id_user'], $pwd1);
        header('Location: /'); exit;
    }
    $this->render('auth/first_login.twig');
}

    public function logout() {
        session_destroy();
        header('Location: /'); exit;
    }

    public function forgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            
            // On cherche l'utilisateur
            $adminModel = new \App\Models\AdminModel();
            $user = $adminModel->getUtilisateurByEmail($email);

            if ($user) {
                // 1. Génération d'un jeton (token) unique et sécurisé
                $token = bin2hex(random_bytes(32));
                // Expiration dans 1 heure
                $expires = date("Y-m-d H:i:s", strtotime('+1 hour'));
                
                // 2. On sauvegarde le token dans la BDD
                $adminModel->setResetToken($email, $token, $expires);

                // 3. Préparation de l'e-mail
                $to = $email;
                $subject = "Reinitialisation de votre mot de passe - CESI Ton Stage";
                
                // Le lien cliquable que l'utilisateur va recevoir
                $resetLink = "https://cesi-ton-stage.com/reset-password?token=" . $token;
                
                // Le contenu du message
                $message = "Bonjour " . $user['prenom'] . ",\n\n";
                $message .= "Vous avez demande a reinitialiser votre mot de passe.\n";
                $message .= "Veuillez cliquer sur le lien ci-dessous pour creer un nouveau mot de passe :\n\n";
                $message .= $resetLink . "\n\n";
                $message .= "Ce lien est valide pendant 1 heure.\n";
                $message .= "Si vous n'avez pas fait cette demande, ignorez cet e-mail.\n\n";
                $message .= "L'equipe CESI Ton Stage";

                // 4. Les en-têtes (Headers) - CRUCIAL POUR OVH
                // Remplace 'contact@cesi-ton-stage.com' par une adresse qui existe sur ton OVH si possible
                $headers = "From: contact@cesi-ton-stage.com\r\n";
                $headers .= "Reply-To: noreply@cesi-ton-stage.com\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();

                // 5. Envoi de l'e-mail
                mail($to, $subject, $message, $headers);
            }
            
            // Message de succès générique (pour ne pas dévoiler aux hackers si un email existe ou non)
            return $this->render('auth/forgot_password.twig', [
                'success' => 'Si cette adresse correspond à un compte, un e-mail contenant les instructions a été envoyé.'
            ]);
        }
        
        // Affichage de la page par défaut (GET)
        $this->render('auth/forgot_password.twig');
    }

public function firstLogin() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pwd1 = $_POST['new_password'];
        $pwd2 = $_POST['confirm_password'];

        // VERIFICATION CRUCIALE
        if ($pwd1 !== $pwd2) {
            return $this->render('auth/first_login.twig', [
                'error' => 'Les mots de passe ne correspondent pas. Attention aux majuscules !'
            ]);
        }

        // Si c'est OK, on enregistre
        $adminModel = new \App\Models\AdminModel();
        $adminModel->updatePasswordAndFirstLogin($_SESSION['id_user'], $pwd1);
        header('Location: /'); exit;
    }
    $this->render('auth/first_login.twig');
}

public function resetPassword() {
        $token = $_GET['token'] ?? '';
        $adminModel = new \App\Models\AdminModel();
        
        // On vérifie si le jeton existe en BDD et n'est pas expiré
        $user = $adminModel->getUserByToken($token);

        if (!$user) {
            die("<h2 style='text-align:center; margin-top:50px; color:#fff;'>Erreur : Ce lien est invalide ou a expiré.</h2>");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pwd1 = $_POST['new_password'];
            $pwd2 = $_POST['confirm_password'];

            if ($pwd1 !== $pwd2) {
                return $this->render('auth/reset_password.twig', [
                    'error' => 'Les mots de passe ne correspondent pas.',
                    'token' => $token
                ]);
            }

            // On met à jour le mot de passe (on réutilise ta fonction existante)
            $adminModel->updatePasswordAndFirstLogin($user['id_user'], $pwd1);
            
            // On efface le jeton pour qu'il ne soit plus réutilisable
            $adminModel->setResetToken($user['email'], NULL, NULL);

            // Redirection vers le login
            header('Location: /login'); 
            exit;
        }

        return $this->render('auth/reset_password.twig', ['token' => $token]);
    }
}