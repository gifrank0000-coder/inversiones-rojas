/**
 * inv-notifications.js
 * Sistema de notificaciones y diálogos personalizados para Inventario
 * Reemplaza: alert(), confirm() y showNotification() básico
 * Ubicación: /public/js/inv-notifications.js
 */

// ═══════════════════════════════════════════════════════════════
//  ESTILOS INYECTADOS
// ═══════════════════════════════════════════════════════════════
(function injectStyles() {
    if (document.getElementById('inv-notif-styles')) return;
    const style = document.createElement('style');
    style.id = 'inv-notif-styles';
    style.textContent = `
    /* ── Toast Container ── */
    #inv-toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 99999;
        display: flex;
        flex-direction: column;
        gap: 10px;
        pointer-events: none;
        max-width: 380px;
    }

    /* ── Toast Base ── */
    .inv-toast {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px 16px;
        border-radius: 10px;
        background: white;
        box-shadow: 0 8px 24px rgba(0,0,0,0.14), 0 2px 6px rgba(0,0,0,0.08);
        pointer-events: all;
        min-width: 280px;
        max-width: 380px;
        border-left: 4px solid #ccc;
        animation: invToastIn 0.35s cubic-bezier(0.21,1.02,0.73,1) forwards;
        position: relative;
        overflow: hidden;
    }
    .inv-toast.hiding {
        animation: invToastOut 0.3s ease forwards;
    }

    /* ── Tipos ── */
    .inv-toast.success  { border-left-color: #1F9166; }
    .inv-toast.error    { border-left-color: #e74c3c; }
    .inv-toast.warning  { border-left-color: #f39c12; }
    .inv-toast.info     { border-left-color: #3498db; }
    .inv-toast.edited   { border-left-color: #9b59b6; }
    .inv-toast.deleted  { border-left-color: #e74c3c; }
    .inv-toast.canceled { border-left-color: #95a5a6; }

    /* ── Ícono ── */
    .inv-toast-icon {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        flex-shrink: 0;
        margin-top: 1px;
    }
    .inv-toast.success  .inv-toast-icon { background: #e8f6f1; color: #1F9166; }
    .inv-toast.error    .inv-toast-icon { background: #fdeaea; color: #e74c3c; }
    .inv-toast.warning  .inv-toast-icon { background: #fff7e6; color: #f39c12; }
    .inv-toast.info     .inv-toast-icon { background: #eaf4fe; color: #3498db; }
    .inv-toast.edited   .inv-toast-icon { background: #f4eaff; color: #9b59b6; }
    .inv-toast.deleted  .inv-toast-icon { background: #fdeaea; color: #e74c3c; }
    .inv-toast.canceled .inv-toast-icon { background: #f5f5f5; color: #95a5a6; }

    /* ── Contenido ── */
    .inv-toast-body { flex: 1; min-width: 0; }
    .inv-toast-title {
        font-size: 13px;
        font-weight: 700;
        color: #222;
        margin-bottom: 2px;
    }
    .inv-toast-message {
        font-size: 12px;
        color: #666;
        line-height: 1.4;
        word-break: break-word;
    }

    /* ── Cerrar ── */
    .inv-toast-close {
        background: none;
        border: none;
        color: #bbb;
        cursor: pointer;
        font-size: 16px;
        padding: 0 0 0 8px;
        line-height: 1;
        flex-shrink: 0;
        transition: color 0.15s;
    }
    .inv-toast-close:hover { color: #666; }

    /* ── Barra de progreso ── */
    .inv-toast-progress {
        position: absolute;
        bottom: 0; left: 0;
        height: 3px;
        border-radius: 0 0 0 10px;
        animation: invToastProgress var(--duration, 4s) linear forwards;
    }
    .inv-toast.success  .inv-toast-progress { background: #1F9166; }
    .inv-toast.error    .inv-toast-progress { background: #e74c3c; }
    .inv-toast.warning  .inv-toast-progress { background: #f39c12; }
    .inv-toast.info     .inv-toast-progress { background: #3498db; }
    .inv-toast.edited   .inv-toast-progress { background: #9b59b6; }
    .inv-toast.deleted  .inv-toast-progress { background: #e74c3c; }
    .inv-toast.canceled .inv-toast-progress { background: #95a5a6; }

    /* ── Animaciones ── */
    @keyframes invToastIn {
        from { opacity: 0; transform: translateX(120%); }
        to   { opacity: 1; transform: translateX(0); }
    }
    @keyframes invToastOut {
        from { opacity: 1; transform: translateX(0); max-height: 120px; margin-bottom: 0; }
        to   { opacity: 0; transform: translateX(120%); max-height: 0; margin-bottom: -10px; }
    }
    @keyframes invToastProgress {
        from { width: 100%; }
        to   { width: 0%; }
    }

    /* ═══════════════════════════════════
       DIÁLOGO PERSONALIZADO (reemplaza confirm)
    ════════════════════════════════════ */
    .inv-dialog-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.45);
        z-index: 100000;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: invFadeIn 0.2s ease;
    }
    @keyframes invFadeIn { from{opacity:0} to{opacity:1} }

    .inv-dialog {
        background: white;
        border-radius: 14px;
        padding: 28px 28px 22px;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        animation: invDialogIn 0.25s cubic-bezier(0.34,1.56,0.64,1) forwards;
    }
    @keyframes invDialogIn {
        from { opacity:0; transform: scale(0.85); }
        to   { opacity:1; transform: scale(1); }
    }

    .inv-dialog-icon {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        margin: 0 auto 16px;
    }
    .inv-dialog-icon.danger  { background: #fdeaea; color: #e74c3c; }
    .inv-dialog-icon.warning { background: #fff7e6; color: #f39c12; }
    .inv-dialog-icon.info    { background: #eaf4fe; color: #3498db; }

    .inv-dialog h3 {
        text-align: center;
        font-size: 17px;
        font-weight: 700;
        color: #222;
        margin-bottom: 10px;
    }
    .inv-dialog p {
        text-align: center;
        font-size: 13px;
        color: #666;
        line-height: 1.5;
        margin-bottom: 22px;
    }
    .inv-dialog-btns {
        display: flex;
        gap: 10px;
        justify-content: center;
    }
    .inv-dialog-btns button {
        padding: 9px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        min-width: 100px;
    }
    .inv-btn-cancel {
        background: #f0f0f0;
        color: #555;
    }
    .inv-btn-cancel:hover { background: #e0e0e0; }
    .inv-btn-confirm-danger {
        background: #e74c3c;
        color: white;
    }
    .inv-btn-confirm-danger:hover { background: #c0392b; }
    .inv-btn-confirm-warning {
        background: #f39c12;
        color: white;
    }
    .inv-btn-confirm-warning:hover { background: #d68910; }
    .inv-btn-confirm-info {
        background: #1F9166;
        color: white;
    }
    .inv-btn-confirm-info:hover { background: #187a54; }

    /* ── Validación ── */
    .inv-field-error {
        font-size: 11px;
        color: #e74c3c;
        margin-top: 4px;
        display: flex;
        align-items: center;
        gap: 4px;
        animation: invErrorIn 0.2s ease;
    }
    @keyframes invErrorIn { from{opacity:0;transform:translateY(-4px)} to{opacity:1;transform:translateY(0)} }
    .inv-field-error i { font-size: 10px; }
    .inv-input-error {
        border-color: #e74c3c !important;
        box-shadow: 0 0 0 3px rgba(231,76,60,0.1) !important;
    }
    .inv-input-success {
        border-color: #1F9166 !important;
        box-shadow: 0 0 0 3px rgba(31,145,102,0.1) !important;
    }
    `;
    document.head.appendChild(style);
})();

