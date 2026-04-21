<?php

/**
 * GPlayed - Nouveau point d'entrée unifié
 * Redirige le trafic web vers l'application mobile.
 */

$action = $_GET['action'] ?? '';

// 1. REDIRECTION DES LIENS DE PARTAGE (Legacy)
if ($action === 'share' && !empty($_GET['username'])) {
    $username = urlencode($_GET['username']);
    $appLink = "gplayed://profile/$username";
    header("Location: /api/index.php?action=app_bounce&target=" . urlencode($appLink));
    exit;
}

// 2. REDIRECTION DES LIENS DE RÉINITIALISATION DE MOT DE PASSE
if ($action === 'reset_password' && !empty($_GET['token'])) {
    $token = urlencode($_GET['token']);
    $appLink = "gplayed://reset-password?token=$token";
    header("Location: /api/index.php?action=app_bounce&target=" . urlencode($appLink));
    exit;
}

// 3. PAGE VITRINE MINIMALE
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1.0, user-scalable=no">
    <meta name="view-transition" content="same-origin">

    <meta property="og:title" content="GPlayed | Your Gaming Story" />
    <meta property="og:description" content="Your entire gaming world in your pocket. Manage your collection, track your playtime habits, and share your passion with a community of players." />
    <meta property="og:image" content="https://www.g-played.com/assets/images/gplayed-app-thumb.jpg" />
    <meta property="og:url" content="https://www.g-played.com" />
    <meta property="og:type" content="website" />

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="GPlayed | Your Gaming Story" />
    <meta name="twitter:description" content="Your entire gaming world in your pocket. Manage your collection, track your playtime habits, and share your passion with a community of players." />
    <meta property="og:image" content="https://www.g-played.com/assets/images/gplayed-app-thumb.jpg" />

    <link rel="icon" href="favicon-light.png" type="image/png">
    <title>GPlayed • Your Gaming Story</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>

