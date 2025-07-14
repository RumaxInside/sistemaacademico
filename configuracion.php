<?php
require_once '../config/database.php';
verificarRol('coordinador');

$database = new Database();
$db = $database->getConnection();

// Procesar formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'actualizar_configuracion':
                // Actualizar nombre de institución
                if (isset($_POST['nombre_institucion'])) {
                    $nombre = limpiarDatos($_POST['nombre_institucion']);
                    $query = "UPDATE configuracion SET valor = ? WHERE clave = 'nombre_institucion'";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$nombre]);
                }
                
                // Procesar membrete si se subió
                if (isset($_FILES['membrete']) && $_FILES['membrete']['error'] === UPLOAD_ERR_OK) {
                    $archivo = $_FILES['membrete'];
                    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
                    
                    // Validar tipo de archivo
                    $tipos_permitidos = ['jpg', 'jpeg', 'png', 'gif'];
                    if (in_array($extension, $tipos_permitidos)) {
                        // Crear directorio si no existe
                        $directorio = '../uploads/membretes/';
                        if (!file_exists($directorio)) {
                            mkdir($directorio, 0777, true);
                        }
                        
                        // Generar nombre único
                        $nombre_archivo = 'membrete_' . date('YmdHis') . '.' . $extension;
                        $ruta_completa = $directorio . $nombre_archivo;
                        
                        // Mover archivo
                        if (move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
                            // Guardar ruta en BD
                            $ruta_relativa = 'uploads/membretes/' . $nombre_archivo;
                            $query = "UPDATE configuracion SET valor = ? WHERE clave = 'membrete_imagen'";
                            $stmt = $db->prepare($query);
                            $stmt->execute([$ruta_relativa]);
                            
                            mostrarMensaje('exito', 'Membrete actualizado correctamente');
                        } else {
                            mostrarMensaje('error', 'Error al subir el archivo');
                        }
                    } else {
                        mostrarMensaje('error', 'Tipo de archivo no permitido. Use JPG, PNG o GIF');
                    }
                }
                
                // Otras configuraciones
                $configuraciones = [
                    'formato_fecha' => $_POST['formato_fecha'] ?? 'd/m/Y',
                    'zona_horaria' => $_POST['zona_horaria'] ?? 'America/Asuncion',
                    'semestre_actual' => $_POST['semestre_actual'] ?? '1',
                    'año_academico' => $_POST['año_academico'] ?? date('Y')
                ];
                
                foreach ($configuraciones as $clave => $valor) {
                    // Verificar si existe
                    $query = "SELECT id FROM configuracion WHERE clave = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$clave]);
                    
                    if ($stmt->fetch()) {
                        // Actualizar
                        $query = "UPDATE configuracion SET valor = ? WHERE clave = ?";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$valor, $clave]);
                    } else {
                        // Insertar
                        $query = "INSERT INTO configuracion (clave, valor) VALUES (?, ?)";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$clave, $valor]);
                    }
                }
                
                mostrarMensaje('exito', 'Configuración actualizada correctamente');
                break;
                
            case 'eliminar_membrete':
                // Obtener membrete actual
                $query = "SELECT valor FROM configuracion WHERE clave = 'membrete_imagen'";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $resultado = $stmt->fetch();
                
                if ($resultado && !empty($resultado['valor'])) {
                    // Eliminar archivo
                    $archivo = '../' . $resultado['valor'];
                    if (file_exists($archivo)) {
                        unlink($archivo);
                    }
                    
                    // Limpiar BD
                    $query = "UPDATE configuracion SET valor = '' WHERE clave = 'membrete_imagen'";
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    
                    mostrarMensaje('exito', 'Membrete eliminado correctamente');
                }
                break;
        }
        
        header('Location: configuracion.php');
        exit();
    }
}

// Obtener configuraciones actuales
$configuraciones = [];
$query = "SELECT clave, valor FROM configuracion";
$stmt = $db->prepare($query);
$stmt->execute();
while ($row = $stmt->fetch()) {
    $configuraciones[$row['clave']] = $row['valor'];
}

