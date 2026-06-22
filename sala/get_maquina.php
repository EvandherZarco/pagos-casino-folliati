<?php
require 'db.php';
header('Content-Type: application/json');
$asset = mysqli_real_escape_string($conex, $_GET['asset'] ?? '');
$r = mysqli_fetch_assoc(mysqli_query($conex,
    "SELECT modelo, serie AS numero_serie, proveedor FROM catalogos_maquinas.catalogo_maquinas WHERE asset='$asset'"
));
echo json_encode($r ?: ['modelo' => '', 'numero_serie' => '', 'proveedor' => '']);
