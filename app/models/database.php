<?php
class Database {
    private $host = "localhost";
    private $db_name = "Inversiones_Rojas";
    private $username = "postgres";
    private $password = "1234";
    private $port = "5432";
    public $conn;
    private $lastError = null;

    public function getConnection() {
        $this->conn = null;
        try {
            // Use the configured port (bugfix: previously used host twice)
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Verificar conexión exitosa
            error_log("✅ Conexión a PostgreSQL establecida correctamente");
            
        } catch(PDOException $exception) {
            $this->lastError = $exception->getMessage();
            error_log("❌ Error de conexión PostgreSQL: " . $exception->getMessage());
            error_log("📋 Detalles - Host: " . $this->host . ", DB: " . $this->db_name . ", Usuario: " . $this->username);
            $this->conn = null;
        }
        return $this->conn;
    }

    /**
     * Devuelve el último mensaje de error ocurrido en la conexión (si existe)
     */
    public function getLastError() {
        return $this->lastError;
    }

    // Método para verificar conexión
    public function testConnection() {
        $conn = $this->getConnection();
        if ($conn) {
            try {
                $stmt = $conn->query("SELECT version()");
                $version = $stmt->fetch();
                error_log("✅ PostgreSQL Version: " . $version['version']);
                return true;
            } catch (PDOException $e) {
                error_log("❌ Error en test: " . $e->getMessage());
                return false;
            }
        }
        return false;
    }
}
?>