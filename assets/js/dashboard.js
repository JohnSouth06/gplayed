const RAWG_API_KEY = 'ae5a8fa57c704860b5010b3787ab78ef';

// --- CONFIGURATION ICONES (Material Icons Codepoints) ---
const statusConfig = {
    'not_started': { label: 'Non commencé', class: 'bg-secondary', icon: '&#xe837;' },
    'playing': { label: 'En cours', class: 'bg-info', icon: '&#xea5b;' },      // sports_esports
    'finished': { label: 'Terminé', class: 'bg-success', icon: '&#xe86c;' },  // check_circle
    'completed': { label: 'Platiné', class: 'bg-warning text-dark', icon: '&#xea23;' }, // emoji_events
    'dropped': { label: 'Abandon', class: 'bg-danger', icon: '&#xe14b;' },    // block
    'wishlist': { label: 'Souhait', class: 'bg-primary text-white', icon: '&#xe8b1;' } // redeem
};

const platformIcons = { 'PS5': 'svg-icon ps-icon', 'PS4': 'svg-icon ps-icon', 'Xbox Series': 'svg-icon xbox-icon', 'Xbox': 'svg-icon xbox-icon', 'Switch': 'svg-icon switch-icon', 'PC': 'svg-icon pc-icon' };

// --- CONFIGURATION INFINITE SCROLL ---
let currentView = localStorage.getItem('viewMode') || 'grid';
let processedGamesCache = []; 
let displayedCount = 0;       
const batchSize = 12;         
let observer;                 
let modal;

// Variable pour le délai de recherche (Debounce)
let searchTimeout;

document.addEventListener('DOMContentLoaded', () => {
    modal = new bootstrap.Modal(document.getElementById('gameModal'));
    initViewButtons();
    setupIntersectionObserver();
    
    // --- MISE EN PLACE DE LA RECHERCHE SERVEUR ---
    const searchInput = document.getElementById('internalSearchInput');
    if (searchInput) {
        // On désactive l'événement inline (celui dans le HTML) pour éviter les conflits
        searchInput.onkeyup = null;
        
        // On écoute l'événement 'input' pour une meilleure réactivité
        searchInput.addEventListener('input', (e) => {
            handleServerSearch(e.target.value);
        });
    }

    updateView(); 
});

// TROPHY SELECT
document.querySelectorAll('.dropdown-item').forEach(item => {
    item.addEventListener('click', function(e) {
        e.preventDefault();
        const selectedValue = this.getAttribute('data-value');
        const selectedHtml = this.innerHTML;

        const btn = document.getElementById('trophyDropdownBtn');
        // On cible le span pour ne pas écraser la flèche dropdown si elle est générée par CSS, 
        // mais ici Bootstrap la gère via ::after, donc on peut modifier le contenu du bouton ou de son span.
        // Pour être sûr de garder la structure propre :
        btn.querySelector('span').innerHTML = selectedHtml;

        document.getElementById('newTrophyType').value = selectedValue;
    });
});


// --- GESTION RECHERCHE SERVEUR (AJAX) ---
function handleServerSearch(query) {
    // Annule l'appel précédent si l'utilisateur tape encore
    clearTimeout(searchTimeout);

    // Attend 300ms avant d'envoyer la requête au serveur
    searchTimeout = setTimeout(() => {
        fetchGamesFromServer(query);
    }, 300);
}

async function fetchGamesFromServer(query) {
    toggleLoader(true);
    try {
        // Appel à l'API PHP
        const response = await fetch(`index.php?action=api_search&q=${encodeURIComponent(query)}`);
        
        if (!response.ok) throw new Error('Erreur réseau');
        
        const games = await response.json();
        
        // On remplace les données locales par celles reçues du serveur
        localGames = games; 
        
        // On met à jour l'affichage
        updateView(); 
    } catch (error) {
        console.error('Erreur lors de la recherche:', error);
    } finally {
        toggleLoader(false);
    }
}

