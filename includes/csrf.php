<?php

/**
 * Protección CSRF para formularios y peticiones AJAX.
 */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_validate(?string $token): bool
{
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function request_csrf_token(): ?string
{
    $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if ($header) {
        return $header;
    }

    $token = $_POST['_csrf'] ?? null;
    if ($token) {
        return is_string($token) ? $token : null;
    }

    $json = request_json();
    return isset($json['_csrf']) && is_string($json['_csrf']) ? $json['_csrf'] : null;
}

function require_csrf(): void
{
    if (!csrf_validate(request_csrf_token())) {
        api_error('La sesión de seguridad expiró. Recarga la página e intenta de nuevo.', 419);
    }
}
