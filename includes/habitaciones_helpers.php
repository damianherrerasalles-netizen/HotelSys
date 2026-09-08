<?php
// includes/habitaciones_helpers.php
// Semana 13 Día 2 — Dashboard ejecutivo (Actividad IX)
//
// Función compartida para asignar un color según el estado de una
// habitación. Antes vivía únicamente dentro de views/habitaciones.php;
// se extrae aquí para poder reutilizarla también en el mapa de
// habitaciones del Dashboard, sin duplicar la lógica de colores.

function colorEstadoHabitacion(string $estado): string {
    switch ($estado) {
        case 'Disponible':    return '#2E7D32'; // verde HotelSys
        case 'Ocupada':       return '#C62828'; // rojo
        case 'Mantenimiento': return '#F9A825'; // amarillo
        case 'Reservada':     return '#1565C0'; // azul
        default:              return '#757575'; // gris
    }
}
