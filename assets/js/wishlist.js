function openModal() {
    // On s'assure de vider tous les champs, y compris les nouveaux (Desc, Dev, Pub, Meta)
    const fields = ['gameId', 'gameRawgId', 'gameTitle', 'gameGenres', 'gameComment', 'gamePrice', 'gameDate', 'gameDateVisual', 'gameDesc', 'gameDeveloper', 'gamePublisher', 'gameMeta'];
    fields.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });

    const previewImg = document.getElementById('previewImg');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');

    const dateCol = document.getElementById('releaseDateCol');
    if (dateCol) dateCol.classList.add('d-none');
    
    if (previewImg && uploadPlaceholder) {
        previewImg.src = '';
        previewImg.classList.add('d-none');
        uploadPlaceholder.classList.remove('d-none');
    }

    const deleteBtn = document.getElementById('deleteBtnContainer');
    if (deleteBtn) deleteBtn.classList.add('d-none');

    const statusField = document.getElementById('gameStatus');
    if (statusField) statusField.value = 'wishlist';

    const platformSelect = document.getElementById('gamePlatform');
    if (platformSelect && typeof defaultPlatformsHTML !== 'undefined') {
        platformSelect.innerHTML = defaultPlatformsHTML;
    }

    if (typeof currentLibraryFormat !== 'undefined') {
        const fmtPhys = document.getElementById('fmtPhysical');
        const fmtDigi = document.getElementById('fmtDigital');
        if (fmtPhys && fmtDigi) {
            if (currentLibraryFormat === 'digital') {
                fmtDigi.checked = true;
                fmtPhys.checked = false;
            } else {
                fmtPhys.checked = true;
                fmtDigi.checked = false;
            }
        }
    }

    // --- NOUVEAU : Réinitialisation de l'onglet Médias et des infos visuelles ---
    const ytPlayer = document.getElementById('ytPlayer');
    const ytLink = document.getElementById('ytLink');
    if (ytPlayer) ytPlayer.src = '';
    if (ytLink) ytLink.href = '#';

    const displayDev = document.getElementById('displayDev');
    if (displayDev) displayDev.innerText = 'Inconnu';

    const displayPub = document.getElementById('displayPub');
    if (displayPub) displayPub.innerText = 'Inconnu';

    const descContent = document.getElementById('gameDescriptionContent');
    if (descContent) descContent.innerText = "Aucune description.";
    
    const modesContainer = document.getElementById('game-modes-container');
    if (modesContainer) {
        modesContainer.innerHTML = '';
        modesContainer.style.display = 'none';
    }

    const mediaTabContent = document.getElementById('media-tab-content');
    if (mediaTabContent) {
        mediaTabContent.innerHTML = '';
    }
    // ----------------------------------------------------------------------------

    new bootstrap.Modal(document.getElementById('gameModal')).show();
}

