function copyShareLink() {
    var copyText = document.getElementById("shareLinkInput");
    copyText.select();
    copyText.setSelectionRange(0, 99999); // Pour mobile
    navigator.clipboard.writeText(copyText.value).then(function () {
        var feedback = document.getElementById("copyFeedback");
        feedback.style.display = "block";
        setTimeout(function () { feedback.style.display = "none"; }, 3000);
    });
}

let isImportingSteam = false;

window.addEventListener('beforeunload', function (e) {
    if (isImportingSteam) {
        e.preventDefault();
        e.returnValue = '';
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const steamModalEl = document.getElementById('steamSyncModal');
    if (steamModalEl) {
        const steamModal = new bootstrap.Modal(steamModalEl);
        steamModal.show();
        isImportingSteam = true;
        startSteamSync();
    }
});

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
            document.getElementById('steamProgressBar').style.width = "100%";
            document.getElementById('steamProgressBar').innerText = "100%";
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

            document.getElementById('steamProgressBar').style.width = percent + "%";
            document.getElementById('steamProgressBar').innerText = percent + "%";
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

document.addEventListener('DOMContentLoaded', () => {
    const btnSyncPsn = document.getElementById('btn-sync-psn');
    const syncMessage = document.getElementById('psn-sync-message');

    if (btnSyncPsn) {
        btnSyncPsn.addEventListener('click', async () => {
            const btnText = btnSyncPsn.querySelector('.btn-text');
            const btnLoader = btnSyncPsn.querySelector('.btn-loader');

            // 1. Mise à jour de l'UI (Désactiver le bouton et afficher le loader)
            btnSyncPsn.disabled = true;
            btnText.style.display = 'none';
            btnLoader.style.display = 'inline-block';
            syncMessage.textContent = '';
            syncMessage.style.color = 'inherit';

            try {
                // Appel au routeur web principal (qui utilise la session PHP $_SESSION)
                const response = await fetch('/?action=api_psn_sync', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();

                console.log("Données reçues de Sony :", data); 

                // 3. Traitement de la réponse
                if (data.success) {
                    syncMessage.style.color = 'green';
                    syncMessage.textContent = `Succès ! ${data.games_synced} jeu(x) et ${data.trophies_processed} trophée(s) synchronisés.`;
                } else {
                    syncMessage.style.color = 'red';
                    syncMessage.textContent = `Erreur : ${data.message}`;
                }
            } catch (error) {
                console.error("Erreur lors de la synchronisation PSN :", error);
                syncMessage.style.color = 'red';
                syncMessage.textContent = "Une erreur réseau est survenue. Veuillez réessayer.";
            } finally {
                // 4. Restauration de l'UI
                btnSyncPsn.disabled = false;
                btnText.style.display = 'inline-block';
                btnLoader.style.display = 'none';
            }
        });
    }
});