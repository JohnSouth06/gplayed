async function toggleReaction(type, refId, reaction, btn) {
    // Feedback visuel immédiat (Optimistic UI)
    const allBtns = btn.parentElement.querySelectorAll('.reaction-btn');
    const wasActive = btn.classList.contains('active-reaction');
    
    // Reset buttons
    allBtns.forEach(b => b.classList.remove('active-reaction'));

    // Toggle logic visual
    if (!wasActive) {
        btn.classList.add('active-reaction');
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
            // Optionnel : Recharger juste le compteur de réactions ici si vous voulez être précis
            // Ou recharger la page silencieusement
            location.reload(); 
        }
    } catch (e) {
        console.error('Erreur reaction', e);
    }
}