<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="mb-4 text-center">
            <h2 class="fw-light">Fil d'actualité</h2>
        </div>

        <?php if (empty($activities)): ?>
            <div class="text-center py-5 text-muted bg-body rounded-4 shadow-sm">
                <i class="material-icons-outlined opacity-25 mb-3" style="font-size: 4em;">&#xe2c8;</i>
                <p>C'est bien calme ici...</p>
                <a href="index.php?action=community" class="btn btn-primary rounded-pill btn-sm">Suivre des membres</a>
            </div>
        <?php else: ?>
            <?php foreach ($activities as $act): ?>
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-body overflow-hidden">
                    <div class="card-header bg-transparent border-0 p-3 d-flex align-items-center">
                        <?php if($act['avatar_url']): ?>
                            <img src="<?= $act['avatar_url'] ?>" class="rounded-circle object-fit-cover me-2" width="40" height="40">
                        <?php else: ?>
                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px; font-weight:bold;">
                                <?= strtoupper(substr($act['username'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div>
                            <div class="fw-bold"><?= htmlspecialchars($act['username']) ?></div>
                            <div class="small text-secondary">
                                <?= date('d/m H:i', strtotime($act['time_posted'])) ?> • 
                                <?php if($act['type'] == 'new_game'): ?>
                                    <span class="text-primary"><i class="material-icons icon-sm me-1">&#xe147;</i>A ajouté un jeu</span>
                                <?php else: ?>
                                    <span class="text-success"><i class="material-icons icon-sm me-1">&#xe8e5;</i>A progressé</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if($act['image']): ?>
                        <div class="position-relative">
                            <img src="<?= $act['image'] ?>" class="w-100 object-fit-cover" style="height: 250px;">
                            <?php if($act['type'] == 'new_game' && $act['extra_info'] > 0): ?>
                                <span class="position-absolute top-0 end-0 m-3 rounded-pill badge bg-warning text-dark shadow">
                                    <i class="material-icons icon-sm me-1">&#xe838;</i><?= $act['extra_info'] ?>/10
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="card-body">
                        <h5 class="fw-bold mb-2"><?= htmlspecialchars($act['title']) ?></h5>
                        
                        <?php if($act['type'] == 'progress' && !empty($act['extra_info'])): ?>
                            <div class="p-3 bg-body-tertiary rounded-3 border-start border-4 border-success mb-3">
                                <i class="material-icons text-success opacity-25 me-2 icon-md">&#xe244;</i>
                                <?= htmlspecialchars($act['extra_info']) ?>
                            </div>
                        <?php endif; ?>

                        <div class="mt-3 pt-3 border-top">
                            <form action="index.php?action=add_comment" method="POST" class="d-flex gap-2">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="game_id" value="<?= $act['ref_id'] ?>">
                                <input type="text" name="content" class="form-control rounded-pill bg-body-tertiary border-0 px-3" placeholder="Écrire un commentaire..." required>
                                <button type="submit" class="btn btn-primary rounded-circle shadow-sm" style="width: 38px; height: 38px;">
                                    <i class="material-icons icon-sm">&#xe163;</i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>