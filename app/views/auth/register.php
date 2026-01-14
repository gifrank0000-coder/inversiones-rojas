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
            <div class="form-row">
                <div class="form-group">
                    <label for="cedula_rif">Cédula o RIF *</label>
                    <div class="input-group">
                        <i class="fas fa-id-card"></i>
                        <input type="text" id="cedula_rif" name="cedula_rif" required placeholder="V-12345678 o J-123456789">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="telefono_principal">Teléfono *</label>
                    <div class="input-group">
                        <i class="fas fa-phone"></i>
                        <input type="tel" id="telefono_principal" name="telefono_principal" required placeholder="0412-1234567">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="nombre_completo">Nombre Completo *</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="nombre_completo" name="nombre_completo" required placeholder="Tu nombre completo">
                </div>
            </div>

            <div class="form-group">
                <label for="email">Correo Electrónico *</label>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" required placeholder="tu@email.com">
                </div>
            </div>

            <div class="form-group">
                <label for="direccion">Dirección</label>
                <div class="input-group">
                    <i class="fas fa-map-marker-alt"></i>
                    <input type="text" id="direccion" name="direccion" placeholder="Tu dirección completa">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Contraseña *</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required placeholder="Mínimo 6 caracteres">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmar Contraseña *</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Repite tu contraseña">
                    </div>
                </div>
            </div>

            <div class="form-options">
                <label class="checkbox-container">
                    <input type="checkbox" id="acceptTerms" name="acceptTerms" required>
                    <span class="checkmark"></span>
                    Acepto los <a href="#" style="color: #1F9166;">términos y condiciones</a>
                </label>
            </div>

            <button type="submit" class="auth-btn primary">
                <i class="fas fa-user-plus"></i>
                Crear Cuenta
            </button>

            <div class="auth-footer">
                <p>¿Ya tienes cuenta? <a href="login.php">Inicia Sesión</a></p>
            </div>
        </form>
    </div>

    <script>
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validación básica del frontend
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (password !== confirmPassword) {
            alert('Las contraseñas no coinciden');
            return;
        }
        
        if (password.length < 6) {
            alert('La contraseña debe tener al menos 6 caracteres');
            return;
        }
        
        if (!document.getElementById('acceptTerms').checked) {
            alert('Debes aceptar los términos y condiciones');
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