<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
// Valor seguro para reCAPTCHA
$__RECAPTCHA_SITE_KEY = defined('RECAPTCHA_SITE_KEY') ? constant('RECAPTCHA_SITE_KEY') : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Inversiones Rojas</title>
         <link rel="icon" href="<?php echo BASE_URL; ?>/public/img/logo.png">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/base.css">
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/pages/auth.css">
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/admin.css">
        <!-- Estilos inline para el botón 'Inicio' (incluido directamente en la vista) -->
        <style>
        .auth-back-btn {
            position: fixed;
            top: 14px;
            left: 14px;
            background: linear-gradient(180deg, #1F9166 0%, #147a4f 100%);
            color: #fff;
            padding: 12px 16px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 8px 22px rgba(31,145,102,0.18);
            z-index: 9999;
            transition: transform 0.12s ease, box-shadow 0.12s ease, background 0.12s ease;
            padding-left: 14px;
        }
        .auth-back-btn i { color: #fff; font-size: 24px; line-height: 1; }
        .auth-back-btn span { color: #fff; display: inline-block; transform: translateY(-1px); font-size:15px; }
        .auth-back-btn:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(31,145,102,0.22); }
        @media (max-width: 420px) {
            .auth-back-btn { top:10px; left:8px; padding:8px 10px; font-size:14px; border-radius:10px; gap:8px; }
            .auth-back-btn i { font-size:20px; }
        }
        
        /* Estilos para select y form controls */
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #1F9166;
            box-shadow: 0 0 0 3px rgba(31, 145, 102, 0.1);
        }
        
        /* Estilos para mensajes de validación personalizados */
        .form-message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
            animation: slideIn 0.3s ease;
        }
        .form-message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        /* Estilos para validación de campos */
        .field-validation-message {
            font-size: 13px;
            margin-top: 5px;
            padding-left: 35px;
            animation: slideIn 0.3s ease;
        }
        
        .field-error-message {
            color: #dc3545;
        }
        
        .field-success-message {
            color: #28a745;
        }
        
        .field-loading {
            color: #6c757d;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .input-group input.is-valid {
            border-color: #28a745;
        }
        
        .input-group input.is-invalid {
            border-color: #dc3545;
        }
        </style>
