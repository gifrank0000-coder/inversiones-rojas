<?php
// Partial: Header común usado por la vista pública (inicio) y para clientes
// Requiere: session_start() ya ejecutado y BASE_URL disponible
if (!isset($base_url)) {
    $base_url = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
}
require_once __DIR__ . '/../../../models/database.php';
require_once __DIR__ . '/../../../helpers/moneda_helper.php';
?>
<script>
    var APP_BASE = '<?php echo $base_url; ?>';
    // INJECT: cart snapshot from PHP session to render mini-drawer without extra fetch
    window.INIT_CARRITO = <?php echo json_encode(array_values($_SESSION['carrito'] ?? []), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
    // Expose helper to update INIT_CARRITO from server
    window.reloadMiniCart = async function(){
        try{
            const p = (window.APP_BASE||'') + '/api/get_cart.php';
            const res = await fetch(p, { credentials: 'include', cache: 'no-store' });
            if(!res.ok) return;
            const j = await res.json();
            if(j && j.ok){
                // convert carrito array to session-like map for compatibility
                // keep INIT_CARRITO as array of items
                window.INIT_CARRITO = j.carrito || [];
                // dispatch event so UI can refresh
                window.dispatchEvent(new CustomEvent('mini-cart:updated', { detail: j }));
            }
        }catch(e){ console.warn('reloadMiniCart failed', e); }
    };
    // Monkeypatch fetch to auto-refresh mini cart after add_to_cart.php calls
    (function(){
        const _fetch = window.fetch;
        if (!_fetch) return;
        window.fetch = function(input, init){
            const url = (typeof input === 'string') ? input : (input && input.url) || '';
            return _fetch.apply(this, arguments).then(async function(resp){
                try{
                    if(url && url.indexOf('add_to_cart.php') !== -1){
                        // try to clone and read json to detect success
                        const copy = resp.clone();
                        const json = await copy.json().catch(()=>null);
                        if(json && (json.success || json.ok)){
                            // refresh mini cart snapshot
                            window.reloadMiniCart();
                        }
                    }
                }catch(e){ console.warn('mini-cart fetch hook', e); }
                return resp;
            });
        };
    })();
</script>
<?php
// Detectar sesión usando múltiples nombres de keys que pueden usarse en distintas partes del código
$usuario_logueado = false;
$user_name = '';
$user_email = '';
$user_rol = '';

$session_keys = [
    'id' => ['user_id','usuario_id','id'],
    'name' => ['user_name','username','nombre_completo','name'],
    'email' => ['user_email','email'],
    'role' => ['user_rol','rol_nombre','rol']
];

// Encontrar user id
$foundId = null;
foreach ($session_keys['id'] as $k) {
    if (!empty($_SESSION[$k])) { $foundId = $_SESSION[$k]; break; }
}

if ($foundId) {
    $usuario_logueado = true;
    // Nombre
    foreach ($session_keys['name'] as $k) {
        if (!empty($_SESSION[$k])) { $user_name = $_SESSION[$k]; break; }
    }
    // Email
    foreach ($session_keys['email'] as $k) {
        if (!empty($_SESSION[$k])) { $user_email = $_SESSION[$k]; break; }
    }
    // Rol
    foreach ($session_keys['role'] as $k) {
        if (!empty($_SESSION[$k])) { $user_rol = $_SESSION[$k]; break; }
    }
}
?>

<!-- Barra superior con buscador - COMPARTIDO -->
<div class="top-bar" data-base-url="<?php echo BASE_URL; ?>">
    <div class="container top-bar-content">
        <div class="logo-section">
            <button class="nav-btn" id="navToggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo">
                <a href="<?php echo BASE_URL; ?>/" class="logo-link">
                    <i class="fas fa-motorcycle logo-icon"></i>
                    <h1>Inversiones Rojas</h1>
                </a>
            </div>
        </div>

        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-bar" placeholder="Buscar productos por nombre..." autocomplete="off">
        </div>

<div class="top-actions">
            <!-- Carrito: botón que abre drawer lateral -->
            <button id="cartToggle" type="button" class="icon-btn cart-link mobile-cart" aria-label="Abrir carrito" onclick="(function(){const d=document.getElementById('cartDrawer'),b=document.getElementById('cartBackdrop'); if(d){ d.classList.toggle('open'); d.setAttribute('aria-hidden', d.classList.contains('open') ? 'false' : 'true'); } if(b){ b.classList.toggle('open'); b.setAttribute('aria-hidden', b.classList.contains('open') ? 'false' : 'true'); } })()">
                <i class="fas fa-shopping-cart"></i>
                <?php
                $cart_count = 0;
                if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
                    foreach ($_SESSION['carrito'] as $item) {
                        $itemQuantity = 0;
                        if (isset($item['quantity']) && is_numeric($item['quantity'])) {
                            $itemQuantity = intval($item['quantity']);
                        } elseif (isset($item['cantidad']) && is_numeric($item['cantidad'])) {
                            $itemQuantity = intval($item['cantidad']);
                        }
                        $cart_count += max(0, $itemQuantity);
                    }
                }
                if ($cart_count > 0):
                ?>
                <span class="cart-count"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </button>

            <!-- Drawer lateral del carrito -->
            <div id="cartDrawer" class="cart-drawer" aria-hidden="true">
                <div class="cart-drawer-header">
                    <h3>Tu carrito</h3>
                    <button id="cartClose" class="cart-close" aria-label="Cerrar carrito"><i class="fas fa-times"></i></button>
                </div>
                <div id="cartItems" class="cart-items">
                <?php
                $carrito_items = $_SESSION['carrito'] ?? [];
                $drawer_items = [];
                if (!empty($carrito_items)) {
                    try {
                        $db = new Database();
                        $conn = $db->getConnection();
                        $product_ids = array_keys($carrito_items);
                        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
                        $sql = "SELECT p.id, p.nombre, COALESCE(pi.imagen_url, '') as imagen_url
                                FROM productos p
                                LEFT JOIN producto_imagenes pi ON pi.producto_id = p.id AND pi.es_principal = true
                                WHERE p.id IN ($placeholders) AND p.estado = true";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute($product_ids);
                        $productos_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($productos_db as $producto) {
                            $id = $producto['id'];
                            if (isset($carrito_items[$id])) {
                                $drawer_items[$id] = [
                                    'id' => $id,
                                    'nombre' => $carrito_items[$id]['nombre'] ?? $producto['nombre'],
                                    'precio' => $carrito_items[$id]['precio'] ?? 0,
                                    'cantidad' => $carrito_items[$id]['cantidad'] ?? $carrito_items[$id]['quantity'] ?? 1,
                                    'imagen' => $producto['imagen_url'] ?? ''
                                ];
                            }
                        }
                    } catch (Exception $e) {
                        foreach ($carrito_items as $id => $item) {
                            $drawer_items[$id] = [
                                'id' => $id,
                                'nombre' => $item['nombre'] ?? 'Producto',
                                'precio' => $item['precio'] ?? 0,
                                'cantidad' => $item['cantidad'] ?? $item['quantity'] ?? 1,
                                'imagen' => ''
                            ];
                        }
                    }
                }
                if (!empty($drawer_items)):
                ?>
                <ul class="cart-drawer-list">
                    <?php foreach ($drawer_items as $item): 
                        $imgSrc = !empty($item['imagen']) ? $item['imagen'] : '';
                        $precios = formatearMonedaDual($item['precio']);
                    ?>
                    <li class="cart-drawer-item" data-id="<?php echo $item['id']; ?>">
                        <img src="<?php echo $imgSrc; ?>" class="cart-drawer-img" alt="<?php echo htmlspecialchars($item['nombre']); ?>"/>
                        <div class="cart-drawer-meta">
                            <div><strong><?php echo htmlspecialchars($item['nombre']); ?></strong></div>
                            <small><?php echo $item['cantidad']; ?> × <span class="moneda-usd"><?php echo $precios['usd']; ?></span> <span class="moneda-bs"><?php echo $precios['bs']; ?></span></small>
                        </div>
                        <button class="cart-item-remove" title="Eliminar" data-id="<?php echo $item['id']; ?>"><i class="fas fa-trash-alt"></i></button>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <div class="cart-empty">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Tu carrito está vacío</p>
                </div>
                <?php endif; ?>
            </div>
                <div class="cart-drawer-footer">
                    <button id="checkoutBtn" class="btn btn-primary btn-block">Comprar</button>
                </div>
            </div>
            <div id="cartBackdrop" class="cart-backdrop" aria-hidden="true"></div>

            <style>
                /* Drawer personalizado - Estilo mejorado */
                :root { --primary: #1F9166; --muted: #6c757d; }
                .cart-backdrop { position:fixed; inset:0; background:rgba(0,0,0,0.5); opacity:0; visibility:hidden; transition:opacity 250ms ease; z-index:9998; }
                .cart-backdrop.open { opacity:1; visibility:visible; }
                .cart-drawer { position: fixed; top: 0; right: 0; height: 100vh; width: 400px; max-width: 100%; background:#fff; box-shadow:-4px 0 24px rgba(0,0,0,0.15); transform: translateX(100%); transition: transform 300ms cubic-bezier(.4, 0, .2, 1); z-index: 9999; display: flex; flex-direction: column; overflow: hidden; }
                .cart-drawer.open { transform: translateX(0); }
                .cart-drawer-header { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; background:var(--primary); color:#fff; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
                .cart-drawer-header h3 { margin:0; font-size:1.15rem; font-weight:600; }
                .cart-close { background:transparent; border:none; color:#fff; font-size:1.2rem; cursor:pointer; padding:4px 8px; border-radius:4px; transition:background 0.2s; }
                .cart-close:hover { background:rgba(255,255,255,0.2); }
                .cart-items { flex:1; overflow-y:auto; background:#f8f9fa; }
                .cart-drawer-footer { padding:16px; background:#fff; border-top:1px solid #dee2e6; box-shadow:0 -2px 8px rgba(0,0,0,0.05); }
                .cart-drawer-list { list-style:none; margin:0; padding:0; }
                .cart-drawer-item { display:flex; align-items:center; padding:12px 16px; border-bottom:1px solid #eee; gap:14px; background:#fff; transition:background 0.2s; }
                .cart-drawer-item:hover { background:#fafafa; }
                .cart-drawer-img { width:60px; height:60px; object-fit:cover; border-radius:8px; background:#e9ecef; flex-shrink:0; border:1px solid #dee2e6; }
                .cart-drawer-meta { font-size:14px; color:#333; }
                .cart-drawer-meta small { color:var(--muted); display:block; margin-top:6px; font-size:13px; }
                .cart-item-remove { background:transparent; border:none; color:var(--muted); font-size:14px; padding:6px; cursor:pointer; }
                .cart-count { background:var(--primary); color:#fff; border-radius:12px; padding:2px 7px; font-size:12px; margin-left:6px; display:inline-block; }
                .cart-drawer-meta { flex:1; min-width:0; }
                .cart-drawer-meta > div { font-size:14px; font-weight:600; color:#212529; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
                .cart-drawer-meta small { color:var(--muted); font-size:13px; display:block; margin-top:4px; }
                .cart-item-remove { background:transparent; border:none; color:#dc3545; font-size:14px; padding:8px; cursor:pointer; border-radius:4px; transition:all 0.2s; opacity:0.7; }
                .cart-item-remove:hover { background:#ffeef0; opacity:1; }
                .btn-block { width:100%; padding:14px; border-radius:8px; border:none; background:var(--primary); color:#fff; font-weight:600; font-size:15px; cursor:pointer; transition:all 0.2s; }
                .btn-block:hover { background:#178a5a; transform:translateY(-1px); box-shadow:0 4px 12px rgba(31,145,102,0.3); }
                .cart-empty { text-align:center; padding:40px 20px; color:var(--muted); }
                .cart-empty i { font-size:48px; margin-bottom:12px; opacity:0.4; }
                .moneda-usd { color:#1F9166; font-weight:600; }
                .moneda-bs { color:#6c757d; font-size:0.9em; margin-left:4px; }
                @media (max-width: 480px){ .cart-drawer{ width:100%; } }
            </style>

            <script>
                document.addEventListener('DOMContentLoaded', function(){
                    const cartToggle = document.getElementById('cartToggle');
                    const cartDrawer = document.getElementById('cartDrawer');
                    const cartClose = document.getElementById('cartClose');
                    const cartItems = document.getElementById('cartItems');
                    const checkoutBtn = document.getElementById('checkoutBtn');
                    const cartBackdrop = document.getElementById('cartBackdrop');
                    const cartCountSpans = document.querySelectorAll('.cart-count');

                    function updateCount(n){
                        cartCountSpans.forEach(s=>{ if(n>0){ s.textContent=n; s.style.display='inline-block'; } else { s.style.display='none'; } });
                    }

                    async function loadCart(){
                        try{
                            if (!cartItems) return;
                            // If server provided an initial snapshot, render it immediately (faster, avoids fetch issues)
                            if (Array.isArray(window.INIT_CARRITO) && window.INIT_CARRITO.length > 0){
                                const items = window.INIT_CARRITO;
                                let html = '<ul class="cart-drawer-list">';
                                items.forEach(it => {
                                    const img = it.imagen && it.imagen.length ? it.imagen : (window.APP_BASE || '') + '/public/img/logo.png';
                                    const precio = Number(it.precio || it.price || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                    const cantidad = it.cantidad ?? it.quantity ?? it.quantity ?? 1;
                                    html += `<li class="cart-drawer-item" data-id="${it.id}"><img src="${img}" class="cart-drawer-img" alt="${(it.nombre||it.name||'Producto')?.replace(/"/g,'') }"/><div class="cart-drawer-meta"><div><strong>${(it.nombre||it.name||'Producto')}</strong></div><small>${cantidad} × ${precio}</small></div><button class="cart-item-remove" title="Eliminar" data-id="${it.id}"><i class="fas fa-trash-alt"></i></button></li>`;
                                });
                                html += '</ul>';
                                cartItems.innerHTML = html;
                                updateCount(Array.isArray(items) ? items.reduce((s,i)=>s + (parseInt(i.cantidad||i.quantity||0)||0),0) : 0);
                                return;
                            }
                            // Otherwise, fallback to network fetch (existing logic)
                            cartItems.innerHTML = '<p style="color:var(--muted)">Cargando...</p>';
                            const base = (window.APP_BASE || '').replace(/\/$/, '');
                            const origin = window.location.origin;
                            const candidates = [
                                base + '/api/get_cart.php',
                                '/api/get_cart.php',
                                origin + base + '/api/get_cart.php',
                                origin + '/inversiones-rojas/api/get_cart.php',
                                'api/get_cart.php'
                            ];
                            let data = null;
                            let lastErr = null;
                            for (const p of candidates){
                                try{
                                    console.debug('Trying cart endpoint:', p);
                                    const res = await fetch(p, { cache: 'no-store', credentials: 'include' });
                                    if (!res){ lastErr = 'no response'; continue; }
                                    if (!res.ok){ console.warn('Not OK', p, res.status); lastErr = 'HTTP ' + res.status; continue; }
                                    const text = await res.text();
                                    try{ data = JSON.parse(text); }catch(pe){ console.warn('JSON parse failed for', p, pe); console.debug('Response text:', text.substring(0,500)); lastErr = 'invalid json'; continue; }
                                    break;
                                }catch(err){ console.warn('Fetch failed for', p, err); lastErr = err && err.message ? err.message : String(err); continue; }
                            }
                            if (!data){
                                console.error('No JSON response from get_cart.php, lastErr=', lastErr);
                                cartItems.innerHTML = '<p style="color:var(--muted)">Error al obtener el carrito ('+lastErr+')</p>';
                                return;
                            }
                            if (!data.ok){
                                console.error('API get_cart returned error', data);
                                cartItems.innerHTML = '<p style="color:var(--muted)">Error al obtener el carrito</p>';
                                return;
                            }
                            const items = data.carrito || [];
                            if (items.length === 0){
                                cartItems.innerHTML = '<p style="color:var(--muted)">Tu carrito está vacío.</p>';
                                updateCount(0);
                                return;
                            }
                            let html = '<ul class="cart-drawer-list">';
                            items.forEach(it => {
                                const img = it.imagen && it.imagen.length ? it.imagen : (window.APP_BASE || '') + '/public/img/logo.png';
                                const precio = Number(it.precio).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                html += `<li class="cart-drawer-item" data-id="${it.id}"><img src="${img}" class="cart-drawer-img" alt="${it.nombre}"/><div class="cart-drawer-meta"><div><strong>${it.nombre}</strong></div><small>${it.cantidad} × ${precio}</small></div><button class="cart-item-remove" title="Eliminar" data-id="${it.id}"><i class="fas fa-trash-alt"></i></button></li>`;
                            });
                            html += '</ul>';
                            cartItems.innerHTML = html;
                            updateCount(data.count || 0);
                        }catch(e){
                            console.error('Error in loadCart:', e);
                            if (cartItems) cartItems.innerHTML = '<p style="color:var(--muted)">Error cargando el carrito: '+(e && e.message ? e.message : e)+'</p>';
                        }
                    }

                    function openDrawer(){ cartDrawer.classList.add('open'); cartBackdrop.classList.add('open'); cartDrawer.setAttribute('aria-hidden','false'); cartBackdrop.setAttribute('aria-hidden','false'); loadCart(); }
                    function closeDrawer(){ cartDrawer.classList.remove('open'); cartBackdrop.classList.remove('open'); cartDrawer.setAttribute('aria-hidden','true'); cartBackdrop.setAttribute('aria-hidden','true'); }

                    cartToggle && cartToggle.addEventListener('click', function(){ if (cartDrawer && cartDrawer.classList.contains('open')) closeDrawer(); else openDrawer(); });
                    cartClose && cartClose.addEventListener('click', closeDrawer);
                    cartBackdrop && cartBackdrop.addEventListener('click', closeDrawer);
                    checkoutBtn && checkoutBtn.addEventListener('click', ()=>{ window.location.href = (window.APP_BASE||'') + '/app/views/layouts/carrito.php'; });

                    // Delegación para eliminar item desde drawer (solo si existe el contenedor)
                    if (cartItems){
                        cartItems.addEventListener('click', async function(e){
                            const btn = e.target.closest('.cart-item-remove');
                            if (!btn) return;
                            const pid = btn.getAttribute('data-id');
                            if (!pid) return;
                            const nombre = btn.closest('.cart-drawer-item')?.querySelector('.cart-drawer-meta > div')?.textContent || 'este producto';
                            
                            const confirmed = typeof showConfirm === 'function'
                                ? await showConfirm({
                                    title: 'Eliminar producto',
                                    message: `¿Estás seguro de eliminar "${nombre}" del carrito?`,
                                    confirmText: 'Eliminar',
                                    cancelText: 'Cancelar',
                                    type: 'warning'
                                })
                                : confirm(`¿Eliminar este producto del carrito?`);
                            
                            if (!confirmed) return;
                            
                            try{
                                const itemEl = btn.closest('.cart-drawer-item');
                                if(itemEl) itemEl.style.opacity = '0.5';
                                btn.disabled = true;
                                
                                const res = await fetch((window.APP_BASE||'') + '/api/update_cart.php', { method: 'POST', headers: { 'Content-Type':'application/json' }, body: JSON.stringify({ product_id: pid, quantity: 0 }) });
                                if(!res.ok) throw new Error('HTTP '+res.status);
                                const json = await res.json();
                                if(!json.success){ 
                                    if(itemEl) itemEl.style.opacity = '1';
                                    if(window.Toast) Toast.error(json.message || 'Error al eliminar');
                                    else alert(json.message || 'Error');
                                    return; 
                                }
                                // Eliminar visualmente con animación
                                if(itemEl){
                                    itemEl.style.transition = 'all 0.3s ease';
                                    itemEl.style.opacity = '0';
                                    itemEl.style.transform = 'translateX(20px)';
                                    setTimeout(() => itemEl.remove(), 300);
                                }
                                updateCount(json.total_items || 0);
                                
                                if(window.Toast){
                                    Toast.success('Producto eliminado del carrito', 'Eliminado', 2000);
                                }
                                
                                setTimeout(() => {
                                    if(!cartItems.querySelector('.cart-drawer-item')){
                                        cartItems.innerHTML = '<div class="cart-empty"><i class="fas fa-shopping-cart"></i><p>Tu carrito está vacío</p></div>';
                                    }
                                }, 350);
                            }catch(err){ 
                                console.error(err); 
                                if(window.Toast) Toast.error('Error al eliminar el producto');
                                else alert('Error al eliminar el producto');
                            }
                        });
                    }
                });
            </script>

            <style>
                /* Override visual: personalizar drawer rápidamente */
                :root { --primary: #1F9166; --muted:#6c757d; }
                /* Ocultar total (el usuario pidió que no se muestre) */
                .cart-total { display: none !important; }
                /* Cabecera verde y botón centrado acorde al estilo */
                .cart-drawer-header { background: var(--primary) !important; color: #fff !important; }
                .cart-drawer-meta strong { font-size: 14px; }
                .cart-drawer-meta small { color: var(--muted); }
                #checkoutBtn.btn-block { background: var(--primary) !important; border: none !important; box-shadow: none; }
            </style>

            <script>
                // Ajustes dinámicos: crear backdrop si hace falta y mejorar comportamiento visual
                document.addEventListener('DOMContentLoaded', function(){
                    // Si backdrop inexistente, crear uno (por compatibilidad)
                    if (!document.getElementById('cartBackdrop')){
                        const backdrop = document.createElement('div');
                        backdrop.id = 'cartBackdrop';
                        backdrop.className = 'cart-backdrop';
                        document.body.appendChild(backdrop);
                    }
                    const backdrop = document.getElementById('cartBackdrop');
                    const cartDrawer = document.getElementById('cartDrawer');
                    const cartToggle = document.getElementById('cartToggle');
                    const cartClose = document.getElementById('cartClose');

                    function open(){
                        cartDrawer && cartDrawer.classList.add('open');
                        backdrop && backdrop.classList.add('open');
                    }
                    function close(){
                        cartDrawer && cartDrawer.classList.remove('open');
                        backdrop && backdrop.classList.remove('open');
                    }

                    backdrop && backdrop.addEventListener('click', close);
                    cartClose && cartClose.addEventListener('click', close);
                    cartToggle && cartToggle.addEventListener('click', function(){
                        if (cartDrawer && cartDrawer.classList.contains('open')) close(); else open();
                    });

                    // Quitar el elemento cartTotal si existe en el DOM (por si quedó visible)
                    const ct = document.getElementById('cartTotal');
                    if (ct) ct.style.display = 'none';
                });
            </script>

            <!-- Botones de auth solo visibles en desktop -->
            <div class="auth-buttons desktop-only">
                <?php if (!$usuario_logueado): ?>
                    <a href="<?php echo BASE_URL; ?>/app/views/auth/Login.php" class="auth-btn login-btn">Iniciar Sesión</a>
                    <a href="<?php echo BASE_URL; ?>/app/views/auth/register.php" class="auth-btn register-btn">Registrarse</a>
                <?php else: ?>
                    <?php require __DIR__ . '/user_panel.php'; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Header y Navegación -->
<header>
    <div class="container header-content">
        <nav>
            <ul id="navMenu">
                <li><a href="<?php echo BASE_URL; ?>/">Inicio</a></li>
                <li><a href="<?php echo BASE_URL; ?>/app/views/layouts/motos.php">Motos</a></li>
                <li><a href="<?php echo BASE_URL; ?>/app/views/layouts/repuestos.php">Repuestos</a></li>
                <li><a href="<?php echo BASE_URL; ?>/app/views/layouts/accesorios.php">Accesorios</a></li>
                <li><a href="<?php echo BASE_URL; ?>/app/views/layouts/ayuda.php">Ayuda</a></li>
                <li><a href="<?php echo BASE_URL; ?>/app/views/layouts/contacto.php">Contacto</a></li>
                
                <!-- Botones de auth para móvil dentro del menú -->
                <li class="mobile-auth-links">
                    <?php if (!$usuario_logueado): ?>
                        <a href="<?php echo BASE_URL; ?>/app/views/auth/Login.php" class="auth-btn login-btn mobile-auth">
                            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                        </a>
                        <a href="<?php echo BASE_URL; ?>/app/views/auth/register.php" class="auth-btn register-btn mobile-auth">
                            <i class="fas fa-user-plus"></i> Registrarse
                        </a>
                    <?php else: ?>
                        <a href="#" class="auth-btn user-btn mobile-auth">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($user_name); ?>
                        </a>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>
    </div>
</header>