<?php
// ===== CONFIGURACIÓN (ajusta estos datos a tu XAMPP) =====
const DB_HOST = 'localhost';
const DB_NAME = 'm_parts';       // nombre de tu base de datos en phpMyAdmin
const DB_USER = 'root';
const DB_PASS = '';              // XAMPP por defecto no tiene contraseña
const WHATSAPP = '573224764768'; // Colombia
// Unifica el error de digitación "RESPUESTOS" -> "REPUESTOS" sin tocar tus datos
const CAT_SQL = "REPLACE(categoria,'RESPUESTOS','REPUESTOS')";

function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function cop($n): string { return '$' . number_format((float)$n, 0, ',', '.'); }
function nombre($s): string { return mb_convert_case(mb_strtolower($s), MB_CASE_TITLE); }
function cat_label($c): string {
    $t = mb_convert_case(mb_strtolower($c), MB_CASE_TITLE);
    return str_replace(['Klr', 'Dr ', 'V-strom'], ['KLR', 'DR ', 'V-Strom'], $t);
}
function cat_icon($c): string {
    $c = strtoupper($c);
    foreach (['USADOS' => '♻️', 'ACCESORIOS' => '🧰', 'LIQUIDOS' => '🛢️', 'MORRALES' => '🎒', 'STICKERS' => '✨'] as $k => $i)
        if (str_contains($c, $k)) return $i;
    return '🏍️';
}
function stock_badge($n): array {
    if ($n <= 0) return ['Agotado', 'out'];
    if ($n <= 5) return ['Últimas unidades', 'low'];
    return ['Disponible', 'ok'];
}
function url(array $o = []): string {
    $p = array_filter(array_merge($_GET, $o), fn($v) => $v !== '' && $v !== null);
    return 'index.php' . ($p ? '?' . http_build_query($p) : '');
}

// ===== REDES SOCIALES (deja '' para ocultar una) =====
const SOCIAL = [
    'Instagram' => 'https://www.instagram.com/mparts_28',
    'Facebook'  => '',
    'TikTok'    => 'https://www.tiktok.com/@mpartsrepuestos1',
    'YouTube'   => '',
];
const CONTACTO = ['Ciudad' => 'Cúcuta, Colombia', 'Horario' => 'Lunes a sábado · 8:00 a.m. – 6:00 p.m.', 'Correo' => 'contacto@mparts.com'];

// ===== ACCESO AL PANEL DE ADMINISTRADOR (cámbialo) =====
const ADMIN_USER = 'admin';
const ADMIN_PASS = 'mparts2026';

if (session_status() === PHP_SESSION_NONE) session_start();
function is_admin(): bool { return !empty($_SESSION['admin']); }
function require_admin(): void { if (!is_admin()) { header('Location: login.php'); exit; } }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function check_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Sesión expirada. Recarga la página e inténtalo de nuevo.'); }
}
function flash(string $m, string $t = 'ok'): void { $_SESSION['flash'] = [$m, $t]; }
function next_id(PDO $pdo, string $t, string $pk): int { return (int)$pdo->query("SELECT COALESCE(MAX(`$pk`),0)+1 FROM `$t`")->fetchColumn(); }
