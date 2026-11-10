<?php

require_once __DIR__ . '/bootstrap.php';
require_api_login();

$action = api_action();
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$pdo = db();

function record_can_edit(array $record): bool
{
    return is_admin() || (int) $record['usuario_id'] === (int) (current_user()['id'] ?? 0);
}

function normalize_teeth($value): array
{
    $items = is_array($value) ? $value : explode(',', (string) $value);
    $teeth = [];
    foreach ($items as $item) {
        $tooth = trim((string) $item);
        if ($tooth !== '' && preg_match('/^(?:[1-4][1-8]|[5-8][1-5])$/', $tooth)) {
            $teeth[$tooth] = $tooth;
        }
    }
    $teeth = array_values($teeth);
    sort($teeth, SORT_NATURAL);
    return $teeth;
}

function record_payload(): array
{
    return [
        'fecha'         => trim((string) input('fecha', date('Y-m-d'))),
        'hora'          => trim((string) input('hora', '')),
        'motivo'        => trim((string) input('motivo', '')),
        'diagnostico'   => trim((string) input('diagnostico', '')),
        'tratamiento'   => trim((string) input('tratamiento', '')),
        'observaciones' => trim((string) input('observaciones', '')),
        'estado'        => in_array(input('estado', 'realizado'), ['realizado', 'pendiente', 'seguimiento'], true)
            ? input('estado', 'realizado')
            : 'realizado',
        'proxima_cita'  => trim((string) input('proxima_cita', '')),
        'dientes'       => normalize_teeth(input('dientes', [])),
        'procedimientos' => array_values(array_unique(array_map('intval', input_array('procedimientos')))),
    ];
}

function validate_record(array $data, ?int $patientId = null): array
{
    $errors = [];

    if ($patientId !== null) {
        $stmt = db()->prepare('SELECT id FROM pacientes WHERE id = ?');
        $stmt->execute([$patientId]);
        if (!$stmt->fetch()) {
            $errors['paciente_id'] = 'El paciente no existe.';
        }
    }

    if (!valid_date($data['fecha'])) {
        $errors['fecha'] = 'Selecciona una fecha válida.';
    } elseif ($data['fecha'] > date('Y-m-d', strtotime('+365 days'))) {
        $errors['fecha'] = 'La fecha no puede superar un año desde hoy.';
    }

    if ($data['hora'] !== '' && !preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $data['hora'])) {
        $errors['hora'] = 'La hora no es válida.';
    }

    if (mb_strlen($data['motivo']) < 3) {
        $errors['motivo'] = 'Describe brevemente el motivo de la consulta.';
    } elseif (mb_strlen($data['motivo']) > 255) {
        $errors['motivo'] = 'El motivo no puede superar los 255 caracteres.';
    }

    foreach (['diagnostico' => 5000, 'tratamiento' => 5000, 'observaciones' => 5000] as $field => $limit) {
        if (mb_strlen($data[$field]) > $limit) {
            $errors[$field] = "El texto no puede superar los {$limit} caracteres.";
        }
    }

    if ($data['proxima_cita'] !== '') {
        if (!valid_date($data['proxima_cita'])) {
            $errors['proxima_cita'] = 'La fecha de próxima cita no es válida.';
        } elseif ($data['proxima_cita'] < $data['fecha']) {
            $errors['proxima_cita'] = 'La próxima cita debe ser igual o posterior a la fecha de la atención.';
        }
    }

    if ($data['procedimientos']) {
        $ids = array_filter($data['procedimientos'], fn ($id) => $id > 0);
        if ($ids) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = db()->prepare("SELECT COUNT(*) FROM procedimientos WHERE id IN ($placeholders)");
            $stmt->execute(array_values($ids));
            if ((int) $stmt->fetchColumn() !== count($ids)) {
                $errors['procedimientos'] = 'Uno de los procedimientos seleccionados no es válido.';
            }
        } else {
            $data['procedimientos'] = [];
        }
    }

    return $errors;
}

