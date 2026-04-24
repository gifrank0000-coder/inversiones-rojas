<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';

// Si el usuario ya está logueado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Inversiones Rojas</title>
    <link rel="icon" href="<?php echo BASE_URL; ?>/public/img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/pages/auth.css">
    <style>
        /* Estilos específicos para recuperación */
        .recovery-container {
            max-width: 450px;
            margin: 50px auto;
            padding: 40px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .recovery-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }
        
        .recovery-steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 50px;
            right: 50px;
            height: 3px;
            background: #e9ecef;
            z-index: 1;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-bottom: 10px;
            border: 3px solid white;
            transition: all 0.3s;
        }
        
        .step.active .step-number {
            background: #1F9166;
            color: white;
            transform: scale(1.1);
        }
        
        .step.completed .step-number {
            background: #28a745;
            color: white;
        }
        
        .step-label {
            font-size: 0.85rem;
            color: #6c757d;
            text-align: center;
            font-weight: 500;
        }
        
        .step.active .step-label {
            color: #1F9166;
            font-weight: 600;
        }
        
        .recovery-content {
            display: none;
        }
        
        .recovery-content.active {
            display: block;
            animation: fadeIn 0.5s;
        }
        
        .recovery-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn-recovery {
            flex: 1;
            padding: 14px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-recovery-primary {
            background: linear-gradient(135deg, #1F9166, #30B583);
            color: white;
        }
        
        .btn-recovery-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(31, 145, 102, 0.3);
        }
        
        .btn-recovery-outline {
            background: transparent;
            border: 2px solid #e9ecef;
            color: #2c3e50;
        }
        
        .btn-recovery-outline:hover {
            border-color: #1F9166;
            color: #1F9166;
        }
        
        .code-inputs {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 20px 0;
        }
        
        .code-input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            background: white;
            color: #2c3e50;
        }
        
        .code-input:focus {
            outline: none;
            border-color: #1F9166;
            box-shadow: 0 0 0 3px rgba(31, 145, 102, 0.1);
        }
        
        .resend-code {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
        }
        
        .resend-code a {
            color: #1F9166;
            text-decoration: none;
            font-weight: 600;
        }
        
        .resend-code a:hover {
            text-decoration: underline;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
        }
        
        .info-box {
            background: #e8f4ff;
            border-left: 4px solid #0066cc;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .info-box i {
            color: #0066cc;
            margin-right: 10px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            .recovery-container {
                margin: 20px;
                padding: 25px;
            }
            
            .recovery-actions {
                flex-direction: column;
            }
            
            .code-input {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }
        }
        
        /* Debug styles removed */
    </style>
