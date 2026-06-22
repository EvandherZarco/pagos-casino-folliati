<?php
// reintentar_push.php — Reintento periódico de envíos pendientes a oficina
// Lado: SALA
// Pensado para correr vía Windows Task Scheduler cada 5-10 minutos.
// No tiene salida visual — es un proceso de fondo. Los resultados se
// reflejan en push_pendiente (cierres de día) y push_evento
// (ediciones/eliminaciones de pagos ya cerrados) para que cualquier
// pantalla de sala pueda mostrar un aviso si algo necesita atención humana.
//
// <!-- Desarrollado por Evandher Zarco (ZarcoMecatronics) - Junio 2026 -->

require_once 'db.php';           // conexión mysqli de sala -> $conex
require_once 'push_helpers.php'; // enviarPushOficina()

// ============================================================
// PARTE 1: push_pendiente — cierres de día fallidos (sin cambios)
// ============================================================

$pendientes = mysqli_query($conex, "SELECT * FROM push_pendiente WHERE estado = 'pendiente'");

$procesados = 0;
$exitosos = 0;
$fallidos = 0;

while ($fila = mysqli_fetch_assoc($pendientes)) {
    $procesados++;
    $payload = json_decode($fila['datos_json'], true);

    if (!$payload) {
        // JSON corrupto/ilegible — esto no se resuelve reintentando, pasa directo a error.
        $stmt = mysqli_prepare($conex,
            "UPDATE push_pendiente SET estado='error', ultimo_error=?, ultimo_intento=NOW() WHERE id=?");
        $msg = "JSON inválido en datos_json, no se puede reintentar.";
        mysqli_stmt_bind_param($stmt, 'si', $msg, $fila['id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $fallidos++;
        continue;
    }

    $resultado = enviarPushOficina($payload);
    $nuevosIntentos = (int)$fila['intentos'] + 1;

    if ($resultado['ok']) {
        $stmt = mysqli_prepare($conex,
            "UPDATE push_pendiente SET estado='enviado', intentos=?, ultimo_intento=NOW(), ultimo_error=NULL WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'ii', $nuevosIntentos, $fila['id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $exitosos++;

    } elseif ($nuevosIntentos >= PUSH_MAX_INTENTOS) {
        // Se agotaron los reintentos — pasa a 'error' para que deje de insistir solo
        // y un humano lo revise. El mensaje queda listo para mostrarse en la GUI.
        $mensajeFinal = "Favor de llamar al técnico debido a: fallo persistente al "
            . "sincronizar el cierre del día {$fila['fecha_operativa']} con oficina "
            . "después de {$nuevosIntentos} intentos. Último error: {$resultado['error']}";
        $stmt = mysqli_prepare($conex,
            "UPDATE push_pendiente SET estado='error', intentos=?, ultimo_intento=NOW(), ultimo_error=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'isi', $nuevosIntentos, $mensajeFinal, $fila['id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $fallidos++;

    } else {
        // Sigue pendiente, todavía dentro del margen de reintentos.
        $stmt = mysqli_prepare($conex,
            "UPDATE push_pendiente SET intentos=?, ultimo_intento=NOW(), ultimo_error=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'isi', $nuevosIntentos, $resultado['error'], $fila['id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $fallidos++;
    }
}

// ============================================================
// PARTE 2: push_evento — ediciones/eliminaciones fallidas (NUEVO)
// Mismo patrón exacto que la Parte 1, pero el endpoint depende del
// tipo de evento, y el mensaje final menciona id_sala en vez de
// fecha_operativa (un evento es de UN pago, no de un día completo).
// ============================================================

$eventos = mysqli_query($conex, "SELECT * FROM push_evento WHERE estado = 'pendiente'");

$procesados_evento = 0;
$exitosos_evento = 0;
$fallidos_evento = 0;

while ($fila = mysqli_fetch_assoc($eventos)) {
    $procesados_evento++;
    $payload = json_decode($fila['datos_json'], true);

    if (!$payload) {
        $stmt = mysqli_prepare($conex,
            "UPDATE push_evento SET estado='error', ultimo_error=?, ultimo_intento=NOW() WHERE id=?");
        $msg = "JSON inválido en datos_json, no se puede reintentar.";
        mysqli_stmt_bind_param($stmt, 'si', $msg, $fila['id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $fallidos_evento++;
        continue;
    }

    $endpoint = $fila['tipo'] === 'delete' ? 'eliminar_pago.php' : 'actualizar_pago.php';
    $resultado = enviarPushOficina($payload, $endpoint);
    $nuevosIntentos = (int)$fila['intentos'] + 1;

    if ($resultado['ok']) {
        $stmt = mysqli_prepare($conex,
            "UPDATE push_evento SET estado='enviado', intentos=?, ultimo_intento=NOW(), ultimo_error=NULL WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'ii', $nuevosIntentos, $fila['id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $exitosos_evento++;

    } elseif ($nuevosIntentos >= PUSH_MAX_INTENTOS) {
        $accion = $fila['tipo'] === 'delete' ? 'eliminación' : 'edición';
        $mensajeFinal = "Favor de llamar al técnico debido a: fallo persistente al "
            . "sincronizar la {$accion} del pago id_sala={$fila['id_sala']} con oficina "
            . "después de {$nuevosIntentos} intentos. Último error: {$resultado['error']}";
        $stmt = mysqli_prepare($conex,
            "UPDATE push_evento SET estado='error', intentos=?, ultimo_intento=NOW(), ultimo_error=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'isi', $nuevosIntentos, $mensajeFinal, $fila['id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $fallidos_evento++;

    } else {
        $stmt = mysqli_prepare($conex,
            "UPDATE push_evento SET intentos=?, ultimo_intento=NOW(), ultimo_error=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'isi', $nuevosIntentos, $resultado['error'], $fila['id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $fallidos_evento++;
    }
}

// Log simple para revisar manualmente si algo se ve raro (opcional, se puede quitar).
$logLine = date('Y-m-d H:i:s')
    . " — cierres: procesados $procesados, exitosos $exitosos, fallidos $fallidos"
    . " | eventos: procesados $procesados_evento, exitosos $exitosos_evento, fallidos $fallidos_evento"
    . PHP_EOL;
file_put_contents(__DIR__ . '/reintentar_push.log', $logLine, FILE_APPEND);
