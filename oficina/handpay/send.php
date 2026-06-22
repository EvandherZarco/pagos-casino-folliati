<?php
include("../conexion.php");

if (isset($_POST['send'])) {

    $campos = ['fecha','hora','cantidad','blue_point','cliente','tecnico','gerente'];
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
        $fecha      = trim($_POST['fecha']);
        $hora       = trim($_POST['hora']);
        $cantidad   = trim($_POST['cantidad']);
        $blue_point = trim($_POST['blue_point']);
        $cliente    = trim($_POST['cliente']);
        $tecnico    = trim($_POST['tecnico']);
        $gerente    = trim($_POST['gerente']);

        $stmt = mysqli_prepare($conex,
            "INSERT INTO handpay (fecha, hora, cantidad, blue_point, cliente, tecnico, gerente)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "sssssss",
            $fecha, $hora, $cantidad, $blue_point, $cliente, $tecnico, $gerente
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
