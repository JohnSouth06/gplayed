<style>
    /* Styles existants */
    .game-card-modern {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        position: relative;
    }
    .game-card-modern:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
    .card-cover-container { position: relative; padding-top: 56.25%; overflow: hidden; background-color: rgba(0,0,0,0.2); }
    .card-cover-img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
    .game-card-modern:hover .card-cover-img { transform: scale(1.05); }
    .status-badge-float { position: absolute; top: 10px; right: 10px; backdrop-filter: blur(8px); box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
    .card-content-area {
        background: linear-gradient(to bottom, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.6) 100%);
        flex-grow: 1; display: flex; flex-direction: column; padding: 1rem; color: white; text-shadow: 0 1px 2px rgba(0,0,0,0.5);
    }
    .badge-adaptive { background-color: rgba(255, 255, 255, 0.15); color: white; border: 1px solid rgba(255, 255, 255, 0.1); backdrop-filter: blur(4px); }
    .text-adaptive-muted { color: rgba(255, 255, 255, 0.7) !important; }
    .btn-action-adaptive { background-color: rgba(255, 255, 255, 0.15); color: white; border: none; }
    .btn-action-adaptive:hover { background-color: white; color: #333; }
    
    .trophy-item { border-left: 3px solid transparent; transition: all 0.2s; }
    .trophy-item:hover { background-color: rgba(255,255,255,0.05); }
    .trophy-bronze { border-left-color: #cd7f32; }
    .trophy-silver { border-left-color: #c0c0c0; }
    .trophy-gold { border-left-color: #ffd700; }
    .trophy-platinum { border-left-color: #e5e4e2; }
    .trophy-icon { width: 24px; text-align: center; }

    #statsRow { scrollbar-width: none; -ms-overflow-style: none; }
    #statsRow::-webkit-scrollbar { display: none; }
    
    .card-actions { 
        opacity: 1 !important;
        z-index: 10;
        position: relative;
    }
</style>

<!-- CALCULS PHP -->
<?php 
    $totalGames = isset($games) && is_array($games) ? count($games) : 0;
    $finishedCount = 0;
    $playingCount = 0;
    
    if ($totalGames > 0) {
        foreach ($games as $g) {
            if (isset($g['status'])) {
                if ($g['status'] == 'finished' || $g['status'] == 'completed') {
                    $finishedCount++;
                }
                if ($g['status'] == 'playing') {
                    $playingCount++;
                }
            }
        }
    }
    
    $username = $_SESSION['username'] ?? 'Gamer';
    // URL de partage
    $shareLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[SCRIPT_NAME]?action=share&user=" . $username;
?>

<!-- EN-TÊTE COMPACT -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 gap-3 pt-2">
    <div>
        <h2 class="h3 dashboard-welcome mb-1">Bonjour, <span class="text-primary"><?= htmlspecialchars($username) ?></span> 👋</h2>
        <p class="text-secondary small mb-0">Ravi de vous revoir dans votre collection.</p>
    </div>
    <div class="d-flex gap-2 overflow-x-auto pb-2 pb-md-0" style="scrollbar-width:none;">
        <div class="stat-pill"><i class="fas fa-gamepad text-primary"></i> Total <strong><?= $totalGames ?></strong></div>
        <div class="stat-pill"><i class="fas fa-check text-success"></i> Terminés <strong><?= $finishedCount ?></strong></div>
        <div class="stat-pill"><i class="fas fa-play text-warning"></i> En cours <strong><?= $playingCount ?></strong></div>
    </div>
</div>

<!-- ZONE 1 : AJOUT DE JEUX -->
<div class="card bg-body-tertiary border-0 shadow-sm rounded-4 mb-4 p-3">
    <div class="d-flex flex-column flex-md-row gap-3 align-items-center">
        <div class="flex-grow-1 w-100">
            <div class="input-group rounded-pill overflow-hidden border border-secondary border-opacity-25">
                <span class="input-group-text bg-body border-0 ps-3"><i class="fas fa-cloud-download-alt text-primary"></i></span>
                <input type="text" id="rawgSearchInput" class="form-control border-0 shadow-none bg-body" placeholder="Ajouter un nouveau jeu (Recherche RAWG)..." onkeypress="handleEnter(event)">
                <button class="btn btn-primary px-3" type="button" onclick="searchRawg()">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
        <button class="btn btn-outline-primary shadow-sm rounded-pill fw-bold px-4 w-100 w-md-auto" onclick="openModal()">
            <i class="fas fa-pen me-2"></i>Saisie Manuelle
        </button>
    </div>
    
    <div id="rawgContainer" class="mt-3 d-none">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0 text-secondary small fw-bold text-uppercase">Résultats RAWG</h6>
            <button type="button" class="btn-close btn-sm" onclick="closeSearch()"></button>
        </div>
        <div id="rawgLoading" class="text-center d-none py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>
        <div id="rawgResults" class="d-flex gap-3 overflow-auto pb-2"></div>
    </div>
</div>

<!-- ZONE 2 : FILTRES & TRI -->
<div class="d-flex flex-wrap justify-content-end align-items-center mb-3 gap-2">
    <!-- Filtre Plateforme -->
    <select id="filterPlatform" class="form-select border-0 shadow-sm rounded-3 py-2 bg-body" style="width: auto; cursor: pointer;" onchange="updateView()">
        <option value="all">Toutes les plateformes</option>
        <option value="PS5">PlayStation 5</option>
        <option value="PS4">PlayStation 4</option>
        <option value="Xbox Series">Xbox Series</option>
        <option value="Switch">Switch</option>
        <option value="PC">PC</option>
    </select>

    <!-- Filtre Statut -->
    <select id="filterStatus" class="form-select border-0 shadow-sm rounded-3 py-2 bg-body" style="width: auto; cursor: pointer;" onchange="updateView()">
        <option value="all">Tous les statuts</option>
        <option value="playing">En cours</option>
        <option value="finished">Terminé</option>
        <option value="completed">Platiné / 100%</option>
        <option value="wishlist">Liste de souhaits</option>
        <option value="dropped">Abandonné</option>
    </select>

    <!-- Tri -->
    <select id="sortSelect" class="form-select border-0 shadow-sm rounded-3 py-2 bg-body" style="width: auto; cursor: pointer;" onchange="updateView()">
        <option value="date_desc">📅 Plus récents</option>
        <option value="rating_desc">⭐ Mieux notés</option>
        <option value="status_asc">📌 Par statut</option>
        <option value="platform_asc">🎮 Par plateforme</option>
    </select>
    
    <!-- Vue -->
    <div class="bg-body rounded-3 shadow-sm p-1 d-flex">
        <button class="btn btn-sm btn-light rounded-2 active border-0" id="btnGrid" onclick="setView('grid')"><i class="fas fa-th-large"></i></button>
        <button class="btn btn-sm btn-light rounded-2 border-0" id="btnList" onclick="setView('list')"><i class="fas fa-list"></i></button>
    </div>
</div>

<!-- Games Container -->
<div id="gamesContainer" class="row g-4"></div>
<nav class="mt-5"><ul class="pagination justify-content-center" id="paginationControls"></ul></nav>

<!-- MODALE EDITION -->
<div class="modal fade" id="gameModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form action="index.php?action=save" method="POST" enctype="multipart/form-data">
                <div class="modal-header border-bottom-0 pb-0 d-block">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="modal-title fs-5 fw-bold">Détails du jeu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <ul class="nav nav-tabs mt-3 border-0" id="modalTabs" style="display:none;">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#tab-info">Informations</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-trophies">Trophées PSN</a>
                        </li>
                    </ul>
                </div>
                <div class="modal-body">
                    <div class="tab-content">
                        <!-- TAB INFO -->
                        <div class="tab-pane fade show active" id="tab-info">
                            <input type="hidden" name="game_id" id="gameId">
                            <div class="row g-4">
                                <div class="col-md-5">
                                    <div class="ratio ratio-1x1 bg-body-tertiary rounded-4 overflow-hidden position-relative group-hover-upload border">
                                        <img id="previewImg" src="" class="d-none w-100 h-100 object-fit-cover">
                                        <div id="uploadPlaceholder" class="d-flex flex-column align-items-center justify-content-center h-100 text-secondary">
                                            <i class="fas fa-cloud-upload-alt fa-3x mb-2"></i>
                                            <small>Cliquez pour ajouter une image</small>
                                        </div>
                                        <input type="file" name="image_upload" class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer" accept="image/*" onchange="previewFile(this)">
                                        <input type="hidden" name="image_url_hidden" id="gameImageHidden">
                                    </div>
                                    <!-- CHAMP PRIX -->
                                    <div class="mt-3">
                                        <label class="form-label small fw-bold text-secondary">Prix Estimé (€)</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-body-tertiary border-end-0">€</span>
                                            <input type="number" name="estimated_price" id="gamePrice" class="form-control rounded-end border-start-0" step="0.01" placeholder="0.00">
                                            <button type="button" class="btn btn-outline-secondary ms-2 rounded" onclick="searchPrice()" title="Chercher la cote">
                                                <i class="fas fa-search-dollar"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="form-floating mb-3">
                                        <input type="text" name="title" id="gameTitle" class="form-control rounded-3" placeholder="Titre" required>
                                        <label>Titre du jeu</label>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold mb-1 text-secondary">Plateforme</label>
                                            <select name="platform" id="gamePlatform" class="form-select rounded-3" required onchange="toggleCustomPlatform(); checkPsnVisibility();">
                                                <option value="PS5">PlayStation 5</option>
                                                <option value="PS4">PlayStation 4</option>
                                                <option value="Xbox Series">Xbox Series</option>
                                                <option value="Switch">Switch</option>
                                                <option value="PC">PC</option>
                                                <option value="Multiplateforme">Multiplateforme</option>
                                            </select>
                                            <div id="multiPlatformContainer" class="d-none mt-2 p-2 bg-body-tertiary rounded-3 border">
                                                <div id="platformInputsList" class="d-flex flex-column gap-2 mb-2"></div>
                                                <button type="button" class="btn btn-sm btn-outline-primary w-100 dashed-border" onclick="addPlatformInput()"><i class="fas fa-plus"></i></button>
                                                <input type="hidden" name="platform_custom" id="gamePlatformCustom">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold mb-1 text-secondary">Statut</label>
                                            <select name="status" id="gameStatus" class="form-select rounded-3">
                                                <option value="playing">En cours</option>
                                                <option value="finished">Terminé</option>
                                                <option value="completed">100% / Platiné</option>
                                                <option value="dropped">Abandonné</option>
                                                <option value="wishlist">Liste de souhaits</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold mb-1 text-secondary">Format</label>
                                            <div class="bg-body-tertiary p-1 rounded-3 d-flex">
                                                <input type="radio" class="btn-check" name="format" id="fmtDigital" value="digital" checked>
                                                <label class="btn btn-sm btn-outline-secondary border-0 flex-grow-1 rounded-2" for="fmtDigital"><i class="fas fa-cloud"></i> Digital</label>
                                                <input type="radio" class="btn-check" name="format" id="fmtPhysical" value="physical">
                                                <label class="btn btn-sm btn-outline-secondary border-0 flex-grow-1 rounded-2" for="fmtPhysical"><i class="fas fa-box"></i> Physique</label>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <label class="form-label small fw-bold mb-1 text-secondary">Note</label>
                                            <input type="number" name="user_rating" id="gameRating" class="form-control rounded-3" max="10">
                                        </div>
                                        <div class="col-3">
                                            <label class="form-label small fw-bold mb-1 text-secondary">Meta</label>
                                            <input type="number" name="metacritic" id="gameMeta" class="form-control rounded-3" placeholder="---">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold mb-1 text-secondary">Genres</label>
                                        <input type="text" name="genres" id="gameGenres" class="form-control rounded-3" placeholder="Action, RPG...">
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label small fw-bold mb-1 text-secondary">Commentaire / Avis</label>
                                <textarea name="comment" id="gameComment" class="form-control rounded-3 bg-body-tertiary border-0" rows="2"></textarea>
                            </div>
                            <input type="hidden" name="release_date" id="gameDate">
                            <input type="hidden" name="description" id="gameDesc">
                        </div>

                        <div class="tab-pane fade" id="tab-trophies">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 fw-bold">Liste des Trophées</h6>
                                <div class="progress flex-grow-1 mx-3" style="height: 10px;">
                                    <div id="trophyProgressBar" class="progress-bar bg-warning" role="progressbar" style="width: 0%"></div>
                                </div>
                                <span id="trophyProgressText" class="small fw-bold">0%</span>
                            </div>
                            <div class="input-group mb-3">
                                <select id="newTrophyType" class="form-select" style="max-width: 100px;">
                                    <option value="bronze">🥉</option>
                                    <option value="silver">🥈</option>
                                    <option value="gold">🥇</option>
                                    <option value="platinum">🏆</option>
                                </select>
                                <input type="text" id="newTrophyTitle" class="form-control" placeholder="Nom du trophée...">
                                <button type="button" class="btn btn-primary" onclick="addTrophy()"><i class="fas fa-plus"></i></button>
                            </div>
                            <div id="trophiesList" class="overflow-y-auto" style="max-height: 350px;"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Sauvegarder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const RAWG_API_KEY = 'ae5a8fa57c704860b5010b3787ab78ef'; 
    const localGames = <?= json_encode($games) ?>;
    const statusConfig = {
        'playing': { label: 'En cours', class: 'bg-primary', icon: 'fa-gamepad' },
        'finished': { label: 'Terminé', class: 'bg-success', icon: 'fa-check' },
        'completed': { label: 'Platiné', class: 'bg-warning text-dark', icon: 'fa-trophy' },
        'dropped': { label: 'Abandon', class: 'bg-danger', icon: 'fa-ban' },
        'wishlist': { label: 'Souhait', class: 'bg-info text-white', icon: 'fa-gift' }
    };
    const platformIcons = { 'PS5': 'fab fa-playstation', 'PS4': 'fab fa-playstation', 'Xbox Series': 'fab fa-xbox', 'Xbox': 'fab fa-xbox', 'Switch': 'fas fa-gamepad', 'PC': 'fas fa-desktop' };
    
    let currentView = localStorage.getItem('viewMode') || 'grid';
    let currentPage = 1;
    const itemsPerPage = 8;
    let modal;

    document.addEventListener('DOMContentLoaded', () => {
        modal = new bootstrap.Modal(document.getElementById('gameModal'));
        initViewButtons();
        // Force l'affichage initial complet
        updateView(); 
    });

    // --- MOTEUR DE TRI ET FILTRE CENTRALISÉ ---
    function getProcessedGames() {
        const platformFilter = document.getElementById('filterPlatform').value;
        const statusFilter = document.getElementById('filterStatus').value;
        const sortType = document.getElementById('sortSelect').value;

        // 1. Filtrage
        let filtered = localGames.filter(g => {
            // Filtre Plateforme (souple pour gérer Multiplateforme)
            if (platformFilter !== 'all' && !g.platform.includes(platformFilter) && g.platform !== 'Multiplateforme') return false;
            // Filtre Statut
            if (statusFilter !== 'all' && g.status !== statusFilter) return false;
            return true;
        });

        // 2. Tri
        filtered.sort((a, b) => {
            const valA = (key) => a[key] || 0;
            const valB = (key) => b[key] || 0;

            switch(sortType) {
                case 'date_desc': return new Date(b.created_at) - new Date(a.created_at);
                case 'rating_desc': return valB('user_rating') - valA('user_rating');
                case 'status_asc': return a.status.localeCompare(b.status);
                case 'platform_asc': return a.platform.localeCompare(b.platform);
                default: return new Date(b.created_at) - new Date(a.created_at);
            }
        });

        return filtered;
    }

    // --- RECHERCHE PRIX ---
    function searchPrice() { const title = document.getElementById('gameTitle').value; const platform = document.getElementById('gamePlatform').value; if(title) { const query = encodeURIComponent(`${title} ${platform}`); const w = 1000; const h = 600; const left = (screen.width/2)-(w/2); const top = (screen.height/2)-(h/2); window.open(`https://www.ebay.fr/sch/i.html?_nkw=${query}&_sacat=139973`, 'PriceCheck', `width=${w},height=${h},top=${top},left=${left}`); } else { alert("Veuillez d'abord saisir un titre de jeu."); } }
    
    // --- HELPERS ---
    function handleEnter(e) { if(e.key === 'Enter') searchRawg(); }
    function closeSearch() { document.getElementById('rawgContainer').classList.add('d-none'); document.getElementById('rawgSearchInput').value = ''; }
    function setView(v) { currentView = v; localStorage.setItem('viewMode', v); initViewButtons(); updateView(); }
    function initViewButtons() { document.getElementById('btnGrid').className = currentView === 'grid' ? 'btn btn-sm btn-light rounded-2 active border-0' : 'btn btn-sm btn-transparent rounded-2 text-secondary'; document.getElementById('btnList').className = currentView === 'list' ? 'btn btn-sm btn-light rounded-2 active border-0' : 'btn btn-sm btn-transparent rounded-2 text-secondary'; }
    function previewFile(input) { if (input.files && input.files[0]) { var reader = new FileReader(); reader.onload = function(e) { document.getElementById('previewImg').src = e.target.result; document.getElementById('previewImg').classList.remove('d-none'); document.getElementById('uploadPlaceholder').classList.add('d-none'); }; reader.readAsDataURL(input.files[0]); } }
    
    // --- MULTIPLATEFORME ---
    function toggleCustomPlatform() { const select = document.getElementById('gamePlatform'); const container = document.getElementById('multiPlatformContainer'); const hiddenInput = document.getElementById('gamePlatformCustom'); if (select.value === 'Multiplateforme') { container.classList.remove('d-none'); if(document.getElementById('platformInputsList').children.length === 0) addPlatformInput(); } else { container.classList.add('d-none'); hiddenInput.value = ''; } }
    function addPlatformInput(value = '') { const list = document.getElementById('platformInputsList'); const div = document.createElement('div'); div.className = 'input-group input-group-sm mb-1'; div.innerHTML = `<input type="text" class="form-control rounded-start-2 border-end-0 bg-white" value="${value}" placeholder="Nom..." oninput="updateHiddenPlatformInput()"><button type="button" class="btn btn-outline-danger border-start-0 rounded-end-2 bg-white text-danger" onclick="this.parentElement.remove(); updateHiddenPlatformInput()"><i class="fas fa-times"></i></button>`; list.appendChild(div); updateHiddenPlatformInput(); }
    function updateHiddenPlatformInput() { const inputs = document.querySelectorAll('#platformInputsList input'); const values = Array.from(inputs).map(i => i.value.trim()).filter(v => v !== ''); document.getElementById('gamePlatformCustom').value = values.join(', '); }
    
    // --- RENDU ---
    function updateView() {
        const container = document.getElementById('gamesContainer');
        const pagination = document.getElementById('paginationControls');
        container.innerHTML = '';
        pagination.innerHTML = '';

        if (localGames.length === 0) { 
             container.innerHTML = '<div class="col-12 text-center text-muted py-5"><i class="fas fa-ghost fa-3x mb-3 opacity-25"></i><p>Votre collection est vide.</p></div>'; 
             return; 
        }

        // Utilisation de la nouvelle fonction centralisée
        const processedGames = getProcessedGames();
        
        if (processedGames.length === 0) {
             container.innerHTML = '<div class="col-12 text-center text-muted py-5"><i class="fas fa-filter fa-3x mb-3 opacity-25"></i><p>Aucun jeu ne correspond aux filtres.</p></div>'; 
             return;
        }

        const totalPages = Math.ceil(processedGames.length / itemsPerPage);
        if (currentPage > totalPages) currentPage = 1;
        const start = (currentPage - 1) * itemsPerPage;
        const pagedGames = processedGames.slice(start, start + itemsPerPage);

        // ... (Rendu Grid/List identique) ...
        if (currentView === 'grid') {
            pagedGames.forEach(g => {
                const s = statusConfig[g.status] || statusConfig['playing'];
                const img = g.image_url ? g.image_url : '';
                const formatIcon = (g.format === 'physical') ? '<i class="fas fa-box text-adaptive-muted" title="Physique"></i>' : '<i class="fas fa-cloud text-adaptive-muted" title="Digital"></i>';
                const bgColor = g.dominant_color ? g.dominant_color : 'rgb(30,30,30)';
                let metaBadge = '';
                if(g.metacritic_score > 0) { let metaIcon = g.metacritic_score >= 75 ? 'text-success' : (g.metacritic_score >= 50 ? 'text-warning' : 'text-danger'); metaBadge = `<span class="badge badge-adaptive rounded-1 small fw-normal ms-2 px-2" title="Metascore"><i class="fas fa-chart-bar ${metaIcon} me-1"></i>${g.metacritic_score}</span>`; }
                let priceBadge = ''; if (g.estimated_price > 0) { priceBadge = `<span class="badge badge-adaptive rounded-1 small fw-normal ms-2 px-2" title="Prix Estimé"><i class="fas fa-tag text-info me-1"></i>${g.estimated_price}€</span>`; }
                let platIconClass = platformIcons[g.platform] || 'fas fa-gamepad';
                if (g.platform.includes(',')) platIconClass = 'fas fa-layer-group';

                container.innerHTML += `
                <div class="col-sm-6 col-lg-4 col-xl-3">
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
                                ${metaBadge}
                                ${priceBadge}
                            </div>
                            <div class="mt-auto d-flex justify-content-between align-items-end pt-2 border-top" style="border-color: rgba(255,255,255,0.1) !important;">
                                <div class="d-flex align-items-center gap-2">${formatIcon}<small class="text-adaptive-muted text-truncate" style="max-width: 100px;">${g.genres || ''}</small></div>
                                <div class="card-actions d-flex gap-1"><button class="btn btn-sm btn-action-adaptive rounded-circle" style="width:32px;height:32px" onclick='edit(${g.id})'><i class="fas fa-pen fa-xs"></i></button><a href="index.php?action=delete&id=${g.id}" class="btn btn-sm btn-action-adaptive rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px" onclick="return confirm('Supprimer ?')"><i class="fas fa-trash fa-xs"></i></a></div>
                            </div>
                        </div>
                    </div>
                </div>`;
            });
        } else {
             let html = `<div class="col-12"><div class="card border-0 shadow-sm rounded-4 overflow-hidden"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="bg-body-secondary text-secondary small text-uppercase"><tr><th class="ps-4">Jeu</th><th>Plateforme</th><th>Prix</th><th>Statut</th><th>Note</th><th class="text-end pe-4">Actions</th></tr></thead><tbody>`;
            pagedGames.forEach(g => {
                const s = statusConfig[g.status] || statusConfig['playing'];
                const img = g.image_url ? `<img src="${g.image_url}" class="rounded-3 shadow-sm" style="width:48px;height:48px;object-fit:cover;">` : `<div class="rounded-3 bg-secondary-subtle d-flex align-items-center justify-content-center" style="width:48px;height:48px"><i class="fas fa-gamepad text-secondary"></i></div>`;
                const price = g.estimated_price > 0 ? `${g.estimated_price}€` : '-';
                let platIcon = platformIcons[g.platform] || 'fas fa-gamepad';
                if (g.platform.includes(',')) platIcon = 'fas fa-layer-group';
                html += `<tr><td class="ps-4"><div class="d-flex align-items-center gap-3">${img}<div><div class="fw-bold text-body">${g.title}</div><div class="small text-secondary">${g.genres || ''}</div></div></div></td><td><span class="badge bg-secondary-subtle text-secondary-emphasis rounded-2 fw-normal"><i class="${platIcon} me-1"></i>${g.platform}</span></td><td>${price}</td><td><span class="badge ${s.class} rounded-pill bg-opacity-75"><i class="fas ${s.icon} me-1"></i>${s.label}</span></td><td class="text-end pe-4 fw-bold text-warning">${g.user_rating || '-'}</td><td class="text-end pe-4"><button class="btn btn-sm btn-light rounded-circle me-1" onclick='edit(${g.id})'><i class="fas fa-pen fa-xs text-primary"></i></button><a href="index.php?action=delete&id=${g.id}" class="btn btn-sm btn-light rounded-circle" onclick="return confirm('Supprimer ?')"><i class="fas fa-trash fa-xs text-danger"></i></a></td></tr>`;
            });
            container.innerHTML = html + `</tbody></table></div></div></div>`;
        }

        if (totalPages > 1) { for (let i = 1; i <= totalPages; i++) { const active = i === currentPage ? 'active bg-primary border-primary text-white' : 'text-body bg-body hover-bg-gray'; pagination.innerHTML += `<li class="page-item"><button class="page-link rounded-circle mx-1 border-0 shadow-sm fw-bold ${active}" style="width:36px;height:36px" onclick="changePage(${i})">${i}</button></li>`; } }
    }

    function changePage(p) { currentPage = p; updateView(); }
    function edit(id) { openModal(localGames.find(g => g.id == id)); }
    function openModal(g = null) {
        if(!modal) modal = new bootstrap.Modal(document.getElementById('gameModal'));
        document.getElementById('gameId').value = g ? g.id : '';
        document.getElementById('gameTitle').value = g ? g.title : '';
        const prev = document.getElementById('previewImg');
        const holder = document.getElementById('uploadPlaceholder');
        document.getElementById('gameImageHidden').value = g ? (g.image_url || '') : '';
        if(g && g.image_url) { prev.src = g.image_url; prev.classList.remove('d-none'); holder.classList.add('d-none'); } else { prev.classList.add('d-none'); holder.classList.remove('d-none'); }
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
        if(g && g.id) loadTrophies(g.id);
        modal.show();
    }
    
    function checkPsnVisibility() { const plat = document.getElementById('gamePlatform').value; const tabs = document.getElementById('modalTabs'); if(plat.includes('PS') || plat.includes('PlayStation')) { tabs.style.display = 'flex'; } else { tabs.style.display = 'none'; const firstTabEl = document.querySelector('#modalTabs li:first-child button') || document.querySelector('#modalTabs li:first-child a'); if(firstTabEl) { const firstTab = new bootstrap.Tab(firstTabEl); firstTab.show(); } } }
    async function loadTrophies(gameId) { const list = document.getElementById('trophiesList'); list.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>'; try { const res = await fetch(`index.php?action=api_get_trophies&game_id=${gameId}`); const data = await res.json(); const pct = data.progress.percent; document.getElementById('trophyProgressBar').style.width = pct + '%'; document.getElementById('trophyProgressText').innerText = pct + '%'; list.innerHTML = ''; if(data.trophies.length === 0) { list.innerHTML = '<div class="text-center text-muted small py-3">Aucun trophée ajouté.</div>'; return; } data.trophies.forEach(t => { const colorClass = `trophy-${t.type}`; const icon = t.type === 'platinum' ? '🏆' : (t.type === 'gold' ? '🥇' : (t.type === 'silver' ? '🥈' : '🥉')); const isObtained = t.is_obtained == 1; const gameTitle = document.getElementById('gameTitle').value; const searchUrl = `https://www.google.com/search?q=${encodeURIComponent(gameTitle + ' trophée ' + t.title + ' guide')}`; list.innerHTML += `<div class="d-flex align-items-center p-2 mb-2 bg-body-tertiary rounded trophy-item ${colorClass}"><div class="trophy-icon fs-5">${icon}</div><div class="flex-grow-1 ms-2" style="cursor:pointer" onclick="toggleTrophy(${t.id})"><div class="fw-bold small ${isObtained ? 'text-decoration-line-through text-muted' : ''}">${t.title}</div></div><div class="d-flex gap-2"><a href="${searchUrl}" target="_blank" class="btn btn-sm btn-link text-info p-0" title="Chercher solution"><i class="fas fa-search"></i></a><button class="btn btn-sm btn-link text-danger p-0" onclick="deleteTrophy(${t.id})"><i class="fas fa-trash"></i></button></div></div>`; }); } catch(e) { list.innerHTML = 'Erreur chargement.'; } }
    async function addTrophy() { const gameId = document.getElementById('gameId').value; if(!gameId) { alert("Veuillez d'abord sauvegarder le jeu."); return; } const title = document.getElementById('newTrophyTitle').value; const type = document.getElementById('newTrophyType').value; if(!title) return; const formData = new FormData(); formData.append('game_id', gameId); formData.append('title', title); formData.append('type', type); formData.append('description', ''); await fetch('index.php?action=api_add_trophy', { method: 'POST', body: formData }); document.getElementById('newTrophyTitle').value = ''; loadTrophies(gameId); }
    async function toggleTrophy(id) { await fetch(`index.php?action=api_toggle_trophy&id=${id}`); loadTrophies(document.getElementById('gameId').value); }
    async function deleteTrophy(id) { if(!confirm('Supprimer ?')) return; await fetch(`index.php?action=api_delete_trophy&id=${id}`); loadTrophies(document.getElementById('gameId').value); }
    async function searchRawg() { const q = document.getElementById('rawgSearchInput').value; if(!q) return; document.getElementById('rawgContainer').classList.remove('d-none'); document.getElementById('rawgLoading').classList.remove('d-none'); try { const res = await fetch(`https://api.rawg.io/api/games?key=${RAWG_API_KEY}&search=${encodeURIComponent(q)}&page_size=5`); const data = await res.json(); let html = ''; data.results.forEach(g => { html += `<div class="card border-0 shadow-sm flex-shrink-0 bg-body-tertiary" style="width: 160px; cursor: pointer; overflow: hidden; border-radius: 12px;" onclick="fetchGameDetails(${g.id})"><img src="${g.background_image || ''}" style="height:100px; width: 100%; object-fit:cover"><div class="p-2 text-center"><small class="fw-bold d-block text-truncate text-body">${g.name}</small><small class="text-muted" style="font-size: 0.7rem;">${g.released ? g.released.substring(0,4) : ''}</small></div></div>`; }); document.getElementById('rawgResults').innerHTML = html; } catch(e) {} finally { document.getElementById('rawgLoading').classList.add('d-none'); } }
    async function fetchGameDetails(id) { document.getElementById('rawgLoading').classList.remove('d-none'); try { const res = await fetch(`https://api.rawg.io/api/games/${id}?key=${RAWG_API_KEY}`); const g = await res.json(); openModal(); document.getElementById('gameTitle').value = g.name; document.getElementById('gameDate').value = g.released; document.getElementById('gameMeta').value = g.metacritic; document.getElementById('gameImageHidden').value = g.background_image; if(g.background_image) { document.getElementById('previewImg').src = g.background_image; document.getElementById('previewImg').classList.remove('d-none'); document.getElementById('uploadPlaceholder').classList.add('d-none'); } document.getElementById('gameDesc').value = g.description_raw; document.getElementById('gameGenres').value = g.genres ? g.genres.map(x => x.name).join(', ') : ''; } catch(e) { alert("Erreur import."); } finally { document.getElementById('rawgLoading').classList.add('d-none'); } }
</script>