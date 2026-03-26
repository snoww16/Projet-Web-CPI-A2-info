document.addEventListener('DOMContentLoaded', function() {
    
    // --- MENU BURGER (Navigation Mobile) ---
    const burgerBtn = document.getElementById('burger-btn');
    const navMenu = document.getElementById('nav-menu');

    if (burgerBtn && navMenu) {
        burgerBtn.addEventListener('click', function(e) {
            e.stopPropagation(); // Empêche le clic de fermer immédiatement le menu
            navMenu.classList.toggle('active');
        });
    }

    // Fermer le menu si on clique ailleurs sur la page
    document.addEventListener('click', function(e) {
        if (navMenu && navMenu.classList.contains('active')) {
            if (!navMenu.contains(e.target) && e.target !== burgerBtn) {
                navMenu.classList.remove('active');
            }
        }
    });

    // --- FILTRES DE RECHERCHE (Mobile) ---
    const btnToggleFiltres = document.getElementById('btn-toggle-filtres');
    const btnCloseFiltres = document.getElementById('btn-close-filtres');
    const zoneFiltres = document.getElementById('zone-filtres');

    if (btnToggleFiltres && zoneFiltres) {
        btnToggleFiltres.addEventListener('click', function() {
            zoneFiltres.style.display = 'block';
            zoneFiltres.classList.add('active');
        });
    }

    if (btnCloseFiltres && zoneFiltres) {
        btnCloseFiltres.addEventListener('click', function() {
            zoneFiltres.style.display = 'none';
            zoneFiltres.classList.remove('active');
        });
    }
});

// --- CHANGEMENT DE COULEUR DES BOUTONS DE FICHIER (Page Postuler) ---
function updateFileLabel(inputElement) {
    const label = inputElement.previousElementSibling;
    
    if (inputElement.files && inputElement.files.length > 0) {
        const fileName = inputElement.files[0].name;
        label.style.background = '#475569'; 
        label.style.color = '#f8fafc';
        label.innerHTML = '📄 ' + fileName;
    } else {
        label.style.background = '#38bdf8';
        label.style.color = '#0f172a';
        label.innerHTML = '📁 Parcourir...';
    }
}