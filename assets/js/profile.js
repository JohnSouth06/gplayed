// --- FONCTIONS UTILITAIRES ---
function copyShareLink() {
    var copyText = document.getElementById("shareLinkInput");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value).then(function () {
        var feedback = document.getElementById("copyFeedback");
        feedback.style.display = "block";
        setTimeout(function () { feedback.style.display = "none"; }, 3000);
    });
}

// --- ÉTATS DE SYNCHRONISATION ---
let isImportingSteam = false;
let isPsnSyncing = false;
let psnModal = null;
let psnCountdownInterval = null;

// --- SÉCURITÉ ANTI-QUITTER UNIQUE ---
window.onbeforeunload = function (e) {
    if (isImportingSteam || isPsnSyncing) {
        const msg = "La synchronisation est en cours. Si vous quittez, l'importation sera incomplète.";
        e.returnValue = msg;
        return msg;
    }
};

// --- INITIALISATION AU CHARGEMENT ---
document.addEventListener("DOMContentLoaded", function () {
    // 1. Initialisation Modal Steam (si présente)
    const steamModalEl = document.getElementById('steamSyncModal');
    if (steamModalEl) {
        const steamModal = new bootstrap.Modal(steamModalEl);
        steamModal.show();
        isImportingSteam = true;
        startSteamSync();
    }

    // 2. Initialisation Modal PSN
    const psnModalEl = document.getElementById('psnSyncModal');
    if (psnModalEl) {
        psnModal = new bootstrap.Modal(psnModalEl);
    }

    // 3. Vérification du compte à rebours PSN au chargement
    const btnSyncPsn = document.getElementById('btn-sync-psn');
    if (btnSyncPsn && btnSyncPsn.disabled) {
        const remaining = parseInt(btnSyncPsn.getAttribute('data-remaining'));
        if (remaining > 0) {
            startPsnCountdown(remaining);
        }
    }
});

// --- LOGIQUE SYNCHRONISATION PSN ---
function startPsnCountdown(seconds) {
    const countdownEl = document.getElementById('psn-countdown');
    const btnSyncPsn = document.getElementById('btn-sync-psn');
    const psnIdInput = document.getElementById('psn_id_sync_input');
    const syncMessage = document.getElementById('psn-sync-message');

    if (!countdownEl || seconds <= 0) return;

    clearInterval(psnCountdownInterval);
    psnCountdownInterval = setInterval(() => {
        seconds--;
        if (seconds <= 0) {
            clearInterval(psnCountdownInterval);
            if (btnSyncPsn) btnSyncPsn.disabled = false;
            if (psnIdInput) psnIdInput.disabled = false;
            if (syncMessage) {
                syncMessage.textContent = "La synchronisation est à nouveau disponible.";
                syncMessage.style.color = "green";
            }
            return;
        }
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        countdownEl.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }, 1000);
}

const btnSyncPsn = document.getElementById('btn-sync-psn');
if (btnSyncPsn) {
    btnSyncPsn.addEventListener('click', async () => {
        const psnIdInput = document.getElementById('psn_id_sync_input');
        const syncMessage = document.getElementById('psn-sync-message');
        const statusText = document.getElementById('psnSyncStatus');
        
        const psnId = psnIdInput.value.trim();
        if (!psnId) {
            syncMessage.style.color = "red";
            syncMessage.textContent = "Veuillez entrer un ID PSN valide.";
            return;
        }

        isPsnSyncing = true;
        syncMessage.textContent = "";
        if (statusText) statusText.textContent = "Recherche de vos trophées sur le PSN...";
        if (psnModal) psnModal.show();

        try {
            const response = await fetch('/?action=api_psn_sync', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ psn_id: psnId })
            });

            const data = await response.json();

            if (data.success) {
                syncMessage.style.color = "green";
                syncMessage.innerHTML = `<i class="fas fa-check-circle me-2"></i>Succès ! Prochaine synchro dans <span id="psn-countdown">60:00</span>`;
                
                // Désactivation et lancement du cooldown
                btnSyncPsn.disabled = true;
                psnIdInput.disabled = true;
                startPsnCountdown(3600);
            } else {
                syncMessage.style.color = "red";
                syncMessage.textContent = data.message;
            }
        } catch (error) {
            syncMessage.style.color = "red";
            syncMessage.textContent = "Erreur réseau : impossible de contacter le serveur.";
        } finally {
            isPsnSyncing = false;
            setTimeout(() => { if (psnModal) psnModal.hide(); }, 500);
        }
    });
}

// --- LOGIQUE SYNCHRONISATION STEAM ---
async function startSteamSync() {
    try {
        const listResponse = await fetch('/index.php?action=api_steam_games');
        const listData = await listResponse.json();

        if (!listData.success) {
            document.getElementById('steamSyncStatus').innerText = "Erreur: " + listData.error;
            document.getElementById('steamSyncStatus').classList.add("text-danger");
            isImportingSteam = false;
            return;
        }

        const games = listData.games;
        const total = games.length;

        if (total === 0) {
            document.getElementById('steamSyncStatus').innerText = "Votre bibliothèque est déjà à jour !";
            const bar = document.getElementById('steamProgressBar');
            bar.style.width = "100%";
            bar.innerText = "100%";
            finishSync();
            return;
        }

        let processed = 0;
        for (const game of games) {
            document.getElementById('steamSyncStatus').innerText = `Importation : ${game.name}\n(${processed + 1} sur ${total} jeux)`;
            await fetch('/index.php?action=api_steam_import_single', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(game)
            });
            processed++;
            const percent = Math.round((processed / total) * 100);
            const bar = document.getElementById('steamProgressBar');
            bar.style.width = percent + "%";
            bar.innerText = percent + "%";
        }
        finishSync();
    } catch (e) {
        document.getElementById('steamSyncStatus').innerText = "Une erreur est survenue pendant la synchronisation.";
        document.getElementById('steamSyncStatus').classList.add("text-danger");
        isImportingSteam = false;
    }
}

async function finishSync() {
    isImportingSteam = false;
    await fetch('/index.php?action=api_steam_complete');
    window.location.href = '/profile';
}