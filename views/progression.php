<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="fw-light text-tertiary mb-1"><?= __('progression_title') ?></h2>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <button class="btn btn-outline-primary shadow-sm rounded-pill fw-bold px-4 py-2 w-auto text-nowrap" data-bs-toggle="modal" data-bs-target="#addProgressModal">
            <i class="material-icons-outlined icon-md fs-3 me-2">&#xea28;</i><?= __('progression_add_btn') ?></button>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-0 mb-5 bg-transparent overflow-hidden">
    <div class="card-body p-4 text-center">
        <h5 class="text-secondary small fw-bold text-uppercase mb-3"><?= __('progression_current_session') ?></h5>

        <div class="display-1 fw-light my-3" id="timerDisplay">00:00:00</div>

        <div class="d-flex justify-content-center gap-3 mt-4">
            <button id="btnStart" class="btn btn-success btn-lg rounded-pill px-5 shadow-sm" onclick="startTimer()">
                <i class="material-icons icon-lg me-2">&#xe037;</i><?= __('progression_btn_start') ?>
            </button>

            <button id="btnResume" class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm d-none" onclick="resumeTimer()">
                <i class="material-icons icon-lg me-2">&#xe037;</i><?= __('progression_btn_resume') ?>
            </button>

            <button id="btnPause" class="btn btn-warning btn-lg rounded-pill px-5 shadow-sm d-none" onclick="pauseTimer()">
                <i class="material-icons icon-lg me-2">&#xe034;</i><?= __('progression_btn_pause') ?>
            </button>

            <button id="btnStop" class="btn btn-danger btn-lg rounded-pill px-5 shadow-sm d-none" onclick="stopTimer()">
                <i class="material-icons icon-lg me-2">&#xe047;</i><?= __('progression_btn_stop') ?>
            </button>
        </div>
        <p class="text-muted small mt-3 mb-0 d-none" id="timerHint"><?= __('progression_timer_hint') ?></p>
    </div>
    <div class="progress" style="height: 6px;">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="timerProgress"></div>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="p-4 rounded-4 bg-body shadow-sm h-100 border-start border-4 border-primary">
            <div class="text-secondary small fw-bold text-uppercase"><?= __('progression_stat_total') ?></div>
            <div class="fs-2 fw-bold"><?= $totalHours ?> <small class="fs-6 text-muted"><?= __('progression_stat_hours') ?></small></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-4 rounded-4 bg-body shadow-sm h-100 border-start border-4 border-info">
            <div class="text-secondary small fw-bold text-uppercase"><?= __('progression_stat_sessions') ?></div>
            <div class="fs-2 fw-bold"><?= count($history) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-4 rounded-4 bg-body shadow-sm h-100 border-start border-4 border-warning">
            <div class="text-secondary small fw-bold text-uppercase"><?= __('progression_stat_last') ?></div>
            <div class="fs-5 fw-bold text-truncate">
                <?= !empty($history) ? date('d/m/Y', strtotime($history[0]['log_date'])) : '-' ?>
            </div>
        </div>
    </div>
</div>

<?php if (empty($history)): ?>
    <div class="text-center py-5">
        <div class="mb-3 text-secondary opacity-25"><i class="material-icons" style="font-size: 4em;">&#xe85d;</i></div>
        <h5 class="text-secondary"><?= __('progression_empty_title') ?></h5>
        <p class="small text-muted"><?= __('progression_empty_desc') ?></p>
    </div>
<?php else: ?>
    <h5 class="fw-bold mb-3"><?= __('progression_history_title') ?></h5>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="list-group list-group-flush">
            <?php foreach ($history as $h): ?>
                <div class="list-group-item p-4 border-light-subtle hover-bg-light">
                    <div class="d-flex gap-3 align-items-center">
                        <div class="flex-shrink-0">
                            <?php if ($h['game_image']): ?>
                                <img src="<?= $h['game_image'] ?>" class="rounded-3 object-fit-cover shadow-sm" style="width: 60px; height: 60px;">
                            <?php else: ?>
                                <div class="rounded-3 bg-secondary-subtle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="material-icons align-middle text-secondary icon-lg">&#xe338;</i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="mb-0 fw-bold text-body"><?= htmlspecialchars($h['game_title']) ?></h6>
                                <span class="badge bg-secondary-subtle text-secondary fw-normal">
                                    <i class="material-icons align-middle icon-sm me-1">&#xe8b5;</i>
                                    <?= floor($h['duration_minutes'] / 60) ?>h <?= $h['duration_minutes'] % 60 > 0 ? sprintf('%02d', $h['duration_minutes'] % 60) : '00' ?>
                                </span>
                            </div>
                            <div class="text-muted small">
                                <span><?= date('d/m/Y', strtotime($h['log_date'])) ?></span>
                                <?php if ($h['progress_value']): ?>
                                    <span class="mx-2">•</span>
                                    <span class="text-primary"><i class="material-icons align-middle icon-sm me-1">&#xe153;</i><?= htmlspecialchars($h['progress_value']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($h['notes']): ?>
                                <div class="mt-2 small text-secondary fst-italic border-start border-2 ps-2">
                                    "<?= nl2br(htmlspecialchars($h['notes'])) ?>"
                                </div>
                            <?php endif; ?>
                        </div>

                        <a href="index.php?action=delete_progress&id=<?= $h['id'] ?>" class="btn-action btn-icon-action btn-light text-danger" onclick="return confirm('<?= addslashes(__('progression_confirm_delete')) ?>')" title="<?= __('js_btn_delete') ?>">
                            <i class="material-icons-outlined icon-md">&#xe872;</i></a>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>


<div class="modal fade" id="addProgressModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form action="index.php?action=add_progress" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold"><?= __('progression_modal_title') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary"><?= __('progression_label_game') ?></label>
                        <select name="game_id" class="form-select rounded-3" required>
                            <?php foreach ($games as $g): ?>
                                <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary"><?= __('progression_label_date') ?></label>
                            <input type="date" name="log_date" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary"><?= __('progression_label_progress') ?></label>
                            <input type="text" name="progress_value" class="form-control rounded-3" placeholder="<?= __('progression_placeholder_progress') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary"><?= __('progression_label_duration') ?></label>
                        <div class="input-group">
                            <input type="number" name="duration_hours" id="inputHours" class="form-control" placeholder="0" min="0">
                            <span class="input-group-text bg-body-tertiary">h</span>
                            <input type="number" name="duration_minutes" id="inputMinutes" class="form-control" placeholder="0" min="0" max="59">
                            <span class="input-group-text bg-body-tertiary">m</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary"><?= __('progression_label_notes') ?></label>
                        <textarea name="notes" class="form-control rounded-3" rows="3" placeholder="<?= __('progression_placeholder_notes') ?>"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4"><?= __('modal_btn_save') ?></button>
                    <button type="button" class="btn btn-light fw-bold rounded-pill px-4" data-bs-dismiss="modal"><?= __('modal_btn_cancel') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/progression.js"></script>