<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pagina = basename($_SERVER['PHP_SELF']);
function isActive($file, $pagina) {
    return $pagina === $file ? 'active' : '';
}
?>

<style>
/* Avatar con fondo azul original + brillo suave tipo línea */
.user-avatar {
  width: 50px;
  height: 50px;
  background: linear-gradient(135deg, #352f62 0%, #4a4374 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 1rem;
  font-weight: bold;
  color: #e45504;
  font-size: 1.25rem;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25),
              0 0 6px rgba(228, 85, 4, 0.4);
  border: 2px solid #e45504;
  position: relative;
  overflow: hidden;
}



.user-avatar::after {
  content: "";
  position: absolute;
  top: 0;
  left: -100%;
  width: 50%;
  height: 100%;
  background: linear-gradient(
    120deg,
    transparent,
    rgba(255, 255, 255, 0.4),
    transparent
  );
  animation: shine 2.2s infinite ease-in-out;
  pointer-events: none;
}

@keyframes shine {
  0% { left: -100%; }
  60% { left: 120%; }
  100% { left: 120%; }
}

/* ========== ESTILOS RESPONSIVE CON SCROLL ========== */

/* Para móvil */
@media (max-width: 768px) {
  .admin-sidebar {
    position: fixed;
    left: -280px;
    top: 0;
    height: 100vh;
    width: 280px;
    z-index: 1000;
    transition: left 0.3s ease;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;

    overflow-y: auto;   /* ← ACTIVA EL SCROLL */
    overflow-x: hidden; /* ← EVITA SCROLL HORIZONTAL */
  }
  
  .admin-sidebar.active {
    left: 0;
  }
  
  .sidebar-nav {
    flex: 1;
    overflow-y: auto; /* Scroll solo en el área de navegación */
    padding-right: 5px; /* Espacio para el scroll */
  }
  
  /* Personalizar scrollbar */
  .sidebar-nav::-webkit-scrollbar {
    width: 6px;
  }
  
  .sidebar-nav::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 3px;
  }
  
  .sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 3px;
  }
  
  .sidebar-nav::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
  }
  
  .sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
    display: none;
  }
  
  .sidebar-overlay.active {
    display: block;
  }
  
  .mobile-sidebar-toggle {
    display: flex !important;
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 998;
    background: #352f62;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 10px 15px;
    font-size: 1.2rem;
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
  }
  
  .main-content {
    margin-left: 0 !important;
    padding-top: 60px;
  }
  
  .sidebar-footer {
    flex-shrink: 0; /* Evita que el footer se reduzca */
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding: 20px;
    background: #1a1a2e;
  }
}

/* Para desktop */
@media (min-width: 769px) {
  .admin-sidebar {
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    width: 280px;
    display: flex;
    flex-direction: column;
    overflow: hidden; /* Contenedor padre sin scroll */
  }
  
  .sidebar-nav {
    flex: 1;
    overflow-y: auto; /* Scroll solo en el área de navegación */
    padding-right: 10px; /* Espacio para el scroll */
    margin-right: -10px; /* Compensar padding */
  }
  
  /* Personalizar scrollbar para desktop */
  .sidebar-nav::-webkit-scrollbar {
    width: 8px;
  }
  
  .sidebar-nav::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 4px;
  }
  
  .sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 4px;
  }
  
  .sidebar-nav::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.25);
  }
  
  /* Ocultar scrollbar cuando no se necesita */
  .sidebar-nav {
    scrollbar-width: thin; /* Firefox */
    scrollbar-color: rgba(255, 255, 255, 0.15) rgba(255, 255, 255, 0.05);
  }
  
  .mobile-sidebar-toggle {
    display: none !important;
  }
  
  .sidebar-overlay {
    display: none !important;
  }
  
  .main-content {
    margin-left: 280px;
  }
  
  .sidebar-footer {
    flex-shrink: 0; /* Evita que el footer se reduzca */
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding: 20px;
    background: #1a1a2e;
  }
}

/* ========== ESTILOS BASE DEL SIDEBAR ========== */
.admin-sidebar {
  background: #1a1a2e;
  color: white;
  padding: 20px 0 0 0; /* Quitar padding inferior porque ahora usamos flex */
  display: flex;
  flex-direction: column;
  width: 280px;
}

.sidebar-header {
  padding: 0 20px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  flex-shrink: 0; /* Evita que el header se reduzca */
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  color: white;
  text-decoration: none;
  font-size: 1.2rem;
  font-weight: 600;
}

.sidebar-nav {
  flex: 1;
  padding: 20px 0;
  /* El scroll se maneja con overflow-y: auto en los media queries */
}

