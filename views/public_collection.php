<?php
// On récupère si l'utilisateur connecté suit déjà ce profil
$isFollowing = false;
if (isset($_SESSION['user_id']) && isset($owner['id'])) {
    require_once dirname(__DIR__) . '/models/User.php';
    $uModel = new User($db);
    $followingIds = $uModel->getFollowedIds($_SESSION['user_id']);
    $isFollowing = in_array($owner['id'], $followingIds);
}
?>

<div class="row mb-5 align-items-center">
    <div class="col-md-8 d-flex align-items-center gap-3">
        <?php if (!empty($owner['avatar_url'])): ?>
            <img src="<?= $owner['avatar_url'] ?>" class="rounded-circle shadow object-fit-cover border border-4 border-white flex-shrink-0" style="width: 100px; height: 100px;">
        <?php else: ?>
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow border border-4 border-white flex-shrink-0" style="width: 100px; height: 100px; font-size: 2.5rem; font-weight:bold;">
                <?= strtoupper(substr($owner['username'], 0, 1)) ?>
            </div>
        <?php endif; ?>

        <div>
            <h2 class="fw-light mb-0"><?= __('public_collection_title') ?> <span class="fw-bold text-primary"><?= htmlspecialchars($owner['username']) ?></span></h2>
            <p class="text-secondary mb-2"><?= count($games) ?> <?= __('public_collection_count') ?></p>

            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $owner['id']): ?>
                <?php if ($isFollowing): ?>
                    <a href="/toggle_follow&id=<?= $owner['id'] ?>&do=unfollow" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="fas fa-check me-1"></i> <?= __('public_collection_following') ?>
                    </a>
                <?php else: ?>
                    <a href="/toggle_follow&id=<?= $owner['id'] ?>&do=follow" class="btn btn-primary btn-sm rounded-pill px-3">
                        <i class="fas fa-user-plus me-1"></i> <?= __('public_collection_follow') ?>
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="d-flex flex-column flex-xxl-row align-items-center justify-content-between mb-3 gap-2">

    <div class="input-group rounded-pill overflow-hidden border border-opacity-10 bg shadow-sm w-100 w-xxl-50">
        <span class="input-group-text border-0 ps-3 bg-transparent">
            <i class="material-icons-outlined text-secondary icon-md">&#xe8b6;</i>
        </span>
        <input type="text" id="publicSearchInput" class="form-control border-0 shadow-none bg-transparent" placeholder="<?= __('public_collection_search')?>">
        <span class="input-group-text border-0 pe-3 bg-transparent" style="cursor:pointer" id="btnClearSearch">
            <i class="material-icons-outlined opacity-50 icon-sm">&#xe5cd;</i>
        </span>
    </div>

    <div class="d-flex flex-wrap justify-content-between justify-content-xxl-end gap-2 w-100 w-xxl-auto">

        <select id="filterPlatform" class="form-select border shadow-sm rounded-3 py-2 bg-body" style="width: auto; cursor: pointer;">
            <option value="all"><?= __('filter_platform') ?? 'Plateforme' ?></option>
            <option value="PS5">PlayStation 5</option>
            <option value="PS4">PlayStation 4</option>
            <option value="Xbox Series">Xbox Series</option>
            <option value="Switch">Switch</option>
            <option value="PC">PC / Steam</option>
        </select>

        <select id="filterStatus" class="form-select border shadow-sm rounded-3 py-2 bg-body" style="width: auto; cursor: pointer;">
            <option value="all"><?= __('filter_status') ?? 'Statut' ?></option>
            <option value="not_started"><?= __('status_not_started') ?></option>
            <option value="playing"><?= __('status_playing') ?></option>
            <option value="finished"><?= __('status_finished') ?></option>
            <option value="completed"><?= __('status_completed') ?></option>
            <option value="wishlist"><?= __('status_wishlist') ?></option>
            <option value="dropped"><?= __('status_dropped') ?></option>
        </select>

        <select id="sortSelect" class="form-select border shadow-sm rounded-3 py-2 bg-body" style="width: auto; cursor: pointer;">
            <option value="date_desc"><?= __('sort_recent') ?></option>
            <option value="alpha_asc"><?= __('sort_az') ?></option>
            <option value="rating_desc"><?= __('sort_rating') ?></option>
            <option value="status_asc"><?= __('sort_status') ?></option>
            <option value="platform_asc"><?= __('sort_platform') ?></option>
        </select>

        <div class="bg-body rounded-3 shadow-sm p-1 d-flex">
            <button class="btn btn-sm btn-light rounded-2 active border-0" id="btnGrid">
                <i class="material-icons-outlined icon-md">&#xe9b0;</i>
            </button>
            <button class="btn btn-sm btn-light rounded-2 border-0" id="btnList">
                <i class="material-icons-outlined icon-md">&#xe8ef;</i>
            </button>
        </div>
    </div>

</div>

<div id="gamesContainer" class="row g-4"></div>

<script>
    window.publicGamesData = <?= json_encode($games) ?>;
</script>
<script src="assets/js/public_collection.js"></script>