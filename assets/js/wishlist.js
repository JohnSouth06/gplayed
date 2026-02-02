/**
 * Ouvre la modale en mode "Ajout"
 * Réinitialise les champs pour une nouvelle entrée
 */
function openModal() {
    // Réinitialisation des champs simples
    const fields = ['gameId', 'gameTitle', 'gameGenres', 'gameComment', 'gamePrice', 'gameDate', 'gameDateVisual'];
    fields.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });

    // Gestion de l'image et du placeholder
    const previewImg = document.getElementById('previewImg');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    
    if (previewImg && uploadPlaceholder) {
        previewImg.src = '';
        previewImg.classList.add('d-none');
        uploadPlaceholder.classList.remove('d-none');
    }

    // Masquer le bouton de suppression
    const deleteBtn = document.getElementById('deleteBtnContainer');
    if (deleteBtn) deleteBtn.classList.add('d-none');
    
    // Forcer le statut pour la Wishlist
    const statusField = document.getElementById('gameStatus');
    if (statusField) statusField.value = 'wishlist';

    new bootstrap.Modal(document.getElementById('gameModal')).show();
}

/**
 * Ouvre la modale en mode "Édition"
 * Remplit les champs avec les données du jeu
 * @param {Object} game - L'objet jeu passé par PHP/JSON
 */
function editGame(game) {
    document.getElementById('gameId').value = game.id;
    document.getElementById('gameRawgId').value = game.rawg_id || '';
    document.getElementById('gameTitle').value = game.title;
    document.getElementById('gamePlatform').value = game.platform;
    document.getElementById('gameGenres').value = game.genres || '';
    document.getElementById('gameComment').value = game.comment || '';
    document.getElementById('gamePrice').value = game.estimated_price || '';
    
    // Gestion Date
    document.getElementById('gameDate').value = game.release_date || '';
    const dateVisual = document.getElementById('gameDateVisual');
    if (dateVisual) dateVisual.value = game.release_date || '';

    // Gestion Image
    const previewImg = document.getElementById('previewImg');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    const hiddenImg = document.getElementById('gameImageHidden');

    if (game.image_url) {
        previewImg.src = game.image_url;
        previewImg.classList.remove('d-none');
        uploadPlaceholder.classList.add('d-none');
        hiddenImg.value = game.image_url;
    }

    // Gestion Lien Suppression
    const deleteLink = document.getElementById('deleteLink');
    if (deleteLink) {
        deleteLink.href = "/delete?id=" + game.id;
        document.getElementById('deleteBtnContainer').classList.remove('d-none');
    }

    new bootstrap.Modal(document.getElementById('gameModal')).show();
}

/**
 * Prévisualisation de l'image uploadée
 */
function previewFile(input) {
    const file = input.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewImg = document.getElementById('previewImg');
            previewImg.src = e.target.result;
            previewImg.classList.remove('d-none');
            document.getElementById('uploadPlaceholder').classList.add('d-none');
        }
        reader.readAsDataURL(file);
    }
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    // SYSTEME DE SYNCHRONISATION DATE (Pour que RAWG remplisse le champ visuel)
    const hiddenDateInput = document.getElementById('gameDate');
    if(hiddenDateInput) {
        // On intercepte les changements de valeur faits par script (ex: dashboard.js ou rawg)
        const descriptor = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
        Object.defineProperty(hiddenDateInput, 'value', {
            set: function(val) {
                const oldVal = this.value;
                descriptor.set.call(this, val);
                if(oldVal !== val) {
                    const visual = document.getElementById('gameDateVisual');
                    if(visual) visual.value = val;
                }
            },
            get: function() {
                return descriptor.get.call(this);
            }
        });
    }
});