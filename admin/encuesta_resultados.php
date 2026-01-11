<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/encuestas_functions.php';
verificarRol('admin');

if (!isset($_GET['id'])) { 
    header("Location: encuestas.php"); 
    exit; 
}

$id = intval($_GET['id']);
$enc = obtenerEncuestaPorId($id);
if (!$enc) { 
    header("Location: encuestas.php"); 
    exit; 
}

// Exportar a CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $csv = exportarResultadosCSV($id);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="encuesta_' . $id . '.csv"');
    echo $csv; 
    exit;
}

// Obtener datos
$results = obtenerResultadosEncuesta($id);
$totalRespuestas = obtenerNumeroRespuestas($id);
$respondentes = obtenerRespondentesEncuesta($id); // Nueva función para obtener usuarios que respondieron
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resultados - <?= htmlspecialchars($enc['titulo']) ?> - Panel ONT</title>
    <link rel="icon" type="image/png" href="../assets/images/logo_sup.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .survey-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        .survey-header {
            background: linear-gradient(135deg, #352f62 0%, #e45504 100%);
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 1.5rem;
        }
        .question-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        .question-header {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eee;
        }
        .question-body {
            padding: 1.5rem;
        }
        .option-result {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        .option-label {
            min-width: 150px;
            font-weight: 500;
        }
        .progress-container {
            flex-grow: 1;
            margin: 0 1rem;
        }
        .vote-count {
            min-width: 100px;
            text-align: right;
            font-weight: 500;
        }
        .chart-container {
            height: 300px;
            margin-top: 2rem;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #352f62;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
       
         .nav-tabs .nav-link {
        color: black;
    }

    .nav-tabs .nav-link.active {
        color: black;
        background-color: #f8f9fa; /* Fondo claro para que el negro resalte */
        border-color: #dee2e6 #dee2e6 #fff; /* Borde estándar de Bootstrap */
    }
    </style>
</head>
<body>
    
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main-content" id="mainContent">
      <header class="admin-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="page-title">Resultados de Encuesta</h1>
            </div>
            <div class="header-right">
                <a href="?id=<?= $id ?>&export=csv" class="btn-ont success">
                    <i class="bi bi-download"></i> Exportar CSV
                </a>
                <a href="encuestas.php" class="btn-ont secondary">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            </div>
        </header>

        <div class="admin-content">
            <div class="survey-container">
                <!-- Encabezado de la encuesta -->
                <div class="question-card mb-4">
                    <div class="survey-header">
                        <h2 class="mb-2"><?= htmlspecialchars($enc['titulo']) ?></h2>
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            <span class="badge bg-light text-dark">
                                <i class="bi bi-people"></i> <?= $totalRespuestas ?> respuestas
                            </span>
                            <span class="badge bg-light text-dark">
                                <i class="bi bi-calendar"></i> <?= date('d/m/Y H:i', strtotime($enc['fecha_creacion'])) ?>
                            </span>
                            <span class="badge bg-<?= $enc['estado'] === 'activa' ? 'success' : 'warning' ?>">
                                <?= ucfirst($enc['estado']) ?>
                            </span>
                        </div>
                    </div>
                    <?php if (!empty($enc['descripcion'])): ?>
                    <div class="question-body">
                        <p class="lead mb-0"><?= nl2br(htmlspecialchars($enc['descripcion'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>

               <!-- Pestañas para cambiar entre vistas -->
<ul class="nav nav-tabs" id="resultsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="stats-tab" 
                data-bs-toggle="tab" 
                data-bs-target="#stats" 
                type="button" role="tab" aria-controls="stats" aria-selected="true">
            <i class="bi bi-bar-chart"></i> Estadísticas
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="respondents-tab" 
                data-bs-toggle="tab" 
                data-bs-target="#respondents" 
                type="button" role="tab" aria-controls="respondents" aria-selected="false">
            <i class="bi bi-people"></i> Respondentes (<?= count($respondentes) ?>)
        </button>
    </li>
</ul>


                <div class="tab-content" id="resultsTabsContent">
                    <!-- Pestaña de Estadísticas -->
                    <div class="tab-pane fade show active" id="stats" role="tabpanel" aria-labelledby="stats-tab">
                        <?php if (empty($results)): ?>
                            <div class="question-card text-center py-5">
                                <i class="bi bi-bar-chart" style="font-size: 3rem; color: #6c757d;"></i>
                                <h3 class="mt-3">Sin respuestas aún</h3>
                                <p class="text-muted">Esta encuesta no ha recibido respuestas</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($results as $index => $r): ?>
                            <div class="question-card">
                                <div class="question-header">
                                    <h4 class="mb-0">
                                        <i class="bi bi-question-circle me-2"></i>
                                        Pregunta <?= $index + 1 ?>: <?= htmlspecialchars($r['texto']) ?>
                                    </h4>
                                </div>
                                <div class="question-body">
                                    <?php if ($r['tipo'] === 'texto'): ?>
                                        <div class="alert alert-info d-flex align-items-center">
                                            <i class="bi bi-info-circle-fill me-2"></i>
                                            <span><?= $r['total'] ?> respuestas de texto registradas</span>
                                        </div>
                                    <?php else: ?>
                                        <?php 
                                        $totalVotos = array_sum(array_column($r['opciones'], 'votos'));
                                        foreach($r['opciones'] as $o): 
                                            $porcentaje = $totalVotos > 0 ? round(($o['votos'] / $totalVotos) * 100, 1) : 0;
                                        ?>
                                        <div class="option-result">
                                            <div class="option-label"><?= htmlspecialchars($o['texto']) ?></div>
                                            <div class="progress-container">
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?= $porcentaje ?>%" 
                                                         aria-valuenow="<?= $porcentaje ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100"></div>
                                                </div>
                                            </div>
                                            <div class="vote-count">
                                                <?= $o['votos'] ?> (<?= $porcentaje ?>%)
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        
                                        <div class="chart-container">
                                            <canvas id="chart<?= $index ?>"></canvas>
                                        </div>
                                        
                                        <div class="mt-3 text-end text-muted">
                                            <small><i class="bi bi-bar-chart"></i> Total votos: <?= $r['total'] ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Pestaña de Respondentes -->
                    <div class="tab-pane fade" id="respondents" role="tabpanel" aria-labelledby="respondents-tab">
                        <div class="question-card">
                            <div class="question-header">
                                <h4 class="mb-0">
                                    <i class="bi bi-people me-2"></i>
                                    Usuarios que respondieron
                                </h4>
                            </div>
                            <div class="question-body">
                                <?php if (empty($respondentes)): ?>
                                    <div class="alert alert-info">
                                        Ningún usuario ha respondido esta encuesta aún
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Usuario</th>
                                                    <th>Correo</th>
                                                    <th>Fecha</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($respondentes as $user): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="user-avatar me-2">
                                                                <?= strtoupper(substr($user['nombre'], 0, 1)) ?>
                                                            </div>
                                                            <?= htmlspecialchars($user['nombre']) ?>
                                                        </div>
                                                    </td>
                                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($user['fecha_respuesta'])) ?></td>
                                                    <td>
                                                        <a href="encuesta_respuestas_usuario.php?encuesta_id=<?= $id ?>&usuario_id=<?= $user['id'] ?>" class="btn-ont primary btn-sm">
                                                            <i class="bi bi-eye"></i> Ver respuestas
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    <script>
        // Gráficos para preguntas de opción múltiple/unica
        <?php foreach($results as $index => $r): ?>
        <?php if ($r['tipo'] !== 'texto'): ?>
        {
            const ctx<?= $index ?> = document.getElementById('chart<?= $index ?>').getContext('2d');
            const data<?= $index ?> = {
                labels: [<?php foreach($r['opciones'] as $o): ?>'<?= addslashes($o['texto']) ?>',<?php endforeach; ?>],
                datasets: [{
                    data: [<?php foreach($r['opciones'] as $o): ?><?= intval($o['votos']) ?>,<?php endforeach; ?>],
                    backgroundColor: [
                        '#352f62',
                        '#e45504',
                        '#6c757d',
                        '#198754',
                        '#dc3545',
                        '#fd7e14',
                        '#6f42c1',
                        '#20c997'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            };

            new Chart(ctx<?= $index ?>, {
                type: 'doughnut',
                data: data<?= $index ?>,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return `${context.label}: ${context.parsed} votos (${percentage}%)`;
                                }
                            }
                        }
                    },
                    cutout: '65%'
                }
            });
        }
        <?php endif; ?>
        <?php endforeach; ?>
    </script>
</body>
</html>