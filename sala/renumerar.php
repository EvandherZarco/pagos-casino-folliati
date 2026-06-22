<?php
// renumerar.php — Renumera folios de un día y resincroniza con oficina
// los pagos ya cerrados de ese mismo día (porque renumerar_dia() puede
// cambiar el pago_num de filas que NO se tocaron directamente, si vienen
// después en orden de hora — confirmado: renumera TODO el día, no solo
// la fila editada/borrada).
//
// Requiere push_helpers.php (enviarPushOficina, guardarPushEvento).
//
// <!-- Desarrollado por Evandher Zarco (ZarcoMecatronics) - Junio 2026 -->

require_once __DIR__ . '/push_helpers.php';

function renumerar_dia($conex, $fecha) {
    $fecha = mysqli_real_escape_string($conex, $fecha);

    // 1. Renumerar (igual que antes, sin cambios en esta parte)
    $res = mysqli_query($conex,
        "SELECT id FROM pagos WHERE fecha='$fecha' ORDER BY hora, id"
    );
    $num = 1;
    while ($r = mysqli_fetch_assoc($res)) {
        mysqli_query($conex, "UPDATE pagos SET pago_num=$num WHERE id={$r['id']}");
        $num++;
    }

    // 2. Resincronizar con oficina TODOS los pagos de este día que ya
    //    estaban cerrados (cerrado=1) — para tu volumen (10 o menos por
    //    día) es más barato y más seguro que calcular cuáles folios
    //    realmente cambiaron.
    $resCerrados = mysqli_query($conex,
        "SELECT * FROM pagos WHERE fecha='$fecha' AND cerrado=1"
    );
    while ($p = mysqli_fetch_assoc($resCerrados)) {
        $payload = [
            'id_sala'      => (int) $p['id'],
            'pago'         => $p['pago_num'],
            'turno'        => $p['turno'],
            'asset'        => $p['asset'],
            'unidad'       => $p['unidad'],
            'proveedor'    => $p['proveedor'],
            'modelo'       => $p['modelo'],
            'numero_serie' => $p['numero_serie'],
            'cantidad'     => $p['cantidad'],
            'apuesta'      => $p['apuesta'],
            'nivel'        => $p['nivel'],
            'cliente'      => $p['cliente'],
            'tarjeta'      => $p['id_cliente'], // mismo mapeo que cerrar_dia.php
            'fecha'        => $p['fecha'],
            'hora'         => $p['hora'],
            'tecnico'      => $p['tecnico'],
            'gerente'      => $p['gerente'],
        ];

        $resultado = enviarPushOficina($payload, 'actualizar_pago.php');

        if (!$resultado['ok']) {
            guardarPushEvento($conex, (int) $p['id'], 'update', $payload, $resultado['error']);
        }
    }
}
?>
