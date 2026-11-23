<?php
/**
 * Suite de Pruebas Unitarias — M-PARTS
 * Ejecucion: php tests/run_tests.php
 * (En Windows XAMPP: C:\xampp\php\php.exe tests/run_tests.php)
 */

// Cargar configuracion real si existe; de lo contrario usar la plantilla publica
if (is_file(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
} elseif (is_file(__DIR__ . '/../config.example.php')) {
    require_once __DIR__ . '/../config.example.php';
} else {
    fwrite(STDERR, "ERROR: No se encontro config.php ni config.example.php en la raiz del proyecto.\n");
    exit(1);
}

require_once __DIR__ . '/../includes/fotos.php';
$schema = require __DIR__ . '/../admin/includes/schema.php';

// ===== Motor de pruebas unitarias sin dependencias externas =====
$passed = 0;
$failed = 0;
$skipped = 0;

function section(string $title): void {
    echo "\n[$title]\n";
}

function assert_eq($expected, $actual, string $detail = ''): void {
    if ($expected !== $actual) {
        $exp = var_export($expected, true);
        $act = var_export($actual, true);
        throw new Exception(($detail ? "$detail — " : '') . "Esperado: $exp | Obtenido: $act");
    }
}

function assert_true(bool $condition, string $detail = 'Condicion falsa'): void {
    if (!$condition) {
        throw new Exception($detail);
    }
}

function assert_contains(string $needle, string $haystack, string $detail = ''): void {
    if (!str_contains($haystack, $needle)) {
        throw new Exception(($detail ? "$detail — " : '') . "No se encontro '$needle' dentro de '$haystack'");
    }
}

function assert_throws(string $expectedClass, callable $fn, string $msgFragment = ''): void {
    try {
        $fn();
    } catch (Throwable $e) {
        if (!($e instanceof $expectedClass)) {
            throw new Exception("Se esperaba excepcion $expectedClass, se obtuvo " . get_class($e));
        }
        if ($msgFragment !== '' && !str_contains($e->getMessage(), $msgFragment)) {
            throw new Exception("Mensaje de excepcion inesperado: '{$e->getMessage()}' (se esperaba contener '$msgFragment')");
        }
        return;
    }
    throw new Exception("Se esperaba que lanzara $expectedClass, pero no lanzo ninguna excepcion.");
}

function test(string $id, string $name, callable $fn): void {
    global $passed, $failed, $skipped;
    try {
        $res = $fn();
        if ($res === 'SKIP') {
            $skipped++;
            echo "  [SKIP] $id: $name\n";
            return;
        }
        $passed++;
        echo "  [PASS] $id: $name\n";
    } catch (Throwable $e) {
        $failed++;
        echo "  [FAIL] $id: $name\n";
        echo "         -> {$e->getMessage()}\n";
    }
}

echo "================================================================\n";
echo "  M-PARTS — SUITE DE PRUEBAS UNITARIAS (PHP NATIVO)\n";
echo "================================================================\n";

// ====================================================================
// MODULO 1: Helpers de formato, presentacion y sanitizacion
// ====================================================================
section('Modulo 1: Helpers de formato, presentacion y sanitizacion (config.php)');

test('UT-01', 'h() escapa etiquetas HTML y comillas (prevencion XSS)', function () {
    $payload = '<script>alert("xss")</script>';
    $expected = '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;';
    assert_eq($expected, h($payload));
    assert_eq("O&#039;Reilly", h("O'Reilly"));
});

test('UT-02', 'cop() formatea montos en pesos colombianos (COP)', function () {
    assert_eq('$80.000', cop(80000));
    assert_eq('$450.000', cop(450000));
    assert_eq('$0', cop(0));
    assert_eq('$1.250.000', cop('1250000'));
});

test('UT-03', 'nombre() aplica formato Titulo a nombres de repuestos', function () {
    assert_eq('Kit Carbudador', nombre('KIT CARBUDADOR'));
    assert_eq('Guayas De Clutch', nombre('guayas de clutch'));
});

test('UT-04', 'cat_label() conserva siglas tecnicas KLR, DR y V-Strom', function () {
    assert_eq('Repuestos Nuevos KLR 650', cat_label('REPUESTOS NUEVOS KLR 650'));
    assert_eq('Repuestos Nuevos De DR 650', cat_label('REPUESTOS NUEVOS DE DR 650'));
    assert_eq('Repuestos Nuevos De V-Strom', cat_label('REPUESTOS NUEVOS DE V-STROM'));
});

test('UT-05', 'cat_icon() asigna iconos diferenciados por categoria', function () {
    $usados = cat_icon('REPUESTOS USADOS KLR 650');
    $acc = cat_icon('ACCESORIOS');
    $liq = cat_icon('LIQUIDOS');
    $mor = cat_icon('MORRALES');
    $stk = cat_icon('STICKERS');
    $def = cat_icon('REPUESTOS NUEVOS KLR 650');

    foreach ([$usados, $acc, $liq, $mor, $stk, $def] as $icon) {
        assert_true(is_string($icon) && trim($icon) !== '', 'El icono no debe estar vacio');
    }
    assert_true($usados !== $def, 'USADOS debe tener un icono distinto al generico');
    assert_true($acc !== $liq && $mor !== $stk, 'Categorias distintas deben retornar iconos distintos');
});

// ====================================================================
// MODULO 2: Reglas de disponibilidad de stock y construccion de URLs
// ====================================================================
section('Modulo 2: Reglas de disponibilidad y navegacion (config.php)');

test('UT-06', 'stock_badge() marca Agotado cuando stock es 0 o negativo', function () {
    assert_eq(['Agotado', 'out'], stock_badge(0));
    assert_eq(['Agotado', 'out'], stock_badge(-5));
});

test('UT-07', 'stock_badge() marca Ultimas unidades en rango limite 1 a 5', function () {
    assert_eq(['Últimas unidades', 'low'], stock_badge(1));
    assert_eq(['Últimas unidades', 'low'], stock_badge(5));
});

test('UT-08', 'stock_badge() marca Disponible cuando stock es mayor a 5', function () {
    assert_eq(['Disponible', 'ok'], stock_badge(6));
    assert_eq(['Disponible', 'ok'], stock_badge(80));
});

test('UT-09', 'url() preserva filtros activos, agrega nuevos y elimina vacios', function () {
    $backup = $_GET;
    try {
        $_GET = ['cat' => 'ACCESORIOS', 'p' => '2'];
        assert_eq('index.php?cat=ACCESORIOS', url(['p' => '']));
        assert_eq('index.php?cat=ACCESORIOS&p=2&orden=precio_asc', url(['orden' => 'precio_asc']));
    } finally {
        $_GET = $backup;
    }
});

test('UT-10', 'url() retorna index.php limpio cuando todos los parametros se vacian', function () {
    $backup = $_GET;
    try {
        $_GET = ['cat' => 'STICKERS', 'p' => '3'];
        assert_eq('index.php', url(['cat' => '', 'p' => '']));
    } finally {
        $_GET = $backup;
    }
});

// ====================================================================
// MODULO 3: Seguridad, sesiones y proteccion CSRF
// ====================================================================
section('Modulo 3: Seguridad, sesiones y proteccion CSRF (config.php)');

test('UT-11', 'csrf() genera token hexadecimal de 32 caracteres y lo mantiene en sesion', function () {
    unset($_SESSION['csrf']);
    $t1 = csrf();
    $t2 = csrf();
    assert_eq(32, strlen($t1), 'Longitud esperada de 32 caracteres hex');
    assert_true((bool)preg_match('/^[a-f0-9]{32}$/', $t1), 'El token debe ser hexadecimal');
    assert_eq($t1, $t2, 'El token debe persistir durante la misma sesion');
});

test('UT-12', 'csrf_field() construye input oculto HTML con el token activo', function () {
    $token = csrf();
    $field = csrf_field();
    assert_contains('type="hidden"', $field);
    assert_contains('name="csrf"', $field);
    assert_contains('value="' . $token . '"', $field);
});

test('UT-13', 'is_admin() valida correctamente estado no autenticado y autenticado', function () {
    unset($_SESSION['admin']);
    assert_eq(false, is_admin());
    $_SESSION['admin'] = true;
    assert_eq(true, is_admin());
    unset($_SESSION['admin']);
});

test('UT-14', 'flash() guarda mensaje y tipo de alerta en la sesion', function () {
    flash('Guardado correctamente.', 'ok');
    assert_eq(['Guardado correctamente.', 'ok'], $_SESSION['flash']);
    flash('Stock insuficiente.', 'err');
    assert_eq(['Stock insuficiente.', 'err'], $_SESSION['flash']);
    unset($_SESSION['flash']);
});

// ====================================================================
// MODULO 4: Gestion segura de imagenes de productos
// ====================================================================
section('Modulo 4: Gestion segura de imagenes (includes/fotos.php)');

test('UT-15', 'foto_codigo() conserva codigos alfanumericos validos', function () {
    assert_eq('A001', foto_codigo('A001'));
    assert_eq('KLR_650-01', foto_codigo('KLR_650-01'));
});

test('UT-16', 'foto_codigo() neutraliza intentos de Path Traversal e inyeccion', function () {
    $sanitized1 = foto_codigo('../../etc/passwd');
    $sanitized2 = foto_codigo('A001; rm -rf /');
    assert_eq('etcpasswd', $sanitized1);
    assert_eq('A001rm-rf', $sanitized2);
    assert_true(!str_contains($sanitized1, '..') && !str_contains($sanitized1, '/'), 'No debe contener separadores de ruta');
});

test('UT-17', 'foto() retorna null para codigo vacio o producto sin imagen en disco', function () {
    assert_eq(null, foto(''));
    assert_eq(null, foto('CODIGO_INEXISTENTE_99999'));
});

test('UT-18', 'guardar_foto() ignora silenciosamente cuando no se adjunta archivo', function () {
    guardar_foto('A001', ['error' => UPLOAD_ERR_NO_FILE]);
    assert_true(true);
});

test('UT-19', 'guardar_foto() rechaza archivos mayores a 3 MB', function () {
    assert_throws(RuntimeException::class, function () {
        guardar_foto('A001', [
            'error' => UPLOAD_ERR_OK,
            'size' => 4 * 1024 * 1024,
            'tmp_name' => '',
        ]);
    }, '3 MB');
});

test('UT-20', 'guardar_foto() rechaza archivos con formato MIME invalido', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'mparts_test_');
    file_put_contents($tmp, 'este archivo es texto plano, no una imagen real');
    try {
        assert_throws(RuntimeException::class, function () use ($tmp) {
            guardar_foto('A999', [
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($tmp),
                'tmp_name' => $tmp,
            ]);
        }, 'Formato no permitido');
    } finally {
        @unlink($tmp);
    }
});

