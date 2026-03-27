<?php
namespace App\Models;
use PDO;

class AdminModel {
    protected $db;

    public function __construct() {
    $host = 'mysql-13320d91-o0640ffa2.database.cloud.ovh.net';
    $port = '20184';
    $dbname = 'web4all';
    $user = 'admin.web';
    $pass = 'h4GB0kzLI6txSmqj12PW';

    try {
        // Connexion avec gestion du SSL obligatoire sur Public Cloud
        $this->db = new PDO(
            "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", 
            $user, 
            $pass, 
            [
                PDO::MYSQL_ATTR_SSL_CA => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]
        );
    } catch (\PDOException $e) {
        die("Erreur de connexion à la base de données : " . $e->getMessage());
    }
}

    // ==========================================
    // 1. GESTION DES UTILISATEURS & PILOTES
    // ==========================================

    public function getPilotes() {
        $sql = "SELECT id_user, nom, prenom FROM Utilisateur WHERE id_role = 2 ORDER BY nom ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUtilisateurById($id) {
        // Version améliorée pour lier le Pilote associé à l'étudiant
        $sql = "SELECT u.*, p.nom as pilote_nom, p.prenom as pilote_prenom 
                FROM Utilisateur u 
                LEFT JOIN Utilisateur p ON u.id_pilote = p.id_user 
                WHERE u.id_user = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUtilisateurByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM Utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUtilisateursByRoleFiltered($role, $filters = [], $limit = 15, $offset = 0) {
        $sql = "SELECT * FROM Utilisateur WHERE id_role = :role";
        $params = ['role' => $role];

        if (!empty($filters['q'])) {
            $sql .= " AND (nom LIKE :q OR prenom LIKE :q OR email LIKE :q)";
            $params['q'] = '%' . $filters['q'] . '%';
        }
        if (!empty($filters['statut'])) {
            $sql .= " AND statut_recherche = :statut";
            $params['statut'] = $filters['statut'];
        }
        if (!empty($filters['id_pilote'])) {
            $sql .= " AND id_pilote = :id_pilote";
            $params['id_pilote'] = $filters['id_pilote'];
        }

        $sql .= " ORDER BY id_user DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countUtilisateursByRoleFiltered($role, $filters = []) {
        $sql = "SELECT COUNT(*) FROM Utilisateur WHERE id_role = :role";
        $params = ['role' => $role];

        if (!empty($filters['q'])) {
            $sql .= " AND (nom LIKE :q OR prenom LIKE :q OR email LIKE :q)";
            $params['q'] = '%' . $filters['q'] . '%';
        }
        if (!empty($filters['statut'])) {
            $sql .= " AND statut_recherche = :statut";
            $params['statut'] = $filters['statut'];
        }
        if (!empty($filters['id_pilote'])) {
            $sql .= " AND id_pilote = :id_pilote";
            $params['id_pilote'] = $filters['id_pilote'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function creerUtilisateur($nom, $prenom, $email, $password, $role, $id_pilote = null) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO Utilisateur (nom, prenom, email, mot_de_passe, id_role, id_pilote) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nom, $prenom, $email, $hash, $role, $id_pilote]);
    }

    public function modifierUtilisateur($id, $nom, $prenom, $email, $password, $id_pilote = null) {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE Utilisateur SET nom=?, prenom=?, email=?, mot_de_passe=?, id_pilote=? WHERE id_user=?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$nom, $prenom, $email, $hash, $id_pilote, $id]);
        } else {
            $sql = "UPDATE Utilisateur SET nom=?, prenom=?, email=?, id_pilote=? WHERE id_user=?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$nom, $prenom, $email, $id_pilote, $id]);
        }
    }

    public function supprimerUtilisateur($id) {
        $stmt = $this->db->prepare("DELETE FROM Utilisateur WHERE id_user = ?");
        return $stmt->execute([$id]);
    }

    public function getStudentsByPilote($id_pilote) {
        $stmt = $this->db->prepare("SELECT * FROM Utilisateur WHERE id_pilote = ? ORDER BY nom ASC");
        $stmt->execute([$id_pilote]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatutRecherche($id_user, $statut) {
        $stmt = $this->db->prepare("UPDATE Utilisateur SET statut_recherche = ? WHERE id_user = ?");
        return $stmt->execute([$statut, $id_user]);
    }

    public function updateAvatar($id, $path) {
        $stmt = $this->db->prepare("UPDATE Utilisateur SET photo_path = ? WHERE id_user = ?");
        return $stmt->execute([$path, $id]);
    }

    // ==========================================
    // 2. GESTION DES ENTREPRISES
    // ==========================================

    public function getEntreprises($limit = 15, $offset = 0) {
        $sql = "SELECT * FROM Entreprise ORDER BY id_entreprise DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEntrepriseById($id) {
        $stmt = $this->db->prepare("SELECT * FROM Entreprise WHERE id_entreprise = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function countEntreprises() {
        return $this->db->query("SELECT COUNT(*) FROM Entreprise")->fetchColumn();
    }

    public function creerEntreprise($nom, $description, $email, $telephone) {
        $sql = "INSERT INTO Entreprise (nom, description, email_contact, telephone) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nom, $description, $email, $telephone]);
    }

    public function supprimerEntreprise($id) {
        $stmt = $this->db->prepare("DELETE FROM Entreprise WHERE id_entreprise = ?");
        return $stmt->execute([$id]);
    }

    // ==========================================
    // 3. GESTION DES OFFRES
    // ==========================================
    
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

    public function modifierOffre($id, $titre, $description, $remuneration, $date_debut, $id_entreprise, $ville, $duree, $type_contrat, $domaine, $niveau_requis) {
        $sql = "UPDATE Offre 
                SET titre = :titre, description = :desc, remuneration = :remun, date_debut = :date_deb, 
                    id_entreprise = :id_e, ville = :ville, duree = :duree, type_contrat = :type, 
                    domaine = :domaine, niveau_requis = :niveau
                WHERE id_offre = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id, 'titre' => $titre, 'desc' => $description, 'remun' => $remuneration, 
            'date_deb' => $date_debut, 'id_e' => $id_entreprise, 'ville' => $ville, 
            'duree' => $duree, 'type' => $type_contrat, 'domaine' => $domaine, 'niveau' => $niveau_requis
        ]);
    }

    public function supprimerOffre($id) {
        $stmt = $this->db->prepare("DELETE FROM Offre WHERE id_offre = ?");
        return $stmt->execute([$id]);
    }

    // ==========================================
    // 4. SÉCURITÉ ET MOTS DE PASSE
    // ==========================================

    public function updatePasswordAndFirstLogin($id_user, $hash) {
        // Gère le changement forcé de mot de passe à la première connexion
        $stmt = $this->db->prepare("UPDATE Utilisateur SET mot_de_passe = ?, premiere_connexion = 0 WHERE id_user = ?");
        return $stmt->execute([$hash, $id_user]);
    }

    public function setPasswordResetToken($email, $token, $expires) {
        // Enregistre le token de réinitialisation
        $stmt = $this->db->prepare("UPDATE Utilisateur SET reset_token = ?, reset_expires = ? WHERE email = ?");
        return $stmt->execute([$token, $expires, $email]);
    }

    public function getUserByResetToken($token) {
        // Récupère l'utilisateur si le token est encore valide (date expiration > maintenant)
        $stmt = $this->db->prepare("SELECT * FROM Utilisateur WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function resetUserPassword($id_user, $hash) {
        // Définit le nouveau mot de passe et nettoie les colonnes de réinitialisation
        $stmt = $this->db->prepare("UPDATE Utilisateur SET mot_de_passe = ?, reset_token = NULL, reset_expires = NULL WHERE id_user = ?");
        return $stmt->execute([$hash, $id_user]);
    }
}