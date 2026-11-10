<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

require_once dirname(__DIR__) . '/vendor/fpdf/fpdf.php';

$patientId = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM pacientes WHERE id = ? LIMIT 1');
$stmt->execute([$patientId]);
$patient = $stmt->fetch();

if (!$patient) {
    http_response_code(404);
    exit('Paciente no encontrado.');
}

$recordsStmt = db()->prepare(
    "SELECT a.*, u.nombre AS odontologo,
            (SELECT GROUP_CONCAT(pr.nombre ORDER BY pr.nombre SEPARATOR ', ')
             FROM atencion_procedimientos ap
             JOIN procedimientos pr ON pr.id = ap.procedimiento_id
             WHERE ap.atencion_id = a.id) AS procedimientos
     FROM atenciones a
     LEFT JOIN usuarios u ON u.id = a.usuario_id
     WHERE a.paciente_id = ?
     ORDER BY a.fecha DESC, a.id DESC"
);
$recordsStmt->execute([$patientId]);
$records = $recordsStmt->fetchAll();

$summaryStmt = db()->prepare(
    "SELECT COUNT(*) AS total,
            MAX(CASE WHEN fecha <= CURDATE() THEN fecha END) AS ultima_visita,
            MIN(CASE WHEN proxima_cita >= CURDATE() THEN proxima_cita END) AS proxima_cita
     FROM atenciones WHERE paciente_id = ?"
);
$summaryStmt->execute([$patientId]);
$summary = $summaryStmt->fetch();

$procStmt = db()->prepare(
    'SELECT COUNT(DISTINCT ap.procedimiento_id)
     FROM atencion_procedimientos ap
     JOIN atenciones a ON a.id = ap.atencion_id
     WHERE a.paciente_id = ?'
);
$procStmt->execute([$patientId]);
$summary['procedimientos_distintos'] = (int) $procStmt->fetchColumn();

$chartStmt = db()->prepare('SELECT pieza, estado, notas FROM odontograma WHERE paciente_id = ? ORDER BY pieza ASC');
$chartStmt->execute([$patientId]);
$chart = [];
foreach ($chartStmt->fetchAll() as $chartRow) {
    $chart[$chartRow['pieza']] = $chartRow;
}

function pdf_text($text): string
{
    $text = (string) $text;
    if (function_exists('iconv')) {
        $converted = iconv('UTF-8', 'WINDOWS-1252//TRANSLIT', $text);
        if ($converted !== false) {
            return $converted;
        }
    }
    return preg_replace('/[^\x20-\x7E]/', '', $text);
}

class ClinicalHistoryPDF extends FPDF
{
    public string $clinicName = '';
    public string $specialty = '';
    public string $phone = '';
    public string $email = '';
    public string $address = '';
    public string $code = '';

    public function Header(): void
    {
        $this->SetFillColor(13, 74, 72);
        $this->Rect(0, 0, 210, 35, 'F');

        $this->SetFillColor(20, 184, 166);
        $this->Rect(0, 35, 210, 1.4, 'F');

        $logo = dirname(__DIR__) . '/assets/img/logo-pdf.png';
        if (is_file($logo)) {
            $this->Image($logo, 14, 8, 19, 19, 'PNG');
        }

        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Helvetica', 'B', 15);
        $this->SetXY(37, 8);
        $this->Cell(0, 7, pdf_text($this->clinicName));

        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(204, 251, 241);
        $this->SetXY(37, 15);
        $this->Cell(0, 4, pdf_text($this->specialty . '  ·  ' . $this->phone));

        $this->SetFont('Helvetica', '', 7.5);
        $this->SetTextColor(178, 235, 228);
        $this->SetXY(37, 20);
        $this->Cell(0, 4, pdf_text($this->address));

        $this->SetFont('Helvetica', 'B', 9.5);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY(136, 9);
        $this->Cell(60, 5, pdf_text('HISTORIA CLÍNICA'), 0, 0, 'R');

        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(204, 251, 241);
        $this->SetXY(136, 15);
        $this->Cell(60, 4, pdf_text('Código: ' . $this->code), 0, 0, 'R');
        $this->SetXY(136, 20);
        $this->Cell(60, 4, pdf_text('Generado: ' . date('d/m/Y')), 0, 0, 'R');

        $this->SetY(43);
        $this->SetTextColor(30, 41, 59);
    }

