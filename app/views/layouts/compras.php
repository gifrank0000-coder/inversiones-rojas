<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compras - Inversiones Rojas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
 <link rel="stylesheet" href="/inversiones-rojas/public/css/layouts/compras.css">
</head>
<body>
  

            <div class="admin-content">
                <!-- Stats Cards -->
                <div class="compras-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="stat-info">
                            <h3>$28,450</h3>
                            <p>Compras del Mes</p>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i>
                                15.3%
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3>8</h3>
                            <p>Pendientes Recepción</p>
                            <div class="stat-trend trend-down">
                                <i class="fas fa-arrow-down"></i>
                                5.7%
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3>12</h3>
                            <p>Órdenes Completadas</p>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i>
                                8.2%
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="stat-info">
                            <h3>15</h3>
                            <p>Proveedores Activos</p>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i>
                                12.5%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="charts-grid">
                    <!-- Compras por Mes -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Compras Mensuales</h3>
                            <div class="chart-actions">
                                <select class="chart-filter">
                                    <option>Últimos 3 meses</option>
                                    <option>Este año</option>
                                </select>
                            </div>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="comprasChart"></canvas>
                        </div>
                    </div>

                    <!-- Distribución por Proveedor -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Compras por Proveedor</h3>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="proveedoresChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="search-filters">
                    <div class="search-box">
                        <input type="text" placeholder="Buscar órdenes de compra...">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    <select class="filter-select">
                        <option>Todos los estados</option>
                        <option>Pendiente</option>
                        <option>Aprobada</option>
                        <option>Recibida</option>
                        <option>Cancelada</option>
                    </select>
                    <select class="filter-select">
                        <option>Todos los proveedores</option>
                        <option>Bera Motors</option>
                        <option>Empire Parts</option>
                        <option>Repuestos Venezuela</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="compras-actions">
                    <div class="action-buttons">
                        <button class="btn btn-primary">
                            <i class="fas fa-file-purchase"></i>
                            Nueva Orden Compra
                        </button>
                        <button class="btn btn-secondary">
                            <i class="fas fa-truck"></i>
                            Recepción Mercancía
                        </button>
                        <button class="btn btn-secondary">
                            <i class="fas fa-file-export"></i>
                            Exportar Reporte
                        </button>
                        <button class="btn btn-warning">
                            <i class="fas fa-clock"></i>
                            Pendientes (8)
                        </button>
                    </div>
                </div>

                <!-- Compras Table -->
                <div class="compras-table">
                    <div class="table-header">
                        <div>Orden #</div>
                        <div>Proveedor</div>
                        <div>Fecha</div>
                        <div>Productos</div>
                        <div>Total</div>
                        <div>Estado</div>
                    </div>
                    
                    <!-- Órdenes de ejemplo -->
                    <div class="table-row">
                        <div>
                            <strong>OC-2024-025</strong>
                            <div style="font-size: 0.8rem; color: #6c757d;">Creada: 15/03/2024</div>
                        </div>
                        <div>Bera Motors</div>
                        <div>20/03/2024</div>
                        <div>12 productos</div>
                        <div>$8,450.00</div>
                        <div><span class="status-badge status-pending">Pendiente</span></div>
                    </div>
                    
                    <div class="table-row">
                        <div>
                            <strong>OC-2024-024</strong>
                            <div style="font-size: 0.8rem; color: #6c757d;">Creada: 14/03/2024</div>
                        </div>
                        <div>Empire Parts</div>
                        <div>18/03/2024</div>
                        <div>8 productos</div>
                        <div>$5,200.00</div>
                        <div><span class="status-badge status-approved">Aprobada</span></div>
                    </div>
                    
                    <div class="table-row">
                        <div>
                            <strong>OC-2024-023</strong>
                            <div style="font-size: 0.8rem; color: #6c757d;">Creada: 12/03/2024</div>
                        </div>
                        <div>Repuestos Venezuela</div>
                        <div>15/03/2024</div>
                        <div>15 productos</div>
                        <div>$3,150.00</div>
                        <div><span class="status-badge status-received">Recibida</span></div>
                    </div>

                    <div class="table-row">
                        <div>
                            <strong>OC-2024-022</strong>
                            <div style="font-size: 0.8rem; color: #6c757d;">Creada: 10/03/2024</div>
                        </div>
                        <div>Moto Accesorios CA</div>
                        <div>12/03/2024</div>
                        <div>6 productos</div>
                        <div>$2,800.00</div>
                        <div><span class="status-badge status-received">Recibida</span></div>
                    </div>

                    <div class="table-row">
                        <div>
                            <strong>OC-2024-021</strong>
                            <div style="font-size: 0.8rem; color: #6c757d;">Creada: 08/03/2024</div>
                        </div>
                        <div>Distribuidora Aragua</div>
                        <div>10/03/2024</div>
                        <div>10 productos</div>
                        <div>$4,500.00</div>
                        <div><span class="status-badge status-cancelled">Cancelada</span></div>
                    </div>
                </div>

                <!-- Pending Orders -->
                <div class="pending-section">
                    <h3 style="margin-bottom: 20px; color: #2c3e50;">Órdenes Pendientes de Recepción</h3>
                    <div class="pending-list">
                        <div class="pending-item">
                            <div class="pending-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="pending-content">
                                <p>OC-2024-025 - Bera Motors</p>
                                <span>$8,450.00 • 12 productos • Vence: 20/03/2024</span>
                            </div>
                        </div>
                        <div class="pending-item">
                            <div class="pending-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="pending-content">
                                <p>OC-2024-024 - Empire Parts</p>
                                <span>$5,200.00 • 8 productos • Vence: 18/03/2024</span>
                            </div>
                        </div>
                        <div class="pending-item">
                            <div class="pending-icon">
                                <i class="fas fa-exclamation"></i>
                            </div>
                            <div class="pending-content">
                                <p>OC-2024-020 - Repuestos Venezuela</p>
                                <span>$3,800.00 • 5 productos • Vencida: 14/03/2024</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Gráfica de Compras Mensuales
        const comprasCtx = document.getElementById('comprasChart').getContext('2d');
        const comprasChart = new Chart(comprasCtx, {
            type: 'bar',
            data: {
                labels: ['Ene', 'Feb', 'Mar'],
                datasets: [{
                    label: 'Compras ($)',
                    data: [24500, 26800, 28450],
                    backgroundColor: '#1F9166',
                    borderColor: '#187a54',
                    borderWidth: 2,
                    borderRadius: 6,
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
                        beginAtZero: true,
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

        // Gráfica de Compras por Proveedor
        const proveedoresCtx = document.getElementById('proveedoresChart').getContext('2d');
        const proveedoresChart = new Chart(proveedoresCtx, {
            type: 'doughnut',
            data: {
                labels: ['Bera Motors', 'Empire Parts', 'Repuestos Venezuela', 'Moto Accesorios'],
                datasets: [{
                    data: [45, 25, 20, 10],
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
                if (this.innerHTML.includes('Nueva Orden')) {
                    alert('Redirigiendo al formulario de nueva orden de compra...');
                }
            });
        });
    </script>
</body>
</html>