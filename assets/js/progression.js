let timerInterval;

/**
 * Gère l'affichage des boutons selon l'état
 * @param {string} state - 'stopped', 'running', 'paused'
 */
function updateTimerUI(state) {
    const btnStart = document.getElementById('btnStart');
    const btnResume = document.getElementById('btnResume');
    const btnPause = document.getElementById('btnPause');
    const btnStop = document.getElementById('btnStop');
    
    const timerHint = document.getElementById('timerHint');
    const timerDisplay = document.getElementById('timerDisplay');
    const timerProgress = document.getElementById('timerProgress');

    // Reset de base : tout cacher
    btnStart.classList.add('d-none');
    btnResume.classList.add('d-none');
    btnPause.classList.add('d-none');
    btnStop.classList.add('d-none');

    if (state === 'running') {
        btnPause.classList.remove('d-none');
        btnStop.classList.remove('d-none');
        timerHint.classList.remove('d-none');
        
        timerDisplay.classList.add('text-primary');
        timerDisplay.classList.remove('text-warning'); // Enlever couleur pause
        timerProgress.parentElement.classList.remove('d-none'); // Barre visible
        timerProgress.classList.add('progress-bar-animated', 'progress-bar-striped');
        timerProgress.style.width = "100%";

    } else if (state === 'paused') {
        btnResume.classList.remove('d-none');
        btnStop.classList.remove('d-none');
        timerHint.classList.remove('d-none');
        
        timerDisplay.classList.remove('text-primary');
        timerDisplay.classList.add('text-warning'); // Indicateur visuel pause
        timerProgress.classList.remove('progress-bar-animated', 'progress-bar-striped'); // Figer la barre

    } else { // 'stopped'
        btnStart.classList.remove('d-none');
        timerHint.classList.add('d-none');
        
        timerDisplay.classList.remove('text-primary', 'text-warning');
        timerProgress.style.width = "0%";
        timerDisplay.innerText = "00:00:00";
    }
}

// 1. DÉMARRAGE (Premier lancement)
function startTimer() {
    localStorage.setItem('session_start_time', Date.now());
    localStorage.setItem('session_accumulated', 0); // 0 ms accumulées au départ
    localStorage.setItem('session_state', 'running');
    
    runTimer();
}

// 2. REPRENDRE (Après une pause)
function resumeTimer() {
    localStorage.setItem('session_start_time', Date.now()); // On reset le "top départ" à maintenant
    localStorage.setItem('session_state', 'running');
    
    runTimer();
}

// 3. PAUSE
function pauseTimer() {
    clearInterval(timerInterval);
    
    // Calculer ce qu'on a fait depuis le dernier "Start/Resume"
    const startTime = parseInt(localStorage.getItem('session_start_time'));
    const currentSession = Date.now() - startTime;
    
    // Ajouter au total accumulé
    let accumulated = parseInt(localStorage.getItem('session_accumulated')) || 0;
    accumulated += currentSession;
    
    localStorage.setItem('session_accumulated', accumulated);
    localStorage.setItem('session_state', 'paused');
    
    updateTimerUI('paused');
    updateDisplayWithTotal(accumulated); // Afficher le total figé
}

// Fonction utilitaire pour lancer l'intervalle
function runTimer() {
    updateTimerUI('running');
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(updateTimerDisplay, 1000);
    updateTimerDisplay(); // Appel immédiat pour éviter le délai d'1s
}

// 4. ARRÊT TOTAL
function stopTimer() {
    clearInterval(timerInterval);
    
    // Calcul final
    let totalMs = parseInt(localStorage.getItem('session_accumulated')) || 0;
    
    // Si on arrête alors que ça tourne, on ajoute la session en cours
    if (localStorage.getItem('session_state') === 'running') {
        const startTime = parseInt(localStorage.getItem('session_start_time'));
        totalMs += (Date.now() - startTime);
    }
    
    // Conversion
    const totalMinutes = Math.floor(totalMs / 1000 / 60);
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;

    // Nettoyage
    localStorage.removeItem('session_start_time');
    localStorage.removeItem('session_accumulated');
    localStorage.removeItem('session_state');

    // UI Reset
    updateTimerUI('stopped');

    // Modale
    const modal = new bootstrap.Modal(document.getElementById('addProgressModal'));
    document.getElementById('inputHours').value = hours;
    document.getElementById('inputMinutes').value = minutes;
    modal.show();
}

function updateTimerDisplay() {
    const startTime = parseInt(localStorage.getItem('session_start_time'));
    const accumulated = parseInt(localStorage.getItem('session_accumulated')) || 0;
    
    if(!startTime) return;

    // Temps Total = (Temps passé depuis le dernier clic Start) + (Temps accumulé avant)
    const currentSession = Date.now() - startTime;
    const totalMs = currentSession + accumulated;
    
    updateDisplayWithTotal(totalMs);
}

function updateDisplayWithTotal(totalMs) {
    const seconds = Math.floor((totalMs / 1000) % 60);
    const minutes = Math.floor((totalMs / 1000 / 60) % 60);
    const hours = Math.floor((totalMs / 1000 / 60 / 60));

    const formatted = 
        (hours < 10 ? "0" + hours : hours) + ":" + 
        (minutes < 10 ? "0" + minutes : minutes) + ":" + 
        (seconds < 10 ? "0" + seconds : seconds);

    document.getElementById('timerDisplay').innerText = formatted;
}

// Au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    const state = localStorage.getItem('session_state');
    
    if (state === 'running') {
        runTimer();
    } else if (state === 'paused') {
        // Si on recharge la page alors qu'on était en pause
        const accumulated = parseInt(localStorage.getItem('session_accumulated')) || 0;
        updateTimerUI('paused');
        updateDisplayWithTotal(accumulated);
    } else {
        updateTimerUI('stopped');
    }
});