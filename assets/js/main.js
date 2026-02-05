// --- Gestionnaire Global (Loader, Sidebar, Toast, Thème) ---

(function() {
    // Initialisation du Loader
    const loader = document.getElementById('app-loader');
    // On récupère les variables globales définies dans le layout avant l'inclusion de ce script
    const forceAnimation = window.forceLoader || false;
    const hasVisited = sessionStorage.getItem('app_visited');

    if (!forceAnimation && hasVisited) {
        if(loader) {
            loader.style.display = 'none';
            document.body.style.overflow = 'auto'; 
        }
    } else {
        sessionStorage.setItem('app_visited', 'true');
        window.addEventListener('load', function() {
            setTimeout(function() {
                if(loader) {
                    loader.style.opacity = '0';
                    loader.style.visibility = 'hidden';
                }
                document.body.style.overflow = 'auto';
            }, 800);
        });
    }
})();

// Toggle Sidebar Mobile
function toggleSidebar() { 
    document.getElementById('sidebar').classList.toggle('show'); 
    document.getElementById('sidebarOverlay').classList.toggle('show'); 
}

// Logique de gestion des Cookies
document.addEventListener('DOMContentLoaded', function() {
    const consent = localStorage.getItem('gplayed_cookie_consent');
    const banner = document.getElementById('cookieConsentBanner');
    
    // Si aucun choix n'a été fait, on affiche la bannière
    if (consent === null) {
        banner.classList.remove('d-none');
    }
});

function handleCookieConsent(accepted) {
    const banner = document.getElementById('cookieConsentBanner');
    
    // On enregistre le choix (true ou false) dans le localStorage
    // Cela persiste même si on ferme le navigateur
    localStorage.setItem('gplayed_cookie_consent', accepted ? 'true' : 'false');
    
    // On masque la bannière avec une petite animation
    banner.style.transition = 'opacity 0.5s';
    banner.style.opacity = '0';
    setTimeout(() => {
        banner.classList.add('d-none');
    }, 500);
}

// Initialisation au chargement
document.addEventListener('DOMContentLoaded', () => { 
    initTheme(); 
    
    // Gestion des Toasts (Notifications)
    if(window.toastData) { 
        document.getElementById('toastMessage').innerText = window.toastData.msg; 
        const toastEl = document.getElementById('liveToast'); 
        if(window.toastData.type === 'danger') document.querySelector('.toast-header').classList.add('text-danger'); 
        new bootstrap.Toast(toastEl).show(); 
    } 
});

// Gestion du Thème (Dark/Light)
function initTheme() { 
    const t = document.getElementById('themeToggle'); 
    if(!t) return; 
    
    const savedTheme = localStorage.getItem('theme'); 
    if(savedTheme) { 
        document.documentElement.setAttribute('data-bs-theme', savedTheme); 
        updateThemeIcon(savedTheme === 'dark'); 
    } 
    
    t.onclick = (e) => { 
        e.preventDefault(); 
        e.stopPropagation(); 
        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark'; 
        const newTheme = isDark ? 'light' : 'dark'; 
        document.documentElement.setAttribute('data-bs-theme', newTheme); 
        localStorage.setItem('theme', newTheme); 
        updateThemeIcon(!isDark); 
    }; 
}

function updateThemeIcon(isDark) { 
    const t = document.getElementById('themeToggle'); 
    if(t) t.innerHTML = isDark ? '<i class="material-icons align-middle fs-5">&#xe81a;</i>Thème' : '<i class="material-icons align-middle fs-5">&#xef44;</i>Thème'; 
}