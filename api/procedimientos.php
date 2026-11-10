<?php

require_once __DIR__ . '/bootstrap.php';
require_api_login();

$action = api_action();
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$pdo = db();

function procedure_payload(): array
{
    return [
        'nombre'       => trim((string) input('nombre', '')),
        'categoria'    => trim((string) input('categoria', 'General')),
        'descripcion'  => trim((string) input('descripcion', '')),
        'precio'       => input('precio', 0),
        'duracion_min' => (int) input('duracion_min', 30),
        'activo'       => (int) input('activo', 1) === 1 ? 1 : 0,
    ];
}

function validate_procedure(array $data, ?int $ignoreId = null): array
{
    $errors = [];

    if (mb_strlen($data['nombre']) < 3) {
        $errors['nombre'] = 'El nombre debe tener al menos 3 caracteres.';
    } elseif (mb_strlen($data['nombre']) > 150) {
        $errors['nombre'] = 'El nombre no puede superar los 150 caracteres.';
    }

    if (!in_array($data['categoria'], procedure_categories(), true)) {
        $errors['categoria'] = 'Selecciona una categoría válida.';
    }

    if (!is_numeric($data['precio']) || (float) $data['precio'] < 0) {
        $errors['precio'] = 'Ingresa un precio válido.';
    } elseif ((float) $data['precio'] > 100000) {
        $errors['precio'] = 'El precio ingresado es demasiado alto.';
    }

    if ($data['duracion_min'] < 5 || $data['duracion_min'] > 480) {
        $errors['duracion_min'] = 'La duración debe estar entre 5 y 480 minutos.';
    }

    $sql = 'SELECT id FROM procedimientos WHERE LOWER(nombre) = LOWER(?)' . ($ignoreId ? ' AND id <> ?' : '') . ' LIMIT 1';
    $stmt = db()->prepare($sql);
    $params = [$data['nombre']];
    if ($ignoreId) {
        $params[] = $ignoreId;
    }
    $stmt->execute($params);
    if ($stmt->fetch()) {
        $errors['nombre'] = 'Ya existe un procedimiento con ese nombre.';
    }

    return $errors;
}

if ($action === 'list') {
    $q         = trim((string) input('q', ''));
    $categoria = trim((string) input('categoria', ''));
    $estado    = trim((string) input('estado', ''));

    $where = [];
    $params = [];

    if ($q !== '') {
        $where[] = '(pr.nombre LIKE ? OR pr.descripcion LIKE ?)';
        $params[] = '%' . $q . '%';
        $params[] = '%' . $q . '%';
    }
    if (in_array($categoria, procedure_categories(), true)) {
        $where[] = 'pr.categoria = ?';
        $params[] = $categoria;
    }
    if ($estado === 'activos') {
        $where[] = 'pr.activo = 1';
    } elseif ($estado === 'inactivos') {
        $where[] = 'pr.activo = 0';
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $sql = "SELECT pr.*,
                   (SELECT COUNT(*) FROM atencion_procedimientos ap WHERE ap.procedimiento_id = pr.id) AS usos
            FROM procedimientos pr
            $whereSql
            ORDER BY pr.activo DESC, pr.categoria ASC, pr.nombre ASC
            LIMIT 300";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    $stats = [
        'total'     => (int) $pdo->query('SELECT COUNT(*) FROM procedimientos')->fetchColumn(),
        'activos'   => (int) $pdo->query('SELECT COUNT(*) FROM procedimientos WHERE activo = 1')->fetchColumn(),
        'categorias' => (int) $pdo->query('SELECT COUNT(DISTINCT categoria) FROM procedimientos WHERE activo = 1')->fetchColumn(),
        'usos'      => (int) $pdo->query('SELECT COUNT(*) FROM atencion_procedimientos')->fetchColumn(),
    ];

    $catStmt = $pdo->query(
        'SELECT categoria, COUNT(*) AS total
         FROM procedimientos WHERE activo = 1
         GROUP BY categoria ORDER BY categoria ASC'
    );

    api_success([
        'items'        => $items,
        'categorias'   => procedure_categories(),
        'stats'        => $stats,
        'por_categoria' => $catStmt->fetchAll(),
    ]);
}

