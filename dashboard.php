<?php
require_once '../config/database.php';
verificarRol('docente');

$database = new Database();
$db = $database->getConnection();

// Obtener asignaturas del docente
$query = "SELECT DISTINCT a.* FROM asignaturas a ORDER BY a.codigo_asignatura";
$stmt = $db->prepare($query);
$stmt->execute();
$asignaturas = $stmt->fetchAll();

// Obtener planes del mes actual
$mes_actual = date('Y-m');
$query = "SELECT pd.*, a.nombre as asignatura_nombre, a.codigo_asignatura 
          FROM planes_didacticos pd 
          INNER JOIN asignaturas a ON pd.asignatura_id = a.id 
          WHERE pd.docente_id = ? AND DATE_FORMAT(pd.fecha_clase, '%Y-%m') = ?
          ORDER BY pd.fecha_clase";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['usuario_id'], $mes_actual]);
$planes_mes = $stmt->fetchAll();

// Obtener todas las clases del docente para el calendario (3 meses)
$fecha_inicio = date('Y-m-01');
$fecha_fin = date('Y-m-t', strtotime('+2 months'));

$query = "SELECT pd.*, a.nombre as asignatura_nombre, a.codigo_asignatura 
          FROM planes_didacticos pd 
          INNER JOIN asignaturas a ON pd.asignatura_id = a.id 
          WHERE pd.docente_id = ? AND pd.fecha_clase BETWEEN ? AND ?
          ORDER BY pd.fecha_clase";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['usuario_id'], $fecha_inicio, $fecha_fin]);
$todas_las_clases = $stmt->fetchAll();

