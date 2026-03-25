<?php
namespace App\Models;

use PDO;

class AdminModel extends Model {
    
    // --- 1. LECTURE DES DONNÉES (Pour les listes) ---

    public function getUtilisateursByRole($id_role) {
        // On concatène Nom et Prénom pour l'affichage dans la liste
        $sql = "SELECT id_user AS id, CONCAT(nom, ' ', prenom) AS nom FROM Utilisateur WHERE id_role = :role ORDER BY nom ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['role' => $id_role]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEntreprises() {
        $sql = "SELECT id_entreprise AS id, nom FROM Entreprise ORDER BY nom ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOffres() {
        $sql = "SELECT id_offre AS id, titre AS nom FROM Offre ORDER BY date_offre DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
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
    public function creerOffre($titre, $description, $remuneration, $date_debut) {
        $sql = "INSERT INTO Offre (titre, description, remuneration, date_debut, id_entreprise) VALUES (:titre, :desc, :remun, :date_debut, 1)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'titre' => $titre, 
            'desc' => $description, 
            'remun' => $remuneration, 
            'date_debut' => $date_debut
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
}