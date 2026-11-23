# 🧪 Documentación de Pruebas Unitarias — M-PARTS

Este documento describe la estrategia, guía de ejecución y matriz de casos de las **26 pruebas unitarias automatizadas** implementadas para verificar las funciones críticas, la seguridad y la integridad del sistema **M-PARTS**.

---

## 1. Enfoque y Estrategia de Pruebas

Debido a que **M-PARTS** está desarrollado en **PHP nativo puro** (sin dependencias de Composer ni frameworks externos), se construyó una **suite de pruebas unitarias autocontenida en PHP CLI** ubicada en `tests/run_tests.php`.

### Principios aplicados
- **Cero dependencias externas:** Se ejecuta directamente con el intérprete de PHP incluido en XAMPP o en GitHub Actions.
- **Aislamiento total (*Unit Testing*):** Las pruebas importan directamente las funciones reales del proyecto (`config.example.php`/`config.php`, `includes/fotos.php` y `admin/includes/schema.php`) y se ejecutan en memoria sin modificar la base de datos MySQL `m_parts`.
- **Integración Continua (CI):** El flujo `.github/workflows/tests.yml` ejecuta la suite automáticamente en cada *push* en la pestaña **Actions** de GitHub.
- **Código de salida estándar:** Retorna `0` cuando todas las pruebas aprueban y `1` si alguna falla.

---

## 2. Cómo Ejecutar las Pruebas

### En local (Windows con XAMPP)
```powershell
C:\xampp\php\php.exe tests/run_tests.php
```

### En local (si `php` está en el PATH)
```bash
php tests/run_tests.php
```

### En la nube (GitHub Actions)
Abre la pestaña **Actions** de este repositorio y selecciona la ejecución más reciente de **Pruebas Unitarias M-PARTS** para ver el reporte en vivo.

---

## 3. Matriz de Casos de Prueba Unitarios (26 Casos)

### Módulo 1: Helpers de Formato, Presentación y Sanitización (`config.php`)

| ID | Función Probada | Caso / Entrada | Resultado Esperado | Objetivo |
|---|---|---|---|---|
| **UT-01** | `h()` | `'<script>alert("xss")</script>'`, `"O'Reilly"` | Cadena HTML escapada (`&lt;script&gt;...`, `&#039;`) | Verificar protección contra ataques XSS. |
| **UT-02** | `cop()` | `80000`, `450000`, `0`, `'1250000'` | `'$80.000'`, `'$450.000'`, `'$0'`, `'$1.250.000'` | Validar formato monetario en pesos colombianos (COP). |
| **UT-03** | `nombre()` | `'KIT CARBUDADOR'`, `'guayas de clutch'` | `'Kit Carbudador'`, `'Guayas De Clutch'` | Comprobar capitalización uniforme de repuestos. |
| **UT-04** | `cat_label()` | `'REPUESTOS NUEVOS KLR 650'`, `'REPUESTOS NUEVOS DE DR 650'`, `'REPUESTOS NUEVOS DE V-STROM'` | `'Repuestos Nuevos KLR 650'`, `'Repuestos Nuevos De DR 650'`, `'Repuestos Nuevos De V-Strom'` | Verificar preservación de siglas técnicas de motos. |
| **UT-05** | `cat_icon()` | Categorías `USADOS`, `ACCESORIOS`, `LIQUIDOS`, `MORRALES`, `STICKERS` y genérica | Íconos no vacíos y diferenciados por categoría | Validar asignación correcta de respaldo visual. |

### Módulo 2: Reglas de Disponibilidad y Navegación (`config.php`)

| ID | Función Probada | Caso / Entrada | Resultado Esperado | Objetivo |
|---|---|---|---|---|
| **UT-06** | `stock_badge()` | `0` y `-5` | `['Agotado', 'out']` | Validar estado agotado en cero o negativo. |
| **UT-07** | `stock_badge()` | `1` y `5` (valores frontera) | `['Últimas unidades', 'low']` | Validar alerta de stock bajo (`1..5`). |
| **UT-08** | `stock_badge()` | `6` y `80` | `['Disponible', 'ok']` | Validar estado disponible sobre el umbral (`> 5`). |
| **UT-09** | `url()` | `$_GET = ['cat' => 'ACCESORIOS', 'p' => '2']`; `url(['p' => ''])` y `url(['orden' => 'precio_asc'])` | `'index.php?cat=ACCESORIOS'` y `'index.php?cat=ACCESORIOS&p=2&orden=precio_asc'` | Comprobar combinación y conservación de filtros en URL. |
| **UT-10** | `url()` | `url(['cat' => '', 'p' => ''])` | `'index.php'` | Verificar limpieza total de parámetros vacíos. |

### Módulo 3: Seguridad, Sesiones y Protección CSRF (`config.php`)

