<?php require_once __DIR__ . '/../../../config/config.php';
// Valor seguro para la clave de reCAPTCHA (puede no estar definida)
$__RECAPTCHA_SITE_KEY = defined('RECAPTCHA_SITE_KEY') ? constant('RECAPTCHA_SITE_KEY') : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Inversiones Rojas</title>
    <link rel="icon" href="<?php echo BASE_URL; ?>/public/img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/pages/auth.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/admin.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/pages/home.css">
    <?php if ($__RECAPTCHA_SITE_KEY): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
    <script>
        // Base URL para peticiones desde JavaScript
        window.APP_BASE = '<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>';
    </script>
    <!-- Estilos inline para el botón 'Inicio' (incluido directamente en la vista) -->
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
    @media (max-width: 420px) {
        .auth-back-btn { top:10px; left:8px; padding:8px 10px; font-size:14px; border-radius:10px; gap:8px; }
        .auth-back-btn i { font-size:20px; }
    }
    </style>
</head>
    <!-- Botón de volver al inicio (visible y fijo en la esquina superior izquierda) -->
    <a href="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/" class="auth-back-btn" aria-label="Inicio">
        <i class="fas fa-arrow-left" aria-hidden="true"></i>
        <span>Volver al Inicio</span>
    </a>

<body class="auth-page">

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
            <!-- Honeypot field to trap simple bots (should remain empty) -->
            <input type="text" name="website" id="website" style="position:absolute;left:-9999px;top:auto;opacity:0;" tabindex="-1" autocomplete="off">
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" required placeholder="tu@email.com">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required placeholder="Tu contraseña">
                </div>
            </div>

            <div class="form-options">
                <label class="checkbox-container">
                    <input type="checkbox" id="rememberMe" name="rememberMe">
                    <span class="checkmark"></span>
                    Recordar sesión
                </label>
                <a href="recuperacion.php" class="forgot-password">¿Olvidaste tu contraseña?</a>
            </div>

            <!-- Simple anti-bot checkbox (client + server validated) -->
            <div style="margin:12px 0; display:flex; align-items:center; gap:12px;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" id="notRobot" name="not_robot" value="1">
                    <span>No soy un robot</span>
                </label>
            </div>

            <?php if ($__RECAPTCHA_SITE_KEY): ?>
            <div style="margin:10px 0;">
                <div class="g-recaptcha" data-sitekey="<?php echo $__RECAPTCHA_SITE_KEY; ?>"></div>
            </div>
            <?php endif; ?>

            <button type="submit" class="auth-btn primary">
                <i class="fas fa-sign-in-alt"></i>
                Iniciar Sesión
            </button>

            <div class="auth-footer">
                <p>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Simple client-side anti-bot check
            var notRobot = document.getElementById('notRobot');
            var honey = document.getElementById('website');
            if (honey && honey.value.trim() !== '') {
                alert('Envío detectado como sospechoso');
                return;
            }
            if (notRobot && !notRobot.checked) {
                alert('Por favor confirma que no eres un robot');
                return;
            }

            // Mostrar mensaje de carga
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Iniciando sesión...';
            submitBtn.disabled = true;
            
            const formData = new FormData(this);
            // Adjuntar token reCAPTCHA si está configurado
            <?php if ($__RECAPTCHA_SITE_KEY): ?>
            try {
                if (typeof grecaptcha !== 'undefined') {
                    var recaptchaResponse = grecaptcha.getResponse();
                    if (!recaptchaResponse) {
                        alert('Por favor completa el reCAPTCHA');
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        return;
                    }
                    formData.append('g-recaptcha-response', recaptchaResponse);
                }
            } catch (err) {
                console.warn('reCAPTCHA check failed:', err);
            }
            <?php endif; ?>
            
            fetch('<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/tests/process_login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.href = data.redirect;
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error en la conexión');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    </script>
</body>
</html>