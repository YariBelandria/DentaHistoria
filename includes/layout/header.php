<?php
/**
 * Cabecera y barra lateral compartidas.
 *
 * Variables esperadas: $pageTitle, $active y opcionalmente $bodyClass.
 */
$user       = current_user();
$pageTitle  = $pageTitle ?? 'Panel principal';
$active     = $active ?? '';
$bodyClass  = $bodyClass ?? '';
$appName    = app_config('app.name', 'DentaHistoria');
$clinicName = app_config('app.clinic', 'Clínica Dental');
$specialty  = app_config('app.specialty', 'Odontología integral');
$userName   = $user['nombre'] ?? 'Usuario';
$userRole   = ($user['rol'] ?? '') === 'admin' ? 'Administrador' : 'Odontólogo';
$userInitials = initials($userName);

$navItems = [
    ['key' => 'dashboard', 'href' => 'dashboard.php', 'icon' => 'bi-grid-1x2-fill', 'label' => 'Panel principal'],
    ['key' => 'pacientes', 'href' => 'pacientes.php', 'icon' => 'bi-people-fill', 'label' => 'Pacientes'],
    ['key' => 'procedimientos', 'href' => 'procedimientos.php', 'icon' => 'bi-clipboard2-pulse-fill', 'label' => 'Procedimientos'],
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f766e">
    <meta name="description" content="<?= e($appName) ?> · Sistema de gestión de historia clínica odontológica.">
    <?= csrf_meta_tag() ?>
    <title><?= e($pageTitle) ?> · <?= e($appName) ?></title>
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="app-body <?= e($bodyClass) ?>">
<script>
    window.APP_USER = <?= json_encode($user, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    window.APP_CONFIG = {
        clinic: <?= json_encode($clinicName, JSON_UNESCAPED_UNICODE) ?>,
        demo: <?= !empty(app_config('app.demo')) ? 'true' : 'false' ?>
    };
</script>

<div class="app-shell">
    <aside class="sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
        <div class="sidebar-head">
            <a class="brand" href="dashboard.php" id="sidebarLabel">
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
            <button type="button" class="btn-close-sidebar d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Cerrar menú">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="clinic-pill">
            <span class="clinic-icon"><i class="bi bi-hospital"></i></span>
            <span class="clinic-copy">
                <strong><?= e($clinicName) ?></strong>
                <small><?= e($specialty) ?></small>
            </span>
        </div>

        <nav class="sidebar-nav" aria-label="Menú principal">
            <span class="nav-label">Clínica</span>
            <?php foreach ($navItems as $item): ?>
                <a class="sidebar-link <?= $active === $item['key'] ? 'active' : '' ?>" href="<?= e($item['href']) ?>">
                    <i class="bi <?= e($item['icon']) ?>"></i>
                    <span><?= e($item['label']) ?></span>
                    <?php if ($active === $item['key']): ?><span class="active-dot" aria-hidden="true"></span><?php endif; ?>
                </a>
            <?php endforeach; ?>

            <span class="nav-label nav-label-spaced">Accesos rápidos</span>
            <a class="sidebar-link" href="pacientes.php?nuevo=1">
                <i class="bi bi-person-plus"></i>
                <span>Registrar paciente</span>
            </a>
            <a class="sidebar-link" href="procedimientos.php?nuevo=1">
                <i class="bi bi-plus-square"></i>
                <span>Nuevo procedimiento</span>
            </a>
        </nav>

        <div class="sidebar-foot">
            <div class="sidebar-user">
                <span class="avatar avatar-sm"><?= e($userInitials) ?></span>
                <span class="sidebar-user-copy">
                    <strong><?= e($userName) ?></strong>
                    <small><?= e($userRole) ?></small>
                </span>
                <form action="logout.php" method="post" class="ms-auto">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <button type="submit" class="btn-logout" title="Cerrar sesión" aria-label="Cerrar sesión">
                        <i class="bi bi-box-arrow-right"></i>
                    </button>
                </form>
            </div>
            <p class="sidebar-note"><?= e($appName) ?> <span>v1.0</span></p>
        </div>
    </aside>

    <div class="app-main">
        <header class="topbar">
            <button class="btn btn-icon topbar-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Abrir menú">
                <i class="bi bi-list"></i>
            </button>
            <div class="topbar-heading">
                <span class="topbar-eyebrow"><?= e($clinicName) ?></span>
                <h1><?= e($pageTitle) ?></h1>
            </div>
            <div class="topbar-actions">
                <span class="date-chip d-none d-md-inline-flex">
                    <i class="bi bi-calendar3"></i>
                    <?= e(human_date(date('Y-m-d'))) ?>
                </span>
                <div class="dropdown">
                    <button class="btn user-menu" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar avatar-sm"><?= e($userInitials) ?></span>
                        <span class="user-menu-copy d-none d-sm-flex">
                            <strong><?= e($userName) ?></strong>
                            <small><?= e($userRole) ?></small>
                        </span>
                        <i class="bi bi-chevron-down d-none d-sm-block"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end user-dropdown">
                        <li class="dropdown-header">
                            <strong><?= e($userName) ?></strong>
                            <small><?= e($user['email'] ?? '') ?></small>
                        </li>
                        <li><a class="dropdown-item" href="dashboard.php"><i class="bi bi-grid-1x2"></i> Panel principal</a></li>
                        <li><a class="dropdown-item" href="pacientes.php"><i class="bi bi-people"></i> Pacientes</a></li>
                        <li><a class="dropdown-item" href="procedimientos.php"><i class="bi bi-clipboard2-pulse"></i> Procedimientos</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="logout.php" method="post">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="app-content">
