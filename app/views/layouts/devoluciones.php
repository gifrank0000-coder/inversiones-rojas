<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devoluciones - Inversiones Rojas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
 <link rel="stylesheet" href="/inversiones-rojas/public/css/layouts/devoluciones.css">
 
</head>
<body>


            <div class="admin-content">
                <!-- Stats Cards -->
                <div class="devoluciones-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3>24</h3>
                            <p>Devoluciones Pendientes</p>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i>
                                8.5%
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3>156</h3>
                            <p>Devoluciones Aprobadas</p>
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
                            <h3>$3,450</h3>
                            <p>Valor en Devoluciones</p>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i>
                                12.7%
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="stat-info">
                            <h3>2.8%</h3>
                            <p>Tasa de Devolución</p>
                            <div class="stat-trend trend-down">
                                <i class="fas fa-arrow-down"></i>
                                1.2%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="charts-grid">
                    <!-- Devoluciones por Motivo -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Devoluciones por Motivo</h3>
                            <div class="chart-actions">
                                <select class="chart-filter">
                                    <option>Este mes</option>
                                    <option>Mes anterior</option>
                                </select>
                            </div>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="motivosChart"></canvas>
                        </div>
                    </div>

                    <!-- Tendencias de Devoluciones -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Tendencias de Devoluciones</h3>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="tendenciasChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Formulario de Nueva Devolución (Inicialmente oculto) -->
                <div class="form-container" id="formDevolucion" style="display: none;">
                    <h3 style="margin-bottom: 20px; color: #2c3e50;">Nueva Devolución</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="ventaDevolucion">Venta/Orden</label>
                            <select class="form-control" id="ventaDevolucion">
                                <option value="">Seleccione venta</option>
                                <option value="1">VENT-2024-087 - Laura González</option>
                                <option value="2">VENT-2024-086 - Miguel Torres</option>
                                <option value="3">VENT-2024-085 - Carlos Mendoza</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="clienteDevolucion">Cliente</label>
                            <input type="text" class="form-control" id="clienteDevolucion" readonly>
                        </div>
                        <div class="form-group">
                            <label for="productoDevolucion">Producto</label>
                            <select class="form-control" id="productoDevolucion">
                                <option value="">Seleccione producto</option>
                                <option value="1">Yamaha MT-03 2024</option>
                                <option value="2">Honda CB190R</option>
                                <option value="3">Suzuki GSX-S150</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="cantidadDevolucion">Cantidad</label>
                            <input type="number" class="form-control" id="cantidadDevolucion" min="1" value="1">
                        </div>
                        <div class="form-group">
                            <label for="motivoDevolucion">Motivo</label>
                            <select class="form-control" id="motivoDevolucion">
                                <option value="">Seleccione motivo</option>
                                <option value="defectuoso">Producto Defectuoso</option>
                                <option value="incorrecto">Producto Incorrecto</option>
                                <option value="arrepentimiento">Arrepentimiento</option>
                                <option value="danado">Producto Dañado</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="fechaDevolucion">Fecha de Devolución</label>
                            <input type="date" class="form-control" id="fechaDevolucion">
                        </div>
                        <div class="form-group">
                            <label for="montoDevolucion">Monto a Devolver ($)</label>
                            <input type="number" class="form-control" id="montoDevolucion" min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label for="estadoDevolucion">Estado</label>
                            <select class="form-control" id="estadoDevolucion">
                                <option value="pendiente">Pendiente</option>
                                <option value="aprobado">Aprobado</option>
                                <option value="rechazado">Rechazado</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="descripcionDevolucion">Descripción/Comentarios</label>
                        <textarea class="form-control" id="descripcionDevolucion" rows="3" placeholder="Descripción detallada del motivo de la devolución..."></textarea>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-secondary" id="cancelarDevolucionBtn">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </button>
                        <button class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Guardar Devolución
                        </button>
                    </div>
                </div>

                <!-- Motivos de Devolución -->
                <div class="motivos-section">
                    <h3 style="margin-bottom: 20px; color: #2c3e50;">Motivos de Devolución Más Comunes</h3>
                    <div class="motivos-grid">
                        <div class="motivo-card">
                            <div class="motivo-header">
                                <div class="motivo-name">Producto Defectuoso</div>
                                <div class="motivo-count">45 casos</div>
                            </div>
                            <div class="motivo-description">
                                Producto presenta fallas de fabricación o no funciona correctamente.
                            </div>
                        </div>
                        <div class="motivo-card">
                            <div class="motivo-header">
                                <div class="motivo-name">Arrepentimiento</div>
                                <div class="motivo-count">38 casos</div>
                            </div>
                            <div class="motivo-description">
                                Cliente cambia de opinión después de realizar la compra.
                            </div>
                        </div>
                        <div class="motivo-card">
                            <div class="motivo-header">
                                <div class="motivo-name">Producto Incorrecto</div>
                                <div class="motivo-count">22 casos</div>
                            </div>
                            <div class="motivo-description">
                                Se envió producto diferente al solicitado por el cliente.
                            </div>
                        </div>
                        <div class="motivo-card">
                            <div class="motivo-header">
                                <div class="motivo-name">Producto Dañado</div>
                                <div class="motivo-count">18 casos</div>
                            </div>
                            <div class="motivo-description">
                                Producto llegó con daños visibles durante el transporte.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="search-filters">
                    <div class="search-box">
                        <input type="text" placeholder="Buscar devoluciones...">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    <select class="filter-select">
                        <option>Todos los estados</option>
                        <option>Pendiente</option>
                        <option>Aprobado</option>
                        <option>Rechazado</option>
                        <option>Completado</option>
                        <option>Reintegrado</option>
                    </select>
                    <select class="filter-select">
                        <option>Todos los motivos</option>
                        <option>Producto Defectuoso</option>
                        <option>Arrepentimiento</option>
                        <option>Producto Incorrecto</option>
                        <option>Producto Dañado</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="devoluciones-actions">
                    <div class="action-buttons">
                        <button class="btn btn-primary" id="mostrarFormBtn">
                            <i class="fas fa-plus"></i>
                            Nueva Devolución
                        </button>
                        <button class="btn btn-secondary">
                            <i class="fas fa-check"></i>
                            Aprobar Seleccionadas
                        </button>
                        <button class="btn btn-danger">
                            <i class="fas fa-times"></i>
                            Rechazar Seleccionadas
                        </button>
                        <button class="btn btn-warning">
                            <i class="fas fa-clock"></i>
                            Pendientes (24)
                        </button>
                        <button class="btn btn-secondary">
                            <i class="fas fa-file-pdf"></i>
                            Generar Reporte
                        </button>
                    </div>
                </div>

                <!-- Devoluciones Table -->
                <div class="devoluciones-table">
                    <div class="table-header">
                        <div>ID</div>
                        <div>Cliente</div>
                        <div>Producto</div>
                        <div>Motivo</div>
                        <div>Fecha</div>
                        <div>Monto</div>
                        <div>Venta</div>
                        <div>Estado</div>
                    </div>
                    
                    <!-- Devoluciones de ejemplo -->
                    <div class="table-row">
                        <div>
                            <strong>DEV-2024-045</strong>
                        </div>
                        <div>Miguel Torres</div>
                        <div>Yamaha MT-03 2024</div>
                        <div>Producto Defectuoso</div>
                        <div>15/03/2024</div>
                        <div>$450.00</div>
                        <div>VENT-2024-086</div>
                        <div><span class="status-badge status-pending">Pendiente</span></div>
                    </div>
                    
                    <div class="table-row">
                        <div>
                            <strong>DEV-2024-044</strong>
                        </div>
                        <div>Ana Rodríguez</div>
                        <div>Honda CB190R</div>
                        <div>Arrepentimiento</div>
                        <div>14/03/2024</div>
                        <div>$320.00</div>
                        <div>VENT-2024-084</div>
                        <div><span class="status-badge status-approved">Aprobado</span></div>
                    </div>
                    
                    <div class="table-row">
                        <div>
                            <strong>DEV-2024-043</strong>
                        </div>
                        <div>Carlos Mendoza</div>
                        <div>Suzuki GSX-S150</div>
                        <div>Producto Incorrecto</div>
                        <div>12/03/2024</div>
                        <div>$280.00</div>
                        <div>VENT-2024-082</div>
                        <div><span class="status-badge status-refunded">Reintegrado</span></div>
                    </div>

                    <div class="table-row">
                        <div>
                            <strong>DEV-2024-042</strong>
                        </div>
                        <div>Roberto Silva</div>
                        <div>Yamaha R15</div>
                        <div>Producto Dañado</div>
                        <div>10/03/2024</div>
                        <div>$240.00</div>
                        <div>VENT-2024-080</div>
                        <div><span class="status-badge status-completed">Completado</span></div>
                    </div>

                    <div class="table-row">
                        <div>
                            <strong>DEV-2024-041</strong>
                        </div>
                        <div>Laura González</div>
                        <div>Honda XRE 300</div>
                        <div>Producto Defectuoso</div>
                        <div>08/03/2024</div>
                        <div>$400.00</div>
                        <div>VENT-2024-078</div>
                        <div><span class="status-badge status-rejected">Rechazado</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Gráfica de Devoluciones por Motivo
        const motivosCtx = document.getElementById('motivosChart').getContext('2d');
        const motivosChart = new Chart(motivosCtx, {
            type: 'doughnut',
            data: {
                labels: ['Defectuoso', 'Arrepentimiento', 'Incorrecto', 'Dañado', 'Otro'],
                datasets: [{
                    data: [45, 38, 22, 18, 12],
                    backgroundColor: [
                        '#e74c3c',
                        '#3498db',
                        '#9b59b6',
                        '#f39c12',
                        '#95a5a6'
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

        // Gráfica de Tendencias de Devoluciones
        const tendenciasCtx = document.getElementById('tendenciasChart').getContext('2d');
        const tendenciasChart = new Chart(tendenciasCtx, {
            type: 'line',
            data: {
                labels: ['Ene', 'Feb', 'Mar'],
                datasets: [{
                    label: 'Devoluciones',
                    data: [18, 22, 24],
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    borderColor: '#e74c3c',
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
                        display: false
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

        // Mostrar/ocultar formulario de nueva devolución
        document.getElementById('mostrarFormBtn').addEventListener('click', function() {
            document.getElementById('formDevolucion').style.display = 'block';
        });

        document.getElementById('cancelarDevolucionBtn').addEventListener('click', function() {
            document.getElementById('formDevolucion').style.display = 'none';
        });

        // Actualizar cliente cuando se selecciona una venta
        const ventaSelect = document.getElementById('ventaDevolucion');
        const clienteInput = document.getElementById('clienteDevolucion');
        
        ventaSelect.addEventListener('change', function() {
            if (this.value === '1') {
                clienteInput.value = 'Laura González';
            } else if (this.value === '2') {
                clienteInput.value = 'Miguel Torres';
            } else if (this.value === '3') {
                clienteInput.value = 'Carlos Mendoza';
            } else {
                clienteInput.value = '';
            }
        });

        // Simular acciones de botones
        document.querySelectorAll('.btn-primary').forEach(btn => {
            btn.addEventListener('click', function() {
                if (this.innerHTML.includes('Nueva Devolución') && this.id !== 'mostrarFormBtn') {
                    document.getElementById('formDevolucion').style.display = 'block';
                }
            });
        });
    </script>
</body>
</html>