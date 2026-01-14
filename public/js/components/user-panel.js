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
        userToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            userPanel.classList.toggle('active');
            console.log('[user-panel] toggled active (now)', userPanel.classList.contains('active'));
        });

        // Soporte de teclado: Enter / Space togglean el panel
        userToggle.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar') {
                e.preventDefault();
                e.stopPropagation();
                userPanel.classList.toggle('active');
                console.log('[user-panel] toggled (keyboard) active (now)', userPanel.classList.contains('active'));
            }
        });

        document.addEventListener('click', function() {
            if (userPanel.classList.contains('active')) console.debug('[user-panel] closing panel due to outside click');
            userPanel.classList.remove('active');
        });

        const userDropdown = userPanel.querySelector('.user-dropdown');
        if (userDropdown) userDropdown.addEventListener('click', function(e) { e.stopPropagation(); });

        // Marcar como inicializado
        userToggle._userPanelInitialized = true;
        window.USER_PANEL_COMPONENT_LOADED = true;
        return true;
    } catch (err) {
        console.error('[user-panel] init error', err);
        return false;
    }
}

// Si el DOM ya está listo, inicializa inmediatamente, si no espera DOMContentLoaded
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    // Ejecutar en el siguiente tick para asegurar que los elementos están montados
    setTimeout(initUserPanel, 0);
} else {
    document.addEventListener('DOMContentLoaded', initUserPanel);
}