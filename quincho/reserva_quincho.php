<?php
// 1. Errores y Sesión
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['afiliado_id'])) {
    header("Location: ../login.php");
    exit();
}

$afiliado_id = $_SESSION['afiliado_id'];
$nombre_afiliado = $_SESSION['afiliado_nombre'] ?? 'Afiliado';

// 2. Conexión a la DB
$host = 'localhost'; $db = 'sindicato'; $user = 'rudagaleano'; $pass = 'Napst3rfarr3l';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { 
    die("Error de conexión: " . $e->getMessage()); 
}

// Traer los quinchos
$stmt_q = $pdo->query("SELECT * FROM quinchos");
$quinchos = $stmt_q->fetchAll(PDO::FETCH_ASSOC);

$mensaje = "";

// 4. Configuración de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

function enviarMailSecretariaDepto($datos) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'rubengaleano83@gmail.com'; 
        $mail->Password   = 'maoozkskidrbuasz'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port       = 587;

        $mail->setFrom('rubengaleano83@gmail.com', 'Sistema de Reservas Quinchos');
        $mail->addAddress('rubengaleano83@gmail.com');

        $mail->isHTML(true);
        $mail->Subject = 'SOLICITUD DE QUINCHO PENDIENTE: ' . $datos['afiliado'];

        $cuerpo = "
            <div style='font-family: sans-serif; border: 1px solid #ddd; padding: 20px;'>
                <h2 style='color: #985605;'>Nueva Solicitud de Reserva 🍖</h2>
                <p><strong>Afiliado:</strong> {$datos['afiliado']}</p>
                <p><strong>Quincho:</strong> {$datos['quincho_nombre']}</p>
                <p><strong>Fecha:</strong> " . date('d/m/Y', strtotime($datos['fecha'])) . "</p>
                <p><strong>Turno:</strong> {$datos['turno']}</p>
                <p><strong>Estado:</strong> <span style='color: orange;'>PENDIENTE DE APROBACION</span></p>
                <hr>
                <p style='font-size: 0.8em; color: #666;'>Ingrese al panel de administración para aprobar o rechazar.</p>
            </div>";

        $mail->Body = $cuerpo;
        $mail->AltBody = strip_tags($cuerpo);

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Traemos reservas futuras (Pendientes y Aprobadas) para bloquear el calendario
$stmt_bloqueo = $pdo->query("SELECT quincho_id, fecha_reserva, turno FROM reservas_quincho WHERE estado != 'Rechazada' AND fecha_reserva >= CURDATE()");
$todas_las_reservas = $stmt_bloqueo->fetchAll(PDO::FETCH_ASSOC);
$reservas_json = json_encode($todas_las_reservas);

// 3. Lógica de Procesamiento (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // CASO: CANCELAR SOLICITUD
    if (isset($_POST['cancelar_id'])) {
        // Corrección de columna: afiliado_id
        $stmt = $pdo->prepare("DELETE FROM reservas_quincho WHERE id_reserva_q = ? AND afiliado_id = ?");
        $stmt->execute([$_POST['cancelar_id'], $afiliado_id]);
        header("Location: reserva_quincho.php?eliminado=1#reservas");
        exit();
    }

    // CASO: NUEVA SOLICITUD (Como en Cabañas)
    if (isset($_POST['quincho_id']) && !empty($_POST['quincho_id'])) {
        $q_id = $_POST['quincho_id'];
        $fecha = $_POST['fecha_reserva'];
        $turno = $_POST['turno'];

        // Validar si ya está ocupado (Aprobado o Pendiente)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservas_quincho WHERE quincho_id = ? AND fecha_reserva = ? AND turno = ? AND estado != 'Rechazada'");
        $stmt->execute([$q_id, $fecha, $turno]);
        
        if ($stmt->fetchColumn() > 0) {
            $mensaje = "<div class='alert alert-danger'>❌ Error: Este turno ya está solicitado o reservado.</div>";
        } else {
            // Se inserta con estado 'Pendiente'
            $stmt = $pdo->prepare("INSERT INTO reservas_quincho (afiliado_id, quincho_id, fecha_reserva, turno, estado, fecha_solicitud) VALUES (?, ?, ?, ?, 'Pendiente', NOW())");
            if ($stmt->execute([$afiliado_id, $q_id, $fecha, $turno])) {
                
                $stmt_n = $pdo->prepare("SELECT nombre FROM quinchos WHERE id_quincho = ?");
                $stmt_n->execute([$q_id]);
                $q_info = $stmt_n->fetch();

                $datos_mail = [
                    'afiliado' => $nombre_afiliado,
                    'quincho_nombre' => $q_info['nombre'],
                    'fecha' => $fecha,
                    'turno' => $turno
                ];
                enviarMailSecretariaDepto($datos_mail);

                header("Location: reserva_quincho.php?exito=1#reservas");
                exit();
            }
        }
    }
}

