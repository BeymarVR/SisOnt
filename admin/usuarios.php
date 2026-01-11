<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/usuarios_functions.php';
verificarRol('admin');

// Procesar eliminación
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    
    // Verificar que no sea el propio usuario administrador
    if ($id != $_SESSION['user_id']) {
        if (eliminarUsuario($id)) {
            $_SESSION['mensaje'] = "Usuario eliminado correctamente";
            $_SESSION['tipo_mensaje'] = "success";
        } else {
            $_SESSION['mensaje'] = "Error al eliminar el usuario";
            $_SESSION['tipo_mensaje'] = "danger";
        }
    } else {
        $_SESSION['mensaje'] = "No puedes eliminar tu propio usuario";
        $_SESSION['tipo_mensaje'] = "warning";
    }
    
    header("Location: usuarios.php");
    exit();
}

// Manejar acciones
$action = $_GET['action'] ?? 'list';
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $biografia = trim($_POST['biografia'] ?? '');
    $rol_id = isset($_POST['rol_id']) ? intval($_POST['rol_id']) : 0;
    $activo = isset($_POST['activo']) ? 1 : 0;

    $conexion = obtenerConexion();

    if ($action === 'new') {
        // Verificar si el email ya existe
        if (emailExiste($email)) {
            $message = 'El email ya está registrado';
            $messageType = 'error';
        } else {
            $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("
                INSERT INTO usuarios 
                (nombre, email, password, telefono, biografia, rol_id, activo, provider) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if ($stmt) {
                $provider = 'local';
                $stmt->bind_param("ssssssii", $nombre, $email, $password, $telefono, $biografia, $rol_id, $activo, $provider);
                if ($stmt->execute()) {
                    $message = 'Usuario creado exitosamente';
                    $messageType = 'success';
                } else {
                    $message = 'Error al crear el usuario';
                    $messageType = 'error';
                }
            } else {
                $message = 'Error en la consulta al crear usuario';
                $messageType = 'error';
            }
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $message = 'ID de usuario inválido';
            $messageType = 'error';
        } else {
            // Verificar si el usuario es de Google
            $stmt = $conexion->prepare("SELECT provider FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $usuario_data = $result->fetch_assoc();
            $esGoogle = $usuario_data && $usuario_data['provider'] === 'google';

            // Usar transacción para garantizar consistencia
            $conexion->begin_transaction();
            try {
                if ($esGoogle) {
                    // ⭐ Para usuarios de Google: SOLO actualizar rol_id y activo
                    $stmt = $conexion->prepare("
                        UPDATE usuarios 
                        SET rol_id = ?, activo = ?
                        WHERE id = ?
                    ");
                    $types = "iii";
                    $params = [$rol_id, $activo, $id];
                } else {
                    // Para usuarios locales: actualizar todo
                    if (!empty($_POST['password'])) {
                        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt = $conexion->prepare("
                            UPDATE usuarios 
                            SET nombre = ?, email = ?, password = ?, telefono = ?, biografia = ?, rol_id = ?, activo = ?
                            WHERE id = ?
                        ");
                        $types = "sssssiii";
                        $params = [$nombre, $email, $password, $telefono, $biografia, $rol_id, $activo, $id];
                    } else {
                        $stmt = $conexion->prepare("
                            UPDATE usuarios 
                            SET nombre = ?, email = ?, telefono = ?, biografia = ?, rol_id = ?, activo = ?
                            WHERE id = ?
                        ");
                        $types = "ssssiii";
                        $params = [$nombre, $email, $telefono, $biografia, $rol_id, $activo, $id];
                    }
                }

                if (!$stmt) {
                    throw new Exception("Error en la preparación de la consulta: " . $conexion->error);
                }

                // Bind dinámico
                $stmt->bind_param($types, ...$params);

                if (!$stmt->execute()) {
                    throw new Exception("Error al ejecutar actualización: " . $stmt->error);
                }

                // Commit y mensaje de éxito
                $conexion->commit();
                $message = 'Usuario actualizado exitosamente';
                $messageType = 'success';
            } catch (Exception $ex) {
                $conexion->rollback();
                $message = 'Error al actualizar el usuario: ' . $ex->getMessage();
                $messageType = 'error';
            }
        }
    }

    if ($messageType === 'success') {
        header("Location: usuarios.php?message=" . urlencode($message) . "&type=" . $messageType);
        exit();
    }
}

// Obtener datos para edición
$usuario = null;
$esGoogle = false;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $usuario = obtenerUsuarioPorId($id);
    $esGoogle = $usuario && $usuario['provider'] === 'google';
}

// Obtener lista de usuarios con roles
$usuarios = obtenerResultados("
    SELECT u.*, r.nombre as rol_nombre 
    FROM usuarios u 
    LEFT JOIN roles r ON u.rol_id = r.id 
    ORDER BY u.fecha_registro DESC
");

// Obtener roles disponibles
$roles = obtenerResultados("SELECT * FROM roles ORDER BY nombre");

// Mensajes de la URL
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'] ?? 'info';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - ONT Bolivia</title>
    <link rel="icon" type="image/png" href="../assets/images/logo_sup.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        .last-access {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .biografia-preview {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .google-badge {
            display: inline-block;
            background-color: #4285f4;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .form-control:disabled, 
        .form-select:disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
            opacity: 0.7;
        }
        .disabled-field-notice {
            font-size: 0.85rem;
            color: #0d6efd;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
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
                <h1 class="page-title">
                    <?php if ($action === 'new'): ?>
                        Nuevo Usuario
                    <?php elseif ($action === 'edit'): ?>
                        Editar Usuario
                        <?php if ($esGoogle): ?>
                            <span class="google-badge ms-2"><i class="bi bi-google me-1"></i>Google</span>
                        <?php endif; ?>
                    <?php else: ?>
                        Gestión de Usuarios
                    <?php endif; ?>
                </h1>
            </div>
        </header>

        <div class="content-area">
            <!-- Mostrar mensajes -->
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-<?= $_SESSION['tipo_mensaje'] ?> alert-dismissible fade show">
                    <?= $_SESSION['mensaje'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
            <?php endif; ?>
            
            <?php if ($action === 'list' || !$action): ?>
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="stat-header">
                            <div class="stat-icon primary">
                                <i class="bi bi-people"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?= count($usuarios) ?></h3>
                            <p>Total Usuarios</p>
                        </div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-header">
                            <div class="stat-icon success">
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?= count(array_filter($usuarios, fn($u) => $u['activo'])) ?></h3>
                            <p>Activos</p>
                        </div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-header">
                            <div class="stat-icon warning">
                                <i class="bi bi-shield-check"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?= count(array_filter($usuarios, fn($u) => $u['rol_nombre'] === 'admin')) ?></h3>
                            <p>Administradores</p>
                        </div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-header">
                            <div class="stat-icon info">
                                <i class="bi bi-clock-history"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?= count(array_filter($usuarios, fn($u) => !empty($u['ultimo_acceso']))) ?></h3>
                            <p>Con acceso reciente</p>
                        </div>
                    </div>
                </div>

                <!-- Content Header -->
                <div class="content-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="content-title">Lista de Usuarios</h2>
                            <p class="content-subtitle">Gestiona los usuarios y sus permisos del sistema</p>
                        </div>
                        <div class="header-actions">
                            <a href="usuarios.php?action=new" class="btn-ont primary">
                                 <i class="bi bi-plus-circle"></i>
                                Nuevo Usuario
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
                    <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($action === 'new' || $action === 'edit'): ?>
                <!-- User Form -->
                <div class="form-card">
                    <?php if ($esGoogle): ?>
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Usuario registrado con Google:</strong> Solo puedes cambiar el rol y el estado activo. 
                        Los datos personales están vinculados a la cuenta de Google.
                    </div>
                    <?php endif; ?>

                    <h4>
                        <i class="bi bi-<?= $action === 'new' ? 'plus-circle' : 'pencil-square' ?> me-2"></i>
                        <?= $action === 'new' ? 'Crear Nuevo Usuario' : 'Editar Usuario' ?>
                    </h4>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="mb-3">
                                    <label class="form-label">Nombre Completo <?= !$esGoogle ? '*' : '' ?></label>
                                    <input type="text" name="nombre" class="form-control" 
                                           value="<?= $usuario ? htmlspecialchars($usuario['nombre']) : '' ?>" 
                                           <?= $esGoogle ? 'disabled' : 'required' ?>>
                                    <?php if ($esGoogle): ?>
                                    <div class="disabled-field-notice">
                                        <i class="bi bi-lock"></i>Vinculado a Google
                                    </div>
                                    <?php endif; ?>
                                    <div class="invalid-feedback">
                                        Por favor ingrese el nombre completo.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Email <?= !$esGoogle ? '*' : '' ?></label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?= $usuario ? htmlspecialchars($usuario['email']) : '' ?>" 
                                           <?= $esGoogle ? 'disabled' : 'required' ?>>
                                    <?php if ($esGoogle): ?>
                                    <div class="disabled-field-notice">
                                        <i class="bi bi-lock"></i>Vinculado a Google
                                    </div>
                                    <?php endif; ?>
                                    <div class="invalid-feedback">
                                        Por favor ingrese un email válido.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Teléfono</label>
                                    <input type="tel" name="telefono" class="form-control" 
                                           value="<?= $usuario ? htmlspecialchars($usuario['telefono'] ?? '') : '' ?>"
                                           <?= $esGoogle ? 'disabled' : '' ?>>
                                    <?php if ($esGoogle): ?>
                                    <div class="disabled-field-notice">
                                        <i class="bi bi-lock"></i>Vinculado a Google
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Biografía</label>
                                    <textarea name="biografia" class="form-control" rows="3"
                                              placeholder="Breve descripción del usuario"
                                              <?= $esGoogle ? 'disabled' : '' ?>><?= $usuario ? htmlspecialchars($usuario['biografia'] ?? '') : '' ?></textarea>
                                    <?php if ($esGoogle): ?>
                                    <div class="disabled-field-notice">
                                        <i class="bi bi-lock"></i>Vinculado a Google
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <?php if (!$esGoogle): ?>
                                <div class="mb-3">
                                    <label class="form-label">Contraseña <?= $action === 'new' ? '*' : '(dejar vacío para mantener actual)' ?></label>
                                    <input type="password" name="password" class="form-control" 
                                           <?= $action === 'new' ? 'required' : '' ?>
                                           minlength="6">
                                    <div class="invalid-feedback">
                                        La contraseña debe tener al menos 6 caracteres.
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    La contraseña está gestionada por Google y no puede ser modificada aquí.
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label">Rol *</label>
                                    <select name="rol_id" class="form-select" required>
                                        <option value="">Seleccionar rol</option>
                                        <?php foreach ($roles as $rol): ?>
                                            <option value="<?= $rol['id'] ?>" 
                                                    <?= ($usuario && $usuario['rol_id'] == $rol['id']) ? 'selected' : '' ?>>
                                                <?= ucfirst($rol['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Por favor seleccione un rol.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" name="activo" class="form-check-input" id="activo"
                                               <?= (!$usuario || $usuario['activo']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="activo">
                                            Usuario activo
                                        </label>
                                    </div>
                                </div>

                                <?php if ($action === 'edit' && $usuario): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title">Información adicional</h6>
                                        <p class="card-text small mb-1">
                                            <strong>Registrado:</strong> 
                                            <?= date('d/m/Y H:i', strtotime($usuario['fecha_registro'])) ?>
                                        </p>
                                        <p class="card-text small mb-1">
                                            <strong>Proveedor:</strong>
                                            <?php if ($esGoogle): ?>
                                                <span class="google-badge"><i class="bi bi-google me-1"></i>Google</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Local</span>
                                            <?php endif; ?>
                                        </p>
                                        <?php if (!empty($usuario['ultimo_acceso'])): ?>
                                        <p class="card-text small mb-0">
                                            <strong>Último acceso:</strong> 
                                            <?= date('d/m/Y H:i', strtotime($usuario['ultimo_acceso'])) ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-ont-primary">
                                        <i class="bi bi-<?= $action === 'new' ? 'plus' : 'check' ?> me-2"></i>
                                        <?= $action === 'new' ? 'Crear Usuario' : 'Actualizar Usuario' ?>
                                    </button>
                                    <a href="usuarios.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-x me-2"></i>
                                        Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

            <?php else: ?>
                <!-- Users Table -->
                <div class="data-table">
                    <div class="table-header d-flex justify-content-between align-items-center mb-3">
                        <h5><i class="bi bi-people me-2"></i>Lista de Usuarios</h5>
                        <div class="table-actions">
                            <input type="text" class="form-control search-input" placeholder="Buscar usuarios..." 
                                   style="width: 250px;">
                        </div>
                    </div>
                    
                    <?php if (empty($usuarios)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>No hay usuarios registrados.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Contacto</th>
                                        <th>Proveedor</th>
                                        <th>Rol</th>
                                        <th>Estado</th>
                                        <th>Último acceso</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $user): ?>
                                        <tr class="searchable-item">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar me-3">
                                                        <?= strtoupper(substr($user['nombre'], 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold"><?= htmlspecialchars($user['nombre']) ?></div>
                                                        <?php if (!empty($user['biografia'])): ?>
                                                            <div class="text-muted biografia-preview" title="<?= htmlspecialchars($user['biografia']) ?>">
                                                                <?= htmlspecialchars(substr($user['biografia'], 0, 50)) ?>
                                                                <?= strlen($user['biografia']) > 50 ? '...' : '' ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div><?= htmlspecialchars($user['email']) ?></div>
                                                <?php if (!empty($user['telefono'])): ?>
                                                    <div class="text-muted"><?= htmlspecialchars($user['telefono']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($user['provider'] === 'google'): ?>
                                                    <span class="google-badge"><i class="bi bi-google me-1"></i>Google</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Local</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $user['rol_nombre'] === 'admin' ? 'danger' : ($user['rol_nombre'] === 'editor' ? 'warning' : 'info') ?>">
                                                    <?= ucfirst($user['rol_nombre'] ?? 'Sin rol') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $user['activo'] ? 'success' : 'secondary' ?>">
                                                    <?= $user['activo'] ? 'Activo' : 'Inactivo' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($user['ultimo_acceso'])): ?>
                                                    <span class="last-access" title="<?= date('d/m/Y H:i', strtotime($user['ultimo_acceso'])) ?>">
                                                        <?= date('d/m/Y', strtotime($user['ultimo_acceso'])) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">Nunca</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="usuarios.php?action=edit&id=<?= $user['id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary" title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                        <button class="btn btn-sm btn-outline-danger" 
                                                                title="Eliminar"
                                                                onclick="confirmarEliminacion(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nombre']) ?>')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    <script>
        // Validación de formulario
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()

        function confirmarEliminacion(id, nombre) {
            if (confirm(`¿Estás seguro de eliminar al usuario "${nombre}"?\nEsta acción no se puede deshacer.`)) {
                window.location.href = `usuarios.php?eliminar=${id}`;
            }
        }

        // Búsqueda en tiempo real
        document.querySelector('.search-input').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.searchable-item');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>
</html>