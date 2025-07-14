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

// Obtener objetivos del plan con estado
$query = "SELECT po.estado, oc.descripcion 
          FROM plan_objetivos po 
          INNER JOIN objetivos_clase oc ON po.objetivo_id = oc.id 
          WHERE po.plan_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$plan_id]);
$objetivos = $stmt->fetchAll();

// Obtener contenidos del plan con estado
$query = "SELECT pc.estado, c.descripcion 
          FROM plan_contenidos pc 
          INNER JOIN contenidos c ON pc.contenido_id = c.id 
          WHERE pc.plan_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$plan_id]);
$contenidos = $stmt->fetchAll();

// Obtener estrategias metodológicas
$query = "SELECT pem.realizado, em.descripcion 
          FROM plan_estrategias_metodologicas pem 
          INNER JOIN estrategias_metodologicas em ON pem.estrategia_id = em.id 
          WHERE pem.plan_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$plan_id]);
$estrategias_metodologicas = $stmt->fetchAll();

// Obtener estrategias evaluativas
$query = "SELECT pee.realizado, ee.descripcion 
          FROM plan_estrategias_evaluativas pee 
          INNER JOIN estrategias_evaluativas ee ON pee.estrategia_id = ee.id 
          WHERE pee.plan_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$plan_id]);
$estrategias_evaluativas = $stmt->fetchAll();

// Obtener recursos
$query = "SELECT pr.utilizado, r.descripcion 
          FROM plan_recursos pr 
          INNER JOIN recursos r ON pr.recurso_id = r.id 
          WHERE pr.plan_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$plan_id]);
$recursos = $stmt->fetchAll();

