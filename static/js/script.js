document.addEventListener('DOMContentLoaded', () => {
    
    // --- 1. Gestion du Menu Burger ---
    const burgerBtn = document.getElementById('burger-btn');
    const navMenu = document.getElementById('nav-menu');
    if(burgerBtn && navMenu) {
        burgerBtn.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });
    }

    // --- 2. Gestion du Menu de Filtres (Mobile) ---
    const btnToggleFiltres = document.getElementById('btn-toggle-filtres');
    const btnCloseFiltres = document.getElementById('btn-close-filtres');
    const zoneFiltres = document.getElementById('zone-filtres');

    // Ouvrir les filtres
    if(btnToggleFiltres && zoneFiltres) {
        btnToggleFiltres.addEventListener('click', () => {
            zoneFiltres.classList.add('active');
            document.body.style.overflow = 'hidden'; // Empêche le fond de scroller
        });
    }

    // Fermer les filtres
    if(btnCloseFiltres && zoneFiltres) {
        btnCloseFiltres.addEventListener('click', (e) => {
            e.preventDefault(); // Empêche le bouton de recharger la page
            zoneFiltres.classList.remove('active');
            document.body.style.overflow = 'auto'; // Réactive le scroll
        });
    }
});