function editGame(game) {
    document.getElementById('gameId').value = game.id;
    document.getElementById('gameRawgId').value = game.rawg_id || '';
    document.getElementById('gameTitle').value = game.title;
    
    const platformSelect = document.getElementById('gamePlatform');
    if (platformSelect && typeof defaultPlatformsHTML !== 'undefined') {
        platformSelect.innerHTML = defaultPlatformsHTML;
        const platformExists = Array.from(platformSelect.options).some(opt => opt.value === game.platform);
        
        if (!platformExists && game.platform) {
            const option = document.createElement('option');
            option.value = game.platform;
            option.textContent = game.platform;
            platformSelect.appendChild(option);
        }
    }
    
    if (document.getElementById('gamePlatform')) {
        document.getElementById('gamePlatform').value = game.platform;
    }

    document.getElementById('gameGenres').value = game.genres || '';
    document.getElementById('gameComment').value = game.comment || '';
    document.getElementById('gamePrice').value = game.estimated_price || '';

    // --- Remplissage des champs de Description, Développeur et Éditeur ---
    const safeSet = (id, val) => { const el = document.getElementById(id); if (el) el.value = val; };
    safeSet('gameDesc', game.summary || game.description || '');
    safeSet('gameDeveloper', game.developer || '');
    safeSet('gamePublisher', game.publisher || '');
    safeSet('gameMeta', game.igdb_rating || game.metacritic || '');

    const displayDev = document.getElementById('displayDev');
    if (displayDev) displayDev.innerText = game.developer || 'Inconnu';

    const displayPub = document.getElementById('displayPub');
    if (displayPub) displayPub.innerText = game.publisher || 'Inconnu';

    const descContent = document.getElementById('gameDescriptionContent');
    if (descContent) descContent.innerText = game.summary || game.description || "Aucune description.";
    // -------------------------------------------------------------------------------
    
    document.getElementById('gameDate').value = game.release_date || '';
    const dateVisual = document.getElementById('gameDateVisual');
    const dateCol = document.getElementById('releaseDateCol');

    if (game.release_date) {
        if (dateVisual) dateVisual.value = game.release_date;
        if (dateCol) dateCol.classList.remove('d-none');
    } else {
        if (dateVisual) dateVisual.value = '';
        if (dateCol) dateCol.classList.add('d-none'); 
    }

    const fmtPhys = document.getElementById('fmtPhysical');
    const fmtDigi = document.getElementById('fmtDigital');
    if (fmtPhys && fmtDigi) {
        if (game.format === 'digital') {
            fmtDigi.checked = true;
            fmtPhys.checked = false;
        } else {
            fmtPhys.checked = true;
            fmtDigi.checked = false;
        }
    }

    const previewImg = document.getElementById('previewImg');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    const hiddenImg = document.getElementById('gameImageHidden');

    if (game.image_url) {
        let prevImgUrl = game.image_url;
        if (prevImgUrl.startsWith('//')) prevImgUrl = 'https:' + prevImgUrl;
        else if (!prevImgUrl.startsWith('http') && !prevImgUrl.startsWith('/')) prevImgUrl = '/' + prevImgUrl;
        
        previewImg.src = prevImgUrl;
        previewImg.classList.remove('d-none');
        uploadPlaceholder.classList.add('d-none');
        if (hiddenImg) hiddenImg.value = game.image_url; 
    } else {
        previewImg.classList.add('d-none');
        uploadPlaceholder.classList.remove('d-none');
        if (hiddenImg) hiddenImg.value = '';
    }

    const deleteLink = document.getElementById('deleteLink');
    if (deleteLink) {
        deleteLink.href = "/?action=delete&id=" + game.id;
        document.getElementById('deleteBtnContainer').classList.remove('d-none');
    }

    // --- RECHERCHE IGDB EN ARRIERE-PLAN POUR MEDIAS (Wishlist) ---
    const mediaTabContent = document.getElementById('media-tab-content');
    if (mediaTabContent) {
        mediaTabContent.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
    }

    const igdbId = game.game_id || game.rawg_id;
    if (igdbId) {
        fetch(`/?action=get_igdb_details&id=${igdbId}`)
            .then(res => res.json())
            .then(data => {
                const modesContainer = document.getElementById('game-modes-container');
                if (modesContainer) {
                    if (data.game_modes && data.game_modes.length > 0) {
                        modesContainer.innerHTML = `<strong>Modes de jeu :</strong> ${data.game_modes}`;
                        modesContainer.style.display = 'block';
                    } else {
                        modesContainer.style.display = 'none';
                    }
                }

                if (mediaTabContent) {
                    let mediaHtml = '';
                    if (data.video_id) {
                        mediaHtml += `<div class="video-container mb-4"><iframe width="100%" height="315" src="https://www.youtube.com/embed/${data.video_id}" frameborder="0" allowfullscreen class="rounded"></iframe></div>`;
                    } else {
                        mediaHtml += `<div class="video-container mb-4"><iframe width="100%" height="315" src="https://www.youtube.com/embed?listType=search&list=${encodeURIComponent(game.title + ' game trailer')}" frameborder="0" allowfullscreen class="rounded"></iframe></div>`;
                    }

                    if (data.screenshots && data.screenshots.length > 0) {
                        mediaHtml += `<h6 class="text-uppercase text-muted fw-bold mb-3">Captures d'écran</h6><div class="row g-2 mb-4">`;
                        data.screenshots.forEach(imgUrl => mediaHtml += `<div class="col-6 col-md-4"><a href="${imgUrl}" target="_blank"><img src="${imgUrl}" class="img-fluid rounded shadow-sm" style="object-fit: cover; height: 120px; width: 100%;"></a></div>`);
                        mediaHtml += `</div>`;
                    }

                    if (data.artworks && data.artworks.length > 0) {
                        mediaHtml += `<h6 class="text-uppercase text-muted fw-bold mb-3">Artworks</h6><div class="row g-2">`;
                        data.artworks.forEach(imgUrl => mediaHtml += `<div class="col-6 col-md-4"><a href="${imgUrl}" target="_blank"><img src="${imgUrl}" class="img-fluid rounded shadow-sm" style="object-fit: cover; height: 120px; width: 100%;"></a></div>`);
                        mediaHtml += `</div>`;
                    }
                    mediaTabContent.innerHTML = mediaHtml;
                }
            })
            .catch(() => {
                // En cas d'erreur IGDB, on affiche juste la recherche Youtube de secours
                if (mediaTabContent) {
                    mediaTabContent.innerHTML = `<div class="video-container mb-4"><iframe width="100%" height="315" src="https://www.youtube.com/embed?listType=search&list=${encodeURIComponent(game.title + ' game trailer')}" frameborder="0" allowfullscreen class="rounded"></iframe></div>`;
                }
            });
    } else {
        // Jeu manuel sans ID IGDB
        if (mediaTabContent) {
            mediaTabContent.innerHTML = `<div class="video-container mb-4"><iframe width="100%" height="315" src="https://www.youtube.com/embed?listType=search&list=${encodeURIComponent(game.title + ' game trailer')}" frameborder="0" allowfullscreen class="rounded"></iframe></div>`;
        }
    }
    // ---------------------------------------------------------

    new bootstrap.Modal(document.getElementById('gameModal')).show();
}

