# 🏍️ M-PARTS — Tienda Virtual y Panel de Administración

**M-PARTS** es una aplicación web full-stack desarrollada en **PHP nativo y MySQL/MariaDB** para la gestión comercial y catálogo en línea de un negocio de repuestos y accesorios para motocicletas (**KLR 650, DR 650 y V-Strom**) en Cúcuta, Norte de Santander, Colombia.

> **Proyecto Integrador · FESC (Fundación de Estudios Superiores Comfanorte) · 2026**  
> 🌐 **Demo en línea:** [https://mparts-fesc.free.je/mparts/](https://mparts-fesc.free.je/mparts/?i=1)

---

## 📚 Documentación Técnica del Repositorio

| Documento | Descripción |
|---|---|
| 🏛️ [`docs/ARQUITECTURA.md`](docs/ARQUITECTURA.md) | Arquitectura del sistema, capas, diagramas Mermaid, modelo Entidad-Relación de las 7 tablas, flujos transaccionales y seguridad. |
| 🧪 [`docs/PRUEBAS_UNITARIAS.md`](docs/PRUEBAS_UNITARIAS.md) | Estrategia de pruebas, guía de ejecución en consola y matriz de los 26 casos de prueba unitarios automatizados. |
| ⚙️ [`tests/run_tests.php`](tests/run_tests.php) | Suite ejecutable de pruebas unitarias en PHP nativo (sin dependencias externas). |

---

## ✨ Funcionalidades Principales

### 🛒 Tienda Virtual Pública
- **Catálogo dinámico:** 195 productos organizados en 8 categorías con precios en pesos colombianos (COP) y paginación de 12 productos por página.
- **Búsqueda y filtrado:** Búsqueda por nombre o código interno (ej. `A001`), filtro por categoría, opción *"Solo disponibles"* y ordenamiento por precio o nombre (A–Z).
- **Indicadores de inventario:** Etiquetas automáticas según existencias (`Disponible`, `Últimas unidades` cuando stock ≤ 5 y `Agotado` cuando stock ≤ 0).
- **Detalle de producto:** Ficha individual con indicador visual de stock, productos relacionados de la misma categoría y enlace directo de pedido.
- **Carrito persistente e integración con WhatsApp:** Carrito almacenado en `localStorage` (`assets/js/cart.js`) con control contra sobreventa y confirmación directa por WhatsApp (`wa.me`).
- **Gestión inteligente de imágenes:** Carga automática por código (`assets/img/productos/<CODIGO>.jpg|png|webp`) con respaldo visual por categoría.
- **Diseño adaptable:** Interfaz *responsive* para dispositivos móviles y escritorio.

### 🛠️ Panel de Administración (`/admin`)
- **Autenticación segura:** Inicio de sesión protegido con sesiones PHP, regeneración de ID y protección CSRF.
- **Dashboard de indicadores (KPIs):** Ventas totales, compras de mercancía, utilidad bruta estimada (`subtotal − cantidad × costo`), valor del inventario (a costo y a precio de venta), ventas por mes, métodos de pago, Top 5 productos más vendidos y alertas de stock bajo.
- **CRUD genérico para 7 tablas:** Administración centralizada de `productos`, `clientes`, `proveedores`, `trabajadores`, `entradas`, `ventas` y `detalle_venta` dirigida por `admin/includes/schema.php`, incluyendo carga y borrado de fotografías de productos.
- **Registro transaccional de ventas (`admin/nueva_venta.php`):** Descuenta `stock_actual` e incrementa `ventas_registradas` dentro de una transacción ACID con bloqueo `FOR UPDATE`.
- **Registro transaccional de entradas (`admin/nueva_entrada.php`):** Suma existencias al inventario y actualiza el `costo_unitario`.
- **Historial de movimientos (`admin/movimientos.php`):** Consulta unificada (`UNION ALL`) de ingresos (ventas) y egresos (entradas) con filtros por tipo y fecha.

---

## 🧰 Stack Tecnológico

| Capa | Tecnología |
|---|---|
| **Backend** | PHP 8.x nativo con extensión PDO |
| **Base de datos** | MySQL / MariaDB 10.4 (`m_parts`, motor InnoDB, `utf8mb4`) |
| **Frontend** | HTML5, CSS3 propio (`style.css`, `admin.css`), JavaScript puro (`cart.js`) |
| **Tipografía** | Google Fonts (`Barlow Condensed` e `Inter`) |
| **Servidor local** | XAMPP (Apache + MariaDB + phpMyAdmin) |
| **Publicación** | InfinityFree |
| **Integración externa** | WhatsApp (`https://wa.me`) |

---

## 📁 Estructura del Proyecto

```text
mparts/
├── index.php                  # Catálogo público: búsqueda, filtros, orden y paginación
├── producto.php               # Detalle de producto y sugerencias relacionadas
├── carrito.php                # Carrito de compras y confirmación por WhatsApp
├── config.example.php         # Plantilla de configuración (copiar a config.php)
├── .gitignore                 # Exclusión de config.php y credenciales sensibles
├── README.md                  # Documentación principal del repositorio
├── admin/                     # Módulo de administración protegido por sesión
│   ├── index.php              # Dashboard con KPIs y reportes financieros
│   ├── login.php / logout.php # Control de acceso y cierre de sesión
│   ├── tabla.php              # Motor CRUD genérico dirigido por schema.php
│   ├── nueva_venta.php        # Registro transaccional de ventas (descuenta stock)
│   ├── nueva_entrada.php      # Registro transaccional de compras (suma stock)
│   ├── movimientos.php        # Flujo de caja (ingresos vs. egresos)
│   └── includes/
│       ├── boot.php           # Arranque del panel, sesión y conexión PDO
│       ├── schema.php         # Definición declarativa de las 7 tablas del CRUD
│       ├── top.php            # Menú lateral y notificaciones flash
│       └── bottom.php         # Cierre de plantilla administrativa
├── includes/                  # Componentes reutilizables de la tienda pública
│   ├── header.php             # Barra de navegación y buscador global
│   ├── footer.php             # Pie de página, contacto, redes y acceso admin
│   └── fotos.php              # Búsqueda, validación, guardado y borrado de fotos
├── assets/                    # Recursos estáticos
│   ├── css/                   # style.css (tienda) y admin.css (panel)
│   ├── js/                    # cart.js (carrito en localStorage)
│   └── img/                   # logo.jpg y carpeta productos/<CODIGO>.jpg|png|webp
├── docs/                      # Documentación técnica en Markdown
│   ├── ARQUITECTURA.md        # Diseño arquitectónico, diagramas y base de datos
│   └── PRUEBAS_UNITARIAS.md   # Especificación y matriz de pruebas unitarias
└── tests/                     # Pruebas automatizadas
    └── run_tests.php          # Suite de 26 pruebas unitarias en PHP CLI
```

---

## 🚀 Instalación y Ejecución Local (XAMPP)

1. **Clonar el repositorio** dentro de `C:\xampp\htdocs`:
   ```bash
   git clone [https://github.com/miguelangelmarquezcarrillo61-glitch/mparts.git](https://github.com/miguelangelmarquezcarrillo61-glitch/mparts.git)
   cd mparts
   ```
2. **Importar la base de datos:**
   - Inicia **Apache** y **MySQL** en XAMPP.
   - Abre `http://localhost/phpmyadmin`, crea la base de datos `m_parts` (`utf8mb4_general_ci`) e importa el respaldo `m_parts.sql`.
3. **Configurar credenciales locales:**
   - Copia `config.example.php` y renómbralo a `config.php`.
   - Ajusta `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `WHATSAPP`, `ADMIN_USER` y `ADMIN_PASS`.
   > ⚠️ **Seguridad:** `config.php` está excluido mediante `.gitignore` para no exponer contraseñas ni datos de contacto reales en GitHub.
4. **Abrir la aplicación:**
   - Tienda pública: `http://localhost/mparts/`
   - Panel administrador: `http://localhost/mparts/admin/`

---

## 🧪 Ejecutar las Pruebas Unitarias

Desde la raíz del proyecto:

```bash
# En Windows con XAMPP
C:\xampp\php\php.exe tests/run_tests.php

# Si PHP está en el PATH del sistema
php tests/run_tests.php
```

Consulta el detalle de los 26 casos de prueba en [`docs/PRUEBAS_UNITARIAS.md`](docs/PRUEBAS_UNITARIAS.md).

---

## 👥 Autoría

- **Institución:** Fundación de Estudios Superiores Comfanorte (FESC) — Cúcuta, Colombia
- **Proyecto:** Proyecto Integrador — M-PARTS (2026)
- **Integrantes:** *(Escribe aquí los nombres completos de los integrantes del equipo)*
