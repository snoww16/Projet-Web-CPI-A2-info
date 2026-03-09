<?php 
namespace App\Controllers;

use App\Models\OfferModel;

class MineController extends Controller {

    public function __construct($templateEngine) {
        $this->model = new OfferModel();
        $this->templateEngine = $templateEngine;
    }

    public function welcomePage() {}
        
}
