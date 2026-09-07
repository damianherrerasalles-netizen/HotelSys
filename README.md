# HotelSys — Sistema de Gestión Hotelera
**Hotel Plaza Hostal · Yarumal, Antioquia**  
Aprendiz: Robinson Damian Herrera Betancurt · SENA Ficha 3262266

---

## Tecnologías
- PHP 8.1 + MySQL 8.0
- HTML5 / CSS3 / JavaScript
- XAMPP (entorno local Windows)

---

## Base de datos — Semanas 3 y 4
Esquema MySQL con 6 tablas: clientes, habitaciones, reservas, personal, inventario, facturas.

| Archivo | Descripción |
|---|---|
| hotelsys_schema_v1.sql | Script de creación de tablas |
| hotelsys_seed_data.sql | Datos de prueba del hotel |

---

## Wireframes — Semana 5
Diseños de interfaz previos al desarrollo. Generados con `wireframes_hotelsys.py` (Python + matplotlib).

| Pantalla | Archivo | Acceso |
|---|---|---|
| Login | wireframes/wireframe_login.png | admin + recepcionista |
| Dashboard | wireframes/wireframe_dashboard.png | solo admin |
| Reservas | wireframes/wireframe_reservas.png | admin + recepcionista |
| Habitaciones | wireframes/wireframe_habitaciones.png | admin + recepcionista |

---

## Estado del proyecto

| Semana | Actividad | Estado |
|---|---|---|
| 1–2 | Configuración inicial y onboarding | ✅ Completado |
| 3–4 | Diseño BD MySQL | ✅ Completado |
| 5 | Wireframes de interfaz de usuario | ✅ Completado |
| 6 | Módulo de autenticación PHP con roles | 🔄 Próximo |



## Autenticación — Semana 6

Sistema de login con control de roles implementado en PHP 8.1.

### Roles del sistema

| Rol | Email | Acceso |
|---|---|---|
| administrador | admin@hotelplazahostal.com | Dashboard + todos los módulos |
| recepcionista | recep@hotelplazahostal.com | Reservas, Habitaciones, Clientes |

### Archivos del módulo de autenticación

| Archivo | Descripción |
|---|---|
| `config/db.php` | Conexión PDO a MySQL con manejo de errores |
| `includes/auth.php` | Lógica de login con password_verify() |
| `includes/check_auth.php` | Middleware de protección de rutas |
| `includes/logout.php` | Cierre de sesión seguro |
| `views/login.php` | Formulario de acceso |
| `views/dashboard.php` | Panel ejecutivo — solo administrador |
| `views/reservas.php` | Vista principal del recepcionista |
| `views/acceso_denegado.php` | Vista para acceso sin permisos |

### Seguridad implementada
- PDO con prepared statements — previene inyección SQL
- password_hash() / password_verify() — contraseñas nunca en texto plano
- session_destroy() en logout — destruye cookie de sesión
- Middleware en cada vista — sin acceso directo por URL

### Estado del proyecto

| Semana | Actividad | Estado |
|---|---|---|
| 1–2 | Configuración inicial y onboarding | ✅ Completado |
| 3–4 | Diseño BD MySQL | ✅ Completado |
| 5 | Wireframes de interfaz de usuario | ✅ Completado |
| 6 | Módulo de autenticación PHP con roles | ✅ Completado |
| 7 | Módulo de Reservas | ✅ Completado |
| 11 | Módulo de Inventario con alertas de stock crítico | 🔄 En curso — Día 1 |

---

## Inventario — Semana 11 (Actividad VIII del cronograma)

El modelo de datos del inventario (tabla `inventario` + vista `v_stock_critico`)
ya existía desde la Semana 4. El Día 1 de esta semana auditó ese diseño contra
las respuestas reales del hostal (formulario de levantamiento, P10–P11) y
desarrolló el módulo PHP completo sobre esa base.

### Hallazgo de la auditoría
La categoría **Bebidas** (insumos de minibar: agua, gaseosa, café) estaba en las
respuestas del hostal pero no en el ENUM original de `categoria`. Se agregó vía
`hotelsys_migration_semana11_inventario.sql`, junto con 3 ítems de ejemplo.

### Archivos del módulo de inventario

| Archivo | Descripción |
|---|---|
| `hotelsys_migration_semana11_inventario.sql` | Migración: agrega categoría "Bebidas" + datos de ejemplo |
| `views/inventario.php` | Listado con filtros, indicador de stock crítico y aviso superior |
| `views/inventario_form.php` | Formulario de alta/edición de insumos (solo admin) |
| `modules/inventario/inventario_procesar.php` | Procesa alta/edición con validaciones |
| `modules/inventario/inventario_estado.php` | Activa/desactiva un insumo (baja lógica) |

### Widget de alertas en el Dashboard
Se agregó una tarjeta KPI en `views/dashboard.php` que muestra el número de
insumos en stock crítico en tiempo real y enlaza al listado filtrado —
respondiendo a la Prioridad 2 del Dashboard definida en el levantamiento
de información (P15).

### Corrección aplicada de paso
`views/personal.php` enlazaba a `assets/css/style.css`, un archivo que nunca
existió (el real es `assets/css/estilos.css`), por lo que sus estilos de
botones y contenedor no cargaban. Se corrigió la referencia y se agregaron a
`estilos.css` las clases genéricas (`.contenedor`, `.btn`, `.btn-primario`,
etc.) que esa vista y la nueva de inventario usan.
