<?php

/**
 * Conexión PDO única (lazy) a MySQL.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $db = app_config('db');

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $db['host'],
        $db['port'],
        $db['name'],
        $db['charset']
    );

    try {
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 8,
        ]);
    } catch (PDOException $e) {
        error_log('DB connection error: ' . $e->getMessage());
        throw new RuntimeException('No se pudo conectar con la base de datos. Revisa la configuración.');
    }

    return $pdo;
}
