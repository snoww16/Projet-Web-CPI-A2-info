<?php
namespace App\Controllers;

class Controller {
    protected $twig;
    protected $domaines = ['Informatique', 'Réseaux', 'Data', 'Sécurité', 'Design', 'Gestion', 'BTP', 'Santé', 'Finance', 'Commerce', 'Marketing', 'Ressources Humaines', 'Ingénierie', 'Mécanique', 'Autre'];
    protected $niveaux = ['Collège (3ème)', 'Lycée / Bac', 'Bac+2 (BTS/DUT)', 'Bac+3 (Licence/Bachelor)', 'Bac+4/5 (Master/Ingénieur)'];

    public function __construct() {
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../../src/Views');
        $this->twig = new \Twig\Environment($loader, []);
        
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        $this->twig->addGlobal('session', $_SESSION);
    }

    protected function render($view, $data = []) {
        echo $this->twig->render($view, $data);
    }
}