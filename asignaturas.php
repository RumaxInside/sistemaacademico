<?php
require_once '../config/database.php';
verificarRol('coordinador');

$database = new Database();
$db = $database->getConnection();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'crear_asignatura':
                $codigo = limpiarDatos($_POST['codigo_asignatura']);
                $nombre = limpiarDatos($_POST['nombre']);
                $semestre = (int)$_POST['semestre'];
                $carga_horaria = (int)$_POST['carga_horaria'];
                $horario = limpiarDatos($_POST['horario']);
                $horas_practicas = (float)$_POST['porcentaje_horas_practicas'];
                $horas_teoricas = (float)$_POST['porcentaje_horas_teoricas'];
                $prerequisitos = limpiarDatos($_POST['prerequisitos']);
                $ciclo = limpiarDatos($_POST['ciclo']);
                
                // Verificar código duplicado
                $query = "SELECT id FROM asignaturas WHERE codigo_asignatura = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$codigo]);
                
                if ($stmt->fetch()) {
                    mostrarMensaje('error', 'Ya existe una asignatura con ese código');
                } else {
                    $query = "INSERT INTO asignaturas (codigo_asignatura, nombre, semestre, carga_horaria, horario, 
                              porcentaje_horas_practicas, porcentaje_horas_teoricas, prerequisitos, ciclo) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($query);
                    
                    if ($stmt->execute([$codigo, $nombre, $semestre, $carga_horaria, $horario, 
                                       $horas_practicas, $horas_teoricas, $prerequisitos, $ciclo])) {
                        mostrarMensaje('exito', 'Asignatura creada exitosamente');
                    } else {
                        mostrarMensaje('error', 'Error al crear la asignatura');
                    }
                }
                break;
                
            case 'editar_asignatura':
                $id = (int)$_POST['id'];
                $codigo = limpiarDatos($_POST['codigo_asignatura']);
                $nombre = limpiarDatos($_POST['nombre']);
                $semestre = (int)$_POST['semestre'];
                $carga_horaria = (int)$_POST['carga_horaria'];
                $horario = limpiarDatos($_POST['horario']);
                $horas_practicas = (float)$_POST['porcentaje_horas_practicas'];
                $horas_teoricas = (float)$_POST['porcentaje_horas_teoricas'];
                $prerequisitos = limpiarDatos($_POST['prerequisitos']);
                $ciclo = limpiarDatos($_POST['ciclo']);
                
                // Verificar código duplicado
                $query = "SELECT id FROM asignaturas WHERE codigo_asignatura = ? AND id != ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$codigo, $id]);
                
                if ($stmt->fetch()) {
                    mostrarMensaje('error', 'Ya existe otra asignatura con ese código');
                } else {
                    $query = "UPDATE asignaturas SET codigo_asignatura = ?, nombre = ?, semestre = ?, 
                              carga_horaria = ?, horario = ?, porcentaje_horas_practicas = ?, 
                              porcentaje_horas_teoricas = ?, prerequisitos = ?, ciclo = ? WHERE id = ?";
                    $stmt = $db->prepare($query);
                    
                    if ($stmt->execute([$codigo, $nombre, $semestre, $carga_horaria, $horario, 
                                       $horas_practicas, $horas_teoricas, $prerequisitos, $ciclo, $id])) {
                        mostrarMensaje('exito', 'Asignatura actualizada exitosamente');
                    } else {
                        mostrarMensaje('error', 'Error al actualizar la asignatura');
                    }
                }
                break;
                
            case 'agregar_elemento':
                $tipo = limpiarDatos($_POST['tipo_elemento']);
                $codigo_asignatura = limpiarDatos($_POST['codigo_asignatura']);
                $descripcion = limpiarDatos($_POST['descripcion']);
                
                $tablas = [
                    'objetivos' => 'objetivos_clase',
                    'contenidos' => 'contenidos',
                    'estrategias_metodologicas' => 'estrategias_metodologicas',
                    'estrategias_evaluativas' => 'estrategias_evaluativas',
                    'recursos' => 'recursos'
                ];
                
                if (isset($tablas[$tipo])) {
                    $tabla = $tablas[$tipo];
                    $query = "INSERT INTO $tabla (codigo_asignatura, descripcion) VALUES (?, ?)";
                    $stmt = $db->prepare($query);
                    
                    if ($stmt->execute([$codigo_asignatura, $descripcion])) {
                        mostrarMensaje('exito', 'Elemento agregado exitosamente');
                    } else {
                        mostrarMensaje('error', 'Error al agregar el elemento');
                    }
                }
                break;
                
            case 'eliminar_elemento':
                $tipo = limpiarDatos($_POST['tipo']);
                $id = (int)$_POST['id'];
                
                $tablas = [
                    'objetivos' => 'objetivos_clase',
                    'contenidos' => 'contenidos',
                    'estrategias_metodologicas' => 'estrategias_metodologicas',
                    'estrategias_evaluativas' => 'estrategias_evaluativas',
                    'recursos' => 'recursos'
                ];
                
                if (isset($tablas[$tipo])) {
                    $tabla = $tablas[$tipo];
                    $query = "DELETE FROM $tabla WHERE id = ?";
                    $stmt = $db->prepare($query);
                    
                    if ($stmt->execute([$id])) {
                        mostrarMensaje('exito', 'Elemento eliminado exitosamente');
                    } else {
                        mostrarMensaje('error', 'Error al eliminar el elemento');
                    }
                }
                break;
        }
        
        header('Location: asignaturas.php');
        exit();
    }
}

