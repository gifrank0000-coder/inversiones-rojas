<?php
// ============================================================
// EmailHelper.php  →  app/helpers/EmailHelper.php
// Cliente SMTP puro en PHP — sin PHPMailer, sin Composer.
// Funciona en XAMPP directamente con Gmail App Password.
// Lee la configuración desde las constantes de config.php:
//   SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS,
//   SMTP_FROM, SMTP_FROM_NAME, SMTP_SECURE
// ============================================================

/**
 * Enviar email via SMTP nativo (sin dependencias externas).
 *
 * @param string $to_email   Destinatario
 * @param string $to_name    Nombre del destinatario
 * @param string $subject    Asunto
 * @param string $html_body  Cuerpo HTML
 * @return array ['success'=>bool, 'message'=>string]
 */
function enviarEmailSMTP(
    string $to_email,
    string $to_name,
    string $subject,
    string $html_body
): array {

    // ── Leer configuracion desde config.php ──────────────────
    $host      = defined('SMTP_HOST')      ? SMTP_HOST      : 'smtp.gmail.com';
    $port      = defined('SMTP_PORT')      ? (int)SMTP_PORT : 587;
    $user      = defined('SMTP_USER')      ? SMTP_USER      : '';
    $pass      = defined('SMTP_PASS')      ? SMTP_PASS      : '';
    $from      = defined('SMTP_FROM')      ? SMTP_FROM      : $user;
    $from_name = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Inversiones Rojas';
    $secure    = defined('SMTP_SECURE')    ? strtolower(SMTP_SECURE) : 'tls';
    $timeout   = defined('SMTP_TIMEOUT')   ? (int)SMTP_TIMEOUT : 20;

    if (!$user || !$pass) {
        return ['success' => false, 'message' => 'SMTP_USER o SMTP_PASS no configurados en config.php'];
    }

    // ── Construir el email en formato MIME ────────────────────
    $boundary = '----=_Part_' . md5(uniqid('', true));

    $text_body = strip_tags(str_replace(
        ['<br>', '<br/>', '<br />', '</p>', '</div>', '</h1>', '</h2>', '</h3>'],
        "\n", $html_body
    ));

    $mime  = "MIME-Version: 1.0\r\n";
    $mime .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";

    $body  = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($text_body)) . "\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($html_body)) . "\r\n";
    $body .= "--{$boundary}--\r\n";

    // ── Conectar al servidor SMTP ─────────────────────────────
    $errno = 0; $errstr = '';

    // Opciones SSL — en XAMPP local los certificados CA no están instalados,
    // por eso se desactiva verify_peer. En producción (hosting real) esto
    // funciona automáticamente sin estas opciones.
    $ssl_opts = [
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ]
    ];
    $ctx_ssl = stream_context_create($ssl_opts);

    // Puerto 465 = SSL directo | Puerto 587 = STARTTLS
    $socket_host = ($port === 465 || $secure === 'ssl')
        ? "ssl://{$host}"
        : $host;

    $sock = @stream_socket_client(
        "{$socket_host}:{$port}",
        $errno, $errstr, $timeout,
        STREAM_CLIENT_CONNECT,
        $ctx_ssl
    );
    if (!$sock) {
        return [
            'success' => false,
            'message' => "No se pudo conectar a {$host}:{$port} — {$errstr} ({$errno})"
        ];
    }
    stream_set_timeout($sock, $timeout);

    // ── Helpers internos ──────────────────────────────────────
    $read = function() use ($sock): string {
        $data = '';
        while ($line = fgets($sock, 515)) {
            $data .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }
        return $data;
    };

    $cmd = function(string $command) use ($sock, $read): string {
        fwrite($sock, $command . "\r\n");
        return $read();
    };

    try {
        // Banner de bienvenida
        $banner = $read();
        if (substr(trim($banner), 0, 3) !== '220') {
            throw new RuntimeException("Banner inesperado del servidor: " . trim($banner));
        }

        // EHLO
        $ehlo = $cmd("EHLO " . (gethostname() ?: 'localhost'));
        if (substr($ehlo, 0, 3) !== '250') {
            $ehlo = $cmd("HELO localhost");
        }

        // STARTTLS para puerto 587
        if ($port === 587 || $secure === 'tls') {
            $tls = $cmd("STARTTLS");
            if (substr($tls, 0, 3) !== '220') {
                throw new RuntimeException("STARTTLS rechazado: " . trim($tls));
            }
            // Activar TLS — desactivar verify_peer para XAMPP local
            // (en hosting de producción los CA certs están instalados y esto no hace falta)
            stream_context_set_option($sock, 'ssl', 'verify_peer',       false);
            stream_context_set_option($sock, 'ssl', 'verify_peer_name',  false);
            stream_context_set_option($sock, 'ssl', 'allow_self_signed', true);

            if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException("No se pudo activar TLS con Gmail");
            }
            // Re-handshake despues de TLS
            $cmd("EHLO " . (gethostname() ?: 'localhost'));
        }

        // Autenticacion LOGIN
        $auth = $cmd("AUTH LOGIN");
        if (substr($auth, 0, 3) !== '334') {
            throw new RuntimeException("AUTH LOGIN rechazado: " . trim($auth));
        }

        $u = $cmd(base64_encode($user));
        if (substr($u, 0, 3) !== '334') {
            throw new RuntimeException("Usuario SMTP rechazado por el servidor");
        }

        $p = $cmd(base64_encode($pass));
        if (substr($p, 0, 3) !== '235') {
            throw new RuntimeException(
                "Autenticacion fallida. Verifica que:\n"
                . "1) SMTP_PASS en config.php es el App Password de Gmail (16 caracteres)\n"
                . "2) La cuenta de Gmail tiene Verificacion en 2 pasos activada\n"
                . "3) El App Password se creo desde myaccount.google.com/apppasswords\n"
                . "Respuesta del servidor: " . trim($p)
            );
        }

        // Cabeceras codificadas para soporte UTF-8
        $from_enc = "=?UTF-8?B?" . base64_encode($from_name) . "?=";
        $to_enc   = $to_name
            ? ("=?UTF-8?B?" . base64_encode($to_name) . "?= <{$to_email}>")
            : $to_email;
        $subj_enc = "=?UTF-8?B?" . base64_encode($subject) . "?=";

        // MAIL FROM
        $mf = $cmd("MAIL FROM:<{$from}>");
        if (substr($mf, 0, 3) !== '250') {
            throw new RuntimeException("MAIL FROM rechazado: " . trim($mf));
        }

        // RCPT TO
        $rt = $cmd("RCPT TO:<{$to_email}>");
        if (substr($rt, 0, 3) !== '250') {
            throw new RuntimeException("Destinatario rechazado ({$to_email}): " . trim($rt));
        }

        // DATA
        $dc = $cmd("DATA");
        if (substr($dc, 0, 3) !== '354') {
            throw new RuntimeException("DATA rechazado: " . trim($dc));
        }

        // Mensaje completo
        $message  = "From: {$from_enc} <{$from}>\r\n";
        $message .= "To: {$to_enc}\r\n";
        $message .= "Subject: {$subj_enc}\r\n";
        $message .= "Date: " . date('r') . "\r\n";
        $message .= "Message-ID: <" . md5(uniqid()) . "@inversiones-rojas>\r\n";
        $message .= $mime . "\r\n" . $body . "\r\n.";

        $sent = $cmd($message);
        if (substr($sent, 0, 3) !== '250') {
            throw new RuntimeException("Mensaje rechazado por el servidor: " . trim($sent));
        }

        $cmd("QUIT");
        fclose($sock);

        return ['success' => true, 'message' => "Email enviado correctamente a {$to_email}"];

    } catch (RuntimeException $e) {
        @fclose($sock);
        error_log("[EmailHelper] " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}