<?php
header('Content-Type: application/json');
include("conexion.php");

$asset = isset($_GET['asset']) ? (int) $_GET['asset'] : 0;

if ($asset <= 0) {
    echo json_encode(['found' => false]);
    exit;
}

$stmt = mysqli_prepare($conex, "SELECT proveedor, modelo, serie FROM catalogo_maquinas WHERE asset = ?");
mysqli_stmt_bind_param($stmt, "i", $asset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($row) {
    echo json_encode(['found' => true, 'proveedor' => $row['proveedor'], 'modelo' => $row['modelo'], 'serie' => $row['serie']]);
} else {
    echo json_encode(['found' => false]);
}
?>
