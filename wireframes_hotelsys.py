"""
wireframes_hotelsys.py
Genera los 4 wireframes principales de HotelSys como imágenes PNG.
Requiere: matplotlib (pip install matplotlib)

Uso:
    python wireframes_hotelsys.py

Salida (carpeta wireframes/):
    wireframe_login.png
    wireframe_dashboard.png
    wireframe_reservas.png
    wireframe_habitaciones.png
"""

import matplotlib.pyplot as plt
import matplotlib.patches as patches
from matplotlib.patches import FancyBboxPatch
import os

# ── Configuración global ──────────────────────────────────────────────────────
CARPETA = "wireframes"
os.makedirs(CARPETA, exist_ok=True)

# Paleta de colores HotelSys
VERDE      = "#2E7D32"
VERDE_MED  = "#388E3C"
VERDE_CLAR = "#C8E6C9"
VERDE_FOND = "#F1F8E9"
GRIS       = "#F5F5F5"
GRIS_BORDE = "#BDBDBD"
GRIS_OSC   = "#757575"
BLANCO     = "#FFFFFF"
NEGRO      = "#212121"
ROJO       = "#F44336"
ROJO_CLAR  = "#FFEBEE"
AMARILLO   = "#FF9800"
AMAR_CLAR  = "#FFF3E0"
AZUL       = "#1565C0"
AZUL_CLAR  = "#E3F2FD"

DPI = 150  # resolución de exportación


# ── Utilidades de dibujo ──────────────────────────────────────────────────────

def rect(ax, x, y, w, h, color=BLANCO, borde=GRIS_BORDE, lw=1.0, radio=0.0):
    """Dibuja un rectángulo con borde."""
    r = FancyBboxPatch(
        (x, y), w, h,
        boxstyle=f"round,pad=0" if radio == 0 else f"round,pad={radio}",
        facecolor=color, edgecolor=borde, linewidth=lw
    )
    ax.add_patch(r)

def rect_simple(ax, x, y, w, h, color=BLANCO, borde=GRIS_BORDE, lw=1.0):
    r = patches.Rectangle((x, y), w, h, facecolor=color, edgecolor=borde, linewidth=lw)
    ax.add_patch(r)

def texto(ax, x, y, txt, size=9, color=NEGRO, bold=False, align="center", va="center"):
    weight = "bold" if bold else "normal"
    ax.text(x, y, txt, fontsize=size, color=color, fontweight=weight,
            ha=align, va=va, fontfamily="monospace" if False else "sans-serif")

def boton(ax, x, y, w, h, label, bg=VERDE, fg=BLANCO, size=9, lw=1.2):
    rect_simple(ax, x, y, w, h, color=bg, borde=bg, lw=lw)
    texto(ax, x + w/2, y + h/2, label, size=size, color=fg, bold=True)

def campo(ax, x, y, w, h, placeholder, bg=BLANCO, size=8.5):
    rect_simple(ax, x, y, w, h, color=bg, borde=GRIS_BORDE, lw=1.0)
    texto(ax, x + 0.012, y + h/2, placeholder, size=size, color=GRIS_OSC, align="left")

def linea_h(ax, x, y, w, color=GRIS_BORDE, lw=0.8):
    ax.plot([x, x+w], [y, y], color=color, linewidth=lw)

def sidebar(ax, x, y, w, h, items, activo=0):
    """Dibuja el sidebar con íconos y etiquetas."""
    rect_simple(ax, x, y, w, h, color=VERDE, borde=VERDE, lw=0)
    # Header sidebar
    rect_simple(ax, x, y + h - 0.06, w, 0.06, color=VERDE_MED, borde=VERDE_MED)
    texto(ax, x + w/2, y + h - 0.03, "☰  HotelSys", size=8, color=BLANCO, bold=True)

    alto_item = 0.055
    for i, (icono, label) in enumerate(items):
        iy = y + h - 0.06 - (i + 1) * alto_item
        bg = VERDE_MED if i == activo else VERDE
        rect_simple(ax, x, iy, w, alto_item, color=bg, borde=bg)
        texto(ax, x + 0.01, iy + alto_item/2, f"{icono}  {label}",
              size=7.5, color=BLANCO, align="left")


