Script to append modal
<?php
$modal_html = '<div id="updateProductModal" class="modal-overlay" style="display:none;"><div class="modal" style="max-width:600px;"><div class="modal-header"><h3>Actualizar Producto</h3></div><div class="modal-body"><div id="upd_error"></div></div></div>';
file_put_contents('app/views/layouts/inventario_new.php', str_replace('</body>', $modal_html . '</body>', file_get_contents('app/views/layouts/inventario_new.php'))
