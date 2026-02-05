async function toggleReaction(type, refId, reaction, btn) {
    // Empêche le dropdown de se fermer (optionnel, selon préférence UX)
    // event.stopPropagation(); 

    // Sélectionne les boutons voisins via la classe correcte
    const container = btn.parentElement;
    const allBtns = container.querySelectorAll('.reaction-btn');
    
    // Vérifie si le bouton cliqué était déjà actif (classe Bootstrap)
    const wasActive = btn.classList.contains('bg-primary-subtle');
    
    // Reset : On enlève la couleur de fond de tous les boutons
    allBtns.forEach(b => b.classList.remove('bg-primary-subtle'));

    // Toggle : Si ce n'était pas actif, on l'active
    if (!wasActive) {
        btn.classList.add('bg-primary-subtle');
    }

    try {
        const response = await fetch('/social/react', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ type: type, ref_id: refId, reaction: reaction })
        });
        
        const data = await response.json();
        
        if(data.status === 'success') {
            // Recharge la page pour mettre à jour les compteurs totaux
            location.reload(); 
        }
    } catch (e) {
        console.error('Erreur reaction', e);
    }
}