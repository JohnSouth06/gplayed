const RAWG_API_KEY = 'ae5a8fa57c704860b5010b3787ab78ef';
const statusConfig = {
    'playing': { label: 'En cours', class: 'bg-primary', icon: 'fa-gamepad' },
    'finished': { label: 'Terminé', class: 'bg-success', icon: 'fa-check' },
    'completed': { label: 'Platiné', class: 'bg-warning text-dark', icon: 'fa-trophy' },
    'dropped': { label: 'Abandon', class: 'bg-danger', icon: 'fa-ban' },
    'wishlist': { label: 'Souhait', class: 'bg-info text-white', icon: 'fa-gift' }
};
const platformIcons = { 'PS5': 'fab fa-playstation', 'PS4': 'fab fa-playstation', 'Xbox Series': 'fab fa-xbox', 'Xbox': 'fab fa-xbox', 'Switch': 'fas fa-gamepad', 'PC': 'fas fa-desktop' };

// --- CONFIGURATION INFINITE SCROLL ---
let currentView = localStorage.getItem('viewMode') || 'grid';
let processedGamesCache = []; // Cache des jeux filtrés
let displayedCount = 0;       // Compteur d'affichage
const batchSize = 12;         // Nombre d'éléments à charger par scroll
let observer;                 // Intersection Observer
let modal;

document.addEventListener('DOMContentLoaded', () => {
    modal = new bootstrap.Modal(document.getElementById('gameModal'));
    initViewButtons();
    setupIntersectionObserver();
    updateView(); // Premier chargement
});

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

