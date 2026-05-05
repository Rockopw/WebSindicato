<?php
session_start();

// 1. Protección de la página
if (!isset($_SESSION['afiliados_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Conexión a la DB
$host = 'localhost';
 $db = 'sindicato'; 
 $user = 'rudagaleano';
  $pass = 'Napst3rfarr3l';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

$mensaje = "";
$afiliado_id = $_SESSION['afiliados_id'];

// 3. Procesar la Reserva
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cabana_id = $_POST['cabana_id'];
    $fecha_desde = $_POST['fecha_desde'];
    $fecha_hasta = $_POST['fecha_hasta'];

    // Validar que las fechas sean coherentes
    if (strtotime($fecha_desde) >= strtotime($fecha_hasta)) {
        $mensaje = "<div class='mensaje-error'>❌ La fecha de salida debe ser posterior a la de entrada.</div>";
    } else {
        // VERIFICAR DISPONIBILIDAD (Evitar solapamientos)
        $sql_check = "SELECT COUNT(*) FROM Reservas 
                      WHERE cabana_id = :cab_id 
                      AND (:f_desde < fecha_hasta AND :f_hasta > fecha_desde)";
        
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([
            'cab_id' => $cabana_id,
            'f_desde' => $fecha_desde,
            'f_hasta' => $fecha_hasta
        ]);
        
        if ($stmt_check->fetchColumn() > 0) {
            $mensaje = "<div class='mensaje-error'>❌ Lo sentimos, la cabaña ya está reservada en esas fechas.</div>";
        } else {
            // Realizar la reserva
            $sql_reserva = "INSERT INTO Reservas (afiliados_id, cabana_id, fecha_desde, fecha_hasta) 
                            VALUES (:af_id, :cab_id, :f_desde, :f_hasta)";
            $stmt_reserva = $pdo->prepare($sql_reserva);
            
            if ($stmt_reserva->execute([
                'af_id' => $afiliados_id,
                'cab_id' => $cabana_id,
                'f_desde' => $fecha_desde,
                'f_hasta' => $fecha_hasta
            ])) {
                $mensaje = "<div class='mensaje-exito'>✅ ¡Reserva realizada con éxito!</div>";
            }
        }
    }
}

// 4. Obtener las cabañas para el selector
$cabanas = $pdo->query("SELECT * FROM cabañas")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reservar Cabaña - Sindicato</title>
    <link rel="stylesheet" href="estilos_afiliados.css">
</head>
<body>
    <div class="container">
        <header style="display: flex; justify-content: space-between; align-items: center;">
            <h1>Reservas</h1>
            <a href="logout.php" class="btn-remove" style="text-decoration: none;">Cerrar Sesión</a>
        </header>

        <p>Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['afiliado_nombre']); ?></strong>.</p>
        
        <?php echo $mensaje; ?>

        <form method="POST">
            <fieldset>
                <legend>Nueva Reserva</legend>
                
                <div class="form-group">
                    <label>Seleccione Cabaña:</label>
                    <select name="cabana_id" required>
                        <?php foreach ($cabanas as $c): ?>
                            <option value="<?php echo $c['cabana_id']; ?>">
                                <?php echo $c['nombre']; ?> (Capacidad: <?php echo $c['capacidad_maxima']; ?> personas)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Fecha de Entrada:</label>
                    <input type="date" name="fecha_desde" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label>Fecha de Salida:</label>
                    <input type="date" name="fecha_hasta" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                </div>

                <button type="submit" class="btn-primary">Verificar y Reservar</button>
            </fieldset>
        </form>

        <hr>
        <h3>Tus Reservas Actuales</h3>
        </div>
</body>
</html>







<!-- ACA VA EL CODIGO QUE FUNCIONABA DE Reservas-->






<?php
// 1. Configuración de errores y sesión
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// 2. Blindaje de Sesión: Si no hay ID, redirigir
// Nota: Verifica si tu login usa 'afiliado_id' o 'afiliados_id'. Usaré 'afiliado_id' según tu código actual.
if (!isset($_SESSION['afiliado_id'])) {
    header("Location: login.php");
    exit();
}

$afiliado_id = $_SESSION['afiliado_id'];
$nombre_afiliado = $_SESSION['afiliado_nombre'] ?? 'Afiliado'; // Evita el error de htmlspecialchars

// 3. Conexión a la DB
$host = 'localhost'; $db = 'sindicato'; $user = 'rudagaleano'; $pass = 'Napst3rfarr3l';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { 
    die("Error de conexión: " . $e->getMessage()); 
}

$mensaje = "";

// aca se define la funcion de envio de la reserva por correo
function enviarMailSecretaria($reserva_id, $datos, $pdo) {
    $destinatario = "rubengaleano83@gmail.com"; //puse mi mail, cambiar
    $asunto = "CONFIRMACIÓN DE RESERVA: " . $datos['afiliado'];
    
    // Consultar invitados
    $stmt_inv = $pdo->prepare("SELECT nombre_completo, dni FROM invitados WHERE reserva_id = ?");
    $stmt_inv->execute([$reserva_id]);
    $invitados = $stmt_inv->fetchAll();

    $mensaje = "Nueva reserva confirmada por el afiliado:\n\n";
    $mensaje .= "👤 Titular: " . $datos['afiliado'] . "\n";
    $mensaje .= "🏠 Cabaña: " . $datos['cabana'] . "\n";
    $mensaje .= "📅 Estadía: " . date('d/m/Y', strtotime($datos['desde'])) . " al " . date('d/m/Y', strtotime($datos['hasta'])) . "\n\n";
    
    $mensaje .= "👥 INVITADOS REGISTRADOS:\n";
    if (count($invitados) > 0) {
        foreach ($invitados as $inv) {
            $mensaje .= "- " . $inv['nombre_completo'] . " (DNI: " . $inv['dni'] . ")\n";
        }
    } else {
        $mensaje .= "Sin invitados adicionales.\n";
    }

    $headers = "From: sistema@sindicato.com";
    mail($destinatario, $asunto, $mensaje, $headers);
}



// 4. LÓGICA DE PROCESAMIENTO (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- ACCIÓN: NUEVA RESERVA ---
    if (isset($_POST['cabana_id']) && isset($_POST['fecha_desde'])) {
        $cab_id = $_POST['cabana_id'];
        $f_desde = $_POST['fecha_desde'];
        $f_hasta = $_POST['fecha_hasta'];

        try {
            if (strtotime($f_desde) >= strtotime($f_hasta)) {
                $mensaje = "<div class='mensaje-error'>❌ La fecha de salida debe ser posterior a la de entrada.</div>";
            } else {
                // Verificar disponibilidad
                $sql_check = "SELECT COUNT(*) FROM reservas 
                              WHERE cabana_id = :cab_id 
                              AND (:f_desde < fecha_hasta AND :f_hasta > fecha_desde)";
                $stmt_check = $pdo->prepare($sql_check);
                $stmt_check->execute([
                    'cab_id' => $cab_id, 
                    'f_desde' => $f_desde, 
                    'f_hasta' => $f_hasta
                ]);

                if ($stmt_check->fetchColumn() > 0) {
                    $mensaje = "<div class='mensaje-error'>❌ Esta cabaña ya está ocupada en esas fechas.</div>";
                } else {
                    $sql_ins = "INSERT INTO reservas (afiliados_id, cabana_id, fecha_desde, fecha_hasta) 
                                VALUES (:af_id, :cab_id, :f_desde, :f_hasta)";
                    $pdo->prepare($sql_ins)->execute([
                        'af_id' => $afiliado_id, 
                        'cab_id' => $cab_id, 
                        'f_desde' => $f_desde, 
                        'f_hasta' => $f_hasta
                    ]);

                    // ... dentro del try del INSERT de reserva ...
                        if ($stmt_reserva->execute([
                            'af_id' => $afiliado_id, 
                            'cab_id' => $cabana_id,
                            'f_desde' => $fecha_desde,
                            'f_hasta' => $fecha_hasta
                        ])) {
                            $mensaje = "<div class='mensaje-exito'>✅ ¡Reserva realizada con éxito!</div>";

                            // --- ENVIAR CORREO AL SECRETARIO ---
                            // Buscamos el nombre de la cabaña para el mail
                            $stmt_c = $pdo->prepare("SELECT nombre FROM cabañas WHERE cabana_id = ?");
                            $stmt_c->execute([$cabana_id]);
                            $nombre_c = $stmt_c->fetchColumn();

                            $datosMail = [
                                'afiliado' => $_SESSION['afiliado_nombre'],
                                'cabana' => $nombre_c,
                                'desde' => $fecha_desde,
                                'hasta' => $fecha_hasta
                            ];

                            enviarMailSecretaria($datosMail);
                            // ------------------------------------
                        }

                }
            }
        } catch (PDOException $e) { $mensaje = "<div class='mensaje-error'>❌ Error: " . $e->getMessage() . "</div>"; }


        // --- ACCIÓN: CONFIRMAR RESERVA Y ENVIAR MAIL ---
             if (isset($_POST['confirmar_y_voucher_id'])) {
                    $id_res = $_POST['confirmar_y_voucher_id'];
                    
                    // ... (aquí va tu lógica de obtener datos y enviar mail) ...
                    enviarMailSecretaria($id_res, $datosMail, $pdo);

                    // Redirección con respaldo
                    echo "<script>
                        var win = window.open('comprobante.php?id=$id_res', '_blank');
                        if (win) {
                            win.focus();
                        } else {
                            alert('Por favor, permite las ventanas emergentes para ver tu comprobante.');
                            window.location.href = 'comprobante.php?id=$id_res';
                        }
                    </script>";
                }
    }

    // --- ACCIÓN: CANCELAR RESERVA ---
    if (isset($_POST['cancelar_id'])) {
        try {
            $sql_del = "DELETE FROM reservas WHERE reserva_id = :res_id AND afiliados_id = :af_id";
            $stmt_del = $pdo->prepare($sql_del);
            $stmt_del->execute(['res_id' => $_POST['cancelar_id'], 'af_id' => $afiliado_id]);
            $mensaje = "<div class='mensaje-exito'>✅ Reserva cancelada correctamente.</div>";
        } catch (PDOException $e) { $mensaje = "<div class='mensaje-error'>❌ Error al cancelar: " . $e->getMessage() . "</div>"; }
    }

    // ACA GUARDAMOS INVITADOS

    // --- ACCIÓN: AGREGAR INVITADO ---
// --- ACCIÓN: AGREGAR INVITADO CON LÍMITE ---
if (isset($_POST['nombre_invitado'])) {
    $res_id = $_POST['reserva_id_invitado'];
    $nombre_inv = $_POST['nombre_invitado'];
    $dni_inv = $_POST['dni_invitado'];
    $tipo_inv = $_POST['tipo_invitado'];

    try {
        // 1. Obtener la capacidad máxima de la cabaña para esta reserva
        $sql_capacidad = "SELECT c.capacidad_maxima 
                          FROM reservas r 
                          JOIN cabañas c ON r.cabana_id = c.cabana_id 
                          WHERE r.reserva_id = :res_id";
        $stmt_cap = $pdo->prepare($sql_capacidad);
        $stmt_cap->execute(['res_id' => $res_id]);
        $capacidad_max = $stmt_cap->fetchColumn();

        // 2. Contar cuántos invitados hay ya cargados
        $sql_conteo = "SELECT COUNT(*) FROM invitados WHERE reserva_id = :res_id";
        $stmt_conteo = $pdo->prepare($sql_conteo);
        $stmt_conteo->execute(['res_id' => $res_id]);
        $invitados_actuales = $stmt_conteo->fetchColumn();

        // 3. Validar (Contamos al afiliado como 1 persona)
        if (($invitados_actuales + 1) >= $capacidad_max) {
            $mensaje = "<div class='mensaje-error'>⚠️ Límite alcanzado. La cabaña es para $capacidad_max personas (incluyendo al afiliado).</div>";
        } else {
            // 4. Insertar si hay lugar
            $sql_inv = "INSERT INTO invitados (reserva_id, nombre_completo, dni, tipo) 
                        VALUES (:res_id, :nom, :dni, :tipo)";
            $pdo->prepare($sql_inv)->execute([
                'res_id' => $res_id,
                'nom' => $nombre_inv,
                'dni' => $dni_inv,
                'tipo' => $tipo_inv
            ]);
            $mensaje = "<div class='mensaje-exito'>✅ Invitado agregado correctamente.</div>";
        }
    } catch (PDOException $e) {
        $mensaje = "<div class='mensaje-error'>❌ Error: " . $e->getMessage() . "</div>";
    }
}
}