# ══════════════════════════════════════════════════════════════════════════════
# WIREFRAME 1 — LOGIN
# ══════════════════════════════════════════════════════════════════════════════

def wf_login():
    fig, ax = plt.subplots(figsize=(7, 9))
    ax.set_xlim(0, 1)
    ax.set_ylim(0, 1)
    ax.axis("off")
    fig.patch.set_facecolor(GRIS)
    ax.set_facecolor(GRIS)

    # Etiqueta de wireframe
    texto(ax, 0.5, 0.97, "WIREFRAME — login.php", size=8, color=GRIS_OSC, bold=False)
    linea_h(ax, 0.05, 0.955, 0.9, color=GRIS_BORDE)

    # Tarjeta central
    cx, cy, cw, ch = 0.18, 0.25, 0.64, 0.65
    rect_simple(ax, cx, cy, cw, ch, color=BLANCO, borde=GRIS_BORDE, lw=1.2)

    # Header verde de la tarjeta
    rect_simple(ax, cx, cy + ch - 0.10, cw, 0.10, color=VERDE, borde=VERDE)
    texto(ax, cx + cw/2, cy + ch - 0.04, "[H]  HotelSys", size=14, color=BLANCO, bold=True)
    texto(ax, cx + cw/2, cy + ch - 0.075, "Hotel Plaza Hostal — Yarumal", size=8, color=VERDE_CLAR)

    # Campos
    pad = 0.06
    fw = cw - 2*pad
    fx = cx + pad

    # Email
    texto(ax, fx, cy + 0.515, "Correo electrónico *", size=8, color=NEGRO, align="left", bold=True)
    campo(ax, fx, cy + 0.455, fw, 0.05, "  ejemplo@hotel.com", size=8.5)

    # Password
    texto(ax, fx, cy + 0.41, "Contraseña *", size=8, color=NEGRO, align="left", bold=True)
    campo(ax, fx, cy + 0.35, fw, 0.05, "  ••••••••••", size=8.5)

    # Mensaje de error
    rect_simple(ax, fx, cy + 0.29, fw, 0.044, color=ROJO_CLAR, borde=ROJO, lw=0.8)
    texto(ax, fx + fw/2, cy + 0.312, "[X]  Correo o contraseña incorrectos", size=8, color=ROJO)

    # Botón
    boton(ax, fx, cy + 0.21, fw, 0.058, "INICIAR SESIÓN", bg=VERDE, size=10)

    # Separador
    linea_h(ax, fx, cy + 0.19, fw, color=GRIS_BORDE)

    # Nota al pie
    texto(ax, cx + cw/2, cy + 0.155, "Sistema de uso exclusivo del personal autorizado",
          size=7.5, color=GRIS_OSC)
    texto(ax, cx + cw/2, cy + 0.12, "© 2026 HotelSys · Hotel Plaza Hostal · Yarumal, Antioquia",
          size=7, color=GRIS_BORDE)

    # Anotaciones de diseño
    texto(ax, 0.05, 0.20, "← Fondo: #F5F5F5", size=7, color=GRIS_OSC, align="left")
    texto(ax, 0.05, 0.16, "← Tarjeta: sombra suave", size=7, color=GRIS_OSC, align="left")
    texto(ax, 0.05, 0.12, "← Botón: #2E7D32", size=7, color=VERDE, align="left")
    texto(ax, 0.77, 0.20, "Error: #F44336 →", size=7, color=ROJO, align="right")

    plt.tight_layout(pad=0.3)
    ruta = os.path.join(CARPETA, "wireframe_login.png")
    plt.savefig(ruta, dpi=DPI, bbox_inches="tight", facecolor=GRIS)
    plt.close()
    print(f"✓  {ruta}")


# ══════════════════════════════════════════════════════════════════════════════
# WIREFRAME 2 — DASHBOARD
# ══════════════════════════════════════════════════════════════════════════════