$mensaje = obtenerMensaje();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detalles del Plan - Sistema Académico</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                        <div>
                            <h1 class="h3 mb-0 text-gray-800">Detalles del Plan Didáctico</h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Detalles del Plan</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="btn-group" role="group">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                            <?php if ($plan['estado'] === 'planificado'): ?>
                                <a href="configurar_plan.php?plan_id=<?php echo $plan_id; ?>" class="btn btn-warning">
                                    <i class="fas fa-edit"></i> Editar Plan
                                </a>
                                <a href="registro_actividades.php?plan_id=<?php echo $plan_id; ?>" class="btn btn-success">
                                    <i class="fas fa-play"></i> Ejecutar Clase
                                </a>
                            <?php else: ?>
                                <a href="registro_actividades.php?plan_id=<?php echo $plan_id; ?>" class="btn btn-info">
                                    <i class="fas fa-edit"></i> Editar Registro
                                </a>
                                <button class="btn btn-primary" onclick="generarPDF()">
                                    <i class="fas fa-file-pdf"></i> Generar PDF
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Información del Plan -->
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card border-left-<?php echo $plan['estado'] === 'ejecutado' ? 'success' : 'primary'; ?> shadow">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h4 class="text-<?php echo $plan['estado'] === 'ejecutado' ? 'success' : 'primary'; ?>">
                                                <i class="fas fa-book"></i> <?php echo $plan['codigo_asignatura']; ?> - <?php echo $plan['asignatura_nombre']; ?>
                                            </h4>
                                            <p class="mb-1">
                                                <strong><i class="fas fa-calendar"></i> Fecha:</strong> 
                                                <?php echo date('d/m/Y', strtotime($plan['fecha_clase'])); ?>
                                                <span class="ml-3">
                                                    <strong><i class="fas fa-tag"></i> Tipo:</strong>
                                                    <span class="badge badge-<?php echo $plan['tipo_clase'] === 'regular' ? 'primary' : 'warning'; ?>">
                                                        <?php echo ucfirst($plan['tipo_clase']); ?>
                                                    </span>
                                                </span>
                                            </p>
                                            <p class="mb-0">
                                                <strong><i class="fas fa-info-circle"></i> Estado:</strong> 
                                                <span class="badge badge-<?php echo $plan['estado'] === 'ejecutado' ? 'success' : 'info'; ?> badge-lg">
                                                    <?php echo ucfirst($plan['estado']); ?>
                                                </span>
                                            </p>
                                        </div>
                                        <div class="col-md-4 text-right">
                                            <div class="text-muted">
                                                <small>
                                                    <i class="fas fa-clock"></i> Creado: <?php echo date('d/m/Y H:i', strtotime($plan['fecha_creacion'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contenido del Plan -->
                    <div class="row">
                        <!-- Objetivos -->
                        <?php if (!empty($objetivos)): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card shadow">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="fas fa-bullseye"></i> Objetivos de la Clase</h6>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($objetivos as $objetivo): ?>
                                        <div class="d-flex justify-content-between align-items-start mb-3 p-2 bg-light rounded">
                                            <span class="flex-grow-1"><?php echo $objetivo['descripcion']; ?></span>
                                            <?php if ($plan['estado'] === 'ejecutado'): ?>
                                                <span class="badge badge-<?php echo $objetivo['estado'] === 'logrado' ? 'success' : ($objetivo['estado'] === 'no_logrado' ? 'danger' : 'secondary'); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $objetivo['estado'])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Pendiente</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Contenidos -->
                        <?php if (!empty($contenidos)): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card shadow">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="fas fa-list"></i> Contenidos</h6>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($contenidos as $contenido): ?>
                                        <div class="d-flex justify-content-between align-items-start mb-3 p-2 bg-light rounded">
                                            <span class="flex-grow-1"><?php echo $contenido['descripcion']; ?></span>
                                            <?php if ($plan['estado'] === 'ejecutado'): ?>
                                                <span class="badge badge-<?php echo $contenido['estado'] === 'terminado' ? 'success' : ($contenido['estado'] === 'sin_concluir' ? 'warning' : 'secondary'); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $contenido['estado'])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Pendiente</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Estrategias Metodológicas -->
                        <?php if (!empty($estrategias_metodologicas)): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card shadow">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-cogs"></i> Estrategias Metodológicas</h6>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($estrategias_metodologicas as $estrategia): ?>
                                        <div class="d-flex justify-content-between align-items-start mb-3 p-2 bg-light rounded">
                                            <span class="flex-grow-1"><?php echo $estrategia['descripcion']; ?></span>
                                            <?php if ($plan['estado'] === 'ejecutado'): ?>
                                                <span class="badge badge-<?php echo $estrategia['realizado'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $estrategia['realizado'] ? 'Realizado' : 'No Realizado'; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Pendiente</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Estrategias Evaluativas -->
                        <?php if (!empty($estrategias_evaluativas)): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card shadow">
                                <div class="card-header bg-warning text-white">
                                    <h6 class="mb-0"><i class="fas fa-clipboard-check"></i> Estrategias Evaluativas</h6>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($estrategias_evaluativas as $estrategia): ?>
                                        <div class="d-flex justify-content-between align-items-start mb-3 p-2 bg-light rounded">
                                            <span class="flex-grow-1"><?php echo $estrategia['descripcion']; ?></span>
                                            <?php if ($plan['estado'] === 'ejecutado'): ?>
                                                <span class="badge badge-<?php echo $estrategia['realizado'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $estrategia['realizado'] ? 'Realizado' : 'No Realizado'; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Pendiente</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Recursos -->
                        <?php if (!empty($recursos)): ?>
                        <div class="col-md-12 mb-4">
                            <div class="card shadow">
                                <div class="card-header bg-secondary text-white">
                                    <h6 class="mb-0"><i class="fas fa-tools"></i> Recursos</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach ($recursos as $recurso): ?>
                                            <div class="col-md-6 mb-2">
                                                <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                                    <span class="flex-grow-1"><?php echo $recurso['descripcion']; ?></span>
                                                    <?php if ($plan['estado'] === 'ejecutado'): ?>
                                                        <span class="badge badge-<?php echo $recurso['utilizado'] ? 'success' : 'secondary'; ?>">
                                                            <?php echo $recurso['utilizado'] ? 'Utilizado' : 'No Utilizado'; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary">Pendiente</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Estado del Plan -->
                    <?php if ($plan['estado'] === 'planificado'): ?>
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h5><i class="fas fa-info-circle"></i> Plan Didáctico Preparado</h5>
                                    <p class="mb-3">Esta clase está lista para ser ejecutada. Puedes:</p>
                                    <div class="btn-group" role="group">
                                        <a href="configurar_plan.php?plan_id=<?php echo $plan_id; ?>" class="btn btn-outline-info">
                                            <i class="fas fa-edit"></i> Hacer cambios al plan
                                        </a>
                                        <a href="registro_actividades.php?plan_id=<?php echo $plan_id; ?>" class="btn btn-info">
                                            <i class="fas fa-play"></i> Ejecutar la clase ahora
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                