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
    if(t) t.innerHTML = isDark ? '<i class="fas fa-sun"></i>Thème' : '<i class="fas fa-moon"></i>Thème'; 
}