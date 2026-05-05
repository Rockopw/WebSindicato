<?php
// 1. Configuración de errores y sesión
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// 2. Blindaje de Sesión
if (!isset($_SESSION['afiliado_id'])) {
    header("Location: ../login.php");
    exit();
}

// 3. CONEXIÓN A LA DB (ESTO FALTABA)
$host = 'localhost'; $db = 'sindicato'; $user = 'rudagaleano'; $pass = 'Napst3rfarr3l';
$clave_secreta = "Patoneta-2026"; // <-- AÑADE ESTA LÍNEA (debe ser la misma que en el comprobante)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { 
    die("Error de conexión: " . $e->getMessage()); 
}

$afiliado_id = $_SESSION['afiliado_id'];
$nombre_afiliado = $_SESSION['afiliado_nombre'] ?? 'Afiliado';
$mensaje = "";
if (isset($_SESSION['mensaje_toast'])) {
    $m = $_SESSION['mensaje_toast'];
    $mensaje = "<div class='alert alert-{$m['tipo']}'>{$m['texto']}</div>";
    unset($_SESSION['mensaje_toast']); // Lo borramos para que no aparezca siempre
}

// 4. CARGAR PHPMAILER (Rutas ajustadas para la raíz)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

function enviarMailAvisoSecretaria($reserva_id, $datos, $pdo) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'rubengaleano83@gmail.com'; 
        $mail->Password   = 'maoozkskidrbuasz'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port       = 587;

        $mail->setFrom('rubengaleano83@gmail.com', 'Sistema Sindicato');
        $mail->addAddress('rubengaleano83@gmail.com'); // Correo del Secretario

        $mail->isHTML(true);
        $mail->Subject = 'SOLICITUD DE RESERVA PENDIENTE: ' . $datos['afiliado'];

          $cuerpo = "
            <div style='font-family: sans-serif; border: 1px solid #ddd; padding: 20px;'>
                <h2 style='color: #985605;'>Nueva Solicitud de Reserva 🏙️​</h2>
                <p><strong>Afiliado:</strong> {$datos['afiliado']}</p>
                <p><strong>Quincho:</strong> {$datos['depto']}</p>
                <p><strong>Fechas:</strong> {$datos['desde']} al {$datos['hasta']}</p>
                
                <p><strong>Estado:</strong> <span style='color: orange;'>PENDIENTE DE APROBACION</span></p>
                <hr>
                <p style='font-size: 0.8em; color: #666;'>Ingrese al panel de administración para aprobar o rechazar.</p>
            </div>";

       

        $mail->Body = $cuerpo;
        $mail->send();
        return true;
    } catch (Exception $e) { return false; }
}

// 5. LÓGICA DE PROCESAMIENTO (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- ACCIÓN: NUEVA RESERVA ---
if (isset($_POST['depto_id']) && isset($_POST['fecha_desde'])) {
    $dep_id = $_POST['depto_id'];
    $f_desde = $_POST['fecha_desde'];
    $f_hasta = $_POST['fecha_hasta'];

    if (strtotime($f_desde) >= strtotime($f_hasta)) {
        $_SESSION['mensaje_toast'] = ["tipo" => "danger", "texto" => "❌ La fecha de salida debe ser posterior."];
    } else {
        $sql_check = "SELECT COUNT(*) FROM reservas_deptos WHERE depto_id = :dep_id AND (:f_desde < fecha_hasta AND :f_hasta > fecha_desde) AND estado != 'rechazado'";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute(['dep_id' => $dep_id, 'f_desde' => $f_desde, 'f_hasta' => $f_hasta]);

        if ($stmt_check->fetchColumn() > 0) {
            $_SESSION['mensaje_toast'] = ["tipo" => "danger", "texto" => "❌ Este Departamento ya está ocupado."];
        } else {
            $sql_ins = "INSERT INTO reservas_deptos (afiliados_id, depto_id, fecha_desde, fecha_hasta, estado) 
                        VALUES (:af_id, :dep_id, :f_desde, :f_hasta, 'pendiente')";
            $stmt_ins = $pdo->prepare($sql_ins);
            $stmt_ins->execute(['af_id' => $afiliado_id, 'dep_id' => $dep_id, 'f_desde' => $f_desde, 'f_hasta' => $f_hasta]);

          //ACA BORRAMOS LOS INVITADOS BASURA   
            $reserva_id_nueva = $pdo->lastInsertId();

            // LIMPIAR invitados por seguridad (caso pruebas o IDs reutilizados)
            $stmt_del_inv = $pdo->prepare("DELETE FROM invitados WHERE reserva_id = ?");
            $stmt_del_inv->execute([$reserva_id_nueva]);

            
            $_SESSION['mensaje_toast'] = ["tipo" => "success", "texto" => "✅ Solicitud registrada. Cargue sus invitados y presione 'Enviar a Secretaría'."];
            // REDIRECCIÓN PARA EVITAR REENVÍO AL ACTUALIZAR
            header("Location: " . $_SERVER['PHP_SELF'] . "#reservas");
            exit();
        }
    }
}


