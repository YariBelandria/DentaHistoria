<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

if (!csrf_validate($_POST['_csrf'] ?? null)) {
    http_response_code(419);
    exit('La sesión de seguridad expiró. Vuelve a intentarlo.');
}

if (auth_check()) {
    audit_log('logout', 'sesion', current_user()['id']);
}
auth_logout();

header('Location: index.php?logout=1');
exit;
