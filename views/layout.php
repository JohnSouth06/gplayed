<?php
// Récupération de l'indicateur de chargement forcé (après login/inscription)
$forceLoader = false;
if (isset($_SESSION['force_loader'])) {
    $forceLoader = true;
    unset($_SESSION['force_loader']); // On le consomme pour qu'il ne s'affiche qu'une fois
}
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- --- FAVICONS ADAPTATIFS --- -->
    <!-- S'affiche si le navigateur/système est en mode CLAIR -->
    <link rel="icon" href="favicon-light.png" type="image/png" media="(prefers-color-scheme: light)">
    <!-- S'affiche si le navigateur/système est en mode SOMBRE -->
    <link rel="icon" href="favicon-dark.png" type="image/png" media="(prefers-color-scheme: dark)">
    <!-- Fallback pour les navigateurs qui ne supportent pas le media query (Défaut) -->
    <link rel="icon" href="favicon-light.png" type="image/png">

    <title>Ma Collection de Jeux</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --sidebar-width: 280px;
            --header-height: 60px;
            --primary-color: #0d6efd;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bs-body-bg);
        }

        /* --- SVG THEME COLORS --- */
        [data-bs-theme="light"] .svg-adaptive-fill { fill: #333333; }
        [data-bs-theme="light"] .svg-adaptive-stroke { stroke: #333333; }
        [data-bs-theme="dark"] .svg-adaptive-fill { fill: #ffffff; }
        [data-bs-theme="dark"] .svg-adaptive-stroke { stroke: #ffffff; }

        /* --- LOADER --- */
        #app-loader {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-color: var(--bs-body-bg);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: opacity 0.5s ease-out, visibility 0.5s ease-out;
        }

        .loader-content { text-align: center; }
        
        .loader-logo-svg { 
            width: 240px; 
            height: 160px; 
            margin-bottom: 40px; 
            display: inline-block; 
        }
        
        .loader-logo-svg svg { 
            width: 100%; 
            height: 100%; 
            animation: logoPulse 2s infinite ease-in-out; 
            transform-origin: center; 
        }
        
        @keyframes logoPulse {
            0% { transform: scale(1); filter: drop-shadow(0 0 5px rgba(255, 255, 255, 0.2)); }
            50% { transform: scale(1.1); filter: drop-shadow(0 0 15px rgba(255, 255, 255, 0.4)); }
            100% { transform: scale(1); filter: drop-shadow(0 0 5px rgba(255, 255, 255, 0.2)); }
        }
        
        .loader-bar-container { width: 200px; height: 4px; background-color: rgba(128, 128, 128, 0.2); border-radius: 4px; overflow: hidden; position: relative; margin: 0 auto; }
        .loader-bar { width: 100%; height: 100%; background-color: var(--primary-color); position: absolute; left: -100%; animation: loading 1.5s infinite ease-in-out; }
        @keyframes loading { 0% { left: -100%; } 50% { left: 0; } 100% { left: 100%; } }

        /* --- SIDEBAR --- */
        #sidebar {
            width: var(--sidebar-width);
            position: fixed; 
            top: 0; 
            bottom: 0; 
            left: 0;
            z-index: 1040; 
            background-color: var(--bs-body-bg);
            border-right: 1px solid var(--bs-border-color);
            transition: transform 0.3s ease-in-out;
            display: flex; flex-direction: column; overflow: hidden;
        }
        .sidebar-scrollable { flex-grow: 1; overflow-y: auto; -webkit-overflow-scrolling: touch; }
        .main-content { margin-left: var(--sidebar-width); transition: margin-left 0.3s ease-in-out; min-height: 100vh; padding: 2rem; }
        .nav-link { border-radius: 8px; padding: 0.75rem 1rem; color: var(--bs-body-color); margin-bottom: 0.25rem; font-weight: 500; }
        .nav-link:hover, .nav-link.active { background-color: var(--bs-primary-bg-subtle); color: var(--bs-primary); }
        .nav-link i { width: 24px; text-align: center; margin-right: 10px; }
        .user-avatar-sidebar { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; }
        
        .sidebar-logo-container { width: 100%; max-width: 200px; height: auto; display: block; }
        .sidebar-logo-container svg { width: 100%; height: auto; display: block; }

        /* --- STYLES DROPDOWN UNIFIES --- */
        .dropdown-toggle::after { display: none !important; }
        
        .sidebar-dropdown-menu {
            min-width: 240px;
            padding: 0.5rem;
            background-color: var(--bs-body-bg);
            border: 1px solid var(--bs-border-color);
            box-shadow: 0 -5px 20px rgba(0,0,0,0.1);
        }
        
        .sidebar-dropdown-item {
            padding: 10px 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            color: var(--bs-body-color);
            text-decoration: none;
            transition: background-color 0.2s, color 0.2s;
            background: transparent;
            border: none;
            width: 100%;
            text-align: left;
        }
        
        .sidebar-dropdown-item:hover {
            background-color: var(--bs-primary-bg-subtle);
            color: var(--bs-primary);
        }
        
        .sidebar-dropdown-item i {
            width: 20px;
            text-align: center;
            color: var(--bs-secondary);
            transition: color 0.2s;
        }
        
        .sidebar-dropdown-item:hover i {
            color: var(--bs-primary);
        }
        
        .sidebar-dropdown-item.text-danger:hover {
            background-color: var(--bs-danger-bg-subtle) !important;
            color: var(--bs-danger) !important;
        }
        
        .sidebar-dropdown-item.text-danger:hover i {
            color: var(--bs-danger) !important;
        }

        .custom-chevron { 
            transition: transform 0.2s ease; 
            font-size: 0.8rem; 
            color: var(--bs-secondary); 
        }
        .dropdown.dropup .show .custom-chevron { 
            transform: rotate(180deg); 
        }

        /* --- INPUTS ADAPTATIFS --- */
        [data-bs-theme="light"] .form-control, [data-bs-theme="light"] .form-select, [data-bs-theme="light"] .input-group-text { background-color: #f8f9fa; border-color: #dee2e6; color: #212529; }
        [data-bs-theme="light"] .form-control:focus, [data-bs-theme="light"] .form-select:focus { background-color: #ffffff; border-color: #86b7fe; }
        [data-bs-theme="dark"] .form-control, [data-bs-theme="dark"] .form-select, [data-bs-theme="dark"] .input-group-text { background-color: #2b3035; border-color: #495057; color: #e9ecef; }
        [data-bs-theme="dark"] .form-control:focus, [data-bs-theme="dark"] .form-select:focus { background-color: #343a40; border-color: #0d6efd; }

        @media (max-width: 991.98px) {
            #sidebar { transform: translateX(-100%); width: 85%; max-width: 320px; height: 100%; height: 100dvh; }
            #sidebar.show { transform: translateX(0); box-shadow: 0 0 50px rgba(0,0,0,0.5); }
            .main-content { margin-left: 0; padding-top: calc(var(--header-height) + 1rem); }
            .mobile-header {
                height: var(--header-height); position: fixed; top: 0; left: 0; right: 0;
                background-color: var(--bs-body-bg); z-index: 1030; border-bottom: 1px solid var(--bs-border-color);
                display: flex; align-items: center; padding: 0 1rem;
            }
        }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1035; backdrop-filter: blur(2px); }
        .sidebar-overlay.show { display: block; }
    </style>
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
                <svg id="logo" xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 200 41">
                    <path id="grey" class="svg-adaptive-fill" d="M92,30.5l-1.7-2.8h.5l1.4,2.4h0l1.4-2.4h.5l-1.7,2.8v2h-.4v-2ZM99,27.6c1.2,0,2,1,2,2.5s-.8,2.5-2,2.5-2-1-2-2.5.8-2.5,2-2.5ZM99,28c-1,0-1.6.8-1.6,2.1s.6,2.1,1.6,2.1,1.6-.8,1.6-2.1-.6-2.1-1.6-2.1ZM104.7,27.7h.4v3.2c0,.9.4,1.3,1.3,1.3s1.3-.4,1.3-1.3v-3.2h.4v3.2c0,1.1-.6,1.7-1.7,1.7s-1.7-.6-1.7-1.7v-3.2ZM114,30.5l1.1,2h-.5l-1-2h-1.1v2h-.4v-4.8h1.7c1,0,1.6.5,1.6,1.4s-.5,1.3-1.2,1.4h0ZM113.7,30.1c.8,0,1.2-.3,1.2-1s-.4-1-1.2-1h-1.2v2h1.2ZM125.2,27.6c.5,0,.9.1,1.2.4.3.3.5.7.6,1.1h-.4c-.2-.7-.7-1.2-1.4-1.2s-1.6.8-1.6,2.1.6,2.1,1.6,2.1,1.4-.6,1.4-1.6h-1.4v-.4h1.8v.4c0,1.3-.7,2-1.8,2s-2-1-2-2.5.8-2.5,2-2.5ZM133.1,31.5h-2.3l-.4,1h-.4l1.8-4.8h.4l1.8,4.8h-.4l-.4-1ZM133,31.1l-1-2.8h0l-1,2.8h2ZM137.3,27.7h.6l1.7,4.1h0l1.7-4.1h.6v4.8h-.4v-4.1h0l-1.7,4.1h-.2l-1.7-4.1h0v4.1h-.4v-4.8ZM145.9,27.7h.4v4.8h-.4v-4.8ZM150.3,27.7h.5l2.4,4.1h0v-4.1h.4v4.8h-.5l-2.4-4.1h0v4.1h-.4v-4.8ZM159.4,27.6c.5,0,.9.1,1.2.4.3.3.5.7.6,1.1h-.4c-.2-.7-.7-1.2-1.4-1.2s-1.6.8-1.6,2.1.6,2.1,1.6,2.1,1.4-.6,1.4-1.6h-1.4v-.4h1.8v.4c0,1.3-.7,2-1.8,2s-2-1-2-2.5.8-2.5,2-2.5ZM169.6,30.9c0,.9.6,1.3,1.5,1.3s1.2-.3,1.2-.9-.3-.8-1.4-1.2c-1.1-.4-1.5-.7-1.5-1.3s.6-1.2,1.5-1.2.8.1,1.1.4c.3.2.5.6.6,1h-.4c-.1-.6-.6-1-1.3-1s-1.1.3-1.1.8.3.7,1.2.9c1.3.4,1.7.8,1.7,1.5s-.7,1.3-1.6,1.3-1.8-.6-1.9-1.7h.4ZM177.3,28.1h-1.5v-.4h3.5v.4h-1.5v4.4h-.4v-4.4ZM184.3,27.6c1.2,0,2,1,2,2.5s-.8,2.5-2,2.5-2-1-2-2.5.8-2.5,2-2.5ZM184.3,28c-1,0-1.6.8-1.6,2.1s.6,2.1,1.6,2.1,1.6-.8,1.6-2.1-.6-2.1-1.6-2.1ZM192,30.5l1.1,2h-.5l-1-2h-1.1v2h-.4v-4.8h1.7c1,0,1.6.5,1.6,1.4s-.5,1.3-1.2,1.4h0ZM191.7,30.1c.8,0,1.2-.3,1.2-1s-.4-1-1.2-1h-1.2v2h1.2ZM197.8,30.5l-1.7-2.8h.5l1.4,2.4h0l1.4-2.4h.5l-1.7,2.8v2h-.4v-2Z"/>
                    <path id="green" d="M107.2,11.6c.7,0,.7.4.7,1.2,0,5-3.6,8.6-8.7,8.6s-8.8-3.6-8.8-8.6,3.7-8.6,8.8-8.6,7.1,2.2,8.1,5.4c.2.5-.1.8-.6.8h-3.7c-.4,0-.6-.2-.9-.5-.7-1-1.7-1.5-3-1.5-2.3,0-3.9,1.8-3.9,4.4s1.6,4.4,3.9,4.4,2.8-.8,3.3-2h-2.6c-.5,0-.7-.3-.7-.7v-2.2c0-.5.3-.7.7-.7h7.2ZM54.1,21.2c-1.1,0-1.9.9-1.9,1.9s.9,1.9,1.9,1.9,1.9-.9,1.9-1.9-.9-1.9-1.9-1.9ZM54.1,12.3c-1.1,0-1.9.9-1.9,1.9s.9,1.9,1.9,1.9,1.9-.9,1.9-1.9-.9-1.9-1.9-1.9ZM58.6,16.7c-1.1,0-1.9.9-1.9,1.9s.9,1.9,1.9,1.9,1.9-.9,1.9-1.9-.9-1.9-1.9-1.9ZM49.7,16.7c-1.1,0-1.9.9-1.9,1.9s.9,1.9,1.9,1.9,1.9-.9,1.9-1.9-.9-1.9-1.9-1.9Z" fill="#74f36f"/>
                    <path id="white" class="svg-adaptive-fill" d="M117.6,4.5c3.5,0,6.1,2.5,6.1,5.9s-2.5,5.9-6.1,5.9h-3.2v4.2c0,.5-.3.7-.7.7h-3.4c-.5,0-.7-.3-.7-.7V5.2c0-.5.3-.7.7-.7h7.4ZM117.1,12.2c1,0,1.6-.8,1.6-1.7s-.7-1.7-1.6-1.7h-2.7v3.4h2.7ZM136.1,16.9c.5,0,.7.3.7.7v2.9c0,.5-.3.7-.7.7h-10c-.5,0-.7-.3-.7-.7V5.2c0-.5.3-.7.7-.7h3.4c.5,0,.7.3.7.7v11.7h5.9ZM155.7,20.4c.2.5,0,.8-.6.8h-3.8c-.4,0-.7-.2-.8-.6l-.7-2.2h-5.5l-.7,2.2c-.1.4-.4.6-.8.6h-3.8c-.5,0-.7-.3-.6-.8l5.4-15.3c.1-.4.4-.6.8-.6h4.7c.4,0,.7.2.8.6l5.4,15.3ZM145.3,15.2h3.6l-1.8-6-1.8,6ZM168.6,4.5c.6,0,.8.4.5.9l-5.8,9.4v5.7c0,.5-.3.7-.7.7h-3.4c-.5,0-.7-.3-.7-.7v-5.5l-5.9-9.6c-.3-.5,0-.9.5-.9h3.9c.4,0,.6.2.8.5l3,5.7,3-5.7c.2-.3.5-.5.8-.5h3.9ZM175.4,8.8v2h6.1c.5,0,.7.3.7.7v2.7c0,.5-.3.7-.7.7h-6.1v2h6.6c.5,0,.7.3.7.7v2.9c0,.5-.3.7-.7.7h-10.7c-.5,0-.7-.3-.7-.7V5.2c0-.5.3-.7.7-.7h10.7c.5,0,.7.3.7.7v2.9c0,.5-.3.7-.7.7h-6.6ZM190.8,4.5c5,0,8.6,3.5,8.6,8.3s-3.6,8.3-8.6,8.3h-5.9c-.5,0-.7-.3-.7-.7V5.2c0-.5.3-.7.7-.7h5.9ZM190.7,16.9c2.1,0,3.7-1.7,3.7-4.1s-1.5-4-3.7-4h-1.7v8.1h1.7ZM54.1.2H18.7c-10.1,0-18.6,8-18.7,18.2s8.2,18.8,18.5,18.8,9.4-1.9,12.7-5.1.5-.7.5-1.1v-3.1h0v-3.1c0-.5-.4-.9-.9-.9h-5.4c-.5,0-.9.4-.9.9v2.6c0,.5-.3,1.1-.8,1.3-1.9,1-4.1,1.4-6.4,1.2-5.4-.5-9.8-5-10.1-10.4s4.8-12.1,11.3-12.1.2,0,.3,0h14.2s0,0,0,0h6.6s0,0,0,0c-1.5,1.9-2.6,4.2-3.3,6.7-.4,1.5-.6,3-.6,4.7s0,0,0,0h0v21.4c0,.5.4.9.9.9h5.4c.5,0,.9-.4.9-.9v-6.9c3.3,2.6,7.4,4,11.9,3.9,10-.3,18-8.7,17.8-18.7S64.2.2,54.1.2ZM55.4,29.9c-7.2.8-13.2-5.3-12.5-12.5s4.8-9.5,10-10,13.2,5.3,12.5,12.5-4.8,9.5-10,10ZM17.1,25.1h2.7c.3,0,.6-.3.6-.6v-3.9h3.9c0,0,.2,0,.2,0,.2,0,.4-.3.4-.6v-2.7c0-.3-.3-.6-.6-.6h-3.9v-3.9c0-.3-.3-.6-.6-.6h-2.7c-.3,0-.6.3-.6.6v3.9h-3.9c-.3,0-.6.3-.6.6v2.7c0,.3.3.6.6.6h3.9v3.9c0,.3.3.6.6.6Z"/>
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
    (function() {
        const loader = document.getElementById('app-loader');
        const forceAnimation = <?= $forceLoader ? 'true' : 'false' ?>;
        const hasVisited = sessionStorage.getItem('app_visited');

        if (!forceAnimation && hasVisited) {
            loader.style.display = 'none';
            document.body.style.overflow = 'auto'; 
        } else {
            sessionStorage.setItem('app_visited', 'true');
            window.addEventListener('load', function() {
                setTimeout(function() {
                    loader.style.opacity = '0';
                    loader.style.visibility = 'hidden';
                    document.body.style.overflow = 'auto';
                }, 800);
            });
        }
    })();

    function toggleSidebar() { document.getElementById('sidebar').classList.toggle('show'); document.getElementById('sidebarOverlay').classList.toggle('show'); }
    const toastData = <?= isset($_SESSION['toast']) ? json_encode($_SESSION['toast']) : 'null' ?>; <?php unset($_SESSION['toast']); ?>
    document.addEventListener('DOMContentLoaded', () => { initTheme(); if(toastData) { document.getElementById('toastMessage').innerText = toastData.msg; const toastEl = document.getElementById('liveToast'); if(toastData.type === 'danger') document.querySelector('.toast-header').classList.add('text-danger'); new bootstrap.Toast(toastEl).show(); } });
    function initTheme() { const t = document.getElementById('themeToggle'); if(!t) return; const savedTheme = localStorage.getItem('theme'); if(savedTheme) { document.documentElement.setAttribute('data-bs-theme', savedTheme); updateThemeIcon(savedTheme === 'dark'); } t.onclick = (e) => { e.preventDefault(); e.stopPropagation(); const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark'; const newTheme = isDark ? 'light' : 'dark'; document.documentElement.setAttribute('data-bs-theme', newTheme); localStorage.setItem('theme', newTheme); updateThemeIcon(!isDark); }; }
    function updateThemeIcon(isDark) { const t = document.getElementById('themeToggle'); if(t) t.innerHTML = isDark ? '<i class="fas fa-sun"></i>Thème' : '<i class="fas fa-moon"></i>Thème'; }
</script>
</body>
</html>