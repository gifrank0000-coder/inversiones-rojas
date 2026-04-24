// Scripts extraídos de Dashboard.php
// Fullscreen y chart initialization
document.addEventListener('DOMContentLoaded', function() {
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
    const salesChartData = data.salesChartData || null;
    const defaultRange = data.defaultSalesRange || '12';

    const canvas = document.getElementById('salesChart');
    if (canvas && typeof Chart !== 'undefined') {
        const ctx = canvas.getContext('2d');

        const chartConfig = {
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
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { drawBorder: false } },
                    x: { grid: { display: false } }
                }
            }
        };

        const chartInstance = new Chart(ctx, chartConfig);

        function setChartRange(range) {
            if (salesChartData && salesChartData[range]) {
                chartInstance.data.labels = salesChartData[range].labels;
                chartInstance.data.datasets[0].data = salesChartData[range].data;
            } else if (data.meses && data.datosGrafico) {
                chartInstance.data.labels = data.meses;
                chartInstance.data.datasets[0].data = data.datosGrafico;
            } else {
                chartInstance.data.labels = [];
                chartInstance.data.datasets[0].data = [];
            }
            chartInstance.update();
        }

        const selectRange = document.getElementById('salesRangeFilter');
        if (selectRange) {
            selectRange.addEventListener('change', function() {
                setChartRange(this.value);
            });
        }

        setChartRange(defaultRange);
    }
});