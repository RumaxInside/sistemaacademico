<?php
require_once '../config/database.php';
verificarRol('coordinador');

$database = new Database();
$db = $database->getConnection();

if (!isset($_POST['tipo']) || !isset($_POST['codigo_asignatura'])) {
    die('Parámetros faltantes');
}

$tipo = limpiarDatos($_POST['tipo']);
$codigo_asignatura = limpiarDatos($_POST['codigo_asignatura']);

$tablas = [
    'objetivos' => 'objetivos_clase',
    'contenidos' => 'contenidos',
    'estrategias_metodologicas' => 'estrategias_metodologicas',
    'estrategias_evaluativas' => 'estrategias_evaluativas',
    'recursos' => 'recursos'
];

if (!isset($tablas[$tipo])) {
    die('Tipo inválido');
}

$tabla = $tablas[$tipo];

// Obtener elementos
$query = "SELECT * FROM $tabla WHERE codigo_asignatura = ? ORDER BY descripcion";
$stmt = $db->prepare($query);
$stmt->execute([$codigo_asignatura]);
$elementos = $stmt->fetchAll();

// Generar HTML
if (empty($elementos)) {
    echo '<div class="text-center text-muted py-4">';
    echo '<i class="fas fa-inbox fa-3x mb-3"></i>';
    echo '<p>No hay elementos registrados</p>';
    echo '</div>';
} else {
    foreach ($elementos as $elemento) {
        echo '<div class="elemento-item">';
        echo '<span>' . htmlspecialchars($elemento['descripcion']) . '</span>';
        echo '<button class="btn btn-danger btn-sm" onclick="eliminarElemento(\'' . $tipo . '\', ' . $elemento['id'] . ')">';
        echo '<i class="fas fa-trash"></i>';
        echo '</button>';
        echo '</div>';
    }
}
?>