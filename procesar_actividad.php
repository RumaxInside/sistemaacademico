<?php
require_once '../config/database.php';
verificarRol('docente');

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mostrarMensaje('error', 'Método no permitido');
    header('Location: dashboard.php');
    exit();
}

if (!isset($_POST['plan_id']) || empty($_POST['plan_id'])) {
    mostrarMensaje('error', 'Plan no especificado');
    header('Location: dashboard.php');
    exit();
}

$plan_id = (int)$_POST['plan_id'];

try {
    // Verificar que el plan pertenece al docente
    $query = "SELECT pd.*, a.nombre as asignatura_nombre, a.codigo_asignatura 
              FROM planes_didacticos pd 
              INNER JOIN asignaturas a ON pd.asignatura_id = a.id 
              WHERE pd.id = ? AND pd.docente_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$plan_id, $_SESSION['usuario_id']]);
    $plan = $stmt->fetch();
    
    if (!$plan) {
        mostrarMensaje('error', 'Plan no encontrado o no tienes permisos para modificarlo');
        header('Location: dashboard.php');
        exit();
    }

    // Iniciar transacción
    $db->beginTransaction();
    
    // Contadores para el resumen
    $actualizaciones = [
        'objetivos' => ['total' => 0, 'logrados' => 0, 'no_logrados' => 0],
        'contenidos' => ['total' => 0, 'terminados' => 0, 'sin_concluir' => 0],
        'estrategias_metodologicas' => ['total' => 0, 'realizadas' => 0],
        'estrategias_evaluativas' => ['total' => 0, 'realizadas' => 0]
    ];

    // Procesar objetivos
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'objetivo_') === 0) {
            $objetivo_id = str_replace('objetivo_', '', $key);
            $estado = limpiarDatos($value);
            
            // Validar estado
            if (!in_array($estado, ['logrado', 'no_logrado', 'pendiente'])) {
                $estado = 'pendiente';
            }
            
            // Actualizar estado del objetivo
            $query = "UPDATE plan_objetivos SET estado = ? WHERE plan_id = ? AND objetivo_id = ?";
            $stmt = $db->prepare($query);
            $resultado = $stmt->execute([$estado, $plan_id, $objetivo_id]);
            
            if ($resultado) {
                $actualizaciones['objetivos']['total']++;
                if ($estado === 'logrado') {
                    $actualizaciones['objetivos']['logrados']++;
                } elseif ($estado === 'no_logrado') {
                    $actualizaciones['objetivos']['no_logrados']++;
                }
            }
        }
        
        // Procesar contenidos
        elseif (strpos($key, 'contenido_') === 0) {
            $contenido_id = str_replace('contenido_', '', $key);
            $estado = limpiarDatos($value);
            
            // Validar estado
            if (!in_array($estado, ['terminado', 'sin_concluir', 'pendiente'])) {
                $estado = 'pendiente';
            }
            
            // Actualizar estado del contenido
            $query = "UPDATE plan_contenidos SET estado = ? WHERE plan_id = ? AND contenido_id = ?";
            $stmt = $db->prepare($query);
            $resultado = $stmt->execute([$estado, $plan_id, $contenido_id]);
            
            if ($resultado) {
                $actualizaciones['contenidos']['total']++;
                if ($estado === 'terminado') {
                    $actualizaciones['contenidos']['terminados']++;
                } elseif ($estado === 'sin_concluir') {
                    $actualizaciones['contenidos']['sin_concluir']++;
                }
            }
        }
        
        // Procesar estrategias metodológicas
        elseif (strpos($key, 'est_met_') === 0) {
            $estrategia_id = str_replace('est_met_', '', $key);
            $realizado = ($value === '1') ? 1 : 0;
            
            // Actualizar estado de la estrategia metodológica
            $query = "UPDATE plan_estrategias_metodologicas SET realizado = ? WHERE plan_id = ? AND estrategia_id = ?";
            $stmt = $db->prepare($query);
            $resultado = $stmt->execute([$realizado, $plan_id, $estrategia_id]);
            
            if ($resultado) {
                $actualizaciones['estrategias_metodologicas']['total']++;
                if ($realizado) {
                    $actualizaciones['estrategias_metodologicas']['realizadas']++;
                }
            }
        }
        
        // Procesar estrategias evaluativas
        elseif (strpos($key, 'est_eval_') === 0) {
            $estrategia_id = str_replace('est_eval_', '', $key);
            $realizado = ($value === '1') ? 1 : 0;
            
            // Actualizar estado de la estrategia evaluativa
            $query = "UPDATE plan_estrategias_evaluativas SET realizado = ? WHERE plan_id = ? AND estrategia_id = ?";
            $stmt = $db->prepare($query);
            $resultado = $stmt->execute([$realizado, $plan_id, $estrategia_id]);
            
            if ($resultado) {
                $actualizaciones['estrategias_evaluativas']['total']++;
                if ($realizado) {
                    $actualizaciones['estrategias_evaluativas']['realizadas']++;
                }
            }
        }
    }

    // Marcar el plan como ejecutado y actualizar fecha de ejecución
    $query = "UPDATE planes_didacticos SET estado = 'ejecutado' WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$plan_id]);

    // Opcional: Crear un registro de log de la actividad
    $resumen_actividad = json_encode([
        'plan_id' => $plan_id,
        'docente_id' => $_SESSION['usuario_id'],
        'fecha_registro' => date('Y-m-d H:i:s'),
        'actualizaciones' => $actualizaciones,
        'asignatura' => $plan['codigo_asignatura'],
        'fecha_clase' => $plan['fecha_clase']
    ]);

    // Verificar si existe tabla de logs (opcional)
    try {
        $query = "INSERT INTO logs_actividades (plan_id, docente_id, resumen, fecha_registro) VALUES (?, ?, ?, NOW())";
        $stmt = $db->prepare($query);
        $stmt->execute([$plan_id, $_SESSION['usuario_id'], $resumen_actividad]);
    } catch (Exception $e) {
        // Si no existe la tabla de logs, continuar sin error
        // Esto es opcional y no debe afectar el funcionamiento principal
    }

    // Confirmar transacción
    $db->commit();
    
    // Crear mensaje de éxito detallado
    $mensaje_exito = "✅ Actividad registrada correctamente para {$plan['codigo_asignatura']} - {$plan['asignatura_nombre']}\n";
    
    if ($actualizaciones['objetivos']['total'] > 0) {
        $mensaje_exito .= "📌 Objetivos: {$actualizaciones['objetivos']['logrados']} logrados, {$actualizaciones['objetivos']['no_logrados']} no logrados\n";
    }
    
    if ($actualizaciones['contenidos']['total'] > 0) {
        $mensaje_exito .= "📚 Contenidos: {$actualizaciones['contenidos']['terminados']} terminados, {$actualizaciones['contenidos']['sin_concluir']} sin concluir\n";
    }
    
    if ($actualizaciones['estrategias_metodologicas']['total'] > 0) {
        $mensaje_exito .= "🔧 Est. Metodológicas: {$actualizaciones['estrategias_metodologicas']['realizadas']}/{$actualizaciones['estrategias_metodologicas']['total']} realizadas\n";
    }
    
    if ($actualizaciones['estrategias_evaluativas']['total'] > 0) {
        $mensaje_exito .= "📊 Est. Evaluativas: {$actualizaciones['estrategias_evaluativas']['realizadas']}/{$actualizaciones['estrategias_evaluativas']['total']} realizadas";
    }
    
    mostrarMensaje('exito', $mensaje_exito);
    
} catch (Exception $e) {
    // Rollback en caso de error
    $db->rollback();
    
    // Log del error para debugging
    error_log("Error en procesar_actividad.php: " . $e->getMessage());
    error_log("Plan ID: " . $plan_id);
    error_log("Usuario: " . $_SESSION['usuario_id']);
    error_log("POST data: " . print_r($_POST, true));
    
    mostrarMensaje('error', 'Error al registrar la actividad: ' . $e->getMessage());
}

// Redirigir al dashboard
header('Location: dashboard.php');
exit();
?>