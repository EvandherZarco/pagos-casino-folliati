<?php
include("../conexion.php");

if (isset($_POST['send'])) {

    $campos = ['fecha','asset','proveedor','cliente','tarjeta','nivel','cantidad','apuesta','gerente','tecnico'];
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
        $fecha     = trim($_POST['fecha']);
        $asset     = trim($_POST['asset']);
        $proveedor = trim($_POST['proveedor']);
        $cliente   = trim($_POST['cliente']);
        $tarjeta   = trim($_POST['tarjeta']);
        $nivel     = trim($_POST['nivel']);
        $cantidad  = trim($_POST['cantidad']);
        $apuesta   = trim($_POST['apuesta']);
        $gerente   = trim($_POST['gerente']);
        $tecnico   = trim($_POST['tecnico']);

        $stmt = mysqli_prepare($conex,
            "INSERT INTO pagos (fecha, asset, proveedor, cliente, tarjeta, nivel, cantidad, apuesta, gerente, tecnico)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "ssssssssss",
            $fecha, $asset, $proveedor, $cliente, $tarjeta,
            $nivel, $cantidad, $apuesta, $gerente, $tecnico
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
