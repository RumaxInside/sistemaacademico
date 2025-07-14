<?php
require_once '../config/database.php';
verificarRol('coordinador');

$database = new Database();
$db = $database->getConnection();

// Obtener lista de docentes
$query = "SELECT id, nombre_completo FROM usuarios WHERE rol = 'docente' AND estado = 'activo' ORDER BY nombre_completo";
$stmt = $db->prepare($query);
$stmt->execute();
$docentes = $stmt->fetchAll();

// Obtener lista de asignaturas
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
    <title>Progreso de Clases - Sistema Académico</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .periodo-selector {
            background-color: #f8f9fc;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .progress-bar-animated {
            animation: progress-fill 1s ease-out;
        }
        
        @keyframes progress-fill {
            from { width: 0; }
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

            <li class="nav-item active">
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
                        <h1 class="h3 mb-0 text-gray-800">Progreso de Clases</h1>
                        <button class="btn btn-primary" onclick="generarReportePDF()">
                            <i class="fas fa-file-pdf"></i> Generar Reporte PDF
                        </button>
                    </div>

                    <!-- Selector de Filtros -->
                    <div class="periodo-selector">
                        <h5 class="mb-3"><i class="fas fa-filter"></i> Filtros de Búsqueda</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Docente</label>
                                    <select class="form-control" id="filtroDocente">
                                        <option value="">Todos los docentes</option>
                                        <?php foreach ($docentes as $docente): ?>
                                            <option value="<?php echo $docente['id']; ?>">
                                                <?php echo $docente['nombre_completo']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Asignatura</label>
                                    <select class="form-control" id="filtroAsignatura">
                                        <option value="">Todas las asignaturas</option>
                                        <?php foreach ($asignaturas as $asignatura): ?>
                                            <option value="<?php echo $asignatura['id']; ?>">
                                                <?php echo $asignatura['codigo_asignatura'] . ' - ' . $asignatura['nombre']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Período</label>
                                    <select class="form-control" id="filtroPeriodo">
                                        <option value="semanal">Semanal</option>
                                        <option value="mensual" selected>Mensual</option>
                                        <option value="semestral">Semestral</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button class="btn btn-success btn-block" onclick="actualizarGraficos()">
                                        <i class="fas fa-search"></i> Mostrar Progreso
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas Generales -->
                    <div class="row" id="estadisticasGenerales">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stat-card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Clases</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalClases">0</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stat-card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Clases Ejecutadas</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="clasesEjecutadas">0</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stat-card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Objetivos Logrados</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="objetivosLogrados">0%</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-bullseye fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stat-card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Contenidos Completados</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="contenidosCompletados">0%</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-list fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráficos -->
                    <div class="row">
                        <!-- Gráfico de Progreso General -->
                        <div class="col-xl-6 col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Progreso General</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="graficoProgreso"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Gráfico de Distribución -->
                        <div class="col-xl-6 col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-success">Distribución de Elementos</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="graficoDistribucion"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detalles por Elemento -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-info">Desglose Detallado</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <!-- Objetivos -->
                                        <div class="col-md-6 mb-4">
                                            <h6 class="font-weight-bold">Objetivos de Clase</h6>
                                            <div class="mb-2">
                                                <span class="text-success">Logrados: <span id="objLogrados">0</span></span> / 
                                                <span class="text-danger">No Logrados: <span id="objNoLogrados">0</span></span>
                                            </div>
                                            <div class="progress" style="height: 25px;">
                                                <div class="progress-bar bg-success progress-bar-animated" role="progressbar" 
                                                     id="progressObjetivos" style="width: 0%">0%</div>
                                            </div>
                                        </div>

                                        <!-- Contenidos -->
                                        <div class="col-md-6 mb-4">
                                            <h6 class="font-weight-bold">Contenidos</h6>
                                            <div class="mb-2">
                                                <span class="text-success">Terminados: <span id="contTerminados">0</span></span> / 
                                                <span class="text-warning">Sin Concluir: <span id="contSinConcluir">0</span></span>
                                            </div>
                                            <div class="progress" style="height: 25px;">
                                                <div class="progress-bar bg-success progress-bar-animated" role="progressbar" 
                                                     id="progressContenidos" style="width: 0%">0%</div>
                                            </div>
                                        </div>

                                        <!-- Estrategias Metodológicas -->
                                        <div class="col-md-6 mb-4">
                                            <h6 class="font-weight-bold">Estrategias Metodológicas</h6>
                                            <div class="mb-2">
                                                <span class="text-info">Realizadas: <span id="estMetRealizadas">0</span></span> / 
                                                <span class="text-muted">Total: <span id="estMetTotal">0</span></span>
                                            </div>
                                            <div class="progress" style="height: 25px;">
                                                <div class="progress-bar bg-info progress-bar-animated" role="progressbar" 
                                                     id="progressMetodologicas" style="width: 0%">0%</div>
                                            </div>
                                        </div>

                                        <!-- Estrategias Evaluativas -->
                                        <div class="col-md-6 mb-4">
                                            <h6 class="font-weight-bold">Estrategias Evaluativas</h6>
                                            <div class="mb-2">
                                                <span class="text-warning">Realizadas: <span id="estEvalRealizadas">0</span></span> / 
                                                <span class="text-muted">Total: <span id="estEvalTotal">0</span></span>
                                            </div>
                                            <div class="progress" style="height: 25px;">
                                                <div class="progress-bar bg-warning progress-bar-animated" role="progressbar" 
                                                     id="progressEvaluativas" style="width: 0%">0%</div>
                                            </div>
                                        </div>
                                    </div>
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

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/js/sb-admin-2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

    <script>
        let graficoProgreso = null;
        let graficoDistribucion = null;

        // Inicializar gráficos al cargar la página
        $(document).ready(function() {
            inicializarGraficos();
            actualizarGraficos();
        });

        function inicializarGraficos() {
            // Gráfico de Progreso General
            const ctxProgreso = document.getElementById('graficoProgreso').getContext('2d');
            graficoProgreso = new Chart(ctxProgreso, {
                type: 'bar',
                data: {
                    labels: ['Objetivos', 'Contenidos', 'E. Metodológicas', 'E. Evaluativas'],
                    datasets: [{
                        label: 'Completado',
                        data: [0, 0, 0, 0],
                        backgroundColor: ['#1cc88a', '#1cc88a', '#1cc88a', '#1cc88a'],
                        borderWidth: 0
                    }, {
                        label: 'Sin Completar',
                        data: [0, 0, 0, 0],
                        backgroundColor: ['#e74a3b', '#f6c23e', '#858796', '#858796'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { stacked: true },
                        y: { 
                            stacked: true,
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + '%';
                                }
                            }
                        }
                    }
                }
            });

            // Gráfico de Distribución
            const ctxDistribucion = document.getElementById('graficoDistribucion').getContext('2d');
            graficoDistribucion = new Chart(ctxDistribucion, {
                type: 'doughnut',
                data: {
                    labels: ['Objetivos', 'Contenidos', 'E. Metodológicas', 'E. Evaluativas'],
                    datasets: [{
                        data: [25, 25, 25, 25],
                        backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        function actualizarGraficos() {
            const docente = $('#filtroDocente').val();
            const asignatura = $('#filtroAsignatura').val();
            const periodo = $('#filtroPeriodo').val();

            // Hacer petición AJAX para obtener datos
            $.ajax({
                url: 'obtener_datos_progreso.php',
                method: 'POST',
                data: {
                    docente_id: docente,
                    asignatura_id: asignatura,
                    periodo: periodo
                },
                dataType: 'json',
                success: function(datos) {
                    actualizarEstadisticas(datos);
                    actualizarGraficoProgreso(datos);
                    actualizarDetalles(datos);
                },
                error: function() {
                    console.error('Error al obtener datos');
                }
            });
        }

        function actualizarEstadisticas(datos) {
            $('#totalClases').text(datos.total_clases || 0);
            $('#clasesEjecutadas').text(datos.clases_ejecutadas || 0);
            $('#objetivosLogrados').text((datos.porcentaje_objetivos || 0) + '%');
            $('#contenidosCompletados').text((datos.porcentaje_contenidos || 0) + '%');
        }

        function actualizarGraficoProgreso(datos) {
            graficoProgreso.data.datasets[0].data = [
                datos.porcentaje_objetivos || 0,
                datos.porcentaje_contenidos || 0,
                datos.porcentaje_est_met || 0,
                datos.porcentaje_est_eval || 0
            ];
            
            graficoProgreso.data.datasets[1].data = [
                100 - (datos.porcentaje_objetivos || 0),
                100 - (datos.porcentaje_contenidos || 0),
                100 - (datos.porcentaje_est_met || 0),
                100 - (datos.porcentaje_est_eval || 0)
            ];
            
            graficoProgreso.update();

            // Actualizar gráfico de distribución con totales
            graficoDistribucion.data.datasets[0].data = [
                datos.total_objetivos || 0,
                datos.total_contenidos || 0,
                datos.total_est_met || 0,
                datos.total_est_eval || 0
            ];
            graficoDistribucion.update();
        }

        function actualizarDetalles(datos) {
            // Objetivos
            $('#objLogrados').text(datos.objetivos_logrados || 0);
            $('#objNoLogrados').text(datos.objetivos_no_logrados || 0);
            $('#progressObjetivos').css('width', (datos.porcentaje_objetivos || 0) + '%')
                                   .text((datos.porcentaje_objetivos || 0) + '%');

            // Contenidos
            $('#contTerminados').text(datos.contenidos_terminados || 0);
            $('#contSinConcluir').text(datos.contenidos_sin_concluir || 0);
            $('#progressContenidos').css('width', (datos.porcentaje_contenidos || 0) + '%')
                                    .text((datos.porcentaje_contenidos || 0) + '%');

            // Estrategias Metodológicas
            $('#estMetRealizadas').text(datos.est_met_realizadas || 0);
            $('#estMetTotal').text(datos.total_est_met || 0);
            $('#progressMetodologicas').css('width', (datos.porcentaje_est_met || 0) + '%')
                                       .text((datos.porcentaje_est_met || 0) + '%');

            // Estrategias Evaluativas
            $('#estEvalRealizadas').text(datos.est_eval_realizadas || 0);
            $('#estEvalTotal').text(datos.total_est_eval || 0);
            $('#progressEvaluativas').css('width', (datos.porcentaje_est_eval || 0) + '%')
                                     .text((datos.porcentaje_est_eval || 0) + '%');
        }

        function generarReportePDF() {
            const docente = $('#filtroDocente').val();
            const asignatura = $('#filtroAsignatura').val();
            const periodo = $('#filtroPeriodo').val();
            
            window.open('generar_reporte_progreso.php?docente=' + docente + 
                       '&asignatura=' + asignatura + '&periodo=' + periodo, '_blank');
        }
    </script>
</body>
</html>