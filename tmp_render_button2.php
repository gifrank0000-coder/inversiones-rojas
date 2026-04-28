<?php
$_GET["id"] = 11;
ob_start();
include "app/views/layouts/product_detail.php";
$html = ob_get_clean();
$pos = strpos($html, '<button class="btn-add-cart"');
if ($pos === false) {
    $pos = strpos($html, 'class="btn-add-cart"');
}
if ($pos !== false) {
    echo substr($html, max(0, $pos-200), 600);
} else {
    echo "button not found\n";
}
