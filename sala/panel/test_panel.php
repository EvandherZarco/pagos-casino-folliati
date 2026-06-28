<?php
require_once __DIR__ . '/../fecha_helpers.php';
echo 'fechaOperativa: ' . fechaOperativa() . '<br>';
echo 'date d/m/Y: ' . date('d/m/Y', strtotime(fechaOperativa()));