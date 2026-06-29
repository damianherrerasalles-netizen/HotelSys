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
| 7 | Módulo de Reservas | 🔄 Próximo |