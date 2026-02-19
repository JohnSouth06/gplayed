<link rel="stylesheet" href="assets/css/dashboard.css">

<?php
// On récupère le nombre de jeux prêtés
$totalLoaned = is_array($games) ? count($games) : 0;
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 gap-3 pt-2">
    <div>
        <h2 class="h2 dashboard-welcome mb-1 fw-light"><?= __('loaned_title_part1') ?> <span class="text-warning fw-bold"><?= __('loaned_title_part2') ?></span> 🤝</h2>
    </div>
    <div class="stat-widget">
        <i class="material-icons stat-icon text-warning">&#xe0e3;</i>
        <div class="stat-content">
            <span class="stat-label"><?= __('loaned_count_label') ?></span>
            <span class="stat-value text-warning animate-counter" data-target="<?= $totalLoaned ?>">0</span>
        </div>
    </div>
</div>

<?php if ($totalLoaned === 0): ?>
    <div class="col-12 text-center py-5">
        <div class="mb-3 text-secondary opacity-25"><i class="material-icons-outlined icon-xl">&#xe0e3;</i></div>
        <h5 class="text-secondary fw-bold"><?= __('loaned_empty_title') ?></h5>
        <p class="text-muted small"><?= __('loaned_empty_desc') ?></p>
        <a href="/" class="btn btn-outline-primary rounded-pill mt-3 px-4"><?= __('loaned_btn_back') ?></a>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($games as $g): ?>
            <?php 
            $img = !empty($g['image_url']) ? $g['image_url'] : '';
            $imagePlaceholder = '<div class="w-100 h-100 d-flex align-items-center justify-content-center bg-body-tertiary"><i class="material-icons-outlined icon-xl text-secondary opacity-25">&#xea5b;</i></div>';
            ?>
            <div class="col-12 col-md-6 col-xl-4 animate-in">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-body-tertiary overflow-hidden">
                    <div class="d-flex h-100">
                        <div style="width: 130px; min-height: 100%;" class="flex-shrink-0 bg-body-secondary position-relative">
                            <?php if ($img): ?>
                                <img src="<?= htmlspecialchars($img) ?>" class="w-100 h-100 object-fit-cover position-absolute top-0 start-0" alt="Cover">
                            <?php else: ?>
                                <?= $imagePlaceholder ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-body d-flex flex-column py-3 pe-3 ps-4">
                            <h6 class="fw-bold text-truncate mb-1" title="<?= htmlspecialchars($g['title']) ?>"><?= htmlspecialchars($g['title']) ?></h6>
                            <span class="badge bg-secondary bg-opacity-25 text-body border w-auto align-self-start mb-3"><i class="material-icons-outlined icon-sm align-middle me-1">&#xe53b;</i><?= htmlspecialchars($g['platform']) ?></span>
                            
                            <div class="mt-auto bg-body rounded-3 p-2 border border-warning border-opacity-25">
                                <div class="small text-muted mb-1 text-truncate" title="<?= htmlspecialchars($g['loaned_to']) ?>">
                                    <i class="material-icons-outlined icon-sm align-middle me-1 text-warning">&#xe7fd;</i><?= __('loaned_to') ?> <strong class="text-body"><?= htmlspecialchars($g['loaned_to']) ?></strong>
                                </div>
                                <div class="small text-muted">
                                    <i class="material-icons-outlined icon-sm align-middle me-1 text-warning">&#xe8df;</i><?= __('loaned_date') ?> <strong class="text-body"><?= date('d/m/Y', strtotime($g['loaned_date'])) ?></strong>
                                </div>
                            </div>

                            <a href="/?action=returnGame&id=<?= $g['id'] ?>" class="btn btn-outline-warning btn-sm mt-3 fw-bold rounded-pill w-100" onclick="return confirm('<?= __('loaned_confirm_return') ?>')">
                                <i class="material-icons-outlined icon-sm align-middle me-1">&#xe15a;</i> <?= __('loaned_btn_return') ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script src="assets/js/loaned.js"></script>