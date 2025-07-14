<?php
require_once '../config/database.php';
verificarRol('coordinador');

$database = new Database();
$db = $database->getConnection();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'crear':
                $numero_documento = limpiarDatos($_POST['numero_documento']);
                $nombre_completo = limpiarDatos($_POST['nombre_completo']);
                $password = $_POST['password'];
                $rol = limpiarDatos($_POST['rol']);
                $estado = limpiarDatos($_POST['estado']);
                
                // Verificar si el documento ya existe
                $query = "SELECT id FROM usuarios WHERE numero_documento = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$numero_documento]);
                
                if ($stmt->fetch()) {
                    mostrarMensaje('error', 'Ya existe un usuario con ese número de documento');
                } else {
                    // Crear usuario
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $query = "INSERT INTO usuarios (numero_documento, nombre_completo, contraseña, rol, estado) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($query);
                    
                    if ($stmt->execute([$numero_documento, $nombre_completo, $password_hash, $rol, $estado])) {
                        mostrarMensaje('exito', 'Usuario creado exitosamente');
                    } else {
                        mostrarMensaje('error', 'Error al crear el usuario');
                    }
                }
                break;
                
            case 'editar':
                $id = (int)$_POST['id'];
                $numero_documento = limpiarDatos($_POST['numero_documento']);
                $nombre_completo = limpiarDatos($_POST['nombre_completo']);
                $rol = limpiarDatos($_POST['rol']);
                $estado = limpiarDatos($_POST['estado']);
                
                // Verificar documento duplicado
                $query = "SELECT id FROM usuarios WHERE numero_documento = ? AND id != ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$numero_documento, $id]);
                
                if ($stmt->fetch()) {
                    mostrarMensaje('error', 'Ya existe otro usuario con ese número de documento');
                } else {
                    // Actualizar usuario
                    if (!empty($_POST['password'])) {
                        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $query = "UPDATE usuarios SET numero_documento = ?, nombre_completo = ?, contraseña = ?, rol = ?, estado = ? WHERE id = ?";
                        $params = [$numero_documento, $nombre_completo, $password_hash, $rol, $estado, $id];
                    } else {
                        $query = "UPDATE usuarios SET numero_documento = ?, nombre_completo = ?, rol = ?, estado = ? WHERE id = ?";
                        $params = [$numero_documento, $nombre_completo, $rol, $estado, $id];
                    }
                    
                    $stmt = $db->prepare($query);
                    if ($stmt->execute($params)) {
                        mostrarMensaje('exito', 'Usuario actualizado exitosamente');
                    } else {
                        mostrarMensaje('error', 'Error al actualizar el usuario');
                    }
                }
                break;
                
            case 'cambiar_estado':
                $id = (int)$_POST['id'];
                $estado = $_POST['estado'] === 'activo' ? 'inactivo' : 'activo';
                
                $query = "UPDATE usuarios SET estado = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$estado, $id])) {
                    mostrarMensaje('exito', 'Estado actualizado exitosamente');
                } else {
                    mostrarMensaje('error', 'Error al actualizar el estado');
                }
                break;
        }
        
        header('Location: usuarios.php');
        exit();
    }
}

// Obtener lista de usuarios
$query = "SELECT * FROM usuarios ORDER BY rol, nombre_completo";
$stmt = $db->prepare($query);
$stmt->execute();
$usuarios = $stmt->fetchAll();

$mensaje = obtenerMensaje();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Usuarios - Sistema Académico</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.25/css/dataTables.bootstrap4.min.css" rel="stylesheet">
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
                        <a class="collapse-item active" href="usuarios.php">Usuarios</a>
                        <a class="collapse-item" href="asignaturas.php">Asignaturas</a>
                        <a class="collapse-item" href="configuracion.php">Configuración</a>
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
                        <h1 class="h3 mb-0 text-gray-800">Gestión de Usuarios</h1>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#modalUsuario">
                            <i class="fas fa-user-plus"></i> Nuevo Usuario
                        </button>
                    </div>

                    <!-- DataTable -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-users"></i> Lista de Usuarios
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>N° Documento</th>
                                            <th>Nombre Completo</th>
                                            <th>Rol</th>
                                            <th>Estado</th>
                                            <th>Fecha Creación</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios as $usuario): ?>
                                            <tr>
                                                <td><?php echo $usuario['numero_documento']; ?></td>
                                                <td><?php echo $usuario['nombre_completo']; ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $usuario['rol'] === 'coordinador' ? 'success' : 'primary'; ?>">
                                                        <?php echo ucfirst($usuario['rol']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo $usuario['estado'] === 'activo' ? 'success' : 'danger'; ?>">
                                                        <?php echo ucfirst($usuario['estado']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($usuario['fecha_creacion'])); ?></td>
                                                <td>
                                                    <button class="btn btn-info btn-sm" onclick="editarUsuario(<?php echo htmlspecialchars(json_encode($usuario)); ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" style="display: inline-block;">
                                                        <input type="hidden" name="accion" value="cambiar_estado">
                                                        <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                                                        <input type="hidden" name="estado" value="<?php echo $usuario['estado']; ?>">
                                                        <button type="submit" class="btn btn-<?php echo $usuario['estado'] === 'activo' ? 'danger' : 'success'; ?> btn-sm"
                                                                title="<?php echo $usuario['estado'] === 'activo' ? 'Desactivar' : 'Activar'; ?>">
                                                            <i class="fas fa-<?php echo $usuario['estado'] === 'activo' ? 'ban' : 'check'; ?>"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
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

    <!-- Modal Usuario -->
    <div class="modal fade" id="modalUsuario" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalUsuarioTitle">Nuevo Usuario</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST" id="formUsuario">
                    <input type="hidden" name="accion" id="accion" value="crear">
                    <input type="hidden" name="id" id="usuario_id">
                    
                    <div class="modal-body">
                        <div class="form-group">
                            <label>N° de Documento <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="numero_documento" id="numero_documento" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Nombre Completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre_completo" id="nombre_completo" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" id="password">
                            <small class="form-text text-muted" id="passwordHelp">
                                Dejar en blanco para no cambiar la contraseña (solo al editar)
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label>Rol <span class="text-danger">*</span></label>
                            <select class="form-control" name="rol" id="rol" required>
                                <option value="docente">Docente</option>
                                <option value="coordinador">Coordinador</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Estado <span class="text-danger">*</span></label>
                            <select class="form-control" name="estado" id="estado" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.25/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.25/js/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
                }
            });
        });

        // Reset form cuando se abre para nuevo usuario
        $('#modalUsuario').on('show.bs.modal', function (e) {
            if (!$(e.relatedTarget).hasClass('btn-edit')) {
                $('#formUsuario')[0].reset();
                $('#accion').val('crear');
                $('#usuario_id').val('');
                $('#modalUsuarioTitle').text('Nuevo Usuario');
                $('#password').attr('required', true);
                $('#passwordHelp').hide();
            }
        });

        function editarUsuario(usuario) {
            $('#modalUsuarioTitle').text('Editar Usuario');
            $('#accion').val('editar');
            $('#usuario_id').val(usuario.id);
            $('#numero_documento').val(usuario.numero_documento);
            $('#nombre_completo').val(usuario.nombre_completo);
            $('#rol').val(usuario.rol);
            $('#estado').val(usuario.estado);
            $('#password').val('').attr('required', false);
            $('#passwordHelp').show();
            $('#modalUsuario').modal('show');
        }
    </script>
</body>
</html>