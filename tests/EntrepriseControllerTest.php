<?php
use PHPUnit\Framework\TestCase;
use App\Controllers\EntrepriseController;

class EntrepriseControllerTest extends TestCase {
    
    protected function setUp(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_GET = []; 
    }

    public function testEnterpriseIndexLoadsCorrectly() {
        $controller = new EntrepriseController();
        
        ob_start();
        $controller->index();
        $output = ob_get_clean();

        // Vérifie qu'on est bien sur la page des entreprises
        $this->assertStringContainsString('<!DOCTYPE html>', $output);
    }
}