// --- ACCIÓN: ENVIAR A SECRETARÍA (Aviso) ---
if (isset($_POST['enviar_a_secretaria_id'])) {
    $id_res = $_POST['enviar_a_secretaria_id'];
    
    $stmt = $pdo->prepare("SELECT r.*, d.nombre as departamento_n FROM reservas_deptos r
                           JOIN departamentos d ON r.depto_id = d.depto_id
                           WHERE r.reserva_id = ?");
    $stmt->execute([$id_res]);
    $r_info = $stmt->fetch();

    if ($r_info && $r_info['mail_enviado'] == 0) { // Solo si NO se envió antes
        $datosMail = [
            'afiliado' => $nombre_afiliado,
            'depto'   => $r_info['departamento_n'],
            'desde'    => $r_info['fecha_desde'],
            'hasta'    => $r_info['fecha_hasta']
        ];

        if (enviarMailAvisoSecretaria($id_res, $datosMail, $pdo)) {
            // MARCAR COMO ENVIADO EN LA DB
            $stmt_update = $pdo->prepare("UPDATE reservas_deptos SET mail_enviado = 1 WHERE reserva_id = ?");
            $stmt_update->execute([$id_res]);
            
            $_SESSION['mensaje_toast'] = ["tipo" => "info", "texto" => "📧 Solicitud enviada al Secretario de Deportes, recibira la confirmacion a su casilla de correo electronico registrado 📩."];
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "#reservas");
        exit();
    }
}

   

 // --- ACCIÓN: CANCELAR RESERVA ---
if (isset($_POST['cancelar_id'])) {

    $reserva_id = $_POST['cancelar_id'];

    // 1. BORRAR PRIMERO LOS INVITADOS
    $stmt_del_inv = $pdo->prepare("DELETE FROM invitados WHERE reserva_id = ?");
    $stmt_del_inv->execute([$reserva_id]);

    // 2. DESPUÉS BORRAR LA RESERVA
    $stmt_del = $pdo->prepare("DELETE FROM reservas_deptos WHERE reserva_id = ? AND afiliados_id = ? AND estado = 'pendiente'");
    $stmt_del->execute([$reserva_id, $afiliado_id]);

    $mensaje = "<div class='alert alert-success'>✅ Solicitud cancelada correctamente.</div>";
}

   // --- ACCIÓN: AGREGAR INVITADO ---
            if (isset($_POST['nombre_invitado'])) {
                $res_id = $_POST['reserva_id_invitado'];
                $nombre_inv = $_POST['nombre_invitado'];
                $dni_inv = $_POST['dni_invitado'];
                $tipo_inv = $_POST['tipo_invitado'];

                // 1. Obtenemos la capacidad máxima del departamento
                $stmt_cap = $pdo->prepare("SELECT c.capacidad_maxima FROM reservas_deptos r JOIN departamentos c ON r.depto_id = c.depto_id WHERE r.reserva_id = ?");
                $stmt_cap->execute([$res_id]);
                $cap_max = $stmt_cap->fetchColumn();

                // 2. Contamos cuántos invitados ya hay cargados
                $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM invitados WHERE reserva_id = ?");
                $stmt_count->execute([$res_id]);
                $invitados_actuales = $stmt_count->fetchColumn();

                // 3. CÁLCULO CLAVE: Sumamos 1 (el afiliado) + los invitados ya cargados + 1 (el que intenta agregar ahora)
                $total_personas = 1 + $invitados_actuales + 1;

                if ($total_personas > $cap_max) {
                    $mensaje = "<div class='alert alert-warning'>⚠️ Capacidad máxima alcanzada. El cupo de $cap_max personas incluye al afiliado.</div>";
                } else {
                    $stmt_ins_inv = $pdo->prepare("INSERT INTO invitados (reserva_id, nombre_completo, dni, tipo) VALUES (?, ?, ?, ?)");
                    $stmt_ins_inv->execute([$res_id, $nombre_inv, $dni_inv, $tipo_inv]);
                    $mensaje = "<div class='alert alert-success'>✅ Invitado añadido.</div>";   
                }
            }
}

