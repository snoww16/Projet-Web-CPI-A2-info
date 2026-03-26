<?php

namespace App\Models;

class OfferModel extends Model {

    public function searchOffers($filtres = [], $limit = 15, $offset = 0) {
        // CORRECTION 1 : Ajout de o.niveau_requis dans le SELECT
        $sql = "SELECT o.id_offre AS id, o.titre, o.description, o.ville, o.duree, o.type_contrat, o.domaine, o.remuneration, o.date_debut, o.niveau_requis, e.nom AS entreprise_nom, e.logo_path,
                       GROUP_CONCAT(c.nom_competence SEPARATOR ', ') AS competences,
                       (SELECT COUNT(*) FROM Candidature cand WHERE cand.id_offre = o.id_offre) AS nb_candidatures,
                       (SELECT ROUND(AVG(note), 1) FROM Evaluation_Entreprise WHERE id_entreprise = o.id_entreprise) AS moyenne_avis,
                       (SELECT COUNT(*) FROM Evaluation_Entreprise WHERE id_entreprise = o.id_entreprise) AS nb_avis
                FROM Offre o 
                JOIN Entreprise e ON o.id_entreprise = e.id_entreprise 
                LEFT JOIN Offre_Competence oc ON o.id_offre = oc.id_offre
                LEFT JOIN Competence c ON oc.id_competence = c.id_competence
                WHERE 1=1";
                
        $params = []; 

        if (!empty($filtres['q'])) {
            $sql .= " AND (o.titre LIKE :q1 OR o.description LIKE :q2 OR e.nom LIKE :q3)";
            $params['q1'] = '%' . $filtres['q'] . '%';
            $params['q2'] = '%' . $filtres['q'] . '%';
            $params['q3'] = '%' . $filtres['q'] . '%';
        }
        if (!empty($filtres['ville'])) {
            $sql .= " AND o.ville = :ville";
            $params['ville'] = $filtres['ville'];
        }
        if (!empty($filtres['type_contrat'])) {
            $sql .= " AND o.type_contrat = :type_contrat";
            $params['type_contrat'] = $filtres['type_contrat'];
        }
        if (!empty($filtres['domaine'])) {
            $sql .= " AND o.domaine LIKE :domaine";
            $params['domaine'] = '%' . $filtres['domaine'] . '%';
        }
        if (!empty($filtres['duree'])) {
            $sql .= " AND o.duree >= :duree";
            $params['duree'] = (int)$filtres['duree'];
        }
        
        // CORRECTION 2 : Correction de la variable et de la construction de la requête
        if (!empty($filtres['niveau_requis'])) {
            $sql .= " AND o.niveau_requis = :niveau_requis";
            $params['niveau_requis'] = $filtres['niveau_requis'];
        }

        $sql .= " GROUP BY o.id_offre ORDER BY o.date_offre DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countOffers($filtres = []) {
        $sql = "SELECT COUNT(*) FROM Offre o JOIN Entreprise e ON o.id_entreprise = e.id_entreprise WHERE 1=1";
        $params = [];
        
        if (!empty($filtres['q'])) {
            $sql .= " AND (o.titre LIKE :q1 OR o.description LIKE :q2 OR e.nom LIKE :q3)";
            $params['q1'] = '%' . $filtres['q'] . '%';
            $params['q2'] = '%' . $filtres['q'] . '%';
            $params['q3'] = '%' . $filtres['q'] . '%';
        }
        if (!empty($filtres['ville'])) {
            $sql .= " AND o.ville = :ville";
            $params['ville'] = $filtres['ville'];
        }
        if (!empty($filtres['type_contrat'])) {
            $sql .= " AND o.type_contrat = :type_contrat";
            $params['type_contrat'] = $filtres['type_contrat'];
        }
        if (!empty($filtres['domaine'])) {
            $sql .= " AND o.domaine LIKE :domaine";
            $params['domaine'] = '%' . $filtres['domaine'] . '%';
        }
        if (!empty($filtres['duree'])) {
            $sql .= " AND o.duree >= :duree";
            $params['duree'] = (int)$filtres['duree'];
        }
        
        // CORRECTION 3 : Même chose ici pour le comptage
        if (!empty($filtres['niveau_requis'])) {
            $sql .= " AND o.niveau_requis = :niveau_requis";
            $params['niveau_requis'] = $filtres['niveau_requis'];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function getOfferById($id) {
        $sql = "SELECT o.id_offre AS id, o.titre, o.description AS offre_desc, o.remuneration, o.date_debut,
                       o.ville, o.duree, o.type_contrat, o.niveau_requis, o.id_entreprise,
                       e.nom AS entreprise_nom, e.description AS entreprise_desc, e.logo_path,
                       GROUP_CONCAT(c.nom_competence SEPARATOR ', ') AS competences,
                       (SELECT COUNT(*) FROM Candidature cand WHERE cand.id_offre = o.id_offre) AS nb_candidatures,
                       (SELECT ROUND(AVG(note), 1) FROM Evaluation_Entreprise WHERE id_entreprise = o.id_entreprise) AS moyenne_avis,
                       (SELECT COUNT(*) FROM Evaluation_Entreprise WHERE id_entreprise = o.id_entreprise) AS nb_avis
                FROM Offre o
                JOIN Entreprise e ON o.id_entreprise = e.id_entreprise
                LEFT JOIN Offre_Competence oc ON o.id_offre = oc.id_offre
                LEFT JOIN Competence c ON oc.id_competence = c.id_competence
                WHERE o.id_offre = :id
                GROUP BY o.id_offre";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    // --- FAVORIS (Wishlist) ---
    public function getWishlistIdsByUser($userId) {
        $sql = "SELECT id_offre FROM Wishlist WHERE id_user = :userId";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function toggleWishlist($userId, $offreId) {
        $sqlCheck = "SELECT COUNT(*) FROM Wishlist WHERE id_user = :userId AND id_offre = :offreId";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->execute(['userId' => $userId, 'offreId' => $offreId]);
        
        if ($stmtCheck->fetchColumn() > 0) {
            $sql = "DELETE FROM Wishlist WHERE id_user = :userId AND id_offre = :offreId";
        } else {
            $sql = "INSERT INTO Wishlist (id_user, id_offre) VALUES (:userId, :offreId)";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['userId' => $userId, 'offreId' => $offreId]);
    }

    // --- GESTION DES CANDIDATURES ---

    // Vérifie si l'utilisateur a déjà postulé à cette offre
    public function hasUserApplied($id_offre, $id_user) {
        $sql = "SELECT COUNT(*) FROM Candidature WHERE id_offre = :id_offre AND id_user = :id_user";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_offre' => $id_offre, 'id_user' => $id_user]);
        return $stmt->fetchColumn() > 0;
    }

    // Enregistre la candidature avec les chemins des fichiers et le message
    public function applyForOffer($id_offre, $id_user, $cv_path, $lm_path, $message = '') {
        $sql = "INSERT INTO Candidature (id_offre, id_user, date_candidature, cv_path, lm_path, message) 
                VALUES (:id_offre, :id_user, CURDATE(), :cv_path, :lm_path, :message)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id_offre' => $id_offre,
            'id_user' => $id_user,
            'cv_path' => $cv_path,
            'lm_path' => $lm_path,
            'message' => $message
        ]);
    }

    // Récupère toutes les informations des offres mises en favoris par un utilisateur
    public function getWishlistOffersByUser($userId, $limit = 15, $offset = 0) {
        // CORRECTION 5 : Ajout de o.niveau_requis pour afficher le badge dans les favoris
        $sql = "SELECT o.id_offre AS id, o.titre, o.description, o.ville, o.duree, o.type_contrat, o.remuneration, o.niveau_requis, e.nom AS entreprise_nom, e.logo_path,
                GROUP_CONCAT(c.nom_competence SEPARATOR ', ') AS competences,
                (SELECT COUNT(*) FROM Candidature cand WHERE cand.id_offre = o.id_offre) AS nb_candidatures
                FROM Wishlist w
                JOIN Offre o ON w.id_offre = o.id_offre
                JOIN Entreprise e ON o.id_entreprise = e.id_entreprise
                LEFT JOIN Offre_Competence oc ON o.id_offre = oc.id_offre
                LEFT JOIN Competence c ON oc.id_competence = c.id_competence
                WHERE w.id_user = :userId
                GROUP BY o.id_offre
                ORDER BY w.id_offre DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countWishlistOffersByUser($userId) {
        $sql = "SELECT COUNT(*) FROM Wishlist WHERE id_user = :userId";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetchColumn();
    }

    // Récupère l'historique des candidatures d'un utilisateur
    public function getCandidaturesByUser($userId, $limit = 15, $offset = 0) {
        // NOUVEAU : On ajoute c.cv_path, c.lm_path et c.message dans le SELECT
        $sql = "SELECT c.date_candidature, c.cv_path, c.lm_path, c.message, 
                o.id_offre AS id, o.titre, o.ville, o.type_contrat, o.duree, o.remuneration, o.description, o.niveau_requis, e.nom AS entreprise_nom, e.logo_path,
                (SELECT COUNT(*) FROM Candidature cand WHERE cand.id_offre = o.id_offre) AS nb_candidatures
                FROM Candidature c
                JOIN Offre o ON c.id_offre = o.id_offre
                JOIN Entreprise e ON o.id_entreprise = e.id_entreprise
                WHERE c.id_user = :userId
                ORDER BY c.date_candidature DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countCandidaturesByUser($userId) {
        $sql = "SELECT COUNT(*) FROM Candidature WHERE id_user = :userId";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetchColumn();
    }

    public function getStatistiquesGlobales() {
        $stats = [];
        
        // 1. Nombre total d'offres
        $stats['total_offres'] = $this->db->query("SELECT COUNT(*) FROM Offre")->fetchColumn();

        // 2. Nombre moyen de candidatures par offre
        $total_cand = $this->db->query("SELECT COUNT(*) FROM Candidature")->fetchColumn();
        $stats['moyenne_candidatures'] = $stats['total_offres'] > 0 ? round($total_cand / $stats['total_offres'], 1) : 0;

        // 3. Top des offres les plus ajoutées en wish-list
        $stats['top_wishlist'] = $this->db->query("
            SELECT o.titre, COUNT(w.id_user) as nb_ajouts
            FROM Wishlist w
            JOIN Offre o ON w.id_offre = o.id_offre
            GROUP BY o.id_offre
            ORDER BY nb_ajouts DESC
            LIMIT 3
        ")->fetchAll(\PDO::FETCH_ASSOC);

        // 4. Répartition par durée de stage
        $stats['repartition_duree'] = $this->db->query("
            SELECT duree, COUNT(*) as nb
            FROM Offre
            GROUP BY duree
            ORDER BY duree ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        return $stats;
    }
}