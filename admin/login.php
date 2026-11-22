<?php
require_once __DIR__ . '/../config.php';
if (is_admin()) { header('Location: index.php'); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (hash_equals(ADMIN_USER, $_POST['u'] ?? '') && hash_equals(ADMIN_PASS, $_POST['p'] ?? '')) {
        session_regenerate_id(true); $_SESSION['admin'] = true; header('Location: index.php'); exit;
    }
    sleep(1); $err = 'Usuario o contraseña incorrectos.';
}
?><!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Acceso · M-PARTS Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800&family=Inter:wght@400;600&display=swap" rel="stylesheet">
<link rel="icon" href="../assets/img/logo.jpg"><link rel="stylesheet" href="../assets/css/style.css?v=18"><link rel="stylesheet" href="../assets/css/admin.css?v=5"></head>
<body class="login"><form class="lbox" method="post"><div class="logo"><b>M-</b>PARTS</div><p class="muted">Panel de administración</p>
<?php if ($err): ?><div class="flash err"><?= h($err) ?></div><?php endif; ?>
<?= csrf_field() ?><label>Usuario<input name="u" required autofocus></label><label>Contraseña<input type="password" name="p" required></label>
<button class="btn">Ingresar</button><a class="muted" href="../index.php">← Volver a la tienda</a></form></body></html>
