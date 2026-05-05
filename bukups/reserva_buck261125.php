<?php
// 1. Configuración de errores y sesión
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// 2. Blindaje de Sesión
if (!isset($_SESSION['afiliado_id'])) {
    header("Location: login.php");
    exit();
}

$afiliado_id = $_SESSION['afiliado_id'];
$nombre_afiliado = $_SESSION['afiliado_nombre'] ?? 'Afiliado';

// 3. Conexión a la DB
$host = 'localhost'; $db = 'sindicato'; $user = 'rudagaleano'; $pass = 'Napst3rfarr3l';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { 
    die("Error de conexión: " . $e->getMessage()); 
}

$mensaje = "";

// 4. Función de envío de correo (Se llama al confirmar)
// Cargar las clases de PHPMailer (Asegúrate de que estas rutas sean correctas en tu carpeta)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

function enviarMailSecretaria($reserva_id, $datos, $pdo) {
    $mail = new PHPMailer(true);

    try {
        // --- CONFIGURACIÓN DEL SERVIDOR SMTP ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'rubengaleano83@gmail.com';       // Tu cuenta de Gmail
        $mail->Password   = 'maoozkskidrbuasz';      // LA CONTRASEÑA DE APP (16 letras)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port       = 587;

        // --- DESTINATARIOS ---
        $mail->setFrom('tucorreo@gmail.com', 'Sistema de Reservas');
        $mail->addAddress('rubengaleano83@gmail.com');  // El correo del Secretario

        // --- CONTENIDO DEL CORREO ---
        $mail->isHTML(true); // Cambiamos a HTML para que se vea mejor
        $mail->Subject = 'CONFIRMACION DE RESERVA: ' . $datos['afiliado'];

        // Consultar invitados para el cuerpo del mail
        $stmt_inv = $pdo->prepare("SELECT nombre_completo, dni FROM invitados WHERE reserva_id = ?");
        $stmt_inv->execute([$reserva_id]);
        $invitados = $stmt_inv->fetchAll();

        $cuerpo = "<h2>Nueva reserva confirmada</h2>";
        $cuerpo .= "<p><strong>Titular:</strong> {$datos['afiliado']}</p>";
        $cuerpo .= "<p><strong>Cabaña:</strong> {$datos['cabana']}</p>";
        $cuerpo .= "<p><strong>Fechas:</strong> " . date('d/m/Y', strtotime($datos['desde'])) . " al " . date('d/m/Y', strtotime($datos['hasta'])) . "</p>";
        
        $cuerpo .= "<h3>Invitados:</h3><ul>";
        foreach ($invitados as $inv) {
            $cuerpo .= "<li>{$inv['nombre_completo']} (DNI: {$inv['dni']})</li>";
        }
        $cuerpo .= "</ul>";

        $mail->Body = $cuerpo;
        $mail->AltBody = strip_tags($cuerpo); // Texto plano para correos que no aceptan HTML

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Si hay error, lo guardamos en un log pero no detenemos al usuario
        error_log("Error al enviar mail: {$mail->ErrorInfo}");
        return false;
    }

}