<body>

    <div id="cursor"></div>
    <div id="cursor-ring"></div>

    <!-- NAV -->
    <nav id="nav">
        <a class="nav-logo" href="#">
            <svg width="180" height="40" id="logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 177.82 38.32">
                <defs>
                    <style>
                        .cls-1 {
                            fill: #fff;
                        }

                        .cls-2 {
                            fill: #4ce5ae;
                        }
                    </style>
                </defs>
                <path id="white" class="cls-1" d="M101.06,3.8c3.06-.08,5.61,2.33,5.69,5.39.08,3.06-2.33,5.61-5.39,5.69-.1,0-.2,0-.3,0h-3.02v3.92c.03.34-.21.64-.55.67-.04,0-.08,0-.12,0h-3.22c-.34.03-.64-.21-.67-.55,0-.04,0-.08,0-.12V4.47c-.03-.34.21-.64.55-.67.04,0,.08,0,.12,0h6.92ZM100.55,11.03c.86,0,1.55-.72,1.54-1.58,0-.01,0-.02,0-.03.04-.84-.61-1.55-1.44-1.59-.03,0-.07,0-.1,0h-2.51v3.2h2.51ZM118.41,15.44c.34-.03.64.21.67.55,0,.04,0,.08,0,.12v2.69c.03.34-.21.64-.55.67-.04,0-.08,0-.12,0h-9.4c-.34.03-.64-.21-.67-.55,0-.04,0-.08,0-.12V4.47c-.03-.34.21-.64.55-.67.04,0,.08,0,.12,0h3.22c.34-.03.64.21.67.55,0,.04,0,.08,0,.12v10.97h5.51ZM136.8,18.71c.16.47-.04.76-.54.76h-3.54c-.36.03-.68-.21-.76-.56l-.63-2.06h-5.15l-.63,2.06c-.08.35-.4.59-.76.56h-3.54c-.49,0-.69-.29-.54-.76l5.06-14.37c.1-.34.43-.57.78-.54h4.39c.36-.03.68.19.78.54l5.06,14.37ZM127.09,13.87h3.36l-1.68-5.62-1.68,5.62ZM148.91,3.8c.54,0,.72.36.45.81l-5.44,8.86v5.33c.03.34-.21.64-.55.67-.04,0-.08,0-.12,0h-3.22c-.34.03-.64-.21-.67-.55,0-.04,0-.08,0-.12v-5.17l-5.53-9.02c-.27-.45-.09-.81.45-.81h3.69c.33-.01.64.17.78.47l2.84,5.37,2.84-5.37c.15-.3.45-.48.78-.47h3.69ZM155.29,7.83v1.86h5.73c.34-.03.64.21.67.55,0,.04,0,.08,0,.12v2.51c.03.34-.21.64-.55.67-.04,0-.08,0-.12,0h-5.73v1.9h6.18c.34-.03.64.21.67.55,0,.04,0,.08,0,.13v2.68c.03.34-.21.64-.55.67-.04,0-.08,0-.12,0h-10.07c-.34.03-.64-.21-.67-.55,0-.04,0-.08,0-.12V4.47c-.03-.34.21-.64.55-.67.04,0,.08,0,.12,0h10.07c.34-.03.64.21.67.55,0,.04,0,.08,0,.12v2.69c.03.34-.21.64-.55.67-.04,0-.08,0-.12,0h-6.18ZM169.77,3.8c4.33-.12,7.93,3.29,8.05,7.62s-3.29,7.93-7.62,8.05c-.14,0-.29,0-.43,0h-5.57c-.34.03-.64-.21-.67-.55,0-.04,0-.08,0-.12V4.47c-.03-.34.21-.64.55-.67.04,0,.08,0,.12,0h5.57ZM169.72,15.44c2.1-.21,3.64-2.08,3.43-4.18-.18-1.81-1.61-3.25-3.43-3.43h-1.63v7.61h1.63ZM50.91,0H17.61C8.07-.05.24,7.54,0,17.07c-.15,9.57,7.48,17.46,17.05,17.61,4.53.07,8.91-1.63,12.2-4.75.28-.27.45-.64.45-1.03v-5.82c0-.46-.38-.84-.84-.84h-5.06c-.46,0-.84.37-.84.83h0v2.47c0,.52-.28.99-.73,1.23-5.21,2.7-11.63.67-14.33-4.54-2.7-5.21-.67-11.63,4.54-14.33,1.51-.79,3.2-1.2,4.9-1.19.11,0,.21,0,.32,0h13.38,0s6.19,0,6.19,0h0c-2.37,3.04-3.65,6.78-3.65,10.63v.03h0v20.11c0,.46.38.84.84.84h5.03c.46,0,.84-.38.84-.84v-6.45c7.56,5.87,18.46,4.5,24.33-3.06,5.87-7.56,4.5-18.46-3.06-24.33C58.51,1.28,54.77,0,50.91,0M52.07,27.91c-5.84.64-11.09-3.58-11.72-9.42-.64-5.84,3.58-11.09,9.42-11.72,5.84-.64,11.09,3.58,11.72,9.42.08.77.08,1.54,0,2.31-.55,4.96-4.46,8.87-9.42,9.42M16.09,23.36h2.52c.31,0,.56-.25.56-.56h0v-3.64h3.64c.31,0,.56-.25.56-.56v-2.52c0-.31-.25-.56-.56-.56h-3.64v-3.64c0-.31-.25-.56-.56-.56h-2.52c-.31,0-.56.25-.56.56v3.64h-3.64c-.31,0-.56.25-.56.56h0v2.52c0,.31.25.56.56.56h3.64v3.64c0,.31.25.56.56.56h0M77.14,29.17l-1.59-2.63h.45l1.33,2.24h.01l1.32-2.24h.45l-1.59,2.63v1.85h-.4v-1.85ZM83.66,26.45c1.13,0,1.88.94,1.88,2.33s-.75,2.33-1.88,2.33-1.88-.94-1.88-2.33.75-2.33,1.88-2.33M83.66,26.82c-.9,0-1.49.77-1.49,1.96s.59,1.96,1.49,1.96,1.49-.77,1.49-1.96-.58-1.96-1.49-1.96M88.98,26.54h.39v2.99c0,.81.39,1.21,1.17,1.21.58.08,1.11-.33,1.18-.91.01-.1.01-.2,0-.29v-3h.4v3.01c-.02.87-.74,1.55-1.61,1.53-.84-.02-1.51-.69-1.53-1.53v-3.01ZM97.7,29.12l1.01,1.9h-.46l-.98-1.89h-1.06v1.89h-.39v-4.47h1.58c.92,0,1.46.47,1.46,1.28.04.67-.47,1.24-1.13,1.28,0,0-.02,0-.02,0h0ZM97.37,28.76c.72,0,1.09-.32,1.09-.93s-.38-.91-1.09-.91h-1.16v1.85h1.16ZM108.07,26.45c.41-.01.81.13,1.12.39.31.27.52.65.57,1.05l-.39.07c-.06-.66-.63-1.17-1.3-1.14-.9,0-1.49.77-1.49,1.96s.59,1.96,1.49,1.96,1.35-.55,1.33-1.49h-1.27v-.37h1.65v.36c.12.91-.52,1.74-1.42,1.86-.1.01-.19.02-.29.01-1.13,0-1.88-.93-1.88-2.33s.75-2.33,1.88-2.33M115.49,30.06h-2.16l-.35.95h-.41l1.64-4.47h.38l1.64,4.47h-.41l-.34-.95ZM115.37,29.72l-.95-2.64h-.01l-.95,2.64h1.91ZM119.44,26.54h.53l1.57,3.8h.01l1.57-3.8h.53v4.47h-.4v-3.85h-.01l-1.61,3.85h-.18l-1.61-3.85h0v3.85h-.39v-4.47ZM127.41,26.54h.39v4.47h-.39v-4.47ZM131.55,26.54h.47l2.22,3.87h.01v-3.87h.4v4.47h-.47l-2.22-3.86h-.01v3.86h-.39v-4.47ZM139.99,26.45c.41-.01.81.13,1.12.39.31.27.52.65.57,1.05l-.39.07c-.06-.66-.63-1.17-1.3-1.14-.9,0-1.49.77-1.49,1.96s.59,1.96,1.49,1.96,1.35-.55,1.33-1.49h-1.27v-.37h1.65v.36c.12.91-.52,1.74-1.42,1.86-.1.01-.19.02-.29.01-1.13,0-1.88-.93-1.88-2.33s.75-2.33,1.88-2.33M149.56,29.5c0,.68.54,1.24,1.23,1.25.06,0,.11,0,.17,0,.69,0,1.14-.33,1.14-.82s-.33-.78-1.31-1.09c-1.07-.33-1.4-.63-1.4-1.23.03-.68.61-1.21,1.3-1.17.03,0,.06,0,.1,0,.38-.01.75.11,1.05.35.29.23.48.56.52.93l-.38.08c-.08-.59-.6-1.02-1.2-.98-.59,0-1,.33-1,.79,0,.4.27.61,1.15.88,1.17.36,1.57.73,1.57,1.43s-.62,1.19-1.53,1.19c-.87.11-1.66-.51-1.77-1.38,0-.06-.01-.12-.01-.17l.39-.06ZM156.69,26.91h-1.42v-.37h3.23v.37h-1.41v4.1h-.4v-4.1ZM163.21,26.45c1.13,0,1.88.94,1.88,2.33s-.75,2.33-1.88,2.33-1.88-.94-1.88-2.33.75-2.33,1.88-2.33M163.21,26.82c-.9,0-1.49.77-1.49,1.96s.59,1.96,1.49,1.96,1.49-.77,1.49-1.96-.58-1.96-1.49-1.96M170.44,29.12l1.01,1.9h-.46l-.98-1.89h-1.06v1.89h-.39v-4.47h1.58c.92,0,1.46.47,1.46,1.28.04.67-.47,1.24-1.13,1.28,0,0-.02,0-.02,0h0ZM170.1,28.76c.71,0,1.09-.32,1.09-.93s-.38-.91-1.09-.91h-1.16v1.85h1.16ZM175.84,29.17l-1.59-2.63h.45l1.33,2.24h.01l1.32-2.24h.45l-1.58,2.63v1.85h-.4v-1.85Z" />
                <path id="green" class="cls-2" d="M91.31,10.49c.69,0,.69.38.69,1.14.16,4.3-3.2,7.91-7.5,8.06-.22,0-.45,0-.67,0-4.45.12-8.16-3.39-8.28-7.84-.12-4.45,3.39-8.16,7.84-8.28.15,0,.29,0,.44,0,3.37-.11,6.45,1.93,7.65,5.08.16.47-.11.76-.58.76h-3.45c-.33,0-.64-.16-.81-.45-.63-.93-1.68-1.47-2.8-1.46-2.13,0-3.65,1.72-3.65,4.1s1.52,4.1,3.65,4.1c1.31.06,2.53-.66,3.11-1.84h-2.44c-.34.03-.64-.21-.67-.55,0-.04,0-.08,0-.12v-2.05c-.03-.34.21-.64.55-.67.04,0,.08,0,.12,0h6.78ZM50.91,19.72c-1,0-1.82.81-1.82,1.82s.81,1.82,1.82,1.82c1,0,1.82-.81,1.82-1.82h0c0-1-.81-1.82-1.82-1.82M50.91,11.33c-1,0-1.82.81-1.82,1.82s.81,1.82,1.82,1.82,1.82-.81,1.82-1.82h0c0-1-.81-1.82-1.82-1.82M55.11,15.53c-1,0-1.82.81-1.82,1.82s.81,1.82,1.82,1.82,1.82-.81,1.82-1.82h0c0-1-.81-1.82-1.82-1.82M46.72,15.53c-1,0-1.82.81-1.82,1.82s.81,1.82,1.82,1.82,1.82-.81,1.82-1.82h0c0-1-.81-1.82-1.82-1.82" />
            </svg>
            <ul>
                <li><a href="#features">Fonctionnalités</a></li>
                <li><a href="#library">Bibliothèque</a></li>
                <li><a href="#stats">Statistiques</a></li>
            </ul>
            <a href="#cta" class="nav-cta">Rejoindre la bêta</a>
    </nav>

    <!-- HERO -->
    <section class="hero">
        <div class="hero-grid"></div>
        <div class="hero-glow"></div>
        <div class="hero-ring"></div>
        <div class="hero-ring2"></div>

        <div class="hero-badge">
            <span class="badge-dot"></span>Bientôt disponible
        </div>

        <h1>
            <div class="line"><span>Ton histoire</span></div>
            <div class="line"><span class="accent">gaming</span></div>
            <div class="line"><span>enfin racontée.</span></div>
        </h1>

        <p>Suis tes jeux, note tes sessions, analyse tes stats. GPlayed centralise toute ta collection sur toutes tes plateformes.</p>

        <div class="hero-btns">
            <a href="#cta" class="btn-p">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.7 9.05 7.42c1.27.06 2.15.59 2.9.62.85-.03 1.67-.62 3.09-.67 2.02.09 3.51 1.18 4.37 2.99-3.83 2.25-2.93 7.5 1.64 8.92zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z" />
                </svg>
                App Store
            </a>
            <a href="#cta" class="btn-o">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M3.18 23.76c.35.19.77.2 1.12.02l13.1-7.56-2.92-2.92-11.3 10.46zM.5 1.22C.18 1.57 0 2.08 0 2.72v18.56c0 .64.18 1.15.5 1.5l.08.07 10.4-10.4v-.24L.58 1.15.5 1.22zm20.28 9.83l-2.89-1.67-3.24 3.24 3.24 3.24 2.9-1.68c.83-.48.83-1.26-.01-1.13zM4.3.22L17.4 7.78l-2.92 2.92L3.18.24c.35-.2.77-.18 1.12-.02z" />
                </svg>
                Google Play
            </a>
        </div>

        <!-- CAROUSEL MOBILE -->
        <div class="hero-carousel">
          <div class="carousel-stage" id="carouselStage">

            <!-- PHONE 0 · Accueil -->
            <div class="c-phone" data-idx="0">
              <img class="c-screen-img" src="assets/images/01-screen-home.png" alt="Connexion">
              <div class="c-phone-glow"></div>
            </div>

            <!-- PHONE 1 · Bibliothèque -->
            <div class="c-phone" data-idx="1">
              <img class="c-screen-img" src="assets/images/02-screen-library.png" alt="Bibliothèque">
              <div class="c-phone-glow"></div>
            </div>

            <!-- PHONE 2 · Jeux -->
            <div class="c-phone" data-idx="2">
              <img class="c-screen-img" src="assets/images/03-screen-game.png" alt="Jeux">
              <div class="c-phone-glow"></div>
            </div>

            <!-- 
            <div class="c-phone" data-idx="3">
              <img class="c-screen-img" src="assets/images/screen-journal.jpg" alt="Journal de jeu">
              <div class="c-phone-glow"></div>
            </div>


            <div class="c-phone" data-idx="4">
              <img class="c-screen-img" src="assets/images/screen-profil.jpg" alt="Mon Profil">
              <div class="c-phone-glow"></div>
            </div>-->

          </div><!-- /carousel-stage -->

          <div class="carousel-meta">
            <div class="carousel-dots">
              <button class="c-dot" data-idx="0"></button>
              <button class="c-dot" data-idx="1"></button>
              <button class="c-dot" data-idx="2"></button>
              <button class="c-dot" data-idx="3"></button>
              <button class="c-dot" data-idx="4"></button>
            </div>
            <div class="carousel-label"><span class="c-label-text">Accueil</span></div>
          </div>
        </div><!-- /hero-carousel -->
    </section>

    <!-- MARQUEE -->
    <div class="marquee-section">
        <div class="marquee-track">
            <span class="marquee-item">Bibliothèque<span class="marquee-sep"></span></span>
            <span class="marquee-item">Journal de jeu<span class="marquee-sep"></span></span>
            <span class="marquee-item">Statistiques<span class="marquee-sep"></span></span>
            <span class="marquee-item">Multi-plateformes<span class="marquee-sep"></span></span>
            <span class="marquee-item">Suivi de progression<span class="marquee-sep"></span></span>
            <span class="marquee-item">Notes & avis<span class="marquee-sep"></span></span>
            <span class="marquee-item">Recherche instantanée<span class="marquee-sep"></span></span>
            <span class="marquee-item">Bibliothèque<span class="marquee-sep"></span></span>
            <span class="marquee-item">Journal de jeu<span class="marquee-sep"></span></span>
            <span class="marquee-item">Statistiques<span class="marquee-sep"></span></span>
            <span class="marquee-item">Multi-plateformes<span class="marquee-sep"></span></span>
            <span class="marquee-item">Suivi de progression<span class="marquee-sep"></span></span>
            <span class="marquee-item">Notes & avis<span class="marquee-sep"></span></span>
            <span class="marquee-item">Recherche instantanée<span class="marquee-sep"></span></span>
        </div>
    </div>

    <!-- FEATURES -->
    <section id="features" style="background:var(--bg2);border-top:1px solid var(--border);border-bottom:1px solid var(--border)">
        <div class="features-wrap">
            <div class="s-label reveal">Fonctionnalités</div>
            <h2 class="s-title reveal d1">Tout ce qu'il te faut<br>pour écrire ton histoire.</h2>
            <div class="feat-grid reveal d2">
                <div class="feat-card">
                    <div class="feat-icon">🎮</div>
                    <h3>Bibliothèque unifiée</h3>
                    <p>Centralise tous tes jeux, quelle que soit la plateforme, en un seul espace organisé.</p>
                </div>
                <div class="feat-card">
                    <div class="feat-icon">📖</div>
                    <h3>Journal de jeu</h3>
                    <p>Documente chaque session, ajoute des notes et revivez tes meilleures aventures.</p>
                </div>
                <div class="feat-card">
                    <div class="feat-icon">📊</div>
                    <h3>Statistiques</h3>
                    <p>Visualise tes habitudes de jeu, genres favoris et l'évolution de ta collection.</p>
                </div>
                <div class="feat-card">
                    <div class="feat-icon">🏆</div>
                    <h3>Suivi de statut</h3>
                    <p>En cours, terminé, platine ou abandonné — catégorise chaque jeu précisément.</p>
                </div>
                <div class="feat-card">
                    <div class="feat-icon">🔍</div>
                    <h3>Recherche rapide</h3>
                    <p>Retrouve n'importe quel jeu de ta collection instantanément.</p>
                </div>
                <div class="feat-card">
                    <div class="feat-icon">⭐</div>
                    <h3>Notes & avis</h3>
                    <p>Attribue une note personnelle et retrouve tes coups de cœur en un clin d'œil.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- LIBRARY -->
    <section id="library">
        <div class="split">
            <div class="reveal-l">
                <div class="s-label">Bibliothèque</div>
                <h2 class="s-title">Ta collection,<br>organisée à<br>la perfection.</h2>
                <p class="s-sub">Filtre par plateforme, statut ou genre. Vue grille ou liste selon ton humeur. Tout s'adapte à ta façon de jouer.</p>
                <div class="pill-wrap">
                    <span class="pill on">PlayStation</span>
                    <span class="pill on">Xbox</span>
                    <span class="pill">Nintendo</span>
                    <span class="pill">PC</span>
                    <span class="pill">Mobile</span>
                    <span class="pill">Rétro</span>
                </div>
            </div>
            <div class="split-vis reveal-r">
                <div class="s-list">
                    <div class="s-list-title">Ma collection — 98 jeux</div>
                    <div class="s-row">
                        <div class="s-dot" style="background:#4CE5AE"></div><span class="s-name">En cours</span><span class="s-count">7</span>
                    </div>
                    <div class="s-row">
                        <div class="s-dot" style="background:#4a9eed"></div><span class="s-name">Terminé</span><span class="s-count">43</span>
                    </div>
                    <div class="s-row">
                        <div class="s-dot" style="background:#f0c040"></div><span class="s-name">Platine / 100%</span><span class="s-count">12</span>
                    </div>
                    <div class="s-row">
                        <div class="s-dot" style="background:#e06060"></div><span class="s-name">À faire</span><span class="s-count">31</span>
                    </div>
                    <div class="s-row">
                        <div class="s-dot" style="background:#555"></div><span class="s-name">Abandonné</span><span class="s-count">5</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- STATS -->
    <section id="stats" style="background:var(--bg2);border-top:1px solid var(--border);border-bottom:1px solid var(--border)">
        <div class="split rev">
            <div class="split-vis reveal-l">
                <div class="chart-demo">
                    <div class="chart-meta">
                        <div>
                            <div class="chart-num">207h</div>
                            <div class="chart-sub">6 derniers mois</div>
                        </div>
                        <div style="text-align:right">
                            <div style="font-size:.9rem;font-weight:800;color:var(--g)">+18%</div>
                            <div class="chart-sub">vs avant</div>
                        </div>
                    </div>
                    <div class="bars">
                        <div class="bar-col">
                            <div class="bar" style="height:55%"></div><span class="bar-lbl">Nov</span>
                        </div>
                        <div class="bar-col">
                            <div class="bar" style="height:70%"></div><span class="bar-lbl">Déc</span>
                        </div>
                        <div class="bar-col">
                            <div class="bar" style="height:42%"></div><span class="bar-lbl">Jan</span>
                        </div>
                        <div class="bar-col">
                            <div class="bar" style="height:92%"></div><span class="bar-lbl">Fév</span>
                        </div>
                        <div class="bar-col">
                            <div class="bar" style="height:63%"></div><span class="bar-lbl">Mar</span>
                        </div>
                        <div class="bar-col">
                            <div class="bar" style="height:78%"></div><span class="bar-lbl">Avr</span>
                        </div>
                    </div>
                    <div class="chart-cards">
                        <div class="c-card">
                            <div class="c-card-val">RPG</div>
                            <div class="c-card-lbl">Genre favori</div>
                        </div>
                        <div class="c-card">
                            <div class="c-card-val">PS5</div>
                            <div class="c-card-lbl">Plateforme #1</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="reveal-r">
                <div class="s-label">Statistiques</div>
                <h2 class="s-title">Découvre ta<br>vraie façon<br>de jouer.</h2>
                <p class="s-sub">Des graphiques intuitifs pour comprendre tes habitudes. Temps de jeu, genres, plateformes — tout est analysé automatiquement.</p>
            </div>
        </div>
    </section>

    <!-- PLATFORMS -->
    <section id="platforms" style="text-align:center">
        <div class="s-label reveal">Compatibilité</div>
        <h2 class="s-title reveal d1" style="max-width:500px;margin:0 auto 1rem">Toutes tes plateformes,<br>un seul endroit.</h2>
        <p class="s-sub reveal d2" style="margin:0 auto">Du dernier né aux consoles rétro, GPlayed couvre tout l'écosystème gaming.</p>
        <div class="plat-grid">
            <div class="plat-badge reveal d1">PlayStation 5</div>
            <div class="plat-badge reveal d1">PlayStation 4</div>
            <div class="plat-badge reveal d2">Xbox Series X/S</div>
            <div class="plat-badge reveal d2">Xbox One</div>
            <div class="plat-badge reveal d3">Nintendo Switch</div>
            <div class="plat-badge reveal d3">PC / Steam</div>
            <div class="plat-badge reveal d4">iOS</div>
            <div class="plat-badge reveal d4">Android</div>
            <div class="plat-badge reveal d5">PlayStation 3</div>
            <div class="plat-badge reveal d5">Game Boy</div>
            <div class="plat-badge reveal d6">+ bien d'autres</div>
        </div>
    </section>

    <!-- CTA -->
    <section id="cta" style="text-align:center;background:var(--bg2);border-top:1px solid var(--border)">
        <div class="cta-glow"></div>
        <div style="position:relative;z-index:1">
            <div class="s-label reveal">Lancement imminent</div>
            <h2 class="s-title reveal d1" style="max-width:560px;margin:0 auto 1rem">
                Prêt à écrire<br><span style="color:var(--g)">ton gaming story ?</span>
            </h2>
            <p class="s-sub reveal d2" style="margin:0 auto">Rejoins les premiers joueurs et sois notifié dès le lancement de l'application.</p>
            <div class="reveal d3" style="margin-top:2.5rem">
                <div class="email-form">
                    <input type="email" placeholder="ton@email.com">
                    <button type="button" id="cta-btn">Rejoindre →</button>
                </div>
                <p class="cta-note">Gratuit. Aucun spam. Désabonnement en un clic.</p>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        <div>
            <div class="f-logo">
                <svg width="100" height="100" viewBox="0 0 452.7 280" xmlns="http://www.w3.org/2000/svg">
                    <path fill="#f2f2f2" d="M286,50.5h-119.1c-34,0-62.4,27-62.9,61c-0.5,34.6,27.5,63,62,63c12.2,0,31.5-6.5,42.6-17c1-1,1.6-2.3,1.6-3.7v-20.8c0-1.7-1.3-3-3-3h-18.1c-1.7,0-3,1.3-3,3v8.8c0,1.8-1,3.6-2.6,4.4c-6.3,3.3-13.6,4.9-21.3,4.1c-18.3-1.8-32.8-16.8-34.1-35.1c-1.6-22.2,16-40.7,37.9-40.7h49h22.1c-5.1,6.5-8.9,14.1-11,22.3c-1.3,5-2,10.2-2,15.6v72h21v-23.1c11,8.6,25,13.5,40.1,13c33.6-1.1,60.4-29.3,59.9-63C347.3,77.7,319.9,50.5,286,50.5z M290.2,150.3c-24.2,2.6-44.5-17.7-41.9-41.9c1.9-17.6,16-31.8,33.7-33.7c24.2-2.6,44.5,17.7,41.9,41.9C322,134.3,307.9,148.4,290.2,150.3z" />
                    <circle fill="#4CE5AE" cx="286" cy="127.5" r="6.5" />
                    <circle fill="#4CE5AE" cx="286" cy="97.5" r="6.5" />
                    <circle fill="#4CE5AE" cx="301" cy="112.5" r="6.5" />
                    <circle fill="#4CE5AE" cx="271" cy="112.5" r="6.5" />
                    <path fill="#f2f2f2" d="M161.5,134h9c1.1,0,2-0.9,2-2v-13h13c1.1,0,2-0.9,2-2v-9c0-1.1-0.9-2-2-2h-13v-13c0-1.1-0.9-2-2-2h-9c-1.1,0-2,0.9-2,2v13h-13c-1.1,0-2,0.9-2,2v9c0,1.1,0.9,2,2,2h13v13C159.5,133.1,160.4,134,161.5,134z" />
                </svg>
                <div>
                    <div class="f-logo-name"><span>G</span>PLAYED</div>
                    <div class="f-tag">YOUR GAMING STORY</div>
                </div>
            </div>
        </div>
        <div class="f-links">
            <a href="#">Confidentialité</a>
            <a href="#">Conditions</a>
            <a href="#">Contact</a>
        </div>
        <div class="f-copy">© 2026 GPlayed. Tous droits réservés.</div>
    </footer>
    <script src="/assets/js/main.js"></script>
</body>

</html>