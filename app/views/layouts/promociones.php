<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promociones - Inversiones Rojas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
 <link rel="stylesheet" href="/inversiones-rojas/public/css/layouts/promociones.css">

     
    </style>
</head>
<body>
   

            <div class="admin-content">
                <!-- Stats Cards -->
                <div class="promociones-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="stat-info">
                            <h3>18</h3>
                            <p>Promociones Activas</p>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i>
                                15.5%
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3>245</h3>
                            <p>Ventas con Promoción</p>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i>
                                22.3%
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3>$12,850</h3>
                            <p>Ingresos por Promociones</p>
                            <div class="stat-trend trend-up">
                                <i class="fas fa-arrow-up"></i>
                                18.7%
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3>7</h3>
                            <p>Próximas a Vencer</p>
                            <div class="stat-trend trend-down">
                                <i class="fas fa-arrow-down"></i>
                                8.2%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="charts-grid">
                    <!-- Efectividad de Promociones -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Efectividad de Promociones</h3>
                            <div class="chart-actions">
                                <select class="chart-filter">
                                    <option>Este mes</option>
                                    <option>Mes anterior</option>
                                </select>
                            </div>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="efectividadChart"></canvas>
                        </div>
                    </div>

                    <!-- Ventas por Tipo de Promoción -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Ventas por Tipo de Promoción</h3>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="tipoPromocionesChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Formulario de Nueva Promoción (Inicialmente oculto) -->
                <div class="form-container" id="formPromocion" style="display: none;">
                    <h3 style="margin-bottom: 20px; color: #2c3e50;">Nueva Promoción</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nombrePromocion">Nombre de la Promoción</label>
                            <input type="text" class="form-control" id="nombrePromocion" placeholder="Ej: Oferta de Verano">
                        </div>
                        <div class="form-group">
                            <label for="tipoPromocion">Tipo de Promoción</label>
                            <select class="form-control" id="tipoPromocion">
                                <option value="">Seleccione tipo</option>
                                <option value="descuento">Descuento Porcentual</option>
                                <option value="monto">Descuento en Monto</option>
                                <option value="combo">Combo/Pack</option>
                                <option value="envio">Envío Gratis</option>
                                <option value="regalo">Producto de Regalo</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="valorPromocion">Valor de Promoción</label>
                            <input type="text" class="form-control" id="valorPromocion" placeholder="Ej: 15% o $50">
                        </div>
                        <div class="form-group">
                            <label for="fechaInicio">Fecha de Inicio</label>
                            <input type="date" class="form-control" id="fechaInicio">
                        </div>
                        <div class="form-group">
                            <label for="fechaFin">Fecha de Fin</label>
                            <input type="date" class="form-control" id="fechaFin">
                        </div>
                        <div class="form-group">
                            <label for="estadoPromocion">Estado</label>
                            <select class="form-control" id="estadoPromocion">
                                <option value="activa">Activa</option>
                                <option value="inactiva">Inactiva</option>
                                <option value="programada">Programada</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="productosPromocion">Productos Aplicables</label>
                            <select class="form-control" id="productosPromocion" multiple>
                                <option value="1">Yamaha MT-03 2024</option>
                                <option value="2">Honda CB190R</option>
                                <option value="3">Suzuki GSX-S150</option>
                                <option value="4">Honda XRE 300</option>
                                <option value="5">Yamaha R15</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="categoriasPromocion">Categorías Aplicables</label>
                            <select class="form-control" id="categoriasPromocion" multiple>
                                <option value="1">Motos Deportivas</option>
                                <option value="2">Motos Naked</option>
                                <option value="3">Motos Adventure</option>
                                <option value="4">Repuestos</option>
                                <option value="5">Accesorios</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="descripcionPromocion">Descripción</label>
                        <textarea class="form-control" id="descripcionPromocion" rows="3" placeholder="Descripción detallada de la promoción..."></textarea>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-secondary" id="cancelarPromocionBtn">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </button>
                        <button class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Guardar Promoción
                        </button>
                    </div>
                </div>

                <!-- Productos en Promoción -->
                <div class="productos-section">
                    <h3 style="margin-bottom: 20px; color: #2c3e50;">Productos en Promoción</h3>
                    <div class="productos-grid">
                        <div class="producto-card">
                            <div class="producto-img">
                                <i class="fas fa-motorcycle fa-2x"></i>
                            </div>
                            <div class="producto-name">Yamaha MT-03 2024</div>
                            <div class="producto-price">
                                <span class="price-old">$6,500</span>
                                <span class="price-new">$5,850</span>
                            </div>
                            <div class="producto-discount">10% OFF</div>
                        </div>
                        <div class="producto-card">
                            <div class="producto-img">
                                <i class="fas fa-motorcycle fa-2x"></i>
                            </div>
                            <div class="producto-name">Honda CB190R</div>
                            <div class="producto-price">
                                <span class="price-old">$3,200</span>
                                <span class="price-new">$2,880</span>
                            </div>
                            <div class="producto-discount">10% OFF</div>
                        </div>
                        <div class="producto-card">
                            <div class="producto-img">
                                <i class="fas fa-motorcycle fa-2x"></i>
                            </div>
                            <div class="producto-name">Suzuki GSX-S150</div>
                            <div class="producto-price">
                                <span class="price-old">$2,800</span>
                                <span class="price-new">$2,380</span>
                            </div>
                            <div class="producto-discount">15% OFF</div>
                        </div>
                        <div class="producto-card">
                            <div class="producto-img">
                                <i class="fas fa-motorcycle fa-2x"></i>
                            </div>
                            <div class="producto-name">Honda XRE 300</div>
                            <div class="producto-price">
                                <span class="price-old">$4,500</span>
                                <span class="price-new">$4,050</span>
                            </div>
                            <div class="producto-discount">10% OFF</div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="search-filters">
                    <div class="search-box">
                        <input type="text" placeholder="Buscar promociones...">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    <select class="filter-select">
                        <option>Todos los estados</option>
                        <option>Activa</option>
                        <option>Inactiva</option>
                        <option>Programada</option>
                        <option>Expirada</option>
                    </select>
                    <select class="filter-select">
                        <option>Todos los tipos</option>
                        <option>Descuento Porcentual</option>
                        <option>Descuento en Monto</option>
                        <option>Combo/Pack</option>
                        <option>Envío Gratis</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="promociones-actions">
                    <div class="action-buttons">
                        <button class="btn btn-primary" id="mostrarFormBtn">
                            <i class="fas fa-plus"></i>
                            Nueva Promoción
                        </button>
                        <button class="btn btn-secondary">
                            <i class="fab fa-whatsapp"></i>
                            Compartir en WhatsApp
                        </button>
                        <button class="btn btn-secondary">
                            <i class="fas fa-envelope"></i>
                            Enviar por Email
                        </button>
                        <button class="btn btn-warning">
                            <i class="fas fa-clock"></i>
                            Próximas a Vencer (7)
                        </button>
                        <button class="btn btn-danger">
                            <i class="fas fa-times"></i>
                            Desactivar Seleccionadas
                        </button>
                    </div>
                </div>

                <!-- Promociones Table -->
                <div class="promociones-table">
                    <div class="table-header">
                        <div>ID</div>
                        <div>Nombre</div>
                        <div>Tipo</div>
                        <div>Valor</div>
                        <div>Productos</div>
                        <div>Fecha Inicio</div>
                        <div>Fecha Fin</div>
                        <div>Estado</div>
                    </div>
                    
                    <!-- Promociones de ejemplo -->
                    <div class="table-row">
                        <div>
                            <strong>PROM-2024-015</strong>
                        </div>
                        <div>Oferta de Verano</div>
                        <div>Descuento %</div>
                        <div>15% OFF</div>
                        <div>8 productos</div>
                        <div>01/03/2024</div>
                        <div>31/03/2024</div>
                        <div><span class="status-badge status-active">Activa</span></div>
                    </div>
                    
                    <div class="table-row">
                        <div>
                            <strong>PROM-2024-014</strong>
                        </div>
                        <div>Combo Mantenimiento</div>
                        <div>Combo/Pack</div>
                        <div>$50 OFF</div>
                        <div>3 productos</div>
                        <div>15/02/2024</div>
                        <div>15/04/2024</div>
                        <div><span class="status-badge status-active">Activa</span></div>
                    </div>
                    
                    <div class="table-row">
                        <div>
                            <strong>PROM-2024-013</strong>
                        </div>
                        <div>Envío Gratis</div>
                        <div>Envío Gratis</div>
                        <div>$0 Envío</div>
                        <div>Todos</div>
                        <div>01/03/2024</div>
                        <div>31/03/2024</div>
                        <div><span class="status-badge status-active">Activa</span></div>
                    </div>

                    <div class="table-row">
                        <div>
                            <strong>PROM-2024-012</strong>
                        </div>
                        <div>Lanzamiento MT-03</div>
                        <div>Descuento %</div>
                        <div>10% OFF</div>
                        <div>1 producto</div>
                        <div>01/02/2024</div>
                        <div>28/02/2024</div>
                        <div><span class="status-badge status-expired">Expirada</span></div>
                    </div>

                    <div class="table-row">
                        <div>
                            <strong>PROM-2024-011</strong>
                        </div>
                        <div>Black Friday</div>
                        <div>Descuento %</div>
                        <div>20% OFF</div>
                        <div>12 productos</div>
                        <div>25/11/2024</div>
                        <div>30/11/2024</div>
                        <div><span class="status-badge status-scheduled">Programada</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Gráfica de Efectividad de Promociones
        const efectividadCtx = document.getElementById('efectividadChart').getContext('2d');
        const efectividadChart = new Chart(efectividadCtx, {
            type: 'bar',
            data: {
                labels: ['Oferta Verano', 'Combo Mantenimiento', 'Envío Gratis', 'Lanzamiento MT-03'],
                datasets: [{
                    label: 'Ventas Generadas',
                    data: [45, 28, 32, 19],
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

        // Gráfica de Ventas por Tipo de Promoción
        const tipoPromocionesCtx = document.getElementById('tipoPromocionesChart').getContext('2d');
        const tipoPromocionesChart = new Chart(tipoPromocionesCtx, {
            type: 'doughnut',
            data: {
                labels: ['Descuento %', 'Descuento Monto', 'Combo/Pack', 'Envío Gratis'],
                datasets: [{
                    data: [45, 25, 20, 10],
                    backgroundColor: [
                        '#1F9166',
                        '#3498db',
                        '#9b59b6',
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

        // Mostrar/ocultar formulario de nueva promoción
        document.getElementById('mostrarFormBtn').addEventListener('click', function() {
            document.getElementById('formPromocion').style.display = 'block';
        });

        document.getElementById('cancelarPromocionBtn').addEventListener('click', function() {
            document.getElementById('formPromocion').style.display = 'none';
        });

        // Actualizar campo de valor según tipo de promoción
        const tipoPromocionSelect = document.getElementById('tipoPromocion');
        const valorPromocionInput = document.getElementById('valorPromocion');
        
        tipoPromocionSelect.addEventListener('change', function() {
            if (this.value === 'descuento') {
                valorPromocionInput.placeholder = 'Ej: 15%';
            } else if (this.value === 'monto') {
                valorPromocionInput.placeholder = 'Ej: $50';
            } else if (this.value === 'envio') {
                valorPromocionInput.placeholder = 'Envío Gratis';
                valorPromocionInput.value = 'Envío Gratis';
            } else {
                valorPromocionInput.placeholder = 'Valor de la promoción';
            }
        });

        // Establecer fecha mínima para fecha de fin
        const fechaInicioInput = document.getElementById('fechaInicio');
        const fechaFinInput = document.getElementById('fechaFin');
        
        fechaInicioInput.addEventListener('change', function() {
            fechaFinInput.min = this.value;
        });

        // Simular acciones de botones
        document.querySelectorAll('.btn-primary').forEach(btn => {
            btn.addEventListener('click', function() {
                if (this.innerHTML.includes('Nueva Promoción') && this.id !== 'mostrarFormBtn') {
                    document.getElementById('formPromocion').style.display = 'block';
                }
            });
        });
    </script>
</body>
</html>