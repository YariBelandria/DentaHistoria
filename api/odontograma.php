<?php

/**
 * Endpoint AJAX del odontograma.
 *
 * GET  ?action=get&paciente_id=1   → estados guardados de las 32 piezas
 * POST ?action=save                 → guarda una pieza
 * POST ?action=reset                → deja el odontograma en estado "sano"
 */

require_once __DIR__ . '/bootstrap.php';
require_api_login();

$action = api_action();
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$pdo = db();

function chart_patient(int $patientId): array
{
    $stmt = db()->prepare('SELECT id, codigo, nombres, apellidos FROM pacientes WHERE id = ? LIMIT 1');
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch();

    if (!$patient) {
        api_error('El paciente no existe.', 404);
    }

    return $patient;
}

function chart_tooth(string $tooth): string
{
    if (!in_array($tooth, dental_tooth_list(), true)) {
        api_error('La pieza dental no es válida.', 422, ['pieza' => 'Selecciona una pieza dental válida.']);
    }

    return $tooth;
}

function chart_state(string $state): string
{
    if (!array_key_exists($state, dental_states())) {
        api_error('El estado de la pieza no es válido.', 422, ['estado' => 'Selecciona un estado válido.']);
    }

    return $state;
}

if ($action === 'get') {
    $patientId = (int) input('paciente_id', 0);
    $patient = chart_patient($patientId);

    $stmt = $pdo->prepare('SELECT pieza, estado, notas, actualizado_en FROM odontograma WHERE paciente_id = ?');
    $stmt->execute([$patientId]);
    $items = $stmt->fetchAll();

    $map = [];
    foreach ($items as $item) {
        $map[$item['pieza']] = [
            'estado'         => $item['estado'],
            'notas'          => $item['notas'],
            'actualizado_en' => $item['actualizado_en'],
        ];
    }

    $counts = array_fill_keys(array_keys(dental_states()), 0);
    foreach ($map as $item) {
        $counts[$item['estado']] = ($counts[$item['estado']] ?? 0) + 1;
    }

    api_success([
        'patient' => $patient,
        'teeth'   => $map,
        'counts'  => $counts,
        'states'  => dental_states(),
    ]);
}

if ($action === 'save' && $method === 'POST') {
    $patientId = (int) input('paciente_id', 0);
    chart_patient($patientId);

    $tooth = chart_tooth(trim((string) input('pieza', '')));
    $state = chart_state(trim((string) input('estado', 'sano')));
    $notes = trim((string) input('notas', ''));

    if (mb_strlen($notes) > 180) {
        api_error('La nota de la pieza no puede superar los 180 caracteres.', 422, [
            'notas' => 'Máximo 180 caracteres.',
        ]);
    }

    if ($state === 'sano' && $notes === '') {
        $pdo->prepare('DELETE FROM odontograma WHERE paciente_id = ? AND pieza = ?')->execute([$patientId, $tooth]);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO odontograma (paciente_id, pieza, estado, notas)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE estado = VALUES(estado), notas = VALUES(notas)'
        );
        $stmt->execute([$patientId, $tooth, $state, $notes !== '' ? $notes : null]);
    }

    audit_log('actualizar', 'odontograma', $patientId, 'Pieza ' . $tooth . ' · ' . (dental_states()[$state] ?? $state));

    $stmt = $pdo->prepare('SELECT pieza, estado, notas, actualizado_en FROM odontograma WHERE paciente_id = ? AND pieza = ? LIMIT 1');
    $stmt->execute([$patientId, $tooth]);

    api_success([
        'pieza'  => $tooth,
        'estado' => $state,
        'notas'  => $notes,
        'row'    => $stmt->fetch() ?: null,
    ], 'Pieza ' . $tooth . ' actualizada en el odontograma.');
}

if ($action === 'reset' && $method === 'POST') {
    $patientId = (int) input('paciente_id', 0);
    $patient = chart_patient($patientId);

    $pdo->prepare('DELETE FROM odontograma WHERE paciente_id = ?')->execute([$patientId]);
    audit_log('actualizar', 'odontograma', $patientId, 'Odontograma restablecido');

    api_success(null, 'El odontograma de ' . $patient['nombres'] . ' se restableció correctamente.');
}

api_error('Acción no válida.', 404);
