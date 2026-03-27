<?php

namespace App\Models;

use PDO;
use Exception;

class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            // Configuration OVH Cloud
            $host = 'mysql-13320d91-o0640ffa2.database.cloud.ovh.net';
            $port = '20184';
            $dbname = 'web4all';
            $user = 'admin.web';
            $pass = 'h4GB0kzLI6txSmqj12PW';

            try {
                $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
                
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    // Désactivation de la vérification stricte du certificat pour OVH
                    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false 
                ]);
            } catch (Exception $e) {
                die("Erreur de connexion : " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}