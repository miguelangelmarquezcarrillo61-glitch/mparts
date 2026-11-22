<?php
// Fotos de productos: archivo assets/img/productos/<CODIGO>.jpg|png|webp  (ej. A001.jpg)
const FOTO_EXT = ['jpg', 'jpeg', 'png', 'webp', 'JPG', 'JPEG', 'PNG', 'WEBP'];
function foto_dir(): string { return __DIR__ . '/../assets/img/productos/'; }
function foto_codigo(string $c): string { return preg_replace('/[^A-Za-z0-9_-]/', '', $c); }

// Devuelve la ruta relativa a la raíz del sitio, o null si el producto no tiene foto.
function foto(string $codigo, string $categoria = '', string $nombre = ''): ?string {
    $c = foto_codigo($codigo);
    if ($c !== '') {
    foreach (FOTO_EXT as $e) {
        $f = foto_dir() . $c . '.' . $e;
        if (is_file($f)) return 'assets/img/productos/' . $c . '.' . $e . '?v=' . filemtime($f);
    }
    }

    $texto = mb_strtoupper($categoria . ' ' . $nombre);
    $archivo = 'fallback-repuesto.svg';
    if (str_contains($texto, 'LLANTA') || str_contains($texto, 'NEUMATICO') || str_contains($texto, 'RIN')) $archivo = 'fallback-llanta.svg';
    elseif (str_contains($texto, 'ACEITE') || str_contains($texto, 'LIQUID') || str_contains($texto, 'REFRIGER') || str_contains($texto, 'SILICONA') || str_contains($texto, 'RESTaurador')) $archivo = 'fallback-liquidos.svg';
    elseif (str_contains($texto, 'MORRAL') || str_contains($texto, 'BOLSO') || str_contains($texto, 'MALET') || str_contains($texto, 'MOCHILA')) $archivo = 'fallback-bolso.svg';
    elseif (str_contains($texto, 'TORNILLO') || str_contains($texto, 'TUERCA') || str_contains($texto, 'ARANDELA') || str_contains($texto, 'PERNO')) $archivo = 'fallback-tornillo.svg';
    elseif (str_contains($texto, 'PISTON') || str_contains($texto, 'PIST') || str_contains($texto, 'EMBOLO') || str_contains($texto, 'CILINDRO') || str_contains($texto, 'CULATA')) $archivo = 'fallback-piston.svg';
    elseif (str_contains($texto, 'CADENA') || str_contains($texto, 'SPRDCKET') || str_contains($texto, 'PI�ON')) $archivo = 'fallback-cadena.svg';
    elseif (str_contains($texto, 'FILTRO') || str_contains($texto, 'CAJA FILT')) $archivo = 'fallback-filtro.svg';
    elseif (str_contains($texto, 'BATERIA')) $archivo = 'fallback-bateria.svg';
    elseif (str_contains($texto, 'DISCO') || str_contains($texto, 'FRENO') || str_contains($texto, 'CALIPER') || str_contains($texto, 'CLUTCH') || str_contains($texto, 'CLOCHERA')) $archivo = 'fallback-freno.svg';
    elseif (str_contains($texto, 'TANQUE') || str_contains($texto, 'GASOLINA') || str_contains($texto, 'TAPA DE GAS')) $archivo = 'fallback-tanque.svg';
    elseif (str_contains($texto, 'ASIENTO') || str_contains($texto, 'ASIENT')) $archivo = 'fallback-asiento.svg';
    elseif (str_contains($texto, 'CAJA DE') || str_contains($texto, 'HERRAMIENT')) $archivo = 'fallback-herramienta.svg';
    elseif (str_contains($texto, 'GUAYA') || str_contains($texto, 'CABLE') || str_contains($texto, 'BARRA') || str_contains($texto, 'TENSOR')) $archivo = 'fallback-cable.svg';
    elseif (str_contains($texto, 'ESPEJO') || str_contains($texto, 'ESPEJOS')) $archivo = 'fallback-espejo.svg';
    elseif (str_contains($texto, 'FARO') || str_contains($texto, 'FAROL') || str_contains($texto, 'LAMPARA') || str_contains($texto, 'LUZ') || str_contains($texto, 'EXPLORADOR')) $archivo = 'fallback-lampara.svg';
    elseif (str_contains($texto, 'ESCAPE') || str_contains($texto, 'TUBO') || str_contains($texto, 'ESCAP')) $archivo = 'fallback-escape.svg';
    elseif (str_contains($texto, 'CHASIS') || str_contains($texto, 'DEFENSA') || str_contains($texto, 'PARRILLA') || str_contains($texto, 'BASE') || str_contains($texto, 'PROTECTOR')) $archivo = 'fallback-accesorio.svg';
    elseif (str_contains($texto, 'CARBURADOR') || str_contains($texto, 'CARBUDADOR')) $archivo = 'fallback-carburador.svg';
    elseif (str_contains($texto, 'STICKER') || str_contains($texto, 'CALCOMANIA')) $archivo = 'fallback-sticker.svg';

    $f = foto_dir() . $archivo;
    return is_file($f) ? 'assets/img/productos/' . $archivo . '?v=' . filemtime($f) : null;
}
function borrar_fotos(string $codigo): void {
    $c = foto_codigo($codigo); if ($c === '') return;
    foreach (FOTO_EXT as $e) { $f = foto_dir() . $c . '.' . $e; if (is_file($f)) @unlink($f); }
}
// Guarda la foto subida desde el panel (reemplaza la anterior).
function guardar_foto(string $codigo, array $f): void {
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return;
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('El servidor no pudo recibir el archivo (código ' . $f['error'] . ').');
    if ($f['size'] > 3 * 1024 * 1024) throw new RuntimeException('La imagen supera los 3 MB.');
    $info = @getimagesize($f['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$info['mime'] ?? ''] ?? null;
    if (!$ext) throw new RuntimeException('Formato no permitido. Usa JPG, PNG o WEBP.');
    $c = foto_codigo($codigo); if ($c === '') throw new RuntimeException('Código de producto inválido.');
    if (!is_dir(foto_dir())) @mkdir(foto_dir(), 0755, true);
    borrar_fotos($c);
    if (!move_uploaded_file($f['tmp_name'], foto_dir() . $c . '.' . $ext)) throw new RuntimeException('No se pudo guardar la imagen en el servidor.');
}