// Obtener lista de asignaturas
$query = "SELECT * FROM asignaturas ORDER BY codigo_asignatura";
$stmt = $db->prepare($query);
$stmt->execute();
$asignaturas = $stmt->fetchAll();

$mensaje = obtenerMensaje();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Asignaturas - Sistema Académico</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.25/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    
    <style>
        .elementos-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .elemento-item {
            padding: 8px;
            margin-bottom: 5px;
            background-color: #f8f9fc;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .elemento-item:hover {
            background-color: #e3e6f0;
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
                        <a class="collapse-item active" href="asignaturas.php">Asignaturas</a>
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
                        <h1 class="h3 mb-0 text-gray-800">Gestión de Asignaturas</h1>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#modalAsignatura">
                            <i class="fas fa-plus"></i> Nueva Asignatura
                        </button>
                    </div>

                    <!-- DataTable -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-book"></i> Lista de Asignaturas
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Nombre</th>
                                            <th>Semestre</th>
                                            <th>Carga Horaria</th>
                                            <th>Ciclo</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($asignaturas as $asignatura): ?>
                                            <tr>
                                                <td><strong><?php echo $asignatura['codigo_asignatura']; ?></strong></td>
                                                <td><?php echo $asignatura['nombre']; ?></td>
                                                <td><?php echo $asignatura['semestre']; ?></td>
                                                <td><?php echo $asignatura['carga_horaria']; ?> horas</td>
                                                <td><?php echo $asignatura['ciclo']; ?></td>
                                                <td>
                                                    <button class="btn btn-info btn-sm" onclick="editarAsignatura(<?php echo htmlspecialchars(json_encode($asignatura)); ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-success btn-sm" onclick="gestionarElementos('<?php echo $asignatura['codigo_asignatura']; ?>', '<?php echo htmlspecialchars($asignatura['nombre']); ?>')">
                                                        <i class="fas fa-list"></i> Elementos
                                                    </button>
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

    <!-- Modal Asignatura -->
    <div class="modal fade" id="modalAsignatura" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAsignaturaTitle">Nueva Asignatura</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST" id="formAsignatura">
                    <input type="hidden" name="accion" id="accion" value="crear_asignatura">
                    <input type="hidden" name="id" id="asignatura_id">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Código de Asignatura <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="codigo_asignatura" id="codigo_asignatura" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nombre" id="nombre" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Semestre <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="semestre" id="semestre" min="1" max="10" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Carga Horaria <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="carga_horaria" id="carga_horaria" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Horario</label>
                                    <input type="text" class="form-control" name="horario" id="horario" placeholder="Ej: Lunes 8:00-10:00">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>% Horas Prácticas</label>
                                    <input type="number" class="form-control" name="porcentaje_horas_practicas" id="porcentaje_horas_practicas" 
                                           min="0" max="100" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>% Horas Teóricas</label>
                                    <input type="number" class="form-control" name="porcentaje_horas_teoricas" id="porcentaje_horas_teoricas" 
                                           min="0" max="100" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Ciclo</label>
                                    <select class="form-control" name="ciclo" id="ciclo">
                                        <option value="Básico">Básico</option>
                                        <option value="Intermedio">Intermedio</option>
                                        <option value="Avanzado">Avanzado</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Prerrequisitos</label>
                            <input type="text" class="form-control" name="prerequisitos" id="prerequisitos" 
                                   placeholder="Ej: MAT101, FIS101">
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

    <!-- Modal Gestión de Elementos -->
    <div class="modal fade" id="modalElementos" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-list"></i> Gestión de Elementos - <span id="elementosAsignaturaNombre"></span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="elementosCodigoAsignatura">
                    
                    <!-- Tabs para cada tipo de elemento -->
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#tab-objetivos">
                                <i class="fas fa-bullseye"></i> Objetivos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tab-contenidos">
                                <i class="fas fa-list"></i> Contenidos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tab-metodologicas">
                                <i class="fas fa-cogs"></i> E. Metodológicas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tab-evaluativas">
                                <i class="fas fa-clipboard-check"></i> E. Evaluativas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tab-recursos">
                                <i class="fas fa-tools"></i> Recursos
                            </a>
                        </li>
                    </ul>
                    
                    <!-- Tab content -->
                    <div class="tab-content mt-3">
                        <!-- Objetivos -->
                        <div class="tab-pane fade show active" id="tab-objetivos">
                            <div class="mb-3">
                                <form class="form-inline" onsubmit="agregarElemento(event, 'objetivos')">
                                    <input type="text" class="form-control mr-2 flex-fill" 
                                           placeholder="Nuevo objetivo..." id="nuevo-objetivos">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Agregar
                                    </button>
                                </form>
                            </div>
                            <div class="elementos-list" id="lista-objetivos">
                                <!-- Se carga dinámicamente -->
                            </div>
                        </div>
                        
                        <!-- Contenidos -->
                        <div class="tab-pane fade" id="tab-contenidos">
                            <div class="mb-3">
                                <form class="form-inline" onsubmit="agregarElemento(event, 'contenidos')">
                                    <input type="text" class="form-control mr-2 flex-fill" 
                                           placeholder="Nuevo contenido..." id="nuevo-contenidos">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Agregar
                                    </button>
                                </form>
                            </div>
                            <div class="elementos-list" id="lista-contenidos">
                                <!-- Se carga dinámicamente -->
                            </div>
                        </div>
                        
                        <!-- Estrategias Metodológicas -->
                        <div class="tab-pane fade" id="tab-metodologicas">
                            <div class="mb-3">
                                <form class="form-inline" onsubmit="agregarElemento(event, 'estrategias_metodologicas')">
                                    <input type="text" class="form-control mr-2 flex-fill" 
                                           placeholder="Nueva estrategia metodológica..." id="nuevo-estrategias_metodologicas">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Agregar
                                    </button>
                                </form>
                            </div>
                            <div class="elementos-list" id="lista-estrategias_metodologicas">
                                <!-- Se carga dinámicamente -->
                            </div>
                        </div>
                        
                        <!-- Estrategias Evaluativas -->
                        <div class="tab-pane fade" id="tab-evaluativas">
                            <div class="mb-3">
                                <form class="form-inline" onsubmit="agregarElemento(event, 'estrategias_evaluativas')">
                                    <input type="text" class="form-control mr-2 flex-fill" 
                                           placeholder="Nueva estrategia evaluativa..." id="nuevo-estrategias_evaluativas">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Agregar
                                    </button>
                                </form>
                            </div>
                            <div class="elementos-list" id="lista-estrategias_evaluativas">
                                <!-- Se carga dinámicamente -->
                            </div>
                        </div>
                        
                        <!-- Recursos -->
                        <div class="tab-pane fade" id="tab-recursos">
                            <div class="mb-3">
                                <form class="form-inline" onsubmit="agregarElemento(event, 'recursos')">
                                    <input type="text" class="form-control mr-2 flex-fill" 
                                           placeholder="Nuevo recurso..." id="nuevo-recursos">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Agregar
                                    </button>
                                </form>
                            </div>
                            <div class="elementos-list" id="lista-recursos">
                                <!-- Se carga dinámicamente -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
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

        // Reset form cuando se abre para nueva asignatura
        $('#modalAsignatura').on('show.bs.modal', function (e) {
            if (!$(e.relatedTarget).hasClass('btn-edit')) {
                $('#formAsignatura')[0].reset();
                $('#accion').val('crear_asignatura');
                $('#asignatura_id').val('');
                $('#modalAsignaturaTitle').text('Nueva Asignatura');
            }
        });

        function editarAsignatura(asignatura) {
            $('#modalAsignaturaTitle').text('Editar Asignatura');
            $('#accion').val('editar_asignatura');
            $('#asignatura_id').val(asignatura.id);
            $('#codigo_asignatura').val(asignatura.codigo_asignatura);
            $('#nombre').val(asignatura.nombre);
            $('#semestre').val(asignatura.semestre);
            $('#carga_horaria').val(asignatura.carga_horaria);
            $('#horario').val(asignatura.horario);
            $('#porcentaje_horas_practicas').val(asignatura.porcentaje_horas_practicas);
            $('#porcentaje_horas_teoricas').val(asignatura.porcentaje_horas_teoricas);
            $('#prerequisitos').val(asignatura.prerequisitos);
            $('#ciclo').val(asignatura.ciclo);
            $('#modalAsignatura').modal('show');
        }

        function gestionarElementos(codigoAsignatura, nombreAsignatura) {
            $('#elementosCodigoAsignatura').val(codigoAsignatura);
            $('#elementosAsignaturaNombre').text(codigoAsignatura + ' - ' + nombreAsignatura);
            
            // Cargar elementos de cada tipo
            cargarElementos('objetivos', codigoAsignatura);
            cargarElementos('contenidos', codigoAsignatura);
            cargarElementos('estrategias_metodologicas', codigoAsignatura);
            cargarElementos('estrategias_evaluativas', codigoAsignatura);
            cargarElementos('recursos', codigoAsignatura);
            
            $('#modalElementos').modal('show');
        }

        function cargarElementos(tipo, codigoAsignatura) {
            $.ajax({
                url: 'obtener_elementos.php',
                method: 'POST',
                data: {
                    tipo: tipo,
                    codigo_asignatura: codigoAsignatura
                },
                success: function(response) {
                    $('#lista-' + tipo).html(response);
                }
            });
        }

        function agregarElemento(event, tipo) {
            event.preventDefault();
            
            const descripcion = $('#nuevo-' + tipo).val().trim();
            const codigoAsignatura = $('#elementosCodigoAsignatura').val();
            
            if (!descripcion) return;
            
            $.ajax({
                url: 'asignaturas.php',
                method: 'POST',
                data: {
                    accion: 'agregar_elemento',
                    tipo_elemento: tipo,
                    codigo_asignatura: codigoAsignatura,
                    descripcion: descripcion
                },
                success: function() {
                    $('#nuevo-' + tipo).val('');
                    cargarElementos(tipo, codigoAsignatura);
                    mostrarToast('Elemento agregado correctamente', 'success');
                }
            });
        }

        function eliminarElemento(tipo, id) {
            if (!confirm('¿Está seguro de eliminar este elemento?')) return;
            
            const codigoAsignatura = $('#elementosCodigoAsignatura').val();
            
            $.ajax({
                url: 'asignaturas.php',
                method: 'POST',
                data: {
                    accion: 'eliminar_elemento',
                    tipo: tipo,
                    id: id
                },
                success: function() {
                    cargarElementos(tipo, codigoAsignatura);
                    mostrarToast('Elemento eliminado correctamente', 'success');
                }
            });
        }

        function mostrarToast(mensaje, tipo = 'info') {
            const alertClass = tipo === 'error' ? 'danger' : tipo;
            const toast = $(`
                <div class="alert alert-${alertClass} alert-dismissible fade show" 
                     style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
                    ${mensaje}
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            `);
            
            $('body').append(toast);
            setTimeout(() => toast.alert('close'), 3000);
        }
    </script>
</body>
</html>