<link rel="stylesheet" href="assets/css/dashboard.css">

<?php
$username = $_SESSION['username'] ?? 'Gamer';
$shareLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[SCRIPT_NAME]?action=share&user=" . $username;
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 gap-3 pt-2">
    <div>
        <h2 class="h2 dashboard-welcome mb-1 fw-light"><?= __('dashboard_hello') ?> <span class="text-primary fw-bold"><?= htmlspecialchars($username) ?></span> 👋</h2>
    </div>
    <div class="d-flex gap-2 overflow-x-auto pb-2 pb-md-0" style="scrollbar-width:none;">
        <div class="stat-widget">
            <i class="material-icons stat-icon text-warning-emphasis">&#xea28;</i>
            <div class="stat-content">
                <span class="stat-label"><?= __('dashboard_total') ?></span>
                <span id="statTotal" class="stat-value text-warning-emphasis animate-counter" data-target="<?= $totalGames ?>">0</span>
            </div>
        </div>

        <div class="stat-widget">
            <i class="material-icons stat-icon text-info">&#xe037;</i>
            <div class="stat-content">
                <span class="stat-label"><?= __('dashboard_playing') ?></span>
                <span id="statPlaying" class="stat-value text-info animate-counter" data-target="<?= $playingCount ?>">0</span>
            </div>
        </div>

        <div class="stat-widget">
            <i class="material-icons stat-icon text-primary">&#xe5ca;</i>
            <div class="stat-content">
                <span class="stat-label"><?= __('dashboard_finished') ?></span>
                <span id="statFinished" class="stat-value text-primary animate-counter" data-target="<?= $finishedCount ?>">0</span>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-center mb-4 mt-2">
    <div class="custom-tabs-container shadow-sm border border-opacity-10">

        <input type="radio" name="libFormat" id="btnLibPhys" class="custom-tab-input" autocomplete="off" onchange="setLibraryFormat('physical')">
        <label for="btnLibPhys" class="custom-tab-label">
            <i class="material-icons-outlined icon-sm align-middle me-2">&#xe1a1;</i><?= __('dashboard_btn_Physicalformat') ?>
        </label>

        <input type="radio" name="libFormat" id="btnLibDigi" class="custom-tab-input" autocomplete="off" onchange="setLibraryFormat('digital')">
        <label for="btnLibDigi" class="custom-tab-label">
            <i class="material-icons-outlined icon-sm align-middle me-2">&#xe3dd;</i><?= __('dashboard_btn_Digitalformat') ?>
        </label>

        <div class="custom-tab-slider"></div>
    </div>
</div>

