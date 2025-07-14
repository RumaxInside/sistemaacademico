<?php
session_start();
require_once '../config/database.php';

// Verificar sesión y rol
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'docente') {
    header('Location: ../acceso_denegado.php');
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

// Obtener datos del formulario
$plan_id = isset($_POST['plan_id']) ? intval($_POST['plan_id']) : 0;
$objetivos = isset($_POST['objetivos']) ? array_map('intval', $_POST['objetivos']) : [];
$contenidos = isset($_POST['contenidos']) ? array_map('intval', $_POST['contenidos']) : [];
$estrategias_met = isset($_POST['estrategias_met']) ? array_map('intval', $_POST['estrategias_met']) : [];
$estrategias_eval = isset($_POST['estrategias_eval']) ? array_map('intval', $_POST['estrategias_eval']) : [];
$recursos = isset($_POST['recursos']) ? array_map('intval', $_POST['recursos']) : [];

if ($plan_id <= 0) {
    $_SESSION['mensaje'] = ['tipo' => 'danger', 'texto' => 'Error: Plan no especificado.'];
    header('Location: dashboard.php');
    exit;
}

// Conectar a la base de datos
$conn = conectarDB();

// Validar que el plan existe y pertenece al docente
$query = "SELECT id FROM planes_didacticos WHERE id = ? AND docente_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $plan_id, $_SESSION['user_id']);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $_SESSION['mensaje'] = ['tipo' => 'danger', 'texto' => 'Error: Plan no válido o no autorizado.'];
    header('Location: dashboard.php');
    exit;
}

// Iniciar transacción
$conn->begin_transaction();

try {
    // Limpiar elementos anteriores del plan
    $conn->query("DELETE FROM plan_objetivos WHERE plan_id = $plan_id");
    $conn->query("DELETE FROM plan_contenidos WHERE plan_id = $plan_id");
    $conn->query("DELETE FROM plan_estrategias_metodologicas WHERE plan_id = $plan_id");
    $conn->query("DELETE FROM plan_estrategias_evaluativas WHERE plan_id = $plan_id");
    $conn->query("DELETE FROM plan_recursos WHERE plan_id = $plan_id");

    // Insertar objetivos
    if (!empty($objetivos)) {
        $stmt = $conn->prepare("INSERT INTO plan_objetivos (plan_id, objetivo_id, estado) VALUES (?, ?, 'pendiente')");
        foreach ($objetivos as $obj_id) {
            $stmt->bind_param('ii', $plan_id, $obj_id);
            $stmt->execute();
        }
    }

    // Insertar contenidos
    if (!empty($contenidos)) {
        $stmt = $conn->prepare("INSERT INTO plan_contenidos (plan_id, contenido_id, estado) VALUES (?, ?, 'pendiente')");
        foreach ($contenidos as $cont_id) {
            $stmt->bind_param('ii', $plan_id, $cont_id);
            $stmt->execute();
        }
    }

    // Insertar estrategias metodológicas
    if (!empty($estrategias_met)) {
        $stmt = $conn->prepare("INSERT INTO plan_estrategias_metodologicas (plan_id, estrategia_id, realizado) VALUES (?, ?, 0)");
        foreach ($estrategias_met as $est_id) {
            $stmt->bind_param('ii', $plan_id, $est_id);
            $stmt->execute();
        }
    }

    // Insertar estrategias evaluativas
    if (!empty($estrategias_eval)) {
        $stmt = $conn->prepare("INSERT INTO plan_estrategias_evaluativas (plan_id, estrategia_id, realizado) VALUES (?, ?, 0)");
        foreach ($estrategias_eval as $est_id) {
            $stmt->bind_param('ii', $plan_id, $est_id);
            $stmt->execute();
        }
    }

    // Insertar recursos
    if (!empty($recursos)) {
        $stmt = $conn->prepare("INSERT INTO plan_recursos (plan_id, recurso_id, utilizado) VALUES (?, ?, 0)");
        foreach ($recursos as $rec_id) {
            $stmt->bind_param('ii', $plan_id, $rec_id);
            $stmt->execute();
        }
    }

    // Confirmar transacción
    $conn->commit();
    $_SESSION['mensaje'] = ['tipo' => 'success', 'texto' => 'Plan guardado correctamente.'];
    header('Location: dashboard.php');
    exit;

} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();
    $_SESSION['mensaje'] = ['tipo' => 'danger', 'texto' => 'Error al guardar el plan: ' . $e->getMessage()];
    header('Location: configurar_plan.php?plan_id=' . $plan_id);
    exit;
}
?>