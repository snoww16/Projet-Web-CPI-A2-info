<?php
namespace App\Models;

use PDO;

class AdminModel extends Model {
    
    // --- 1. LECTURE DES DONNÉES (Pour les listes) ---

    // --- 1. LECTURE DES DONNÉES (Avec Pagination) ---

    public function getUtilisateursByRole($id_role, $limit = 15, $offset = 0) {
        $sql = "SELECT id_user AS id, CONCAT(nom, ' ', prenom) AS nom FROM Utilisateur WHERE id_role = :role ORDER BY nom ASC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['role' => $id_role]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function countUtilisateursByRole($id_role) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM Utilisateur WHERE id_role = :role");
        $stmt->execute(['role' => $id_role]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function getEntreprises($limit = 15, $offset = 0) {
        $sql = "SELECT id_entreprise AS id, nom FROM Entreprise ORDER BY nom ASC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    public function countEntreprises() {
        return $this->db->query("SELECT COUNT(*) as total FROM Entreprise")->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function getOffres($limit = 15, $offset = 0) {
        $sql = "SELECT id_offre AS id, titre AS nom FROM Offre ORDER BY date_offre DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    public function countOffres() {
        return $this->db->query("SELECT COUNT(*) as total FROM Offre")->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // --- 2. CRÉATION DES DONNÉES (Pour les formulaires) ---

    public function creerUtilisateur($nom, $prenom, $email, $password, $id_role) {
        $sql = "INSERT INTO Utilisateur (nom, prenom, email, mot_de_passe, id_role) VALUES (:nom, :prenom, :email, :mdp, :role)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'nom' => $nom, 
            'prenom' => $prenom, 
            'email' => $email, 
            'mdp' => $password, 
            'role' => $id_role
        ]);
    }

    public function creerEntreprise($nom, $description, $email, $telephone) {
        $sql = "INSERT INTO Entreprise (nom, description, email_contact, telephone) VALUES (:nom, :desc, :email, :tel)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'nom' => $nom, 
            'desc' => $description, 
            'email' => $email, 
            'tel' => $telephone
        ]);
    }

    // Pour l'offre, je mets id_entreprise = 1 par défaut pour tes tests. 
    // Plus tard, on ajoutera un menu déroulant pour choisir l'entreprise !
    public function creerOffre($titre, $description, $remuneration, $date_debut, $id_entreprise, $ville, $duree, $type_contrat, $domaine) {
        $sql = "INSERT INTO Offre (titre, description, remuneration, date_debut, id_entreprise, ville, duree, type_contrat, domaine, date_offre) 
                VALUES (:titre, :desc, :remun, :date_debut, :id_entreprise, :ville, :duree, :type_contrat, :domaine, CURDATE())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'titre' => $titre, 
            'desc' => $description, 
            'remun' => $remuneration, 
            'date_debut' => $date_debut,
            'id_entreprise' => $id_entreprise,
            'ville' => $ville,
            'duree' => $duree,
            'type_contrat' => $type_contrat,
            'domaine' => $domaine
        ]);
    }

    // --- 3. SUPPRESSION DES DONNÉES ---

    public function supprimerUtilisateur($id) {
        $sql = "DELETE FROM Utilisateur WHERE id_user = :id";
        $this->db->prepare($sql)->execute(['id' => $id]);
    }

    public function supprimerEntreprise($id) {
        $sql = "DELETE FROM Entreprise WHERE id_entreprise = :id";
        $this->db->prepare($sql)->execute(['id' => $id]);
    }

    public function supprimerOffre($id) {
        $sql = "DELETE FROM Offre WHERE id_offre = :id";
        $this->db->prepare($sql)->execute(['id' => $id]);
    }

    public function modifierOffre($id, $titre, $description, $remuneration, $date_debut, $id_entreprise, $ville, $duree, $type_contrat, $domaine) {
        $sql = "UPDATE Offre 
                SET titre = :titre, description = :desc, remuneration = :remun, date_debut = :date_debut, 
                    id_entreprise = :id_entreprise, ville = :ville, duree = :duree, type_contrat = :type_contrat, domaine = :domaine 
                WHERE id_offre = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'titre' => $titre, 
            'desc' => $description, 
            'remun' => $remuneration, 
            'date_debut' => $date_debut,
            'id_entreprise' => $id_entreprise,
            'ville' => $ville,
            'duree' => $duree,
            'type_contrat' => $type_contrat,
            'domaine' => $domaine,
            'id' => $id
        ]);
    }
}