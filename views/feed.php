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
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4">
            <?php foreach ($activities as $act): ?>
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden"
                        data-type="<?= $act['type'] ?>"
                        data-id="<?= $act['ref_id'] ?>">

                        <div class="card-header bg-transparent border-0 p-3 d-flex align-items-center gap-2">
                            <?php if (!empty($act['avatar_url'])): ?>
                                <img src="/<?= $act['avatar_url'] ?>"
                                    class="rounded-circle border shadow-sm object-fit-cover"
                                    width="36" height="36"
                                    alt="<?= htmlspecialchars($act['username']) ?>">
                            <?php else: ?>
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm flex-shrink-0"
                                    style="width: 36px; height: 36px; font-size: 14px; user-select: none;">
                                    <?= strtoupper(substr($act['username'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex flex-column lh-1">
                                <span class="fw-bold small mb-1"><?= htmlspecialchars($act['username']) ?></span>
                                <small class="text-muted" style="font-size: 0.75rem;">
                                    <?= date('d/m/Y', strtotime($act['time_posted'])) ?>
                                </small>
                            </div>
                        </div>

                        <div class="position-relative ratio ratio-16x9 bg-light">
                            <img src="<?= $act['image'] ?? '/assets/images/no-cover.jpg' ?>"
                                class="object-fit-cover w-100 h-100"
                                alt="<?= htmlspecialchars($act['title']) ?>">
                        </div>

                        <div class="card-body p-3">
                            <h6 class="card-title text-truncate fw-bold mb-2" title="<?= htmlspecialchars($act['title']) ?>">
                                <?= htmlspecialchars($act['title']) ?>
                            </h6>
                            
                            <?php if(!empty($act['platform'])): ?>
                                <span class="badge bg-secondary-subtle text-secondary rounded-pill fw-normal border border-secondary-subtle">
                                    <?= htmlspecialchars($act['platform']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="card-footer bg-transparent border-top p-2 mt-auto">
                            <div class="d-flex justify-content-between align-items-center">

                                <div class="reactions-count small text-muted d-flex align-items-center ps-2" id="rc-<?= $act['type'] ?>-<?= $act['ref_id'] ?>">
                                    <?php
                                    $hasReacted = false;
                                    foreach ($act['reactions'] as $r) {
                                        if ($r['user_has_reacted']) $hasReacted = $r['reaction_type'];

                                        if ($r['count'] > 0 && isset($reactionTypes[$r['reaction_type']])) {
                                            echo '<div class="d-flex align-items-center me-3" title="' . $reactionTypes[$r['reaction_type']]['label'] . '">';
                                            echo '<span class="me-1 fw-bold text-primary">' . $r['count'] . '</span>';
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
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="assets/js/feed.js"></script>