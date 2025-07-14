<?php
require_once '../config/database.php';
verificarRol('docente');

$database = new Database();
$db = $database->getConnection();

// Verificar datos recibidos
if (!isset($_POST['plan_id'])) {
    die('Plan no especificado');
}

$plan_id = (int)$_POST['plan_id'];

// Obtener información del plan
$query = "SELECT pd.*, a.nombre as asignatura_nombre, a.codigo_asignatura, a.semestre, a.carga_horaria
          FROM planes_didacticos pd 
          INNER JOIN asignaturas a ON pd.asignatura_id = a.id 
          WHERE pd.id = ? AND pd.docente_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$plan_id, $_SESSION['usuario_id']]);
$plan = $stmt->fetch();

if (!$plan) {
    die('Plan no encontrado o sin permisos');
}

// Obtener configuración del sistema
$query = "SELECT valor FROM configuracion WHERE clave = 'nombre_institucion'";
$stmt = $db->prepare($query);
$stmt->execute();
$institucion = $stmt->fetch();
$nombre_institucion = $institucion ? $institucion['valor'] : 'Instituto de Educación Superior';

// Obtener elementos seleccionados
$elementos_seleccionados = [
    'objetivos' => isset($_POST['objetivos']) ? $_POST['objetivos'] : [],
    'contenidos' => isset($_POST['contenidos']) ? $_POST['contenidos'] : [],
    'estrategias_metodologicas' => isset($_POST['estrategias_metodologicas']) ? $_POST['estrategias_metodologicas'] : [],
    'estrategias_evaluativas' => isset($_POST['estrategias_evaluativas']) ? $_POST['estrategias_evaluativas'] : [],
    'recursos' => isset($_POST['recursos']) ? $_POST['recursos'] : []
];

