<?php
require __DIR__ . '/includes/boot.php';
$t = $_GET['t'] ?? ''; if (!isset($S[$t])) { header('Location: index.php'); exit; }
$T = $S[$t]; $pk = $T['pk']; $m = $_GET['m'] ?? 'list'; $id = $_GET['id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf(); $act = $_POST['act'] ?? ''; $back = 'tabla.php?t=' . $t;
    try {
        if ($act === 'delete') {
            $pdo->prepare("DELETE FROM `$t` WHERE `$pk`=?")->execute([$_POST['id']]);
            if ($t === 'productos') borrar_fotos($_POST['id']);
            flash('Registro eliminado.');
        } elseif ($act === 'save') {
            $orig = $_POST['orig'] ?? ''; $vals = [];
            foreach ($T['fields'] as $c => $f) {
                if ($c === $pk && ($T['auto'] || $orig !== '')) continue;
                $v = trim($_POST[$c] ?? ''); $vals[$c] = $v === '' ? null : $v;
            }
            if ($orig !== '') {
                $set = implode(',', array_map(fn($c) => "`$c`=?", array_keys($vals)));
                $pdo->prepare("UPDATE `$t` SET $set WHERE `$pk`=?")->execute([...array_values($vals), $orig]);
            } else {
                if ($T['auto']) $vals = [$pk => next_id($pdo, $t, $pk)] + $vals;
                $cols = implode(',', array_map(fn($c) => "`$c`", array_keys($vals)));
                $pdo->prepare("INSERT INTO `$t` ($cols) VALUES (" . implode(',', array_fill(0, count($vals), '?')) . ')')->execute(array_values($vals));
            }
            if ($t === 'productos') {
                try { guardar_foto($orig !== '' ? $orig : (string)($vals[$pk] ?? ''), $_FILES['foto'] ?? []); }
                catch (RuntimeException $e) { flash('Producto guardado, pero la imagen no: ' . $e->getMessage(), 'err'); header('Location: ' . $back); exit; }
            }
            flash('Guardado correctamente.');
        }
    } catch (PDOException $e) {
        flash('No se pudo completar la acción: ' . $e->getMessage(), 'err');
        if ($act === 'save') $back .= '&m=form' . (($_POST['orig'] ?? '') !== '' ? '&id=' . urlencode($_POST['orig']) : '');
    }
    header('Location: ' . $back); exit;
}

$titulo = $T['label']; $activo = $t;
include __DIR__ . '/includes/top.php';

