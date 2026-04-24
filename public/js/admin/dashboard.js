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
        const salesChartData = data.salesChartData || {
            '7': { labels: [], data: [] },
            '30': { labels: [], data: [] },
            '12': { labels: [], data: [] }
        };
        const defaultRange = data.defaultSalesRange || '12';
        const canvas = document.getElementById('salesChart');
        let salesChartInstance = null;

        const chartOptions = {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Ventas ($)',
                    data: [],
                    borderColor: '#1F9166',
                    backgroundColor: 'rgba(31, 145, 102, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { drawBorder: false }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        };

        function updateSalesChart(range) {
            const selected = salesChartData[range] ? range : defaultRange;
            const chartData = salesChartData[selected];
            if (!canvas || typeof Chart === 'undefined') return;
            if (!salesChartInstance) {
                chartOptions.data.labels = chartData.labels;
                chartOptions.data.datasets[0].data = chartData.data;
                salesChartInstance = new Chart(canvas.getContext('2d'), chartOptions);
            } else {
                salesChartInstance.data.labels = chartData.labels;
                salesChartInstance.data.datasets[0].data = chartData.data;
                salesChartInstance.update();
            }
        }

        const chartFilter = document.getElementById('salesRangeFilter');
        if (chartFilter) {
            chartFilter.addEventListener('change', function() {
                updateSalesChart(this.value);
            });
        }

        updateSalesChart(defaultRange);
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