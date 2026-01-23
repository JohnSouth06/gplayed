<div class="row justify-content-center">
    <div class="col-md-8">
        <h2 class="mb-4 fw-light">Mon Profil</h2>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 text-center h-100 bg-body rounded-4">
                    <div class="card-body">
                        <div class="mb-3 mt-3">
                            <?php if(!empty($user['avatar_url'])): ?>
                                <img src="<?= $user['avatar_url'] ?>" class="rounded-circle shadow-sm object-fit-cover" style="width: 120px; height: 120px; border: 4px solid var(--bs-body-bg);">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto shadow-sm" style="width: 120px; height: 120px; font-size: 3rem;">
                                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h4 class="card-title fw-bold"><?= htmlspecialchars($user['username']) ?></h4>
                        <p class="text-secondary small mb-1"><?= htmlspecialchars($user['email']) ?></p>
                        <p class="text-secondary small">Membre depuis le <?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0 mb-4 rounded-4 bg-body">
                    <div class="card-header bg-transparent border-bottom pt-3">
                        <h6 class="mb-0 fw-bold">Modifier mes informations</h6>
                    </div>
                    <div class="card-body">
                        <form action="index.php?action=update_profile" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-secondary">Adresse Email</label>
                                <input type="email" name="email" class="form-control rounded-3" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-secondary">Changer d'avatar</label>
                                <input type="file" name="avatar" class="form-control rounded-3" accept="image/*">
                            </div>
                            
                            <hr class="my-4 opacity-10">
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-secondary">Nouveau mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body-tertiary border-end-0"><i class="material-icons icon-sm">&#xe897;</i></span>
                                    <input type="password" name="new_password" class="form-control border-start-0" placeholder="Laisser vide pour ne pas changer">
                                </div>
                                <div class="form-text small mt-1">Min 10 cars, Maj, Min, Chiffre, Spécial.</div>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="material-icons icon-sm me-2">&#xe161;</i>Enregistrer</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4 rounded-4 bg-body">
                    <div class="card-header bg-transparent border-bottom pt-3">
                        <h6 class="mb-0 fw-bold"><i class="material-icons icon-md me-2">&#xe1db;</i>Gestion des données</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6 border-end border-light-subtle">
                                <label class="form-label small fw-bold text-secondary">Exporter ma collection</label>
                                <p class="small text-muted mb-2">Téléchargez un fichier JSON contenant tous vos jeux.</p>
                                <a href="index.php?action=export_json" class="btn btn-outline-primary btn-sm w-100 rounded-3">
                                    <i class="material-icons icon-sm me-2">&#xf090;</i>Exporter JSON
                                </a>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary">Importer une collection</label>
                                <p class="small text-muted mb-2">Ajoutez des jeux depuis un fichier JSON.</p>
                                <form action="index.php?action=import_json" method="POST" enctype="multipart/form-data">
                                    <div class="input-group input-group-sm mb-2">
                                        <input type="file" name="json_file" class="form-control rounded-start-3" accept=".json" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm w-100 rounded-3">
                                        <i class="material-icons icon-sm me-2">&#xe2c6;</i>Importer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 border-start border-danger border-4 rounded-4 bg-body">
                    <div class="card-body">
                        <h6 class="text-danger fw-bold"><i class="material-icons icon-md me-2">&#xe002;</i>Zone de danger</h6>
                        <p class="small text-secondary mb-3">La suppression du compte est irréversible.</p>
                        <button class="btn btn-outline-danger btn-sm rounded-pill" onclick="if(confirm('Êtes-vous ABSOLUMENT sûr ?')) document.getElementById('deleteForm').submit();">
                            Supprimer mon compte
                        </button>
                        <form id="deleteForm" action="index.php?action=delete_account" method="POST" class="d-none">
                            <input type="hidden" name="confirm_delete" value="yes">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>