<?php
require_once '../config/database.php';
verificarRol('docente');

$database = new Database();
$db = $database->getConnection();

if ($_POST) {
    $fecha_clase = limpiarDatos($_POST['fecha_clase']);
    $asignatura_id = (int)$_POST['asignatura_id'];
    $tipo_clase = limpiarDatos($_POST['tipo_clase']);
    
    if (empty($fecha_clase) || empty($asignatura_id) || empty($tipo_clase)) {
        mostrarMensaje('error', 'Por favor complete todos los campos');
        header('Location: dashboard.php');
        exit();
    }
    
    // Verificar si ya existe una planificación para esa fecha, asignatura y docente
    $query = "SELECT id FROM planes_didacticos 
              WHERE docente_id = ? AND asignatura_id = ? AND fecha_clase = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['usuario_id'], $asignatura_id, $fecha_clase]);
    
    if ($stmt->fetch()) {
        mostrarMensaje('error', 'Ya existe una planificación para esa fecha y asignatura');
        header('Location: dashboard.php');
        exit();
    }
    
    // Verificar que la asignatura existe
    $query = "SELECT codigo_asignatura, nombre FROM asignaturas WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$asignatura_id]);
    $asignatura = $stmt->fetch();
    
    if (!$asignatura) {
        mostrarMensaje('error', 'La asignatura seleccionada no existe');
        header('Location: dashboard.php');
        exit();
    }
    
    // Crear el plan didáctico
    $query = "INSERT INTO planes_didacticos (docente_id, asignatura_id, fecha_clase, tipo_clase) 
              VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$_SESSION['usuario_id'], $asignatura_id, $fecha_clase, $tipo_clase])) {
        $plan_id = $db->lastInsertId();
        
        mostrarMensaje('exito', 'Planificación creada exitosamente para ' . $asignatura['codigo_asignatura'] . ' - ' . $asignatura['nombre']);
        
        // Redirigir a la página de configuración del plan
        header("Location: configurar_plan.php?plan_id=$plan_id");
        exit();
    } else {
        mostrarMensaje('error', 'Error al crear la planificación');
        header('Location: dashboard.php');
        exit();
    }
}

header('Location: dashboard.php');
exit();
?>