if ($m === 'form') {
    $row = []; $edit = $id !== '';
    if ($edit) { $st = $pdo->prepare("SELECT * FROM `$t` WHERE `$pk`=?"); $st->execute([$id]); $row = $st->fetch() ?: []; }
    echo '<form class="panel form" method="post" enctype="multipart/form-data"><h2>' . ($edit ? 'Editar' : 'Nuevo') . ' registro</h2>' . csrf_field()
       . '<input type="hidden" name="act" value="save"><input type="hidden" name="orig" value="' . h($edit ? $id : '') . '"><div class="fgrid">';
    foreach ($T['fields'] as $c => $f) {
        if ($c === $pk && $T['auto']) continue;
        $val = $row[$c] ?? ''; $ro = ($c === $pk && $edit);
        echo '<label>' . h($f[0]);
        if (isset($f['fk'])) {
            [$ft, $fv, $fl] = $f['fk'];
            echo '<select name="' . $c . '"><option value="">— ninguno —</option>';
            foreach ($pdo->query("SELECT `$fv` v, $fl l FROM `$ft` ORDER BY 1")->fetchAll() as $o)
                echo '<option value="' . h($o['v']) . '"' . ((string)$o['v'] === (string)$val ? ' selected' : '') . '>' . h($o['l']) . '</option>';
            echo '</select>';
        } elseif (isset($f['opts'])) {
            echo '<select name="' . $c . '"><option value="">—</option>';
            foreach ($f['opts'] as $o) echo '<option' . ($o === $val ? ' selected' : '') . '>' . h($o) . '</option>';
            echo '</select>';
        } else {
            $type = in_array($f[1], ['number', 'money']) ? 'number' : $f[1];
            if ($f[1] === 'date') $val = substr((string)$val, 0, 10);
            echo '<input type="' . $type . '" name="' . $c . '" value="' . h($val) . '"' . ($type === 'number' ? ' step="any"' : '') . ($ro ? ' readonly' : '') . (isset($f['datalist']) ? ' list="dl_' . $c . '"' : '') . '>';
            if (isset($f['datalist'])) { echo '<datalist id="dl_' . $c . '">'; foreach ($pdo->query($f['datalist'])->fetchAll(PDO::FETCH_COLUMN) as $o) echo '<option value="' . h($o) . '">'; echo '</datalist>'; }
        }
        echo '</label>';
    }
    echo '</div>';
    if ($t === 'productos') {
        $ph = $edit ? foto($id, (string)($row['categoria'] ?? ''), (string)($row['nombre'] ?? '')) : null;
        echo '<div class="fotobox">' . ($ph ? '<img class="mini big" src="../' . h($ph) . '" alt="">' : '<div class="mini big ph">📷</div>')
           . '<label>Foto del producto (JPG, PNG o WEBP, máx. 3 MB)<input type="file" name="foto" accept="image/jpeg,image/png,image/webp"></label></div>';
    }
    echo '<div class="acts"><button class="btn">Guardar</button><a class="btn ghost" href="tabla.php?t=' . $t . '">Cancelar</a></div>';
    if ($t === 'productos') echo '<p class="muted">Los productos nuevos aparecen automáticamente en el catálogo de la tienda.</p>';
    if (in_array($t, ['ventas', 'entradas', 'detalle_venta'])) echo '<p class="muted">Editar aquí no ajusta el stock. Para que el inventario se actualice, usa “Registrar venta” o “Registrar entrada”.</p>';
    echo '</form>';
} else {
    $q = trim($_GET['q'] ?? ''); $pag = max(1, (int)($_GET['p'] ?? 1)); $por = 15; $w = '1=1'; $par = [];
    if ($q !== '') { $w = '(' . implode(' OR ', array_map(fn($c) => "`$c` LIKE ?", $T['search'])) . ')'; $par = array_fill(0, count($T['search']), "%$q%"); }
    $st = $pdo->prepare("SELECT COUNT(*) FROM `$t` WHERE $w"); $st->execute($par); $n = (int)$st->fetchColumn();
    $pags = max(1, (int)ceil($n / $por)); $pag = min($pag, $pags); $off = ($pag - 1) * $por;
    $cols = implode(',', array_map(fn($c) => "`$c`", $T['list']));
    $st = $pdo->prepare("SELECT $cols FROM `$t` WHERE $w ORDER BY `$pk` " . ($T['auto'] ? 'DESC' : 'ASC') . " LIMIT $por OFFSET $off"); $st->execute($par); $rows = $st->fetchAll();
    echo '<div class="toolbar"><form><input type="hidden" name="t" value="' . $t . '"><input name="q" value="' . h($q) . '" placeholder="Buscar…"><button class="btn ghost">Buscar</button></form>'
       . '<span class="muted">' . $n . ' registros</span><a class="btn" href="tabla.php?t=' . $t . '&m=form">+ Nuevo</a></div>';
    echo '<div class="panel tw"><table><thead><tr>';
    if ($t === 'productos') echo '<th>Foto</th>';
    foreach ($T['list'] as $c) echo '<th>' . h($T['fields'][$c][0]) . '</th>';
    echo '<th></th></tr></thead><tbody>';
    foreach ($rows as $r) {
        echo '<tr>';
        if ($t === 'productos') echo '<td>' . (($ph = foto($r[$pk], (string)($r['categoria'] ?? ''), (string)($r['nombre'] ?? ''))) ? '<img class="mini" src="../' . h($ph) . '" alt="">' : '<span class="muted">—</span>') . '</td>';
        foreach ($T['list'] as $c) {
            $ft = $T['fields'][$c][1]; $v = $r[$c];
            echo '<td' . ($ft === 'money' || $ft === 'number' ? ' class="num"' : '') . '>' . h($ft === 'money' && $v !== null ? cop($v) : ($ft === 'date' ? substr((string)$v, 0, 10) : $v)) . '</td>';
        }
        echo '<td class="ra"><a href="tabla.php?t=' . $t . '&m=form&id=' . urlencode($r[$pk]) . '">Editar</a>'
           . '<form method="post" onsubmit="return confirm(\'¿Eliminar este registro? No se puede deshacer.\')">' . csrf_field()
           . '<input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="' . h($r[$pk]) . '"><button class="del">Eliminar</button></form></td></tr>';
    }
    if (!$rows) echo '<tr><td colspan="' . (count($T['list']) + ($t === 'productos' ? 2 : 1)) . '" class="muted">Sin resultados.</td></tr>';
    echo '</tbody></table></div>';
    if ($pags > 1) { echo '<nav class="pager">'; for ($i = 1; $i <= $pags; $i++) echo '<a class="' . ($i == $pag ? 'on' : '') . '" href="tabla.php?' . h(http_build_query(['t' => $t, 'q' => $q, 'p' => $i])) . '">' . $i . '</a>'; echo '</nav>'; }
}
include __DIR__ . '/includes/bottom.php';
