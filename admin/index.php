<?php
// admin/dashboard.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

verificarRol('admin');

// Obtener todas las estadísticas
$estadisticas = obtenerEstadisticasDashboard();
$estadisticasUsuarios = obtenerEstadisticasUsuarios();
$estadisticasContenido = obtenerEstadisticasContenido();
$topUsuarios = obtenerTopUsuariosActivos();
$topNoticias = obtenerTopNoticiasVistas();

// Inicializar arrays si están vacíos
$estadisticasContenido['noticias_mas_comentadas'] = $estadisticasContenido['noticias_mas_comentadas'] ?? [];
$estadisticasContenido['encuestas_mas_populares'] = $estadisticasContenido['encuestas_mas_populares'] ?? [];
$estadisticasContenido['noticias_mas_vistas'] = $estadisticasContenido['noticias_mas_vistas'] ?? [];

// Calcular porcentajes
$porcentajeAprobados = $estadisticas['total_comentarios'] > 0 
    ? round(($estadisticas['comentarios_aprobados'] / $estadisticas['total_comentarios']) * 100) 
    : 0;
    
$porcentajeGoogle = $estadisticas['total_usuarios'] > 0 
    ? round(($estadisticas['usuarios_google'] / $estadisticas['total_usuarios']) * 100) 
    : 0;
    
