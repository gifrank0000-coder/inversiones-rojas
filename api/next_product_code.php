<?php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../app/models/database.php';

// Agrega logging para debug
error_log("DEBUG next_product_code.php: Iniciando, category_id = " . ($_GET['category_id'] ?? 'no definido'));

$db = new Database();
$conn = $db->getConnection();
if (!$conn) {
    error_log("ERROR next_product_code.php: No hay conexión a BD");
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No DB connection']);
    exit;
}

$catId = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
error_log("DEBUG next_product_code.php: catId = $catId");

if ($catId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'category_id requerido']);
    exit;
}

try {
    // Consulta 1: Obtener nombre de categoría
    $q = $conn->prepare('SELECT nombre FROM categorias WHERE id = :id');
    $q->bindValue(':id', $catId, PDO::PARAM_INT);
    $q->execute();
    $catName = $q->fetchColumn();
    
    error_log("DEBUG next_product_code.php: catName = " . ($catName ?: 'NO ENCONTRADO'));
    
    if (!$catName) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Categoría no encontrada']);
        exit;
    }

    // Mapear nombres conocidos a prefijos fijos
    $map = [
        'Motocicletas' => 'MOTO',
        'Cascos' => 'CASCO',
        'Aceites' => 'ACEITE',
        'Repuestos' => 'REP',
        'Herramientas' => 'HERR'
    ];

    $prefix = null;
    foreach ($map as $name => $p) {
        if (stripos($catName, $name) !== false) { 
            $prefix = $p; 
            break; 
        }
    }
    
    if (!$prefix) {
        // fallback: tomar la primera palabra en mayúsculas
        $parts = preg_split('/\s+/', trim($catName));
        $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $parts[0] ?? 'CAT'));
        $prefix = substr($prefix, 0, 6);
    }
    
    error_log("DEBUG next_product_code.php: prefix = $prefix");

    // Obtener el máximo número existente para ese prefijo de forma robusta
    // Usamos regexp_replace para tomar la parte después del último guion y castear a integer
    $stmt = $conn->prepare("SELECT MAX( (regexp_replace(codigo_interno, '^.*-', ''))::integer ) AS maxnum
                           FROM productos
                           WHERE codigo_interno LIKE :like_prefix");
    $stmt->bindValue(':like_prefix', $prefix . '-%');
    $stmt->execute();
    $maxnum = $stmt->fetchColumn();

    error_log("DEBUG next_product_code.php: maxnum = " . ($maxnum ?? 'NULL'));

    $next = ($maxnum !== null && $maxnum !== false) ? intval($maxnum) + 1 : 1;

    // Asegurar que el código generado no exista (evita colisiones simples)
    do {
        $code = sprintf('%s-%03d', $prefix, $next);
        $check = $conn->prepare('SELECT 1 FROM productos WHERE codigo_interno = :code LIMIT 1');
        $check->bindValue(':code', $code);
        $check->execute();
        $exists = $check->fetchColumn();
        if ($exists) {
            $next++;
        }
    } while ($exists);
    
    error_log("DEBUG next_product_code.php: código generado = $code");

    echo json_encode(['ok' => true, 'code' => $code, 'prefix' => $prefix]);
    
} catch (Exception $e) {
    error_log("ERROR next_product_code.php: " . $e->getMessage());
    error_log("ERROR next_product_code.php: Trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error al calcular código: ' . $e->getMessage()]);
}

exit;
?>