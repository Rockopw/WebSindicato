<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Datos de conexión (usando los mismos que pusiste en tu AMB)
$host = 'localhost';
$db   = 'sindicato';
$user = 'rudagaleano'; 
$pass_db = 'Napst3rfarr3l'; 

$conexion = mysqli_connect($host, $user, $pass_db, $db);

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

if ($_POST) {
    $dni = $_POST['dni'];
    $pass_usuario = $_POST['password'];

    // Buscamos en la tabla administrador
    // IMPORTANTE: Verifica si en tu DB es "contraseña" o "contrasena"
    $query = "SELECT * FROM super_admin WHERE dni = '$dni' AND contrasena = '$pass_usuario'";
    $resultado = mysqli_query($conexion, $query);

    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $datos = mysqli_fetch_array($resultado);
        
        // --- AQUÍ ESTÁ LA CLAVE: Usar los nombres que pide tu amb_afiliados.php ---
      $_SESSION['id_admin'] = $datos['dni']; 
    $_SESSION['super_admin_nombre'] = $datos['nombre'] . " " . $datos['apellido'];
    
    header("Location: gestion_admins.php");
    exit();
    } else {
        // Si falla, volvemos al login con un error
        
        header("Location: login_2.php");
        
        exit();
    }
}
?>