</head>
<body class="auth-page">
    <script>
        window.APP_BASE = '<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>';
    </script>
    <div class="auth-container">

    <!-- Botón de volver al inicio (visible y fijo en la esquina superior izquierda) -->
    <a href="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/" class="auth-back-btn" aria-label="Inicio">
        <i class="fas fa-arrow-left" aria-hidden="true"></i>
        <span>Inicio</span>
    </a>

        <div class="auth-header">
            <div class="auth-logo">
                <i class="fas fa-motorcycle"></i>
                <h1>Inversiones Rojas</h1>
            </div>
            <h2>Crear Cuenta</h2>
            <p>Regístrate para comenzar a comprar</p>
        </div>
        
        <form class="auth-form" id="registerForm" action="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/tests/process_register.php" method="POST">
            <input type="hidden" name="rol_id" value="5">

            <div class="form-group">
                <label for="username">Nombre de Usuario *</label>
                <div class="input-group">
                    <i class="fas fa-user-circle"></i>
                    <input type="text" id="username" name="username" class="form-control" placeholder="usuario123">
                </div>
                <div id="username-validation" class="field-validation-message"></div>
            </div>

            
            <div class="form-row">
                <div class="form-group">
                    <label for="doc_type">Tipo de Documento *</label>
                    <select id="doc_type" name="doc_type" class="form-control">
                        <option value="">Seleccionar...</option>
                        <option value="V">Cédula (V)</option>
                        <option value="J">RIF (J)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="cedula_rif">Número de Documento *</label>
                    <div class="input-group">
                        <i class="fas fa-id-card"></i>
                        <input type="text" id="cedula_rif" name="cedula_rif" class="form-control" placeholder="12345678">
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="telefono_principal">Teléfono *</label>
                <div class="input-group">
                    <i class="fas fa-phone"></i>
                    <input type="text" id="telefono_principal" name="telefono_principal" placeholder="0412-1234567">
                </div>
            </div>

            <div class="form-group">
                <label for="nombre_completo">Nombre Completo *</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="nombre_completo" name="nombre_completo" placeholder="Tu nombre completo">
                </div>
            </div>

            <div class="form-group">
                <label for="email">Correo Electrónico *</label>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="text" id="email" name="email" placeholder="tu@email.com">
                </div>
                <div id="email-validation" class="field-validation-message"></div>
            </div>

            

        

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Contraseña *</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Mínimo 6 caracteres">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmar Contraseña *</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Repite tu contraseña">
                    </div>
                </div>
            </div>

            <div class="form-options">
                <label class="checkbox-container">
                    <input type="checkbox" id="acceptTerms" name="acceptTerms">
                    <span class="checkmark"></span>
                    Acepto los <a href="#" onclick="showTermsModal(event)" style="color: #1F9166; cursor: pointer;">términos y condiciones</a>
                </label>
            </div>

            <!-- Modal de Términos y Condiciones -->
            <div id="termsModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 10000; overflow-y: auto;">
                <div class="modal-content" style="background: #fff; margin: 5% auto; padding: 30px; max-width: 700px; border-radius: 12px; position: relative;">
                    <span onclick="closeTermsModal()" style="position: absolute; right: 20px; top: 15px; font-size: 28px; cursor: pointer; color: #666;">&times;</span>
                    <h2 style="color: #1F9166; margin-bottom: 20px; text-align: center;">Términos y Condiciones</h2>
                    <h3 style="color: #333; font-size: 16px; margin-top: 20px;">1. INFORMACIÓN GENERAL</h3>
                    <p style="color: #555; line-height: 1.6; margin: 10px 0;">Inversiones Rojas, C.A. (en adelante "la Empresa") es una empresa dedicada a la venta de motorcycles, repuestos y accesorios automotrices. Al registrarse en nuestro sistema, usted acepta cumplir con los presentes términos y condiciones.</p>
                    
                    <h3 style="color: #333; font-size: 16px; margin-top: 20px;">2. CUENTA DE USUARIO</h3>
                    <p style="color: #555; line-height: 1.6; margin: 10px 0;">Al crear una cuenta, usted se compromete a proporcionar información veraz y actualizada. Es responsable de mantener la confidencialidad de su contraseña y de todas las actividades realizadas bajo su cuenta.</p>
                    
                    <h3 style="color: #333; font-size: 16px; margin-top: 20px;">3. PRIVACIDAD Y PROTECCIÓN DE DATOS</h3>
                    <p style="color: #555; line-height: 1.6; margin: 10px 0;">Sus datos personales serán tratados conforme a la Ley Orgánica de Protección de Datos Personales (LOPDP). Nousamos sus datos para fines comerciales legítimos, incluyendo procesamiento de compras y comunicación de promociones.</p>
                    
                    <h3 style="color: #333; font-size: 16px; margin-top: 20px;">4. POLÍTICA DE COMPRAS</h3>
                    <p style="color: #555; line-height: 1.6; margin: 10px 0;">Los precios mostrados pueden incluir o excluir IVA según el método de pago. Las ofertas tienen validez sujeta a disponibilidad de inventario. Las ventas definitivo son aquellas pagadas en su totalidad.</p>
                    
                    <h3 style="color: #333; font-size: 16px; margin-top: 20px;">5. GARANTÍAS Y DEVOLUCIONES</h3>
                    <p style="color: #555; line-height: 1.6; margin: 10px 0;">Los productos nuevos incluyen garantía del fabricante. Las devoluciones proceden dentro de los primeros 5 días hábiles tras la compra, presentando factura y empaque original.</p>
                    
                    <h3 style="color: #333; font-size: 16px; margin-top: 20px;">6. RESPONSABILIDADES</h3>
                    <p style="color: #555; line-height: 1.6; margin: 10px 0;">La Empresa no se hace responsable por daños derivados del uso inadecuado de los productos. Es responsabilidad del cliente verificar compatibilidad de repuestos.</p>
                    
                    <h3 style="color: #333; font-size: 16px; margin-top: 20px;">7. MEDIOS DE PAGO</h3>
                    <p style="color: #555; line-height: 1.6; margin: 10px 0;">Aceptamos: Efectivo (USD/Bs), Transferencia bancaria, Pago Móvil y Punto de Venta. Los precios en Bs sufren variaciones según la tasa de cambio vigente.</p>
                    
                    <h3 style="color: #333; font-size: 16px; margin-top: 20px;">8. CONTACTO</h3>
                    <p style="color: #555; line-height: 1.6; margin: 10px 0;">Para consultas o reclamaciones: Correo: 2016rojasinversiones@gmail.com | Teléfonos: 0412-1234567 | Dirección: Av. Bolívar, Centro Comercial Inversiones Rojas, San Cristóbal, Táchira.</p>
                    
                    <div style="margin-top: 25px; padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: center;">
                        <p style="color: #333; font-size: 14px; margin: 0;"><strong>Última actualización:</strong> 17 de Abril de 2026</p>
                    </div>
                </div>
            </div>

            <script>
            function showTermsModal(event) {
                event.preventDefault();
                document.getElementById('termsModal').style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
            
            function closeTermsModal() {
                document.getElementById('termsModal').style.display = 'none';
                document.body.style.overflow = 'auto';
            }
            
            // Cerrar modal al hacer clic fuera del contenido
            document.getElementById('termsModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeTermsModal();
                }
            });
            </script>

            <button type="submit" class="auth-btn primary">
                <i class="fas fa-user-plus"></i>
                Crear Cuenta
            </button>

            <div class="auth-footer">
                <p>¿Ya tienes cuenta? <a href="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/app/views/auth/Login.php">Inicia Sesión</a></p>
            </div>
        </form>

        <!-- Contenedor para mensajes de validación personalizados -->
        <div id="formMessages" class="form-messages-container"></div>
    </div>

    <script>
    // Funciones para mostrar mensajes de validación personalizados
    function showFormMessage(message, type = 'error') {
        console.log('showFormMessage called:', message, type);
        const messagesContainer = document.getElementById('formMessages');
        if (!messagesContainer) {
            console.error('formMessages container not found');
            return;
        }
        messagesContainer.innerHTML = `<div class="form-message ${type}">${message}</div>`;
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            messagesContainer.innerHTML = '';
        }, 5000);
    }
    
    function showFieldMessage(fieldId, message, type = 'error') {
        const validationElement = document.getElementById(`${fieldId}-validation`);
        if (validationElement) {
            validationElement.className = `field-validation-message field-${type}-message`;
            validationElement.textContent = message;
            
            // Auto-hide after 3 seconds for success messages
            if (type === 'success') {
                setTimeout(() => {
                    validationElement.textContent = '';
                }, 3000);
            }
        }
    }
    
    function clearFieldMessage(fieldId) {
        const validationElement = document.getElementById(`${fieldId}-validation`);
        if (validationElement) {
            validationElement.textContent = '';
        }
    }
    
    function setFieldState(fieldId, state) {
        const field = document.getElementById(fieldId);
        if (field) {
            field.classList.remove('is-valid', 'is-invalid');
            if (state === 'valid') {
                field.classList.add('is-valid');
            } else if (state === 'invalid') {
                field.classList.add('is-invalid');
            }
        }
    }
    
    document.getElementById('registerForm').addEventListener('submit', async function(e) {
        console.log('Form submit event triggered');
        e.preventDefault();
        
        // 1. Validar nombre de usuario
        const usernameValidation = validateUsername();
        if (!usernameValidation.valid) {
            showFormMessage(usernameValidation.message);
            setFieldState('username', 'invalid');
            document.getElementById('username').focus();
            return;
        }
        
        // 2. Validar documento (RIF/Cédula)
        const docValidation = validateDocumento();
        if (!docValidation.valid) {
            showFormMessage(docValidation.message);
            setFieldState('cedula_rif', 'invalid');
            document.getElementById('cedula_rif').focus();
            return;
        }
        
        // 3. Validar teléfono
        const phoneValidation = validateTelefono();
        if (!phoneValidation.valid) {
            showFormMessage(phoneValidation.message);
            setFieldState('telefono_principal', 'invalid');
            document.getElementById('telefono_principal').focus();
            return;
        }
        
        // 4. Validar nombre completo
        const nombreValidation = validateNombreCompleto();
        if (!nombreValidation.valid) {
            showFormMessage(nombreValidation.message);
            setFieldState('nombre_completo', 'invalid');
            document.getElementById('nombre_completo').focus();
            return;
        }
        
        // 5. Validar correo electrónico
        const emailValidation = validateEmail();
        if (!emailValidation.valid) {
            showFormMessage(emailValidation.message);
            setFieldState('email', 'invalid');
            document.getElementById('email').focus();
            return;
        }
        
        // 6. Validar contraseña
        const passwordValidation = validatePassword();
        if (!passwordValidation.valid) {
            showFormMessage(passwordValidation.message);
            setFieldState('password', 'invalid');
            document.getElementById('password').focus();
            return;
        }
        
        // 7. Validar que contraseñas coincidan
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (password !== confirmPassword) {
            showFormMessage('Las contraseñas no coinciden');
            setFieldState('confirm_password', 'invalid');
            document.getElementById('confirm_password').focus();
            return;
        }
        
        // 8. Validar términos aceptados
        if (!document.getElementById('acceptTerms').checked) {
            showFormMessage('Debes aceptar los términos y condiciones');
            return;
        }
  // 9. Validar username único en el sistema
const username = document.getElementById('username').value.trim();
try {
    const urlCheck = '<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/api/check_username.php?username=' + encodeURIComponent(username);
    const respCheck = await fetch(urlCheck);
    const dataCheck = await respCheck.json();
    
    if (dataCheck.exists) {  // ✅ CORREGIDO: exists en lugar de !available
        showFormMessage('El nombre de usuario ya está en uso. Elige otro.');
        setFieldState('username', 'invalid');
        document.getElementById('username').focus();
        return;
    }
} catch (error) {
    console.error('Error:', error);
    showFormMessage('Error al validar usuario. Intenta de nuevo.');
    return;
}
        // Mostrar mensaje de carga
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando cuenta...';
        submitBtn.disabled = true;
        
        // Enviar el formulario
        const formData = new FormData(this);
        
        fetch('<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/tests/process_register.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showFormMessage(data.message, 'success');
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 2000);
            } else {
                showFormMessage(data.message || 'Error al crear la cuenta');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showFormMessage('Error en la conexión con el servidor');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
    
    // Validación en tiempo real para username
    document.getElementById('username').addEventListener('blur', async function() {
        const username = this.value.trim();
        if (!username) return;
        
        showFieldMessage('username', '<i class="fas fa-spinner fa-spin"></i> Verificando...', 'loading');
        
        try {
            const url = '<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/api/check_username.php?username=' + encodeURIComponent(username);
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType?.includes('application/json')) {
                const text = await response.text();
                throw new Error(`Expected JSON, got: ${text.substring(0, 200)}`);
            }
            
            const data = await response.json();
            if (data.exists) {
                showFieldMessage('username', 'Este nombre de usuario ya está en uso', 'error');
                setFieldState('username', 'invalid');
            } else {
                showFieldMessage('username', 'Nombre de usuario disponible', 'success');
                setFieldState('username', 'valid');
            }
        } catch (error) {
            console.error('Error checking username:', error);
            showFieldMessage('username', 'Error al verificar usuario', 'error');
            setFieldState('username', 'invalid');
        }
    });
    
    // Validación en tiempo real para email
    document.getElementById('email').addEventListener('blur', async function() {
        const email = this.value.trim();
        if (!email) return;
        
        // Validación básica de formato
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showFieldMessage('email', 'Formato de correo electrónico inválido', 'error');
            setFieldState('email', 'invalid');
            return;
        }
        
        showFieldMessage('email', '<i class="fas fa-spinner fa-spin"></i> Verificando...', 'loading');
        
        try {
            const url = '<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/api/check_email.php?email=' + encodeURIComponent(email);
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType?.includes('application/json')) {
                const text = await response.text();
                throw new Error(`Expected JSON, got: ${text.substring(0, 200)}`);
            }
            
            const data = await response.json();
            if (data.exists) {
                showFieldMessage('email', 'Este correo electrónico ya está registrado', 'error');
                setFieldState('email', 'invalid');
            } else {
                showFieldMessage('email', 'Correo electrónico disponible', 'success');
                setFieldState('email', 'valid');
            }
        } catch (error) {
            console.error('Error checking email:', error);
            showFieldMessage('email', 'Error al verificar correo electrónico', 'error');
            setFieldState('email', 'invalid');
        }
    });
    
    // Limpiar mensajes de validación cuando el usuario comienza a escribir
    document.getElementById('username').addEventListener('input', function() {
        clearFieldMessage('username');
        setFieldState('username', '');
    });
    
    document.getElementById('email').addEventListener('input', function() {
        clearFieldMessage('email');
        setFieldState('email', '');
    });
    
    // Manejo del tipo de documento
    const docTypeSelect = document.getElementById('doc_type');
    const cedulaRifInput = document.getElementById('cedula_rif');
    
    docTypeSelect.addEventListener('change', function() {
        const type = this.value;
        if (type === 'V') {
            cedulaRifInput.placeholder = '12345678 (7-8 dígitos)';
        } else if (type === 'J') {
            cedulaRifInput.placeholder = '123456789 (9 dígitos)';
        } else {
            cedulaRifInput.placeholder = 'Selecciona tipo primero';
        }
    });
    
    // Función para validar documento
    function validateDocumento() {
        const type = docTypeSelect.value;
        const value = cedulaRifInput.value.trim();
        
        if (!type) {
            return { valid: false, message: 'Selecciona el tipo de documento' };
        }
        
        if (!value) {
            return { valid: false, message: 'Ingresa el número de documento' };
        }
        
        // Remover guiones y espacios
        const cleanValue = value.replace(/[-\s]/g, '');
        
        if (type === 'V') {
            // Cédula: 7-8 dígitos
            if (!/^\d{7,8}$/.test(cleanValue)) {
                return { valid: false, message: 'Cédula debe tener 7-8 dígitos' };
            }
        } else if (type === 'J') {
            // RIF: 9 dígitos
            if (!/^\d{9}$/.test(cleanValue)) {
                return { valid: false, message: 'RIF debe tener 9 dígitos' };
            }
        }
        
        return { valid: true };
    }
    
    // Validación de nombre de usuario único
    let usernameValidated = false;
    let usernameCheckTimeout = null;

    function validateUsername() {
        const username = document.getElementById('username').value.trim();
        
        if (!username) {
            return { valid: false, message: 'Ingresa nombre de usuario' };
        }
        
        if (username.length < 3) {
            return { valid: false, message: 'Usuario debe tener al menos 3 caracteres' };
        }
        
        if (username.length > 20) {
            return { valid: false, message: 'Usuario debe tener máximo 20 caracteres' };
        }
        
        if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            return { valid: false, message: 'Solo letras, números y guiones bajos' };
        }
        
        return { valid: true };
    }

    function checkUsernameExists(username) {
        if (usernameCheckTimeout) {
            clearTimeout(usernameCheckTimeout);
        }
        
        usernameCheckTimeout = setTimeout(() => {
            const url = '<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/api/check_username.php?username=' + encodeURIComponent(username);
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (!data.available) {
                        usernameValidated = false;
                    } else {
                        usernameValidated = true;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }, 500);
    }

    // Función para validar teléfono
    function validateTelefono() {
        const telefono = document.getElementById('telefono_principal').value.trim();
        // Formato: 04XX-XXXXXXX (10 dígitos)
        if (!/^04\d{2}-\d{7}$/.test(telefono)) {
            return { valid: false, message: 'Formato de teléfono inválido (ej: 0412-1234567)' };
        }
        return { valid: true };
    }

    // Función para validar correo electrónico
    function validateEmail() {
        const email = document.getElementById('email').value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (!email) {
            return { valid: false, message: 'Ingresa correo electrónico' };
        }
        
        if (!emailRegex.test(email)) {
            return { valid: false, message: 'Correo electrónico inválido' };
        }
        
        return { valid: true };
    }

    // Función para validar contraseña segura
    function validatePassword() {
        const password = document.getElementById('password').value;
        
        if (password.length < 6) {
            return { valid: false, message: 'Contraseña debe tener al menos 6 caracteres' };
        }
        
        if (password.length > 50) {
            return { valid: false, message: 'Contraseña debe tener máximo 50 caracteres' };
        }
        
        return { valid: true };
    }

    // Función para validar nombre completo
    function validateNombreCompleto() {
        const nombre = document.getElementById('nombre_completo').value.trim();
        
        if (!nombre) {
            return { valid: false, message: 'Ingresa nombre completo' };
        }
        
        if (nombre.length < 3) {
            return { valid: false, message: 'Nombre debe tener al menos 3 caracteres' };
        }
        
        if (nombre.length > 200) {
            return { valid: false, message: 'Nombre muy largo' };
        }
        
        return { valid: true };
    }
</script>
</body>
</html>