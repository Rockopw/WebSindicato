<?php
include 'conexion.php'; // Asegúrate de tener tu conexión en un archivo aparte o incluirla aquí
$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM familia WHERE afiliado_id = ?");
$stmt->execute([$id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));