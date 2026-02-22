document.addEventListener('DOMContentLoaded', function() {
    const gamesData = window.publicGamesData || [];
    let currentView = localStorage.getItem('publicViewMode') || 'grid';
    let currentLibraryFormat = localStorage.getItem('publicLibraryFormat') || 'physical';

    // Nouvelles variables pour le chargement progressif par lot
    let processedGamesCache = [];
    let displayedCount = 0;
    const batchSize = 12;
    let observer;

    window.setLibraryFormat = function(format) {
        currentLibraryFormat = format;
        localStorage.setItem('publicLibraryFormat', format);
        render();
    };

    if(currentLibraryFormat === 'physical') { 
        const btnPhys = document.getElementById('btnLibPhys');
        if (btnPhys) btnPhys.checked = true; 
    } else { 
        const btnDigi = document.getElementById('btnLibDigi');
        if (btnDigi) btnDigi.checked = true; 
    }
    
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

    const container = document.getElementById('gamesContainer');
    const searchInput = document.getElementById('publicSearchInput');
    const btnClear = document.getElementById('btnClearSearch');
    const filterPlatform = document.getElementById('filterPlatform');
    const filterStatus = document.getElementById('filterStatus');
    const sortSelect = document.getElementById('sortSelect');
    const btnGrid = document.getElementById('btnGrid');
    const btnList = document.getElementById('btnList');

    if (!container || !searchInput) return;

    // --- ALGORITHME HSL AJOUTÉ POUR UNIFIER L'EFFET NÉON ---
    function getNeonColor(rgbString, opacity = 1, platform = '') {
        // Fallback si la couleur est absente ou ancienne valeur buggée
        if (!rgbString || rgbString === 'null' || rgbString === 'rgb(30, 30, 30)') {
            let r = 255, g = 255, b = 255; 
            if (platform) {
                if (platform.includes('PS') || platform.includes('PlayStation')) { r = 0; g = 112; b = 210; }
                else if (platform.includes('Xbox')) { r = 16; g = 124; b = 16; }
                else if (platform.includes('Switch')) { r = 228; g = 0; b = 15; }
                else if (platform.includes('PC')) { r = 102; g = 192; b = 244; }
            }
            return `rgba(${r}, ${g}, ${b}, ${opacity})`;
        }

        const match = rgbString.match(/\d+/g);
        if (!match || match.length < 3) return `rgba(255, 255, 255, ${opacity})`;

        let [r, g, b] = match.map(Number);
        r /= 255; g /= 255; b /= 255;
        const max = Math.max(r, g, b), min = Math.min(r, g, b);
        let h, s, l = (max + min) / 2;

        if (max === min) {
            h = s = 0;
        } else {
            const d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            switch (max) {
                case r: h = (g - b) / d + (g < b ? 6 : 0); break;
                case g: h = (b - r) / d + 2; break;
                case b: h = (r - g) / d + 4; break;
            }
            h /= 6;
        }

        // Boost saturation et contrainte de luminosité pour l'effet lueur
        if (s > 0.1) s = Math.max(s, 0.85);
        l = Math.max(0.50, Math.min(0.75, l));

        h = Math.round(h * 360);
        s = Math.round(s * 100);
        l = Math.round(l * 100);

        return `hsla(${h}, ${s}%, ${l}%, ${opacity})`;
    }

    // Gestion de l'affichage du loader
    function toggleLoader(show) { 
        const l = document.getElementById('scrollLoader'); 
        if (show && l) l.classList.remove('d-none'); 
        else if (l) l.classList.add('d-none'); 
    }

    // Configuration de l'IntersectionObserver pour le défilement infini
    function setupIntersectionObserver() {
        const options = { root: null, rootMargin: '0px', threshold: 0.1 };
        observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    loadMoreGames();
                }
            });
        }, options);
        const sentinel = document.getElementById('scrollSentinel');
        if (sentinel) observer.observe(sentinel);
    }

    function getProcessedGames() {
        const query = searchInput.value.toLowerCase();
        const platform = filterPlatform.value;
        const status = filterStatus.value;
        const sort = sortSelect.value;

        let filtered = gamesData.filter(g => {
            const gFormat = g.format || 'physical';
            if (gFormat !== currentLibraryFormat) return false;

            if (query && !g.title.toLowerCase().includes(query)) return false;
            
            if (platform !== 'all') {
                if (g.platform === 'Multiplateforme') return true; 
                if (!g.platform.includes(platform)) return false;
            }

            if (status !== 'all' && g.status !== status) return false;

            return true;
        });

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

    function generateGridCard(g) {
        const s = statusConfig[g.status] || statusConfig['playing'];
        const img = g.image_url ? g.image_url : '';
        
        // --- On utilise la couleur plateforme en cas de besoin ---
        const shadowColor = getNeonColor(g.dominant_color, 0.4, g.platform);
        const borderColor = getNeonColor(g.dominant_color, 0.5, g.platform);

        let platIconHtml = '<i class="material-icons-outlined icon-sm me-1">&#xea5b;</i>';
        if (g.platform && g.platform.includes(',')) platIconHtml = '<i class="material-icons-outlined icon-sm me-1">&#xe53b;</i>';
        else if (platformIcons[g.platform]) platIconHtml = `<i class="${platformIcons[g.platform]} me-1"></i>`;

        let metaHtml = `<span class="meta-tag">${platIconHtml}${g.platform}</span>`;
        if (g.user_rating > 0) metaHtml += `<span class="meta-tag text-warning bg-warning-subtle border-warning-subtle"><i class="material-icons-outlined icon-sm filled-icon me-1">&#xe838;</i>${g.user_rating}</span>`;

        if (g.format === 'digital' && g.playtime && g.playtime > 0) {
            metaHtml += `<span class="meta-tag text-info bg-info-subtle border-info-subtle"><i class="material-icons-outlined icon-sm me-1">&#xe425;</i>${g.playtime} h</span>`;
        }

        const imagePlaceholder = `<div class="position-absolute top-0 w-100 h-100 d-flex align-items-center justify-content-center bg-body-tertiary"><i class="material-icons-outlined icon-xl text-secondary opacity-25">&#xea5b;</i></div>`;

        return `
        <div class="col-sm-6 col-lg-4 col-xl-3 animate-in">
            <div class="game-card-modern"
                 onmouseover="this.style.boxShadow='0 25px 60px -12px ${shadowColor}'; this.style.borderColor='${borderColor}'"
                 onmouseout="this.style.boxShadow=''; this.style.borderColor='rgba(0,0,0,0.05)'">
                <div class="card-cover-container ${g.format === 'digital' ? 'ratio-digital' : ''}">
                    ${img ? `<img src="${img}" class="card-cover-img" loading="lazy">` : imagePlaceholder}
                    <span class="status-badge-float"><i class="material-icons-outlined icon-sm me-1">${s.icon}</i>${s.label}</span>
                </div>
                <div class="card-content-area">
                    <h6 class="game-title text-truncate" title="${g.title}">${g.title}</h6>
                    <div class="meta-badges">${metaHtml}</div>
                </div>
            </div>
        </div>`;
    }

    function generateListRow(g) {
        const s = statusConfig[g.status] || statusConfig['playing'];
        const img = g.image_url ?
            `<img src="${g.image_url}" class="rounded-3 shadow-sm object-fit-cover" style="width:48px;height:48px;">` :
            `<div class="rounded-3 bg-body-secondary d-flex align-items-center justify-content-center" style="width:48px;height:48px"><i class="material-icons-outlined text-secondary icon-md">&#xea5b;</i></div>`;
        
        let platIconHtml = '<i class="material-icons-outlined icon-sm me-1">&#xea5b;</i>';
        if (platformIcons[g.platform]) platIconHtml = `<i class="${platformIcons[g.platform]} me-1"></i>`;

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
            <td class="d-none d-lg-table-cell"><span class="badge ${s.class} rounded-pill bg-opacity-75"><i class="material-icons-outlined icon-sm me-1">${s.icon}</i>${s.label}</span></td>
            <td class="d-none d-lg-table-cell fw-bold text-warning">${g.user_rating ? `<i class="material-icons-outlined icon-sm filled-icon me-1">&#xe838;</i>${g.user_rating}` : '<span class="text-muted opacity-25">-</span>'}</td>
        </tr>`;
    }

    function loadMoreGames() {
        if (displayedCount >= processedGamesCache.length) {
            toggleLoader(false);
            return;
        }
        toggleLoader(true);

        window.requestAnimationFrame(() => {
            const nextBatch = processedGamesCache.slice(displayedCount, displayedCount + batchSize);

            if (currentView === 'grid') {
                let html = '';
                nextBatch.forEach(g => { html += generateGridCard(g); });
                container.insertAdjacentHTML('beforeend', html);
            } else {
                let tbody = container.querySelector('tbody');
                if (!tbody) {
                    container.innerHTML = `
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
    }

    function render() {
        processedGamesCache = getProcessedGames();
        container.innerHTML = '';
        displayedCount = 0; 

        if (processedGamesCache.length === 0) {
            container.innerHTML = `<div class="col-12 text-center py-5 text-muted"><i class="material-icons-outlined icon-xl opacity-25 mb-3">&#xe811;</i><p>${LANG.no_game_found}</p></div>`;
            return;
        }

        if (currentView === 'grid') {
            btnGrid.classList.add('active', 'btn-light');
            btnGrid.classList.remove('bg-transparent', 'text-secondary');
            btnList.classList.remove('active', 'btn-light');
            btnList.classList.add('bg-transparent', 'text-secondary');
        } else {
            btnList.classList.add('active', 'btn-light');
            btnList.classList.remove('bg-transparent', 'text-secondary');
            btnGrid.classList.remove('active', 'btn-light');
            btnGrid.classList.add('bg-transparent', 'text-secondary');
        }

        loadMoreGames(); 
    }

    setupIntersectionObserver();

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

    render();
});