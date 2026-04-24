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
    <title>Contacto - Inversiones Rojas</title>
    <link rel="icon" href="<?php echo BASE_URL; ?>/public/img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/pages/auth.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/admin.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/pages/home.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/components/user-panel.css">
    <style>
        /* Estilos específicos para la página de contacto */
        .contact-page {
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
        .contact-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        
        @media (max-width: 992px) {
            .contact-container {
                grid-template-columns: 1fr;
            }
        }
        
        /* Formulario de contacto */
        .contact-form-section {
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            border-color: #1F9166;
            outline: none;
        }
        
        .form-textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 576px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        .submit-btn {
            width: 100%;
            padding: 15px;
            background: #1F9166;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .submit-btn:hover {
            background: #187a54;
        }
        
        .submit-btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }
        
        /* Información de contacto */
        .contact-info-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .info-card {
            margin-bottom: 30px;
        }
        
        .info-card h3 {
            font-size: 1.3rem;
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .contact-details {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .contact-detail {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            color: #555;
            line-height: 1.5;
        }
        
        .contact-detail i {
            color: #1F9166;
            font-size: 18px;
            margin-top: 2px;
            min-width: 20px;
        }
        
        /* Mapa */
        .map-container {
            border-radius: 8px;
            overflow: hidden;
            margin-top: 20px;
            height: 250px;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
        }
        
        .map-placeholder {
            text-align: center;
        }
        
        /* Horarios */
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .schedule-table td {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            color: #555;
        }
        
        .schedule-table td:first-child {
            font-weight: 600;
            color: #333;
        }
        
        .schedule-table tr:last-child td {
            border-bottom: none;
        }
        
        /* Redes sociales */
        .social-contact {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .social-link {
            width: 45px;
            height: 45px;
            background: #f5f5f5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 18px;
        }
        
        .social-link:hover {
            background: #1F9166;
            color: white;
            transform: translateY(-3px);
        }
        
        /* Mensaje de éxito/error */
        .message-alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }
        
        .message-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Sección de departamentos */
        .departments-section {
            grid-column: 1 / -1;
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .departments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .department-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            transition: transform 0.3s;
        }
        
        .department-card:hover {
            transform: translateY(-5px);
        }
        
        .department-icon {
            font-size: 2rem;
            color: #1F9166;
            margin-bottom: 15px;
        }
        
        .department-card h4 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .department-card p {
            color: #666;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        
        .department-email {
            color: #1F9166;
            font-weight: 600;
            text-decoration: none;
        }
        
        .department-email:hover {
            text-decoration: underline;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }
            
            .contact-form-section,
            .contact-info-section {
                padding: 20px;
            }
            
            .departments-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php require __DIR__ . '/partials/header.php'; ?>

    <main class="contact-page container">
        <div class="page-header">
            <h1><i class="fas fa-envelope"></i> CONTÁCTANOS</h1>
            <p>Estamos aquí para ayudarte. Escríbenos y te responderemos lo antes posible</p>
        </div>
        
        <!-- Mensajes de alerta -->
        <div id="successMessage" class="message-alert message-success" style="display: none;">
            <i class="fas fa-check-circle"></i> ¡Mensaje enviado con éxito! Te contactaremos pronto.
        </div>
        
        <div id="errorMessage" class="message-alert message-error" style="display: none;">
            <i class="fas fa-exclamation-circle"></i> Error al enviar el mensaje. Por favor, intenta nuevamente.
        </div>
        
        <div class="contact-container">
            <!-- Formulario de contacto -->
            <div class="contact-form-section">
                <h2 class="section-title">Envíanos un Mensaje</h2>
                <form id="contactForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre" class="form-label">
                                <i class="fas fa-user"></i> Nombre Completo *
                            </label>
                            <input type="text" 
                                   id="nombre" 
                                   class="form-input" 
                                   placeholder="Ingresa tu nombre completo"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i> Correo Electrónico *
                            </label>
                            <input type="email" 
                                   id="email" 
                                   class="form-input" 
                                   placeholder="ejemplo@correo.com"
                                   required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="telefono" class="form-label">
                                <i class="fas fa-phone"></i> Teléfono
                            </label>
                            <input type="tel" 
                                   id="telefono" 
                                   class="form-input" 
                                   placeholder="+1 (234) 567-8900">
                        </div>
                        
                        <div class="form-group">
                            <label for="asunto" class="form-label">
                                <i class="fas fa-tag"></i> Asunto *
                            </label>
                            <select id="asunto" class="form-select" required>
                                <option value="">Selecciona un asunto</option>
                                <option value="consulta">Consulta General</option>
                                <option value="compra">Consulta de Compra</option>
                                <option value="soporte">Soporte Técnico</option>
                                <option value="garantia">Garantía y Devoluciones</option>
                                <option value="reclamo">Reclamo o Queja</option>
                                <option value="trabajo">Solicitud de Trabajo</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="mensaje" class="form-label">
                            <i class="fas fa-comment-dots"></i> Mensaje *
                        </label>
                        <textarea id="mensaje" 
                                  class="form-textarea" 
                                  placeholder="Describe detalladamente tu consulta o solicitud..."
                                  required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" id="privacidad" required>
                            He leído y acepto la <a href="#" style="color: #1F9166;">Política de Privacidad</a> *
                        </label>
                    </div>
                    
                    <button type="submit" class="submit-btn" id="submitBtn">
                        <i class="fas fa-paper-plane"></i> Enviar Mensaje
                    </button>
                </form>
            </div>
            
            <!-- Información de contacto -->
            <div class="contact-info-section">
                <div class="info-card">
                    <h3><i class="fas fa-map-marker-alt"></i> Nuestra Ubicación</h3>
                    <div class="contact-details">
                        <div class="contact-detail">
                            <i class="fas fa-building"></i>
                            <div>
                                <strong>INVERSIONES ROJAS 2016. C.A.</strong><br>
                                AV ARAGUA LOCAL NRO 286 SECTOR ANDRES ELOY BLANCO, MARACAY ARAGUA ZONA POSTAL 2102<br>
                                Código Postal: 2102
                            </div>
                        </div>
                    </div>
                    
                    <div class="map-container">
                        <iframe 
                            src="https://maps.google.com/maps?q=6CG7+XW7, Maracay 2103, Aragua, Venezuela&output=embed"
                            width="100%" 
                            height="100%" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy">
                        </iframe>
                    </div>
                </div>
                
                <div class="info-card">
                    <h3><i class="fas fa-clock"></i> Horarios de Atención</h3>
                    <table class="schedule-table">
                        <tr>
                            <td>Lunes - Viernes</td>
                            <td>8:00 AM - 6:00 PM</td>
                        </tr>
                        <tr>
                            <td>Sábados</td>
                            <td>9:00 AM - 2:00 PM</td>
                        </tr>
                        <tr>
                            <td>Domingos</td>
                            <td>Cerrado</td>
                        </tr>
                        <tr>
                            <td>Feriados</td>
                            <td>Horario especial</td>
                        </tr>
                    </table>
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
            <p>&copy; 2023 Inversiones Rojas. Todos los derechos reservados. | <a href="#" style="color: #27ae60;">Aviso Legal</a> | <a href="#" style="color: #27ae60;">Política de Cookies</a></p>
        </div>
    </footer>

    <script src="<?php echo BASE_URL; ?>/public/js/inv-notifications.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/components/user-panel.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/script.js"></script>
    <script>
        // Manejo del formulario de contacto
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validar formulario
            if (!validarFormulario()) {
                return;
            }
            
            // Deshabilitar botón de envío
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
            
            // Ocultar mensajes anteriores
            document.getElementById('successMessage').style.display = 'none';
            document.getElementById('errorMessage').style.display = 'none';
            
            // Recopilar datos del formulario
            const formData = {
                nombre: document.getElementById('nombre').value.trim(),
                email: document.getElementById('email').value.trim(),
                telefono: document.getElementById('telefono').value.trim(),
                asunto: document.getElementById('asunto').value,
                mensaje: document.getElementById('mensaje').value.trim()
            };
            
            // Enviar petición AJAX
            fetch('<?php echo BASE_URL; ?>/api/contacto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito
                    document.getElementById('successMessage').style.display = 'block';
                    
                    // Limpiar formulario
                    document.getElementById('contactForm').reset();
                    
                    // Hacer scroll al mensaje de éxito
                    document.getElementById('successMessage').scrollIntoView({ behavior: 'smooth' });
                } else {
                    // Mostrar mensaje de error
                    document.getElementById('errorMessage').style.display = 'block';
                    document.getElementById('errorMessage').innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (data.message || 'Error al enviar el mensaje. Por favor, intenta nuevamente.');
                    
                    // Mostrar errores específicos si existen
                    if (data.errores && data.errores.length > 0) {
                        document.getElementById('errorMessage').innerHTML += '<br><small>' + data.errores.join('<br>') + '</small>';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Mostrar mensaje de error genérico
                document.getElementById('errorMessage').style.display = 'block';
                document.getElementById('errorMessage').innerHTML = '<i class="fas fa-exclamation-circle"></i> Error de conexión. Por favor, verifica tu conexión a internet e intenta nuevamente.';
            })
            .finally(() => {
                // Restaurar botón
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Mensaje';
                
                // Ocultar mensaje de error después de 5 segundos
                setTimeout(() => {
                    document.getElementById('errorMessage').style.display = 'none';
                }, 5000);
            });
        });
        
        function validarFormulario() {
            const nombre = document.getElementById('nombre').value.trim();
            const email = document.getElementById('email').value.trim();
            const asunto = document.getElementById('asunto').value;
            const mensaje = document.getElementById('mensaje').value.trim();
            const privacidad = document.getElementById('privacidad').checked;
            
            let errores = [];
            
            if (!nombre) {
                errores.push('El nombre es requerido');
            }
            
            if (!email) {
                errores.push('El correo electrónico es requerido');
            } else if (!validarEmail(email)) {
                errores.push('El correo electrónico no es válido');
            }
            
            if (!asunto) {
                errores.push('Debes seleccionar un asunto');
            }
            
            if (!mensaje) {
                errores.push('El mensaje es requerido');
            } else if (mensaje.length < 10) {
                errores.push('El mensaje debe tener al menos 10 caracteres');
            }
            
            if (!privacidad) {
                errores.push('Debes aceptar la política de privacidad');
            }
            
            if (errores.length > 0) {
                alert('Por favor, corrige los siguientes errores:\n\n' + errores.join('\n'));
                return false;
            }
            
            return true;
        }
        
        function validarEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
        
        // Prellenar datos del usuario si está logueado
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($usuario_logueado): ?>
                // Aquí podrías prellenar el formulario con datos del usuario
                // document.getElementById('nombre').value = '<?php echo $user_name; ?>';
                // document.getElementById('email').value = '<?php echo $_SESSION['user_email'] ?? ''; ?>';
            <?php endif; ?>
            
            // Agregar validación en tiempo real
            const inputs = document.querySelectorAll('.form-input, .form-textarea, .form-select');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    validarCampo(this);
                });
            });
        });
        
        function validarCampo(campo) {
            const valor = campo.value.trim();
            
            campo.classList.remove('invalid');
            
            if (campo.required && !valor) {
                campo.classList.add('invalid');
                return false;
            }
            
            if (campo.type === 'email' && valor && !validarEmail(valor)) {
                campo.classList.add('invalid');
                return false;
            }
            
            return true;
        }
        
        // Agregar estilo para campos inválidos
        const style = document.createElement('style');
        style.textContent = `
            .form-input.invalid,
            .form-textarea.invalid,
            .form-select.invalid {
                border-color: #e74c3c !important;
                background-color: #fff8f8;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>