<?php

/**
 * Utilidades compartidas por toda la aplicación.
 */

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function api_success($data = null, string $message = ''): void
{
    api_response(['ok' => true, 'data' => $data, 'message' => $message]);
}

function api_error(string $message, int $status = 422, array $errors = []): void
{
    api_response(['ok' => false, 'message' => $message, 'errors' => $errors], $status);
}

function api_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function request_json(): array
{
    static $payload = null;

    if (is_array($payload)) {
        return $payload;
    }

    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        $payload = [];
        return $payload;
    }

    $decoded = json_decode($raw, true);
    $payload = is_array($decoded) ? $decoded : [];

    return $payload;
}

function input(string $key, $default = null)
{
    if (array_key_exists($key, $_POST)) {
        return is_string($_POST[$key]) ? trim($_POST[$key]) : $_POST[$key];
    }
    if (array_key_exists($key, $_GET)) {
        return is_string($_GET[$key]) ? trim($_GET[$key]) : $_GET[$key];
    }

    $json = request_json();
    if (array_key_exists($key, $json)) {
        return is_string($json[$key]) ? trim($json[$key]) : $json[$key];
    }

    return $default;
}

function input_array(string $key): array
{
    $value = input($key, []);
    if (is_array($value)) {
        return $value;
    }
    if (is_string($value) && $value !== '') {
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
    return [];
}

function only_digits(?string $value): string
{
    return preg_replace('/\D+/', '', (string) $value) ?? '';
}

function valid_date(?string $date): bool
{
    if (!$date) {
        return false;
    }
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt !== false && $dt->format('Y-m-d') === $date;
}

function format_date(?string $date, string $format = 'd/m/Y'): string
{
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '—';
}

function format_datetime(?string $date): string
{
    return format_date($date, 'd/m/Y · h:i a');
}

function human_date(?string $date): string
{
    if (!$date) {
        return '—';
    }
    $months = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];
    $ts = strtotime($date);
    if (!$ts) {
        return '—';
    }
    return (int) date('j', $ts) . ' de ' . $months[(int) date('n', $ts)] . ' de ' . date('Y', $ts);
}

function age_from_birthdate(?string $birthdate): ?int
{
    if (!$birthdate) {
        return null;
    }
    try {
        $birth = new DateTime($birthdate);
        $today = new DateTime('today');
        return max(0, $birth->diff($today)->y);
    } catch (Exception $e) {
        return null;
    }
}

function age_label(?string $birthdate): string
{
    $age = age_from_birthdate($birthdate);
    return $age === null ? '—' : $age . ' años';
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $parts = array_values(array_filter($parts));
    if (count($parts) === 0) {
        return 'DP';
    }
    $first = mb_substr($parts[0], 0, 1);
    $second = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';
    return mb_strtoupper($first . $second);
}

function sexo_label(?string $sexo): string
{
    return match ($sexo) {
        'M' => 'Masculino',
        'F' => 'Femenino',
        default => 'No especificado',
    };
}

function estado_label(?string $estado): string
{
    return match ($estado) {
        'pendiente'  => 'Pendiente',
        'seguimiento' => 'En seguimiento',
        default       => 'Realizado',
    };
}

function estado_class(?string $estado): string
{
    return match ($estado) {
        'pendiente'   => 'estado-pendiente',
        'seguimiento' => 'estado-seguimiento',
        default        => 'estado-realizado',
    };
}

function money($amount): string
{
    return '$' . number_format((float) $amount, 2, ',', '.');
}

function truncate_text(?string $text, int $limit = 90): string
{
    $text = trim((string) $text);
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $limit)) . '…';
}

function csrf_meta_tag(): string
{
    return '<meta name="csrf-token" content="' . e(csrf_token()) . '">';
}

/**
 * Devuelve la lista de piezas dentales FDI usadas en el odontograma.
 */
function dental_teeth(): array
{
    return [
        'upper_right' => ['18', '17', '16', '15', '14', '13', '12', '11'],
        'upper_left'  => ['21', '22', '23', '24', '25', '26', '27', '28'],
        'lower_left'  => ['31', '32', '33', '34', '35', '36', '37', '38'],
        'lower_right' => ['48', '47', '46', '45', '44', '43', '42', '41'],
    ];
}

/**
 * Lista plana de las 32 piezas permanentes en orden FDI.
 */
function dental_tooth_list(): array
{
    return array_merge(
        dental_teeth()['upper_right'],
        dental_teeth()['upper_left'],
        dental_teeth()['lower_right'],
        dental_teeth()['lower_left']
    );
}

/**
 * Estados clínicos posibles de una pieza dental.
 */
function dental_states(): array
{
    return [
        'sano'       => 'Sano',
        'caries'     => 'Caries',
        'obturado'   => 'Obturado / calza',
        'corona'     => 'Corona',
        'endodoncia' => 'Endodoncia',
        'extraccion' => 'Extracción',
        'ausente'    => 'Ausente',
        'implante'   => 'Implante',
        'sellante'   => 'Sellante',
    ];
}

function dental_state_class(string $state): string
{
    return 'tooth-' . (array_key_exists($state, dental_states()) ? $state : 'sano');
}

function teeth_label(?string $teeth): string
{
    if (!$teeth) {
        return 'Sin especificar';
    }
    $items = array_values(array_filter(array_map('trim', explode(',', $teeth))));
    if (count($items) === 0) {
        return 'Sin especificar';
    }
    return implode(', ', $items);
}

function audit_log(string $action, string $entity, ?int $entityId = null, string $detail = ''): void
{
    try {
        $stmt = db()->prepare(
            'INSERT INTO auditoria (usuario_id, accion, entidad, entidad_id, detalle, ip, creado_en)
             VALUES (?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            current_user()['id'] ?? null,
            $action,
            $entity,
            $entityId,
            $detail,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable $e) {
        error_log('Audit log error: ' . $e->getMessage());
    }
}

/**
 * Normaliza el nombre de una categoría para mostrarla de forma consistente.
 */
function procedure_categories(): array
{
    return ['Preventiva', 'Estética', 'Cirugía', 'Endodoncia', 'Restauración', 'Ortodoncia', 'Diagnóstico'];
}
