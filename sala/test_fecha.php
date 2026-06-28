<?php
echo 'Hora PHP: ' . date('H:i:s') . '<br>';
echo 'Timezone: ' . date_default_timezone_get() . '<br>';
require 'fecha_helpers.php';
echo 'fechaOperativa: ' . fechaOperativa();