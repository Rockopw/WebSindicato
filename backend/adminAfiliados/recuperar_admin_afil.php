<?php
// 1. Siempre los "use" al principio del archivo
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 2. Cargar la librería
require '../../PHPMailer/src/Exception.php';
require '../../PHPMailer/src/PHPMailer.php';
require '../../PHPMailer/src/SMTP.php';

// 3. Conexión a la DB
$host = 'localhost';        
$db   = 'sindicato';  
$user = 'rudagaleano';       
$pass = 'Napst3rfarr3l';    
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { 
    die("Error de conexión: " . $e->getMessage()); 
}

$mensaje = "";

// 4. Procesar el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dni = trim($_POST['dni']);
    $email = trim($_POST['email']);

    // IMPORTANTE: Asegúrate que los nombres de tablas y columnas coincidan con tu DB
    // Usaremos 'admin_deporte' y 'id_administrador' como pusiste en tu primer bloque
    $stmt = $pdo->prepare("SELECT id_administrador FROM administrador WHERE dni = :dni AND email = :email");
    $stmt->execute(['dni' => $dni, 'email' => $email]);
    $afiliado = $stmt->fetch();

    if ($afiliado) {
        // Generar Token
        $token = bin2hex(random_bytes(32));
        $expiracion = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // Guardar token
        $update = $pdo->prepare("UPDATE administrador SET token_recuperacion = :token, token_expiracion = :exp WHERE id_administrador = :id");
        $update->execute(['token' => $token, 'exp' => $expiracion, 'id' => $afiliado['id_administrador']]);

        // URL real (Cambia 'tu-web.com' por 'localhost/nombre_tu_carpeta' si estás en local)
        $url_recuperacion = "http://localhost/sindicato/backend/adminAfiliados/resetear_admin_afil.php?token=" . $token;

        // --- ENVIAR CORREO CON PHPMAILER ---
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; 
            $mail->SMTPAuth   = true;
            $mail->Username   = 'rubengaleano83@gmail.com'; 
            $mail->Password   = 'maoozkskidrbuasz'; // <-- CAMBIA ESTO
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom('rubengaleano83@gmail.com', 'Sindicato Reservas');
            $mail->addAddress($email); 

            $mail->isHTML(true);
            $mail->Subject = 'Recuperación de Contraseña';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif;'>
                    <h3>Hola, has solicitado restablecer tu contraseña</h3>
                    <p>Haz clic en el siguiente botón para continuar. Tienes 1 hora antes de que expire:</p>
                    <a href='$url_recuperacion' style='background:#007bff; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Restablecer mi contraseña</a>
                    <p>Si no solicitaste esto, ignora este correo.</p>
                </div>";

            $mail->send();
            $mensaje = "<div style='color:green; font-weight:bold; margin-bottom:15px;'>✅ Se ha enviado el enlace a tu correo. Revisa tu bandeja de entrada.</div>";
        } catch (Exception $e) {
            $mensaje = "<div style='color:red;'>❌ Error al enviar el correo: {$mail->ErrorInfo}</div>";
        }

    } else {
        $mensaje = "<div style='color:red; font-weight:bold; margin-bottom:15px;'>❌ Los datos no coinciden con nuestros registros.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña</title>
    <link rel="stylesheet" href="../../estilos_afiliados.css">
     <link rel="icon" href="../super admin/imagenes/llave.ico">
</head>
<body>
    <div class="container" style="max-width: 450px; margin-top: 50px;">
        <h1>Recuperar Acceso</h1>
        <?php echo $mensaje; ?>
        
        <form method="POST">
            <p>Ingresa tu DNI y el correo electrónico registrado.</p>
            <div class="form-group">
                <label>DNI:</label>
                <input type="text" name="dni" required>
            </div>
            <div class="form-group">
                <label>Email registrado:</label>
                <input type="email" name="email" required>
            </div>
            <button type="submit" class="btn-primary">Enviar enlace</button>
            <a href="login_admin_afil.php" style="display:block; text-align:center; margin-top:15px; color:#666;">Volver al Login</a>
        </form>
    </div>
</body>
</html>