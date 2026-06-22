<?php
// recibir_pagos.php — Endpoint receptor de cierres de día desde sala
// Lado: OFICINA
// Recibe JSON con las filas del día operativo cerrado.
// Dedup por id_sala (id real de la fila en sala, nunca se reutiliza) —
// NO por el folio "pago", que sí se puede reciclar si se borra un pago en sala.
//
// <!-- Desarrollado por Evandher Zarco (ZarcoMecatronics) - Junio 2026 -->

header('Content-Type: application/json');
require_once 'conexion.php'; // conexión mysqli a la BD "maquinas" -> $conex

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!$payload || !isset($payload['archivo']) || !isset($payload['dia']) || !isset($payload['filas'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Payload inválido: faltan archivo, dia o filas']);
    exit;
}

$archivo = $payload['archivo']; // ej. "PAGOS-JUNIO-2026"
$dia = $payload['dia'];         // ej. "20" (día del mes)
$filas = $payload['filas'];     // arreglo de pagos individuales

$insertados = 0;
$omitidos = 0;
$errores = [];

foreach ($filas as $fila) {
    if (!isset($fila['id_sala'])) {
        $errores[] = ['pago' => $fila['pago'] ?? '?', 'error' => 'Falta id_sala, no se puede deduplicar de forma segura. Fila omitida.'];
        continue;
    }
    $idSala = (int)$fila['id_sala'];
    $pagoNum = (string)$fila['pago'];

    // ¿Ya se recibió esta fila específica? (dedup por id_sala, NO por folio)
    $stmtCheck = mysqli_prepare($conex, "SELECT COUNT(*) FROM pagos_sala_log WHERE id_sala = ?");
    mysqli_stmt_bind_param($stmtCheck, 'i', $idSala);
    mysqli_stmt_execute($stmtCheck);
    mysqli_stmt_bind_result($stmtCheck, $count);
    mysqli_stmt_fetch($stmtCheck);
    mysqli_stmt_close($stmtCheck);

    if ($count > 0) {
        $omitidos++;
        continue; // ya existe, no se duplica
    }

    mysqli_begin_transaction($conex);

    try {
        // NOTA (21-jun-2026): se agregó id_sala a la columna directa de "pagos"
        // (antes solo vivía en pagos_sala_log). Esto es lo que permite que
        // actualizar_pago.php / eliminar_pago.php encuentren la fila correcta
        // más adelante, sin depender de un JOIN contra el log ni del folio
        // (que se puede reciclar).
        $stmt = mysqli_prepare($conex, "INSERT INTO pagos
            (fecha, hora, asset, unidad, proveedor, modelo, numero_serie, cliente, tarjeta, nivel, cantidad, apuesta, gerente, tecnico, pago, turno, id_sala)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param(
            $stmt, 'ssssssssssddssssi',
            $fila['fecha'], $fila['hora'], $fila['asset'], $fila['unidad'],
            $fila['proveedor'], $fila['modelo'], $fila['numero_serie'], $fila['cliente'],
            $fila['tarjeta'], $fila['nivel'], $fila['cantidad'], $fila['apuesta'],
            $fila['gerente'], $fila['tecnico'], $pagoNum, $fila['turno'], $idSala
        );
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);

        $stmtLog = mysqli_prepare($conex, "INSERT INTO pagos_sala_log (id_sala, archivo, dia, pago_num, importado_en) VALUES (?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmtLog, 'isss', $idSala, $archivo, $dia, $pagoNum);
        if (!mysqli_stmt_execute($stmtLog)) {
            throw new Exception(mysqli_stmt_error($stmtLog));
        }
        mysqli_stmt_close($stmtLog);

        mysqli_commit($conex);
        $insertados++;

    } catch (Exception $e) {
        mysqli_rollback($conex);
        $errores[] = ['pago' => $pagoNum, 'id_sala' => $idSala, 'error' => $e->getMessage()];
    }
}

echo json_encode([
    'ok' => empty($errores),
    'insertados' => $insertados,
    'omitidos_duplicados' => $omitidos,
    'errores' => $errores
]);