// --- SETUP OBSERVER ---
function setupIntersectionObserver() {
    const options = { root: null, rootMargin: '0px', threshold: 0.1 };
    observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                loadMoreGames();
            }
        });
    }, options);
    const sentinel = document.getElementById('scrollSentinel');
    if (sentinel) observer.observe(sentinel);
}

// --- MOTEUR DE TRI ET FILTRE (Modifié pour la recherche serveur) ---
function getProcessedGames() {
    const platformFilter = document.getElementById('filterPlatform').value;
    const statusFilter = document.getElementById('filterStatus').value;
    const sortType = document.getElementById('sortSelect').value;
    
    // NOTE : On ne filtre plus le texte ici car le serveur l'a déjà fait (dans localGames).
    // On garde uniquement les filtres "secondaires" (dropdowns) qui s'appliquent sur les résultats.

    // 1. Filtrage (Dropdowns uniquement)
    let filtered = localGames.filter(g => {
        if (platformFilter !== 'all') {
            if (g.platform === 'Multiplateforme') return true; 
            if (!g.platform.includes(platformFilter)) return false;
        }
        if (statusFilter !== 'all' && g.status !== statusFilter) return false;
        return true;
    });

    // 2. Tri
    filtered.sort((a, b) => {
        const valA = (key) => a[key] || 0;
        const valB = (key) => b[key] || 0;

        switch(sortType) {
            case 'date_desc': return new Date(b.created_at) - new Date(a.created_at);
            case 'alpha_asc': return a.title.localeCompare(b.title);
            case 'rating_desc': return valB('user_rating') - valA('user_rating');
            case 'status_asc': return a.status.localeCompare(b.status);
            case 'platform_asc': return a.platform.localeCompare(b.platform);
            default: return new Date(b.created_at) - new Date(a.created_at);
        }
    });

    return filtered;
}

// --- RENDU : INITIALISATION ---
function updateView() {
    const container = document.getElementById('gamesContainer');
    container.innerHTML = '';
    processedGamesCache = getProcessedGames();
    displayedCount = 0;

    if (localGames.length === 0) {
        // Cas où le serveur ne renvoie rien (Recherche vide)
        container.innerHTML = '<div class="col-12 text-center text-muted py-5"><i class="material-icons-outlined icon-xl opacity-25 mb-3">&#xe811;</i><p>Aucun jeu trouvé.</p></div>';
        toggleLoader(false);
        return;
    }

    if (processedGamesCache.length === 0) {
        // Cas où il y a des résultats de recherche, mais les filtres dropdown masquent tout
        container.innerHTML = `
            <div class="col-12 text-center py-5">
                <div class="mb-3 text-secondary opacity-25"><i class="material-icons-outlined icon-xl">&#xea76;</i></div>
                <h5 class="text-secondary fw-bold">Aucun résultat</h5>
                <p class="text-muted small">Essayez de modifier vos filtres (Plateforme/Statut).</p>
            </div>`;
        toggleLoader(false);
        return;
    }

    loadMoreGames(); 
}

