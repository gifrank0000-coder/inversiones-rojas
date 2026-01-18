<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Ventas - Inversiones Rojas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
     <link rel="stylesheet" href="/inversiones-rojas/public/css/layouts/ventas.css">

</head>
<body>



            <div class="admin-content">
                <!-- Stats Cards -->
                <div class="sales-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3>$4,258</h3>
                            <p>Ventas Hoy</p>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i>
                                12.5%
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-info">
                            <h3>28</h3>
                            <p>Transacciones Hoy</p>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i>
                                8.2%
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3>45</h3>
                            <p>Clientes Atendidos</p>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i>
                                5.7%
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3>$68,450</h3>
                            <p>Ventas del Mes</p>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i>
                                15.3%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section Compacta -->
                <div class="charts-grid">
                    <!-- Gráfica de Ventas Mensuales Compacta -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Ventas Mensuales</h3>
                            <div class="chart-actions">
                                <select class="chart-filter">
                                    <option>Últimos 3 meses</option>
                                    <option>Este año</option>
                                </select>
                            </div>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>

                    <!-- Top Métodos de Pago -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Métodos de Pago Más Usados</h3>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="paymentChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="sales-actions">
                    <div class="action-buttons">
                        <button class="btn btn-primary">
                            <i class="fas fa-cash-register"></i>
                            Nueva Venta
                        </button>
                        <button class="btn btn-secondary">
                            <i class="fas fa-file-invoice"></i>
                            Ver Facturas
                        </button>
                        <button class="btn btn-secondary">
                            <i class="fas fa-chart-bar"></i>
                            Reportes Detallados
                        </button>
                        <button class="btn btn-secondary">
                            <i class="fas fa-file-export"></i>
                            Exportar Datos
                        </button>
                    </div>
                </div>

                <!-- Sales Table -->
                <div class="sales-table">
                    <div class="table-header">
                        <div>Factura</div>
                        <div>Cliente</div>
                        <div>Fecha</div>
                        <div>Total</div>
                        <div>Método Pago</div>
                        <div>Estado</div>
                    </div>
                    
                    <!-- Ventas de ejemplo -->
                    <div class="table-row">
                        <div>
                            <strong>F001-2587</strong>
                            <div style="font-size: 0.8rem; color: #6c757d;">Vendedor: María González</div>
                        </div>
                        <div>Carlos Rodríguez</div>
                        <div>15/03/2024 14:30</div>
                        <div>$1,850.00</div>
                        <div>Transferencia</div>
                        <div><span class="status-badge status-completed">Completada</span></div>
                    </div>
                    
                    <div class="table-row">
                        <div>
                            <strong>F001-2586</strong>
                            <div style="font-size: 0.8rem; color: #6c757d;">Vendedor: José Pérez</div>
                        </div>
                        <div>Ana Martínez</div>
                        <div>15/03/2024 13:15</div>
                        <div>$320.00</div>
                        <div>Efectivo</div>
                        <div><span class="status-badge status-completed">Completada</span></div>
                    </div>
                    
                    <div class="table-row">
                        <div>
                            <strong>F001-2585</strong>
                            <div style="font-size: 0.8rem; color: #6c757d;">Vendedor: Luis Rojas</div>
                        </div>
                        <div>Roberto Silva</div>
                        <div>15/03/2024 11:45</div>
                        <div>$2,150.00</div>
                        <div>Pago Móvil</div>
                        <div><span class="status-badge status-pending">Pendiente</span></div>
                    </div>

                    <div class="table-row">
                        <div>
                            <strong>F001-2584</strong>
                            <div style="font-size: 0.8rem; color: #6c757d;">Vendedor: Carmen López</div>
                        </div>
                        <div>Empresa Transportes Aragua</div>
                        <div>14/03/2024 16:20</div>
                        <div>$4,580.00</div>
                        <div>Transferencia</div>
                        <div><span class="status-badge status-completed">Completada</span></div>
                    </div>

                    <div class="table-row">
                        <div>
                            <strong>F001-2583</strong>
                            <div style="font-size: 0.8rem; color: #6c757d;">Vendedor: Pedro Mendoza</div>
                        </div>
                        <div>Laura González</div>
                        <div>14/03/2024 10:30</div>
                        <div>$850.00</div>
                        <div>Tarjeta Débito</div>
                        <div><span class="status-badge status-processing">Procesando</span></div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="activity-section">
                    <h3 style="margin-bottom: 20px; color: #2c3e50;">Actividad Reciente</h3>
                    <div class="activity-list">
                        <div class="activity-item">
                            <div class="activity-icon sales">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="activity-content">
                                <p>Nueva venta registrada - Factura F001-2587</p>
                                <span>Carlos Rodríguez - $1,850.00 • Hace 5 minutos</span>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon payment">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="activity-content">
                                <p>Pago confirmado - Factura F001-2585</p>
                                <span>Roberto Silva - Pago móvil • Hace 1 hora</span>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon client">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="activity-content">
                                <p>Nuevo cliente registrado</p>
                                <span>Empresa Transportes Aragua • Hace 2 horas</span>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon sales">
                                <i class="fas fa-tags"></i>
                            </div>
                            <div class="activity-content">
                                <p>Promoción aplicada en venta</p>
                                <span>Descuento verano 2024 - 15% • Hace 3 horas</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Gráfica de Ventas Mensuales COMPACTA (3 meses)
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: ['Enero', 'Febrero', 'Marzo'],
                datasets: [{
                    label: 'Ventas ($)',
                    data: [65400, 72000, 68450],
                    borderColor: '#1F9166',
                    backgroundColor: 'rgba(31, 145, 102, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + (value/1000).toFixed(0) + 'k';
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Gráfica de Métodos de Pago (Top 4 más usados)
        const paymentCtx = document.getElementById('paymentChart').getContext('2d');
        const paymentChart = new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: ['Transferencia', 'Efectivo', 'Pago Móvil', 'Tarjeta'],
                datasets: [{
                    data: [42, 35, 15, 8],
                    backgroundColor: [
                        '#1F9166',
                        '#3498db',
                        '#9b59b6',
                        '#e67e22'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Toggle sidebar
        document.querySelector('.sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.admin-sidebar').classList.toggle('collapsed');
        });

        // Simular acciones de botones
        document.querySelectorAll('.btn-primary').forEach(btn => {
            btn.addEventListener('click', function() {
                if (this.innerHTML.includes('Nueva Venta')) {
                    alert('Redirigiendo al punto de venta...');
                }
            });
        });
    </script>
</body>
</html>