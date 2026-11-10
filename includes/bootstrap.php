<?php

/**
 * Arranque común para páginas y endpoints AJAX.
 */

$CONFIG = require __DIR__ . '/config.php';

date_default_timezone_set($CONFIG['app']['timezone'] ?? 'America/Caracas');

$debug = !empty($CONFIG['app']['debug']);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

if (session_status() === PHP_SESSION_NONE) {
    session_name('DENTAHISTORIA_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

require_once __DIR__ . '/compat.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

function app_config(?string $path = null, $default = null)
{
    global $CONFIG;

    if ($path === null || $path === '') {
        return $CONFIG;
    }

    $value = $CONFIG;
    foreach (explode('.', $path) as $key) {
        if (!is_array($value) || !array_key_exists($key, $value)) {
            return $default;
        }
        $value = $value[$key];
    }

    return $value;
}

function app_url(string $path = ''): string
{
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $base = rtrim($script, '/');

    // Cuando se ejecuta desde /api o /pdf, subimos un nivel.
    if (preg_match('#/(api|pdf)$#', $base)) {
        $base = dirname($base);
    }

    if ($base === '/' || $base === '.' || $base === '\\') {
        $base = '';
    }

    return $base . '/' . ltrim($path, '/');
}
