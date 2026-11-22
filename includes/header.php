<?php require_once __DIR__ . '/../config.php'; require_once __DIR__ . '/fotos.php'; $titulo = $titulo ?? 'M-PARTS | Repuestos para motos'; ?>
<!DOCTYPE html>
<html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($titulo) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="icon" href="assets/img/logo.jpg"><meta name="theme-color" content="#0b0c0f">
<link rel="stylesheet" href="assets/css/style.css?v=18">
</head><body>
<header class="nav"><div class="wrap nav-in">
  <a class="logo" href="index.php"><b>M-</b>PARTS</a>
  <form class="search" action="index.php" method="get">
    <input name="q" value="<?= h($_GET['q'] ?? '') ?>" placeholder="Buscar por nombre, código o categoría…" aria-label="Buscar productos">
    <button>Buscar</button>
  </form>
  <a class="cart-link" href="carrito.php">Ver carrito <b id="cart-count">0</b></a>
</div></header>
