<?php
require __DIR__ . '/includes/boot.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    try {
        $cod = $_POST['cod'] ?? []; $cant = $_POST['cant'] ?? []; $pre = $_POST['precio'] ?? []; $lin = [];
        foreach ($cod as $i => $c) if ($c !== '' && (int)$cant[$i] > 0) $lin[] = [$c, (int)$cant[$i], (float)$pre[$i]];
        if (!$lin) throw new Exception('Agrega al menos un producto.');
        if (empty($_POST['id_cliente']) || empty($_POST['id_trabajador'])) throw new Exception('Selecciona el cliente y el vendedor.');
        $pdo->beginTransaction();
        $total = array_sum(array_map(fn($l) => $l[1] * $l[2], $lin)); $idv = next_id($pdo, 'ventas', 'id_venta');
        $pdo->prepare("INSERT INTO ventas (id_venta, fecha, id_cliente, id_trabajador, metodo_pago, total) VALUES (?,?,?,?,?,?)")
            ->execute([$idv, $_POST['fecha'] ?: date('Y-m-d'), $_POST['id_cliente'], $_POST['id_trabajador'], $_POST['metodo_pago'], $total]);
        foreach ($lin as [$c, $q, $p]) {
            $s = $pdo->prepare("SELECT stock_actual, nombre FROM productos WHERE codigo_producto=? FOR UPDATE"); $s->execute([$c]); $pr = $s->fetch();
            if (!$pr) throw new Exception("Producto $c no existe.");
            if ($pr['stock_actual'] < $q) throw new Exception("Stock insuficiente de {$pr['nombre']} (hay {$pr['stock_actual']}).");
            $pdo->prepare("INSERT INTO detalle_venta (id_detalle, id_venta, codigo_producto, cantidad, precio_unitario, subtotal) VALUES (?,?,?,?,?,?)")
                ->execute([next_id($pdo, 'detalle_venta', 'id_detalle'), $idv, $c, $q, $p, $q * $p]);
            $pdo->prepare("UPDATE productos SET stock_actual = stock_actual - ?, ventas_registradas = COALESCE(ventas_registradas,0) + ? WHERE codigo_producto=?")->execute([$q, $q, $c]);
        }
        $pdo->commit(); flash("Venta #$idv registrada por " . cop($total) . '. El stock se actualizó.'); header('Location: nueva_venta.php'); exit;
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); flash($e->getMessage(), 'err'); header('Location: nueva_venta.php'); exit; }
}
$prods = array_map(fn($p) => ['c' => $p['codigo_producto'], 'n' => $p['nombre'], 's' => (int)$p['stock_actual'], 'p' => (float)$p['precio_venta']],
    $pdo->query("SELECT codigo_producto, nombre, stock_actual, precio_venta FROM productos WHERE stock_actual > 0 ORDER BY nombre")->fetchAll());
$cli = $pdo->query("SELECT id_cliente, CONCAT(nombre,' ',apellido) n FROM clientes ORDER BY nombre")->fetchAll();
$trab = $pdo->query("SELECT id_trabajador, CONCAT(nombre,' ',apellido) n FROM trabajadores ORDER BY nombre")->fetchAll();
$pagos = array_values(array_unique(array_merge(['EFECTIVO', 'TRANSFERENCIA', 'NEQUI', 'DAVIPLATA', 'TARJETA'], $pdo->query("SELECT DISTINCT metodo_pago FROM ventas WHERE metodo_pago IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN))));
$titulo = 'Registrar venta'; $activo = 'nv'; include __DIR__ . '/includes/top.php';
?>
<form class="panel form" method="post"><?= csrf_field() ?>
  <div class="fgrid">
    <label>Fecha<input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required></label>
    <label>Cliente<select name="id_cliente" required><option value="">Elegir…</option><?php foreach ($cli as $c) echo '<option value="' . h($c['id_cliente']) . '">' . h($c['n']) . ' (' . h($c['id_cliente']) . ')</option>'; ?></select></label>
    <label>Vendedor<select name="id_trabajador" required><option value="">Elegir…</option><?php foreach ($trab as $c) echo '<option value="' . h($c['id_trabajador']) . '">' . h($c['n']) . '</option>'; ?></select></label>
    <label>Método de pago<select name="metodo_pago"><?php foreach ($pagos as $p) echo '<option>' . h($p) . '</option>'; ?></select></label>
  </div>
  <div class="tw"><table class="lines"><thead><tr><th>Producto</th><th>Cant.</th><th>Precio unit.</th><th>Subtotal</th><th></th></tr></thead><tbody id="lines"></tbody></table></div>
  <div class="acts"><button type="button" class="btn ghost" id="add">+ Agregar producto</button><span class="gt">Total: <b id="gt">$0</b></span><button class="btn">Registrar venta</button></div>
  <p class="muted">Solo se listan productos con stock. Al registrar, se guarda la venta y su detalle y se descuenta el inventario.</p>
</form>
<script>
const P = <?= json_encode($prods, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const fmt = n => '$' + Math.round(n).toLocaleString('es-CO'), body = document.getElementById('lines');
function calc() { let t = 0; body.querySelectorAll('tr').forEach(r => { const s = (+r.querySelector('[name="cant[]"]').value || 0) * (+r.querySelector('[name="precio[]"]').value || 0); r.querySelector('.sub').textContent = fmt(s); t += s; }); document.getElementById('gt').textContent = fmt(t); }
function addRow() {
  const tr = document.createElement('tr');
  tr.innerHTML = `<td><select name="cod[]" required><option value="">Elegir producto…</option>${P.map(p => `<option value="${esc(p.c)}" data-p="${p.p}" data-s="${p.s}">${esc(p.c)} · ${esc(p.n)} (stock ${p.s})</option>`).join('')}</select></td>
  <td><input type="number" name="cant[]" min="1" value="1" required></td><td><input type="number" name="precio[]" min="0" step="any" required></td><td class="sub num">$0</td><td><button type="button" class="del x">✕</button></td>`;
  body.appendChild(tr);
}
body.addEventListener('change', e => { if (e.target.name === 'cod[]') { const o = e.target.selectedOptions[0], r = e.target.closest('tr'); r.querySelector('[name="precio[]"]').value = o.dataset.p || ''; r.querySelector('[name="cant[]"]').max = o.dataset.s || ''; } calc(); });
body.addEventListener('input', calc);
body.addEventListener('click', e => { if (e.target.classList.contains('x') && body.rows.length > 1) { e.target.closest('tr').remove(); calc(); } });
document.getElementById('add').onclick = addRow; addRow();
</script>
<?php include __DIR__ . '/includes/bottom.php';
