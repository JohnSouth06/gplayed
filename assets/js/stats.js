// window.localGames est injecté depuis stats.php

document.addEventListener('DOMContentLoaded', () => {
    calculateKPIs();
    initStatusChart();
    initFormatChart();
    initPlatformChart();
    initGenreChart();
});

function calculateKPIs() {
    const total = window.localGames.length;
    if(total === 0) return;
    document.getElementById('kpiTotal').innerText = total;
    const finished = window.localGames.filter(g => ['finished', 'completed'].includes(g.status)).length;
    document.getElementById('kpiCompletion').innerText = Math.round((finished / total) * 100) + '%';
    const ratedGames = window.localGames.filter(g => g.user_rating > 0);
    if(ratedGames.length > 0) {
        const sum = ratedGames.reduce((acc, g) => acc + parseInt(g.user_rating), 0);
        document.getElementById('kpiRating').innerText = (sum / ratedGames.length).toFixed(1);
    } else {
        document.getElementById('kpiRating').innerText = '-';
    }
    const physical = window.localGames.filter(g => g.format === 'physical').length;
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
    window.localGames.forEach(g => { if(counts[g.status] !== undefined) counts[g.status]++; });
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
    window.localGames.forEach(g => { 
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
    window.localGames.forEach(g => {
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
    window.localGames.forEach(g => {
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