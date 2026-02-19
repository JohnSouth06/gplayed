document.addEventListener('DOMContentLoaded', () => {
    // Initialise l'animation du compteur si la fonction existe (dans main.js ou dashboard.js)
    if (typeof initCounters === 'function') initCounters();
});