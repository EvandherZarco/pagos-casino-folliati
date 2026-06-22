<?php
// actualizar_pago.php — Endpoint receptor de ediciones desde sala
// Lado: OFICINA
// Recibe JSON con los datos actualizados de un pago YA sincronizado.
// Matchea por id_sala — NUNCA por folio (pago), que se puede reciclar
// si se borra un pago en sala.
//
// <!-- Desarrollado por Evandher Zarco (ZarcoMecatronics) - Junio 2026 -->

header('Content-Type: application/json');
require_once 'conexion.php'; // conexión mysqli a la BD "maquinas" -> $conex

$raw = file_get_contents('php://input');
$fila = json_decode($raw, true);

if (!$fila || !isset($fila['id_sala'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Payload inválido: falta id_sala']);
    exit;
}

$idSala = (int)$fila['id_sala'];

// Confirmar que la fila existe ANTES de actualizar. No basta con revisar
// "filas afectadas" después del UPDATE: si los valores nuevos son iguales
// a los que ya tenía, MySQL reporta 0 filas afectadas aunque la fila sí
// se haya encontrado y esté correcta — eso ya nos confundió una vez.
$stmtCheck = mysqli_prepare($conex, "SELECT id FROM pagos WHERE id_sala = ?");
mysqli_stmt_bind_param($stmtCheck, 'i', $idSala);
mysqli_stmt_execute($stmtCheck);
mysqli_stmt_store_result($stmtCheck);
$existe = mysqli_stmt_num_rows($stmtCheck) > 0;
mysqli_stmt_close($stmtCheck);

if (!$existe) {
    http_response_code(404);
    echo json_encode([
        'ok' => false,
        'error' => "No existe ningún pago con id_sala=$idSala en oficina. No se puede editar algo que nunca se sincronizó."
    ]);
    exit;
}

$stmt = mysqli_prepare($conex, "UPDATE pagos SET
    fecha=?, hora=?, asset=?, unidad=?, proveedor=?, modelo=?, numero_serie=?,
    cliente=?, tarjeta=?, nivel=?, cantidad=?, apuesta=?, gerente=?, tecnico=?,
    pago=?, turno=?
    WHERE id_sala=?");
mysqli_stmt_bind_param(
    $stmt, 'ssssssssssddssssi',
    $fila['fecha'], $fila['hora'], $fila['asset'], $fila['unidad'],
    $fila['proveedor'], $fila['modelo'], $fila['numero_serie'], $fila['cliente'],
    $fila['tarjeta'], $fila['nivel'], $fila['cantidad'], $fila['apuesta'],
    $fila['gerente'], $fila['tecnico'], $fila['pago'], $fila['turno'], $idSala
);

if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => mysqli_stmt_error($stmt)]);
    mysqli_stmt_close($stmt);
    exit;
}
mysqli_stmt_close($stmt);

echo json_encode(['ok' => true, 'id_sala' => $idSala, 'mensaje' => 'Pago actualizado correctamente']);
