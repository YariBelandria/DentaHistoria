# Documentación técnica · DentaHistoria

## 1. Descripción general

DentaHistoria es una aplicación web monolítica en PHP que usa MySQL como base de datos, peticiones AJAX para las operaciones dinámicas y Bootstrap 5 para la interfaz. No usa frameworks ni requiere Composer: FPDF está incluida en `vendor/`.

## 2. Tecnologías

| Componente | Tecnología |
| --- | --- |
| Servidor | PHP 8.0 o superior |
| Base de datos | MySQL 5.7+ / MariaDB 10.3+ |
| Acceso a datos | PDO con consultas preparadas |
| Interfaz | Bootstrap 5.3.3, Bootstrap Icons, CSS propio |
| AJAX | JavaScript nativo con `fetch` y `FormData` |
| Gráficos | Chart.js 4 |
| PDF | FPDF 1.9 |
| Tipografía | Plus Jakarta Sans |

## 3. Estructura de archivos

```text
api/                    Endpoints JSON (auth, dashboard, pacientes, atenciones, procedimientos, odontograma)
assets/css/app.css      Estilos de la aplicación
assets/js/              Utilidades y lógica por pantalla
database/schema.sql     Creación de tablas
database/seed.sql       Usuarios y datos de demostración
docs/                   Manual de usuario, documentación técnica y capturas
includes/               Configuración, conexión, autenticación, CSRF, funciones y layout
pdf/historia.php        Generación de la historia clínica en PDF
vendor/fpdf/            Librería FPDF
index.php               Inicio de sesión
dashboard.php           Panel principal
pacientes.php           Listado y formulario de pacientes
paciente.php            Ficha e historia clínica del paciente
procedimientos.php      Catálogo de procedimientos
logout.php              Cierre de sesión
```

## 4. Modelo de datos

| Tabla | Propósito |
| --- | --- |
| `usuarios` | Cuentas del sistema, rol (`admin`/`odontologo`), estado y último acceso. |
| `pacientes` | Datos personales y clínicos del paciente. Código único `PAC-XXXX`. |
| `procedimientos` | Catálogo de procedimientos con precio, duración y estado activo. |
| `atenciones` | Cada consulta: fecha, hora, motivo, diagnóstico, tratamiento, observaciones, piezas y próxima cita. |
| `atencion_procedimientos` | Relación muchos-a-muchos entre atenciones y procedimientos. |
| `odontograma` | Estado actual por pieza dental y paciente, con nota clínica. Clave única `(paciente_id, pieza)`. |
| `auditoria` | Bitácora de acciones relevantes con usuario, entidad, detalle e IP. |

### Relaciones principales

- Un paciente tiene muchas atenciones (`atenciones.paciente_id`).
- Un paciente tiene hasta 32 filas de odontograma (`odontograma.paciente_id`).
- Una atención puede tener varios procedimientos y un procedimiento puede aparecer en varias atenciones.
- Al eliminar un paciente se eliminan en cascada sus atenciones y su odontograma.
- Al eliminar un procedimiento con atenciones asociadas, la API lo desactiva en lugar de borrarlo.

## 5. Endpoints AJAX

Todas las respuestas usan el formato:

```json
{ "ok": true, "data": {}, "message": "" }
```

| Endpoint | Acciones |
| --- | --- |
| `api/auth.php` | `login`, `session` |
| `api/dashboard.php` | `stats` |
| `api/pacientes.php` | `list`, `get`, `save`, `delete` |
| `api/atenciones.php` | `list`, `get`, `save`, `delete` |
| `api/procedimientos.php` | `list`, `get`, `save`, `toggle`, `delete` |
| `api/odontograma.php` | `get`, `save`, `reset` |

Las peticiones `POST` requieren el token CSRF enviado en la cabecera `X-CSRF-Token`.

## 6. Seguridad

- Contraseñas con `password_hash` (bcrypt) y verificación con `password_verify`.
- Consultas preparadas en todas las operaciones con PDO.
- Escape de salida con `htmlspecialchars` y `App.escapeHtml`.
- Tokens CSRF por sesión para formularios y AJAX.
- Cookies de sesión `HttpOnly`, `SameSite=Lax` y `Secure` cuando hay HTTPS.
- Validación de sesión en cada página y endpoint mediante `require_login()`.
- Permisos por rol: solo el administrador elimina pacientes; cada odontólogo edita sus atenciones.
- Límite básico de intentos de inicio de sesión.
- `.htaccess` bloquea el acceso directo a `includes/`, `database/` y `vendor/`.
- Registro de auditoría de acciones principales.

## 7. Generación del PDF

`pdf/historia.php` consulta al paciente, sus atenciones, el resumen y el odontograma. Con FPDF construye un documento A4 con:

- Encabezado con datos de la clínica y código del paciente.
- Ficha del paciente y datos clínicos.
- Resumen de atenciones, última visita, próxima cita y procedimientos.
- Odontograma de 32 piezas con colores por estado y hallazgos.
- Detalle de cada atención.
- Pie de página con numeración y espacio de firma.

El texto se convierte de UTF-8 a Windows-1252 para que las fuentes base de FPDF muestren correctamente los acentos.

## 8. Configuración

La configuración está en `includes/config.php`. Se puede sobreescribir sin modificar el archivo versionado:

1. Copia `includes/config.local.example.php` como `includes/config.local.php`.
2. Ajusta los datos de conexión.
3. Opcionalmente define `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASS` como variables de entorno.

En producción configura:

```php
'app' => [
    'demo'  => false,
    'debug' => false,
],
```

## 9. Instalación local

```bash
mysql -u root -p -e "CREATE DATABASE dentahistoria CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p dentahistoria < database/schema.sql
mysql -u root -p dentahistoria < database/seed.sql
cp includes/config.local.example.php includes/config.local.php
php -S localhost:8000 -t .
```

Abre `http://localhost:8000/`.

## 10. Despliegue en InfinityFree

1. Crea una cuenta y un hosting con subdominio gratuito.
2. Crea una base de datos MySQL y anota host, nombre, usuario y contraseña.
3. Importa `database/schema.sql` y, si quieres datos de demostración, `database/seed.sql` desde phpMyAdmin.
4. Sube el proyecto a `htdocs` manteniendo la estructura.
5. Crea `includes/config.local.php` con los datos reales y `demo`/`debug` en `false`.
6. Selecciona PHP 8.2 o 8.3 en el panel.
7. Abre el subdominio y prueba el inicio de sesión y la descarga del PDF.

## 11. Mantenimiento

- Respalda la base de datos con `mysqldump` antes de cada actualización.
- Revisa la tabla `auditoria` para detectar accesos o cambios inusuales.
- Cambia las contraseñas de demostración antes de usar datos reales.
- No subas `includes/config.local.php` al repositorio.
- Conserva la carpeta `vendor/fpdf/font/` completa: es necesaria para generar PDF.
