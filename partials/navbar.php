<?php
// partials/navbar.php
require_once __DIR__ . '/../includes/auth.php';


// Obtener información del usuario actual
$usuarioActual = null;
if (isset($_SESSION['user_id'])) {
    $usuarioActual = [
        'id' => $_SESSION['user_id'],
        'nombre' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'avatar' => $_SESSION['user_avatar'] ?? null,
        'provider' => $_SESSION['provider'] ?? 'local'
    ];
}


?>
<style>
        :root {
            --ont-primary: #352f62;    /* Azul oscuro profesional */
            --ont-secondary: #e45504;  /* Naranja corporativo */
            --ont-light: #f5f7f9;      /* Gris claro para fondos */
            --ont-dark: #2c3e50;       /* Texto oscuro */
            --ont-accent: #3498db;     /* Azul claro para acentos */
        } 
        /* NAVEGACIÓN MEJORADA */
        .navbar-ont {
            background: linear-gradient(135deg, var(--ont-primary) 0%, #283593 100%);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            padding: 0.5rem 1rem;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
            display: flex;
            align-items: center;
        }
        
        .navbar-brand i {
            color: var(--ont-secondary);
            font-size: 1.8rem;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            border-radius: 4px;
            margin: 0 2px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover, .nav-link:focus {
            color: white !important;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .dropdown-menu {
            background: white;
            border: none;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-top: 8px !important;
            padding: 0.2rem 0;
        }
        
        .dropdown-item {
            color: var(--ont-dark);
            padding: 0.6rem 1.2rem;
            transition: all 0.9s ease;
            display: flex;
            align-items: center;
        }
        
        .dropdown-item i {
            margin-right: 8px;
            color: var(--ont-primary);
        }
        
        .dropdown-item:hover, .dropdown-item:focus {
            background-color: var(--ont-primary);
            color: white;
        }
        
        .dropdown-item:hover i, .dropdown-item:focus i {
            color: white;
        }
        
        /* Estilo para dropdown en hover */
        .navbar-nav .dropdown:hover > .dropdown-menu {
            display: block;
        }
        
        .dropdown-toggle::after {
            transition: transform 0.3s ease;
        }
        
        .dropdown:hover .dropdown-toggle::after {
            transform: rotate(180deg);
        }
        
        /* Botones */
        .btn-ont-primary {
            background-color: var(--ont-secondary);
            border: none;
            color: white;
            padding: 0.5rem 1.2rem;
            border-radius: 4px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-ont-primary:hover {
            background-color: #c44a04;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-text {
            color: rgba(255, 255, 255, 0.85) !important;
            padding-right: 1rem;
        }
        
        /* Responsive */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: linear-gradient(135deg, var(--ont-primary) 0%, #283593 100%);
                padding: 1rem;
                border-radius: 8px;
                margin-top: 10px;
            }
            
            .dropdown-menu {
                margin-left: 1rem;
                width: 90%;
            }
        }
        
        /* Contenido de ejemplo */
        .content-section {
            padding: 7rem 2rem 3rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .article-title {
            color: var(--ont-primary);
            margin-bottom: 1.5rem;
            font-weight: 700;
        }
        
        .article-content {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .tag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 1.5rem 0;
        }
        
        .tag {
            background: var(--ont-light);
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            color: var(--ont-dark);
        }
        
        .tag.active {
            background: var(--ont-primary);
            color: white;
        }
    </style>

<!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-ont fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <img src="assets\uploads\general\Logo_superior-125x52.png" alt="Logo" width="100" height="40" class="d-inline-block align-text-top">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="index.php#noticias" id="navbarDropdownNoticias" role="button">
                            <i class="bi bi-newspaper me-1"></i>Noticias
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdownNoticias">
                            <li><a class="dropdown-item" href="noticias.php">Todas las Noticias</a></li>
                            
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="index.php#normativas" id="navbarDropdownNormativas" role="button">
                            <i class="bi bi-file-text me-1"></i>Estudios
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdownNormativas">
                            <li><a class="dropdown-item" href="normativas.php">Todos los Estudios</a></li>
                            
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="encuestas.php" role="button">
                            <i class="bi bi-clipboard-check me-1"></i>Encuestas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#sobre-ont" role="button">
                            <i class="bi bi-info-circle me-1"></i>Sobre ONT
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#soporte" role="button">
                            <i class="bi bi-question-circle me-1"></i>Soporte
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="index.php#aliados">
                            <i class="bi bi-handshake me-1"></i>Aliados
                        </a>
                    </li>
                </ul>
                
               <div class="d-flex align-items-center">
                <?php if (isset($_SESSION['user_name'])): ?>
                    <div class="dropdown">
                        <button class="btn btn-link text-white dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown">
                            <?php if ($usuarioActual && $usuarioActual['avatar']): ?>
                                <!-- Mostrar avatar de Google -->
                                <img src="<?= htmlspecialchars($usuarioActual['avatar']) ?>" 
                                     alt="<?= htmlspecialchars($_SESSION['user_name']) ?>"
                                     class="user-avatar rounded-circle me-2"
                                     style="width: 32px; height: 32px; object-fit: cover;">
                            <?php else: ?>
                                <!-- Mostrar inicial o icono por defecto -->
                                <div class="user-avatar-default rounded-circle d-flex align-items-center justify-content-center me-2"
                                     style="width: 32px; height: 32px; background-color: #e45504; color: white; font-weight: bold;">
                                    <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            
                            <?= htmlspecialchars($_SESSION['user_name']) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="perfil.php">
                                    <i class="bi bi-person me-2"></i>Mi Perfil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="encuestas.php">
                                    <i class="bi bi-clipboard-check me-2"></i>Encuestas
                                </a>
                            </li>
                            
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="./auth/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="btn btn-outline-light" href="./auth/login.php">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Iniciar Sesión
                    </a>
                <?php endif; ?>
            </div>
            </div>
        </div>
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropdowns = document.querySelectorAll('.dropdown');
            let hideTimeouts = {};
            
            dropdowns.forEach(function(dropdown) {
                dropdown.addEventListener('mouseenter', function() {
                    // Cancelar el timeout de ocultar si existe
                    const dropdownId = this.id || this.querySelector('a').id;
                    if (hideTimeouts[dropdownId]) {
                        clearTimeout(hideTimeouts[dropdownId]);
                        delete hideTimeouts[dropdownId];
                    }
                    
                    // Mostrar el dropdown inmediatamente
                    this.classList.add('show');
                    this.querySelector('.dropdown-menu').classList.add('show');
                });
                
                dropdown.addEventListener('mouseleave', function() {
                    const dropdownElement = this;
                    const dropdownId = this.id || this.querySelector('a').id;
                    
                    // Establecer un timeout para ocultar el dropdown después de 300ms
                    hideTimeouts[dropdownId] = setTimeout(function() {
                        dropdownElement.classList.remove('show');
                        dropdownElement.querySelector('.dropdown-menu').classList.remove('show');
                        delete hideTimeouts[dropdownId];
                    }, 300); // 300ms de retraso antes de ocultar
                });
                
                // También agregar eventos al menú desplegable mismo
                const dropdownMenu = dropdown.querySelector('.dropdown-menu');
                if (dropdownMenu) {
                    dropdownMenu.addEventListener('mouseenter', function() {
                        const dropdownId = dropdown.id || dropdown.querySelector('a').id;
                        if (hideTimeouts[dropdownId]) {
                            clearTimeout(hideTimeouts[dropdownId]);
                            delete hideTimeouts[dropdownId];
                        }
                    });
                
                    dropdownMenu.addEventListener('mouseleave', function() {
                        const dropdownId = dropdown.id || dropdown.querySelector('a').id;
                        hideTimeouts[dropdownId] = setTimeout(function() {
                            dropdown.classList.remove('show');
                            dropdown.querySelector('.dropdown-menu').classList.remove('show');
                            delete hideTimeouts[dropdownId];
                        }, 300); // 300ms de retraso antes de ocultar
                    });
                }
            });
        });
    </script>