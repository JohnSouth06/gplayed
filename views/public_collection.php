<?php
// On récupère si l'utilisateur connecté suit déjà ce profil
$isFollowing = false;
if (isset($_SESSION['user_id']) && isset($owner['id'])) {
    require_once dirname(__DIR__) . '/models/User.php';
    $uModel = new User($db); // $db est disponible via layout -> index
    $followingIds = $uModel->getFollowedIds($_SESSION['user_id']);
    $isFollowing = in_array($owner['id'], $followingIds);
}
?>

<div class="row mb-5 align-items-center">
    <div class="col-md-8 d-flex align-items-center gap-3">
        <?php if (!empty($owner['avatar_url'])): ?>
            <img src="<?= $owner['avatar_url'] ?>" class="rounded-circle shadow object-fit-cover border border-4 border-white" style="width: 100px; height: 100px;">
        <?php else: ?>
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow border border-4 border-white" style="width: 100px; height: 100px; font-size: 2.5rem; font-weight:bold;">
                <?= strtoupper(substr($owner['username'], 0, 1)) ?>
            </div>
        <?php endif; ?>

        <div>
            <h2 class="fw-bold mb-0"><?= __('public_collection_title') ?> <?= htmlspecialchars($owner['username']) ?></h2>
            <p class="text-secondary mb-2"><?= count($games) ?> <?= __('public_collection_count') ?></p>

            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $owner['id']): ?>
                <?php if ($isFollowing): ?>
                    <a href="index.php?action=toggle_follow&id=<?= $owner['id'] ?>&do=unfollow" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="fas fa-check me-1"></i> <?= __('public_collection_following') ?>
                    </a>
                <?php else: ?>
                    <a href="index.php?action=toggle_follow&id=<?= $owner['id'] ?>&do=follow" class="btn btn-primary btn-sm rounded-pill px-3">
                        <i class="fas fa-user-plus me-1"></i> <?= __('public_collection_follow') ?>
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
            <p><?= __('public_collection_empty') ?></p>
        </div>
    <?php else: ?>
        <?php foreach ($games as $g): ?>
            <?php
            // 1. Configuration EXACTE traduite
            // On utilise les clés de traduction définies précédemment pour le dashboard
            $statusConfig = [
                'not_started' => ['label' => __('status_not_started'), 'icon' => '&#xe837;'], // watch_later
                'playing'     => ['label' => __('status_playing'),     'icon' => '&#xea5b;'], // sports_esports
                'finished'    => ['label' => __('status_finished'),    'icon' => '&#xe86c;'], // check_circle
                'completed'   => ['label' => __('status_completed'),   'icon' => '&#xea23;'], // emoji_events
                'dropped'     => ['label' => __('status_dropped'),     'icon' => '&#xe14b;'], // block
                'wishlist'    => ['label' => __('status_wishlist'),    'icon' => '&#xe8b1;']  // redeem
            ];

            // Valeur par défaut si le statut est inconnu
            $s = $statusConfig[$g['status']] ?? $statusConfig['playing'];

            // 2. Gestion des icônes Plateforme (Identique au JS)
            $plat = $g['platform'];
            $platIconHtml = '<i class="material-icons-outlined icon-sm me-1">&#xea5b;</i>'; // Par défaut

            // Détection SVG vs Material Icon standard
            if (stripos($plat, 'PS') !== false) {
                $platIconHtml = '<i class="svg-icon ps-icon me-1"></i>';
            } elseif (stripos($plat, 'Xbox') !== false) {
                $platIconHtml = '<i class="svg-icon xbox-icon me-1"></i>';
            } elseif (stripos($plat, 'Switch') !== false) {
                $platIconHtml = '<i class="svg-icon switch-icon me-1"></i>';
            } elseif (stripos($plat, 'PC') !== false || stripos($plat, 'Steam') !== false) {
                $platIconHtml = '<i class="svg-icon pc-icon me-1"></i>';
            }
            ?>

            <div class="col-sm-6 col-lg-4 col-xl-3">
                <div class="game-card-modern">

                    <div class="card-cover-container">
                        <?php if ($g['image_url']): ?>
                            <img src="<?= $g['image_url'] ?>" class="card-cover-img" loading="lazy" alt="<?= htmlspecialchars($g['title']) ?>">
                        <?php else: ?>
                            <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-body-tertiary">
                                <i class="material-icons-outlined icon-xl text-secondary opacity-25">&#xea5b;</i>
                            </div>
                        <?php endif; ?>

                        <span class="status-badge-float">
                            <i class="material-icons-outlined icon-sm me-1"><?= $s['icon'] ?></i><?= $s['label'] ?>
                        </span>
                    </div>

                    <div class="card-content-area">
                        <h5 class="game-title text-truncate" title="<?= htmlspecialchars($g['title']) ?>">
                            <?= htmlspecialchars($g['title']) ?>
                        </h5>

                        <div class="meta-badges">
                            <span class="meta-tag">
                                <?= $platIconHtml ?><?= htmlspecialchars($g['platform']) ?>
                            </span>

                            <?php if (!empty($g['user_rating'])): ?>
                                <span class="meta-tag text-warning bg-warning-subtle border-warning-subtle">
                                    <i class="material-icons-outlined icon-sm filled-icon me-1">&#xe838;</i><?= $g['user_rating'] ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($g['comment'])): ?>
                            <div class="mt-auto pt-3 border-top border-light-subtle">
                                <p class="small text-secondary mb-0 fst-italic text-truncate">
                                    <?= htmlspecialchars($g['comment']) ?>
                                </p>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>