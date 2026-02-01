/**
 * GESTION DE LA COLLECTION PUBLIQUE
 * Gère la recherche, les filtres, le tri et l'affichage (Grille/Liste)
 */

document.addEventListener('DOMContentLoaded', function() {
    // 1. Récupération des données injectées via PHP (window.publicGamesData)
    const gamesData = window.publicGamesData || [];
    let currentView = localStorage.getItem('publicViewMode') || 'grid';
    
    // Configuration LANG (Traductions depuis le système global)
    const statusConfig = {
        'not_started': { label: LANG.status_not_started, class: 'bg-secondary', icon: '&#xe837;' },
        'playing':     { label: LANG.status_playing,     class: 'bg-info',      icon: '&#xea5b;' },      
        'finished':    { label: LANG.status_finished,    class: 'bg-success',   icon: '&#xe86c;' },  
        'completed':   { label: LANG.status_completed,   class: 'bg-warning text-dark', icon: '&#xea23;' }, 
        'dropped':     { label: LANG.status_dropped,     class: 'bg-danger',    icon: '&#xe14b;' },    
        'wishlist':    { label: LANG.status_wishlist,    class: 'bg-primary text-white', icon: '&#xe8b1;' } 
    };

    const platformIcons = { 
        'PS5': 'svg-icon ps-icon', 'PS4': 'svg-icon ps-icon', 
        'Xbox Series': 'svg-icon xbox-icon', 'Xbox': 'svg-icon xbox-icon', 
        'Switch': 'svg-icon switch-icon', 'PC': 'svg-icon pc-icon' 
    };

    // 2. Éléments DOM
    const container = document.getElementById('gamesContainer');
    const searchInput = document.getElementById('publicSearchInput');
    const btnClear = document.getElementById('btnClearSearch');
    const filterPlatform = document.getElementById('filterPlatform');
    const filterStatus = document.getElementById('filterStatus');
    const sortSelect = document.getElementById('sortSelect');
    const btnGrid = document.getElementById('btnGrid');
    const btnList = document.getElementById('btnList');

    // Sécurité : si la page n'a pas les éléments (ex: erreur de chargement), on arrête.
    if (!container || !searchInput) return;

    // 3. Helpers
    function getNeonColor(rgbString, opacity = 1) {
        if (!rgbString || rgbString === 'null') return `rgba(255, 255, 255, ${opacity})`;
        const match = rgbString.match(/\d+/g);
        if (!match || match.length < 3) return `rgba(255, 255, 255, ${opacity})`;
        let [r, g, b] = match.map(Number);
        return `rgba(${r}, ${g}, ${b}, ${opacity})`;
    }

    // 4. Moteur de Tri et Filtre
    function getProcessedGames() {
        const query = searchInput.value.toLowerCase();
        const platform = filterPlatform.value;
        const status = filterStatus.value;
        const sort = sortSelect.value;

        // Filtrage
        let filtered = gamesData.filter(g => {
            // Recherche texte
            if (query && !g.title.toLowerCase().includes(query)) return false;
            
            // Filtre Plateforme
            if (platform !== 'all') {
                if (g.platform === 'Multiplateforme') return true; 
                if (!g.platform.includes(platform)) return false;
            }

            // Filtre Statut
            if (status !== 'all' && g.status !== status) return false;

            return true;
        });

        // Tri
        filtered.sort((a, b) => {
            const valA = (key) => a[key] || 0;
            const valB = (key) => b[key] || 0;

            switch (sort) {
                case 'date_desc': return new Date(b.created_at || 0) - new Date(a.created_at || 0);
                case 'alpha_asc': return a.title.localeCompare(b.title);
                case 'rating_desc': return valB('user_rating') - valA('user_rating');
                case 'status_asc': return (a.status || '').localeCompare(b.status || '');
                case 'platform_asc': return (a.platform || '').localeCompare(b.platform || '');
                default: return 0;
            }
        });

        return filtered;
    }

    // 5. Générateurs HTML
    function generateGridCard(g) {
        const s = statusConfig[g.status] || statusConfig['playing'];
        const img = g.image_url ? g.image_url : '';
        const shadowColor = getNeonColor(g.dominant_color, 0.4);
        const borderColor = getNeonColor(g.dominant_color, 0.5);

        let platIconHtml = '<i class="material-icons-outlined icon-sm me-1">&#xea5b;</i>';
        if (g.platform && g.platform.includes(',')) platIconHtml = '<i class="material-icons-outlined icon-sm me-1">&#xe53b;</i>';
        else if (platformIcons[g.platform]) platIconHtml = `<i class="${platformIcons[g.platform]} me-1"></i>`;

        let metaHtml = `<span class="meta-tag">${platIconHtml}${g.platform}</span>`;
        if (g.user_rating > 0) metaHtml += `<span class="meta-tag text-warning bg-warning-subtle border-warning-subtle"><i class="material-icons-outlined icon-sm filled-icon me-1">&#xe838;</i>${g.user_rating}</span>`;

        const imagePlaceholder = `<div class="position-absolute top-0 w-100 h-100 d-flex align-items-center justify-content-center bg-body-tertiary"><i class="material-icons-outlined icon-xl text-secondary opacity-25">&#xea5b;</i></div>`;

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
                    <h6 class="game-title text-truncate" title="${g.title}">${g.title}</h6>
                    <div class="meta-badges">${metaHtml}</div>
                    <div class="mt-auto pt-3 border-top border-light-subtle ${!g.comment ? 'd-none' : ''}">
                         <p class="small text-secondary mb-0 fst-italic text-truncate">${g.comment || ''}</p>
                    </div>
                </div>
            </div>
        </div>`;
    }

    function generateListTable(games) {
        if(games.length === 0) return '';
        let rows = '';
        games.forEach(g => {
            const s = statusConfig[g.status] || statusConfig['playing'];
            const img = g.image_url ?
                `<img src="${g.image_url}" class="rounded-3 shadow-sm object-fit-cover" style="width:48px;height:48px;">` :
                `<div class="rounded-3 bg-body-secondary d-flex align-items-center justify-content-center" style="width:48px;height:48px"><i class="material-icons-outlined text-secondary icon-md">&#xea5b;</i></div>`;
            
            let platIconHtml = '<i class="material-icons-outlined icon-sm me-1">&#xea5b;</i>';
            if (platformIcons[g.platform]) platIconHtml = `<i class="${platformIcons[g.platform]} me-1"></i>`;

            rows += `
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
                <td class="d-none d-lg-table-cell"><span class="badge ${s.class} rounded-pill bg-opacity-75"><i class="material-icons-outlined icon-sm me-1">${s.icon}</i>${s.label}</span></td>
                <td class="d-none d-lg-table-cell fw-bold text-warning">${g.user_rating ? `<i class="material-icons-outlined icon-sm filled-icon me-1">&#xe838;</i>${g.user_rating}` : '<span class="text-muted opacity-25">-</span>'}</td>
            </tr>`;
        });

        return `
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden" style="background-color: var(--bs-body-bg);">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-body-tertiary text-secondary small text-uppercase fw-bold">
                            <tr>
                                <th class="ps-4 py-3">${LANG.table_game}</th>
                                <th class="d-none d-sm-table-cell">${LANG.table_platform}</th>
                                <th class="d-none d-lg-table-cell">${LANG.table_status}</th>
                                <th class="d-none d-lg-table-cell">${LANG.table_rating}</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            </div>
        </div>`;
    }

    // 6. Rendu Principal
    function render() {
        const filtered = getProcessedGames();
        container.innerHTML = '';

        if (filtered.length === 0) {
            container.innerHTML = `<div class="col-12 text-center py-5 text-muted"><i class="material-icons-outlined icon-xl opacity-25 mb-3">&#xe811;</i><p>${LANG.no_game_found}</p></div>`;
            return;
        }

        if (currentView === 'grid') {
            container.innerHTML = filtered.map(g => generateGridCard(g)).join('');
            btnGrid.classList.add('active', 'btn-light');
            btnGrid.classList.remove('bg-transparent', 'text-secondary');
            btnList.classList.remove('active', 'btn-light');
            btnList.classList.add('bg-transparent', 'text-secondary');
        } else {
            container.innerHTML = generateListTable(filtered);
            btnList.classList.add('active', 'btn-light');
            btnList.classList.remove('bg-transparent', 'text-secondary');
            btnGrid.classList.remove('active', 'btn-light');
            btnGrid.classList.add('bg-transparent', 'text-secondary');
        }
    }

    // 7. Listeners
    if(searchInput) {
        [searchInput, filterPlatform, filterStatus, sortSelect].forEach(el => {
            if(el) el.addEventListener('input', render);
        });

        if(btnClear) btnClear.addEventListener('click', () => { searchInput.value = ''; render(); });
        
        if(btnGrid) btnGrid.addEventListener('click', () => {
            currentView = 'grid';
            localStorage.setItem('publicViewMode', 'grid');
            render();
        });

        if(btnList) btnList.addEventListener('click', () => {
            currentView = 'list';
            localStorage.setItem('publicViewMode', 'list');
            render();
        });
    }

    // Init
    render();
});