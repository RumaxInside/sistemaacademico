<?php
require_once '../config/database.php';
verificarRol('coordinador');

$database = new Database();
$db = $database->getConnection();

header('Content-Type: application/json');

// Obtener parámetros
$docente_id = isset($_POST['docente_id']) ? $_POST['docente_id'] : '';
$asignatura_id = isset($_POST['asignatura_id']) ? $_POST['asignatura_id'] : '';
$periodo = isset($_POST['periodo']) ? $_POST['periodo'] : 'mensual';

// Construir condiciones WHERE
$where_conditions = [];
$params = [];

if (!empty($docente_id)) {
    $where_conditions[] = "pd.docente_id = ?";
    $params[] = $docente_id;
}

if (!empty($asignatura_id)) {
    $where_conditions[] = "pd.asignatura_id = ?";
    $params[] = $asignatura_id;
}

// Agregar filtro de período
$fecha_inicio = '';
$fecha_fin = date('Y-m-d');

switch ($periodo) {
    case 'semanal':
        $fecha_inicio = date('Y-m-d', strtotime('-1 week'));
        break;
    case 'mensual':
        $fecha_inicio = date('Y-m-d', strtotime('-1 month'));
        break;
    case 'semestral':
        $fecha_inicio = date('Y-m-d', strtotime('-6 months'));
        break;
}

$where_conditions[] = "pd.fecha_clase BETWEEN ? AND ?";
$params[] = $fecha_inicio;
$params[] = $fecha_fin;

$where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Inicializar respuesta
$response = [
    'total_clases' => 0,
    'clases_ejecutadas' => 0,
    'total_objetivos' => 0,
    'objetivos_logrados' => 0,
    'objetivos_no_logrados' => 0,
    'porcentaje_objetivos' => 0,
    'total_contenidos' => 0,
    'contenidos_terminados' => 0,
    'contenidos_sin_concluir' => 0,
    'porcentaje_contenidos' => 0,
    'total_est_met' => 0,
    'est_met_realizadas' => 0,
    'porcentaje_est_met' => 0,
    'total_est_eval' => 0,
    'est_eval_realizadas' => 0,
    'porcentaje_est_eval' => 0
];

try {
    // Total de clases
    $query = "SELECT COUNT(*) as total FROM planes_didacticos pd $where_sql";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $response['total_clases'] = $stmt->fetch()['total'];

    // Clases ejecutadas
    $where_ejecutadas = $where_sql ? $where_sql . " AND pd.estado = 'ejecutado'" : "WHERE pd.estado = 'ejecutado'";
    $query = "SELECT COUNT(*) as total FROM planes_didacticos pd $where_ejecutadas";
    $stmt = $db->prepare($query);
    $params_ejecutadas = $params;
    if (!$where_sql) {
        $params_ejecutadas = [$fecha_inicio, $fecha_fin];
    }
    $stmt->execute($params_ejecutadas);
    $response['clases_ejecutadas'] = $stmt->fetch()['total'];

    // Objetivos
    $query = "SELECT 
              COUNT(DISTINCT po.id) as total,
              SUM(CASE WHEN po.estado = 'logrado' THEN 1 ELSE 0 END) as logrados,
              SUM(CASE WHEN po.estado = 'no_logrado' THEN 1 ELSE 0 END) as no_logrados
              FROM plan_objetivos po
              INNER JOIN planes_didacticos pd ON po.plan_id = pd.id
              $where_ejecutadas";
    $stmt = $db->prepare($query);
    $stmt->execute($params_ejecutadas);
    $objetivos = $stmt->fetch();
    
    $response['total_objetivos'] = $objetivos['total'];
    $response['objetivos_logrados'] = $objetivos['logrados'];
    $response['objetivos_no_logrados'] = $objetivos['no_logrados'];
    
    if ($objetivos['total'] > 0) {
        $response['porcentaje_objetivos'] = round(($objetivos['logrados'] / $objetivos['total']) * 100, 1);
    }

    // Contenidos
    $query = "SELECT 
              COUNT(DISTINCT pc.id) as total,
              SUM(CASE WHEN pc.estado = 'terminado' THEN 1 ELSE 0 END) as terminados,
              SUM(CASE WHEN pc.estado = 'sin_concluir' THEN 1 ELSE 0 END) as sin_concluir
              FROM plan_contenidos pc
              INNER JOIN planes_didacticos pd ON pc.plan_id = pd.id
              $where_ejecutadas";
    $stmt = $db->prepare($query);
    $stmt->execute($params_ejecutadas);
    $contenidos = $stmt->fetch();
    
    $response['total_contenidos'] = $contenidos['total'];
    $response['contenidos_terminados'] = $contenidos['terminados'];
    $response['contenidos_sin_concluir'] = $contenidos['sin_concluir'];
    
    if ($contenidos['total'] > 0) {
        $response['porcentaje_contenidos'] = round(($contenidos['terminados'] / $contenidos['total']) * 100, 1);
    }

    // Estrategias Metodológicas
    $query = "SELECT 
              COUNT(DISTINCT pem.id) as total,
              SUM(CASE WHEN pem.realizado = 1 THEN 1 ELSE 0 END) as realizadas
              FROM plan_estrategias_metodologicas pem
              INNER JOIN planes_didacticos pd ON pem.plan_id = pd.id
              $where_ejecutadas";
    $stmt = $db->prepare($query);
    $stmt->execute($params_ejecutadas);
    $est_met = $stmt->fetch();
    
    $response['total_est_met'] = $est_met['total'];
    $response['est_met_realizadas'] = $est_met['realizadas'];
    
    if ($est_met['total'] > 0) {
        $response['porcentaje_est_met'] = round(($est_met['realizadas'] / $est_met['total']) * 100, 1);
    }

    // Estrategias Evaluativas
    $query = "SELECT 
              COUNT(DISTINCT pee.id) as total,
              SUM(CASE WHEN pee.realizado = 1 THEN 1 ELSE 0 END) as realizadas
              FROM plan_estrategias_evaluativas pee
              INNER JOIN planes_didacticos pd ON pee.plan_id = pd.id
              $where_ejecutadas";
    $stmt = $db->prepare($query);
    $stmt->execute($params_ejecutadas);
    $est_eval = $stmt->fetch();
    
    $response['total_est_eval'] = $est_eval['total'];
    $response['est_eval_realizadas'] = $est_eval['realizadas'];
    
    if ($est_eval['total'] > 0) {
        $response['porcentaje_est_eval'] = round(($est_eval['realizadas'] / $est_eval['total']) * 100, 1);
    }

} catch (Exception $e) {
    $response['error'] = 'Error al obtener datos: ' . $e->getMessage();
}

echo json_encode($response);
?>