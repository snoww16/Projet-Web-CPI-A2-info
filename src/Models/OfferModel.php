<?php
namespace App\Models;
use PDO;
use PDOException;

class OfferModel extends Model {

    public function __construct() {
        // Connexion à la BDD (idéalement à mettre dans la classe parente Model)
        try {
            $this->connection = new PDO("mysql:host=localhost;dbname=web4all;charset=utf8mb4", "root", "", [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }

    public function getAllOffers() {
        // Vraie requête SQL
        $stmt = $this->connection->prepare("SELECT * FROM offres");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getOfferById($id) {
        $stmt = $this->connection->prepare("SELECT * FROM offres WHERE id_offre = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
}