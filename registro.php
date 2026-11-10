<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (auth_check()) {
    header('Location: dashboard.php');
    exit;
}

$registration = app_config('app.registration', []);
$registrationEnabled = !empty($registration['enabled']);
$requiresCode = $registrationEnabled && (string) ($registration['code'] ?? '') !== '';
$appName = app_config('app.name', 'DentaHistoria');
$clinicName = app_config('app.clinic', 'Clínica Dental');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1a5ab0">
    <?= csrf_meta_tag() ?>
    <title>Crear cuenta · <?= e($appName) ?></title>
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <?php require __DIR__ . '/includes/layout/theme-init.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="login-body">
<button class="btn theme-toggle theme-toggle-floating" type="button" data-theme-toggle aria-label="Cambiar a modo oscuro" title="Cambiar a modo oscuro">
    <i class="bi bi-moon-stars-fill" data-theme-icon aria-hidden="true"></i>
</button>
<div class="register-shell">
    <div class="register-card">
        <a class="brand" href="index.php">
            <span class="brand-mark">
                <img src="assets/img/logo-mark.svg" alt="" width="44" height="44">
            </span>
            <span class="brand-copy">
                <strong><?= e($appName) ?></strong>
                <small><?= e($clinicName) ?></small>
            </span>
        </a>

        <div class="login-heading">
            <span class="login-eyebrow">Registro de usuario</span>
            <h2>Crea tu cuenta</h2>
            <p>Regístrate con tu propio correo y contraseña para acceder a la historia clínica.</p>
        </div>

        <div class="alert alert-danger login-alert d-none" id="registerAlert" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span id="registerAlertText">Ocurrió un error.</span>
        </div>

        <?php if (!$registrationEnabled): ?>
            <div class="alert alert-warning login-alert" role="alert">
                <i class="bi bi-info-circle-fill"></i>
                <span>El registro de nuevas cuentas está deshabilitado. Pide al administrador que cree tu usuario.</span>
            </div>
            <a href="index.php" class="btn btn-primary btn-login">
                <span>Volver al inicio de sesión</span>
                <i class="bi bi-arrow-right"></i>
            </a>
        <?php else: ?>
            <form id="registerForm" class="register-form" novalidate>
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

                <div class="form-field">
                    <label for="nombre">Nombre completo</label>
                    <div class="input-icon">
                        <i class="bi bi-person"></i>
                        <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Dra. Ana Martínez" autocomplete="name" maxlength="120" required>
                    </div>
                    <div class="field-error" id="nombreError"></div>
                </div>

                <div class="form-field">
                    <label for="email">Correo electrónico</label>
                    <div class="input-icon">
                        <i class="bi bi-envelope"></i>
                        <input type="email" class="form-control" id="email" name="email" placeholder="tucorreo@clinica.com" autocomplete="email" required>
                    </div>
                    <div class="field-error" id="emailError"></div>
                </div>

                <div class="form-field">
                    <div class="label-row">
                        <label for="password">Contraseña</label>
                        <span class="secure-note"><i class="bi bi-shield-lock"></i> Mínimo 8 caracteres</span>
                    </div>
                    <div class="input-icon input-icon-action">
                        <i class="bi bi-lock"></i>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Crea una contraseña" autocomplete="new-password" minlength="8" required>
                        <button type="button" class="password-toggle" data-target="password" aria-label="Mostrar contraseña">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="field-error" id="passwordError"></div>
                </div>

                <div class="form-field">
                    <label for="password_confirm">Confirmar contraseña</label>
                    <div class="input-icon input-icon-action">
                        <i class="bi bi-lock-fill"></i>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Repite la contraseña" autocomplete="new-password" minlength="8" required>
                        <button type="button" class="password-toggle" data-target="password_confirm" aria-label="Mostrar contraseña">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="field-error" id="passwordConfirmError"></div>
                </div>

                <?php if ($requiresCode): ?>
                    <div class="form-field">
                        <label for="codigo">Código de invitación</label>
                        <div class="input-icon">
                            <i class="bi bi-key"></i>
                            <input type="text" class="form-control" id="codigo" name="codigo" placeholder="Código entregado por el administrador" autocomplete="off" required>
                        </div>
                        <div class="field-error" id="codigoError"></div>
                        <p class="register-hint">Si eres docente, solicita este código a la persona que te compartió el sistema.</p>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary btn-login" id="registerButton">
                    <span class="btn-label">Crear cuenta</span>
                    <span class="spinner-border spinner-border-sm d-none" id="registerSpinner" aria-hidden="true"></span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <p class="login-switch">¿Ya tienes una cuenta? <a href="index.php">Inicia sesión</a></p>
        <?php endif; ?>

        <p class="login-foot"><i class="bi bi-shield-lock"></i> Tus datos se almacenan de forma segura.</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/js/registro.js"></script>
</body>
</html>
