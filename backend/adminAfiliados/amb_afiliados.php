<?php
// 1. Configuración de errores y sesión
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("error", 3, "logs/php-error.log");

session_start();

// 2. Blindaje de Sesión
if (!isset($_SESSION['id_administrador'])) {
    header("Location: login_admin_afil.php");
    exit();
}

require_once "configpdo.php";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Error crítico de conexión: " . $e->getMessage());
}

// --- LÓGICA DE ELIMINACIÓN ---
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    // Opcional: Aquí podrías buscar los nombres de archivos y borrarlos de la carpeta /uploads
    $pdo->prepare("DELETE FROM familia WHERE afiliado_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM afiliados WHERE afiliados_id = ?")->execute([$id]);
    header("Location: amb_afiliados.php?res=eliminado");
    exit;
}

// --- LÓGICA AJAX PARA FAMILIARES ---
if (isset($_GET['get_familiares'])) {
    $stmt = $pdo->prepare("SELECT * FROM familia WHERE afiliado_id = ?");
    $stmt->execute([$_GET['get_familiares']]);
    echo json_encode($stmt->fetchAll());
    exit;
}

// --- PROCESAMIENTO DEL FORMULARIO (INSERT / UPDATE) ---
$error_msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // 1. Capturamos TODO primero
        $id       = !empty($_POST['afiliados_id']) ? $_POST['afiliados_id'] : null;
        $dni      = trim($_POST['dni_afiliado'] ?? '');
        $nombre   = trim($_POST['nombre_afiliado'] ?? '');
        $apellido = trim($_POST['apellido_afiliado'] ?? '');
        $email    = trim($_POST['email_afiliado'] ?? ''); // <--- Aseguramos que se limpie y capture
        $tel      = $_POST['telefono_afiliado'] ?? '';
        $f_nac    = !empty($_POST['fecha_nacimiento_afiliado']) ? $_POST['fecha_nacimiento_afiliado'] : null;
        $f_ing    = !empty($_POST['ingreso_afiliado']) ? $_POST['ingreso_afiliado'] : date('Y-m-d');
        $empresa  = $_POST['empresa_afiliado'] ?? '';

        // 2. VALIDACIÓN DE DNI DUPLICADO (Antes de iniciar transacción)
        if ($id) {
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM afiliados WHERE dni = ? AND afiliados_id <> ?");
            $stmtCheck->execute([$dni, $id]);
        } else {
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM afiliados WHERE dni = ?");
            $stmtCheck->execute([$dni]);
        }

        if ($stmtCheck->fetchColumn() > 0) {
            throw new Exception("​🚫​ Error: El DNI $dni ya pertenece a otro afiliado.");
        }

        // 3. Si pasó el chequeo, iniciamos transacción y manejo de archivos
        $pdo->beginTransaction();
        $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR;

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // --- MANEJO DE ARCHIVO DNI ---
        $doc_dni_final = $_POST['doc_dni_old'] ?? null;
        if (!empty($_FILES['doc_dni_afiliado']['name'])) {
            $ext = pathinfo($_FILES['doc_dni_afiliado']['name'], PATHINFO_EXTENSION);
            $doc_dni_final = "dni_" . $dni . "_" . time() . "." . $ext;
            move_uploaded_file($_FILES['doc_dni_afiliado']['tmp_name'], $upload_dir . $doc_dni_final);
        }

        // 4. EJECUCIÓN DE SQL (Asegurate que el orden de las variables sea el mismo de los ?)
        if ($id) {
            $sql = "UPDATE afiliados SET nombre=?, apellido=?, dni=?, email=?, telefono=?, fecha_nacimiento=?, fecha_ingreso=?, empresa=?, doc_dni=? WHERE afiliados_id=?";
            $pdo->prepare($sql)->execute([$nombre, $apellido, $dni, $email, $tel, $f_nac, $f_ing, $empresa, $doc_dni_final, $id]);
            // Limpiamos familiares para re-insertar (tu lógica original)
            $pdo->prepare("DELETE FROM familia WHERE afiliado_id = ?")->execute([$id]);
        } else {
            $pass_h = password_hash($_POST['contrasena'] ?: '123456', PASSWORD_DEFAULT);
            $sql = "INSERT INTO afiliados (nombre, apellido, dni, email, telefono, fecha_nacimiento, fecha_ingreso, empresa, contrasena, doc_dni) VALUES (?,?,?,?,?,?,?,?,?,?)";
            $pdo->prepare($sql)->execute([$nombre, $apellido, $dni, $email, $tel, $f_nac, $f_ing, $empresa, $pass_h, $doc_dni_final]);
            $id = $pdo->lastInsertId();
        }

      

        // --- MANEJO DE FAMILIARES ---
        if (!empty($_POST['f_nombre'])) {
            $ins_f = $pdo->prepare("INSERT INTO familia (afiliado_id, nombre, fecha_nacimiento, parentesco, sexo, doc_vinculo, doc_escolar) VALUES (?,?,?,?,?,?,?)");
            
            foreach ($_POST['f_nombre'] as $i => $f_nom) {
                if (!empty(trim($f_nom))) {
                    $vinc_file = $_POST['f_doc_vinculo_old'][$i] ?? null;
                    $esco_file = $_POST['f_doc_escolar_old'][$i] ?? null;

                    // Subida de Partida/Vínculo
                    if (!empty($_FILES['f_doc_vinculo']['name'][$i])) {
                        $ext = pathinfo($_FILES['f_doc_vinculo']['name'][$i], PATHINFO_EXTENSION);
                        $vinc_file = "vinc_" . $i . "_" . time() . "." . $ext;
                        move_uploaded_file($_FILES['f_doc_vinculo']['tmp_name'][$i], $upload_dir . $vinc_file);
                    }

                    // Subida de Certificado Escolar
                    if (!empty($_FILES['f_doc_escolar']['name'][$i])) {
                        $ext = pathinfo($_FILES['f_doc_escolar']['name'][$i], PATHINFO_EXTENSION);
                        $esco_file = "esc_" . $i . "_" . time() . "." . $ext;
                        move_uploaded_file($_FILES['f_doc_escolar']['tmp_name'][$i], $upload_dir . $esco_file);
                    }

                    $ins_f->execute([$id, $f_nom, $_POST['f_f_nac_fam'][$i], $_POST['f_parentesco'][$i], $_POST['f_sexo'][$i], $vinc_file, $esco_file]);
                }
            }
        }

        $pdo->commit();
        header("Location: amb_afiliados.php?res=ok");
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error_msg = $e->getMessage();
    }
}