$tasaParticipacion = $estadisticas['total_usuarios'] > 0 
    ? round(($estadisticasUsuarios['usuarios_activos_30dias'] / $estadisticas['total_usuarios']) * 100) 
    : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Estadístico - Panel ONT</title>
    <link rel="icon" type="image/png" href="../assets/images/logo_sup.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        /* Estilos mejorados para el dashboard */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, transparent 30%, rgba(255,255,255,0.1));
            border-radius: 50%;
            transform: translate(30%, -30%);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .stat-icon i {
            font-size: 28px;
            color: white;
        }
        
        .stat-content h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--ont-dark);
        }
        
        .stat-content p {
            color: var(--ont-dark);
            opacity: 0.7;
            margin-bottom: 0.5rem;
        }
        
        .stat-details {
            font-size: 0.875rem;
            color: #666;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(0,0,0,0.05);
        }
        
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .chart-container:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .chart-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--ont-dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .chart-title i {
            color: var(--ont-primary);
        }
        
        .top-list {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .top-list:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .top-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s ease;
            border-radius: 8px;
            margin: 0 -0.5rem;
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
        
        .top-item:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .top-item:last-child { border-bottom: none; }
        
        .top-rank {
            font-weight: 700;
            color: var(--ont-primary);
            width: 40px;
            font-size: 1.25rem;
        }
        
        .top-info {
            flex: 1;
        }
        
        .top-value {
            font-weight: 600;
            color: var(--ont-dark);
        }
        
        .insight-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .insight-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .insight-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .insight-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .insight-icon i { font-size: 24px; }
        
        .insight-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
            color: var(--ont-dark);
        }
        
        .insight-content {
            color: #666;
            line-height: 1.6;
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .quick-stat {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border-left: 3px solid;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .quick-stat:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
        }
        
        .quick-stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quick-stat-icon i {
            font-size: 20px;
            color: white;
        }
        
        .quick-stat-content h4 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0;
            color: var(--ont-dark);
        }
        
        .quick-stat-content p {
            font-size: 0.875rem;
            color: #666;
            margin-bottom: 0;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--ont-dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Colores para las tarjetas */
        .card-primary { border-left-color: var(--ont-primary); }
        .card-secondary { border-left-color: var(--ont-secondary); }
        .card-success { border-left-color: #10b981; }
        .card-warning { border-left-color: #f59e0b; }
        .card-info { border-left-color: #3b82f6; }
        .card-purple { border-left-color: #8b5cf6; }
        .card-pink { border-left-color: #ec4899; }
        
        .icon-primary { background: var(--ont-primary); }
        .icon-secondary { background: var(--ont-secondary); }
        .icon-success { background: #10b981; }
        .icon-warning { background: #f59e0b; }
        .icon-info { background: #3b82f6; }
        .icon-purple { background: #8b5cf6; }
        .icon-pink { background: #ec4899; }
        
        /* Estilos para los gráficos */
        .chart-wrapper {
            min-height: 300px;
            position: relative;
        }
        
        .data-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .table-header {
            background: linear-gradient(135deg, var(--ont-primary), var(--ont-secondary));
            color: white;
            padding: 1rem 1.5rem;
        }
        
        .table-header h5 {
            margin: 0;
            font-weight: 600;
        }
        
        .metric-box {
            text-align: center;
            padding: 1.5rem;
            border-radius: 8px;
            background: linear-gradient(135deg, #f8f9fa, #ffffff);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .metric-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            border-color: var(--ont-primary);
        }
        
        .metric-box .fw-bold {
            color: var(--ont-primary);
            font-size: 2rem;
            display: block;
            margin-bottom: 0.5rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-stats {
                grid-template-columns: 1fr 1fr;
            }
            
            .chart-wrapper {
                min-height: 250px;
            }
        }
        
        /* Animaciones de carga */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .stat-card, .quick-stat, .chart-container, .top-list, .insight-card {
            animation: fadeInUp 0.5s ease-out;
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
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
                <h1 class="page-title">
                    <i class="bi bi-graph-up me-2"></i>Dashboard Estadístico
                </h1>
            </div>
            <div class="header-right">
                <span class="text-muted me-3 d-none d-md-inline">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= date('d/m/Y H:i') ?>
                </span>
                <a href="../index.php" class="btn-ont info" target="_blank">
                    <i class="bi bi-eye"></i>
                    Ver Sitio
                </a>
            </div>
        </header>

        <div class="content-area">
            <!-- Estadísticas Rápidas -->
            <div class="quick-stats">
                <div class="quick-stat card-primary">
                <div class="quick-stat-icon icon-warning">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="quick-stat-content">
                        <h4><?= $estadisticas['total_usuarios'] ?></h4>
                        <p>Usuarios Totales</p>
                    </div>
                </div>
                <div class="quick-stat card-success">
                    <div class="quick-stat-icon icon-success">
                        <i class="bi bi-chat-dots"></i>
                    </div>
                    <div class="quick-stat-content">
                        <h4><?= $estadisticas['total_comentarios'] ?></h4>
                        <p>Comentarios</p>
                    </div>
                </div>
                <div class="quick-stat card-info">
                    <div class="quick-stat-icon icon-info">
                        <i class="bi bi-eye"></i>
                    </div>
                    <div class="quick-stat-content">
                        <h4><?= $estadisticas['total_vistas'] ?></h4>
                        <p>Vistas de Noticias</p>
                    </div>
                </div>
                <div class="quick-stat card-warning">
                    <div class="quick-stat-icon icon-warning">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                    <div class="quick-stat-content">
                        <h4><?= $estadisticas['total_encuestas'] ?></h4>
                        <p>Encuestas</p>
                    </div>
                </div>
            </div>

            <!-- Tarjetas Principales con Gráficos Integrados -->
            <div class="row mb-4">
                <!-- Estadísticas de Usuarios con Gráfico -->
                <div class="col-lg-4 mb-4">
                    <div class="stat-card card-primary">
                        <div class="stat-header d-flex align-items-center mb-3">
                            <div class="stat-icon icon-warning me-3">
                                <i class="bi bi-people"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Usuarios</h5>
                                <small class="text-muted">Distribución y actividad</small>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div id="usuariosChart" style="height: 200px;"></div>
                            <div class="stat-details mt-3">
                                <div class="d-flex justify-content-between w-100 mb-2">
                                    <span><i class="bi bi-circle-fill text-primary me-1"></i>Activos:</span>
                                    <strong><?= $estadisticas['usuarios_activos'] ?></strong>
                                </div>
                                <div class="d-flex justify-content-between w-100 mb-2">
                                    <span><i class="bi bi-circle-fill me-1" style="color: #6b7280;"></i>Inactivos:</span>
                                    <strong><?= $estadisticas['usuarios_inactivos'] ?></strong>
                                </div>
                                <div class="d-flex justify-content-between w-100">
                                    <span><i class="bi bi-google me-1"></i>Registro Google:</span>
                                    <strong><?= $porcentajeGoogle ?>%</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas de Contenido con Gráfico -->
                <div class="col-lg-4 mb-4">
                    <div class="stat-card card-success">
                        <div class="stat-header d-flex align-items-center mb-3">
                            <div class="stat-icon icon-success me-3">
                                <i class="bi bi-newspaper"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Contenido</h5>
                                <small class="text-muted">Publicaciones y estados</small>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div id="contenidoChart" style="height: 200px;"></div>
                            <div class="stat-details mt-3">
                                <div class="d-flex justify-content-between w-100 mb-2">
                                    <span><i class="bi bi-circle-fill text-success me-1"></i>Publicadas:</span>
                                    <strong><?= $estadisticas['noticias_publicadas'] ?></strong>
                                </div>
                                <div class="d-flex justify-content-between w-100 mb-2">
                                    <span><i class="bi bi-circle-fill me-1" style="color: #f59e0b;"></i>Borrador:</span>
                                    <strong><?= $estadisticas['noticias_borrador'] ?></strong>
                                </div>
                                <div class="d-flex justify-content-between w-100">
                                    <span><i class="bi bi-star-fill text-warning me-1"></i>Destacadas:</span>
                                    <strong><?= $estadisticas['noticias_destacadas'] ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas de Participación con Gráfico -->
                <div class="col-lg-4 mb-4">
                    <div class="stat-card card-info">
                        <div class="stat-header d-flex align-items-center mb-3">
                            <div class="stat-icon icon-info me-3">
                                <i class="bi bi-chat-dots"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Participación</h5>
                                <small class="text-muted">Comentarios y encuestas</small>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div id="comentariosChart" style="height: 200px;"></div>
                            <div class="stat-details mt-3">
                                <div class="d-flex justify-content-between w-100 mb-2">
                                    <span><i class="bi bi-circle-fill text-success me-1"></i>Aprobados:</span>
                                    <strong><?= $estadisticas['comentarios_aprobados'] ?> (<?= $porcentajeAprobados ?>%)</strong>
                                </div>
                                <div class="d-flex justify-content-between w-100 mb-2">
                                    <span><i class="bi bi-circle-fill text-warning me-1"></i>Pendientes:</span>
                                    <strong><?= $estadisticas['comentarios_pendientes'] ?></strong>
                                </div>
                                <div class="d-flex justify-content-between w-100">
                                    <span><i class="bi bi-circle-fill text-danger me-1"></i>Rechazados:</span>
                                    <strong><?= $estadisticas['comentarios_rechazados'] ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos de Tendencias -->
            <div class="row mb-4">
                <div class="col-lg-6 mb-4">
                    <div class="chart-container">
                        <h5 class="chart-title">
                            <i class="bi bi-bar-chart-line"></i>
                            Distribución de Roles
                        </h5>
                        <div id="rolesChart" class="chart-wrapper"></div>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-4">
                    <div class="chart-container">
                        <h5 class="chart-title">
                            <i class="bi bi-pie-chart"></i>
                            Estado de Encuestas
                        </h5>
                        <div id="encuestasChart" class="chart-wrapper"></div>
                    </div>
                </div>
            </div>

            <!-- Top Rankings -->
            <div class="row mb-4">
                <!-- Top Usuarios Activos -->
                <div class="col-lg-6 mb-4">
                    <div class="top-list">
                        <h5 class="chart-title">
                            <i class="bi bi-trophy"></i>
                            Usuarios Más Activos
                        </h5>
                        <?php if (!empty($topUsuarios)): ?>
                            <?php $rank = 1; ?>
                            <?php foreach ($topUsuarios as $usuario): ?>
                                <div class="top-item">
                                    <div class="top-rank">#<?= $rank++ ?></div>
                                    <div class="top-info">
                                        <div class="fw-semibold"><?= htmlspecialchars($usuario['nombre']) ?></div>
                                        <div class="small text-muted">
                                            <i class="bi bi-chat-square-text me-1"></i><?= $usuario['comentarios'] ?> comentarios • 
                                            <i class="bi bi-clipboard-check me-1"></i><?= $usuario['respuestas_encuestas'] ?> respuestas
                                        </div>
                                    </div>
                                    <div class="top-value text-end">
                                        <small class="text-muted d-block">
                                            <i class="bi bi-clock me-1"></i>
                                            <?= $usuario['ultimo_acceso'] ? date('d/m/Y', strtotime($usuario['ultimo_acceso'])) : 'Nunca' ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-people" style="font-size: 2rem; opacity: 0.5;"></i>
                                <p class="mt-2 mb-0">No hay usuarios activos</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Top Noticias Más Vistas -->
                <div class="col-lg-6 mb-4">
                    <div class="top-list">
                        <h5 class="chart-title">
                            <i class="bi bi-eye"></i>
                            Noticias Más Vistas
                        </h5>
                        <?php if (!empty($topNoticias)): ?>
                            <?php $rank = 1; ?>
                            <?php foreach ($topNoticias as $noticia): ?>
                                <div class="top-item">
                                    <div class="top-rank">#<?= $rank++ ?></div>
                                    <div class="top-info">
                                        <div class="fw-semibold"><?= htmlspecialchars(substr($noticia['titulo'], 0, 40)) ?>...</div>
                                        <div class="small text-muted">
                                            <i class="bi bi-chat-dots me-1"></i><?= $noticia['comentarios'] ?> comentarios • 
                                            <i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y', strtotime($noticia['fecha_publicacion'])) ?>
                                        </div>
                                    </div>
                                    <!-- <div class="top-value">
                                        <i class="bi bi-eye me-1"></i><?= $noticia['vistas'] ?> vistas
                                    </div> -->
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-newspaper" style="font-size: 2rem; opacity: 0.5;"></i>
                                <p class="mt-2 mb-0">No hay noticias vistas</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Insights y Métricas -->
            <div class="row mb-4">
                <div class="col-md-4 mb-4">
                    <div class="insight-card">
                        <div class="insight-header">
                            <div class="insight-icon icon-primary">
                                <i class="bi bi-graph-up-arrow"></i>
                            </div>
                            <h6 class="insight-title">Tasa de Participación</h6>
                        </div>
                        <div class="insight-content">
                            <div class="text-center mb-3">
                                <div style="font-size: 2.5rem; font-weight: 700; color: var(--ont-primary);">
                                    <?= $tasaParticipacion ?>%
                                </div>
                                <small class="text-muted">de usuarios activos en los últimos 30 días</small>
                            </div>
                            <div id="participacionMiniChart" style="height: 60px;"></div>
                            <p class="small text-muted mb-0 mt-2">
                                <i class="bi bi-info-circle me-1"></i>
                                <?= $estadisticasUsuarios['usuarios_activos_30dias'] ?> de <?= $estadisticas['total_usuarios'] ?> usuarios han participado recientemente.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="insight-card">
                        <div class="insight-header">
                            <div class="insight-icon icon-success">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <h6 class="insight-title">Calidad de Contenido</h6>
                        </div>
                        <div class="insight-content">
                            <div class="text-center mb-3">
                                <div style="font-size: 2.5rem; font-weight: 700; color: #10b981;">
                                    <?= $porcentajeAprobados ?>%
                                </div>
                                <small class="text-muted">de comentarios aprobados</small>
                            </div>
                            <div id="calidadMiniChart" style="height: 60px;"></div>
                            <p class="small text-muted mb-0 mt-2">
                                <i class="bi bi-info-circle me-1"></i>
                                Solo <?= $estadisticas['comentarios_pendientes'] ?> comentarios pendientes de moderación.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="insight-card">
                        <div class="insight-header">
                            <div class="insight-icon icon-info">
                                <i class="bi bi-google"></i>
                            </div>
                            <h6 class="insight-title">Registro por Método</h6>
                        </div>
                        <div class="insight-content">
                            <div class="text-center mb-3">
                                <div style="font-size: 2.5rem; font-weight: 700; color: #3b82f6;">
                                    <?= $porcentajeGoogle ?>%
                                </div>
                                <small class="text-muted">de usuarios con Google</small>
                            </div>
                            <div id="metodoRegistroChart" style="height: 60px;"></div>
                            <p class="small text-muted mb-0 mt-2">
                                <i class="bi bi-info-circle me-1"></i>
                                <?= $estadisticas['usuarios_google'] ?> usuarios Google vs 
                                <?= $estadisticas['usuarios_local'] ?> locales.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Noticias Más Comentadas -->
            <div class="row mb-4">
                <div class="col-lg-6 mb-4">
                    <div class="top-list">
                        <h5 class="chart-title">
                            <i class="bi bi-chat-left-text"></i>
                            Noticias Más Comentadas
                        </h5>
                        <?php if (!empty($estadisticasContenido['noticias_mas_comentadas'])): ?>
                            <?php $rank = 1; ?>
                            <?php foreach ($estadisticasContenido['noticias_mas_comentadas'] as $noticia): ?>
                                <div class="top-item">
                                    <div class="top-rank">#<?= $rank++ ?></div>
                                    <div class="top-info">
                                        <div class="fw-semibold"><?= htmlspecialchars(substr($noticia['titulo'], 0, 40)) ?>...</div>
                                        <div class="small text-muted">
                                            ID: <?= $noticia['id'] ?>
                                        </div>
                                    </div>
                                    <div class="top-value">
                                        <i class="bi bi-chat-dots me-1"></i><?= $noticia['cantidad_comentarios'] ?> comentarios
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-chat-left-text" style="font-size: 2rem; opacity: 0.5;"></i>
                                <p class="mt-2 mb-0">No hay noticias comentadas</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Top Encuestas Más Populares -->
                <div class="col-lg-6 mb-4">
                    <div class="top-list">
                        <h5 class="chart-title">
                            <i class="bi bi-bar-chart"></i>
                            Encuestas Más Populares
                        </h5>
                        <?php if (!empty($estadisticasContenido['encuestas_mas_populares'])): ?>
                            <?php $rank = 1; ?>
                            <?php foreach ($estadisticasContenido['encuestas_mas_populares'] as $encuesta): ?>
                                <div class="top-item">
                                    <div class="top-rank">#<?= $rank++ ?></div>
                                    <div class="top-info">
                                        <div class="fw-semibold"><?= htmlspecialchars(substr($encuesta['titulo'], 0, 40)) ?>...</div>
                                        <div class="small text-muted">
                                            ID: <?= $encuesta['id'] ?>
                                        </div>
                                    </div>
                                    <div class="top-value">
                                        <i class="bi bi-people me-1"></i><?= $encuesta['participantes'] ?> participantes
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-bar-chart" style="font-size: 2rem; opacity: 0.5;"></i>
                                <p class="mt-2 mb-0">No hay encuestas con respuestas</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Métricas del Sistema -->
            <div class="data-table">
                <div class="table-header">
                    <h5><i class="bi bi-activity me-2"></i>Métricas del Sistema</h5>
                </div>
                <div class="p-3">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <div class="metric-box">
                                <div class="fw-bold"><?= $estadisticas['popups_activos'] ?></div>
                                <small class="text-muted"><i class="bi bi-window me-1"></i>Pop-ups Activos</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="metric-box">
                                <div class="fw-bold"><?= $estadisticas['carrusel_activo'] ?></div>
                                <small class="text-muted"><i class="bi bi-images me-1"></i>Elementos en Carrusel</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="metric-box">
                                <div class="fw-bold"><?= $estadisticas['normativas_activas'] ?></div>
                                <small class="text-muted"><i class="bi bi-file-earmark-text me-1"></i>Normativas Activas</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="metric-box">
                                <div class="fw-bold"><?= count($estadisticasContenido['noticias_mas_comentadas']) ?></div>
                                <small class="text-muted"><i class="bi bi-chat-left-text me-1"></i>Noticias con Comentarios</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Colores consistentes
            const colors = {
                primary: getComputedStyle(document.documentElement).getPropertyValue('--ont-primary').trim() || '#0066cc',
                secondary: getComputedStyle(document.documentElement).getPropertyValue('--ont-secondary').trim() || '#00a651',
                success: '#10b981',
                warning: '#f59e0b',
                danger: '#ef4444',
                info: '#3b82f6',
                gray: '#6b7280'
            };

            // Gráfico de Usuarios (Donut)
            const usuariosChart = new ApexCharts(document.querySelector("#usuariosChart"), {
                series: [<?= $estadisticas['usuarios_activos'] ?>, <?= $estadisticas['usuarios_inactivos'] ?>],
                chart: {
                    type: 'donut',
                    height: 200
                },
                labels: ['Activos', 'Inactivos'],
                colors: [colors.primary, colors.gray],
                legend: {
                    show: false
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total',
                                    fontSize: '14px',
                                    formatter: () => '<?= $estadisticas['total_usuarios'] ?>'
                                }
                            }
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val + " usuarios"
                        }
                    }
                }
            });
            usuariosChart.render();

            // Gráfico de Contenido (Donut)
            const contenidoChart = new ApexCharts(document.querySelector("#contenidoChart"), {
                series: [
                    <?= $estadisticas['noticias_publicadas'] ?>,
                    <?= $estadisticas['noticias_borrador'] ?>,
                    <?= $estadisticas['noticias_destacadas'] ?>
                ],
                chart: {
                    type: 'donut',
                    height: 200
                },
                labels: ['Publicadas', 'Borrador', 'Destacadas'],
                colors: [colors.success, colors.warning, colors.info],
                legend: {
                    show: false
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total',
                                    fontSize: '14px',
                                    formatter: () => '<?= $estadisticas['total_noticias'] ?>'
                                }
                            }
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val + " noticias"
                        }
                    }
                }
            });
            contenidoChart.render();

            // Gráfico de Comentarios (Donut)
            const comentariosChart = new ApexCharts(document.querySelector("#comentariosChart"), {
                series: [
                    <?= $estadisticas['comentarios_aprobados'] ?>,
                    <?= $estadisticas['comentarios_pendientes'] ?>,
                    <?= $estadisticas['comentarios_rechazados'] ?>
                ],
                chart: {
                    type: 'donut',
                    height: 200
                },
                labels: ['Aprobados', 'Pendientes', 'Rechazados'],
                colors: [colors.success, colors.warning, colors.danger],
                legend: {
                    show: false
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total',
                                    fontSize: '14px',
                                    formatter: () => '<?= $estadisticas['total_comentarios'] ?>'
                                }
                            }
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val + " comentarios"
                        }
                    }
                }
            });
            comentariosChart.render();

            // Gráfico de Distribución de Roles (Barras horizontales)
            const rolesData = <?= json_encode($estadisticas['distribucion_roles']) ?>;
            const rolesChart = new ApexCharts(document.querySelector("#rolesChart"), {
                series: [{
                    name: 'Usuarios',
                    data: rolesData.map(r => r.cantidad)
                }],
                chart: {
                    type: 'bar',
                    height: 300,
                    toolbar: {
                        show: false
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 8,
                        distributed: true,
                        dataLabels: {
                            position: 'top'
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    offsetX: 30,
                    style: {
                        fontSize: '12px',
                        colors: ['#000']
                    }
                },
                colors: [colors.primary, colors.success, colors.info, colors.warning],
                xaxis: {
                    categories: rolesData.map(r => r.nombre),
                    title: {
                        text: 'Cantidad de Usuarios'
                    }
                },
                yaxis: {
                    title: {
                        text: 'Roles'
                    }
                },
                legend: {
                    show: false
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val + " usuarios"
                        }
                    }
                }
            });
            rolesChart.render();

            // Gráfico de Estado de Encuestas (Pie)
            const encuestasChart = new ApexCharts(document.querySelector("#encuestasChart"), {
                series: [
                    <?= $estadisticas['encuestas_activas'] ?>,
                    <?= $estadisticas['total_encuestas'] - $estadisticas['encuestas_activas'] ?>
                ],
                chart: {
                    type: 'pie',
                    height: 300
                },
                labels: ['Activas', 'Inactivas'],
                colors: [colors.success, colors.gray],
                legend: {
                    position: 'bottom',
                    fontSize: '14px'
                },
                plotOptions: {
                    pie: {
                        dataLabels: {
                            offset: -10
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val, opts) {
                        return opts.w.config.series[opts.seriesIndex]
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val + " encuestas"
                        }
                    }
                }
            });
            encuestasChart.render();

            // Mini gráfico de Participación (Área)
            const participacionMiniChart = new ApexCharts(document.querySelector("#participacionMiniChart"), {
                series: [{
                    name: 'Participación',
                    data: [20, 35, 45, 50, 49, 60, <?= $tasaParticipacion ?>]
                }],
                chart: {
                    type: 'area',
                    height: 60,
                    sparkline: {
                        enabled: true
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                fill: {
                    opacity: 0.3,
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.3
                    }
                },
                colors: [colors.primary],
                tooltip: {
                    enabled: false
                }
            });
            participacionMiniChart.render();

            // Mini gráfico de Calidad (Área)
            const calidadMiniChart = new ApexCharts(document.querySelector("#calidadMiniChart"), {
                series: [{
                    name: 'Aprobación',
                    data: [75, 80, 85, 88, 90, 92, <?= $porcentajeAprobados ?>]
                }],
                chart: {
                    type: 'area',
                    height: 60,
                    sparkline: {
                        enabled: true
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                fill: {
                    opacity: 0.3,
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.3
                    }
                },
                colors: [colors.success],
                tooltip: {
                    enabled: false
                }
            });
            calidadMiniChart.render();

            // Mini gráfico de Método de Registro (Área)
            const metodoRegistroChart = new ApexCharts(document.querySelector("#metodoRegistroChart"), {
                series: [{
                    name: 'Google',
                    data: [30, 40, 50, 55, 60, 65, <?= $porcentajeGoogle ?>]
                }],
                chart: {
                    type: 'area',
                    height: 60,
                    sparkline: {
                        enabled: true
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                fill: {
                    opacity: 0.3,
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.3
                    }
                },
                colors: [colors.info],
                tooltip: {
                    enabled: false
                }
            });
            metodoRegistroChart.render();
        });
        
        // Auto-refresh cada 5 minutos
        setTimeout(() => {
            window.location.reload();
        }, 300000);
    </script>
</body>
</html>