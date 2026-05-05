<?php
 require_once "configpdo.php";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

// --- CONSULTA SQL CON DESGLOSE POR GÉNERO ---
$sql = "SELECT 
            a.apellido, a.nombre, a.dni, a.empresa,
            -- KIT 1: Pre Escolar (3-5)
            SUM(CASE WHEN f.parentesco = 'Hijo/a' AND TIMESTAMPDIFF(YEAR, f.fecha_nacimiento, CURDATE()) BETWEEN 3 AND 5 THEN 1 ELSE 0 END) as k1_t,
            SUM(CASE WHEN f.parentesco = 'Hijo/a' AND f.sexo = 'F' AND TIMESTAMPDIFF(YEAR, f.fecha_nacimiento, CURDATE()) BETWEEN 3 AND 5 THEN 1 ELSE 0 END) as k1_f,
            SUM(CASE WHEN f.parentesco = 'Hijo/a' AND f.sexo = 'M' AND TIMESTAMPDIFF(YEAR, f.fecha_nacimiento, CURDATE()) BETWEEN 3 AND 5 THEN 1 ELSE 0 END) as k1_m,
            -- KIT 2: Primario 1 (6-8)
            SUM(CASE WHEN f.parentesco = 'Hijo/a' AND TIMESTAMPDIFF(YEAR, f.fecha_nacimiento, CURDATE()) BETWEEN 6 AND 8 THEN 1 ELSE 0 END) as k2_t,
            SUM(CASE WHEN f.parentesco = 'Hijo/a' AND f.sexo = 'F' AND TIMESTAMPDIFF(YEAR, f.fecha_nacimiento, CURDATE()) BETWEEN 6 AND 8 THEN 1 ELSE 0 END) as k2_f,
            SUM(CASE WHEN f.parentesco = 'Hijo/a' AND f.sexo = 'M' AND TIMESTAMPDIFF(YEAR, f.fecha_nacimiento, CURDATE()) BETWEEN 6 AND 8 THEN 1 ELSE 0 END) as k2_m,
            -- KIT 3: Primario 2 (9-12)
            SUM(CASE WHEN f.parentesco = 'Hijo/a' AND TIMESTAMPDIFF(YEAR, f.fecha_nacimiento, CURDATE()) BETWEEN 9 AND 12 THEN 1 ELSE 0 END) as k3_t,
            SUM(CASE WHEN f.parentesco = 'Hijo/a' AND f.sexo = 'F' AND TIMESTAMPDIFF(YEAR, f.fecha_nacimiento, CURDATE()) BETWEEN 9 AND 12 THEN 1 ELSE 0 END) as k3_f,
            SUM(CASE WHEN f.parentesco = 'Hijo/a' AND f.sexo = 'M' AND TIMESTAMPDIFF(YEAR, f.fecha_nacimiento, CURDATE()) BETWEEN 9 AND 12 THEN 1 ELSE 0 END) as k3_m,
            -- KIT 4: Universitario (13-18) -> Según tu instrucción anterior lo llamamos Universitario
            SUM(CASE WHEN f.parentesco = 'Hijo/a' AND TIMESTAMPDIFF(YEAR, f.fecha_nacimiento, CURDATE()) BETWEEN 13 AND 18 THEN 1 ELSE 0 END) as k4_t,
            SUM(CASE WHEN f.parentesco = 'Hijo/a' AND f.sexo = 'F' AND TIMESTAMPDIFF(YEAR, f.fecha_nacimiento, CURDATE()) BETWEEN 13 AND 18 THEN 1 ELSE 0 END) as k4_f,
            SUM(CASE WHEN f.parentesco = 'Hijo/a' AND f.sexo = 'M' AND TIMESTAMPDIFF(YEAR, f.fecha_nacimiento, CURDATE()) BETWEEN 13 AND 18 THEN 1 ELSE 0 END) as k4_m,
            
            COUNT(CASE WHEN f.parentesco = 'Hijo/a' THEN 1 END) as total_hijos
        FROM afiliados a
        LEFT JOIN familia f ON a.afiliados_id = f.afiliado_id
        GROUP BY a.afiliados_id
        HAVING total_hijos > 0
        ORDER BY a.apellido ASC";

