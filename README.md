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
| 11–12 | Módulo de Inventario con alertas de stock crítico (Kardex, alertas automáticas, reporte exportable, validaciones) | ✅ Completado |
| 13–14 | Dashboard ejecutivo con KPIs en tiempo real (mapa de habitaciones, alertas de inventario, caja del día y tareas del personal) | 🔄 En progreso (Semana 13 completada) |

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

### Verificación funcional — Días 2 a 4
Con el módulo ya construido (Día 1), el resto de la semana se dedicó a probarlo
sobre datos reales del Hotel Plaza Hostal:

- **Migración y estructura (Día 2):** se ejecutó `hotelsys_migration_semana11_inventario.sql`
  en phpMyAdmin y se verificó con `DESCRIBE inventario` y `SELECT * FROM v_stock_critico`
  que la categoría Bebidas y los 3 insumos de ejemplo quedaron correctamente cargados.
- **Filtros del listado (Día 3):** se probó `views/inventario.php` sin filtros (15 insumos,
  6 críticos), con búsqueda por proveedor ("Suministros Yarumal" → 5 resultados) y con
  filtro por categoría ("Oficina" → 1 resultado), confirmando que el indicador de stock
  crítico se recalcula correctamente en cada caso.
- **CRUD y Dashboard (Día 4):** se probó el alta de un insumo nuevo, la edición de uno
  existente (cambiando su stock mínimo para forzar el estado crítico), y la
  activación/desactivación (baja lógica) de otro insumo — los tres a través del formulario
  y el procesador construidos en el Día 1. Se verificó además que el conteo de la tarjeta
  de stock crítico del Dashboard se mantiene sincronizado con estos cambios.

Evidencia detallada de cada día en la bitácora de la Semana 11 (`HotelSys_Semana11.pdf` y
`Evidencia_Semana11_Dia1..4_HotelSys.pdf`).

---

## Inventario — Semana 12 (continuación de la Actividad VIII)

El cronograma asignó 2 semanas a la Actividad VIII, pero el desarrollo inicial
(Semana 11) se completó en 1. En vez de adelantar la Actividad IX, la Semana
12 profundizó el módulo de Inventario en 4 frentes, día por día.

### Día 1 — Historial de movimientos (Kardex)
Hasta la Semana 11, el formulario de edición permitía cambiar `stock_actual`
directamente, sin dejar rastro de por qué cambió. Ahora todo cambio de stock
(fuera del alta inicial de un insumo) debe registrarse como un movimiento de
entrada o salida.

| Archivo | Descripción |
|---|---|
| `hotelsys_migration_semana12_kardex.sql` | Crea `movimientos_inventario` (entrada/salida, motivo, cantidad, stock resultante, usuario, fecha) |
| `modules/inventario/movimiento_registrar.php` | Registra un movimiento dentro de una transacción con `SELECT ... FOR UPDATE`, evita dejar el stock en negativo |
| `views/inventario_kardex.php` | Página por insumo: stock actual, formulario de movimiento y los últimos 15 movimientos |

`inventario_form.php` deshabilita el campo de stock al editar (con enlace al
Kardex), e `inventario_procesar.php` excluye `stock_actual` de su `UPDATE` —
la única forma de cambiar el stock de un insumo existente es registrando un
movimiento.

### Día 2 — Alertas automáticas de stock crítico
Acerca el sistema al requerimiento original del hostal: un mecanismo que
compare stock actual vs. mínimo y genere alertas (P10–P11 del levantamiento
de información).

| Archivo | Descripción |
|---|---|
| `hotelsys_migration_semana12_dia2_alertas.sql` | Crea `alertas_inventario` (estado, stock y mínimo al generar, fechas, atendida por) |
| `modules/inventario/alerta_atender.php` | Marca una alerta abierta como atendida manualmente |
| `views/alertas_inventario.php` | Alertas abiertas (con botón "Marcar atendida") e historial de las últimas cerradas |

Dentro de la misma transacción de `movimiento_registrar.php`: si el stock
resultante queda en o bajo el mínimo y no hay ya una alerta abierta para ese
insumo, se crea una; si el stock se recupera por encima del mínimo, la alerta
abierta se cierra sola como "resuelta automáticamente".

### Día 3 — Reporte exportable de inventario
| Archivo | Descripción |
|---|---|
| `modules/inventario/inventario_exportar.php` | Genera un CSV (con BOM UTF-8, para que tildes y Ñ se vean bien en Excel) respetando los filtros activos de la pantalla |