<div class="card bg-body-primaary border-0 shadow-sm rounded-4 mb-4 overflow-hidden">

    <div class="card-header accordion-trigger bg-transparent border-0 p-3 p-md-4 d-flex justify-content-between align-items-center <?= isset($_GET['open_add']) ? '' : 'collapsed' ?>"
        data-bs-toggle="collapse"
        data-bs-target="#addGameSection"
        aria-expanded="<?= isset($_GET['open_add']) ? 'true' : 'false' ?>"
        aria-controls="addGameSection">

        <h5 class="mb-0 fw-bold text-primary d-flex align-items-center gap-2">
            <i class="material-icons icon-md fs-2">&#xea28;</i><?= __('dashboard_add_game') ?>
        </h5>

        <i class="material-icons-outlined text-secondary rotate-icon icon-md">&#xe5cf;</i>
    </div>

    <div class="collapse <?= isset($_GET['open_add']) ? 'show' : '' ?>" id="addGameSection">
        <div class="card-body px-4 pb-4 pt-0">

            <hr class="text-secondary opacity-10 my-2 mb-4">

            <div class="d-flex flex-column flex-md-row gap-3 align-items-center">

                <div class="flex-grow-1 w-100">
                    <div class="search-wrapper">
                        <div class="search-box">
                            <i class="material-icons-outlined search-icon icon-md">&#xe8b6;</i>
                            <input type="text" id="rawgSearchInput" class="form-control border rounded-pill search-input" placeholder="<?= __('dashboard_search_api') ?>" onkeypress="handleEnter(event)">
                        </div>
                    </div>
                </div>
                <div class="position-relative">
                    <button class="btn btn-light border shadow-sm rounded-circle d-flex align-items-center justify-content-center"
                        style="width: 45px; height: 45px;"
                        id="micBtn"
                        onclick="toggleVoiceSearch()"
                        title="Recherche vocale">
                        <i class="material-icons-outlined icon-md" id="micIcon">&#xe029;</i>
                    </button>

                    <span id="langBadge"
                        onclick="toggleVoiceLang(event)"
                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark border border-white shadow-sm"
                        style="cursor: pointer; font-size: 0.65rem; z-index: 10;"
                        title="Changer de langue (EN/FR)">
                        EN
                    </span>
                </div>
                <button class="btn btn-outline-primary shadow-sm rounded-pill fw-bold px-4 py-2 w-auto text-nowrap" onclick="openModal()">
                    <i class="material-icons-outlined icon-sm fs-4 me-2">&#xea28;</i><?= __('dashboard_manual_add') ?>
                </button>
            </div>

            <div id="rawgContainer" class="mt-3 d-none border-top pt-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0 text-secondary small fw-bold text-uppercase"><?= __('dashboard_internet_results') ?></h6>
                    <button type="button" class="btn-close btn-sm" onclick="closeSearch()"></button>
                </div>
                <div id="rawgLoading" class="text-center d-none py-3">
                    <div class="spinner-border spinner-border-sm text-primary"></div>
                </div>
                <div id="rawgResults" class="d-flex gap-2 overflow-auto pb-2"></div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-column flex-xxl-row align-items-center justify-content-between mb-3 gap-3">

    <div class="input-group rounded-pill overflow-hidden border border-opacity-10 bg shadow-sm w-100 w-xxl-50">
        <span class="input-group-text border-0 ps-3 bg-transparent"><i class="material-icons-outlined text-secondary icon-md">&#xe8b6;</i></span>
        <input type="text" id="internalSearchInput" class="form-control border-0 shadow-none bg-transparent" placeholder="<?= __('dashboard_search_collection') ?>" onkeyup="updateView()">
        <span class="input-group-text border-0 pe-3 bg-transparent" style="cursor:pointer" onclick="document.getElementById('internalSearchInput').value=''; updateView();"><i class="material-icons-outlined opacity-50 icon-sm">&#xe5cd;</i></span>
    </div>

    <div class="w-100 flex-grow-1 overflow-hidden">
        <div class="filters-scroll-container">

            <select id="filterPlatform" class="form-select border shadow-sm rounded-3 py-2" onchange="updateView()">
                <option value="all" selected><?= __('modal_platform_label') ?></option>
                <option value="PC">PC (Microsoft Windows)</option>
                <option value="Mac">Mac</option>
                <option value="Linux">Linux</option>
                <option value="PS5">PlayStation 5</option>
                <option value="PS4">PlayStation 4</option>
                <option value="PS3">PlayStation 3</option>
                <option value="PS2">PlayStation 2</option>
                <option value="PlayStation">PlayStation</option>
                <option value="PS Vita">PS Vita</option>
                <option value="PSP">PSP</option>
                <option value="Xbox Series">Xbox Series X|S</option>
                <option value="Xbox One">Xbox One</option>
                <option value="Xbox 360">Xbox 360</option>
                <option value="Xbox">Xbox</option>
                <option value="Switch">Nintendo Switch</option>
                <option value="Wii U">Wii U</option>
                <option value="Wii">Wii</option>
                <option value="GameCube">GameCube</option>
                <option value="Nintendo 64">Nintendo 64</option>
                <option value="SNES">SNES</option>
                <option value="NES">NES</option>
                <option value="Nintendo 3DS">Nintendo 3DS</option>
                <option value="Nintendo DS">Nintendo DS</option>
                <option value="Game Boy Advance">Game Boy Advance</option>
                <option value="Game Boy Color">Game Boy Color</option>
                <option value="Game Boy">Game Boy</option>
                <option value="iOS">iOS</option>
                <option value="Android">Android</option>
                <option value="Sega Dreamcast">Sega Dreamcast</option>
                <option value="Sega Saturn">Sega Saturn</option>
                <option value="Sega Mega Drive">Sega Mega Drive/Genesis</option>
                <option value="Sega Master System">Sega Master System</option>
            </select>

            <select id="filterStatus" class="form-select border shadow-sm rounded-3 py-2" onchange="updateView()">
                <option value="all"><?= __('filter_status') ?></option>
                <option value="not_started"><?= __('status_not_started') ?></option>
                <option value="playing"><?= __('status_playing') ?></option>
                <option value="finished"><?= __('status_finished') ?></option>
                <option value="completed"><?= __('status_completed') ?></option>
                <option value="dropped"><?= __('status_dropped') ?></option>
            </select>

            <select id="sortSelect" class="form-select border shadow-sm rounded-3 py-2" onchange="updateView()">
                <option value="date_desc"><?= __('sort_recent') ?></option>
                <option value="alpha_asc"><?= __('sort_az') ?></option>
                <option value="platform_asc"><?= __('sort_platform') ?></option>
            </select>

            <div class="view-toggle-tabs shadow-sm border border-opacity-10 d-none d-md-flex">
                <input type="radio" name="viewMode" id="btnGridInput" class="view-tab-input"
                    onclick="setView('grid')" <?= (isset($_COOKIE['viewMode']) && $_COOKIE['viewMode'] == 'list') ? '' : 'checked' ?>>
                <label for="btnGridInput" class="view-tab-label">
                    <i class="material-icons-outlined icon-md">&#xe9b0;</i>
                </label>

                <input type="radio" name="viewMode" id="btnListInput" class="view-tab-input"
                    onclick="setView('list')" <?= (isset($_COOKIE['viewMode']) && $_COOKIE['viewMode'] == 'list') ? 'checked' : '' ?>>
                <label for="btnListInput" class="view-tab-label">
                    <i class="material-icons-outlined icon-md">&#xe8ef;</i>
                </label>

                <div class="view-tab-slider"></div>
            </div>
        </div>
    </div>

