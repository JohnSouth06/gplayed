let timerInterval;

function startTimer() {
    // Enregistrer l'heure de début si pas déjà fait
    if (!localStorage.getItem('session_start_time')) {
        localStorage.setItem('session_start_time', Date.now());
    }
    
    // UI
    document.getElementById('btnStart').classList.add('d-none');
    document.getElementById('btnStop').classList.remove('d-none');
    document.getElementById('timerHint').classList.remove('d-none');
    document.getElementById('timerProgress').style.width = "100%";
    document.getElementById('timerDisplay').classList.add('text-primary');

    // Lancer la boucle
    timerInterval = setInterval(updateTimerDisplay, 1000);
    updateTimerDisplay();
}

function stopTimer() {
    clearInterval(timerInterval);
    
    const startTime = parseInt(localStorage.getItem('session_start_time'));
    const now = Date.now();
    const diff = now - startTime;
    
    // Convertir en heures/minutes
    const totalMinutes = Math.floor(diff / 1000 / 60);
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;

    // Reset
    localStorage.removeItem('session_start_time');
    document.getElementById('timerDisplay').innerText = "00:00:00";
    document.getElementById('timerDisplay').classList.remove('text-primary');
    document.getElementById('btnStart').classList.remove('d-none');
    document.getElementById('btnStop').classList.add('d-none');
    document.getElementById('timerHint').classList.add('d-none');
    document.getElementById('timerProgress').style.width = "0%";

    // Ouvrir Modale & Remplir
    const modal = new bootstrap.Modal(document.getElementById('addProgressModal'));
    document.getElementById('inputHours').value = hours;
    document.getElementById('inputMinutes').value = minutes;
    modal.show();
}

function updateTimerDisplay() {
    const startTime = parseInt(localStorage.getItem('session_start_time'));
    if(!startTime) return;

    const diff = Date.now() - startTime;
    
    const seconds = Math.floor((diff / 1000) % 60);
    const minutes = Math.floor((diff / 1000 / 60) % 60);
    const hours = Math.floor((diff / 1000 / 60 / 60));

    const formatted = 
        (hours < 10 ? "0" + hours : hours) + ":" + 
        (minutes < 10 ? "0" + minutes : minutes) + ":" + 
        (seconds < 10 ? "0" + seconds : seconds);

    document.getElementById('timerDisplay').innerText = formatted;
}

// Au chargement : vérifier si un chrono est en cours
document.addEventListener('DOMContentLoaded', () => {
    if (localStorage.getItem('session_start_time')) {
        startTimer();
    }
});