<?php
require_once '../config/database.php';
verificarRol('docente');

$database = new Database();
$db = $database->getConnection();

// Obtener parámetros
$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$plan_id = isset($_GET['plan_id']) ? (int)$_GET['plan_id'] : 0;

// Si no hay plan_id, buscar planes para esa fecha
if (!$plan_id) {
    $query = "SELECT pd.*, a.nombre as asignatura_nombre, a.codigo_asignatura 
              FROM planes_didacticos pd 
              INNER JOIN asignaturas a ON pd.asignatura_id = a.id 
              WHERE pd.docente_id = ? AND pd.fecha_clase = ?
              ORDER BY a.codigo_asignatura";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['usuario_id'], $fecha]);
    $planes_fecha = $stmt->fetchAll();
    
    if (count($planes_fecha) == 1) {
        $plan_id = $planes_fecha[0]['id'];
    }
} else {
    $planes_fecha = [];
}

$plan = null;
$objetivos = [];
$contenidos = [];
$estrategias_metodologicas = [];
$estrategias_evaluativas = [];

if ($plan_id) {
    // Obtener información del plan
    $query = "SELECT pd.*, a.nombre as asignatura_nombre, a.codigo_asignatura 
              FROM planes_didacticos pd 
              INNER JOIN asignaturas a ON pd.asignatura_id = a.id 
              WHERE pd.id = ? AND pd.docente_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$plan_id, $_SESSION['usuario_id']]);
    $plan = $stmt->fetch();

    if ($plan) {
        // Obtener objetivos del plan con estado
        $query = "SELECT po.id as plan_objetivo_id, po.estado, oc.id as objetivo_id, oc.descripcion 
                  FROM plan_objetivos po 
                  INNER JOIN objetivos_clase oc ON po.objetivo_id = oc.id 
                  WHERE po.plan_id = ?
                  ORDER BY oc.descripcion";
        $stmt = $db->prepare($query);
        $stmt->execute([$plan_id]);
        $objetivos = $stmt->fetchAll();

        // Obtener contenidos del plan con estado
        $query = "SELECT pc.id as plan_contenido_id, pc.estado, c.id as contenido_id, c.descripcion 
                  FROM plan_contenidos pc 
                  INNER JOIN contenidos c ON pc.contenido_id = c.id 
                  WHERE pc.plan_id = ?
                  ORDER BY c.descripcion";
        $stmt = $db->prepare($query);
        $stmt->execute([$plan_id]);
        $contenidos = $stmt->fetchAll();

        // Obtener estrategias metodológicas
        $query = "SELECT pem.id as plan_estrategia_id, pem.realizado, em.id as estrategia_id, em.descripcion 
                  FROM plan_estrategias_metodologicas pem 
                  INNER JOIN estrategias_metodologicas em ON pem.estrategia_id = em.id 
                  WHERE pem.plan_id = ?
                  ORDER BY em.descripcion";
        $stmt = $db->prepare($query);
        $stmt->execute([$plan_id]);
        $estrategias_metodologicas = $stmt->fetchAll();

        // Obtener estrategias evaluativas
        $query = "SELECT pee.id as plan_estrategia_id, pee.realizado, ee.id as estrategia_id, ee.descripcion 
                  FROM plan_estrategias_evaluativas pee 
                  INNER JOIN estrategias_evaluativas ee ON pee.estrategia_id = ee.id 
                  WHERE pee.plan_id = ?
                  ORDER BY ee.descripcion";
        $stmt = $db->prepare($query);
        $stmt->execute([$plan_id]);
        $estrategias_evaluativas = $stmt->fetchAll();
    }
}

