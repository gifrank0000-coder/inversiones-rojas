<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';

// Verificar si el usuario está logueado
$usuario_logueado = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';

// ayuda.php siempre muestra el manual de cliente
$manual_url = BASE_URL . '/docs/manuales/MANUAL(CLIENTE).pdf';
$manual_titulo = 'Manual del Cliente';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayuda - Inversiones Rojas</title>
    <link rel="icon" href="<?php echo BASE_URL; ?>/public/img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/pages/auth.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/admin.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/pages/home.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/components/user-panel.css">
    <style>
        /* Estilos específicos para la página de ayuda */
        .help-page {
            padding: 40px 0;
            min-height: 70vh;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
            color: #333;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .page-header p {
            color: #666;
            font-size: 1.1rem;
            max-width: 800px;
            margin: 0 auto;
        }
        
        /* Contenedor principal */
        .help-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 40px;
        }
        
        @media (max-width: 992px) {
            .help-container {
                grid-template-columns: 1fr;
            }
        }
        
        /* Sección de FAQs */
        .faq-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #1F9166;
        }
        
        .faq-categories {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .category-btn {
            padding: 10px 20px;
            background: #f5f5f5;
            border: 2px solid #ddd;
            border-radius: 6px;
            color: #333;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .category-btn:hover,
        .category-btn.active {
            background: #1F9166;
            border-color: #1F9166;
            color: white;
        }
        
        .faq-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .faq-item {
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .faq-item:hover {
            border-color: #1F9166;
            box-shadow: 0 3px 10px rgba(31, 145, 102, 0.1);
        }
        
        .faq-question {
            padding: 20px;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            font-weight: 600;
            color: #333;
        }
        
        .faq-question i {
            transition: transform 0.3s;
            color: #1F9166;
        }
        
        .faq-item.active .faq-question i {
            transform: rotate(180deg);
        }
        
        .faq-answer {
            padding: 0 20px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out, padding 0.3s;
            color: #555;
            line-height: 1.6;
        }
        
        .faq-item.active .faq-answer {
            padding: 20px;
            max-height: 500px;
        }
        
        /* Sidebar de ayuda rápida */
        .help-sidebar {
            position: sticky;
            top: 20px;
            height: fit-content;
        }
        
        .help-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .help-card h3 {
            font-size: 1.3rem;
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #555;
        }
        
        .contact-item i {
            color: #1F9166;
            width: 20px;
        }
        
        .quick-links {
            list-style: none;
            padding: 0;
        }
        
        .quick-links li {
            margin-bottom: 10px;
        }
        
        .quick-links a {
            color: #333;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            transition: color 0.3s;
        }
        
        .quick-links a:hover {
            color: #1F9166;
        }
        
        .btn-help {
            display: block;
            width: 100%;
            padding: 12px;
            text-align: center;
            background: #1F9166;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin-top: 10px;
            transition: background 0.3s;
        }
        
        .btn-help:hover {
            background: #187a54;
        }
        
        /* Sección de búsqueda */
        .search-help {
            margin: 30px 0;
        }
        
        .search-container {
            position: relative;
        }
        
        .search-help-input {
            width: 100%;
            padding: 15px 20px 15px 50px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .search-help-input:focus {
            border-color: #1F9166;
            outline: none;
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }
        
        /* Sección de contacto directo */
        .contact-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 40px;
            margin-top: 40px;
            text-align: center;
        }
        
        .contact-section h2 {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .contact-section p {
            color: #666;
            margin-bottom: 25px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }
            
            .faq-categories {
                justify-content: center;
            }
            
            .help-card {
                padding: 20px;
            }
            
            .contact-section {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php require __DIR__ . '/partials/header.php'; ?>

    <main class="help-page container">
        <div class="page-header">
            <h1><i class="fas fa-book"></i> Manual de Ayuda</h1>
            <p>Descarga el manual de usuario para obtener instrucciones completas sobre el uso del sistema.</p>
        </div>

        <div class="help-container" style="grid-template-columns: 1fr;">
            <div class="faq-section" style="text-align:center; padding:40px;">
                <h2 class="section-title">Descarga el Manual</h2>
                <p>Hemos preparado un manual de ayuda en formato PDF que explica paso a paso las funcionalidades principales: carrito, reservas, pedidos y más.</p>

                <div style="margin: 30px 0;">
                    <a href="<?php echo $manual_url; ?>" class="btn-help" download style="font-size:18px; padding:14px 24px; display:inline-block;">
                        <i class="fas fa-download"></i> Descargar <?php echo $manual_titulo; ?> (PDF)
                    </a>
                </div>

                <p style="color:#666; margin-top:20px;">Si no puede descargar el archivo, contacte soporte: <a href="mailto:<?php echo htmlspecialchars(COMPANY_EMAIL); ?>"><?php echo htmlspecialchars(COMPANY_EMAIL); ?></a></p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer-custom">
        <div class="footer-content-custom">
            <div class="footer-section-custom">
                <h3>Acerca de Nosotros</h3>
                <p><?php echo htmlspecialchars(COMPANY_NAME); ?> es una empresa especializada en repuestos y vehículos Bera, ofreciendo productos de alta calidad y el mejor servicio al cliente.</p>
            </div>
            
            <div class="footer-section-custom">
                <h3>Contacto</h3>
                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars(COMPANY_ADDRESS); ?></p>
                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars(COMPANY_PHONE); ?></p>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars(COMPANY_EMAIL); ?></p>
            </div>
            
            <div class="footer-section-custom">
                <h3>Enlaces Rápidos</h3>
                <a href="<?php echo BASE_URL; ?>/app/views/pages/inicio.php">Inicio</a>
                <a href="<?php echo BASE_URL; ?>/app/views/pages/motos.php">Motos</a>
                <a href="<?php echo BASE_URL; ?>/app/views/pages/repuestos.php">Repuestos</a>
                <a href="<?php echo BASE_URL; ?>/app/views/pages/ayuda.php">Ayuda</a>
                <a href="<?php echo BASE_URL; ?>/app/views/pages/contacto.php">Contacto</a>
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
            <p>&copy; 2023 Inversiones Rojas. Todos los derechos reservados. | <a href="#" style="color: #27ae60;">Política de Privacidad</a> | <a href="#" style="color: #27ae60;">Términos de Servicio</a></p>
        </div>
    </footer>

    <script src="<?php echo BASE_URL; ?>/public/js/inv-notifications.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/components/user-panel.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/script.js"></script>
    <script>
        // Funcionalidad para FAQs
        function toggleFAQ(element) {
            const faqItem = element.closest('.faq-item');
            faqItem.classList.toggle('active');
            
            // Cerrar otros FAQs si se desea
            // document.querySelectorAll('.faq-item').forEach(item => {
            //     if (item !== faqItem) {
            //         item.classList.remove('active');
            //     }
            // });
        }
        
        // Filtrar FAQs por categoría
        function filtrarFAQs(categoria) {
            // Actualizar botones activos
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Mostrar/ocultar FAQs según categoría
            const faqs = document.querySelectorAll('.faq-item');
            
            faqs.forEach(faq => {
                if (categoria === 'all' || faq.getAttribute('data-category') === categoria) {
                    faq.style.display = 'block';
                } else {
                    faq.style.display = 'none';
                    faq.classList.remove('active'); // Cerrar FAQs ocultos
                }
            });
        }
        
        // Búsqueda en FAQs
        const searchInput = document.querySelector('.search-help-input');
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const faqs = document.querySelectorAll('.faq-item');
            
            faqs.forEach(faq => {
                const question = faq.querySelector('.faq-question span').textContent.toLowerCase();
                const answer = faq.querySelector('.faq-answer').textContent.toLowerCase();
                
                if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                    faq.style.display = 'block';
                } else {
                    faq.style.display = 'none';
                    faq.classList.remove('active');
                }
            });
            
            // Actualizar categoría activa a "Todas"
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector('.category-btn[onclick="filtrarFAQs(\'all\')"]').classList.add('active');
        });
        
        // Iniciar chat (simulación)
        function iniciarChat() {
            if (confirm('¿Deseas iniciar un chat en vivo con nuestro equipo de soporte?')) {
                alert('El chat en vivo se abrirá en una nueva ventana. Horario de atención: Lunes a Viernes 8:00 - 18:00');
                // En un sistema real, aquí abrirías el widget de chat
            }
        }
        
        // Inicializar todas las FAQs cerradas excepto la primera
        document.addEventListener('DOMContentLoaded', function() {
            const faqs = document.querySelectorAll('.faq-item');
            faqs.forEach((faq, index) => {
                if (index !== 0) {
                    faq.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>