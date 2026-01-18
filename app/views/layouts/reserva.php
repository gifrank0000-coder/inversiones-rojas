<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva y Apartado - Inversiones Rojas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
 <link rel="stylesheet" href="/inversiones-rojas/public/css/layouts/reserva.css">


    
</head>
<body>
   

            <div class="admin-content">
                <!-- Stats Cards -->
                <div class="reservas-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3>28</h3>
                            <p>Reservas Activas</p>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i>
                                12.5%
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3>15</h3>
                            <p>Apartados Pendientes</p>
                            <div class="stat-trend trend-down">
                                <i class="fas fa-arrow-down"></i>
                                5.3%
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3>$8,450</h3>
                            <p>Valor en Reservas</p>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i>
                                18.7%
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-info">
                            <h3>7</h3>
                            <p>Próximas a Vencer</p>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i>
                                22.2%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="charts-grid">
                    <!-- Reservas por Tipo -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Reservas por Tipo</h3>
                            <div class="chart-actions">
                                <select class="chart-filter">
                                    <option>Este mes</option>
                                    <option>Mes anterior</option>
                                </select>
                            </div>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="tipoReservasChart"></canvas>
                        </div>
                    </div>

                    <!-- Tendencias de Reservas -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Tendencias de Reservas</h3>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="tendenciasChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Formulario de Nueva Reserva (Inicialmente oculto) -->
                <div class="form-container" id="formReserva" style="display: none;">
                    <h3 style="margin-bottom: 20px; color: #2c3e50;">Nueva Reserva/Apartado</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="tipoReserva">Tipo</label>
                            <select class="form-control" id="tipoReserva">
                                <option value="">Seleccione tipo</option>
                                <option value="reserva">Reserva</option>
                                <option value="apartado">Apartado</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="cliente">Cliente</label>
                            <select class="form-control" id="cliente">
                                <option value="">Seleccione cliente</option>
                                <option value="1">Laura González</option>
                                <option value="2">Miguel Torres</option>
                                <option value="3">Carlos Mendoza</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="producto">Producto</label>
                            <select class="form-control" id="producto">
                                <option value="">Seleccione producto</option>
                                <option value="1">Yamaha MT-03 2024</option>
                                <option value="2">Honda CB190R</option>
                                <option value="3">Suzuki GSX-S150</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="fechaReserva">Fecha de Reserva</label>
                            <input type="date" class="form-control" id="fechaReserva">
                        </div>
                        <div class="form-group">
                            <label for="fechaVencimiento">Fecha de Vencimiento</label>
                            <input type="date" class="form-control" id="fechaVencimiento">
                        </div>
                        <div class="form-group">
                            <label for="anticipo">Anticipo ($)</label>
                            <input type="number" class="form-control" id="anticipo" min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label for="valorTotal">Valor Total ($)</label>
                            <input type="number" class="form-control" id="valorTotal" min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select class="form-control" id="estado">
                                <option value="reservado">Reservado</option>
                                <option value="apartado">Apartado</option>
                                <option value="confirmado">Confirmado</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="notas">Notas Adicionales</label>
                        <textarea class="form-control" id="notas" rows="3"></textarea>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-secondary" id="cancelarReservaBtn">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </button>
                        <button class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Guardar Reserva
                        </button>
                    </div>
                </div>

                <!-- Notificaciones Automáticas -->
                <div class="notifications-section">
                    <h3 style="margin-bottom: 20px; color: #2c3e50;">Notificaciones Automáticas</h3>
                    <div class="notification-item">
                        <div class="notification-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div class="notification-content">
                            <p>Reserva #RES-2024-015 próxima a vencer</p>
                            <span>Cliente: Miguel Torres • Vence: 20/03/2024</span>
                        </div>
                    </div>
                    <div class="notification-item">
                        <div class="notification-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div class="notification-content">
                            <p>Apartado #APT-2024-008 completado exitosamente</p>
                            <span>Cliente: Ana Rodríguez • Producto: Honda CB190R</span>
                        </div>
                    </div>
                    <div class="notification-item">
                        <div class="notification-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div class="notification-content">
                            <p>Recordatorio: 5 reservas pendientes de confirmación</p>
                            <span>Enviar recordatorio a clientes</span>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="search-filters">
                    <div class="search-box">
                        <input type="text" placeholder="Buscar reservas...">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    <select class="filter-select">
                        <option>Todos los estados</option>
                        <option>Reservado</option>
                        <option>Apartado</option>
                        <option>Confirmado</option>
                        <option>Expirado</option>
                        <option>Cancelado</option>
                    </select>
                    <select class="filter-select">
                        <option>Todos los tipos</option>
                        <option>Reserva</option>
                        <option>Apartado</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="reservas-actions">
                    <div class="action-buttons">
                        <button class="btn btn-primary" id="mostrarFormBtn">
                            <i class="fas fa-plus"></i>
                            Nueva Reserva
                        </button>
                        <button class="btn btn-secondary">
                            <i class="fab fa-whatsapp"></i>
                            Enviar Recordatorio
                        </button>
                        <button class="btn btn-secondary">
                            <i class="fas fa-envelope"></i>
                            Enviar Email
                        </button>
                        <button class="btn btn-warning">
                            <i class="fas fa-clock"></i>
                            Próximas a Vencer (7)
                        </button>
                        <button class="btn btn-danger">
                            <i class="fas fa-times"></i>
                            Cancelar Seleccionadas
                        </button>
                    </div>
                </div>

                <!-- Reservas Table -->
                <div class="reservas-table">
                    <div class="table-header">
                        <div>ID</div>
                        <div>Cliente</div>
                        <div>Producto</div>
                        <div>Tipo</div>
                        <div>Fecha Reserva</div>
                        <div>Fecha Vencimiento</div>
                        <div>Anticipo</div>
                        <div>Estado</div>
                    </div>
                    
                    <!-- Reservas de ejemplo -->
                    <div class="table-row">
                        <div>
                            <strong>RES-2024-015</strong>
                        </div>
                        <div>Miguel Torres</div>
                        <div>Yamaha MT-03 2024</div>
                        <div>Reserva</div>
                        <div>15/03/2024</div>
                        <div>20/03/2024</div>
                        <div>$500.00</div>
                        <div><span class="status-badge status-reserved">Reservado</span></div>
                    </div>
                    
                    <div class="table-row">
                        <div>
                            <strong>APT-2024-008</strong>
                        </div>
                        <div>Ana Rodríguez</div>
                        <div>Honda CB190R</div>
                        <div>Apartado</div>
                        <div>10/03/2024</div>
                        <div>25/03/2024</div>
                        <div>$1,200.00</div>
                        <div><span class="status-badge status-apartado">Apartado</span></div>
                    </div>
                    
                    <div class="table-row">
                        <div>
                            <strong>RES-2024-014</strong>
                        </div>
                        <div>Carlos Mendoza</div>
                        <div>Suzuki GSX-S150</div>
                        <div>Reserva</div>
                        <div>08/03/2024</div>
                        <div>18/03/2024</div>
                        <div>$300.00</div>
                        <div><span class="status-badge status-confirmed">Confirmado</span></div>
                    </div>

                    <div class="table-row">
                        <div>
                            <strong>APT-2024-007</strong>
                        </div>
                        <div>Roberto Silva</div>
                        <div>Yamaha R15</div>
                        <div>Apartado</div>
                        <div>05/03/2024</div>
                        <div>15/03/2024</div>
                        <div>$800.00</div>
                        <div><span class="status-badge status-expired">Expirado</span></div>
                    </div>

                    <div class="table-row">
                        <div>
                            <strong>RES-2024-013</strong>
                        </div>
                        <div>Laura González</div>
                        <div>Honda XRE 300</div>
                        <div>Reserva</div>
                        <div>01/03/2024</div>
                        <div>10/03/2024</div>
                        <div>$400.00</div>
                        <div><span class="status-badge status-completed">Completado</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Gráfica de Reservas por Tipo
        const tipoReservasCtx = document.getElementById('tipoReservasChart').getContext('2d');
        const tipoReservasChart = new Chart(tipoReservasCtx, {
            type: 'doughnut',
            data: {
                labels: ['Reservas', 'Apartados'],
                datasets: [{
                    data: [65, 35],
                    backgroundColor: [
                        '#1F9166',
                        '#3498db'
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

        // Gráfica de Tendencias de Reservas
        const tendenciasCtx = document.getElementById('tendenciasChart').getContext('2d');
        const tendenciasChart = new Chart(tendenciasCtx, {
            type: 'line',
            data: {
                labels: ['Ene', 'Feb', 'Mar'],
                datasets: [{
                    label: 'Reservas',
                    data: [15, 22, 28],
                    backgroundColor: 'rgba(31, 145, 102, 0.1)',
                    borderColor: '#1F9166',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }, {
                    label: 'Apartados',
                    data: [10, 12, 15],
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderColor: '#3498db',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
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

        // Toggle sidebar
        document.querySelector('.sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.admin-sidebar').classList.toggle('collapsed');
        });

        // Mostrar/ocultar formulario de nueva reserva
        document.getElementById('mostrarFormBtn').addEventListener('click', function() {
            document.getElementById('formReserva').style.display = 'block';
        });

        document.getElementById('cancelarReservaBtn').addEventListener('click', function() {
            document.getElementById('formReserva').style.display = 'none';
        });

        // Establecer fecha mínima para fecha de vencimiento
        const fechaReservaInput = document.getElementById('fechaReserva');
        const fechaVencimientoInput = document.getElementById('fechaVencimiento');
        
        fechaReservaInput.addEventListener('change', function() {
            fechaVencimientoInput.min = this.value;
        });

        // Simular acciones de botones
        document.querySelectorAll('.btn-primary').forEach(btn => {
            btn.addEventListener('click', function() {
                if (this.innerHTML.includes('Nueva Reserva') && this.id !== 'mostrarFormBtn') {
                    document.getElementById('formReserva').style.display = 'block';
                }
            });
        });
    </script>
</body>
</html>