// Función para obtener elementos
function obtenerElementos($db, $tabla, $ids) {
    if (empty($ids)) return [];
    
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $query = "SELECT descripcion FROM $tabla WHERE id IN ($placeholders) ORDER BY descripcion";
    $stmt = $db->prepare($query);
    $stmt->execute($ids);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Obtener todos los elementos
$objetivos = obtenerElementos($db, 'objetivos_clase', $elementos_seleccionados['objetivos']);
$contenidos = obtenerElementos($db, 'contenidos', $elementos_seleccionados['contenidos']);
$estrategias_met = obtenerElementos($db, 'estrategias_metodologicas', $elementos_seleccionados['estrategias_metodologicas']);
$estrategias_eval = obtenerElementos($db, 'estrategias_evaluativas', $elementos_seleccionados['estrategias_evaluativas']);
$recursos = obtenerElementos($db, 'recursos', $elementos_seleccionados['recursos']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Plan Didáctico - <?php echo $plan['codigo_asignatura']; ?></title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
            .page-break { page-break-after: always; }
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            border: 2px solid #333;
        }
        
        .header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 24px;
        }
        
        .header h2 {
            margin: 10px 0 0 0;
            color: #34495e;
            font-size: 20px;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .info-table td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        
        .info-table td:first-child {
            background-color: #f8f9fa;
            font-weight: bold;
            width: 30%;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section-title {
            background-color: #3498db;
            color: white;
            padding: 10px;
            margin: 0;
            font-size: 16px;
            text-transform: uppercase;
        }
        
        .section-content {
            border: 1px solid #3498db;
            border-top: none;
            padding: 15px;
        }
        
        .section-content ol {
            margin: 0;
            padding-left: 20px;
        }
        
        .section-content li {
            margin-bottom: 8px;
        }
        
        .signatures {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            text-align: center;
            width: 45%;
        }
        
        .signature-line {
            border-bottom: 2px solid #333;
            margin-bottom: 5px;
            height: 40px;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }
        
        .btn-actions {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 10px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.3s;
        }
        
        .btn:hover {
            opacity: 0.8;
        }
        
        .btn-primary {
            background-color: #3498db;
        }
        
        .btn-success {
            background-color: #2ecc71;
        }
        
        .btn-secondary {
            background-color: #95a5a6;
        }
        
        /* Colores para cada sección */
        .section-objetivos .section-title { background-color: #3498db; }
        .section-objetivos .section-content { border-color: #3498db; }
        
        .section-contenidos .section-title { background-color: #2ecc71; }
        .section-contenidos .section-content { border-color: #2ecc71; }
        
        .section-metodologicas .section-title { background-color: #1abc9c; }
        .section-metodologicas .section-content { border-color: #1abc9c; }
        
        .section-evaluativas .section-title { background-color: #f39c12; }
        .section-evaluativas .section-content { border-color: #f39c12; }
        
        .section-recursos .section-title { background-color: #7f8c8d; }
        .section-recursos .section-content { border-color: #7f8c8d; }
    </style>
</head>
<body>
    <!-- Botones de acción -->
    <div class="btn-actions no-print">
        <button class="btn btn-primary" onclick="window.print()">
            <i class="fas fa-print"></i> Imprimir / Guardar PDF
        </button>
        <button class="btn btn-success" onclick="descargarPDF()">
            <i class="fas fa-download"></i> Descargar PDF
        </button>
        <button class="btn btn-secondary" onclick="window.close()">
            <i class="fas fa-times"></i> Cerrar
        </button>
    </div>

    <!-- Contenido del documento -->
    <div id="contenido-pdf">
        <!-- Header -->
        <div class="header">
            <h1><?php echo strtoupper($nombre_institucion); ?></h1>
            <h2>PLAN DIDÁCTICO</h2>
        </div>

        <!-- Información básica -->
        <table class="info-table">
            <tr>
                <td>Asignatura:</td>
                <td><?php echo $plan['codigo_asignatura'] . ' - ' . $plan['asignatura_nombre']; ?></td>
            </tr>
            <tr>
                <td>Docente:</td>
                <td><?php echo $_SESSION['usuario_nombre']; ?></td>
            </tr>
            <tr>
                <td>Fecha de Clase:</td>
                <td><?php echo date('d/m/Y', strtotime($plan['fecha_clase'])); ?></td>
            </tr>
            <tr>
                <td>Tipo de Clase:</td>
                <td><?php echo ucfirst($plan['tipo_clase']); ?></td>
            </tr>
            <tr>
                <td>Semestre:</td>
                <td><?php echo $plan['semestre']; ?></td>
            </tr>
            <tr>
                <td>Carga Horaria:</td>
                <td><?php echo $plan['carga_horaria']; ?> horas</td>
            </tr>
        </table>

        <!-- Objetivos -->
        <?php if (!empty($objetivos)): ?>
        <div class="section section-objetivos">
            <h3 class="section-title">OBJETIVOS DE LA CLASE</h3>
            <div class="section-content">
                <ol>
                    <?php foreach ($objetivos as $objetivo): ?>
                        <li><?php echo htmlspecialchars($objetivo); ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
        <?php endif; ?>

        <!-- Contenidos -->
        <?php if (!empty($contenidos)): ?>
        <div class="section section-contenidos">
            <h3 class="section-title">CONTENIDOS</h3>
            <div class="section-content">
                <ol>
                    <?php foreach ($contenidos as $contenido): ?>
                        <li><?php echo htmlspecialchars($contenido); ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
        <?php endif; ?>

        <!-- Estrategias Metodológicas -->
        <?php if (!empty($estrategias_met)): ?>
        <div class="section section-metodologicas">
            <h3 class="section-title">ESTRATEGIAS METODOLÓGICAS</h3>
            <div class="section-content">
                <ol>
                    <?php foreach ($estrategias_met as $estrategia): ?>
                        <li><?php echo htmlspecialchars($estrategia); ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
        <?php endif; ?>

        <!-- Estrategias Evaluativas -->
        <?php if (!empty($estrategias_eval)): ?>
        <div class="section section-evaluativas">
            <h3 class="section-title">ESTRATEGIAS EVALUATIVAS</h3>
            <div class="section-content">
                <ol>
                    <?php foreach ($estrategias_eval as $estrategia): ?>
                        <li><?php echo htmlspecialchars($estrategia); ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recursos -->
        <?php if (!empty($recursos)): ?>
        <div class="section section-recursos">
            <h3 class="section-title">RECURSOS</h3>
            <div class="section-content">
                <ol>
                    <?php foreach ($recursos as $recurso): ?>
                        <li><?php echo htmlspecialchars($recurso); ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
        <?php endif; ?>

        <!-- Firmas -->
        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line"></div>
                <p><?php echo $_SESSION['usuario_nombre']; ?><br>Docente</p>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <p>Coordinador Académico<br>Sello y Firma</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Generado el <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        function descargarPDF() {
            const elemento = document.getElementById('contenido-pdf');
            const opciones = {
                margin: 10,
                filename: 'Plan_Didactico_<?php echo $plan['codigo_asignatura']; ?>_<?php echo date('Ymd', strtotime($plan['fecha_clase'])); ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            // Usar html2canvas y jsPDF
            html2canvas(elemento).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jspdf.jsPDF();
                const imgWidth = 210;
                const pageHeight = 295;
                const imgHeight = canvas.height * imgWidth / canvas.width;
                let heightLeft = imgHeight;

                let position = 0;

                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;

                while (heightLeft >= 0) {
                    position = heightLeft - imgHeight;
                    pdf.addPage();
                    pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }

                pdf.save(opciones.filename);
            });
        }

        // Auto-imprimir si se requiere
        window.onload = function() {
            // Opcional: window.print();
        };
    </script>
</body>
</html>