if ($action === 'get') {
    $id = (int) input('id', 0);
    $stmt = $pdo->prepare('SELECT * FROM procedimientos WHERE id = ?');
    $stmt->execute([$id]);
    $procedure = $stmt->fetch();
    if (!$procedure) {
        api_error('El procedimiento no existe.', 404);
    }
    api_success(['procedure' => $procedure]);
}

if ($action === 'create' && $method === 'POST') {
    $data = procedure_payload();
    $errors = validate_procedure($data);
    if ($errors) {
        api_error('Revisa los campos marcados.', 422, $errors);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO procedimientos (nombre, categoria, descripcion, precio, duracion_min, activo)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $data['nombre'], $data['categoria'], $data['descripcion'] ?: null,
        (float) $data['precio'], $data['duracion_min'], $data['activo'],
    ]);
    $id = (int) $pdo->lastInsertId();

    audit_log('crear', 'procedimiento', $id, $data['nombre']);

    $stmt = $pdo->prepare('SELECT * FROM procedimientos WHERE id = ?');
    $stmt->execute([$id]);

    api_success(['procedure' => $stmt->fetch()], 'Procedimiento agregado al catálogo.');
}

if ($action === 'update' && $method === 'POST') {
    $id = (int) input('id', 0);
    $exists = $pdo->prepare('SELECT id FROM procedimientos WHERE id = ?');
    $exists->execute([$id]);
    if (!$exists->fetch()) {
        api_error('El procedimiento no existe.', 404);
    }

    $data = procedure_payload();
    $errors = validate_procedure($data, $id);
    if ($errors) {
        api_error('Revisa los campos marcados.', 422, $errors);
    }

    $stmt = $pdo->prepare(
        'UPDATE procedimientos SET nombre = ?, categoria = ?, descripcion = ?, precio = ?, duracion_min = ?, activo = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $data['nombre'], $data['categoria'], $data['descripcion'] ?: null,
        (float) $data['precio'], $data['duracion_min'], $data['activo'], $id,
    ]);

    audit_log('actualizar', 'procedimiento', $id, $data['nombre']);

    $stmt = $pdo->prepare('SELECT * FROM procedimientos WHERE id = ?');
    $stmt->execute([$id]);

    api_success(['procedure' => $stmt->fetch()], 'Procedimiento actualizado.');
}

if ($action === 'toggle' && $method === 'POST') {
    $id = (int) input('id', 0);
    $stmt = $pdo->prepare('SELECT id, activo, nombre FROM procedimientos WHERE id = ?');
    $stmt->execute([$id]);
    $procedure = $stmt->fetch();
    if (!$procedure) {
        api_error('El procedimiento no existe.', 404);
    }

    $nuevo = (int) $procedure['activo'] === 1 ? 0 : 1;
    $pdo->prepare('UPDATE procedimientos SET activo = ? WHERE id = ?')->execute([$nuevo, $id]);
    audit_log($nuevo ? 'activar' : 'desactivar', 'procedimiento', $id, $procedure['nombre']);

    api_success(['activo' => $nuevo], $nuevo ? 'Procedimiento activado.' : 'Procedimiento desactivado.');
}

if ($action === 'delete' && $method === 'POST') {
    $id = (int) input('id', 0);
    $stmt = $pdo->prepare('SELECT id, nombre FROM procedimientos WHERE id = ?');
    $stmt->execute([$id]);
    $procedure = $stmt->fetch();
    if (!$procedure) {
        api_error('El procedimiento no existe.', 404);
    }

    $uses = $pdo->prepare('SELECT COUNT(*) FROM atencion_procedimientos WHERE procedimiento_id = ?');
    $uses->execute([$id]);
    $usos = (int) $uses->fetchColumn();

    if ($usos > 0) {
        $pdo->prepare('UPDATE procedimientos SET activo = 0 WHERE id = ?')->execute([$id]);
        audit_log('desactivar', 'procedimiento', $id, $procedure['nombre']);
        api_success(
            ['desactivado' => true],
            'El procedimiento tiene atenciones asociadas, por lo que se desactivó en lugar de eliminarse.'
        );
    }

    $pdo->prepare('DELETE FROM procedimientos WHERE id = ?')->execute([$id]);
    audit_log('eliminar', 'procedimiento', $id, $procedure['nombre']);

    api_success(null, 'Procedimiento eliminado del catálogo.');
}

api_error('Acción no válida.', 404);
