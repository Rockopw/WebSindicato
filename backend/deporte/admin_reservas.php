<?php
// 1. Configuración de errores y sesión
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// 2. Blindaje de Sesión
if (!isset($_SESSION['id_administrador'])) {
    header("Location: login_admin_afil.php");
    exit();
}   

// 2. Conexión a la DB
require_once "../adminAfiliados/configpdo.php";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { 
    die("Error de conexión: " . $e->getMessage()); 
}

// 3. Cargar PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../PHPMailer/src/Exception.php';
require '../../PHPMailer/src/PHPMailer.php';
require '../../PHPMailer/src/SMTP.php';

// 4. Función de envío de Voucher Multiuso
function enviarVoucherAprobacion($id, $tipo, $pdo) {
    $mail = new PHPMailer(true);
    try {

    // Define una clave secreta (inventa una palabra larga)
$clave_secreta = "Patoneta-2026";
        // Buscamos datos según el tipo
        if ($tipo == 'cabana') {
            $hash = hash_hmac('sha256', $id, $clave_secreta);
            $sql = "SELECT r.*, c.nombre as recurso, a.nombre as afiliado, a.email as email_afiliado 
                    FROM reservas r JOIN cabañas c ON r.cabana_id = c.cabana_id 
                    JOIN afiliados a ON r.afiliados_id = a.afiliados_id WHERE r.reserva_id = ?";
            $link = "http://localhost/sindicato/comprobante_cabana.php?id=$id&hash=$hash";
        } elseif ($tipo == 'depto') {
            $hash = hash_hmac('sha256', $id, $clave_secreta);
            $sql = "SELECT r.*, d.nombre as recurso, a.nombre as afiliado, a.email as email_afiliado 
                    FROM reservas_deptos r JOIN departamentos d ON r.depto_id = d.depto_id 
                    JOIN afiliados a ON r.afiliados_id = a.afiliados_id WHERE r.reserva_id = ?";
            $link = "http://localhost/sindicato/departamentos/comprobante_depto.php?id=$id&hash=$hash";
        } else { // quincho
        $hash = hash_hmac('sha256', $id, $clave_secreta);
            $sql = "SELECT r.*, q.nombre as recurso, a.nombre as afiliado, a.email as email_afiliado, r.fecha_reserva as fecha_desde, r.turno as fecha_hasta
                    FROM reservas_quincho r JOIN quinchos q ON r.quincho_id = q.id_quincho 
                    JOIN afiliados a ON r.afiliado_id = a.afiliados_id WHERE r.id_reserva_q = ?";
            $link = "http://localhost/sindicato/quincho/comprobante_quincho.php?id=$id&hash=$hash";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $datos = $stmt->fetch();
        if (!$datos) return false;

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rubengaleano83@gmail.com'; 
        $mail->Password = 'maoozkskidrbuasz'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port = 587;

        $mail->setFrom('rubengaleano83@gmail.com', 'Sistema de Reservas Sindicato');
        $mail->addAddress($datos['email_afiliado']);
        $mail->isHTML(true);
        $mail->Subject = 'RESERVA APROBADA - ' . strtoupper($tipo) . ": " . $datos['recurso'];

        $cuerpo = "<h2>¡Hola {$datos['afiliado']}! Tu reserva ha sido aprobada.</h2>";
        $cuerpo .= "<p><strong>Recurso:</strong> {$datos['recurso']}</p>";
        $cuerpo .= "<p><strong>Fecha/Turno:</strong> ".($tipo == 'quincho' ? $datos['fecha_desde'] . " (".$datos['fecha_hasta'].")" : $datos['fecha_desde'] . " al " . $datos['fecha_hasta'])."</p>";
        $cuerpo .= "<p><a href='$link' style='background:#28a745; color:white; padding:10px; text-decoration:none; border-radius:5px;'>VER VOUCHER</a></p>";

        $mail->Body = $cuerpo;
        $mail->send();
        return true;
    } catch (Exception $e) { return false; }
}

// 5. PROCESAMIENTO DE ACCIONES (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    $id = $_POST['reserva_id'];
    $tipo = $_POST['tipo_reserva'];
    $accion = $_POST['accion'];

    // Definir tabla y columna ID según tipo
    $tabla = ($tipo == 'cabana') ? 'reservas' : (($tipo == 'depto') ? 'reservas_deptos' : 'reservas_quincho');
    $col_id = ($tipo == 'quincho') ? 'id_reserva_q' : 'reserva_id';

    if ($accion == 'confirmar') {
        $stmt = $pdo->prepare("UPDATE $tabla SET estado = 'confirmado' WHERE $col_id = ?");
        if ($stmt->execute([$id])) {
            enviarVoucherAprobacion($id, $tipo, $pdo);
            echo "<script>alert('Reserva de $tipo Confirmada.'); window.location='admin_reservas.php';</script>";
        }
    } 
    elseif ($accion == 'rechazar') {
        $motivo = $_POST['motivo'];
        $stmt = $pdo->prepare("UPDATE $tabla SET estado = 'rechazado', motivo_rechazo = ? WHERE $col_id = ?");
        $stmt->execute([$motivo, $id]);
        echo "<script>alert('Reserva de $tipo Rechazada.'); window.location='admin_reservas.php';</script>";
    }
}