$afiliados = $pdo->query("SELECT * FROM afiliados ORDER BY apellido ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sindicato - Gestión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="icon" href="../imagenes/victoria.ico">
    <link rel="stylesheet" href="estilo_amb.css"> 
    <style>
        body { background: #f8f9fa; }
        .card { border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .familiar-row { background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin-bottom: 12px; position: relative; }
        .admin-nombre { background: #e9ecef; padding: 5px 15px; border-radius: 20px; font-weight: bold; }
        .btn-logout { background: #dc3545; color: white; border-radius: 20px; padding: 5px 20px; text-decoration: none; }
        .btn-logout:hover { background: #c82333; color: white; }
        .btn-outline-secondary { background: #3447a2; color: white; text-align: center;border-radius: 20px; }
        .btn-outline-secondary:hover { background: #0f2281; color: white;border-radius: 20px; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <span class="admin-nombre">👤 ¡Hola, <?= htmlspecialchars($_SESSION['administrador_nombre']) ?>! Bienvenido 👋</span>
        <a href="logout_admin_afil.php" class="btn-logout" onclick="return confirm('¿Seguro?')">Cerrar sesión 🚪</a>
    </div>

    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="text-primary fw-bold m-0">Administración de Afiliados</h3>
            <button class="btn btn-success px-4" onclick="abrirModal(null)">Nuevo Afiliado 👤</button>
        </div>

        <?php if(isset($_GET['res'])): ?>
            <div class="alert alert-success alert-dismissible fade show">¡Operación exitosa! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if($error_msg): ?>
            <div class="alert alert-danger"><?= $error_msg ?></div>
        <?php endif; ?>

        <table id="tabla" class="table table-hover">
            <thead class="table-light">
                <tr><th>DNI</th><th>Nombre</th><th>Empresa</th><th class="text-center">Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($afiliados as $a): ?>
                <tr>
                    <td><?= $a['dni'] ?></td>
                    <td class="fw-bold"><?= $a['apellido'].", ".$a['nombre'] ?></td>
                    <td><?= $a['empresa'] ?></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-primary" onclick='abrirModal(<?= json_encode($a) ?>)'>Ver / Editar</button>
                        <a href="?eliminar=<?= $a['afiliados_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')">Borrar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
   <div class="mt-4 text-center">
    <a href="reporte_escolar.php" class="btn btn-outline-secondary">
        Kit Escolares 📦📚
    </a>
</div>
    </div>
</div>

<div class="modal fade" id="modalAfiliado" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="mainForm" class="modal-content" enctype="multipart/form-data">
            <input type="hidden" name="afiliados_id" id="id_af">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="mTitulo">Registro</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Nombre</label><input type="text" name="nombre_afiliado" id="mNom" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Apellido</label><input type="text" name="apellido_afiliado" id="mApe" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">DNI</label><input type="text" name="dni_afiliado" id="mDni" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email_afiliado" id="mEmail" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">Teléfono</label><input type="text" name="telefono_afiliado" id="mTel" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">F. Nacimiento</label><input type="date" name="fecha_nacimiento_afiliado" id="mNac" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">F. Ingreso</label><input type="date" name="ingreso_afiliado" id="mIng" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Empresa</label><input type="text" name="empresa_afiliado" id="mEmp" class="form-control"></div>
                    <div class="col-md-12" id="boxPass"><label class="form-label">Contraseña</label><input type="password" name="contrasena" class="form-control" placeholder="123456 por defecto"></div>
                    
                    <div class="col-md-12 border-top pt-2">
                        <label class="form-label fw-bold text-primary">DNI Escaneado (Frente y Dorso)</label>
                        <input type="file" name="doc_dni_afiliado" class="form-control">
                        <input type="hidden" name="doc_dni_old" id="mDniOld">
                        <div id="viewDni" class="mt-2"></div>
                    </div>
                </div>

                <div class="mt-4 p-3 border rounded bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="m-0 fw-bold">Grupo Familiar y Documentación</h6>
                        <button type="button" class="btn btn-sm btn-dark" onclick="addFam()">+ Agregar Familiar</button>
                    </div>
                    <div id="famCont"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="submit" class="btn btn-success px-5 fw-bold">GUARDAR TODO</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
let modal;
$(document).ready(function() {
    modal = new bootstrap.Modal(document.getElementById('modalAfiliado'));
    $('#tabla').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' }
    });
});

function addFam(data = null) {
    const container = document.getElementById('famCont'); 
    const div = document.createElement('div');
    div.className = "familiar-row";
    
    const vincOld = data ? (data.doc_vinculo || '') : '';
    const escoOld = data ? (data.doc_escolar || '') : '';

    div.innerHTML = `
        <div class="row g-2">
            <div class="col-md-4"><input type="text" name="f_nombre[]" class="form-control form-control-sm" value="${data?data.nombre:''}" placeholder="Nombre" required></div>
            <div class="col-md-3"><input type="date" name="f_f_nac_fam[]" class="form-control form-control-sm" value="${data?data.fecha_nacimiento:''}" required></div>
            <div class="col-md-2">
                <select name="f_sexo[]" class="form-control form-control-sm">
                    <option value="M" ${data && data.sexo==='M'?'selected':''}>Varón</option>
                    <option value="F" ${data && data.sexo==='F'?'selected':''}>Nena</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="f_parentesco[]" class="form-control form-control-sm">
                    <option value="Hijo/a" ${data && data.parentesco==='Hijo/a'?'selected':''}>Hijo/a</option>
                    <option value="Esposo/a" ${data && data.parentesco==='Esposo/a'?'selected':''}>Esposo/a</option>
                </select>
            </div>
            <div class="col-md-1"><button type="button" class="btn btn-danger btn-sm w-100" onclick="this.parentElement.parentElement.parentElement.remove()">×</button></div>
        </div>
        <div class="row g-2 mt-2 border-top pt-2">
            <div class="col-md-6">
                <label class="small fw-bold">Partida / Vínculo:</label>
                <input type="file" name="f_doc_vinculo[]" class="form-control form-control-sm">
                <input type="hidden" name="f_doc_vinculo_old[]" value="${vincOld}">
                ${vincOld ? `<a href="uploads/${vincOld}" target="_blank" class="badge bg-success text-decoration-none mt-1 d-inline-block">Ver Documento</a>` : ''}
            </div>
            <div class="col-md-6">
                <label class="small fw-bold">Boletín / Certificado:</label>
                <input type="file" name="f_doc_escolar[]" class="form-control form-control-sm">
                <input type="hidden" name="f_doc_escolar_old[]" value="${escoOld}">
                ${escoOld ? `<a href="uploads/${escoOld}" target="_blank" class="badge bg-primary text-decoration-none mt-1 d-inline-block">Ver Escolaridad</a>` : ''}
            </div>
        </div>
    `;
    container.appendChild(div);
}

async function abrirModal(data) {
    document.getElementById('mainForm').reset();
    document.getElementById('famCont').innerHTML = "";
    document.getElementById('viewDni').innerHTML = "";
    
    if (data) {
        document.getElementById('mTitulo').innerText = "Editar Afiliado";
        document.getElementById('id_af').value = data.afiliados_id;
        document.getElementById('mNom').value = data.nombre;
        document.getElementById('mApe').value = data.apellido;
        document.getElementById('mDni').value = data.dni;
        document.getElementById('mEmail').value = data.email || '';
        document.getElementById('mTel').value = data.telefono || '';
        document.getElementById('mNac').value = data.fecha_nacimiento || '';
        document.getElementById('mIng').value = data.fecha_ingreso || '';
        document.getElementById('mEmp').value = data.empresa || '';
        document.getElementById('boxPass').style.display = "none";
        
        if (data.doc_dni) {
            document.getElementById('mDniOld').value = data.doc_dni;
            document.getElementById('viewDni').innerHTML = `<a href="uploads/${data.doc_dni}" target="_blank" class="btn btn-sm btn-info text-white">📄 Ver DNI Cargado</a>`;
        }
        
        const resp = await fetch('?get_familiares=' + data.afiliados_id);
        const fams = await resp.json();
        fams.forEach(f => addFam(f));
    } else {
        document.getElementById('mTitulo').innerText = "Nuevo Afiliado";
        document.getElementById('id_af').value = "";
        document.getElementById('mDniOld').value = "";
        document.getElementById('boxPass').style.display = "block";
    }
    modal.show();
}
</script>



</body>
<footer class="footer text-center py-3 mt-5 bg-white border-top">
    <p class="small text-muted mb-0">&copy; 2025 Sindicato de Obras Sanitarias Misiones. Desarrollado por Ruben D. Galeano Consultor IT.</p>
</footer>
</html>


