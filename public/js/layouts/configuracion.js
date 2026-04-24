// Notificar al dashboard que estamos en módulo de configuración
(function() {
    try {
        const payload = {
            irModuleHeader: true,
            title: 'Configuración',
            breadcrumb: ['Inicio', 'Configuración']
        };
        window.parent.postMessage(payload, '*');
    } catch (e) {
        console.log('No se pudo comunicar con el padre');
    }
})();

// Configurar base URL
window.APP_BASE = window.APP_BASE || '';

// Funciones auxiliares
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close">&times;</button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
    
    // Cerrar manualmente
    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 300);
    });
}

function confirmAction(message) {
    return new Promise((resolve) => {
        const confirmModal = document.createElement('div');
        confirmModal.className = 'confirm-modal-overlay';
        confirmModal.innerHTML = `
            <div class="confirm-modal">
                <div class="confirm-modal-header">
                    <h4><i class="fas fa-exclamation-triangle"></i> Confirmar acción</h4>
                </div>
                <div class="confirm-modal-body">
                    <p>${message}</p>
                </div>
                <div class="confirm-modal-footer">
                    <button class="btn btn-outline" id="confirmCancel">Cancelar</button>
                    <button class="btn btn-primary" id="confirmOk">Aceptar</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(confirmModal);
        
        document.getElementById('confirmCancel').addEventListener('click', () => {
            confirmModal.remove();
            resolve(false);
        });
        
        document.getElementById('confirmOk').addEventListener('click', () => {
            confirmModal.remove();
            resolve(true);
        });
        
        // Cerrar al hacer clic fuera
        confirmModal.addEventListener('click', (e) => {
            if (e.target === confirmModal) {
                confirmModal.remove();
                resolve(false);
            }
        });
    });
}

function updateUserRowStatus(row, newStatus) {
    // Actualizar estado en la celda
    const statusCell = row.querySelector('.status-active, .status-inactive');
    if (statusCell) {
        statusCell.className = newStatus ? 'status-active' : 'status-inactive';
        statusCell.textContent = newStatus ? 'Activo' : 'Inactivo';
    }
    
    // Actualizar botón de toggle
    const toggleBtn = row.querySelector('.btn-toggle-user');
    if (toggleBtn) {
        toggleBtn.setAttribute('data-estado', newStatus ? '1' : '0');
        toggleBtn.innerHTML = newStatus 
            ? '<i class="fas fa-user-slash"></i>' 
            : '<i class="fas fa-user-check"></i>';
        toggleBtn.title = newStatus ? 'Desactivar usuario' : 'Activar usuario';
    }
}

function removeUserRow(row) {
    row.style.opacity = '0.5';
    row.style.transition = 'opacity 0.3s';
    
    setTimeout(() => {
        row.remove();
        
        // Si no quedan filas, mostrar mensaje
        const tbody = document.querySelector('.users-table tbody');
        if (tbody && tbody.children.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" style="text-align: center; padding: 40px;">
                        <div style="color: #666;">
                            <i class="fas fa-users-slash" style="font-size: 48px; margin-bottom: 20px;"></i>
                            <h3>No hay usuarios registrados</h3>
                            <p>Haga clic en "Nuevo Usuario" para agregar usuarios al sistema</p>
                        </div>
                    </td>
                </tr>
            `;
        }
    }, 300);
}

