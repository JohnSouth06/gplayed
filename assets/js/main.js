(function () {
    const loader = document.getElementById('app-loader');
    const forceAnimation = window.forceLoader || false;
    const hasVisited = sessionStorage.getItem('app_visited');

    if (!forceAnimation && hasVisited) {
        if (loader) {
            loader.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    } else {
        sessionStorage.setItem('app_visited', 'true');
        window.addEventListener('load', function () {
            setTimeout(function () {
                if (loader) {
                    loader.style.opacity = '0';
                    loader.style.visibility = 'hidden';
                }
                document.body.style.overflow = 'auto';
            }, 800);
        });
    }
})();

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}

document.addEventListener('DOMContentLoaded', () => {
    initTheme();

    if (window.toastData) {
        document.getElementById('toastMessage').innerText = window.toastData.msg;
        const toastEl = document.getElementById('liveToast');
        if (window.toastData.type === 'danger') document.querySelector('.toast-header').classList.add('text-danger');
        new bootstrap.Toast(toastEl).show();
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const consent = localStorage.getItem('gplayed_cookie_consent');
    const banner = document.getElementById('cookieConsentBanner');
    if (consent === null) {
        banner.classList.remove('d-none');
    }
});

function handleCookieConsent(accepted) {
    const banner = document.getElementById('cookieConsentBanner');

    localStorage.setItem('gplayed_cookie_consent', accepted ? 'true' : 'false');

    banner.style.transition = 'opacity 0.5s';
    banner.style.opacity = '0';
    setTimeout(() => {
        banner.classList.add('d-none');
    }, 500);
}

function initTheme() {
    const t = document.getElementById('themeToggle');
    if (!t) return;

    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
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
    if (t) t.innerHTML = isDark ? `<i class="material-icons align-middle fs-5">&#xe81a;</i>${LANG.js_theme_label}` : `<i class="material-icons align-middle fs-5">&#xef44;</i>${LANG.js_theme_label}`;
}

// WHeel
let rouletteModalInstance;
let rouletteGames = [];
let selectedGameId = null;
const itemHeight = 50; // Hauteur d'une ligne en pixels

function populateRoulette(gamesList) {
    const container = document.getElementById('rouletteItems');
    container.innerHTML = '';
    
    gamesList.forEach(game => {
        const item = document.createElement('div');
        item.style.height = `${itemHeight}px`;
        item.style.lineHeight = `${itemHeight}px`;
        item.style.fontSize = '1.2rem';
        item.style.fontWeight = '600';
        item.className = 'text-truncate px-3';
        item.innerText = game.title;
        container.appendChild(item);
    });
}

function openRouletteModal() {
    if (!rouletteModalInstance) {
        rouletteModalInstance = new bootstrap.Modal(document.getElementById('rouletteModal'));
    }
    
    document.getElementById('rouletteResult').classList.add('d-none');
    document.getElementById('btnStartRoulette').classList.remove('d-none');
    document.getElementById('rouletteItems').style.transition = 'none';
    
    // On définit le centrage initial (94px est la hauteur de 100px moins les 6px de bordure)
    const containerInnerHeight = 94; 
    const initialTop = (containerInnerHeight / 2) - (itemHeight / 2);
    document.getElementById('rouletteItems').style.top = `${initialTop}px`;
    
    document.getElementById('rouletteItems').innerHTML = '<div style="height: 50px; line-height: 50px; color: gray;">Chargement...</div>';
    
    fetch('/?action=api_roulette_games')
                .then(res => res.json())
                .then(data => {
                    if (data.games && data.games.length > 0) {
                        rouletteGames = data.games;

                        populateRoulette([...rouletteGames, ...rouletteGames, ...rouletteGames, ...rouletteGames, ...rouletteGames]);
                        rouletteModalInstance.show();
            } else {
                alert("<?= __('wheel_no_games') ?>");
            }
        })
        .catch(err => {
            console.error("Détail de l'erreur :", err);
            alert(err.message);
        });
}

function spinRoulette() {
    if (rouletteGames.length === 0) return;

    document.getElementById('btnStartRoulette').classList.add('d-none');
    document.getElementById('rouletteResult').classList.add('d-none');
    
    const container = document.getElementById('rouletteItems');
    const rouletteContainer = document.getElementById('rouletteContainer');
    
    // Ici la modale est visible, on peut calculer la vraie taille intérieure
    const containerInnerHeight = rouletteContainer.clientHeight; 
    
    container.style.transition = 'none';
    
    // On repositionne au centre exact avant de lancer
    const initialTop = (containerInnerHeight / 2) - (itemHeight / 2);
    container.style.top = `${initialTop}px`; 
    
    // Choix du gagnant (index basé sur la liste originale)
    const winnerIndex = Math.floor(Math.random() * rouletteGames.length);
    selectedGameId = rouletteGames[winnerIndex].id;
    
    // L'astuce : on arrête la roulette sur le gagnant dans la 4ème copie
    const targetIndex = (rouletteGames.length * 3) + winnerIndex;
    
    // Calcul du décalage avec la vraie hauteur intérieure
    const offset = -(targetIndex * itemHeight) + (containerInnerHeight / 2) - (itemHeight / 2);

    // Petit délai pour laisser le navigateur réinitialiser la position CSS
    setTimeout(() => {
        container.style.transition = 'top 3s cubic-bezier(0.15, 0.85, 0.35, 1)';
        container.style.top = `${offset}px`;
        
        // Afficher le résultat à la fin de l'animation
        setTimeout(() => {
            const winner = rouletteGames[winnerIndex];
            
            // 1. Mettre à jour le titre
            document.getElementById('winnerTitle').innerText = winner.title;
            
            // 2. Mettre à jour et afficher l'image
            const winnerImg = document.getElementById('winnerImage');
            if (winner.image_url) {
                winnerImg.src = winner.image_url;
                winnerImg.style.display = 'inline-block';
                
                // Réinitialiser l'animation pop
                winnerImg.classList.remove('animate-pop');
                void winnerImg.offsetWidth; 
                winnerImg.classList.add('animate-pop');

                // Ombre et bordure de couleur
                if (winner.dominant_color) {
                    winnerImg.style.border = `1px solid ${winner.dominant_color}`;
                    const shadowColor = winner.dominant_color.replace('rgb', 'rgba').replace(')', ', 0.6)');
                    winnerImg.style.boxShadow = `0 10px 30px ${shadowColor}`;
                } else {
                    winnerImg.style.border = 'none';
                    winnerImg.style.boxShadow = 'none';
                }
            } else {
                winnerImg.style.display = 'none';
            }
            
            // 3. Afficher le bloc de résultat
            document.getElementById('rouletteResult').classList.remove('d-none');
        }, 3000);
    }, 50);
}

function acceptRouletteGame() {
    if (!selectedGameId) return;
    
    // Change le bouton en chargement
    const btn = document.getElementById('btnAcceptGame');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Traitement...';
    btn.disabled = true;

    fetch('/?action=api_start_game', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ game_id: selectedGameId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert("<?= __('wheel_error') ?>");
            btn.innerHTML = "<?= __('wheel_launch') ?>";
            btn.disabled = false;
        }
    });
}