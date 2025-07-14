<?php
require_once '../config/database.php';
verificarRol('docente');

$database = new Database();
$db = $database->getConnection();

$plan_id = (int)$_GET['plan_id'];

// Obtener información del plan
$query = "SELECT pd.*, a.nombre as asignatura_nombre, a.codigo_asignatura, a.id as asignatura_id
          FROM planes_didacticos pd 
          INNER JOIN asignaturas a ON pd.asignatura_id = a.id 
          WHERE pd.id = ? AND pd.docente_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$plan_id, $_SESSION['usuario_id']]);
$plan = $stmt->fetch();

if (!$plan) {
    mostrarMensaje('error', 'Plan no encontrado');
    header('Location: dashboard.php');
    exit();
}

// Obtener código de asignatura para las consultas
$codigo_asignatura = $plan['codigo_asignatura'];

// Obtener elementos disponibles para la asignatura
$query = "SELECT * FROM objetivos_clase WHERE codigo_asignatura = ? ORDER BY descripcion";
$stmt = $db->prepare($query);
$stmt->execute([$codigo_asignatura]);
$objetivos = $stmt->fetchAll();

$query = "SELECT * FROM contenidos WHERE codigo_asignatura = ? ORDER BY descripcion";
$stmt = $db->prepare($query);
$stmt->execute([$codigo_asignatura]);
$contenidos = $stmt->fetchAll();

$query = "SELECT * FROM estrategias_metodologicas WHERE codigo_asignatura = ? ORDER BY descripcion";
$stmt = $db->prepare($query);
$stmt->execute([$codigo_asignatura]);
$estrategias_metodologicas = $stmt->fetchAll();

$query = "SELECT * FROM estrategias_evaluativas WHERE codigo_asignatura = ? ORDER BY descripcion";
$stmt = $db->prepare($query);
$stmt->execute([$codigo_asignatura]);
$estrategias_evaluativas = $stmt->fetchAll();

$query = "SELECT * FROM recursos WHERE codigo_asignatura = ? ORDER BY descripcion";
$stmt = $db->prepare($query);
$stmt->execute([$codigo_asignatura]);
$recursos = $stmt->fetchAll();

// Verificar si ya hay elementos guardados
$query = "SELECT COUNT(*) as total FROM (
    SELECT plan_id FROM plan_objetivos WHERE plan_id = ?
    UNION SELECT plan_id FROM plan_contenidos WHERE plan_id = ?
    UNION SELECT plan_id FROM plan_estrategias_metodologicas WHERE plan_id = ?
    UNION SELECT plan_id FROM plan_estrategias_evaluativas WHERE plan_id = ?
    UNION SELECT plan_id FROM plan_recursos WHERE plan_id = ?
) as elementos";
$stmt = $db->prepare($query);
$stmt->execute([$plan_id, $plan_id, $plan_id, $plan_id, $plan_id]);
$hay_elementos_guardados = $stmt->fetch()['total'] > 0;

// Obtener elementos ya seleccionados
$objetivos_seleccionados = [];
$contenidos_seleccionados = [];
$estrategias_met_seleccionadas = [];
$estrategias_eval_seleccionadas = [];
$recursos_seleccionados = [];

if ($hay_elementos_guardados) {
    $query = "SELECT objetivo_id FROM plan_objetivos WHERE plan_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$plan_id]);
    $objetivos_seleccionados = array_column($stmt->fetchAll(), 'objetivo_id');

    $query = "SELECT contenido_id FROM plan_contenidos WHERE plan_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$plan_id]);
    $contenidos_seleccionados = array_column($stmt->fetchAll(), 'contenido_id');

    $query = "SELECT estrategia_id FROM plan_estrategias_metodologicas WHERE plan_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$plan_id]);
    $estrategias_met_seleccionadas = array_column($stmt->fetchAll(), 'estrategia_id');

    $query = "SELECT estrategia_id FROM plan_estrategias_evaluativas WHERE plan_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$plan_id]);
    $estrategias_eval_seleccionadas = array_column($stmt->fetchAll(), 'estrategia_id');

    $query = "SELECT recurso_id FROM plan_recursos WHERE plan_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$plan_id]);
    $recursos_seleccionados = array_column($stmt->fetchAll(), 'recurso_id');
} else {
    // Si no hay elementos guardados, seleccionar todos por defecto
    $objetivos_seleccionados = array_column($objetivos, 'id');
    $contenidos_seleccionados = array_column($contenidos, 'id');
    $estrategias_met_seleccionadas = array_column($estrategias_metodologicas, 'id');
    $estrategias_eval_seleccionadas = array_column($estrategias_evaluativas, 'id');
    $recursos_seleccionados = array_column($recursos, 'id');
}

