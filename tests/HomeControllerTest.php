<?php
use PHPUnit\Framework\TestCase;
use App\Controllers\HomeController;

class HomeControllerTest extends TestCase {
    
    protected function setUp(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function testIndexReturnsValidHtml() {
        $controller = new HomeController();
        ob_start();
        $controller->index();
        $output = ob_get_clean();

        $this->assertStringContainsString('<!DOCTYPE html>', $output, "La page d'accueil ne génère pas de HTML valide.");
        $this->assertStringContainsString('CESI Ton Stage', $output, "Le titre du site n'apparaît pas.");
    }

    public function testMentionsLegalesLoadsCorrectly() {
        $controller = new HomeController();
        ob_start();
        $controller->mentionsLegales();
        $output = ob_get_clean();

        $this->assertStringContainsString('Mentions', $output);
    }
}