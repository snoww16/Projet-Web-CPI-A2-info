<?php
namespace App\Controllers;
use App\Models\OfferModel;

class HomeController extends Controller {
    public function index() {
        $offerModel = new OfferModel();
        $mes_favoris = isset($_SESSION['id_user']) ? $offerModel->getWishlistIdsByUser($_SESSION['id_user']) : [];
        $this->render('index.twig', [
            'dernieres_offres' => $offerModel->searchOffers([], 3, 0),
            'wishlist' => $mes_favoris
        ]);
    }

    public function aPropos() { $this->render('a-propos.twig'); }
    public function mentionsLegales() { $this->render('legal/mentions-legales.twig'); }
    public function confidentialite() { $this->render('legal/confidentialite.twig'); }
    public function contact() { $this->render('legal/contact.twig'); }
    
    public function profil() {
        if (!isset($_SESSION['id_user'])) { header('Location: /login'); exit; }
        $offerModel = new OfferModel();
        $data = ['candidatures_resume' => [], 'wishlist_resume' => [], 'total_candidatures' => 0, 'total_wishlist' => 0];

        if ($_SESSION['role'] == 3) {
            $toutes_candidatures = $offerModel->getCandidaturesByUser($_SESSION['id_user']);
            $toutes_favorites = $offerModel->getWishlistOffersByUser($_SESSION['id_user']);
            $data['candidatures_resume'] = array_slice($toutes_candidatures, 0, 3);
            $data['wishlist_resume'] = array_slice($toutes_favorites, 0, 3);
            $data['total_candidatures'] = count($toutes_candidatures);
            $data['total_wishlist'] = count($toutes_favorites);
        }
        $this->render('profil.twig', $data);
    }
}