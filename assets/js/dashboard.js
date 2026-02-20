const statusConfig = {
    'not_started': { label: LANG.status_not_started, class: 'bg-secondary', icon: '&#xe837;' },
    'playing': { label: LANG.status_playing, class: 'bg-info', icon: '&#xea5b;' },
    'finished': { label: LANG.status_finished, class: 'bg-success', icon: '&#xe86c;' },
    'completed': { label: LANG.status_completed, class: 'bg-warning text-dark', icon: '&#xea23;' },
    'dropped': { label: LANG.status_dropped, class: 'bg-danger', icon: '&#xe14b;' },
    'wishlist': { label: LANG.status_wishlist, class: 'bg-primary text-dark', icon: '&#xe8b1;' },
    'loaned': { label: (typeof LANG !== 'undefined' && LANG.status_loaned) ? LANG.status_loaned : 'Prêté', class: 'bg-warning text-dark', icon: '&#xe0e3;' }
};

const platformIcons = { 'PS5': 'svg-icon ps-icon', 'PS4': 'svg-icon ps-icon', 'Xbox Series': 'svg-icon xbox-icon', 'Xbox': 'svg-icon xbox-icon', 'Switch': 'svg-icon switch-icon', 'PC': 'svg-icon pc-icon' };

let currentView = localStorage.getItem('viewMode') || 'grid';
let processedGamesCache = [];
let displayedCount = 0;
const batchSize = 12;
let observer;
let modal;
let searchTimeout;
let currentLibraryFormat = localStorage.getItem('libraryFormat') || 'physical';

function setLibraryFormat(format) {
    currentLibraryFormat = format;
    localStorage.setItem('libraryFormat', format);
    updateView();
}

function updateDashboardStats() {
    let total = 0;
    let playing = 0;
    let finished = 0;

    localGames.forEach(g => {

        if (g.format === currentLibraryFormat && g.status !== 'wishlist') {
            total++;
            if (g.status === 'playing') playing++;
            if (g.status === 'finished' || g.status === 'completed') finished++;
        }
    });

    const elTotal = document.getElementById('statTotal');
    const elPlaying = document.getElementById('statPlaying');
    const elFinished = document.getElementById('statFinished');

    if (elTotal && elPlaying && elFinished) {

        const currentTotal = parseInt(elTotal.innerText) || 0;
        const currentPlaying = parseInt(elPlaying.innerText) || 0;
        const currentFinished = parseInt(elFinished.innerText) || 0;

        animateValue(elTotal, currentTotal, total, 800);
        animateValue(elPlaying, currentPlaying, playing, 800);
        animateValue(elFinished, currentFinished, finished, 800);
    }
}

document.addEventListener('DOMContentLoaded', () => {

    const btnLibPhys = document.getElementById('btnLibPhys');
    const btnLibDigi = document.getElementById('btnLibDigi');
    
    if (btnLibPhys && btnLibDigi) {
        if(currentLibraryFormat === 'physical') { 
            btnLibPhys.checked = true; 
        } else { 
            btnLibDigi.checked = true; 
        }
    }

    const modalElement = document.getElementById('gameModal');
    if (modalElement) {
        modal = new bootstrap.Modal(modalElement);
    }

    initViewButtons();
    setupIntersectionObserver();

    const searchInput = document.getElementById('internalSearchInput');
    if (searchInput) {
        searchInput.onkeyup = null;
        searchInput.addEventListener('input', (e) => {
            handleServerSearch(e.target.value);
        });
    }

    updateView();
    initCounters();
});

document.querySelectorAll('.dropdown-item').forEach(item => {
    item.addEventListener('click', function (e) {
        e.preventDefault();
        const selectedValue = this.getAttribute('data-value');
        const selectedHtml = this.innerHTML;

        const btn = document.getElementById('trophyDropdownBtn');
        if (btn) btn.querySelector('span').innerHTML = selectedHtml;

        const typeInput = document.getElementById('newTrophyType');
        if (typeInput) typeInput.value = selectedValue;
    });
});

let currentViewMode = 'grid';

function toggleMobileView() {
    if (currentViewMode === 'grid') setView('list');
    else setView('grid');
}

const originalSetView = window.setView;
window.setView = function (mode) {
    if (originalSetView) originalSetView(mode);
    currentViewMode = mode;
    const fabIcon = document.getElementById('fabIcon');
    if (fabIcon) {
        fabIcon.innerHTML = (mode === 'grid') ? '&#xe8ef;' : '&#xe9b0;';
    }
};