// --- LOGIQUE METIER (FILTRES & TRI) ---
function getProcessedGames() {
    const platformFilter = document.getElementById('filterPlatform').value;
    const statusFilter = document.getElementById('filterStatus').value;
    const sortType = document.getElementById('sortSelect').value;

    // 1. Filtrage
    let filtered = localGames.filter(g => {
        if (platformFilter !== 'all' && !g.platform.includes(platformFilter) && g.platform !== 'Multiplateforme') return false;
        if (statusFilter !== 'all' && g.status !== statusFilter) return false;
        return true;
    });

    // 2. Tri
    filtered.sort((a, b) => {
        const valA = (key) => a[key] || 0;
        const valB = (key) => b[key] || 0;
        switch (sortType) {
            case 'date_desc': return new Date(b.created_at) - new Date(a.created_at);
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
        container.innerHTML = '<div class="col-12 text-center text-muted py-5"><i class="fas fa-ghost fa-3x mb-3 opacity-25"></i><p>Votre collection est vide.</p></div>';
        toggleLoader(false);
        return;
    }

    if (processedGamesCache.length === 0) {
        container.innerHTML = '<div class="col-12 text-center text-muted py-5"><i class="fas fa-filter fa-3x mb-3 opacity-25"></i><p>Aucun jeu ne correspond aux filtres.</p></div>';
        toggleLoader(false);
        return;
    }

    loadMoreGames(); // Charge le premier lot
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
            // Gestion table pour vue liste
            let tbody = container.querySelector('tbody');
            if (!tbody) {
                container.innerHTML = `<div class="col-12"><div class="card border-0 shadow-sm rounded-4 overflow-hidden"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="bg-body-secondary text-secondary small text-uppercase"><tr><th class="ps-4">Jeu</th><th>Plateforme</th><th>Prix</th><th>Statut</th><th>Note</th><th class="text-end pe-4">Actions</th></tr></thead><tbody></tbody></table></div></div></div>`;
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
function generateGridCard(g) {
    const s = statusConfig[g.status] || statusConfig['playing'];
    const img = g.image_url ? g.image_url : '';
    const formatIcon = (g.format === 'physical') ? '<i class="fas fa-box text-adaptive-muted" title="Physique"></i>' : '<i class="fas fa-cloud text-adaptive-muted" title="Digital"></i>';
    const bgColor = g.dominant_color ? g.dominant_color : 'rgb(30,30,30)';

    let metaBadge = '';
    if (g.metacritic_score > 0) {
        let metaIcon = g.metacritic_score >= 75 ? 'text-success' : (g.metacritic_score >= 50 ? 'text-warning' : 'text-danger');
        metaBadge = `<span class="badge badge-adaptive rounded-1 small fw-normal ms-2 px-2" title="Metascore"><i class="fas fa-chart-bar ${metaIcon} me-1"></i>${g.metacritic_score}</span>`;
    }
    let priceBadge = '';
    if (g.estimated_price > 0) {
        priceBadge = `<span class="badge badge-adaptive rounded-1 small fw-normal ms-2 px-2" title="Prix Estimé"><i class="fas fa-tag text-info me-1"></i>${g.estimated_price}€</span>`;
    }
    let platIconClass = platformIcons[g.platform] || 'fas fa-gamepad';
    if (g.platform.includes(',')) platIconClass = 'fas fa-layer-group';

    return `
    <div class="col-sm-6 col-lg-4 col-xl-3 animate-in">
        <div class="game-card-modern shadow-sm" style="background-color: ${bgColor};">
            <div class="card-cover-container">
                ${img ? `<img src="${img}" class="card-cover-img" loading="lazy">` : `<div class="position-absolute top-0 w-100 h-100 d-flex align-items-center justify-content-center bg-secondary-subtle text-secondary"><i class="fas fa-gamepad fa-3x opacity-50"></i></div>`}
                <span class="badge ${s.class} rounded-pill status-badge-float"><i class="fas ${s.icon} me-1"></i>${s.label}</span>
            </div>
            <div class="card-content-area">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <h6 class="fw-bold text-truncate mb-0" style="max-width: 85%;" title="${g.title}">${g.title}</h6>
                    <div class="small fw-bold text-warning d-flex align-items-center">${g.user_rating > 0 ? `<i class="fas fa-star me-1"></i>${g.user_rating}` : ''}</div>
                </div>
                <div class="mb-2 d-flex align-items-center flex-wrap gap-1">
                    <span class="badge badge-adaptive rounded-2 fw-normal text-truncate" style="max-width: 150px;"><i class="${platIconClass} me-1"></i>${g.platform}</span>
                    ${metaBadge} ${priceBadge}
                </div>
                <div class="mt-auto d-flex justify-content-between align-items-end pt-2 border-top" style="border-color: rgba(255,255,255,0.1) !important;">
                    <div class="d-flex align-items-center gap-2">${formatIcon}<small class="text-adaptive-muted text-truncate" style="max-width: 100px;">${g.genres || ''}</small></div>
                    <div class="card-actions d-flex gap-1">
                        <button class="btn btn-sm btn-action-adaptive rounded-circle" style="width:32px;height:32px" onclick='edit(${g.id})'><i class="fas fa-pen fa-xs"></i></button>
                        <a href="index.php?action=delete&id=${g.id}" class="btn btn-sm btn-action-adaptive rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px" onclick="return confirm('Supprimer ?')"><i class="fas fa-trash fa-xs"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
}

function generateListRow(g) {
    const s = statusConfig[g.status] || statusConfig['playing'];
    const img = g.image_url ? `<img src="${g.image_url}" class="rounded-3 shadow-sm" style="width:48px;height:48px;object-fit:cover;">` : `<div class="rounded-3 bg-secondary-subtle d-flex align-items-center justify-content-center" style="width:48px;height:48px"><i class="fas fa-gamepad text-secondary"></i></div>`;
    const price = g.estimated_price > 0 ? `${g.estimated_price}€` : '-';
    let platIcon = platformIcons[g.platform] || 'fas fa-gamepad';
    if (g.platform.includes(',')) platIcon = 'fas fa-layer-group';

    return `<tr>
        <td class="ps-4"><div class="d-flex align-items-center gap-3">${img}<div><div class="fw-bold text-body">${g.title}</div><div class="small text-secondary">${g.genres || ''}</div></div></div></td>
        <td><span class="badge bg-secondary-subtle text-secondary-emphasis rounded-2 fw-normal"><i class="${platIcon} me-1"></i>${g.platform}</span></td>
        <td>${price}</td>
        <td><span class="badge ${s.class} rounded-pill bg-opacity-75"><i class="fas ${s.icon} me-1"></i>${s.label}</span></td>
        <td class="text-end pe-4 fw-bold text-warning">${g.user_rating || '-'}</td>
        <td class="text-end pe-4">
            <button class="btn btn-sm btn-light rounded-circle me-1" onclick='edit(${g.id})'><i class="fas fa-pen fa-xs text-primary"></i></button>
            <a href="index.php?action=delete&id=${g.id}" class="btn btn-sm btn-light rounded-circle" onclick="return confirm('Supprimer ?')"><i class="fas fa-trash fa-xs text-danger"></i></a>
        </td>
    </tr>`;
}

// --- FONCTIONS UTILITAIRES ---
function toggleLoader(show) { const l = document.getElementById('scrollLoader'); if (show) l.classList.remove('d-none'); else l.classList.add('d-none'); }
function searchPrice() { const title = document.getElementById('gameTitle').value; const platform = document.getElementById('gamePlatform').value; if (title) { const query = encodeURIComponent(`${title} ${platform}`); const w = 1000; const h = 600; const left = (screen.width / 2) - (w / 2); const top = (screen.height / 2) - (h / 2); window.open(`https://www.ebay.fr/sch/i.html?_nkw=${query}&_sacat=139973`, 'PriceCheck', `width=${w},height=${h},top=${top},left=${left}`); } else { alert("Veuillez d'abord saisir un titre de jeu."); } }
function handleEnter(e) { if (e.key === 'Enter') searchRawg(); }
function closeSearch() { document.getElementById('rawgContainer').classList.add('d-none'); document.getElementById('rawgSearchInput').value = ''; }
function setView(v) { currentView = v; localStorage.setItem('viewMode', v); initViewButtons(); updateView(); }
function initViewButtons() { document.getElementById('btnGrid').className = currentView === 'grid' ? 'btn btn-sm btn-light rounded-2 active border-0' : 'btn btn-sm btn-transparent rounded-2 text-secondary'; document.getElementById('btnList').className = currentView === 'list' ? 'btn btn-sm btn-light rounded-2 active border-0' : 'btn btn-sm btn-transparent rounded-2 text-secondary'; }
function previewFile(input) { if (input.files && input.files[0]) { var reader = new FileReader(); reader.onload = function (e) { document.getElementById('previewImg').src = e.target.result; document.getElementById('previewImg').classList.remove('d-none'); document.getElementById('uploadPlaceholder').classList.add('d-none'); }; reader.readAsDataURL(input.files[0]); } }

// --- MULTIPLATEFORME ---
function toggleCustomPlatform() { const select = document.getElementById('gamePlatform'); const container = document.getElementById('multiPlatformContainer'); const hiddenInput = document.getElementById('gamePlatformCustom'); if (select.value === 'Multiplateforme') { container.classList.remove('d-none'); if (document.getElementById('platformInputsList').children.length === 0) addPlatformInput(); } else { container.classList.add('d-none'); hiddenInput.value = ''; } }
function addPlatformInput(value = '') { const list = document.getElementById('platformInputsList'); const div = document.createElement('div'); div.className = 'input-group input-group-sm mb-1'; div.innerHTML = `<input type="text" class="form-control rounded-start-2 border-end-0 bg-white" value="${value}" placeholder="Nom..." oninput="updateHiddenPlatformInput()"><button type="button" class="btn btn-outline-danger border-start-0 rounded-end-2 bg-white text-danger" onclick="this.parentElement.remove(); updateHiddenPlatformInput()"><i class="fas fa-times"></i></button>`; list.appendChild(div); updateHiddenPlatformInput(); }
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
    if (g && g.format === 'physical') document.getElementById('fmtPhysical').checked = true; else document.getElementById('fmtDigital').checked = true;
    document.getElementById('gameStatus').value = g ? (g.status || 'playing') : 'playing';
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
async function loadTrophies(gameId) { const list = document.getElementById('trophiesList'); list.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>'; try { const res = await fetch(`index.php?action=api_get_trophies&game_id=${gameId}`); const data = await res.json(); const pct = data.progress.percent; document.getElementById('trophyProgressBar').style.width = pct + '%'; document.getElementById('trophyProgressText').innerText = pct + '%'; list.innerHTML = ''; if (data.trophies.length === 0) { list.innerHTML = '<div class="text-center text-muted small py-3">Aucun trophée ajouté.</div>'; return; } data.trophies.forEach(t => { const colorClass = `trophy-${t.type}`; const icon = t.type === 'platinum' ? '🏆' : (t.type === 'gold' ? '🥇' : (t.type === 'silver' ? '🥈' : '🥉')); const isObtained = t.is_obtained == 1; const gameTitle = document.getElementById('gameTitle').value; const searchUrl = `https://www.google.com/search?q=${encodeURIComponent(gameTitle + ' trophée ' + t.title + ' guide')}`; list.innerHTML += `<div class="d-flex align-items-center p-2 mb-2 bg-body-tertiary rounded trophy-item ${colorClass}"><div class="trophy-icon fs-5">${icon}</div><div class="flex-grow-1 ms-2" style="cursor:pointer" onclick="toggleTrophy(${t.id})"><div class="fw-bold small ${isObtained ? 'text-decoration-line-through text-muted' : ''}">${t.title}</div></div><div class="d-flex gap-2"><a href="${searchUrl}" target="_blank" class="btn btn-sm btn-link text-info p-0" title="Chercher solution"><i class="fas fa-search"></i></a><button class="btn btn-sm btn-link text-danger p-0" onclick="deleteTrophy(${t.id})"><i class="fas fa-trash"></i></button></div></div>`; }); } catch (e) { list.innerHTML = 'Erreur chargement.'; } }
async function addTrophy() { const gameId = document.getElementById('gameId').value; if (!gameId) { alert("Veuillez d'abord sauvegarder le jeu."); return; } const title = document.getElementById('newTrophyTitle').value; const type = document.getElementById('newTrophyType').value; if (!title) return; const formData = new FormData(); formData.append('game_id', gameId); formData.append('title', title); formData.append('type', type); formData.append('description', ''); await fetch('index.php?action=api_add_trophy', { method: 'POST', body: formData }); document.getElementById('newTrophyTitle').value = ''; loadTrophies(gameId); }
async function toggleTrophy(id) { await fetch(`index.php?action=api_toggle_trophy&id=${id}`); loadTrophies(document.getElementById('gameId').value); }
async function deleteTrophy(id) { if (!confirm('Supprimer ?')) return; await fetch(`index.php?action=api_delete_trophy&id=${id}`); loadTrophies(document.getElementById('gameId').value); }

// --- RAWG ---
async function searchRawg() { const q = document.getElementById('rawgSearchInput').value; if (!q) return; document.getElementById('rawgContainer').classList.remove('d-none'); document.getElementById('rawgLoading').classList.remove('d-none'); try { const res = await fetch(`https://api.rawg.io/api/games?key=${RAWG_API_KEY}&search=${encodeURIComponent(q)}&page_size=5`); const data = await res.json(); let html = ''; data.results.forEach(g => { html += `<div class="card border-0 shadow-sm flex-shrink-0 bg-body-tertiary" style="width: 160px; cursor: pointer; overflow: hidden; border-radius: 12px;" onclick="fetchGameDetails(${g.id})"><img src="${g.background_image || ''}" style="height:100px; width: 100%; object-fit:cover"><div class="p-2 text-center"><small class="fw-bold d-block text-truncate text-body">${g.name}</small><small class="text-muted" style="font-size: 0.7rem;">${g.released ? g.released.substring(0, 4) : ''}</small></div></div>`; }); document.getElementById('rawgResults').innerHTML = html; } catch (e) { } finally { document.getElementById('rawgLoading').classList.add('d-none'); } }
async function fetchGameDetails(id) { document.getElementById('rawgLoading').classList.remove('d-none'); try { const res = await fetch(`https://api.rawg.io/api/games/${id}?key=${RAWG_API_KEY}`); const g = await res.json(); openModal(); document.getElementById('gameTitle').value = g.name; document.getElementById('gameDate').value = g.released; document.getElementById('gameMeta').value = g.metacritic; document.getElementById('gameImageHidden').value = g.background_image; if (g.background_image) { document.getElementById('previewImg').src = g.background_image; document.getElementById('previewImg').classList.remove('d-none'); document.getElementById('uploadPlaceholder').classList.add('d-none'); } document.getElementById('gameDesc').value = g.description_raw; document.getElementById('gameGenres').value = g.genres ? g.genres.map(x => x.name).join(', ') : ''; } catch (e) { alert("Erreur import."); } finally { document.getElementById('rawgLoading').classList.add('d-none'); } }