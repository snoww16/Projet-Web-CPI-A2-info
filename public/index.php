<?php
session_start();
ini_set('display_errors', 1); error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// =========================================================
// ROUTEUR MVC (Le cerveau de l'application)
// =========================================================

switch (true) {
    // --- STATIQUES & PROFIL ---
    case $request === '/': (new \App\Controllers\HomeController())->index(); break;
    case $request === '/a-propos': (new \App\Controllers\HomeController())->aPropos(); break;
    case $request === '/mentions-legales': (new \App\Controllers\HomeController())->mentionsLegales(); break;
    case $request === '/politique-confidentialite': (new \App\Controllers\HomeController())->confidentialite(); break;
    case $request === '/contact': (new \App\Controllers\HomeController())->contact(); break;
    case $request === '/profil/avatar': (new \App\Controllers\HomeController())->updateAvatar(); break;

    // 👇 LA CORRECTION EST ICI : Deux routes pour le profil
    case $request === '/profil': (new \App\Controllers\HomeController())->profil(); break;
    case (preg_match('/^\/profil\/([0-9]+)$/', $request, $matches) ? true : false): (new \App\Controllers\HomeController())->profil($matches[1]); break;
    // --- AUTHENTIFICATION ---
    case $request === '/login': (new \App\Controllers\AuthController())->login(); break;
    case $request === '/logout': (new \App\Controllers\AuthController())->logout(); break;
    case $request === '/premiere-connexion': (new \App\Controllers\AuthController())->firstLogin(); break; // Ta page de 1ère connexion
    case $request === '/forgot-password': (new \App\Controllers\AuthController())->forgotPassword(); break; // La nouvelle page
    case $request === '/reset-password': (new \App\Controllers\AuthController())->resetPassword(); break;

    // --- OFFRES & CANDIDATURES ---
    case $request === '/offres': (new \App\Controllers\OfferController())->index(); break;
    case $request === '/mes-candidatures': (new \App\Controllers\OfferController())->mesCandidatures(); break;
    case $request === '/mes-favoris': (new \App\Controllers\OfferController())->mesFavoris(); break;
    case $request === '/wishlist/toggle': (new \App\Controllers\OfferController())->toggleWishlist(); break;
    case (preg_match('/^\/offre\/([0-9]+)$/', $request, $matches) ? true : false): (new \App\Controllers\OfferController())->details($matches[1]); break;
    case (preg_match('/^\/postuler\/([0-9]+)$/', $request, $matches) ? true : false): (new \App\Controllers\OfferController())->postuler($matches[1]); break;

    // --- ENTREPRISES ---
    case $request === '/entreprises': (new \App\Controllers\EntrepriseController())->index(); break;
    case $request === '/entreprise/evaluer': (new \App\Controllers\EntrepriseController())->evaluer(); break;
    case (preg_match('/^\/entreprise\/([0-9]+)$/', $request, $matches) ? true : false): (new \App\Controllers\EntrepriseController())->details($matches[1]); break;

    // --- ADMINISTRATION ---
    case $request === '/admin': (new \App\Controllers\AdminController())->dashboard(); break;
    case $request === '/admin/etudiant/statut': (new \App\Controllers\AdminController())->updateStatutEtudiant(); break;
    case (preg_match('/^\/admin\/(etudiants|pilotes|entreprises|offres)$/', $request, $matches) ? true : false): (new \App\Controllers\AdminController())->liste($matches[1]); break;
    case (preg_match('/^\/admin\/(etudiants|pilotes|entreprises|offres)\/creer$/', $request, $matches) ? true : false): (new \App\Controllers\AdminController())->creer($matches[1]); break;
    case (preg_match('/^\/admin\/(etudiants|pilotes|entreprises|offres)\/modifier\/([0-9]+)$/', $request, $matches) ? true : false): (new \App\Controllers\AdminController())->modifier($matches[1], $matches[2]); break;
    case (preg_match('/^\/admin\/(etudiants|pilotes|entreprises|offres)\/supprimer\/([0-9]+)$/', $request, $matches) ? true : false): (new \App\Controllers\AdminController())->supprimer($matches[1], $matches[2]); break;

    // --- 404 ---
    default:
        http_response_code(404);
        echo "<h1 style='text-align:center; margin-top:50px; color:#fff;'>Erreur 404 - Page introuvable</h1>";
        break;
}