function attach_procedures(PDO $pdo, int $recordId, array $procedureIds): void
{
    $pdo->prepare('DELETE FROM atencion_procedimientos WHERE atencion_id = ?')->execute([$recordId]);

    $procedureIds = array_values(array_filter(array_unique(array_map('intval', $procedureIds)), fn ($id) => $id > 0));
    if (!$procedureIds) {
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO atencion_procedimientos (atencion_id, procedimiento_id) VALUES (?, ?)');
    foreach ($procedureIds as $procedureId) {
        $stmt->execute([$recordId, $procedureId]);
    }
}

if ($action === 'list') {
    $patientId = (int) input('paciente_id', 0);
    if ($patientId <= 0) {
        api_error('Paciente no válido.', 422);
    }

    $patientStmt = $pdo->prepare('SELECT id, nombres, apellidos, codigo, alergias, antecedentes, fecha_nacimiento, sexo, telefono, email FROM pacientes WHERE id = ?');
    $patientStmt->execute([$patientId]);
    $patient = $patientStmt->fetch();
    if (!$patient) {
        api_error('El paciente no existe.', 404);
    }

    $sql = "SELECT a.*, u.nombre AS odontologo,
                   (SELECT GROUP_CONCAT(pr.nombre ORDER BY pr.nombre SEPARATOR '||')
                    FROM atencion_procedimientos ap
                    JOIN procedimientos pr ON pr.id = ap.procedimiento_id
                    WHERE ap.atencion_id = a.id) AS procedimientos,
                   (SELECT GROUP_CONCAT(pr.id ORDER BY pr.id)
                    FROM atencion_procedimientos ap
                    JOIN procedimientos pr ON pr.id = ap.procedimiento_id
                    WHERE ap.atencion_id = a.id) AS procedimiento_ids
            FROM atenciones a
            LEFT JOIN usuarios u ON u.id = a.usuario_id
            WHERE a.paciente_id = ?
            ORDER BY a.fecha DESC, a.id DESC
            LIMIT 300";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$patientId]);
    $records = $stmt->fetchAll();

    foreach ($records as &$record) {
        $record['puede_editar'] = record_can_edit($record);
        $record['procedimientos_lista'] = $record['procedimientos'] ? explode('||', $record['procedimientos']) : [];
        $record['procedimiento_ids'] = $record['procedimiento_ids']
            ? array_map('intval', explode(',', $record['procedimiento_ids']))
            : [];
    }
    unset($record);

    $summaryStmt = $pdo->prepare(
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN estado = 'realizado' THEN 1 ELSE 0 END) AS realizadas,
            SUM(CASE WHEN estado <> 'realizado' THEN 1 ELSE 0 END) AS pendientes,
            MAX(CASE WHEN fecha <= CURDATE() THEN fecha END) AS ultima_visita,
            MIN(CASE WHEN proxima_cita >= CURDATE() THEN proxima_cita END) AS proxima_cita,
            COUNT(DISTINCT dientes) AS notas_dentales
         FROM atenciones WHERE paciente_id = ?"
    );
    $summaryStmt->execute([$patientId]);
    $summary = $summaryStmt->fetch();

    $procStmt = $pdo->prepare(
        'SELECT COUNT(DISTINCT ap.procedimiento_id)
         FROM atencion_procedimientos ap
         JOIN atenciones a ON a.id = ap.atencion_id
         WHERE a.paciente_id = ?'
    );
    $procStmt->execute([$patientId]);
    $summary['procedimientos_distintos'] = (int) $procStmt->fetchColumn();

    api_success([
        'patient' => $patient,
        'records' => $records,
        'summary' => $summary,
        'puede_eliminar' => is_admin(),
    ]);
}

