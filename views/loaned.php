<link rel="stylesheet" href="assets/css/dashboard.css">

<?php
$totalLoaned = is_array($games) ? count($games) : 0;
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 gap-3 pt-2">
    <div>
        <h2 class="h2 dashboard-welcome mb-1 fw-light"><?= __('loaned_title_part1') ?> <span class="text-warning fw-bold"><?= __('loaned_title_part2') ?></span> 🤝</h2>
    </div>
    <div class="stat-widget">
        <i class="material-icons stat-icon text-warning">&#xe0e3;</i>
        <div class="stat-content">
            <span class="stat-label"><?= __('loaned_count_label') ?></span>
            <span class="stat-value text-warning animate-counter" data-target="<?= $totalLoaned ?>">0</span>
        </div>
    </div>
</div>

<div class="d-flex flex-column flex-xxl-row align-items-center justify-content-between mb-3 gap-3">
    <div class="input-group rounded-pill overflow-hidden border border-opacity-10 bg shadow-sm w-100 w-xxl-50">
        <span class="input-group-text border-0 ps-3 bg-transparent"><i class="material-icons-outlined text-secondary icon-md">&#xe8b6;</i></span>
        <input type="text" id="internalSearchInput" class="form-control border-0 shadow-none bg-transparent" placeholder="<?= __('loaned_research_placeholder') ?>" onkeyup="updateView()">
        <span class="input-group-text border-0 pe-3 bg-transparent" style="cursor:pointer" onclick="document.getElementById('internalSearchInput').value=''; updateView();"><i class="material-icons-outlined opacity-50 icon-sm">&#xe5cd;</i></span>
    </div>

    <div class="w-100 flex-grow-1 overflow-hidden"> 
        <div class="filters-scroll-container">
            <select id="filterPlatform" class="form-select border shadow-sm rounded-3 py-2" onchange="updateView()">
                <option value="all"><?= __('filter_platform') ?></option>
                <option value="PS5">PlayStation 5</option>
                <option value="PS4">PlayStation 4</option>
                <option value="Xbox Series">Xbox Series</option>
                <option value="Switch">Switch 1 / 2</option>
                <option value="PC">PC / Steam</option>
            </select>

            <input type="hidden" id="filterStatus" value="loaned">

            <select id="sortSelect" class="form-select border shadow-sm rounded-3 py-2" onchange="updateView()">
                <option value="date_desc"><?= __('sort_recent') ?></option>
                <option value="alpha_asc"><?= __('sort_az') ?></option>
                <option value="rating_desc"><?= __('sort_rating') ?></option>
                <option value="platform_asc"><?= __('sort_platform') ?></option>
            </select>

            <div class="bg-body rounded-3 shadow-sm border p-1 d-none d-md-flex view-toggle-desktop">
                <button class="btn btn-sm btn-light rounded-2 active border-0" id="btnGrid" onclick="setView('grid')"><i class="material-icons-outlined icon-md">&#xe9b0;</i></button>
                <button class="btn btn-sm btn-light rounded-2 border-0" id="btnList" onclick="setView('list')"><i class="material-icons-outlined icon-md">&#xe8ef;</i></button>
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

<template id="gridCardTemplate">
    <?php include 'views/_game_card_template.html'; ?>
</template>

<script>
    // On indique au script JS qu'on est sur la page des prêts
    window.isLoanedPage = true;
    let localGames = <?= json_encode($games) ?>;
</script>
<script src="assets/js/dashboard.js"></script>