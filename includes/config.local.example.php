<?php
# DentaHistoria · configuración local
#
# Copia este archivo como includes/config.local.php y ajusta los datos de tu
# servidor MySQL. El archivo config.local.php NO se sube a GitHub.
#
# En InfinityFree los datos aparecen en el panel "MySQL Databases":
#   host: sqlXXX.infinityfree.com
#   name: if0_XXXXXXXX_dentahistoria
#   user: if0_XXXXXXXX
#   pass: la contraseña que definiste al crear la base de datos.

return [
    'db' => [
        'host'    => 'sqlXXX.infinityfree.com',
        'port'    => '3306',
        'name'    => 'if0_XXXXXXXX_dentahistoria',
        'user'    => 'if0_XXXXXXXX',
        'pass'    => 'TU_CONTRASENA',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        // Cambia a false en producción para ocultar mensajes técnicos.
        'debug' => false,
        // Oculta el cuadro con las credenciales de demostración en el login.
        'demo'  => false,
        // Registro de nuevas cuentas. Cambia el código antes de publicar.
        'registration' => [
            'enabled' => true,
            'code'    => 'CAMBIA-ESTE-CODIGO',
        ],
    ],
];
