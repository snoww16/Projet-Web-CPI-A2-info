<?php
namespace App\Models;

use PDO;

class EntrepriseModel extends Model {
    
    // 1. Récupère les entreprises avec une limite (15) et un décalage (la page)
    public function searchEntreprises($filtres = [], $limit = 15, $offset = 0) {
        $sql = "SELECT id_entreprise AS id, nom, secteur, ville, description FROM Entreprise WHERE 1=1";
        $params = [];

        if (!empty($filtres['q'])) {
            $sql .= " AND (nom LIKE :q OR description LIKE :q2)";
            $params['q'] = '%' . $filtres['q'] . '%';
            $params['q2'] = '%' . $filtres['q'] . '%';
        }
        if (!empty($filtres['ville'])) {
            $sql .= " AND ville = :ville";
            $params['ville'] = $filtres['ville'];
        }
        if (!empty($filtres['secteur'])) {
            $sql .= " AND secteur LIKE :secteur";
            $params['secteur'] = '%' . $filtres['secteur'] . '%';
        }

        // Ajout de la pagination à la requête SQL
        $sql .= " ORDER BY nom ASC LIMIT $limit OFFSET $offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. Compte le total exact (sans le LIMIT) pour générer les boutons de pages
    public function countEntreprises($filtres = []) {
        $sql = "SELECT COUNT(id_entreprise) as total FROM Entreprise WHERE 1=1";
        $params = [];

        if (!empty($filtres['q'])) {
            $sql .= " AND (nom LIKE :q OR description LIKE :q2)";
            $params['q'] = '%' . $filtres['q'] . '%';
            $params['q2'] = '%' . $filtres['q'] . '%';
        }
        if (!empty($filtres['ville'])) {
            $sql .= " AND ville = :ville";
            $params['ville'] = $filtres['ville'];
        }
        if (!empty($filtres['secteur'])) {
            $sql .= " AND secteur LIKE :secteur";
            $params['secteur'] = '%' . $filtres['secteur'] . '%';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function ajouterEvaluation($id_entreprise, $id_user, $note, $commentaire) {
        $sql = "INSERT INTO Evaluation_Entreprise (id_entreprise, id_user, note, commentaire, date_evaluation)
                VALUES (:id, :user, :note, :com, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id_entreprise,
            'user' => $id_user,
            'note' => $note,
            'com' => $commentaire
        ]);
    }

    public function getEvaluationsByEntreprise($id_entreprise) {
        $sql = "SELECT e.note, e.commentaire, DATE_FORMAT(e.date_evaluation, '%d/%m/%Y') as date_eval, u.prenom, u.nom, u.id_role
                FROM Evaluation_Entreprise e
                JOIN Utilisateur u ON e.id_user = u.id_user
                WHERE e.id_entreprise = :id
                ORDER BY e.date_evaluation DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id_entreprise]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}