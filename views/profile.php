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
                                <input type="file" name="avatar_file" class="form-control rounded-3 mb-2">
                                <input type="url" name="avatar_url" class="form-control rounded-3" placeholder="https://..." value="<?= htmlspecialchars($user['avatar_url']) ?>">
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
                                        <input type="file" name="json_file" class="form-control rounded-3" accept=".json" required>
                                    </div>
                                    <div class="mt-2">
                                        <button type="submit" class="btn btn-light w-100 rounded-3 text-secondary border">
                                            <i class="material-icons icon-sm me-2 align-middle">publish</i><?= __('profile_import_btn') ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
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