Botón "Exportar CSV" en `views/inventario.php`, junto a "+ Nuevo Insumo".
Columnas: nombre, categoría, unidad, stock actual, stock mínimo, estado de
stock, precio unitario, proveedor, teléfono, última compra y estado.

### Día 4 — Validaciones reforzadas y pruebas
| Archivo | Descripción |
|---|---|
| `includes/validaciones_inventario.php` | Funciones reutilizables: nombre, categoría, enteros/decimales no negativos, teléfono de proveedor y longitud de observaciones |
| `tests/test_validaciones_inventario.php` | Script de pruebas por consola (`php tests/test_validaciones_inventario.php`) — 28 casos válidos e inválidos, todos en PASS |

`inventario_procesar.php` y `movimiento_registrar.php` ahora comparten estas
funciones; además se rechaza un insumo con el mismo nombre que otro insumo
activo ya existente.

### Día 5 — Pruebas integrales y cierre
Recorrido completo verificado en el navegador con un insumo de prueba: alta →
salida que genera una alerta automática → confirmación en Alertas de
Inventario → entrada que la cierra sola → confirmación como "auto-resuelta" →
edición con el stock bloqueado → rechazo por nombre duplicado → exportación a
CSV — cerrando la Actividad VIII a través de las Semanas 11 y 12.

Evidencia detallada de cada día en `Evidencia_Semana12_Dia1..5_HotelSys.pdf`.

---

## Dashboard ejecutivo — Semana 13 (Actividad IX, continúa en Semana 14)

El levantamiento de información (P15) pidió tres widgets concretos para el
Dashboard ejecutivo. El Día 1 auditó lo que ya existía (KPIs con datos reales
desde semanas anteriores, pero ningún widget en tiempo real) y confirmó que
el módulo de Facturación (Actividad X) todavía no existe, definiendo que el
widget de caja mostraría el valor real ($0) en vez de simularlo.

### Los tres widgets

| Widget | Día | Descripción |
|---|---|---|
| 1. Mapa de habitaciones | Día 2 | Cuadrícula de las 24 habitaciones coloreada por estado real (Disponible/Ocupada/Reservada/Mantenimiento), reutilizando `colorEstadoHabitacion()` extraída a `includes/habitaciones_helpers.php` |
| 2. Alertas de Inventario | Día 3 | Panel lateral (junto al mapa, ~60/40) con las alertas abiertas más antiguas, reemplazando la tarjeta KPI que existía desde la Semana 12 |
| 3. Caja del día y Tareas | Día 4 | Suma real de facturas pagadas hoy (honestamente $0 sin Facturación) + % de tareas del personal completadas hoy |

### Archivos del Widget 3 (tabla nueva)

| Archivo | Descripción |
|---|---|
| `hotelsys_migration_semana13_dia4_tareas.sql` | Crea `tareas_personal` (id_personal FK, descripción, fecha, completada, fecha_completada, creada_por) |
| `views/tareas_personal.php` | Registro, listado por fecha y toggle de tareas del personal |
| `modules/personal/tarea_procesar.php` | Valida y registra una tarea nueva |
| `modules/personal/tarea_estado.php` | Marca una tarea como completada o la reabre |

### Bug encontrado y corregido — zona horaria PHP vs. MySQL
Al verificar el Widget 3 en vivo (Día 4), una tarea completada para "hoy" no
aparecía en el Dashboard. La causa: el proyecto no fijaba la zona horaria de
PHP, que por defecto calculaba la fecha en UTC, mientras que MySQL
(`CURDATE()`, `NOW()`) usaba la hora real del sistema (Bogotá, UTC-5). De
noche, ese desfase de 5 horas hacía que PHP ya "viera" el día siguiente
mientras MySQL seguía en el día actual. Se corrigió agregando
`date_default_timezone_set('America/Bogota');` en `config/db.php` (cargado
por todas las páginas), beneficiando no solo al Widget 3 sino a cualquier
módulo futuro que dependa de "hoy" (reservas, alertas, facturación).

### Día 5 — Integración y cierre de la semana
Recorrido en vivo con los tres widgets ya integrados: navegación desde el
mapa de habitaciones hasta el detalle de una habitación, desde el panel de
alertas hasta la vista completa de Alertas de Inventario, y desde el panel
de tareas hasta la gestión completa — confirmando datos consistentes entre
cada widget y su vista de detalle.

Evidencia detallada de cada día en `Evidencia_Semana13_Dia1..5_HotelSys.pdf`.
La Semana 14 continúa y cierra la Actividad IX.
