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
    die('Error de conexion a la base de datos');
}

// Obtener venta con tasa de cambio
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

// Obtener detalles de venta
$sql_detalles = "SELECT dv.*, p.nombre as producto_nombre
                 FROM detalle_ventas dv
                 JOIN productos p ON dv.producto_id = p.id
                 WHERE dv.venta_id = ?";
$stmt_detalles = $conn->prepare($sql_detalles);
$stmt_detalles->execute([$venta_id]);
$detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

// Obtener pagos multiples si existen
$sql_pagos = "SELECT vp.*, mp.nombre as metodo_nombre
              FROM venta_pagos vp
              LEFT JOIN metodos_pago mp ON vp.metodo_pago_id = mp.id
              WHERE vp.venta_id = ?
              ORDER BY vp.id";
$stmt_pagos = $conn->prepare($sql_pagos);
$stmt_pagos->execute([$venta_id]);
$pagos = $stmt_pagos->fetchAll(PDO::FETCH_ASSOC);

// Funcion para formatear numeros con estilo venezolano
function formatVenezolanNumber($amount) {
    return number_format($amount, 2, ',', '.');
}

// Tasa de cambio
$tasa = floatval($venta['tasa_cambio']) ?: 35.50;

class ComprobantePDF extends FPDF {
    public function Header() {}
    public function Footer() {}
}

$pdf = new ComprobantePDF('P', 'mm', array(80, 200));
$pdf->AddPage();
$pdf->SetMargins(4, 3, 4);

// === ENCABEZADO ===
$pdf->SetFont('Courier', 'B', 8);
$pdf->Cell(0, 3.5, 'INVERSIONES ROJAS 2016. C.A.', 0, 1, 'C');

$pdf->SetFont('Courier', '', 6);
$pdf->Cell(0, 2.8, 'RIF: J-40888806-8', 0, 1, 'C');
$pdf->Cell(0, 2.8, 'AV ARAGUA LOCAL NRO 286', 0, 1, 'C');
$pdf->Cell(0, 2.8, 'MARACAY, ARAGUA', 0, 1, 'C');
$pdf->Cell(0, 2.8, 'TEL: 0243-2343044', 0, 1, 'C');
$pdf->Ln(2);

// Linea separadora
$pdf->SetDrawColor(0);
$pdf->Line(4, $pdf->GetY(), 76, $pdf->GetY());
$pdf->Ln(2);

// === COMPROBANTE Y DATOS ===
$fechaFmt = date('d/m/Y', strtotime($venta['created_at']));
$horaFmt = date('H:i:s', strtotime($venta['created_at']));

$pdf->SetFont('Courier', 'B', 7);
$pdf->Cell(0, 3, 'COMPROBANTE', 0, 1, 'C');
$pdf->Ln(1);

$pdf->SetFont('Courier', 'B', 6);
$pdf->Cell(20, 3, 'Codigo:', 0, 0, 'L');
$pdf->SetFont('Courier', '', 6);
$pdf->Cell(0, 3, $venta['codigo_venta'], 0, 1, 'L');

$pdf->SetFont('Courier', 'B', 6);
$pdf->Cell(20, 3, 'Fecha:', 0, 0, 'L');
$pdf->SetFont('Courier', '', 6);
$pdf->Cell(0, 3, $fechaFmt, 0, 1, 'L');

$pdf->SetFont('Courier', 'B', 6);
$pdf->Cell(20, 3, 'Hora:', 0, 0, 'L');
$pdf->SetFont('Courier', '', 6);
$pdf->Cell(0, 3, $horaFmt, 0, 1, 'L');

$pdf->SetFont('Courier', 'B', 6);
$pdf->Cell(20, 3, 'Cliente:', 0, 0, 'L');
$pdf->SetFont('Courier', '', 6);
$cliente_nombre = $venta['cliente_nombre'] ?: 'CLIENTE GENERAL';
$pdf->MultiCell(0, 3, substr($cliente_nombre, 0, 35), 0, 'L');

$pdf->SetFont('Courier', 'B', 6);
$pdf->Cell(20, 3, 'Cedula:', 0, 0, 'L');
$pdf->SetFont('Courier', '', 6);
$pdf->Cell(0, 3, $venta['cliente_cedula'] ?: 'V-00000000', 0, 1, 'L');

$pdf->SetFont('Courier', 'B', 6);
$pdf->Cell(20, 3, 'Vendedor:', 0, 0, 'L');
$pdf->SetFont('Courier', '', 6);
$pdf->Cell(0, 3, $venta['vendedor'] ?: 'SISTEMA', 0, 1, 'L');

$pdf->Ln(1);
$pdf->Line(4, $pdf->GetY(), 76, $pdf->GetY());
$pdf->Ln(2);

// === PRODUCTOS ===
$pdf->SetFont('Courier', 'B', 6);
$pdf->Cell(0, 3, 'PRODUCTOS', 0, 1, 'C');
$pdf->Ln(1);

$pdf->SetFont('Courier', 'B', 5);
$pdf->Cell(28, 3, 'Producto', 0, 0, 'L');
$pdf->Cell(14, 3, 'Cantidad', 0, 0, 'C');
$pdf->Cell(0, 3, 'Subtotal', 0, 1, 'R');

