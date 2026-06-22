<?php
/*
 * fecha_helpers.php — Regla de día operativo (compartida)
 *
 * ⚠️ CRÍTICO: este archivo debe incluirse en CUALQUIER lugar donde se
 * registre, cierre, o consulte un pago por fecha — index.php, lista.php,
 * historico.php, cerrar_dia.php. Si algún archivo calcula la fecha de
 * forma distinta (ej. date('Y-m-d') crudo), vuelve a aparecer el bug
 * de pagos "huérfanos" después de medianoche.
 *
 * Viernes y sábado: el casino cierra a las 4am.
 * Resto de los días (domingo a jueves): cierra a las 2am.
 * Las horas antes del corte cuentan como parte del día anterior.
 *
 * Desarrollado por Evandher Zarco (ZarcoMecatronics) - Junio 2026
 */

function fechaOperativa($timestamp = null) {
    $timestamp = $timestamp ?? time();
    $hora = (int) date('G', $timestamp);
    $diaSemanaAyer = (int) date('N', strtotime('-1 day', $timestamp)); // 1=Lun...5=Vie,6=Sáb,7=Dom
    $cutoff = in_array($diaSemanaAyer, [5, 6]) ? 4 : 2;
    if ($hora < $cutoff) {
        return date('Y-m-d', strtotime('-1 day', $timestamp));
    }
    return date('Y-m-d', $timestamp);
}
