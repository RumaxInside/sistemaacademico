<?php
require_once '../config/database.php';
verificarRol('docente');

$database = new Database();
$db = $database->getConnection();

if (!isset($_POST['fecha'])) {
    echo '<div class="alert alert-danger">Fecha no especificada</div>';
    exit();
}

$fecha = $_POST['fecha'];
$plan_id = isset($_POST['plan_id']) ? (int)$_POST['plan_id'] : null;

// Obtener planes para esa fecha
$query = "SELECT pd.*, a.nombre as asignatura_nombre, a.codigo_asignatura 
          FROM planes_didacticos pd 
          INNER JOIN asignaturas a ON pd.asignatura_id = a.id 
          WHERE pd.docente_id = ? AND pd.fecha_clase = ?
          ORDER BY a.nombre";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['usuario_id'], $fecha]);
$planes = $stmt->fetchAll();

if (empty($planes)) {
    echo '<div class="alert alert-info text-center">';
    echo '<i class="fas fa-info-circle fa-2x mb-3"></i>';
    echo '<h5>No hay clases planificadas para esta fecha</h5>';
    echo '<p class="mb-3">¿Deseas planificar una clase para el ' . date('d/m/Y', strtotime($fecha)) . '?</p>';
    echo '<button class="btn btn-primary" data-dismiss="modal" data-toggle="modal" data-target="#modalPlanificar">';
    echo '<i class="fas fa-plus"></i> Planificar Clase';
    echo '</button>';
    echo '</div>';
    exit();
}

// Mostrar información de la fecha
echo '<div class="mb-3">';
echo '<h6 class="text-primary"><i class="fas fa-calendar"></i> Clases del ' . date('d/m/Y', strtotime($fecha)) . '</h6>';
echo '</div>';

foreach ($planes as $plan) {
    $card_class = '';
    $badge_class = '';
    $icon = '';
    
    switch ($plan['estado']) {
        case 'planificado':
            $card_class = 'border-left-primary';
            $badge_class = 'badge-primary';
            $icon = 'fas fa-clock';
            break;
        case 'ejecutado':
            $card_class = 'border-left-success';
            $badge_class = 'badge-success';
            $icon = 'fas fa-check-circle';
            break;
    }
    
    echo '<div class="card ' . $card_class . ' mb-3">';
    echo '<div class="card-header d-flex justify-content-between align-items-center">';
    echo '<div>';
    echo '<h6 class="mb-0">';
    echo '<i class="fas fa-book text-primary"></i> ';
    echo $plan['codigo_asignatura'] . ' - ' . $plan['asignatura_nombre'];
    echo '</h6>';
    echo '</div>';
    echo '<div>';
    echo '<span class="badge badge-' . ($plan['tipo_clase'] === 'regular' ? 'primary' : 'warning') . ' mr-2">';
    echo '<i class="fas fa-tag"></i> ' . ucfirst($plan['tipo_clase']);
    echo '</span>';
    echo '<span class="badge ' . $badge_class . '">';
    echo '<i class="' . $icon . '<?php
require_once '../config/database.php';
verificarRol('docente');

$database = new Database();
$db = $database->getConnection();

if (!isset($_POST['fecha'])) {
    echo '<div class="alert alert-danger">Fecha no especificada</div>';
    exit();
}

$fecha = $_POST['fecha'];

// Obtener planes para esa fecha
$query = "SELECT pd.*, a.nombre as asignatura_nombre, a.codigo_asignatura 
          FROM planes_didacticos pd 
          INNER JOIN asignaturas a ON pd.asignatura_id = a.id 
          WHERE pd.docente_id = ? AND pd.fecha_clase = ?
          ORDER BY a.nombre";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['usuario_id'], $fecha]);
$planes = $stmt->fetchAll();

if (empty($planes)) {
    echo '<div class="alert alert-info">No hay clases planificadas para esta fecha.</div>';
    exit();
}

echo '<div class="row">';
foreach ($planes as $plan) {
    echo '<div class="col-md-12 mb-3">';
    echo '<div class="card">';
    echo '<div class="card-header d-flex justify-content-between align-items-center">';
    echo '<h6 class="mb-0">' . $plan['codigo_asignatura'] . ' - ' . $plan['asignatura_nombre'] . '</h6>';
    echo '<span class="badge badge-' . ($plan['tipo_clase'] === 'regular' ? 'primary' : 'warning') . '">' . ucfirst($plan['tipo_clase']) . '</span>';
    echo '</div>';
    echo '<div class="card-body">';
    echo '<p><strong>Estado:</strong> ' . ucfirst($plan['estado']) . '</p>';
    
    if ($plan['estado'] === 'planificado') {
        echo '<a href="registrar_actividad.php?plan_id=' . $plan['id'] . '" class="btn btn-success btn-sm">';
        echo '<i class="fas fa-play"></i> Registrar Actividad';
        echo '</a>';
    } else {
        echo '<a href="ver_detalles_plan.php?plan_id=' . $plan['id'] . '" class="btn btn-info btn-sm">';
        echo '<i class="fas fa-eye"></i> Ver Detalles';
        echo '</a>';
    }
    
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
echo '</div>';
?>