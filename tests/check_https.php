<?php
// Comprobación de HTTPS neutralizada por petición del proyecto.
// Este archivo ahora devuelve un JSON simple indicando que la comprobación
// ha sido desactivada para evitar referencias a configuraciones HTTPS locales.

header('Content-Type: application/json');
echo json_encode([
    'isSecure' => false,
    'note' => 'Comprobación HTTPS desactivada en el repositorio por solicitud del mantenedor.'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

?>
