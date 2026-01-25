<?php
// On récupère si l'utilisateur connecté suit déjà ce profil
$isFollowing = false;
if(isset($_SESSION['user_id']) && isset($owner['id'])) {
    require_once dirname(__DIR__) . '/models/User.php';
    $uModel = new User($db); // $db est disponible via layout -> index
    $followingIds = $uModel->getFollowedIds($_SESSION['user_id']);
    $isFollowing = in_array($owner['id'], $followingIds);
}
?>

<div class="row mb-5 align-items-center">
    <div class="col-md-8 d-flex align-items-center gap-3">
        <?php if(!empty($owner['avatar_url'])): ?>
            <img src="<?= $owner['avatar_url'] ?>" class="rounded-circle shadow object-fit-cover border border-4 border-white" style="width: 100px; height: 100px;">
        <?php else: ?>
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow border border-4 border-white" style="width: 100px; height: 100px; font-size: 2.5rem; font-weight:bold;">
                <?= strtoupper(substr($owner['username'], 0, 1)) ?>
            </div>
        <?php endif; ?>
        
        <div>
            <h2 class="fw-bold mb-0">Collection de <?= htmlspecialchars($owner['username']) ?></h2>
            <p class="text-secondary mb-2"><?= count($games) ?> jeux dans la bibliothèque</p>
            
            <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $owner['id']): ?>
                <?php if ($isFollowing): ?>
                    <a href="index.php?action=toggle_follow&id=<?= $owner['id'] ?>&do=unfollow" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="fas fa-check me-1"></i> Abonné
                    </a>
                <?php else: ?>
                    <a href="index.php?action=toggle_follow&id=<?= $owner['id'] ?>&do=follow" class="btn btn-primary btn-sm rounded-pill px-3">
                        <i class="fas fa-user-plus me-1"></i> Suivre
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <?php if (empty($games)): ?>
        <div class="col-12 text-center py-5 text-muted">
            <i class="fas fa-ghost fa-3x mb-3 opacity-25"></i>
            <p>Ce joueur n'a pas encore ajouté de jeux.</p>
        </div>
    <?php else: ?>
        <?php foreach ($games as $g): ?>
            <div class="col-sm-6 col-lg-4 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden bg-body-tertiary">
                    <div class="position-relative" style="padding-top: 56.25%;">
                        <?php if($g['image_url']): ?>
                            <img src="<?= $g['image_url'] ?>" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover">
                        <?php else: ?>
                            <div class="position-absolute top-0 start-0 w-100 h-100 bg-secondary-subtle d-flex align-items-center justify-content-center">
                                <i class="fas fa-gamepad fa-2x text-secondary opacity-50"></i>
                            </div>
                        <?php endif; ?>
                        
                        <?php 
                            $statusColors = ['playing'=>'bg-info','finished'=>'bg-success','completed'=>'bg-warning text-dark','dropped'=>'bg-danger','wishlist'=>'bg-primary'];
                            $sLabel = ['playing'=>'En cours','finished'=>'Terminé','completed'=>'Platiné','dropped'=>'Abandon','wishlist'=>'Souhait'];
                            $bg = $statusColors[$g['status']] ?? 'bg-secondary';
                        ?>
                        <span class="position-absolute top-0 end-0 m-2 badge <?= $bg ?> rounded-pill shadow-sm">
                            <?= $sLabel[$g['status']] ?? $g['status'] ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <h6 class="fw-bold text-truncate mb-1"><?= htmlspecialchars($g['title']) ?></h6>
                        <div class="d-flex justify-content-between align-items-center small">
                            <span class="badge bg-body border text-body fw-normal"><?= htmlspecialchars($g['platform']) ?></span>
                            <?php if($g['user_rating']): ?>
                                <span class="text-warning fw-bold"><i class="fas fa-star me-1"></i><?= $g['user_rating'] ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if($g['comment']): ?>
                            <p class="mt-2 mb-0 small text-secondary fst-italic text-truncate">"<?= htmlspecialchars($g['comment']) ?>"</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>