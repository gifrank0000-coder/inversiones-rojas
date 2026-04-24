<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';

// Verificar si el usuario está logueado
$usuario_logueado = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acerca de Nosotros - Inversiones Rojas</title>
    <link rel="icon" href="<?php echo BASE_URL; ?>/public/img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/pages/auth.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/admin.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/pages/home.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/components/user-panel.css">
    <style>
        .about-page {
            padding: 40px 0;
            min-height: 70vh;
        }

        .page-header {
            text-align: center;
            margin-bottom: 50px;
            padding: 40px 20px;
            background: linear-gradient(135deg, #1F9166 0%, #2ecc71 100%);
            color: white;
            border-radius: 16px;
        }

        .page-header h1 {
            font-size: 2.8rem;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .page-header p {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto;
            opacity: 0.95;
        }

        .header-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }

        /* Secciones */
        .about-section {
            background: white;
            padding: 40px;
            margin-bottom: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .about-section h2 {
            font-size: 1.8em;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .about-section h2 i {
            color: #1F9166;
            font-size: 1.3em;
        }

        .about-section p {
            color: #555;
            line-height: 1.8;
            font-size: 1.05em;
            margin-bottom: 15px;
        }

        .about-section p:last-child {
            margin-bottom: 0;
        }

        /* Grid de categorías */
        .categories-section {
            margin-top: 50px;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .category-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e8f6f1 100%);
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 30px 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .category-card:hover {
            border-color: #1F9166;
            box-shadow: 0 10px 30px rgba(31, 145, 102, 0.15);
            transform: translateY(-8px);
        }

        .category-icon {
            font-size: 48px;
            color: #1F9166;
            margin-bottom: 15px;
        }

        .category-card h3 {
            color: #2c3e50;
            font-size: 1.3em;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .category-card p {
            color: #666;
            font-size: 0.95em;
            line-height: 1.6;
            margin: 0;
        }

        /* Lista de ventajas */
        .why-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .why-item {
            display: flex;
            gap: 15px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #1F9166;
        }

        .why-item i {
            color: #1F9166;
            font-size: 1.5em;
            flex-shrink: 0;
        }

        .why-item h4 {
            color: #2c3e50;
            margin: 0 0 5px 0;
            font-weight: 600;
        }

        .why-item p {
            color: #666;
            margin: 0;
            font-size: 0.95em;
            line-height: 1.5;
        }

        /* Grid de contacto */
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .contact-item {
            background: linear-gradient(135deg, #f8f9fa 0%, #e8f6f1 100%);
            padding: 30px 25px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
        }

        .contact-item:hover {
            border-color: #1F9166;
            box-shadow: 0 8px 20px rgba(31, 145, 102, 0.1);
        }

        .contact-item i {
            color: #1F9166;
            font-size: 2em;
            margin-bottom: 15px;
        }

        .contact-item strong {
            display: block;
            color: #2c3e50;
            font-size: 1.1em;
            margin-bottom: 10px;
        }

        .contact-item p {
            color: #666;
            font-size: 0.95em;
            margin: 0;
            line-height: 1.6;
        }

        /* Botones de acción */
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 50px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1F9166 0%, #2ecc71 100%);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #156b4d 0%, #1F9166 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(31, 145, 102, 0.3);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2em;
            }

            .page-header p {
                font-size: 1em;
            }

            .about-section {
                padding: 25px;
            }

            .about-section h2 {
                font-size: 1.5em;
            }

            .header-icon {
                font-size: 45px;
            }

            .categories-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php require __DIR__ . '/partials/header.php'; ?>

    <main class="about-page container">
        <!-- Encabezado -->
        <div class="page-header">
            <div class="header-icon">
                <i class="fas fa-motorcycle"></i>
            </div>
            <h1>Inversiones Rojas 2016 C.A.</h1>
            <p>Tu concesionario de confianza para motos, repuestos y accesorios</p>
        </div>

        <!-- Sección: Sobre Nosotros -->
        <div class="about-section">
            <h2><i class="fas fa-info-circle"></i> Sobre Nosotros</h2>
            <p>INVERSIONES ROJAS 2016 C.A. es una empresa especializada en la venta y distribución de motos, repuestos y accesorios de alta calidad. Con más de 10 años en el mercado, nos hemos ganado la confianza de miles de clientes a través de nuestro compromiso con la excelencia y el mejor servicio.</p>
            <p>Nuestro objetivo es proporcionar a nuestros clientes una experiencia de compra excepcional, con una amplia variedad de productos de las mejores marcas, precios competitivos y un equipo de profesionales dispuestos a ayudarte en cada paso del camino.</p>
        </div>

        <!-- Sección: Nuestras Categorías -->
        <div class="about-section categories-section">
            <h2><i class="fas fa-th"></i> Nuestras Categorías</h2>
            <div class="categories-grid">
                <div class="category-card" onclick="window.location.href='<?php echo BASE_URL; ?>/app/views/layouts/motos.php'">
                    <div class="category-icon">
                        <i class="fas fa-motorcycle"></i>
                    </div>
                    <h3>Motos y Vehículos</h3>
                    <p>Amplio catálogo de motos nuevas y usadas de las mejores marcas del mercado</p>
                </div>

                <div class="category-card" onclick="window.location.href='<?php echo BASE_URL; ?>/app/views/layouts/repuestos.php'">
                    <div class="category-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h3>Repuestos</h3>
                    <p>Repuestos originales y compatibles para mantener tu moto en óptimas condiciones</p>
                </div>

                <div class="category-card" onclick="window.location.href='<?php echo BASE_URL; ?>/app/views/layouts/accesorios.php'">
                    <div class="category-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <h3>Accesorios</h3>
                    <p>Accesorios y complementos para personalizar y mejorar tu experiencia de conducción</p>
                </div>
            </div>
        </div>

        <!-- Sección: ¿Por Qué Elegirnos? -->
        <div class="about-section">
            <h2><i class="fas fa-star"></i> ¿Por Qué Elegirnos?</h2>
            <div class="why-list">
                <div class="why-item">
                    <i class="fas fa-box"></i>
                    <div>
                        <h4>+ 4000 Productos</h4>
                        <p>Amplio catálogo con más de 4000 productos disponibles</p>
                    </div>
                </div>

                <div class="why-item">
                    <i class="fas fa-tag"></i>
                    <div>
                        <h4>Precios Competitivos</h4>
                        <p>Los mejores precios del mercado con promociones especiales</p>
                    </div>
                </div>

                <div class="why-item">
                    <i class="fas fa-headset"></i>
                    <div>
                        <h4>Atención Personalizada</h4>
                        <p>Equipo de profesionales dispuestos a ayudarte en todo momento</p>
                    </div>
                </div>

                <div class="why-item">
                    <i class="fas fa-truck"></i>
                    <div>
                        <h4>Envíos Rápidos</h4>
                        <p>Entregas seguras y puntuales en toda la región</p>
                    </div>
                </div>

                <div class="why-item">
                    <i class="fas fa-certificate"></i>
                    <div>
                        <h4>Garantía Total</h4>
                        <p>Garantía en todos nuestros productos y servicios</p>
                    </div>
                </div>

                <div class="why-item">
                    <i class="fas fa-heart"></i>
                    <div>
                        <h4>Satisfacción del Cliente</h4>
                        <p>Tu satisfacción es nuestra prioridad principal</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección: Contacto -->
        <div class="about-section">
            <h2><i class="fas fa-phone"></i> Contáctanos</h2>
            <div class="contact-grid">
                <div class="contact-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <strong>Ubicación</strong>
                    <p>Av. Aragua, Local 286<br>Sector Andres Eloy Blanco<br>Maracay, Estado Aragua 2102</p>
                </div>

                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <strong>Teléfono</strong>
                    <p>0243-2343044<br>Atención al cliente disponible</p>
                </div>

                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <strong>Correo Electrónico</strong>
                    <p>2016rojasinversiones<br>@gmail.com</p>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="action-buttons">
            <a href="<?php echo BASE_URL; ?>/app/views/layouts/motos.php" class="btn-action btn-primary">
                <i class="fas fa-motorcycle"></i> Explorar Catálogo
            </a>
            <a href="<?php echo BASE_URL; ?>/app/views/layouts/ayuda.php" class="btn-action btn-secondary">
                <i class="fas fa-question-circle"></i> Preguntas Frecuentes
            </a>
            <a href="<?php echo BASE_URL; ?>/app/views/layouts/contacto.php" class="btn-action btn-secondary">
                <i class="fas fa-envelope"></i> Contacto
            </a>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer-custom">
        <div class="footer-content-custom">
            <div class="footer-section-custom">
                <h3>Acerca de Nosotros</h3>
                <p>INVERSIONES ROJAS 2016. C.A. es una empresa especializada en repuestos y vehículos Bera, ofreciendo productos de alta calidad y el mejor servicio al cliente.</p>
            </div>
            
            <div class="footer-section-custom">
                <h3>Contacto</h3>
                <p><i class="fas fa-map-marker-alt"></i> AV ARAGUA LOCAL NRO 286 SECTOR ANDRES ELOY BLANCO, MARACAY ARAGUA ZONA POSTAL 2102</p>
                <p><i class="fas fa-phone"></i> 0243-2343044</p>
                <p><i class="fas fa-envelope"></i> 2016rojasinversiones@gmail.com</p>
            </div>
            
            <div class="footer-section-custom">
                <h3>Enlaces Rápidos</h3>
                <a href="<?php echo BASE_URL; ?>/app/views/layouts/inicio.php">Inicio</a>
                <a href="<?php echo BASE_URL; ?>/app/views/layouts/motos.php">Motos</a>
                <a href="<?php echo BASE_URL; ?>/app/views/layouts/repuestos.php">Repuestos</a>
                <a href="<?php echo BASE_URL; ?>/app/views/layouts/about.php">Sobre Nosotros</a>
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
