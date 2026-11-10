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

if ($action === 'register') {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        api_error('Método no permitido.', 405);
    }

    $registration = app_config('app.registration', []);
    if (empty($registration['enabled'])) {
        api_error('El registro de nuevas cuentas está deshabilitado.', 403);
    }

    $name            = trim((string) input('nombre', ''));
    $email           = mb_strtolower(trim((string) input('email', '')));
    $password        = (string) input('password', '');
    $passwordConfirm = (string) input('password_confirm', '');
    $code            = trim((string) input('codigo', ''));

    $errors = [];
    if (mb_strlen($name) < 3) {
        $errors['nombre'] = 'Escribe tu nombre completo.';
    } elseif (mb_strlen($name) > 120) {
        $errors['nombre'] = 'El nombre no puede superar los 120 caracteres.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Ingresa un correo electrónico válido.';
    }
    if (mb_strlen($password) < 8) {
        $errors['password'] = 'La contraseña debe tener al menos 8 caracteres.';
    }
    if ($password !== $passwordConfirm) {
        $errors['password_confirm'] = 'Las contraseñas no coinciden.';
    }

    $expectedCode = (string) ($registration['code'] ?? '');
    if ($expectedCode !== '' && !hash_equals($expectedCode, $code)) {
        $errors['codigo'] = 'El código de invitación no es correcto.';
    }

    if ($errors) {
        api_error('Revisa los datos del formulario.', 422, $errors);
    }

    $stmt = db()->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        api_error('Ya existe una cuenta con ese correo.', 422, [
            'email' => 'Ese correo ya está registrado. Inicia sesión.',
        ]);
    }

    try {
        $stmt = db()->prepare(
            'INSERT INTO usuarios (nombre, email, password_hash, rol, activo) VALUES (?, ?, ?, ?, 1)'
        );
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), 'odontologo']);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            api_error('Ya existe una cuenta con ese correo.', 422, [
                'email' => 'Ese correo ya está registrado. Inicia sesión.',
            ]);
        }
        throw $e;
    }

    $userId = (int) db()->lastInsertId();
    $stmt = db()->prepare('SELECT id, nombre, email, rol FROM usuarios WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    auth_login($user);
    audit_log('registrar', 'usuario', $userId, 'Cuenta creada desde el formulario de registro');

    api_success([
        'user'     => current_user(),
        'redirect' => 'dashboard.php',
    ], '¡Cuenta creada! Bienvenido a DentaHistoria.');
}

if ($action === 'session') {
    api_success([
        'authenticated' => auth_check(),
        'user'          => current_user(),
    ]);
}

api_error('Acción no válida.', 404);