if ($action === 'get') {
    $id = (int) input('id', 0);
    $stmt = $pdo->prepare(
        "SELECT a.*,
                (SELECT GROUP_CONCAT(pr.id ORDER BY pr.id)
                 FROM atencion_procedimientos ap
                 JOIN procedimientos pr ON pr.id = ap.procedimiento_id
                 WHERE ap.atencion_id = a.id) AS procedimiento_ids
         FROM atenciones a WHERE a.id = ? LIMIT 1"
    );
    $stmt->execute([$id]);
    $record = $stmt->fetch();
    if (!$record) {
        api_error('La atención no existe.', 404);
    }
    if (!record_can_edit($record)) {
        api_error('No tienes permisos para editar esta atención.', 403);
    }
    $record['procedimiento_ids'] = $record['procedimiento_ids']
        ? array_map('intval', explode(',', $record['procedimiento_ids']))
        : [];
    api_success(['record' => $record]);
}

if ($action === 'create' && $method === 'POST') {
    $patientId = (int) input('paciente_id', 0);
    $data = record_payload();
    $errors = validate_record($data, $patientId);
    if ($errors) {
        api_error('Revisa los campos marcados.', 422, $errors);
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'INSERT INTO atenciones
             (paciente_id, usuario_id, fecha, hora, motivo, diagnostico, tratamiento, observaciones, dientes, estado, proxima_cita)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $patientId,
            current_user()['id'],
            $data['fecha'],
            $data['hora'] !== '' ? $data['hora'] . ':00' : null,
            $data['motivo'],
            $data['diagnostico'] ?: null,
            $data['tratamiento'] ?: null,
            $data['observaciones'] ?: null,
            $data['dientes'] ? implode(',', $data['dientes']) : null,
            $data['estado'],
            $data['proxima_cita'] !== '' ? $data['proxima_cita'] : null,
        ]);
        $id = (int) $pdo->lastInsertId();
        attach_procedures($pdo, $id, $data['procedimientos']);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    audit_log('crear', 'atencion', $id, 'Atención del paciente #' . $patientId);

    api_success(['id' => $id], 'Atención registrada en la historia clínica.');
}

if ($action === 'update' && $method === 'POST') {
    $id = (int) input('id', 0);
    $stmt = $pdo->prepare('SELECT * FROM atenciones WHERE id = ?');
    $stmt->execute([$id]);
    $record = $stmt->fetch();
    if (!$record) {
        api_error('La atención no existe.', 404);
    }
    if (!record_can_edit($record)) {
        api_error('No tienes permisos para editar esta atención.', 403);
    }

    $data = record_payload();
    $errors = validate_record($data);
    if ($errors) {
        api_error('Revisa los campos marcados.', 422, $errors);
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'UPDATE atenciones SET
                fecha = ?, hora = ?, motivo = ?, diagnostico = ?, tratamiento = ?,
                observaciones = ?, dientes = ?, estado = ?, proxima_cita = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['fecha'],
            $data['hora'] !== '' ? $data['hora'] . ':00' : null,
            $data['motivo'],
            $data['diagnostico'] ?: null,
            $data['tratamiento'] ?: null,
            $data['observaciones'] ?: null,
            $data['dientes'] ? implode(',', $data['dientes']) : null,
            $data['estado'],
            $data['proxima_cita'] !== '' ? $data['proxima_cita'] : null,
            $id,
        ]);
        attach_procedures($pdo, $id, $data['procedimientos']);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    audit_log('actualizar', 'atencion', $id, 'Atención actualizada');

    api_success(['id' => $id], 'Atención actualizada correctamente.');
}

if ($action === 'delete' && $method === 'POST') {
    $id = (int) input('id', 0);
    $stmt = $pdo->prepare('SELECT * FROM atenciones WHERE id = ?');
    $stmt->execute([$id]);
    $record = $stmt->fetch();
    if (!$record) {
        api_error('La atención no existe.', 404);
    }
    if (!record_can_edit($record)) {
        api_error('No tienes permisos para eliminar esta atención.', 403);
    }

    audit_log('eliminar', 'atencion', $id, 'Atención del paciente #' . $record['paciente_id']);
    $pdo->prepare('DELETE FROM atenciones WHERE id = ?')->execute([$id]);

    api_success(null, 'Atención eliminada de la historia clínica.');
}

api_error('Acción no válida.', 404);
