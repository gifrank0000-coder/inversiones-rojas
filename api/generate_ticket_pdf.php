<?php
// generate_ticket_pdf.php - Comprobante de Pago para punto de venta
error_reporting(0);
ini_set('display_errors', '0');
if (ob_get_length()) ob_end_clean();

require_once __DIR__ . '/../lib/fpdf/fpdf.php';

if (!isset($_GET['venta_id'])) {
    die('ID de venta requerido');
}

$venta_id = intval($_GET['venta_id']);

require_once __DIR__ . '/../app/models/database.php';
$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    die('Error de conexión a la base de datos');
}

$sql = "SELECT v.*, c.nombre_completo as cliente_nombre, c.cedula_rif as cliente_cedula,
               mp.nombre as metodo_pago, u.nombre_completo as vendedor
        FROM ventas v
        LEFT JOIN clientes c ON v.cliente_id = c.id
        LEFT JOIN metodos_pago mp ON v.metodo_pago_id = mp.id
        LEFT JOIN usuarios u ON v.usuario_id = u.id
        WHERE v.id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$venta_id]);
$venta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
    die('Venta no encontrada');
}

$sql_detalles = "SELECT dv.*, p.nombre as producto_nombre
                 FROM detalle_ventas dv
                 JOIN productos p ON dv.producto_id = p.id
                 WHERE dv.venta_id = ?";
$stmt_detalles = $conn->prepare($sql_detalles);
$stmt_detalles->execute([$venta_id]);
$detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

class ComprobantePDF extends FPDF {
  
}

$pdf = new ComprobantePDF('P', 'mm', array(80, 200));
$pdf->AddPage();
$pdf->SetMargins(5, 3, 5);

// === ENCABEZADO CENTRADO ===
$pdf->SetFont('Courier', 'B', 8);
$pdf->Cell(0, 4, 'INVERSIONES ROJAS 2016. C.A.', 0, 1, 'C');
$pdf->SetFont('Courier', '', 6);
$pdf->Cell(0, 3, 'RIF: J-40888806-8', 0, 1, 'C');
$pdf->Cell(0, 3, 'AV ARAGUA LOCAL NRO 286', 0, 1, 'C');
$pdf->Cell(0, 3, 'SECTOR ANDRES ELOY BLANCO, MARACAY', 0, 1, 'C');
$pdf->Cell(0, 3, 'TEL: 0243-2343044', 0, 1, 'C');
$pdf->Ln(3);

// Línea separadora
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(3);

// === DATOS DE FACTURA CON FORMATO DE DOS COLUMNAS ===
$fechaNormal = date('d/m/Y', strtotime($venta['created_at']));
$horaNormal = date('h:i:s A', strtotime($venta['created_at']));

$pdf->SetFont('Courier', 'B', 6);
$pdf->Cell(18, 3, 'COMPROBANTE:', 0, 0, 'L');
$pdf->SetFont('Courier', '', 6);
$pdf->Cell(0, 3, $venta['codigo_venta'], 0, 1, 'L');

$pdf->SetFont('Courier', 'B', 6);
$pdf->Cell(18, 3, 'FECHA:', 0, 0, 'L');
$pdf->SetFont('Courier', '', 6);
$pdf->Cell(20, 3, $fechaNormal, 0, 0, 'L');
$pdf->SetFont('Courier', 'B', 6);
$pdf->Cell(10, 3, 'HORA:', 0, 0, 'L');
$pdf->SetFont('Courier', '', 6);
$pdf->Cell(0, 3, $horaNormal, 0, 1, 'L');

$pdf->SetFont('Courier', 'B', 6);
$pdf->Cell(18, 3, 'CLIENTE:', 0, 0, 'L');
$pdf->SetFont('Courier', '', 6);
$pdf->Cell(0, 3, ($venta['cliente_nombre'] ?: 'CLIENTE GENERAL'), 0, 1, 'L');

$pdf->SetFont('Courier', 'B', 6);
$pdf->Cell(18, 3, 'CEDULA:', 0, 0, 'L');
$pdf->SetFont('Courier', '', 6);
$pdf->Cell(0, 3, ($venta['cliente_cedula'] ?: 'V-00000000'), 0, 1, 'L');

$pdf->SetFont('Courier', 'B', 6);
$pdf->Cell(18, 3, 'VENDEDOR:', 0, 0, 'L');
$pdf->SetFont('Courier', '', 6);
$pdf->Cell(0, 3, ($venta['vendedor'] ?: 'SISTEMA'), 0, 1, 'L');

