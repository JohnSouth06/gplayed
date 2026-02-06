let timerInterval;

function updateTimerUI(state) {
    const btnStart = document.getElementById('btnStart');
    const btnResume = document.getElementById('btnResume');
    const btnPause = document.getElementById('btnPause');
    const btnStop = document.getElementById('btnStop');
    
    const timerHint = document.getElementById('timerHint');
    const timerDisplay = document.getElementById('timerDisplay');
    const timerProgress = document.getElementById('timerProgress');

    btnStart.classList.add('d-none');
    btnResume.classList.add('d-none');
    btnPause.classList.add('d-none');
    btnStop.classList.add('d-none');

    if (state === 'running') {
        btnPause.classList.remove('d-none');
        btnStop.classList.remove('d-none');
        timerHint.classList.remove('d-none');
        
        timerDisplay.classList.add('text-primary');
        timerDisplay.classList.remove('text-warning'); 
        timerProgress.parentElement.classList.remove('d-none'); 
        timerProgress.classList.add('progress-bar-animated', 'progress-bar-striped');
        timerProgress.style.width = "100%";

    } else if (state === 'paused') {
        btnResume.classList.remove('d-none');
        btnStop.classList.remove('d-none');
        timerHint.classList.remove('d-none');
        
        timerDisplay.classList.remove('text-primary');
        timerDisplay.classList.add('text-warning'); 
        timerProgress.classList.remove('progress-bar-animated', 'progress-bar-striped'); 

    } else { 
        btnStart.classList.remove('d-none');
        timerHint.classList.add('d-none');
        
        timerDisplay.classList.remove('text-primary', 'text-warning');
        timerProgress.style.width = "0%";
        timerDisplay.innerText = "00:00:00";
    }
}

function startTimer() {
    localStorage.setItem('session_start_time', Date.now());
    localStorage.setItem('session_accumulated', 0); 
    localStorage.setItem('session_state', 'running');
    
    runTimer();
}

function resumeTimer() {
    localStorage.setItem('session_start_time', Date.now()); 
    localStorage.setItem('session_state', 'running');
    
    runTimer();
}

function pauseTimer() {
    clearInterval(timerInterval);
    
    const startTime = parseInt(localStorage.getItem('session_start_time'));
    const currentSession = Date.now() - startTime;
    
    let accumulated = parseInt(localStorage.getItem('session_accumulated')) || 0;
    accumulated += currentSession;
    
    localStorage.setItem('session_accumulated', accumulated);
    localStorage.setItem('session_state', 'paused');
    
    updateTimerUI('paused');
    updateDisplayWithTotal(accumulated); 
}

function runTimer() {
    updateTimerUI('running');
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(updateTimerDisplay, 1000);
    updateTimerDisplay(); 
}

function stopTimer() {
    clearInterval(timerInterval);
    
    let totalMs = parseInt(localStorage.getItem('session_accumulated')) || 0;
    
    if (localStorage.getItem('session_state') === 'running') {
        const startTime = parseInt(localStorage.getItem('session_start_time'));
        totalMs += (Date.now() - startTime);
    }
    
    const totalMinutes = Math.floor(totalMs / 1000 / 60);
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;

    localStorage.removeItem('session_start_time');
    localStorage.removeItem('session_accumulated');
    localStorage.removeItem('session_state');

    updateTimerUI('stopped');

    const modal = new bootstrap.Modal(document.getElementById('addProgressModal'));
    document.getElementById('inputHours').value = hours;
    document.getElementById('inputMinutes').value = minutes;
    modal.show();
}

function updateTimerDisplay() {
    const startTime = parseInt(localStorage.getItem('session_start_time'));
    const accumulated = parseInt(localStorage.getItem('session_accumulated')) || 0;
    
    if(!startTime) return;

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

document.addEventListener('DOMContentLoaded', () => {
    const state = localStorage.getItem('session_state');
    
    if (state === 'running') {
        runTimer();
    } else if (state === 'paused') {
        const accumulated = parseInt(localStorage.getItem('session_accumulated')) || 0;
        updateTimerUI('paused');
        updateDisplayWithTotal(accumulated);
    } else {
        updateTimerUI('stopped');
    }
});