<?php
require_once __DIR__ . '/config.php';
$codigo = $_GET['codigo'] ?? ''; $p = null; $rel = []; $error = null; $E = CAT_SQL;
try {
    $st = db()->prepare("SELECT codigo_producto, nombre, $E AS cat, stock_actual, precio_venta FROM productos WHERE codigo_producto = :c");
    $st->execute([':c' => $codigo]); $p = $st->fetch();
    if ($p) {
        $st = db()->prepare("SELECT codigo_producto, nombre, $E AS cat, stock_actual, precio_venta FROM productos WHERE $E = :cat AND codigo_producto <> :c ORDER BY RAND() LIMIT 4");
        $st->execute([':cat' => $p['cat'], ':c' => $codigo]); $rel = $st->fetchAll();
    }
} catch (PDOException $e) { $error = $e->getMessage(); }
if (!$p && !$error) http_response_code(404);
$titulo = $p ? nombre($p['nombre']) . ' | M-PARTS' : 'Producto no encontrado | M-PARTS';
include __DIR__ . '/includes/header.php';
?>
<main class="wrap">
<?php if ($error): ?>
  <div class="alert"><b>Error de base de datos.</b><br><small><?= h($error) ?></small></div>
<?php elseif (!$p): ?>
  <div class="empty">Producto no encontrado. <a href="index.php">Volver al catálogo</a></div>
<?php else: [$txt, $cls] = stock_badge($p['stock_actual']);
  $msg = 'Hola M-PARTS, me interesa: ' . nombre($p['nombre']) . ' (código ' . $p['codigo_producto'] . ').'; ?>
  <p class="crumbs"><a href="index.php">Catálogo</a> › <a href="<?= h(url(['cat' => $p['cat'], 'q' => '', 'p' => ''])) ?>"><?= h(cat_label($p['cat'])) ?></a> › <?= h(nombre($p['nombre'])) ?></p>
  <section class="detail">
    <div class="thumb big"><?php if ($ph = foto($p['codigo_producto'], $p['cat'], $p['nombre'])): ?><img src="<?= h($ph) ?>" alt="<?= h(nombre($p['nombre'])) ?>" loading="lazy"><?php else: ?><span><?= cat_icon($p['cat']) ?></span><?php endif; ?></div>
    <div>
      <small class="cat"><?= h(cat_label($p['cat'])) ?></small>
      <h1><?= h(nombre($p['nombre'])) ?></h1>
      <p class="price"><?= cop($p['precio_venta']) ?></p>
      <p><span class="badge <?= $cls ?>"><?= $txt ?></span> <span class="muted"><?= (int)$p['stock_actual'] ?> unidades en stock</span></p>
      <div class="meter"><i class="<?= $cls ?>" style="width:<?= min(100, max(3, $p['stock_actual'] * 2)) ?>%"></i></div>
      <ul class="facts"><li><span>Código</span><b><?= h($p['codigo_producto']) ?></b></li><li><span>Categoría</span><b><?= h(cat_label($p['cat'])) ?></b></li></ul>
      <?php if ($p['stock_actual'] > 0): ?>
      <button class="btn big-btn cart-add" type="button" data-codigo="<?= h($p['codigo_producto']) ?>" data-nombre="<?= h(nombre($p['nombre'])) ?>" data-precio="<?= h($p['precio_venta']) ?>" data-stock="<?= (int)$p['stock_actual'] ?>">Agregar al carrito</button>
      <?php endif; ?>
      <a class="btn big-btn" href="https://wa.me/<?= WHATSAPP ?>?text=<?= urlencode($msg) ?>" target="_blank" rel="noopener"><?= $p['stock_actual'] > 0 ? 'Consultar por WhatsApp' : 'Consultar disponibilidad' ?></a>
      <a class="btn ghost" href="index.php">← Seguir viendo</a>
    </div>
  </section>
  <?php if ($rel): ?>
  <h2 class="sec">También te puede interesar</h2>
  <div class="grid">
    <?php foreach ($rel as $r): [$t2, $c2] = stock_badge($r['stock_actual']); ?>
      <a class="card" href="producto.php?codigo=<?= urlencode($r['codigo_producto']) ?>">
        <div class="thumb"><?php if ($ph = foto($r['codigo_producto'], $r['cat'], $r['nombre'])): ?><img src="<?= h($ph) ?>" alt="<?= h(nombre($r['nombre'])) ?>" loading="lazy"><?php else: ?><span><?= cat_icon($r['cat']) ?></span><?php endif; ?><em class="badge <?= $c2 ?>"><?= $t2 ?></em></div>
        <div class="info"><small class="cat"><?= h(cat_label($r['cat'])) ?></small><h3><?= h(nombre($r['nombre'])) ?></h3>
        <small class="stk <?= $c2 ?>"><?= $r['stock_actual'] > 0 ? (int)$r['stock_actual'] . ' disponibles' : 'Sin stock' ?></small>
        <div class="row"><strong><?= cop($r['precio_venta']) ?></strong><code><?= h($r['codigo_producto']) ?></code></div></div>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; endif; ?>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