def wf_dashboard():
    fig, ax = plt.subplots(figsize=(11, 7))
    ax.set_xlim(0, 1)
    ax.set_ylim(0, 1)
    ax.axis("off")
    fig.patch.set_facecolor(GRIS)
    ax.set_facecolor(GRIS)

    texto(ax, 0.5, 0.975, "WIREFRAME — dashboard.php  (solo rol: admin)", size=8, color=GRIS_OSC)
    linea_h(ax, 0.01, 0.960, 0.98)

    # ── Sidebar ──
    sb_items = [
        (">", "Dashboard"),
        (">", "Reservas"),
        (">", "Habitaciones"),
        (">", "Clientes"),
        (">", "Personal"),
        (">", "Inventario"),
        (">", "Facturación"),
        (">", "Reportes"),
        (">", "Usuarios"),
    ]
    sidebar(ax, 0.01, 0.02, 0.17, 0.93, sb_items, activo=0)

    # ── Área principal ──
    mx, my, mw, mh = 0.19, 0.02, 0.80, 0.93
    rect_simple(ax, mx, my, mw, mh, color=BLANCO, borde=GRIS_BORDE)

    # Header principal
    rect_simple(ax, mx, my + mh - 0.08, mw, 0.08, color=VERDE, borde=VERDE)
    texto(ax, mx + 0.01, my + mh - 0.04, "[H]  HotelSys — Dashboard", size=11,
          color=BLANCO, bold=True, align="left")
    texto(ax, mx + mw - 0.01, my + mh - 0.035, "[U] Admin  |  Salir →",
          size=8.5, color=VERDE_CLAR, align="right")

    cy_main = my + mh - 0.09

    # Subtítulo
    texto(ax, mx + 0.01, cy_main - 0.025, "Resumen general — Hotel Plaza Hostal",
          size=9, color=NEGRO, bold=True, align="left")
    linea_h(ax, mx + 0.01, cy_main - 0.038, mw - 0.02, color=VERDE_CLAR)

    # ── KPIs ──
    kpis = [
        ("[Hab]", "Habitaciones\nOcupadas", "18 / 24", VERDE, VERDE_FOND),
        ("[Res]", "Reservas\nHoy", "5", AZUL, AZUL_CLAR),
        ("[$", "Ingresos\ndel Mes", "$850.000", VERDE_MED, VERDE_FOND),
    ]
    kw = (mw - 0.04) / 3
    for i, (ico, label, val, col, bg) in enumerate(kpis):
        kx = mx + 0.01 + i * (kw + 0.01)
        ky = cy_main - 0.175
        rect_simple(ax, kx, ky, kw - 0.005, 0.115, color=bg, borde=col, lw=1.5)
        texto(ax, kx + (kw-0.005)/2, ky + 0.085, ico, size=16, color=col)
        texto(ax, kx + (kw-0.005)/2, ky + 0.055, label, size=8, color=GRIS_OSC)
        texto(ax, kx + (kw-0.005)/2, ky + 0.022, val, size=13, color=col, bold=True)

    # ── Gráfica (placeholder) ──
    gy = cy_main - 0.415
    gw = mw * 0.57
    gh = 0.215
    rect_simple(ax, mx + 0.01, gy, gw, gh, color=VERDE_FOND, borde=VERDE_CLAR, lw=1.0)
    texto(ax, mx + 0.01 + gw/2, gy + gh - 0.022,
          "Ocupación semanal — Gráfica de barras (Chart.js · Mes 4)",
          size=8, color=GRIS_OSC, bold=False)

    # Barras simuladas
    bar_vals = [0.5, 0.75, 0.60, 0.90, 0.70, 0.80, 0.55]
    bar_labels = ["Lun", "Mar", "Mié", "Jue", "Vie", "Sáb", "Dom"]
    bw = gw / 10
    for i, (v, lbl) in enumerate(zip(bar_vals, bar_labels)):
        bx = mx + 0.01 + 0.04 + i * (gw - 0.04) / 7
        bh_real = (gh - 0.06) * v
        rect_simple(ax, bx, gy + 0.03, bw * 0.7, bh_real, color=VERDE_MED, borde=VERDE)
        texto(ax, bx + bw*0.35, gy + 0.018, lbl, size=6.5, color=GRIS_OSC)

    # ── Tabla últimas reservas ──
    tw = mw - gw - 0.035
    tx = mx + 0.01 + gw + 0.015
    th_total = gh
    rect_simple(ax, tx, gy, tw, th_total, color=BLANCO, borde=GRIS_BORDE)
    rect_simple(ax, tx, gy + th_total - 0.038, tw, 0.038, color=VERDE, borde=VERDE)
    texto(ax, tx + tw/2, gy + th_total - 0.019, "Últimas Reservas", size=8,
          color=BLANCO, bold=True)

    cols_r = ["ID", "Cliente", "Hab.", "Estado"]
    col_w  = [tw*0.12, tw*0.38, tw*0.18, tw*0.32]
    cx_r = tx
    ry_header = gy + th_total - 0.068
    rect_simple(ax, tx, ry_header, tw, 0.03, color=VERDE_FOND, borde=GRIS_BORDE)
    for j, (c, cw2) in enumerate(zip(cols_r, col_w)):
        texto(ax, cx_r + cw2/2, ry_header + 0.015, c, size=7.5, color=VERDE, bold=True)
        cx_r += cw2

    reservas = [
        ("1", "Juan P.", "101", "o Activa"),
        ("2", "María G.", "205", "o Pendiente"),
        ("3", "Luis R.", "310", "o Finalizada"),
        ("4", "Ana M.", "102", "o Activa"),
        ("5", "Pedro S.", "401", "o Cancelada"),
    ]
    colores_estado = [VERDE, AMARILLO, GRIS_OSC, VERDE, ROJO]
    for k, (rid, cli, hab, est) in enumerate(reservas):
        ry = ry_header - (k + 1) * 0.028
        bg_r = VERDE_FOND if k % 2 == 0 else BLANCO
        rect_simple(ax, tx, ry, tw, 0.028, color=bg_r, borde=GRIS_BORDE, lw=0.5)
        vals = [rid, cli, hab, est]
        cx_r2 = tx
        for j2, (v2, cw2) in enumerate(zip(vals, col_w)):
            col_txt = colores_estado[k] if j2 == 3 else NEGRO
            texto(ax, cx_r2 + cw2/2, ry + 0.014, v2, size=7, color=col_txt)
            cx_r2 += cw2

    plt.tight_layout(pad=0.2)
    ruta = os.path.join(CARPETA, "wireframe_dashboard.png")
    plt.savefig(ruta, dpi=DPI, bbox_inches="tight", facecolor=GRIS)
    plt.close()
    print(f"✓  {ruta}")


