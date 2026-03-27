<?php
namespace App\Controllers;
use App\Models\AdminModel;

class AuthController extends Controller {

    public function login() {
        if (isset($_SESSION['id_user'])) { header('Location: /'); exit; }

        $erreur = null;
        $email_saisi = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email_saisi = $_POST['email'] ?? '';
            $password = $_POST['mot_de_passe'] ?? '';

            $adminModel = new AdminModel();
            $user = $adminModel->getUtilisateurByEmail($email_saisi);

            if ($user && password_verify($password, $user['mot_de_passe'])) {
                if (isset($user['premiere_connexion']) && $user['premiere_connexion'] == 1) {
                    $_SESSION['temp_id_user'] = $user['id_user'];
                    header('Location: /premiere-connexion');
                    exit;
                }

                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['role'] = $user['id_role'];
                $_SESSION['prenom'] = $user['prenom'];
                $_SESSION['nom'] = $user['nom'];
                
                header('Location: ' . ($_SESSION['redirect_to'] ?? '/'));
                unset($_SESSION['redirect_to']);
                exit;
            } else {
                $erreur = "Email ou mot de passe incorrect.";
            }
        }
        $this->render('auth/login.twig', ['erreur' => $erreur, 'email' => $email_saisi]);
    }

    public function forcePasswordChange() {
        if (!isset($_SESSION['temp_id_user'])) { header('Location: /login'); exit; }

        $erreur = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (strlen($new_password) < 8) {
                $erreur = "Le mot de passe doit contenir au moins 8 caractères.";
            } elseif ($new_password !== $confirm_password) {
                $erreur = "Les mots de passe ne correspondent pas.";
            } else {
                $adminModel = new AdminModel();
                $hash = password_hash($new_password, PASSWORD_BCRYPT);
                $adminModel->updatePasswordAndFirstLogin($_SESSION['temp_id_user'], $hash);
                $user = $adminModel->getUtilisateurById($_SESSION['temp_id_user']);
                
                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['role'] = $user['id_role'];
                $_SESSION['prenom'] = $user['prenom'];
                $_SESSION['nom'] = $user['nom'];
                unset($_SESSION['temp_id_user']);

                header('Location: /'); exit;
            }
        }
        $this->render('auth/premiere-connexion.twig', ['erreur' => $erreur]);
    }

    public function forgotPassword() {
        $message = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $adminModel = new AdminModel();
            $user = $adminModel->getUtilisateurByEmail($email);

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600);
                $adminModel->setPasswordResetToken($email, $token, $expires);

                $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
                $reset_link = $protocol . $_SERVER['HTTP_HOST'] . "/reinitialiser-mot-de-passe?token=" . $token;

                $sujet = "Réinitialisation de votre mot de passe - CESI Ton Stage";
                $corps = "Bonjour,\n\nCliquez sur ce lien pour réinitialiser votre mot de passe (valide 1h) :\n" . $reset_link;
                $headers = "From: noreply@" . $_SERVER['HTTP_HOST'];
                
                mail($email, $sujet, $corps, $headers);
            }
            $message = "Si cette adresse existe, un e-mail avec un lien de réinitialisation a été envoyé.";
        }
        $this->render('auth/mot-de-passe-oublie.twig', ['message' => $message]);
    }

    public function resetPassword() {
        $token = $_GET['token'] ?? null;
        if (!$token) { header('Location: /login'); exit; }

        $adminModel = new AdminModel();
        $user = $adminModel->getUserByResetToken($token);
        if (!$user) { die("Lien invalide ou expiré."); }

        $erreur = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new_password = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if (strlen($new_password) < 8) {
                $erreur = "8 caractères minimum.";
            } elseif ($new_password !== $confirm) {
                $erreur = "Les mots de passe ne correspondent pas.";
            } else {
                $hash = password_hash($new_password, PASSWORD_BCRYPT);
                $adminModel->resetUserPassword($user['id_user'], $hash);
                header('Location: /login?reset=success'); exit;
            }
        }
        $this->render('auth/reinitialiser.twig', ['erreur' => $erreur, 'token' => $token]);
    }

    public function logout() {
        session_destroy();
        header('Location: /login');
        exit;
    }
}