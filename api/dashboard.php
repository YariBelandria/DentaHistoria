<?php

require_once __DIR__ . '/bootstrap.php';
require_api_login();

$pdo = db();

$stats = [];
$stats['pacientes'] = (int) $pdo->query('SELECT COUNT(*) FROM pacientes')->fetchColumn();
$stats['atenciones_mes'] = (int) $pdo->query(
    'SELECT COUNT(*) FROM atenciones WHERE YEAR(fecha) = YEAR(CURDATE()) AND MONTH(fecha) = MONTH(CURDATE())'
)->fetchColumn();
$stats['procedimientos'] = (int) $pdo->query('SELECT COUNT(*) FROM procedimientos WHERE activo = 1')->fetchColumn();
$stats['proximas'] = (int) $pdo->query(
    "SELECT COUNT(*) FROM atenciones
     WHERE (proxima_cita >= CURDATE())
        OR (fecha >= CURDATE() AND estado = 'pendiente')"
)->fetchColumn();
$stats['nuevos_mes'] = (int) $pdo->query(
    'SELECT COUNT(*) FROM pacientes WHERE YEAR(creado_en) = YEAR(CURDATE()) AND MONTH(creado_en) = MONTH(CURDATE())'
)->fetchColumn();

$prevMonthStmt = $pdo->query(
    'SELECT COUNT(*) FROM atenciones
     WHERE YEAR(fecha) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
       AND MONTH(fecha) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))'
);
$stats['atenciones_mes_anterior'] = (int) $prevMonthStmt->fetchColumn();

$prevPatientsStmt = $pdo->query(
    'SELECT COUNT(*) FROM pacientes
     WHERE YEAR(creado_en) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
       AND MONTH(creado_en) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))'
);
$stats['nuevos_mes_anterior'] = (int) $prevPatientsStmt->fetchColumn();

// Serie de los últimos 6 meses.
$labels = [];
$keys = [];
$cursor = new DateTime('first day of -5 months');
for ($i = 0; $i < 6; $i++) {
    $monthKey = $cursor->format('Y-m');
    $keys[] = $monthKey;
    $monthNames = [1 => 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    $labels[] = $monthNames[(int) $cursor->format('n')] . ' ' . $cursor->format('y');
    $cursor->modify('+1 month');
}

$since = (new DateTime('first day of -5 months'))->format('Y-m-d');
$stmt = $pdo->prepare(
    "SELECT DATE_FORMAT(fecha, '%Y-%m') AS ym, COUNT(*) AS total
     FROM atenciones
     WHERE fecha >= ? AND fecha <= CURDATE()
     GROUP BY ym"
);
$stmt->execute([$since]);
$monthly = [];
foreach ($stmt->fetchAll() as $row) {
    $monthly[$row['ym']] = (int) $row['total'];
}
$monthlyData = array_map(fn ($key) => $monthly[$key] ?? 0, $keys);

$catStmt = $pdo->query(
    "SELECT pr.categoria, COUNT(*) AS total
     FROM atencion_procedimientos ap
     JOIN procedimientos pr ON pr.id = ap.procedimiento_id
     JOIN atenciones a ON a.id = ap.atencion_id
     WHERE a.fecha >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
     GROUP BY pr.categoria
     ORDER BY total DESC"
);
$categories = $catStmt->fetchAll();

$recentPatients = $pdo->query(
    "SELECT p.id, p.codigo, p.nombres, p.apellidos, p.telefono, p.fecha_nacimiento, p.sexo, p.creado_en,
            (SELECT MAX(a.fecha) FROM atenciones a WHERE a.paciente_id = p.id AND a.fecha <= CURDATE()) AS ultima_visita
     FROM pacientes p
     ORDER BY p.creado_en DESC, p.id DESC
     LIMIT 5"
)->fetchAll();

$recentRecords = $pdo->query(
    "SELECT a.id, a.fecha, a.motivo, a.estado, a.paciente_id,
            CONCAT(p.nombres, ' ', p.apellidos) AS paciente, p.codigo,
            (SELECT GROUP_CONCAT(pr.nombre ORDER BY pr.nombre SEPARATOR '||')
             FROM atencion_procedimientos ap
             JOIN procedimientos pr ON pr.id = ap.procedimiento_id
             WHERE ap.atencion_id = a.id) AS procedimientos
     FROM atenciones a
     JOIN pacientes p ON p.id = a.paciente_id
     ORDER BY a.creado_en DESC, a.id DESC
     LIMIT 6"
)->fetchAll();

$upcoming = $pdo->query(
    "SELECT a.id, a.proxima_cita, a.motivo, a.paciente_id, a.estado,
            CONCAT(p.nombres, ' ', p.apellidos) AS paciente, p.codigo, p.telefono
     FROM atenciones a
     JOIN pacientes p ON p.id = a.paciente_id
     WHERE a.proxima_cita >= CURDATE()
     ORDER BY a.proxima_cita ASC
     LIMIT 5"
)->fetchAll();

api_success([
    'stats'            => $stats,
    'monthly'          => ['labels' => $labels, 'values' => $monthlyData],
    'categories'       => [
        'labels' => array_column($categories, 'categoria'),
        'values' => array_map('intval', array_column($categories, 'total')),
    ],
    'recent_patients'  => $recentPatients,
    'recent_records'   => $recentRecords,
    'upcoming'         => $upcoming,
]);
