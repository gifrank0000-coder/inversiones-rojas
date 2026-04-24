<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== Ejecutando migración v2 ===\n";

try {
    // Agregar campo moneda a metodos_pago (solo 3 chars)
    $conn->exec("ALTER TABLE metodos_pago ADD COLUMN IF NOT EXISTS moneda VARCHAR(10) DEFAULT 'AMBOS'");
    echo "Campo moneda agregado a metodos_pago\n";
} catch (Exception $e) {
    echo "Nota: " . $e->getMessage() . "\n";
}

try {
    // Agregar métodos de pago
    $conn->exec("INSERT INTO metodos_pago (nombre, descripcion, moneda) 
        SELECT 'Efectivo $', 'Pago en dólares efectivo', 'USD'
        WHERE NOT EXISTS (SELECT 1 FROM metodos_pago WHERE nombre = 'Efectivo $')");
    echo "Método Efectivo $ agregado\n";
    
    $conn->exec("INSERT INTO metodos_pago (nombre, descripcion, moneda) 
        SELECT 'Efectivo Bs', 'Pago en bolívares efectivo', 'BS'
        WHERE NOT EXISTS (SELECT 1 FROM metodos_pago WHERE nombre = 'Efectivo Bs')");
    echo "Método Efectivo Bs agregado\n";
} catch (Exception $e) {
    echo "Nota métodos: " . $e->getMessage() . "\n";
}

try {
    $conn->exec("ALTER TABLE compras ADD COLUMN IF NOT EXISTS moneda_pago VARCHAR(10) DEFAULT 'USD'");
    echo "Campo moneda_pago agregado a compras\n";
} catch (Exception $e) {
    echo "Nota compras: " . $e->getMessage() . "\n";
}

try {
    $conn->exec("ALTER TABLE compras ADD COLUMN IF NOT EXISTS metodo_pago_id INTEGER REFERENCES metodos_pago(id)");
    echo "Campo metodo_pago_id agregado a compras\n";
} catch (Exception $e) {
    echo "Nota metodo_pago_id: " . $e->getMessage() . "\n";
}

// Verificar métodos de pago
$stmt = $conn->query("SELECT id, nombre, moneda FROM metodos_pago ORDER BY id");
$metodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\nMétodos de pago:\n";
foreach ($metodos as $m) {
    echo "  - {$m['nombre']} (moneda: {$m['moneda']})\n";
}
