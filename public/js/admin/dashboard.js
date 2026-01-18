// Scripts extraídos de Dashboard.php
// Fullscreen y chart initialization
document.addEventListener('DOMContentLoaded', function() {
    // Fullscreen button
    try {
        const fullscreenBtn = document.getElementById('fullscreenBtn');
        if (fullscreenBtn) fullscreenBtn.addEventListener('click', function() { if (!document.fullscreenElement) { document.documentElement.requestFullscreen(); } else { if (document.exitFullscreen) document.exitFullscreen(); } });
    } catch(e) { console.error(e); }

    // Chart initialization
    try {
        const data = window.DASHBOARD_DATA || {};
        const meses = data.meses || [];
        const datosGrafico = data.datosGrafico || [];
        const canvas = document.getElementById('salesChart');
        if (canvas && typeof Chart !== 'undefined') {
            const ctx = canvas.getContext('2d');
            new Chart(ctx, { type: 'line', data: { labels: meses, datasets: [{ label: 'Ventas ($)', data: datosGrafico, borderColor: '#1F9166', backgroundColor: 'rgba(31, 145, 102, 0.1)', borderWidth: 2, fill: true, tension: 0.4 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { drawBorder: false } }, x: { grid: { display: false } } } } });
        }
    } catch(e) { console.error('chart init', e); }

    // Settings modal (abrir/cerrar y botón Gestión de Usuarios)
    try {
        const settingsBtn = document.getElementById('settingsBtn');
        const settingsModal = document.getElementById('settingsModal');
        const settingsBackdrop = document.getElementById('settingsModalBackdrop');
        const settingsClose = document.getElementById('settingsModalClose');
        if (settingsBtn && settingsModal) {
            function openSettings() {
                settingsModal.classList.add('open');
                settingsModal.setAttribute('aria-hidden', 'false');
            }
            function closeSettings() {
                settingsModal.classList.remove('open');
                settingsModal.setAttribute('aria-hidden', 'true');
            }
            settingsBtn.addEventListener('click', function(e){ e.stopPropagation(); openSettings(); });
            if (settingsClose) settingsClose.addEventListener('click', closeSettings);
            if (settingsBackdrop) settingsBackdrop.addEventListener('click', closeSettings);
            document.addEventListener('keydown', function(ev){ if (ev.key === 'Escape') closeSettings(); });
            // Close if click outside modal-content
            document.addEventListener('click', function(ev){
                if (settingsModal.classList.contains('open') && !ev.target.closest('.modal-content') && !ev.target.closest('#settingsBtn')) {
                    closeSettings();
                }
            });
        }
    } catch(err) { console.error('settings/init', err); }
});