// ═══════════════════════════════════════════════════════════════
//  SISTEMA DE TOASTS
// ═══════════════════════════════════════════════════════════════

function getToastContainer() {
    let c = document.getElementById('inv-toast-container');
    if (!c) {
        c = document.createElement('div');
        c.id = 'inv-toast-container';
        document.body.appendChild(c);
    }
    return c;
}

/**
 * Mostrar notificación toast personalizada
 * @param {string} message  - Mensaje principal
 * @param {string} type     - success | error | warning | info | edited | deleted | canceled
 * @param {string} title    - Título (opcional, se autoasigna si no se pasa)
 * @param {number} duration - Duración en ms (default 10000)
 */
function showNotification(message, type = 'info', title = '', duration = 10000) {
    const icons = {
        success:  'fas fa-check-circle',
        error:    'fas fa-times-circle',
        warning:  'fas fa-exclamation-triangle',
        info:     'fas fa-info-circle',
        edited:   'fas fa-pen',
        deleted:  'fas fa-trash-alt',
        canceled: 'fas fa-ban',
    };
    const titles = {
        success:  title || '¡Operación exitosa!',
        error:    title || 'Error',
        warning:  title || 'Advertencia',
        info:     title || 'Información',
        edited:   title || 'Actualizado',
        deleted:  title || 'Eliminado',
        canceled: title || 'Cancelado',
    };

    const container = getToastContainer();
    const toast = document.createElement('div');
    toast.className = `inv-toast ${type}`;
    toast.style.setProperty('--duration', `${duration}ms`);

    toast.innerHTML = `
        <div class="inv-toast-icon"><i class="${icons[type] || icons.info}"></i></div>
        <div class="inv-toast-body">
            <div class="inv-toast-title">${titles[type]}</div>
            <div class="inv-toast-message">${message}</div>
        </div>
        <button class="inv-toast-close" title="Cerrar">&times;</button>
        <div class="inv-toast-progress"></div>
    `;

    container.appendChild(toast);

    // Cerrar al clicar X
    toast.querySelector('.inv-toast-close').addEventListener('click', () => dismissToast(toast));

    // Auto-cerrar
    const timer = setTimeout(() => dismissToast(toast), duration);

    // Pausar al hacer hover
    toast.addEventListener('mouseenter', () => {
        clearTimeout(timer);
        const prog = toast.querySelector('.inv-toast-progress');
        if (prog) prog.style.animationPlayState = 'paused';
    });
    toast.addEventListener('mouseleave', () => {
        const prog = toast.querySelector('.inv-toast-progress');
        if (prog) prog.style.animationPlayState = 'running';
        setTimeout(() => dismissToast(toast), 1500);
    });
}