document.addEventListener('DOMContentLoaded', function() {
    // 1. Control de tabs
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    // Activar tab según URL
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab') || 'general';
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Actualizar URL sin recargar
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.pushState({}, '', url);
            
            // Remover active de todos
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Agregar active al seleccionado
            this.classList.add('active');
            const targetTab = document.getElementById(`tab-${tabId}`);
            if (targetTab) targetTab.classList.add('active');
        });
    });
    
    // Activar tab inicial
    const initialTabBtn = document.querySelector(`.tab-btn[data-tab="${activeTab}"]`);
    const initialTabContent = document.getElementById(`tab-${activeTab}`);
    if (initialTabBtn && initialTabContent) {
        tabBtns.forEach(b => b.classList.remove('active'));
        tabContents.forEach(c => c.classList.remove('active'));
        initialTabBtn.classList.add('active');
        initialTabContent.classList.add('active');
    }
    
    // 2. Botón "Nuevo Usuario"
    const newUserBtn = document.getElementById('newUserBtn');
    const newUserModal = document.getElementById('newUserModal');
    
    if (newUserBtn && newUserModal) {
        // Evitar envío nativo del formulario (prevenir fallback a configuracion.php)
        const newUserForm = document.getElementById('newUserForm');
        if (newUserForm) {
            newUserForm.addEventListener('submit', function(e) {
                e.preventDefault();
            });
        }
        newUserBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Limpiar formulario
            const form = document.getElementById('newUserForm');
            if (form) {
                form.reset();
                document.getElementById('user_id').value = '';
            }

            // Asegurar que los campos de contraseña estén visibles y requeridos al crear
            const pwd = document.getElementById('userPassword');
            const pwdc = document.getElementById('userConfirmPassword');
            if (pwd) {
                const g = pwd.closest('.form-group'); 
                if (g) g.style.display = '';
                pwd.setAttribute('required', ''); 
                pwd.setAttribute('minlength', '8');
                pwd.value = '';
            }
            if (pwdc) {
                const gc = pwdc.closest('.form-group'); 
                if (gc) gc.style.display = '';
                pwdc.setAttribute('required', ''); 
                pwdc.setAttribute('minlength', '8');
                pwdc.value = '';
            }
            
            // Ajustar cabecera y texto del botón
            const hdr = newUserModal.querySelector('.modal-header h3'); 
            if (hdr) hdr.innerHTML = '<i class="fas fa-user-plus"></i> Crear Nuevo Usuario';
            const saveBtn = document.getElementById('saveNewUser'); 
            if (saveBtn) saveBtn.innerText = 'Crear Usuario';
            
            // Mostrar modal
            newUserModal.classList.add('active');
            
            // Enfocar primer campo
            setTimeout(() => {
                const firstInput = document.getElementById('userUsername');
                if (firstInput) firstInput.focus();
            }, 100);
        });
        
        // Cerrar modal
        const closeNewUserModal = () => {
            newUserModal.classList.remove('active');
        };
        
        document.getElementById('closeNewUserModal').addEventListener('click', closeNewUserModal);
        document.getElementById('cancelNewUser').addEventListener('click', closeNewUserModal);
        
        // Cerrar al hacer clic fuera
        newUserModal.addEventListener('click', function(e) {
            if (e.target === newUserModal) {
                closeNewUserModal();
            }
        });
        
        // Guardar usuario (crear o actualizar)
        document.getElementById('saveNewUser').addEventListener('click', async function() {
            const form = document.getElementById('newUserForm');
            const fd = new FormData(form);
            const userId = document.getElementById('user_id').value;

            // Validaciones básicas
            const username = (fd.get('username') || '').toString().trim();
            const email = (fd.get('email') || '').toString().trim();
            const fullname = (fd.get('nombre_completo') || '').toString().trim();
            const password = (fd.get('password') || '').toString();
            const confirmPassword = (fd.get('confirm_password') || '').toString();
            
            if (!username || !email || !fullname) { 
                showNotification('Complete los campos obligatorios', 'error');
                return; 
            }
            
            if (userId === '' && (!password || password.length < 8)) { 
                showNotification('La contraseña debe tener al menos 8 caracteres', 'error');
                return; 
            }
            
            if (password && password !== confirmPassword) { 
                showNotification('Las contraseñas no coinciden', 'error');
                return; 
            }

            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            this.disabled = true;

            try {
                // SIEMPRE usar user_action.php - NUNCA process_register.php
        const base = (typeof window.APP_BASE !== 'undefined' ? (window.APP_BASE || '').replace(/\/$/, '') : (location.origin + '/inversiones-rojas'));
        let url, method, body;

        // Usar siempre POST y enviar FormData
        method = 'POST';
        if (userId) {
            // Actualizar usuario existente
            url = `${base}/app/views/layouts/user_action.php`;
            fd.append('action', 'update');
            fd.append('id', userId);
        } else {
            // Crear nuevo usuario - usar user_action.php
            url = `${base}/app/views/layouts/user_action.php`;
            fd.append('action', 'create');

            // Asegurar que el rol_id esté incluido (obtener desde el select si es necesario)
            if (!fd.get('rol_id')) {
                const roleEl = document.getElementById('userRole');
                if (roleEl) fd.append('rol_id', roleEl.value);
            }
        }

        body = fd;
        const response = await fetch(url, {
            method: method,
            body: body,
            credentials: 'same-origin'
        });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification(data.message || 'Operación exitosa', 'success');
                    
                    // Cerrar modal y recargar lista
                    newUserModal.classList.remove('active');
                    
                    // Si estamos en la pestaña de usuarios, recargar la tabla
                    if (activeTab === 'users') {
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                } else {
                    showNotification(data.message || 'Error desconocido', 'error');
                }
            } catch (err) { 
                console.error(err); 
                showNotification('Error en la conexión con el servidor', 'error');
            } finally { 
                this.innerHTML = originalText; 
                this.disabled = false; 
            }
        });
    }
    
    // 3. Botón "Nuevo Rol"
    const newRoleBtn = document.getElementById('newRoleBtn');
    const newRoleModal = document.getElementById('newRoleModal');
    
    if (newRoleBtn && newRoleModal) {
        newRoleBtn.addEventListener('click', function() {
            newRoleModal.classList.add('active');
        });
        
        // Cerrar modal
        const closeRoleModal = () => {
            newRoleModal.classList.remove('active');
            document.getElementById('newRoleForm').reset();
        };
        
        document.getElementById('closeRoleModal').addEventListener('click', closeRoleModal);
        document.getElementById('cancelRoleModal').addEventListener('click', closeRoleModal);
        
        // Cerrar al hacer clic fuera
        newRoleModal.addEventListener('click', function(e) {
            if (e.target === newRoleModal) {
                closeRoleModal();
            }
        });
        
        // Guardar rol
        document.getElementById('saveRole').addEventListener('click', function() {
            const roleName = document.getElementById('roleName').value.trim();
            
            if (!roleName) {
                alert('Por favor ingrese el nombre del rol');
                return;
            }
            
            alert(`Rol "${roleName}" creado exitosamente`);
            closeRoleModal();
        });
    }
    
    // 4. Botones de acción en la tabla de usuarios
    document.addEventListener('click', async function(e) {
        // Editar usuario
        if (e.target.closest('.btn-edit-user')) {
            const btn = e.target.closest('.btn-edit-user');
            const userId = btn.getAttribute('data-id');
            if (!userId) return;
            
            // Obtener datos por AJAX
            const base = (typeof window.APP_BASE !== 'undefined' ? (window.APP_BASE || '').replace(/\/$/, '') : (location.origin + '/inversiones-rojas'));
            
            try {
                const response = await fetch(`${base}/tests/user_api.php?id=${encodeURIComponent(userId)}`);
                const data = await response.json();
                
                if (!data.success) { 
                    showNotification('No se pudo obtener información del usuario', 'error');
                    return; 
                }
                
                const u = data.user;
                
                // Llenar modal
                document.getElementById('user_id').value = u.id || '';
                document.getElementById('userUsername').value = u.username || '';
                document.getElementById('userEmail').value = u.email || '';
                document.getElementById('userFullname').value = u.nombre_completo || '';
                
                const ced = document.getElementById('userCedula'); 
                if (ced) ced.value = (u.cedula_rif || '');
                
                const phone = document.getElementById('userPhone'); 
                if (phone) phone.value = (u.telefono_principal || '');
                
                // Seleccionar rol si existe
                const roleEl = document.getElementById('userRole');
                if (roleEl && u.rol_id) { 
                    roleEl.value = u.rol_id; 
                }
                
                // Limpiar password fields y ocultarlos (se cambia por otro flujo)
                const pwd = document.getElementById('userPassword'); 
                if (pwd) { 
                    pwd.value = ''; 
                    const pg = pwd.closest('.form-group'); 
                    if (pg) pg.style.display = 'none'; 
                    pwd.removeAttribute('required'); 
                    pwd.removeAttribute('minlength'); 
                }
                
                const pwdc = document.getElementById('userConfirmPassword'); 
                if (pwdc) { 
                    pwdc.value = ''; 
                    const pcg = pwdc.closest('.form-group'); 
                    if (pcg) pcg.style.display = 'none'; 
                    pwdc.removeAttribute('required'); 
                    pwdc.removeAttribute('minlength'); 
                }
                
                // Cambiar cabecera y texto del botón
                const hdr = newUserModal.querySelector('.modal-header h3'); 
                if (hdr) hdr.innerHTML = '<i class="fas fa-user-edit"></i> Editar Usuario';
                const saveBtn = document.getElementById('saveNewUser'); 
                if (saveBtn) saveBtn.innerText = 'Guardar cambios';
                
                // Mostrar modal
                newUserModal.classList.add('active');
                
            } catch (err) { 
                console.error(err); 
                showNotification('Error al cargar datos del usuario', 'error');
            }
        }
        
        // Cambiar estado (habilitar / inhabilitar) - MEJORADO
        if (e.target.closest('.btn-toggle-user')) {
            const btn = e.target.closest('.btn-toggle-user');
            const userId = btn.getAttribute('data-id');
            const currentStatus = btn.getAttribute('data-estado') === '1';
            const userName = btn.getAttribute('data-username') || `ID: ${userId}`;
            
            if (!userId) return;

            const action = currentStatus ? 'desactivar' : 'activar';
            const message = currentStatus 
                ? `¿Está seguro de desactivar al usuario "${userName}"? El usuario no podrá acceder al sistema.`
                : `¿Está seguro de activar al usuario "${userName}"? El usuario podrá acceder al sistema.`;
            
            const confirmed = await confirmAction(message);
            if (!confirmed) return;

            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'toggle_status');
                formData.append('id', userId);
                
                const base = (typeof window.APP_BASE !== 'undefined' ? (window.APP_BASE || '').replace(/\/$/, '') : (location.origin + '/inversiones-rojas'));
                const response = await fetch(`${base}/app/views/layouts/user_action.php`, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification(data.message || 'Estado cambiado exitosamente', 'success');
                    
                    // Actualizar la fila en la tabla sin recargar
                    const row = btn.closest('tr');
                    if (row) {
                        updateUserRowStatus(row, data.new_status);
                    }
                } else {
                    showNotification(data.message || 'Error al cambiar estado', 'error');
                    btn.innerHTML = originalHTML;
                }
            } catch (err) {
                console.error('Error:', err);
                showNotification('Error de conexión con el servidor', 'error');
                btn.innerHTML = originalHTML;
            } finally {
                btn.disabled = false;
            }
        }

        // Eliminar usuario - MEJORADO
        if (e.target.closest('.btn-delete-user')) {
            const btn = e.target.closest('.btn-delete-user');
            const userId = btn.getAttribute('data-id');
            const userName = btn.getAttribute('data-username') || `ID: ${userId}`;
            
            if (!userId) return;

            const confirmed = await confirmAction(
                `¿Está seguro de eliminar al usuario "${userName}"? Esta acción es irreversible y eliminará todos los datos asociados.`
            );
            
            if (!confirmed) return;

            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', userId);
                
                const base = (typeof window.APP_BASE !== 'undefined' ? (window.APP_BASE || '').replace(/\/$/, '') : (location.origin + '/inversiones-rojas'));
                const response = await fetch(`${base}/app/views/layouts/user_action.php`, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification(data.message || 'Usuario eliminado exitosamente', 'success');
                    
                    // Remover la fila de la tabla sin recargar
                    const row = btn.closest('tr');
                    if (row) {
                        removeUserRow(row);
                    }
                } else {
                    showNotification(data.message || 'Error al eliminar usuario', 'error');
                    btn.innerHTML = originalHTML;
                }
            } catch (err) {
                console.error('Error:', err);
                showNotification('Error de conexión con el servidor', 'error');
                btn.innerHTML = originalHTML;
            } finally {
                btn.disabled = false;
            }
        }
    });
    

    
    // 6. Buscador en tiempo real
    const searchInput = document.querySelector('input[name="q"]');
    if (searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            
            searchTimeout = setTimeout(() => {
                document.getElementById('usersFilterForm').submit();
            }, 500);
        });
    }
});

