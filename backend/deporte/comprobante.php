<?php
// 1. Configuración de errores y sesión
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// 2. Blindaje de Sesión
if (!isset($_SESSION['afiliado_id'])) {
    header("Location: ../../login.php");
    exit();
}

$host = 'localhost'; $db = 'sindicato'; $user = 'rudagaleano'; $pass = 'Napst3rfarr3l';
$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);

// 1. Obtener datos de la reserva y validar que sea del usuario logueado
$stmt = $pdo->prepare("SELECT r.*, c.nombre as cabana_nombre, c.capacidad_maxima 
                       FROM reservas r 
                       JOIN cabañas c ON r.cabana_id = c.cabana_id 
                       WHERE r.reserva_id = ? AND r.afiliados_id = ?");
$stmt->execute([$_GET['id'], $_SESSION['afiliado_id']]);
$reserva = $stmt->fetch();

if (!$reserva) { die("Reserva no encontrada"); }

// 2. Obtener invitados
$stmt_inv = $pdo->prepare("SELECT * FROM invitados WHERE reserva_id = ?");
$stmt_inv->execute([$reserva['reserva_id']]);
$invitados = $stmt_inv->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmacion de tu Reserva #<?php echo $reserva['reserva_id']; ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .voucher-card { 
            background: white; max-width: 600px; margin: 0 auto; 
            padding: 30px; border: 2px solid #ddd; border-radius: 10px;
        }
        .header { text-align: center; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .section { margin: 20px 0; }
        .section h4 { color: #007bff; margin-bottom: 5px; border-bottom: 1px solid #eee; }
        .grid { display: flex; justify-content: space-between; }
        .footer { text-align: center; font-size: 0.8rem; color: #777; margin-top: 30px; }
        .btn-print { 
            display: block; width: 100%; padding: 10px; background: #28a745; 
            color: white; text-align: center; text-decoration: none; border-radius: 5px; margin-top: 20px;
        }
        @media print { .btn-print { display: none; } } /* Ocultar botón al imprimir */
          /* ... tus estilos anteriores ... */

   

  .container-botones {
    display: flex;
    align-items: stretch;
    justify-content: center;
    gap: 25px;
}

.btn-volver,
.btn-print {
    display: inline-flex;
    align-items: center;
    justify-content: center;
border-radius: 8px !important;
    padding: 10px 22px;
    line-height: 1;
    margin: 0 !important;
    
}


.btn-volver {
    background-color: #0b3c6f;
    white-space: nowrap;
   text-decoration: none !important;
color: #fff !important;

    
}

.btn-print {
    background-color: #2fa843;
}

.btn-volver:hover { background: #0d8dee; }
.btn-print:hover { background: #218838; }

@media print { 
    .btn-print, .btn-volver, .container-botones { 
        display: none !important; 
    } 
}



        
    </style>
</head>
<body>

<div class="voucher-card">
    <div class="header">
        <h2>CONFIRMACION DE TU RESERVA</h2>
        <p>Sindicato de Obras Sanitarias Misiones</p>
    </div>

    <div class="section">
        <h4>📍 Información del Alojamiento</h4>
        <p><strong>Cabaña:</strong> <?php echo htmlspecialchars($reserva['cabana_nombre']); ?></p>
        <p><strong>Ubicación:</strong> Ituzaingó, Corrientes.</p>
    </div>

    <div class="section grid">
        <div>
            <h4>📅 Entrada</h4>
            <p><?php echo date('d/m/Y', strtotime($reserva['fecha_desde'])); ?></p>
        </div>
        <div>
            <h4>📅 Salida</h4>
            <p><?php echo date('d/m/Y', strtotime($reserva['fecha_hasta'])); ?></p>
        </div>
    </div>

    <div class="section">
        <h4>👤 Titular</h4>
        <p><?php echo htmlspecialchars($_SESSION['afiliado_nombre']); ?></p>
    </div>

    <div class="section">
        <h4>👥 Invitados Registrados</h4>
        <?php if (count($invitados) > 0): ?>
            <ul>
                <?php foreach ($invitados as $inv): ?>
                    <li><?php echo htmlspecialchars($inv['nombre_completo']); ?> - DNI: <?php echo $inv['dni']; ?> (<?php echo $inv['tipo']; ?>)</li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Sin invitados registrados.</p>
        <?php endif; ?>
    </div>

       <div class="container-botones">
    <a href="../../espacio de reservas.php" class="btn-volver">
        ← Volver a
    </a>

    <a href="#" class="btn-print" onclick="window.print()">
        🖨 Imprimir o Guardar PDF
    </a>
</div>


   

    <div style="margin-top: 40px; text-align: center;">
    <div style="display: inline-block; width: 250px;">
        <img src="imagenes/firma_secretario.png" alt="Firma Secretario" style="width: 150px; height: auto; margin-bottom: -10px;">
        
        <div style="border-top: 1px solid #000; padding-top: 5px;">
            <strong style="display: block; font-size: 14px; text-transform: uppercase;">Orlando Richard Garay</strong>
            <span style="font-size: 12px; color: #555;">Secretario de Deportes</span>
            <br>
            <span style="font-size: 11px; color: #777;">Sindicato de Obras Sanitarias Misiones</span>
        </div>
    </div>
</div>

<p style="text-align: center; font-size: 12px; color: #444; margin-top: 30px;">
    Este comprobante es personal e intransferible.<br>
    Recuerde presentar DNI de todos los ocupantes al ingresar.
</p>
</div>

</body>
</html>