// Archivo global main.js (hemos movido funcionalidades comunes aquí si hace falta)
console.log('main.js cargado');

// ========== FUNCIÓN GLOBAL: Agregar item al drawer del carrito ==========
// Disponible en todas las páginas (motos, repuestos, accesorios, product_detail)
window.addItemToDrawer = function(id, nombre, cantidad, precio, imagen) {
    const cartItems = document.getElementById('cartItems');
    if (!cartItems) return;
    
    const imgSrc = imagen || '';
    const tasaCambio = window.TASA_CAMBIO || 1;
    const precioNum = typeof precio === 'number' ? precio : parseFloat(String(precio).replace(/[^0-9.]/g, '')) || 0;
    const precioBS = precioNum * tasaCambio;
    const precioUSDStr = '$' + precioNum.toFixed(2);
    const precioBSStr = 'Bs ' + precioBS.toFixed(2);
    
    const existingItem = cartItems.querySelector(`.cart-drawer-item[data-id="${id}"]`);
    if (existingItem) {
        const meta = existingItem.querySelector('.cart-drawer-meta small');
        if (meta) {
            const currentQty = parseInt(meta.textContent.split(' × ')[0]) || 1;
            meta.innerHTML = `${Number(currentQty) + Number(cantidad)} × <span class="moneda-usd">${precioUSDStr}</span> <span class="moneda-bs">${precioBSStr}</span>`;
        }
        return;
    }
    
    const html = `<li class="cart-drawer-item" data-id="${id}">
        <img src="${imgSrc}" class="cart-drawer-img" alt="${nombre}"/>
        <div class="cart-drawer-meta">
            <div><strong>${nombre}</strong></div>
            <small>${cantidad} × <span class="moneda-usd">${precioUSDStr}</span> <span class="moneda-bs">${precioBSStr}</span></small>
        </div>
        <button class="cart-item-remove" title="Eliminar" data-id="${id}"><i class="fas fa-trash-alt"></i></button>
    </li>`;
    
    const emptyMsg = cartItems.querySelector('p[style*="Tu carrito está vacío"]');
    if (emptyMsg) {
        cartItems.innerHTML = '<ul class="cart-drawer-list">' + html + '</ul>';
    } else {
        const list = cartItems.querySelector('.cart-drawer-list');
        if (list) {
            list.insertAdjacentHTML('beforeend', html);
        } else {
            cartItems.innerHTML = '<ul class="cart-drawer-list">' + html + '</ul>';
        }
    }
};