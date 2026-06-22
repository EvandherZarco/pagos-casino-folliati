<?php
include("../conexion.php");

if (isset($_POST['send'])) {

    $campos = ['fecha','nombre','creditos','cnr','comentario','tipo','tecnico','gerente'];
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
        $nombre     = trim($_POST['nombre']);
        $creditos   = trim($_POST['creditos']);
        $cnr        = trim($_POST['cnr']);
        $comentario = trim($_POST['comentario']);
        $tipo       = trim($_POST['tipo']);
        $tecnico    = trim($_POST['tecnico']);
        $gerente    = trim($_POST['gerente']);

        $stmt = mysqli_prepare($conex,
            "INSERT INTO slips (fecha, nombre, creditos, cnr, comentario, tipo, tecnico, gerente)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "ssssssss",
            $fecha, $nombre, $creditos, $cnr, $comentario, $tipo, $tecnico, $gerente
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
