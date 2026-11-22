<?php
require __DIR__ . '/includes/boot.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    try {
        $c = $_POST['codigo'] ?? ''; $q = (int)($_POST['cantidad'] ?? 0); $cost = (float)($_POST['costo'] ?? 0);
        if ($c === '' || $q <= 0) throw new Exception('Elige un producto y una cantidad mayor a 0.');
        $pdo->beginTransaction();
        $s = $pdo->prepare("SELECT categoria FROM productos WHERE codigo_producto=? FOR UPDATE"); $s->execute([$c]); $cat = $s->fetchColumn();
        if ($cat === false) throw new Exception('El producto no existe.');
        $pdo->prepare("INSERT INTO entradas (id_entrada, codigo_producto, fecha, categoria, costo_unitario, cantidad, id_proveedor, total) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([next_id($pdo, 'entradas', 'id_entrada'), $c, $_POST['fecha'] ?: date('Y-m-d'), $cat, $cost, $q, ($_POST['prov'] ?? '') ?: null, $q * $cost]);
        $pdo->prepare("UPDATE productos SET stock_actual = stock_actual + ?, costo_unitario = ? WHERE codigo_producto=?")->execute([$q, $cost, $c]);
        $pdo->commit(); flash("Entrada registrada: +$q unidades. Inversión: " . cop($q * $cost) . '.'); header('Location: nueva_entrada.php'); exit;
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); flash($e->getMessage(), 'err'); header('Location: nueva_entrada.php'); exit; }
}
$prods = $pdo->query("SELECT codigo_producto, nombre, stock_actual, costo_unitario FROM productos ORDER BY nombre")->fetchAll();
$prov = $pdo->query("SELECT id_proveedor, empresa FROM proveedores ORDER BY empresa")->fetchAll();
$titulo = 'Registrar entrada de mercancía'; $activo = 'ne'; include __DIR__ . '/includes/top.php';
?>
<form class="panel form" method="post"><?= csrf_field() ?><div class="fgrid">
  <label>Producto<select name="codigo" id="prod" required><option value="">Elegir…</option>
    <?php foreach ($prods as $p) echo '<option value="' . h($p['codigo_producto']) . '" data-c="' . h($p['costo_unitario']) . '">' . h($p['codigo_producto']) . ' · ' . h($p['nombre']) . ' (stock ' . (int)$p['stock_actual'] . ')</option>'; ?></select></label>
  <label>Proveedor<select name="prov"><option value="">— sin proveedor —</option><?php foreach ($prov as $p) echo '<option value="' . h($p['id_proveedor']) . '">' . h($p['empresa']) . '</option>'; ?></select></label>
  <label>Fecha<input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required></label>
  <label>Cantidad<input type="number" name="cantidad" id="q" min="1" value="1" required></label>
  <label>Costo unitario (COP)<input type="number" name="costo" id="c" min="0" step="any" required></label>
  <label>Total<input id="t" readonly></label></div>
  <div class="acts"><button class="btn">Registrar entrada</button></div>
  <p class="muted">Suma las unidades al stock del producto y actualiza su costo unitario. ¿Es un producto nuevo? Créalo primero en <a href="tabla.php?t=productos&m=form">Productos</a>.</p></form>
<script>
const $ = i => document.getElementById(i), upd = () => $('t').value = '$' + Math.round((+$('q').value || 0) * (+$('c').value || 0)).toLocaleString('es-CO');
$('prod').onchange = e => { $('c').value = e.target.selectedOptions[0].dataset.c || ''; upd(); }; $('q').oninput = $('c').oninput = upd;
</script>
<?php include __DIR__ . '/includes/bottom.php';
