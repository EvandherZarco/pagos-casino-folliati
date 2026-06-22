<?php
include("../conexion.php");

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    $stmt = mysqli_prepare($conex, "DELETE FROM pagos WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: lista.php");
        exit;
    } else {
        echo "Error al eliminar";
    }
    mysqli_stmt_close($stmt);
}
?>
