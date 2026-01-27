<div class="mb-4">
    <h2 class="fw-light text-tertiary mb-1">Statistiques & analyses</h2>
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
            <div class="text-secondary small fw-bold text-uppercase">Taux de terminés</div>
            <div class="fs-2 fw-bold" id="kpiCompletion">0%</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="p-3 bg-body rounded-4 shadow-sm h-100 border-start border-4 border-warning">
            <div class="text-secondary small fw-bold text-uppercase">Note Moyenne</div>
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
                <h6 class="fw-bold mb-0">Répartition par statut</h6>
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
                <h6 class="fw-bold mb-0">Top plateformes</h6>
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
                <h6 class="fw-bold mb-0">Genres favoris</h6>
            </div>
            <div class="card-body p-4">
                <div style="height: 300px;">
                    <canvas id="genreChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts Stats -->
<script>
    window.localGames = <?= json_encode($games) ?>;
</script>
<script src="assets/js/stats.js"></script>