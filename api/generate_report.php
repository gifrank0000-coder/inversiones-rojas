<?php
// ============================================================
// REPORTES EMPRESARIALES - INVERSIONES ROJAS 2016 C.A.
// Verde Corporativo | FPDF
// Módulos: ventas, inventario, compras, bitacora
// ============================================================

function u($s) {
    if ($s === null || $s === '') return '';
    $s = str_replace(
        ['á','é','í','ó','ú','ü','ñ','Á','É','Í','Ó','Ú','Ü','Ñ','¿','¡'],
        ['a','e','i','o','u','u','n','A','E','I','O','U','U','N','?','!'],
        (string)$s
    );
    $r = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $s);
    return ($r !== false) ? $r : preg_replace('/[^\x00-\x7F]/', '', $s);
}

error_reporting(E_ALL);
ini_set('display_errors', '0');
ob_clean();
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    die(json_encode(['error' => 'No autenticado']));
}

$data         = json_decode(file_get_contents('php://input'), true);
$report_type  = $data['report_type'] ?? '';
$module       = $data['module'] ?? '';
$report_date  = isset($data['report_date']) ? trim($data['report_date']) : '';
$report_month = isset($data['report_month']) ? trim($data['report_month']) : '';

if (!$report_type || !$module) {
    http_response_code(400);
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Parametros faltantes']));
}

require_once __DIR__ . '/../app/models/database.php';
require_once __DIR__ . '/../lib/fpdf/fpdf.php';

$db   = new Database();
$conn = $db->getConnection();
if (!$conn) {
    http_response_code(500);
    die(json_encode(['error' => 'Error de conexion a BD']));
}

// ================================================================
//  FUNCIONES AUXILIARES PARA MONEDA (Bs only)
// ================================================================

