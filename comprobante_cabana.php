<?php
session_start();

// 1. CLAVE SECRETA (Idéntica a la de reserva cabaña.php)
$clave_secreta = "Patoneta-2026";

$id_reserva = $_GET['id'] ?? null;
$hash_recibido = $_GET['hash'] ?? null;

// Inicializamos variables para evitar errores de "Undefined"
$reserva = null;
$invitados = [];
$nombre_usuario_mostrar = "";

// 2. VALIDACIÓN DE ACCESO (Sesión o Hash de seguridad)
$acceso_valido = false;

if (isset($_SESSION['afiliado_id']) && $id_reserva) {
    $acceso_valido = true;
} elseif ($id_reserva && $hash_recibido) {
    $hash_esperado = hash_hmac('sha256', $id_reserva, $clave_secreta);
    if (hash_equals($hash_esperado, $hash_recibido)) {
        $acceso_valido = true;
    }
}

if (!$acceso_valido) {
    die("Acceso no autorizado o enlace vencido.");
}

// 3. CONEXIÓN Y BÚSQUEDA DE DATOS
$host = 'localhost'; $db = 'sindicato'; $user = 'rudagaleano'; $pass = 'Napst3rfarr3l';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta para traer los datos de la reserva, la cabaña y el nombre del afiliado
    $sql = "SELECT r.*, c.nombre as cabana_nombre, a.nombre as nombre_afiliado_db 
            FROM reservas r 
            JOIN cabañas c ON r.cabana_id = c.cabana_id 
            JOIN afiliados a ON r.afiliados_id = a.afiliados_id
            WHERE r.reserva_id = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_reserva]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reserva) { 
        die("La reserva no existe."); 
    }

    $nombre_usuario_mostrar = $reserva['nombre_afiliado_db'];

    // 4. BUSCAR INVITADOS
    $stmt_inv = $pdo->prepare("SELECT * FROM invitados WHERE reserva_id = ?");
    $stmt_inv->execute([$id_reserva]);
    $invitados = $stmt_inv->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante Cabaña #<?php echo $id_reserva; ?></title>
    <link rel="icon" type="image/png" href="voucher.ico">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #ececec; padding: 20px; color: #333; }
        .voucher-card { background: white; max-width: 650px; margin: 0 auto; padding: 40px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-top: 8px solid #D2B48C; }
        .header { text-align: center; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
        .logo { font-weight: bold; color: #5d4037; font-size: 24px; margin: 0; }
        .section { margin: 25px 0; }
        .section h4 { color: #5d4037; margin-bottom: 8px; font-size: 16px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #f0f0f0; }
        .section p { margin: 5px 0; font-size: 15px; }
        .invitados-list { background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #eee; list-style: none; padding-left: 0; }
        .invitados-list li { padding: 5px 0; border-bottom: 1px solid #eee; font-size: 14px; }
        .invitados-list li:last-child { border-bottom: none; }
        .btn-print { display: block; width: 20%; padding: 10px; background: linear-gradient(135deg,  #b37b6a 0%,  #4a332c 100%); color: white; text-align: center; text-decoration: none; border-radius: 10px; margin-top: 20px;margin: 20px auto ; font-weight: bold; }
       
       .btn-print:hover {
    background: linear-gradient(135deg, #495057 0%, #343a40 100%);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}
        @media print { .btn-print { display: none; } }

        
 /* boton volver del boucher*/

.btn-volver {
    display: inline-block;
    width: 40%;
    padding: 12px 20px;
    background: linear-gradient(135deg,  #218838 0%,  #124a1e 100%);
    color: white !important;
    text-align: center;
    text-decoration: none;
    border-radius: 50px; /* Bordes bien redondeados */
    margin: 20px auto;
    font-weight: bold;
    font-size: 14px;
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-volver:hover {
    background: linear-gradient(135deg, #495057 0%, #343a40 100%);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

/* Para que no salga en la impresión */
@media print {
    .btn-volver, .btn-print { display: none !important; }
}
    </style>
</head>
<body>

<div class="voucher-card">
    <div class="header">
        <p class="logo">VOUCHER DE ESTADÍA</p>
        <p style="margin: 5px 0; color: #666;">Sindicato de Obras Sanitarias Misiones</p>
    </div>

    <div class="section">
        <h4>🏡 Detalles del Alojamiento</h4>
        <p><strong>Cabaña:</strong> <?php echo htmlspecialchars($reserva['cabana_nombre']); ?></p>
        <p><strong>Ubicación:</strong> Predio Ituzaingó, Corrientes.</p>
    </div>

    <div class="section">
        <h4>📅 Período de Reserva</h4>
        <p><strong>Check-in (Entrada):</strong> <?php echo date('d/m/Y', strtotime($reserva['fecha_desde'])); ?></p>
        <p><strong>Check-out (Salida):</strong> <?php echo date('d/m/Y', strtotime($reserva['fecha_hasta'])); ?></p>
    </div>

    <div class="section">
        <h4>👤 Titular</h4>
        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($nombre_usuario_mostrar); ?></p>
        <p><strong>Estado:</strong> <span style="color:green; font-weight:bold;">CONFIRMADO</span></p>
    </div>

   <div class="section">
        <h4>👥 Lista de Invitados</h4>
        <?php if (!empty($invitados)): ?>
            <ul class="invitados-list">
                <?php foreach ($invitados as $inv): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($inv['nombre_completo']); ?></strong> 
                        (DNI: <?php echo $inv['dni']; ?>) - 
                        <span style="color: #666;"><?php echo ucfirst($inv['tipo']); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p style="color: #999; font-style: italic;">No hay invitados adicionales registrados.</p>
        <?php endif; ?>
    </div>

      <div style="margin-top: 40px; text-align: center;">
    <div style="display: inline-block; width: 250px;">
        <img src="backend/deporte/imagenes/firma_secretario.png" alt="Firma Secretario" style="width: 150px; height: auto; margin-bottom: -10px;">
        
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

    <div style="text-align: center;">
        <a href="reserva_cabaña.php" class="btn-volver">
            <i class="fas fa-arrow-left"></i> ⬅️ VOLVER A MIS RESERVAS
        </a>
    </div>
</div> <a href="#" class="btn-print" onclick="window.print()">🖨️ Imprimir o Guardar PDF</a>

</body>
</html>