function dismissToast(toast) {
    if (!toast.parentNode) return;
    toast.classList.add('hiding');
    toast.addEventListener('animationend', () => toast.remove(), { once: true });
}

// Atajos semánticos
const Toast = {
    success:  (msg, title, duration) => showNotification(msg, 'success',  title, duration),
    error:    (msg, title, duration) => showNotification(msg, 'error',    title, duration),
    warning:  (msg, title, duration) => showNotification(msg, 'warning',  title, duration),
    info:     (msg, title, duration) => showNotification(msg, 'info',     title, duration),
    edited:   (msg, title, duration) => showNotification(msg, 'edited',   title, duration),
    deleted:  (msg, title, duration) => showNotification(msg, 'deleted',  title, duration),
    canceled: (msg, title, duration) => showNotification(msg, 'canceled', title, duration),
};

// Compatibilidad: algunos scripts usan showNotification.error/success/etc.
if (typeof showNotification === 'function') {
    showNotification.success  = Toast.success;
    showNotification.error    = Toast.error;
    showNotification.warning  = Toast.warning;
    showNotification.info     = Toast.info;
    showNotification.edited   = Toast.edited;
    showNotification.deleted  = Toast.deleted;
    showNotification.canceled = Toast.canceled;
}


// ═══════════════════════════════════════════════════════════════
//  DIÁLOGO PERSONALIZADO (reemplaza confirm())
// ═══════════════════════════════════════════════════════════════

/**
 * @param {object} opts
 *   title, message, confirmText, cancelText,
 *   type: danger|warning|info, icon
 * @returns {Promise<boolean>}
 */