function previewFile(input) {
    const file = input.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewImg = document.getElementById('previewImg');
            previewImg.src = e.target.result;
            previewImg.classList.remove('d-none');
            document.getElementById('uploadPlaceholder').classList.add('d-none');
        }
        reader.readAsDataURL(file);
    }
}

/* --- SURCHARGES GLOBALES (Placées en dehors du DOMContentLoaded pour agir AVANT le premier rendu) --- */

window.edit = function(id) {
    const game = localGames.find(g => g.id == id);
    if (game) {
        editGame(game);
    }
};

window.generateGridCard = function(g) {
    const s = statusConfig['wishlist'] || statusConfig['playing']; 
    let img = g.image_url ? g.image_url : '';
    if (img.startsWith('//')) {
        img = 'https:' + img;
    } else if (img && !img.startsWith('http') && !img.startsWith('/')) {
        img = '/' + img;
    }

    // --- Gestion du Format (Physique/Digital) comme sur le Dashboard ---
    const formatIcon = (g.format === 'physical')
        ? `<i class="material-icons-outlined icon-sm text-secondary" title="${LANG.fmt_physical}">&#xe1a1;</i>`
        : `<i class="material-icons-outlined icon-sm text-secondary" title="${LANG.fmt_digital}">&#xe3dd;</i>`;

    // --- Appel avec la plateforme ajoutée ---
    const shadowColor = getNeonColor(g.dominant_color, 0.4, g.platform);
    const borderColor = getNeonColor(g.dominant_color, 0.5, g.platform);

    let metaHtml = '';
    let platIconHtml = '';

    let releaseDateHtml = '';
    if (g.release_date) {
        const d = new Date(g.release_date);
        const formattedDate = d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
        releaseDateHtml = `<div class="small text-primary fw-bold mt-2"><i class="material-icons-outlined icon-sm align-middle me-1">&#xeb9b;</i><?= __('modal_title_placeholder') ?> : ${formattedDate}</div>`;
    }

    // Icône Plateforme
    if (g.platform && g.platform.includes(',')) {
        platIconHtml = '<i class="material-icons-outlined icon-sm me-1">&#xe53b;</i>';
    } else if (platformIcons[g.platform]) {
        platIconHtml = `<i class="${platformIcons[g.platform]} me-1"></i>`;
    } else {
        platIconHtml = '<i class="material-icons-outlined icon-sm me-1">&#xea5b;</i>';
    }

    metaHtml += `<span class="meta-tag">${platIconHtml}${g.platform}</span>`;
    // Ajout du badge Format
    metaHtml += `<span class="meta-tag bg-body-secondary border-0">${formatIcon}</span>`;

    if (g.metacritic_score > 0) {
        let metaIcon = g.metacritic_score >= 75 ? 'text-success' : (g.metacritic_score >= 50 ? 'text-warning' : 'text-danger');
        metaHtml += `<span class="meta-tag" title="${LANG.meta_score}"><i class="svg-icon metacritic-icon ${metaIcon} me-1"></i>${g.metacritic_score}</span>`;
    }

    const imagePlaceholder = `<div class="position-absolute top-0 w-100 h-100 d-flex align-items-center justify-content-center bg-body-tertiary"><i class="material-icons-outlined icon-xl text-secondary opacity-25">&#xea5b;</i></div>`;

    // --- BOUTONS D'ACTION (Overlay Centré) ---
    
    // 1. Bouton Acquérir (Spécifique Wishlist) - Vert
    const acquireBtn = `
        <a href="/?action=acquire&id=${g.id}" class="btn-icon-action btn-light text-success rounded-circle shadow-sm" title="${LANG.btn_acquire}" onclick="return confirm('${LANG.confirm_acquire}')">
            <i class="material-icons-outlined icon-md">&#xe8cc;</i>
        </a>`;

    // 2. Bouton Éditer - Standard
    const editBtn = `
        <button class="btn-icon-action btn-light rounded-circle shadow-sm" onclick='edit(${g.id})' title="${LANG.btn_edit}">
            <i class="material-icons-outlined icon-md">&#xe3c9;</i>
        </button>`;

    // 3. Bouton Supprimer - Rouge
    const deleteBtn = `
        <a href="/?action=delete&id=${g.id}" class="btn-icon-action btn-light text-danger rounded-circle shadow-sm" onclick="return confirm('${LANG.confirm_delete}')" title="${LANG.btn_delete}">
            <i class="material-icons-outlined icon-md">&#xe872;</i>
        </a>`;

    return `
    <div class="col-6 col-sm-6 col-lg-4 col-xl-3 animate-in">
        <div class="game-card-modern"
             onmouseover="this.style.boxShadow='0 25px 60px -12px ${shadowColor}'; this.style.borderColor='${borderColor}'"
             onmouseout="this.style.boxShadow=''; this.style.borderColor='rgba(0,0,0,0.05)'">
            
            <div class="card-cover-container">
                ${img ? `<img src="${img}" class="card-cover-img" loading="lazy">` : imagePlaceholder}
                <span class="status-badge-float"><i class="material-icons-outlined icon-sm me-1">&#xe8cb;</i>Wishlist</span>
            </div>
            
            <div class="card-content-area pb-4">
                <div class="d-flex justify-content-between align-items-start">
                    <h6 class="game-title text-truncate" title="${g.title}">${g.title}</h6>
                </div>
                
                <div class="meta-badges mb-0">
                    ${metaHtml}
                </div>
                ${releaseDateHtml}
                <div class="small text-muted text-truncate mt-2">${translateGenres(g.genres)}</div>
            </div>

            <div class="card-overlay-actions">
                ${acquireBtn}
                ${editBtn}
                ${deleteBtn}
            </div>
        </div>
    </div>`;
};

