<?php
require_once '../config/database.php';
verificarRol('coordinador');

$database = new Database();
$db = $database->getConnection();

if (!isset($_POST['plan_id'])) {
    echo '<div class="alert alert-danger">Plan no especificado</div>';
    exit();
}

$plan_id = (int)$_POST['plan_id'];

// Obtener información del plan
$query = "SELECT pd.*, a.nombre as asignatura_nombre, a.codigo_asignatura, u.nombre_completo as docente_nombre
          FROM planes_didacticos pd 
          INNER JOIN asignaturas a ON pd.asignatura_id = a.id 
          INNER JOIN usuarios u ON pd.docente_id = u.id
          WHERE pd.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$plan_id]);
$plan = $stmt->fetch();

if (!$plan) {
    echo '<div class="alert alert-danger">Plan no encontrado</div>';
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
?>

<div class="row">
    <div class="col-12 mb-3">
        <div class="card border-left-primary">
            <div class="card-body">
                <h5 class="card-title text-primary">
                    <?php echo $plan['codigo_asignatura']; ?> - <?php echo $plan['asignatura_nombre']; ?>
                </h5>
                <p class="card-text">
                    <strong>Docente:</strong> <?php echo $plan['docente_nombre']; ?><br>
                    <strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($plan['fecha_clase'])); ?><br>
                    <strong>Tipo:</strong> 
                    <span class="badge badge-<?php echo $plan['tipo_clase'] === 'regular' ? 'primary' : 'warning'; ?>">
                        <?php echo ucfirst($plan['tipo_clase']); ?>
                    </span><br>
                    <strong>Estado:</strong> 
                    <span class="badge badge-<?php echo $plan['estado'] === 'ejecutado' ? 'success' : 'info'; ?>">
                        <?php echo ucfirst($plan['estado']); ?>
                    </span>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Objetivos -->
    <?php if (!empty($objetivos)): ?>
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">Objetivos de la Clase</h6>
            </div>
            <div class="card-body">
                <?php foreach ($objetivos as $objetivo): ?>
                    <div class="d-flex justify-content-between align-items-start mb-2 p-2 bg-light rounded">
                        <span><?php echo $objetivo['descripcion']; ?></span>
                        <?php if ($plan['estado'] === 'ejecutado'): ?>
                            <span class="badge badge-<?php echo $objetivo['estado'] === 'logrado' ? 'success' : ($objetivo['estado'] === 'no_logrado' ? 'danger' : 'secondary'); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $objetivo['estado'])); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Contenidos -->
    <?php if (!empty($contenidos)): ?>
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0">Contenidos</h6>
            </div>
            <div class="card-body">
                <?php foreach ($contenidos as $contenido): ?>
                    <div class="d-flex justify-content-between align-items-start mb-2 p-2 bg-light rounded">
                        <span><?php echo $contenido['descripcion']; ?></span>
                        <?php if ($plan['estado'] === 'ejecutado'): ?>
                            <span class="badge badge-<?php echo $contenido['estado'] === 'terminado' ? 'success' : ($contenido['estado'] === 'sin_concluir' ? 'warning' : 'secondary'); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $contenido['estado'])); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Estrategias Metodológicas -->
    <?php if (!empty($estrategias_metodologicas)): ?>
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">Estrategias Metodológicas</h6>
            </div>
            <div class="card-body">
                <?php foreach ($estrategias_metodologicas as $estrategia): ?>
                    <div class="d-flex justify-content-between align-items-start mb-2 p-2 bg-light rounded">
                        <span><?php echo $estrategia['descripcion']; ?></span>
                        <?php if ($plan['estado'] === 'ejecutado'): ?>
                            <span class="badge badge-<?php echo $estrategia['realizado'] ? 'success' : 'secondary'; ?>">
                                <?php echo $estrategia['realizado'] ? 'Realizado' : 'No Realizado'; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Estrategias Evaluativas -->
    <?php if (!empty($estrategias_evaluativas)): ?>
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header bg-warning text-white">
                <h6 class="mb-0">Estrategias Evaluativas</h6>
            </div>
            <div class="card-body">
                <?php foreach ($estrategias_evaluativas as $estrategia): ?>
                    <div class="d-flex justify-content-between align-items-start mb-2 p-2 bg-light rounded">
                        <span><?php echo $estrategia['descripcion']; ?></span>
                        <?php if ($plan['estado'] === 'ejecutado'): ?>
                            <span class="badge badge-<?php echo $estrategia['realizado'] ? 'success' : 'secondary'; ?>">
                                <?php echo $estrategia['realizado'] ? 'Realizado' : 'No Realizado'; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recursos -->
    <?php if (!empty($recursos)): ?>
    <div class="col-md-12 mb-3">
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0">Recursos Utilizados</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($recursos as $recurso): ?>
                        <div class="col-md-6 mb-2">
                            <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                <span><?php echo $recurso['descripcion']; ?></span>
                                <?php if ($plan['estado'] === 'ejecutado'): ?>
                                    <span class="badge badge-<?php echo $recurso['utilizado'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $recurso['utilizado'] ? 'Utilizado' : 'No Utilizado'; ?>
                                    </span>
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

<?php if ($plan['estado'] === 'planificado'): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Esta clase aún no ha sido ejecutada por el docente.
    </div>
<?php endif; ?>