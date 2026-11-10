<?php
/**
 * Configuración general de DentaHistoria.
 *
 * Para pruebas locales puedes definir las variables de entorno DB_HOST, DB_PORT,
 * DB_NAME, DB_USER y DB_PASS. En un hosting compartido edita directamente los
 * valores de la sección "db".
 */

$config = [
    'app' => [
        'name'        => 'DentaHistoria',
        'clinic'      => 'Clínica Dental Sonrisa',
        'doctor'      => 'Dra. Valentina Ríos',
        'specialty'   => 'Odontología integral',
        'phone'       => '+58 412 555-0182',
        'email'       => 'contacto@dentahistoria.com',
        'address'     => 'Av. Libertador, Centro Médico Vital, Piso 2, Caracas',
        'timezone'    => 'America/Caracas',
        'demo'        => true,
        'debug'       => getenv('APP_DEBUG') === '1',
        // Registro público de odontólogos. Cambia el código en config.local.php.
        'registration' => [
            'enabled' => getenv('REGISTRATION_ENABLED') !== '0',
            'code'    => getenv('REGISTRATION_CODE') ?: 'DENTA-2026',
        ],
    ],
    'db' => [
        'host'    => getenv('DB_HOST') ?: '127.0.0.1',
        'port'    => getenv('DB_PORT') ?: '3306',
        'name'    => getenv('DB_NAME') ?: 'dentahistoria',
        'user'    => getenv('DB_USER') ?: 'root',
        'pass'    => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
        'charset' => 'utf8mb4',
    ],
];

// Permite sobreescribir la configuración sin ensuciar el archivo versionado.
$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) {
    $overrides = require $localConfig;
    if (is_array($overrides)) {
        $config = array_replace_recursive($config, $overrides);
    }
}

return $config;
