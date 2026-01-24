<link rel="stylesheet" href="assets/css/dashboard.css">

<?php
// CALCULS PHP POUR L'EN-TÊTE
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
$shareLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[SCRIPT_NAME]?action=share&user=" . $username;
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 gap-3 pt-2">
    <div>
        <h2 class="h3 dashboard-welcome mb-1 fw-light">Bonjour <span class="text-primary fw-bold"><?= htmlspecialchars($username) ?></span> 👋</h2>
    </div>
    <div class="d-flex gap-2 overflow-x-auto pb-2 pb-md-0" style="scrollbar-width:none;">
        <div class="stat-pill"><i class="material-icons text-primary align-top icon-lg pe-2">&#xea28;</i>Total <strong><?= $totalGames ?></strong></div>
        <div class="stat-pill mx-4"><i class="material-icons text-info align-top icon-lg pe-2">&#xe037;</i>En cours <strong><?= $playingCount ?></strong></div>
        <div class="stat-pill"><i class="material-icons text-success align-top icon-lg pe-2">&#xe5ca;</i>Terminés <strong><?= $finishedCount ?></strong></div> 
    </div>
</div>

<div class="card bg-body-primaary border-0 shadow-sm rounded-4 mb-4 overflow-hidden">

    <div class="card-header accordion-trigger bg-transparent border-0 p-3 p-md-4 d-flex justify-content-between align-items-center"
        data-bs-toggle="collapse"
        data-bs-target="#addGameSection"
        aria-expanded="false"
        aria-controls="addGameSection">

        <h5 class="mb-0 fw-bold text-primary d-flex align-items-center gap-2">
            <i class="material-icons icon-md fs-2">&#xea28;</i>Ajouter un jeu à votre collection
        </h5>

        <i class="material-icons-outlined text-secondary rotate-icon icon-md">&#xe5cf;</i>
    </div>

    <div class="collapse" id="addGameSection">
        <div class="card-body px-4 pb-4 pt-0">

            <hr class="text-secondary opacity-10 my-2 mb-4">

            <div class="d-flex flex-column flex-md-row gap-3 align-items-center">

                <div class="flex-grow-1 w-100">
                    <div class="search-wrapper">
                        <div class="search-box">
                            <i class="material-icons-outlined search-icon icon-md">&#xe8b6;</i>
                            <input type="text" id="rawgSearchInput" class="form-control border rounded-pill search-input" placeholder="Taper le nom du jeu..." onkeypress="handleEnter(event)">
                        </div>
                    </div>
                </div>

                <button class="btn btn-outline-primary shadow-sm rounded-pill fw-bold px-4 py-2 w-auto text-nowrap" onclick="openModal()">
                    <i class="material-icons-outlined icon-sm fs-4 me-2">&#xea28;</i>Ajout manuel
                </button>

            </div>

            <div id="rawgContainer" class="mt-3 d-none border-top pt-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0 text-secondary small fw-bold text-uppercase">Résultats Internet</h6>
                    <button type="button" class="btn-close btn-sm" onclick="closeSearch()"></button>
                </div>
                <div id="rawgLoading" class="text-center d-none py-3">
                    <div class="spinner-border spinner-border-sm text-primary"></div>
                </div>
                <div id="rawgResults" class="d-flex gap-3 overflow-auto pb-2"></div>
            </div>

        </div>
    </div>
</div>

