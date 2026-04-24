<?php
// Script a ejecutar por cron o manualmente para marcar reservas vencidas
// y enviar recordatorios 2 días antes.

require_once __DIR__ . '/../app/models/database.php';
require_once __DIR__ . '/../config/config.php';
if (file_exists(__DIR__ . '/../api/helpers/whatsapp.php')) {
    require_once __DIR__ . '/../api/helpers/whatsapp.php';
}

$pdo = Database::getInstance();
if (!$pdo) {
    echo "No DB connection\n";
    exit(1);
}

try {
    // 1) Enviar recordatorios 2 días antes (fecha_limite = CURRENT_DATE + 2)
    $stmt = $pdo->prepare("SELECT r.*, c.email, c.nombre_completo, c.telefono_principal FROM reservas r LEFT JOIN clientes c ON r.cliente_id = c.id WHERE (r.estado_reserva = 'PENDIENTE' OR r.estado_reserva = 'PRORROGADA') AND r.fecha_limite = CURRENT_DATE + INTERVAL '2 days'");
    $stmt->execute();
    $toRemind = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($toRemind as $res) {
        $telefono = $res['telefono_contacto'] ?: $res['telefono_principal'] ?: null;
        $email = $res['email'] ?? null;
        $codigo = $res['codigo_reserva'];
        $cliente = $res['nombre_completo'] ?? 'Cliente';
        $mensaje = "Recordatorio: su reserva {$codigo} vence el " . date('d/m/Y', strtotime($res['fecha_limite'])) . ". Por favor acérquese a tienda antes de la fecha. Gracias.";

        // Insert audit record if table exists
        try {
            $stmtIns = $pdo->prepare("INSERT INTO whatsapp_messages (to_number, from_number, body, status, related_type, related_id, created_at, updated_at) VALUES (:to_number, :from_number, :body, :status, :related_type, :related_id, now(), now()) RETURNING id");
            $stmtIns->execute([':to_number' => $telefono ?: '', ':from_number' => TWILIO_WHATSAPP_FROM ?? '', ':body' => $mensaje, ':status' => 'pending', ':related_type' => 'reserva_reminder', ':related_id' => $res['id']]);
            $insId = $stmtIns->fetchColumn();
        } catch (Exception $e) {
            $insId = null;
        }

        if ($telefono && function_exists('send_whatsapp_message') && defined('TWILIO_WHATSAPP_ENABLED') && TWILIO_WHATSAPP_ENABLED) {
            try {
                $resp = send_whatsapp_message($telefono, $mensaje);
                if (isset($insId) && $insId) {
                    $sid = is_array($resp) && isset($resp['sid']) ? $resp['sid'] : null;
                    $status = is_array($resp) && isset($resp['status']) ? $resp['status'] : 'queued';
                    $pdo->prepare("UPDATE whatsapp_messages SET external_sid = :sid, status = :status, payload = :payload, updated_at = now() WHERE id = :id")->execute([':sid'=>$sid,':status'=>$status,':payload'=>json_encode($resp),':id'=>$insId]);
                }
            } catch (Exception $e) {
                error_log('Error sending reminder WhatsApp: ' . $e->getMessage());
            }
        }

        // Email de respaldo (mejor usar librería real; aquí intento mail simple)
        if ($email) {
            $subject = "Recordatorio: Reserva {$codigo} vence pronto";
            $body = "Hola {$cliente},\n\nLe recordamos que su reserva {$codigo} vence el " . date('d/m/Y', strtotime($res['fecha_limite'])) . ". Por favor acérquese a la tienda para completar su pago.\n\nGracias.";
            @mail($email, $subject, $body);
        }
    }

    // 2) Marcar reservas vencidas (fecha_limite < current_date)
    $stmtV = $pdo->prepare("SELECT id, codigo_reserva, telefono_contacto, cliente_id FROM reservas WHERE (estado_reserva = 'PENDIENTE' OR estado_reserva = 'PRORROGADA') AND fecha_limite < CURRENT_DATE");
    $stmtV->execute();
    $vencidas = $stmtV->fetchAll(PDO::FETCH_ASSOC);

    foreach ($vencidas as $rv) {
        // actualizar estado
        $pdo->prepare("UPDATE reservas SET estado_reserva = 'VENCIDA', updated_at = now() WHERE id = :id")->execute([':id' => $rv['id']]);

        $telefono = $rv['telefono_contacto'] ?? null;
        $mensaje = "Su reserva {$rv['codigo_reserva']} ha vencido. Si desea prorrogar contacte con la tienda.";

        try {
            $stmtIns = $pdo->prepare("INSERT INTO whatsapp_messages (to_number, from_number, body, status, related_type, related_id, created_at, updated_at) VALUES (:to_number, :from_number, :body, :status, :related_type, :related_id, now(), now()) RETURNING id");
            $stmtIns->execute([':to_number' => $telefono ?: '', ':from_number' => TWILIO_WHATSAPP_FROM ?? '', ':body' => $mensaje, ':status' => 'pending', ':related_type' => 'reserva_vencida', ':related_id' => $rv['id']]);
            $insId = $stmtIns->fetchColumn();
        } catch (Exception $e) {
            $insId = null;
        }

        if ($telefono && function_exists('send_whatsapp_message') && defined('TWILIO_WHATSAPP_ENABLED') && TWILIO_WHATSAPP_ENABLED) {
            try {
                $resp = send_whatsapp_message($telefono, $mensaje);
                if ($insId) {
                    $sid = is_array($resp) && isset($resp['sid']) ? $resp['sid'] : null;
                    $status = is_array($resp) && isset($resp['status']) ? $resp['status'] : 'queued';
                    $pdo->prepare("UPDATE whatsapp_messages SET external_sid = :sid, status = :status, payload = :payload, updated_at = now() WHERE id = :id")->execute([':sid'=>$sid,':status'=>$status,':payload'=>json_encode($resp),':id'=>$insId]);
                }
            } catch (Exception $e) {
                error_log('Error sending vencida WhatsApp: ' . $e->getMessage());
            }
        }
    }

    echo "Expire job completed. Reminders: " . count($toRemind) . ", Vencidas: " . count($vencidas) . "\n";
    exit(0);

} catch (Exception $e) {
    error_log('expire_reservas error: ' . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

?>