# ══════════════════════════════════════════════════════════════════════════════
# WIREFRAME 3 — RESERVAS
# ══════════════════════════════════════════════════════════════════════════════

def wf_reservas():
    fig, ax = plt.subplots(figsize=(11, 7.5))
    ax.set_xlim(0, 1)
    ax.set_ylim(0, 1)
    ax.axis("off")
    fig.patch.set_facecolor(GRIS)

    texto(ax, 0.5, 0.978, "WIREFRAME — reservas.php  (roles: admin + recepcionista)", size=8, color=GRIS_OSC)
    linea_h(ax, 0.01, 0.963, 0.98)

    sb_items = [
        (">", "Dashboard"),
        (">", "Reservas  "),
        (">", "Habitaciones"),
        (">", "Clientes"),
        (">", "Personal"),
        (">", "Inventario"),
        (">", "Facturación"),
        (">", "Reportes"),
    ]
    sidebar(ax, 0.01, 0.02, 0.17, 0.93, sb_items, activo=1)

    mx, my, mw, mh = 0.19, 0.02, 0.80, 0.93
    rect_simple(ax, mx, my, mw, mh, color=BLANCO, borde=GRIS_BORDE)

    # Header
    rect_simple(ax, mx, my + mh - 0.07, mw, 0.07, color=VERDE, borde=VERDE)
    texto(ax, mx + 0.01, my + mh - 0.038, "[Res]  Reservas", size=11, color=BLANCO, bold=True, align="left")
    boton(ax, mx + mw - 0.175, my + mh - 0.058, 0.16, 0.038, "+ Nueva reserva", bg=VERDE_MED, size=8.5)

    cy = my + mh - 0.085

    # Barra búsqueda + filtro
    campo(ax, mx + 0.01, cy - 0.06, mw * 0.50, 0.042, "  [B]  Buscar por cliente o habitación...", size=8.5)
    campo(ax, mx + 0.01 + mw*0.52, cy - 0.06, mw * 0.26, 0.042, "  Estado: Todas  ▼", size=8.5)

    # ── Tabla reservas ──
    th = cy - 0.075
    cols = ["ID", "Cliente", "Habitación", "F. Entrada", "F. Salida", "Estado", "Acciones"]
    cws  = [0.06, 0.20, 0.13, 0.13, 0.13, 0.15, 0.15]
    cws  = [c * (mw - 0.02) for c in cws]

    # Header tabla
    rect_simple(ax, mx + 0.01, th - 0.032, mw - 0.02, 0.032, color=VERDE, borde=VERDE)
    cx_t = mx + 0.01
    for c, cw2 in zip(cols, cws):
        texto(ax, cx_t + cw2/2, th - 0.016, c, size=7.5, color=BLANCO, bold=True)
        cx_t += cw2

    # Filas
    filas = [
        ("1", "Juan Pérez",    "101 — Simple", "22/06/26", "24/06/26", "Activa",    VERDE,    VERDE_FOND),
        ("2", "María García",  "205 — Doble",  "23/06/26", "25/06/26", "Pendiente", AMARILLO, AMAR_CLAR),
        ("3", "Luis Ramírez",  "310 — Suite",  "20/06/26", "22/06/26", "Finalizada",GRIS_OSC, GRIS),
        ("4", "Ana Martínez",  "102 — Simple", "24/06/26", "26/06/26", "Activa",    VERDE,    VERDE_FOND),
        ("5", "Pedro Soto",    "401 — Doble",  "21/06/26", "23/06/26", "Cancelada", ROJO,     ROJO_CLAR),
        ("6", "Clara Ruiz",    "303 — Simple", "25/06/26", "27/06/26", "Pendiente", AMARILLO, AMAR_CLAR),
    ]
    for k, (fid, cli, hab, fe, fs, est, col_e, bg_e) in enumerate(filas):
        fy = th - 0.032 - (k + 1) * 0.05
        bg_f = VERDE_FOND if k % 2 == 0 else BLANCO
        rect_simple(ax, mx + 0.01, fy, mw - 0.02, 0.05, color=bg_f, borde=GRIS_BORDE, lw=0.5)
        vals = [fid, cli, hab, fe, fs]
        cx_f = mx + 0.01
        for j2, (v2, cw2) in enumerate(zip(vals, cws[:5])):
            texto(ax, cx_f + cw2/2, fy + 0.025, v2, size=7.5, color=NEGRO)
            cx_f += cw2
        # Estado con color
        rect_simple(ax, cx_f + 0.005, fy + 0.010, cws[5] - 0.01, 0.030, color=bg_e, borde=col_e, lw=0.8)
        texto(ax, cx_f + cws[5]/2, fy + 0.025, est, size=7.5, color=col_e, bold=True)
        cx_f += cws[5]
        # Acciones
        boton(ax, cx_f + 0.004, fy + 0.012, 0.045, 0.026, "[Ed] Editar", bg=AZUL, size=6.5)
        boton(ax, cx_f + 0.056, fy + 0.012, 0.045, 0.026, "[Br] Borrar", bg=ROJO, size=6.5)

    # ── Modal Nueva Reserva ──
    modal_x, modal_y = mx + mw * 0.38, my + 0.06
    modal_w, modal_h = 0.38, 0.48
    rect_simple(ax, modal_x, modal_y, modal_w, modal_h, color=BLANCO, borde=GRIS_BORDE, lw=1.5)
    rect_simple(ax, modal_x, modal_y + modal_h - 0.055, modal_w, 0.055, color=VERDE, borde=VERDE)
    texto(ax, modal_x + modal_w/2, modal_y + modal_h - 0.028,
          "Nueva Reserva", size=9, color=BLANCO, bold=True)
    texto(ax, modal_x + modal_w - 0.015, modal_y + modal_h - 0.028,
          "[X]", size=10, color=VERDE_CLAR)

    campos_modal = [
        ("Cliente *",           "  Seleccionar cliente...  ▼"),
        ("Habitación *",        "  Solo disponibles         ▼"),
        ("Fecha de entrada *",  "  dd / mm / aaaa"),
        ("Fecha de salida *",   "  dd / mm / aaaa"),
        ("Observaciones",       "  Notas adicionales..."),
    ]
    for i, (lbl, ph) in enumerate(campos_modal):
        fy2 = modal_y + modal_h - 0.085 - i * 0.075
        texto(ax, modal_x + 0.015, fy2, lbl, size=8, color=NEGRO, bold=True, align="left")
        campo(ax, modal_x + 0.015, fy2 - 0.040, modal_w - 0.03, 0.034, ph, size=8)

    boton(ax, modal_x + 0.015, modal_y + 0.015, modal_w*0.46, 0.038, "[OK] Guardar", bg=VERDE, size=8.5)
    boton(ax, modal_x + modal_w*0.54, modal_y + 0.015, modal_w*0.43, 0.038, "[X] Cancelar", bg=GRIS_OSC, size=8.5)

    plt.tight_layout(pad=0.2)
    ruta = os.path.join(CARPETA, "wireframe_reservas.png")
    plt.savefig(ruta, dpi=DPI, bbox_inches="tight", facecolor=GRIS)
    plt.close()
    print(f"✓  {ruta}")


