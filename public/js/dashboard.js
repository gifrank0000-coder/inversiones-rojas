// Scripts extraídos de Dashboard.php
// Toggle sidebar
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.querySelector('.admin-sidebar').classList.toggle('collapsed');
        });
    }

    // Fullscreen toggle
    const fullscreenBtn = document.getElementById('fullscreenBtn');
    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', function() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        });
    }

    // Sales Chart con datos reales (espera datos en window.DASHBOARD_DATA)
    const data = window.DASHBOARD_DATA || {};
    const meses = data.meses || [];
    const datosGrafico = data.datosGrafico || [];

    const canvas = document.getElementById('salesChart');
    if (canvas && typeof Chart !== 'undefined') {
        const ctx = canvas.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: meses,
                datasets: [{
                    label: 'Ventas ($)',
                    data: datosGrafico,
                    borderColor: '#1F9166',
                    backgroundColor: 'rgba(31, 145, 102, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { drawBorder: false } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
});