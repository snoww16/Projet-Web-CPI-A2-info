<?php
use PHPUnit\Framework\TestCase;
use App\Controllers\OfferController;

class OfferControllerTest extends TestCase {
    
    protected function setUp(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // On simule une URL de recherche vide
        $_SERVER['REQUEST_URI'] = '/offres';
        $_GET = []; 
    }

    public function testOfferSearchPageLoadsAndContainsFilters() {
        $controller = new OfferController();
        
        ob_start();
        $controller->index();
        $output = ob_get_clean();

        // On vérifie que la page charge bien le HTML
        $this->assertStringContainsString('<!DOCTYPE html>', $output);
        
        // On vérifie que les filtres de recherche sont bien présents sur la page
        $this->assertStringContainsString('Mots-clés', $output, "Le filtre de mots-clés est manquant.");
        $this->assertStringContainsString('Domaine', $output, "Le filtre de domaine est manquant.");
        $this->assertStringContainsString('Niveau requis', $output, "Le filtre de niveau est manquant.");
    }
}