    public function Footer(): void
    {
        $this->SetY(-17);
        $this->SetDrawColor(226, 232, 240);
        $this->Line(15, $this->GetY(), 195, $this->GetY());

        $this->SetY(-13);
        $this->SetFont('Helvetica', '', 7);
        $this->SetTextColor(100, 116, 139);
        $this->Cell(0, 4, pdf_text($this->clinicName . '  ·  ' . $this->phone . '  ·  ' . $this->email));

        $this->SetY(-9);
        $this->SetFont('Helvetica', '', 7);
        $this->Cell(0, 4, pdf_text('Documento generado el ' . date('d/m/Y h:i a') . '  ·  Página ' . $this->PageNo() . ' de {nb}'), 0, 0, 'R');
    }

    public function sectionTitle(string $title): void
    {
        $this->SetFont('Helvetica', 'B', 10);
        $this->SetTextColor(15, 118, 110);
        $this->Cell(0, 6, pdf_text($title), 0, 1);
        $this->SetDrawColor(204, 251, 241);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->Ln(2);
        $this->SetTextColor(30, 41, 59);
    }

    public function labelValue(string $label, string $value, float $labelWidth = 30): void
    {
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetTextColor(100, 116, 139);
        $this->Cell($labelWidth, 4.5, pdf_text($label));
        $this->SetFont('Helvetica', '', 9);
        $this->SetTextColor(30, 41, 59);
        $this->Cell(0, 4.5, pdf_text($value), 0, 1);
    }

    public function twoColumnInfo(string $leftLabel, string $leftValue, string $rightLabel, string $rightValue): void
    {
        $y = $this->GetY();

        $this->SetFont('Helvetica', 'B', 7.5);
        $this->SetTextColor(100, 116, 139);
        $this->SetXY(15, $y);
        $this->Cell(86, 4, pdf_text($leftLabel));
        $this->SetXY(109, $y);
        $this->Cell(86, 4, pdf_text($rightLabel));

        $this->SetFont('Helvetica', '', 9);
        $this->SetTextColor(30, 41, 59);
        $this->SetXY(15, $y + 4.2);
        $this->MultiCell(86, 4.4, pdf_text($leftValue !== '' ? $leftValue : 'No registrado'));
        $leftEnd = $this->GetY();

        $this->SetXY(109, $y + 4.2);
        $this->MultiCell(86, 4.4, pdf_text($rightValue !== '' ? $rightValue : 'No registrado'));
        $rightEnd = $this->GetY();

        $this->SetY(max($leftEnd, $rightEnd) + 1);
    }
}

$fullName = trim($patient['nombres'] . ' ' . $patient['apellidos']);
$age = age_from_birthdate($patient['fecha_nacimiento']);

$pdf = new ClinicalHistoryPDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->SetTitle(pdf_text('Historia clínica - ' . $fullName));
$pdf->SetAuthor(pdf_text(app_config('app.clinic', 'Clínica Dental')));
$pdf->SetCreator(pdf_text(app_config('app.name', 'DentaHistoria')));
$pdf->clinicName = app_config('app.clinic', 'Clínica Dental');
$pdf->specialty = app_config('app.specialty', 'Odontología integral');
$pdf->phone = app_config('app.phone', '');
$pdf->email = app_config('app.email', '');
$pdf->address = app_config('app.address', '');
$pdf->code = $patient['codigo'] ?: 'PAC-' . $patient['id'];

$pdf->SetMargins(15, 43, 15);
$pdf->SetAutoPageBreak(true, 21);
$pdf->AddPage();