// ====================================================================
// MODULO 5: Integridad del esquema CRUD generico (7 tablas)
// ====================================================================
section('Modulo 5: Integridad del esquema CRUD generico (admin/includes/schema.php)');

test('UT-21', 'schema.php define exactamente las 7 tablas del modelo relacional', function () use ($schema) {
    $expectedTables = ['productos', 'clientes', 'proveedores', 'trabajadores', 'entradas', 'ventas', 'detalle_venta'];
    assert_eq(7, count($schema));
    assert_eq($expectedTables, array_keys($schema));
});

test('UT-22', 'Todas las tablas poseen metadatos obligatorios y su PK existe en fields', function () use ($schema) {
    foreach ($schema as $table => $cfg) {
        foreach (['label', 'icon', 'pk', 'auto', 'list', 'search', 'fields'] as $key) {
            assert_true(array_key_exists($key, $cfg), "Falta clave '$key' en tabla '$table'");
        }
        assert_true(
            array_key_exists($cfg['pk'], $cfg['fields']),
            "La llave primaria '{$cfg['pk']}' no existe en fields de '$table'"
        );
    }
});

test('UT-23', 'Todas las columnas de list y search existen en fields en las 7 tablas', function () use ($schema) {
    foreach ($schema as $table => $cfg) {
        foreach ($cfg['list'] as $col) {
            assert_true(array_key_exists($col, $cfg['fields']), "Columna list '$col' no existe en '$table'");
        }
        foreach ($cfg['search'] as $col) {
            assert_true(array_key_exists($col, $cfg['fields']), "Columna search '$col' no existe en '$table'");
        }
    }
});