$pdf->Ln(2);
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(3);

// === PRODUCTOS ===
$pdf->SetFont('Courier', 'B', 7);
$pdf->Cell(0, 3, 'PRODUCTOS', 0, 1, 'C');
$pdf->Ln(2);

// Cabecera de productos con columnas definidas
$pdf->SetFont('Courier', 'B', 5);
$pdf->Cell(30, 3, 'Producto', 0, 0, 'L');
$pdf->Cell(15, 3, 'P.Unit', 0, 0, 'C');
$pdf->Cell(8, 3, 'Cant', 0, 0, 'C');
$pdf->Cell(0, 3, 'Total', 0, 1, 'R');

$pdf->SetFont('Courier', '', 5);

foreach ($detalles as $detalle) {
    $nombre = $detalle['producto_nombre'];
    $cantidad = $detalle['cantidad'];
    $precio = number_format($detalle['precio_unitario'], 2);
    $total = number_format($detalle['subtotal'], 2);
    
    $pdf->SetFont('Courier', 'B', 5);
    $pdf->MultiCell(0, 2.5, $nombre, 0, 'L');
    
    $pdf->SetFont('Courier', '', 5);
    $pdf->Cell(30, 2.5, '', 0, 0, 'L');
    $pdf->Cell(15, 2.5, 'Bs' . $precio, 0, 0, 'C');
    $pdf->Cell(8, 2.5, $cantidad, 0, 0, 'C');
    $pdf->Cell(0, 2.5, 'Bs' . $total, 0, 1, 'R');
}

$pdf->Ln(2);
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(3);

// === MÉTODO DE PAGO Y OBSERVACIONES EN DOS COLUMNAS ===
$pdf->SetFont('Courier', 'B', 5);
$pdf->Cell(20, 3, 'METODO DE PAGO:', 0, 0, 'L');
$pdf->SetFont('Courier', '', 5);
$pdf->Cell(0, 3, ($venta['metodo_pago'] ?: 'NO ESPECIFICADO'), 0, 1, 'L');

if (!empty($venta['observaciones'])) {
    $pdf->SetFont('Courier', '', 4.5);
    $observacion_limpia = str_replace(["\r", "\n"], ' ', $venta['observaciones']);
    $observacion_limpia = str_replace(['OBSERVACIONES:', 'Observaciones:', 'observaciones:'], '', $observacion_limpia);
    $pdf->Cell(0, 2.5, trim($observacion_limpia), 0, 1, 'L');
}

$pdf->Ln(2);
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(3);

// === TOTALES ALINEADOS A LA DERECHA ===
$subtotal = $venta['subtotal'] ?: array_sum(array_column($detalles, 'subtotal'));
$iva = $venta['iva'] ?: ($subtotal * 0.16);
$total = $venta['total'];

$pdf->SetFont('Courier', '', 6);
$pdf->Cell(50, 3, 'SUBTOTAL:', 0, 0, 'R');
$pdf->Cell(0, 3, 'Bs' . number_format($subtotal, 2), 0, 1, 'R');

$pdf->Cell(50, 3, 'IVA (16%):', 0, 0, 'R');
$pdf->Cell(0, 3, 'Bs' . number_format($iva, 2), 0, 1, 'R');

$pdf->SetFont('Courier', 'B', 7);
$pdf->Cell(50, 4, 'TOTAL BS.:', 0, 0, 'R');
$pdf->Cell(0, 4, 'Bs' . number_format($total, 2), 0, 1, 'R');

$pdf->Ln(3);
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(3);

// === PIE DE PÁGINA CENTRADO ===
$pdf->SetFont('Courier', 'B', 6);
$pdf->Cell(0, 3, chr(161) . 'GRACIAS POR SU COMPRA!', 0, 1, 'C');$pdf->SetFont('Courier', '', 5);
$pdf->Cell(0, 2.5, 'VUELVA PRONTO', 0, 1, 'C');
$pdf->Ln(1);
$pdf->SetFont('Courier', '', 4);
$pdf->Cell(0, 2, $fechaNormal . ' ' . $horaNormal, 0, 1, 'C');
$pdf->SetFont('Courier', 'I', 3.5);
$pdf->Cell(0, 2, '*** TICKET NO VALIDO COMO FACTURA ***', 0, 1, 'C');

$filename = 'comprobante_' . $venta['codigo_venta'] . '.pdf';
$pdf->Output('D', $filename);
?>