// Ficha del paciente
$pdf->SetFillColor(247, 250, 251);
$pdf->Rect(15, 43, 180, 34, 'F');
$pdf->SetFillColor(15, 118, 110);
$pdf->Rect(15, 43, 2, 34, 'F');

$pdf->SetXY(21, 46);
$pdf->SetFont('Helvetica', 'B', 13);
$pdf->SetTextColor(15, 23, 42);
$pdf->Cell(0, 7, pdf_text($fullName));

$pdf->SetXY(21, 53);
$pdf->SetFont('Helvetica', '', 8);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(0, 4, pdf_text('Código ' . $pdf->code . '   ·   Documento ' . ($patient['documento'] ?: 'No registrado')));

$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(51, 65, 85);
$pdf->SetXY(21, 60);
$pdf->Cell(86, 4.5, pdf_text('Edad: ' . ($age === null ? 'No registrada' : $age . ' años') . '   ·   Sexo: ' . sexo_label($patient['sexo'])));
$pdf->SetXY(109, 60);
$pdf->Cell(86, 4.5, pdf_text('Nacimiento: ' . format_date($patient['fecha_nacimiento'])));

$pdf->SetXY(21, 66);
$pdf->Cell(86, 4.5, pdf_text('Teléfono: ' . ($patient['telefono'] ?: 'No registrado')));
$pdf->SetXY(109, 66);
$pdf->Cell(86, 4.5, pdf_text('Correo: ' . ($patient['email'] ?: 'No registrado')));

$pdf->SetXY(21, 72);
$pdf->Cell(174, 4.5, pdf_text('Dirección: ' . ($patient['direccion'] ?: 'No registrada')));

$pdf->SetY(80);

// Información clínica
$pdf->twoColumnInfo('ALERGIAS', $patient['alergias'] ?: 'Sin alergias registradas', 'ANTECEDENTES MÉDICOS', $patient['antecedentes'] ?: 'Sin antecedentes registrados');

if (!empty($patient['notas'])) {
    $pdf->twoColumnInfo('NOTAS INTERNAS', $patient['notas'], 'CÓDIGO DE PACIENTE', $pdf->code);
}

// Resumen
$pdf->Ln(2);
$summaryY = $pdf->GetY();
$boxWidth = 43.5;
$summaryItems = [
    ['Atenciones', (string) (int) $summary['total']],
    ['Última visita', format_date($summary['ultima_visita'])],
    ['Próxima cita', format_date($summary['proxima_cita'])],
    ['Procedimientos', (string) (int) $summary['procedimientos_distintos']],
];

foreach ($summaryItems as $index => $item) {
    $x = 15 + ($index * ($boxWidth + 2));
    $pdf->SetFillColor(240, 253, 250);
    $pdf->Rect($x, $summaryY, $boxWidth, 16, 'F');
    $pdf->SetFillColor(20, 184, 166);
    $pdf->Rect($x, $summaryY, $boxWidth, 1.2, 'F');
    $pdf->SetXY($x + 4, $summaryY + 3.5);
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->SetTextColor(15, 118, 110);
    $pdf->Cell($boxWidth - 8, 5, pdf_text($item[1]));
    $pdf->SetXY($x + 4, $summaryY + 9.5);
    $pdf->SetFont('Helvetica', '', 7);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell($boxWidth - 8, 4, pdf_text($item[0]));
}
$pdf->SetY($summaryY + 21);

// Odontograma
if ($pdf->GetY() > 218) {
    $pdf->AddPage();
}

$pdf->sectionTitle('ODONTOGRAMA');
$pdf->SetFont('Helvetica', '', 7.5);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(0, 4, pdf_text('Estado actual de las 32 piezas permanentes (numeración FDI).'), 0, 1);
$pdf->Ln(2);

