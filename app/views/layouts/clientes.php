<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Clientes - Inversiones Rojas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
 <link rel="stylesheet" href="/inversiones-rojas/public/css/layouts/clientes.css">

</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php require __DIR__ . '/partials/sidebar_menu.php'; ?>

        <!-- Main Content -->
        <div class="admin-main">
            <div class="admin-header">
                <div class="header-left">
                    <h1>Gestión de Clientes</h1>
                    <div class="breadcrumb">
                        <a href="#">Inicio</a>
                        <span>/</span>
                        <span>Clientes</span>
                    </div>
                </div>
            </div>

            <div class="admin-content">
                <!-- Clients Stats -->
                <div class="clients-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number">2,847</div>
                        <div class="stat-label">Total Clientes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="stat-number">84</div>
                        <div class="stat-label">Nuevos Hoy</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-number">156</div>
                        <div class="stat-label">Clientes VIP</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-number">1.2k</div>
                        <div class="stat-label">Compras Activas</div>
                    </div>
                </div>

                <!-- Action Bar -->
                <div class="clients-actions">
                    <div class="action-bar">
                        <div class="search-box">
                            <input type="text" placeholder="Buscar clientes...">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                        <div class="action-buttons">
                            <button class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                Nuevo Cliente
                            </button>
                            <button class="btn btn-secondary">
                                <i class="fas fa-file-export"></i>
                                Exportar
                            </button>
                            <button class="btn btn-secondary">
                                <i class="fas fa-filter"></i>
                                Filtros
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Clients Table -->
                <div class="clients-table">
                    <div class="table-header">
                        <div>Cliente</div>
                        <div>Contacto</div>
                        <div>Última Compra</div>
                        <div>Total Gastado</div>
                        <div>Estado</div>
                    </div>
                    
                    <div class="placeholder-section">
                        <div class="placeholder-large">
                            <i class="fas fa-users"></i>
                            <h3>Lista de Clientes</h3>
                            <p>Área para tabla de gestión de clientes</p>
                            <p style="font-size: 0.9rem; margin-top: 10px;">Incluye búsqueda, filtros y acciones de cliente</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>