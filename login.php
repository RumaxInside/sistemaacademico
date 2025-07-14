<?php
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documento = limpiarDatos($_POST['documento']);
    $password = $_POST['password'];
    
    if (empty($documento) || empty($password)) {
        $error = 'Por favor complete todos los campos';
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            if ($db === null) {
                $error = 'Error de conexión a la base de datos.';
            } else {
                $query = "SELECT id, numero_documento, nombre_completo, contraseña, rol, estado 
                          FROM usuarios 
                          WHERE numero_documento = ? AND estado = 'activo'";
                
                $stmt = $db->prepare($query);
                $stmt->execute([$documento]);
                $usuario = $stmt->fetch();
                
                if ($usuario && password_verify($password, $usuario['contraseña'])) {
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario_nombre'] = $usuario['nombre_completo'];
                    $_SESSION['usuario_documento'] = $usuario['numero_documento'];
                    $_SESSION['rol'] = $usuario['rol'];
                    
                    if ($usuario['rol'] === 'docente') {
                        header('Location: docente/dashboard.php');
                    } else {
                        header('Location: coordinador/dashboard.php');
                    }
                    exit();
                } else {
                    $error = 'Credenciales incorrectas o usuario inactivo';
                }
            }
        } catch (Exception $e) {
            $error = 'Error del sistema: ' . $e->getMessage();
        }
    }
}

if (isset($_SESSION['usuario_id'])) {
    if ($_SESSION['rol'] === 'docente') {
        header('Location: docente/dashboard.php');
    } else {
        header('Location: coordinador/dashboard.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Sistema Académico - Login</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gradient-primary">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-6 d-none d-lg-block bg-login-image" style="background: linear-gradient(45deg, #4e73df, #224abe);"></div>
                            <div class="col-lg-6">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">¡Bienvenido!</h1>
                                        <p class="mb-4">Sistema de Gestión Académica</p>
                                    </div>
                                    
                                    <?php if (!empty($error)): ?>
                                        <div class="alert alert-danger">
                                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <form method="POST" class="user">
                                        <div class="form-group">
                                            <input type="text" 
                                                   class="form-control form-control-user" 
                                                   name="documento"
                                                   placeholder="Número de Documento"
                                                   value="<?php echo isset($documento) ? htmlspecialchars($documento) : ''; ?>"
                                                   required>
                                        </div>
                                        <div class="form-group">
                                            <input type="password" 
                                                   class="form-control form-control-user"
                                                   name="password" 
                                                   placeholder="Contraseña"
                                                   required>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-user btn-block">
                                            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                                        </button>
                                    </form>
                                    
                                    <hr>
                                    <div class="text-center">
                                        <small class="text-muted">
                                            <strong>DevPy - 2025</strong>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/js/sb-admin-2.min.js"></script>
</body>
</html>