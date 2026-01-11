<?php
require_once './includes/auth.php';
require_once './includes/database.php';
require_once './includes/carrusel_functions.php';
require_once __DIR__ . '/includes/popups_functions.php';

$cn = obtenerConexion();
$userId = $_SESSION['user_id'] ?? 0;
$popups_activos = getPopupsActivos($cn, $userId);


$normativas = obtenerResultados("
    SELECT id, titulo, descripcion, archivo, fecha_publicacion 
    FROM normativas 
    WHERE estado = 'activo'
    ORDER BY fecha_publicacion DESC 
    LIMIT 3
");

$noticias = obtenerResultados("SELECT * FROM noticias WHERE estado = 'publicado' ORDER BY fecha_publicacion DESC LIMIT 6");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ONT Bolivia - Observatorio Nacional del Trabajo</title>
    <link rel="icon" type="image/png" href="assets/images/logo_sup.png">
    <meta name="description" content="Observatorio Nacional del Trabajo de Bolivia - Información que transforma el trabajo, datos que impulsan el progreso"> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
     
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
      
    <link href="./assets/css/style.css" rel="stylesheet">
    <link href="./assets/css/sections.css" rel="stylesheet">

</head>
<body>
<!--Carga-->
    <div id="preloader" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, #352f62 0%, #e45504 100%); z-index: 9999; display: flex; align-items: center; justify-content: center;">
        <div class="loading"></div>
    </div>

<!-- Navigation -->
        <?php include './partials/navbar.php'; ?>
<!-- Hero Section - Carrusel -->
<section class="hero-section">
 <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="7000">

        <div class="carousel-indicators">
            <?php 
            $slides = obtenerSlidesCarrusel(true); // true = solo activos
            foreach ($slides as $key => $slide): ?>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?= $key ?>" 
                        class="<?= $key === 0 ? 'active' : '' ?>" 
                        aria-current="<?= $key === 0 ? 'true' : 'false' ?>" 
                        aria-label="Slide <?= $key + 1 ?>"></button>
            <?php endforeach; ?>
        </div>
    
        <div class="carousel-inner">
    <?php foreach ($slides as $key => $slide): ?>
    <div class="carousel-item <?= $key === 0 ? 'active' : '' ?>" 
         style="background-image: url('assets/uploads/carrusel/<?= htmlspecialchars($slide['imagen']) ?>'); 
                background-size: cover; 
                background-position: center; 
                min-height: 500px;">
        <div class="overlay"></div>
        <div class="container hero-content text-white d-flex align-items-center" style="min-height: 500px;">
            <div class="row w-100">
                <div class="col-lg-8">
                    <h1 class="hero-title"><?= htmlspecialchars($slide['titulo']) ?></h1>
                    <p class="hero-subtitle"><?= htmlspecialchars($slide['subtitulo']) ?></p>
                    <div class="d-flex flex-column flex-sm-row gap-3">
                        <?php if ($slide['texto_boton_1'] && $slide['url_boton_1']): ?>
                        <a href="<?= htmlspecialchars($slide['url_boton_1']) ?>" class="btn btn-ont-primary btn-lg">
                            <i class="bi bi-arrow-down-circle me-2"></i><?= htmlspecialchars($slide['texto_boton_1']) ?>
                        </a>
                        <?php endif; ?>

                        <?php if ($slide['texto_boton_2'] && $slide['url_boton_2']): ?>
                        <a href="<?= htmlspecialchars($slide['url_boton_2']) ?>" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-clipboard-data me-2"></i><?= htmlspecialchars($slide['texto_boton_2']) ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
 
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Anterior</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Siguiente</span>
        </button>
    </div>
</section>

    <section id="noticias" class="section-ont">
        <div class="container">
            <h2 class="section-title">
                <i class="bi bi-newspaper me-3"></i>Últimas Noticias
            </h2>
            
            <div class="row">
                <?php if (empty($noticias)): ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>No hay noticias disponibles.
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($noticias as $noticia): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <a href="noticias.php?id=<?= $noticia['id'] ?>" class="text-decoration-none">
                                <div class="card card-ont h-100">
                                    <?php if (!empty($noticia['imagen_portada'])): ?>
                                        <img src="./assets/uploads/noticias/<?= htmlspecialchars($noticia['imagen_portada']) ?>" 
                                             class="card-img-top" 
                                             alt="<?= htmlspecialchars($noticia['titulo']) ?>"
                                             loading="lazy">
                                    <?php else: ?>
                                        <div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height: 200px;">
                                            <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($noticia['titulo']) ?></h5>
                                        <p class="card-text text-muted"><?= substr(strip_tags($noticia['contenido']), 0, 150) ?>...</p>
                                    </div>
                                    <div class="card-footer">
                                        <small class="text-muted">
                                            <i class="bi bi-calendar3 me-1"></i>
                                            <?= date('d/m/Y', strtotime($noticia['fecha_publicacion'])) ?>
                                        </small>
                                        <span class="float-end">
                                            <i class="bi bi-arrow-right-circle text-primary"></i>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>


<section id="sobre-ont" class="section-ont"> 
<div class="container">  
<div class="row">
         <div class="col-md-7"> 
        <h2 class="section-title">
             <i class="bi bi-info-circle me-1"></i>Sobre Nosotros
        </h2>
        <p>
            <strong>El Observatorio Nacional del Trabajo (ONT)

            </strong> ha sido creado con el objetivo principal de recopilar, analizar y difundir información relacionada con el mercado laboral del país, a fin de contribuir al desarrollo y mejora de las políticas laborales, a la toma de decisiones en el ámbito empresarial y desde la perspectiva académica, al diseño de programas que coadyuven al cierre de la brecha entre oferta y demanda laboral en los diferentes niveles de formación.</p> 
            <ul> 
            <li>Identificar tendencias y cambios en el mercado laboral, como por ejemplo cambios en las demandas de competencias, en las condiciones de trabajo, en las necesidades del mercado laboral, en los niveles salariales, etc.</li>
            <li>Identificar las competencias más demandadas en el mercado laboral, clasificándolas por nivel y sector.</li>
             <li>Proporcionar información sobre la situación actual y las perspectivas del mercado laboral y el ajuste de las mismas con las políticas laborales y empresariales.</li> 
             <li>Ayudar a las empresas a tomar decisiones informadas sobre su estrategia de recursos humanos, como la contratación de personal, la equidad de género, nivelación de brechas salariales, la formación de empleados, la gestión de carrera, etc.</li> 
             <li>Entender las necesidades y vivencias de los colaboradores para ajustar su mapa de viaje y mejorar constantemente las experiencias laborales, mejorando los niveles de compromiso e incrementando los ratios de retención.</li> 
             <li>Identificar las tendencias en la transformación digital, para garantizar que los equipos de trabajo cuenten con competencias digitales actualizadas.</li> 
             <li>Detectar marcos de trabajo eficientes que incorporen metodologías ágiles y aplicables en empresas que buscan mejorar su eficacia y productividad.</li> 
            </ul>
               
            </div> 
            <div class="col-md-5">
                 <div class="ratio ratio-16x9">
                     <iframe src="https://www.youtube.com/embed/r7QfFhuBtJU" title="ONT" allowfullscreen>
                     </iframe> 
            </div> 
            <p class="mt-3">En resumen, el Observatorio Nacional del Trabajo (ONT) proporcionará información valiosa para el desarrollo de políticas y estrategias de Recursos Humanos...</p>
         </div> 
        </div>
        </div>  
         </section>

     <section id="normativas" class="section-ont">
    <div class="container">
        <h2 class="section-title">
            <i class="bi bi-file-text me-3"></i>Estudios Recientes
        </h2>
        <div class="row">
            <?php if (empty($normativas)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>No hay estudios activas disponibles actualmente.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($normativas as $normativa): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card card-ont h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start mb-3">
                                    <i class="bi bi-file-earmark-pdf text-danger me-3" style="font-size: 2rem;"></i>
                                    <div>
                                        <h5 class="card-title"><?= htmlspecialchars($normativa['titulo']) ?></h5>
                                        <p class="card-text"><?= substr(strip_tags($normativa['descripcion']), 0, 120) ?>...</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        <?= date('d/m/Y', strtotime($normativa['fecha_publicacion'])) ?>
                                    </small>
                                    <a href="./assets/uploads/normativas/<?= htmlspecialchars($normativa['archivo']) ?>" 
                                       target="_blank" 
                                       class="btn btn-ont-primary btn-sm">
                                        <i class="bi bi-download me-1"></i>Descargar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
     
    <section id="soporte" class="section-ont py-5">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title">
                    <i class="bi bi-people me-3"></i>Nuestro Equipo de Soporte
                </h2>
                <p class="section-description">Nos esforzamos por ofrecer una experiencia renovada y de calidad a los participantes en nuestros estudios, ya sea a través de entrevistas, encuestas o grupos focales.</p>
            </div>

            <div class="team-container">
                <div class="team-grid">
                    <div class="team-member">
                        <div class="member-image">
                            <img src="./assets/uploads/soporte/pedro.png" alt="Pedro Sáenz">
                            <div class="member-overlay">
                                <span class="badge-ont">Director</span>
                            </div>
                        </div>
                        <div class="member-info">
                            <h6>Pedro Sáenz</h6>
                            <p>Director Observatorio Nacional del Trabajo</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-image">
                            <img src="./assets/uploads/soporte/rafael.jpg" alt="Rafael Vidaurre">
                            <div class="member-overlay">
                                <span class="badge-ont">Coordinador</span>
                            </div>
                        </div>
                        <div class="member-info">
                            <h6>Rafael Vidaurre</h6>
                            <p>Coordinador Observatorio Nacional del Trabajo</p>
                        </div>
                    </div>
                    <div class="team-member">
                        <div class="member-image">
                            <img src="./assets/uploads/soporte/alejandro.jpg" alt="Alejandro de la Reza">
                            <div class="member-overlay">
                                <span class="badge-ont">Vicerrector</span>
                            </div>
                        </div>
                        <div class="member-info">
                            <h6>Alejandro de la Reza</h6>
                            <p>Vicerrector Nacional de Postgrado Unifranz</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
 
     <section id="aliados" class="section-ont py-5">
    
        <div class="section-header text-center mb-5">
            <h2 class="section-title">
                <i class="bi bi-handshake me-3"></i>Nuestros Aliados Estratégicos
            </h2>
            <p class="section-description">No es posible organizar estos estudios, investigaciones y otros, en diferentes lugares de manera aislada, por lo que contamos con el apoyo de nuestros aliados estratégicos con cuyo concurso y experiencia se hace más llano el camino que nos permita lograr nuestro objetivo en beneficio del país.</p>
        </div>

        <div class="partners-container">
               <div class="partners-grid" style="
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    grid-template-rows: repeat(2, auto);
    gap: 40px;
    justify-items: center;
    align-items: center;
">
    <img src="./assets/uploads/aliados/cni.png" alt="Cni" style="width: 150%; max-width: 300px; height: 180px; object-fit: contain;">
    <img src="./assets/uploads/aliados/cnc.png" alt="CNC" style="width: 150%; max-width: 300px; height: 280px; object-fit: contain;">
    <img src="./assets/uploads/aliados/CAINCO.png" alt="CAINCO" style="width: 100%; max-width: 300px; height: 180px; object-fit: contain;">
    <img src="./assets/uploads/aliados/logotipo_camebol.png" alt="CAMEBOL" style="width: 100%; max-width: 220px; height: 180px; object-fit: contain;">
    <img src="./assets/uploads/aliados/federacion.png" alt="Federación" style="width: 100%; max-width: 600px; height: 180px; object-fit: contain;">
    <img src="./assets/uploads/aliados/pnud.png" alt="PNUD" style="width: 100%; max-width: 300px; height: 180px; object-fit: contain;">
    <img src="./assets/uploads/aliados/logo-solidar.png" alt="SOLIDAR" style="width: 100%; max-width: 220px; height: 180px; object-fit: contain;">
    <img src="./assets/uploads/aliados/jubileo.png" alt="OIT" style="width: 100%; max-width: 180px; height: 180px; object-fit: contain;">
    <img src="./assets/uploads/aliados/unifranz.png" alt="Unifranz" style="width: 100%; max-width: 300px; height: 180px; object-fit: contain;">
</div>
            </div>
        </div>
    </div>
</section>

 
       <!-- Footer -->
    <?php include './partials/footer.php'; ?>
    
<!-- Popups -->
<?php foreach($popups_activos as $i => $p): ?>
<div id="popup-<?= $p['id'] ?>" 
     class="popup-overlay <?= $i === 0 ? 'show' : '' ?>" 
     style="<?= $i === 0 ? 'display:flex;' : 'display:none;' ?>">
    <div class="popup-content <?= $p['tipo']==='video' ? 'video-popup' : '' ?>"
         style="width: <?= $p['ancho'] ?>px; max-width: 90%; height: <?= $p['alto'] ?>px; max-height: 90%;">
        <div class="popup-header">
            <h5><?= htmlspecialchars($p['titulo']) ?></h5>
            
        </div> 
        <div class="popup-body">
            <?php if ($p['tipo']==='texto'): ?>
                <div class="popup-text-content">
                    <?= $p['contenido'] ?> <!-- HTML permitido -->
                </div>
            <?php elseif ($p['tipo']==='imagen' && !empty($p['archivo'])): ?>
                <img src="./assets/uploads/popups/<?= htmlspecialchars($p['archivo']) ?>" 
                     style="max-width:100%; max-height:100%; object-fit: contain;">
            <?php elseif ($p['tipo']==='video'): ?>
                <?php if (!empty($p['url_externa'])): ?>
                    <div class="ratio ratio-16x9">
                        <iframe src="<?= htmlspecialchars($p['url_externa']) ?>" 
                                allowfullscreen></iframe>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if (!empty($p['descripcion'])): ?>
                <div class="popup-description mt-3">
                    <p><?= htmlspecialchars($p['descripcion']) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
let currentIndex = 0;
const popups = document.querySelectorAll('.popup-overlay');

// Mostrar solo el primero
popups.forEach((p, i) => {
  p.style.display = (i === 0) ? 'flex' : 'none';
});

function cerrarPopup(id){
  const currentPopup = document.getElementById('popup-'+id);
  if (!currentPopup) return;

  // Detener video si es iframe
  const iframe = currentPopup.querySelector('iframe');
  if (iframe) {
    iframe.src = iframe.src; // Esto reinicia el video
  }
  
  // Detener video si es elemento video
  const video = currentPopup.querySelector('video');
  if (video) {
    video.pause();
    video.currentTime = 0;
  }

  // Ocultar popup actual
  currentPopup.classList.remove('show');
  currentPopup.style.display = 'none';

  // Guardar como visto en BD
  fetch('includes/popup_visto.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'popup_id='+encodeURIComponent(id)
  });

  // Avanzar al siguiente popup (si existe)
  currentIndex++;
  if (currentIndex < popups.length) {
    const nextPopup = popups[currentIndex];
    nextPopup.classList.add('show');
    nextPopup.style.display = 'flex';
  }
}

// Cerrar al hacer clic fuera del contenido
document.addEventListener('click', function(e){
  const overlays = document.querySelectorAll('.popup-overlay.show');
  overlays.forEach(ov => {
    const content = ov.querySelector('.popup-content');
    if (content && !content.contains(e.target)) {
      const id = ov.id.replace('popup-','');
      cerrarPopup(id);
    }
  });
});

// Cerrar con tecla ESC
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    const visiblePopup = document.querySelector('.popup-overlay.show');
    if (visiblePopup) {
      const id = visiblePopup.id.replace('popup-','');
      cerrarPopup(id);
    }
  }
});
</script>   
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/js/main.js"></script>
</body>
</html>
