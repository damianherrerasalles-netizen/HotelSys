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
| 13–14 | Dashboard ejecutivo con KPIs en tiempo real (mapa de habitaciones, alertas de inventario, caja del día y tareas del personal, actualización automática cada 30 segundos) | ✅ Completado |
| 15–16 | Módulo de Facturación con IVA automático (cargos extra, factura automática al checkout, detalle/impresión, listado y pago, cierre de caja diario con alerta de descuadre) | ✅ Completado |
| 17–18 | Módulo de Reportes con gráficas e indicadores (ocupación, ingresos y costo de mantenimiento por habitación/mes, con filtro de fechas personalizado y gráficas Chart.js) | ✅ Completado |

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

---

## Dashboard ejecutivo — Semana 14 (cierre de la Actividad IX)

Los tres widgets del levantamiento de información ya estaban construidos al
cerrar la Semana 13. La Semana 14 no arrancó la Actividad X: en su lugar
profundizó el Dashboard para que cumpliera de verdad la promesa de "tiempo
real" de su nombre — el mismo patrón que la Semana 12 aplicó sobre Inventario.

### Día 1 — Auditoría y decisiones de diseño
Sin código: se confirmó que el Dashboard solo se actualizaba al recargar la
página manualmente y que la tarjeta KPI "Reservas hoy" seguía siendo un
placeholder desde la Semana 6. Se definieron tres decisiones: "Reservas hoy"
cuenta los check-ins programados para hoy (`fecha_entrada = CURDATE()`, no
reservas activas); el refresco ocurre cada 30 segundos; y la actualización es
parcial vía JavaScript (`fetch()`), no una recarga completa de la página.

### Día 2 — Función de datos compartida, endpoint JSON y KPI real
| Archivo | Descripción |
|---|---|
| `includes/dashboard_datos.php` | Función `obtenerDatosDashboard()` — reúne las ~10 consultas del Dashboard (antes repetidas dentro de `dashboard.php`) en un solo lugar |
| `modules/dashboard/dashboard_datos.php` | Endpoint JSON que reutiliza esa misma función, con el mismo control de acceso de administrador |

`views/dashboard.php` pasó a llamar `obtenerDatosDashboard()` en vez de tener
las consultas inline, y la tarjeta "Reservas hoy" quedó con su valor real —
completada antes de lo previsto, ya que la nueva función la dejaba lista.

### Día 3 — Actualización automática en el navegador
Se agregaron atributos `id` a cada elemento dinámico del Dashboard y un
`<script>` en `views/dashboard.php` que, cada 30 segundos, consulta el
endpoint del Día 2 y reconstruye cada widget con JavaScript (una función por
sección: KPIs, mapa de habitaciones, alertas, ocupación y Widget 3), con un
helper `escaparHtml()` equivalente a `htmlspecialchars()` y un indicador
visible de "Última actualización".

### Día 4 — Verificación cruzada entre módulos
Se comprobó, con cambios reales hechos desde otras pantallas (el estado de
una habitación desde su vista de detalle, una tarea marcada como completada
desde Personal), que el Dashboard los refleja solo dentro del mismo ciclo de
30 segundos, sin recargar. En el camino apareció una falla intermitente
(el mapa no se actualizaba ni aparecía el indicador); se descartó como error
de código revisando el endpoint directamente (siempre devolvió datos
correctos) y la consola del navegador (sin errores propios) — la causa era
una copia en caché del `dashboard.php` anterior al Día 3, resuelta con una
carga fresca.

### Día 5 — Integración final y cierre
Recorrido completo en una sola carga: los 6 KPIs, los tres widgets y el
ciclo de actualización automática funcionando juntos sin inconsistencias,
cerrando la Actividad IX a través de las Semanas 13 y 14.

Evidencia detallada de cada día en `Evidencia_Semana14_Dia1..5_HotelSys.pdf`.

---

## Facturación — Semana 15 (Actividad X, cierre en Semana 16)

El levantamiento de información (P12–P14) pidió IVA automático, cargos extra
sobre la estadía (minibar, daños, late check-out) y un cierre de caja diario
por método de pago con alerta de descuadre. La tabla `facturas` ya existía
desde la Semana 4 (1:1 con `reservas`) y ya era consultada por el Widget 3 del
Dashboard desde la Semana 13 — pero ningún módulo ni vista existía todavía
para generarla o gestionarla: toda la Semana 15 se construyó desde cero sobre
esa base.

### Día 1 — Auditoría y decisiones de diseño
Sin código: se confirmó que no existía la tabla `cargos_extras` ni archivos
bajo `modules/facturacion/`. Se definieron tres decisiones: (1) la factura se
genera automáticamente al finalizar una reserva (checkout), cuando ya se
conocen todos los cargos; (2) los cargos extra viven en una tabla nueva
(`cargos_extras`, con tipo Minibar/Daño/Penalización) sumada al subtotal antes
del IVA; (3) el nivel de cumplimiento DIAN se queda en NIT + fecha + desglose
de IVA + una leyenda visible de "documento de prueba, sin validez tributaria
real" — sin CUFE real, ya que eso requiere un proveedor de facturación
electrónica certificado por la DIAN, fuera del alcance académico.

