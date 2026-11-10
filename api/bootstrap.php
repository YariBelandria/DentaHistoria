<?php

/**
 * Arranque común de los endpoints AJAX.
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

set_exception_handler(function (Throwable $e) {
    error_log('API error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    $message = app_config('app.debug')
        ? $e->getMessage()
        : 'Ocurrió un error interno. Intenta nuevamente en un momento.';
    api_error($message, 500);
});

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    require_csrf();
}

function api_action(): string
{
    $action = $_GET['action'] ?? input('action', '');
    return is_string($action) ? $action : '';
}
