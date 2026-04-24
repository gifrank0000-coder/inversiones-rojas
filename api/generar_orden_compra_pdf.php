<?php
/**
 * generar_orden_compra_pdf.php
 * Genera PDF de orden de compra con el diseño actual
 * Ruta: /inversiones-rojas/api/generar_orden_compra_pdf.php
 */

require_once __DIR__ . '/../dompdf-3.1.5/dompdf/autoload.inc.php'; // Ajusta según tu estructura
require_once __DIR__ . '/../app/models/database.php';

use Dompdf\Dompdf;
use Dompdf\Options;

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Validar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }
    
    // Leer datos
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['compra_id'])) {
        throw new Exception('ID de compra no proporcionado');
    }
    
    $compra_id = (int) $input['compra_id'];
    
    // Conectar a BD
    $db = new Database();
    $conn = $db->getConnection();
    
    // Obtener datos de la compra
    $query = "
        SELECT 
            c.id,
            c.codigo_compra,
            c.subtotal,
            c.iva,
            c.total,
            c.fecha_estimada_entrega,
            c.observaciones,
            c.created_at,
            p.razon_social as proveedor_nombre,
            p.rif as proveedor_rif,
            p.direccion as proveedor_direccion,
            p.telefono_principal as proveedor_telefono,
            u.nombre_completo as comprador_nombre,
            u.email as comprador_email,
            '' as comprador_telefono
        FROM compras c
        LEFT JOIN proveedores p ON c.proveedor_id = p.id
        LEFT JOIN usuarios u ON c.usuario_id = u.id
        WHERE c.id = ?
    ";
    
