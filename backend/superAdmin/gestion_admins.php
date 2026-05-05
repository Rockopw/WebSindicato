<?php
// 1. Configuración de errores y sesión
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. Protección de sesión (Solo SuperAdmin)
if (!isset($_SESSION['id_admin'])) {
    header("Location: login_2.php");
    exit();
}

// 3. Conexión a la base de datos
$host = 'localhost'; $db = 'sindicato'; $user = 'rudagaleano'; $pass = 'Napst3rfarr3l'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// 4. LÓGICA DE ELIMINAR
if (isset($_GET['eliminar']) && isset($_GET['tabla'])) {
    $dni_eliminar = $_GET['eliminar'];
    $tipo_tabla = $_GET['tabla']; // 'general' o 'deporte'
    $tabla_real = ($tipo_tabla == 'deporte') ? 'admin_deporte' : 'administrador';

    // Evitar que el admin se borre a sí mismo si está en la tabla general
    if ($dni_eliminar == $_SESSION['id_admin'] && $tipo_tabla == 'general') {
        header("Location: gestion_admins.php?res=error_autodelete");
    } else {
        $stmt = $pdo->prepare("DELETE FROM $tabla_real WHERE dni = ?");
        $stmt->execute([$dni_eliminar]);
        header("Location: gestion_admins.php?res=eliminado");
    }
    exit;
}

// --- LÓGICA DE GUARDAR (Nuevo o Editar) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre     = $_POST['nombre'];
    $apellido   = $_POST['apellido'];
    $dni        = $_POST['dni'];
    $email      = $_POST['email'];
    $tipo_admin = $_POST['tipo_admin'];
    
    // 1. Ciframos la contraseña antes de cualquier otra cosa
    // Usamos PASSWORD_DEFAULT que actualmente utiliza el algoritmo bcrypt
    $password_plana = $_POST['contrasena'];
    $password_cifrada = password_hash($password_plana, PASSWORD_DEFAULT);

    $tabla_dest = ($tipo_admin == 'deporte') ? 'admin_deporte' : 'administrador';
    $es_edicion = !empty($_POST['dni_original']);

    if ($es_edicion) {
        // UPDATE: Usamos la versión cifrada
        $sql = "UPDATE $tabla_dest SET nombre=?, apellido=?, dni=?, contrasena=?, email=? WHERE dni=?";
        $pdo->prepare($sql)->execute([$nombre, $apellido, $dni, $password_cifrada, $email, $_POST['dni_original']]);
    } else {
        // INSERT: Usamos la versión cifrada
        $sql = "INSERT INTO $tabla_dest (nombre, apellido, dni, contrasena, email) VALUES (?,?,?,?,?)";
        $pdo->prepare($sql)->execute([$nombre, $apellido, $dni, $password_cifrada, $email]);
    }
    header("Location: gestion_admins.php?res=ok");
    exit;
}

// 6. OBTENER TODOS LOS ADMINS (UNION de ambas tablas)
$sql_union = "
    SELECT nombre, apellido, dni, email, contrasena, 'general' as tipo FROM administrador
    UNION 
    SELECT nombre, apellido, dni, email, contrasena, 'deporte' as tipo FROM admin_deporte
    ORDER BY apellido ASC";
