<?php

require_once __DIR__ . '/bootstrap.php';
require_api_login();

$action = api_action();
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$pdo = db();

function patient_nullable($value): ?string
{
    $value = trim((string) $value);
    return $value === '' ? null : $value;
}

function patient_payload(): array
{
    return [
        'nombres'          => trim((string) input('nombres', '')),
        'apellidos'        => trim((string) input('apellidos', '')),
        'documento'        => patient_nullable(input('documento', '')),
        'fecha_nacimiento' => patient_nullable(input('fecha_nacimiento', '')),
        'sexo'             => in_array(input('sexo', 'O'), ['M', 'F', 'O'], true) ? input('sexo', 'O') : 'O',
        'telefono'         => patient_nullable(input('telefono', '')),
        'email'            => patient_nullable(input('email', '')),
        'direccion'        => patient_nullable(input('direccion', '')),
        'ocupacion'        => patient_nullable(input('ocupacion', '')),
        'alergias'         => patient_nullable(input('alergias', '')),
        'antecedentes'     => patient_nullable(input('antecedentes', '')),
        'notas'            => patient_nullable(input('notas', '')),
    ];
}

function validate_patient(array $data, ?int $ignoreId = null): array
{
    $errors = [];

    if (mb_strlen($data['nombres']) < 2) {
        $errors['nombres'] = 'Ingresa al menos 2 caracteres.';
    }
    if (mb_strlen($data['apellidos']) < 2) {
        $errors['apellidos'] = 'Ingresa al menos 2 caracteres.';
    }
    if ($data['fecha_nacimiento'] !== null) {
        if (!valid_date($data['fecha_nacimiento'])) {
            $errors['fecha_nacimiento'] = 'La fecha no es válida.';
        } elseif ($data['fecha_nacimiento'] > date('Y-m-d')) {
            $errors['fecha_nacimiento'] = 'La fecha de nacimiento no puede ser futura.';
        }
    }
    if ($data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'El correo electrónico no es válido.';
    }
    if ($data['documento'] !== null) {
        $sql = 'SELECT id FROM pacientes WHERE documento = ?' . ($ignoreId ? ' AND id <> ?' : '') . ' LIMIT 1';
        $stmt = db()->prepare($sql);
        $params = [$data['documento']];
        if ($ignoreId) {
            $params[] = $ignoreId;
        }
        $stmt->execute($params);
        if ($stmt->fetch()) {
            $errors['documento'] = 'Ya existe un paciente con esta cédula o documento.';
        }
    }
    if ($data['telefono'] !== null && only_digits($data['telefono']) !== '' && mb_strlen(only_digits($data['telefono'])) < 7) {
        $errors['telefono'] = 'El teléfono parece incompleto.';
    }

    return $errors;
}