// 5. LÓGICA DE PROCESAMIENTO (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- ACCIÓN: NUEVA RESERVA ---
    if (isset($_POST['cabana_id']) && isset($_POST['fecha_desde'])) {
        $cab_id = $_POST['cabana_id'];
        $f_desde = $_POST['fecha_desde'];
        $f_hasta = $_POST['fecha_hasta'];

        try {
            if (strtotime($f_desde) >= strtotime($f_hasta)) {
                $mensaje = "<div class='mensaje-error'>❌ La fecha de salida debe ser posterior.</div>";
            } else {
                // Verificar disponibilidad
                $sql_check = "SELECT COUNT(*) FROM reservas WHERE cabana_id = :cab_id AND (:f_desde < fecha_hasta AND :f_hasta > fecha_desde)";
                $stmt_check = $pdo->prepare($sql_check);
                $stmt_check->execute(['cab_id' => $cab_id, 'f_desde' => $f_desde, 'f_hasta' => $f_hasta]);

                if ($stmt_check->fetchColumn() > 0) {
                    $mensaje = "<div class='mensaje-error'>❌ Esta cabaña ya está ocupada en esas fechas.</div>";
                } else {
                    $sql_ins = "INSERT INTO reservas (afiliados_id, cabana_id, fecha_desde, fecha_hasta) VALUES (:af_id, :cab_id, :f_desde, :f_hasta)";
                    $pdo->prepare($sql_ins)->execute(['af_id' => $afiliado_id, 'cab_id' => $cab_id, 'f_desde' => $f_desde, 'f_hasta' => $f_hasta]);
                    $mensaje = "<div class='mensaje-exito'>✅ Reserva registrada. Ahora cargue sus invitados abajo y luego presione 'Confirmar'.</div>";
                }
            }
        } catch (PDOException $e) { $mensaje = "<div class='mensaje-error'>❌ Error: " . $e->getMessage() . "</div>"; }
    }

    // --- ACCIÓN: CONFIRMAR Y VER VOUCHER (Aquí se envía el Mail) ---
    if (isset($_POST['confirmar_y_voucher_id'])) {
        $id_res = $_POST['confirmar_y_voucher_id'];
        
        $stmt = $pdo->prepare("SELECT r.*, c.nombre as cabana_n FROM reservas r JOIN cabañas c ON r.cabana_id = c.cabana_id WHERE r.reserva_id = ?");
        $stmt->execute([$id_res]);
        $r_info = $stmt->fetch();

        if ($r_info) {
            $datosMail = [
                'afiliado' => $_SESSION['afiliado_nombre'],
                'cabana'   => $r_info['cabana_n'],
                'desde'    => $r_info['fecha_desde'],
                'hasta'    => $r_info['fecha_hasta']
            ];

            // Enviamos mail al secretario
            enviarMailSecretaria($id_res, $datosMail, $pdo);

            // Redireccionamos al voucher (Esto evita bloqueos de popups)
            header("Location: comprobante.php?id=$id_res");
            exit();
        }
    }

    // --- ACCIÓN: CANCELAR RESERVA ---
    if (isset($_POST['cancelar_id'])) {
        $stmt_del = $pdo->prepare("DELETE FROM reservas WHERE reserva_id = ? AND afiliados_id = ?");
        $stmt_del->execute([$_POST['cancelar_id'], $afiliado_id]);
        $mensaje = "<div class='mensaje-exito'>✅ Reserva eliminada correctamente.</div>";
    }

    // --- ACCIÓN: AGREGAR INVITADO ---
    if (isset($_POST['nombre_invitado'])) {
        $res_id = $_POST['reserva_id_invitado'];
        $nombre_inv = $_POST['nombre_invitado'];
        $dni_inv = $_POST['dni_invitado'];
        $tipo_inv = $_POST['tipo_invitado'];

        try {
            // Validar capacidad
            $stmt_cap = $pdo->prepare("SELECT c.capacidad_maxima FROM reservas r JOIN cabañas c ON r.cabana_id = c.cabana_id WHERE r.reserva_id = ?");
            $stmt_cap->execute([$res_id]);
            $cap_max = $stmt_cap->fetchColumn();

            $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM invitados WHERE reserva_id = ?");
            $stmt_count->execute([$res_id]);
            $actuales = $stmt_count->fetchColumn();

            if (($actuales + 1) >= $cap_max) {
                $mensaje = "<div class='mensaje-error'>⚠️ Capacidad máxima de la cabaña alcanzada.</div>";
            } else {
                $stmt_ins_inv = $pdo->prepare("INSERT INTO invitados (reserva_id, nombre_completo, dni, tipo) VALUES (?, ?, ?, ?)");
                $stmt_ins_inv->execute([$res_id, $nombre_inv, $dni_inv, $tipo_inv]);
                $mensaje = "<div class='mensaje-exito'>✅ Invitado añadido.</div>";
            }
        } catch (PDOException $e) { $mensaje = "<div class='mensaje-error'>❌ Error: " . $e->getMessage() . "</div>"; }
    }
}

// 6. Cargar datos para la vista
$mis_reservas = $pdo->prepare("SELECT r.reserva_id, r.fecha_desde, r.fecha_hasta, c.nombre as cabana_nombre FROM reservas r JOIN cabañas c ON r.cabana_id = c.cabana_id WHERE r.afiliados_id = :af_id ORDER BY r.fecha_desde ASC");
$mis_reservas->execute(['af_id' => $afiliado_id]);
$reservas = $mis_reservas->fetchAll();