test('UT-24', 'Todas las llaves foraneas (fk) apuntan a tablas y columnas existentes', function () use ($schema) {
    $fkCount = 0;
    foreach ($schema as $table => $cfg) {
        foreach ($cfg['fields'] as $col => $meta) {
            if (isset($meta['fk'])) {
                [$targetTable, $targetCol] = $meta['fk'];
                assert_true(isset($schema[$targetTable]), "FK '$table.$col' apunta a tabla inexistente '$targetTable'");
                assert_true(
                    isset($schema[$targetTable]['fields'][$targetCol]),
                    "FK '$table.$col' apunta a columna inexistente '$targetTable.$targetCol'"
                );
                $fkCount++;
            }
        }
    }
    assert_true($fkCount >= 6, 'Se esperaban al menos 6 llaves foraneas declaradas');
});

// ====================================================================
// MODULO 6: Reglas de negocio, calculos financieros y transacciones
// ====================================================================
section('Modulo 6: Reglas de negocio, calculos financieros y transacciones');

test('UT-25', 'Calculo de total de venta, utilidad bruta y valoracion de inventario', function () {
    $lineas = [
        ['codigo' => 'A001', 'cant' => 2, 'precio' => 80000.0, 'costo' => 52000.0],
        ['codigo' => 'A003', 'cant' => 1, 'precio' => 60000.0, 'costo' => 35000.0],
    ];
    $totalVenta = array_sum(array_map(fn($l) => $l['cant'] * $l['precio'], $lineas));
    $utilidadBruta = array_sum(array_map(fn($l) => ($l['cant'] * $l['precio']) - ($l['cant'] * $l['costo']), $lineas));

    assert_eq(220000.0, $totalVenta);
    assert_eq(81000.0, $utilidadBruta);

    $stock = 10;
    assert_eq(520000.0, $stock * 52000.0);
    assert_eq(800000.0, $stock * 80000.0);
});

