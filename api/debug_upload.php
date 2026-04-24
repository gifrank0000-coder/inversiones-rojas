<?php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

// Registrar para el log de Apache/PHP
error_log("DEBUG debug_upload.php: invocado. Method=" . $_SERVER['REQUEST_METHOD']);

$info = [];
$info['method'] = $_SERVER['REQUEST_METHOD'] ?? null;
$info['request_uri'] = $_SERVER['REQUEST_URI'] ?? null;
$info['headers'] = getallheaders();
$info['cookies'] = $_COOKIE ?? [];
$info['session_id'] = session_id();
$info['session_user'] = $_SESSION['user_id'] ?? null;
$info['post'] = $_POST ?? [];
$info['files'] = [];

// Normalize $_FILES structure for easier reading
foreach ($_FILES as $k => $v) {
    if (is_array($v['name'])) {
        $cnt = count($v['name']);
        $arr = [];
        for ($i = 0; $i < $cnt; $i++) {
            $arr[] = [
                'name' => $v['name'][$i] ?? null,
                'type' => $v['type'][$i] ?? null,
                'tmp_name' => $v['tmp_name'][$i] ?? null,
                'error' => $v['error'][$i] ?? null,
                'size' => $v['size'][$i] ?? null,
            ];
        }
        $info['files'][$k] = $arr;
    } else {
        $info['files'][$k] = [
            'name' => $v['name'] ?? null,
            'type' => $v['type'] ?? null,
            'tmp_name' => $v['tmp_name'] ?? null,
            'error' => $v['error'] ?? null,
            'size' => $v['size'] ?? null,
        ];
    }
}

$info['php_ini'] = [
    'file_uploads' => ini_get('file_uploads'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'upload_tmp_dir' => ini_get('upload_tmp_dir'),
    'max_file_uploads' => ini_get('max_file_uploads')
];

// También incluir el raw input para ver si algo llega
$raw = @file_get_contents('php://input');
if ($raw !== false && strlen($raw) > 0) {
    $info['raw_input_snippet'] = substr($raw, 0, 1000);
} else {
    $info['raw_input_snippet'] = null;
}

error_log('DEBUG debug_upload.php: devolver info: ' . json_encode([ 'post' => $info['post'], 'files' => array_map(function($f){ return is_array($f) ? array_map(function($x){ return $x['name'] ?? null; }, $f) : ($f['name'] ?? null); }, $info['files']) ]));

echo json_encode(['ok' => true, 'debug' => $info], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

exit;
?>
