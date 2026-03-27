<?php
namespace App\Controllers;
use App\Models\AuthModel;

class AuthController extends Controller {
    public function login() {
        if (isset($_SESSION['id_user'])) { header('Location: /'); exit; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authModel = new AuthModel();
            $user = $authModel->login($_POST['email'] ?? '', $_POST['password'] ?? '');
            
            if ($user) {
                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['nom'] = $user['nom'];
                $_SESSION['prenom'] = $user['prenom'];
                $_SESSION['role'] = $user['id_role'];
                
                $redirectUrl = $_SESSION['redirect_to'] ?? '/';
                unset($_SESSION['redirect_to']);
                header('Location: ' . $redirectUrl); exit;
            } else {
                return $this->render('auth/login.twig', ['error' => 'Adresse e-mail ou mot de passe incorrect.']);
            }
        } 
        if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], '/login') === false) {
            $_SESSION['redirect_to'] = $_SERVER['HTTP_REFERER'];
        }
        $this->render('auth/login.twig');
    }

    public function logout() {
        session_destroy();
        header('Location: /'); exit;
    }
}