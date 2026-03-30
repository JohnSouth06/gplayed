<?php

if (!function_exists('getNeonColorPHP')) {
    function getNeonColorPHP($rgbString, $opacity = 1) {
        if (empty($rgbString) || $rgbString === 'null') return "rgba(255, 255, 255, {$opacity})";

        preg_match_all('/\d+/', $rgbString, $matches);
        if (empty($matches[0]) || count($matches[0]) < 3) return "rgba(255, 255, 255, {$opacity})";
        
        $r = (int)$matches[0][0] / 255;
        $g = (int)$matches[0][1] / 255;
        $b = (int)$matches[0][2] / 255;
        
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        
        if ($max == $min) {
            $h = $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            if ($max === $r) {
                $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
            } elseif ($max === $g) {
                $h = ($b - $r) / $d + 2;
            } else {
                $h = ($r - $g) / $d + 4;
            }
            $h /= 6;
        }
        
        if ($s > 0.1) $s = max($s, 0.85);
        $l = max(0.50, min(0.75, $l));
        
        $h = round($h * 360);
        $s = round($s * 100);
        $l = round($l * 100);
        
        return "hsla({$h}, {$s}%, {$l}%, {$opacity})";
    }
}

$img = !empty($g['image_url']) ? $g['image_url'] : '';
$imagePlaceholder = '<div class="position-absolute top-0 w-100 h-100 d-flex align-items-center justify-content-center bg-body-tertiary"><i class="material-icons-outlined icon-xl text-secondary opacity-25">&#xea5b;</i></div>';

$formatIcon = (isset($g['format']) && $g['format'] === 'physical') 
    ? '<i class="material-icons-outlined icon-sm text-secondary" title="Physique">&#xe1a1;</i>' 
    : '<i class="material-icons-outlined icon-sm text-secondary" title="Dématérialisé">&#xe3dd;</i>';

$platIconHtml = '<i class="material-icons-outlined icon-sm me-1">&#xea5b;</i>';
if (!empty($g['platform'])) {
    $platL = strtolower($g['platform']);
    
    if (strpos($platL, 'ps') !== false || strpos($platL, 'playstation') !== false || strpos($platL, 'vita') !== false || strpos($platL, 'psp') !== false) {
        $platIconHtml = '<i class="svg-icon ps-icon me-1"></i>';
    } elseif (strpos($platL, 'xbox') !== false) {
        $platIconHtml = '<i class="svg-icon xbox-icon me-1"></i>';
    } elseif (strpos($platL, 'switch') !== false || strpos($platL, 'nintendo') !== false || strpos($platL, 'wii') !== false || strpos($platL, 'gamecube') !== false || strpos($platL, 'game boy') !== false || strpos($platL, 'nes') !== false || strpos($platL, 'ds') !== false) {
        $platIconHtml = '<i class="svg-icon switch-icon me-1"></i>';
    } elseif (strpos($platL, 'pc') !== false || strpos($platL, 'mac') !== false || strpos($platL, 'linux') !== false) {
        $platIconHtml = '<i class="svg-icon pc-icon me-1"></i>';
    } elseif (strpos($platL, 'ios') !== false || strpos($platL, 'android') !== false) {
        $platIconHtml = '<i class="material-icons-outlined icon-sm me-1" title="Mobile">&#xe32c;</i>';
    }
}

$domColor = isset($g['dominant_color']) ? $g['dominant_color'] : '';
$shadowColor = getNeonColorPHP($domColor, 0.4);
$borderColor = getNeonColorPHP($domColor, 0.5);
?>

<div class="col-6 col-sm-6 col-lg-4 col-xl-3 animate-in">
    <div class="game-card-modern d-flex flex-column h-100" 
         style="border-color: rgba(0,0,0,0.05);"
         onmouseover="this.style.boxShadow='0 25px 60px -12px <?= $shadowColor ?>'; this.style.borderColor='<?= $borderColor ?>'"
         onmouseout="this.style.boxShadow=''; this.style.borderColor='rgba(0,0,0,0.05)'">
        
        <div class="card-cover-container flex-shrink-0">
            <?php if ($img): ?>
                <img src="<?= htmlspecialchars($img) ?>" class="card-cover-img" loading="lazy">
            <?php else: ?>
                <?= $imagePlaceholder ?>
            <?php endif; ?>
            
            <span class="status-badge-float bg-warning text-dark">
                <i class="material-icons-outlined icon-sm me-1">&#xe0e3;</i><?= __('status_loaned') ?? 'Prêté' ?>
            </span>
        </div>
        
        <div class="card-content-area d-flex flex-column flex-grow-1 pb-3">
            <div class="d-flex justify-content-between align-items-start">
                <h6 class="game-title text-truncate" title="<?= htmlspecialchars($g['title']) ?>"><?= htmlspecialchars($g['title']) ?></h6>
                <?php if (!empty($g['user_rating']) && $g['user_rating'] > 0): ?>
                    <div class="fw-bold text-warning d-flex align-items-center small">
                        <i class="material-icons-outlined icon-sm filled-icon me-1">&#xe838;</i><?= htmlspecialchars($g['user_rating']) ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="meta-badges mb-2">
                <span class="meta-tag"><?= $platIconHtml ?><?= htmlspecialchars($g['platform']) ?></span>
                <span class="meta-tag bg-body-secondary border-0"><?= $formatIcon ?></span>
                
                <?php if (isset($g['trophies_summary']) && $g['trophies_summary']['total'] > 0): 
                    $t = $g['trophies_summary'];
                    $percent = round(($t['obtained'] / $t['total']) * 100);
                ?>
                    <span class="meta-tag text-warning bg-warning-subtle border-warning-subtle" title="Progression des trophées PSN">
                        <i class="material-icons-outlined icon-sm me-1">&#xea23;</i><?= $percent ?>%
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="mt-auto bg-body rounded-3 p-2 border border-warning border-opacity-25 mb-3">
                <div class="small text-muted mb-1 text-truncate" title="<?= htmlspecialchars($g['loaned_to']) ?>">
                    <i class="material-icons-outlined icon-sm align-middle me-1 text-warning">&#xe7fd;</i><?= __('loaned_to') ?? 'À :' ?> <strong class="text-body"><?= htmlspecialchars($g['loaned_to']) ?></strong>
                </div>
                <div class="small text-muted">
                    <i class="material-icons-outlined icon-sm align-middle me-1 text-warning">&#xe8df;</i><?= __('loaned_date') ?? 'Le :' ?> <strong class="text-body"><?= date('d/m/Y', strtotime($g['loaned_date'])) ?></strong>
                </div>
            </div>

            <a href="/?action=returnGame&id=<?= $g['id'] ?>" class="btn btn-warning rounded-pill px-4 fw-bold" onclick="return confirm('<?= __('loaned_confirm_return') ?>')">
                <i class="material-icons-outlined icon-sm align-middle me-1">&#xe15a;</i> <?= __('loaned_btn_return') ?? 'Rendu' ?>
            </a>
        </div>

    </div>
</div>