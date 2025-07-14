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
    $elementos_guardados = [
        'objetivos' => 0,
        'contenidos' => 0,
        'estrategias_metodologicas' => 0,
        'estrategias_evaluativas' => 0,
        'recursos' => 0
    ];
    
    // Limpiar selecciones anteriores
    $tablas_limpieza = [
        'plan_objetivos',
        'plan_contenidos', 
        'plan_estrategias_metodologicas',
        'plan_estrategias_evaluativas',
        'plan_recursos'
    ];
    
    foreach ($tablas_limpieza as $tabla) {
        $query = "DELETE FROM $tabla WHERE plan_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$plan_id]);
    }
    
    // Insertar objetivos seleccionados
    if (isset($_POST['objetivos']) && is_array($_POST['objetivos'])) {
        $query = "INSERT INTO plan_objetivos (plan_id, objetivo_id, estado) VALUES (?, ?, 'pendiente')";
        $stmt = $db->prepare($query);
        foreach ($_POST['objetivos'] as $objetivo_id) {
            $objetivo_id = (int)$objetivo_id;
            if ($objetivo_id > 0) {
                $stmt->execute([$plan_id, $objetivo_id]);
                $elementos_guardados['objetivos']++;
            }
        }
    }
    
    // Insertar contenidos seleccionados
    if (isset($_POST['contenidos']) && is_array($_POST['contenidos'])) {
        $query = "INSERT INTO plan_contenidos (plan_id, contenido_id, estado) VALUES (?, ?, 'pendiente')";
        $stmt = $db->prepare($query);
        foreach ($_POST['contenidos'] as $contenido_id) {
            $contenido_id = (int)$contenido_id;
            if ($contenido_id > 0) {
                $stmt->execute([$plan_id, $contenido_id]);
                $elementos_guardados['contenidos']++;
            }
        }
    }
    
    // Insertar estrategias metodológicas seleccionadas
    if (isset($_POST['estrategias_metodologicas']) && is_array($_POST['estrategias_metodologicas'])) {
        $query = "INSERT INTO plan_estrategias_metodologicas (plan_id, estrategia_id, realizado) VALUES (?, ?, 0)";
        $stmt = $db->prepare($query);
        foreach ($_POST['estrategias_metodologicas'] as $estrategia_id) {
            $estrategia_id = (int)$estrategia_id;
            if ($estrategia_id > 0) {
                $stmt->execute([$plan_id, $estrategia_id]);
                $elementos_guardados['estrategias_metodologicas']++;
            }
        }
    }
    
    // Insertar estrategias evaluativas seleccionadas
    if (isset($_POST['estrategias_evaluativas']) && is_array($_POST['estrategias_evaluativas'])) {
        $query = "INSERT INTO plan_estrategias_evaluativas (plan_id, estrategia_id, realizado) VALUES (?, ?, 0)";
        $stmt = $db->prepare($query);
        foreach ($_POST['estrategias_evaluativas'] as $estrategia_id) {
            $estrategia_id = (int)$estrategia_id;
            if ($estrategia_id > 0) {
                $stmt->execute([$plan_id, $estrategia_id]);
                $elementos_guardados['estrategias_evaluativas']++;
            }
        }
    }
    
    // Insertar recursos seleccionados
    if (isset($_POST['recursos']) && is_array($_POST['recursos'])) {
        $query = "INSERT INTO plan_recursos (plan_id, recurso_id, utilizado) VALUES (?, ?, 0)";
        $stmt = $db->prepare($query);
        foreach ($_POST['recursos'] as $recurso_id) {
            $recurso_id = (int)$recurso_id;
            if ($recurso_id > 0) {
                $stmt->execute([$plan_id, $recurso_id]);
                $elementos_guardados['recursos']++;
            }
        }
    }
    
    // Confirmar transacción
    $db->commit();
    
    // Verificar que se guardó al menos un elemento
    $total_elementos = array_sum($elementos_guardados);
    
    if ($total_elementos === 0) {
        mostrarMensaje('error', 'No se seleccionó ningún elemento para el plan didáctico');
        header('Location: configurar_plan.php?plan_id=' . $plan_id);
        exit();
    }
    
    // Crear mensaje de éxito detallado
    $mensaje_exito = "✅ Plan didáctico guardado correctamente para {$plan['codigo_asignatura']} - {$plan['asignatura_nombre']}\n";
    $mensaje_exito .= "📊 Total de elementos configurados: {$total_elementos}\n";
    
    $detalles = [];
    if ($elementos_guardados['objetivos'] > 0) {
        $detalles[] = "{$elementos_guardados['objetivos']} objetivos";
    }
    if ($elementos_guardados['contenidos'] > 0) {
        $detalles[] = "{$elementos_guardados['contenidos']} contenidos";
    }
    if ($elementos_guardados['estrategias_metodologicas'] > 0) {
        $detalles[] = "{$elementos_guardados['estrategias_metodologicas']} est. metodológicas";
    }
    if ($elementos_guardados['estrategias_evaluativas'] > 0) {
        $detalles[] = "{$elementos_guardados['estrategias_evaluativas']} est. evaluativas";
    }
    if ($elementos_guardados['recursos'] > 0) {
        $detalles[] = "{$elementos_guardados['recursos']} recursos";
    }
    
    if (!empty($detalles)) {
        $mensaje_exito .= "📝 Incluye: " . implode(', ', $detalles);
    }
    
    mostrarMensaje('exito', $mensaje_exito);
    
} catch (Exception $e) {
    // Rollback en caso de error
    $db->rollback();
    
    // Log del error para debugging
    error_log("Error en guardar_plan.php: " . $e->getMessage());
    error_log("Plan ID: " . $plan_id);
    error_log("Usuario: " . $_SESSION['usuario_id']);
    error_log("POST data: " . print_r($_POST, true));
    
    mostrarMensaje('error', 'Error al guardar el plan didáctico: ' . $e->getMessage());
    header('Location: configurar_plan.php?plan_id=' . $plan_id);
    exit();
}

// Redirigir al dashboard
header('Location: dashboard.php');
exit();
?>