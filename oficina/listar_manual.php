<?php
// listar_manual.php — Devuelve los pagos capturados manualmente en oficina
// (id_sala IS NULL, es decir, que nunca pasaron por el pipeline de sala)
// para una fecha dada.
//
// Lado: OFICINA — Solo lectura. Sala lo usa para MOSTRAR (no editar) estos
// pagos en su propia lista, mientras dure la transición de captura manual
// en oficina (temporal, hasta que se adopte oficialmente la app de sala).
//
// <!-- Desarrollado por Evandher Zarco (ZarcoMecatronics) - Junio 2026 -->

header('Content-Type: application/json');
require_once 'conexion.php'; // conexión mysqli a la BD "maquinas" -> $conex

$fecha = $_GET['fecha'] ?? null;
if (!$fecha) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Falta el parámetro fecha']);
    exit;
}

$stmt = mysqli_prepare($conex, "SELECT id, fecha, hora, asset, unidad, proveedor, modelo, numero_serie,
    cliente, tarjeta, nivel, cantidad, apuesta, gerente, tecnico, pago, turno
    FROM pagos
    WHERE fecha = ? AND id_sala IS NULL AND anulado = 0
    ORDER BY hora");
mysqli_stmt_bind_param($stmt, 's', $fecha);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$filas = [];
while ($r = mysqli_fetch_assoc($result)) {
    $filas[] = $r;
}
mysqli_stmt_close($stmt);

echo json_encode(['ok' => true, 'filas' => $filas]);