$stateLabels = dental_states();
$stateColors = [
    'sano'       => [[226, 232, 240], [71, 85, 105]],
    'caries'     => [[239, 68, 68], [255, 255, 255]],
    'obturado'   => [[37, 99, 235], [255, 255, 255]],
    'corona'     => [[217, 119, 6], [255, 255, 255]],
    'endodoncia' => [[124, 58, 237], [255, 255, 255]],
    'extraccion' => [[100, 116, 139], [255, 255, 255]],
    'ausente'    => [[203, 213, 225], [71, 85, 105]],
    'implante'   => [[15, 118, 110], [255, 255, 255]],
    'sellante'   => [[8, 145, 178], [255, 255, 255]],
];

$teethRows = [
    array_merge(dental_teeth()['upper_right'], dental_teeth()['upper_left']),
    array_merge(dental_teeth()['lower_right'], dental_teeth()['lower_left']),
];

foreach ($teethRows as $rowIndex => $teeth) {
    $rowY = $pdf->GetY();
    $x = 15;
    foreach ($teeth as $index => $tooth) {
        if ($index === 8) {
            $x += 3.2;
        }
        $chartRow = $chart[$tooth] ?? null;
        $toothState = $chartRow['estado'] ?? 'sano';
        [$fill, $textColor] = $stateColors[$toothState] ?? $stateColors['sano'];

        $pdf->SetFillColor($fill[0], $fill[1], $fill[2]);
        $pdf->Rect($x, $rowY, 10, 9, 'F');
        $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
        $pdf->SetFont('Helvetica', 'B', 7.5);
        $pdf->SetXY($x, $rowY + 2.4);
        $pdf->Cell(10, 4, pdf_text($tooth), 0, 0, 'C');

        if ($toothState === 'extraccion') {
            $pdf->SetDrawColor($textColor[0], $textColor[1], $textColor[2]);
            $pdf->Line($x + 2.4, $rowY + 4.5, $x + 7.6, $rowY + 4.5);
        }

        $x += 10.95;
    }
    $pdf->SetY($rowY + 10.6);
}

$pdf->Ln(1);
$pdf->SetFont('Helvetica', '', 6.8);
$pdf->SetTextColor(100, 116, 139);
$pdf->MultiCell(180, 3.6, pdf_text('Referencias: ' . implode('  ·  ', array_values($stateLabels))), 0, 'L');

$findings = array_filter($chart, static function ($item) {
    return ($item['estado'] ?? 'sano') !== 'sano' || trim((string) ($item['notas'] ?? '')) !== '';
});

$pdf->Ln(1.5);
if ($findings) {
    $pdf->SetFont('Helvetica', 'B', 7);
    $pdf->SetTextColor(15, 118, 110);
    $pdf->Cell(0, 4, 'HALLAZGOS REGISTRADOS', 0, 1);
    foreach ($findings as $tooth => $item) {
        $label = 'Pieza ' . $tooth . ' · ' . ($stateLabels[$item['estado']] ?? $item['estado']);
        if (!empty($item['notas'])) {
            $label .= ' — ' . $item['notas'];
        }
        $pdf->SetX(15);
        $pdf->SetFont('Helvetica', '', 7.5);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->MultiCell(180, 4, pdf_text($label));
    }
} else {
    $pdf->SetFont('Helvetica', '', 7.5);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(0, 4, pdf_text('Sin hallazgos registrados: todas las piezas se encuentran sanas.'), 0, 1);
}

$pdf->Ln(3);

// Historia clínica
$pdf->sectionTitle('HISTORIA CLÍNICA ODONTOLÓGICA');
$pdf->SetFont('Helvetica', '', 8);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(0, 4, pdf_text(count($records) . ' atención(es) registrada(s)'), 0, 1);
$pdf->Ln(3);

if (!$records) {
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(0, 10, pdf_text('El paciente aún no tiene atenciones registradas.'), 0, 1);
}

$statusLabels = ['realizado' => 'REALIZADO', 'pendiente' => 'PENDIENTE', 'seguimiento' => 'EN SEGUIMIENTO'];
$statusColors = ['realizado' => [16, 185, 129], 'pendiente' => [217, 119, 6], 'seguimiento' => [14, 165, 233]];

