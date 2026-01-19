<div class="row justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="col-md-5">
        <div class="card shadow-lg border-0 rounded-4 bg-body">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <h1 class="fw-bold text-primary"><i class="fas fa-gamepad me-2"></i>GameCol.</h1>
                    <p class="text-secondary">Gérez votre collection simplement</p>
                </div>
                
                <div class="mb-4">
                    <a href="#" class="btn btn-outline-light w-100 rounded-pill py-2 d-flex align-items-center justify-content-center gap-2 border-secondary text-body" onclick="alert('Configuration serveur OAuth requise.')">
                        <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="20" height="20">
                        <span class="fw-bold">Continuer avec Google</span>
                    </a>
                </div>
                <div class="position-relative mb-4 text-center">
                    <hr class="border-secondary opacity-25">
                    <span class="position-absolute top-50 start-50 translate-middle bg-body px-3 text-secondary small">OU</span>
                </div>

                <ul class="nav nav-pills nav-fill mb-4 bg-body-tertiary rounded-pill p-1">
                    <li class="nav-item"><button class="nav-link active rounded-pill" data-bs-toggle="tab" data-bs-target="#login-tab">Connexion</button></li>
                    <li class="nav-item"><button class="nav-link rounded-pill" data-bs-toggle="tab" data-bs-target="#register-tab">Inscription</button></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="login-tab">
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
                        <?php if(isset($_GET['error']) && $_GET['error'] == 'invalid') echo '<div class="alert alert-danger mt-3 rounded-3 small"><i class="fas fa-exclamation-circle me-2"></i>Identifiants incorrects</div>'; ?>
                    </div>
                    <div class="tab-pane fade" id="register-tab">
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
                                <div class="form-text small mt-2">Min 10 caractères, 1 Majuscule, 1 Minuscule, 1 Chiffre, 1 Spécial.</div>
                            </div>
                            <button type="submit" class="btn btn-success w-100 rounded-pill py-2 fw-bold">Créer compte</button>
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