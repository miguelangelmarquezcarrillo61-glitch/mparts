<?php $titulo = $titulo ?? 'Panel'; $activo = $activo ?? ''; $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
function nav_a($href, $txt, $key, $activo) { return '<a class="' . ($key === $activo ? 'on' : '') . '" href="' . $href . '">' . $txt . '</a>'; } ?>
<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($titulo) ?> · M-PARTS Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="icon" href="../assets/img/logo.jpg"><link rel="stylesheet" href="../assets/css/style.css?v=18"><link rel="stylesheet" href="../assets/css/admin.css?v=5">
</head><body class="adm">
<aside class="side"><a class="logo" href="index.php"><b>M-</b>PARTS <small>ADMIN</small></a>
<nav>
  <?= nav_a('index.php', '📊 Panel general', 'dash', $activo) ?>
  <?= nav_a('movimientos.php', '💱 Entradas y salidas', 'mov', $activo) ?>
  <?= nav_a('nueva_venta.php', '➕ Registrar venta', 'nv', $activo) ?>
  <?= nav_a('nueva_entrada.php', '➕ Registrar entrada', 'ne', $activo) ?>
  <p>Base de datos</p>
  <?php foreach ($S as $nav_k => $nav_t) echo nav_a('tabla.php?t=' . $nav_k, $nav_t['icon'] . ' ' . h($nav_t['label']), $nav_k, $activo); ?>
  <p>&nbsp;</p><?= nav_a('../index.php', '🏍️ Ver tienda', '', '') ?><?= nav_a('logout.php', '🚪 Cerrar sesión', '', '') ?>
</nav></aside>
<main class="mainc"><header class="topb"><h1><?= h($titulo) ?></h1></header>
<?php if ($f): ?><div class="flash <?= $f[1] ?>"><?= h($f[0]) ?></div><?php endif; ?>
