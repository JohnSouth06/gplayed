<?php
$forceLoader = false;
if (isset($_SESSION['force_loader'])) {
    $forceLoader = true;
    unset($_SESSION['force_loader']); 
}
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="favicon-light.png" type="image/png">
    <title>Ma Collection de Jeux</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/style.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-body-tertiary <?= $forceLoader ? 'loading' : '' ?>">

<!-- LOADER -->
<div id="app-loader">
    <div class="loader-content">
        <div class="loader-logo-svg">
            <svg id="logo" xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 240 150">
                <defs><style>.st1{fill:#00f265;}</style></defs>
                <path id="green" class="st1" d="M36.97,128.63c1.63,0,1.63.88,1.63,2.65,0,10.92-7.88,18.72-19.17,18.72S0,142.15,0,131.28s8.14-18.72,19.43-18.72c8.4,0,15.65,4.89,17.96,11.81.37,1.09-.26,1.77-1.37,1.77h-8.09c-.84,0-1.42-.36-1.89-1.04-1.47-2.08-3.73-3.38-6.56-3.38-4.99,0-8.56,4-8.56,9.52s3.57,9.52,8.56,9.52c3.36,0,6.2-1.72,7.3-4.26h-5.72c-1,0-1.58-.57-1.58-1.56v-4.73c0-.99.58-1.56,1.58-1.56h15.91ZM159.49,45.95c-2.36,0-4.28,1.9-4.28,4.24s1.92,4.24,4.28,4.24,4.28-1.9,4.28-4.24-1.92-4.24-4.28-4.24ZM159.49,26.4c-2.36,0-4.28,1.9-4.28,4.24s1.92,4.24,4.28,4.24,4.28-1.9,4.28-4.24-1.92-4.24-4.28-4.24ZM169.36,36.17c-2.36,0-4.28,1.9-4.28,4.24s1.92,4.24,4.28,4.24,4.28-1.9,4.28-4.24-1.92-4.24-4.28-4.24ZM149.62,36.17c-2.36,0-4.28,1.9-4.28,4.24s1.92,4.24,4.28,4.24,4.28-1.9,4.28-4.24-1.92-4.24-4.28-4.24Z"/>
                <path id="white" class="svg-adaptive-fill" d="M59.87,113.08c7.72,0,13.34,5.41,13.34,12.9s-5.62,12.85-13.34,12.85h-7.09v9.1c0,.99-.58,1.56-1.58,1.56h-7.56c-1,0-1.58-.57-1.58-1.56v-33.28c0-.99.58-1.56,1.58-1.56h16.23ZM58.66,129.87c2.1,0,3.62-1.72,3.62-3.74,0-2.13-1.52-3.69-3.62-3.69h-5.88v7.44h5.88ZM100.57,140.12c1,0,1.58.57,1.58,1.56v6.24c0,.99-.58,1.56-1.58,1.56h-22.06c-1,0-1.58-.57-1.58-1.56v-33.28c0-.99.58-1.56,1.58-1.56h7.56c1,0,1.58.57,1.58,1.56v25.48h12.92ZM143.74,147.71c.37,1.09-.11,1.77-1.26,1.77h-8.3c-.89,0-1.52-.42-1.79-1.3l-1.47-4.78h-12.08l-1.47,4.78c-.26.88-.89,1.3-1.79,1.3h-8.3c-1.16,0-1.63-.68-1.26-1.77l11.87-33.39c.32-.88.95-1.25,1.84-1.25h10.29c.89,0,1.52.36,1.84,1.25l11.87,33.39ZM120.94,136.48h7.88l-3.94-13.05-3.94,13.05ZM172.15,113.08c1.26,0,1.68.83,1.05,1.87l-12.76,20.59v12.38c0,.99-.58,1.56-1.58,1.56h-7.56c-1,0-1.58-.57-1.58-1.56v-12.01l-12.97-20.96c-.63-1.04-.21-1.87,1.05-1.87h8.67c.84,0,1.42.36,1.84,1.09l6.67,12.48,6.67-12.48c.42-.73,1-1.09,1.84-1.09h8.67ZM187.12,122.44v4.32h13.44c1,0,1.58.57,1.58,1.56v5.82c0,.99-.58,1.56-1.58,1.56h-13.44v4.42h14.49c1,0,1.58.57,1.58,1.56v6.24c0,.99-.58,1.56-1.58,1.56h-23.63c-1,0-1.58-.57-1.58-1.56v-33.28c0-.99.58-1.56,1.58-1.56h23.63c1,0,1.58.57,1.58,1.56v6.24c0,.99-.58,1.56-1.58,1.56h-14.49ZM221.09,113.08c10.98,0,18.91,7.64,18.91,18.2s-7.93,18.2-18.91,18.2h-13.08c-1,0-1.58-.57-1.58-1.56v-33.28c0-.99.58-1.56,1.58-1.56h13.08ZM220.99,140.12c4.67,0,8.09-3.74,8.09-8.89s-3.41-8.79-8.09-8.79h-3.83v17.68h3.83ZM159.49,0h-78.36C58.74,0,40.05,17.61,39.71,39.78c-.35,22.57,18.09,41.04,40.8,41.04,10.84,0,20.71-4.21,28.02-11.07.67-.62,1.06-1.49,1.06-2.4v-6.72h0v-6.85c0-1.08-.88-1.95-1.97-1.96l-11.89-.02c-1.09,0-1.98.87-1.98,1.96v5.74c0,1.2-.65,2.32-1.72,2.87-4.14,2.14-8.95,3.16-14.04,2.67-12.02-1.18-21.57-10.93-22.42-22.86-1.03-14.46,10.56-26.55,24.95-26.55.25,0,.5.01.75.02h31.48s0-.01-.01-.02h14.56s0,.01-.01.02c-3.34,4.26-5.85,9.19-7.27,14.56-.86,3.26-1.32,6.67-1.32,10.19,0,.02,0,.05,0,.07h0v46.85c0,1.08.88,1.96,1.97,1.96h11.85c1.09,0,1.97-.88,1.97-1.96v-15.03c7.25,5.59,16.44,8.82,26.39,8.49,22.11-.73,39.76-19.12,39.42-41.03C199.94,17.78,181.77,0,159.49,0ZM162.21,65.03c-15.92,1.67-29.27-11.55-27.58-27.31,1.23-11.48,10.55-20.72,22.15-21.93,15.92-1.67,29.27,11.55,27.58,27.31-1.23,11.48-10.55,20.72-22.15,21.93ZM77.55,54.42h5.92c.73,0,1.32-.58,1.32-1.3v-8.47h8.56c.17,0,.34-.03.49-.09.49-.19.83-.66.83-1.21v-5.87c0-.72-.59-1.3-1.32-1.3h-8.56v-8.47c0-.72-.59-1.3-1.32-1.3h-5.92c-.73,0-1.32.58-1.32,1.3v8.47h-8.56c-.73,0-1.32.58-1.32,1.3v5.87c0,.72.59,1.3,1.32,1.3h8.56v8.47c0,.72.59,1.3,1.32,1.3Z"/>
            </svg>
        </div>
        <h5 class="fw-bold mb-3 text-body" style="letter-spacing: 2px;">GAME COLLECTION</h5>
        <div class="loader-bar-container"><div class="loader-bar"></div></div>
    </div>
</div>

<div id="sidebarOverlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

<div class="mobile-header d-lg-none d-flex justify-content-between">
    <button class="btn border-0 p-2" onclick="toggleSidebar()"><i class="fas fa-bars fa-lg"></i></button>
    <span class="fw-bold fs-5 align-self-center">GameCollection</span>
    <div style="width: 40px;"></div>
</div>

<nav id="sidebar">
    <div class="p-4 border-bottom d-flex align-items-center justify-content-between flex-shrink-0">
        <a href="index.php" class="text-decoration-none d-block">
            <div class="sidebar-logo-container">
                <svg id="logo" xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 200 42">
                <path id="green" d="M98.93,17.73c.81,0,.81.44.81,1.33,0,5.49-3.92,9.41-9.54,9.41s-9.67-3.95-9.67-9.41,4.05-9.41,9.67-9.41c4.18,0,7.79,2.46,8.94,5.93.18.55-.13.89-.68.89h-4.03c-.42,0-.71-.18-.94-.52-.73-1.05-1.86-1.7-3.27-1.7-2.48,0-4.26,2.01-4.26,4.78s1.78,4.78,4.26,4.78c1.67,0,3.08-.86,3.63-2.14h-2.85c-.5,0-.78-.29-.78-.78v-2.38c0-.5.29-.78.78-.78h7.92ZM54.36,21.6c-1.07,0-1.94.87-1.94,1.95s.87,1.95,1.94,1.95,1.94-.87,1.94-1.95-.87-1.95-1.94-1.95ZM54.36,12.62c-1.07,0-1.94.87-1.94,1.95s.87,1.95,1.94,1.95,1.94-.87,1.94-1.95-.87-1.95-1.94-1.95ZM58.84,17.11c-1.07,0-1.94.87-1.94,1.95s.87,1.95,1.94,1.95,1.94-.87,1.94-1.95-.87-1.95-1.94-1.95ZM49.88,17.11c-1.07,0-1.94.87-1.94,1.95s.87,1.95,1.94,1.95,1.94-.87,1.94-1.95-.87-1.95-1.94-1.95Z" fill="#00f265"/>
                <path id="white" class="svg-adaptive-fill" d="M110.33,9.91c3.84,0,6.64,2.72,6.64,6.48s-2.8,6.46-6.64,6.46h-3.53v4.57c0,.5-.29.78-.78.78h-3.76c-.5,0-.78-.29-.78-.78V10.69c0-.5.29-.78.78-.78h8.08ZM109.72,18.35c1.05,0,1.8-.86,1.8-1.88,0-1.07-.76-1.86-1.8-1.86h-2.93v3.74h2.93ZM130.59,23.5c.5,0,.78.29.78.78v3.14c0,.5-.29.78-.78.78h-10.98c-.5,0-.78-.29-.78-.78V10.69c0-.5.29-.78.78-.78h3.76c.5,0,.78.29.78.78v12.81h6.43ZM152.08,27.32c.18.55-.05.89-.63.89h-4.13c-.44,0-.76-.21-.89-.65l-.73-2.4h-6.01l-.73,2.4c-.13.44-.44.65-.89.65h-4.13c-.58,0-.81-.34-.63-.89l5.91-16.78c.16-.44.47-.63.91-.63h5.12c.44,0,.76.18.91.63l5.91,16.78ZM140.73,21.67h3.92l-1.96-6.56-1.96,6.56ZM166.22,9.91c.63,0,.84.42.52.94l-6.35,10.35v6.22c0,.5-.29.78-.78.78h-3.76c-.5,0-.78-.29-.78-.78v-6.04l-6.46-10.53c-.31-.52-.1-.94.52-.94h4.31c.42,0,.71.18.91.55l3.32,6.27,3.32-6.27c.21-.37.5-.55.91-.55h4.31ZM173.67,14.62v2.17h6.69c.5,0,.78.29.78.78v2.93c0,.5-.29.78-.78.78h-6.69v2.22h7.22c.5,0,.78.29.78.78v3.14c0,.5-.29.78-.78.78h-11.77c-.5,0-.78-.29-.78-.78V10.69c0-.5.29-.78.78-.78h11.77c.5,0,.78.29.78.78v3.14c0,.5-.29.78-.78.78h-7.22ZM190.59,9.91c5.46,0,9.41,3.84,9.41,9.15s-3.95,9.15-9.41,9.15h-6.51c-.5,0-.78-.29-.78-.78V10.69c0-.5.29-.78.78-.78h6.51ZM190.54,23.5c2.33,0,4.03-1.88,4.03-4.47s-1.7-4.42-4.03-4.42h-1.91v8.89h1.91ZM54.36.5H18.8c-10.16,0-18.64,8.09-18.8,18.27-.16,10.36,8.21,18.84,18.52,18.84,4.92,0,9.4-1.93,12.72-5.08.3-.29.48-.68.48-1.1v-3.08h0v-3.14c0-.5-.4-.9-.89-.9h-5.4c-.5,0-.9.39-.9.89v2.64c0,.55-.3,1.06-.78,1.32-1.88.98-4.06,1.45-6.37,1.22-5.45-.54-9.79-5.02-10.18-10.5-.47-6.64,4.79-12.19,11.32-12.19.11,0,.23,0,.34,0h14.28s0,0,0,0h6.61s0,0,0,0c-1.52,1.96-2.66,4.22-3.3,6.68-.39,1.5-.6,3.06-.6,4.68,0,.01,0,.02,0,.03h0v21.51c0,.5.4.9.9.9h5.38c.49,0,.9-.4.9-.9v-6.9c3.29,2.57,7.46,4.05,11.98,3.9,10.04-.34,18.05-8.78,17.89-18.84-.16-10.1-8.4-18.26-18.52-18.26ZM55.6,30.36c-7.22.77-13.28-5.3-12.52-12.54.56-5.27,4.79-9.51,10.05-10.07,7.22-.77,13.28,5.3,12.52,12.54-.56,5.27-4.79,9.51-10.05,10.07ZM17.18,25.49h2.69c.33,0,.6-.27.6-.6v-3.89h3.88c.08,0,.15-.02.22-.04.22-.09.38-.3.38-.56v-2.69c0-.33-.27-.6-.6-.6h-3.88v-3.89c0-.33-.27-.6-.6-.6h-2.69c-.33,0-.6.27-.6.6v3.89h-3.88c-.33,0-.6.27-.6.6v2.69c0,.33.27.6.6.6h3.88v3.89c0,.33.27.6.6.6Z" fill="#fff"/>
                </svg>
            </div>
        </a>
        <button class="btn btn-sm d-lg-none ms-auto" onclick="toggleSidebar()"><i class="fas fa-times"></i></button>
    </div>

    <div class="p-3 sidebar-scrollable">
        <span class="text-uppercase text-secondary small fw-bold px-3 mb-2 d-block" style="font-size: 0.75rem; letter-spacing: 1px;">Menu</span>
        <ul class="nav flex-column mb-4">
            <?php $act = $_GET['action'] ?? 'home'; ?>
            <li class="nav-item"><a href="index.php?action=home" class="nav-link <?= ($act == 'home' || $act == '') ? 'active' : '' ?>"><i class="fas fa-th-large"></i> Bibliothèque</a></li>
            <li class="nav-item"><a href="index.php?action=progression" class="nav-link <?= ($act == 'progression') ? 'active' : '' ?>"><i class="fas fa-tasks"></i> Journal</a></li>
            <li class="nav-item"><a href="index.php?action=stats" class="nav-link <?= ($act == 'stats') ? 'active' : '' ?>"><i class="fas fa-chart-pie"></i> Statistiques</a></li>
            <li class="nav-item"><a href="index.php?action=community" class="nav-link <?= ($act == 'community') ? 'active' : '' ?>"><i class="fas fa-users"></i> Communauté</a></li>
            <li class="nav-item"><a href="index.php?action=feed" class="nav-link <?= ($act == 'feed') ? 'active' : '' ?>"><i class="fas fa-stream"></i>Fil d'actualités</a></li>
        </ul>
    </div>

    <div class="p-3 border-top mt-auto flex-shrink-0" style="background-color: var(--bg-darker); border-color: rgba(255,255,255,0.05) !important;">
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="dropdown dropup">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-body" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php if(isset($_SESSION['avatar']) && $_SESSION['avatar']): ?>
                        <img src="<?= $_SESSION['avatar'] ?>" class="user-avatar-sidebar me-2">
                    <?php else: ?>
                        <div class="user-avatar-sidebar bg-secondary d-flex align-items-center justify-content-center text-white me-2"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
                    <?php endif; ?>
                    <div class="overflow-hidden me-2"><strong class="d-block text-truncate"><?= htmlspecialchars($_SESSION['username']) ?></strong><small class="text-muted">En ligne</small></div>
                    <i class="fas fa-chevron-up custom-chevron ms-auto"></i>
                </a>
                <ul class="dropdown-menu sidebar-dropdown-menu rounded-3 border-0">
                    <li><a class="sidebar-dropdown-item" href="index.php?action=profile"><i class="fas fa-user-cog"></i>Mon Profil</a></li>
                    <li><button class="sidebar-dropdown-item w-100 text-start border-0 bg-transparent" id="themeToggle"><i class="fas fa-moon"></i>Thème</button></li>
                    <li><hr class="dropdown-divider my-1" style="border-color: rgba(255,255,255,0.1)"></li>
                    <li><a class="sidebar-dropdown-item text-danger" href="index.php?action=logout"><i class="fas fa-sign-out-alt"></i>Déconnexion</a></li>
                </ul>
            </div>
        <?php else: ?>
            <a href="index.php" class="btn btn-primary w-100">Se connecter</a>
        <?php endif; ?>
    </div>
</nav>

<div class="main-content"><?php require $view; ?></div>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="liveToast" class="toast border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-body border-bottom-0"><i class="fas fa-info-circle me-2 text-primary"></i><strong class="me-auto">Notification</strong><button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button></div>
        <div class="toast-body bg-body rounded-bottom-2" id="toastMessage"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    window.forceLoader = <?= $forceLoader ? 'true' : 'false' ?>;
    window.toastData = <?= isset($_SESSION['toast']) ? json_encode($_SESSION['toast']) : 'null' ?>; 
    <?php unset($_SESSION['toast']); ?>
</script>

<script src="assets/js/main.js"></script>

</body>
</html>