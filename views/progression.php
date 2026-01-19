<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="fw-bold mb-1">Journal de Jeu</h2>
        <p class="text-secondary mb-0">Suivez vos sessions et votre temps de jeu.</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <button class="btn btn-primary rounded-pill shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#addProgressModal">
            <i class="fas fa-pen me-2"></i>Saisie Manuelle
        </button>
    </div>
</div>

<!-- CARD CHRONOMÈTRE -->
<div class="card border-0 shadow-sm rounded-4 mb-5 bg-body-tertiary overflow-hidden">
    <div class="card-body p-4 text-center">
        <h5 class="text-secondary small fw-bold text-uppercase mb-3">Session en cours</h5>
        
        <!-- Affichage du Temps -->
        <div class="display-1 fw-bold font-monospace my-3" id="timerDisplay">00:00:00</div>
        
        <!-- Contrôles -->
        <div class="d-flex justify-content-center gap-3 mt-4">
            <button id="btnStart" class="btn btn-success btn-lg rounded-pill px-5 shadow-sm" onclick="startTimer()">
                <i class="fas fa-play me-2"></i>Démarrer
            </button>
            <button id="btnStop" class="btn btn-danger btn-lg rounded-pill px-5 shadow-sm d-none" onclick="stopTimer()">
                <i class="fas fa-stop me-2"></i>Arrêter & Enregistrer
            </button>
        </div>
        <p class="text-muted small mt-3 mb-0 d-none" id="timerHint">Le chronomètre continue même si vous quittez cette page.</p>
    </div>
    <div class="progress" style="height: 4px;">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="timerProgress" style="width: 0%"></div>
    </div>
</div>

<!-- STATS RAPIDES -->
<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="p-4 rounded-4 bg-body shadow-sm h-100 border-start border-4 border-primary">
            <div class="text-secondary small fw-bold text-uppercase">Temps Total</div>
            <div class="fs-2 fw-bold"><?= $totalHours ?> <small class="fs-6 text-muted">Heures</small></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-4 rounded-4 bg-body shadow-sm h-100 border-start border-4 border-info">
            <div class="text-secondary small fw-bold text-uppercase">Sessions</div>
            <div class="fs-2 fw-bold"><?= count($history) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-4 rounded-4 bg-body shadow-sm h-100 border-start border-4 border-warning">
            <div class="text-secondary small fw-bold text-uppercase">Dernière activité</div>
            <div class="fs-5 fw-bold text-truncate">
                <?= !empty($history) ? date('d/m/Y', strtotime($history[0]['log_date'])) : '-' ?>
            </div>
        </div>
    </div>
</div>

<!-- HISTORIQUE -->
<?php if (empty($history)): ?>
    <div class="text-center py-5">
        <div class="mb-3 text-secondary opacity-25"><i class="fas fa-clipboard-list fa-4x"></i></div>
        <h5 class="text-secondary">Aucune session enregistrée</h5>
        <p class="small text-muted">Lancez le chronomètre ou ajoutez une session manuellement !</p>
    </div>
<?php else: ?>
    <h5 class="fw-bold mb-3">Historique récent</h5>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="list-group list-group-flush">
            <?php foreach ($history as $h): ?>
                <div class="list-group-item p-4 border-light-subtle hover-bg-light">
                    <div class="d-flex gap-3 align-items-center">
                        <!-- Image -->
                        <div class="flex-shrink-0">
                            <?php if($h['game_image']): ?>
                                <img src="<?= $h['game_image'] ?>" class="rounded-3 object-fit-cover shadow-sm" style="width: 60px; height: 60px;">
                            <?php else: ?>
                                <div class="rounded-3 bg-secondary-subtle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="fas fa-gamepad text-secondary fa-lg"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Infos -->
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="mb-0 fw-bold text-body"><?= htmlspecialchars($h['game_title']) ?></h6>
                                <span class="badge bg-secondary-subtle text-secondary fw-normal">
                                    <i class="far fa-clock me-1"></i> 
                                    <?= floor($h['duration_minutes']/60) ?>h <?= $h['duration_minutes']%60 > 0 ? sprintf('%02d', $h['duration_minutes']%60) : '00' ?>
                                </span>
                            </div>
                            <div class="text-muted small">
                                <span><?= date('d/m/Y', strtotime($h['log_date'])) ?></span>
                                <?php if($h['progress_value']): ?>
                                    <span class="mx-2">•</span> 
                                    <span class="text-primary"><i class="fas fa-flag me-1"></i><?= htmlspecialchars($h['progress_value']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if($h['notes']): ?>
                                <div class="mt-2 small text-secondary fst-italic border-start border-2 ps-2">
                                    "<?= nl2br(htmlspecialchars($h['notes'])) ?>"
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Actions -->
                        <a href="index.php?action=delete_progress&id=<?= $h['id'] ?>" class="btn btn-sm btn-light text-danger rounded-circle shadow-sm" onclick="return confirm('Supprimer cette entrée ?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>


<!-- MODAL AJOUT (Pré-remplie par le chrono) -->
<div class="modal fade" id="addProgressModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form action="index.php?action=add_progress" method="POST">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Enregistrer la session</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Jeu joué</label>
                        <select name="game_id" class="form-select rounded-3" required>
                            <?php foreach($games as $g): ?>
                                <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Date</label>
                            <input type="date" name="log_date" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Avancement</label>
                            <input type="text" name="progress_value" class="form-control rounded-3" placeholder="Ex: Niveau 5...">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Durée (Calculée)</label>
                        <div class="input-group">
                            <input type="number" name="duration_hours" id="inputHours" class="form-control" placeholder="0" min="0">
                            <span class="input-group-text bg-body-tertiary">h</span>
                            <input type="number" name="duration_minutes" id="inputMinutes" class="form-control" placeholder="0" min="0" max="59">
                            <span class="input-group-text bg-body-tertiary">m</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Notes</label>
                        <textarea name="notes" class="form-control rounded-3" rows="3" placeholder="Un moment marquant ?"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Sauvegarder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
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
</script>