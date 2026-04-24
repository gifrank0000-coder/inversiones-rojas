<?php
// ============================================================
// notificaciones_widget.php  →  app/views/partials/notificaciones_widget.php
// Campanita de notificaciones para el header del panel admin/vendedor.
// Incluir con: <?php require __DIR__ . '/notificaciones_widget.php'; ?>
// ============================================================
if (!isset($_SESSION['user_id'])) return;
?>
<style>
.notif-bell-wrapper {
    position: relative; display: inline-block;
}
.notif-bell-btn {
    background: none; border: none; cursor: pointer;
    color: inherit; font-size: 1.2rem; padding: 6px 10px;
    position: relative; transition: color 0.2s;
}
.notif-bell-btn:hover { color: #1F9166; }
.notif-bell-badge {
    position: absolute; top: 2px; right: 4px;
    background: #e74c3c; color: white;
    font-size: 10px; font-weight: 700;
    width: 18px; height: 18px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    display: none; /* se muestra via JS */
    border: 2px solid white;
}
.notif-dropdown {
    display: none; position: absolute; right: 0; top: calc(100% + 6px);
    width: 320px; background: white; border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15); z-index: 9999;
    animation: dropFadeIn 0.2s ease;
}
.notif-dropdown.open { display: block; }
@keyframes dropFadeIn {
    from { opacity:0; transform:translateY(-6px); }
    to   { opacity:1; transform:translateY(0); }
}
.notif-drop-header {
    padding: 12px 16px; border-bottom: 1px solid #f0f0f0;
    display: flex; justify-content: space-between; align-items: center;
}
.notif-drop-header h4 { font-size: 14px; font-weight: 700; color: #333; }
.notif-drop-header a  { font-size: 12px; color: #1F9166; text-decoration: none; }
.notif-drop-header a:hover { text-decoration: underline; }
.notif-drop-list { max-height: 300px; overflow-y: auto; }
.notif-drop-item {
    padding: 11px 16px; border-bottom: 1px solid #f8f8f8;
    cursor: pointer; transition: background 0.15s;
    display: flex; gap: 10px; align-items: flex-start;
}
.notif-drop-item:hover { background: #f8f9fa; }
.notif-drop-item.no-leida { background: #f0f9f4; border-left: 3px solid #1F9166; }
.notif-drop-icon {
    width: 30px; height: 30px; border-radius: 50%;
    background: #e8f6f1; color: #1F9166;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; flex-shrink: 0;
}
.notif-drop-titulo  { font-size: 12px; font-weight: 600; color: #333; }
.notif-drop-hace    { font-size: 11px; color: #aaa; margin-top: 2px; }
.notif-drop-empty   { padding: 20px; text-align: center; color: #aaa; font-size: 13px; }
.notif-drop-footer  {
    padding: 10px 16px; border-top: 1px solid #f0f0f0; text-align: center;
}
.notif-drop-footer a {
    font-size: 13px; color: #1F9166; text-decoration: none; font-weight: 600;
}
.notif-drop-footer a:hover { text-decoration: underline; }
</style>

<div class="notif-bell-wrapper" id="notifBellWrapper">
    <button class="notif-bell-btn" id="notifBellBtn" onclick="toggleNotifDropdown()" title="Notificaciones">
        <i class="fas fa-bell"></i>
        <span class="notif-bell-badge" id="notifBellBadge">0</span>
    </button>

    <div class="notif-dropdown" id="notifDropdown">
        <div class="notif-drop-header">
            <h4><i class="fas fa-bell" style="color:#1F9166;margin-right:5px;"></i>Notificaciones</h4>
            <a href="#" onclick="marcarTodasLeidasHeader(event)">Marcar todas leídas</a>
        </div>
        <div class="notif-drop-list" id="notifDropList">
            <div class="notif-drop-empty">Cargando...</div>
        </div>
        <div class="notif-drop-footer">
            <a href="<?php echo BASE_URL; ?>/app/views/pages/panel_notificaciones.php">
                Ver todas las notificaciones &rarr;
            </a>
        </div>
    </div>
</div>

<script>
(function() {
    const BASE_NOTIF = '<?php echo BASE_URL; ?>';

    function toggleNotifDropdown() {
        const d = document.getElementById('notifDropdown');
        d.classList.toggle('open');
        if (d.classList.contains('open')) cargarNotifsHeader();
    }

    // Cerrar al clicar fuera
    document.addEventListener('click', function(e) {
        const w = document.getElementById('notifBellWrapper');
        if (w && !w.contains(e.target)) {
            document.getElementById('notifDropdown').classList.remove('open');
        }
    });

    async function cargarNotifsHeader() {
        try {
            const r = await fetch(`${BASE_NOTIF}/api/get_notificaciones.php?accion=listar`);
            const j = await r.json();

            // Badge
            const badge = document.getElementById('notifBellBadge');
            if (j.total_no_leidas > 0) {
                badge.textContent = j.total_no_leidas > 99 ? '99+' : j.total_no_leidas;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }

            const lista = document.getElementById('notifDropList');
            if (!j.notificaciones || j.notificaciones.length === 0) {
                lista.innerHTML = '<div class="notif-drop-empty"><i class="fas fa-bell-slash"></i><br>Sin notificaciones</div>';
                return;
            }

            // Mostrar máximo 6 en el dropdown
            lista.innerHTML = j.notificaciones.slice(0, 6).map(n => `
                <div class="notif-drop-item ${n.leida ? '' : 'no-leida'}"
                     onclick="marcarLeidaHeader(${n.id}, this)">
                    <div class="notif-drop-icon">
                        <i class="fas ${n.tipo === 'PEDIDO_ASIGNADO' ? 'fa-user-plus' : 'fa-shopping-bag'}"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="notif-drop-titulo" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${n.titulo}</div>
                        <div class="notif-drop-hace">${n.hace}${n.codigo_pedido ? ' • '+n.codigo_pedido : ''}</div>
                    </div>
                    ${!n.leida ? '<span style="width:7px;height:7px;border-radius:50%;background:#1F9166;flex-shrink:0;margin-top:5px;"></span>' : ''}
                </div>
            `).join('');
        } catch(e) {
            console.error('Error notificaciones:', e);
        }
    }

    async function marcarLeidaHeader(id, el) {
        el.classList.remove('no-leida');
        await fetch(`${BASE_NOTIF}/api/get_notificaciones.php?accion=marcar_leida&id=${id}`);
        cargarNotifsHeader();
    }

    async function marcarTodasLeidasHeader(e) {
        e.preventDefault();
        await fetch(`${BASE_NOTIF}/api/get_notificaciones.php?accion=marcar_todas`);
        cargarNotifsHeader();
    }

    // Exponer para uso externo
    window.marcarLeidaHeader    = marcarLeidaHeader;
    window.marcarTodasLeidasHeader = marcarTodasLeidasHeader;

    // Cargar badge al iniciar
    cargarNotifsHeader();
    // Refrescar cada 30 segundos
    setInterval(cargarNotifsHeader, 30000);
})();
</script>