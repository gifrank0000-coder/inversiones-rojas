<?php 
require_once __DIR__ . '/../../../config/config.php';
$__RECAPTCHA_SITE_KEY = defined('RECAPTCHA_SITE_KEY') ? constant('RECAPTCHA_SITE_KEY') : '';

// Generar token CSRF si no existe
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$base_url = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Inversiones Rojas</title>
    <link rel="icon" href="<?php echo $base_url; ?>/public/img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/base.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/pages/auth.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/admin.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/pages/home.css">
    
    <style>
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
    </style>
    
    <?php if ($__RECAPTCHA_SITE_KEY): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
    
    <script>
         window.APP_BASE = '<?php echo $base_url; ?>';
    </script>
    
    <style>
    .auth-back-btn {
        position: fixed;
        top: 14px;
        left: 14px;
        color: #fff;
        padding: 12px 16px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        font-size: 16px;
        font-weight: 600;
        text-decoration: none;
        z-index: 9999;
        padding-left: 14px;
    }
    .auth-back-btn i { color: #fff; font-size: 24px; line-height: 1; }
    .auth-back-btn span { color: #fff; display: inline-block; transform: translateY(-1px); font-size:15px; }
    .auth-back-btn:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(31,145,102,0.22); }
    
    .recaptcha-container {
        margin: 15px 0;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }
    
    .recaptcha-notice {
        margin-top: 10px;
        padding: 8px 12px;
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 6px;
        font-size: 14px;
        color: #856404;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .recaptcha-notice i {
        color: #f39c12;
    }
    
    /* Estilos para validación de email */
    .email-validation-message {
        font-size: 13px;
        margin-top: 5px;
        padding-left: 35px;
        animation: slideIn 0.3s ease;
    }
    
    .email-error-message {
        color: #dc3545;
    }
    
    .email-success-message {
        color: #28a745;
    }
    
    .email-loading {
        color: #6c757d;
    }
    
    .email-error-message a {
        color: #dc3545;
        text-decoration: underline;
        font-weight: 600;
    }
    
    .email-error-message a:hover {
        color: #bd2130;
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
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .input-group input.is-valid {
        border-color: #28a745;
    }
    
    .input-group input.is-invalid {
        border-color: #dc3545;
    }
    
    @media (max-width: 420px) {
        .auth-back-btn { top:10px; left:8px; padding:8px 10px; font-size:14px; border-radius:10px; gap:8px; }
        .auth-back-btn i { font-size:20px; }
        
        .g-recaptcha {
            transform: scale(0.85);
            transform-origin: 0 0;
        }
    }
    </style>
</head>
<body class="auth-page">
    <a href="<?php echo $base_url; ?>/" class="auth-back-btn" aria-label="Inicio">
        <i class="fas fa-arrow-left" aria-hidden="true"></i>
        <span>Volver al Inicio</span>
    </a>

    <div class="auth-container">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fas fa-motorcycle"></i>
                <h1>Inversiones Rojas</h1>
            </div>
            <h2>Iniciar Sesión</h2>
            <p>Accede a tu cuenta para continuar</p>
        </div>

        <form class="auth-form" id="loginForm">
            <input type="hidden" name="csrf_token" id="csrfToken" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="text" name="website" id="website" style="position:absolute;left:-9999px;top:auto;opacity:0;" tabindex="-1" autocomplete="off">
            
            <div class="form-group">
                <label for="email">Usuario o Correo electrónico</label>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="text" id="email" name="email" placeholder="usuario123 o tu@email.com" autocomplete="username">
                </div>
                <div id="emailValidationMessage" class="email-validation-message"></div>
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Tu contraseña" autocomplete="current-password">
                </div>
            </div>

            <?php if ($__RECAPTCHA_SITE_KEY): ?>
                <div class="g-recaptcha" data-sitekey="<?php echo $__RECAPTCHA_SITE_KEY; ?>"></div>
            <?php endif; ?>

            <button type="submit" class="auth-btn primary" id="loginSubmitBtn">
                <i class="fas fa-sign-in-alt"></i>
                Iniciar Sesión
            </button>
            
            <div id="formMessage" class="form-message" style="display:none;"></div>
            
            <a href="<?php echo $base_url; ?>/app/views/auth/recuperacion.php" class="forgot-password">¿Olvidaste tu contraseña?</a>
            
            <div class="auth-footer">
                <p>¿No tienes cuenta? <a href="<?php echo $base_url; ?>/app/views/auth/register.php">Regístrate aquí</a></p>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const submitBtn = document.getElementById('loginSubmitBtn');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const emailValidationMessage = document.getElementById('emailValidationMessage');
            const formMessage = document.getElementById('formMessage');
            
            let emailValidated = false;
            let emailCheckTimeout = null;
            let lastCheckedEmail = '';
            
            function getBaseUrl() {
                const base = window.APP_BASE || '';
                return base.replace(/\/$/, '');
            }
            
            function buildUrl(path) {
                const base = getBaseUrl();
                if (!base) return path;
                return base + (path.startsWith('/') ? path : '/' + path);
            }
            
            function validateEmailFormat(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }
            
            function validateUsernameFormat(username) {
                // Username: letras, números, guiones y guiones bajos, 3-50 caracteres
                const usernameRegex = /^[a-zA-Z0-9_\-]{3,50}$/;
                return usernameRegex.test(username);
            }
            
            function isValidIdentifier(identifier) {
                return validateEmailFormat(identifier) || validateUsernameFormat(identifier);
            }
            
            function showFormMessage(message, type) {
                formMessage.textContent = message;
                formMessage.className = 'form-message ' + type;
                formMessage.style.display = 'block';
            }
            
            function hideFormMessage() {
                formMessage.style.display = 'none';
            }
            
            function showEmailMessage(message, type) {
                emailValidationMessage.innerHTML = message;
                emailValidationMessage.className = `email-validation-message email-${type}-message`;
                
                const inputGroup = emailInput.closest('.input-group').querySelector('input');
                if (type === 'success') {
                    inputGroup.classList.add('is-valid');
                    inputGroup.classList.remove('is-invalid');
                } else if (type === 'error') {
                    inputGroup.classList.add('is-invalid');
                    inputGroup.classList.remove('is-valid');
                } else {
                    inputGroup.classList.remove('is-valid', 'is-invalid');
                }
            }
            
            function clearEmailMessage() {
                emailValidationMessage.innerHTML = '';
                emailValidationMessage.className = 'email-validation-message';
                const inputGroup = emailInput.closest('.input-group').querySelector('input');
                inputGroup.classList.remove('is-valid', 'is-invalid');
            }
            
            function checkIdentifier() {
                const identifier = emailInput.value.trim();
                
                if (emailCheckTimeout) {
                    clearTimeout(emailCheckTimeout);
                }
                
                clearEmailMessage();
                emailValidated = false;
                
                if (!identifier) {
                    lastCheckedEmail = '';
                    return;
                }
                
                if (!isValidIdentifier(identifier)) {
                    showEmailMessage(
                        '<i class="fas fa-exclamation-circle"></i> Ingresa un correo electrónico válido o nombre de usuario (3-50 caracteres)',
                        'error'
                    );
                    emailValidated = false;
                    return;
                }
                
                emailCheckTimeout = setTimeout(() => {
                    // Verificar si existe como email o username
                    const emailUrl = buildUrl('/api/check_email.php') + '?email=' + encodeURIComponent(identifier);
                    const usernameUrl = buildUrl('/api/check_username.php') + '?username=' + encodeURIComponent(identifier);
                    
                    console.log('Checking identifier:', identifier);
                    console.log('Email URL:', emailUrl);
                    console.log('Username URL:', usernameUrl);

                    // Intentar primero como email
                    fetch(emailUrl)
                        .then(response => {
                            console.log('Email response status:', response.status);
                            return response.json();
                        })
                        .then(data => {
                            console.log('Email API response:', data);
                            if (data.exists) {
                                console.log('Email exists, showing success message');
                                showEmailMessage('<i class="fas fa-check-circle"></i> Usuario válido','success');
                                emailValidated = true;
                                lastCheckedEmail = identifier;
                                return; // Importante: detener aquí si el email existe
                            } else {
                                console.log('Email does not exist, trying username');
                                // Si no es email válido, intentar como username
                                return fetch(usernameUrl).then(response => response.json()).then(usernameData => {
                                    console.log('Username API response:', usernameData);
                                    if (usernameData.exists) {
                                        console.log('Username exists, showing success message');
                                        showEmailMessage('<i class="fas fa-check-circle"></i> Usuario válido','success');
                                        emailValidated = true;
                                        lastCheckedEmail = identifier;
                                    } else {
                                        console.log('Neither email nor username exists, showing error');
                                        showEmailMessage(
                                            '<i class="fas fa-exclamation-circle"></i> Usuario no encontrado',
                                            'error'
                                        );
                                        emailValidated = false;
                                    }
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error checking identifier:', error);
                            showEmailMessage(
                                '<i class="fas fa-exclamation-circle"></i> Error de conexión. Intenta de nuevo.',
                                'error'
                            );
                            emailValidated = false;
                        });
                }, 500);
            }
            
            // Event listeners para validación en tiempo real
            emailInput.addEventListener('input', function() {
                if (emailCheckTimeout) {
                    clearTimeout(emailCheckTimeout);
                }
                clearEmailMessage();
                emailValidated = false;
                lastCheckedEmail = '';
                
                // Llamar a checkIdentifier con delay
                emailCheckTimeout = setTimeout(() => {
                    checkIdentifier();
                }, 300);
            });
            
            emailInput.addEventListener('blur', function() {
                checkIdentifier();
            });
            
            loginForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                hideFormMessage();
                
                const honey = document.getElementById('website');
                if (honey && honey.value.trim() !== '') {
                    showFormMessage('Envío detectado como sospechoso', 'error');
                    return;
                }
                
                const identifier = emailInput.value.trim();
                const password = passwordInput.value.trim();
                
                if (!identifier || !password) {
                    showFormMessage('Por favor completa todos los campos', 'error');
                    if (!identifier) emailInput.focus();
                    else passwordInput.focus();
                    return;
                }
                
                if (!isValidIdentifier(identifier)) {
                    showFormMessage('Ingresa un correo electrónico válido o nombre de usuario (3-50 caracteres)', 'error');
                    emailInput.focus();
                    return;
                }
                
                if (!emailValidated) {
                    try {
                        // Verificar si existe como email o username
                        const emailUrl = buildUrl('/api/check_email.php') + '?email=' + encodeURIComponent(identifier);
                        const usernameUrl = buildUrl('/api/check_username.php') + '?username=' + encodeURIComponent(identifier);
                        
                        const emailResp = await fetch(emailUrl);
                        const emailData = await emailResp.json();
                        
                        if (emailData.exists) {
                            emailValidated = true;
                        } else {
                            const usernameResp = await fetch(usernameUrl);
                            const usernameData = await usernameResp.json();
                            if (usernameData.exists) {
                                emailValidated = true;
                            } else {
                                showEmailMessage(
                                    '<i class="fas fa-exclamation-circle"></i> Usuario no encontrado',
                                    'error'
                                );
                                return;
                            }
                        }
                    } catch (err) {
                        console.error('Error:', err);
                        showFormMessage('Error de conexión. Intenta de nuevo.', 'error');
                        return;
                    }
                }
                
                submitLogin();
            });
            
            function submitLogin() {
                <?php if ($__RECAPTCHA_SITE_KEY): ?>
                if (typeof grecaptcha !== 'undefined') {
                    const recaptchaResponse = grecaptcha.getResponse();
                    if (!recaptchaResponse || recaptchaResponse.length === 0) {
                        showFormMessage('Debes marcar el "reCAPTCHA" para continuar', 'error');
                        return;
                    }
                } else {
                    showFormMessage('Error de seguridad. Por favor recarga la página.', 'error');
                    return;
                }
                <?php endif; ?>
                
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Iniciando sesión...';
                submitBtn.disabled = true;
                
                const formData = new FormData(loginForm);
                
                fetch(buildUrl('/tests/process_login.php'), {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const redirectUrl = data.redirect;
                        const base = getBaseUrl();
                        
                        if (redirectUrl.startsWith('/') || redirectUrl.startsWith(base)) {
                            window.location.href = redirectUrl;
                        } else if (redirectUrl.startsWith('http')) {
                            const redirectHost = new URL(redirectUrl).host;
                            const currentHost = window.location.host;
                            if (redirectHost === currentHost) {
                                window.location.href = redirectUrl;
                            } else {
                                showFormMessage('Error de redirección. Contacta al administrador.', 'error');
                            }
                        } else {
                            window.location.href = redirectUrl;
                        }
                    } else {
                        showFormMessage(data.message || 'Error en el login', 'error');
                        <?php if ($__RECAPTCHA_SITE_KEY): ?>
                        if (typeof grecaptcha !== 'undefined') {
                            grecaptcha.reset();
                        }
                        <?php endif; ?>
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showFormMessage('Error en la conexión. Por favor intenta de nuevo.', 'error');
                    <?php if ($__RECAPTCHA_SITE_KEY): ?>
                    if (typeof grecaptcha !== 'undefined') {
                        grecaptcha.reset();
                    }
                    <?php endif; ?>
                })
                .finally(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            }
        });
    </script>
</body>
</html>