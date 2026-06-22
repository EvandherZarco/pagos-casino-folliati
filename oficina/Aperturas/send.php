<?php
include("../conexion.php");

if (isset($_POST['send'])) {

    $campos = ['asset','proveedor','modelo','serie','tecnico','tipo_de_falla','falla','solucion','fecha','turno'];
    $vacio = false;

    foreach ($campos as $campo) {
        if (!isset($_POST[$campo]) || strlen(trim($_POST[$campo])) < 1) {
            $vacio = true;
            break;
        }
    }

    if ($vacio) {
        echo "<h3 class='error'>Llena todos los campos</h3>";
    } else {
        $asset        = trim($_POST['asset']);
        $proveedor    = trim($_POST['proveedor']);
        $modelo       = trim($_POST['modelo']);
        $serie        = trim($_POST['serie']);
        $tecnico      = trim($_POST['tecnico']);
        $tipo_de_falla = trim($_POST['tipo_de_falla']);
        $falla        = trim($_POST['falla']);
        $solucion     = trim($_POST['solucion']);
        $fecha        = trim($_POST['fecha']);
        $turno        = trim($_POST['turno']);

        $stmt = mysqli_prepare($conex,
            "INSERT INTO datos (asset, proveedor, modelo, serie, tecnico, tipo_de_falla, falla, solucion, fecha, turno)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "ssssssssss",
            $asset, $proveedor, $modelo, $serie, $tecnico,
            $tipo_de_falla, $falla, $solucion, $fecha, $turno
        );

        if (mysqli_stmt_execute($stmt)) {
            echo "<h3 id='mensaje' class='success'>✔ Registro guardado</h3>";
        } else {
            echo "<h3 id='mensaje' class='error'>Error al guardar</h3>";
        }
        mysqli_stmt_close($stmt);
    }
}
?>
