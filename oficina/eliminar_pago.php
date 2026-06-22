<?php
// eliminar_pago.php — Endpoint receptor de eliminaciones desde sala
// Lado: OFICINA
// NO borra físicamente la fila — la marca como ANULADA, conservando
// rastro para auditoría (decisión explícita: más valor para el reporte
// formal que un borrado limpio sin huella). Identificado por id_sala.
//
// <!-- Desarrollado por Evandher Zarco (ZarcoMecatronics) - Junio 2026 -->

header('Content-Type: application/json');
require_once 'conexion.php'; // conexión mysqli a la BD "maquinas" -> $conex

$raw = file_get_contents('php://input');
$datos = json_decode($raw, true);

if (!$datos || !isset($datos['id_sala'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Payload inválido: falta id_sala']);
    exit;
}

$idSala = (int)$datos['id_sala'];
$motivo = $datos['motivo'] ?? 'Eliminado desde sala';

$stmtCheck = mysqli_prepare($conex, "SELECT id, anulado FROM pagos WHERE id_sala = ?");
mysqli_stmt_bind_param($stmtCheck, 'i', $idSala);
mysqli_stmt_execute($stmtCheck);
mysqli_stmt_bind_result($stmtCheck, $idFila, $yaAnulado);
$encontrado = mysqli_stmt_fetch($stmtCheck);
mysqli_stmt_close($stmtCheck);

if (!$encontrado) {
    http_response_code(404);
    echo json_encode([
        'ok' => false,
        'error' => "No existe ningún pago con id_sala=$idSala en oficina. No se puede anular algo que nunca se sincronizó."
    ]);
    exit;
}

if ($yaAnulado) {
    // Ya estaba anulado — no es error. Evita doble trabajo si reintentar_push.php
    // reintenta un evento de borrado que en realidad ya se había aplicado antes.
    echo json_encode(['ok' => true, 'id_sala' => $idSala, 'mensaje' => 'El pago ya estaba anulado, no se hizo nada nuevo.']);
    exit;
}

$stmt = mysqli_prepare($conex, "UPDATE pagos SET anulado=1, fecha_anulado=NOW(), motivo_anulado=? WHERE id_sala=?");
mysqli_stmt_bind_param($stmt, 'si', $motivo, $idSala);

if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => mysqli_stmt_error($stmt)]);
    mysqli_stmt_close($stmt);
    exit;
}
mysqli_stmt_close($stmt);

echo json_encode(['ok' => true, 'id_sala' => $idSala, 'mensaje' => 'Pago marcado como anulado correctamente']);
