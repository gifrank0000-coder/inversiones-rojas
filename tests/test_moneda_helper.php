<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/config.php';

echo "Config loaded\n";

require_once __DIR__ . '/../app/helpers/moneda_helper.php';

echo "Helper loaded\n";

$tasa = getTasaCambio();
echo "Tasa: " . $tasa . "\n";
