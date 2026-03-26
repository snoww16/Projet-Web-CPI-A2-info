<?php
namespace App\Models;

use PDO;

abstract class Model {
    protected PDO $db;

    public function __construct() {
        // C'est ICI qu'on appelle notre super fichier de connexion !
        $this->db = Database::getConnection();
    }
}