$mensaje = obtenerMensaje();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configurar Plan Didáctico - Sistema Académico</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .seleccion-card {
            border: 2px solid #e3e6f0;
            border-radius: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .seleccion-card:hover {
            border-color: #4e73df;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .seleccion-card.selected {
            border-color: #1cc88a;
            background-color: #f8fff8;
        }
        
        .elemento-seleccionado {
            background-color: #f8f9fc;
            border: 1px solid #e3e6f0;
            padding: 8px 12px;
            margin: 3px;
            border-radius: 15px;
            display: inline-block;
            font-size: 12px;
        }
        
        .contador-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #1cc88a;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        
        .lista-elementos {
            max-height: 200px;
            overflow-y: auto;
            padding: 10px;
            background-color: #f8f9fc;
            border-radius: 5px;
            margin-top: 10px;
        }
        
        .elemento-check {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .elemento-check:hover {
            background-color: #f8f9fa !important;
            border-color: #007bff !important;
        }
        
        .elemento-check.border-success {
            animation: pulseSuccess 0.3s ease-in-out;
        }
        
        @keyframes pulseSuccess {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
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

            <li class="nav-item">
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
                        <div>
                            <h1 class="h3 mb-0 text-gray-800">Configurar Plan Didáctico</h1>
                            <p class="mb-0 text-gray-600">
                                <strong><?php echo $plan['codigo_asignatura']; ?> - <?php echo $plan['asignatura_nombre']; ?></strong><br>
                                Fecha: <?php echo date('d/m/Y', strtotime($plan['fecha_clase'])); ?> | 
                                Tipo: <?php echo ucfirst($plan['tipo_clase']); ?>
                            </p>
                        </div>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>

                    <!-- Alerta si no hay elementos -->
                    <?php if (empty($objetivos) && empty($contenidos) && empty($estrategias_metodologicas) && empty($estrategias_evaluativas) && empty($recursos)): ?>
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-exclamation-triangle"></i> No hay elementos disponibles</h5>
                            <p>No se encontraron elementos para la asignatura <strong><?php echo $codigo_asignatura; ?></strong>.</p>
                            <p>Contacta al coordinador para que configure los elementos de esta asignatura.</p>
                        </div>
                    <?php else: ?>

                    <!-- Instrucciones -->
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Instrucciones</h5>
                        <p class="mb-0">Por defecto, todos los elementos están seleccionados. Puedes desmarcar los que no necesites para esta clase específica.</p>
                    </div>

                    <!-- Formulario principal -->
                    <form method="POST" action="guardar_plan.php" id="formGuardarPlan">
                        <input type="hidden" name="plan_id" value="<?php echo $plan_id; ?>">
                        
                        <!-- Tarjetas de Selección -->
                        <div class="row">
                            <!-- Objetivos de la Clase -->
                            <?php if (!empty($objetivos)): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card seleccion-card h-100 <?php echo !empty($objetivos_seleccionados) ? 'selected' : ''; ?>" data-tipo="objetivos">
                                    <div class="card-header bg-primary text-white position-relative">
                                        <h6 class="m-0 font-weight-bold">
                                            <i class="fas fa-bullseye"></i> Objetivos de la Clase
                                        </h6>
                                        <span class="contador-badge" id="contador-objetivos"><?php echo count($objetivos_seleccionados); ?></span>
                                    </div>
                                    <div class="card-body text-center">
                                        <i class="fas fa-bullseye fa-3x text-primary mb-3"></i>
                                        <p class="card-text">
                                            <strong><?php echo count($objetivos); ?></strong> objetivos disponibles
                                        </p>
                                        <button type="button" class="btn btn-primary" onclick="abrirSeleccion('objetivos')">
                                            <i class="fas fa-edit"></i> Modificar Selección
                                        </button>
                                        <div class="lista-elementos" id="lista-objetivos" style="display: <?php echo !empty($objetivos_seleccionados) ? 'block' : 'none'; ?>;">
                                            <?php foreach ($objetivos as $objetivo): ?>
                                                <?php if (in_array($objetivo['id'], $objetivos_seleccionados)): ?>
                                                    <span class="elemento-seleccionado"><?php echo $objetivo['descripcion']; ?></span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Contenidos -->
                            <?php if (!empty($contenidos)): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card seleccion-card h-100 <?php echo !empty($contenidos_seleccionados) ? 'selected' : ''; ?>" data-tipo="contenidos">
                                    <div class="card-header bg-success text-white position-relative">
                                        <h6 class="m-0 font-weight-bold">
                                            <i class="fas fa-list"></i> Contenidos
                                        </h6>
                                        <span class="contador-badge" id="contador-contenidos"><?php echo count($contenidos_seleccionados); ?></span>
                                    </div>
                                    <div class="card-body text-center">
                                        <i class="fas fa-list fa-3x text-success mb-3"></i>
                                        <p class="card-text">
                                            <strong><?php echo count($contenidos); ?></strong> contenidos disponibles
                                        </p>
                                        <button type="button" class="btn btn-success" onclick="abrirSeleccion('contenidos')">
                                            <i class="fas fa-edit"></i> Modificar Selección
                                        </button>
                                        <div class="lista-elementos" id="lista-contenidos" style="display: <?php echo !empty($contenidos_seleccionados) ? 'block' : 'none'; ?>;">
                                            <?php foreach ($contenidos as $contenido): ?>
                                                <?php if (in_array($contenido['id'], $contenidos_seleccionados)): ?>
                                                    <span class="elemento-seleccionado"><?php echo $contenido['descripcion']; ?></span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Estrategias Metodológicas -->
                            <?php if (!empty($estrategias_metodologicas)): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card seleccion-card h-100 <?php echo !empty($estrategias_met_seleccionadas) ? 'selected' : ''; ?>" data-tipo="estrategias_metodologicas">
                                    <div class="card-header bg-info text-white position-relative">
                                        <h6 class="m-0 font-weight-bold">
                                            <i class="fas fa-cogs"></i> Estrategias Metodológicas
                                        </h6>
                                        <span class="contador-badge" id="contador-estrategias_metodologicas"><?php echo count($estrategias_met_seleccionadas); ?></span>
                                    </div>
                                    <div class="card-body text-center">
                                        <i class="fas fa-cogs fa-3x text-info mb-3"></i>
                                        <p class="card-text">
                                            <strong><?php echo count($estrategias_metodologicas); ?></strong> estrategias disponibles
                                        </p>
                                        <button type="button" class="btn btn-info" onclick="abrirSeleccion('estrategias_metodologicas')">
                                            <i class="fas fa-edit"></i> Modificar Selección
                                        </button>
                                        <div class="lista-elementos" id="lista-estrategias_metodologicas" style="display: <?php echo !empty($estrategias_met_seleccionadas) ? 'block' : 'none'; ?>;">
                                            <?php foreach ($estrategias_metodologicas as $estrategia): ?>
                                                <?php if (in_array($estrategia['id'], $estrategias_met_seleccionadas)): ?>
                                                    <span class="elemento-seleccionado"><?php echo $estrategia['descripcion']; ?></span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Estrategias Evaluativas -->
                            <?php if (!empty($estrategias_evaluativas)): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card seleccion-card h-100 <?php echo !empty($estrategias_eval_seleccionadas) ? 'selected' : ''; ?>" data-tipo="estrategias_evaluativas">
                                    <div class="card-header bg-warning text-white position-relative">
                                        <h6 class="m-0 font-weight-bold">
                                            <i class="fas fa-clipboard-check"></i> Estrategias Evaluativas
                                        </h6>
                                        <span class="contador-badge" id="contador-estrategias_evaluativas"><?php echo count($estrategias_eval_seleccionadas); ?></span>
                                    </div>
                                    <div class="card-body text-center">
                                        <i class="fas fa-clipboard-check fa-3x text-warning mb-3"></i>
                                        <p class="card-text">
                                            <strong><?php echo count($estrategias_evaluativas); ?></strong> estrategias disponibles
                                        </p>
                                        <button type="button" class="btn btn-warning" onclick="abrirSeleccion('estrategias_evaluativas')">
                                            <i class="fas fa-edit"></i> Modificar Selección
                                        </button>
                                        <div class="lista-elementos" id="lista-estrategias_evaluativas" style="display: <?php echo !empty($estrategias_eval_seleccionadas) ? 'block' : 'none'; ?>;">
                                            <?php foreach ($estrategias_evaluativas as $estrategia): ?>
                                                <?php if (in_array($estrategia['id'], $estrategias_eval_seleccionadas)): ?>
                                                    <span class="elemento-seleccionado"><?php echo $estrategia['descripcion']; ?></span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Recursos -->
                            <?php if (!empty($recursos)): ?>
                            <div class="col-md-12 mb-4">
                                <div class="card seleccion-card <?php echo !empty($recursos_seleccionados) ? 'selected' : ''; ?>" data-tipo="recursos">
                                    <div class="card-header bg-secondary text-white position-relative">
                                        <h6 class="m-0 font-weight-bold">
                                            <i class="fas fa-tools"></i> Recursos
                                        </h6>
                                        <span class="contador-badge" id="contador-recursos"><?php echo count($recursos_seleccionados); ?></span>
                                    </div>
                                    <div class="card-body text-center">
                                        <i class="fas fa-tools fa-3x text-secondary mb-3"></i>
                                        <p class="card-text">
                                            <strong><?php echo count($recursos); ?></strong> recursos disponibles
                                        </p>
                                        <button type="button" class="btn btn-secondary" onclick="abrirSeleccion('recursos')">
                                            <i class="fas fa-edit"></i> Modificar Selección
                                        </button>
                                        <div class="lista-elementos" id="lista-recursos" style="display: <?php echo !empty($recursos_seleccionados) ? 'block' : 'none'; ?>;">
                                            <?php foreach ($recursos as $recurso): ?>
                                                <?php if (in_array($recurso['id'], $recursos_seleccionados)): ?>
                                                    <span class="elemento-seleccionado"><?php echo $recurso['descripcion']; ?></span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Hidden inputs para los elementos seleccionados -->
                        <div id="hidden-inputs-container">
                            <?php foreach ($objetivos_seleccionados as $id): ?>
                                <input type="hidden" name="objetivos[]" value="<?php echo $id; ?>">
                            <?php endforeach; ?>
                            <?php foreach ($contenidos_seleccionados as $id): ?>
                                <input type="hidden" name="contenidos[]" value="<?php echo $id; ?>">
                            <?php endforeach; ?>
                            <?php foreach ($estrategias_met_seleccionadas as $id): ?>
                                <input type="hidden" name="estrategias_metodologicas[]" value="<?php echo $id; ?>">
                            <?php endforeach; ?>
                            <?php foreach ($estrategias_eval_seleccionadas as $id): ?>
                                <input type="hidden" name="estrategias_evaluativas[]" value="<?php echo $id; ?>">
                            <?php endforeach; ?>
                            <?php foreach ($recursos_seleccionados as $id): ?>
                                <input type="hidden" name="recursos[]" value="<?php echo $id; ?>">
                            <?php endforeach; ?>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card shadow border-left-success">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="fas fa-eye fa-2x text-info mb-2"></i>
                                            <h5 class="text-info">Vista Previa y Guardado</h5>
                                            <p class="text-muted">Revisa tu selección antes de guardar el plan didáctico</p>
                                        </div>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-info btn-lg" onclick="mostrarVistaPrevia()">
                                                <i class="fas fa-eye"></i> Vista Previa
                                            </button>
                                            <button type="button" class="btn btn-success btn-lg" id="btnGuardar" onclick="mostrarVistaPrevia()">
                                                <i class="fas fa-save"></i> Guardar Plan Didáctico
                                            </button>
                                            <a href="dashboard.php" class="btn btn-secondary btn-lg">
                                                <i class="fas fa-times"></i> Cancelar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <?php endif; ?>
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

    <!-- Modal de Selección -->
    <div class="modal fade" id="modalSeleccion" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-list-check"></i> Seleccionar Elementos
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-success" onclick="marcarTodos(true)">
                                <i class="fas fa-check-square"></i> Marcar Todos
                            </button>
                            <button type="button" class="btn btn-danger" onclick="marcarTodos(false)">
                                <i class="fas fa-square"></i> Desmarcar Todos
                            </button>
                        </div>
                        <hr>
                    </div>
                    <div id="modalContent" style="max-height: 400px; overflow-y: auto;">
                        <!-- Contenido dinámico -->
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            Selecciona los elementos que utilizarás en esta clase específica
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="mr-auto">
                        <span class="badge badge-info" id="contadorSeleccionados">0 seleccionados</span>
                    </div>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="confirmarSeleccion()">
                        <i class="fas fa-check"></i> Confirmar Selección
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/js/sb-admin-2.min.js"></script>

    <script>
        let tipoActual = '';
        let elementosDisponibles = {
            objetivos: <?php echo json_encode($objetivos); ?>,
            contenidos: <?php echo json_encode($contenidos); ?>,
            estrategias_metodologicas: <?php echo json_encode($estrategias_metodologicas); ?>,
            estrategias_evaluativas: <?php echo json_encode($estrategias_evaluativas); ?>,
            recursos: <?php echo json_encode($recursos); ?>
        };
        
        let elementosSeleccionados = {
            objetivos: <?php echo json_encode($objetivos_seleccionados); ?>,
            contenidos: <?php echo json_encode($contenidos_seleccionados); ?>,
            estrategias_metodologicas: <?php echo json_encode($estrategias_met_seleccionadas); ?>,
            estrategias_evaluativas: <?php echo json_encode($estrategias_eval_seleccionadas); ?>,
            recursos: <?php echo json_encode($recursos_seleccionados); ?>
        };

        const configuracionTipos = {
            objetivos: {
                titulo: 'Seleccionar Objetivos de la Clase',
                icono: 'fas fa-bullseye',
                color: 'primary',
                descripcion: 'Selecciona los objetivos que se trabajarán en esta clase específica'
            },
            contenidos: {
                titulo: 'Seleccionar Contenidos',
                icono: 'fas fa-list',
                color: 'success',
                descripcion: 'Elige los contenidos que se desarrollarán durante la clase'
            },
            estrategias_metodologicas: {
                titulo: 'Seleccionar Estrategias Metodológicas',
                icono: 'fas fa-cogs',
                color: 'info',
                descripcion: 'Define las metodologías que aplicarás en la clase'
            },
            estrategias_evaluativas: {
                titulo: 'Seleccionar Estrategias Evaluativas',
                icono: 'fas fa-clipboard-check',
                color: 'warning',
                descripcion: 'Establece cómo evaluarás el progreso de los estudiantes'
            },
            recursos: {
                titulo: 'Seleccionar Recursos',
                icono: 'fas fa-tools',
                color: 'secondary',
                descripcion: 'Determina qué recursos necesitarás para la clase'
            }
        };

        function abrirSeleccion(tipo) {
            tipoActual = tipo;
            const config = configuracionTipos[tipo];
            
            // Configurar el modal
            $('#modalTitle').html(`<i class="${config.icono}"></i> ${config.titulo}`);
            $('.modal-header').removeClass().addClass(`modal-header bg-${config.color} text-white`);
            
            // Generar contenido del modal
            let contenido = `
                <div class="alert alert-${config.color} alert-sm">
                    <i class="fas fa-info-circle"></i> ${config.descripcion}
                </div>
            `;
            
            if (elementosDisponibles[tipo].length === 0) {
                contenido += `
                    <div class="text-center py-4">
                        <i class="${config.icono} fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay elementos disponibles</h5>
                        <p class="text-muted">No se han configurado ${tipo} para esta asignatura.</p>
                    </div>
                `;
            } else {
                elementosDisponibles[tipo].forEach(function(elemento) {
                    let checked = elementosSeleccionados[tipo].includes(elemento.id) ? 'checked' : '';
                    contenido += `
                        <div class="form-check mb-3 p-3 border rounded elemento-check ${checked ? 'border-success bg-light' : ''}" data-id="${elemento.id}">
                            <input class="form-check-input elemento-checkbox" type="checkbox" 
                                   value="${elemento.id}" id="${tipo}_${elemento.id}" ${checked}>
                            <label class="form-check-label w-100" for="${tipo}_${elemento.id}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <strong>${elemento.descripcion}</strong>
                                    </div>
                                    <div class="ml-2">
                                        <i class="fas fa-check text-success check-icon" style="display: ${checked ? 'inline' : 'none'}"></i>
                                    </div>
                                </div>
                            </label>
                        </div>
                    `;
                });
            }
            
            $('#modalContent').html(contenido);
            actualizarContador();
            configurarEventosModal();
            $('#modalSeleccion').modal('show');
        }

        function configurarEventosModal() {
            // Eventos para checkboxes individuales
            $('.elemento-checkbox').off('change').on('change', function() {
                const $elemento = $(this).closest('.elemento-check');
                const $icon = $elemento.find('.check-icon');
                
                if ($(this).is(':checked')) {
                    $elemento.addClass('border-success bg-light');
                    $icon.show();
                } else {
                    $elemento.removeClass('border-success bg-light');
                    $icon.hide();
                }
                
                actualizarContador();
            });

            // Hacer clickeable todo el div
            $('.elemento-check').off('click').on('click', function(e) {
                if (e.target.type !== 'checkbox') {
                    const checkbox = $(this).find('.elemento-checkbox');
                    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
                }
            });
        }

        function marcarTodos(marcar) {
            $('.elemento-checkbox').prop('checked', marcar).trigger('change');
        }

        function actualizarContador() {
            const seleccionados = $('.elemento-checkbox:checked').length;
            const total = $('.elemento-checkbox').length;
            $('#contadorSeleccionados').text(`${seleccionados} de ${total} seleccionados`);
        }

        function confirmarSeleccion() {
            let seleccionados = [];
            $('.elemento-checkbox:checked').each(function() {
                seleccionados.push(parseInt($(this).val()));
            });
            
            elementosSeleccionados[tipoActual] = seleccionados;
            actualizarVistaSeleccionados(tipoActual);
            actualizarHiddenInputs();
            $('#modalSeleccion').modal('hide');
            
            // Mostrar mensaje de confirmación
            mostrarToast(`${seleccionados.length} elementos seleccionados correctamente`, 'success');
        }

        function actualizarVistaSeleccionados(tipo) {
            const config = configuracionTipos[tipo];
            const elementosActuales = elementosDisponibles[tipo].filter(elemento => 
                elementosSeleccionados[tipo].includes(elemento.id)
            );
            
            // Actualizar contador
            $('#contador-' + tipo).text(elementosActuales.length);
            
            // Actualizar lista de elementos
            const $lista = $('#lista-' + tipo);
            let contenido = '';
            
            if (elementosActuales.length === 0) {
                $lista.hide();
                $(`.seleccion-card[data-tipo="${tipo}"]`).removeClass('selected');
            } else {
                elementosActuales.forEach(function(elemento) {
                    contenido += `<span class="elemento-seleccionado">${elemento.descripcion}</span>`;
                });
                $lista.html(contenido).show();
                $(`.seleccion-card[data-tipo="${tipo}"]`).addClass('selected');
            }
        }

        function actualizarHiddenInputs() {
            // Limpiar inputs existentes
            $('#hidden-inputs-container').empty();
            
            // Agregar nuevos inputs
            Object.keys(elementosSeleccionados).forEach(tipo => {
                elementosSeleccionados[tipo].forEach(id => {
                    $('#hidden-inputs-container').append(
                        `<input type="hidden" name="${tipo}[]" value="${id}">`
                    );
                });
            });
        }

        function mostrarToast(mensaje, tipo = 'info') {
            const alertClass = tipo === 'error' ? 'danger' : tipo;
            const iconClass = tipo === 'success' ? 'check-circle' : 
                             tipo === 'error' ? 'exclamation-triangle' : 'info-circle';
            
            const toast = $(`
                <div class="toast-custom alert alert-${alertClass} alert-dismissible fade show" 
                     style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                    <i class="fas fa-${iconClass}"></i> ${mensaje}
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            `);
            
            $('body').append(toast);
            
            setTimeout(() => {
                toast.alert('close');
            }, 3000);
        }

        // Validación del formulario
        $('#formGuardarPlan').on('submit', function(e) {
            e.preventDefault(); // Siempre prevenir el envío directo
            
            // Verificar que hay al menos un elemento seleccionado
            let tieneElementos = false;
            
            Object.keys(elementosSeleccionados).forEach(tipo => {
                if (elementosSeleccionados[tipo].length > 0) {
                    tieneElementos = true;
                }
            });
            
            if (!tieneElementos) {
                mostrarToast('Debes seleccionar al menos un elemento para el plan didáctico', 'error');
                return false;
            }
            
            // Mostrar vista previa en lugar de enviar directamente
            mostrarVistaPrevia();
        });

        function guardarPlanDefinitivo() {
            // Actualizar los hidden inputs antes de enviar
            actualizarHiddenInputs();
            
            // Crear un nuevo formulario para enviar
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'guardar_plan.php';
            
            // Agregar plan_id
            const planInput = document.createElement('input');
            planInput.type = 'hidden';
            planInput.name = 'plan_id';
            planInput.value = <?php echo $plan_id; ?>;
            form.appendChild(planInput);
            
            // Agregar elementos seleccionados
            Object.keys(elementosSeleccionados).forEach(tipo => {
                elementosSeleccionados[tipo].forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = tipo + '[]';
                    input.value = id;
                    form.appendChild(input);
                });
            });
            
            // Agregar al body y enviar
            document.body.appendChild(form);
            form.submit();
        }

        // Inicializar página
        $(document).ready(function() {
            // Actualizar vista inicial para todos los tipos
            Object.keys(elementosSeleccionados).forEach(function(tipo) {
                actualizarVistaSeleccionados(tipo);
            });
            
            // Asegurar que los hidden inputs estén correctos
            actualizarHiddenInputs();
            
            // Animación de entrada para las cards
            $('.seleccion-card').each(function(index) {
                $(this).hide().delay(index * 100).fadeIn(500);
            });
            
            // Debug - verificar que las funciones estén disponibles
            console.log('Vista previa función disponible:', typeof mostrarVistaPrevia);
            console.log('Elementos disponibles:', elementosDisponibles);
            console.log('Elementos seleccionados:', elementosSeleccionados);
        });
    </script>

    <style>
        .toast-custom {
            animation: slideInRight 0.3s ease-out;
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .check-icon {
            animation: bounceIn 0.3s ease-out;
        }
        
        @keyframes bounceIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .contador-badge {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
</body>
</html>
    </div>

    <!-- Modal Vista Previa -->
    <div class="modal fade" id="modalVistaPrevia" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-eye"></i> Vista Previa del Plan Didáctico
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="contenidoVistaPrevia">
                    <!-- Contenido generado dinámicamente -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="generarPDF()">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-edit"></i> Modificar Selección
                    </button>
                    <button type="button" class="btn btn-success" onclick="guardarPlanDefinitivo()">
                        <i class="fas fa-save"></i> Confirmar y Guardar
                    </button>
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