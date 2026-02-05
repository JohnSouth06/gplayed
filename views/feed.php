<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-light mb-0"><?= __('feed_title') ?></h2>
        </div>
    </div>

    <?php if (empty($activities)): ?>
        <div class="text-center py-5 text-muted bg-body rounded-4 border border-dashed">
            <i class="material-icons-outlined opacity-25 mb-3" style="font-size: 3em;">group_off</i>
            <p class="mb-2">Votre flux est vide.</p>
            <a href="/community" class="btn btn-primary rounded-pill btn-sm">Suivre des joueurs</a>
        </div>
    <?php else: ?>
        <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3">
            <?php foreach ($activities as $act): ?>
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative game-card-hover"
                        data-type="<?= $act['type'] ?>"
                        data-id="<?= $act['ref_id'] ?>">

                        <div class="position-relative ratio ratio-16x9 bg-light">
                            <img src="<?= $act['image'] ?? '/assets/images/no-cover.jpg' ?>"
                                class="object-fit-cover w-100 h-100"
                                alt="<?= htmlspecialchars($act['title']) ?>">

                            <div class="position-absolute top-0 end-0 m-2">
                                <?php if ($act['type'] == 'new_game'): ?>
                                    <span class="badge bg-primary bg-opacity-75 backdrop-blur shadow-sm rounded-pill">
                                        <i class="material-icons icon-xs align-middle">add</i>
                                    </span>
                                <?php elseif ($act['type'] == 'progress'): ?>
                                    <span class="badge bg-success bg-opacity-75 backdrop-blur shadow-sm rounded-pill">
                                        <i class="material-icons icon-xs align-middle">trending_up</i>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="position-absolute bottom-0 start-0 m-2">
                                <?php if (!empty($act['avatar_url'])): ?>
                                    <img src="/<?= $act['avatar_url'] ?>"
                                        class="rounded-circle border border-2 border-white shadow-sm"
                                        width="32" height="32"
                                        title="<?= htmlspecialchars($act['username']) ?>"
                                        data-bs-toggle="tooltip">
                                <?php else: ?>
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow border border-2 border-white flex-shrink-0"
                                        style="width: 32px; height: 32px; font-size: 14px; user-select: none;"
                                        title="<?= htmlspecialchars($act['username']) ?>"
                                        data-bs-toggle="tooltip">
                                        <?= strtoupper(substr($act['username'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card-body p-3 d-flex flex-column">
                            <div class="mb-2">
                                <h6 class="card-title text-truncate fw-bold mb-0" title="<?= htmlspecialchars($act['title']) ?>">
                                    <?= htmlspecialchars($act['title']) ?>
                                </h6>
                                <small class="text-secondary" style="font-size: 0.75rem;">
                                    <?= htmlspecialchars($act['username']) ?> • <span class="text-muted"><?= date('d/m', strtotime($act['time_posted'])) ?></span>
                                </small>
                            </div>

                            <?php if ($act['type'] == 'new_game' && $act['extra_info'] > 0): ?>
                                <div class="mb-3">
                                    <div class="d-flex align-items-center text-warning small">
                                        <i class="material-icons icon-sm me-1">star</i>
                                        <strong><?= $act['extra_info'] ?></strong>/10
                                    </div>
                                </div>
                            <?php elseif ($act['type'] == 'progress'): ?>
                                <div class="mb-3">
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $act['extra_info'] ?>%"></div>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.7rem;">Progression : <?= $act['extra_info'] ?>%</small>
                                </div>
                            <?php else: ?>
                                <div class="mb-3"></div>
                            <?php endif; ?>

                            <div class="mt-auto pt-2 border-top">
                                <div class="d-flex justify-content-between align-items-center">

                                    <div class="reactions-count small text-muted d-flex align-items-center" id="rc-<?= $act['type'] ?>-<?= $act['ref_id'] ?>">
                                        <?php
                                        $hasReacted = false;
                                        foreach ($act['reactions'] as $r) {
                                            if ($r['user_has_reacted']) $hasReacted = $r['reaction_type'];

                                            // Affichage uniquement si > 0
                                            if ($r['count'] > 0 && isset($reactionTypes[$r['reaction_type']])) {
                                                echo '<div class="d-flex align-items-center me-3" title="' . $reactionTypes[$r['reaction_type']]['label'] . '">';
                                                // 1. Le compteur
                                                echo '<span class="me-1 fw-bold text-primary">' . $r['count'] . '</span>';
                                                // 2. L'icône
                                                echo '<span>' . $reactionTypes[$r['reaction_type']]['icon'] . '</span>';
                                                echo '</div>';
                                            }
                                        }
                                        ?>
                                    </div>

                                    <div class="dropdown dropup">
                                        <button class="btn btn-sm btn-light rounded-circle shadow-sm p-0 d-flex align-items-center justify-content-center"
                                            style="width: 32px; height: 32px;"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="material-icons icon-sm text-secondary">
                                                <?= $hasReacted ? 'favorite' : 'add_reaction' ?>
                                            </i>
                                        </button>
                                        <ul class="dropdown-menu border-0 shadow-lg rounded-4 p-2 text-center" style="min-width: 180px;">
                                            <div class="d-flex justify-content-between gap-1">
                                                <?php foreach ($reactionTypes as $key => $rType): ?>
                                                    <button onclick="toggleReaction('<?= $act['type'] ?>', <?= $act['ref_id'] ?>, '<?= $key ?>', this)"
                                                        class="btn btn-ghost p-1 rounded-3 fs-5 reaction-btn <?= ($hasReacted === $key) ? 'bg-primary-subtle' : '' ?>"
                                                        title="<?= $rType['label'] ?>">
                                                        <?= $rType['icon'] ?>
                                                    </button>
                                                <?php endforeach; ?>
                                            </div>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="assets/js/feed.js"></script>