function handleServerSearch(query) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchGamesFromServer(query);
    }, 300);
}

async function fetchGamesFromServer(query) {
    toggleLoader(true);
    try {
        const response = await fetch(`/?action=api_search&q=${encodeURIComponent(query)}`);
        if (!response.ok) throw new Error(LANG.error_network);

        const games = await response.json();
        localGames = games;
        updateView();
    } catch (error) {
        console.error('Erreur lors de la recherche:', error);
    } finally {
        toggleLoader(false);
    }
}

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
    const platformFilterElement = document.getElementById('filterPlatform');
    const statusFilterElement = document.getElementById('filterStatus');
    const sortSelectElement = document.getElementById('sortSelect');

    const platformFilter = platformFilterElement ? platformFilterElement.value : 'all';
    const statusFilter = statusFilterElement ? statusFilterElement.value : 'all';
    const sortType = sortSelectElement ? sortSelectElement.value : 'date_desc';

    let filtered = localGames.filter(g => {
        if (!window.isLoanedPage && g.format !== currentLibraryFormat) return false;
        
        if (platformFilter !== 'all') {
            if (g.platform === 'Multiplateforme') return true;
            if (!g.platform || !g.platform.includes(platformFilter)) return false;
        }
        if (statusFilter !== 'all' && g.status !== statusFilter) return false;
        return true;
    });

    filtered.sort((a, b) => {
        const valA = (key) => a[key] || 0;
        const valB = (key) => b[key] || 0;

        switch (sortType) {
            case 'date_desc': return new Date(b.created_at) - new Date(a.created_at);
            case 'alpha_asc': return (a.title || '').localeCompare(b.title || '');
            case 'rating_desc': return valB('user_rating') - valA('user_rating');
            case 'status_asc': return (a.status || '').localeCompare(b.status || '');
            case 'platform_asc': return (a.platform || '').localeCompare(b.platform || '');
            default: return new Date(b.created_at) - new Date(a.created_at);
        }
    });

    return filtered;
}

let currentVoiceLang = 'en-US';

function toggleVoiceLang(e) {
    if (e) e.stopPropagation();
    const badge = document.getElementById('langBadge');
    if (!badge) return;

    if (currentVoiceLang === 'en-US') {
        currentVoiceLang = 'fr-FR';
        badge.innerText = 'FR';
        badge.classList.remove('bg-dark');
        badge.classList.add('bg-primary');
    } else {
        currentVoiceLang = 'en-US';
        badge.innerText = 'EN';
        badge.classList.remove('bg-primary');
        badge.classList.add('bg-dark');
    }
    badge.style.transform = "scale(1.2)";
    setTimeout(() => badge.style.transform = "translate(-50%, -50%) scale(1)", 200);
}

function toggleVoiceSearch() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
        alert(LANG.js_voice_incompatible);
        return;
    }

    const recognition = new SpeechRecognition();
    recognition.lang = currentVoiceLang;
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;

    const micBtn = document.getElementById('micBtn');
    const micIcon = document.getElementById('micIcon');
    const searchInput = document.getElementById('rawgSearchInput');

    recognition.start();

    recognition.onstart = () => {
        if(micBtn) { micBtn.classList.remove('btn-light', 'border'); micBtn.classList.add('btn-danger', 'pulse-animation'); }
        if(micIcon) micIcon.innerText = 'mic_off';
        const langLabel = currentVoiceLang === 'en-US' ? LANG.js_voice_lang_en : LANG.js_voice_lang_fr;
        if(searchInput) searchInput.placeholder = LANG.js_voice_listening_with_lang.replace('{lang}', langLabel);
    };

    recognition.onend = () => resetMicVisuals();

    recognition.onresult = (event) => {
        const transcript = event.results[0][0].transcript;
        if(searchInput) searchInput.value = transcript;
        searchIgdb(true);
    };

    recognition.onerror = (event) => {
        resetMicVisuals();
        if (event.error === 'not-allowed') alert(LANG.js_voice_mic_refused);
    };

    function resetMicVisuals() {
        if(micBtn) { micBtn.classList.remove('btn-danger', 'pulse-animation'); micBtn.classList.add('btn-light', 'border'); }
        if(micIcon) micIcon.innerHTML = '&#xe029;';
        if(searchInput) searchInput.placeholder = LANG.js_search_placeholder;
    }
}

