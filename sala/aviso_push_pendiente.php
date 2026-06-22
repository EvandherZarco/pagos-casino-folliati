<?php
/*
 * aviso_push_pendiente.php — Banner de alerta para mostrar en pantallas de sala
 * cuando algún envío a oficina agotó los reintentos — ya sea un cierre de día
 * (estado='error' en push_pendiente) o una edición/eliminación de un pago
 * (estado='error' en push_evento).
 *
 * Uso: agregar esta línea en cualquier página de sala, dentro del <div class="container">:
 *
 *     include 'aviso_push_pendiente.php';
 *
 * Requiere que la variable $conex (conexión mysqli) ya exista en el archivo que lo incluye.
 *
 * Desarrollado por Evandher Zarco (ZarcoMecatronics) - Junio 2026
 */

$avisos_push   = mysqli_query($conex, "SELECT fecha_operativa, ultimo_error FROM push_pendiente WHERE estado = 'error'");
$avisos_evento = mysqli_query($conex, "SELECT id_sala, tipo, ultimo_error FROM push_evento WHERE estado = 'error'");

$hay_avisos_push   = $avisos_push && mysqli_num_rows($avisos_push) > 0;
$hay_avisos_evento = $avisos_evento && mysqli_num_rows($avisos_evento) > 0;

if ($hay_avisos_push || $hay_avisos_evento):
?>
<div style="
    background: rgba(231,76,60,0.15);
    border: 2px solid #e74c3c;
    color: #e74c3c;
    border-radius: var(--radius, 8px);
    padding: 16px 20px;
    margin: 16px;
    font-weight: 600;
    text-align: center;
">
  <?php if ($hay_avisos_push): ?>
    <?php while ($a = mysqli_fetch_assoc($avisos_push)): ?>
      <div style="margin-bottom: 6px;">
        🔧 <?= htmlspecialchars($a['ultimo_error']) ?>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>

  <?php if ($hay_avisos_evento): ?>
    <?php while ($a = mysqli_fetch_assoc($avisos_evento)): ?>
      <div style="margin-bottom: 6px;">
        🔧 <?= htmlspecialchars($a['ultimo_error']) ?>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>
<?php endif; ?>