$cabanas = $pdo->query("SELECT * FROM cabañas")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reservas - Sindicato</title>
    <link rel="stylesheet" href="estilos_reservas.css">
    <?php require_once "encabezado.php"; ?>
    <style>
        .btn-confirmar-final { background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn-confirmar-final:hover { background: #218838; }
        .reserva-item { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-left: 5px solid #007bff; }
        .form-invitado-mini { display: flex; gap: 10px; flex-wrap: wrap; margin: 15px 0; background: #f8f9fa; padding: 15px; border-radius: 8px; }
        .form-invitado-mini input, .form-invitado-mini select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <header class="top-bar">
        <div class="d-flex align-items-center">
            <img src="logo sindi.jpg" class="rounded-circle me-3 shadow-white" width="75" height="70">
            <h3 class="ms-3 mb-0 text-white">Bienvenido, <?php echo htmlspecialchars($nombre_afiliado); ?></h3>
        </div>
        <nav class="main-nav">
            <ul>
                <li><a href="menu.php">Inicio</a></li>
                <li><a href="#home">Home</a></li>
                <li><a href="#reservas">Gestion de Reservas</a></li>
                <li><a href="#ubicacion">Ubicación</a></li>
                <li><a href="logout.php">Cerrar Sesión</a></li>
            </ul>
        </nav>
    </header>



<div id="sliderPredio" class="carousel slide" data-bs-ride="carousel">

  <div class="carousel-inner" id="home">

        <div class="carousel-item active">
        <div class="slider-overlay">
            <h2>Predio Ituzaingó – Corrientes</h2>
        </div>
        <img src="imagenes/cabaña itu/predio/1.jpeg" class="d-block w-100 slider-img" alt="Predio">
        </div>

        <div class="carousel-item">
        <div class="slider-overlay">
            <h2>Predio Ituzaingó – Corrientes</h2>
        </div>
        <img src="imagenes/cabaña itu/predio/2.jpeg" class="d-block w-100 slider-img" alt="Predio">
        </div>
          <div class="carousel-item">
        <div class="slider-overlay">
            <h2>Predio Ituzaingó – Corrientes</h2>
        </div>
        <img src="imagenes/cabaña itu/predio/3.jpeg" class="d-block w-100 slider-img" alt="Predio">
        </div>
        <div class="carousel-item">
        <div class="slider-overlay">
            <h2>Predio Ituzaingó – Corrientes</h2>
        </div>
        <img src="imagenes/cabaña itu/predio/4.jpeg" class="d-block w-100 slider-img" alt="Predio">
        </div>


  </div>

  <!-- Controles -->
  <button class="carousel-control-prev" type="button" data-bs-target="#sliderPredio" data-bs-slide="prev">
    <span class="carousel-control-prev-icon"></span>
  </button>

  <button class="carousel-control-next" type="button" data-bs-target="#sliderPredio" data-bs-slide="next">
    <span class="carousel-control-next-icon"></span>
  </button>

</div>
 <p>&nbsp;</p> <!-- dejo un parrafo vacio -->
  <p>&nbsp;</p> <!-- dejo un parrafo vacio -->




    <div class="container mt-5" id="reservas">
        <h1>Gestión de Reservas</h1>
        <?php echo $mensaje; ?>

        <div class="cabin-gallery">
            <div class="cabin-card" id="card-1" onclick="selectCabana(1)">
               <div class="cabin-image-grid">
                    <img src="imagenes/cabaña itu/1/cabaña.jpeg" alt="Vista 1" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="imagenes/cabaña itu/1/2.jpeg" alt="Vista 2" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="imagenes/cabaña itu/1/3.jpeg" alt="Vista 3" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="imagenes/cabaña itu/1/4.jpeg" alt="Vista 4" onclick="openModal(this.src); event.stopPropagation();">
                 
                </div>
                <div class="info">Cabaña 1 (6 personas)</div>
            </div>

            <div class="cabin-card" id="card-2" onclick="selectCabana(2)">
                 <div class="cabin-image-grid">
                    <img src="imagenes/cabaña itu/2/cabaña.png" alt="Vista 1" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="imagenes/cabaña itu/2/1.jpeg" alt="Vista 2" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="imagenes/cabaña itu/2/2.jpeg" alt="Vista 3" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="imagenes/cabaña itu/2/3.jpeg" alt="Vista 4" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="imagenes/cabaña itu/2/4.jpeg" alt="Vista 5" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="imagenes/cabaña itu/2/5.jpeg" alt="Vista 6" onclick="openModal(this.src); event.stopPropagation();">
                </div>
                <div class="info">Cabaña 2 (4 personas)</div>
            </div>

            <div class="cabin-card" id="card-5" onclick="selectCabana(5)">
                 <div class="cabin-image-grid">
                    <img src="imagenes/cabaña itu/5/cabaña.jpeg" alt="Vista 1" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="imagenes/cabaña itu/5/1.jpeg" alt="Vista 2" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="imagenes/cabaña itu/5/2.jpeg" alt="Vista 3" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="imagenes/cabaña itu/5/3.jpeg" alt="Vista 4" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="imagenes/cabaña itu/5/4.jpeg" alt="Vista 5" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="imagenes/cabaña itu/5/5.jpeg" alt="Vista 6" onclick="openModal(this.src); event.stopPropagation();">
                </div>
                <div class="info">Cabaña 5 (4 personas)</div>
                <p style="font-size: 0.8rem; text-align: center;">Incluye: Anafe, Gas y Ventilador</p>
            </div>
        </div>

        <form method="POST" class="mt-4">
            <fieldset>
                <legend>Realizar Nueva Reserva</legend>
                <div class="form-group">
                    <label>Cabaña:</label>
                    <select name="cabana_id" id="selectCabana" required>
                        <?php foreach ($cabanas as $c): ?>
                            <option value="<?php echo $c['cabana_id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?> (Capacidad: <?php echo $c['capacidad_maxima']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Entrada:</label>
                    <input type="date" name="fecha_desde" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Salida:</label>
                    <input type="date" name="fecha_hasta" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                </div>
                <button  type="submit" formaction= "#reservas" class="btn btn-primary w-100">Verificar y Reservar</button>
            </fieldset>
        </form>

        <hr>

        <h3>📅 Tus Reservas Actuales</h3>
        <div class="reservas-list">
            <?php foreach ($reservas as $res): ?>
                <div class="reserva-item">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong><?php echo htmlspecialchars($res['cabana_nombre']); ?></strong><br>
                            <small>Desde: <?php echo date('d/m/Y', strtotime($res['fecha_desde'])); ?> al <?php echo date('d/m/Y', strtotime($res['fecha_hasta'])); ?></small>
                        </div>
                        <div class="reserva-acciones">
                            
                            <button class="btn-invitados" onclick="toggleInvitados(<?php echo $res['reserva_id']; ?>)">👥 Invitados</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar reserva?');">
                                <input type="hidden" name="cancelar_id" value="<?php echo $res['reserva_id']; ?>">
                                <button type="submit" class="btn-cancelar">Eliminar</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="confirmar_y_voucher_id" value="<?php echo $res['reserva_id']; ?>">
                                <button type="submit" class="btn-confirmar-final">✅ Confirmar Reserva</button>
                            </form>
                        </div>
                    </div>

                    <div id="seccion-inv-<?php echo $res['reserva_id']; ?>" style="display:none; margin-top:15px; border-top: 1px dashed #ccc; padding-top:15px;">
                        <form method="POST" class="form-invitado-mini">
                            <input type="hidden" name="reserva_id_invitado" value="<?php echo $res['reserva_id']; ?>">
                            <input type="text" name="nombre_invitado" placeholder="Nombre Completo" required>
                            <input type="text" name="dni_invitado" placeholder="DNI" required>
                            <select name="tipo_invitado">
                                <option value="Familiar">Familiar</option>
                                <option value="Externo">Externo</option>
                            </select>
                            <button type="submit" class="btn-add">Añadir</button>
                        </form>
                        
                        <ul class="lista-invitados-cargados">
                            <?php
                            $stmt_inv = $pdo->prepare("SELECT * FROM invitados WHERE reserva_id = ?");
                            $stmt_inv->execute([$res['reserva_id']]);
                            while($inv = $stmt_inv->fetch()): ?>
                                <li>• <?php echo htmlspecialchars($inv['nombre_completo']); ?> (DNI: <?php echo $inv['dni']; ?>)</li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function toggleInvitados(id) {
            const s = document.getElementById('seccion-inv-' + id);
            s.style.display = (s.style.display === 'none') ? 'block' : 'none';
        }
        function openModal(src) {
            document.getElementById('imageModal').style.display = "flex";
            document.getElementById('fullImg').src = src;
        }
        function closeModal() {
            document.getElementById('imageModal').style.display = "none";
        }
        function selectCabana(id) {
            document.getElementById('selectCabana').value = id;
        }
    </script>
<!-- UBICACION-->
            <section id="ubicacion"  class="ubicacion">    
            <h3>Ubicación</h3>

            <div class="mapa-container">
                <iframe
                 src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d883.8993680686934!2d-56.7219449304262!3d-27.606006610088077!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9457390045be9331%3A0xaf6732fc749ea600!2sCaba%C3%B1as%20S.P.O.S.M!5e0!3m2!1ses!2sar!4v1766354467367!5m2!1ses!2sar" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                allowfullscreen
                loading="lazy">
                </iframe>
            </div>
            </section>
    <div id="imageModal" class="modal" onclick="closeModal()">
        <span class="close-modal">×</span>
        <img class="modal-content" id="fullImg">
    </div>

    <?php require_once "pie.php"; ?>
</body>
</html>