// Agregar CSS para notificaciones y confirmaciones si no existe
if (!document.querySelector('#configuracion-css-extra')) {
    const style = document.createElement('style');
    style.id = 'configuracion-css-extra';
    style.textContent = `
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-width: 300px;
            max-width: 400px;
            z-index: 9999;
            animation: slideIn 0.3s ease-out;
            border-left: 4px solid #28a745;
        }
        
        .notification.error {
            border-left-color: #dc3545;
        }
        
        .notification.warning {
            border-left-color: #ffc107;
        }
        
        .notification.info {
            border-left-color: #17a2b8;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .notification.fade-out {
            animation: slideOut 0.3s ease-in forwards;
        }
        
        @keyframes slideOut {
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }
        
        .notification-content i {
            font-size: 18px;
        }
        
        .notification.success .notification-content i {
            color: #28a745;
        }
        
        .notification.error .notification-content i {
            color: #dc3545;
        }
        
        .notification-close {
            background: none;
            border: none;
            color: #666;
            font-size: 20px;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .notification-close:hover {
            background: #f8f9fa;
        }
        
        /* Confirm modal */
        .confirm-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .confirm-modal {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            animation: scaleIn 0.2s ease-out;
        }
        
        @keyframes scaleIn {
            from {
                transform: scale(0.9);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .confirm-modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #eaeaea;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px 12px 0 0;
        }
        
        .confirm-modal-header h4 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
        }
        
        .confirm-modal-body {
            padding: 24px;
            color: #333;
            line-height: 1.5;
        }
        
        .confirm-modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #eaeaea;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
    `;
    document.head.appendChild(style);
}

