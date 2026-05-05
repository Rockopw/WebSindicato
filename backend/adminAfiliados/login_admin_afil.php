<?php
    require_once "../../encabezado.php"; // hace un requerimiento a archivos para poder usar sus funciones, y si no funciona se queda ahi, no sigue ejecutando. ej base de datos//
?>

<?php
// Iniciar sesión en la parte superior de cada página que use sesiones
session_start();

// Si el usuario ya está logueado, redirigir a la página de reservas (o principal)
if (isset($_SESSION['id_administrador'])) {
    header("Location:amb_afiliados.php");
    exit();
}

// --- Configuración y Conexión a la Base de Datos (Igual que antes) ---
$host = 'localhost';        
$db   = 'sindicato';  
$user = 'rudagaleano';       
$pass = 'Napst3rfarr3l';    
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Error de Conexión: " . $e->getMessage());
}

$error_message = ''; // Variable para mensajes de error

// --- Lógica de Procesamiento de Login ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $dni = trim($_POST['dni']);
    $contrasena_ingresada = $_POST['contrasena']; // Ya no es el número de afiliado
    
   if (empty($dni) || empty($contrasena_ingresada)) {
        $error_message = "Ambos campos son obligatorios.";
   } else {
        // 1. Consultar la DB solo por DNI y obtener el hash
        $sql = "SELECT id_administrador, nombre, apellido, contrasena FROM administrador 
                WHERE dni = :dni";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['dni' => $dni]);
        $admin = $stmt->fetch(); 

        if ($admin) {
            // 2. Usar password_verify() para comparar la contraseña ingresada con el hash guardado
            if (password_verify($contrasena_ingresada, $admin['contrasena'])) {
                // Login exitoso
                $_SESSION['id_administrador'] = $admin['id_administrador'];
                $_SESSION['administrador_nombre'] = $admin['nombre'] . " " . $admin['apellido'];
                
                header("Location:amb_afiliados.php");
                exit();
            } else {
                // Contraseña incorrecta
                $error_message = "DNI o Contraseña incorrectos. Intente de nuevo.";
            }
        } else {
             // DNI no encontrado
            $error_message = "DNI o Contraseña incorrectos. Intente de nuevo.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso de Afiliados</title>
    <link rel="stylesheet" href="estilo_loginAdmin.css">
    <link rel="icon" href="../imagenes/victoria.ico">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
   
</head>
<body>

    <div class="container login-container">
        <h1>Acceso Administrador de Afiliados</h1>
        <p>Ingrese sus credenciales para acceder al Sistema</p>
        
        <?php if ($error_message): ?>
            <p class="mensaje error-msg"><?php echo $error_message; ?></p>
        <?php endif; ?>
        
        <form method="POST" action="login_admin_afil.php">

            <div class="form-group">
                <label for="dni">DNI:</label>
                <input type="text" id="dni" name="dni" required autofocus>
            </div>
           

<!-- aca le puse el ojito para ayudar a ver la pasw -->

                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>

            <div class="input-group">
                 <input 
                    type="password" 
                    class="form-control" 
                    id="contrasena" 
                    name="contrasena"
                    required
    >

                <span class="input-group-text" style="cursor: pointer;" onclick="togglePassword()">
                <i class="bi bi-eye" id="toggleIcon"></i>
                </span>
            </div>

            <script>
                function togglePassword() {
                const passwordInput = document.getElementById("contrasena");
                const icon = document.getElementById("toggleIcon​");

                if (passwordInput.type === "password") {
                    passwordInput.type = "text";
                    icon.classList.remove("bi-eye");
                    icon.classList.add("bi-eye-slash");
                } else {
                    passwordInput.type = "password";
                    icon.classList.remove("bi-eye-slash");
                    icon.classList.add("bi-eye");
                }
                }
        </script>

            </div>

            <button type="submit" class="btn-primary">Acceder</button>

            <div style="text-align: center; margin-top: 15px;">
            <a href="recuperar_admin_afil.php" style="color: #007bff; text-decoration: none; font-size: 0.9em;">
                ¿Olvidó su contraseña? Haga clic aquí
            </a>
            </div>

        </form>
    </div>

</body>
</html>
