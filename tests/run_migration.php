<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== Verificando estructura de la base de datos ===\n\n";

// Verificar si tasas_cambio existe
try {
    $stmt = $conn->query("SELECT COUNT(*) FROM tasas_cambio");
    echo "tasas_cambio: EXISTE\n";
} catch (Exception $e) {
    echo "tasas_cambio: NO EXISTE (se creará)\n";
}

// Verificar campos en productos
$stmt = $conn->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'productos' AND column_name LIKE '%bs%' OR column_name LIKE '%usd%' OR column_name = 'moneda_base'");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Campos moneda en productos: " . (empty($cols) ? "NO HAY" : implode(", ", $cols)) . "\n";

// Verificar campos en ventas
$stmt = $conn->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'ventas' AND column_name IN ('moneda_pago', 'tasa_cambio', 'monto_bs', 'monto_usd')");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Campos moneda en ventas: " . (empty($cols) ? "NO HAY" : implode(", ", $cols)) . "\n";

// Verificar campos en compras
$stmt = $conn->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'compras' AND column_name IN ('moneda_factura', 'tasa_cambio', 'monto_bs', 'monto_usd')");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Campos moneda en compras: " . (empty($cols) ? "NO HAY" : implode(", ", $cols)) . "\n";

echo "\n=== Ejecutando migración ===\n";

// Ejecutar migraciÃ³n
$sql = "
-- Tabla de tasas de cambio
CREATE TABLE IF NOT EXISTS tasas_cambio (
    id SERIAL PRIMARY KEY,
    tasa NUMERIC(12, 4) NOT NULL,
    moneda_origen VARCHAR(3) DEFAULT 'USD',
    moneda_destino VARCHAR(3) DEFAULT 'VES',
    fecha_vigencia DATE NOT NULL,
    usuario_id INTEGER,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Productos
ALTER TABLE productos ADD COLUMN IF NOT EXISTS moneda_base VARCHAR(3) DEFAULT 'USD';
ALTER TABLE productos ADD COLUMN IF NOT EXISTS precio_venta_bs NUMERIC(12, 2);
ALTER TABLE productos ADD COLUMN IF NOT EXISTS precio_venta_usd NUMERIC(12, 2);
ALTER TABLE productos ADD COLUMN IF NOT EXISTS precio_compra_bs NUMERIC(12, 2);
ALTER TABLE productos ADD COLUMN IF NOT EXISTS precio_compra_usd NUMERIC(12, 2);

-- Ventas
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS moneda_pago VARCHAR(3) DEFAULT 'USD';
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS tasa_cambio NUMERIC(12, 4);
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS monto_bs NUMERIC(12, 2);
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS monto_usd NUMERIC(12, 2);
ALTER TABLE detalle_ventas ADD COLUMN IF NOT EXISTS precio_unitario_bs NUMERIC(12, 2);
ALTER TABLE detalle_ventas ADD COLUMN IF NOT EXISTS precio_unitario_usd NUMERIC(12, 2);

-- Compras
ALTER TABLE compras ADD COLUMN IF NOT EXISTS moneda_factura VARCHAR(3) DEFAULT 'USD';
ALTER TABLE compras ADD COLUMN IF NOT EXISTS tasa_cambio NUMERIC(12, 4);
ALTER TABLE compras ADD COLUMN IF NOT EXISTS monto_bs NUMERIC(12, 2);
ALTER TABLE compras ADD COLUMN IF NOT EXISTS monto_usd NUMERIC(12, 2);
ALTER TABLE detalle_compras ADD COLUMN IF NOT EXISTS precio_unitario_bs NUMERIC(12, 2);
ALTER TABLE detalle_compras ADD COLUMN IF NOT EXISTS precio_unitario_usd NUMERIC(12, 2);

-- Insertar tasa inicial
INSERT INTO tasas_cambio (tasa, moneda_origen, moneda_destino, fecha_vigencia, observaciones)
SELECT 35.50, 'USD', 'VES', CURRENT_DATE, 'Tasa inicial'
WHERE NOT EXISTS (SELECT 1 FROM tasas_cambio LIMIT 1);
";

try {
    $conn->exec($sql);
    echo "Migración ejecutada correctamente!\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Verificando resultado ===\n";

// Verificar de nuevo
$stmt = $conn->query("SELECT COUNT(*) FROM tasas_cambio");
echo "tasas_cambio: " . ($stmt->fetchColumn() > 0 ? "EXISTE" : "NO EXISTE") . "\n";

$stmt = $conn->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'productos' AND (column_name LIKE '%bs%' OR column_name LIKE '%usd%' OR column_name = 'moneda_base')");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Campos moneda en productos: " . (empty($cols) ? "NO HAY" : implode(", ", $cols)) . "\n";