window.generateListRow = function(g) {
    let finalImg = g.image_url ? g.image_url : '';
    if (finalImg.startsWith('//')) {
        finalImg = 'https:' + finalImg;
    } else if (finalImg && !finalImg.startsWith('http') && !finalImg.startsWith('/')) {
        finalImg = '/' + finalImg;
    }

    const img = finalImg ?
        `<img src="${finalImg}" class="rounded-3 shadow-sm object-fit-cover" style="width:48px;height:48px;">` :
        `<div class="rounded-3 bg-body-secondary d-flex align-items-center justify-content-center" style="width:48px;height:48px"><i class="material-icons-outlined text-secondary icon-md">&#xea5b;</i></div>`;


    let platIconHtml = '';
    if (g.platform && g.platform.includes(',')) {
        platIconHtml = '<i class="material-icons-outlined icon-sm me-1">&#xe53b;</i>';
    } else if (platformIcons[g.platform]) {
        platIconHtml = `<i class="${platformIcons[g.platform]} me-1"></i>`;
    } else {
        platIconHtml = '<i class="material-icons-outlined icon-sm me-1">&#xe338;</i>';
    }

    let releaseDateHtml = '';
    if (g.release_date) {
        const d = new Date(g.release_date);
        releaseDateHtml = `<span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2"><i class="material-icons-outlined icon-sm align-middle me-1">&#xeb9b;</i>${d.toLocaleDateString('fr-FR')}</span>`;
    }

    const acquireBtn = `<a href="/?action=acquire&id=${g.id}" class="btn-icon-action text-success" title="${LANG.btn_acquire}" onclick="return confirm('${LANG.confirm_acquire}')"><i class="material-icons-outlined icon-md">&#xe8cc;</i></a>`;

    return `
    <tr>
        <td class="ps-4">
            <div class="d-flex align-items-center gap-3">
                ${img}
                <div>
                    <div class="fw-bold text-body">${g.title} ${releaseDateHtml}</div>
                    <div class="small text-secondary">${translateGenres(g.genres)}</div>
                </div>
            </div>
        </td>
        <td class="d-none d-sm-table-cell"><span class="meta-tag border">${platIconHtml}${g.platform}</span></td>
        
        <td class="pe-4">
            <div class="d-flex justify-content-end align-items-center gap-1">
                ${acquireBtn}
                <button class="btn-icon-action" onclick='edit(${g.id})' title="${LANG.btn_edit}"><i class="material-icons-outlined icon-md">&#xe3c9;</i></button>
                <a href="/?action=delete&id=${g.id}" class="btn-action btn-icon-action btn-light text-danger" onclick="return confirm('${LANG.confirm_delete}')" title="${LANG.btn_delete}"><i class="material-icons-outlined icon-md">&#xe872;</i></a>
            </div>
        </td>
    </tr>`;
};

window.loadMoreGames = function() {
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
            const existingHeader = container.querySelector('thead tr');
            const isWrongHeader = existingHeader && existingHeader.children.length > 3;

            if (!tbody || isWrongHeader) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden" style="background-color: var(--bs-body-bg);">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-body-tertiary text-secondary small text-uppercase fw-bold">
                                        <tr>
                                            <th class="ps-4 py-3">${LANG.table_game}</th>
                                            <th class="d-none d-sm-table-cell">${LANG.table_platform}</th>
                                            <th class="text-end text-nowrap pe-4">${LANG.table_actions}</th>
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
};

/* --- INITIALISATION --- */

document.addEventListener('DOMContentLoaded', () => {
    const hiddenDateInput = document.getElementById('gameDate');
    if(hiddenDateInput) {
        const descriptor = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
        Object.defineProperty(hiddenDateInput, 'value', {
            set: function(val) {
                const oldVal = this.value;
                descriptor.set.call(this, val);
                if(oldVal !== val) {
                    const visual = document.getElementById('gameDateVisual');
                    if(visual) visual.value = val;
                }
            },
            get: function() { return descriptor.get.call(this); }
        });
    }
    
    if(typeof updateView === 'function') {
        updateView();
    }
});