$mensaje = obtenerMensaje();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Docente - Sistema Académico</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .calendario-container {
            background: white;
            border-radius: 0.175rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            padding: 1.5rem;
            border: 3px solid #d6dbdf ;
        }
        
        .calendario-grid {
            display: grid;
            grid-template-rows: auto 1fr;
            border: 1px solid #e3e6f0;
            border-radius: 8px;
            overflow: hidden;
            background: white;
        }
        
        .calendario-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            background: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .dia-header {
            padding: 12px 8px;
            text-align: center;
            font-weight: bold;
            color: #5a5c69;
            font-size: 14px;
            border-right: 1px solid #e3e6f0;
        }
        
        .dia-header:last-child {
            border-right: none;
        }
        
        .calendario-body {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            min-height: 400px;
        }
        
        .dia-celda {
            border-right: 1px solid #e3e6f0;
            border-bottom: 1px solid #e3e6f0;
            padding: 8px;
            min-height: 80px;
            cursor: pointer;
            transition: background-color 0.2s;
            position: relative;
        }
        
        .dia-celda:hover {
            background-color: #f8f9fc;
        }
        
        .dia-celda:last-child {
            border-right: none;
        }
        
        .dia-numero {
            font-weight: bold;
            color: #5a5c69;
            margin-bottom: 4px;
            font-size: 14px;
        }
        
        .dia-otro-mes {
            color: #d1d3e2;
            background-color: #f8f9fc;
        }
        
        .dia-hoy {
            background-color: #e3f2fd;
            border: 2px solid #4e73df;
        }
        
        .dia-hoy .dia-numero {
            color: #4e73df;
            font-weight: bold;
        }
        
        .evento-clase {
            font-size: 10px;
            padding: 2px 4px;
            border-radius: 3px;
            margin-bottom: 2px;
            color: white;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .evento-clase:hover {
            transform: scale(1.05);
        }
        
        .evento-planificado {
            background-color: #4e73df;
        }
        
        .evento-compensatoria {
            background-color: #f6c23e;
        }
        
        .evento-ejecutado {
            background-color: #1cc88a;
        }
        
        .leyenda-calendario {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        
        .leyenda-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
        }
        
        .leyenda-color {
            width: 15px;
            height: 15px;
            border-radius: 3px;
        }
        
        .fc-day-today {
            background-color: #f8f9fc !important;
        }
        
        .fc-daygrid-day:hover {
            background-color: #eaecf4;
            cursor: pointer;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Sistema<sup>Académico</sup></div>
            </a>

            <hr class="sidebar-divider my-0">

            <li class="nav-item active">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">Gestión de Clases</div>

            <li class="nav-item">
                <a class="nav-link" href="registro_actividades.php">
                    <i class="fas fa-fw fa-calendar-alt"></i>
                    <span>Registro de Actividades</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="asignaturas.php">
                    <i class="fas fa-fw fa-book"></i>
                    <span>Mis Asignaturas</span>
                </a>
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
                        <h1 class="h3 mb-0 text-gray-800">Dashboard Docente</h1>
                        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalPlanificar">
                            <i class="fas fa-plus"></i> Planificar Clase
                        </button>
                    </div>

                    <!-- Content Row -->
                    <div class="row">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Asignaturas</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($asignaturas); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-book fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Clases Este Mes</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($planes_mes); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Planificadas</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo count(array_filter($planes_mes, function($p) { return $p['estado'] === 'planificado'; })); ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Ejecutadas</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo count(array_filter($planes_mes, function($p) { return $p['estado'] === 'ejecutado'; })); ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Calendario Visual Estilo Grid -->
                    <div class="row">
                        <div class="col-12">
                            <div class="calendario-container">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">
                                        <i class="fas fa-calendar-alt text-primary"></i> Calendario de Clases
                                    </h5>
                                    <div class="d-flex align-items-center">
                                        <button class="btn btn-outline-primary btn-sm mr-2" onclick="cambiarMes(-1)">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                        <span id="mesActual" class="font-weight-bold mx-3"></span>
                                        <button class="btn btn-outline-primary btn-sm mr-3" onclick="cambiarMes(1)">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                        <button class="btn btn-primary btn-sm" onclick="irHoy()">
                                            <i class="fas fa-calendar-day"></i> Hoy
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Leyenda -->
                                <div class="leyenda-calendario">
                                    <div class="leyenda-item">
                                        <div class="leyenda-color" style="background-color: #4e73df;"></div>
                                        <span>Planificada</span>
                                    </div>
                                    <div class="leyenda-item">
                                        <div class="leyenda-color" style="background-color: #f6c23e;"></div>
                                        <span>Compensatoria</span>
                                    </div>
                                    <div class="leyenda-item">
                                        <div class="leyenda-color" style="background-color: #1cc88a;"></div>
                                        <span>Ejecutada</span>
                                    </div>
                                </div>
                                
                                <!-- Calendario Grid -->
                                <div class="calendario-grid">
                                    <!-- Encabezados de días -->
                                    <div class="calendario-header">
                                        <div class="dia-header">Dom</div>
                                        <div class="dia-header">Lun</div>
                                        <div class="dia-header">Mar</div>
                                        <div class="dia-header">Mié</div>
                                        <div class="dia-header">Jue</div>
                                        <div class="dia-header">Vie</div>
                                        <div class="dia-header">Sáb</div>
                                    </div>
                                    
                                    <!-- Días del calendario -->
                                    <div class="calendario-body" id="calendarioBody">
                                        <!-- Se genera dinámicamente con JavaScript -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información adicional -->
                    <div class="row mt-4">
                        <!-- Próximas clases -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Próximas Clases</h6>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Obtener próximas 5 clases
                                    $query = "SELECT pd.*, a.nombre as asignatura_nombre, a.codigo_asignatura 
                                              FROM planes_didacticos pd 
                                              INNER JOIN asignaturas a ON pd.asignatura_id = a.id 
                                              WHERE pd.docente_id = ? AND pd.fecha_clase >= CURDATE()
                                              ORDER BY pd.fecha_clase ASC 
                                              LIMIT 5";
                                    $stmt = $db->prepare($query);
                                    $stmt->execute([$_SESSION['usuario_id']]);
                                    $proximas_clases = $stmt->fetchAll();
                                    
                                    if (empty($proximas_clases)): ?>
                                        <p class="text-muted text-center">No hay clases programadas próximamente</p>
                                        <div class="text-center">
                                            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalPlanificar">
                                                <i class="fas fa-plus"></i> Planificar Primera Clase
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($proximas_clases as $clase): ?>
                                            <div class="media mb-3">
                                                <div class="media-object">
                                                    <i class="fas fa-calendar-alt fa-2x text-<?php echo $clase['tipo_clase'] === 'regular' ? 'primary' : 'warning'; ?>"></i>
                                                </div>
                                                <div class="media-body ml-3">
                                                    <h6 class="mt-0 mb-1">
                                                        <?php echo $clase['codigo_asignatura']; ?> - <?php echo $clase['asignatura_nombre']; ?>
                                                    </h6>
                                                    <p class="mb-1 text-sm">
                                                        <i class="fas fa-clock"></i> <?php echo date('d/m/Y', strtotime($clase['fecha_clase'])); ?>
                                                        <span class="badge badge-<?php echo $clase['tipo_clase'] === 'regular' ? 'primary' : 'warning'; ?> ml-2">
                                                            <?php echo ucfirst($clase['tipo_clase']); ?>
                                                        </span>
                                                    </p>
                                                    <p class="mb-0 text-sm text-muted">
                                                        Estado: <?php echo ucfirst($clase['estado']); ?>
                                                    </p>
                                                    <?php if ($clase['estado'] === 'planificado'): ?>
                                                        <a href="registro_actividades.php?fecha=<?php echo $clase['fecha_clase']; ?>&plan_id=<?php echo $clase['id']; ?>" 
                                                           class="btn btn-sm btn-success mt-2">
                                                            <i class="fas fa-play"></i> Registrar Actividad
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Resumen de actividades recientes -->
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-success">Actividades Recientes</h6>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Obtener últimas 5 clases ejecutadas
                                    $query = "SELECT pd.*, a.nombre as asignatura_nombre, a.codigo_asignatura 
                                              FROM planes_didacticos pd 
                                              INNER JOIN asignaturas a ON pd.asignatura_id = a.id 
                                              WHERE pd.docente_id = ? AND pd.estado = 'ejecutado'
                                              ORDER BY pd.fecha_clase DESC 
                                              LIMIT 5";
                                    $stmt = $db->prepare($query);
                                    $stmt->execute([$_SESSION['usuario_id']]);
                                    $actividades_recientes = $stmt->fetchAll();
                                    
                                    if (empty($actividades_recientes)): ?>
                                        <p class="text-muted text-center">Aún no hay actividades registradas</p>
                                        <div class="text-center">
                                            <small class="text-muted">Las clases ejecutadas aparecerán aquí</small>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($actividades_recientes as $actividad): ?>
                                            <div class="media mb-3">
                                                <div class="media-object">
                                                    <i class="fas fa-check-circle fa-2x text-success"></i>
                                                </div>
                                                <div class="media-body ml-3">
                                                    <h6 class="mt-0 mb-1">
                                                        <?php echo $actividad['codigo_asignatura']; ?> - <?php echo $actividad['asignatura_nombre']; ?>
                                                    </h6>
                                                    <p class="mb-1 text-sm">
                                                        <i class="fas fa-calendar-check"></i> <?php echo date('d/m/Y', strtotime($actividad['fecha_clase'])); ?>
                                                    </p>
                                                    <p class="mb-0 text-sm text-success">
                                                        <i class="fas fa-check"></i> Clase ejecutada
                                                    </p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        <div class="text-center mt-3">
                                            <a href="historial_actividades.php" class="btn btn-outline-success btn-sm">
                                                <i class="fas fa-history"></i> Ver Historial Completo
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
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

    <!-- Modal Planificar Clase Mejorado -->
    <div class="modal fade" id="modalPlanificar" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-calendar-plus"></i> Planificar Nueva Clase
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="formPlanificar" method="POST" action="procesar_planificacion.php">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-calendar"></i> Fecha de Clase</label>
                                    <input type="date" class="form-control" name="fecha_clase" id="fecha_clase" required>
                                    <small class="form-text text-muted">Selecciona la fecha para la clase</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-tag"></i> Tipo de Clase</label>
                                    <select class="form-control" name="tipo_clase" required>
                                        <option value="regular">Clase Regular</option>
                                        <option value="compensatoria">Clase Compensatoria</option>
                                    </select>
                                    <small class="form-text text-muted">Las clases compensatorias son para recuperar contenidos</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-code"></i> Código de Asignatura</label>
                                    <select class="form-control" name="codigo_asignatura" id="codigo_asignatura_select" required>
                                        <option value="">Seleccionar código...</option>
                                        <?php foreach ($asignaturas as $asignatura): ?>
                                            <option value="<?php echo $asignatura['codigo_asignatura']; ?>" 
                                                    data-id="<?php echo $asignatura['id']; ?>"
                                                    data-nombre="<?php echo htmlspecialchars($asignatura['nombre']); ?>">
                                                <?php echo $asignatura['codigo_asignatura']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">Elige el código de la asignatura</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-book"></i> Nombre de Asignatura</label>
                                    <input type="text" class="form-control" id="nombre_asignatura_display" 
                                           placeholder="Se completará automáticamente" readonly>
                                    <input type="hidden" name="asignatura_id" id="asignatura_id_hidden">
                                    <small class="form-text text-muted">Se completa automáticamente al seleccionar el código</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Información adicional -->
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Nota:</strong> Después de crear la planificación, podrás configurar los objetivos, 
                            contenidos, estrategias metodológicas y recursos específicos para esta clase.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-arrow-right"></i> Continuar con Configuración
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Clase -->
    <div class="modal fade" id="modalConfirmarClase" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-clipboard-check"></i> Registrar Actividades de Clase
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-info-circle"></i> Información de la Clase
                            </h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong><i class="fas fa-calendar"></i> Fecha:</strong></td>
                                    <td id="modalClaseFecha">-</td>
                                </tr>
                                <tr>
                                    <td><strong><i class="fas fa-code"></i> Código:</strong></td>
                                    <td id="modalClaseCodigo">-</td>
                                </tr>
                                <tr>
                                    <td><strong><i class="fas fa-book"></i> Asignatura:</strong></td>
                                    <td id="modalClaseNombre">-</td>
                                </tr>
                                <tr>
                                    <td><strong><i class="fas fa-tag"></i> Tipo:</strong></td>
                                    <td id="modalClaseTipo">-</td>
                                </tr>
                                <tr>
                                    <td><strong><i class="fas fa-info-circle"></i> Estado:</strong></td>
                                    <td id="modalClaseEstado">-</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4 text-center">
                            <i class="fas fa-clipboard-check fa-4x text-info mb-3"></i>
                            <p class="text-muted">
                                Podrás marcar el progreso de objetivos, contenidos y estrategias.
                            </p>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-lightbulb"></i>
                        <strong>¿Qué harás?</strong> Irás a la página de registro donde podrás marcar:
                        <ul class="mb-0 mt-2">
                            <li>Estado de objetivos (Logrado/No Logrado)</li>
                            <li>Progreso de contenidos (Terminado/Sin Concluir)</li>
                            <li>Estrategias metodológicas y evaluativas aplicadas</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <a href="#" class="btn btn-success" id="btnIrRegistrar">
                        <i class="fas fa-arrow-right"></i> Ir a Registrar Actividades
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/js/sb-admin-2.min.js"></script>

    <script>
        // Variables globales para el calendario
        let fechaActual = new Date();
        let clasesData = <?php echo json_encode($todas_las_clases); ?>;
        
        // Nombres de meses en español
        const meses = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];

        function generarCalendario() {
            const year = fechaActual.getFullYear();
            const month = fechaActual.getMonth();
            
            // Actualizar título del mes
            $('#mesActual').text(meses[month] + ' ' + year);
            
            // Primer día del mes y último día del mes
            const primerDia = new Date(year, month, 1);
            const ultimoDia = new Date(year, month + 1, 0);
            
            // Día de la semana del primer día (0 = domingo)
            const primerDiaSemana = primerDia.getDay();
            
            // Días del mes anterior para completar la primera semana
            const mesAnterior = new Date(year, month, 0);
            const diasMesAnterior = mesAnterior.getDate();
            
            let html = '';
            let diaContador = 1;
            let proximoMesDia = 1;
            
            // Generar 6 semanas (42 días)
            for (let semana = 0; semana < 6; semana++) {
                for (let dia = 0; dia < 7; dia++) {
                    const diaNumero = semana * 7 + dia;
                    let fechaCelda, numeroDia, esOtroMes = false, esHoy = false;
                    
                    if (diaNumero < primerDiaSemana) {
                        // Días del mes anterior
                        numeroDia = diasMesAnterior - primerDiaSemana + diaNumero + 1;
                        fechaCelda = new Date(year, month - 1, numeroDia);
                        esOtroMes = true;
                    } else if (diaContador <= ultimoDia.getDate()) {
                        // Días del mes actual
                        numeroDia = diaContador;
                        fechaCelda = new Date(year, month, diaContador);
                        diaContador++;
                        
                        // Verificar si es hoy
                        const hoy = new Date();
                        if (fechaCelda.toDateString() === hoy.toDateString()) {
                            esHoy = true;
                        }
                    } else {
                        // Días del próximo mes
                        numeroDia = proximoMesDia;
                        fechaCelda = new Date(year, month + 1, proximoMesDia);
                        proximoMesDia++;
                        esOtroMes = true;
                    }
                    
                    // Formatear fecha para buscar clases
                    const fechaStr = fechaCelda.toISOString().split('T')[0];
                    const clasesDelDia = clasesData.filter(clase => clase.fecha_clase === fechaStr);
                    
                    // Clases CSS para la celda
                    let clasesCSS = 'dia-celda';
                    if (esOtroMes) clasesCSS += ' dia-otro-mes';
                    if (esHoy) clasesCSS += ' dia-hoy';
                    
                    html += `<div class="${clasesCSS}" onclick="clickDia('${fechaStr}')">`;
                    html += `<div class="dia-numero">${numeroDia}</div>`;
                    
                    // Agregar eventos del día
                    clasesDelDia.forEach(clase => {
                        let colorEvento = 'evento-planificado';
                        if (clase.estado === 'ejecutado') {
                            colorEvento = 'evento-ejecutado';
                        } else if (clase.tipo_clase === 'compensatoria') {
                            colorEvento = 'evento-compensatoria';
                        }
                        
                        html += `<div class="evento-clase ${colorEvento}" onclick="clickClase('${fechaStr}', ${clase.id}, event)" title="${clase.codigo_asignatura} - ${clase.estado}">`;
                        html += `${clase.codigo_asignatura}`;
                        html += `</div>`;
                    });
                    
                    html += '</div>';
                }
            }
            
            $('#calendarioBody').html(html);
        }

        function cambiarMes(direccion) {
            fechaActual.setMonth(fechaActual.getMonth() + direccion);
            generarCalendario();
        }

        function irHoy() {
            fechaActual = new Date();
            generarCalendario();
        }

        function clickDia(fecha) {
            // Si no hay clases, abrir modal para planificar
            const clasesDelDia = clasesData.filter(clase => clase.fecha_clase === fecha);
            if (clasesDelDia.length === 0) {
                $('#fecha_clase').val(fecha);
                $('#modalPlanificar').modal('show');
            }
        }

        function clickClase(fecha, planId, event) {
            event.stopPropagation();
            
            // Buscar información de la clase
            const clase = clasesData.find(c => c.id == planId);
            if (!clase) return;
            
            // Llenar el modal con información de la clase
            $('#modalClaseFecha').text(fecha);
            $('#modalClaseCodigo').text(clase.codigo_asignatura);
            $('#modalClaseNombre').text(clase.asignatura_nombre);
            $('#modalClaseEstado').text(clase.estado);
            $('#modalClaseTipo').text(clase.tipo_clase);
            
            // Configurar el enlace del botón
            $('#btnIrRegistrar').attr('href', `registro_actividades.php?fecha=${fecha}&plan_id=${planId}`);
            
            // Mostrar el modal
            $('#modalConfirmarClase').modal('show');
        }

        // Validación del formulario
        $('#formPlanificar').on('submit', function(e) {
            var fecha = $('#fecha_clase').val();
            var asignatura_id = $('#asignatura_id_hidden').val();
            
            if (!fecha || !asignatura_id) {
                e.preventDefault();
                alert('Por favor complete todos los campos obligatorios');
                return false;
            }
            
            // Verificar que la fecha no sea anterior a hoy
            var today = new Date().toISOString().split('T')[0];
            if (fecha < today) {
                e.preventDefault();
                alert('No se puede planificar clases en fechas pasadas');
                return false;
            }
        });

        // Autocompletar nombre de asignatura al seleccionar código
        $('#codigo_asignatura_select').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            var nombre = selectedOption.data('nombre');
            var asignaturaId = selectedOption.data('id');
            
            if (nombre && asignaturaId) {
                $('#nombre_asignatura_display').val(nombre);
                $('#asignatura_id_hidden').val(asignaturaId);
                
                // Efecto visual de confirmación
                $('#nombre_asignatura_display').addClass('is-valid');
                setTimeout(() => {
                    $('#nombre_asignatura_display').removeClass('is-valid');
                }, 2000);
            } else {
                $('#nombre_asignatura_display').val('');
                $('#asignatura_id_hidden').val('');
            }
        });

        // Inicializar calendario al cargar la página
        $(document).ready(function() {
            generarCalendario();
            
            // Configurar fecha mínima (hoy)
            var today = new Date().toISOString().split('T')[0];
            $('#fecha_clase').attr('min', today);
        });
    </script>
</body>
</html>