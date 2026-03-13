<div class="row justify-content-center">
    <div class="col-md-8">
        <h2 class="mb-4 fw-light"><?= __('profile_title') ?></h2>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 text-center h-100 bg-body rounded-4">
                    <div class="card-body">
                        <div class="mb-3 mt-3">
                            <?php
                            $profileAvatar = !empty($user['avatar_url']) ? $user['avatar_url'] : 'uploads/avatars/default.png';
                            ?>
                            <img src="<?= htmlspecialchars($profileAvatar) ?>" class="rounded-circle shadow-sm object-fit-cover" style="width: 120px; height: 120px; border: 4px solid var(--bs-body-bg);" alt="Profil">
                        </div>
                        <h4 class="card-title fw-bold"><?= htmlspecialchars($user['username']) ?></h4>
                        <p class="text-secondary small mb-1"><?= htmlspecialchars($user['email']) ?></p>
                        <p class="text-secondary small"><?= __('profile_member_since') ?> <?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0 mb-4 rounded-4 bg-body">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-4 fw-bold"><?= __('profile_settings_title') ?></h5>

                        <form action="/update_profile" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                            <div class="mb-3">
                                <label class="form-label small text-muted text-uppercase fw-bold"><?= __('auth_user') ?? 'Nom d\'utilisateur' ?></label>
                                <input type="text" name="username" class="form-control rounded-3" value="<?= htmlspecialchars($user['username']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small text-muted text-uppercase fw-bold"><?= __('auth_mail') ?></label>
                                <input type="email" name="email" class="form-control rounded-3" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small text-muted text-uppercase fw-bold"><?= __('profile_label_new_pass') ?></label>
                                <input type="password" name="new_password" class="form-control rounded-3" placeholder="<?= __('profile_placeholder_pass') ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small text-muted text-uppercase fw-bold"><?= __('label_language') ?></label>
                                <select name="language" class="form-select rounded-3" onchange="this.form.submit()">
                                    <option value="fr" <?= ($user['language'] ?? 'fr') === 'fr' ? 'selected' : '' ?>><?= __('option_fr') ?></option>
                                    <option value="en" <?= ($user['language'] ?? 'fr') === 'en' ? 'selected' : '' ?>><?= __('option_en') ?></option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small text-muted text-uppercase fw-bold"><?= __('profile_label_avatar') ?></label>
                                <input type="file" name="avatar" class="form-control file-upload rounded-3 mb-2">
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">
                                    <?= __('profile_btn_update') ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4 rounded-4 bg-body">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-3 fw-bold"><?= __('profile_data_title') ?></h5>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <a href="/export_json" class="btn btn-outline-primary w-100 rounded-3 py-2">
                                    <i class="material-icons icon-sm me-2 align-middle">download</i><?= __('profile_export_btn') ?>
                                </a>
                            </div>
                            <div class="col-sm-6">
                                <form action="/import_json" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <div class="input-group">
                                        <input type="file" name="json_file" class="form-control file-upload rounded-3" accept=".json" required>
                                    </div>
                                    <div class="mt-2">
                                        <button type="submit" class="btn btn-outline w-100 rounded-3 text-secondary border">
                                            <i class="material-icons icon-sm me-2 align-middle">publish</i><?= __('profile_import_btn') ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PSN SYNC -->
                <?php
                    // Calcul du temps restant pour la synchronisation PSN
                    $lastSync = !empty($user['last_psn_sync']) ? strtotime($user['last_psn_sync']) : 0;
                    $diff = time() - $lastSync;
                    $cooldown = 3600; // 1 heure en secondes
                    $isSyncDisabled = ($diff < $cooldown);
                    $remainingSeconds = $isSyncDisabled ? ($cooldown - $diff) : 0;
                    ?>

                    <div class="card shadow-sm border-0 mb-4 rounded-4 bg-body">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-3 fw-bold"><i class="fab fa-playstation text-primary me-2"></i><?= __('psn_sync_title') ?></h5>
                        <p class="small text-secondary mb-4">
                            <?= __('psn_sync_desc') ?>
                        </p>

                        <div class="mb-3">
                            <label class="form-label small text-muted text-uppercase fw-bold"><?= __('psn_sync_id') ?></label>
                            <div class="input-group">
                                <input type="text" id="psn_id_sync_input" class="form-control"
                                    value="<?= htmlspecialchars($user['psn_id'] ?? '') ?>"
                                    placeholder="Pseudo PSN"
                                    <?= $isSyncDisabled ? 'disabled' : '' ?>>
                                <button id="btn-sync-psn" class="btn btn-primary px-4 shadow-sm fw-bold"
                                    <?= $isSyncDisabled ? 'disabled' : '' ?>
                                    data-remaining="<?= $remainingSeconds ?>">
                                    <i class="fas fa-sync me-2"></i><?= __('psn_sync_btn') ?>
                                </button>
                            </div>
                        </div>

                        <div id="psn-sync-message" class="text-center mt-3 fw-bold" style="<?= $isSyncDisabled ? 'color: orange;' : '' ?>">
                            <?php if ($isSyncDisabled): ?>
                                <i class="fas fa-clock me-2"></i><?= __('psn_sync_cooldown') ?> <span id="psn-countdown">--:--</span>
                            <?php endif; ?>
                        </div>
                    </div>
            </div>

            <!-- STEAM IMPORT -->
            <div class="card shadow-sm border-0 mb-4 rounded-4 bg-body">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3 fw-bold"><i class="fab fa-steam text-light me-2"></i><?= __('steam_import_title') ?></h5>
                    <p class="small text-secondary mb-4">
                        <?= __('steam_import_desc') ?>
                    </p>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="/steam_login" class="btn btn-primary fw-bold rounded-pill px-4 shadow-sm">
                            <i class="fas fa-cloud-download-alt me-2"></i><?= __('steam_import_btn') ?>
                        </a>

                        <a href="/update_steam_playtime" class="btn btn-dark fw-bold rounded-pill px-4 shadow-sm">
                            <i class="fas fa-stopwatch me-2"></i><?= __('steam_update_btn') ?>
                        </a>
                    </div>
                </div>
            </div>
            <!-- STEAM IMPORT -->

            <div class="card shadow-sm border-0 mb-4 rounded-4 bg-body">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3 fw-bold"><?= __('profile_share_title') ?></h5>
                    <p class="small text-secondary mb-3">
                        <?= __('profile_share_desc') ?>
                    </p>

                    <div class="input-group">
                        <?php
                        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                        $host = $_SERVER['HTTP_HOST'];

                        $shareLink = "$protocol://$host/index.php?action=share&id=" . $_SESSION['user_id'];
                        ?>
                        <input type="text" id="shareLinkInput" class="form-control rounded-start-3" value="<?= $shareLink ?>" readonly>
                        <button class="btn btn-primary rounded-end-3" onclick="copyShareLink()">
                            <i class="material-icons icon-sm align-middle">content_copy</i> <?= __('profile_share_copy') ?>
                        </button>
                    </div>
                    <div id="copyFeedback" class="form-text text-success mt-2" style="display:none;">
                        <i class="fas fa-check-circle me-1"></i><?= __('profile_share_link_copied') ?>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 border-start border-danger border-4 rounded-4 bg-body">
                <div class="card-body p-4">
                    <h6 class="text-danger fw-bold mb-2">
                        <i class="material-icons icon-md me-2 align-middle">warning</i><?= __('profile_danger_title') ?>
                    </h6>
                    <p class="small text-secondary mb-3"><?= __('profile_danger_text') ?></p>

                    <button class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="if(confirm('<?= __('profile_delete_confirm') ?>')) document.getElementById('deleteForm').submit();">
                        <?= __('profile_btn_delete_account') ?>
                    </button>

                    <form id="deleteForm" action="/delete_account" method="POST" class="d-none">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="confirm_delete" value="yes">
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<?php if (isset($_GET['importing']) && $_GET['importing'] === 'steam'): ?>
    <div class="modal fade" id="steamSyncModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-body p-5 text-center">
                    <h4 class="mb-4 fw-bold text-info">
                        <i class="fas fa-sync fa-spin me-2"></i><?= __('steam_sync') ?>
                    </h4>
                    <p class="text-secondary mb-4" id="steamSyncStatus"><?= __('steam_sync_in_progress') ?></p>

                    <div class="progress mb-3 rounded-pill" style="height: 25px;">
                        <div id="steamProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-info fw-bold" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>

                    <div class="alert alert-danger mt-4 mb-0 py-2 small" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?= __('steam_warning_close') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<div class="modal fade" id="psnSyncModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-body p-5 text-center">
                <h4 class="mb-4 fw-bold text-primary">
                    <i class="fas fa-sync fa-spin me-2"></i><?= __('psn_sync_title') ?>
                </h4>
                <p class="text-secondary mb-4" id="psnSyncStatus"><?= __('psn_sync_status') ?></p>

                <div class="progress mb-3 rounded-pill" style="height: 25px;">
                    <div id="psnProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary fw-bold" role="progressbar" style="width: 100%;"><?= __('psn_sync_in_progress') ?></div>
                </div>

                <div class="alert alert-danger mt-4 mb-0 py-2 small" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= __('psn_warning_close') ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/profile.js"></script>