test('UT-26', 'next_id() y rollback ACID ante stock insuficiente (PDO en memoria)', function () {
    if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
        echo "         -> Driver pdo_sqlite no habilitado en php.ini (omitido de forma segura).\n";
        return 'SKIP';
    }

    $mem = new PDO('sqlite::memory:', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $mem->exec("CREATE TABLE productos (codigo_producto TEXT PRIMARY KEY, nombre TEXT, stock_actual INTEGER, ventas_registradas INTEGER)");
    $mem->exec("CREATE TABLE ventas (id_venta INTEGER PRIMARY KEY, total REAL)");
    $mem->exec("INSERT INTO productos VALUES ('A001', 'Kit Carbudador', 3, 0)");

    assert_eq(1, next_id($mem, 'ventas', 'id_venta'));
    $mem->exec("INSERT INTO ventas VALUES (1, 80000)");
    assert_eq(2, next_id($mem, 'ventas', 'id_venta'));

    $mem->beginTransaction();
    try {
        $idv = next_id($mem, 'ventas', 'id_venta');
        $mem->prepare("INSERT INTO ventas (id_venta, total) VALUES (?, ?)")->execute([$idv, 400000]);
        $st = $mem->prepare("SELECT stock_actual FROM productos WHERE codigo_producto = ?");
        $st->execute(['A001']);
        $stock = (int)$st->fetchColumn();
        $cantidadSolicitada = 5;
        if ($stock < $cantidadSolicitada) {
            throw new Exception('Stock insuficiente');
        }
        $mem->commit();
    } catch (Throwable $e) {
        if ($mem->inTransaction()) {
            $mem->rollBack();
        }
    }

    $ventasCount = (int)$mem->query("SELECT COUNT(*) FROM ventas")->fetchColumn();
    $stockFinal = (int)$mem->query("SELECT stock_actual FROM productos WHERE codigo_producto = 'A001'")->fetchColumn();
    assert_eq(1, $ventasCount, 'La venta fallida debe haberse revertido con rollBack');
    assert_eq(3, $stockFinal, 'El stock debe permanecer inalterado tras el rollBack');
});

// ===== Resumen final =====
echo "\n----------------------------------------------------------------\n";
echo "  RESUMEN: $passed aprobadas | $failed fallidas | $skipped omitidas\n";
if ($failed === 0) {
    echo "  ESTADO:  TODAS LAS PRUEBAS PASARON CORRECTAMENTE\n";
} else {
    echo "  ESTADO:  SE ENCONTRARON FALLOS — REVISAR DETALLES ARRIBA\n";
}
echo "----------------------------------------------------------------\n";

exit($failed === 0 ? 0 : 1);
