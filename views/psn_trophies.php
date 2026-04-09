<link rel="stylesheet" href="assets/css/dashboard.css">

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 gap-3 pt-2">
    <div>
        <h2 class="h2 dashboard-welcome mb-1 fw-light"><?= __('psn_title') ?> 🏆</h2>
    </div>

    <div class="d-flex gap-2 overflow-x-auto pb-2 pb-md-0" style="scrollbar-width:none;">
        <div class="stat-widget">
            <img src="assets/images/platinum.png" width="24" class="ms-1" alt="P">
            <div class="stat-content">
                <span class="stat-label"><?= __('psn_total_platinum') ?></span>
                <span class="stat-value text-white"><?= $userStats['psn_total_platinum'] ?? 0 ?></span>
            </div>
        </div>

        <div class="stat-widget">
            <img src="assets/images/gold.png" width="24" class="ms-1" alt="G">
            <div class="stat-content">
                <span class="stat-label"><?= __('psn_total_gold') ?></span>
                <span class="stat-value text-white"><?= $userStats['psn_total_gold'] ?? 0 ?></span>
            </div>
        </div>

        <div class="stat-widget">
            <img src="assets/images/silver.png" width="24" class="ms-1" alt="S">
            <div class="stat-content">
                <span class="stat-label"><?= __('psn_total_silver') ?></span>
                <span class="stat-value text-white"><?= $userStats['psn_total_silver'] ?? 0 ?></span>
            </div>
        </div>

        <div class="stat-widget">
            <img src="assets/images/bronze.png" width="24" class="ms-1" alt="B">
            <div class="stat-content">
                <span class="stat-label"><?= __('psn_total_bronze') ?></span>
                <span class="stat-value text-white"><?= $userStats['psn_total_bronze'] ?? 0 ?></span>
            </div>
        </div>
    </div>
</div>

<?php if (empty($psnGames)): ?>
    <div class="alert alert-info border-0 shadow-sm">
        <span class="material-symbols-rounded align-middle me-2">info</span>
        Aucun jeu PlayStation n'a été synchronisé. Vérifiez votre ID PSN dans votre profil.
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($psnGames as $game): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100 bg-dark border-0 shadow-sm psn-card overflow-hidden" 
                     style="cursor: pointer;" 
                     onclick="openPsnTrophyModal(<?= $game['id'] ?>, '<?= addslashes($game['title']) ?>', '<?= addslashes($game['image_url']) ?>')">
                    
                    <div class="d-flex flex-row h-100">
                        <div class="psn-card-img-wrapper">
                            <img src="<?= htmlspecialchars($game['image_url'] ?: 'assets/images/no-cover.png') ?>" class="psn-card-img" alt="Cover">
                        </div>

                        <div class="card-body p-3 d-flex flex-column">
                            <h5 class="card-title text-truncate mb-2 fs-6" title="<?= htmlspecialchars($game['title']) ?>"><?= htmlspecialchars($game['title']) ?></h5>
                            
                            <div class="psn-trophies-grid mb-2">
                                <div class="psn-t-item"><img src="assets/images/platinum.png" width="14" alt="P"> <span><?= $game['plat'] ?></span></div>
                                <div class="psn-t-item"><img src="assets/images/gold.png" width="14" alt="G"> <span><?= $game['gold'] ?></span></div>
                                <div class="psn-t-item"><img src="assets/images/silver.png" width="14" alt="S"> <span><?= $game['silver'] ?></span></div>
                                <div class="psn-t-item"><img src="assets/images/bronze.png" width="14" alt="B"> <span><?= $game['bronze'] ?></span></div>
                            </div>

                            <div class="mt-auto">
                                <?php 
                                $total = $game['total_trophies'] ?: 0;
                                $obtained = $game['obtained_trophies'] ?: 0;
                                $percent = ($total > 0) ? round(($obtained / $total) * 100) : 0;
                                ?>
                                <div class="progress psn-progress-bar">
                                    <div class="progress-bar bg-primary" style="width: <?= $percent ?>%"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-muted" style="font-size:0.65rem;"><?= $percent ?>%</small>
                                    <small class="text-muted" style="font-size:0.65rem;"><?= $obtained ?> / <?= $total ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>


