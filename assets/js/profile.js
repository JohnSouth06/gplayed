function copyShareLink() {
    var copyText = document.getElementById("shareLinkInput");
    copyText.select();
    copyText.setSelectionRange(0, 99999); // Pour mobile
    navigator.clipboard.writeText(copyText.value).then(function() {
        var feedback = document.getElementById("copyFeedback");
        feedback.style.display = "block";
        setTimeout(function(){ feedback.style.display = "none"; }, 3000);
    });
}

let isImportingSteam = false;

// 1. Avertir l'utilisateur s'il essaie de fermer l'onglet
window.addEventListener('beforeunload', function (e) {
    if (isImportingSteam) {
        // Le message standard du navigateur s'affichera
        e.preventDefault();
        e.returnValue = ''; 
    }
});

document.addEventListener("DOMContentLoaded", function() {
    // 2. Afficher la modale de force au chargement de la page
    const steamModalEl = document.getElementById('steamSyncModal');
    if (steamModalEl) {
        const steamModal = new bootstrap.Modal(steamModalEl);
        steamModal.show(); // Affiche la modale
        isImportingSteam = true; // Active la sécurité anti-fermeture
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
            isImportingSteam = false; // On désactive la sécurité en cas d'erreur
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
    isImportingSteam = false; // L'import est fini, on autorise la redirection
    await fetch('/index.php?action=api_steam_complete');
    window.location.href = '/profile'; // Redirection vers le profil propre
}