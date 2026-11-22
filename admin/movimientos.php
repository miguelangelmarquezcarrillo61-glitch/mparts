<?php
require __DIR__ . '/includes/boot.php';
$tipo = $_GET['tipo'] ?? ''; $d = $_GET['desde'] ?? ''; $h = $_GET['hasta'] ?? ''; $pag = max(1, (int)($_GET['p'] ?? 1)); $por = 25;
$base = "SELECT e.fecha, 'Entrada' AS tipo, CONCAT(COALESCE(p.nombre,e.codigo_producto),' × ',e.cantidad) AS detalle, e.total AS monto
         FROM entradas e LEFT JOIN productos p ON p.codigo_producto=e.codigo_producto
         UNION ALL SELECT v.fecha, 'Venta', CONCAT('Venta #',v.id_venta,' · ',v.metodo_pago), v.total FROM ventas v";
$w = []; $par = [];
if ($tipo === 'ingresos') $w[] = "tipo='Venta'"; elseif ($tipo === 'egresos') $w[] = "tipo='Entrada'";
if ($d !== '') { $w[] = 'DATE(fecha) >= ?'; $par[] = $d; }
if ($h !== '') { $w[] = 'DATE(fecha) <= ?'; $par[] = $h; }
$W = $w ? 'WHERE ' . implode(' AND ', $w) : '';
$st = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN tipo='Venta' THEN monto END),0), COALESCE(SUM(CASE WHEN tipo='Entrada' THEN monto END),0), COUNT(*) FROM ($base) m $W");
$st->execute($par); [$ing, $egr, $n] = $st->fetch(PDO::FETCH_NUM);
$pags = max(1, (int)ceil($n / $por)); $pag = min($pag, $pags); $off = ($pag - 1) * $por;
$st = $pdo->prepare("SELECT * FROM ($base) m $W ORDER BY fecha DESC, tipo LIMIT $por OFFSET $off"); $st->execute($par); $rows = $st->fetchAll();
$titulo = 'Entradas y salidas'; $activo = 'mov'; include __DIR__ . '/includes/top.php';
?>
<section class="kpis three">
  <div class="kpi good"><small>Ingresos por ventas</small><b><?= cop($ing) ?></b></div>
  <div class="kpi bad"><small>Egresos por compras de mercancía</small><b><?= cop($egr) ?></b></div>
  <div class="kpi"><small>Balance del periodo</small><b class="<?= $ing - $egr >= 0 ? 'pos' : 'neg' ?>"><?= cop($ing - $egr) ?></b></div>
</section>
<form class="toolbar filt">
  <select name="tipo"><?php foreach (['' => 'Todos los movimientos', 'ingresos' => 'Solo ventas (ingresos)', 'egresos' => 'Solo entradas (egresos)'] as $k => $l) echo '<option value="' . $k . '"' . ($tipo === $k ? ' selected' : '') . '>' . $l . '</option>'; ?></select>
  <label>Desde <input type="date" name="desde" value="<?= h($d) ?>"></label><label>Hasta <input type="date" name="hasta" value="<?= h($h) ?>"></label>
  <button class="btn ghost">Filtrar</button><a class="btn ghost" href="movimientos.php">Limpiar</a><span class="muted"><?= $n ?> movimientos</span>
</form>
<div class="panel tw"><table><thead><tr><th>Fecha</th><th>Tipo</th><th>Detalle</th><th>Monto</th></tr></thead><tbody>
<?php foreach ($rows as $r): $in = $r['tipo'] === 'Venta'; ?>
  <tr><td><?= h(substr($r['fecha'], 0, 10)) ?></td><td><span class="badge <?= $in ? 'ok' : 'low' ?>"><?= $in ? 'Venta · ingreso' : 'Entrada · egreso' ?></span></td><td><?= h($r['detalle']) ?></td>
  <td class="num <?= $in ? 'pos' : 'neg' ?>"><?= $in ? '+' : '−' ?><?= cop($r['monto']) ?></td></tr>
<?php endforeach; if (!$rows) echo '<tr><td colspan="4" class="muted">Sin movimientos para este filtro.</td></tr>'; ?></tbody></table></div>
<?php if ($pags > 1): ?><nav class="pager"><?php for ($i = 1; $i <= $pags; $i++): ?><a class="<?= $i == $pag ? 'on' : '' ?>" href="movimientos.php?<?= h(http_build_query(['tipo' => $tipo, 'desde' => $d, 'hasta' => $h, 'p' => $i])) ?>"><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
<?php include __DIR__ . '/includes/bottom.php';
