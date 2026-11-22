<?php
require __DIR__ . '/includes/boot.php';
$one = fn($sql) => $pdo->query($sql)->fetch(PDO::FETCH_NUM);
[$vTot, $vN] = $one("SELECT COALESCE(SUM(total),0), COUNT(*) FROM ventas");
[$eTot, $eN] = $one("SELECT COALESCE(SUM(total),0), COUNT(*) FROM entradas");
[$invC, $invV, $nProd] = $one("SELECT COALESCE(SUM(stock_actual*costo_unitario),0), COALESCE(SUM(stock_actual*precio_venta),0), COUNT(*) FROM productos");
[$bajo] = $one("SELECT COUNT(*) FROM productos WHERE stock_actual <= 5");
[$util] = $one("SELECT COALESCE(SUM(d.subtotal - d.cantidad*p.costo_unitario),0) FROM detalle_venta d JOIN productos p ON p.codigo_producto=d.codigo_producto");
$meses = array_reverse($pdo->query("SELECT DATE_FORMAT(fecha,'%Y-%m') m, SUM(total) t FROM ventas GROUP BY m ORDER BY m DESC LIMIT 12")->fetchAll());
$maxM = max(array_merge([1], array_column($meses, 't')));
$pagos = $pdo->query("SELECT metodo_pago, COUNT(*) n, SUM(total) t FROM ventas GROUP BY metodo_pago ORDER BY t DESC")->fetchAll();
$top = $pdo->query("SELECT p.nombre, SUM(d.cantidad) u, SUM(d.subtotal) t FROM detalle_venta d JOIN productos p ON p.codigo_producto=d.codigo_producto GROUP BY d.codigo_producto, p.nombre ORDER BY u DESC LIMIT 5")->fetchAll();
$ult = $pdo->query("SELECT v.id_venta, v.fecha, CONCAT(c.nombre,' ',c.apellido) cli, v.metodo_pago, v.total FROM ventas v LEFT JOIN clientes c ON c.id_cliente=v.id_cliente ORDER BY v.fecha DESC, v.id_venta DESC LIMIT 6")->fetchAll();
$low = $pdo->query("SELECT codigo_producto, nombre, stock_actual FROM productos WHERE stock_actual <= 5 ORDER BY stock_actual ASC, nombre LIMIT 6")->fetchAll();
$titulo = 'Panel general'; $activo = 'dash'; include __DIR__ . '/includes/top.php';
?>
<section class="kpis">
  <div class="kpi"><small>Ventas totales</small><b><?= cop($vTot) ?></b><span><?= $vN ?> ventas</span></div>
  <div class="kpi"><small>Compras de mercancía</small><b><?= cop($eTot) ?></b><span><?= $eN ?> entradas</span></div>
  <div class="kpi"><small>Utilidad bruta estimada</small><b><?= cop($util) ?></b><span>ventas − costo de lo vendido</span></div>
  <div class="kpi"><small>Valor del inventario</small><b><?= cop($invC) ?></b><span>a costo · <?= cop($invV) ?> a precio de venta</span></div>
  <div class="kpi"><small>Productos</small><b><?= $nProd ?></b><span><?= $bajo ?> con stock bajo (≤ 5)</span></div>
</section>
<section class="cols">
  <div class="panel"><h2>Ventas por mes</h2>
    <?php foreach ($meses as $x): ?><div class="hbar"><span><?= h($x['m']) ?></span><div><i style="width:<?= round($x['t'] / $maxM * 100) ?>%"></i></div><b><?= cop($x['t']) ?></b></div><?php endforeach; ?>
    <?php if (!$meses): ?><p class="muted">Aún no hay ventas.</p><?php endif; ?></div>
  <div class="panel"><h2>Métodos de pago</h2>
    <?php foreach ($pagos as $x): ?><div class="hbar"><span><?= h($x['metodo_pago']) ?></span><div><i style="width:<?= $vTot ? round($x['t'] / $vTot * 100) : 0 ?>%"></i></div><b><?= $x['n'] ?> · <?= cop($x['t']) ?></b></div><?php endforeach; ?></div>
</section>
<section class="cols">
  <div class="panel"><h2>Productos más vendidos</h2><table><tbody>
    <?php foreach ($top as $x): ?><tr><td><?= h(nombre($x['nombre'])) ?></td><td class="num"><?= (int)$x['u'] ?> und.</td><td class="num"><?= cop($x['t']) ?></td></tr><?php endforeach; ?></tbody></table></div>
  <div class="panel"><h2>Stock bajo <a class="more" href="tabla.php?t=productos">ver productos</a></h2><table><tbody>
    <?php foreach ($low as $x): ?><tr><td><?= h(nombre($x['nombre'])) ?> <code><?= h($x['codigo_producto']) ?></code></td><td class="num"><span class="badge <?= $x['stock_actual'] <= 0 ? 'out' : 'low' ?>"><?= (int)$x['stock_actual'] ?> und.</span></td></tr><?php endforeach; ?>
    <?php if (!$low): ?><tr><td class="muted">Todo el inventario tiene stock suficiente.</td></tr><?php endif; ?></tbody></table></div>
</section>
<div class="panel"><h2>Últimas ventas <a class="more" href="tabla.php?t=ventas">ver todas</a></h2><div class="tw"><table><thead><tr><th>#</th><th>Fecha</th><th>Cliente</th><th>Pago</th><th>Total</th></tr></thead><tbody>
  <?php foreach ($ult as $x): ?><tr><td><?= $x['id_venta'] ?></td><td><?= h(substr($x['fecha'], 0, 10)) ?></td><td><?= h($x['cli']) ?></td><td><?= h($x['metodo_pago']) ?></td><td class="num"><?= cop($x['total']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php include __DIR__ . '/includes/bottom.php';