</div>

<button class="fab-view-toggle shadow-lg" id="fabViewToggle" onclick="toggleMobileView()">
    <i class="material-icons-outlined" id="fabIcon">&#xe8ef;</i>
</button>

<div id="gamesContainer" class="row g-xxl-4 g-md-3 g-2"></div>

<div id="scrollSentinel" class="text-center py-4 my-2">
    <div class="spinner-border text-primary d-none" role="status" id="scrollLoader">
        <span class="visually-hidden"><?= __('dashboard_loading') ?></span>
    </div>
</div>

<div class="modal fade" id="gameModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form action="/save" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="modal-header border-bottom-0 pb-0 d-block">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="modal-title fs-5 fw-bold"><?= __('modal_details_title') ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-pills nav-fill mb-4 bg-body-tertiary rounded-3 p-1" id="gameModalTabs">
                        <li class="nav-item"><button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#tab-info" type="button">Général</button></li>
                        <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#tab-desc" type="button">Description</button></li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tab-info">
                            <input type="hidden" name="game_id" id="gameId">
                            <input type="hidden" name="rawg_id" id="gameRawgId">
                            <div class="row g-4">
                                <div class="col-md-5">
                                    <div class="ratio ratio-1x1 bg-body-tertiary rounded-4 overflow-hidden position-relative group-hover-upload">
                                        <img id="previewImg" src="" class="d-none w-100 h-100 object-fit-cover">
                                        <div id="uploadPlaceholder" class="d-flex flex-column align-items-center justify-content-center h-100 text-secondary">
                                            <i class="material-icons-outlined mb-2 icon-lg">&#xe2c0;</i>
                                            <small><?= __('modal_upload_text') ?></small>
                                        </div>
                                        <input type="file" name="image_upload" class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer" accept="image/*" onchange="previewFile(this)">
                                        <input type="hidden" name="image_url_hidden" id="gameImageHidden">
                                    </div>
                                    <div class="mt-3 d-none d-md-block d-lg-none">
                                        <label class="form-label small fw-bold text-secondary"><?= __('modal_price_label') ?></label>
                                        <div class="input-group">
                                            <input type="number" name="estimated_price_tablet" id="gamePriceTablet" class="form-control rounded-start border-end-0" step="0.01" placeholder="0.00">
                                            <span class="input-group-text bg-body-tertiary border-start-0 rounded-end">€</span>
                                            <button type="button" class="btn btn-primary ms-2 rounded" onclick="searchPrice()" title="<?= __('modal_price_search_title') ?>">
                                                <i class="material-icons icon-md">&#xe8b6;</i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="form-floating mb-3">
                                        <input type="text" name="title" id="gameTitle" class="form-control rounded-3" placeholder="<?= __('modal_title_placeholder') ?>" readonly required>
                                        <label><?= __('modal_title_label') ?></label>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold mb-1 text-secondary"><?= __('modal_genres_label') ?></label>
                                        <input type="text" name="genres" id="gameGenres" class="form-control rounded-3 bg-body-tertiary" placeholder="Action, RPG..." readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold mb-2 text-secondary">Format</label>
                                        <div class="btn-group w-100 shadow-sm" role="group">
                                            <input type="radio" class="btn-check" name="format" id="fmtPhysical" value="physical" autocomplete="off" checked>
                                            <label class="btn btn-outline-secondary" for="fmtPhysical"><i class="material-icons-outlined icon-sm align-middle me-1">&#xe1a1;</i><?= __('dashboard_btn_Physicalformat') ?></label>

                                            <input type="radio" class="btn-check" name="format" id="fmtDigital" value="digital" autocomplete="off">
                                            <label class="btn btn-outline-secondary" for="fmtDigital"><i class="material-icons-outlined icon-sm align-middle me-1">&#xe3dd;</i><?= __('dashboard_btn_Digitalformat') ?></label>
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold mb-1 text-secondary"><?= __('modal_platform_label') ?></label>
                                            <select name="platform" id="gamePlatform" class="form-select rounded-3" required>
                                                <option value="PC">PC (Microsoft Windows)</option>
                                                <option value="Mac">Mac</option>
                                                <option value="Linux">Linux</option>
                                                <option value="PS5">PlayStation 5</option>
                                                <option value="PS4">PlayStation 4</option>
                                                <option value="PS3">PlayStation 3</option>
                                                <option value="PS2">PlayStation 2</option>
                                                <option value="PlayStation">PlayStation</option>
                                                <option value="PS Vita">PS Vita</option>
                                                <option value="PSP">PSP</option>
                                                <option value="Xbox Series">Xbox Series X|S</option>
                                                <option value="Xbox One">Xbox One</option>
                                                <option value="Xbox 360">Xbox 360</option>
                                                <option value="Xbox">Xbox</option>
                                                <option value="Switch">Nintendo Switch</option>
                                                <option value="Wii U">Wii U</option>
                                                <option value="Wii">Wii</option>
                                                <option value="GameCube">GameCube</option>
                                                <option value="Nintendo 64">Nintendo 64</option>
                                                <option value="SNES">SNES</option>
                                                <option value="NES">NES</option>
                                                <option value="Nintendo 3DS">Nintendo 3DS</option>
                                                <option value="Nintendo DS">Nintendo DS</option>
                                                <option value="Game Boy Advance">Game Boy Advance</option>
                                                <option value="Game Boy Color">Game Boy Color</option>
                                                <option value="Game Boy">Game Boy</option>
                                                <option value="iOS">iOS</option>
                                                <option value="Android">Android</option>
                                                <option value="Sega Dreamcast">Sega Dreamcast</option>
                                                <option value="Sega Saturn">Sega Saturn</option>
                                                <option value="Sega Mega Drive">Sega Mega Drive/Genesis</option>
                                                <option value="Sega Master System">Sega Master System</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold mb-1 text-secondary"><?= __('modal_status_label') ?></label>
                                            <select name="status" id="gameStatus" class="form-select rounded-3">
                                                <option value="not_started"><?= __('status_not_started') ?></option>
                                                <option value="playing"><?= __('status_playing') ?></option>
                                                <option value="finished"><?= __('status_finished') ?></option>
                                                <option value="completed"><?= __('status_completed') ?></option>
                                                <option value="dropped"><?= __('status_dropped') ?></option>
                                                <option value="loaned"><?= __('status_loaned') ?></option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold mb-1 text-secondary"><?= __('modal_meta_label') ?></label>
                                        <input type="number" name="metacritic" id="gameMeta" class="form-control rounded-3" placeholder="---" readonly>
                                    </div>
                                    <div class="mt-3 d-md-none d-lg-block">
                                        <label class="form-label small fw-bold text-secondary"><?= __('modal_price_label') ?></label>
                                        <div class="input-group">
                                            <input type="number" name="estimated_price" id="gamePriceDesktop" class="form-control rounded-start border-end-0" step="0.01" placeholder="0.00">
                                            <span class="input-group-text bg-body-tertiary border-start-0 rounded-end">€</span>
                                            <button type="button" class="btn btn-primary ms-2 rounded" onclick="searchPrice()" title="<?= __('modal_price_search_title') ?>">
                                                <i class="material-icons icon-md">&#xe8b6;</i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label small fw-bold mb-1 text-secondary"><?= __('modal_comment_label') ?></label>
                                <textarea name="comment" id="gameComment" class="form-control rounded-3 bg-body-tertiary border-0" rows="2"></textarea>
                            </div>
                            <input type="hidden" name="release_date" id="gameDate">
                            <input type="hidden" name="description" id="gameDesc">
                        </div>
                        <div class="tab-pane fade" id="tab-desc">
                            <div class="mb-3 p-3 bg-body-secondary rounded-3 small border border-opacity-10">
                                <div class="row">
                                    <div class="col-6"><span class="text-muted">Développeur :</span> <strong id="displayDev">Inconnu</strong></div>
                                    <div class="col-6"><span class="text-muted">Éditeur :</span> <strong id="displayPub">Inconnu</strong></div>
                                </div>
                                <div class="row mt-2" id="game-modes-container" style="display: none;">
                                    <div class="col-12"><span class="text-muted">Modes :</span> <strong id="displayModes"></strong></div>
                                </div>
                            </div>

                            <div id="gameDescriptionContent" class="text-secondary mb-4" style="white-space: pre-wrap; max-height: 250px; overflow-y: auto;"></div>

                            <div id="desc-screenshots-container"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4"><?= __('modal_btn_save') ?></button>
                    <button type="button" class="btn btn-light fw-bold rounded-pill px-4" data-bs-dismiss="modal"><?= __('modal_btn_cancel') ?></button>
                </div>
                <input type="hidden" name="developer" id="gameDeveloper">
                <input type="hidden" name="publisher" id="gamePublisher">
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="loanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form action="/loan" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="game_id" id="loanGameId">

                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fs-5 fw-bold"><?= __('loaned_title') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p class="text-secondary small mb-4"><?= __('loaned_desc2') ?> <strong id="loanGameTitle" class="text-body"></strong>. <?= __('loaned_masked_game') ?></p>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary"><?= __('loaned_to') ?></label>
                        <input type="text" name="loaned_to" class="form-control rounded-3" required placeholder="<?= __('loaned_name_placeholder') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary"><?= __('loaned_date') ?></label>
                        <input type="date" name="loaned_date" class="form-control rounded-3" required value="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="submit" class="btn btn-warning fw-bold rounded-pill px-4 text-dark"><?= __('loaned_confirm_return') ?></button>
                    <button type="button" class="btn btn-light fw-bold rounded-pill px-4" data-bs-dismiss="modal"><?= __('modal_btn_cancel') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<template id="gridCardTemplate">
    <?php include 'views/_game_card_template.html'; ?>
</template>
<script>
    window.isDashboard = true;
    let localGames = <?= json_encode($games) ?>;
</script>
<script src="assets/js/dashboard.js"></script>