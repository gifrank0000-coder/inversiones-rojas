<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
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
        /* Estilos para el proceso de recuperación */
        .recovery-container {
            max-width: 500px;
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
        
        .recovery-method {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .recovery-method:hover {
            border-color: #1F9166;
            transform: translateY(-3px);
        }
        
        .recovery-method.selected {
            border-color: #1F9166;
            background: #e8f5e8;
        }
        
        .method-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1F9166, #30B583);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .method-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .method-description {
            color: #6c757d;
            font-size: 0.9rem;
            line-height: 1.5;
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
        
        .btn-recovery-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-recovery-secondary:hover {
            background: #5a6268;
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
        
        .security-questions {
            display: grid;
            gap: 20px;
            margin: 25px 0;
        }
        
        .question-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #1F9166;
        }
        
        .question-text {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
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
    </style>
</head>
<body class="auth-page">
    <!-- Botón de volver al login -->
    <a href="Login.php" class="auth-back-btn" aria-label="Volver al login">
        <i class="fas fa-arrow-left" aria-hidden="true"></i>
        <span>Volver al Login</span>
    </a>

    <div class="recovery-container">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fas fa-motorcycle"></i>
                <h1>Recuperar Contraseña</h1>
            </div>
            <p>Selecciona un método para recuperar tu acceso</p>
        </div>

        <!-- Pasos del proceso -->
        <div class="recovery-steps">
            <div class="step active" id="step1">
                <div class="step-number">1</div>
                <div class="step-label">Método</div>
            </div>
            <div class="step" id="step2">
                <div class="step-number">2</div>
                <div class="step-label">Verificación</div>
            </div>
            <div class="step" id="step3">
                <div class="step-number">3</div>
                <div class="step-label">Nueva Contraseña</div>
            </div>
        </div>

        <!-- Paso 1: Selección de Método -->
        <div class="recovery-content active" id="step1-content">
            <h3 style="text-align: center; margin-bottom: 30px; color: #2c3e50;">
                <i class="fas fa-shield-alt"></i> Selecciona un método de recuperación
            </h3>
            
            <div class="recovery-method" data-method="cedula">
                <div class="method-icon">
                    <i class="fas fa-id-card"></i>
                </div>
                <div class="method-title">Por Cédula y Preguntas de Seguridad</div>
                <div class="method-description">
                    Verifica tu identidad con tu número de cédula y responde las preguntas de seguridad que configuraste.
                </div>
            </div>
            
            <div class="recovery-method" data-method="correo">
                <div class="method-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="method-title">Por Correo Electrónico</div>
                <div class="method-description">
                    Te enviaremos un código de verificación al correo asociado a tu cuenta.
                </div>
            </div>
            
            <div class="recovery-actions">
                <button type="button" class="btn-recovery btn-recovery-secondary" onclick="window.location.href='Login.php'">
                    <i class="fas fa-times"></i>
                    Cancelar
                </button>
                <button type="button" class="btn-recovery btn-recovery-primary" id="nextStep1" disabled>
                    Continuar
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- Paso 2: Verificación por Cédula -->
        <div class="recovery-content" id="step2-cedula-content">
            <h3 style="text-align: center; margin-bottom: 30px; color: #2c3e50;">
                <i class="fas fa-id-card"></i> Verificación por Cédula
            </h3>
            
            <div class="form-group">
                <label for="cedula">Número de Cédula o RIF</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="cedula" name="cedula" placeholder="Ej: V-12345678 o J-123456789">
                </div>
                <div class="form-hint">Ingresa tu cédula o RIF sin puntos ni guiones</div>
            </div>
            
            <div class="recovery-actions">
                <button type="button" class="btn-recovery btn-recovery-outline" onclick="backToStep1()">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </button>
                <button type="button" class="btn-recovery btn-recovery-primary" id="verifyCedula">
                    Verificar Cédula
                    <i class="fas fa-check"></i>
                </button>
            </div>
        </div>

        <!-- Paso 2.1: Preguntas de Seguridad -->
        <div class="recovery-content" id="step2-questions-content">
            <h3 style="text-align: center; margin-bottom: 30px; color: #2c3e50;">
                <i class="fas fa-question-circle"></i> Preguntas de Seguridad
            </h3>
            
            <div class="security-questions" id="securityQuestionsContainer">
                <!-- Las preguntas se cargarán dinámicamente -->
            </div>
            
            <div class="recovery-actions">
                <button type="button" class="btn-recovery btn-recovery-outline" onclick="backToCedula()">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </button>
                <button type="button" class="btn-recovery btn-recovery-primary" id="verifyAnswers">
                    Verificar Respuestas
                    <i class="fas fa-check"></i>
                </button>
            </div>
        </div>

        <!-- Paso 2: Verificación por Correo -->
        <div class="recovery-content" id="step2-correo-content">
            <h3 style="text-align: center; margin-bottom: 30px; color: #2c3e50;">
                <i class="fas fa-envelope"></i> Verificación por Correo
            </h3>
            
            <div class="form-group">
                <label for="emailRecovery">Correo Electrónico Registrado</label>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="emailRecovery" name="email" placeholder="tu@email.com">
                </div>
            </div>
            
            <div class="recovery-actions">
                <button type="button" class="btn-recovery btn-recovery-outline" onclick="backToStep1()">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </button>
                <button type="button" class="btn-recovery btn-recovery-primary" id="sendCode">
                    Enviar Código
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>

        <!-- Paso 2.2: Ingreso de Código -->
        <div class="recovery-content" id="step2-code-content">
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
                <button type="button" class="btn-recovery btn-recovery-outline" onclick="backToEmail()">
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
                    <input type="password" id="newPassword" name="newPassword" placeholder="Mínimo 8 caracteres">
                    <button type="button" class="toggle-password" onclick="togglePassword('newPassword')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="form-hint">
                    <i class="fas fa-info-circle"></i> Debe incluir mayúsculas, minúsculas, números y caracteres especiales
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirmPassword">Confirmar Contraseña</label>
                <div class="input-group">
                    <i class="fas fa-key"></i>
                    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Repite tu contraseña">
                    <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="recovery-actions">
                <button type="button" class="btn-recovery btn-recovery-outline" onclick="backToVerification()">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </button>
                <button type="button" class="btn-recovery btn-recovery-primary" id="updatePassword">
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
        let selectedMethod = null;
        let verificationData = {};
        let timerInterval;
        let countdown = 60;
        let currentStep = 1;

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            // Selección de método
            document.querySelectorAll('.recovery-method').forEach(method => {
                method.addEventListener('click', function() {
                    document.querySelectorAll('.recovery-method').forEach(m => {
                        m.classList.remove('selected');
                    });
                    this.classList.add('selected');
                    selectedMethod = this.getAttribute('data-method');
                    document.getElementById('nextStep1').disabled = false;
                });
            });
            
            // Navegación
            document.getElementById('nextStep1').addEventListener('click', goToStep2);
            document.getElementById('verifyCedula').addEventListener('click', verifyCedula);
            document.getElementById('sendCode').addEventListener('click', sendVerificationCode);
            document.getElementById('verifyCode').addEventListener('click', verifyCode);
            document.getElementById('verifyAnswers').addEventListener('click', verifyAnswers);
            document.getElementById('updatePassword').addEventListener('click', updatePassword);
            document.getElementById('resendCode').addEventListener('click', resendCode);
            
            // Código de verificación
            setupCodeInputs();
            
            // Validación en tiempo real
            document.getElementById('newPassword')?.addEventListener('input', validatePassword);
            document.getElementById('confirmPassword')?.addEventListener('input', validatePassword);
        });
        
        // Navegación entre pasos
        function goToStep2() {
            if (!selectedMethod) return;
            
            updateSteps(2);
            
            if (selectedMethod === 'cedula') {
                showContent('step2-cedula-content');
            } else if (selectedMethod === 'correo') {
                showContent('step2-correo-content');
            }
        }
        
        function backToStep1() {
            updateSteps(1);
            showContent('step1-content');
            selectedMethod = null;
            document.querySelectorAll('.recovery-method').forEach(m => {
                m.classList.remove('selected');
            });
            document.getElementById('nextStep1').disabled = true;
        }
        
        function backToCedula() {
            showContent('step2-cedula-content');
        }
        
        function backToEmail() {
            showContent('step2-correo-content');
        }
        
        function backToVerification() {
            if (selectedMethod === 'cedula') {
                showContent('step2-questions-content');
            } else if (selectedMethod === 'correo') {
                showContent('step2-code-content');
            }
        }
        
        // Actualizar indicador de pasos
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
        }
        
        // Verificar cédula
        function verifyCedula() {
            const cedula = document.getElementById('cedula').value.trim();
            
            if (!cedula) {
                alert('Por favor ingresa tu número de cédula');
                return;
            }
            
            // Validar formato de cédula/RIF
            const cedulaPattern = /^[JGVEP]-?\d{7,9}$/i;
            if (!cedulaPattern.test(cedula)) {
                alert('Formato de cédula/RIF inválido. Use: V-12345678 o J-123456789');
                return;
            }
            
            // Mostrar carga
            const btn = document.getElementById('verifyCedula');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
            btn.disabled = true;
            
            // Simular verificación (en producción sería una petición AJAX)
            setTimeout(() => {
                // Guardar datos de verificación
                verificationData.cedula = cedula;
                verificationData.userId = 'USR_' + Math.random().toString(36).substr(2, 9);
                verificationData.userName = 'Juan Pérez'; // Esto vendría del servidor
                
                // Cargar preguntas de seguridad
                loadSecurityQuestions();
                
                // Mostrar preguntas
                showContent('step2-questions-content');
                
                // Restaurar botón
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 1500);
        }
        
        // Cargar preguntas de seguridad
        function loadSecurityQuestions() {
            const container = document.getElementById('securityQuestionsContainer');
            
            // Simular preguntas de la base de datos
            const questions = [
                {
                    id: 1,
                    question: "¿Cuál es el nombre de tu primera mascota?",
                    placeholder: "Nombre de tu mascota"
                },
                {
                    id: 2,
                    question: "¿En qué ciudad naciste?",
                    placeholder: "Ciudad de nacimiento"
                },
                {
                    id: 3,
                    question: "¿Cuál es tu comida favorita?",
                    placeholder: "Tu comida favorita"
                }
            ];
            
            container.innerHTML = '';
            
            questions.forEach((q, index) => {
                const questionHtml = `
                    <div class="question-item">
                        <div class="question-text">${q.question}</div>
                        <div class="input-group">
                            <i class="fas fa-question"></i>
                            <input type="text" 
                                   class="security-answer" 
                                   data-question-id="${q.id}"
                                   placeholder="${q.placeholder}"
                                   required>
                        </div>
                    </div>
                `;
                container.innerHTML += questionHtml;
            });
        }
        
        // Verificar respuestas
        function verifyAnswers() {
            const answers = document.querySelectorAll('.security-answer');
            const emptyAnswers = Array.from(answers).filter(a => !a.value.trim());
            
            if (emptyAnswers.length > 0) {
                alert('Por favor responde todas las preguntas de seguridad');
                return;
            }
            
            // Mostrar carga
            const btn = document.getElementById('verifyAnswers');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
            btn.disabled = true;
            
            // Simular verificación
            setTimeout(() => {
                // En producción, aquí se enviarían las respuestas al servidor
                goToStep3();
                
                // Restaurar botón
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 1500);
        }
        
        // Enviar código por correo
        function sendVerificationCode() {
            const email = document.getElementById('emailRecovery').value.trim();
            
            if (!email) {
                alert('Por favor ingresa tu correo electrónico');
                return;
            }
            
            // Validar formato de email
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                alert('Por favor ingresa un correo electrónico válido');
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
            
            // Simular envío
            setTimeout(() => {
                // Generar código de 6 dígitos
                verificationData.verificationCode = Math.floor(100000 + Math.random() * 900000).toString();
                console.log('Código generado:', verificationData.verificationCode); // Solo para desarrollo
                
                // Mostrar paso de código
                showContent('step2-code-content');
                startCountdown();
                
                // Restaurar botón
                btn.innerHTML = originalText;
                btn.disabled = false;
                
                alert(`Código enviado a ${email}. En desarrollo, el código es: ${verificationData.verificationCode}`);
            }, 2000);
        }
        
        // Configurar inputs de código
        function setupCodeInputs() {
            const inputs = document.querySelectorAll('.code-input');
            
            inputs.forEach((input, index) => {
                input.addEventListener('input', function() {
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
                alert('Por favor ingresa el código completo de 6 dígitos');
                return;
            }
            
            // Mostrar carga
            const btn = document.getElementById('verifyCode');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
            btn.disabled = true;
            
            // Simular verificación
            setTimeout(() => {
                if (enteredCode === verificationData.verificationCode) {
                    goToStep3();
                } else {
                    alert('Código incorrecto. Por favor intenta nuevamente.');
                }
                
                // Restaurar botón
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 1000);
        }
        
        // Ir al paso 3
        function goToStep3() {
            updateSteps(3);
            showContent('step3-content');
        }
        
        // Reenviar código
        function resendCode() {
            if (countdown > 0) return;
            
            // Generar nuevo código
            verificationData.verificationCode = Math.floor(100000 + Math.random() * 900000).toString();
            console.log('Nuevo código:', verificationData.verificationCode); // Solo para desarrollo
            
            // Reiniciar countdown
            countdown = 60;
            startCountdown();
            
            alert(`Nuevo código enviado. En desarrollo, el código es: ${verificationData.verificationCode}`);
        }
        
        // Iniciar countdown
        function startCountdown() {
            clearInterval(timerInterval);
            countdown = 60;
            
            timerInterval = setInterval(() => {
                countdown--;
                const countdownEl = document.getElementById('countdown');
                const resendLink = document.getElementById('resendCode');
                
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
            const newPass = document.getElementById('newPassword')?.value || '';
            const confirmPass = document.getElementById('confirmPassword')?.value || '';
            const btn = document.getElementById('updatePassword');
            
            // Validar fortaleza
            const hasMinLength = newPass.length >= 8;
            const hasUpperCase = /[A-Z]/.test(newPass);
            const hasLowerCase = /[a-z]/.test(newPass);
            const hasNumbers = /\d/.test(newPass);
            const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(newPass);
            const passwordsMatch = newPass === confirmPass && newPass !== '';
            
            btn.disabled = !(hasMinLength && hasUpperCase && hasLowerCase && 
                           hasNumbers && hasSpecialChar && passwordsMatch);
        }
        
        // Cambiar contraseña
        function updatePassword() {
            const newPass = document.getElementById('newPassword').value;
            const confirmPass = document.getElementById('confirmPassword').value;
            
            if (newPass !== confirmPass) {
                alert('Las contraseñas no coinciden');
                return;
            }
            
            // Mostrar carga
            const btn = document.getElementById('updatePassword');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
            btn.disabled = true;
            
            // Simular actualización
            setTimeout(() => {
                // En producción, aquí se enviaría la nueva contraseña al servidor
                showContent('success-content');
                
                // Restaurar botón
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 1500);
        }
        
        // Mostrar/ocultar contraseña
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
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
    </script>
</body>
</html>