### Día 2 — Cargos extra y factura automática al checkout

| Archivo | Descripción |
|---|---|
| `hotelsys_migration_semana15_dia2_cargos_extras.sql` | Crea `cargos_extras` (tipo ENUM Minibar/Daño/Penalización, valor, FK a `reservas` en cascada, FK a `personal` a SET NULL) |
| `includes/facturacion_helpers.php` | `resolverIdPersonalDesdeSesion()` (resuelve qué colaborador hizo la operación) y `generarFacturaAutomatica()` (subtotal = habitación + cargos extra, IVA 19%, total) |
| `views/cargo_extra_form.php` | Formulario de cargo extra sobre una reserva Activa, con el historial de cargos ya registrados |
| `modules/facturacion/cargo_extra_registrar.php` | Procesa y valida el formulario anterior |
| `views/reservas.php` | Enlace "+ Cargo extra" junto a "Finalizar (check-out)" |
| `modules/reservas/reserva_actualizar_estado.php` | Al finalizar una reserva, genera la factura automáticamente dentro de la misma transacción |

**Bug encontrado y corregido:** la primera prueba en vivo falló con un error
genérico en ambos flujos. Sin acceso a los logs de XAMPP, se expuso
temporalmente el mensaje técnico real, revelando `SQLSTATE[HY000] 1267`
("Illegal mix of collations") en el `JOIN` de `resolverIdPersonalDesdeSesion()`
— las columnas `usuarios.email` y `personal.email` tenían collations
distintas porque se crearon en sesiones de trabajo diferentes. Se corrigió
forzando `COLLATE utf8mb4_unicode_ci` en ambos lados del `JOIN`, sin alterar
las tablas `usuarios` ni `personal` (módulos ya cerrados).

### Día 3 — Detalle/impresión de factura, listado y pago

| Archivo | Descripción |
|---|---|
| `views/factura_detalle.php` | Vista imprimible: NIT, leyenda de documento de prueba, desglose línea por línea (habitación + cada cargo extra), y formulario de pago si está Pendiente |
| `modules/facturacion/factura_pagar.php` | Marca una factura Pendiente como Pagada (Efectivo/Transferencia/Tarjeta/Nequi — los 4 métodos reales del hostal según P14) |
| `views/facturas.php` | Listado con filtros (estado, método, rango de fechas, búsqueda) y tarjetas de resumen |
| `config/db.php` | Constantes `HOTEL_NOMBRE`, `HOTEL_DIRECCION`, `HOTEL_NIT` para la plantilla de factura |

Se conectó además la tarjeta "Ingresos del mes" del Dashboard — un
placeholder desde antes de que existiera este módulo — a la suma real de
facturas Pagadas del mes en curso.


### Día 4 — Cierre de caja diario y alerta de descuadre (P14)

| Archivo | Descripción |
|---|---|
| `hotelsys_migration_semana15_dia4_cierre_caja.sql` | Crea `cierres_caja`: una fila por fecha + método de pago, con `monto_sistema` (calculado), `monto_declarado` (contado por el personal) y `diferencia` como columna generada |
| `modules/facturacion/cierre_registrar.php` | Solo administrador; recalcula `monto_sistema` en el servidor en el momento del guardado (nunca confía en el formulario) |
| `views/cierre_caja.php` | Selector de fecha, resumen y tabla por método con el estado (sin cerrar / descuadre / cuadrada / parcial) |

Se usó `cierres_caja` como tabla de reconciliación por fecha+método, en vez
de la tabla `pagos` transacción-por-transacción que sugería el levantamiento,
porque en el modelo actual cada factura ya equivale a un único pago (1:1, sin
pagos parciales).

**Bug encontrado y corregido:** el cierre de caja del día actual quedaba
siempre bloqueado con "Fecha inválida". Causa: `DateTime::createFromFormat()`
sin un componente de hora en el formato rellena la hora actual del reloj, no
medianoche, mientras que `new DateTime('today')` siempre es medianoche — la
fecha de hoy, en cualquier momento después de medianoche, siempre parecía
"futura". Se corrigió comparando las fechas como texto `Y-m-d` en vez de como
objetos `DateTime`.

### Día 5 — Recorrido de integración y cierre de la semana
Prueba de punta a punta con una reserva nueva: cargo extra → checkout con
factura automática → detalle/impresión → pago → "Ingresos del mes" →
cierre de caja, verificando que las cuatro piezas de la semana funcionan como
un solo flujo. Sin errores en esta prueba — cierra la construcción de la
Actividad X (el commit y push de este README se hace en la Semana 16, mismo
patrón de cierre que las Actividades VIII y IX).

Evidencia detallada de cada día en `Evidencia_Semana15_Dia2..5_HotelSys.pdf`
(el Día 1 fue solo de diseño, sin pruebas en vivo).

---

