<?php
// 1. Conexión a la DB
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
$mostrar_formulario = false;

// 2. Verificar si el token viene en la URL (?token=...)
$token = $_GET['token'] ?? ($_POST['token'] ?? '');

if (empty($token)) {
    $mensaje = "<div style='color:red;'>❌ Enlace inválido. No se encontró un token de acceso.</div>";
} else {
    // 3. Validar el token en la base de datos y que no haya expirado
    $stmt = $pdo->prepare("SELECT afiliados_id FROM afiliados WHERE token_recuperacion = :token AND token_expiracion > NOW()");
    $stmt->execute(['token' => $token]);
    $afiliado = $stmt->fetch();

    if ($afiliado) {
        $mostrar_formulario = true;
    } else {
        $mensaje = "<div style='color:red;'>❌ El enlace es inválido o ya expiró (tienen 1 hora de validez). Por favor, solicita uno nuevo.</div>";
    }
}

// 4. Procesar el cambio de contraseña
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nueva_clave'])) {
    $nueva_clave = $_POST['nueva_clave'];
    $confirmar_clave = $_POST['confirmar_clave'];

    if ($nueva_clave !== $confirmar_clave) {
        $mensaje = "<div style='color:red;'>❌ Las contraseñas no coinciden.</div>";
    } elseif (strlen($nueva_clave) < 6) {
        $mensaje = "<div style='color:red;'>❌ La contraseña debe tener al menos 6 caracteres.</div>";
    } else {
        // Cifrar la nueva contraseña
        $password_hash = password_hash($nueva_clave, PASSWORD_DEFAULT);

        // Actualizar la clave y LIMPIAR los campos del token (para que no se use dos veces)
        $update = $pdo->prepare("UPDATE afiliados SET contrasena = :pass, token_recuperacion = NULL, token_expiracion = NULL WHERE afiliados_id = :id");
        
        if ($update->execute(['pass' => $password_hash, 'id' => $afiliado['afiliados_id']])) {
            $mensaje = "<div style='color:green; font-weight:bold;'>✅ ¡Contraseña actualizada! Ya puedes iniciar sesión.</div>";
            $mostrar_formulario = false; // Ocultar el formulario tras el éxito
        } else {
            $mensaje = "<div style='color:red;'>❌ Error al actualizar la base de datos.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Contraseña</title>
    <link rel="stylesheet" href="estilos_afiliados.css">
</head>
<body>
    <div class="container" style="max-width: 450px; margin-top: 50px;">
        <h1>Nueva Contraseña</h1>
        <?php echo $mensaje; ?>

        <?php if ($mostrar_formulario): ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <p>Ingresa tu nueva contraseña para acceder al sistema.</p>
                
                <div class="form-group">
                    <label>Nueva Contraseña:</label>
                    <input type="password" name="nueva_clave" required minlength="6">
                </div>

                <div class="form-group">
                    <label>Confirmar Contraseña:</label>
                    <input type="password" name="confirmar_clave" required minlength="6">
                </div>

                <button type="submit" class="btn-primary">Guardar Contraseña</button>
            </form>
        <?php endif; ?>

        <div style="text-align:center; margin-top:20px;">
            <a href="login.php" style="color:#666; text-decoration:none;">Ir al Login</a>
        </div>
    </div>
</body>
</html>