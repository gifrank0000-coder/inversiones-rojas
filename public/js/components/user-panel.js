// Copia de inicio_userpanel.js a components/user-panel.js
/**
 * Inicializa el user panel de forma idempotente.
 */
function initUserPanel() {
    try {
        console.log('[user-panel] initUserPanel running');
        const userPanel = document.getElementById('userPanel');
        const userToggle = document.getElementById('userToggle');
        if (!userPanel || !userToggle) {
            console.log('[user-panel] Missing elements: ', { userToggle: !!userToggle, userPanel: !!userPanel });
            return false;
        }

        // Evitar doble-binding
        if (userToggle._userPanelInitialized) {
            console.log('[user-panel] already initialized on this toggle');
            return true;
        }

        console.log('[user-panel] Elements found: userToggle, userPanel');
        
        // Manejador de click para el toggle
        function handleToggleClick(e) {
            e.stopPropagation();
            userPanel.classList.toggle('active');
            console.log('[user-panel] toggled active (now)', userPanel.classList.contains('active'));
        }
        
        userToggle.addEventListener('click', handleToggleClick);

        // Soporte de teclado: Enter / Space togglean el panel
        userToggle.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar') {
                e.preventDefault();
                e.stopPropagation();
                userPanel.classList.toggle('active');
                console.log('[user-panel] toggled (keyboard) active (now)', userPanel.classList.contains('active'));
            }
        });

        // Manejador de click global para cerrar el panel
        function handleDocumentClick() {
            if (userPanel.classList.contains('active')) {
                console.debug('[user-panel] closing panel due to outside click');
                userPanel.classList.remove('active');
            }
        }
        
        document.addEventListener('click', handleDocumentClick);

        // Evitar que clicks dentro del dropdown cierren el panel
        const userDropdown = userPanel.querySelector('.user-dropdown');
        if (userDropdown) {
            userDropdown.addEventListener('click', function(e) { 
                e.stopPropagation(); 
            });
            
            // Cerrar panel al clickear un item del menú
            userDropdown.querySelectorAll('.dropdown-item').forEach(item => {
                item.addEventListener('click', function() {
                    userPanel.classList.remove('active');
                });
            });
        }

        // Marcar como inicializado
        userToggle._userPanelInitialized = true;
        window.USER_PANEL_COMPONENT_LOADED = true;
        return true;
    } catch (err) {
        console.error('[user-panel] init error', err);
        return false;
    }
}

// Reintentor: si los elementos no están disponibles, reintentar
let retryCount = 0;
const maxRetries = 10;

function initUserPanelWithRetry() {
    if (initUserPanel()) {
        console.log('[user-panel] Initialization successful');
        retryCount = 0;
    } else if (retryCount < maxRetries) {
        retryCount++;
        console.log(`[user-panel] Retry ${retryCount}/${maxRetries} in 100ms`);
        setTimeout(initUserPanelWithRetry, 100);
    } else {
        console.warn('[user-panel] Failed to initialize after retries');
    }
}

// Si el DOM ya está listo, inicializa inmediatamente, si no espera DOMContentLoaded
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    // Ejecutar en el siguiente tick para asegurar que los elementos están montados
    setTimeout(initUserPanelWithRetry, 0);
} else {
    document.addEventListener('DOMContentLoaded', initUserPanelWithRetry);
}