foreach ($records as $record) {
    if ($pdf->GetY() > 235) {
        $pdf->AddPage();
    }

    $startY = $pdf->GetY();

    // Fecha
    $pdf->SetFillColor(15, 118, 110);
    $pdf->Rect(15, $startY, 27, 11, 'F');
    $pdf->SetXY(15, $startY + 1.7);
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(27, 4, pdf_text(format_date($record['fecha'])));
    $pdf->SetFont('Helvetica', '', 6.5);
    $pdf->SetXY(15, $startY + 6.2);
    $pdf->Cell(27, 3, pdf_text($record['hora'] ? date('h:i a', strtotime($record['hora'])) : 'Sin hora'));

    // Estado
    $status = $record['estado'] ?: 'realizado';
    $color = $statusColors[$status] ?? [100, 116, 139];
    $pdf->SetFont('Helvetica', 'B', 7);
    $pdf->SetTextColor($color[0], $color[1], $color[2]);
    $pdf->SetXY(140, $startY + 1);
    $pdf->Cell(55, 4, pdf_text($statusLabels[$status] ?? strtoupper($status)), 0, 0, 'R');

    // Motivo
    $pdf->SetXY(46, $startY);
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->MultiCell(149, 5, pdf_text($record['motivo'] ?: 'Consulta odontológica'));

    $pdf->SetX(46);
    $pdf->SetFont('Helvetica', '', 7.5);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(0, 4, pdf_text('Odontólogo: ' . ($record['odontologo'] ?: 'No registrado') . '   ·   Piezas: ' . ($record['dientes'] ?: 'No especificadas')), 0, 1);

    if (!empty($record['procedimientos'])) {
        $pdf->SetX(46);
        $pdf->SetFont('Helvetica', 'B', 7.5);
        $pdf->SetTextColor(15, 118, 110);
        $pdf->Cell(28, 4, 'PROCEDIMIENTOS');
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->MultiCell(121, 4, pdf_text($record['procedimientos']));
    }

    foreach ([
        'diagnostico' => 'DIAGNÓSTICO',
        'tratamiento' => 'TRATAMIENTO',
        'observaciones' => 'OBSERVACIONES',
    ] as $field => $label) {
        if (!empty($record[$field])) {
            $pdf->SetX(46);
            $pdf->SetFont('Helvetica', 'B', 7.5);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(24, 4, pdf_text($label));
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetTextColor(51, 65, 85);
            $pdf->MultiCell(125, 4, pdf_text($record[$field]));
        }
    }

    if (!empty($record['proxima_cita'])) {
        $pdf->SetX(46);
        $pdf->SetFont('Helvetica', 'B', 7.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(24, 4, pdf_text('PRÓXIMA CITA'));
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->Cell(125, 4, pdf_text(format_date($record['proxima_cita'])), 0, 1);
    }

    $pdf->Ln(2);
    $pdf->SetDrawColor(226, 232, 240);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(4);
}

// Firma
if ($pdf->GetY() > 230) {
    $pdf->AddPage();
}
$pdf->Ln(8);
$pdf->SetDrawColor(148, 163, 184);
$pdf->Line(120, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(2);
$pdf->SetFont('Helvetica', '', 8);
$pdf->SetTextColor(100, 116, 139);
$pdf->SetX(120);
$pdf->Cell(75, 4, pdf_text('Firma y sello del odontólogo'), 0, 1, 'C');
$pdf->SetX(120);
$pdf->Cell(75, 4, pdf_text(app_config('app.doctor', '')), 0, 1, 'C');

$safeName = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT', $fullName) : false;
$safeName = preg_replace('/[^A-Za-z0-9]+/', '_', $safeName !== false ? $safeName : $fullName);
$filename = 'Historia_' . $pdf->code . '_' . trim((string) $safeName, '_');
$filename = trim($filename, '_') . '.pdf';

if (ob_get_length()) {
    ob_end_clean();
}

$destination = isset($_GET['descargar']) && $_GET['descargar'] === '1' ? 'D' : 'I';
$pdf->Output($destination, $filename);
exit;
