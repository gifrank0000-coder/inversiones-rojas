<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos Digitales - Inversiones Rojas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
 <link rel="stylesheet" href="/inversiones-rojas/public/css/layouts/pedidos.css">


    </style>
</head>
<body>

            <div class="admin-content">
                <!-- Stats Cards -->
                <div class="pedidos-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="stat-info">
                            <h3>45</h3>
                            <p>Pedidos Hoy</p>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i>
                                22.5%
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3>12</h3>
                            <p>Pendientes Confirmación</p>
                            <div class="stat-trend trend-down">
                                <i class="fas fa-arrow-down"></i>
                                8.3%
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3>$15,680</h3>
                            <p>Valor Pendiente</p>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i>
                                15.7%
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3>32</h3>
                            <p>Clientes Online</p>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i>
                                18.2%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="charts-grid">
                    <!-- Pedidos por Canal -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Pedidos por Canal</h3>
                            <div class="chart-actions">
                                <select class="chart-filter">
                                    <option>Este mes</option>
                                    <option>Mes anterior</option>
                                </select>
                            </div>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="canalesChart"></canvas>
                        </div>
                    </div>

                    <!-- Tasa de Conversión -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Tasa de Conversión</h3>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="conversionChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Integration Status -->
                <div class="integration-section">
                    <h3 style="margin-bottom: 20px; color: #2c3e50;">Estado de Integraciones</h3>
                    <div class="integration-grid">
                        <div class="integration-item">
                            <div class="integration-icon whatsapp">
                                <i class="fab fa-whatsapp"></i>
                            </div>
                            <div class="integration-content">
                                <h4>WhatsApp Business</h4>
                                <p>Conectado • 28 pedidos hoy</p>
                            </div>
                        </div>
                        <div class="integration-item">
                            <div class="integration-icon email">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="integration-content">
                                <h4>Email Automático</h4>
                                <p>Activo • 15 notificaciones</p>
                            </div>
                        </div>
                        <div class="integration-item">
                            <div class="integration-icon web">
                                <i class="fas fa-globe"></i>
                            </div>
                            <div class="integration-content">
                                <h4>Web E-commerce</h4>
                                <p>Online • 156 visitas hoy</p>
                            </div>
                        </div>
                        <div class="integration-item">
                            <div class="integration-icon sms">
                                <i class="fas fa-sms"></i>
                            </div>
                            <div class="integration-content">
                                <h4>SMS Marketing</h4>
                                <p>Activo • 85% entregados</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="search-filters">
                    <div class="search-box">
                        <input type="text" placeholder="Buscar pedidos...">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    <select class="filter-select">
                        <option>Todos los estados</option>
                        <option>Pendiente</option>
                        <option>Confirmado</option>
                        <option>En preparación</option>
                        <option>Enviado</option>
                        <option>Entregado</option>
                    </select>
                    <select class="filter-select">
                        <option>Todos los canales</option>
                        <option>Web</option>
                        <option>WhatsApp</option>
                        <option>Email</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="pedidos-actions">
                    <div class="action-buttons">
                        <button class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Nuevo Pedido
                        </button>
                        <button class="btn btn-secondary">
                            <i class="fab fa-whatsapp"></i>
                            Enviar WhatsApp
                        </button>
                        <button class="btn btn-secondary">
                            <i class="fas fa-envelope"></i>
                            Enviar Email
                        </button>
                        <button class="btn btn-warning">
                            <i class="fas fa-clock"></i>
                            Pendientes (12)
                        </button>
                    </div>
                </div>

                <!-- Pedidos Table -->
                <div class="pedidos-table">
                    <div class="table-header">
                        <div>Pedido #</div>
                        <div>Cliente</div>
                        <div>Canal</div>
                        <div>Productos</div>
                        <div>Total</div>
                        <div>Fecha</div>
                        <div>Estado</div>
                    </div>
                    
                    <!-- Pedidos de ejemplo -->
                    <div class="table-row">
                        <div>
                            <strong>PED-2024-087</strong>
                            <div style="font-size: 0.8rem; color: #6c757d;">Web</div>
                        </div>
                        <div>Laura González</div>
                        <div>Web</div>
                        <div>3 productos</div>
                        <div>$450.00</div>
                        <div>15/03/2024</div>
                        <div><span class="status-badge status-pending">Pendiente</span></div>
                    </div>
                    
                    <div class="table-row">
                        <div>
                            <strong>PED-2024-086</strong>
                            <div style="font-size: 0.8rem; color: #6c757d;">WhatsApp</div>
                        </div>
                        <div>Miguel Torres</div>
                        <div>WhatsApp</div>
                        <div>1 producto</div>
                        <div>$1,850.00</div>
                        <div>15/03/2024</div>
                        <div><span class="status-badge status-confirmed">Confirmado</span></div>
                    </div>
                    
                    <div class="table-row">
                        <div>
                            <strong>PED-2024-085</strong>
                            <div style="font-size: 0.8rem; color: #6c757d;">Web</div>
                        </div>
                        <div>Carlos Mendoza</div>
                        <div>Web</div>
                        <div>2 productos</div>
                        <div>$320.00</div>
                        <div>14/03/2024</div>
                        <div><span class="status-badge status-preparing">Preparando</span></div>
                    </div>

                    <div class="table-row">
                        <div>
                            <strong>PED-2024-084</strong>
                            <div style="font-size: 0.8rem; color: #6c757d;">Email</div>
                        </div>
                        <div>Ana Rodríguez</div>
                        <div>Email</div>
                        <div>4 productos</div>
                        <div>$680.00</div>
                        <div>14/03/2024</div>
                        <div><span class="status-badge status-shipped">Enviado</span></div>
                    </div>

                    <div class="table-row">
                        <div>
                            <strong>PED-2024-083</strong>
                            <div style="font-size: 0.8rem; color: #6c757d;">WhatsApp</div>
                        </div>
                        <div>Roberto Silva</div>
                        <div>WhatsApp</div>
                        <div>2 productos</div>
                        <div>$240.00</div>
                        <div>13/03/2024</div>
                        <div><span class="status-badge status-delivered">Entregado</span></div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="activity-section">
                    <h3 style="margin-bottom: 20px; color: #2c3e50;">Actividad Reciente</h3>
                    <div class="activity-list">
                        <div class="activity-item">
                            <div class="activity-icon whatsapp">
                                <i class="fab fa-whatsapp"></i>
                            </div>
                            <div class="activity-content">
                                <p>Nuevo pedido por WhatsApp - PED-2024-087</p>
                                <span>Laura González • $450.00 • Hace 15 minutos</span>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon web">
                                <i class="fas fa-globe"></i>
                            </div>
                            <div class="activity-content">
                                <p>Pedido web confirmado - PED-2024-086</p>
                                <span>Miguel Torres • $1,850.00 • Hace 1 hora</span>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon payment">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="activity-content">
                                <p>Pago confirmado - PED-2024-085</p>
                                <span>Carlos Mendoza • Transferencia • Hace 2 horas</span>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon shipping">
                                <i class="fas fa-shipping-fast"></i>
                            </div>
                            <div class="activity-content">
                                <p>Pedido enviado - PED-2024-084</p>
                                <span>Ana Rodríguez • MRW Express • Hace 3 horas</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Gráfica de Pedidos por Canal
        const canalesCtx = document.getElementById('canalesChart').getContext('2d');
        const canalesChart = new Chart(canalesCtx, {
            type: 'doughnut',
            data: {
                labels: ['WhatsApp', 'Web', 'Email', 'Teléfono'],
                datasets: [{
                    data: [45, 35, 15, 5],
                    backgroundColor: [
                        '#25D366',
                        '#1F9166',
                        '#EA4335',
                        '#FF6B00'
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

        // Gráfica de Tasa de Conversión
        const conversionCtx = document.getElementById('conversionChart').getContext('2d');
        const conversionChart = new Chart(conversionCtx, {
            type: 'bar',
            data: {
                labels: ['Ene', 'Feb', 'Mar'],
                datasets: [{
                    label: 'Tasa de Conversión (%)',
                    data: [12, 15, 18],
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
                        max: 25,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
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

        // Toggle sidebar
        document.querySelector('.sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.admin-sidebar').classList.toggle('collapsed');
        });

        // Simular acciones de botones
        document.querySelectorAll('.btn-primary').forEach(btn => {
            btn.addEventListener('click', function() {
                if (this.innerHTML.includes('Nuevo Pedido')) {
                    alert('Redirigiendo al formulario de nuevo pedido...');
                }
            });
        });
    </script>
</body>
</html>