</head>
<body class="auth-page">
    <!-- Botón de volver al login -->
    <a href="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/app/views/auth/Login.php" class="auth-back-btn" aria-label="Volver al login">
        <i class="fas fa-arrow-left" aria-hidden="true"></i>
        <span>Volver al Login</span>
    </a>

    <div class="recovery-container">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fas fa-key"></i>
                <h1>Recuperar Contraseña</h1>
            </div>
            <p>Te enviaremos un código de verificación a tu correo electrónico</p>
        </div>

        <!-- Mensajes de error/éxito -->
        <div id="messageContainer"></div>
        
        <!-- Debug info removed -->

        <!-- Pasos del proceso -->
        <div class="recovery-steps">
            <div class="step active" id="step1">
                <div class="step-number">1</div>
                <div class="step-label">Ingresar Email</div>
            </div>
            <div class="step" id="step2">
                <div class="step-number">2</div>
                <div class="step-label">Verificar Código</div>
            </div>
            <div class="step" id="step3">
                <div class="step-number">3</div>
                <div class="step-label">Nueva Contraseña</div>
            </div>
        </div>

        <!-- Paso 1: Ingresar Email -->
        <div class="recovery-content active" id="step1-content">
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <span>Introduce el correo electrónico asociado a tu cuenta para recibir un código de verificación.</span>
            </div>
            
            <div class="form-group">
                <label for="emailRecovery">Correo Electrónico Registrado</label>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="emailRecovery" name="email" placeholder="tu@email.com" required>
                </div>
            </div>
            
            <div class="recovery-actions">
                <button type="button" class="btn-recovery btn-recovery-outline" onclick="window.location.href='Login.php'">
                    <i class="fas fa-times"></i>
                    Cancelar
                </button>
                <button type="button" class="btn-recovery btn-recovery-primary" id="sendCode">
                    Enviar Código
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            
            <!-- Botón de debug eliminado -->
        </div>

        <!-- Paso 2: Verificar Código -->
        <div class="recovery-content" id="step2-content">
            <h3 style="text-align: center; margin-bottom: 30px; color: #2c3e50;">
                <i class="fas fa-key"></i> Código de Verificación
            </h3>
            
            <p style="text-align: center; color: #6c757d; margin-bottom: 30px;">
                Hemos enviado un código de 6 dígitos a tu correo electrónico.
                <br>
                <strong id="userEmail"></strong>
            </p>
            
            <div class="code-inputs">
                <input type="text" class="code-input" maxlength="1" data-index="0">
                <input type="text" class="code-input" maxlength="1" data-index="1">
                <input type="text" class="code-input" maxlength="1" data-index="2">
                <input type="text" class="code-input" maxlength="1" data-index="3">
                <input type="text" class="code-input" maxlength="1" data-index="4">
                <input type="text" class="code-input" maxlength="1" data-index="5">
            </div>
            
            <div class="resend-code">
                ¿No recibiste el código? 
                <a href="#" id="resendCode">Reenviar código</a>
                <br>
                <span id="countdown">(Puedes reenviar en 60 segundos)</span>
            </div>
            
            <div class="recovery-actions">
                <button type="button" class="btn-recovery btn-recovery-outline" onclick="backToStep1()">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </button>
                <button type="button" class="btn-recovery btn-recovery-primary" id="verifyCode" disabled>
                    Verificar Código
                    <i class="fas fa-check"></i>
                </button>
            </div>
        </div>

        <!-- Paso 3: Nueva Contraseña -->
        <div class="recovery-content" id="step3-content">
            <h3 style="text-align: center; margin-bottom: 30px; color: #2c3e50;">
                <i class="fas fa-lock"></i> Crear Nueva Contraseña
            </h3>
            
            <div class="form-group">
                <label for="newPassword">Nueva Contraseña</label>
                <div class="input-group">
                    <i class="fas fa-key"></i>
                    <input type="password" id="newPassword" name="newPassword" placeholder="Mínimo 8 caracteres" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('newPassword')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="form-hint">
                    <i class="fas fa-info-circle"></i> Debe incluir mayúsculas, minúsculas y números
                </div>
                <div class="password-strength" id="passwordStrength" style="margin-top: 10px;"></div>
            </div>
            
            <div class="form-group">
                <label for="confirmPassword">Confirmar Contraseña</label>
                <div class="input-group">
                    <i class="fas fa-key"></i>
                    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Repite tu contraseña" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div id="passwordMatch" style="margin-top: 5px; font-size: 0.9rem;"></div>
            </div>
            
            <div class="recovery-actions">
                <button type="button" class="btn-recovery btn-recovery-outline" onclick="backToStep2()">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </button>
                <button type="button" class="btn-recovery btn-recovery-primary" id="updatePassword" disabled>
                    Cambiar Contraseña
                    <i class="fas fa-save"></i>
                </button>
            </div>
        </div>

        <!-- Mensaje de Éxito -->
        <div class="recovery-content" id="success-content">
            <div class="success-message">
                <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 20px;"></i>
                <h3 style="margin-bottom: 10px;">¡Contraseña Actualizada!</h3>
                <p>Tu contraseña ha sido cambiada exitosamente.</p>
                <p>Ahora puedes iniciar sesión con tu nueva contraseña.</p>
            </div>
            
            <div class="recovery-actions">
                <button type="button" class="btn-recovery btn-recovery-primary" onclick="window.location.href='Login.php'">
                    <i class="fas fa-sign-in-alt"></i>
                    Iniciar Sesión
                </button>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let verificationData = {
            email: '',
            token: '',
            user_id: null
        };
        let timerInterval;
        let countdown = 60;
        let currentStep = 1;

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== RECUPERACIÓN INICIADA ===');
            console.log('Base URL:', '<?php echo BASE_URL; ?>');
            
            // Event listeners
            document.getElementById('sendCode').addEventListener('click', sendVerificationCode);
            document.getElementById('verifyCode').addEventListener('click', verifyCode);
            document.getElementById('updatePassword').addEventListener('click', updatePassword);
            document.getElementById('resendCode').addEventListener('click', resendCode);
            
            // Configurar inputs de código
            setupCodeInputs();
            
            // Validación de contraseña en tiempo real
            document.getElementById('newPassword').addEventListener('input', validatePassword);
            document.getElementById('confirmPassword').addEventListener('input', validatePassword);
            
            // Permitir enviar con Enter en el email
            document.getElementById('emailRecovery').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendVerificationCode();
                }
            });
            
            // Debug info removed from UI
        });
        
        // Mostrar mensaje
        function showMessage(message, type = 'error') {
            const container = document.getElementById('messageContainer');
            container.innerHTML = `
                <div class="${type === 'error' ? 'error-message' : 'success-message'}">
                    <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 'check-circle'}"></i>
                    ${message}
                </div>
            `;
            
            // Auto-ocultar mensajes de éxito después de 5 segundos
            if (type === 'success') {
                setTimeout(() => {
                    container.innerHTML = '';
                }, 5000);
            }
        }
        
        // showDebugInfo removed
        
        // Navegación entre pasos
        function updateSteps(step) {
            currentStep = step;
            
            // Resetear todos los steps
            document.querySelectorAll('.step').forEach(stepEl => {
                stepEl.classList.remove('active', 'completed');
            });
            
            // Marcar steps anteriores como completados
            for (let i = 1; i <= step; i++) {
                const stepEl = document.getElementById('step' + i);
                if (stepEl) {
                    if (i < step) {
                        stepEl.classList.add('completed');
                    } else {
                        stepEl.classList.add('active');
                    }
                }
            }
        }
        
        // Mostrar contenido específico
        function showContent(contentId) {
            document.querySelectorAll('.recovery-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(contentId).classList.add('active');
            document.getElementById('messageContainer').innerHTML = '';
        }
        
        // Volver al paso 1
        function backToStep1() {
            updateSteps(1);
            showContent('step1-content');
        }
        
        // Volver al paso 2
        function backToStep2() {
            updateSteps(2);
            showContent('step2-content');
        }
        
     
        // FUNCIÓN CORREGIDA: Enviar código de verificación
       
        function sendVerificationCode() {
            const email = document.getElementById('emailRecovery').value.trim();
            
            if (!email) {
                showMessage('Por favor ingresa tu correo electrónico', 'error');
                return;
            }
            
            // Validar formato de email
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                showMessage('Por favor ingresa un correo electrónico válido', 'error');
                return;
            }
            
            // Mostrar carga
            const btn = document.getElementById('sendCode');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
            btn.disabled = true;
            
            // Guardar datos
            verificationData.email = email;
            document.getElementById('userEmail').textContent = email;
            
           
            // CORRECCIÓN PRINCIPAL: Usar URLSearchParams en lugar de FormData
          
            const params = new URLSearchParams();
            params.append('action', 'send_code'); 
            params.append('email', email);
            
            // URL de la API
            const apiUrl = '<?php echo BASE_URL; ?>/api/auth/recover.php';
            
            // DEBUG: Mostrar qué se envía
            console.log('=== ENVIANDO CÓDIGO ===');
            console.log('URL:', apiUrl);
            console.log('Parámetros:', params.toString());
            console.log('Email:', email);
            
            // Enviar petición
            fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: params.toString()
            })
            .then(response => {
                console.log('Status:', response.status);
                console.log('OK?', response.ok);
                
                // Primero obtener como texto para debug
                return response.text().then(text => {
                    console.log('Respuesta cruda:', text);
                    
                    // Intentar parsear como JSON
                    try {
                        const data = JSON.parse(text);
                        return { ok: response.ok, data: data };
                    } catch (e) {
                        console.error('Error parseando JSON:', e);
                        console.error('Texto que causó error:', text);
                        throw new Error('Respuesta no es JSON válido: ' + text.substring(0, 100));
                    }
                });
            })
            .then(result => {
                const data = result.data;
                console.log('Datos parseados:', data);
                
                if (data.success) {
                    // Guardar token
                    verificationData.token = data.token || 'test_token';
                    
                    // Mostrar paso de código
                    updateSteps(2);
                    showContent('step2-content');
                    startCountdown();
                    
                    if (data.code_debug) {
                       
                        alert(`🔑 CÓDIGO DE VERIFICACIÓN (MODO PRUEBA): ${data.code_debug}\n\nUsa este código para continuar.`);
                        showMessage(`Código generado: ${data.code_debug} (ver alerta)`, 'success');
                    } else if (data.code) {
                        alert(`🔑 CÓDIGO DE VERIFICACIÓN: ${data.code}`);
                        showMessage('Código enviado a tu correo electrónico', 'success');
                    } else {
                        showMessage(data.message || 'Código enviado', 'success');
                    }
                    
                } else {
                    showMessage(data.message || 'Error al enviar el código', 'error');
                }
            })
            .catch(error => {
                console.error('Error completo:', error);
                showMessage(`Error: ${error.message}`, 'error');
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }
        
        // Configurar inputs de código
        function setupCodeInputs() {
            const inputs = document.querySelectorAll('.code-input');
            
            inputs.forEach((input, index) => {
                input.addEventListener('input', function() {
                    // Solo permitir números
                    this.value = this.value.replace(/\D/g, '');
                    
                    // Mover al siguiente input
                    if (this.value.length === 1 && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                    
                    // Verificar si todos los inputs están llenos
                    checkCodeInputs();
                });
                
                input.addEventListener('keydown', function(e) {
                    // Permitir navegación con flechas
                    if (e.key === 'ArrowRight' && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    } else if (e.key === 'ArrowLeft' && index > 0) {
                        inputs[index - 1].focus();
                    } else if (e.key === 'Backspace') {
                        if (this.value === '' && index > 0) {
                            inputs[index - 1].focus();
                        }
                    }
                });
                
                // Pegar código completo
                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pasteData = e.clipboardData.getData('text').replace(/\D/g, '');
                    
                    if (pasteData.length === 6) {
                        const inputs = document.querySelectorAll('.code-input');
                        for (let i = 0; i < 6; i++) {
                            if (inputs[i]) {
                                inputs[i].value = pasteData[i] || '';
                            }
                        }
                        checkCodeInputs();
                    }
                });
            });
        }
        
        // Verificar inputs de código
        function checkCodeInputs() {
            const inputs = document.querySelectorAll('.code-input');
            const allFilled = Array.from(inputs).every(input => input.value.length === 1);
            document.getElementById('verifyCode').disabled = !allFilled;
        }
        
        // Verificar código
        function verifyCode() {
            const inputs = document.querySelectorAll('.code-input');
            const enteredCode = Array.from(inputs).map(input => input.value).join('');
            
            if (enteredCode.length !== 6) {
                showMessage('Por favor ingresa el código completo de 6 dígitos', 'error');
                return;
            }
            
            // Mostrar carga
            const btn = document.getElementById('verifyCode');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
            btn.disabled = true;
            
            // Enviar petición
            const params = new URLSearchParams();
            params.append('action', 'verify_code');
            params.append('code', enteredCode);
            params.append('token', verificationData.token);
            params.append('email', verificationData.email);
            
            fetch('<?php echo BASE_URL; ?>/api/auth/recover.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: params.toString()
            })
            .then(response => response.json())
            .then(data => {
                console.log('Respuesta verify:', data);
                
                if (data.success) {
                
                    updateSteps(3);
                    showContent('step3-content');
                    showMessage('Código verificado correctamente', 'success');
                } else {
                    showMessage(data.message || 'Código incorrecto', 'error');
                  
                    document.querySelectorAll('.code-input').forEach(input => {
                        input.value = '';
                    });
                    document.getElementById('verifyCode').disabled = true;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Error de conexión con el servidor', 'error');
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }
        
        // Reenviar código
        function resendCode() {
            if (countdown > 0) return;
            
            // Mostrar carga
            const resendLink = document.getElementById('resendCode');
            resendLink.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
            resendLink.style.pointerEvents = 'none';
            
            // Enviar petición
            const params = new URLSearchParams();
            params.append('action', 'resend_code');
            params.append('email', verificationData.email);
            params.append('token', verificationData.token);
            
            fetch('<?php echo BASE_URL; ?>/api/auth/recover.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: params.toString()
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reiniciar countdown
                    countdown = 60;
                    startCountdown();
                    
                    showMessage('Nuevo código enviado a tu correo', 'success');
                    
                    // Mostrar nuevo código si está en debug
                    if (data.code_debug) {
                        alert(`🔑 NUEVO CÓDIGO: ${data.code_debug}`);
                    }
                } else {
                    showMessage(data.message || 'Error al reenviar el código', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Error de conexión con el servidor', 'error');
            })
            .finally(() => {
                setTimeout(() => {
                    resendLink.innerHTML = 'Reenviar código';
                }, 1000);
            });
        }
        
        // Iniciar countdown
        function startCountdown() {
            clearInterval(timerInterval);
            countdown = 60;
            
            const countdownEl = document.getElementById('countdown');
            const resendLink = document.getElementById('resendCode');
            
            timerInterval = setInterval(() => {
                countdown--;
                
                if (countdown > 0) {
                    countdownEl.textContent = `(Puedes reenviar en ${countdown} segundos)`;
                    resendLink.style.pointerEvents = 'none';
                    resendLink.style.opacity = '0.5';
                } else {
                    countdownEl.textContent = '';
                    resendLink.style.pointerEvents = 'auto';
                    resendLink.style.opacity = '1';
                    clearInterval(timerInterval);
                }
            }, 1000);
        }
        
        // Validar contraseña
        function validatePassword() {
            const newPass = document.getElementById('newPassword').value;
            const confirmPass = document.getElementById('confirmPassword').value;
            const strengthEl = document.getElementById('passwordStrength');
            const matchEl = document.getElementById('passwordMatch');
            const btn = document.getElementById('updatePassword');
            
            // Validar fortaleza de contraseña
            const hasMinLength = newPass.length >= 8;
            const hasUpperCase = /[A-Z]/.test(newPass);
            const hasLowerCase = /[a-z]/.test(newPass);
            const hasNumbers = /\d/.test(newPass);
            
            let strength = 0;
            let strengthText = '';
            let strengthColor = '';
            
            if (hasMinLength) strength++;
            if (hasUpperCase) strength++;
            if (hasLowerCase) strength++;
            if (hasNumbers) strength++;
            
            switch(strength) {
                case 0:
                    strengthText = 'Muy débil';
                    strengthColor = '#dc3545';
                    break;
                case 1:
                    strengthText = 'Débil';
                    strengthColor = '#ffc107';
                    break;
                case 2:
                    strengthText = 'Regular';
                    strengthColor = '#fd7e14';
                    break;
                case 3:
                    strengthText = 'Buena';
                    strengthColor = '#28a745';
                    break;
                case 4:
                    strengthText = 'Excelente';
                    strengthColor = '#20c997';
                    break;
            }
            
            // Mostrar fortaleza
            strengthEl.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="font-weight: 600; color: ${strengthColor}">
                        Fortaleza: ${strengthText}
                    </div>
                    <div style="flex: 1; height: 5px; background: #e9ecef; border-radius: 3px;">
                        <div style="width: ${strength * 25}%; height: 100%; background: ${strengthColor}; border-radius: 3px;"></div>
                    </div>
                </div>
            `;
            
            // Validar coincidencia
            if (confirmPass) {
                if (newPass === confirmPass) {
                    matchEl.innerHTML = '<span style="color: #28a745;"><i class="fas fa-check"></i> Las contraseñas coinciden</span>';
                } else {
                    matchEl.innerHTML = '<span style="color: #dc3545;"><i class="fas fa-times"></i> Las contraseñas no coinciden</span>';
                }
            } else {
                matchEl.innerHTML = '';
            }
            
            // Habilitar botón si todo está correcto
            btn.disabled = !(strength >= 3 && newPass === confirmPass && newPass.length > 0);
        }
        
        // Cambiar contraseña
        function updatePassword() {
            const newPass = document.getElementById('newPassword').value;
            const confirmPass = document.getElementById('confirmPassword').value;
            
            if (newPass !== confirmPass) {
                showMessage('Las contraseñas no coinciden', 'error');
                return;
            }
            
            // Mostrar carga
            const btn = document.getElementById('updatePassword');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
            btn.disabled = true;
            
            // Enviar petición
            const params = new URLSearchParams();
            params.append('action', 'update_password');
            params.append('new_password', newPass);
            params.append('confirm_password', confirmPass);
            params.append('token', verificationData.token);
            params.append('email', verificationData.email);
            
            fetch('<?php echo BASE_URL; ?>/api/auth/recover.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: params.toString()
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito
                    showContent('success-content');
                } else {
                    showMessage(data.message || 'Error al actualizar la contraseña', 'error');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Error de conexión con el servidor', 'error');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }
        
        // Mostrar/ocultar contraseña
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.parentElement.querySelector('.toggle-password i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Función para testear la conexión (solo debug)
        function testConnection() {
            console.log('=== TEST CONNECTION ===');
            
            // Probar diferentes métodos
            const tests = [
                { 
                    name: 'Test 1: FormData', 
                    fn: () => {
                        const formData = new FormData();
                        formData.append('action', 'send_code');
                        formData.append('email', 'test@test.com');
                        return fetch('<?php echo BASE_URL; ?>/api/auth/recover.php', {
                            method: 'POST',
                            body: formData
                        });
                    }
                },
                { 
                    name: 'Test 2: URLSearchParams', 
                    fn: () => {
                        const params = new URLSearchParams();
                        params.append('action', 'send_code');
                        params.append('email', 'test@test.com');
                        return fetch('<?php echo BASE_URL; ?>/api/auth/recover.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: params.toString()
                        });
                    }
                },
                { 
                    name: 'Test 3: JSON', 
                    fn: () => {
                        return fetch('<?php echo BASE_URL; ?>/api/auth/recover.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ action: 'send_code', email: 'test@test.com' })
                        });
                    }
                }
            ];
            
            // Ejecutar tests
            tests.forEach(test => {
                test.fn()
                    .then(response => response.text())
                    .then(text => {
                        console.log(`${test.name}:`, text);
                    })
                    .catch(error => {
                        console.error(`${test.name} error:`, error);
                    });
            });
            
            showMessage('Tests ejecutados. Revisa la consola (F12)', 'success');
        }
    </script>
    
</body>
</html>