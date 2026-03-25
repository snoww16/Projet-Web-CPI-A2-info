<?php
namespace App\Models;

use PDO;
use PDOException;

class AuthModel extends Model {
    
    public function login($email, $password) {
        try {
            // Vérifie bien que dans ta BDD, la colonne s'appelle "mot_de_passe" (et pas "mdp")
            $sql = "SELECT id_user, nom, prenom, email, mot_de_passe, id_role FROM Utilisateur WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Si l'utilisateur existe ET que le mot de passe correspond
            if ($user && $user['mot_de_passe'] === $password) {
                return $user;
            }
            return false;
        } catch (PDOException $e) {
            // S'il y a une erreur SQL, on arrête la page blanche et on affiche l'erreur
            die("Erreur SQL dans AuthModel : " . $e->getMessage());
        }
    }
}