// 6. Consultar TODAS las reservas pendientes (UNION)
$query = "
    SELECT r.reserva_id as id, 'cabana' as tipo, c.nombre as recurso, a.nombre as afiliado, r.fecha_desde as f1, r.fecha_hasta as f2 
    FROM reservas r JOIN cabañas c ON r.cabana_id = c.cabana_id JOIN afiliados a ON r.afiliados_id = a.afiliados_id WHERE r.estado = 'pendiente'
    UNION ALL
    SELECT r.reserva_id as id, 'depto' as tipo, d.nombre as recurso, a.nombre as afiliado, r.fecha_desde as f1, r.fecha_hasta as f2 
    FROM reservas_deptos r JOIN departamentos d ON r.depto_id = d.depto_id JOIN afiliados a ON r.afiliados_id = a.afiliados_id WHERE r.estado = 'pendiente'
    UNION ALL
    SELECT r.id_reserva_q as id, 'quincho' as tipo, q.nombre as recurso, a.nombre as afiliado, r.fecha_reserva as f1, r.turno as f2 
    FROM reservas_quincho r JOIN quinchos q ON r.quincho_id = q.id_quincho JOIN afiliados a ON r.afiliado_id = a.afiliados_id WHERE r.estado = 'pendiente'
";
$pendientes = $pdo->query($query)->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión Unificada - Sindicato</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="deportivo.css">
    <link rel="icon" href="imagenes/pelota.ico">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between">
            <h4>Panel de Control - Todas las Reservas</h4>
            <span>👤 <?= htmlspecialchars($_SESSION['administrador_nombre']) ?></span>
        </div>
        <div class="card-body">
            <table id="tablaReservas" class="table table-striped">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Afiliado</th>
                        <th>Recurso</th>
                        <th>Desde / Fecha</th>
                        <th>Hasta / Turno</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendientes as $p): ?>
                    <tr>
                        <td><span class="badge bg-info text-dark"><?= strtoupper($p['tipo']) ?></span></td>
                        <td><?= htmlspecialchars($p['afiliado']) ?></td>
                        <td><?= htmlspecialchars($p['recurso']) ?></td>
                        <td><?= $p['tipo'] == 'quincho' ? date('d/m/Y', strtotime($p['f1'])) : date('d/m/Y', strtotime($p['f1'])) ?></td>
                        <td><?= $p['tipo'] == 'quincho' ? $p['f2'] : date('d/m/Y', strtotime($p['f2'])) ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="reserva_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="tipo_reserva" value="<?= $p['tipo'] ?>">
                                <button type="submit" name="accion" value="confirmar" class="btn btn-success btn-sm">Aprobar</button>
                            </form>
                            <button class="btn btn-danger btn-sm" onclick="abrirModalRechazo(<?= $p['id'] ?>, '<?= $p['tipo'] ?>')">Rechazar</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRechazo" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header bg-danger text-white"><h5>Rechazar Solicitud</h5></div>
      <div class="modal-body">
        <input type="hidden" name="reserva_id" id="rechazo_id">
        <input type="hidden" name="tipo_reserva" id="rechazo_tipo">
        <input type="hidden" name="accion" value="rechazar">
        <label>Motivo:</label>
        <textarea name="motivo" class="form-control" required></textarea>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-danger">Confirmar Rechazo</button>
      </div>
    </form>
  </div>
</div>

 <div class="text-center my-3">
        <a href="logout_admin_afil.php"
            class="btn btn-logout"
            onclick="return confirm('¿Seguro que querés cerrar sesión?')">
            🚪 Cerrar sesión
        </a>
        </div>





<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function abrirModalRechazo(id, tipo) {
    document.getElementById('rechazo_id').value = id;
    document.getElementById('rechazo_tipo').value = tipo;
    new bootstrap.Modal(document.getElementById('modalRechazo')).show();
}
</script>
</body>
</html>
  <p>&nbsp;</p> <!-- dejo un parrafo vacio -->
 <footer class="footer">
                 <p>&copy;2025 Sindicato de Obras Sanitarias Misiones. Todos los derechos reservados desarrollado por Ruben D. Galeano Consultor IT - mail: rubengaleano83@gmail.com.</p>
                 
            </footer>

