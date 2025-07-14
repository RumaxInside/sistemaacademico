<?php
class Database {
    private $host = 'localhost:3308';
    private $db_name = 'sistema_academico';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", 
                $this->username, 
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            die("Error de conexión: " . $exception->getMessage());
        }
        return $this->conn;
    }
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function verificarLogin() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ../login.php');
        exit();
    }
}

function verificarRol($rol_requerido) {
    verificarLogin();
    if ($_SESSION['rol'] !== $rol_requerido) {
        header('Location: ../acceso_denegado.php');
        exit();
    }
}

function limpiarDatos($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function mostrarMensaje($tipo, $mensaje) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['mensaje'] = ['tipo' => $tipo, 'texto' => $mensaje];
}

function obtenerMensaje() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['mensaje'])) {
        $mensaje = $_SESSION['mensaje'];
        unset($_SESSION['mensaje']);
        return $mensaje;
    }
    return null;
}
?>