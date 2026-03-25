<?php
namespace App\Models;

use PDO;

class OfferModel extends Model {
    
    public function searchOffers($filtres = []) {
        // La base de la requête avec les JOIN pour les compétences
        $sql = "SELECT o.id_offre AS id, o.titre, o.description, o.ville, o.duree, o.type_contrat, o.domaine, o.remuneration, o.date_debut, e.nom AS entreprise_nom,
                GROUP_CONCAT(c.nom_competence SEPARATOR ', ') AS competences
                FROM Offre o 
                JOIN Entreprise e ON o.id_entreprise = e.id_entreprise 
                LEFT JOIN Offre_Competence oc ON o.id_offre = oc.id_offre
                LEFT JOIN Competence c ON oc.id_competence = c.id_competence
                WHERE 1=1";
                
        $params = []; 

        // Filtres
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

        if (!empty($filtres['duree'])) {
            $sql .= " AND o.duree LIKE :duree";
            $params['duree'] = '%' . $filtres['duree'] . '%';
        }

        if (!empty($filtres['type_contrat'])) {
            $sql .= " AND o.type_contrat = :type_contrat";
            $params['type_contrat'] = $filtres['type_contrat'];
        }

        if (!empty($filtres['domaine'])) {
            $sql .= " AND o.domaine LIKE :domaine";
            $params['domaine'] = '%' . $filtres['domaine'] . '%';
        }

        // L'ORDRE STRICT : GROUP BY d'abord, ORDER BY ensuite. Et avec le bon espacement !
        $sql .= " GROUP BY o.id_offre ORDER BY o.date_offre DESC";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOfferById($id) {
        $sql = "SELECT o.id_offre AS id, o.titre, o.description AS offre_desc, o.ville, o.duree, o.type_contrat, o.domaine, o.remuneration, o.date_debut, 
                e.nom AS entreprise_nom, e.description AS entreprise_desc, e.email_contact,
                GROUP_CONCAT(c.nom_competence SEPARATOR ', ') AS competences
                FROM Offre o 
                JOIN Entreprise e ON o.id_entreprise = e.id_entreprise 
                LEFT JOIN Offre_Competence oc ON o.id_offre = oc.id_offre
                LEFT JOIN Competence c ON oc.id_competence = c.id_competence
                WHERE o.id_offre = :id
                GROUP BY o.id_offre";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function toggleWishlist($id_user, $id_offre) {
        // On vérifie si l'offre est déjà dans les favoris
        $sqlCheck = "SELECT * FROM Wishlist WHERE id_user = :user AND id_offre = :offre";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->execute(['user' => $id_user, 'offre' => $id_offre]);
        
        if ($stmtCheck->fetch()) {
            // Si elle y est, on la supprime (unlike)
            $sqlDel = "DELETE FROM Wishlist WHERE id_user = :user AND id_offre = :offre";
            $this->db->prepare($sqlDel)->execute(['user' => $id_user, 'offre' => $id_offre]);
        } else {
            // Si elle n'y est pas, on l'ajoute (like)
            $sqlAdd = "INSERT INTO Wishlist (id_user, id_offre) VALUES (:user, :offre)";
            $this->db->prepare($sqlAdd)->execute(['user' => $id_user, 'offre' => $id_offre]);
        }
    }

    public function getWishlistIdsByUser($id_user) {
        $sql = "SELECT id_offre FROM Wishlist WHERE id_user = :user";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user' => $id_user]);
        // PDO::FETCH_COLUMN renvoie un tableau simple [1, 4, 7] au lieu d'un tableau associatif complexe
        return $stmt->fetchAll(PDO::FETCH_COLUMN); 
    }
}