$mensaje = obtenerMensaje();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configuración del Sistema - Sistema Académico</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .config-section {
            background-color: #f8f9fc;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .membrete-preview {
            max-width: 100%;
            max-height: 200px;
            border: 2px solid #e3e6f0;
            border-radius: 5px;
            margin-top: 10px;
        }
        
        .config-icon {
            font-size: 3rem;
            color: #5a5c69;
            margin-bottom: 20px;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-success sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Sistema<sup>Coordinador</sup></div>
            </a>

            <hr class="sidebar-divider my-0">

            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">Gestión Académica</div>

            <li class="nav-item">
                <a class="nav-link" href="control_actividades.php">
                    <i class="fas fa-fw fa-clipboard-list"></i>
                    <span>Control de Actividades</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="progreso_clases.php">
                    <i class="fas fa-fw fa-chart-line"></i>
                    <span>Progreso de Clases</span>
                </a>
            </li>

            <li class="nav-item active">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseGestion">
                    <i class="fas fa-fw fa-cog"></i>
                    <span>Gestión</span>
                </a>
                <div id="collapseGestion" class="collapse show" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Administración:</h6>
                        <a class="collapse-item" href="usuarios.php">Usuarios</a>
                        <a class="collapse-item" href="asignaturas.php">Asignaturas</a>
                        <a class="collapse-item active" href="configuracion.php">Configuración</a>
                    </div>
                </div>
            </li>

            <hr class="sidebar-divider d-none d-md-block">

            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>
        </ul>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo $_SESSION['usuario_nombre']; ?></span>
                                <img class="img-profile rounded-circle" src="../img/undraw_profile.svg" width="30">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Cerrar Sesión
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>

                <!-- Page Content -->
                <div class="container-fluid">
                    <?php if ($mensaje): ?>
                        <div class="alert alert-<?php echo $mensaje['tipo'] === 'exito' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                            <?php echo $mensaje['texto']; ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Configuración del Sistema</h1>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="accion" value="actualizar_configuracion">
                        
                        <div class="row">
                            <!-- Información General -->
                            <div class="col-lg-6">
                                <div class="card shadow mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">
                                            <i class="fas fa-university"></i> Información Institucional
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="text-center mb-4">
                                            <i class="fas fa-university config-icon"></i>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Nombre de la Institución</label>
                                            <input type="text" class="form-control" name="nombre_institucion" 
                                                   value="<?php echo htmlspecialchars($configuraciones['nombre_institucion'] ?? ''); ?>" 
                                                   required>
                                            <small class="form-text text-muted">
                                                Este nombre aparecerá en los reportes y documentos PDF
                                            </small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Año Académico</label>
                                            <input type="number" class="form-control" name="año_academico" 
                                                   value="<?php echo $configuraciones['año_academico'] ?? date('Y'); ?>" 
                                                   min="2020" max="2030" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Semestre Actual</label>
                                            <select class="form-control" name="semestre_actual">
                                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                                    <option value="<?php echo $i; ?>" 
                                                            <?php echo ($configuraciones['semestre_actual'] ?? '1') == $i ? 'selected' : ''; ?>>
                                                        Semestre <?php echo $i; ?>
                                                    </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Membrete -->
                            <div class="col-lg-6">
                                <div class="card shadow mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-success">
                                            <i class="fas fa-image"></i> Membrete para Reportes
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="text-center mb-4">
                                            <i class="fas fa-file-image config-icon"></i>
                                        </div>
                                        
                                        <?php if (!empty($configuraciones['membrete_imagen'])): ?>
                                            <div class="text-center mb-3">
                                                <img src="../<?php echo $configuraciones['membrete_imagen']; ?>" 
                                                     alt="Membrete actual" class="membrete-preview">
                                                <div class="mt-2">
                                                    <button type="button" class="btn btn-danger btn-sm" 
                                                            onclick="eliminarMembrete()">
                                                        <i class="fas fa-trash"></i> Eliminar Membrete
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="form-group">
                                            <label>Subir nuevo membrete</label>
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="membrete" 
                                                       name="membrete" accept="image/*">
                                                <label class="custom-file-label" for="membrete">
                                                    Seleccionar archivo...
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">
                                                Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Configuración Regional -->
                            <div class="col-lg-6">
                                <div class="card shadow mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-info">
                                            <i class="fas fa-globe"></i> Configuración Regional
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label>Formato de Fecha</label>
                                            <select class="form-control" name="formato_fecha">
                                                <option value="d/m/Y" <?php echo ($configuraciones['formato_fecha'] ?? 'd/m/Y') == 'd/m/Y' ? 'selected' : ''; ?>>
                                                    DD/MM/AAAA (31/12/2025)
                                                </option>
                                                <option value="m/d/Y" <?php echo ($configuraciones['formato_fecha'] ?? '') == 'm/d/Y' ? 'selected' : ''; ?>>
                                                    MM/DD/AAAA (12/31/2025)
                                                </option>
                                                <option value="Y-m-d" <?php echo ($configuraciones['formato_fecha'] ?? '') == 'Y-m-d' ? 'selected' : ''; ?>>
                                                    AAAA-MM-DD (2025-12-31)
                                                </option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Zona Horaria</label>
                                            <select class="form-control" name="zona_horaria">
                                                <option value="America/Asuncion" 
                                                        <?php echo ($configuraciones['zona_horaria'] ?? 'America/Asuncion') == 'America/Asuncion' ? 'selected' : ''; ?>>
                                                    Paraguay (America/Asuncion)
                                                </option>
                                                <option value="America/Argentina/Buenos_Aires" 
                                                        <?php echo ($configuraciones['zona_horaria'] ?? '') == 'America/Argentina/Buenos_Aires' ? 'selected' : ''; ?>>
                                                    Argentina (America/Argentina/Buenos_Aires)
                                                </option>
                                                <option value="America/La_Paz" 
                                                        <?php echo ($configuraciones['zona_horaria'] ?? '') == 'America/La_Paz' ? 'selected' : ''; ?>>
                                                    Bolivia (America/La_Paz)
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Otras Configuraciones -->
                            <div class="col-lg-6">
                                <div class="card shadow mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-warning">
                                            <i class="fas fa-cogs"></i> Otras Configuraciones
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i>
                                            <strong>Nota:</strong> Estas configuraciones afectan a todo el sistema.
                                            Los cambios se aplicarán inmediatamente.
                                        </div>
                                        
                                        <div class="text-muted">
                                            <p><i class="fas fa-database"></i> Base de datos: MySQL</p>
                                            <p><i class="fas fa-code"></i> Versión PHP: <?php echo phpversion(); ?></p>
                                            <p><i class="fas fa-server"></i> Servidor: <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card shadow">
                                    <div class="card-body text-center">
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="fas fa-save"></i> Guardar Configuración
                                        </button>
                                        <a href="dashboard.php" class="btn btn-secondary btn-lg ml-2">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Sistema Académico 2025</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Form oculto para eliminar membrete -->
    <form id="formEliminarMembrete" method="POST" style="display: none;">
        <input type="hidden" name="accion" value="eliminar_membrete">
    </form>

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">¿Listo para salir?</h4>
                    <button class="close" type="button" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">Selecciona "Cerrar Sesión" si estás listo para terminar tu sesión actual.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancelar</button>
                    <a class="btn btn-primary" href="../logout.php">Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/js/sb-admin-2.min.js"></script>

    <script>
        // Actualizar nombre del archivo seleccionado
        $('#membrete').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName || 'Seleccionar archivo...');
        });

        function eliminarMembrete() {
            if (confirm('¿Está seguro de eliminar el membrete actual?')) {
                document.getElementById('formEliminarMembrete').submit();
            }
        }
    </script>
</body>
</html>