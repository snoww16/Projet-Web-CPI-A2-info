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
}