<?php
session_start();
require_once '../config/database.php';

// Verificar sesión y rol
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'docente') {
    header('Location: ../acceso_denegado.php');
    exit;
}

// Obtener plan_id desde la URL
$plan_id = isset($_GET['plan_id']) ? intval($_GET['plan_id']) : 0;
if ($plan_id <= 0) {
    die('Error: Plan no especificado.');
}

// Conectar a la base de datos
$conn = conectarDB();

// Obtener datos del plan
$query = "SELECT p.*, a.codigo_asignatura, a.nombre FROM planes_didacticos p 
          JOIN asignaturas a ON p.asignatura_id = a.id 
          WHERE p.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $plan_id);
$stmt->execute();
$plan = $stmt->get_result()->fetch_assoc();
if (!$plan) {
    die('Error: Plan no encontrado.');
}

// Obtener elementos didácticos
$codigo_asignatura = $plan['codigo_asignatura'];
$objetivos = $conn->query("SELECT * FROM objetivos_clase WHERE codigo_asignatura = '$codigo_asignatura'")->fetch_all(MYSQLI_ASSOC);
$contenidos = $conn->query("SELECT * FROM contenidos WHERE codigo_asignatura = '$codigo_asignatura'")->fetch_all(MYSQLI_ASSOC);
$estrategias_met = $conn->query("SELECT * FROM estrategias_metodologicas WHERE codigo_asignatura = '$codigo_asignatura'")->fetch_all(MYSQLI_ASSOC);
$estrategias_eval = $conn->query("SELECT * FROM estrategias_evaluativas WHERE codigo_asignatura = '$codigo_asignatura'")->fetch_all(MYSQLI_ASSOC);
$recursos = $conn->query("SELECT * FROM recursos WHERE codigo_asignatura = '$codigo_asignatura'")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configurar Plan - Sistema Académico</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .toast { position: fixed; top: 20px; right: 20px; z-index: 1050; }
        .counter { font-size: 0.9em; color: #666; margin-left: 10px; }
    </style>
</head>
<body class="bg-gradient-light">
    <div class="container-fluid">
        <h1 class="h3 mb-4 text-gray-800">Configurar Plan: <?php echo htmlspecialchars($plan['nombre']); ?></h1>
        
        <form id="formPlan" action="guardar_plan.php" method="POST">
            <input type="hidden" name="plan_id" value="<?php echo $plan_id; ?>">
            
            <!-- Objetivos -->
            <div class="card mb-4">
                <div class="card-header">
                    Objetivos de Clase
                    <span class="counter" id="objetivos-counter">0 seleccionados</span>
                </div>
                <div class="card-body">
                    <button type="button" class="btn btn-sm btn-primary mb-2" onclick="marcarTodos('objetivos')">Marcar Todos</button>
                    <button type="button" class="btn btn-sm btn-secondary mb-2" onclick="desmarcarTodos('objetivos')">Desmarcar Todos</button>
                    <?php foreach ($objetivos as $obj): ?>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="objetivos[]" value="<?php echo $obj['id']; ?>" checked>
                            <label class="form-check-label"><?php echo htmlspecialchars($obj['descripcion']); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Contenidos -->
            <div class="card mb-4">
                <div class="card-header">
                    Contenidos
                    <span class="counter" id="contenidos-counter">0 seleccionados</span>
                </div>
                <div class="card-body">
                    <button type="button" class="btn btn-sm btn-primary mb-2" onclick="marcarTodos('contenidos')">Marcar Todos</button>
                    <button type="button" class="btn btn-sm btn-secondary mb-2" onclick="desmarcarTodos('contenidos')">Desmarcar Todos</button>
                    <?php foreach ($contenidos as $cont): ?>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="contenidos[]" value="<?php echo $cont['id']; ?>" checked>
                            <label class="form-check-label"><?php echo htmlspecialchars($cont['descripcion']); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Estrategias Metodológicas -->
            <div class="card mb-4">
                <div class="card-header">
                    Estrategias Metodológicas
                    <span class="counter" id="estrategias_met-counter">0 seleccionados</span>
                </div>
                <div class="card-body">
                    <button type="button" class="btn btn-sm btn-primary mb-2" onclick="marcarTodos('estrategias_met')">Marcar Todos</button>
                    <button type="button" class="btn btn-sm btn-secondary mb-2" onclick="desmarcarTodos('estrategias_met')">Desmarcar Todos</button>
                    <?php foreach ($estrategias_met as $est): ?>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="estrategias_met[]" value="<?php echo $est['id']; ?>" checked>
                            <label class="form-check-label"><?php echo htmlspecialchars($est['descripcion']); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Estrategias Evaluativas -->
            <div class="card mb-4">
                <div class="card-header">
                    Estrategias Evaluativas
                    <span class="counter" id="estrategias_eval-counter">0 seleccionados</span>
                </div>
                <div class="card-body">
                    <button type="button" class="btn btn-sm btn-primary mb-2" onclick="marcarTodos('estrategias_eval')">Marcar Todos</button>
                    <button type="button" class="btn btn-sm btn-secondary mb-2" onclick="desmarcarTodos('estrategias_eval')">Desmarcar Todos</button>
                    <?php foreach ($estrategias_eval as $est): ?>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="estrategias_eval[]" value="<?php echo $est['id']; ?>" checked>
                            <label class="form-check-label"><?php echo htmlspecialchars($est['descripcion']); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Recursos -->
            <div class="card mb-4">
                <div class="card-header">
                    Recursos
                    <span class="counter" id="recursos-counter">0 seleccionados</span>
                </div>
                <div class="card-body">
                    <button type="button" class="btn btn-sm btn-primary mb-2" onclick="marcarTodos('recursos')">Marcar Todos</button>
                    <button type="button" class="btn btn-sm btn-secondary mb-2" onclick="desmarcarTodos('recursos')">Desmarcar Todos</button>
                    <?php foreach ($recursos as $rec): ?>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="recursos[]" value="<?php echo $rec['id']; ?>" checked>
                            <label class="form-check-label"><?php echo htmlspecialchars($rec['descripcion']); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Botones de acción -->
            <div class="mb-4">
                <button type="button" class="btn btn-info" data-toggle="modal" data-target="#vistaPreviaModal">Vista Previa</button>
                <button type="submit" class="btn btn-success">Guardar Plan</button>
            </div>
        </form>
        
        <!-- Modal Vista Previa -->
        <div class="modal fade" id="vistaPreviaModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Vista Previa del Plan</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" id="vistaPreviaContenido">
                        <div class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Toast para notificaciones -->
        <div class="toast" id="toastNotificacion" data-delay="3000">
            <div class="toast-body"></div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            // Inicializar contadores
            actualizarContadores();

            // Actualizar contadores al cambiar selección
            $('input[type="checkbox"]').on('change', actualizarContadores);

            // Marcar/Desmarcar todos
            window.marcarTodos = function (categoria) {
                $(`input[name="${categoria}[]"]`).prop('checked', true);
                actualizarContadores();
            };
            window.desmarcarTodos = function (categoria) {
                $(`input[name="${categoria}[]"]`).prop('checked', false);
                actualizarContadores();
            };

            // Actualizar contadores visuales
            function actualizarContadores() {
                $('#objetivos-counter').text(`${$('input[name="objetivos[]"]:checked').length} seleccionados`);
                $('#contenidos-counter').text(`${$('input[name="contenidos[]"]:checked').length} seleccionados`);
                $('#estrategias_met-counter').text(`${$('input[name="estrategias_met[]"]:checked').length} seleccionados`);
                $('#estrategias_eval-counter').text(`${$('input[name="estrategias_eval[]"]:checked').length} seleccionados`);
                $('#recursos-counter').text(`${$('input[name="recursos[]"]:checked').length} seleccionados`);
            }

            // Vista previa
            $('#vistaPreviaModal').on('show.bs.modal', function () {
                let formData = $('#formPlan').serialize();
                $.ajax({
                    url: 'obtener_elementos.php',
                    method: 'POST',
                    data: formData,
                    success: function (response) {
                        $('#vistaPreviaContenido').html(response);
                    },
                    error: function () {
                        $('#vistaPreviaContenido').html('<p class="text-danger">Error al cargar la vista previa.</p>');
                        mostrarToast('Error al cargar la vista previa.', 'danger');
                    }
                });
            });

            // Validar formulario antes de enviar
            $('#formPlan').on('submit', function (e) {
                if ($('input[name="objetivos[]"]:checked').length === 0 ||
                    $('input[name="contenidos[]"]:checked').length === 0 ||
                    $('input[name="estrategias_met[]"]:checked').length === 0 ||
                    $('input[name="estrategias_eval[]"]:checked').length === 0 ||
                    $('input[name="recursos[]"]:checked').length === 0) {
                    e.preventDefault();
                    mostrarToast('Debe seleccionar al menos un elemento por categoría.', 'warning');
                }
            });

            // Mostrar toast
            function mostrarToast(mensaje, tipo) {
                $('#toastNotificacion').removeClass('bg-success bg-danger bg-warning')
                    .addClass(`bg-${tipo}`)
                    .find('.toast-body').text(mensaje);
                $('#toastNotificacion').toast('show');
            }
        });
    </script>
</body>
</html>