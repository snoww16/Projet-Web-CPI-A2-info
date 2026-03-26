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
        // On récupère TOUTES les infos utiles pour les badges
        $sql = "SELECT o.id_offre AS id, o.titre, o.ville, o.duree, o.type_contrat, o.remuneration, o.niveau_requis, o.domaine,
                       e.nom AS entreprise_nom, e.logo_path 
                FROM Offre o 
                JOIN Entreprise e ON o.id_entreprise = e.id_entreprise 
                ORDER BY o.id_offre DESC 
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
    public function creerOffre($titre, $description, $remuneration, $date_debut, $id_entreprise, $ville, $duree, $type_contrat, $domaine, $niveau_requis) {
        $sql = "INSERT INTO Offre (titre, description, remuneration, date_debut, id_entreprise, ville, duree, type_contrat, domaine, niveau_requis)
                VALUES (:titre, :desc, :remun, :date_deb, :id_e, :ville, :duree, :type, :domaine, :niveau)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'titre' => $titre, 'desc' => $description, 'remun' => $remuneration, 'date_deb' => $date_debut,
            'id_e' => $id_entreprise, 'ville' => $ville, 'duree' => $duree, 'type' => $type_contrat,
            'domaine' => $domaine, 'niveau' => $niveau_requis
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

    // Récupérer les utilisateurs avec filtres (Barre de recherche)
    public function getUtilisateursByRoleFiltered($id_role, $filters = [], $limit = 15, $offset = 0) {
        $sql = "SELECT id_user, nom, prenom, email, statut_recherche, photo_path FROM Utilisateur WHERE id_role = :role";
        $params = ['role' => $id_role];

        if (!empty($filters['q'])) {
            $sql .= " AND (nom LIKE :q OR prenom LIKE :q OR email LIKE :q)";
            $params['q'] = '%' . $filters['q'] . '%';
        }
        
        if (!empty($filters['statut']) && $id_role == 3) {
            $sql .= " AND statut_recherche = :statut";
            $params['statut'] = $filters['statut'];
        }

        $sql .= " ORDER BY nom ASC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Compter pour la pagination avec les filtres
    public function countUtilisateursByRoleFiltered($id_role, $filters = []) {
        $sql = "SELECT COUNT(*) FROM Utilisateur WHERE id_role = :role";
        $params = ['role' => $id_role];

        if (!empty($filters['q'])) {
            $sql .= " AND (nom LIKE :q OR prenom LIKE :q OR email LIKE :q)";
            $params['q'] = '%' . $filters['q'] . '%';
        }
        if (!empty($filters['statut']) && $id_role == 3) {
            $sql .= " AND statut_recherche = :statut";
            $params['statut'] = $filters['statut'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    // Mettre à jour le statut d'un étudiant
    public function updateStatutRecherche($id_user, $statut) {
        $sql = "UPDATE Utilisateur SET statut_recherche = :statut WHERE id_user = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['statut' => $statut, 'id' => $id_user]);
    }
}