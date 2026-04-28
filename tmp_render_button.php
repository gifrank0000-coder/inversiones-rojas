<?php
$_GET['id'] = 11;
ob_start();
include 'app/views/layouts/product_detail.php';
$html = ob_get_clean();
if (preg_match('/<button[^>]*onclick=["\"][^"\"]*["\"][^>]*>/s', $html, $m)) {
    echo $m[0] . "\n";
} else {
    echo "button not found\n";
}