// =================== FUNCIONES PARA INTEGRACIONES ====================

// Manejar envío del formulario de integraciones
document.addEventListener('DOMContentLoaded', function() {
    const integrationsForm = document.getElementById('integrationsForm');
    if (integrationsForm) {
        integrationsForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            submitBtn.disabled = true;
            
            try {
                const formData = new FormData(this);
                const base = (typeof window.APP_BASE !== 'undefined' ? (window.APP_BASE || '').replace(/\/$/, '') : (location.origin + '/inversiones-rojas'));
                const response = await fetch(`${base}/api/update_integrations.php`, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification(data.message || 'Configuración guardada exitosamente', 'success');
                } else {
                    showNotification(data.message || 'Error al guardar configuración', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error de conexión con el servidor', 'error');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
    }
});

// Función para probar integraciones
async function testIntegrations() {
    const confirmed = await confirmAction('¿Desea probar todas las integraciones configuradas? Se enviarán mensajes de prueba.');
    if (!confirmed) return;
    
    const testBtn = document.querySelector('button[onclick="testIntegrations()"]');
    const originalText = testBtn.innerHTML;
    testBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Probando...';
    testBtn.disabled = true;
    
    try {
        const base = (typeof window.APP_BASE !== 'undefined' ? (window.APP_BASE || '').replace(/\/$/, '') : (location.origin + '/inversiones-rojas'));
        const response = await fetch(`${base}/api/test_integrations.php`, {
            method: 'POST',
            credentials: 'same-origin'
        });
        
        const data = await response.json();
        
        if (data.success) {
            let message = 'Pruebas completadas:\n';
            if (data.results) {
                Object.entries(data.results).forEach(([integration, result]) => {
                    message += `${integration}: ${result ? '✅ OK' : '❌ Falló'}\n`;
                });
            }
            showNotification(message, 'success');
        } else {
            showNotification(data.message || 'Error al probar integraciones', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión con el servidor', 'error');
    } finally {
        testBtn.innerHTML = originalText;
        testBtn.disabled = false;
    }
}

// =================== MANEJO DE PESTAÑAS ====================

document.addEventListener('DOMContentLoaded', function() {
    // Manejar cambio de pestañas
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            
            // Remover clase active de todos los botones y contenidos
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Agregar clase active al botón clickeado y contenido correspondiente
            this.classList.add('active');
            const targetContent = document.getElementById('tab-' + tabName);
            if (targetContent) {
                targetContent.classList.add('active');
            }
            
            // Actualizar URL con el parámetro tab
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.pushState({}, '', url);
        });
    });
    
    // Activar pestaña desde URL
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab') || 'general';
    const activeBtn = document.querySelector(`.tab-btn[data-tab="${activeTab}"]`);
    if (activeBtn) {
        activeBtn.click();
    }
});