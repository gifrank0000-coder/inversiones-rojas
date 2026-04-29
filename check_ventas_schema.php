<?php
require_once __DIR__ . '/app/models/database.php';

try {
    $pdo = Database::getInstance();
    
    // Obtener esquema de tabla ventas
    echo "=== Esquema de tabla 'ventas' ===\n";
    $st = $pdo->query("
        SELECT column_name, data_type, is_nullable, column_default
        FROM information_schema.columns
        WHERE table_name = 'ventas'
        ORDER BY ordinal_position
    ");
    
    $columns = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        $nullable = $col['is_nullable'] === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $col['column_default'] ? " DEFAULT {$col['column_default']}" : "";
        echo "  - {$col['column_name']}: {$col['data_type']} ($nullable)$default\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
