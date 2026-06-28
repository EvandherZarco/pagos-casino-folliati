<?php
include("../conexion.php");

if (isset($_POST['send'])) {

    $campos = ['proveedor','tecnico','hora_inicial','hora_final','ingreso_al_site','actividades','fecha','turno'];
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
        $proveedor       = trim($_POST['proveedor']);
        $tecnico         = trim($_POST['tecnico']);
        $hora_inicial    = trim($_POST['hora_inicial']);
        $hora_final      = trim($_POST['hora_final']);
        $ingreso_al_site = mb_strtoupper(trim($_POST['ingreso_al_site']), 'UTF-8');
        $actividades     = mb_strtoupper(trim($_POST['actividades']), 'UTF-8');
        $fecha           = trim($_POST['fecha']);
        $turno           = trim($_POST['turno']);

        $stmt = mysqli_prepare($conex,
            "INSERT INTO proveedores (proveedor, tecnico, hora_inicial, hora_final, ingreso_al_site, actividades, fecha, turno)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "ssssssss",
            $proveedor, $tecnico, $hora_inicial, $hora_final,
            $ingreso_al_site, $actividades, $fecha, $turno
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
