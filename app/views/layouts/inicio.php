<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
// Verificar si el usuario está logueado de forma segura
$usuario_logueado = false;
$user_name = '';
$user_rol = '';
$user_email = '';

if (isset($_SESSION['user_id']) && isset($_SESSION['user_name'])) {
    $usuario_logueado = true;
    $user_name = $_SESSION['user_name'];
    $user_rol = $_SESSION['user_rol'] ?? 'cliente'; // Valor por defecto
    $user_email = $_SESSION['user_email'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inversiones Rojas - Motos y Repuestos</title>
    <link rel="icon" href="<?php echo BASE_URL; ?>/public/img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/pages/auth.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/admin.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/pages/home.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/components/user-panel.css">
</head>
<body>
       <!-- Barra superior con buscador - CORREGIDA -->
    <div class="top-bar">
        <div class="container top-bar-content">
            <!-- Logo y botón de menú juntos -->
            <div class="logo-section">
                <button class="nav-btn" id="navToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="logo">
                    <a href="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/" class="logo-link">
                        <i class="fas fa-motorcycle logo-icon"></i>
                        <h1>Inversiones Rojas</h1>
                    </a>
                </div>
            </div>
            
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-bar" placeholder="Buscar...">
            </div>
            
            <div class="top-actions">
                <button class="icon-btn">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count">3</span>
                </button>
                
                <div class="auth-buttons">
                        <?php if (!$usuario_logueado): ?>
                        <a href="<?php echo BASE_URL; ?>/app/views/auth/Login.php" class="auth-btn login-btn">Iniciar Sesión</a>
                        <a href="<?php echo BASE_URL; ?>/app/views/auth/register.php" class="auth-btn register-btn">Registrarse</a>
            <?php else: ?>
    <?php require __DIR__ . '/partials/user_panel.php'; ?>
<?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Header y Navegación -->
    <header>
        <div class="container header-content">
            <nav>
                <ul id="navMenu">
                    <li><a href="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/">Inicio</a></li>
                    <li><a href="#">Motos</a></li>
                    <li><a href="#">Repuestos</a></li>
                    <li><a href="#">Accesorios</a></li>
                    <li><a href="#">Ayuda</a></li>
                    <li><a href="#">Contacto</a></li>
                    <!-- Botones de autenticación para móvil -->
                    <li class="auth-mobile">
                        <?php if (!$usuario_logueado): ?>
                            <a href="<?php echo BASE_URL; ?>/app/views/auth/Login.php" class="auth-btn login-btn mobile-auth-btn">Iniciar Sesión</a>
                            <a href="<?php echo BASE_URL; ?>/app/views/auth/register.php" class="auth-btn register-btn mobile-auth-btn">Registrarse</a>
                        <?php else: ?>
                            <div class="user-info-mobile">
                                <span>Hola, <?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?></span>
                                <?php if ($user_rol !== 'cliente'): ?>
                                    <a href="<?php echo BASE_URL; ?>/app/views/layouts/Dashboard.php" class="auth-btn">Mi Panel</a>
                                <?php endif; ?>
                                <a href="<?php echo BASE_URL; ?>/logout.php" class="auth-btn">Cerrar Sesión</a>
                            </div>
                        <?php endif; ?>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero Section con Texto Fijo y Slider -->
    <div class="hero-section">
        <div class="static-content">
            <h2 class="main-title">Encuentra lo que necesitas</h2>
            <h3 class="subtitle">Más de 4000 motos y repuestos disponibles en Inversiones Rojas</h3>
            <p class="description">Inversiones Rojas es tu concesionario de confianza con la mejor variedad de motos, repuestos y accesorios al mejor precio.</p>
            <a href="#" class="cta-button">Conoce más...</a>
        </div>
        
        <div class="hero-slider-container">
            <div class="hero-slider" id="heroSlider">
                <div class="hero-slide">
                    <img src="https://images.unsplash.com/photo-1609630875171-b1321377ee65?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Moto scooters">
                </div>
                <div class="hero-slide">
                    <img src="https://images.unsplash.com/photo-1517846693594-1567da72af75?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Moto custom">
                </div>
            </div>
            <div class="hero-slider-nav" id="heroSliderNav">
                <div class="hero-slider-dot active"></div>
                <div class="hero-slider-dot"></div>
            </div>
        </div>
    </div>

    <!-- Sección Catálogo -->
    <section class="catalog-section">
        <div class="catalog-container">
            <h2 class="section-title">Explora nuestro Catálogo</h2>
            <div class="filters-container">
                <div class="filter-box">
                    <select>
                        <option>Año</option>
                        <option>2022</option>
                        <option>2023</option>
                        <option>2024</option>
                    </select>
                </div>
                <div class="filter-box">
                    <select>
                        <option>Marca</option>
                        <option>Bera</option>
                        <option>Empire</option>
                        <option>Kawasaki</option>
                    </select>
                </div>
                <div class="filter-box">
                    <select>
                        <option>Modelo</option>
                        <option>BR 200</option>
                        <option>Scooter</option>
                        <option>Custom</option>
                    </select>
                </div>
                <div class="filter-box">
                    <select>
                        <option>Categoría</option>
                        <option>Motos</option>
                        <option>Repuestos</option>
                        <option>Accesorios</option>
                    </select>
                </div>
            </div>
            <button class="catalog-button">Buscar en Catálogo</button>
        </div>
    </section>

    <!-- Sección de Promociones Especiales -->
    <section class="promotions-section">
        <div class="container">
            <div class="promotions-header">
                <h2 class="promotions-title">PROMOCIONES ESPECIALES</h2>
            </div>
           
            <!-- Sección de Sliders de Promociones - CORREGIDOS PARA MOSTRAR TODOS -->
            <div class="promo-sliders-container">
                <!-- Slider Principal -->
                <div class="main-promo-slider" id="main-promo-slider">
                    <div class="promo-slide active">
                        <div class="promo-slide-image">
                            <img src="https://images.unsplash.com/photo-1558981806-ec527fa84c39?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Promoción 1">
                        </div>
                        <div class="promo-slide-description">
                            <h3>Oferta Especial de Verano</h3>
                            <p>Descuentos increíbles en productos seleccionados</p>
                        </div>
                    </div>
                    <div class="promo-slide">
                        <div class="promo-slide-image">
                            <img src="https://images.unsplash.com/photo-1561361513-2d000a50f0dc?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Promoción 2">
                        </div>
                        <div class="promo-slide-description">
                            <h3>Promoción Relámpago</h3>
                            <p>Solo por 48 horas, precios especiales en toda la tienda</p>
                        </div>
                    </div>
                    <div class="promo-slide">
                        <div class="promo-slide-image">
                            <img src="https://images.unsplash.com/photo-1503376780353-7e6692767b70?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Promoción 3">
                        </div>
                        <div class="promo-slide-description">
                            <h3>Fin de Semana de Descuentos</h3>
                            <p>Aprovecha hasta 40% off en productos seleccionados</p>
                        </div>
                    </div>
                    
                    <div class="promo-slider-nav prev" data-slider="main-promo-slider">
                        <i class="fas fa-chevron-left"></i>
                    </div>
                    <div class="promo-slider-nav next" data-slider="main-promo-slider">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>
                
                <!-- Sliders Secundarios - AHORA SE MUESTRAN TODOS -->
                <div class="secondary-promo-sliders">
                    <div class="secondary-promo-slider" id="secondary-promo-slider-1">
                        <div class="promo-slide active">
                            <div class="promo-slide-image">
                                <img src="https://images.unsplash.com/photo-1553440569-bcc63803a83d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Promo 1">
                            </div>
                            <div class="promo-slide-description">
                                <h3>Promo Flash</h3>
                                <p>Ofertas por tiempo limitado, ¡aprovecha ahora!</p>
                            </div>
                        </div>
                        <div class="promo-slide">
                            <div class="promo-slide-image">
                                <img src="https://images.unsplash.com/photo-1563720223880-4d93eef1f0c4?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Promo 2">
                            </div>
                            <div class="promo-slide-description">
                                <h3>Descuentos Exclusivos</h3>
                                <p>Solo para clientes registrados en nuestra web</p>
                            </div>
                        </div>
                        
                        <div class="promo-slider-nav prev" data-slider="secondary-promo-slider-1">
                            <i class="fas fa-chevron-left"></i>
                        </div>
                        <div class="promo-slider-nav next" data-slider="secondary-promo-slider-1">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </div>
                    
                    <div class="secondary-promo-slider" id="secondary-promo-slider-2">
                        <div class="promo-slide active">
                            <div class="promo-slide-image">
                                <img src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Promo 3">
                            </div>
                            <div class="promo-slide-description">
                                <h3>Ofertas de Temporada</h3>
                                <p>Productos especiales con precios increíbles</p>
                            </div>
                        </div>
                        <div class="promo-slide">
                            <div class="promo-slide-image">
                                <img src="https://images.unsplash.com/photo-1532298229144-0ec0c57515c7?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Promo 4">
                            </div>
                            <div class="promo-slide-description">
                                <h3>Compra 2x1</h3>
                                <p>Lleva dos productos al precio de uno en artículos seleccionados</p>
                            </div>
                        </div>
                        
                        <div class="promo-slider-nav prev" data-slider="secondary-promo-slider-2">
                            <i class="fas fa-chevron-left"></i>
                        </div>
                        <div class="promo-slider-nav next" data-slider="secondary-promo-slider-2">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Galería de imágenes decorativas -->
            <div class="decorative-gallery">
                <div class="decorative-image">
                    <img src="<?php echo BASE_URL; ?>/public/img/imagen1.png" alt="Imagen decorativa 1">
                </div>
                <div class="decorative-image">
                    <img src="<?php echo BASE_URL; ?>/public/img/imagen2.png"  alt="Imagen decorativa 2">
                </div>
                <div class="decorative-image">
                    <img src="<?php echo BASE_URL; ?>/public/img/imagen3.png" alt="Imagen decorativa 3">
                </div>
                <div class="decorative-image">
                    <img src="<?php echo BASE_URL; ?>/public/img/imagen4.png"  alt="Imagen decorativa 4">
                </div>
            </div>
            
            <div class="promo-footer">
                <p>TENEMOS TODO LO QUE BUSCAS PARA TU MOTO</p>
            </div>
        </div>
    </section>

    <!-- Sección de Sliders de Productos y Vehículos -->
    <section class="sliders-section">
        <div class="container">
            <!-- Slider de Mejores Productos -->
            <div class="slider-container-custom">
                <div class="slider-header-custom">MEJORES PRODUCTOS</div>
                
                <div class="slider-custom">
                    <div class="slider-control-custom prev-custom" id="prev-productos">
                        <i class="fas fa-chevron-left"></i>
                    </div>
                    
                    <div class="slider-track-custom" id="track-productos">
                        <!-- Producto 1 -->
                        <div class="producto-custom">
                            <div class="producto-imagen-custom">Imagen de Casco + Guantes</div>
                            <div class="producto-info-custom">
                                <h3 class="producto-titulo-custom">Casco integral + guantes</h3>
                                <p class="producto-descripcion-custom">Protegete con estilo y disfruta de nuestros cascos junto con unos elegantes guantes</p>
                                <div class="producto-precio-custom">50 $</div>
                                <a href="#" class="boton-detalles-custom">Detalles</a>
                            </div>
                        </div>
                        
                        <!-- Producto 2 -->
                        <div class="producto-custom">
                            <div class="producto-imagen-custom">Imagen de Tanque Bera</div>
                            <div class="producto-info-custom">
                                <h3 class="producto-titulo-custom">Tanque Bera <span class="km-badge-custom">0km</span></h3>
                                <p class="producto-descripcion-custom">Tanque de Gasolina Bera Sbr Color Fucsia 100% Original</p>
                                <div class="producto-precio-custom">83 $</div>
                                <a href="#" class="boton-detalles-custom">Detalles</a>
                            </div>
                        </div>
                        
                        <!-- Producto 3 -->
                        <div class="producto-custom">
                            <div class="producto-imagen-custom">Imagen de Tapa de crochera</div>
                            <div class="producto-info-custom">
                                <h3 class="producto-titulo-custom">Tapa de crochera bera</h3>
                                <p class="producto-descripcion-custom">Tapa de Crochera para una Moto Bera Dt 200 Original</p>
                                <div class="producto-precio-custom">30 $</div>
                                <a href="#" class="boton-detalles-custom">Detalles</a>
                            </div>
                        </div>
                        
                        <!-- Producto 4 -->
                        <div class="producto-custom">
                            <div class="producto-imagen-custom">Imagen de Cigueñal</div>
                            <div class="producto-info-custom">
                                <h3 class="producto-titulo-custom">Cigueñal Bera</h3>
                                <p class="producto-descripcion-custom">Repuesto de moto bera - Cigueñal Bera RI 200cc</p>
                                <div class="producto-precio-custom">100 $</div>
                                <a href="#" class="boton-detalles-custom">Detalles</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="slider-control-custom next-custom" id="next-productos">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>
            </div>
            
            <!-- Slider de Mejores Vehículos -->
            <div class="slider-container-custom">
                <div class="slider-header-custom">MEJORES VEHÍCULOS</div>
                
                <div class="slider-custom">
                    <div class="slider-control-custom prev-custom" id="prev-vehiculos">
                        <i class="fas fa-chevron-left"></i>
                    </div>
                    
                    <div class="slider-track-custom" id="track-vehiculos">
                        <!-- Vehículo 1 -->
                        <div class="producto-custom">
                            <div class="producto-imagen-custom">Imagen de Bera Br 200</div>
                            <div class="producto-info-custom">
                                <h3 class="producto-titulo-custom">Bera Br 200 (2021) <span class="km-badge-custom">0km</span></h3>
                                <div class="producto-precio-custom">1,820 $</div>
                                <a href="#" class="boton-detalles-custom">Detalles</a>
                            </div>
                        </div>
                        
                        <!-- Vehículo 2 -->
                        <div class="producto-custom">
                            <div class="producto-imagen-custom">Imagen de Bera Br 200</div>
                            <div class="producto-info-custom">
                                <h3 class="producto-titulo-custom">Bera Br 200 (2021) <span class="km-badge-custom">0km</span></h3>
                                <div class="producto-precio-custom">1,820 $</div>
                                <a href="#" class="boton-detalles-custom">Detalles</a>
                            </div>
                        </div>
                        
                        <!-- Vehículo 3 -->
                        <div class="producto-custom">
                            <div class="producto-imagen-custom">Imagen de Bera Br 200</div>
                            <div class="producto-info-custom">
                                <h3 class="producto-titulo-custom">Bera Br 200 (2021) <span class="km-badge-custom">0km</span></h3>
                                <div class="producto-precio-custom">1,820 $</div>
                                <a href="#" class="boton-detalles-custom">Detalles</a>
                            </div>
                        </div>
                        
                        <!-- Vehículo 4 -->
                        <div class="producto-custom">
                            <div class="producto-imagen-custom">Imagen de Bera Br 200</div>
                            <div class="producto-info-custom">
                                <h3 class="producto-titulo-custom">Bera Br 200 (2021) <span class="km-badge-custom">0km</span></h3>
                                <div class="producto-precio-custom">1,820 $</div>
                                <a href="#" class="boton-detalles-custom">Detalles</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="slider-control-custom next-custom" id="next-vehiculos">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer-custom">
        <div class="footer-content-custom">
            <div class="footer-section-custom">
                <h3>Acerca de Nosotros</h3>
                <p>Somos una tienda especializada en repuestos y vehículos Bera, ofreciendo productos de alta calidad y el mejor servicio al cliente.</p>
            </div>
            
            <div class="footer-section-custom">
                <h3>Contacto</h3>
                <p><i class="fas fa-map-marker-alt"></i> Av. Principal 123, Ciudad</p>
                <p><i class="fas fa-phone"></i> +1 234 567 890</p>
                <p><i class="fas fa-envelope"></i> info@inversionesrojas.com</p>
            </div>
            
            <div class="footer-section-custom">
                <h3>Enlaces Rápidos</h3>
                <a href="#">Inicio</a>
                <a href="#">Motos</a>
                <a href="#">Repuestos</a>
                <a href="#">Contacto</a>
            </div>
            
            <div class="footer-section-custom">
                <h3>Síguenos</h3>
                <div class="social-links-custom">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom-custom">
            <p>&copy; 2023 Inversiones Rojas. Todos los derechos reservados.</p>
        </div>
    </footer>
    <script src="<?php echo BASE_URL; ?>/public/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/components/user-panel.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/script.js"></script>
</body>
</html>