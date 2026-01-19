<div class="mb-4">
    <h2 class="fw-bold mb-1">Analyses & Statistiques</h2>
    <p class="text-secondary">Visualisez la répartition de votre collection.</p>
</div>

<!-- KPI Cards -->
<div class="row g-4 mb-4">
    <div class="col-6 col-md-3">
        <div class="p-3 bg-body rounded-4 shadow-sm h-100 border-start border-4 border-primary">
            <div class="text-secondary small fw-bold text-uppercase">Total</div>
            <div class="fs-2 fw-bold" id="kpiTotal">0</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="p-3 bg-body rounded-4 shadow-sm h-100 border-start border-4 border-success">
            <div class="text-secondary small fw-bold text-uppercase">Taux de finis</div>
            <div class="fs-2 fw-bold" id="kpiCompletion">0%</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="p-3 bg-body rounded-4 shadow-sm h-100 border-start border-4 border-warning">
            <div class="text-secondary small fw-bold text-uppercase">Note Moy.</div>
            <div class="fs-2 fw-bold" id="kpiRating">0</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="p-3 bg-body rounded-4 shadow-sm h-100 border-start border-4 border-info">
            <div class="text-secondary small fw-bold text-uppercase">Physique</div>
            <div class="fs-2 fw-bold" id="kpiPhysical">0%</div>
        </div>
    </div>
</div>

<!-- Charts Row 1 -->
<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-body">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                <h6 class="fw-bold mb-0">Répartition par Statut</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center p-4">
                <div style="width: 100%; max-width: 300px; height: 300px;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-body">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                <h6 class="fw-bold mb-0">Physique vs Digital</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center p-4">
                <div style="width: 100%; max-width: 300px; height: 300px;">
                    <canvas id="formatChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12 col-xl-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-body">
             <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                <h6 class="fw-bold mb-0">Top Plateformes</h6>
            </div>
            <div class="card-body p-4">
                 <div style="height: 300px;">
                    <canvas id="platformChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 2 -->
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 bg-body">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                <h6 class="fw-bold mb-0">Genres Favoris</h6>
            </div>
            <div class="card-body p-4">
                <div style="height: 300px;">
                    <canvas id="genreChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const localGames = <?= json_encode($games) ?>;

    document.addEventListener('DOMContentLoaded', () => {
        calculateKPIs();
        initStatusChart();
        initFormatChart();
        initPlatformChart();
        initGenreChart();
    });

    function calculateKPIs() {
        const total = localGames.length;
        if(total === 0) return;
        document.getElementById('kpiTotal').innerText = total;
        const finished = localGames.filter(g => ['finished', 'completed'].includes(g.status)).length;
        document.getElementById('kpiCompletion').innerText = Math.round((finished / total) * 100) + '%';
        const ratedGames = localGames.filter(g => g.user_rating > 0);
        if(ratedGames.length > 0) {
            const sum = ratedGames.reduce((acc, g) => acc + parseInt(g.user_rating), 0);
            document.getElementById('kpiRating').innerText = (sum / ratedGames.length).toFixed(1);
        } else {
            document.getElementById('kpiRating').innerText = '-';
        }
        const physical = localGames.filter(g => g.format === 'physical').length;
        document.getElementById('kpiPhysical').innerText = Math.round((physical / total) * 100) + '%';
    }

    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true, color: '#888' } }
        }
    };

    function initStatusChart() {
        const ctx = document.getElementById('statusChart');
        if(!ctx) return;
        const counts = { 'playing': 0, 'finished': 0, 'completed': 0, 'dropped': 0, 'wishlist': 0 };
        localGames.forEach(g => { if(counts[g.status] !== undefined) counts[g.status]++; });
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['En cours', 'Terminé', 'Platiné', 'Abandonné', 'Souhait'],
                datasets: [{
                    data: [counts.playing, counts.finished, counts.completed, counts.dropped, counts.wishlist],
                    backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#0dcaf0'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: commonOptions
        });
    }

    function initFormatChart() {
        const ctx = document.getElementById('formatChart');
        if(!ctx) return;
        const counts = { 'digital': 0, 'physical': 0 };
        localGames.forEach(g => { 
            const fmt = g.format || 'digital';
            if(counts[fmt] !== undefined) counts[fmt]++; 
        });
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Digital', 'Physique'],
                datasets: [{
                    data: [counts.digital, counts.physical],
                    backgroundColor: ['#6c757d', '#fd7e14'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: commonOptions
        });
    }

    function initPlatformChart() {
        const ctx = document.getElementById('platformChart');
        if(!ctx) return;
        const counts = {};
        localGames.forEach(g => {
            if(g.platform) {
                const parts = g.platform.split(',').map(s => s.trim());
                parts.forEach(p => { counts[p] = (counts[p] || 0) + 1; });
            }
        });
        const sorted = Object.entries(counts).sort((a,b) => b[1] - a[1]).slice(0, 6);
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: sorted.map(x => x[0]),
                datasets: [{
                    label: 'Jeux',
                    data: sorted.map(x => x[1]),
                    backgroundColor: '#6610f2',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } }
            }
        });
    }

    function initGenreChart() {
        const ctx = document.getElementById('genreChart');
        if(!ctx) return;
        const counts = {};
        localGames.forEach(g => {
            if(g.genres) {
                const parts = g.genres.split(',').map(s => s.trim());
                parts.forEach(genre => { if(genre.length > 1) counts[genre] = (counts[genre] || 0) + 1; });
            }
        });
        const sorted = Object.entries(counts).sort((a,b) => b[1] - a[1]).slice(0, 10);
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: sorted.map(x => x[0]),
                datasets: [{
                    label: 'Jeux',
                    data: sorted.map(x => x[1]),
                    backgroundColor: '#20c997',
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } }, y: { grid: { display: false } } }
            }
        });
    }
</script>