$admins = $pdo->query($sql_union)->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión Unificada de Administradores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="estilo_superadmin.css"> 
    <link rel="icon" href="imagenes/super admin.ico">
    <style>
        .badge-general { background-color: #0d6efd; }
        .badge-deporte { background-color: #198754; }
        .card { border-radius: 15px; }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="card p-4 shadow border-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="text-dark fw-bold m-0"><i class="fas fa-user-shield me-2"></i> Gestión de Administradores</h3> 
            <button class="btn btn-success px-4 fw-bold" onclick="abrirModalAdmin(null)">
                <i class="fas fa-plus-circle me-1"></i> Nuevo Administrador
            </button>
        </div>

        <?php if(isset($_GET['res'])): ?>
            <div class="alert alert-<?php 
                echo ($_GET['res'] == 'error_autodelete') ? 'danger' : 'success'; 
            ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <?php 
                    if($_GET['res'] == 'ok') echo "¡Datos guardados correctamente!";
                    if($_GET['res'] == 'eliminado') echo "El administrador fue eliminado del sistema.";
                    if($_GET['res'] == 'error_autodelete') echo "<strong>Error:</strong> No puedes eliminar tu propia cuenta de administrador general.";
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table id="tablaAdmins" class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>DNI</th>
                        <th>Nombre y Apellido</th>
                        <th>Email</th>
                        <th>Área / Tabla</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $ad): ?>
                    <tr>
                        <td class="fw-bold"><?= $ad['dni'] ?></td>
                        <td><?= htmlspecialchars($ad['apellido'] . ", " . $ad['nombre']) ?></td>
                        <td><?= htmlspecialchars($ad['email']) ?></td>
                        <td>
                            <span class="badge <?= $ad['tipo'] == 'general' ? 'badge-general' : 'badge-deporte' ?> px-3 py-2">
                                <i class="fas <?= $ad['tipo'] == 'general' ? 'fa-cog' : 'fa-running' ?> me-1"></i>
                                <?= strtoupper($ad['tipo']) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-warning me-1" onclick='abrirModalAdmin(<?= json_encode($ad) ?>)'>
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <a href="?eliminar=<?= $ad['dni'] ?>&tabla=<?= $ad['tipo'] ?>" 
                               class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('¿Estás seguro de que deseas eliminar este administrador de la tabla <?= $ad['tipo'] ?>?')">
                                <i class="fas fa-trash"></i> Borrar
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="text-center mt-4 pt-3 border-top">
         
            <a href="logout2.php" class="btn btn-outline-danger" onclick="return confirm('¿Cerrar sesión?')">
                <i class="fas fa-sign-out-alt me-1"></i> Cerrar sesión
            </a>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAdmin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content shadow-lg border-0">
            <input type="hidden" name="dni_original" id="dni_original">
            
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="tituloModal">Nuevo Administrador</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold text-primary">Tipo de Acceso / Tabla</label>
                    <select name="tipo_admin" id="mTipo" class="form-select border-primary" required>
                        <option value="general">General (Tabla: administrador)</option>
                        <option value="deporte">Deportes (Tabla: admin_deporte)</option>
                    </select>
                    <small class="text-muted">Define en qué tabla se guardará el registro.</small>
                </div>

                <hr>

                <div class="mb-3">
                    <label class="form-label">DNI (Usuario)</label>
                    <input type="text" name="dni" id="mDni" class="form-control" placeholder="Ingrese DNI sin puntos" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" id="mNom" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Apellido</label>
                        <input type="text" name="apellido" id="mApe" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                        <input type="text" name="contrasena" id="mPass" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email de contacto</label>
                    <input type="email" name="email" id="mEmail" class="form-control" placeholder="ejemplo@correo.com" required>
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary px-4">Guardar Administrador</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
let modalInstancia = new bootstrap.Modal(document.getElementById('modalAdmin'));

$(document).ready(function() {
    $('#tablaAdmins').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        pageLength: 10,
        order: [[1, 'asc']] // Ordenar por apellido por defecto
    });
});

function abrirModalAdmin(data) {
    if (data) {
        // MODO EDICIÓN
        document.getElementById('tituloModal').innerText = "Editar Administrador";
        document.getElementById('dni_original').value = data.dni;
        document.getElementById('mDni').value = data.dni;
        document.getElementById('mNom').value = data.nombre;
        document.getElementById('mApe').value = data.apellido;
        document.getElementById('mPass').value = data.contrasena;
        document.getElementById('mEmail').value = data.email;
        document.getElementById('mTipo').value = data.tipo;
        
        // Bloqueamos el tipo al editar para evitar inconsistencias de DNI entre tablas
        document.getElementById('mTipo').style.pointerEvents = "none";
        document.getElementById('mTipo').style.background = "#e9ecef";
    } else {
        // MODO NUEVO
        document.getElementById('tituloModal').innerText = "Nuevo Administrador";
        document.getElementById('dni_original').value = "";
        document.getElementById('mDni').value = "";
        document.getElementById('mNom').value = "";
        document.getElementById('mApe').value = "";
        document.getElementById('mPass').value = "";
        document.getElementById('mEmail').value = "";
        document.getElementById('mTipo').value = "general";
        
        document.getElementById('mTipo').style.pointerEvents = "auto";
        document.getElementById('mTipo').style.background = "#fff";
    }
    modalInstancia.show();
}
</script>
</body>
</html>