function showConfirm(opts = {}) {
    return new Promise(resolve => {
        const {
            title       = '¿Estás seguro?',
            message     = 'Esta acción no se puede deshacer.',
            confirmText = 'Confirmar',
            cancelText  = 'Cancelar',
            type        = 'danger',
        } = opts;

        const icons = { danger: 'fas fa-exclamation-triangle', warning: 'fas fa-question-circle', info: 'fas fa-info-circle' };
        const overlay = document.createElement('div');
        overlay.className = 'inv-dialog-overlay';
        overlay.innerHTML = `
            <div class="inv-dialog">
                <div class="inv-dialog-icon ${type}">
                    <i class="${icons[type] || icons.danger}"></i>
                </div>
                <h3>${title}</h3>
                <p>${message}</p>
                <div class="inv-dialog-btns">
                    <button class="inv-btn-cancel">${cancelText}</button>
                    <button class="inv-btn-confirm-${type}">${confirmText}</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);

        const close = (result) => {
            overlay.style.animation = 'invFadeIn 0.15s ease reverse';
            setTimeout(() => overlay.remove(), 150);
            resolve(result);
        };

        overlay.querySelector('.inv-btn-cancel').addEventListener('click', () => close(false));
        overlay.querySelector(`[class*="inv-btn-confirm"]`).addEventListener('click', () => close(true));
        overlay.addEventListener('click', e => { if (e.target === overlay) close(false); });

        // Teclado
        const keyHandler = (e) => {
            if (e.key === 'Enter') { document.removeEventListener('keydown', keyHandler); close(true); }
            if (e.key === 'Escape') { document.removeEventListener('keydown', keyHandler); close(false); }
        };
        document.addEventListener('keydown', keyHandler);
    });
}

// ═══════════════════════════════════════════════════════════════
//  VALIDACIONES CENTRALIZADAS
// ═══════════════════════════════════════════════════════════════

const InvValidate = {

    // ── Quitar errores de un campo ─────────────────────────────
    clearField(input) {
        if (!input) return;
        input.classList.remove('inv-input-error', 'inv-input-success');
        const errEl = document.getElementById(`inv-err-${input.id}`) || input.nextElementSibling;
        if (errEl && errEl.classList.contains('inv-field-error')) errEl.remove();
    },

    // ── Marcar campo como error ────────────────────────────────
    setError(input, message) {
        if (!input) return false;
        this.clearField(input);
        input.classList.add('inv-input-error');
        const err = document.createElement('div');
        err.className = 'inv-field-error';
        err.id = `inv-err-${input.id}`;
        err.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
        input.insertAdjacentElement('afterend', err);
        return false;
    },

    // ── Marcar campo como válido ───────────────────────────────
    setValid(input) {
        if (!input) return true;
        this.clearField(input);
        input.classList.add('inv-input-success');
        return true;
    },

    // ── Obligatorio ───────────────────────────────────────────
    required(input, label = 'Este campo') {
        const val = input?.value?.trim() ?? '';
        if (!val) return this.setError(input, `${label} es obligatorio`);
        return this.setValid(input);
    },

    // ── Mínima longitud ───────────────────────────────────────
    minLength(input, min, label = 'Este campo') {
        const val = input?.value?.trim() ?? '';
        if (val.length < min) return this.setError(input, `${label} debe tener al menos ${min} caracteres`);
        return this.setValid(input);
    },

    // ── Máxima longitud ───────────────────────────────────────
    maxLength(input, max, label = 'Este campo') {
        const val = input?.value?.trim() ?? '';
        if (val.length > max) return this.setError(input, `${label} no puede exceder ${max} caracteres`);
        return this.setValid(input);
    },

    // ── Email ─────────────────────────────────────────────────
    email(input, required = false) {
        const val = input?.value?.trim() ?? '';
        if (!val && !required) return this.setValid(input);
        if (!val && required) return this.setError(input, 'El correo electrónico es obligatorio');
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
        if (!re.test(val)) return this.setError(input, 'Formato de correo inválido (Ej: usuario@dominio.com)');
        return this.setValid(input);
    },

    // ── Teléfono venezolano ───────────────────────────────────
    // Acepta: 0412-1234567 | 04121234567 | +584121234567 | 0212-1234567
    telefono(input, required = false) {
        const val = input?.value?.trim() ?? '';
        if (!val && !required) return this.setValid(input);
        if (!val && required) return this.setError(input, 'El teléfono es obligatorio');
        const clean = val.replace(/[\s\-]/g, '');
        const re = /^(\+?58|0)(4(1[246]|2[46]|6[0-6])\d{7}|(2(12|34|43|44|57|58|60|61|63|64|65|67|68|69|74|75|76|77|78|82|84|86|87|88|93|94|95)|4(18|19|38|39|78|79|88|89))\d{7})$/;
        // Versión más flexible para Venezuela
        const reSimple = /^(\+?58|0)[2-9]\d{9}$/;
        const reMobile = /^(0(4(1[246]|2[46]|6[0-6])))\d{7}$/;
        const reLocal  = /^(0(2\d{2}|4(1[246]|2[46]|6[0-6])))\d{7}$/;
        if (!reSimple.test(clean) && !reMobile.test(clean) && !reLocal.test(clean)) {
            return this.setError(input, 'Formato inválido. Ej: 0412-1234567 o 0212-1234567');
        }
        return this.setValid(input);
    },

    // ── RIF venezolano ────────────────────────────────────────
    // Formatos: J-123456789 | V-12345678 | E-12345678 | G-12345678 | P-12345678
    rif(input, required = true) {
        const val = input?.value?.trim() ?? '';
        if (!val && !required) return this.setValid(input);
        if (!val) return this.setError(input, 'El RIF/Cédula es obligatorio');

        const clean = val.toUpperCase().replace(/\s/g, '');
        // Normalizar: J1234... → J-1234...
        const normalized = clean.replace(/^([JVEGP])-?(\d+)/, '$1-$2');
        const reRIF     = /^J-\d{9}$/;     // J para empresas (9 dígitos)
        const reCedula  = /^[VE]-\d{7,8}$/; // V o E para personas
        const reGubern  = /^G-\d{9}$/;      // G gubernamental
        const rePassp   = /^P-\d{6,9}$/;    // P pasaporte

        if (!reRIF.test(normalized) && !reCedula.test(normalized) && !reGubern.test(normalized) && !rePassp.test(normalized)) {
            return this.setError(input, 'Formato inválido. Ej: J-123456789 (empresa) o V-12345678 (persona)');
        }
        // Normalizar el valor en el input
        input.value = normalized;
        return this.setValid(input);
    },

    // ── Cédula simple (solo dígitos) ──────────────────────────
    cedula(input, required = true) {
        const val = input?.value?.trim() ?? '';
        if (!val && !required) return this.setValid(input);
        if (!val) return this.setError(input, 'La cédula es obligatoria');
        if (!/^\d{6,9}$/.test(val)) return this.setError(input, 'La cédula debe tener entre 6 y 9 dígitos numéricos');
        return this.setValid(input);
    },

    // ── Número positivo ───────────────────────────────────────
    positiveNumber(input, label = 'El valor', allowZero = true) {
        const val = parseFloat(input?.value ?? '');
        if (isNaN(val)) return this.setError(input, `${label} debe ser un número válido`);
        if (!allowZero && val <= 0) return this.setError(input, `${label} debe ser mayor a 0`);
        if (val < 0) return this.setError(input, `${label} no puede ser negativo`);
        return this.setValid(input);
    },

    // ── Entero positivo ───────────────────────────────────────
    positiveInt(input, label = 'El valor', allowZero = true) {
        const val = input?.value?.trim() ?? '';
        if (!/^\d+$/.test(val)) return this.setError(input, `${label} debe ser un número entero válido`);
        const n = parseInt(val);
        if (!allowZero && n <= 0) return this.setError(input, `${label} debe ser mayor a 0`);
        return this.setValid(input);
    },

    // ── Precio: venta >= compra ───────────────────────────────
    precioVenta(inputCompra, inputVenta) {
        const pc = parseFloat(inputCompra?.value ?? 0);
        const pv = parseFloat(inputVenta?.value ?? 0);
        if (isNaN(pv)) return this.setError(inputVenta, 'El precio de venta debe ser un número válido');
        if (pv < 0) return this.setError(inputVenta, 'El precio de venta no puede ser negativo');
        if (pv < pc) return this.setError(inputVenta, `El precio de venta (Bs ${pv}) no puede ser menor al precio de compra (Bs ${pc})`);
        return this.setValid(inputVenta);
    },

    // ── Stock: min <= actual <= max y no negativos ───────────────────────────────────────────
    stockConsistency(inpActual, inpMin, inpMax) {
        const actual = parseInt(inpActual?.value ?? 0);
        const min    = parseInt(inpMin?.value    ?? 0);
        const max    = parseInt(inpMax?.value    ?? 0);
        let ok = true;

        if (min < 0) {
            this.setError(inpMin, 'El stock mínimo no puede ser negativo');
            ok = false;
        } else if (min > max) {
            this.setError(inpMin, `El stock mínimo (${min}) no puede ser mayor al máximo (${max})`);
            ok = false;
        } else {
            this.setValid(inpMin);
        }

        if (max < 0) {
            this.setError(inpMax, 'El stock máximo no puede ser negativo');
            ok = false;
        } else if (max < min) {
            this.setError(inpMax, `El stock máximo (${max}) no puede ser menor al mínimo (${min})`);
            ok = false;
        } else if (ok) {
            this.setValid(inpMax);
        }

        if (actual < 0) {
            this.setError(inpActual, 'El stock actual no puede ser negativo');
            ok = false;
        } else {
            this.setValid(inpActual);
        }

        return ok;
    },

    // ── Fecha no futura ───────────────────────────────────────
    notFutureDate(input, label = 'La fecha') {
        const val = input?.value ?? '';
        if (!val) return this.setError(input, `${label} es obligatoria`);
        if (new Date(val) > new Date()) return this.setError(input, `${label} no puede ser futura`);
        return this.setValid(input);
    },

    // ── Fecha no pasada ───────────────────────────────────────
    notPastDate(input, label = 'La fecha') {
        const val = input?.value ?? '';
        if (!val) return this.setError(input, `${label} es obligatoria`);
        const today = new Date(); today.setHours(0,0,0,0);
        if (new Date(val) < today) return this.setError(input, `${label} no puede ser pasada`);
        return this.setValid(input);
    },

    // ── Stock disponible ──────────────────────────────────────
    stockDisponible(inputCantidad, stockActual, nombreProducto = 'el producto') {
        const cant = parseInt(inputCantidad?.value ?? 0);
        if (cant <= 0) return this.setError(inputCantidad, 'La cantidad debe ser mayor a 0');
        if (cant > stockActual) {
            return this.setError(inputCantidad, `Stock insuficiente. Disponible: ${stockActual} unidades de ${nombreProducto}`);
        }
        return this.setValid(inputCantidad);
    },

    // ── Username único (async, verifica con API) ──────────────
    async usernameUnico(input, currentUserId = null) {
        const val = input?.value?.trim() ?? '';
        if (!val) return this.setError(input, 'El nombre de usuario es obligatorio');
        if (val.length < 3) return this.setError(input, 'El usuario debe tener al menos 3 caracteres');
        if (!/^[a-zA-Z0-9_.-]{3,50}$/.test(val)) {
            return this.setError(input, 'Solo letras, números, puntos, guiones y guiones bajos');
        }
        try {
            const url = new URL('/inversiones-rojas/api/check_username.php', window.location.origin);
            url.searchParams.set('username', val);
            if (currentUserId) url.searchParams.set('exclude_id', currentUserId);
            const r = await fetch(url);
            const j = await r.json();
            if (j.exists) return this.setError(input, 'Este nombre de usuario ya está en uso');
            return this.setValid(input);
        } catch {
            return this.setValid(input); // En caso de error de red, no bloquear
        }
    },

    // ── Email único (async) ───────────────────────────────────
    async emailUnico(input, currentUserId = null) {
        if (!this.email(input, true)) return false;
        const val = input?.value?.trim() ?? '';
        try {
            const url = new URL('/inversiones-rojas/api/check_email.php', window.location.origin);
            url.searchParams.set('email', val);
            if (currentUserId) url.searchParams.set('exclude_id', currentUserId);
            const r = await fetch(url);
            const j = await r.json();
            if (j.exists) return this.setError(input, 'Este correo ya está registrado en el sistema');
            return this.setValid(input);
        } catch {
            return this.setValid(input);
        }
    },

    // ── Código de producto único (async) ──────────────────────
    async codigoUnico(input, currentProductId = null) {
        const val = input?.value?.trim() ?? '';
        if (!val) return this.setError(input, 'El código interno es obligatorio');
        try {
            const url = new URL('/inversiones-rojas/api/check_codigo.php', window.location.origin);
            url.searchParams.set('codigo', val);
            if (currentProductId) url.searchParams.set('exclude_id', currentProductId);
            const r = await fetch(url);
            const j = await r.json();
            if (j.exists) return this.setError(input, 'Este código ya existe en el inventario');
            return this.setValid(input);
        } catch {
            return this.setValid(input);
        }
    },

    // ── RIF de proveedor único (async) ────────────────────────
    async rifUnico(input, currentId = null) {
        if (!this.rif(input, true)) return false;
        const val = input?.value?.trim() ?? '';
        try {
            const url = new URL('/inversiones-rojas/api/check_rif.php', window.location.origin);
            url.searchParams.set('rif', val);
            if (currentId) url.searchParams.set('exclude_id', currentId);
            const r = await fetch(url);
            const j = await r.json();
            if (j.exists) return this.setError(input, 'Este RIF ya está registrado en el sistema');
            return this.setValid(input);
        } catch {
            return this.setValid(input);
        }
    },

    // ── Validar todos los campos de un formulario ─────────────
    validateAll(rules) {
        // rules: array de { fn, args } o resultados directos
        // Retorna true solo si todos pasan
        return rules.every(r => r === true);
    },
};

// ── Exportar globalmente ───────────────────────────────────────
window.showNotification = showNotification;
window.showConfirm      = showConfirm;
window.Toast            = Toast;
window.InvValidate      = InvValidate;