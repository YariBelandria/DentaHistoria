<?php

require_once __DIR__ . '/bootstrap.php';

$action = api_action();

if ($action === 'login') {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        api_error('Método no permitido.', 405);
    }

    $email    = mb_strtolower(trim((string) input('email', '')));
    $password = (string) input('password', '');

    if ($email === '' || $password === '') {
        api_error('Ingresa tu correo y contraseña.', 422);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        api_error('Ingresa un correo electrónico válido.', 422);
    }

    $result = auth_attempt($email, $password);
    if (!$result['ok']) {
        api_error($result['message'], 422);
    }

    try {
        $stmt = db()->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?');
        $stmt->execute([$result['user']['id']]);
    } catch (Throwable $e) {
        error_log('No se pudo actualizar ultimo_acceso: ' . $e->getMessage());
    }

    api_success(['user' => $result['user'], 'redirect' => 'dashboard.php'], '¡Bienvenido de nuevo!');
}

if ($action === 'session') {
    api_success([
        'authenticated' => auth_check(),
        'user'          => current_user(),
    ]);
}

api_error('Acción no válida.', 404);