$stmt = $pdo->query($sql);
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Escolaridad</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
      <link rel="icon" href="../imagenes/victoria.ico">
    <style>
        .bg-pastel-azul { background-color: #d0ebff !important; color: #084298; }
        .bg-pastel-amarillo { background-color: #fff3cd !important; color: #664d03; }
        .bg-pastel-verde { background-color: #d1e7dd !important; color: #0f5132; }
        .bg-pastel-lila { background-color: #e2d9f3 !important; color: #4a3f75; }
        .card { border-radius: 15px; border: none; }
        .gender-badge { font-size: 0.75rem; padding: 2px 8px; border-radius: 10px; margin: 0 2px; }
        .badge-f { background-color: #f8d7da; color: #842029; }
        .badge-m { background-color: #cfe2ff; color: #084298; }
        /* Tu clase personalizada para el botón */
        .btn-volver { 
            text-decoration: none; 
            background-color: #3e9cee; 
            color: white; 
            padding: 8px 16px; 
            border-radius: 5px; 
            transition: 0.3s;
        }
        .btn-volver:hover { background-color: #115385; color: white; }
     #repo thead th {
    text-align: center !important;
    vertical-align: middle;
}
   
    </style>
</head>
<body class="bg-light">

<div class="container-fluid py-5 px-4">
    
    <div class="row mb-4">
        <?php 
        $conf = [
            ['k1', 'kit 1 (Pre Escolar)', 'bg-pastel-azul'],
            ['k2', 'kit 2 (Primario 1)', 'bg-pastel-amarillo'],
            ['k3', 'kit 3 (Primario 2)', 'bg-pastel-verde'],
            ['k4', 'kit 4 (Universitario)', 'bg-pastel-lila']
        ];
        foreach($conf as $c): ?>
        <div class="col-md-3">
            <div class="card <?= $c[2] ?> shadow-sm">
                <div class="card-body text-center">
                    <h6 class="fw-bold"><?= $c[1] ?></h6>
                    <h2 class="display-6 fw-bold mb-1" id="card-<?= $c[0] ?>-t">0</h2>
                    <div>
                        <span class="gender-badge badge-f">Nenas: <b id="card-<?= $c[0] ?>-f">0</b></span>
                        <span class="gender-badge badge-m">Varones: <b id="card-<?= $c[0] ?>-m">0</b></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="card shadow p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="text-primary m-0 ">Detalle de Escolaridad por Género</h3>
            <a href="amb_afiliados.php" class="btn-volver">Volver</a>
        </div>
        
        
        <table id="repo" class="table table-bordered align-middle">
            <thead class="table-dark text-center">
                <tr>
                    <th>Afiliado / Empresa</th>
                    <th>Kit 1 (de 3 a 5 años)</th>
                    <th>Kit 2 ( 6 a 8 años)</th>
                    <th>Kit 3 (de 9 a 12 años)</th>
                    <th>Kit 4 (13 a 18 años)</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody class="text-center">
                <?php foreach ($filas as $f): ?>
                <tr>
                    <td class="text-start">
                        <b><?= $f['apellido'].", ".$f['nombre'] ?></b><br>
                        <small class="text-muted"><?= $f['empresa'] ?></small>
                    </td>
                    <?php for($i=1; $i<=4; $i++): 
                        $t = $f["k{$i}_t"]; $f_gen = $f["k{$i}_f"]; $m_gen = $f["k{$i}_m"];
                        $class = ['table-info','table-warning','table-success','table-primary'][$i-1];
                    ?>
                    <td class="<?= $class ?>" data-t="<?= $t ?>" data-f="<?= $f_gen ?>" data-m="<?= $m_gen ?>">
                        <span class="fw-bold"><?= $t ?></span><br>
                        <small>👧🏻<?= $f_gen ?> 👦🏻​<?= $m_gen ?></small>
                    </td>
                    <?php endfor; ?>
                    <td class="fw-bold"><?= $f['total_hijos'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>


<script>
$(document).ready(function() {
    $('#repo').DataTable({
        language: {
            search: "Buscar:",
            lengthMenu: "Mostrar _MENU_ registros",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 a 0 de 0 registros",
            infoFiltered: "(filtrado de _MAX_ registros totales)",
            zeroRecords: "No se encontraron resultados",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            }
        },
        dom: 'Bfrtip',
        buttons: [{
            extend: 'excelHtml5',
            text: 'Descargar Reporte Excel',
            className: 'btn btn-success'
        }],
        drawCallback: function(settings) {
            var api = this.api();
            
            for(let i=1; i<=4; i++) {
                let colT = 0, colF = 0, colM = 0;
                
                api.column(i, { search: 'applied' }).nodes().to$().each(function() {
                    colT += parseInt($(this).data('t')) || 0;
                    colF += parseInt($(this).data('f')) || 0;
                    colM += parseInt($(this).data('m')) || 0;
                });

                $(`#card-k${i}-t`).text(colT);
                $(`#card-k${i}-f`).text(colF);
                $(`#card-k${i}-m`).text(colM);
            }
        }
    });
});

</script>
</body>
<footer class="footer text-center py-3 mt-5 bg-white border-top">
    <p class="small text-muted mb-0">&copy; 2025 Sindicato de Obras Sanitarias Misiones. Desarrollado por Ruben D. Galeano Consultor IT.</p>
</footer>
</html>