<?php
include("../conexion.php");

if (isset($_GET['id'])) {
    $id   = (int) $_GET['id'];
    $mes  = $_GET['mes']  ?? date('m');
    $anio = $_GET['anio'] ?? date('Y');

    $stmt = mysqli_prepare($conex, "DELETE FROM reporte_diario WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: lista.php?mes=$mes&anio=$anio");
    exit;
}