// --- RENDU : CHARGEMENT PROGRESSIF ---
function loadMoreGames() {
    if (displayedCount >= processedGamesCache.length) {
        toggleLoader(false);
        return;
    }
    toggleLoader(true);

    requestAnimationFrame(() => {
        const container = document.getElementById('gamesContainer');
        const nextBatch = processedGamesCache.slice(displayedCount, displayedCount + batchSize);

        if (currentView === 'grid') {
            let html = '';
            nextBatch.forEach(g => { html += generateGridCard(g); });
            container.insertAdjacentHTML('beforeend', html);
        } else {
            let tbody = container.querySelector('tbody');
            if (!tbody) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden" style="background-color: var(--bs-body-bg);">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-body-tertiary text-secondary small text-uppercase fw-bold">
                                        <tr>
                                            <th class="ps-4 py-3">Jeu</th>
                                            <th class="d-none d-sm-table-cell">Plateforme</th>
                                            <th class="d-none d-lg-table-cell">Prix</th>
                                            <th class="d-none d-lg-table-cell">Statut</th>
                                            <th class="d-none d-lg-table-cell">Note</th>
                                            <th class="text-end pe-4">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>`;
                tbody = container.querySelector('tbody');
            }
            let rows = '';
            nextBatch.forEach(g => { rows += generateListRow(g); });
            tbody.insertAdjacentHTML('beforeend', rows);
        }

        displayedCount += nextBatch.length;
        if (displayedCount >= processedGamesCache.length) toggleLoader(false);
    });
}

// --- GENERATEURS HTML ---

function getNeonColor(rgbString, opacity = 1) {
    if (!rgbString || rgbString === 'null') return `rgba(255, 255, 255, ${opacity})`;
    const match = rgbString.match(/\d+/g);
    if (!match || match.length < 3) return `rgba(255, 255, 255, ${opacity})`;
    
    let [r, g, b] = match.map(Number);
    r /= 255; g /= 255; b /= 255;
    const max = Math.max(r, g, b), min = Math.min(r, g, b);
    let h, s, l = (max + min) / 2;

    if (max === min) {
        h = s = 0; 
    } else {
        const d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
        switch (max) {
            case r: h = (g - b) / d + (g < b ? 6 : 0); break;
            case g: h = (b - r) / d + 2; break;
            case b: h = (r - g) / d + 4; break;
        }
        h /= 6;
    }

    if (s > 0.1) s = Math.max(s, 0.85); 
    l = Math.max(0.50, Math.min(0.75, l));

    h = Math.round(h * 360);
    s = Math.round(s * 100);
    l = Math.round(l * 100);

    return `hsla(${h}, ${s}%, ${l}%, ${opacity})`;
}

function generateGridCard(g) {
    const s = statusConfig[g.status] || statusConfig['playing'];
    const img = g.image_url ? g.image_url : '';
    
    const formatIcon = (g.format === 'physical') 
        ? '<i class="material-icons-outlined icon-sm text-secondary me-1" title="Physique">&#xe1a1;</i>' 
        : '<i class="material-icons-outlined icon-sm text-secondary me-1" title="Digital">&#xe3dd;</i>';
    
    const shadowColor = getNeonColor(g.dominant_color, 0.4);
    const borderColor = getNeonColor(g.dominant_color, 0.5);

    let metaHtml = '';
    
    let platIconHtml = '';
    if (g.platform && g.platform.includes(',')) {
        platIconHtml = '<i class="material-icons-outlined icon-sm me-1">&#xe53b;</i>';
    } else if (platformIcons[g.platform]) {
        platIconHtml = `<i class="${platformIcons[g.platform]} me-1"></i>`;
    } else {
        platIconHtml = '<i class="material-icons-outlined icon-sm me-1">&#xea5b;</i>';
    }
    
    metaHtml += `<span class="meta-tag">${platIconHtml}${g.platform}</span>`;

    if (g.metacritic_score > 0) {
        let metaIcon = g.metacritic_score >= 75 ? 'text-success' : (g.metacritic_score >= 50 ? 'text-warning' : 'text-danger');
        metaHtml += `<span class="meta-tag" title="Metascore"><i class="svg-icon metacritic-icon ${metaIcon} me-1"></i>${g.metacritic_score}</span>`;
    }
    
    if (g.estimated_price > 0) {
        metaHtml += `<span class="meta-tag text-primary bg-primary-subtle border-primary-subtle"><i class="material-icons-outlined icon-sm me-1">&#xe54e;</i>${g.estimated_price}€</span>`;
    }

    const imagePlaceholder = `<div class="position-absolute top-0 w-100 h-100 d-flex align-items-center justify-content-center bg-body-tertiary"><i class="material-icons-outlined icon-xl text-secondary opacity-25">&#xea5b;</i></div>`;

    return `
    <div class="col-sm-6 col-lg-4 col-xl-3 animate-in">
        <div class="game-card-modern"
             onmouseover="this.style.boxShadow='0 25px 60px -12px ${shadowColor}'; this.style.borderColor='${borderColor}'"
             onmouseout="this.style.boxShadow=''; this.style.borderColor='rgba(0,0,0,0.05)'">
            
            <div class="card-cover-container">
                ${img ? `<img src="${img}" class="card-cover-img" loading="lazy">` : imagePlaceholder}
                <span class="status-badge-float"><i class="material-icons-outlined icon-sm me-1">${s.icon}</i>${s.label}</span>
            </div>
            
            <div class="card-content-area">
                <div class="d-flex justify-content-between align-items-start">
                    <h6 class="game-title text-truncate" title="${g.title}">${g.title}</h6>
                    ${g.user_rating > 0 ? `<div class="fw-bold text-warning d-flex align-items-center small"><i class="material-icons-outlined icon-sm filled-icon me-1">&#xe838;</i>${g.user_rating}</div>` : ''} 
                </div>
                
                <div class="meta-badges">
                    ${metaHtml}
                </div>

                <div class="card-actions-wrapper">
                    <div class="small text-muted text-truncate me-2 fw-medium" style="max-width: 120px; font-size: 0.8rem;">
                        ${formatIcon} ${g.genres || '<span class="opacity-50">Inconnu</span>'}
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn-icon-action" onclick='edit(${g.id})' title="Modifier"><i class="material-icons-outlined icon-md">&#xe3c9;</i></button>
                        <a href="index.php?action=delete&id=${g.id}" class="btn-icon-action text-danger" onclick="return confirm('Supprimer ?')" title="Supprimer"><i class="material-icons-outlined icon-md">&#xe872;</i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
}

function generateListRow(g) {
    const s = statusConfig[g.status] || statusConfig['playing'];
    const img = g.image_url ? 
        `<img src="${g.image_url}" class="rounded-3 shadow-sm object-fit-cover" style="width:48px;height:48px;">` : 
        `<div class="rounded-3 bg-body-secondary d-flex align-items-center justify-content-center" style="width:48px;height:48px"><i class="material-icons-outlined text-secondary icon-md">&#xea5b;</i></div>`;
    
    const price = g.estimated_price > 0 ? `<span class="badge bg-body-secondary text-body border">${g.estimated_price}€</span>` : '<span class="text-muted opacity-25">-</span>';
    
    let platIconHtml = '';
    if (g.platform && g.platform.includes(',')) {
        platIconHtml = '<i class="material-icons-outlined icon-sm me-1">&#xe53b;</i>';
    } else if (platformIcons[g.platform]) {
        platIconHtml = `<i class="${platformIcons[g.platform]} me-1"></i>`;
    } else {
        platIconHtml = '<i class="material-icons-outlined icon-sm me-1">&#xe338;</i>';
    }

    return `
    <tr>
        <td class="ps-4">
            <div class="d-flex align-items-center gap-3">
                ${img}
                <div>
                    <div class="fw-bold text-body">${g.title}</div>
                    <div class="small text-secondary">${g.genres || ''}</div>
                </div>
            </div>
        </td>
        <td class="d-none d-sm-table-cell"><span class="meta-tag border">${platIconHtml}${g.platform}</span></td>
        <td class="d-none d-lg-table-cell">${price}</td>
        <td class="d-none d-lg-table-cell"><span class="badge ${s.class} rounded-pill bg-opacity-75"><i class="material-icons-outlined icon-sm me-1">${s.icon}</i>${s.label}</span></td>
        <td class="d-none d-lg-table-cell fw-bold text-warning"><i class="material-icons-outlined icon-sm filled-icon me-1">&#xe838;</i>${g.user_rating || '<span class="text-muted opacity-25">-</span>'}</td>
        <td class="text-end pe-4">
            <button class="btn-icon-action" onclick='edit(${g.id})' title="Modifier"><i class="material-icons-outlined icon-md">&#xe3c9;</i></button>
            <a href="index.php?action=delete&id=${g.id}" class="btn-action btn-icon-action btn-light text-danger" onclick="return confirm('Supprimer ?')" title="Supprimer"><i class="material-icons-outlined icon-md">&#xe872;</i></a>
        </td>
    </tr>`;
}

// --- FONCTIONS UTILITAIRES ---
function toggleLoader(show) { const l = document.getElementById('scrollLoader'); if (show && l) l.classList.remove('d-none'); else if (l) l.classList.add('d-none'); }
function searchPrice() { const title = document.getElementById('gameTitle').value; const platform = document.getElementById('gamePlatform').value; if (title) { const query = encodeURIComponent(`${title} ${platform}`); const w = 1000; const h = 600; const left = (screen.width / 2) - (w / 2); const top = (screen.height / 2) - (h / 2); window.open(`https://www.ebay.fr/sch/i.html?_nkw=${query}&_sacat=139973`, 'PriceCheck', `width=${w},height=${h},top=${top},left=${left}`); } else { alert("Veuillez d'abord saisir un titre de jeu."); } }
function handleEnter(e) { if (e.key === 'Enter') searchRawg(); }
function closeSearch() { document.getElementById('rawgContainer').classList.add('d-none'); document.getElementById('rawgSearchInput').value = ''; }
function setView(v) { currentView = v; localStorage.setItem('viewMode', v); initViewButtons(); updateView(); }
function initViewButtons() { document.getElementById('btnGrid').className = currentView === 'grid' ? 'btn btn-sm btn-light rounded-2 active border-0' : 'btn btn-sm btn-transparent rounded-2 text-secondary'; document.getElementById('btnList').className = currentView === 'list' ? 'btn btn-sm btn-light rounded-2 active border-0' : 'btn btn-sm btn-transparent rounded-2 text-secondary'; }
function previewFile(input) { if (input.files && input.files[0]) { var reader = new FileReader(); reader.onload = function (e) { document.getElementById('previewImg').src = e.target.result; document.getElementById('previewImg').classList.remove('d-none'); document.getElementById('uploadPlaceholder').classList.add('d-none'); }; reader.readAsDataURL(input.files[0]); } }

// --- MULTIPLATEFORME ---
function toggleCustomPlatform() { const select = document.getElementById('gamePlatform'); const container = document.getElementById('multiPlatformContainer'); const hiddenInput = document.getElementById('gamePlatformCustom'); if (select.value === 'Multiplateforme') { container.classList.remove('d-none'); if (document.getElementById('platformInputsList').children.length === 0) addPlatformInput(); } else { container.classList.add('d-none'); hiddenInput.value = ''; } }
function addPlatformInput(value = '') { const list = document.getElementById('platformInputsList'); const div = document.createElement('div'); div.className = 'input-group input-group-sm mb-1'; div.innerHTML = `<input type="text" class="form-control rounded-start-2 border-end-0 bg-white" value="${value}" placeholder="Nom..." oninput="updateHiddenPlatformInput()"><button type="button" class="btn btn-outline-danger border-start-0 rounded-end-2 bg-white text-danger" onclick="this.parentElement.remove(); updateHiddenPlatformInput()"><i class="material-icons-outlined icon-sm">&#xe5cd;</i></button>`; list.appendChild(div); updateHiddenPlatformInput(); }
function updateHiddenPlatformInput() { const inputs = document.querySelectorAll('#platformInputsList input'); const values = Array.from(inputs).map(i => i.value.trim()).filter(v => v !== ''); document.getElementById('gamePlatformCustom').value = values.join(', '); }

// --- MODALES & TROPHÉES ---
function edit(id) { openModal(localGames.find(g => g.id == id)); }
function openModal(g = null) {
    if (!modal) modal = new bootstrap.Modal(document.getElementById('gameModal'));
    document.getElementById('gameId').value = g ? g.id : '';
    document.getElementById('gameTitle').value = g ? g.title : '';
    const prev = document.getElementById('previewImg');
    const holder = document.getElementById('uploadPlaceholder');
    document.getElementById('gameImageHidden').value = g ? (g.image_url || '') : '';
    if (g && g.image_url) { prev.src = g.image_url; prev.classList.remove('d-none'); holder.classList.add('d-none'); } else { prev.classList.add('d-none'); holder.classList.remove('d-none'); }
    document.getElementById('gamePrice').value = g ? (g.estimated_price || '') : '';
    const standardPlatforms = ['PS5', 'PS4', 'Xbox Series', 'Switch', 'PC'];
    const platformSelect = document.getElementById('gamePlatform');
    const listContainer = document.getElementById('platformInputsList');
    listContainer.innerHTML = '';
    if (g && g.platform) { if (standardPlatforms.includes(g.platform)) { platformSelect.value = g.platform; toggleCustomPlatform(); } else { platformSelect.value = 'Multiplateforme'; toggleCustomPlatform(); const parts = g.platform.split(',').map(s => s.trim()); parts.forEach(p => addPlatformInput(p)); } } else { platformSelect.value = 'PS5'; toggleCustomPlatform(); }
    checkPsnVisibility();
    if (g && g.format === 'digital') document.getElementById('fmtDigital').checked = true; else document.getElementById('fmtPhysical').checked = true;
    document.getElementById('gameStatus').value = g ? (g.status || 'not_started') : 'not_started';
    document.getElementById('gameDate').value = g ? g.release_date : '';
    document.getElementById('gameMeta').value = g ? g.metacritic_score : '';
    document.getElementById('gameRating').value = g ? (g.user_rating || 5) : 5;
    document.getElementById('gameComment').value = g ? g.comment : '';
    document.getElementById('gameDesc').value = g ? g.description : '';
    document.getElementById('gameGenres').value = g ? g.genres : '';
    if (g && g.id) loadTrophies(g.id);
    modal.show();
}

function checkPsnVisibility() { const plat = document.getElementById('gamePlatform').value; const tabs = document.getElementById('modalTabs'); if (plat.includes('PS') || plat.includes('PlayStation')) { tabs.style.display = 'flex'; } else { tabs.style.display = 'none'; const firstTabEl = document.querySelector('#modalTabs li:first-child button') || document.querySelector('#modalTabs li:first-child a'); if (firstTabEl) { const firstTab = new bootstrap.Tab(firstTabEl); firstTab.show(); } } }

// Trophies - CLASSES AJOUTEES: icon-sm
async function loadTrophies(gameId) { const list = document.getElementById('trophiesList'); list.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>'; try { const res = await fetch(`index.php?action=api_get_trophies&game_id=${gameId}`); const data = await res.json(); const pct = data.progress.percent; document.getElementById('trophyProgressBar').style.width = pct + '%'; document.getElementById('trophyProgressText').innerText = pct + '%'; list.innerHTML = ''; if (data.trophies.length === 0) { list.innerHTML = '<div class="text-center text-muted small py-3">Aucun trophée ajouté.</div>'; return; } data.trophies.forEach(t => { const colorClass = `trophy-${t.type}`; const icon = t.type === 'platinum' ? '<img src="../assets/images/platinum.png" class="trophy-icon d-table-cell me-2">' : (t.type === 'gold' ? '<img src="../assets/images/gold.png" class="trophy-icon d-table-cell me-2">' : (t.type === 'silver' ? '<img src="../assets/images/silver.png" class="trophy-icon d-table-cell me-2">' : '<img src="../assets/images/bronze.png" class="trophy-icon d-table-cell me-2">')); const isObtained = t.is_obtained == 1; const gameTitle = document.getElementById('gameTitle').value; const searchUrl = `https://www.google.com/search?q=${encodeURIComponent(gameTitle + ' trophée ' + t.title + ' guide')}`; list.innerHTML += `<div class="d-flex align-items-center p-2 mb-2 bg-body-tertiary rounded trophy-item ${colorClass}"><div class="trophy-icon fs-5">${icon}</div><div class="flex-grow-1 ms-2" style="cursor:pointer" onclick="toggleTrophy(${t.id})"><div class="fw-bold small ${isObtained ? 'text-decoration-line-through text-muted' : ''}">${t.title}</div></div><div class="d-flex gap-2"><a href="${searchUrl}" target="_blank" class="btn btn-sm btn-link text-info p-0" title="Chercher solution"><i class="material-icons-outlined icon-sm">&#xe8b6;</i></a><button class="btn btn-sm btn-link text-danger p-0" onclick="deleteTrophy(${t.id})"><i class="material-icons-outlined icon-sm">&#xe872;</i></button></div></div>`; }); } catch (e) { list.innerHTML = 'Erreur chargement.'; } }
async function addTrophy() { const gameId = document.getElementById('gameId').value; if (!gameId) { alert("Veuillez d'abord sauvegarder le jeu."); return; } const title = document.getElementById('newTrophyTitle').value; const type = document.getElementById('newTrophyType').value; if (!title) return; const formData = new FormData(); formData.append('game_id', gameId); formData.append('title', title); formData.append('type', type); formData.append('description', ''); await fetch('index.php?action=api_add_trophy', { method: 'POST', body: formData }); document.getElementById('newTrophyTitle').value = ''; loadTrophies(gameId); }
async function toggleTrophy(id) { await fetch(`index.php?action=api_toggle_trophy&id=${id}`); loadTrophies(document.getElementById('gameId').value); }
async function deleteTrophy(id) { if (!confirm('Supprimer ?')) return; await fetch(`index.php?action=api_delete_trophy&id=${id}`); loadTrophies(document.getElementById('gameId').value); }

// --- RAWG ---
async function searchRawg() { const q = document.getElementById('rawgSearchInput').value; if (!q) return; document.getElementById('rawgContainer').classList.remove('d-none'); document.getElementById('rawgLoading').classList.remove('d-none'); try { const res = await fetch(`https://api.rawg.io/api/games?key=${RAWG_API_KEY}&search=${encodeURIComponent(q)}&page_size=5`); const data = await res.json(); let html = ''; data.results.forEach(g => { html += `<div class="card border-0 shadow-sm flex-shrink-0 bg-body-tertiary" style="width: 160px; cursor: pointer; overflow: hidden; border-radius: 12px;" onclick="fetchGameDetails(${g.id})"><img src="${g.background_image || ''}" style="height:100px; width: 100%; object-fit:cover"><div class="p-2 text-center"><small class="fw-bold d-block text-truncate text-body">${g.name}</small><small class="text-muted" style="font-size: 0.7rem;">${g.released ? g.released.substring(0, 4) : ''}</small></div></div>`; }); document.getElementById('rawgResults').innerHTML = html; } catch (e) { } finally { document.getElementById('rawgLoading').classList.add('d-none'); } }
async function fetchGameDetails(id) { document.getElementById('rawgLoading').classList.remove('d-none'); try { const res = await fetch(`https://api.rawg.io/api/games/${id}?key=${RAWG_API_KEY}`); const g = await res.json(); openModal(); document.getElementById('gameTitle').value = g.name; document.getElementById('gameDate').value = g.released; document.getElementById('gameMeta').value = g.metacritic; document.getElementById('gameImageHidden').value = g.background_image; if (g.background_image) { document.getElementById('previewImg').src = g.background_image; document.getElementById('previewImg').classList.remove('d-none'); document.getElementById('uploadPlaceholder').classList.add('d-none'); } document.getElementById('gameDesc').value = g.description_raw; document.getElementById('gameGenres').value = g.genres ? g.genres.map(x => x.name).join(', ') : ''; } catch (e) { alert("Erreur import."); } finally { document.getElementById('rawgLoading').classList.add('d-none'); } }