// 5. Cargar datos para la vista
$mis_reservas = $pdo->prepare("SELECT r.reserva_id, r.fecha_desde, r.fecha_hasta, c.nombre as cabana_nombre 
                               FROM reservas r JOIN cabañas c ON r.cabana_id = c.cabana_id 
                               WHERE r.afiliados_id = :af_id ORDER BY r.fecha_desde ASC");
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

       <div class="container"id="reservas" >

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

        <form method="POST">
            <fieldset>
                <legend>Nueva Reserva</legend>
                <div class="form-group">
                    <label>Seleccione Cabaña:</label>
                    <select name="cabana_id" id="selectCabana" required onchange="updateCards()">
                        <?php foreach ($cabanas as $c): ?>
                            <option value="<?php echo $c['cabana_id']; ?>">
                                <?php echo htmlspecialchars($c['nombre']); ?> (Capacidad: <?php echo $c['capacidad_maxima']; ?> pers.)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Fecha de Entrada:</label>
                    <input type="date" name="fecha_desde" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Fecha de Salida:</label>
                    <input type="date" name="fecha_hasta" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                </div>
                <button type="submit" class="btn btn-primary w-100 rounded-pill py-2">Verificar y Reservar</button>
            </fieldset>
        </form>

        <hr>

        <h3>📅 Tus Próximas Estadías</h3>
        <div class="reservas-list">
            <?php if (count($reservas) > 0): ?>
                <div class="grid-reservas">
                    <?php foreach ($reservas as $res): ?>
    <div class="reserva-item" style="flex-direction: column; align-items: flex-start;">
        <div style="display: flex; justify-content: space-between; width: 100%; border-bottom: 1px solid #eee; padding-bottom: 10px;">
            <div class="reserva-info">
                <strong><?php echo htmlspecialchars($res['cabana_nombre']); ?></strong>
                <span><?php echo date('d/m/Y', strtotime($res['fecha_desde'])); ?> al <?php echo date('d/m/Y', strtotime($res['fecha_hasta'])); ?></span>
            </div>
          
            <div class="reserva-acciones">
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="confirmar_y_voucher_id" value="<?php echo $res['reserva_id']; ?>">
                    <button type="submit" class="btn-confirmar-final">
                        ✅ Confirmar la Reserva
                    </button>
                </form>
                
                <button class="btn-invitados" onclick="toggleInvitados(<?php echo $res['reserva_id']; ?>)">👥 Invitados</button>
                
                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Cancelar reserva?');">
                    <input type="hidden" name="cancelar_id" value="<?php echo $res['reserva_id']; ?>">
                    <button type="submit" class="btn-cancelar">Eliminar</button>
                </form>
            </div>

        </div>

        <div id="seccion-inv-<?php echo $res['reserva_id']; ?>" class="seccion-invitados" style="display:none; width: 100%; padding-top: 15px;">

                        <div id="seccion-inv-<?php echo $res['reserva_id']; ?>" class="seccion-invitados" style="display:none; width: 100%; padding-top: 15px;">
    
    <?php
    // Obtenemos capacidad e invitados para mostrar el contador
    $stmt_info = $pdo->prepare("SELECT c.capacidad_maxima, 
                                (SELECT COUNT(*) FROM invitados WHERE reserva_id = r.reserva_id) as total_inv 
                                FROM reservas r 
                                JOIN cabañas c ON r.cabana_id = c.cabana_id 
                                WHERE r.reserva_id = ?");
    $stmt_info->execute([$res['reserva_id']]);
    $info = $stmt_info->fetch();
    $lugares_ocupados = $info['total_inv'] + 1; // +1 por el afiliado
    $lugares_libres = $info['capacidad_maxima'] - $lugares_ocupados;
    ?>

    <p style="font-size: 0.85rem; color: #555; margin-bottom: 10px;">
        📊 <strong>Ocupación:</strong> <?php echo "$lugares_ocupados / {$info['capacidad_maxima']}"; ?> lugares ocupados.
        (Quedan <?php echo $lugares_libres; ?> lugares para invitados).
    </p>

    <?php if ($lugares_libres > 0): ?>
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
    <?php else: ?>
        <div style="background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; font-size: 0.9rem;">
            🚫 Capacidad máxima alcanzada.
        </div>
    <?php endif; ?>

    <ul class="lista-invitados-cargados">
        <?php
        $stmt_inv = $pdo->prepare("SELECT * FROM invitados WHERE reserva_id = ?");
        $stmt_inv->execute([$res['reserva_id']]);
        while($inv = $stmt_inv->fetch()): ?>
            <li style="display: flex; justify-content: space-between;">
                <span>• <?php echo htmlspecialchars($inv['nombre_completo']); ?> (<?php echo $inv['tipo']; ?>)</span>
                </li>
        <?php endwhile; ?>
    </ul>
</div>





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
                while($inv = $stmt_inv->fetch()) {
                    echo "<li>• " . htmlspecialchars($inv['nombre_completo']) . " (" . $inv['tipo'] . ")</li>";
                }
                ?>
            </ul>
        </div>
    </div>
<?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #666; font-style: italic;">Aún no tienes reservas registradas.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function updateCards() {
        const select = document.getElementById('selectCabana');
        const val = select.value;
        document.querySelectorAll('.cabin-card').forEach(card => card.classList.remove('active'));
        const activeCard = document.getElementById('card-' + val);
        if (activeCard) activeCard.classList.add('active');
    }
    function selectCabana(id) {
        document.getElementById('selectCabana').value = id;
        updateCards();
    }
    window.onload = updateCards;

// scrip para agrandar las fotos de las cabañas
    function openModal(src) {
    const modal = document.getElementById('imageModal');
    const fullImg = document.getElementById('fullImg');
    modal.style.display = "flex";
    fullImg.src = src;
}

function closeModal() {
    document.getElementById('imageModal').style.display = "none";
}

//SCRIP PARA AGREGAR INVITADOS
function toggleInvitados(id) {
    const seccion = document.getElementById('seccion-inv-' + id);
    seccion.style.display = (seccion.style.display === 'none') ? 'block' : 'none';
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




    <?php require_once "pie.php"; ?>

<!-- un modal para agrandar las imagenes de las cabañas -->

    <div id="imageModal" class="modal" onclick="closeModal()">
    <span class="close-modal">&times;</span>
    <img class="modal-content" id="fullImg">
</div>
</body>
</html>