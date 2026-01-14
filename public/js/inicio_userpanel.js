// Script para el panel de usuario extraído de inicio.php
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Script cargado - Buscando panel de usuario...');
    
    const userPanel = document.getElementById('userPanel');
    const userToggle = document.getElementById('userToggle');
    
    if (userToggle && userPanel) {
        console.log('✅ Panel de usuario encontrado');
        
        userToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            userPanel.classList.toggle('active');
            console.log('🔄 Panel toggled:', userPanel.classList.contains('active'));
        });
        
        document.addEventListener('click', function() {
            userPanel.classList.remove('active');
        });
        
        const userDropdown = userPanel.querySelector('.user-dropdown');
        if (userDropdown) {
            userDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    } else {
        console.log('❌ Panel de usuario NO encontrado');
        console.log('userToggle:', userToggle);
        console.log('userPanel:', userPanel);
    }
});

// Funciones globales
function openSettings() {
    alert('Configuración - Esta funcionalidad estará disponible pronto');
    const userPanel = document.getElementById('userPanel');
    if (userPanel) userPanel.classList.remove('active');
}

function openProfile() {
    alert('Perfil de usuario - Esta funcionalidad estará disponible pronto');
    const userPanel = document.getElementById('userPanel');
    if (userPanel) userPanel.classList.remove('active');
}

function openOrders() {
    alert('Historial de pedidos - Esta funcionalidad estará disponible pronto');
    const userPanel = document.getElementById('userPanel');
    if (userPanel) userPanel.classList.remove('active');
}