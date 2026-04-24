<?php
require_once __DIR__ . '/app/models/database.php';

$db = new Database();
$conn = $db->getConnection();

$tables = $conn->query("
    SELECT table_name FROM information_schema.tables
    WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
    ORDER BY table_name
")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $t) {
    echo "\n=== TABLE: $t ===\n";

    $cols = $conn->query("
        SELECT column_name, data_type, is_nullable, column_default
        FROM information_schema.columns
        WHERE table_name = '$t'
        ORDER BY ordinal_position
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cols as $c) {
        $col = $c['column_name'];
        $type = $c['data_type'];
        $null = $c['is_nullable'];
        $def = $c['column_default'] ?? 'NULL';
        echo "  $col | $type | nullable:$null | default:$def\n";
    }

    $cnt = $conn->query("SELECT COUNT(*) FROM \"$t\"")->fetchColumn();
    echo "  ROWS: $cnt\n";
}

// Also check for foreign keys
echo "\n\n=== FOREIGN KEYS ===\n";
$fks = $conn->query("
    SELECT
        tc.table_name,
        kcu.column_name,
        ccu.table_name AS foreign_table_name,
        ccu.column_name AS foreign_column_name
    FROM information_schema.table_constraints AS tc
    JOIN information_schema.key_column_usage AS kcu
        ON tc.constraint_name = kcu.constraint_name
    JOIN information_schema.constraint_column_usage AS ccu
        ON ccu.constraint_name = tc.constraint_name
    WHERE tc.constraint_type = 'FOREIGN KEY'
    ORDER BY tc.table_name
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($fks as $fk) {
    echo "  {$fk['table_name']}.{$fk['column_name']} -> {$fk['foreign_table_name']}.{$fk['foreign_column_name']}\n";
}

echo "\nDone. Total tables: " . count($tables) . "\n";
