<?php
// Déterminer l'onglet actif par défaut en fonction des erreurs URL
$activeTab = 'login'; // Par défaut : Connexion
// Si l'erreur concerne l'inscription (mot de passe faible ou compte existant)
if (isset($_GET['error']) && in_array($_GET['error'], ['weak_password', 'exists', 'register_failed'])) {
    $activeTab = 'register';
}
?>

<div class="row justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="col-md-5 col-xl-3">
        <div class="card shadow-lg border-0 rounded-4 bg-body">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <div class="d-block pb-3 mx-auto w-75 pb-3">
                        <svg id="logo" xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 200 42">
                            <path id="green" d="M98.93,17.73c.81,0,.81.44.81,1.33,0,5.49-3.92,9.41-9.54,9.41s-9.67-3.95-9.67-9.41,4.05-9.41,9.67-9.41c4.18,0,7.79,2.46,8.94,5.93.18.55-.13.89-.68.89h-4.03c-.42,0-.71-.18-.94-.52-.73-1.05-1.86-1.7-3.27-1.7-2.48,0-4.26,2.01-4.26,4.78s1.78,4.78,4.26,4.78c1.67,0,3.08-.86,3.63-2.14h-2.85c-.5,0-.78-.29-.78-.78v-2.38c0-.5.29-.78.78-.78h7.92ZM54.36,21.6c-1.07,0-1.94.87-1.94,1.95s.87,1.95,1.94,1.95,1.94-.87,1.94-1.95-.87-1.95-1.94-1.95ZM54.36,12.62c-1.07,0-1.94.87-1.94,1.95s.87,1.95,1.94,1.95,1.94-.87,1.94-1.95-.87-1.95-1.94-1.95ZM58.84,17.11c-1.07,0-1.94.87-1.94,1.95s.87,1.95,1.94,1.95,1.94-.87,1.94-1.95-.87-1.95-1.94-1.95ZM49.88,17.11c-1.07,0-1.94.87-1.94,1.95s.87,1.95,1.94,1.95,1.94-.87,1.94-1.95-.87-1.95-1.94-1.95Z" />
                            <path id="white" class="svg-adaptive-fill" d="M110.33,9.91c3.84,0,6.64,2.72,6.64,6.48s-2.8,6.46-6.64,6.46h-3.53v4.57c0,.5-.29.78-.78.78h-3.76c-.5,0-.78-.29-.78-.78V10.69c0-.5.29-.78.78-.78h8.08ZM109.72,18.35c1.05,0,1.8-.86,1.8-1.88,0-1.07-.76-1.86-1.8-1.86h-2.93v3.74h2.93ZM130.59,23.5c.5,0,.78.29.78.78v3.14c0,.5-.29.78-.78.78h-10.98c-.5,0-.78-.29-.78-.78V10.69c0-.5.29-.78.78-.78h3.76c.5,0,.78.29.78.78v12.81h6.43ZM152.08,27.32c.18.55-.05.89-.63.89h-4.13c-.44,0-.76-.21-.89-.65l-.73-2.4h-6.01l-.73,2.4c-.13.44-.44.65-.89.65h-4.13c-.58,0-.81-.34-.63-.89l5.91-16.78c.16-.44.47-.63.91-.63h5.12c.44,0,.76.18.91.63l5.91,16.78ZM140.73,21.67h3.92l-1.96-6.56-1.96,6.56ZM166.22,9.91c.63,0,.84.42.52.94l-6.35,10.35v6.22c0,.5-.29.78-.78.78h-3.76c-.5,0-.78-.29-.78-.78v-6.04l-6.46-10.53c-.31-.52-.1-.94.52-.94h4.31c.42,0,.71.18.91.55l3.32,6.27,3.32-6.27c.21-.37.5-.55.91-.55h4.31ZM173.67,14.62v2.17h6.69c.5,0,.78.29.78.78v2.93c0,.5-.29.78-.78.78h-6.69v2.22h7.22c.5,0,.78.29.78.78v3.14c0,.5-.29.78-.78.78h-11.77c-.5,0-.78-.29-.78-.78V10.69c0-.5.29-.78.78-.78h11.77c.5,0,.78.29.78.78v3.14c0,.5-.29.78-.78.78h-7.22ZM190.59,9.91c5.46,0,9.41,3.84,9.41,9.15s-3.95,9.15-9.41,9.15h-6.51c-.5,0-.78-.29-.78-.78V10.69c0-.5.29-.78.78-.78h6.51ZM190.54,23.5c2.33,0,4.03-1.88,4.03-4.47s-1.7-4.42-4.03-4.42h-1.91v8.89h1.91ZM54.36.5H18.8c-10.16,0-18.64,8.09-18.8,18.27-.16,10.36,8.21,18.84,18.52,18.84,4.92,0,9.4-1.93,12.72-5.08.3-.29.48-.68.48-1.1v-3.08h0v-3.14c0-.5-.4-.9-.89-.9h-5.4c-.5,0-.9.39-.9.89v2.64c0,.55-.3,1.06-.78,1.32-1.88.98-4.06,1.45-6.37,1.22-5.45-.54-9.79-5.02-10.18-10.5-.47-6.64,4.79-12.19,11.32-12.19.11,0,.23,0,.34,0h14.28s0,0,0,0h6.61s0,0,0,0c-1.52,1.96-2.66,4.22-3.3,6.68-.39,1.5-.6,3.06-.6,4.68,0,.01,0,.02,0,.03h0v21.51c0,.5.4.9.9.9h5.38c.49,0,.9-.4.9-.9v-6.9c3.29,2.57,7.46,4.05,11.98,3.9,10.04-.34,18.05-8.78,17.89-18.84-.16-10.1-8.4-18.26-18.52-18.26ZM55.6,30.36c-7.22.77-13.28-5.3-12.52-12.54.56-5.27,4.79-9.51,10.05-10.07,7.22-.77,13.28,5.3,12.52,12.54-.56,5.27-4.79,9.51-10.05,10.07ZM17.18,25.49h2.69c.33,0,.6-.27.6-.6v-3.89h3.88c.08,0,.15-.02.22-.04.22-.09.38-.3.38-.56v-2.69c0-.33-.27-.6-.6-.6h-3.88v-3.89c0-.33-.27-.6-.6-.6h-2.69c-.33,0-.6.27-.6.6v3.89h-3.88c-.33,0-.6.27-.6.6v2.69c0,.33.27.6.6.6h3.88v3.89c0,.33.27.6.6.6Z" fill="#fff" />
                        </svg>
                    </div>
                    <p class="fw-light text-secondary">Your Gaming Story</p>
                </div>
                
                <!--<div class="mb-4">
                    <a href="#" class="btn btn-outline-light w-100 rounded-pill py-2 d-flex align-items-center justify-content-center gap-2 border-secondary text-body" onclick="alert('Configuration serveur OAuth requise.')">
                        <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="20" height="20">
                        <span class="fw-bold">Continuer avec Google</span>
                    </a>
                </div>
                <div class="position-relative mb-4 text-center">
                    <hr class="border-secondary opacity-25">
                    <span class="position-absolute top-50 start-50 translate-middle bg-body px-3 text-secondary small">OU</span>
                </div>-->

                <ul class="nav nav-pills nav-fill mb-4 bg-transparent rounded-pill p-1">
                    <li class="nav-item px-1">
                        <button class="nav-link nav-login rounded-pill m-0 <?= $activeTab === 'login' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#login-tab">Connexion</button>
                    </li>
                    <li class="nav-item px-1">
                        <button class="nav-link nav-login rounded-pill m-0 <?= $activeTab === 'register' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#register-tab">Inscription</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade <?= $activeTab === 'login' ? 'show active' : '' ?>" id="login-tab">
                        <form action="index.php?action=login" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <div class="form-floating mb-3">
                                <input type="text" name="username" class="form-control rounded-3" placeholder="Pseudo" required>
                                <label>Utilisateur</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="password" name="password" class="form-control rounded-3" placeholder="Mdp" required>
                                <label>Mot de passe</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold">Se connecter</button>
                        </form>
                        
                        <div class="text-end mt-2">
                            <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal" class="text-decoration-none small text-secondary">Mot de passe oublié ?</a>
                        </div>

                        <?php if(isset($_GET['error']) && $_GET['error'] == 'invalid') echo '<div class="alert alert-danger mt-3 rounded-3 small"><i class="material-icons align-middle fs-5 me-2">&#xe001;</i>Identifiants incorrects</div>'; ?>
                    </div>

                    <div class="tab-pane fade <?= $activeTab === 'register' ? 'show active' : '' ?>" id="register-tab">
                        <form action="index.php?action=register" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <div class="form-floating mb-3">
                                <input type="text" name="username" class="form-control rounded-3" placeholder="Pseudo" required>
                                <label>Choisir un pseudo</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="email" name="email" class="form-control rounded-3" placeholder="Email" required>
                                <label>Adresse Email</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="password" name="password" class="form-control rounded-3" placeholder="Mdp" required>
                                <label>Mot de passe</label>
                                <div class="form-text small mt-2">10 caractères minimum, une majuscule, une minuscule, un chiffre et un caractère spécial.</div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold">Créer compte</button>
                        </form>
                        <?php 
                            if(isset($_GET['error'])) {
                                if($_GET['error'] == 'weak_password') echo '<div class="alert alert-warning mt-3 rounded-3 small">Mot de passe trop faible.</div>';
                                if($_GET['error'] == 'exists') echo '<div class="alert alert-danger mt-3 rounded-3 small">Compte déjà existant.</div>';
                            }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="forgotPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="index.php?action=forgot_password" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold">Réinitialisation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-secondary small">Entrez votre email pour recevoir un lien de réinitialisation.</p>
                    <div class="form-floating mb-3">
                        <input type="email" name="email" class="form-control rounded-3" placeholder="Email" required>
                        <label>Votre adresse Email</label>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="submit" class="btn btn-primary rounded-pill w-100">Envoyer le lien</button>
                </div>
            </form>
        </div>
    </div>
</div>