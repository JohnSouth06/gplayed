<div class="row mb-4 align-items-center">
    <div class="col">
        <h2 class="fw-light text-tertiary mb-1">Communauté</h2>
    </div>
</div>

<div class="row g-4">
    <?php if (empty($users)): ?>
        <div class="col-12 text-center py-5 text-muted">
            <i class="material-icons-outlined opacity-25 mb-3" style="font-size: 4em;">&#xe510;</i>
            <p>Il n'y a pas encore d'autres membres inscrits.</p>
        </div>
    <?php else: ?>
        <?php foreach ($users as $u): ?>
            <?php 
                $isFollowing = in_array($u['id'], $following); 
                // Avatar par défaut ou perso
                $avatar = !empty($u['avatar_url']) ? $u['avatar_url'] : null;
                $initial = strtoupper(substr($u['username'], 0, 1));
            ?>
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 text-center bg-body position-relative overflow-hidden group-hover-card">
                    <div class="card-body p-4 d-flex flex-column align-items-center">
                        <div class="mb-3">
                            <?php if($avatar): ?>
                                <img src="<?= $avatar ?>" class="rounded-circle object-fit-cover shadow-sm" style="width: 80px; height: 80px;">
                            <?php else: ?>
                                <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px; font-size: 2rem; font-weight:bold;">
                                    <?= $initial ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <h5 class="fw-bold mb-1 text-truncate w-100"><?= htmlspecialchars($u['username']) ?></h5>
                        <p class="small text-secondary mb-3">Membre depuis <?= date('Y', strtotime($u['created_at'])) ?></p>
                        
                        <div class="mt-auto w-100 d-flex gap-2 justify-content-center">
                            <a href="index.php?action=share&user=<?= urlencode($u['username']) ?>" class="btn btn-light rounded-pill btn-sm px-3">
                                <i class="material-icons icon-sm me-1">&#xe8f4;</i> Voir
                            </a>
                            
                            <?php if ($isFollowing): ?>
                                <a href="index.php?action=toggle_follow&id=<?= $u['id'] ?>&do=unfollow" class="btn btn-outline-secondary rounded-pill btn-sm px-3">
                                    <i class="material-icons icon-sm me-1">&#xef66;</i> Suivi
                                </a>
                            <?php else: ?>
                                <a href="index.php?action=toggle_follow&id=<?= $u['id'] ?>&do=follow" class="btn btn-primary rounded-pill btn-sm px-3">
                                    <i class="material-icons icon-sm me-1">&#xe7fe;</i> Suivre
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>