| ID | Función Probada | Caso / Entrada | Resultado Esperado | Objetivo |
|---|---|---|---|---|
| **UT-11** | `csrf()` | Dos invocaciones consecutivas en la misma sesión | Cadena hexadecimal de 32 caracteres, idéntica en ambas llamadas | Confirmar entropía y persistencia del token CSRF. |
| **UT-12** | `csrf_field()` | Invocación con sesión activa | `<input type="hidden" name="csrf" value="...">` con el token activo | Validar generación del campo oculto en formularios. |
| **UT-13** | `is_admin()` | `$_SESSION['admin']` vacío vs. `true` | `false` sin autenticar; `true` autenticado | Comprobar guardia de acceso administrativo. |
| **UT-14** | `flash()` | `flash('Guardado correctamente.', 'ok')` | `$_SESSION['flash'] === ['Guardado correctamente.', 'ok']` | Validar almacenamiento de notificaciones *flash*. |

### Módulo 4: Gestión Segura de Imágenes (`includes/fotos.php`)

| ID | Función Probada | Caso / Entrada | Resultado Esperado | Objetivo |
|---|---|---|---|---|
| **UT-15** | `foto_codigo()` | `'A001'`, `'KLR_650-01'` | `'A001'`, `'KLR_650-01'` | Permitir códigos alfanuméricos válidos. |
| **UT-16** | `foto_codigo()` | `'../../etc/passwd'`, `'A001; rm -rf /'` | `'etcpasswd'`, `'A001rm-rf'` (sin `/` ni `..`) | Bloquear ataques de *Path Traversal* e inyección en rutas. |
| **UT-17** | `foto()` | `''` y `'CODIGO_INEXISTENTE_99999'` | `null` | Evitar rutas rotas cuando un repuesto no tiene foto. |
| **UT-18** | `guardar_foto()` | `['error' => UPLOAD_ERR_NO_FILE]` | Retorno limpio sin excepción | Permitir guardar productos sin adjuntar fotografía. |
| **UT-19** | `guardar_foto()` | Archivo simulado de 4 MB | Lanza `RuntimeException` (`'La imagen supera los 3 MB.'`) | Validar límite máximo de peso de archivo. |
| **UT-20** | `guardar_foto()` | Archivo de texto plano simulando imagen | Lanza `RuntimeException` (`'Formato no permitido...'`) | Verificar validación real de MIME con `getimagesize()`. |

### Módulo 5: Integridad del Esquema CRUD Genérico (`admin/includes/schema.php`)

| ID | Componente Probado | Validación Ejecutada | Resultado Esperado | Objetivo |
|---|---|---|---|---|
| **UT-21** | `schema.php` | Conteo y nombres de tablas registradas | Exactamente las 7 tablas (`productos`, `clientes`, `proveedores`, `trabajadores`, `entradas`, `ventas`, `detalle_venta`) | Garantizar cobertura completa del modelo relacional. |
| **UT-22** | `schema.php` | Claves obligatorias (`label`, `pk`, `auto`, `list`, `search`, `fields`) y existencia de `pk` en `fields` | Válido en las 7 tablas | Prevenir fallos en tiempo de ejecución en `admin/tabla.php`. |
| **UT-23** | `schema.php` | Existencia de todas las columnas de `list` y `search` dentro de `fields` | 100 % de correspondencia | Evitar columnas indefinidas en listados y búsquedas. |
| **UT-24** | `schema.php` | Integridad de referencias `fk` | Todas las tablas y columnas destino existen en el esquema | Asegurar consistencia referencial en formularios. |

### Módulo 6: Reglas de Negocio, Finanzas y Transacciones

| ID | Componente Probado | Validación Ejecutada | Resultado Esperado | Objetivo |
|---|---|---|---|---|
| **UT-25** | Reglas financieras (`admin/index.php`, `admin/nueva_venta.php`) | Líneas de venta (2×$80.000 + 1×$60.000), utilidad bruta y valoración de inventario | Total `$220.000`, utilidad `$81.000`, valoración exacta | Validar precisión aritmética de totales y KPIs. |
| **UT-26** | `next_id()` y transacción ACID (`beginTransaction` / `rollBack`) | Ejecución sobre PDO SQLite en memoria: cálculo secuencial de ID y reversión completa ante stock insuficiente | `next_id` incremental (`1` → `2`) y stock intacto tras `rollBack()` | Comprobar atomicidad y protección contra sobreventa sin tocar la BD real. |

---

## 4. Salida Esperada en Consola

```text
================================================================
  M-PARTS — SUITE DE PRUEBAS UNITARIAS (PHP NATIVO)
================================================================

[Modulo 1: Helpers de formato, presentacion y sanitizacion (config.php)]
  [PASS] UT-01: h() escapa etiquetas HTML y comillas (prevencion XSS)
  [PASS] UT-02: cop() formatea montos en pesos colombianos (COP)
  ...
[Modulo 6: Reglas de negocio, calculos financieros y transacciones]
  [PASS] UT-25: Calculo de total de venta, utilidad bruta y valoracion de inventario
  [PASS] UT-26: next_id() y rollback ACID ante stock insuficiente (PDO en memoria)

----------------------------------------------------------------
  RESUMEN: 26 aprobadas | 0 fallidas | 0 omitidas
  ESTADO:  TODAS LAS PRUEBAS PASARON CORRECTAMENTE
----------------------------------------------------------------
```