if (!function_exists('getTasaReporte')) {
    function getTasaReporte() {
        static $tasa = null;
        if ($tasa === null) {
            global $conn;
            try {
                // FIX: Use same logic as getTasaCambio() - get rate where fecha_vigencia <= today
                $stmt = $conn->prepare("
                    SELECT tasa 
                    FROM tasas_cambio 
                    WHERE fecha_vigencia <= CURRENT_DATE 
                    ORDER BY fecha_vigencia DESC, created_at DESC 
                    LIMIT 1
                ");
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $tasa = $row ? floatval($row['tasa']) : 400;
            } catch (Exception $e) {
                $tasa = 400;
            }
        }
        return $tasa;
    }
}

if (!function_exists('aBs')) {
    // Convierte cualquier monto a Bs usando la tasa de la transacción o tasa actual
    function aBs($monto, $tasa = null) {
        if ($tasa === null) {
            $tasa = getTasaReporte();
        }
        return floatval($monto) * floatval($tasa);
    }
}

// ================================================================
//  CLASE PDF
// ================================================================
class ReporteEmpresarial extends FPDF {

    protected $CV  = [31,  120,  70];
    protected $CVL = [56,  161, 100];
    protected $CVB = [237, 247, 241];
    protected $CGO = [40,   40,  40];
    protected $CGM = [115, 115, 115];
    protected $CB  = [255, 255, 255];
    protected $CRO = [192,  57,  43];
    protected $CAZ = [41,  128, 185];
    protected $CMO = [142,  68, 173];
    protected $CTQ = [26,  188, 156];
    protected $CAM = [230, 126,  34];

    public $codigoReporte = '';
    public $empresaNombre = 'INVERSIONES ROJAS 2016. C.A.';
    public $empresaRIF    = 'J-40888806-8';
    public $empresaTel    = '0243-2343044';

    function __construct() {
        parent::__construct('P', 'mm', 'Letter');
        $this->SetMargins(14, 36, 14);
        $this->SetAutoPageBreak(true, 18);
        $this->codigoReporte = 'RPT-' . strtoupper(substr(uniqid(), -6));
    }

    function Header() {
        $this->SetFillColor(...$this->CV);
        $this->Rect(0, 0, 216, 20, 'F');
        $this->SetXY(14, 4);
        $this->SetFont('Helvetica', 'B', 11);
        $this->SetTextColor(...$this->CB);
        $this->Cell(120, 6, u($this->empresaNombre), 0, 0, 'L');
        $this->SetFont('Helvetica', '', 7);
        $this->SetXY(14, 12);
        $this->Cell(182, 4, u('RIF: ' . $this->empresaRIF . '   |   Tel: ' . $this->empresaTel), 0, 0, 'R');
        $this->SetFillColor(248, 248, 248);
        $this->SetDrawColor(220, 220, 220);
        $this->SetLineWidth(0.1);
        $this->Rect(0, 20, 210, 6.5, 'FD');
        $this->SetXY(14, 21.5);
        $this->SetFont('Helvetica', 'I', 5.5);
        $this->SetTextColor(...$this->CGM);
        $this->Cell(91, 3.5, u('Reporte: ' . $this->codigoReporte), 0, 0, 'L');
        $this->Cell(91, 3.5, u('Generado: ' . date('d/m/Y  H:i:s')), 0, 0, 'R');
        $this->SetY(30);
    }

    function Footer() {
        $this->SetY(-12);
        $this->SetDrawColor(...$this->CV);
        $this->SetLineWidth(0.3);
        $this->Line(14, $this->GetY(), 196, $this->GetY());
        $this->SetY(-9);
        $this->SetFont('Helvetica', '', 6);
        $this->SetTextColor(...$this->CGM);
        $this->Cell(91, 4, u($this->empresaNombre), 0, 0, 'L');
        $this->Cell(91, 4, u('Pagina ' . $this->PageNo() . ' de {nb}'), 0, 0, 'R');
    }

    function tituloPagina($titulo, $sub = '') {
        $this->SetFont('Helvetica', 'B', 14);
        $this->SetTextColor(...$this->CGO);
        $this->Cell(0, 6, u($titulo), 0, 1, 'L');
        if ($sub) {
            $this->SetFont('Helvetica', '', 6.5);
            $this->SetTextColor(...$this->CGM);
            $this->Cell(0, 3, u($sub), 0, 1, 'L');
        }
        $this->SetFillColor(...$this->CV);
        $this->Rect(14, $this->GetY(), 28, 0.7, 'F');
        $this->Ln(2.5);
    }

    function filaTarjetas($tarjetas) {
        $this->Ln(1);
        $xBase = 14; $w = 45.5; $h = 20; $y = $this->GetY();
        foreach ($tarjetas as $i => $t) {
            $x = $xBase + $i * $w;
            $this->SetFillColor(210, 210, 210);
            $this->Rect($x + 0.6, $y + 0.6, $w, $h, 'F');
            $this->SetFillColor(...$this->CB);
            $this->SetDrawColor(225, 225, 225);
            $this->SetLineWidth(0.15);
            $this->Rect($x, $y, $w, $h, 'FD');
            $this->SetFillColor(...$this->CV);
            $this->Rect($x, $y, $w, 2, 'F');
            $this->SetXY($x + 2, $y + 3.5);
            $this->SetFont('Helvetica', 'B', 9.5);
            $this->SetTextColor(...$this->CGO);
            $this->Cell($w - 4, 5, u($t['valor']), 0, 0, 'C');
            $this->SetXY($x + 2, $y + 9.5);
            $this->SetFont('Helvetica', '', 6);
            $this->SetTextColor(...$this->CGM);
            $this->Cell($w - 4, 3, u($t['label']), 0, 0, 'C');
            if (isset($t['variacion']) && $t['variacion'] !== null) {
                $v = $t['variacion'];
                $color = $v >= 0 ? $this->CV : $this->CRO;
                $this->SetXY($x + 2, $y + 14);
                $this->SetFont('Helvetica', 'B', 5.5);
                $this->SetTextColor(...$color);
                $this->Cell($w - 4, 3, u(($v >= 0 ? '+' : '') . $v . '% vs periodo ant.'), 0, 0, 'C');
            }
        }
        $this->SetY($y + $h + 2);
    }

    function filaTarjetas5($tarjetas) {
        // 5 cards in one row: usable width = 182mm, each card = 36mm
        $this->Ln(1);
        $xBase = 14; $w = 36.4; $h = 20; $y = $this->GetY();
        foreach ($tarjetas as $i => $t) {
            $x = $xBase + $i * $w;
            $this->SetFillColor(210, 210, 210);
            $this->Rect($x + 0.6, $y + 0.6, $w, $h, 'F');
            $this->SetFillColor(...$this->CB);
            $this->SetDrawColor(225, 225, 225);
            $this->SetLineWidth(0.15);
            $this->Rect($x, $y, $w, $h, 'FD');
            $this->SetFillColor(...$this->CV);
            $this->Rect($x, $y, $w, 2, 'F');
            $this->SetXY($x + 2, $y + 3.5);
            $this->SetFont('Helvetica', 'B', 8.5);
            $this->SetTextColor(...$this->CGO);
            $this->Cell($w - 4, 5, u($t['valor']), 0, 0, 'C');
            $this->SetXY($x + 2, $y + 9.5);
            $this->SetFont('Helvetica', '', 5.5);
            $this->SetTextColor(...$this->CGM);
            $this->Cell($w - 4, 3, u($t['label']), 0, 0, 'C');
            if (isset($t['variacion']) && $t['variacion'] !== null) {
                $v = $t['variacion'];
                $color = $v >= 0 ? $this->CV : $this->CRO;
                $this->SetXY($x + 2, $y + 14);
                $this->SetFont('Helvetica', 'B', 5);
                $this->SetTextColor(...$color);
                $this->Cell($w - 4, 3, u(($v >= 0 ? '+' : '') . $v . '% vs periodo ant.'), 0, 0, 'C');
            }
        }
        $this->SetY($y + $h + 2);
    }

    function cajaResumen($texto) {
        $y0 = $this->GetY(); $w = 188;
        $this->SetFont('Helvetica', '', 7);
        $anchoLinea = $w - 12;
        $palabras = explode(' ', u($texto));
        $nL = 1; $lineaActual = '';
        foreach ($palabras as $palabra) {
            $prueba = $lineaActual === '' ? $palabra : $lineaActual . ' ' . $palabra;
            if ($this->GetStringWidth($prueba) > $anchoLinea) {
                $nL++;
                $lineaActual = $palabra;
            } else {
                $lineaActual = $prueba;
            }
        }
        $ah = max(12, $nL * 3.6 + 7);
        $this->SetFillColor(...$this->CVB);
        $this->SetDrawColor(200, 220, 210);
        $this->SetLineWidth(0.15);
        $this->Rect(14, $y0, $w, $ah, 'FD');
        $this->SetFillColor(...$this->CV);
        $this->Rect(14, $y0, 2.5, $ah, 'F');
        $this->SetXY(20, $y0 + 2.5);
        $this->SetFont('Helvetica', 'B', 6.5);
        $this->SetTextColor(...$this->CV);
        $this->Cell(0, 3.5, u('RESUMEN EJECUTIVO'), 0, 1);
        $this->SetX(20);
        $this->SetFont('Helvetica', '', 7);
        $this->SetTextColor(...$this->CGO);
        $this->MultiCell($w - 12, 3.6, u($texto), 0, 'L');
        $this->SetY($y0 + $ah + 1.5);
    }

    function seccion($texto) {
        $this->Ln(1.5);
        $this->SetFillColor(...$this->CV);
        $this->SetTextColor(...$this->CB);
        $this->SetFont('Helvetica', 'B', 7.5);
        $this->Cell(0, 6, u('   ' . strtoupper($texto)), 0, 1, 'L', true);
        $this->Ln(0.5);
    }

    function tabla($headers, $filas, $anchos, $alins = []) {
        if (empty($alins)) $alins = array_fill(0, count($headers), 'L');
        $this->SetFillColor(...$this->CV);
        $this->SetTextColor(...$this->CB);
        $this->SetFont('Helvetica', 'B', 6.5);
        $this->SetDrawColor(200, 200, 200);
        $this->SetLineWidth(0.1);
        foreach ($headers as $i => $h) {
            $this->Cell($anchos[$i], 6, u($h), 1, 0, 'C', true);
        }
        $this->Ln();
        $this->SetTextColor(...$this->CGO);
        $this->SetFont('Helvetica', '', 6.5);
        foreach ($filas as $idx => $fila) {
            if ($this->GetY() + 5.5 > 260) {
                $this->AddPage();
                $this->SetFillColor(...$this->CV);
                $this->SetTextColor(...$this->CB);
                $this->SetFont('Helvetica', 'B', 6.5);
                foreach ($headers as $i => $h) {
                    $this->Cell($anchos[$i], 6, u($h), 1, 0, 'C', true);
                }
                $this->Ln();
                $this->SetTextColor(...$this->CGO);
                $this->SetFont('Helvetica', '', 6.5);
            }
            $esTotal = (isset($fila[0]) && strtoupper(trim($fila[0])) === 'TOTAL');
            if ($esTotal) {
                $this->SetFillColor(228, 228, 228);
                $this->SetFont('Helvetica', 'B', 6.5);
            } else {
                $bg = ($idx % 2 === 0) ? 255 : 247;
                $this->SetFillColor($bg, $bg, $bg);
            }
            foreach ($fila as $i => $v) {
                $this->Cell($anchos[$i], 5.5, u((string)$v), 1, 0, $alins[$i] ?? 'L', true);
            }
            $this->Ln();
            if ($esTotal) $this->SetFont('Helvetica', '', 6.5);
        }
        $this->Ln(1);
    }

    function graficaBarras($valores, $etiquetas, $titulo,
                            $xO = 14, $yO = null, $anchoT = 182, $altoA = 38) {
        if (empty($valores) || max($valores) == 0) return;
        if ($yO === null) $yO = $this->GetY();
        $n = count($valores); $maxV = max($valores);
        $pIzq = 16; $pDer = 4; $pSup = 9; $pInf = 11;
        $aGraf = $anchoT - $pIzq - $pDer;
        $xEje  = $xO + $pIzq;
        $yBase = $yO + $pSup + $altoA;
        $colores = [$this->CV, $this->CAZ, $this->CTQ, $this->CMO,
                    $this->CAM, $this->CRO, $this->CVL, [155, 89, 182]];
        $this->SetFillColor(252, 252, 252);
        $this->SetDrawColor(220, 220, 220);
        $this->SetLineWidth(0.1);
        $this->Rect($xO, $yO, $anchoT, $pSup + $altoA + $pInf, 'FD');
        if ($titulo) {
            $this->SetXY($xO + 2, $yO + 2);
            $this->SetFont('Helvetica', 'B', 6.5);
            $this->SetTextColor(...$this->CGO);
            $this->Cell($anchoT - 4, 4, u($titulo), 0, 1, 'L');
        }
        $this->SetLineWidth(0.08);
        for ($i = 1; $i <= 4; $i++) {
            $yG = $yBase - ($altoA * $i / 4);
            $this->SetDrawColor(225, 225, 225);
            $this->Line($xEje, $yG, $xEje + $aGraf - $pDer, $yG);
            $this->SetXY($xO + 1, $yG - 1.5);
            $this->SetFont('Helvetica', '', 4.5);
            $this->SetTextColor(...$this->CGM);
            $this->Cell($pIzq - 3, 3, round($maxV * $i / 4), 0, 0, 'R');
        }
        $this->SetDrawColor(...$this->CGM);
        $this->SetLineWidth(0.3);
        $this->Line($xEje, $yO + $pSup, $xEje, $yBase);
        $this->Line($xEje, $yBase, $xEje + $aGraf - $pDer, $yBase);
        $esp    = 3;
        $aDisp  = $aGraf - $pDer - ($n - 1) * $esp;
        $aBarra = max(5, $aDisp / $n);
        $totalW = $n * $aBarra + ($n - 1) * $esp;
        $xIni   = $xEje + ($aGraf - $pDer - $totalW) / 2;
        for ($i = 0; $i < $n; $i++) {
            $val   = $valores[$i];
            $xB    = $xIni + $i * ($aBarra + $esp);
            $color = $colores[$i % count($colores)];
            $etiq  = strlen($etiquetas[$i]) > 7 ? substr($etiquetas[$i], 0, 6) . '.' : $etiquetas[$i];
            if ($val == 0) {
                $this->SetXY($xB - 1, $yBase + 2);
                $this->SetFont('Helvetica', '', 4.5);
                $this->SetTextColor(...$this->CGM);
                $this->Cell($aBarra + 2, 3, u($etiq), 0, 0, 'C');
                continue;
            }
            $altB = ($val / $maxV) * $altoA;
            $yB   = $yBase - $altB;
            $this->SetFillColor(...$color);
            $this->SetDrawColor(...$color);
            $this->SetLineWidth(0.1);
            $this->Rect($xB, $yB, $aBarra, $altB, 'F');
            $this->SetXY($xB - 2, $yB - 4.5);
            $this->SetFont('Helvetica', 'B', 5);
            $this->SetTextColor(...$color);
            $this->Cell($aBarra + 4, 3.5, $val, 0, 0, 'C');
            $this->SetXY($xB - 2, $yBase + 2);
            $this->SetFont('Helvetica', '', 4.5);
            $this->SetTextColor(...$this->CGM);
            $this->Cell($aBarra + 4, 3, u($etiq), 0, 0, 'C');
        }
        $this->SetY($yO + $pSup + $altoA + $pInf + 1.5);
        $this->SetTextColor(0, 0, 0);
    }

    function graficaTorta($valores, $etiquetas, $titulo,
                           $xO = 14, $yO = null, $radio = 18, $anchoBloque = 88) {
        if (empty($valores) || array_sum($valores) == 0) return;
        if ($yO === null) $yO = $this->GetY();

        $colores = [$this->CV, $this->CAZ, $this->CTQ, $this->CMO,
                    $this->CAM, $this->CRO, $this->CVL, [155, 89, 182]];
        $total = array_sum($valores);

        // Calcular items no cero para dimensionar la leyenda
        $itemsLeyenda = count(array_filter($valores, fn($v) => $v > 0));
        // Alto minimo: radio*2 + titulo + padding. Leyenda: cada item 6.5mm + padding inicial
        $altoCirculo  = $radio * 2 + 16;
        $altoLeyenda  = $itemsLeyenda * 6.5 + 10;
        $altoBloque   = max($altoCirculo, $altoLeyenda);

        $this->SetFillColor(252, 252, 252);
        $this->SetDrawColor(220, 220, 220);
        $this->SetLineWidth(0.1);
        $this->Rect($xO, $yO, $anchoBloque, $altoBloque, 'FD');
        $this->SetXY($xO + 2, $yO + 2);
        $this->SetFont('Helvetica', 'B', 6.5);
        $this->SetTextColor(...$this->CGO);
        $this->Cell($anchoBloque - 4, 4, u($titulo), 0, 1, 'L');
        $xC = $xO + $radio + 5;
        $yC = $yO + 8 + $radio;
        $ang = 0;
        foreach ($valores as $i => $v) {
            if ($v == 0) continue;
            $por = ($v / $total) * 360;
            $this->SetFillColor(...$colores[$i % count($colores)]);
            $this->_sector($xC, $yC, $radio, $ang, $ang + $por);
            $ang += $por;
        }
        $this->SetDrawColor(255, 255, 255);
        $this->SetLineWidth(0.4);
        $ang = 0;
        foreach ($valores as $i => $v) {
            if ($v == 0) continue;
            $por = ($v / $total) * 360;
            $this->_sector($xC, $yC, $radio, $ang, $ang + $por, 'D');
            $ang += $por;
        }

        // Leyenda: centrada verticalmente respecto al alto del bloque
        $xL = $xC + $radio + 5;
        $altoTotalLeyenda = $itemsLeyenda * 6.5;
        $yL = $yO + ($altoBloque - $altoTotalLeyenda) / 2;
        $anchoLey = $xO + $anchoBloque - $xL - 3;

        $idx = 0;
        foreach ($valores as $i => $v) {
            if ($v == 0) continue;
            $c    = $colores[$i % count($colores)];
            $porc = round(($v / $total) * 100, 1);
            $yLi  = $yL + ($idx * 6.5);
            $this->SetFillColor(...$c);
            $this->Rect($xL, $yLi + 0.5, 3, 3, 'F');
            $this->SetXY($xL + 4.5, $yLi);
            $this->SetFont('Helvetica', '', 5.5);
            $this->SetTextColor(...$this->CGO);
            $etiq = strlen($etiquetas[$i]) > 20 ? substr($etiquetas[$i], 0, 19) . '.' : $etiquetas[$i];
            $this->Cell($anchoLey, 3.5, u($etiq), 0, 1, 'L');
            $this->SetXY($xL + 4.5, $yLi + 3.5);
            $this->SetFont('Helvetica', 'B', 5.5);
            $this->SetTextColor(...$c);
            $this->Cell($anchoLey, 3, u($porc . '%'), 0, 1, 'L');
            $idx++;
        }

        $this->SetY($yO + $altoBloque + 2);
        $this->SetTextColor(0, 0, 0);
    }

    function _sector($xc, $yc, $r, $aI, $aF, $style = 'F') {
        $k = $this->k; $h = $this->h;
        $cx = $xc * $k; $cy = ($h - $yc) * $k; $rk = $r * $k;
        $aIr = deg2rad($aI - 90); $aFr = deg2rad($aF - 90);
        if ($aFr < $aIr) $aFr += 2 * M_PI;
        $this->_out(sprintf('%.2f %.2f m', $cx, $cy));
        $this->_out(sprintf('%.2f %.2f l', $cx + $rk * cos($aIr), $cy + $rk * sin($aIr)));
        $paso = deg2rad(5);
        for ($a = $aIr + $paso; $a < $aFr; $a += $paso) {
            $this->_out(sprintf('%.2f %.2f l', $cx + $rk * cos($a), $cy + $rk * sin($a)));
        }
        $this->_out(sprintf('%.2f %.2f l', $cx + $rk * cos($aFr), $cy + $rk * sin($aFr)));
        $this->_out('h');
        if ($style === 'F' || $style === 'FD') $this->_out('f');
        if ($style === 'D' || $style === 'FD') $this->_out('s');
    }

    static function calcVar($nuevo, $viejo) {
        if ($viejo == 0) return null;
        return round((($nuevo - $viejo) / $viejo) * 100, 1);
    }
}

// ================================================================
//  REPORTES
// ================================================================
try {
    $pdf = new ReporteEmpresarial();
    $pdf->AliasNbPages();
    $pdf->AddPage();

    $usuario     = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Sistema';
    $fechaActual = date('d/m/Y');
    $horaActual  = date('H:i:s');

    // Helper variacion para tablas
    $fV = fn($a, $b) => $b == 0 ? '--'
        : ((($v = round((($a - $b) / $b) * 100, 1)) >= 0 ? '+' : '') . $v . '%');

    // FIX: Mover fVFix al scope global para que ambos reportes lo usen
    $fVFix = function($actual, $anterior) {
        if ($anterior == 0) return $actual == 0 ? '0%' : '+100%';
        $v = round((($actual - $anterior) / $anterior) * 100, 1);
        return ($v >= 0 ? '+' : '') . $v . '%';
    };

    // ============================================================
    //  VENTAS: INFORME MENSUAL
    // ============================================================
    if ($module === 'ventas' && $report_type === 'ingresos_mensual') {

        $mes = $report_month ?: date('Y-m');
        $mesDT = DateTime::createFromFormat('Y-m', $mes);
        if (!$mesDT) {
            $mesDT = new DateTime();
            $mes = $mesDT->format('Y-m');
        }
        $nomMes = $mesDT->format('F Y');
        $mesAntDT = (clone $mesDT)->modify('-1 month');
        $mesAnt = $mesAntDT->format('Y-m');
        $nomMesAnt = $mesAntDT->format('F Y');

        $tasa = getTasaReporte();

        // Consulta del mes actual - FIX: usar tasa actual si tasa_cambio es NULL
        $stmt = $conn->prepare(
            "SELECT COALESCE(COUNT(*),0)                                        AS total_ventas,
                    COALESCE(SUM(COALESCE(monto_bs, total * COALESCE(NULLIF(tasa_cambio,0), :tasa1))),0)                                      AS total_ingresos,
                    COALESCE(AVG(COALESCE(monto_bs, total * COALESCE(NULLIF(tasa_cambio,0), :tasa2))),0)                                      AS ticket_promedio,
                    COALESCE(COUNT(DISTINCT cliente_id),0)                      AS clientes_unicos,
                    COALESCE(SUM(CASE WHEN metodo_pago_id=1 THEN COALESCE(monto_bs, total * COALESCE(NULLIF(tasa_cambio,0), :tasa3)) END),0)  AS efectivo,
                    COALESCE(SUM(CASE WHEN metodo_pago_id=2 THEN COALESCE(monto_bs, total * COALESCE(NULLIF(tasa_cambio,0), :tasa4)) END),0)  AS transferencia,
                    COALESCE(SUM(CASE WHEN metodo_pago_id=3 THEN COALESCE(monto_bs, total * COALESCE(NULLIF(tasa_cambio,0), :tasa5)) END),0)  AS pago_movil
             FROM ventas
             WHERE TO_CHAR(created_at,'YYYY-MM')=:mes AND estado_venta='COMPLETADA'");
        $stmt->execute([':mes' => $mes, ':tasa1' => $tasa, ':tasa2' => $tasa, ':tasa3' => $tasa, ':tasa4' => $tasa, ':tasa5' => $tasa]);
        $s = $stmt->fetch(PDO::FETCH_ASSOC);

        // Consulta del mes anterior - FIX: usar tasa actual si tasa_cambio es NULL
        $stmtA = $conn->prepare(
            "SELECT COALESCE(SUM(COALESCE(monto_bs, total * COALESCE(NULLIF(tasa_cambio,0), :tasa))),0) AS ingresos, COALESCE(COUNT(*),0) AS ventas,
                    COALESCE(AVG(COALESCE(monto_bs, total * COALESCE(NULLIF(tasa_cambio,0), :tasa2))),0) AS ticket, COALESCE(COUNT(DISTINCT cliente_id),0) AS clientes
             FROM ventas WHERE TO_CHAR(created_at,'YYYY-MM')=:m AND estado_venta='COMPLETADA'");
        $stmtA->execute([':m' => $mesAnt, ':tasa' => $tasa, ':tasa2' => $tasa]);
        $sa = $stmtA->fetch(PDO::FETCH_ASSOC);

        $varIngr   = ReporteEmpresarial::calcVar($s['total_ingresos'],  $sa['ingresos']);
        $varVentas = ReporteEmpresarial::calcVar($s['total_ventas'],    $sa['ventas']);
        $varTicket = ReporteEmpresarial::calcVar($s['ticket_promedio'], $sa['ticket']);
        $varCli    = ReporteEmpresarial::calcVar($s['clientes_unicos'], $sa['clientes']);

        $pdf->tituloPagina('Informe de Ingresos Mensual',
            'Periodo: ' . $nomMes . '   |   ' . $usuario . '   |   ' . $fechaActual . ' ' . $horaActual);

        $pdf->filaTarjetas([
            ['valor' => 'Bs ' . number_format($s['total_ingresos'],  2), 'label' => 'Ingresos del Mes',   'variacion' => $varIngr],
            ['valor' => (int)$s['total_ventas'],                         'label' => 'Ventas Realizadas',  'variacion' => $varVentas],
            ['valor' => 'Bs ' . number_format($s['ticket_promedio'], 2), 'label' => 'Ticket Promedio',    'variacion' => $varTicket],
            ['valor' => (int)$s['clientes_unicos'],                      'label' => 'Clientes Atendidos', 'variacion' => $varCli],
        ]);

        $diff = $s['total_ingresos'] - $sa['ingresos'];
        $pStr = $varIngr !== null ? ' (' . ($varIngr >= 0 ? '+' : '') . $varIngr . '%)' : '';
        $resumen = 'En ' . $nomMes . ' se registraron ' . (int)$s['total_ventas']
                 . ' venta(s) por Bs ' . number_format($s['total_ingresos'], 2)
                 . '. Ticket promedio Bs ' . number_format($s['ticket_promedio'], 2)
                 . ', atendiendo ' . (int)$s['clientes_unicos'] . ' cliente(s).';
        if ($sa['ingresos'] > 0) {
            $resumen .= ' Comparado con ' . $nomMesAnt . ', los ingresos muestran '
                      . ($diff >= 0 ? 'un incremento' : 'una disminucion')
                      . ' de Bs ' . number_format(abs($diff), 2) . $pStr . '.';
        }
        $pdf->cajaResumen($resumen);

        $dPago = [(float)$s['efectivo'], (float)$s['transferencia'], (float)$s['pago_movil']];
        $ePago = ['Efectivo', 'Transferencia', 'Pago Movil'];

        // Ventas diarias del mes - convertir a Bs
        $stmtD = $conn->prepare(
            "SELECT TO_CHAR(created_at,'DD/MM') AS fecha, SUM(COALESCE(monto_bs, total * COALESCE(tasa_cambio, :tasa))) AS monto
             FROM ventas WHERE TO_CHAR(created_at,'YYYY-MM')=:mes AND estado_venta='COMPLETADA'
             GROUP BY TO_CHAR(created_at,'DD/MM') ORDER BY MIN(created_at)");
        $stmtD->execute([':mes' => $mes, ':tasa' => $tasa]);
        $diasR   = $stmtD->fetchAll(PDO::FETCH_ASSOC);
        $fechasD = array_column($diasR, 'fecha');
        $montosD = array_map(fn($r) => (int)round($r['monto']), $diasR);

        $yG = $pdf->GetY() + 1; $yP1 = $yG; $yP2 = $yG;
        if (array_sum($dPago) > 0) {
            $pdf->graficaTorta($dPago, $ePago, 'Metodos de Pago', 14, $yG, 18, 88);
            $yP1 = $pdf->GetY();
        }
        if (!empty($montosD)) {
            $pdf->graficaBarras($montosD, $fechasD, 'Ventas Diarias del Mes', 104, $yG, 92, 36);
            $yP2 = $pdf->GetY();
        }
        $pdf->SetY(max($yP1, $yP2));

        $pdf->seccion('Comparativo vs Mes Anterior');
        $pdf->tabla(
            ['Indicador', $nomMes, $nomMesAnt, 'Variacion'],
            [
                ['Ingresos Totales',   'Bs '.number_format($s['total_ingresos'],2),  'Bs '.number_format($sa['ingresos'],2),  $fVFix($s['total_ingresos'],  $sa['ingresos'])],
                ['Ventas Realizadas',  (int)$s['total_ventas'],                       (int)$sa['ventas'],                      $fVFix($s['total_ventas'],    $sa['ventas'])],
                ['Ticket Promedio',    'Bs '.number_format($s['ticket_promedio'],2),  'Bs '.number_format($sa['ticket'],2),    $fVFix($s['ticket_promedio'], $sa['ticket'])],
                ['Clientes Atendidos', (int)$s['clientes_unicos'],                    (int)$sa['clientes'],                    $fVFix($s['clientes_unicos'], $sa['clientes'])],
            ],
            [60, 42, 42, 38], ['L', 'R', 'R', 'C']
        );

        // Productos más demandados - FIX: usar NULLIF para tasa NULL o 0
        $stmtP = $conn->prepare(
            "SELECT p.nombre, SUM(dv.cantidad) AS unidades, SUM(dv.subtotal * COALESCE(NULLIF(v.tasa_cambio,0), :tasa)) AS ingresos
             FROM detalle_ventas dv
             JOIN productos p ON dv.producto_id=p.id
             JOIN ventas v    ON dv.venta_id=v.id
             WHERE TO_CHAR(v.created_at,'YYYY-MM')=:mes AND v.estado_venta='COMPLETADA'
             GROUP BY p.id, p.nombre ORDER BY ingresos DESC LIMIT 5");
        $stmtP->execute([':mes' => $mes, ':tasa' => $tasa]);
        $prods = $stmtP->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($prods)) {
            $pdf->seccion('Top 5 Productos del Mes');
            $fp = [];
            foreach ($prods as $p)
                $fp[] = [substr($p['nombre'],0,40),(int)$p['unidades'],'Bs '.number_format($p['ingresos'],2)];
            $pdf->tabla(['Producto','Unidades','Ingresos'],$fp,[112,30,40],['L','C','R']);
        }
    }

    // ============================================================
    //  VENTAS: INFORME DIARIO
    // ============================================================
    elseif ($module === 'ventas' && $report_type === 'ingresos_diario') {

        $hoy = $report_date ?: date('Y-m-d');
        $hoyDT = DateTime::createFromFormat('Y-m-d', $hoy);
        if (!$hoyDT) {
            $hoyDT = new DateTime();
            $hoy = $hoyDT->format('Y-m-d');
        }
        $ayerDT = (clone $hoyDT)->modify('-1 day');
        $ayer = $ayerDT->format('Y-m-d');

        $stmt = $conn->prepare(
            "SELECT COALESCE(COUNT(*),0)                                        AS total_ventas,
                    COALESCE(SUM(COALESCE(monto_bs, total * COALESCE(NULLIF(tasa_cambio,0), :tasa1))),0) AS total_ingresos,
                    COALESCE(AVG(COALESCE(monto_bs, total * COALESCE(NULLIF(tasa_cambio,0), :tasa2))),0) AS ticket_promedio,
                    COALESCE(COUNT(DISTINCT cliente_id),0)                      AS clientes_unicos,
                    COALESCE(SUM(CASE WHEN metodo_pago_id=1 THEN COALESCE(monto_bs, total * COALESCE(NULLIF(tasa_cambio,0), :tasa3)) END),0)  AS efectivo,
                    COALESCE(SUM(CASE WHEN metodo_pago_id=2 THEN COALESCE(monto_bs, total * COALESCE(NULLIF(tasa_cambio,0), :tasa4)) END),0)  AS transferencia,
                    COALESCE(SUM(CASE WHEN metodo_pago_id=3 THEN COALESCE(monto_bs, total * COALESCE(NULLIF(tasa_cambio,0), :tasa5)) END),0)  AS pago_movil
             FROM ventas WHERE DATE(created_at)=:hoy AND estado_venta='COMPLETADA'");
        $tasa = getTasaReporte();
        $stmt->execute([':hoy' => $hoy, ':tasa1' => $tasa, ':tasa2' => $tasa, ':tasa3' => $tasa, ':tasa4' => $tasa, ':tasa5' => $tasa]);
        $s = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmtAy = $conn->prepare(
            "SELECT COALESCE(SUM(COALESCE(monto_bs, total * COALESCE(NULLIF(tasa_cambio,0), :tasa))),0) AS ingresos, COALESCE(COUNT(*),0) AS ventas,
                    COALESCE(AVG(COALESCE(monto_bs, total * COALESCE(NULLIF(tasa_cambio,0), :tasa2))),0) AS ticket, COALESCE(COUNT(DISTINCT cliente_id),0) AS clientes
             FROM ventas WHERE DATE(created_at)=:ay AND estado_venta='COMPLETADA'");
        $stmtAy->execute([':ay' => $ayer, ':tasa' => $tasa, ':tasa2' => $tasa]);
        $ay = $stmtAy->fetch(PDO::FETCH_ASSOC);

        $varI = ReporteEmpresarial::calcVar($s['total_ingresos'],  $ay['ingresos']);
        $varV = ReporteEmpresarial::calcVar($s['total_ventas'],    $ay['ventas']);
        $varT = ReporteEmpresarial::calcVar($s['ticket_promedio'], $ay['ticket']);
        $varC = ReporteEmpresarial::calcVar($s['clientes_unicos'], $ay['clientes']);

        $fechaReporte = $hoyDT->format('d/m/Y');
        $pdf->tituloPagina('Informe de Ingresos Diario',
            'Fecha: ' . $fechaReporte . '   |   ' . $usuario . '   |   ' . $horaActual);

        $pdf->filaTarjetas([
            ['valor' => 'Bs ' . number_format($s['total_ingresos'],  2), 'label' => 'Ingresos del Dia',   'variacion' => $varI],
            ['valor' => (int)$s['total_ventas'],                         'label' => 'Ventas Realizadas',  'variacion' => $varV],
            ['valor' => 'Bs ' . number_format($s['ticket_promedio'], 2), 'label' => 'Ticket Promedio',    'variacion' => $varT],
            ['valor' => (int)$s['clientes_unicos'],                      'label' => 'Clientes Atendidos', 'variacion' => $varC],
        ]);

        $diff = $s['total_ingresos'] - $ay['ingresos'];
        $pStr = $varI !== null ? ' (' . ($varI >= 0 ? '+' : '') . $varI . '%)' : '';
        $resumen = 'En la fecha ' . $fechaReporte . ' se registraron ' . (int)$s['total_ventas']
                 . ' venta(s) por Bs ' . number_format($s['total_ingresos'], 2)
                 . '. Ticket promedio Bs ' . number_format($s['ticket_promedio'], 2)
                 . ', atendiendo ' . (int)$s['clientes_unicos'] . ' cliente(s).';
        if ($ay['ingresos'] > 0)
            $resumen .= ' Comparado con ayer, los ingresos muestran '
                      . ($diff >= 0 ? 'un incremento' : 'una disminucion')
                      . ' de Bs ' . number_format(abs($diff), 2) . $pStr . '.';
        $pdf->cajaResumen($resumen);

        $dPago = [(float)$s['efectivo'], (float)$s['transferencia'], (float)$s['pago_movil']];
        $ePago = ['Efectivo', 'Transferencia', 'Pago Movil'];

        $stmtH = $conn->prepare(
            "SELECT LPAD(EXTRACT(hour FROM created_at)::text,2,'0') || ':00' AS hora,
                    SUM(COALESCE(monto_bs, total * COALESCE(NULLIF(tasa_cambio,0), :tasa))) AS monto
             FROM ventas WHERE DATE(created_at)=:hoy AND estado_venta='COMPLETADA'
             GROUP BY EXTRACT(hour FROM created_at) ORDER BY 1");
        $stmtH->execute([':hoy' => $hoy, ':tasa' => $tasa]);
        $porH  = $stmtH->fetchAll(PDO::FETCH_ASSOC);
        $horas = array_column($porH, 'hora');
        $montH = array_map(fn($r) => (int)round($r['monto']), $porH);

        $yG = $pdf->GetY() + 1; $yP1 = $yG; $yP2 = $yG;
        if (array_sum($dPago) > 0) {
            $pdf->graficaTorta($dPago, $ePago, 'Metodos de Pago', 14, $yG, 18, 88);
            $yP1 = $pdf->GetY();
        }
        if (!empty($montH)) {
            $pdf->graficaBarras($montH, $horas, 'Ingresos por Hora', 104, $yG, 92, 36);
            $yP2 = $pdf->GetY();
        }
        $pdf->SetY(max($yP1, $yP2));

        $pdf->seccion('Comparativo vs Ayer');
        $pdf->tabla(
            ['Indicador', 'Hoy ' . $fechaActual, 'Ayer ' . date('d/m/Y', strtotime('-1 day')), 'Variacion'],
            [
                ['Ingresos Totales',   'Bs '.number_format($s['total_ingresos'],2),  'Bs '.number_format($ay['ingresos'],2),  $fVFix($s['total_ingresos'],  $ay['ingresos'])],
                ['Ventas Realizadas',  (int)$s['total_ventas'],                       (int)$ay['ventas'],                      $fVFix($s['total_ventas'],    $ay['ventas'])],
                ['Ticket Promedio',    'Bs '.number_format($s['ticket_promedio'],2),  'Bs '.number_format($ay['ticket'],2),    $fVFix($s['ticket_promedio'], $ay['ticket'])],
                ['Clientes Atendidos', (int)$s['clientes_unicos'],                    (int)$ay['clientes'],                    $fVFix($s['clientes_unicos'], $ay['clientes'])],
            ],
            [58, 42, 42, 40], ['L', 'R', 'R', 'C']
        );

        $stmtV = $conn->prepare(
            "SELECT TO_CHAR(v.created_at,'HH24:MI') AS hora,
                    COALESCE(c.nombre_completo,'General') AS cliente,
                    COALESCE(mp.nombre,'Sin metodo') AS metodo,
                    COALESCE(v.monto_bs, v.total * COALESCE(NULLIF(v.tasa_cambio,0), :tasa)) AS monto
             FROM ventas v
             LEFT JOIN clientes c      ON v.cliente_id=c.id
             LEFT JOIN metodos_pago mp ON v.metodo_pago_id=mp.id
             WHERE DATE(v.created_at)=:hoy AND v.estado_venta='COMPLETADA'
             ORDER BY v.created_at DESC");
        $stmtV->execute([':hoy' => $hoy, ':tasa' => $tasa]);
        $vDia = $stmtV->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($vDia)) {
            $pdf->seccion('Detalle de Ventas del Dia');
            $fv = []; $totalDia = 0;
            foreach ($vDia as $v) {
                $fv[] = [$v['hora'], substr($v['cliente'],0,28), $v['metodo'], 'Bs '.number_format($v['monto'],2)];
                $totalDia += $v['monto'];
            }
            $fv[] = ['TOTAL', '', '', 'Bs '.number_format($totalDia,2)];
            $pdf->tabla(['Hora','Cliente','Metodo','Monto'],$fv,[18,76,44,44],['C','L','L','R']);
        }

        $stmtPD = $conn->prepare(
            "SELECT p.nombre, SUM(dv.cantidad) AS unidades,
                    SUM(dv.subtotal * COALESCE(v.tasa_cambio, :tasa)) AS ingresos
             FROM detalle_ventas dv
             JOIN productos p ON dv.producto_id=p.id
             JOIN ventas v    ON dv.venta_id=v.id
             WHERE DATE(v.created_at)=:hoy AND v.estado_venta='COMPLETADA'
             GROUP BY p.id, p.nombre ORDER BY ingresos DESC LIMIT 5");
        $stmtPD->execute([':hoy' => $hoy, ':tasa' => $tasa]);
        $pDia = $stmtPD->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($pDia)) {
            $pdf->seccion('Top 5 Productos del Dia');
            $fpd = [];
            foreach ($pDia as $p)
                $fpd[] = [substr($p['nombre'],0,42),(int)$p['unidades'],'Bs '.number_format($p['ingresos'],2)];
            $pdf->tabla(['Producto','Unidades','Ingresos'],$fpd,[112,30,40],['L','C','R']);
        }
    }

    // ============================================================
    //  VENTAS: HISTORIAL DE CLIENTES
    // ============================================================
    elseif ($module === 'ventas' && $report_type === 'historial_clientes') {

        $stmtSt = $conn->prepare(
            "SELECT COUNT(*) AS total,
                    COUNT(CASE WHEN id IN (SELECT DISTINCT cliente_id FROM ventas WHERE estado_venta='COMPLETADA') THEN 1 END) AS activos,
                    COUNT(CASE WHEN id NOT IN (SELECT DISTINCT cliente_id FROM ventas WHERE estado_venta='COMPLETADA') THEN 1 END) AS inactivos,
                    COUNT(CASE WHEN created_at >= CURRENT_DATE-INTERVAL '30 days' THEN 1 END) AS nuevos_30,
                    COUNT(CASE WHEN created_at >= CURRENT_DATE-INTERVAL '7 days' THEN 1 END)  AS nuevos_7
             FROM clientes");
        $stmtSt->execute();
        $sc = $stmtSt->fetch(PDO::FETCH_ASSOC);

        $stmtTk = $conn->prepare("SELECT COALESCE(AVG(total),0) AS ticket FROM ventas WHERE estado_venta='COMPLETADA'");
        $stmtTk->execute();
        $tk = $stmtTk->fetch(PDO::FETCH_ASSOC);
        $tasaAct = $sc['total'] > 0 ? round(($sc['activos'] / $sc['total']) * 100, 1) : 0;

        $pdf->tituloPagina('Historial de Clientes',
            'Corte: ' . $fechaActual . '   |   ' . $usuario . '   |   ' . $horaActual);

        $pdf->filaTarjetas([
            ['valor' => (int)$sc['total'],                            'label' => 'Total Clientes'],
            ['valor' => (int)$sc['activos'] . ' (' . $tasaAct . '%)', 'label' => 'Clientes Activos'],
            ['valor' => (int)$sc['inactivos'],                        'label' => 'Sin Compras'],
            ['valor' => (int)$sc['nuevos_30'],                        'label' => 'Nuevos (30 dias)'],
        ]);

        $resumen = 'La base contiene ' . (int)$sc['total'] . ' clientes. '
                 . (int)$sc['activos'] . ' (' . $tasaAct . '%) han comprado, '
                 . (int)$sc['inactivos'] . ' sin compras. '
                 . 'Nuevos ultimos 30 dias: ' . (int)$sc['nuevos_30']
                 . ', ultimos 7 dias: ' . (int)$sc['nuevos_7'] . '. '
                 . 'Ticket promedio: Bs ' . number_format($tk['ticket'], 2) . '.';
        $pdf->cajaResumen($resumen);

        $stmtSem = $conn->prepare(
            "SELECT TO_CHAR(DATE_TRUNC('week', created_at),'DD/MM') AS semana, COUNT(*) AS cantidad
             FROM clientes WHERE created_at >= CURRENT_DATE - INTERVAL '28 days'
             GROUP BY DATE_TRUNC('week', created_at) ORDER BY 1");
        $stmtSem->execute();
        $semanas = $stmtSem->fetchAll(PDO::FETCH_ASSOC);

        $yG = $pdf->GetY() + 1; $yP1 = $yG; $yP2 = $yG;
        if ($sc['activos'] > 0 || $sc['inactivos'] > 0) {
            $pdf->graficaTorta([(int)$sc['activos'],(int)$sc['inactivos']],
                ['Activos','Sin compras'],'Activos vs Sin Compras',14,$yG,18,88);
            $yP1 = $pdf->GetY();
        }
        if (!empty($semanas)) {
            $pdf->graficaBarras(
                array_map('intval', array_column($semanas,'cantidad')),
                array_column($semanas,'semana'),
                'Nuevos Clientes (28 dias)', 104, $yG, 92, 36);
            $yP2 = $pdf->GetY();
        }
        $pdf->SetY(max($yP1, $yP2));

        $tasa = getTasaReporte();

        $stmtTop = $conn->prepare(
            "SELECT c.nombre_completo, c.cedula_rif AS cedula, c.telefono_principal AS telefono,
                    COUNT(v.id) AS compras, 
                    COALESCE(SUM(COALESCE(v.monto_bs, v.total * COALESCE(v.tasa_cambio, :tasa1))),0) AS gastado,
                    COALESCE(AVG(COALESCE(v.monto_bs, v.total * COALESCE(v.tasa_cambio, :tasa2))),0) AS promedio, 
                    MAX(v.created_at) AS ultima
             FROM clientes c
             JOIN ventas v ON c.id=v.cliente_id AND v.estado_venta='COMPLETADA'
             GROUP BY c.id, c.nombre_completo, c.cedula_rif, c.telefono_principal
             ORDER BY gastado DESC LIMIT 10");
        $stmtTop->execute([':tasa1' => $tasa, ':tasa2' => $tasa]);
        $topC = $stmtTop->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($topC)) {
            $pdf->seccion('Top 10 Clientes por Monto Gastado');
            $ft = [];
            foreach ($topC as $tc) {
                $uc = $tc['ultima'] ? date('d/m/Y', strtotime($tc['ultima'])) : '-';
                $ft[] = [substr($tc['nombre_completo'],0,22),$tc['cedula']??'-',$tc['telefono']??'-',
                         (int)$tc['compras'],'Bs '.number_format($tc['gastado'],2),
                         'Bs '.number_format($tc['promedio'],2),$uc];
            }
            $pdf->tabla(['Nombre','Cedula','Telefono','Compras','Total','Promedio','Ult. Compra'],
                $ft,[44,22,26,16,30,28,16],['L','C','C','C','R','R','C']);
        }

        $stmtL = $conn->prepare(
            "SELECT c.nombre_completo, c.cedula_rif AS cedula, c.telefono_principal AS telefono,
                    TO_CHAR(c.created_at,'DD/MM/YY') AS registro,
                    COUNT(v.id) AS compras, 
                    COALESCE(SUM(COALESCE(v.monto_bs, v.total * COALESCE(v.tasa_cambio, :tasa))),0) AS gastado, 
                    MAX(v.created_at) AS ultima
             FROM clientes c
             LEFT JOIN ventas v ON c.id=v.cliente_id AND v.estado_venta='COMPLETADA'
             GROUP BY c.id, c.nombre_completo, c.cedula_rif, c.telefono_principal, c.created_at
             ORDER BY gastado DESC, compras DESC");
        $stmtL->execute([':tasa' => $tasa]);
        $listaC = $stmtL->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($listaC)) {
            $pdf->seccion('Listado Completo de Clientes');
            $fl = [];
            foreach ($listaC as $c) {
                $uc = $c['ultima'] ? date('d/m/Y', strtotime($c['ultima'])) : '-';
                $fl[] = [substr($c['nombre_completo'],0,26),$c['cedula']??'-',$c['telefono']??'-',
                         $c['registro'],(int)$c['compras'],'Bs '.number_format($c['gastado'],2),$uc];
            }
            $pdf->tabla(['Nombre','Cedula','Telefono','Registro','Compras','Total Gastado','Ult. Compra'],
                $fl,[46,22,26,18,16,34,20],['L','C','C','C','C','R','C']);
        }
    }

    // ============================================================
    //  INVENTARIO: PRODUCTOS MAS DEMANDADOS
    // ============================================================
    elseif ($module === 'inventario' && $report_type === 'productos_demandados') {

        $tasa = getTasaReporte();

        // Estadísticas generales mejoradas
        $stmtStats = $conn->prepare(
            "SELECT COUNT(DISTINCT p.id) AS total_activos,
                    COUNT(DISTINCT dv.producto_id) AS con_ventas,
                    COALESCE(SUM(dv.cantidad),0) AS total_unidades,
                    COALESCE(SUM(dv.subtotal * COALESCE(v.tasa_cambio, :tasa1)),0) AS total_ingresos,
                    COUNT(DISTINCT v.id) AS total_transacciones
             FROM productos p
             LEFT JOIN detalle_ventas dv ON p.id=dv.producto_id
             LEFT JOIN ventas v ON dv.venta_id=v.id AND v.estado_venta='COMPLETADA'
             WHERE p.estado=true");
        $stmtStats->execute([':tasa1' => $tasa]);
        $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

        // Top productos con más métricas
        $stmtTop = $conn->prepare(
            "SELECT p.nombre,
                    p.codigo_interno,
                    COALESCE(p.precio_venta_bs, p.precio_venta * :tasa2) AS precio_venta,
                    COALESCE(p.precio_compra_bs, p.precio_compra * :tasa3) AS precio_compra,
                    SUM(dv.cantidad) AS unidades,
                    SUM(dv.subtotal * COALESCE(v.tasa_cambio, :tasa4)) AS ingresos,
                    COUNT(DISTINCT v.id) AS num_transacciones,
                    CASE WHEN p.precio_venta > 0
                         THEN ROUND(((p.precio_venta - COALESCE(p.precio_compra,0)) / p.precio_venta)*100, 1)
                         ELSE 0 END AS margen
             FROM detalle_ventas dv
             JOIN productos p ON dv.producto_id=p.id
             JOIN ventas v ON dv.venta_id=v.id
             WHERE v.estado_venta='COMPLETADA' AND p.estado=true
             GROUP BY p.id, p.nombre, p.codigo_interno, p.precio_venta, p.precio_compra, p.precio_venta_bs, p.precio_compra_bs
             ORDER BY unidades DESC LIMIT 10");
        $stmtTop->execute([':tasa2' => $tasa, ':tasa3' => $tasa, ':tasa4' => $tasa]);
        $productos = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

        // Calcular métricas adicionales
        $sinVentas = $stats['total_activos'] - $stats['con_ventas'];
        $pctConVentas = $stats['total_activos'] > 0 ? round(($stats['con_ventas'] / $stats['total_activos']) * 100, 1) : 0;
        $promedioUnidades = $stats['con_ventas'] > 0 ? round($stats['total_unidades'] / $stats['con_ventas'], 1) : 0;
        $promedioIngreso = $stats['con_ventas'] > 0 ? round($stats['total_ingresos'] / $stats['con_ventas'], 2) : 0;
        $ticketPromedio = $stats['total_transacciones'] > 0 ? round($stats['total_ingresos'] / $stats['total_transacciones'], 2) : 0;

        // Calcular ingresos del top 10 para análisis
        $top10 = array_slice($productos, 0, 10);
        $ingresosTop10 = array_sum(array_column($top10, 'ingresos'));
        $pctTop10 = $stats['total_ingresos'] > 0 ? round(($ingresosTop10 / $stats['total_ingresos']) * 100, 1) : 0;

        // Calcular ganancia estimada total
        $gananciaTotal = 0;
        foreach ($productos as $p) {
            $gananciaUnit = $p['precio_venta'] - $p['precio_compra'];
            $gananciaTotal += $gananciaUnit * $p['unidades'];
        }

        $pdf->tituloPagina('Productos Mas Demandados',
            'Corte: ' . $fechaActual . '   |   ' . $usuario . '   |   ' . $horaActual);

        // Tarjetas de resumen (4 tarjetas)
        $pdf->filaTarjetas([
            ['valor' => (int)$stats['con_ventas'],                               'label' => 'Productos con Ventas'],
            ['valor' => number_format($stats['total_unidades']),                  'label' => 'Unidades Vendidas'],
            ['valor' => 'Bs ' . number_format($stats['total_ingresos'], 2),      'label' => 'Ingresos Totales'],
            ['valor' => number_format($stats['total_transacciones']),             'label' => 'Transacciones'],
        ]);

        // Resumen ejecutivo mejorado (conciso y directo)
        $resumen = 'De ' . (int)$stats['total_activos'] . ' productos activos, ' . (int)$stats['con_ventas'] . ' (' . $pctConVentas . '%) tienen ventas registradas, '
                 . 'mientras que ' . $sinVentas . ' aún no tienen movimientos. '
                 . 'Se comercializaron ' . number_format($stats['total_unidades']) . ' unidades en ' . number_format($stats['total_transacciones']) . ' transacciones, '
                 . 'generando Bs ' . number_format($stats['total_ingresos'], 2) . ' en ingresos, con un ticket promedio de Bs ' . number_format($ticketPromedio, 2) . '. '
                 . 'La ganancia bruta estimada total es de Bs ' . number_format($gananciaTotal, 2) . '. '
                 . 'El Top 10 concentra el ' . $pctTop10 . '% de los ingresos, lo que indica una concentración de demanda en productos clave.';
        $pdf->cajaResumen($resumen);

        if (!empty($productos)) {
            $pdf->seccion('Top 10 Productos Mas Demandados');
            $fp = [];
            foreach ($productos as $p) {
                $pctIngreso = $stats['total_ingresos'] > 0 ? round(($p['ingresos'] / $stats['total_ingresos']) * 100, 1) : 0;
                $fp[] = [
                    substr($p['nombre'],0,28),
                    (int)$p['unidades'],
                    'Bs '.number_format($p['ingresos'],2),
                    'Bs '.number_format($p['precio_venta'],2),
                    $p['margen'].'%',
                    $pctIngreso.'%'
                ];
            }
            $pdf->tabla(
                ['Producto', 'Unidades', 'Ingresos', 'Precio Vta.', 'Margen', '% del Total'],
                $fp,
                [56, 24, 38, 30, 18, 16],
                ['L', 'C', 'R', 'R', 'C', 'C']
            );
        }

        // Gráfica de barras - Top 10
        if (!empty($top10)) {
            $pdf->graficaBarras(
                array_map(fn($p) => (int)$p['unidades'], $top10),
                array_map(fn($p) => substr($p['nombre'],0,8), $top10),
                'Top 10 por Unidades Vendidas', 14, null, 182, 45
            );
        }

        // Análisis adicional: Distribución por tipo de producto (si hay datos)
        $stmtTipo = $conn->prepare(
            "SELECT COALESCE(tp.nombre, 'Sin Tipo') as tipo,
                    COUNT(DISTINCT p.id) as productos,
                    COALESCE(SUM(dv.cantidad),0) as unidades_vendidas,
                    COALESCE(SUM(dv.subtotal * COALESCE(v.tasa_cambio, :tasa)),0) as ingresos
             FROM productos p
             LEFT JOIN tipos_producto tp ON p.tipo_id = tp.id
             LEFT JOIN detalle_ventas dv ON p.id = dv.producto_id
             LEFT JOIN ventas v ON dv.venta_id = v.id AND v.estado_venta = 'COMPLETADA'
             WHERE p.estado = true
             GROUP BY tp.nombre
             ORDER BY ingresos DESC");
        $stmtTipo->execute([':tasa' => $tasa]);
        $tipos = $stmtTipo->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($tipos)) {
            $pdf->seccion('Ventas por Tipo de Producto');
            $ft = [];
            foreach ($tipos as $t) {
                $pctTipo = $stats['total_ingresos'] > 0 ? round(($t['ingresos'] / $stats['total_ingresos']) * 100, 1) : 0;
                $ft[] = [
                    $t['tipo'],
                    (int)$t['productos'],
                    number_format($t['unidades_vendidas']),
                    'Bs ' . number_format($t['ingresos'], 2),
                    $pctTipo . '%'
                ];
            }
            $pdf->tabla(
                ['Tipo de Producto', 'Productos', 'Unidades Vend.', 'Ingresos', '% del Total'],
                $ft,
                [52, 28, 32, 44, 26],
                ['L', 'C', 'C', 'R', 'C']
            );
        }
    }

// ============================================================
//  INVENTARIO: ESTADO DEL INVENTARIO
// ============================================================
elseif ($module === 'inventario' && $report_type === 'estado_inventario') {

    $tasa = getTasaReporte();

    // Consulta principal para estadísticas generales
    // FIX: Use precio_compra_bs if available, otherwise convert precio_compra * tasa
    $stmtInv = $conn->prepare(
        "SELECT COUNT(*) AS total,
                COUNT(CASE WHEN stock_actual <= stock_minimo THEN 1 END) AS bajo_stock,
                COUNT(CASE WHEN stock_actual = 0 THEN 1 END) AS sin_stock,
                COUNT(CASE WHEN stock_actual > stock_maximo THEN 1 END) AS sobre_stock,
                COALESCE(SUM(stock_actual * COALESCE(precio_compra_bs, precio_compra * :tasa1)), 0) AS inversion,
                COALESCE(SUM(stock_actual * COALESCE(precio_venta_bs, precio_venta * :tasa2)), 0) AS valor_venta
         FROM productos
         WHERE estado = true
           AND stock_actual > 0
           AND (precio_compra IS NOT NULL OR precio_compra_bs IS NOT NULL)
           AND (precio_compra > 0 OR precio_compra_bs > 0)");
    $stmtInv->execute([':tasa1' => $tasa, ':tasa2' => $tasa]);
    $inv = $stmtInv->fetch(PDO::FETCH_ASSOC);

    $margen = $inv['valor_venta'] - $inv['inversion'];
    $margen_porcentaje = $inv['inversion'] > 0 ? round(($margen / $inv['inversion']) * 100, 1) : 0;

    $pdf->tituloPagina('Estado del Inventario',
        'Corte: ' . $fechaActual . '   |   ' . $usuario . '   |   ' . $horaActual);

    // Tarjetas de resumen (5 en una fila) - FIX: ajustar ancho para que quepan
    $pdf->filaTarjetas5([
        ['valor' => (int)$inv['total'], 'label' => 'Total Productos'],
        ['valor' => (int)$inv['bajo_stock'], 'label' => 'Bajo Stock'],
        ['valor' => (int)$inv['sin_stock'], 'label' => 'Sin Stock'],
        ['valor' => 'Bs ' . number_format($inv['inversion'], 2), 'label' => 'Inversión en Inventario'],
        ['valor' => 'Bs ' . number_format($inv['valor_venta'], 2), 'label' => 'Valor de Venta Potencial'],
    ]);

    // Resumen ejecutivo
    $resumen = 'El inventario está compuesto por ' . (int)$inv['total'] . ' productos activos. '
             . 'La inversión total en inventario asciende a Bs ' . number_format($inv['inversion'], 2) . ', '
             . 'mientras que el valor potencial de venta alcanza los Bs ' . number_format($inv['valor_venta'], 2) . '. '
             . 'Esto representa una utilidad bruta potencial de Bs ' . number_format($margen, 2) . ', '
             . 'lo que equivale a un margen de ganancia del ' . $margen_porcentaje . '% sobre la inversión. '
             . 'Se reportan ' . (int)$inv['bajo_stock'] . ' productos con nivel de stock bajo '
             . 'y ' . (int)$inv['sin_stock'] . ' productos agotados.';
    $pdf->cajaResumen($resumen);

    // Gráfico de torta - Distribución por estado de stock
    $stmtCat = $conn->prepare(
        "SELECT CASE WHEN stock_actual = 0 THEN 'Sin Stock'
                     WHEN stock_actual <= stock_minimo THEN 'Bajo Stock'
                     WHEN stock_actual > stock_maximo THEN 'Sobre Stock'
                     ELSE 'Normal' END AS categoria,
                COUNT(*) AS cantidad
         FROM productos WHERE estado = true
         GROUP BY 1 ORDER BY cantidad DESC");
    $stmtCat->execute();
    $categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

    $yG = $pdf->GetY() + 1;
    if (!empty($categorias)) {
        $pdf->graficaTorta(
            array_map('intval', array_column($categorias, 'cantidad')),
            array_column($categorias, 'categoria'),
            'Distribución por Estado de Stock', 14, $yG, 18, 88
        );
    }

    // ============================================================
    // TABLA: RESUMEN POR CATEGORÍA DE STOCK (4 columnas)
    // ============================================================
    $stmtResumenCat = $conn->prepare(
        "SELECT CASE WHEN stock_actual = 0 THEN 'Sin Stock'
                     WHEN stock_actual <= stock_minimo THEN 'Bajo Stock'
                     WHEN stock_actual > stock_maximo THEN 'Sobre Stock'
                     ELSE 'Normal' END AS categoria,
                COUNT(*) AS cantidad,
                COALESCE(SUM(stock_actual),0) AS unidades,
                COALESCE(SUM(stock_actual * COALESCE(precio_compra_bs, precio_compra * :tasa1)),0) AS costo_adquisicion,
                COALESCE(SUM(stock_actual * COALESCE(precio_venta_bs, precio_venta * :tasa2)),0) AS valor_mercado
         FROM productos WHERE estado = true
         GROUP BY 1 ORDER BY cantidad DESC");
    $stmtResumenCat->execute([':tasa1' => $tasa, ':tasa2' => $tasa]);
    $resumenCategorias = $stmtResumenCat->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($resumenCategorias)) {
        $pdf->seccion('Resumen por Categoría de Stock');
        $fc = [];
        foreach ($resumenCategorias as $cat) {
            $fc[] = [
                $cat['categoria'],
                (int)$cat['cantidad'],
                (int)$cat['unidades'],
                'Bs ' . number_format($cat['costo_adquisicion'], 2),
                'Bs ' . number_format($cat['valor_mercado'], 2)
            ];
        }
        $pdf->tabla(
            ['Categoría', 'Productos', 'Unidades', 'Costo Adquisición', 'Valor de Mercado'],
            $fc,
            [42, 24, 24, 46, 46],
            ['L', 'C', 'C', 'R', 'R']
        );
    }

    // ============================================================
    // TABLA: PRODUCTOS CON STOCK BAJO O AGOTADO (6 columnas)
    // ============================================================
    $stmtBajo = $conn->prepare(
        "SELECT nombre, stock_actual, stock_minimo,
                COALESCE(precio_compra_bs, precio_compra * :tasa1) AS costo_unitario,
                COALESCE(precio_venta_bs, precio_venta * :tasa2) AS precio_venta,
                (stock_actual * COALESCE(precio_compra_bs, precio_compra * :tasa3)) AS inversion_stock,
                (stock_actual * COALESCE(precio_venta_bs, precio_venta * :tasa4)) AS valor_realizacion
         FROM productos
         WHERE estado = true AND stock_actual <= stock_minimo
         ORDER BY stock_actual ASC LIMIT 20");
    $stmtBajo->execute([
        ':tasa1' => $tasa, ':tasa2' => $tasa,
        ':tasa3' => $tasa, ':tasa4' => $tasa
    ]);
    $bajoStock = $stmtBajo->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($bajoStock)) {
        $pdf->seccion('Productos con Stock Bajo o Agotado');
        $fb = [];
        foreach ($bajoStock as $p) {
            $fb[] = [
                $p['nombre'],
                (int)$p['stock_actual'],
                (int)$p['stock_minimo'],
                'Bs ' . number_format($p['costo_unitario'], 2),
                'Bs ' . number_format($p['precio_venta'], 2),
                'Bs ' . number_format($p['inversion_stock'], 2),
                'Bs ' . number_format($p['valor_realizacion'], 2)
            ];
        }
        $pdf->tabla(
            ['Producto', 'Stock Actual', 'Stock Mínimo', 'Costo Unitario', 'Precio de Venta', 'Inversión en Stock', 'Valor de Realización'],
            $fb,
            [35, 22, 22, 25, 25, 25, 28],
            ['L', 'C', 'C', 'R', 'R', 'R', 'R']
        );
    }
}
    // ============================================================
    //  BITACORA DEL SISTEMA
    // ============================================================
    elseif ($module === 'bitacora' && $report_type === 'bitacora') {

        $filtroQ       = trim($data['bitacora_q']       ?? '');
        $filtroUsuario = trim($data['bitacora_usuario'] ?? '');
        $filtroFecha   = trim($data['bitacora_fecha']   ?? '');

        $where  = []; $params = [];
        if ($filtroQ) {
            $where[] = "(bs.accion ILIKE :q OR bs.tabla_afectada ILIKE :q OR bs.detalles::text ILIKE :q)";
            $params[':q'] = '%' . $filtroQ . '%';
        }
        if ($filtroUsuario) {
            $where[] = "u.nombre_completo ILIKE :usr";
            $params[':usr'] = '%' . $filtroUsuario . '%';
        }
        if ($filtroFecha) {
            $where[] = "DATE(bs.created_at) = :fec";
            $params[':fec'] = $filtroFecha;
        }
        $wc = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmtStats = $conn->prepare(
            "SELECT COUNT(*) AS total,
                    COUNT(DISTINCT bs.usuario_id) AS usuarios,
                    COUNT(DISTINCT DATE(bs.created_at)) AS dias,
                    COUNT(DISTINCT bs.tabla_afectada) AS tablas
             FROM bitacora_sistema bs
             LEFT JOIN usuarios u ON bs.usuario_id=u.id $wc");
        $stmtStats->execute($params);
        $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

        $stmtAcc = $conn->prepare(
            "SELECT bs.accion, COUNT(*) AS cantidad
             FROM bitacora_sistema bs
             LEFT JOIN usuarios u ON bs.usuario_id=u.id $wc
             GROUP BY bs.accion ORDER BY cantidad DESC LIMIT 8");
        $stmtAcc->execute($params);
        $acciones = $stmtAcc->fetchAll(PDO::FETCH_ASSOC);

        $stmtDia = $conn->prepare(
            "SELECT TO_CHAR(DATE(bs.created_at),'DD/MM') AS fecha, COUNT(*) AS registros
             FROM bitacora_sistema bs
             LEFT JOIN usuarios u ON bs.usuario_id=u.id $wc
             GROUP BY DATE(bs.created_at) ORDER BY DATE(bs.created_at) DESC LIMIT 7");
        $stmtDia->execute($params);
        $actDiaria = array_reverse($stmtDia->fetchAll(PDO::FETCH_ASSOC));

        $subtitulo = 'Corte: ' . $fechaActual . '   |   ' . $usuario . '   |   ' . $horaActual;
        if ($filtroQ || $filtroUsuario || $filtroFecha) $subtitulo .= '   |   Con filtros';
        $pdf->tituloPagina('Reporte de Bitacora del Sistema', $subtitulo);

        $pdf->filaTarjetas([
            ['valor' => number_format($stats['total']),  'label' => 'Total Registros'],
            ['valor' => (int)$stats['usuarios'],          'label' => 'Usuarios Activos'],
            ['valor' => (int)$stats['dias'],              'label' => 'Dias con Actividad'],
            ['valor' => (int)$stats['tablas'],            'label' => 'Tablas Afectadas'],
        ]);

        $resumen = 'La bitacora registra ' . number_format($stats['total']) . ' acciones de '
                 . $stats['usuarios'] . ' usuarios en ' . $stats['dias'] . ' dias, '
                 . 'afectando ' . $stats['tablas'] . ' tablas del sistema.';
        $pdf->cajaResumen($resumen);

        // Mapa de traducciones: nombre tecnico -> etiqueta legible
        $mapaAcciones = [
            'CREAR_PRODUCTO'         => 'Crear Producto',
            'EDITAR_PRODUCTO'        => 'Editar Producto',
            'ELIMINAR_PRODUCTO'      => 'Eliminar Producto',
            'CREAR_USUARIO'          => 'Crear Usuario',
            'EDITAR_USUARIO'         => 'Editar Usuario',
            'ELIMINAR_USUARIO'       => 'Eliminar Usuario',
            'REGISTRO_USUARIO'       => 'Registro Usuario',
            'REGISTRO_USU'           => 'Registro Usuario',
            'LOGIN'                  => 'Inicio de Sesion',
            'LOGOUT'                 => 'Cierre de Sesion',
            'CREAR_VENTA'            => 'Crear Venta',
            'EDITAR_VENTA'           => 'Editar Venta',
            'ANULAR_VENTA'           => 'Anular Venta',
            'PEDIDO_DIGITAL'         => 'Pedido Digital',
            'GENERAR_REPORTE'        => 'Generar Reporte',
            'GENERAR_REPO'           => 'Generar Reporte',
            'CREAR_PROMOCION'        => 'Crear Promocion',
            'CREAR_PROMOC'           => 'Crear Promocion',
            'EDITAR_PROMOCION'       => 'Editar Promocion',
            'HABILITAR_PROMOCION'    => 'Habilitar Promocion',
            'INHABILITAR_PROMOCION'  => 'Deshabilitar Promo.',
            'CAMBIAR_ESTADO'         => 'Cambiar Estado',
            'CAMBIAR_ESTA'           => 'Cambiar Estado',
            'CREAR_CLIENTE'          => 'Crear Cliente',
            'EDITAR_CLIENTE'         => 'Editar Cliente',
            'ELIMINAR_CLIENTE'       => 'Eliminar Cliente',
            'CREAR_PROVEEDOR'        => 'Crear Proveedor',
            'EDITAR_PROVEEDOR'       => 'Editar Proveedor',
            'CREAR_COMPRA'           => 'Crear Compra',
            'EDITAR_COMPRA'          => 'Editar Compra',
            'AJUSTE_INVENTARIO'      => 'Ajuste Inventario',
            'ACTUALIZAR_STOCK'       => 'Actualizar Stock',
            'REGISTRO_DE'            => 'Registro General',
        ];

        // Funcion para traducir una accion
        $traducirAccion = function($accion) use ($mapaAcciones) {
            $key = strtoupper(trim($accion));
            if (isset($mapaAcciones[$key])) return $mapaAcciones[$key];
            // Busqueda parcial si no hay coincidencia exacta
            foreach ($mapaAcciones as $patron => $traduccion) {
                if (strpos($key, $patron) !== false) return $traduccion;
            }
            // Formatear el nombre tecnico como fallback: reemplazar _ por espacio y capitalizar
            return ucwords(strtolower(str_replace('_', ' ', $accion)));
        };

        // --- Torta acciones (ancho completo para que la leyenda no se corte) ---
        if (!empty($acciones)) {
            // Limitar a 6 acciones max para que la leyenda quepa bien en el bloque
            $accionesTorta = array_slice($acciones, 0, 6);
            $nAcc = count($accionesTorta);
            $radioAcc    = 20;
            $altoNecesario = max($radioAcc * 2 + 16, $nAcc * 6.5 + 14);
            $yG = $pdf->GetY() + 1;

            $altoBarras = 9 + 36 + 11;
            $altoNeeded = max($altoNecesario, $altoBarras) + 5;
            if ($yG + $altoNeeded > 250) {
                $pdf->AddPage();
                $yG = $pdf->GetY();
            }

            $pdf->graficaTorta(
                array_map(fn($a) => (int)$a['cantidad'], $accionesTorta),
                array_map(fn($a) => $traducirAccion($a['accion']), $accionesTorta),
                'Acciones Mas Comunes', 14, $yG, $radioAcc, 106
            );
            $yPostTorta = $pdf->GetY();

            if (!empty($actDiaria)) {
                $pdf->graficaBarras(
                    array_map(fn($d) => (int)$d['registros'], $actDiaria),
                    array_map(fn($d) => $d['fecha'], $actDiaria),
                    'Actividad Diaria (ultimos 7 dias)', 122, $yG, 74, 36
                );
                $yPostBarras = $pdf->GetY();
            } else {
                $yPostBarras = $yG;
            }

            $pdf->SetY(max($yPostTorta, $yPostBarras) + 2);
        } elseif (!empty($actDiaria)) {
            $pdf->graficaBarras(
                array_map(fn($d) => (int)$d['registros'], $actDiaria),
                array_map(fn($d) => $d['fecha'], $actDiaria),
                'Actividad Diaria (ultimos 7 dias)', 14, null, 182, 36
            );
        }

        $stmtDet = $conn->prepare(
            "SELECT TO_CHAR(bs.created_at,'DD/MM/YY HH24:MI') AS fecha_hora,
                    COALESCE(u.nombre_completo,'Sistema') AS usuario,
                    bs.accion,
                    COALESCE(bs.tabla_afectada,'-') AS tabla,
                    CASE WHEN bs.detalles IS NOT NULL THEN 'Registro modificado' ELSE '-' END AS detalle
             FROM bitacora_sistema bs
             LEFT JOIN usuarios u ON bs.usuario_id=u.id $wc
             ORDER BY bs.created_at DESC LIMIT 100");
        $stmtDet->execute($params);
        $registros = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($registros)) {
            $pdf->seccion('Ultimos 100 Registros');
            $filas = [];
            foreach ($registros as $reg)
                $filas[] = [$reg['fecha_hora'], substr($reg['usuario'],0,22),
                            $traducirAccion($reg['accion']), $reg['tabla'], $reg['detalle']];
            $pdf->tabla(
                ['Fecha/Hora','Usuario','Accion','Tabla','Detalle'],
                $filas, [28,38,42,30,44]
            );
        }

        if ($filtroQ || $filtroUsuario || $filtroFecha) {
            $pdf->seccion('Filtros Aplicados');
            $fl = [];
            if ($filtroQ)       $fl[] = "Busqueda: '$filtroQ'";
            if ($filtroUsuario) $fl[] = "Usuario: '$filtroUsuario'";
            if ($filtroFecha)   $fl[] = "Fecha: '$filtroFecha'";
            $pdf->cajaResumen(implode('   |   ', $fl));
        }
    }

    // ============================================================
    //  COMPRAS: LISTADO DE PROVEEDORES
    // ============================================================
    elseif ($module === 'compras' && $report_type === 'listado_proveedores') {

        // Estadísticas generales
        $stmtStats = $conn->query(
            "SELECT COUNT(*) AS total,
                    COUNT(CASE WHEN estado = true THEN 1 END) AS activos,
                    COUNT(CASE WHEN estado = false THEN 1 END) AS inactivos,
                    COUNT(CASE WHEN email IS NOT NULL AND email != '' THEN 1 END) AS con_email
             FROM proveedores");
        $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

        $pdf->tituloPagina('Listado de Proveedores',
            'Corte: ' . $fechaActual . '   |   ' . $usuario . '   |   ' . $horaActual);

        // Tarjetas de resumen (4 tarjetas)
        $pdf->filaTarjetas([
            ['valor' => (int)$stats['total'],     'label' => 'Total Proveedores'],
            ['valor' => (int)$stats['activos'],   'label' => 'Proveedores Activos'],
            ['valor' => (int)$stats['inactivos'], 'label' => 'Proveedores Inactivos'],
            ['valor' => (int)$stats['con_email'], 'label' => 'Con Email Registrado'],
        ]);

        // Resumen ejecutivo
        $pctActivos = $stats['total'] > 0 ? round(($stats['activos'] / $stats['total']) * 100, 1) : 0;
        $resumen = 'La base de datos contiene ' . (int)$stats['total'] . ' proveedores registrados, '
                 . 'de los cuales ' . (int)$stats['activos'] . ' (' . $pctActivos . '%) se encuentran activos '
                 . 'y ' . (int)$stats['inactivos'] . ' inactivos. '
                 . 'Se registran ' . (int)$stats['con_email'] . ' proveedores con correo electrónico válido.';
        $pdf->cajaResumen($resumen);

        // Listado completo de proveedores
        $stmtProv = $conn->query(
            "SELECT id, razon_social, rif, persona_contacto, telefono_principal,
                    email, estado
             FROM proveedores
             ORDER BY razon_social ASC");
        $proveedores = $stmtProv->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($proveedores)) {
            $pdf->seccion('Listado Completo de Proveedores');
            $fp = [];
            foreach ($proveedores as $p) {
                $estadoLabel = $p['estado'] ? 'Activo' : 'Inactivo';
                $fp[] = [
                    substr($p['razon_social'], 0, 38),
                    $p['rif'] ?? '-',
                    substr($p['persona_contacto'] ?? '-', 0, 24),
                    $p['telefono_principal'] ?? '-',
                    substr($p['email'] ?? '-', 0, 32),
                    $estadoLabel
                ];
            }
            $pdf->tabla(
                ['Razón Social', 'RIF', 'Contacto', 'Teléfono', 'Email', 'Estado'],
                $fp,
                [50, 26, 32, 28, 40, 16],
                ['L', 'C', 'L', 'C', 'L', 'C']
            );
        }
    }

    // ============================================================
    //  SALIDA
    // ============================================================
    $nombreArchivo = 'reporte_' . $report_type . '_' . date('Y-m-d_His') . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    $pdf->Output('D', $nombreArchivo);

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}
?>