$pdf->Line(4, $pdf->GetY(), 76, $pdf->GetY());
$pdf->Ln(1);

$pdf->SetFont('Courier', '', 5);

foreach ($detalles as $detalle) {
    $nombre = substr($detalle['producto_nombre'], 0, 28);
    $cantidad = intval($detalle['cantidad']);
    
    // Convertir a Bs
    $precio_usd = floatval($detalle['precio_unitario']);
    $subtotal_usd = floatval($detalle['subtotal']);
    $subtotal_bs = $subtotal_usd * $tasa;
    
    $pdf->Cell(28, 3, $nombre, 0, 0, 'L');
    $pdf->Cell(14, 3, 'x' . $cantidad, 0, 0, 'C');
    $pdf->Cell(0, 3, formatVenezolanNumber($subtotal_bs), 0, 1, 'R');
}

$pdf->Ln(1);
$pdf->Line(4, $pdf->GetY(), 76, $pdf->GetY());
$pdf->Ln(2);

// === METODO DE PAGO (ANTES DE TOTALES) ===
$pdf->SetFont('Courier', 'B', 6);
$pdf->Cell(0, 3, 'PAGO', 0, 1, 'C');
$pdf->Ln(1);

$pdf->SetFont('Courier', '', 5.5);

if (!empty($pagos) && count($pagos) > 0) {
    // Pagos multiples
    foreach ($pagos as $pago) {
        $monto = floatval($pago['monto']);
        // Convertir a Bs si viene en USD
        if (strtoupper($pago['moneda'] ?? 'USD') === 'USD') {
            $monto_bs = $monto * $tasa;
        } else {
            $monto_bs = $monto;
        }
        
        $nombre_metodo = $pago['metodo_nombre'] ?: 'Pago';
        $pdf->Cell(50, 3, $nombre_metodo . ':', 0, 0, 'L');
        $pdf->Cell(0, 3, formatVenezolanNumber($monto_bs), 0, 1, 'R');
    }
} else {
    // Pago simple
    $nombre_metodo = $venta['metodo_pago'] ?: 'No especificado';
    
    // Obtener moneda del metodo de pago
    $sql_metodo = "SELECT moneda FROM metodos_pago WHERE id = ?";
    $stmt_metodo = $conn->prepare($sql_metodo);
    $stmt_metodo->execute([$venta['metodo_pago_id']]);
    $metodo_info = $stmt_metodo->fetch(PDO::FETCH_ASSOC);
    $moneda_metodo = $metodo_info['moneda'] ?? 'USD';
    
    $total_usd = floatval($venta['total']);
    $total_bs = $total_usd * $tasa;
    
    if (strtoupper($moneda_metodo) === 'USD') {
        $monto_mostrar = formatVenezolanNumber($total_usd);
    } else {
        $monto_mostrar = formatVenezolanNumber($total_bs);
    }
    
    $pdf->Cell(50, 3, $nombre_metodo . ':', 0, 0, 'L');
    $pdf->Cell(0, 3, $monto_mostrar, 0, 1, 'R');
}

$pdf->Ln(1);
$pdf->Line(4, $pdf->GetY(), 76, $pdf->GetY());
$pdf->Ln(2);

// === TOTALES (DESPUES DE PAGOS) ===
$subtotal_usd = floatval($venta['subtotal']);
$iva_usd = floatval($venta['iva']);
$total_usd = floatval($venta['total']);

$subtotal_bs = $subtotal_usd * $tasa;
$iva_bs = $iva_usd * $tasa;
$total_bs = $total_usd * $tasa;

$pdf->SetFont('Courier', '', 6);
$pdf->Cell(50, 3, 'Subtotal:', 0, 0, 'R');
$pdf->Cell(0, 3, formatVenezolanNumber($subtotal_bs), 0, 1, 'R');

$pdf->Cell(50, 3, 'IVA (16%):', 0, 0, 'R');
$pdf->Cell(0, 3, formatVenezolanNumber($iva_bs), 0, 1, 'R');

$pdf->SetFont('Courier', 'B', 7);
$pdf->Cell(50, 4, 'TOTAL:', 0, 0, 'R');
$pdf->Cell(0, 4, formatVenezolanNumber($total_bs), 0, 1, 'R');

$pdf->Ln(1);
$pdf->Line(4, $pdf->GetY(), 76, $pdf->GetY());
$pdf->Ln(2);

// === PIE DE PAGINA ===
$pdf->SetFont('Courier', 'B', 6);
$pdf->Cell(0, 3, 'GRACIAS POR SU COMPRA!', 0, 1, 'C');

$pdf->SetFont('Courier', '', 5);
$pdf->Cell(0, 2.8, 'VUELVA PRONTO', 0, 1, 'C');

$pdf->Ln(1);
$pdf->SetFont('Courier', '', 4.5);
$pdf->Cell(0, 2.5, $fechaFmt . ' ' . $horaFmt, 0, 1, 'C');

$pdf->SetFont('Courier', 'I', 4);
$pdf->Cell(0, 2.5, '*** NO ES FACTURA ***', 0, 1, 'C');

// Generar descarga
$filename = 'Comprobante_' . $venta['codigo_venta'] . '.pdf';
$pdf->Output('I', $filename);
?>
