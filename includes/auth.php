<?php

/**
 * Autenticación basada en sesiones.
 */

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function auth_check(): bool
{
    return !empty($_SESSION['user']['id']);
}

function auth_login(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'     => (int) $user['id'],
        'nombre' => $user['nombre'],
        'email'  => $user['email'],
        'rol'    => $user['rol'],
    ];
    $_SESSION['login_at'] = time();
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function is_admin(): bool
{
    return (current_user()['rol'] ?? '') === 'admin';
}

function require_login(): void
{
    if (!auth_check()) {
        // app_url() resuelve la raíz también desde subcarpetas como /pdf o /api.
        header('Location: ' . app_url('index.php'));
        exit;
    }
}

function require_api_login(): void
{
    if (!auth_check()) {
        api_error('Tu sesión expiró. Inicia sesión nuevamente.', 401);
    }
}

function require_admin(): void
{
    if (!is_admin()) {
        api_error('No tienes permisos para realizar esta acción.', 403);
    }
}

/**
 * Comprueba credenciales con protección básica contra fuerza bruta en sesión.
 */
function auth_attempt(string $email, string $password): array
{
    $key = 'login_attempts';
    $attempts = $_SESSION[$key] ?? ['count' => 0, 'last' => 0];

    if ($attempts['count'] >= 5 && (time() - $attempts['last']) < 60) {
        return ['ok' => false, 'message' => 'Demasiados intentos. Espera un minuto antes de volver a intentar.'];
    }

    $stmt = db()->prepare('SELECT id, nombre, email, password_hash, rol, activo FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([mb_strtolower($email)]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $_SESSION[$key] = [
            'count' => ($attempts['count'] ?? 0) + 1,
            'last'  => time(),
        ];
        return ['ok' => false, 'message' => 'Correo o contraseña incorrectos.'];
    }

    if ((int) $user['activo'] !== 1) {
        return ['ok' => false, 'message' => 'Tu cuenta está desactivada. Contacta al administrador.'];
    }

    unset($_SESSION[$key]);
    auth_login($user);

    return ['ok' => true, 'user' => current_user()];
}
