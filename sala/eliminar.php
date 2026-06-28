<?php
require 'db.php';
require 'renumerar.php';
require_once 'push_helpers.php';

// Solo accesible desde el panel — verificar sesión activa
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['panel_usuario'])) {
    header('Location: panel/login.php');
    exit;
}

$id     = (int)($_GET['id'] ?? 0);
$origen = $_GET['origen'] ?? '';

if ($id) {
    $r = mysqli_fetch_assoc(mysqli_query($conex, "SELECT fecha FROM pagos WHERE id=$id"));
    mysqli_query($conex, "DELETE FROM pagos WHERE id=$id");

    if ($r) {
        // Siempre avisar a oficina — si el pago nunca viajó, oficina
        // no encontrará el id_sala y lo ignorará sin explotar.
        $payloadDelete = ['id_sala' => $id, 'motivo' => 'Eliminado desde sala'];
        $resultado = enviarPushOficina($payloadDelete, 'eliminar_pago.php');
        if (!$resultado['ok']) {
            guardarPushEvento($conex, $id, 'delete', $payloadDelete, $resultado['error']);
        }

        // Renumera el resto del día Y resincroniza los pagos cerrados
        // restantes cuyo folio pudo haber cambiado por el borrado.
        renumerar_dia($conex, $r['fecha']);
    }
}

header('Location: ' . ($origen === 'panel' ? 'panel/index.php' : 'lista.php'));
exit;