$stmt = $conn->prepare($query);
    $stmt->execute([$compra_id]);
    $compra = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$compra) {
        throw new Exception('Compra no encontrada');
    }
    
    // Obtener productos de la compra
    $query_productos = "
        SELECT 
            dc.cantidad,
            dc.precio_unitario,
            dc.subtotal,
            pr.codigo_interno,
            pr.nombre as producto_nombre
        FROM detalle_compras dc
        JOIN productos pr ON dc.producto_id = pr.id
        WHERE dc.compra_id = ?
        ORDER BY pr.nombre
    ";
    
    $stmt = $conn->prepare($query_productos);
    $stmt->execute([$compra_id]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Información de la empresa (puedes sacarla de BD o configuración)
    $empresa = [
        'nombre' => 'Inversiones Rojas C.A.',
        'rif' => 'J-12345678-9',
        'direccion' => 'Av. Principal, Caracas, Venezuela',
        'telefono' => '+58 212-1234567'
    ];
    
    // Generar HTML de productos
    $productosHTML = '';
    foreach ($productos as $prod) {
        $productosHTML .= "
            <tr>
                <td>{$prod['codigo_interno']}</td>
                <td>{$prod['producto_nombre']}</td>
                <td class=\"text-center\">{$prod['cantidad']}</td>
                <td class=\"text-right\">$ " . number_format($prod['precio_unitario'], 2) . "</td>
                <td class=\"text-right\">$ " . number_format($prod['subtotal'], 2) . "</td>
            </tr>
        ";
    }
    
    // Crear HTML completo (usando el mismo diseño que tienes actualmente)
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Orden de Compra {$compra['codigo_compra']}</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
                line-height: 1.6;
                color: #333;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
            }
            .header h1 {
                color: #1F9166;
                margin: 0;
                font-size: 24px;
            }
            .header h2 {
                margin: 5px 0;
                font-size: 18px;
            }
            .empresa-info {
                text-align: center;
                margin-bottom: 20px;
                padding: 15px;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
            .empresa-info h3 {
                margin-top: 0;
                color: #333;
            }
            .info-grid {
                display: table;
                width: 100%;
                margin-bottom: 30px;
            }
            .info-box {
                display: table-cell;
                width: 50%;
                border: 1px solid #ddd;
                padding: 15px;
                vertical-align: top;
            }
            .info-box:first-child {
                border-right: none;
            }
            .info-box h3 {
                margin-top: 0;
                color: #1F9166;
                border-bottom: 1px solid #eee;
                padding-bottom: 8px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 10px;
                text-align: left;
            }
            th {
                background: #f5f5f5;
                font-weight: bold;
            }
            .text-right {
                text-align: right;
            }
            .text-center {
                text-align: center;
            }
            .total-line {
                font-size: 1.2em;
                font-weight: bold;
                color: #1F9166;
            }
            .footer {
                margin-top: 50px;
                text-align: center;
                font-style: italic;
                border-top: 1px solid #ddd;
                padding-top: 20px;
            }
            .observaciones {
                margin-top: 20px;
                padding: 15px;
                background: #f9f9f9;
                border-left: 4px solid #1F9166;
            }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>ORDEN DE COMPRA</h1>
            <h2>N°: {$compra['codigo_compra']}</h2>
            <p><strong>Fecha:</strong> " . date('d/m/Y', strtotime($compra['created_at'])) . "</p>
        </div>

        <div class='empresa-info'>
            <h3>{$empresa['nombre']}</h3>
            <p><strong>RIF:</strong> {$empresa['rif']}</p>
            <p><strong>Dirección:</strong> {$empresa['direccion']}</p>
            <p><strong>Teléfono:</strong> {$empresa['telefono']}</p>
        </div>

        <div class='info-grid'>
            <div class='info-box'>
                <h3>Proveedor</h3>
                <p><strong>Nombre:</strong> {$compra['proveedor_nombre']}</p>
                <p><strong>RIF:</strong> {$compra['proveedor_rif']}</p>
                <p><strong>Dirección:</strong> {$compra['proveedor_direccion']}</p>
                <p><strong>Teléfono:</strong> {$compra['proveedor_telefono']}</p>
            </div>
            <div class='info-box'>
                <h3>Comprador</h3>
                <p><strong>Nombre:</strong> {$compra['comprador_nombre']}</p>
                <p><strong>Correo:</strong> {$compra['comprador_email']}</p>
                <p><strong>Teléfono:</strong> {$compra['comprador_telefono']}</p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th class='text-center'>Cantidad</th>
                    <th class='text-right'>Precio Unit.</th>
                    <th class='text-right'>Total</th>
                </tr>
            </thead>
            <tbody>
                {$productosHTML}
            </tbody>
            <tfoot>
                <tr>
                    <td colspan='4' class='text-right'><strong>Subtotal:</strong></td>
                    <td class='text-right'>$ " . number_format($compra['subtotal'], 2) . "</td>
                </tr>
                <tr>
                    <td colspan='4' class='text-right'><strong>IVA (16%):</strong></td>
                    <td class='text-right'>$ " . number_format($compra['iva'], 2) . "</td>
                </tr>
                <tr>
                    <td colspan='4' class='text-right'><strong>TOTAL:</strong></td>
                    <td class='text-right total-line'>$ " . number_format($compra['total'], 2) . "</td>
                </tr>
            </tfoot>
        </table>
    ";
    
    if (!empty($compra['observaciones'])) {
        $html .= "
        <div class='observaciones'>
            <p><strong>Observaciones:</strong></p>
            <p>{$compra['observaciones']}</p>
        </div>
        ";
    }
    
    $html .= "
        <div class='footer'>
            <p>Gracias por su excelente servicio</p>
            <p>Atentamente, {$empresa['nombre']}</p>
        </div>
    </body>
    </html>
    ";
    
    // Configurar Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'Arial');
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('Letter', 'portrait');
    $dompdf->render();
    
    // Nombre del archivo
    $filename = "Orden_Compra_{$compra['codigo_compra']}.pdf";
    
    // Enviar el PDF
    header('Content-Type: application/pdf');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    echo $dompdf->output();
    exit;
    
} catch (Exception $e) {
    error_log("Error generando PDF orden de compra: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error al generar PDF: ' . $e->getMessage()
    ]);
    exit;
}