.nav-item {
  margin: 5px 15px;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 15px;
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.nav-link:hover {
  background: rgba(255, 255, 255, 0.1);
  color: white;
}

.nav-link.active {
  background: #e45504;
  color: white;
}

.sidebar-footer {
  flex-shrink: 0; /* Evita que el footer se reduzca */
  padding: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  background: #1a1a2e;
}

.user-info {
  display: flex;
  align-items: center;
  margin-bottom: 15px;
}

/* ========== MEJORAS PARA EL SCROLL ========== */

/* Evitar que el contenido se desborde en móvil */
.admin-sidebar {
  -webkit-overflow-scrolling: touch; /* Scroll suave en iOS */
}

/* Asegurar que el contenido no se desborde */
.sidebar-nav {
  min-height: 0; /* Necesario para flexbox con scroll */
}

/* Ajustes para Firefox */
@supports (scrollbar-width: thin) {
  .sidebar-nav {
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.15) rgba(255, 255, 255, 0.05);
  }
}

/* Para navegadores que no soportan scrollbar styling */
.sidebar-nav {
  overflow-y: auto;
  -ms-overflow-style: -ms-autohiding-scrollbar; /* IE/Edge */
}
</style>

<!-- Botón para móvil -->
<button class="mobile-sidebar-toggle" id="sidebarToggle">
  <i class="bi bi-list"></i>
</button>

<!-- Overlay para móvil -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<nav class="admin-sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="index.php" class="sidebar-brand">
            <i class="bi bi-graph-up-arrow"></i>
            Panel ONT
        </a>
    </div>
    
    <div class="sidebar-nav">
        <div class="nav-item">
            <a href="index.php" class="nav-link <?= isActive('index.php', $pagina) ?>">
                <i class="bi bi-speedometer2"></i>
                Dashboard
            </a>
        </div>
        <div class="nav-item">
            <a href="noticias.php" class="nav-link <?= isActive('noticias.php', $pagina) ?>">
                <i class="bi bi-newspaper"></i>
                Noticias
            </a>
        </div>
        <div class="nav-item">
            <a href="normativas.php" class="nav-link <?= isActive('normativas.php', $pagina) ?>">
                <i class="bi bi-file-earmark-text"></i>
                Estudios
            </a>
        </div>
        <div class="nav-item">
            <a href="carrusel.php" class="nav-link <?= isActive('carrusel.php', $pagina) ?>">
                <i class="bi bi-images"></i>
                Carrusel
            </a>
        </div>
        <div class="nav-item">
            <a href="encuestas.php" class="nav-link <?= isActive('encuestas.php', $pagina) ?>">
                <i class="bi bi-list-check"></i>
                Encuestas
            </a>
        </div>
        <div class="nav-item">
            <a href="usuarios.php" class="nav-link <?= isActive('usuarios.php', $pagina) ?>">
                <i class="bi bi-people"></i>
                Usuarios
            </a>
        </div>
        <div class="nav-item">
            <a href="pop_ups.php" class="nav-link <?= isActive('pop_ups.php', $pagina) ?>">
                <i class="bi bi-window-stack"></i>
                Pop-ups
            </a>
        </div>
        <div class="nav-item">
            <a href="comentarios.php" class="nav-link <?= isActive('comentarios.php', $pagina) ?>">
                <i class="bi bi-chat-dots"></i>
                Comentarios
            </a>
        </div>
    </div>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
            </div>
            <div>
                <div style="font-weight: 600; font-size: 0.9rem;">
                    <?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?>
                </div>
                <div style="font-size: 0.8rem; opacity: 0.8;">
                    Administrador
                </div>
            </div>
        </div>
        <a href="../auth/logout.php" class="btn-ont secondary" style="width: 100%; justify-content: center;">
            <i class="bi bi-box-arrow-right"></i>
            Cerrar Sesión
        </a>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const sidebarNav = document.querySelector('.sidebar-nav');
    
    // Alternar sidebar en móvil
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        sidebarOverlay.classList.toggle('active');
        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
        
        // Restaurar scroll al abrir sidebar
        if (sidebar.classList.contains('active')) {
            sidebarNav.scrollTop = 0;
        }
    });
    
    // Cerrar sidebar al hacer clic en el overlay
    sidebarOverlay.addEventListener('click', function() {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        document.body.style.overflow = '';
    });
    
    // Cerrar sidebar al hacer clic en un enlace (en móvil)
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });
    
    // Ajustar al redimensionar la ventana
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
    
    // Prevenir scroll del body cuando el sidebar está abierto en móvil
    sidebarNav.addEventListener('touchmove', function(e) {
        e.stopPropagation(); // Permite scroll solo dentro del sidebar
    }, { passive: true });
});
</script>