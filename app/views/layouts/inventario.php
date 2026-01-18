<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Inventario - Inversiones Rojas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
 <link rel="stylesheet" href="/inversiones-rojas/public/css/layouts/inventario.css">

    </style>
</head>
<body>

            <div class="admin-content">
                <!-- Stats Cards -->
                <div class="inventory-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="stat-info">
                            <h3>423</h3>
                            <p>Productos Totales</p>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i>
                                8.2%
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-info">
                            <h3>18</h3>
                            <p>Stock Bajo</p>
                            <div class="stat-trend trend-down">
                                <i class="fas fa-arrow-down"></i>
                                12.5%
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3>7</h3>
                            <p>Sin Stock</p>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i>
                                5.3%
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3>$125,680</h3>
                            <p>Valor Total</p>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i>
                                15.7%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="charts-grid">
                    <!-- Movimiento de Stock -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Movimiento de Stock</h3>
                            <div class="chart-actions">
                                <select class="chart-filter">
                                    <option>Últimos 3 meses</option>
                                    <option>Este año</option>
                                </select>
                            </div>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="stockMovementChart"></canvas>
                        </div>
                    </div>

                    <!-- Distribución por Categoría -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Stock por Categoría</h3>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="search-filters">
                    <div class="search-box">
                        <input type="text" placeholder="Buscar productos...">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    <select class="filter-select">
                        <option>Todas las categorías</option>
                        <option>Motos</option>
                        <option>Repuestos</option>
                        <option>Accesorios</option>
                    </select>
                    <select class="filter-select">
                        <option>Todos los estados</option>
                        <option>En stock</option>
                        <option>Stock bajo</option>
                        <option>Sin stock</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="inventory-actions">
                    <div class="action-buttons">
                        <button class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Agregar Producto
                        </button>
                        <button class="btn btn-secondary">
                            <i class="fas fa-sync-alt"></i>
                            Actualizar Stock
                        </button>
                        <button class="btn btn-secondary">
                            <i class="fas fa-file-export"></i>
                            Exportar Reporte
                        </button>
                        <button class="btn btn-warning">
                            <i class="fas fa-bell"></i>
                            Ver Alertas (18)
                        </button>
                    </div>
                </div>

                <!-- Inventory Table -->
                <div class="inventory-table">
                    <div class="table-header">
                        <div>Producto</div>
                        <div>Categoría</div>
                        <div>Stock Actual</div>
                        <div>Stock Mínimo</div>
                        <div>Precio Compra</div>
                        <div>Precio Venta</div>
                        <div>Estado</div>
                    </div>
                    
                    <!-- Productos de ejemplo -->
                    <div class="table-row">
                        <div class="product-info">
                            <div class="product-image">
                                <i class="fas fa-motorcycle"></i>
                            </div>
                            <div>
                                <strong>Bera BR 200</strong>
                                <div style="font-size: 0.8rem; color: #6c757d;">Código: BR200-001</div>
                            </div>
                        </div>
                        <div>Motos</div>
                        <div>8</div>
                        <div>5</div>
                        <div>$1,200.00</div>
                        <div>$1,850.00</div>
                        <div><span class="status-badge status-low-stock">Stock Bajo</span></div>
                    </div>
                    
                    <div class="table-row">
                        <div class="product-info">
                            <div class="product-image">
                                <i class="fas fa-helmet-safety"></i>
                            </div>
                            <div>
                                <strong>Casco Integral Bera</strong>
                                <div style="font-size: 0.8rem; color: #6c757d;">Código: CAS-002</div>
                            </div>
                        </div>
                        <div>Accesorios</div>
                        <div>25</div>
                        <div>10</div>
                        <div>$35.00</div>
                        <div>$50.00</div>
                        <div><span class="status-badge status-in-stock">En Stock</span></div>
                    </div>
                    
                    <div class="table-row">
                        <div class="product-info">
                            <div class="product-image">
                                <i class="fas fa-tools"></i>
                            </div>
                            <div>
                                <strong>Kit Herramientas</strong>
                                <div style="font-size: 0.8rem; color: #6c757d;">Código: KIT-015</div>
                            </div>
                        </div>
                        <div>Herramientas</div>
                        <div>0</div>
                        <div>5</div>
                        <div>$25.00</div>
                        <div>$40.00</div>
                        <div><span class="status-badge status-out-of-stock">Sin Stock</span></div>
                    </div>

                    <div class="table-row">
                        <div class="product-info">
                            <div class="product-image">
                                <i class="fas fa-oil-can"></i>
                            </div>
                            <div>
                                <strong>Aceite Motor 4T</strong>
                                <div style="font-size: 0.8rem; color: #6c757d;">Código: ACE-008</div>
                            </div>
                        </div>
                        <div>Lubricantes</div>
                        <div>45</div>
                        <div>20</div>
                        <div>$8.00</div>
                        <div>$12.00</div>
                        <div><span class="status-badge status-in-stock">En Stock</span></div>
                    </div>

                    <div class="table-row">
                        <div class="product-info">
                            <div class="product-image">
                                <i class="fas fa-gas-pump"></i>
                            </div>
                            <div>
                                <strong>Tanque Gasolina Bera</strong>
                                <div style="font-size: 0.8rem; color: #6c757d;">Código: TAN-012</div>
                            </div>
                        </div>
                        <div>Repuestos</div>
                        <div>3</div>
                        <div>5</div>
                        <div>$45.00</div>
                        <div>$68.00</div>
                        <div><span class="status-badge status-low-stock">Stock Bajo</span></div>
                    </div>
                </div>

                <!-- Low Stock Alerts -->
                <div class="alerts-section">
                    <h3 style="margin-bottom: 20px; color: #2c3e50;">Alertas de Stock Bajo</h3>
                    <div class="alert-list">
                        <div class="alert-item">
                            <div class="alert-icon">
                                <i class="fas fa-exclamation"></i>
                            </div>
                            <div class="alert-content">
                                <p>Bera BR 200 - Stock crítico</p>
                                <span>8 unidades disponibles (Mínimo: 5) • Última venta: Hoy</span>
                            </div>
                        </div>
                        <div class="alert-item">
                            <div class="alert-icon">
                                <i class="fas fa-exclamation"></i>
                            </div>
                            <div class="alert-content">
                                <p>Tanque Gasolina Bera - Stock bajo</p>
                                <span>3 unidades disponibles (Mínimo: 5) • Solicitar a proveedor</span>
                            </div>
                        </div>
                        <div class="alert-item">
                            <div class="alert-icon">
                                <i class="fas fa-times"></i>
                            </div>
                            <div class="alert-content">
                                <p>Kit Herramientas - Sin stock</p>
                                <span>0 unidades disponibles (Mínimo: 5) • Pedido pendiente</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Gráfica de Movimiento de Stock
        const stockMovementCtx = document.getElementById('stockMovementChart').getContext('2d');
        const stockMovementChart = new Chart(stockMovementCtx, {
            type: 'line',
            data: {
                labels: ['Ene', 'Feb', 'Mar'],
                datasets: [{
                    label: 'Entradas',
                    data: [120, 150, 180],
                    borderColor: '#1F9166',
                    backgroundColor: 'rgba(31, 145, 102, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Salidas',
                    data: [95, 130, 165],
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
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
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
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

        // Gráfica de Distribución por Categoría
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: ['Motos', 'Repuestos', 'Accesorios', 'Lubricantes', 'Herramientas'],
                datasets: [{
                    data: [15, 35, 25, 15, 10],
                    backgroundColor: [
                        '#1F9166',
                        '#3498db',
                        '#9b59b6',
                        '#e67e22',
                        '#e74c3c'
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
                if (this.innerHTML.includes('Nuevo Producto')) {
                    alert('Redirigiendo al formulario de nuevo producto...');
                }
            });
        });
    </script>
</body>
</html>