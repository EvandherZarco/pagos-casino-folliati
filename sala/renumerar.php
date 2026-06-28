<?php
// renumerar.php — Renumera folios de un día y resincroniza con oficina
// todos los pagos de ese día (ya no hay distinción cerrado/abierto).
//
// Se llama desde editar.php y eliminar.php cuando cambia la hora
// o se borra un pago, porque renumerar_dia() afecta TODO el día.
//
// <!-- Desarrollado por Evandher Zarco (ZarcoMecatronics) - Junio 2026 -->

require_once __DIR__ . '/push_helpers.php';

function renumerar_dia($conex, $fecha) {
    $fecha = mysqli_real_escape_string($conex, $fecha);

    // 1. Renumerar por hora (y por id como desempate)
    $res = mysqli_query($conex,
        "SELECT id FROM pagos WHERE fecha='$fecha' ORDER BY hora, id"
    );
    $num = 1;
    while ($r = mysqli_fetch_assoc($res)) {
        mysqli_query($conex, "UPDATE pagos SET pago_num=$num WHERE id={$r['id']}");
        $num++;
    }

    // 2. Resincronizar TODOS los pagos del día con oficina
    $resTodos = mysqli_query($conex,
        "SELECT * FROM pagos WHERE fecha='$fecha'"
    );
    while ($p = mysqli_fetch_assoc($resTodos)) {
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
            'tarjeta'      => $p['id_cliente'],
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
