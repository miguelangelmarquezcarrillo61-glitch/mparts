<?php
require_once __DIR__ . '/config.php';
$q = trim($_GET['q'] ?? ''); $cat = $_GET['cat'] ?? ''; $orden = $_GET['orden'] ?? ''; $disp = !empty($_GET['disp']);
$pag = max(1, (int)($_GET['p'] ?? 1)); $por = 12; $E = CAT_SQL;
$items = []; $cats = []; $total = 0; $paginas = 1; $totalAll = 0; $error = null;
try {
    $pdo = db(); $where = ['1=1']; $par = [];
    if ($cat !== '') { $where[] = "$E = :cat"; $par[':cat'] = $cat; }
    if ($q !== '') {
        $palabras = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($palabras as $i => $palabra) {
            $p1 = ':q' . $i . 'n';
            $p2 = ':q' . $i . 'c';
            $p3 = ':q' . $i . 'a';
            $where[] = "(nombre LIKE $p1 OR codigo_producto LIKE $p2 OR $E LIKE $p3)";
            $par[$p1] = $par[$p2] = $par[$p3] = '%' . $palabra . '%';
        }
    }
    if ($disp) $where[] = 'stock_actual > 0';
    $w = implode(' AND ', $where);
    $orderBy = ['precio_asc' => 'precio_venta ASC', 'precio_desc' => 'precio_venta DESC', 'nombre' => 'nombre ASC'][$orden] ?? 'codigo_producto ASC';
    $st = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE $w"); $st->execute($par); $total = (int)$st->fetchColumn();
    $paginas = max(1, (int)ceil($total / $por)); $pag = min($pag, $paginas); $off = ($pag - 1) * $por;
    $st = $pdo->prepare("SELECT codigo_producto, nombre, $E AS cat, stock_actual, precio_venta FROM productos WHERE $w ORDER BY $orderBy LIMIT $por OFFSET $off");
    $st->execute($par); $items = $st->fetchAll();
    $cats = $pdo->query("SELECT $E AS cat, COUNT(*) n FROM productos GROUP BY cat ORDER BY n DESC")->fetchAll();
    $totalAll = array_sum(array_column($cats, 'n'));
} catch (PDOException $e) { $error = $e->getMessage(); }
include __DIR__ . '/includes/header.php';
?>
<section class="hero"><div class="wrap hero-in"><div class="hero-tx">
  <p class="eyebrow christmas-eyebrow">🎄 Temporada navideña</p>
  <h1>Este año, regálale a tu moto <em>la mejor ruta</em></h1>
  <p class="lead christmas-lead"><b>Prepara tu moto para un viaje seguro y cómodo</b></p>
  <p class="christmas-message">🏍️ Prepárate para viajar · 🎁 Compra con cariño · ✨ Viaja con seguridad</p>
  <div class="stats"><div><b><?= $totalAll ?></b>productos</div><div><b><?= count($cats) ?></b>categorías</div><div><b>COP</b>precios claros</div></div></div><img class="hero-logo" src="assets/img/logo.jpg" alt="Logo M-PARTS Repuestos y Accesorios">
</div></section>

<main class="wrap">
<?php if ($error): ?>
  <div class="alert"><b>No se pudo conectar con la base de datos.</b><br>Revisa los datos en <code>config.php</code> y que MySQL esté activo en XAMPP.<br><small><?= h($error) ?></small></div>
<?php else: ?>
  <nav class="chips" aria-label="Categorías">
    <a class="<?= $cat === '' ? 'on' : '' ?>" href="<?= h(url(['cat' => '', 'p' => ''])) ?>">Todo <small><?= $totalAll ?></small></a>
    <?php foreach ($cats as $c): ?>
      <a class="<?= $cat === $c['cat'] ? 'on' : '' ?>" href="<?= h(url(['cat' => $c['cat'], 'p' => ''])) ?>"><?= cat_icon($c['cat']) ?> <?= h(cat_label($c['cat'])) ?> <small><?= $c['n'] ?></small></a>
    <?php endforeach; ?>
  </nav>

  <div class="bar">
    <p><b><?= $total ?></b> resultado<?= $total == 1 ? '' : 's' ?><?= $q !== '' ? ' para “' . h($q) . '”' : '' ?></p>
    <form method="get">
      <input type="hidden" name="q" value="<?= h($q) ?>"><input type="hidden" name="cat" value="<?= h($cat) ?>">
      <label class="chk"><input type="checkbox" name="disp" value="1" <?= $disp ? 'checked' : '' ?> onchange="this.form.submit()"> Solo disponibles</label>
      <select name="orden" onchange="this.form.submit()" aria-label="Ordenar">
        <?php foreach (['' => 'Más recientes', 'precio_asc' => 'Precio: menor a mayor', 'precio_desc' => 'Precio: mayor a menor', 'nombre' => 'Nombre A-Z'] as $v => $l): ?>
          <option value="<?= $v ?>" <?= $orden === $v ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>

  <?php if (!$items): ?>
    <div class="empty">😕 No encontramos productos con ese filtro. <a href="index.php">Ver todo el catálogo</a></div>
  <?php else: ?>
  <div class="christmas-strip" aria-label="Decoración navideña">
    <span>🎄</span><b>Especial Navidad</b><span>🎁</span><i></i><span>✨</span>
  </div>
  <div class="grid">
    <?php foreach ($items as $p): [$txt, $cls] = stock_badge($p['stock_actual']); ?>
      <article class="card">
        <a href="producto.php?codigo=<?= urlencode($p['codigo_producto']) ?>">
        <div class="thumb"><?php if ($ph = foto($p['codigo_producto'], $p['cat'], $p['nombre'])): ?><img src="<?= h($ph) ?>" alt="<?= h(nombre($p['nombre'])) ?>" loading="lazy"><?php else: ?><span><?= cat_icon($p['cat']) ?></span><?php endif; ?><em class="badge <?= $cls ?>"><?= $txt ?></em></div>
        <div class="info">
          <small class="cat"><?= h(cat_label($p['cat'])) ?></small>
          <h3><?= h(nombre($p['nombre'])) ?></h3>
          <small class="stk <?= $cls ?>"><?= $p['stock_actual'] > 0 ? (int)$p['stock_actual'] . ' disponibles' : 'Sin stock' ?></small>
          <div class="row"><strong><?= cop($p['precio_venta']) ?></strong><code><?= h($p['codigo_producto']) ?></code></div>
        </div>
        </a>
        <?php if ($p['stock_actual'] > 0): ?>
        <button class="cart-add" type="button" data-codigo="<?= h($p['codigo_producto']) ?>" data-nombre="<?= h(nombre($p['nombre'])) ?>" data-precio="<?= h($p['precio_venta']) ?>" data-stock="<?= (int)$p['stock_actual'] ?>">Agregar al carrito</button>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
  <?php if ($paginas > 1): ?>
  <nav class="pager" aria-label="Páginas">
    <?php if ($pag > 1): ?><a href="<?= h(url(['p' => $pag - 1])) ?>">‹</a><?php endif; ?>
    <?php for ($i = 1; $i <= $paginas; $i++): ?><a class="<?= $i == $pag ? 'on' : '' ?>" href="<?= h(url(['p' => $i])) ?>"><?= $i ?></a><?php endfor; ?>
    <?php if ($pag < $paginas): ?><a href="<?= h(url(['p' => $pag + 1])) ?>">›</a><?php endif; ?>
  </nav>
  <?php endif; endif; endif; ?>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
