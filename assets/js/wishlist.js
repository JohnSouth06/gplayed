function openModal() {
    const fields = ['gameId', 'gameTitle', 'gameGenres', 'gameComment', 'gamePrice', 'gameDate', 'gameDateVisual'];
    fields.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });

    const previewImg = document.getElementById('previewImg');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    
    if (previewImg && uploadPlaceholder) {
        previewImg.src = '';
        previewImg.classList.add('d-none');
        uploadPlaceholder.classList.remove('d-none');
    }

    const deleteBtn = document.getElementById('deleteBtnContainer');
    if (deleteBtn) deleteBtn.classList.add('d-none');
    
    const statusField = document.getElementById('gameStatus');
    if (statusField) statusField.value = 'wishlist';

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
    
    document.getElementById('gameDate').value = game.release_date || '';
    const dateVisual = document.getElementById('gameDateVisual');
    if (dateVisual) dateVisual.value = game.release_date || '';

    const previewImg = document.getElementById('previewImg');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    const hiddenImg = document.getElementById('gameImageHidden');

    if (game.image_url) {
        previewImg.src = game.image_url;
        previewImg.classList.remove('d-none');
        uploadPlaceholder.classList.add('d-none');
        hiddenImg.value = game.image_url;
    }

    const deleteLink = document.getElementById('deleteLink');
    if (deleteLink) {
        deleteLink.href = "/delete?id=" + game.id;
        document.getElementById('deleteBtnContainer').classList.remove('d-none');
    }

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

    window.edit = function(id) {
        const game = localGames.find(g => g.id == id);
        if (game) {
            editGame(game);
        }
    };

    window.generateGridCard = function(g) {
        const s = statusConfig[g.status] || statusConfig['playing'];
        const img = g.image_url ? g.image_url : '';

        const formatIcon = (g.format === 'physical')
            ? `<i class="material-icons-outlined icon-sm text-secondary me-1" title="${LANG.fmt_physical}">&#xe1a1;</i>`
            : `<i class="material-icons-outlined icon-sm text-secondary me-1" title="${LANG.fmt_digital}">&#xe3dd;</i>`;

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
            metaHtml += `<span class="meta-tag" title="${LANG.meta_score}"><i class="svg-icon metacritic-icon ${metaIcon} me-1"></i>${g.metacritic_score}</span>`;
        }

        if (g.estimated_price > 0) {
            metaHtml += `<span class="meta-tag text-primary bg-primary-subtle border-primary-subtle"><i class="material-icons-outlined icon-sm me-1">&#xe54e;</i>${g.estimated_price}€</span>`;
        }

        const imagePlaceholder = `<div class="position-absolute top-0 w-100 h-100 d-flex align-items-center justify-content-center bg-body-tertiary"><i class="material-icons-outlined icon-xl text-secondary opacity-25">&#xea5b;</i></div>`;
        
        const acquireBtn = `<a href="/?action=acquire&id=${g.id}" class="btn-icon-action text-success" title="${LANG.btn_acquire}" onclick="return confirm('${LANG.confirm_acquire}')"><i class="material-icons-outlined icon-md">&#xe8cc;</i></a>`;

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
                            ${formatIcon} ${g.genres || `<span class="opacity-50">${LANG.unknown}</span>`}
                        </div>
                        <div class="d-flex gap-2">
                            ${acquireBtn}
                            <button class="btn-icon-action" onclick='edit(${g.id})' title="${LANG.btn_edit}"><i class="material-icons-outlined icon-md">&#xe3c9;</i></button>
                            <a href="/?action=delete&id=${g.id}" class="btn-icon-action text-danger" onclick="return confirm('${LANG.confirm_delete}')" title="${LANG.btn_delete}"><i class="material-icons-outlined icon-md">&#xe872;</i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
    };

    window.generateListRow = function(g) {
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

        const acquireBtn = `<a href="/?action=acquire&id=${g.id}" class="btn-icon-action text-success" title="${LANG.btn_acquire}" onclick="return confirm('${LANG.confirm_acquire}')"><i class="material-icons-outlined icon-md">&#xe8cc;</i></a>`;

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
                ${acquireBtn}
                <button class="btn-icon-action" onclick='edit(${g.id})' title="${LANG.btn_edit}"><i class="material-icons-outlined icon-md">&#xe3c9;</i></button>
                <a href="/?action=delete&id=${g.id}" class="btn-action btn-icon-action btn-light text-danger" onclick="return confirm('${LANG.confirm_delete}')" title="${LANG.btn_delete}"><i class="material-icons-outlined icon-md">&#xe872;</i></a>
            </td>
        </tr>`;
    };
    
    if(typeof updateView === 'function') {
        updateView();
    }
});