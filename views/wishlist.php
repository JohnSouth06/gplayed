<link rel="stylesheet" href="assets/css/dashboard.css">

<?php
$username = $_SESSION['username'] ?? 'Gamer';
$totalWishlist = is_array($games) ? count($games) : 0;

/**
 * Génère l'icône de plateforme identique au CSS du dashboard
 */
function getPlatformIconClass($platform) {
    $p = strtolower($platform);
    if (strpos($p, 'ps') !== false || strpos($p, 'playstation') !== false) return 'svg-icon ps-icon';
    if (strpos($p, 'xbox') !== false) return 'svg-icon xbox-icon';
    if (strpos($p, 'switch') !== false || strpos($p, 'nintendo') !== false) return 'svg-icon switch-icon';
    if (strpos($p, 'pc') !== false || strpos($p, 'steam') !== false) return 'svg-icon pc-icon';
    return 'material-icons-outlined icon-sm'; // Fallback
}

/**
 * Génère la couleur ombrée (approximation PHP de la fonction JS getNeonColor)
 */
function getShadowStyle($color) {
    if (empty($color) || $color === 'null') return '';
    // On nettoie la chaîne pour avoir juste les chiffres rgb
    // Format attendu en DB : "rgb(r, g, b)"
    return "box-shadow: 0 25px 60px -12px " . str_replace('rgb', 'rgba', str_replace(')', ', 0.4)', $color)) . "; border-color: " . str_replace('rgb', 'rgba', str_replace(')', ', 0.5)', $color)) . ";";
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 gap-3 pt-2">
    <div>
        <h2 class="h2 dashboard-welcome mb-1 fw-light"><?= __('wishlist_title') ?></h2>
    </div>
    <div class="stat-pill"><i class="material-icons text-danger align-top icon-lg pe-2">&#xe8b1;</i><?= __('wishlist_count_label') ?> <strong><?= $totalWishlist ?></strong></div>
</div>

<div class="card bg-body-primaary border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
    <div class="card-header accordion-trigger bg-transparent border-0 p-3 p-md-4 d-flex justify-content-between align-items-center <?= isset($_GET['open_add']) ? '' : 'collapsed' ?>"
        data-bs-toggle="collapse" data-bs-target="#addGameSection" aria-expanded="<?= isset($_GET['open_add']) ? 'true' : 'false' ?>">
        <h5 class="mb-0 fw-bold text-primary d-flex align-items-center gap-2">
            <i class="material-icons icon-md fs-2">&#xea28;</i><?= __('wishlist_add_panel') ?>
        </h5>
        <i class="material-icons-outlined text-secondary rotate-icon icon-md">&#xe5cf;</i>
    </div>

    <div class="collapse <?= isset($_GET['open_add']) ? 'show' : '' ?>" id="addGameSection">
        <div class="card-body px-4 pb-4 pt-0">
            <hr class="text-secondary opacity-10 my-2 mb-4">
            
            <div class="d-flex flex-column flex-md-row gap-3 align-items-center">
                <div class="flex-grow-1 w-100">
                    <div class="search-wrapper mt-0 mb-2">
                        <div class="search-box">
                            <i class="material-icons-outlined search-icon icon-md">&#xe8b6;</i>
                            <input type="text" id="rawgSearchInput" class="form-control border rounded-pill search-input" placeholder="<?= __('wishlist_search_placeholder') ?>" onkeypress="handleEnter(event)">
                        </div>
                    </div>
                </div>
                <button class="btn btn-outline-primary shadow-sm rounded-pill fw-bold px-4 py-2 w-auto text-nowrap" onclick="openModal()">
                    <i class="material-icons-outlined icon-sm fs-4 me-2">&#xe145;</i><?= __('wishlist_manual_add') ?>
                </button>
            </div>
            
            <div id="rawgContainer" class="mt-3 d-none border-top pt-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0 text-secondary small fw-bold text-uppercase"><?= __('dashboard_internet_results') ?></h6>
                    <button type="button" class="btn-close btn-sm" onclick="closeSearch()"></button>
                </div>
                <div id="rawgLoading" class="text-center d-none py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>
                <div id="rawgResults" class="d-flex gap-2 overflow-auto pb-2"></div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-column flex-xxl-row align-items-center justify-content-between mb-3 gap-2">

    <div class="input-group rounded-pill overflow-hidden border border-opacity-10 bg shadow-sm w-100 w-xxl-50">
        <span class="input-group-text border-0 ps-3 bg-transparent"><i class="material-icons-outlined text-secondary icon-md">&#xe8b6;</i></span>
        <input type="text" id="internalSearchInput" class="form-control border-0 shadow-none bg-transparent" placeholder="<?= __('wishlist_search_collection_placeholder') ?>" onkeyup="updateView()">
        <span class="input-group-text border-0 pe-3 bg-transparent" style="cursor:pointer" onclick="document.getElementById('internalSearchInput').value=''; updateView();"><i class="material-icons-outlined opacity-50 icon-sm">&#xe5cd;</i></span>
    </div>

    <div class="d-flex flex-wrap justify-content-between justify-content-xxl-end gap-2 w-100 w-xxl-auto">

        <select id="filterPlatform" class="form-select border shadow-sm rounded-3 py-2 bg-body" style="width: auto; cursor: pointer;" onchange="updateView()">
            <option value="all"><?= __('filter_platform') ?></option>
            <option value="PS5">PlayStation 5</option>
            <option value="PS4">PlayStation 4</option>
            <option value="Xbox Series">Xbox Series</option>
            <option value="Switch">Switch 1 / 2</option>
            <option value="PC">PC / Steam</option>
        </select>

        <input type="hidden" id="filterStatus" value="wishlist">

        <select id="sortSelect" class="form-select border shadow-sm rounded-3 py-2 bg-body" style="width: auto; cursor: pointer;" onchange="updateView()">
            <option value="date_desc"><?= __('sort_recent') ?></option>
            <option value="alpha_asc"><?= __('sort_az') ?></option>
            <option value="rating_desc"><?= __('sort_rating') ?></option>
            <option value="platform_asc"><?= __('sort_platform') ?></option>
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
        <span class="visually-hidden"><?= __('dashboard_loading') ?></span>
    </div>
</div>

<div class="modal fade" id="gameModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form action="/save" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <input type="hidden" name="status" value="wishlist" id="gameStatus">
                <input type="hidden" name="release_date" id="gameDate"> <div class="d-none">
                    <input type="radio" id="fmtPhysical" name="format" value="physical" checked>
                    <input type="radio" id="fmtDigital" name="format" value="digital">
                    <div id="modalTabs"></div>
                    <div id="multiPlatformContainer"><div id="platformInputsList"></div><input id="gamePlatformCustom"></div>
                    <input id="gameMeta"><input id="gameRating"><input id="gameDesc">
                </div>

                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fs-5 fw-bold"><?= __('wishlist_modal_title') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <input type="hidden" name="game_id" id="gameId">
                    <input type="hidden" name="rawg_id" id="gameRawgId">
                    
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="ratio ratio-1x1 bg-body-tertiary rounded-4 overflow-hidden position-relative group-hover-upload">
                                <img id="previewImg" src="" class="d-none w-100 h-100 object-fit-cover">
                                <div id="uploadPlaceholder" class="d-flex flex-column align-items-center justify-content-center h-100 text-secondary">
                                    <i class="material-icons-outlined mb-2 icon-lg">&#xe2c0;</i>
                                    <small><?= __('wishlist_field_image') ?></small>
                                </div>
                                <input type="file" name="image_upload" class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer" accept="image/*" onchange="previewFile(this)">
                                <input type="hidden" name="image_url_hidden" id="gameImageHidden">
                            </div>
                            
                            <div class="mt-3">
                                <label class="form-label small fw-bold text-secondary"><?= __('wishlist_field_price') ?></label>
                                <div class="input-group">
                                    <input type="number" name="estimated_price" id="gamePrice" class="form-control rounded-start border-end-0" step="0.01" placeholder="0.00">
                                    <span class="input-group-text bg-body-tertiary border-start-0 rounded-end">€</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="form-floating mb-3">
                                <input type="text" name="title" id="gameTitle" class="form-control rounded-3" placeholder="<?= __('modal_title_placeholder') ?>" required>
                                <label><?= __('modal_title_label') ?></label>
                            </div>
                            
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-bold mb-1 text-secondary"><?= __('filter_platform') ?></label>
                                    <select name="platform" id="gamePlatform" class="form-select rounded-3" required>
                                        <option value="PS5">PlayStation 5</option>
                                        <option value="PS4">PlayStation 4</option>
                                        <option value="Xbox Series">Xbox Series</option>
                                        <option value="Switch">Switch</option>
                                        <option value="PC">PC</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold mb-1 text-secondary"><?= __('wishlist_release_date') ?></label>
                                    <input type="date" id="gameDateVisual" class="form-control rounded-3" onchange="document.getElementById('gameDate').value = this.value">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold mb-1 text-secondary"><?= __('modal_genres_label') ?></label>
                                <input type="text" name="genres" id="gameGenres" class="form-control rounded-3" placeholder="Action, RPG...">
                            </div>

                            <div class="mt-3">
                                <label class="form-label small fw-bold mb-1 text-secondary"><?= __('wishlist_field_reason') ?></label>
                                <textarea name="comment" id="gameComment" class="form-control rounded-3 bg-body-tertiary border-0" rows="2" placeholder="<?= __('wishlist_placeholder_reason') ?>"></textarea>
                            </div>

                            <div id="deleteBtnContainer" class="mt-3 d-none text-end">
                                <a href="#" id="deleteLink" class="text-danger small text-decoration-none"><i class="material-icons align-middle fs-6 me-1">&#xe872;</i><?= __('wishlist_remove') ?></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4"><?= __('modal_btn_save') ?></button>
                    <button type="button" class="btn btn-light fw-bold rounded-pill px-4" data-bs-dismiss="modal"><?= __('modal_btn_cancel') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let localGames = <?= json_encode($games) ?>;

    function openModal() {
        document.getElementById('gameId').value = '';
        document.getElementById('gameTitle').value = '';
        document.getElementById('gameGenres').value = '';
        document.getElementById('gameComment').value = '';
        document.getElementById('gamePrice').value = '';
        
        // Reset Date (Visuel + Caché)
        document.getElementById('gameDate').value = ''; 
        document.getElementById('gameDateVisual').value = '';

        document.getElementById('previewImg').src = '';
        document.getElementById('previewImg').classList.add('d-none');
        document.getElementById('uploadPlaceholder').classList.remove('d-none');
        document.getElementById('deleteBtnContainer').classList.add('d-none');
        
        // Reset Status pour Wishlist
        document.getElementById('gameStatus').value = 'wishlist';

        new bootstrap.Modal(document.getElementById('gameModal')).show();
    }

    function editGame(game) {
        document.getElementById('gameId').value = game.id;
        document.getElementById('gameRawgId').value = game.rawg_id || '';
        document.getElementById('gameTitle').value = game.title;
        document.getElementById('gamePlatform').value = game.platform;
        document.getElementById('gameGenres').value = game.genres || '';
        document.getElementById('gameComment').value = game.comment || '';
        document.getElementById('gamePrice').value = game.estimated_price || '';
        
        // Gestion Date
        document.getElementById('gameDate').value = game.release_date || '';
        document.getElementById('gameDateVisual').value = game.release_date || '';

        if (game.image_url) {
            document.getElementById('previewImg').src = game.image_url;
            document.getElementById('previewImg').classList.remove('d-none');
            document.getElementById('uploadPlaceholder').classList.add('d-none');
            document.getElementById('gameImageHidden').value = game.image_url;
        }

        const deleteLink = document.getElementById('deleteLink');
        deleteLink.href = "/delete?id=" + game.id;
        document.getElementById('deleteBtnContainer').classList.remove('d-none');

        new bootstrap.Modal(document.getElementById('gameModal')).show();
    }

    function previewFile(input) {
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('previewImg').src = e.target.result;
                document.getElementById('previewImg').classList.remove('d-none');
                document.getElementById('uploadPlaceholder').classList.add('d-none');
            }
            reader.readAsDataURL(file);
        }
    }

    // SYSTEME DE SYNCHRONISATION DATE (Pour que RAWG remplisse le champ visuel)
    const hiddenDateInput = document.getElementById('gameDate');
    if(hiddenDateInput) {
        // On intercepte les changements de valeur faits par dashboard.js
        const descriptor = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
        Object.defineProperty(hiddenDateInput, 'value', {
            set: function(val) {
                const oldVal = this.value;
                descriptor.set.call(this, val);
                if(oldVal !== val) document.getElementById('gameDateVisual').value = val;
            },
            get: function() {
                return descriptor.get.call(this);
            }
        });
    }
</script>
<script src="assets/js/dashboard.js"></script>