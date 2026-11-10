<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (auth_check()) {
    header('Location: dashboard.php');
    exit;
}

$demo = !empty(app_config('app.demo'));
$registrationEnabled = !empty(app_config('app.registration.enabled'));
$loggedOut = isset($_GET['logout']);
$appName = app_config('app.name', 'DentaHistoria');
$clinicName = app_config('app.clinic', 'Clínica Dental');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f766e">
    <?= csrf_meta_tag() ?>
    <title>Iniciar sesión · <?= e($appName) ?></title>
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="login-body">
<div class="login-shell">
    <section class="login-showcase">
        <div class="showcase-orbit showcase-orbit-1" aria-hidden="true"></div>
        <div class="showcase-orbit showcase-orbit-2" aria-hidden="true"></div>
        <div class="showcase-grid" aria-hidden="true"></div>

        <a class="brand brand-light" href="index.php">
            <span class="brand-mark">
                <svg viewBox="0 0 64 64" aria-hidden="true">
                    <path d="M32 6.5c-7.7 0-11.2 3-17.6 3-3.3 0-6.4 2.3-6.4 7.1 0 7.4 4.2 10.6 5.4 17.7 1 7.5 1.1 19.7 6.1 19.7 4.4 0 4.1-9.3 6.2-14.2 1.2-3.9 2.6-5.8 6.3-5.8s5.1 1.9 6.3 5.8c2.1 4.9 1.8 14.2 6.2 14.2 5 0 5.1-12.2 6.1-19.7 1.2-7.1 5.4-10.3 5.4-17.7 0-4.8-3.1-7.1-6.4-7.1C43.2 9.5 39.7 6.5 32 6.5Z" fill="currentColor"/>
                </svg>
            </span>
            <span class="brand-copy">
                <strong><?= e($appName) ?></strong>
                <small>Historia clínica dental</small>
            </span>
        </a>

        <div class="showcase-content">
            <span class="showcase-badge"><i class="bi bi-stars"></i> Historia clínica digital</span>
            <h1>Cada sonrisa tiene una historia. <span>Cuídala bien.</span></h1>
            <p>Registra pacientes, documenta cada tratamiento y genera historias clínicas en PDF en segundos, desde un solo lugar.</p>

            <ul class="showcase-list">
                <li><span><i class="bi bi-check-lg"></i></span> Ficha clínica completa y segura</li>
                <li><span><i class="bi bi-check-lg"></i></span> Procedimientos y odontograma integrados</li>
                <li><span><i class="bi bi-check-lg"></i></span> Historia clínica en PDF profesional</li>
            </ul>
        </div>

        <div class="showcase-visual" aria-hidden="true">
            <div class="float-card record-card">
                <div class="record-top">
                    <span class="record-icon"><i class="bi bi-file-medical"></i></span>
                    <div>
                        <strong>Historia clínica</strong>
                        <small>Actualizada hoy</small>
                    </div>
                    <span class="record-status"><span></span> Activa</span>
                </div>
                <div class="record-patient">
                    <span class="mini-avatar">MG</span>
                    <div>
                        <strong>María González</strong>
                        <small>PAC-0001 · 34 años</small>
                    </div>
                </div>
                <div class="record-line">
                    <span><i class="bi bi-calendar2-check"></i> 05 nov 2026</span>
                    <span class="mini-badge">Limpieza dental</span>
                </div>
                <div class="record-line">
                    <span><i class="bi bi-shield-check"></i> Sin alergias nuevas</span>
                    <span class="mini-badge mini-badge-blue">Control</span>
                </div>
                <div class="record-progress"><span></span></div>
            </div>

            <div class="float-card tooth-card">
                <div class="tooth-graphic">
                    <svg viewBox="0 0 64 64">
                        <path d="M32 6.5c-7.7 0-11.2 3-17.6 3-3.3 0-6.4 2.3-6.4 7.1 0 7.4 4.2 10.6 5.4 17.7 1 7.5 1.1 19.7 6.1 19.7 4.4 0 4.1-9.3 6.2-14.2 1.2-3.9 2.6-5.8 6.3-5.8s5.1 1.9 6.3 5.8c2.1 4.9 1.8 14.2 6.2 14.2 5 0 5.1-12.2 6.1-19.7 1.2-7.1 5.4-10.3 5.4-17.7 0-4.8-3.1-7.1-6.4-7.1C43.2 9.5 39.7 6.5 32 6.5Z"/>
                    </svg>
                </div>
                <div class="tooth-copy">
                    <strong>Odontograma</strong>
                    <small>Piezas tratadas</small>
                    <div class="tooth-pills"><span>46</span><span>47</span><span>48</span></div>
                </div>
            </div>
        </div>

        <p class="showcase-foot"><i class="bi bi-shield-lock"></i> Tus datos clínicos, siempre protegidos.</p>
    </section>

    <section class="login-panel">
        <div class="login-card">
            <div class="login-mobile-brand">
                <span class="brand-mark">
                    <svg viewBox="0 0 64 64" aria-hidden="true">
                        <path d="M32 6.5c-7.7 0-11.2 3-17.6 3-3.3 0-6.4 2.3-6.4 7.1 0 7.4 4.2 10.6 5.4 17.7 1 7.5 1.1 19.7 6.1 19.7 4.4 0 4.1-9.3 6.2-14.2 1.2-3.9 2.6-5.8 6.3-5.8s5.1 1.9 6.3 5.8c2.1 4.9 1.8 14.2 6.2 14.2 5 0 5.1-12.2 6.1-19.7 1.2-7.1 5.4-10.3 5.4-17.7 0-4.8-3.1-7.1-6.4-7.1C43.2 9.5 39.7 6.5 32 6.5Z" fill="currentColor"/>
                    </svg>
                </span>
                <span class="brand-copy">
                    <strong><?= e($appName) ?></strong>
                    <small><?= e($clinicName) ?></small>
                </span>
            </div>

            <div class="login-heading">
                <span class="login-eyebrow">Acceso al sistema</span>
                <h2>Bienvenido de nuevo</h2>
                <p>Ingresa tus credenciales para continuar.</p>
            </div>

            <?php if ($loggedOut): ?>
                <div class="alert alert-success login-alert" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Has cerrado sesión correctamente. ¡Hasta pronto!</span>
                </div>
            <?php endif; ?>

            <div class="alert alert-danger login-alert d-none" id="loginAlert" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span id="loginAlertText">Ocurrió un error.</span>
            </div>

            <form id="loginForm" novalidate>
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

                <div class="form-field">
                    <label for="email">Correo electrónico</label>
                    <div class="input-icon">
                        <i class="bi bi-envelope"></i>
                        <input type="email" class="form-control" id="email" name="email" placeholder="tucorreo@clinica.com" autocomplete="username" required>
                    </div>
                    <div class="field-error" id="emailError"></div>
                </div>

                <div class="form-field">
                    <div class="label-row">
                        <label for="password">Contraseña</label>
                        <span class="secure-note"><i class="bi bi-shield-lock"></i> Segura</span>
                    </div>
                    <div class="input-icon input-icon-action">
                        <i class="bi bi-lock"></i>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Tu contraseña" autocomplete="current-password" required>
                        <button type="button" class="password-toggle" id="togglePassword" aria-label="Mostrar contraseña">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="field-error" id="passwordError"></div>
                </div>

                <button type="submit" class="btn btn-primary btn-login" id="loginButton">
                    <span class="btn-label">Iniciar sesión</span>
                    <span class="spinner-border spinner-border-sm d-none" id="loginSpinner" aria-hidden="true"></span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <?php if ($registrationEnabled): ?>
                <p class="login-switch">¿No tienes cuenta? <a href="registro.php">Crear una cuenta</a></p>
            <?php endif; ?>

            <?php if ($demo): ?>
                <div class="demo-box">
                    <div class="demo-head">
                        <span><i class="bi bi-info-circle"></i> Acceso de demostración</span>
                        <small>Haz clic para completar</small>
                    </div>
                    <div class="demo-accounts">
                        <button type="button" class="demo-account" data-email="admin@dentahistoria.com" data-password="Admin123!">
                            <span class="demo-avatar demo-avatar-admin">AD</span>
                            <span class="demo-copy">
                                <strong>Administrador</strong>
                                <small>admin@dentahistoria.com</small>
                            </span>
                            <i class="bi bi-arrow-up-right"></i>
                        </button>
                        <button type="button" class="demo-account" data-email="valentina@dentahistoria.com" data-password="Odonto2026!">
                            <span class="demo-avatar">VR</span>
                            <span class="demo-copy">
                                <strong>Odontóloga</strong>
                                <small>valentina@dentahistoria.com</small>
                            </span>
                            <i class="bi bi-arrow-up-right"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <p class="login-foot"><i class="bi bi-life-preserver"></i> ¿Problemas para ingresar? Contacta al administrador del sistema.</p>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/js/login.js"></script>
</body>
</html>