$mensaje = obtenerMensaje();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro de Actividades - Sistema Académico</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .progreso-container {
            background-color: #f8f9fc;
            border: 2px solid #e3e6f0;
            padding: 20px;
            margin: 15px 0;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .progreso-container:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .progreso-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 15px;
            justify-content: center;
        }
        
        .progreso-btn {
            padding: 12px 24px;
            border: 2px solid;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            min-width: 140px;
        }
        
        .progreso-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        /* Estilos para Objetivos */
        .progreso-btn.logrado { 
            background-color: #fff; 
            color: #1cc88a; 
            border-color: #1cc88a; 
        }
        .progreso-btn.logrado.active { 
            background-color: #1cc88a; 
            color: white; 
            box-shadow: 0 4px 8px rgba(28, 200, 138, 0.3);
        }
        
        .progreso-btn.no-logrado { 
            background-color: #fff; 
            color: #e74a3b; 
            border-color: #e74a3b; 
        }
        .progreso-btn.no-logrado.active { 
            background-color: #e74a3b; 
            color: white; 
            box-shadow: 0 4px 8px rgba(231, 74, 59, 0.3);
        }
        
        /* Estilos para Contenidos */
        .progreso-btn.terminado { 
            background-color: #fff; 
            color: #1cc88a; 
            border-color: #1cc88a; 
        }
        .progreso-btn.terminado.active { 
            background-color: #1cc88a; 
            color: white; 
            box-shadow: 0 4px 8px rgba(28, 200, 138, 0.3);
        }
        
        .progreso-btn.sin-concluir { 
            background-color: #fff; 
            color: #f6c23e; 
            border-color: #f6c23e; 
        }
        .progreso-btn.sin-concluir.active { 
            background-color: #f6c23e; 
            color: white; 
            box-shadow: 0 4px 8px rgba(246, 194, 62, 0.3);
        }
        
        /* Estilos para Estrategias */
        .progreso-btn.realizado { 
            background-color: #fff; 
            color: #36b9cc; 
            border-color: #36b9cc; 
        }
        .progreso-btn.realizado.active { 
            background-color: #36b9cc; 
            color: white; 
            box-shadow: 0 4px 8px rgba(54, 185, 204, 0.3);
        }
        
        .progreso-btn.no-realizado { 
            background-color: #fff; 
            color: #6c757d; 
            border-color: #6c757d; 
        }
        .progreso-btn.no-realizado.active { 
            background-color: #6c757d; 
            color: white; 
            box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
        }
        
        .asignatura-header {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .selector-plan {
            background: #f8f9fc;
            border: 2px solid #e3e6f0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
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

            <li class="nav-item active">
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
                    <!-- Mensajes -->
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
                            <h1 class="h3 mb-0 text-gray-800">Registro de Actividades de Clase</h1>
                            <p class="mb-0 text-gray-600">
                                <i class="fas fa-calendar"></i> Fecha: <?php echo date('d/m/Y', strtotime($fecha)); ?>
                            </p>
                        </div>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al Dashboard
                        </a>
                    </div>

                    <!-- Contenido Principal -->
                    <?php if (!$plan && count($planes_fecha) > 1): ?>
                        <!-- Selector de Plan cuando hay múltiples clases -->
                        <div class="selector-plan">
                            <h5><i class="fas fa-list"></i> Selecciona la clase a registrar:</h5>
                            <p class="text-muted">Hay múltiples clases planificadas para esta fecha. Selecciona una:</p>
                            <div class="row">
                                <?php foreach ($planes_fecha as $plan_opcion): ?>
                                    <div class="col-md-6 mb-3">
                                        <a href="?fecha=<?php echo $fecha; ?>&plan_id=<?php echo $plan_opcion['id']; ?>" 
                                           class="card h-100 text-decoration-none">
                                            <div class="card-body text-center">
                                                <i class="fas fa-book fa-2x text-primary mb-3"></i>
                                                <h6 class="card-title"><?php echo $plan_opcion['codigo_asignatura']; ?></h6>
                                                <p class="card-text"><?php echo $plan_opcion['asignatura_nombre']; ?></p>
                                                <span class="badge badge-<?php echo $plan_opcion['tipo_clase'] === 'regular' ? 'primary' : 'warning'; ?>">
                                                    <?php echo ucfirst($plan_opcion['tipo_clase']); ?>
                                                </span>
                                            </div>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    
                    <?php elseif (!$plan): ?>
                        <!-- No hay clases para esta fecha -->
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle fa-3x mb-3"></i>
                            <h5>No hay clases planificadas para esta fecha</h5>
                            <p>¿Deseas planificar una clase para el <?php echo date('d/m/Y', strtotime($fecha)); ?>?</p>
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Planificar Clase
                            </a>
                        </div>
                    
                    <?php else: ?>
                        <!-- Información de la clase -->
                        <div class="asignatura-header">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h4 class="mb-1">
                                        <i class="fas fa-book"></i> <?php echo $plan['codigo_asignatura']; ?> - <?php echo $plan['asignatura_nombre']; ?>
                                    </h4>
                                    <p class="mb-0">
                                        <i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($plan['fecha_clase'])); ?> | 
                                        <i class="fas fa-tag"></i> <?php echo ucfirst($plan['tipo_clase']); ?> |
                                        <i class="fas fa-info-circle"></i> Estado: <?php echo ucfirst($plan['estado']); ?>
                                    </p>
                                </div>
                                <div class="col-md-4 text-right">
                                    <span class="badge badge-<?php echo $plan['estado'] === 'ejecutado' ? 'success' : 'info'; ?> badge-lg">
                                        <i class="fas fa-<?php echo $plan['estado'] === 'ejecutado' ? 'check-circle' : 'clock'; ?>"></i>
                                        <?php echo ucfirst($plan['estado']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Verificar si hay elementos configurados -->
                        <?php if (empty($objetivos) && empty($contenidos) && empty($estrategias_metodologicas) && empty($estrategias_evaluativas)): ?>
                            <div class="alert alert-warning">
                                <h5><i class="fas fa-exclamation-triangle"></i> Plan sin configurar</h5>
                                <p>Esta clase no tiene elementos configurados. Primero debes configurar el plan didáctico.</p>
                                <a href="configurar_plan.php?plan_id=<?php echo $plan['id']; ?>" class="btn btn-warning">
                                    <i class="fas fa-cog"></i> Configurar Plan Didáctico
                                </a>
                            </div>
                        
                        <?php else: ?>
                            <!-- Formulario de progreso -->
                            <form method="POST" action="procesar_actividad.php" id="formProgreso">
                                <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                                
                                <div class="row">
                                    <!-- Objetivos de la Clase -->
                                    <?php if (!empty($objetivos)): ?>
                                    <div class="col-md-12 mb-4">
                                        <div class="card shadow border-left-primary">
                                            <div class="card-header bg-primary text-white">
                                                <h6 class="m-0 font-weight-bold">
                                                    <i class="fas fa-bullseye"></i> Objetivos de la Clase
                                                    <span class="badge badge-light text-primary ml-2"><?php echo count($objetivos); ?></span>
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <?php foreach ($objetivos as $objetivo): ?>
                                                    <div class="progreso-container">
                                                        <div class="mb-2">
                                                            <strong class="text-primary">
                                                                <i class="fas fa-target"></i> <?php echo $objetivo['descripcion']; ?>
                                                            </strong>
                                                        </div>
                                                        <div class="progreso-buttons">
                                                            <button type="button" 
                                                                    class="progreso-btn logrado <?php echo $objetivo['estado'] === 'logrado' ? 'active' : ''; ?>" 
                                                                    onclick="seleccionarEstado('objetivo_<?php echo $objetivo['objetivo_id']; ?>', 'logrado', this)">
                                                                <i class="fas fa-check-circle"></i> LOGRADO
                                                            </button>
                                                            <button type="button" 
                                                                    class="progreso-btn no-logrado <?php echo $objetivo['estado'] === 'no_logrado' ? 'active' : ''; ?>" 
                                                                    onclick="seleccionarEstado('objetivo_<?php echo $objetivo['objetivo_id']; ?>', 'no_logrado', this)">
                                                                <i class="fas fa-times-circle"></i> NO LOGRADO
                                                            </button>
                                                        </div>
                                                        <input type="hidden" name="objetivo_<?php echo $objetivo['objetivo_id']; ?>" 
                                                               value="<?php echo $objetivo['estado'] ?: 'pendiente'; ?>" 
                                                               id="objetivo_<?php echo $objetivo['objetivo_id']; ?>">
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Contenidos -->
                                    <?php if (!empty($contenidos)): ?>
                                    <div class="col-md-12 mb-4">
                                        <div class="card shadow border-left-success">
                                            <div class="card-header bg-success text-white">
                                                <h6 class="m-0 font-weight-bold">
                                                    <i class="fas fa-list"></i> Contenidos
                                                    <span class="badge badge-light text-success ml-2"><?php echo count($contenidos); ?></span>
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <?php foreach ($contenidos as $contenido): ?>
                                                    <div class="progreso-container">
                                                        <div class="mb-2">
                                                            <strong class="text-success">
                                                                <i class="fas fa-book-open"></i> <?php echo $contenido['descripcion']; ?>
                                                            </strong>
                                                        </div>
                                                        <div class="progreso-buttons">
                                                            <button type="button" 
                                                                    class="progreso-btn terminado <?php echo $contenido['estado'] === 'terminado' ? 'active' : ''; ?>" 
                                                                    onclick="seleccionarEstado('contenido_<?php echo $contenido['contenido_id']; ?>', 'terminado', this)">
                                                                <i class="fas fa-check-circle"></i> TERMINADO
                                                            </button>
                                                            <button type="button" 
                                                                    class="progreso-btn sin-concluir <?php echo $contenido['estado'] === 'sin_concluir' ? 'active' : ''; ?>" 
                                                                    onclick="seleccionarEstado('contenido_<?php echo $contenido['contenido_id']; ?>', 'sin_concluir', this)">
                                                                <i class="fas fa-clock"></i> SIN CONCLUIR
                                                            </button>
                                                        </div>
                                                        <input type="hidden" name="contenido_<?php echo $contenido['contenido_id']; ?>" 
                                                               value="<?php echo $contenido['estado'] ?: 'pendiente'; ?>" 
                                                               id="contenido_<?php echo $contenido['contenido_id']; ?>">
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Estrategias Metodológicas -->
                                    <?php if (!empty($estrategias_metodologicas)): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow border-left-info">
                                            <div class="card-header bg-info text-white">
                                                <h6 class="m-0 font-weight-bold">
                                                    <i class="fas fa-cogs"></i> Estrategias Metodológicas
                                                    <span class="badge badge-light text-info ml-2"><?php echo count($estrategias_metodologicas); ?></span>
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <?php foreach ($estrategias_metodologicas as $estrategia): ?>
                                                    <div class="progreso-container">
                                                        <div class="mb-2">
                                                            <strong class="text-info">
                                                                <i class="fas fa-tools"></i> <?php echo $estrategia['descripcion']; ?>
                                                            </strong>
                                                        </div>
                                                        <div class="progreso-buttons">
                                                            <button type="button" 
                                                                    class="progreso-btn realizado <?php echo $estrategia['realizado'] ? 'active' : ''; ?>" 
                                                                    onclick="seleccionarRealizacion('est_met_<?php echo $estrategia['estrategia_id']; ?>', true, this)">
                                                                <i class="fas fa-check-circle"></i> REALIZADO
                                                            </button>
                                                            <button type="button" 
                                                                    class="progreso-btn no-realizado <?php echo !$estrategia['realizado'] ? 'active' : ''; ?>" 
                                                                    onclick="seleccionarRealizacion('est_met_<?php echo $estrategia['estrategia_id']; ?>', false, this)">
                                                                <i class="fas fa-times-circle"></i> NO REALIZADO
                                                            </button>
                                                        </div>
                                                        <input type="hidden" name="est_met_<?php echo $estrategia['estrategia_id']; ?>" 
                                                               value="<?php echo $estrategia['realizado'] ? '1' : '0'; ?>" 
                                                               id="est_met_<?php echo $estrategia['estrategia_id']; ?>">
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Estrategias Evaluativas -->
                                    <?php if (!empty($estrategias_evaluativas)): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow border-left-warning">
                                            <div class="card-header bg-warning text-white">
                                                <h6 class="m-0 font-weight-bold">
                                                    <i class="fas fa-clipboard-check"></i> Estrategias Evaluativas
                                                    <span class="badge badge-light text-warning ml-2"><?php echo count($estrategias_evaluativas); ?></span>
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <?php foreach ($estrategias_evaluativas as $estrategia): ?>
                                                    <div class="progreso-container">
                                                        <div class="mb-2">
                                                            <strong class="text-warning">
                                                                <i class="fas fa-chart-line"></i> <?php echo $estrategia['descripcion']; ?>
                                                            </strong>
                                                        </div>
                                                        <div class="progreso-buttons">
                                                            <button type="button" 
                                                                    class="progreso-btn realizado <?php echo $estrategia['realizado'] ? 'active' : ''; ?>" 
                                                                    onclick="seleccionarRealizacion('est_eval_<?php echo $estrategia['estrategia_id']; ?>', true, this)">
                                                                <i class="fas fa-check-circle"></i> REALIZADO
                                                            </button>
                                                            <button type="button" 
                                                                    class="progreso-btn no-realizado <?php echo !$estrategia['realizado'] ? 'active' : ''; ?>" 
                                                                    onclick="seleccionarRealizacion('est_eval_<?php echo $estrategia['estrategia_id']; ?>', false, this)">
                                                                <i class="fas fa-times-circle"></i> NO REALIZADO
                                                            </button>
                                                        </div>
                                                        <input type="hidden" name="est_eval_<?php echo $estrategia['estrategia_id']; ?>" 
                                                               value="<?php echo $estrategia['realizado'] ? '1' : '0'; ?>" 
                                                               id="est_eval_<?php echo $estrategia['estrategia_id']; ?>">
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Resumen de Progreso -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="card shadow border-left-dark">
                                            <div class="card-header py-3 bg-dark text-white">
                                                <h6 class="m-0 font-weight-bold">
                                                    <i class="fas fa-chart-pie"></i> Resumen de Progreso de la Clase
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row" id="resumenProgreso">
                                                    <!-- Se actualiza dinámicamente con JavaScript -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Botones de Acción -->
                                <div class="row">
                                    <div class="col-12">
                                        <div class="card shadow border-left-success">
                                            <div class="card-body text-center">
                                                <div class="mb-3">
                                                    <i class="fas fa-save fa-3x text-success mb-3"></i>
                                                    <h4 class="text-success">Finalizar Registro de Actividad</h4>
                                                    <p class="text-muted">
                                                        Revisa que hayas marcado correctamente el progreso de todos los elementos 
                                                        antes de guardar el registro de la clase.
                                                    </p>
                                                </div>
                                                <div class="btn-group" role="group">
                                                    <button type="submit" class="btn btn-success btn-lg">
                                                        <i class="fas fa-save"></i> Guardar Registro de Actividad
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
        function seleccionarEstado(campo, estado, boton) {
            // Remover clase active de todos los botones del mismo grupo
            $(boton).siblings('.progreso-btn').removeClass('active');
            // Agregar clase active al botón seleccionado
            $(boton).addClass('active');
            // Actualizar el valor del campo oculto
            $('#' + campo).val(estado);
            
            // Actualizar resumen
            actualizarResumenProgreso();
        }

        function seleccionarRealizacion(campo, realizado, boton) {
            // Remover clase active de todos los botones del mismo grupo
            $(boton).siblings('.progreso-btn').removeClass('active');
            // Agregar clase active al botón seleccionado
            $(boton).addClass('active');
            // Actualizar el valor del campo oculto
            $('#' + campo).val(realizado ? '1' : '0');
            
            // Actualizar resumen
            actualizarResumenProgreso();
        }

        function actualizarResumenProgreso() {
            let estadisticas = {
                objetivos: { total: 0, logrados: 0 },
                contenidos: { total: 0, terminados: 0 },
                estrategias_met: { total: 0, realizadas: 0 },
                estrategias_eval: { total: 0, realizadas: 0 }
            };

            // Contar objetivos
            $('input[name^="objetivo_"]').each(function() {
                estadisticas.objetivos.total++;
                if ($(this).val() === 'logrado') estadisticas.objetivos.logrados++;
            });

            // Contar contenidos
            $('input[name^="contenido_"]').each(function() {
                estadisticas.contenidos.total++;
                if ($(this).val() === 'terminado') estadisticas.contenidos.terminados++;
            });

            // Contar estrategias metodológicas
            $('input[name^="est_met_"]').each(function() {
                estadisticas.estrategias_met.total++;
                if ($(this).val() === '1') estadisticas.estrategias_met.realizadas++;
            });

            // Contar estrategias evaluativas
            $('input[name^="est_eval_"]').each(function() {
                estadisticas.estrategias_eval.total++;
                if ($(this).val() === '1') estadisticas.estrategias_eval.realizadas++;
            });

            // Generar HTML del resumen
            let resumenHTML = '';

            if (estadisticas.objetivos.total > 0) {
                let porcentaje = Math.round((estadisticas.objetivos.logrados / estadisticas.objetivos.total) * 100);
                resumenHTML += `
                    <div class="col-md-3 mb-3">
                        <div class="card border-left-primary h-100">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Objetivos Logrados</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">${estadisticas.objetivos.logrados}/${estadisticas.objetivos.total}</div>
                                <div class="progress progress-sm mt-2">
                                    <div class="progress-bar bg-primary" style="width: ${porcentaje}%"></div>
                                </div>
                                <small class="text-muted">${porcentaje}% completado</small>
                            </div>
                        </div>
                    </div>
                `;
            }

            if (estadisticas.contenidos.total > 0) {
                let porcentaje = Math.round((estadisticas.contenidos.terminados / estadisticas.contenidos.total) * 100);
                resumenHTML += `
                    <div class="col-md-3 mb-3">
                        <div class="card border-left-success h-100">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Contenidos Terminados</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">${estadisticas.contenidos.terminados}/${estadisticas.contenidos.total}</div>
                                <div class="progress progress-sm mt-2">
                                    <div class="progress-bar bg-success" style="width: ${porcentaje}%"></div>
                                </div>
                                <small class="text-muted">${porcentaje}% completado</small>
                            </div>
                        </div>
                    </div>
                `;
            }

            if (estadisticas.estrategias_met.total > 0) {
                let porcentaje = Math.round((estadisticas.estrategias_met.realizadas / estadisticas.estrategias_met.total) * 100);
                resumenHTML += `
                    <div class="col-md-3 mb-3">
                        <div class="card border-left-info h-100">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Est. Metodológicas</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">${estadisticas.estrategias_met.realizadas}/${estadisticas.estrategias_met.total}</div>
                                <div class="progress progress-sm mt-2">
                                    <div class="progress-bar bg-info" style="width: ${porcentaje}%"></div>
                                </div>
                                <small class="text-muted">${porcentaje}% realizadas</small>
                            </div>
                        </div>
                    </div>
                `;
            }

            if (estadisticas.estrategias_eval.total > 0) {
                let porcentaje = Math.round((estadisticas.estrategias_eval.realizadas / estadisticas.estrategias_eval.total) * 100);
                resumenHTML += `
                    <div class="col-md-3 mb-3">
                        <div class="card border-left-warning h-100">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Est. Evaluativas</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">${estadisticas.estrategias_eval.realizadas}/${estadisticas.estrategias_eval.total}</div>
                                <div class="progress progress-sm mt-2">
                                    <div class="progress-bar bg-warning" style="width: ${porcentaje}%"></div>
                                </div>
                                <small class="text-muted">${porcentaje}% realizadas</small>
                            </div>
                        </div>
                    </div>
                `;
            }

            $('#resumenProgreso').html(resumenHTML);
        }

        // Validación del formulario
        $('#formProgreso').on('submit', function(e) {
            // Confirmar guardado
            if (!confirm('¿Estás seguro de guardar este registro? Una vez guardado, la clase se marcará como ejecutada.')) {
                e.preventDefault();
                return false;
            }
            
            // Mostrar loading
            $(this).find('button[type="submit"]').html('<i class="fas fa-spinner fa-spin"></i> Guardando...').prop('disabled', true);
        });

        // Inicializar página
        $(document).ready(function() {
            // Marcar botones activos según valores iniciales
            $('input[type="hidden"][name^="objetivo_"], input[type="hidden"][name^="contenido_"], input[type="hidden"][name^="est_"]').each(function() {
                let valor = $(this).val();
                let campo = $(this).attr('name');
                
                if (valor && valor !== 'pendiente' && valor !== '0') {
                    // Encontrar y activar el botón correspondiente
                    if (campo.startsWith('objetivo_')) {
                        $(`.progreso-btn.${valor}`).filter(function() {
                            return $(this).attr('onclick') && $(this).attr('onclick').includes(campo);
                        }).addClass('active');
                    } else if (campo.startsWith('contenido_')) {
                        $(`.progreso-btn.${valor}`).filter(function() {
                            return $(this).attr('onclick') && $(this).attr('onclick').includes(campo);
                        }).addClass('active');
                    } else if (campo.startsWith('est_') && valor === '1') {
                        $(`.progreso-btn.realizado`).filter(function() {
                            return $(this).attr('onclick') && $(this).attr('onclick').includes(campo);
                        }).addClass('active');
                    }
                }
            });
            
            // Actualizar resumen inicial
            actualizarResumenProgreso();
            
            // Animaciones de entrada
            $('.card').each(function(index) {
                $(this).hide().delay(index * 200).fadeIn(600);
            });
        });
    </script>
</body>
</html>