<div class="d-flex flex-column flex-xxl-row align-items-center justify-content-between mb-3 gap-2">

    <div class="input-group rounded-pill overflow-hidden border border-opacity-10 bg shadow-sm w-100 w-xxl-50">
        <span class="input-group-text border-0 ps-3 bg-transparent"><i class="material-icons-outlined text-secondary icon-md">&#xe8b6;</i></span>
        <input type="text" id="internalSearchInput" class="form-control border-0 shadow-none bg-transparent" placeholder="Rechercher dans ma collection..." onkeyup="updateView()">
        <span class="input-group-text border-0 pe-3 bg-transparent" style="cursor:pointer" onclick="document.getElementById('internalSearchInput').value=''; updateView();"><i class="material-icons-outlined opacity-50 icon-sm">&#xe5cd;</i></span>
    </div>

    <div class="d-flex flex-wrap justify-content-between justify-content-xxl-end gap-2 w-100 w-xxl-auto">

        <select id="filterPlatform" class="form-select border-0 shadow-sm rounded-3 py-2 bg-body" style="width: auto; cursor: pointer;" onchange="updateView()">
            <option value="all">Plateforme</option>
            <option value="PS5">PlayStation 5</option>
            <option value="PS4">PlayStation 4</option>
            <option value="Xbox Series">Xbox Series</option>
            <option value="Switch">Switch 1 / 2</option>
            <option value="PC">PC / Steam</option>
        </select>

        <select id="filterStatus" class="form-select border-0 shadow-sm rounded-3 py-2 bg-body" style="width: auto; cursor: pointer;" onchange="updateView()">
            <option value="all">Statut</option>
            <option value="playing">En cours</option>
            <option value="finished">Terminé</option>
            <option value="completed">Platiné / 100%</option>
            <option value="wishlist">Wishlist</option>
            <option value="dropped">Abandonné</option>
        </select>

        <select id="sortSelect" class="form-select border-0 shadow-sm rounded-3 py-2 bg-body" style="width: auto; cursor: pointer;" onchange="updateView()">
            <option value="date_desc">Récents</option>
            <option value="rating_desc">Top Notes</option>
            <option value="status_asc">Statut</option>
            <option value="platform_asc">Plateforme</option>
        </select>

        <div class="bg-body rounded-3 shadow-sm p-1 d-flex">
            <button class="btn btn-sm btn-light rounded-2 active border-0" id="btnGrid" onclick="setView('grid')"><i class="material-icons-outlined icon-md">&#xe9b0;</i></button>
            <button class="btn btn-sm btn-light rounded-2 border-0" id="btnList" onclick="setView('list')"><i class="material-icons-outlined icon-md">&#xe8ef;</i></button>
        </div>
    </div>

</div>

<div id="gamesContainer" class="row g-xxl-4 g-md-3 g-sm-2"></div>

<div id="scrollSentinel" class="text-center py-4 my-2">
    <div class="spinner-border text-primary d-none" role="status" id="scrollLoader">
        <span class="visually-hidden">Chargement...</span>
    </div>
</div>

<div class="modal fade" id="gameModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form action="index.php?action=save" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
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
                        <div class="tab-pane fade show active" id="tab-info">
                            <input type="hidden" name="game_id" id="gameId">
                            <div class="row g-4">
                                <div class="col-md-5">
                                    <div class="ratio ratio-1x1 bg-body-tertiary rounded-4 overflow-hidden position-relative group-hover-upload">
                                        <img id="previewImg" src="" class="d-none w-100 h-100 object-fit-cover">
                                        <div id="uploadPlaceholder" class="d-flex flex-column align-items-center justify-content-center h-100 text-secondary">
                                            <i class="material-icons-outlined mb-2 icon-lg">&#xe2c0;</i>
                                            <small>Cliquez pour ajouter une image</small>
                                        </div>
                                        <input type="file" name="image_upload" class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer" accept="image/*" onchange="previewFile(this)">
                                        <input type="hidden" name="image_url_hidden" id="gameImageHidden">
                                    </div>
                                    <div class="mt-3">
                                        <label class="form-label small fw-bold text-secondary">Prix Estimé (€)</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-body-tertiary border-end-0">€</span>
                                            <input type="number" name="estimated_price" id="gamePrice" class="form-control rounded-end border-start-0" step="0.01" placeholder="0.00">
                                            <button type="button" class="btn btn-primary ms-2 rounded" onclick="searchPrice()" title="Chercher la côte">
                                                <i class="material-icons icon-md">&#xe8b6;</i>
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
                                                <button type="button" class="btn btn-sm btn-outline-primary w-100 dashed-border" onclick="addPlatformInput()">
                                                    <i class="material-icons-outlined icon-sm">&#xe145;</i>
                                                </button>
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
                                            <div class="bg-body-transparent p-1 rounded-3 d-flex">
                                                <input type="radio" class="btn-check" name="format" id="fmtDigital" value="digital" checked>
                                                <label class="btn btn-sm btn-outline-primary border-0 flex-grow-1 rounded-2" for="fmtDigital"><i class="material-icons-outlined icon-sm me-1">&#xe1a1;</i> Cloud</label>
                                                <input type="radio" class="btn-check" name="format" id="fmtPhysical" value="physical">
                                                <label class="btn btn-sm btn-outline-primary border-0 flex-grow-1 rounded-2" for="fmtPhysical"><i class="material-icons-outlined icon-sm me-1">&#xe3dd;</i> Physique</label>
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
                                <button type="button" class="btn btn-primary" onclick="addTrophy()"><i class="material-icons-outlined icon-sm">&#xe145;</i></button>
                            </div>
                            <div id="trophiesList" class="overflow-y-auto" style="max-height: 350px;"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4">Sauvegarder</button>
                    <button type="button" class="btn btn-light fw-bold rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let localGames = <?= json_encode($games) ?>;
</script>
<script src="assets/js/dashboard.js"></script>