## Reportes — Semana 17 (Actividad XI, cierre en Semana 18)

El levantamiento de información (P6–P7) pedía reportes de ocupación e
ingresos; el Día 1 de esta semana amplió el alcance a un tercer reporte
(costo de mantenimiento por habitación/mes) para cerrar una brecha que había
quedado pendiente desde entonces, ya que la tabla `mantenimientos` todavía no
existía — solo `habitaciones.estado` tenía el valor `Mantenimiento`, sin
ningún registro de costo ni historial. Chart.js (vía CDN) se usó como
librería de gráficas, ya definida en la propuesta del proyecto.

### Día 1 — Auditoría y decisiones de diseño
Sin código: se confirmó que no existían ni la tabla `mantenimientos` ni
ningún archivo de reportes. Se definieron cuatro decisiones: (1) el alcance
se amplía a 3 reportes, no solo ocupación e ingresos; (2) los tres reportes
filtran por un rango de fechas personalizado (desde/hasta), el mismo patrón
ya usado en `facturas.php`; (3) la brecha de costo de mantenimiento se cierra
esta semana con una tabla `mantenimientos` nueva (habitación, motivo, fechas
de inicio/fin, responsable, costo) y un CRUD de registro, marcando la
habitación en 'Mantenimiento' mientras el registro esté abierto; (4) el
tercer reporte usa exactamente esa tabla nueva.

### Día 2 — Módulo de Mantenimiento (tabla, CRUD y auto-estado)

| Archivo | Descripción |
|---|---|
| `hotelsys_migration_semana17_dia2_mantenimientos.sql` | Crea `mantenimientos` (habitación, responsable, motivo, fechas de inicio/fin, costo — `fecha_fin` nula = en curso) + vista `v_mantenimientos_en_curso` |
| `includes/mantenimiento_helpers.php` | Marca/libera una habitación de Mantenimiento reutilizando las reglas ya validadas del módulo de Habitaciones |
| `views/mantenimiento_form.php` / `mantenimientos.php` | Registro y listado con filtros, tarjetas de resumen y cierre en línea |

**Hallazgo corregido:** el botón rápido de cambio de estado que ya existía en
`views/habitaciones.php` (de antes de esta semana) cambiaba una habitación a
Mantenimiento sin crear ningún registro en la tabla nueva, lo que habría
dejado huecos en el reporte de costos del Día 5. Se corrigió para que ese
botón también inserte/cierre un registro mínimo en `mantenimientos`, y se
aplicó una corrección retroactiva a la única habitación afectada por pruebas
anteriores.

### Día 3 — Reporte de Ocupación

| Archivo | Descripción |
|---|---|
| `views/reportes.php` | Hub con las 3 tarjetas de reportes |
| `views/reporte_ocupacion.php` | % de ocupación por tipo de habitación en el rango de fechas elegido, con gráfica Chart.js |

Metodología: noches-habitación ocupadas (reservas Activa/Finalizada,
recortadas en los bordes del rango) ÷ noches-habitación disponibles, por
tipo de habitación. El resultado del rango por defecto pareció casi nulo al
probarlo por primera vez; se verificó contra una consulta SQL real de
`reservas` antes de tocar el código, confirmando que el cálculo era correcto
desde el inicio — los datos de prueba simplemente estaban concentrados en
otro rango de fechas.

### Día 4 — Reporte de Ingresos

| Archivo | Descripción |
|---|---|
| `views/reporte_ingresos.php` | Ingresos facturados por método de pago en el rango elegido, con gráfica Chart.js |

Metodología: solo facturas en estado Pagada, filtradas por fecha de emisión
y agrupadas por los 4 métodos reales del hostal — generaliza a un rango de
fechas la misma lógica que `cierre_caja.php` ya usa para un solo día.
Verificado por comparación cruzada contra el cierre de caja del 06/10/2026,
ya confirmado en la evidencia de la Semana 15; coincidió exactamente, sin
hallazgos.

### Día 5 — Reporte de Costo de Mantenimiento y cierre de la semana

| Archivo | Descripción |
|---|---|
| `views/reporte_mantenimiento.php` | Costo de mantenimiento por habitación y por mes en el rango elegido, con gráfica Chart.js |

Metodología: filtra por `fecha_inicio` del mantenimiento (igual criterio de
fecha única que usa Ingresos con `fecha_emision`), incluyendo los
mantenimientos en curso. El mismo patrón de verificación de los Días 3 y 4
se repitió una tercera vez: un resultado aparentemente incompleto resultó
ser, otra vez, un registro real fuera del rango de fechas probado, no un
error de código — confirmado contra la tabla `mantenimientos` antes de
cambiar nada.

Con los 3 reportes construidos, probados y verificados contra datos reales,
la Semana 17 cierra su construcción; el commit y push de este README se hace
en la Semana 18, mismo patrón de cierre que las Actividades VIII, IX y X.

Evidencia detallada de cada día en `Evidencia_Semana17_Dia2..5_HotelSys.pdf`
(el Día 1 fue solo de diseño, sin pruebas en vivo).