// 4. Traer mis reservas SOLO actuales o futuras
$hoy = date('Y-m-d');
$stmt_mis = $pdo->prepare("
    SELECT r.*, q.nombre 
    FROM reservas_quincho r 
    JOIN quinchos q ON r.quincho_id = q.id_quincho 
    WHERE r.afiliado_id = ? 
    AND r.fecha_reserva >= ? 
    ORDER BY r.fecha_reserva ASC
");
$stmt_mis->execute([$afiliado_id, $hoy]);
$reservas_actuales = $stmt_mis->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reserva de Quinchos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"><!-- esto es para que ande el calendario-->
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../estilos_reservas.css">
    <link rel="icon" type="image/png" href="../logo sindi.ico">
      <?php require_once "../encabezado.php"; ?>
          
    <style>
        .badge-pendiente { background-color: #ffc107; color: #000; padding: 5px 10px; border-radius: 5px; font-weight: bold; }
        .badge-aprobado { background-color: #28a745; color: #fff; padding: 5px 10px; border-radius: 5px; font-weight: bold; }
        .badge-rechazado { background-color: #e90a0a; color: #fff; padding: 5px 10px; border-radius: 5px; font-weight: bold; }
        .reserva-item { border-left: 5px solid #985605; background: #fff; padding: 15px; margin-top: 10px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    
    /* Estilo para el texto del motivo */
.motivo-texto {
    background: #fff5f5;
    border-left: 3px solid #dc3545;
    padding: 8px;
    margin-top: 10px;
    font-size: 0.9em;
    color: #a02020;
    border-radius: 4px;
}
    /*menu hamburguesa*/
/* Ajustes generales del Header */


.header-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
}

/* Estilo del Botón Hamburguesa */
/* --- CORRECCIÓN DE COLORES Y MENÚ MÓVIL --- */
:root {
    /* Cambiamos el verde por un celeste/azul brillante */
    --accent-color: #00d4ff; 
}

@media (max-width: 768px) {
    .menu-toggle {
        display: block !important;
        z-index: 1100;
        
    }

    .main-nav {
        display: none; 
        position: fixed; /* Cambiado a fixed para que cubra la pantalla */
        top: 0;
        right: 0;
        width: 80%; /* Ancho del menú lateral */
        height: 100vh;
        background-color: var(--color-opcional);
        flex-direction: column;
        padding: 80px 20px;
        box-shadow: -5px 0 15px rgba(0, 0, 0, 0.27);
        z-index: 1050;
        transition: transform 0.3s ease-in-out;
    }

    /* CLAVE: Cuando el menú está activo, mostramos el contenedor y la lista */
    .main-nav.active {
        display: flex !important;
    }

    .main-nav.active ul {
        display: flex !important; /* Esto soluciona que no se vea nada */
        flex-direction: column;
        width: 100%;
        padding: 0;
    }

    .main-nav ul li {
        margin: 20px 0;
        text-align: left;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    /*esto es del menu hamburgesa*/
    .main-nav ul li {
        margin: 20px 0;
        text-align: left;
        border-bottom: 1px solid rgba(255,255,255,0.1);
}
    
    
    
    </style>
</head>
<body>


 <header class="top-bar">
    <div class="header-container">
        <div class="d-flex align-items-center">
            <img src="../logo sindi.jpg" class="rounded-circle me-3 shadow-white logo-img" width="60" height="55">
            <h3 class="welcome-text mb-0 text-white">Hola, <?php echo htmlspecialchars($nombre_afiliado); ?></h3>
        </div>

       <button class="menu-toggle" id="menuToggle" aria-label="Abrir menú">
    <i class="fa-solid fa-bars"></i>
</button>

        <nav class="main-nav" id="mainNav">
            <ul>
                <li><a href="../menu.php"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a href="#home">Home</a></li>
                <li><a href="#reservas">Gestión de Reservas</a></li>
                <li><a href="#ubicacion">Ubicación</a></li>
                <li><a href="../espacio_de_reservas.php">Volver a Reservas</a></li>
                <li><a href="../logout.php" class="btn-logout-mobile">Cerrar Sesión</a></li>
            </ul>
        </nav>
    </div>
</header>


<div id="sliderPredio" class="carousel slide" data-bs-ride="carousel">

  <div class="carousel-inner" id="home">

        <div class="carousel-item active">
        <div class="slider-overlay">
            <h2>Quincho para 30 Personas</h2>
        </div>
        <img src="fotos/1.png" class="d-block w-100 slider-img" alt="Predio">
        </div>

        <div class="carousel-item">
        <div class="slider-overlay">
            <h2>Lavalle 2445 - Posadas </h2>
        </div>
        <img src="fotos/2.jpeg" class="d-block w-100 slider-img" alt="Predio">
        </div>
          <div class="carousel-item">
        <div class="slider-overlay">
            <h2>Incluye: Aire,mesas,sillas,mantel,vajillas,parrilla,cocina con horno,frezzer,baños</h2>
        </div>
        <img src="fotos/3.jpeg" class="d-block w-100 slider-img" alt="Predio">
        </div>
        <div class="carousel-item">
        <div class="slider-overlay">
            <h2>Temporalmente Sin pileta 😒​</h2>
        </div>
        <img src="fotos/6.jpeg" class="d-block w-100 slider-img" alt="Predio">
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











    <div class="container py-5" id="reservas">
    <h1>🍗 Reserva de Quincho</h1>

    <?php
    if (isset($_GET['exito'])) {
        echo "<div class='alert alert-success'>✅ Solicitud enviada. Su reserva figura como 'Pendiente' hasta que el Secretario de Deportes la apruebe.</div>";
    } 
    elseif (isset($_GET['eliminado'])) {
        echo "<div class='alert alert-success'>✅ Solicitud cancelada correctamente.</div>";
    } 
    elseif (!empty($mensaje)) {
        echo $mensaje; 
    }
    ?>

    <form method="POST" action="reserva_quincho.php#reservas" id="formQuincho" class="mb-5">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label><strong>1. Seleccione el Quincho:</strong></label>
                <select name="quincho_id" id="select_quincho" class="form-control" required onchange="actualizarCalendario()">
                    <option value="" disabled selected>-- Elija un Quincho --</option>
                    <?php foreach ($quinchos as $q): ?>
                        <option value="<?php echo $q['id_quincho']; ?>"><?php echo htmlspecialchars($q['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label><strong>2. Fecha de la Reserva:</strong></label>
                <input type="text" name="fecha_reserva" id="calendario" class="form-control" placeholder="Primero elija un quincho..." readonly required>
            </div>
        </div>

        <div class="mb-3">
            <label><strong>3. Turno Disponible:</strong></label>
            <div class="turno-container d-flex gap-3 mt-2">
                <label class="turno-card border p-3 flex-fill text-center rounded" id="label-diurno">
                    <input type="radio" name="turno" value="Diurno" id="radio-diurno" required>
                    <div class="fw-bold">☀️ Diurno</div><small>10:00 - 18:00 hs</small>
                </label>
                <label class="turno-card border p-3 flex-fill text-center rounded" id="label-nocturno">
                    <input type="radio" name="turno" value="Nocturno" id="radio-nocturno">
                    <div class="fw-bold">🌜 Nocturno</div><small>20:00 - 05:00 hs</small>
                </label>
            </div>
            <div id="aviso-turno" class="text-danger small fw-bold mt-2"></div>
        </div>

        <button type="submit" class="btn btn-success w-100 py-3 fw-bold">Solicitar Reserva</button>
    </form>

    <h2 id="mis-reservas" class="mt-5 border-top pt-4">📅 Mis Solicitudes de Quincho</h2>
        <p>Esta solicitud queda a consideración del Secretario de Deportes. Por favor, aguarde su aprobación.
        En caso de no poder concurrir, le solicitamos tenga a bien cancelar su reserva, de modo que el turno quede disponible para otro afiliado.
        Muchas gracias.</p>
    <?php if(empty($reservas_actuales)): ?>
        <p class="text-muted">No tienes solicitudes pendientes o aprobadas.</p>
    <?php else: ?>
        <div class="row">
        <?php foreach ($reservas_actuales as $res): ?>
    <div class="col-12 mb-3">
        <div class="reserva-item d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1 text-primary"><?php echo htmlspecialchars($res['nombre']); ?></h5>
                <div class="text-muted">
                    <i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($res['fecha_reserva'])); ?> | 
                    <i class="far fa-clock"></i> <?php echo $res['turno']; ?>
                </div>
                
               <div class="mt-2">
                    <?php 
                        $estado = strtolower($res['estado']);
                        if ($estado == 'pendiente') {
                            echo '<span class="badge-pendiente">PENDIENTE</span>';
                        } elseif ($estado == 'confirmado' || $estado == 'aprobado') {
                            // Contenedor flex para alinear horizontalmente
                            echo '<div class="d-flex align-items-center gap-2">';
                                echo '<span class="badge-aprobado">APROBADO</span>';
                                echo '<a href="comprobante_quincho.php?id=' . $res['id_reserva_q'] . '" 
                                        class="btn btn-sm btn-dark d-inline-flex align-items-center" 
                                        target="_blank" 
                                        style="text-decoration: none;">
                                        <i class="fas fa-file-invoice me-2"></i> Ver Voucher
                                    </a>';
                            echo '</div>';
                        } elseif ($estado == 'rechazado' || $estado == 'rechazada') {
                            echo '<span class="badge-rechazado">RECHAZADO</span>';
                        }
                    ?>
                </div>

                <?php if (!empty($res['motivo_rechazo']) && ($estado == 'rechazado' || $estado == 'rechazada')): ?>
                    <div class="motivo-texto">
                        <strong>Motivo:</strong> <?php echo htmlspecialchars($res['motivo_rechazo']); ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if($estado == 'pendiente'): ?>
            <form method="POST" action="reserva_quincho.php#mis-reservas" onsubmit="return confirm('¿Cancelar esta solicitud?')">
                <input type="hidden" name="cancelar_id" value="<?php echo $res['id_reserva_q']; ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm">Cancelar</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>


<!-- UBICACION-->
            <section id="ubicacion"  class="ubicacion">    
            <h3>📌 Ubicación</h3>

            <div class="mapa-container">
             <iframe

                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3543.129134263567!2d-55.90804232486722!3d-27.371682212536356!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9457be38fd4c4f37%3A0xf31a06966a785f10!2sAv.%20Gral.%20Lavalle%202445%2C%20N3300OOR%20Posadas%2C%20Misiones!5e0!3m2!1ses!2sar!4v1772847703746!5m2!1ses!2sar" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                    width="600"
                    height="450"
                    style="border:0;"
                    allowfullscreen
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
            </iframe>
            </section>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script> <!-- esto es para que ande el carrusel-->
<script>
// 1. Pasamos las reservas desde PHP (usando el nombre de variable correcto)
const bloqueadas = <?= json_encode($todas_las_reservas) ?>;
let calendarioInstancia;

// 2. Inicializar el calendario cuando cargue la página
document.addEventListener('DOMContentLoaded', function() {
    calendarioInstancia = flatpickr("#calendario", {
        locale: "es",
        dateFormat: "Y-m-d",
        minDate: "today",
        disableMobile: "true",
        onChange: function(selectedDates, dateStr) {
            // Cuando el usuario elige una fecha, verificamos los turnos
            actualizarTurnos(dateStr);
        }
    });
});

// 3. Función que se ejecuta al cambiar de Quincho
function actualizarCalendario() {
    const quinchoId = document.getElementById('select_quincho').value;
    const inputFecha = document.getElementById('calendario');
    
    // Habilitamos el input de fecha y lo limpiamos
    inputFecha.placeholder = "Seleccione una fecha...";
    calendarioInstancia.clear();
    
    // Resetear radios de turnos
    resetearTurnos();
}

// 4. Función para bloquear turnos según la fecha y quincho elegido
function actualizarTurnos(fechaSel) {
    const quinchoId = document.getElementById('select_quincho').value;
    const radioD = document.getElementById('radio-diurno');
    const radioN = document.getElementById('radio-nocturno');
    const labelD = document.getElementById('label-diurno');
    const labelN = document.getElementById('label-nocturno');
    const aviso = document.getElementById('aviso-turno');

    resetearTurnos();

    if(!fechaSel || !quinchoId) return;

    // Filtrar las reservas que coinciden con el quincho y fecha seleccionada
    const ocupados = bloqueadas.filter(b => b.quincho_id == quinchoId && b.fecha_reserva == fechaSel);

    ocupados.forEach(o => {
        if(o.turno === 'Diurno') {
            radioD.disabled = true;
            radioD.checked = false;
            labelD.style.opacity = "0.3";
            labelD.style.textDecoration = "line-through";
            if(!radioN.disabled) radioN.checked = true;
            aviso.innerText = "⚠️ El turno diurno ya está solicitado.";
        }
        if(o.turno === 'Nocturno') {
            radioN.disabled = true;
            radioN.checked = false;
            labelN.style.opacity = "0.3";
            labelN.style.textDecoration = "line-through";
            if(!radioD.disabled) radioD.checked = true;
            aviso.innerText = "⚠️ El turno nocturno ya está solicitado.";
        }
    });

    if(radioD.disabled && radioN.disabled) {
        aviso.innerText = "❌ Este día no tiene turnos disponibles para este quincho.";
    }
}

function resetearTurnos() {
    const radioD = document.getElementById('radio-diurno');
    const radioN = document.getElementById('radio-nocturno');
    const labelD = document.getElementById('label-diurno');
    const labelN = document.getElementById('label-nocturno');
    const aviso = document.getElementById('aviso-turno');

    radioD.disabled = false;
    radioN.disabled = false;
    labelD.style.opacity = "1";
    labelD.style.textDecoration = "none";
    labelN.style.opacity = "1";
    labelN.style.textDecoration = "none";
    aviso.innerText = "";
}
</script>
<script>
    const menuToggle = document.getElementById('menuToggle');
    const mainNav = document.getElementById('mainNav');

    menuToggle.addEventListener('click', () => {
        // Abre/Cierra el menú lateral
        mainNav.classList.toggle('active');
        // Convierte las barras en una X
        menuToggle.classList.toggle('open');
    });

    // Cerrar el menú si se hace clic en un enlace (ideal para navegación interna)
    document.querySelectorAll('.main-nav a').forEach(link => {
        link.addEventListener('click', () => {
            mainNav.classList.remove('active');
            menuToggle.classList.remove('open');
        });
    });

    // Cerrar si se hace clic fuera del menú
    document.addEventListener('click', (e) => {
        if (!mainNav.contains(e.target) && !menuToggle.contains(e.target)) {
            mainNav.classList.remove('active');
            menuToggle.classList.remove('open');
        }
    });
</script>
<?php require_once "../pie.php"; ?>
</body>
</html>