// 6. Cargar datos para la vista
$hoy = date('Y-m-d');
$mis_reservas = $pdo->prepare("SELECT r.*, c.nombre as depto_nombre 
    FROM reservas_deptos r 
    JOIN departamentos c ON r.depto_id = c.depto_id 
    WHERE r.afiliados_id = :af_id AND r.fecha_hasta >= :hoy 
    ORDER BY r.fecha_desde ASC");
$mis_reservas->execute(['af_id' => $afiliado_id, 'hoy' => $hoy]);
$reservas = $mis_reservas->fetchAll();

$deptos = $pdo->query("SELECT * FROM departamentos ORDER BY depto_id ASC")->fetchAll(PDO::FETCH_ASSOC);
$todas_reservas = $pdo->query("SELECT depto_id, fecha_desde, fecha_hasta FROM reservas_deptos WHERE estado != 'rechazado'")->fetchAll(PDO::FETCH_ASSOC);
$reservas_json = json_encode($todas_reservas);
?>




<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reservas - Departamentos</title>
    <link rel="stylesheet" href="../estilos_reservas.css">
    <link rel="stylesheet" href="../style.css">
    <?php 
    require_once "../encabezado.php";
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"> <!-- esto es para que ande el calendario-->
    <style>
        .btn-confirmar-final { background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn-confirmar-final:hover { background: #218838; }
        .reserva-item {   background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-left: 5px solid #8cbfd2; }
        .form-invitado-mini { display: flex; gap: 10px; flex-wrap: wrap; margin: 15px 0; background: #f8f9fa; padding: 15px; border-radius: 8px; }
        .form-invitado-mini input, .form-invitado-mini select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
 .badge-rechazado {  background-color: #e90a0a; color: #fff; padding:    5px;margin-left:20px;border-radius: 5px;font-weight: bold; font-size: 0.8em; }


.estado-reserva{
    display:flex;
    align-items:center;
    gap:10px;
}

.motivo-texto {
    background: #fff5f5;
    border-left: 3px solid #dc3545;
    padding: 5px 8px;
    font-size: 0.9em;
    color: #a02020;
    border-radius: 6px;
}
/* ... boton amarillo pendiente */
    .btn-pendiente { 
        background-color: #ffc107 !important; 
        color: #000 !important; 
        border: none; 
        padding: 4px 15px; 
        border-radius: 6px; 
        font-weight: bold; 
        cursor: not-allowed; 
        display: inline-block;
    }  
     
   </style>


<style>
    /* Color de fondo de los días seleccionados y el rango */
    .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange,
    .flatpickr-day.selected:hover, .flatpickr-day.startRange:hover, 
    .flatpickr-day.endRange:hover, .flatpickr-day.selected.focus, 
    .flatpickr-day.startRange.focus, .flatpickr-day.endRange.focus {
        background: #488f3fff !important; /* Verde bosque */
        border-color: #488f3fff !important;
    }

    .flatpickr-day.inRange {
        background: #e8f5e9 !important; /* Verde muy clarito para el medio */
        box-shadow: -5px 0 0 #e8f5e9, 5px 0 0 #e8f5e9 !important;
    }

    /* Estilo para los días bloqueados (ocupados) */
    .flatpickr-day.flatpickr-disabled, .flatpickr-day.flatpickr-disabled:hover {
        background: #fdeaea !important;
        color: #d9534f !important; /* Rojo suave para indicar "no disponible" */
        text-decoration: line-through;
    }

    /* Encabezado del calendario */
    .flatpickr-months {
        background: #3e8f7aff;
        color: white;
        border-radius: 5px 5px 0 0;
    }
    .flatpickr-month, .flatpickr-weekday {
        color: white !important;
        fill: white !important;
    }
     /*aca va el estilo de los invitados*/

.form-invitado-mini {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    background: #d3f8b5;
    padding: 10px;
    border-radius: 5px;
}

.form-invitado-mini input, .form-invitado-mini select {
    padding: 5px;
    border: 1px solid #ccc;
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
    /* Cambiamos el verde por un cel/azul brillante */
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
}

/* cuanto seleciono un departamento resalta */
.cabin-card.selected {
    border: 3px solid #2499c7 !important; /* Contorno celeste */
    box-shadow: 0 0 15px rgba(36, 153, 199, 0.6); /* Sombra celeste sutil */
    transform: scale(1.02); /* Un pequeño aumento para resaltar */
    transition: all 0.3s ease; /* Transición suave */
}

/* Opcional: para que toda la tarjeta parezca cliqueable */
.cabin-card {
    cursor: pointer;
    transition: all 0.3s ease;
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
            <h2>Departamentos del Sindicato</h2>
        </div>
        <img src="../departamentos/fotos/dpto 1/frente 1.jpeg" class="d-block w-100 slider-img" alt="Predio">
        </div>

        <div class="carousel-item">
        <div class="slider-overlay">
            <h2>Lavalle 2445 - Posadas Misiones</h2>
        </div>
        <img src="../departamentos/fotos/dpto 1/frente 2.jpeg" class="d-block w-100 slider-img" alt="Predio">
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
<section id="reservas"></section>
 
  <p>&nbsp;</p> <!-- dejo un parrafo vacio -->
  <p>&nbsp;</p> <!-- dejo un parrafo vacio -->




    <div class="container mt-5" id="reservas">
        <h1> 🏙️​ Gestión Reservas Departamentos</h1>
          <p>&nbsp;</p> <!-- dejo un parrafo vacio -->
        <?php echo $mensaje; ?>

        <div class="cabin-gallery">
            <div class="cabin-card" id="card-1" onclick="selectDepto(1)">
               <div class="cabin-image-grid">
                    <img src="../departamentos/fotos/dpto 1/1.jpeg" alt="Vista 1" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="../departamentos/fotos/dpto 1/2.jpeg" alt="Vista 2" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="../departamentos/fotos/dpto 1/3.jpeg" alt="Vista 3" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="../departamentos/fotos/dpto 1/4.jpeg" alt="Vista 4" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="../departamentos/fotos/dpto 1/5.jpeg" alt="Vista 5" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="../departamentos/fotos/dpto 1/6.jpeg" alt="Vista 6" onclick="openModal(this.src); event.stopPropagation();">
                 
                </div>
                <div class="info">Departamento 1</div>
                <p style="font-size: 0.8rem; text-align: center;">Incluye 1 habitacion para 4 personas,Sala,tv,wifi,aire,Baño,Cocina totalmente equipada,Heladera y Estacionamiento</p>

            </div>

            <div class="cabin-card" id="card-2" onclick="selectDepto(2)">
                 <div class="cabin-image-grid">
                    <img src="imagenes/cabaña itu/2/cabaña.png" alt="Vista 1" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="imagenes/cabaña itu/2/1.jpeg" alt="Vista 2" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="imagenes/cabaña itu/2/2.jpeg" alt="Vista 3" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="imagenes/cabaña itu/2/3.jpeg" alt="Vista 4" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="imagenes/cabaña itu/2/4.jpeg" alt="Vista 5" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="imagenes/cabaña itu/2/5.jpeg" alt="Vista 6" onclick="openModal(this.src); event.stopPropagation();">
                </div>
                <div class="info">Departamento 2</div>
            </div>

            <div class="cabin-card" id="card-3" onclick="selectDepto(3)">
                 <div class="cabin-image-grid">
                    <img src="../departamentos/fotos/dpto 3/1.jpeg" alt="Vista 1" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="../departamentos/fotos/dpto 3/2.jpeg" alt="Vista 2" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="../departamentos/fotos/dpto 3/3.jpeg" alt="Vista 3" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="../departamentos/fotos/dpto 3/4.jpeg" alt="Vista 4" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="../departamentos/fotos/dpto 3/5.jpeg" alt="Vista 5" onclick="openModal(this.src); event.stopPropagation();">
                    <img src="../departamentos/fotos/dpto 3/6.jpeg" alt="Vista 6" onclick="openModal(this.src); event.stopPropagation();">
                </div>
                <div class="info">Departamento 3</div>
                <p style="font-size: 0.8rem; text-align: center;">Incluye 2 habitaciones para 4 personas,Tv,wifi,cocina,heladera,aire,Sala de star,baños y estacionamiento</p>
            </div>
        </div>

      <form method="POST" class="mt-4">
    <fieldset>
        <legend>Realizar Nueva Reserva</legend>
        
        <div class="form-group">
            <label>Departamento:</label>
            <select name="depto_id" id="selectDepto" class="form-control" required>
                <?php foreach ($deptos as $c): ?>
                    <option value="<?php echo $c['depto_id']; ?>">
                        <?php echo htmlspecialchars($c['nombre']); ?> (Capacidad: <?php echo $c['capacidad_maxima']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Seleccione Período (Entrada y Salida):</label>
            <input type="text" id="calendario_reserva" class="form-control" placeholder="Haga clic para elegir sus fechas..." readonly style="background-color: #fff; cursor: pointer;" required>
            
            <input type="hidden" name="fecha_desde" id="fecha_desde">
            <input type="hidden" name="fecha_hasta" id="fecha_hasta">
        </div>

        <button type="submit" formaction="#reservas" class="btn btn-primary w-100">Verificar Disponibilidad</button>

        <p>&nbsp;</p> <!-- dejo un parrafo vacio -->
    </fieldset>
    
</form>
        

        <h3>📅 Tus Reservas Actuales</h3>
        <p>Esta solicitud queda a consideración del Secretario de Deportes. Por favor, aguarde su aprobación.
        En caso de no poder concurrir, le solicitamos tenga a bien cancelar su reserva, de modo que el turno quede disponible para otro afiliado.
        Muchas gracias.</p>
        <div class="reservas-list">
    <?php foreach ($reservas as $res): ?>
        <div class="reserva-item">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                
               
            
            <div style="flex-grow: 1; min-width: 250px;">
    <h5 style="margin-bottom: 5px; color: #2c3e50;">
        <i class="fas fa-building"></i> <?php echo htmlspecialchars($res['depto_nombre']); ?>
    </h5>
    <div style="display: flex; gap: 15px; flex-wrap: wrap; color: #666; font-size: 0.95em;">
        <span><strong>📅 Desde:</strong> <?php echo date('d/m/Y', strtotime($res['fecha_desde'])); ?></span>
        <span><strong>📅 Hasta:</strong> <?php echo date('d/m/Y', strtotime($res['fecha_hasta'])); ?></span>
    </div>

    <div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #eee;">
        <strong style="font-size: 0.85rem; color: #333;">👥 Invitados:</strong>
        <div style="display: flex; flex-wrap: wrap; gap: 5px; margin-top: 5px;">
            <?php
            $stmt_inv = $pdo->prepare("SELECT nombre_completo, tipo FROM invitados WHERE reserva_id = ?");
            $stmt_inv->execute([$res['reserva_id']]);
            $invs = $stmt_inv->fetchAll();

            if (count($invs) > 0) {
                foreach ($invs as $i) {
                    $color = ($i['tipo'] == 'familiar') ? '#d1ecf1' : '#fff3cd';
                    $texto = ($i['tipo'] == 'familiar') ? '#0c5460' : '#856404';
                    echo "<span style='background: $color; color: $texto; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem; border: 1px solid rgba(0,0,0,0.05);'>";
                    echo htmlspecialchars($i['nombre_completo']);
                    echo "</span>";
                }
            } else {
                echo "<span style='color: #999; font-size: 0.8rem; font-style: italic;'>Sin invitados cargados</span>";
            }
            ?>
        </div>
    </div>
</div>
            
            
            
            
            
            
           

                      <div class="reserva-acciones" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
    <?php if ($res['estado'] == 'pendiente'): ?>
        
        <?php if ($res['mail_enviado'] == 0): ?>
            <button class="btn-invitados" onclick="toggleInvitados(<?php echo $res['reserva_id']; ?>)">👥 Cargar Invitados</button>
            
            <form method="POST" action="#reservas" style="display:inline;">
                <input type="hidden" name="enviar_a_secretaria_id" value="<?php echo $res['reserva_id']; ?>">
                <button type="submit" class="btn-confirmar-final" style="background: #007bff; border:none;">📧 Enviar a Secretaría</button>
            </form>

            <form method="POST" action="#reservas" style="display:inline; margin-left: 4px; " onsubmit="return confirm('¿Eliminar reserva?');">
                <input type="hidden" name="cancelar_id" value="<?php echo $res['reserva_id']; ?>">
                <button type="submit" class="btn-cancelar">Eliminar</button>
            </form>

        <?php else: ?>
            <button class="btn btn-secondary btn-sm" style="cursor: not-allowed; opacity: 0.7;" disabled>👥 Invitados </button>
            <button type="button" class="btn-pendiente" disabled>⏳ Esperando Aprobación</button>
             <button class="btn btn-secondary btn-sm" style="cursor: not-allowed; opacity: 0.7;" disabled> Eliminar</button>
        <?php endif; ?>

    <?php elseif ($res['estado'] == 'confirmado'): 
        $hash = hash_hmac('sha256', $res['reserva_id'], $clave_secreta);
        ?>
        <span class="badge bg-success" style="padding: 10px;">✅ RESERVA APROBADA</span>
        <a href="comprobante_depto.php?id=<?php echo $res['reserva_id']; ?>&hash=<?php echo $hash; ?>" 
           class="btn btn-sm btn-dark" style="text-decoration:none;">🖨️ Ver Voucher</a>

    <?php elseif ($res['estado'] == 'rechazado'): ?>
        <span class="badge-rechazado">❌RESERVA RECHAZADA</span>
        <span class="motivo-texto"> Motivo: <?php echo htmlspecialchars($res['motivo_rechazo'] ?? 'No especificado'); ?></span>
    <?php endif; ?>


                </div>
            </div>

            <div id="seccion-inv-<?php echo $res['reserva_id']; ?>" style="display:none; margin-top:15px; border-top: 1px dashed #ccc; padding-top:15px;">
    <h5>👥 Invitados para esta reserva</h5>
    
    <?php $bloqueado = ($res['mail_enviado'] == 1) ? 'disabled' : ''; ?>

                <form method="POST" action="#reservas" class="form-invitado-mini">
                    <input type="hidden" name="reserva_id_invitado" value="<?php echo $res['reserva_id']; ?>">
                    
                    <input type="text" name="nombre_invitado" placeholder="Nombre completo" required <?php echo $bloqueado; ?>>
                    <input type="number" name="dni_invitado" placeholder="DNI" required <?php echo $bloqueado; ?>>
                    
                    <select name="tipo_invitado" required <?php echo $bloqueado; ?>>
                        <option value="familiar">Familiar</option> 
                        <option value="invitado">Invitado</option>
                    </select>
                    
                    <?php if (!$bloqueado): ?>
                        <button type="submit" class="btn btn-sm btn-success">+ Agregar</button>
                    <?php else: ?>
                        <span class="text-muted" style="font-size: 0.8em;">(Carga cerrada)</span>
                    <?php endif; ?>
                </form>

    <hr>

    <table class="table table-sm" style="font-size: 0.9rem;">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>DNI</th>
                <th>Parentesco</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Consultamos los invitados de esta reserva específica
            $stmt_inv = $pdo->prepare("SELECT * FROM invitados WHERE reserva_id = ?");
            $stmt_inv->execute([$res['reserva_id']]);
            $invitados_cargados = $stmt_inv->fetchAll();

            if (count($invitados_cargados) > 0):
                foreach ($invitados_cargados as $inv): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($inv['nombre_completo']); ?></td>
                        <td><?php echo htmlspecialchars($inv['dni']); ?></td>

                        <td>
                        <span class="badge bg-secondary">
                            <?php echo ($inv['tipo'] == 'familiar' ? 'Familiar' : 'Invitado'); ?>
                        </span>
                    </td>
                    </tr>
                <?php endforeach; 
            else: ?>
                <tr><td colspan="3" class="text-muted">No hay invitados cargados aún.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
        </div>
    <?php endforeach; ?>
</div>



    <script>
   function selectDepto(id) {
    // 1. Actualizar el valor del select oculto (lo que ya hacías)
    document.getElementById('selectDepto').value = id;

    // 2. Quitar la clase 'selected' de TODAS las tarjetas de departamento
    const todasLasTarjetas = document.querySelectorAll('.cabin-card');
    todasLasTarjetas.forEach(card => {
        card.classList.remove('selected');
    });

    // 3. Agregar la clase 'selected' a la tarjeta clickeada
    // Asegúrate de que los IDs en el HTML sean 'card-1', 'card-2', etc.
    const tarjetaSeleccionada = document.getElementById('card-' + id);
    if (tarjetaSeleccionada) {
        tarjetaSeleccionada.classList.add('selected');
    }

    // 4. (Opcional) Refrescar el calendario si tienes la lógica de disponibilidad
    if (typeof inicializarCalendario === "function") {
        inicializarCalendario();
    }
}
    </script>
    <script>
// Pasamos los datos de PHP a JS
const todasReservas = <?php echo $reservas_json; ?>;

let calendario;

function inicializarCalendario() {
    const deptoSeleccionada = document.getElementById('selectDepto').value;
    
    // Filtrar solo las reservas de la cabaña elegida
    const fechasBloqueadas = todasReservas
        .filter(r => r.depto_id == deptoSeleccionada)
        .map(r => ({
            from: r.fecha_desde,
            to: r.fecha_hasta
        }));

    // Si ya existe un calendario, lo destruimos para crear uno nuevo con las fechas de la otra cabaña
    if (calendario) calendario.destroy();

   calendario = flatpickr("#calendario_reserva", {
    mode: "range",
    minDate: "today",
    showMonths: 2, // <--- Esto muestra dos meses a la vez
    locale: "es",
    dateFormat: "Y-m-d",
    disable: fechasBloqueadas,
    // ... resto del código igual
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                // Formateamos las fechas para los campos ocultos que enviará el POST
                document.getElementById('fecha_desde').value = instance.formatDate(selectedDates[0], "Y-m-d");
                document.getElementById('fecha_hasta').value = instance.formatDate(selectedDates[1], "Y-m-d");
            }
        }
    });
}

// Escuchar cuando cambian la cabaña para actualizar el calendario
document.getElementById('selectDepto').addEventListener('change', inicializarCalendario);

// Iniciar por primera vez
window.onload = inicializarCalendario;


</script>

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
    <div id="imageModal" class="modal" onclick="closeModal()">
        <span class="close-modal">×</span>
        <img class="modal-content" id="fullImg">
    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script> <!-- esto es para que ande el carrusel-->

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script><!-- esto es para que ande el calendario-->
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script> <!-- esto es para que ande el calendario-->



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

<script>
function toggleInvitados(id) {
    const seccion = document.getElementById('seccion-inv-' + id);
    if (seccion) {
        // Si está oculta la muestra, si está visible la oculta
        if (seccion.style.display === "none" || seccion.style.display === "") {
            seccion.style.display = "block";
        } else {
            seccion.style.display = "none";
        }
    }
}
</script>
</body>
<?php require_once "../pie.php"; ?>
</html>