function updateView() {
    const container = document.getElementById('gamesContainer');
    if (!container) return;
    
    container.innerHTML = '';
    processedGamesCache = getProcessedGames();
    displayedCount = 0;

    updateDashboardStats();

    if (localGames.length === 0) {
        container.innerHTML = `<div class="col-12 text-center text-muted py-5"><i class="material-icons-outlined icon-xl opacity-25 mb-3">&#xe811;</i><p>${LANG.no_game_found}</p></div>`;
        toggleLoader(false);
        return;
    }

    if (processedGamesCache.length === 0) {
        container.innerHTML = `
            <div class="col-12 text-center py-5">
                <div class="mb-3 text-secondary opacity-25"><i class="material-icons-outlined icon-xl">&#xea76;</i></div>
                <h5 class="text-secondary fw-bold">${LANG.no_result_title}</h5>
                <p class="text-muted small">${LANG.no_result_desc}</p>
            </div>`;
        toggleLoader(false);
        return;
    }

    loadMoreGames();
}

function loadMoreGames() {
    if (displayedCount >= processedGamesCache.length) {
        toggleLoader(false);
        return;
    }
    toggleLoader(true);

    requestAnimationFrame(() => {
        const container = document.getElementById('gamesContainer');
        if (!container) return;
        
        const nextBatch = processedGamesCache.slice(displayedCount, displayedCount + batchSize);

        if (currentView === 'grid') {
            let html = '';
            nextBatch.forEach(g => { 
                html += generateGridCard(g);
            });
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
                                            <th class="d-none d-xxl-table-cell">${LANG.table_price}</th>
                                            <th class="d-none d-lg-table-cell">${LANG.table_status}</th>
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
}

function getNeonColor(rgbString, opacity = 1) {
    if (!rgbString || rgbString === 'null') return `rgba(255, 255, 255, ${opacity})`;
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

    if (s > 0.1) s = Math.max(s, 0.85);
    l = Math.max(0.50, Math.min(0.75, l));

    h = Math.round(h * 360);
    s = Math.round(s * 100);
    l = Math.round(l * 100);

    return `hsla(${h}, ${s}%, ${l}%, ${opacity})`;
}

function generateGridCard(g) {
    const s = statusConfig[g.status] || statusConfig['playing'];
    const img = g.image_url ? g.image_url : '';

    const shadowColor = getNeonColor(g.dominant_color, 0.4);
    const borderColor = getNeonColor(g.dominant_color, 0.5);

    let metaHtml = '';
    let platIconHtml = '<i class="material-icons-outlined icon-sm me-1">&#xea5b;</i>';
    if (g.platform && g.platform.includes(',')) platIconHtml = '<i class="material-icons-outlined icon-sm me-1">&#xe53b;</i>';
    else if (platformIcons[g.platform]) platIconHtml = `<i class="${platformIcons[g.platform]} me-1"></i>`;

    metaHtml += `<span class="meta-tag">${platIconHtml}${g.platform}</span>`;

    if (g.metacritic_score > 0) {
        let metaIcon = g.metacritic_score >= 75 ? 'text-success' : (g.metacritic_score >= 50 ? 'text-warning' : 'text-danger');
        metaHtml += `<span class="meta-tag" title="${LANG.js_meta_score}"><i class="svg-icon metacritic-icon ${metaIcon} me-1"></i>${g.metacritic_score}</span>`;
    }

    if (g.estimated_price > 0) {
        metaHtml += `<span class="meta-tag text-primary bg-primary-subtle border-primary-subtle"><i class="material-icons-outlined icon-sm me-1">&#xe54e;</i>${g.estimated_price}€</span>`;
    }

    if (g.format === 'digital' && g.playtime && g.playtime > 0) {
        metaHtml += `<span class="meta-tag text-info bg-info-subtle border-info-subtle" title="Temps de jeu"><i class="material-icons-outlined icon-sm me-1">&#xe192;</i>${g.playtime}h</span>`;
    }

    const imageHtml = img ? `<img src="${img}" class="card-cover-img" loading="lazy">` : `<div class="position-absolute top-0 w-100 h-100 d-flex align-items-center justify-content-center bg-body-tertiary"><i class="material-icons-outlined icon-xl text-secondary opacity-25">&#xea5b;</i></div>`;
    const statusHtml = `<i class="material-icons-outlined icon-sm me-1">${s.icon}</i>${s.label}`;
    const ratingHtml = g.user_rating > 0 ? `<div class="fw-bold text-warning d-flex align-items-center small"><i class="material-icons-outlined icon-sm filled-icon me-1">&#xe838;</i>${g.user_rating}</div>` : '';

    let loanBtnHtml = '';
    if (g.format === 'physical' && g.status !== 'wishlist') {
        let safeTitle = g.title.replace(/'/g, "\\'").replace(/"/g, '&quot;');

        loanBtnHtml = `
            <button class="btn-icon-action btn-light text-warning rounded-circle shadow-sm" onclick="openLoanModal(${g.id}, '${safeTitle}')" title="${LANG.js_btn_loan}">
                <i class="material-icons-outlined icon-md">&#xe500;</i>
            </button>`;
    }

    let actionsHtml = `
        <button class="btn-icon-action btn-light rounded-circle shadow-sm" onclick='edit(${g.id})' title="${LANG.js_btn_edit}"><i class="material-icons-outlined icon-md">&#xe3c9;</i></button>
        ${loanBtnHtml}
        <a href="/?action=delete&id=${g.id}" class="btn-icon-action btn-light text-danger rounded-circle shadow-sm" onclick="return confirm('${LANG.js_confirm_delete}')" title="${LANG.js_btn_delete}"><i class="material-icons-outlined icon-md">&#xe872;</i></a>
    `;

    let loanInfoHtml = '';

    if (window.isLoanedPage) {
        let dateStr = '';
        if (g.loaned_date) {
            const d = new Date(g.loaned_date);
            dateStr = ("0" + d.getDate()).slice(-2) + "/" + ("0"+(d.getMonth()+1)).slice(-2) + "/" + d.getFullYear();
        }

        loanInfoHtml = `
        <div class="bg-body rounded-3 p-2 border border-warning border-opacity-25 mt-3 mb-1">
            <div class="small text-muted mb-1 text-truncate" title="${g.loaned_to || ''}">
                <i class="material-icons-outlined icon-sm align-middle me-1 text-warning">&#xe7fd;</i>${LANG.js_loaned_to} <strong class="text-body">${g.loaned_to || ''}</strong>
            </div>
            <div class="small text-muted">
                <i class="material-icons-outlined icon-sm align-middle me-1 text-warning">&#xe8df;</i>${LANG.js_loaned_date} <strong class="text-body">${dateStr}</strong>
            </div>
        </div>`;

        actionsHtml = `
        <a href="/?action=returnGame&id=${g.id}" class="btn-icon-action btn-light text-warning rounded-circle shadow-sm" onclick="return confirm('${LANG.js_confirm_return}')" title="${LANG.js_mark_returned}">
            <i class="material-icons-outlined icon-md">&#xe338;</i>
        </a>`;
    }

    const templateNode = document.getElementById('gridCardTemplate');
    if (!templateNode) return ''; 
    let templateHtml = templateNode.innerHTML;

    return templateHtml
        .replaceAll('{formatClass}', g.format === 'digital' ? 'ratio-digital' : '')
        .replaceAll('{shadowColor}', shadowColor)
        .replaceAll('{borderColor}', borderColor)
        .replaceAll('{imageHtml}', imageHtml)
        .replaceAll('{statusClass}', s.class || '')
        .replaceAll('{statusHtml}', statusHtml)
        .replaceAll('{title}', g.title)
        .replaceAll('{ratingHtml}', ratingHtml)
        .replaceAll('{metaHtml}', metaHtml)
        .replaceAll('{genres}', g.genres || '')
        .replaceAll('{loanInfoHtml}', loanInfoHtml)
        .replaceAll('{actionsHtml}', actionsHtml);
}

function generateListRow(g) {
    const s = statusConfig[g.status] || statusConfig['playing'];

    const img = g.image_url ?
        `<img src="${g.image_url}" class="rounded-3 shadow-sm object-fit-cover" style="width:48px;height:48px;">` :
        `<div class="rounded-3 bg-body-secondary d-flex align-items-center justify-content-center" style="width:48px;height:48px"><i class="material-icons-outlined text-secondary icon-md">&#xea5b;</i></div>`;

    const price = g.estimated_price > 0 ? `<span class="meta-tag text-primary bg-primary-subtle border-primary-subtle">${g.estimated_price}€</span>` : '<span class="text-muted opacity-25">-</span>';

    let platIconHtml = '';
    if (g.platform && g.platform.includes(',')) {
        platIconHtml = '<i class="material-icons-outlined icon-sm me-1">&#xe53b;</i>';
    } else if (platformIcons[g.platform]) {
        platIconHtml = `<i class="${platformIcons[g.platform]} me-1"></i>`;
    } else {
        platIconHtml = '<i class="material-icons-outlined icon-sm me-1">&#xe338;</i>';
    }

    let loanBtnHtml = '';
    if (g.format === 'physical' && g.status !== 'wishlist') {
        let safeTitle = g.title.replace(/'/g, "\\'").replace(/"/g, '&quot;');
        
        loanBtnHtml = `
            <button class="btn-icon-action btn-light text-warning" onclick="openLoanModal(${g.id}, '${safeTitle}')" title="${LANG.js_btn_loan}">
                <i class="material-icons-outlined icon-md">&#xe0e3;</i>
            </button>`;
    }

    let actionsHtml = `
        <button class="btn-icon-action" onclick='edit(${g.id})' title="${LANG.js_btn_edit}"><i class="material-icons-outlined icon-md">&#xe3c9;</i></button>
        ${loanBtnHtml}
        <a href="/?action=delete&id=${g.id}" class="btn-action btn-icon-action btn-light text-danger" onclick="return confirm('${LANG.js_confirm_delete}')" title="${LANG.js_btn_delete}"><i class="material-icons-outlined icon-md">&#xe872;</i></a>
    `;

    if (window.isLoanedPage) {
        actionsHtml = `
        <a href="/?action=returnGame&id=${g.id}" class="btn-action btn-icon-action btn-light text-warning" onclick="return confirm('${LANG.js_confirm_return}')" title="${LANG.js_mark_returned}">
            <i class="material-icons-outlined icon-md">&#xe0e3;</i>
        </a>`;
    }

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
        <td class="d-none d-xxl-table-cell">${price}</td>
        <td class="d-none d-lg-table-cell"><span class="badge status-badge-list rounded-pill py-2 px-3 fw-normal"><i class="material-icons-outlined icon-sm me-1">${s.icon}</i>${s.label}</span></td>
        <td class="text-end text-nowrap pe-4">
            ${actionsHtml}
        </td>
    </tr>`;
}

function initCounters() {
    const counters = document.querySelectorAll('.animate-counter');
    const speed = 200; 

    const counterObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;

                if (['statTotal', 'statPlaying', 'statFinished'].includes(counter.id)) {
                    observer.unobserve(counter);
                    return;
                }

                const target = parseInt(counter.getAttribute('data-target')) || 0;
                animateValue(counter, 0, target, 800); 
                observer.unobserve(counter);
            }
        });
    }, { threshold: 0.5 });


    counters.forEach(counter => {
        counterObserver.observe(counter);
    });
    
}

function animateValue(obj, start, end, duration) {
    if (end === 0) {
        obj.innerHTML = 0;
        return;
    }

    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        obj.innerHTML = Math.floor(end * (1 - Math.pow(1 - progress, 3))); 

        if (progress < 1) {
            window.requestAnimationFrame(step);
        } else {
            obj.innerHTML = end; 
        }
    };
    window.requestAnimationFrame(step);
}

function toggleLoader(show) { const l = document.getElementById('scrollLoader'); if (show && l) l.classList.remove('d-none'); else if (l) l.classList.add('d-none'); }
function searchPrice() { const title = document.getElementById('gameTitle').value; const platform = document.getElementById('gamePlatform').value; if (title) { const query = encodeURIComponent(`${title} ${platform}`); const w = 1000; const h = 600; const left = (screen.width / 2) - (w / 2); const top = (screen.height / 2) - (h / 2); window.open(`https://www.ebay.fr/sch/i.html?_nkw=${query}&_sacat=139973`, 'PriceCheck', `width=${w},height=${h},top=${top},left=${left}`); } else { alert(LANG.alert_enter_title); } }
function handleEnter(e) { if (e.key === 'Enter') searchIgdb(); }
function closeSearch() { document.getElementById('rawgContainer').classList.add('d-none'); document.getElementById('rawgSearchInput').value = ''; }
function setView(v) { currentView = v; localStorage.setItem('viewMode', v); initViewButtons(); updateView(); }
function initViewButtons() { const gridBtn = document.getElementById('btnGrid'); const listBtn = document.getElementById('btnList'); if(gridBtn) gridBtn.className = currentView === 'grid' ? 'btn btn-sm btn-primary rounded-2 active border-0' : 'btn btn-sm btn-transparent rounded-2 text-secondary'; if(listBtn) listBtn.className = currentView === 'list' ? 'btn btn-sm btn-primary rounded-2 active border-0' : 'btn btn-sm btn-transparent rounded-2 text-secondary'; }
function previewFile(input) { if (input.files && input.files[0]) { var reader = new FileReader(); reader.onload = function (e) { document.getElementById('previewImg').src = e.target.result; document.getElementById('previewImg').classList.remove('d-none'); document.getElementById('uploadPlaceholder').classList.add('d-none'); }; reader.readAsDataURL(input.files[0]); } }
function toggleCustomPlatform() { const select = document.getElementById('gamePlatform'); const container = document.getElementById('multiPlatformContainer'); const hiddenInput = document.getElementById('gamePlatformCustom'); if(select && container && hiddenInput) { if (select.value === 'Multiplateforme') { container.classList.remove('d-none'); if (document.getElementById('platformInputsList').children.length === 0) addPlatformInput(); } else { container.classList.add('d-none'); hiddenInput.value = ''; } } }
function addPlatformInput(value = '') { const list = document.getElementById('platformInputsList'); if(!list) return; const div = document.createElement('div'); div.className = 'input-group input-group-sm mb-1'; div.innerHTML = `<input type="text" class="form-control rounded-start-2 border-end-0 bg-white" value="${value}" placeholder="${LANG.placeholder_name}" oninput="updateHiddenPlatformInput()"><button type="button" class="btn btn-outline-danger border-start-0 rounded-end-2 bg-white text-danger" onclick="this.parentElement.remove(); updateHiddenPlatformInput()"><i class="material-icons-outlined icon-sm">&#xe5cd;</i></button>`; list.appendChild(div); updateHiddenPlatformInput(); }
function updateHiddenPlatformInput() { const inputs = document.querySelectorAll('#platformInputsList input'); const values = Array.from(inputs).map(i => i.value.trim()).filter(v => v !== ''); const hidden = document.getElementById('gamePlatformCustom'); if(hidden) hidden.value = values.join(', '); }

function edit(id) { openModal(localGames.find(g => g.id == id)); }

function openModal(g = null) {
    if (!modal) {
        const modalElement = document.getElementById('gameModal');
        if(!modalElement) return;
        modal = new bootstrap.Modal(modalElement);
    }
    const safeSet = (id, val) => { const el = document.getElementById(id); if (el) el.value = val; };
    
    safeSet('gameId', g ? g.id : '');
    safeSet('gameRawgId', '');
    safeSet('gameTitle', g ? g.title : '');
    safeSet('gameImageHidden', g ? (g.image_url || '') : '');

    const prev = document.getElementById('previewImg');
    const holder = document.getElementById('uploadPlaceholder');
    if(prev && holder) {
        if (g && g.image_url) { prev.src = g.image_url; prev.classList.remove('d-none'); holder.classList.add('d-none'); } 
        else { prev.classList.add('d-none'); holder.classList.remove('d-none'); }
    }

    const priceVal = g ? (g.estimated_price || '') : '';
    safeSet('gamePriceTablet', priceVal);
    safeSet('gamePriceDesktop', priceVal);

    const priceTablet = document.getElementById('gamePriceTablet');
    const priceDesktop = document.getElementById('gamePriceDesktop');
    if (priceTablet && priceDesktop) {
        priceTablet.oninput = function () { priceDesktop.value = this.value; };
        priceDesktop.oninput = function () { priceTablet.value = this.value; };
    }

    const standardPlatforms = ['PS5', 'PS4', 'Xbox Series', 'Switch', 'PC'];
    const platformSelect = document.getElementById('gamePlatform');
    const listContainer = document.getElementById('platformInputsList');
    if(listContainer) listContainer.innerHTML = '';
    
    if (platformSelect) {
        if (g && g.platform) { 
            if (standardPlatforms.includes(g.platform)) { platformSelect.value = g.platform; toggleCustomPlatform(); } 
            else { platformSelect.value = 'Multiplateforme'; toggleCustomPlatform(); const parts = g.platform.split(',').map(s => s.trim()); parts.forEach(p => addPlatformInput(p)); } 
        } else { platformSelect.value = 'PS5'; toggleCustomPlatform(); }
    }
    
    checkPsnVisibility();
    
    const fmtDigital = document.getElementById('fmtDigital');
    const fmtPhysical = document.getElementById('fmtPhysical');
    document.getElementById('gameFormatHidden').value = g ? (g.format || currentLibraryFormat) : currentLibraryFormat;

    const isWishlistPage = window.location.pathname.includes('wishlist');
    safeSet('gameStatus', g ? (g.status || 'not_started') : (isWishlistPage ? 'wishlist' : 'not_started'));
    safeSet('gameDate', g ? g.release_date : '');
    safeSet('gameMeta', g ? g.metacritic_score : '');
    safeSet('gameRating', g ? g.user_rating : '');
    safeSet('gameComment', g ? g.comment : '');
    safeSet('gameDesc', g ? g.description : '');
    safeSet('gameGenres', g ? g.genres : '');
    
    if (g && g.id) loadTrophies(g.id);
    modal.show();
}

function checkPsnVisibility() { const plat = document.getElementById('gamePlatform'); if(!plat) return; const tabs = document.getElementById('modalTabs'); if(!tabs) return; if (plat.value.includes('PS') || plat.value.includes('PlayStation')) { tabs.style.display = 'flex'; } else { tabs.style.display = 'none'; const firstTabEl = document.querySelector('#modalTabs li:first-child button') || document.querySelector('#modalTabs li:first-child a'); if (firstTabEl) { const firstTab = new bootstrap.Tab(firstTabEl); firstTab.show(); } } }

let loanModal;
function openLoanModal(gameId, gameTitle) {
    const loanEl = document.getElementById('loanModal');
    if(!loanEl) return;
    if (!loanModal) loanModal = new bootstrap.Modal(loanEl);
    const loanGameId = document.getElementById('loanGameId');
    const loanGameTitle = document.getElementById('loanGameTitle');
    if(loanGameId) loanGameId.value = gameId;
    if(loanGameTitle) loanGameTitle.innerText = gameTitle;
    loanModal.show();
}

async function loadTrophies(gameId) { const list = document.getElementById('trophiesList'); if(!list) return; list.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>'; try { const res = await fetch(`/?action=api_get_trophies&game_id=${gameId}`); const data = await res.json(); const pct = data.progress.percent; const bar = document.getElementById('trophyProgressBar'); const txt = document.getElementById('trophyProgressText'); if(bar) bar.style.width = pct + '%'; if(txt) txt.innerText = pct + '%'; list.innerHTML = ''; if (data.trophies.length === 0) { list.innerHTML = `<div class="text-center text-muted small py-3">${LANG.no_trophies}</div>`; return; } data.trophies.forEach(t => { const colorClass = `trophy-${t.type}`; const icon = t.type === 'platinum' ? '<img src="../assets/images/platinum.png" class="trophy-icon d-table-cell me-2">' : (t.type === 'gold' ? '<img src="../assets/images/gold.png" class="trophy-icon d-table-cell me-2">' : (t.type === 'silver' ? '<img src="../assets/images/silver.png" class="trophy-icon d-table-cell me-2">' : '<img src="../assets/images/bronze.png" class="trophy-icon d-table-cell me-2">')); const isObtained = t.is_obtained == 1; const gameTitleEl = document.getElementById('gameTitle'); const gameTitle = gameTitleEl ? gameTitleEl.value : ''; const searchUrl = `https://www.google.com/search?q=${encodeURIComponent(gameTitle + ' trophée ' + t.title + (typeof LANG !== 'undefined' ? LANG.google_guide : ''))}`; list.innerHTML += `<div class="d-flex align-items-center p-2 mb-2 bg-body-tertiary rounded trophy-item ${colorClass}"><div class="trophy-icon fs-5">${icon}</div><div class="flex-grow-1 ms-2" style="cursor:pointer" onclick="toggleTrophy(${t.id})"><div class="fw-bold small ${isObtained ? 'text-decoration-line-through text-muted' : ''}">${t.title}</div></div><div class="d-flex gap-2"><a href="${searchUrl}" target="_blank" class="btn btn-sm btn-link text-info p-0" title="Chercher une solution"><i class="material-icons-outlined icon-sm">&#xe8b6;</i></a><button class="btn btn-sm btn-link text-danger p-0" onclick="deleteTrophy(${t.id})"><i class="material-icons-outlined icon-sm">&#xe872;</i></button></div></div>`; }); } catch (e) { list.innerHTML = LANG.error_loading; } }
async function addTrophy() { const gameIdEl = document.getElementById('gameId'); if(!gameIdEl) return; const gameId = gameIdEl.value; if (!gameId) { alert(LANG.alert_save_first); return; } const titleEl = document.getElementById('newTrophyTitle'); const typeEl = document.getElementById('newTrophyType'); const title = titleEl ? titleEl.value : ''; const type = typeEl ? typeEl.value : ''; if (!title) return; const formData = new FormData(); formData.append('game_id', gameId); formData.append('title', title); formData.append('type', type); formData.append('description', ''); await fetch('/?action=api_add_trophy', { method: 'POST', body: formData }); if(titleEl) titleEl.value = ''; loadTrophies(gameId); }
async function toggleTrophy(id) { await fetch(`/?action=api_toggle_trophy&id=${id}`); const gameIdEl = document.getElementById('gameId'); if(gameIdEl) loadTrophies(gameIdEl.value); }
async function deleteTrophy(id) { if (!confirm(LANG.confirm_delete)) return; await fetch(`/?action=api_delete_trophy&id=${id}`); const gameIdEl = document.getElementById('gameId'); if(gameIdEl) loadTrophies(gameIdEl.value); }

async function searchIgdb(autoOpen = false) {
    const input = document.getElementById('rawgSearchInput');
    if(!input) return;
    const q = input.value;
    if (!q) return;

    input.blur();

    const container = document.getElementById('rawgContainer');
    const loading = document.getElementById('rawgLoading');
    const results = document.getElementById('rawgResults');
    if(container) container.classList.remove('d-none');
    if(loading) loading.classList.remove('d-none');
    if(results) results.innerHTML = '';

    try {
        const res = await fetch(`/?action=search_igdb&q=${encodeURIComponent(q)}`);
        const data = await res.json();

        if (autoOpen && data.results && data.results.length > 0) {
            fetchGameDetails(data.results[0].id);
            return;
        }

        let html = '';
        if (data.results && data.results.length > 0) {
            data.results.forEach(g => {
                const imgUrl = g.background_image || 'assets/images/no-cover.png';
                const year = g.released || '';
                html += `
                <div class="card border-0 shadow-sm flex-shrink-0 bg-body-tertiary" 
                     style="width: 160px; cursor: pointer; overflow: hidden; border-radius: 12px;" 
                     onclick="fetchGameDetails(${g.id})">
                    <img src="${imgUrl}" style="height:220px; width: 100%; object-fit:cover">
                    <div class="p-2 text-center">
                        <small class="fw-bold d-block text-truncate text-body">${g.name}</small>
                        <small class="text-muted" style="font-size: 0.7rem;">${year}</small>
                    </div>
                </div>`;
            });
            if(results) results.innerHTML = html;
        } else {
            if(results) results.innerHTML = `<div class="text-muted w-100 text-center py-2">${LANG.js_igdb_no_result}</div>`;
        }

    } catch (e) {
        if(results) results.innerHTML = `<div class="text-danger w-100 text-center py-2">${LANG.js_server_error}</div>`;
    } finally {
        if (!autoOpen && loading) {
            loading.classList.add('d-none');
        }
    }
}

async function fetchGameDetails(id) {
    const loading = document.getElementById('rawgLoading');
    if(loading) loading.classList.remove('d-none');
    try {
        const res = await fetch(`/?action=get_igdb_details&id=${id}`);
        if (!res.ok) throw new Error('Erreur API');

        const g = await res.json();

        if (typeof localGames !== 'undefined' && Array.isArray(localGames)) {
            const cleanTitle = g.name.trim().toLowerCase();
            const existingGame = localGames.find(game => game.title && game.title.trim().toLowerCase() === cleanTitle);
            if (existingGame) {
                const msg = (typeof LANG !== 'undefined' && LANG.alert_duplicate)
                    ? LANG.alert_duplicate.replace('{name}', g.name).replace('{platform}', existingGame.platform)
                    : (LANG.js_game_exists_simple || '').replace('{name}', g.name);
                alert(msg);
            }
        }

        openModal();

        const safeSet = (elId, val) => { const el = document.getElementById(elId); if (el) el.value = val; };
        safeSet('gameTitle', g.name);
        safeSet('gameDate', g.released);
        safeSet('gameMeta', g.metacritic);
        safeSet('gameImageHidden', g.background_image);
        
        const prev = document.getElementById('previewImg');
        const uploadPl = document.getElementById('uploadPlaceholder');
        if(prev && uploadPl) {
            if (g.background_image) {
                prev.src = g.background_image;
                prev.classList.remove('d-none');
                uploadPl.classList.add('d-none');
            } else {
                prev.classList.add('d-none');
                uploadPl.classList.remove('d-none');
            }
        }

        safeSet('gameDesc', g.description_raw);
        safeSet('gameGenres', g.genres_list || '');
        safeSet('gameRawgId', id);

    } catch (e) {
        alert((typeof LANG !== 'undefined' && LANG.error_import) ? LANG.error_import : LANG.js_import_error_generic);
    } finally {
        if(loading) loading.classList.add('d-none');
    }
}