<?php
class Usuario {
    private $conn;
    private $table_name = "usuarios";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Obtener usuario por email
     */
    public function obtenerPorEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email AND estado = true";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualizar contador de intentos fallidos
     */
    public function actualizarIntentosFallidos($usuarioId, $intentos) {
        $query = "UPDATE " . $this->table_name . " SET intentos_fallidos = :intentos WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':intentos', $intentos);
        $stmt->bindParam(':id', $usuarioId);
        return $stmt->execute();
    }

    /**
     * Resetear intentos fallidos y desbloquear cuenta
     */
    public function resetearIntentosFallidos($usuarioId) {
        $query = "UPDATE " . $this->table_name . " SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $usuarioId);
        return $stmt->execute();
    }

    /**
     * Bloquear usuario por tiempo determinado
     */
    public function bloquearUsuario($usuarioId, $bloqueadoHasta) {
        $query = "UPDATE " . $this->table_name . " SET bloqueado_hasta = :bloqueado_hasta WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':bloqueado_hasta', $bloqueadoHasta);
        $stmt->bindParam(':id', $usuarioId);
        return $stmt->execute();
    }

    /**
     * Obtener información de bloqueo e intentos
     */
    public function obtenerInfoBloqueo($usuarioId) {
        $query = "SELECT intentos_fallidos, bloqueado_hasta FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $usuarioId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualizar último acceso
     */
    public function actualizarUltimoAcceso($usuarioId) {
        $query = "UPDATE " . $this->table_name . " SET ultimo_acceso = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $usuarioId);
        return $stmt->execute();
    }
}
?>