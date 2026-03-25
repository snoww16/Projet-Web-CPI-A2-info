<?php
namespace App\Models;

use PDO;
use PDOException;

class Database {
    private static $instance = null;

    // Tes vrais identifiants qui marchent
    private const DB_USER = 'admin.web';
    private const DB_PASS = 'uFU6rmQ.@zzGyZ1*'; 

    private function __construct() {}

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                // La connexion TCP qui traverse le pare-feu Fedora
                $dsn = "mysql:host=127.0.0.1;port=3306;dbname=web4all;charset=utf8mb4";
                
                self::$instance = new PDO($dsn, self::DB_USER, self::DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);
            } catch (PDOException $e) {
                die("Erreur de connexion : " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}