# ══════════════════════════════════════════════════════════════════════════════
# WIREFRAME 4 — HABITACIONES
# ══════════════════════════════════════════════════════════════════════════════

def wf_habitaciones():
    fig, ax = plt.subplots(figsize=(11, 7.5))
    ax.set_xlim(0, 1)
    ax.set_ylim(0, 1)
    ax.axis("off")
    fig.patch.set_facecolor(GRIS)

    texto(ax, 0.5, 0.978, "WIREFRAME — habitaciones.php  (roles: admin + recepcionista)", size=8, color=GRIS_OSC)
    linea_h(ax, 0.01, 0.963, 0.98)

    sb_items = [
        (">", "Dashboard"),
        (">", "Reservas"),
        (">", "Habitaciones "),
        (">", "Clientes"),
        (">", "Personal"),
        (">", "Inventario"),
        (">", "Facturación"),
        (">", "Reportes"),
    ]
    sidebar(ax, 0.01, 0.02, 0.17, 0.93, sb_items, activo=2)

    mx, my, mw, mh = 0.19, 0.02, 0.80, 0.93
    rect_simple(ax, mx, my, mw, mh, color=BLANCO, borde=GRIS_BORDE)

    # Header
    rect_simple(ax, mx, my + mh - 0.07, mw, 0.07, color=VERDE, borde=VERDE)
    texto(ax, mx + 0.01, my + mh - 0.038, "[Hab]  Habitaciones", size=11, color=BLANCO, bold=True, align="left")
    campo(ax, mx + mw - 0.22, my + mh - 0.058, 0.20, 0.038, "  Filtro: Todas  ▼",
          bg=BLANCO, size=8.5)

    cy = my + mh - 0.085

    # ── Contadores de estado ──
    estados = [
        ("[V]", "Disponibles", "10", VERDE,    VERDE_FOND),
        ("[R]", "Ocupadas",    "12", ROJO,     ROJO_CLAR),
        ("[A]", "Mantenimiento","2", AMARILLO, AMAR_CLAR),
    ]
    ew = (mw - 0.04) / 3
    for i, (ico, label, cnt, col, bg) in enumerate(estados):
        ex = mx + 0.01 + i * (ew + 0.01)
        ey = cy - 0.075
        rect_simple(ax, ex, ey, ew - 0.005, 0.060, color=bg, borde=col, lw=1.2)
        texto(ax, ex + (ew-0.005)/2, ey + 0.040, f"{ico}  {label}", size=8.5, color=col, bold=True)
        texto(ax, ex + (ew-0.005)/2, ey + 0.016, cnt, size=13, color=col, bold=True)

    # ── Grilla de habitaciones ──
    # 24 habitaciones: 6 columnas x 4 filas (pisos)
    pisos  = ["Piso 1", "Piso 2", "Piso 3", "Piso 4"]
    nums   = list(range(1, 7))  # habitaciones por piso
    estados_hab = [
        # Piso 1:  101-106
        [VERDE, ROJO, VERDE, AMARILLO, ROJO, VERDE],
        # Piso 2:  201-206
        [ROJO, VERDE, ROJO, VERDE, ROJO, AMARILLO],
        # Piso 3:  301-306
        [VERDE, VERDE, ROJO, VERDE, ROJO, VERDE],
        # Piso 4:  401-406
        [ROJO, VERDE, VERDE, ROJO, VERDE, VERDE],
    ]
    bg_hab = {VERDE: VERDE_FOND, ROJO: ROJO_CLAR, AMARILLO: AMAR_CLAR}
    icono_hab = {VERDE: "o", ROJO: "o", AMARILLO: "o"}

    grid_y0  = cy - 0.09
    cell_w   = (mw - 0.04) / 6
    cell_h   = 0.095

    for p, (piso, estados_fila) in enumerate(zip(pisos, estados_hab)):
        row_y = grid_y0 - 0.025 - p * (cell_h + 0.015)
        # Etiqueta de piso
        texto(ax, mx + 0.005, row_y + cell_h/2, piso, size=7.5, color=GRIS_OSC,
              align="left", bold=True)
        for n, est in enumerate(estados_fila):
            hnum = (p + 1) * 100 + (n + 1)
            hx = mx + 0.01 + n * (cell_w + 0.005)
            rect_simple(ax, hx, row_y, cell_w - 0.002, cell_h,
                        color=bg_hab[est], borde=est, lw=1.2)
            texto(ax, hx + (cell_w-0.002)/2, row_y + cell_h*0.65,
                  str(hnum), size=9, color=est, bold=True)
            texto(ax, hx + (cell_w-0.002)/2, row_y + cell_h*0.28,
                  icono_hab[est], size=11, color=est)

    # ── Leyenda ──
    ley_y = grid_y0 - 0.025 - 4 * (cell_h + 0.015) - 0.01
    leyenda = [("o Disponible", VERDE, VERDE_FOND), ("o Ocupada", ROJO, ROJO_CLAR),
               ("o Mantenimiento", AMARILLO, AMAR_CLAR)]
    lx = mx + 0.01
    for etq, col, bg in leyenda:
        rect_simple(ax, lx, ley_y, 0.155, 0.030, color=bg, borde=col, lw=0.8)
        texto(ax, lx + 0.0775, ley_y + 0.015, etq, size=8, color=col, bold=True)
        lx += 0.165

    # ── Panel de detalle ──
    px, py, pw, ph = mx + mw - 0.285, my + 0.04, 0.27, 0.36
    rect_simple(ax, px, py, pw, ph, color=BLANCO, borde=AZUL, lw=1.5)
    rect_simple(ax, px, py + ph - 0.052, pw, 0.052, color=AZUL, borde=AZUL)
    texto(ax, px + pw/2, py + ph - 0.026, "Detalle — Habitación 102",
          size=8.5, color=BLANCO, bold=True)

    detalles = [
        ("Número:",     "102"),
        ("Tipo:",       "Doble"),
        ("Piso:",       "1"),
        ("Precio/noche:","$80.000 COP"),
        ("Estado:",     "o Ocupada"),
        ("Huésped:",    "María García"),
        ("Check-in:",   "23/06/2026"),
        ("Check-out:",  "25/06/2026"),
    ]
    for i, (k, v) in enumerate(detalles):
        dy = py + ph - 0.075 - i * 0.033
        texto(ax, px + 0.012, dy, k, size=7.5, color=GRIS_OSC, align="left", bold=True)
        col_v = ROJO if v.startswith("o") else NEGRO
        texto(ax, px + pw - 0.012, dy, v, size=7.5, color=col_v, align="right")
        linea_h(ax, px + 0.01, dy - 0.010, pw - 0.02, color=GRIS_BORDE, lw=0.4)

    boton(ax, px + 0.015, py + 0.010, pw - 0.03, 0.035,
          "Cambiar estado  ▼", bg=VERDE, size=8)

    plt.tight_layout(pad=0.2)
    ruta = os.path.join(CARPETA, "wireframe_habitaciones.png")
    plt.savefig(ruta, dpi=DPI, bbox_inches="tight", facecolor=GRIS)
    plt.close()
    print(f"✓  {ruta}")


# ══════════════════════════════════════════════════════════════════════════════
# MAIN
# ══════════════════════════════════════════════════════════════════════════════

if __name__ == "__main__":
    print("\n🎨  Generando wireframes HotelSys...\n")
    wf_login()
    wf_dashboard()
    wf_reservas()
    wf_habitaciones()
    print(f"\n✅  4 wireframes generados en la carpeta '{CARPETA}/'")
    print("    Puedes abrirlos con cualquier visor de imágenes.")
    print("    Súbelos a GitHub: git add wireframes/ && git commit -m 'docs: add UI wireframes for week 5'\n")