if ($action === 'list') {
    $q       = trim((string) input('q', ''));
    $sexo    = input('sexo', '');
    $page    = max(1, (int) input('page', 1));
    $perPage = min(50, max(5, (int) input('per_page', 10)));
    $order   = (string) input('orden', 'recientes');
    $offset  = ($page - 1) * $perPage;

    $where = [];
    $params = [];

    if ($q !== '') {
        $where[] = "CONCAT_WS(' ', p.nombres, p.apellidos, p.documento, p.codigo, p.telefono, p.email) LIKE ?";
        $params[] = '%' . $q . '%';
    }
    if (in_array($sexo, ['M', 'F', 'O'], true)) {
        $where[] = 'p.sexo = ?';
        $params[] = $sexo;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $orderMap = [
        'recientes' => 'p.creado_en DESC, p.id DESC',
        'nombre'    => 'p.apellidos ASC, p.nombres ASC',
        'ultima'    => 'ultima_visita DESC, p.apellidos ASC',
        'codigo'    => 'p.codigo ASC',
    ];
    $orderSql = $orderMap[$order] ?? $orderMap['recientes'];

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM pacientes p $whereSql");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $pages = max(1, (int) ceil($total / $perPage));
    if ($page > $pages) {
        $page = $pages;
        $offset = ($page - 1) * $perPage;
    }

    $sql = "SELECT p.id, p.codigo, p.nombres, p.apellidos, p.documento, p.fecha_nacimiento,
                   p.sexo, p.telefono, p.email, p.alergias, p.creado_en,
                   (SELECT COUNT(*) FROM atenciones a WHERE a.paciente_id = p.id) AS total_atenciones,
                   (SELECT MAX(a.fecha) FROM atenciones a WHERE a.paciente_id = p.id AND a.fecha <= CURDATE()) AS ultima_visita,
                   (SELECT MIN(a.proxima_cita) FROM atenciones a WHERE a.paciente_id = p.id AND a.proxima_cita >= CURDATE()) AS proxima_cita
            FROM pacientes p
            $whereSql
            ORDER BY $orderSql
            LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    $stats = [
        'total'         => (int) $pdo->query('SELECT COUNT(*) FROM pacientes')->fetchColumn(),
        'nuevos_mes'    => (int) $pdo->query("SELECT COUNT(*) FROM pacientes WHERE YEAR(creado_en) = YEAR(CURDATE()) AND MONTH(creado_en) = MONTH(CURDATE())")->fetchColumn(),
        'con_alergias'  => (int) $pdo->query("SELECT COUNT(*) FROM pacientes WHERE alergias IS NOT NULL AND TRIM(alergias) <> ''")->fetchColumn(),
    ];

    api_success([
        'items'      => $items,
        'pagination' => [
            'page'     => $page,
            'per_page' => $perPage,
            'total'    => $total,
            'pages'    => $pages,
        ],
        'stats' => $stats,
    ]);
}

if ($action === 'get') {
    $id = (int) input('id', 0);
    $stmt = $pdo->prepare(
        'SELECT p.*,
                (SELECT COUNT(*) FROM atenciones a WHERE a.paciente_id = p.id) AS total_atenciones,
                (SELECT MAX(a.fecha) FROM atenciones a WHERE a.paciente_id = p.id AND a.fecha <= CURDATE()) AS ultima_visita
         FROM pacientes p WHERE p.id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $patient = $stmt->fetch();
    if (!$patient) {
        api_error('El paciente no existe.', 404);
    }
    api_success(['patient' => $patient, 'puede_eliminar' => is_admin()]);
}

if ($action === 'create' && $method === 'POST') {
    $data = patient_payload();
    $errors = validate_patient($data);
    if ($errors) {
        api_error('Revisa los campos marcados.', 422, $errors);
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'INSERT INTO pacientes
             (nombres, apellidos, documento, fecha_nacimiento, sexo, telefono, email, direccion, ocupacion, alergias, antecedentes, notas, creado_por)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['nombres'], $data['apellidos'], $data['documento'], $data['fecha_nacimiento'],
            $data['sexo'], $data['telefono'], $data['email'], $data['direccion'], $data['ocupacion'],
            $data['alergias'], $data['antecedentes'], $data['notas'], current_user()['id'],
        ]);
        $id = (int) $pdo->lastInsertId();
        $codigo = 'PAC-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
        $pdo->prepare('UPDATE pacientes SET codigo = ? WHERE id = ?')->execute([$codigo, $id]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    audit_log('crear', 'paciente', $id, $data['nombres'] . ' ' . $data['apellidos']);

    $stmt = $pdo->prepare('SELECT * FROM pacientes WHERE id = ?');
    $stmt->execute([$id]);

    api_success(['patient' => $stmt->fetch()], 'Paciente registrado correctamente.');
}

if ($action === 'update' && $method === 'POST') {
    $id = (int) input('id', 0);
    $exists = $pdo->prepare('SELECT id FROM pacientes WHERE id = ?');
    $exists->execute([$id]);
    if (!$exists->fetch()) {
        api_error('El paciente no existe.', 404);
    }

    $data = patient_payload();
    $errors = validate_patient($data, $id);
    if ($errors) {
        api_error('Revisa los campos marcados.', 422, $errors);
    }

    $stmt = $pdo->prepare(
        'UPDATE pacientes SET
            nombres = ?, apellidos = ?, documento = ?, fecha_nacimiento = ?, sexo = ?,
            telefono = ?, email = ?, direccion = ?, ocupacion = ?, alergias = ?,
            antecedentes = ?, notas = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $data['nombres'], $data['apellidos'], $data['documento'], $data['fecha_nacimiento'],
        $data['sexo'], $data['telefono'], $data['email'], $data['direccion'], $data['ocupacion'],
        $data['alergias'], $data['antecedentes'], $data['notas'], $id,
    ]);

    audit_log('actualizar', 'paciente', $id, $data['nombres'] . ' ' . $data['apellidos']);

    $stmt = $pdo->prepare('SELECT * FROM pacientes WHERE id = ?');
    $stmt->execute([$id]);

    api_success(['patient' => $stmt->fetch()], 'Datos del paciente actualizados.');
}

if ($action === 'delete' && $method === 'POST') {
    require_admin();
    $id = (int) input('id', 0);
    $stmt = $pdo->prepare('SELECT nombres, apellidos FROM pacientes WHERE id = ?');
    $stmt->execute([$id]);
    $patient = $stmt->fetch();
    if (!$patient) {
        api_error('El paciente no existe.', 404);
    }

    audit_log('eliminar', 'paciente', $id, $patient['nombres'] . ' ' . $patient['apellidos']);
    $pdo->prepare('DELETE FROM pacientes WHERE id = ?')->execute([$id]);

    api_success(null, 'Paciente eliminado junto con su historia clínica.');
}

api_error('Acción no válida.', 404);
