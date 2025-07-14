<?php
require_once '../config/database.php';
verificarRol('coordinador');

$database = new Database();
$db = $database->getConnection();

// Obtener filtros
$filtro_docente = isset($_GET['docente']) ? $_GET['docente'] : '';
$filtro_asignatura = isset($_GET['asignatura']) ? $_GET['asignatura'] : '';
$filtro_fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$filtro_fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Construir consulta con filtros
$where_conditions = [];
$params = [];

if (!empty($filtro_docente)) {
    $where_conditions[] = "u.id = ?";
    $params[] = $filtro_docente;
}

if (!empty($filtro_asignatura)) {
    $where_conditions[] = "a.id = ?";
    $params[] = $filtro_asignatura;
}

if (!empty($filtro_fecha_inicio)) {
    $where_conditions[] = "pd.fecha_clase >= ?";
    $params[] = $filtro_fecha_inicio;
}

if (!empty($filtro_fecha_fin)) {
    $where_conditions[] = "pd.fecha_clase <= ?";
    $params[] = $filtro_fecha_fin;
}

if (!empty($filtro_estado)) {
    $where_conditions[] = "pd.estado = ?";
    $params[] = $filtro_estado;
}

$where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Obtener planes didácticos con filtros
$query = "SELECT pd.*, a.nombre as asignatura_nombre, a.codigo_asignatura, u.nombre_completo as docente_nombre
          FROM planes_didacticos pd 
          INNER JOIN asignaturas a ON pd.asignatura_id = a.id 
          INNER JOIN usuarios u ON pd.docente_id = u.id
          $where_sql
          ORDER BY pd.fecha_clase DESC, pd.id DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$planes = $stmt->fetchAll();

// Obtener listas para filtros
$query = "SELECT id, nombre_completo FROM usuarios WHERE rol = 'docente' AND estado = 'activo' ORDER BY nombre_completo";
$stmt = $db->prepare($query);
$stmt->execute();
$docentes = $stmt->fetchAll();

$query = "SELECT id, codigo_asignatura, nombre FROM asignaturas ORDER BY codigo_asignatura";
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
    <title>Control de Actividades - Sistema Académico</title>
    
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

            <li class="nav-item active">
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

            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseGestion">
                    <i class="fas fa-fw fa-cog"></i>
                    <span>Gestión</span>
                </a>
                <div id="collapseGestion" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Administración:</h6>
                        <a class="collapse-item" href="usuarios.php">Usuarios</a>
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
                        <h1 class="h3 mb-0 text-gray-800">Control de Actividades</h1>
                        <button class="btn btn-primary" data-toggle="collapse" data-target="#filtrosCollapse">
                            <i class="fas fa-filter"></i> Filtros
                        </button>
                    </div>

                    <!-- Filtros -->
                    <div class="collapse <?php echo !empty($_GET) ? 'show' : ''; ?>" id="filtrosCollapse">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Filtros de Búsqueda</h6>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Docente</label>
                                                <select class="form-control" name="docente">
                                                    <option value="">Todos los docentes</option>
                                                    <?php foreach ($docentes as $docente): ?>
                                                        <option value="<?php echo $docente['id']; ?>" 
                                                                <?php echo $filtro_docente == $docente['id'] ? 'selected' : ''; ?>>
                                                            <?php echo $docente['nombre_completo']; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Asignatura</label>
                                                <select class="form-control" name="asignatura">
                                                    <option value="">Todas las asignaturas</option>
                                                    <?php foreach ($asignaturas as $asignatura): ?>
                                                        <option value="<?php echo $asignatura['id']; ?>"
                                                                <?php echo $filtro_asignatura == $asignatura['id'] ? 'selected' : ''; ?>>
                                                            <?php echo $asignatura['codigo_asignatura'] . ' - ' . $asignatura['nombre']; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Fecha Inicio</label>
                                                <input type="date" class="form-control" name="fecha_inicio" 
                                                       value="<?php echo $filtro_fecha_inicio; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Fecha Fin</label>
                                                <input type="date" class="form-control" name="fecha_fin" 
                                                       value="<?php echo $filtro_fecha_fin; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Estado</label>
                                                <select class="form-control" name="estado">
                                                    <option value="">Todos los estados</option>
                                                    <option value="planificado" <?php echo $filtro_estado === 'planificado' ? 'selected' : ''; ?>>Planificado</option>
                                                    <option value="ejecutado" <?php echo $filtro_estado === 'ejecutado' ? 'selected' : ''; ?>>Ejecutado</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search"></i> Buscar
                                            </button>
                                            <a href="control_actividades.php" class="btn btn-secondary ml-2">
                                                <i class="fas fa-times"></i> Limpiar
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- DataTable -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">
                                Registro de Actividades de Clase 
                                <span class="badge badge-info"><?php echo count($planes); ?> registros</span>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Código Asignatura</th>
                                            <th>Asignatura</th>
                                            <th>Docente</th>
                                            <th>Fecha</th>
                                            <th>Tipo</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($planes as $plan): ?>
                                            <tr>
                                                <td><strong><?php echo $plan['codigo_asignatura']; ?></strong></td>
                                                <td><?php echo $plan['asignatura_nombre']; ?></td>
                                                <td><?php echo $plan['docente_nombre']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($plan['fecha_clase'])); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $plan['tipo_clase'] === 'regular' ? 'primary' : 'warning'; ?>">
                                                        <?php echo ucfirst($plan['tipo_clase']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo $plan['estado'] === 'ejecutado' ? 'success' : 'info'; ?>">
                                                        <?php echo ucfirst($plan['estado']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-info btn-sm" onclick="verDetalles(<?php echo $plan['id']; ?>)">
                                                        <i class="fas fa-eye"></i> Ver Detalles
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

    <!-- Modal Ver Detalles -->
    <div class="modal fade" id="modalDetalles" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles del Plan Didáctico</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="contenidoDetalles">
                    <!-- Contenido cargado dinámicamente -->
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
                "pageLength": 25,
                "order": [[ 3, "desc" ]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
                }
            });
        });

        function verDetalles(planId) {
            $.ajax({
                url: 'ver_detalles_plan.php',
                method: 'POST',
                data: { plan_id: planId },
                success: function(response) {
                    $('#contenidoDetalles').html(response);
                    $('#modalDetalles').modal('show');
                },
                error: function() {
                    alert('Error al cargar los detalles del plan');
                }
            });
        }
    </script>
</body>
</html>