<div class="modal fade" id="psnTrophyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content bg-dark text-white border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="position-relative" style="height: 200px; overflow: hidden;">
                <img id="psnModalImage" src="" class="w-100 h-100 object-fit-cover opacity-50" alt="Cover">
                <div class="position-absolute top-0 end-0 p-3">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="position-absolute bottom-0 start-0 p-4 w-100" style="background: linear-gradient(to top, #212529 10%, transparent);">
                    <h4 class="modal-title fw-bold text-shadow d-flex align-items-center mb-0" id="psnModalTitle">
                        Titre du jeu
                    </h4>
                </div>
            </div>

            <div class="modal-body p-0 bg-dark">
                <div id="psnTrophyList" class="list-group list-group-flush">
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentGameTitle = "";
    // On récupère la langue actuelle du site depuis PHP
    const currentLang = '<?= $_SESSION['lang'] ?? 'fr' ?>';

    function openPsnTrophyModal(gameId, gameTitle, gameImage) {
        const modal = new bootstrap.Modal(document.getElementById('psnTrophyModal'));
        const listContainer = document.getElementById('psnTrophyList');

        document.getElementById('psnModalTitle').textContent = gameTitle;
        document.getElementById('psnModalImage').src = gameImage ? gameImage : 'assets/images/no-cover.png';
        currentGameTitle = gameTitle;

        listContainer.innerHTML = '<div class="p-5 text-center"><div class="spinner-border text-primary"></div></div>';
        modal.show();

        fetch(`index.php?action=api_psn_get_trophies&psn_game_id=${gameId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    listContainer.innerHTML = '';
                    data.trophies.forEach(t => {
                        const isObtained = parseInt(t.is_obtained) === 1;
                        const dateEarned = t.earned_at ? new Date(t.earned_at).toLocaleDateString() : '';

                        // Détermination du nom à afficher et à rechercher
                        const hasFr = t.title_fr && t.title_fr.trim() !== '';
                        const displayName = (currentLang === 'fr' && hasFr) ? t.title_fr : t.title;

                        // Construction de la recherche YouTube localisée
                        let searchQuery = "";
                        if (currentLang === 'fr') {
                            // Recherche en français : [Jeu] [Nom FR] guide trophée
                            searchQuery = `${currentGameTitle} ${displayName}`;
                        } else {
                            // Recherche en anglais : [Jeu] [Nom EN] trophy guide
                            searchQuery = `${currentGameTitle} ${t.title}`;
                        }
                        
                        const youtubeSearchUrl = `https://www.youtube.com/results?search_query=${encodeURIComponent(searchQuery)}`;

                        let actionButtonHtml = '';
                        if (!isObtained) {
                            actionButtonHtml = `
                                <a href="${youtubeSearchUrl}" target="_blank" class="btn btn-info fw-bold rounded-pill px-4 py-2 shadow-sm d-flex align-items-center text-nowrap" title="${currentLang === 'fr' ? 'Voir le guide' : 'Watch guide'}">
                                    <span class="material-icons-outlined fs-5 me-2">&#xe639</span>
                                    ${currentLang === 'fr' ? 'Guide' : 'Guide'}
                                </a>
                            `;
                        }

                        const item = document.createElement('div');
                        item.className = `list-group-item bg-dark border-secondary p-3 d-flex flex-column flex-md-row align-items-md-center gap-3 ${!isObtained ? 'opacity-75' : ''}`;

                        item.innerHTML = `
                            <div class="d-flex align-items-center flex-grow-1">
                                <img src="assets/images/${t.type}.png" width="40" class="me-3 drop-shadow">
                                <div>
                                    <div class="fw-bold fs-6 ${!isObtained ? 'text-light' : 'text-white'}">${displayName}</div>
                                    <div class="small text-muted text-uppercase" style="font-size: 0.7rem;">${t.type}</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3 justify-content-between justify-content-md-end w-100 w-md-auto mt-2 mt-md-0">
                                ${isObtained 
                                    ? `<span class="badge bg-success rounded-pill px-3 py-2"><i class="material-icons-outlined fs-6 align-middle me-1">&#xe86c;</i>${dateEarned}</span>` 
                                    : `<span class="position-static status-badge-float bg-danger"><i class="material-icons-outlined icon-sm me-1">&#xe14b</i><?= __('psn_trophy_not_obtained') ?></span>`
                                }
                                ${actionButtonHtml}
                            </div>
                        `;
                        listContainer.appendChild(item);
                    });
                }
            });
    }
</script>