


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novedades - Sindicato de Obras Sanitarias Misiones</title>
     <link rel="stylesheet" href="style.css">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Lora:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">

      <link rel="icon" type="image/png" href="logo sindi.ico">
    <!-- Enlace a Google Fonts para una tipografía moderna -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <!-- Enlace a un icono de ejemplo para la pestaña del navegador (favicon) -->
    <link rel="icon" type="image/png" href="https://sindicatodelpersonaldeobrassanit.com/wp-content/uploads/2021/08/cropped-logo-sindicato-32x32.png">
    

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

</head>

<body>
<style>

/*menu hamburguesa*/
/* Ajustes generales del Header */


.header-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
    margin-left:auto;
 

    
}

.main-nav{
    margin-left:70px;
}

/* Estilo del Botón Hamburguesa */
/* --- CORRECCIÓN DE COLORES Y MENÚ MÓVIL --- */
:root {
    /* Cambiamos el verde por un celeste/azul brillante */
    --accent-color: #00d4ff; 
}

@media (max-width: 768px) {
    .menu-toggle {
        display: block !important;
        z-index: 1100;
        margin-left:50px;
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
/*esto es del menu hamburgesa*/
    .main-nav ul li {
        margin: 20px 0;
        text-align: left;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
}

</style>








<header class="top-bar">
                    <div class="header-container">
                        <div class="d-flex align-items-center">
                            <img src="logo sindi.jpg" class="rounded-circle me-3 shadow-white logo-img" width="60" height="55">
                            <h3 class="welcome-text mb-0 text-white">Bienvenidos al Espacio de Reservas</h3>
                            
                          
                        </div>

                                <button class="menu-toggle" id="menuToggle" aria-label="Abrir menú">
                                <i class="fa-solid fa-bars"></i>
                            </button>

                        <nav class="main-nav" id="mainNav">
                            <ul>
                                <li><a href="menu.php"><i class="fas fa-home"></i> Inicio</a></li>
                                <li><a href="reserva_cabaña.php">Cabañas</a></li>
                                <li><a href="departamentos/reserva_departamentos.php">Alojamiento</a></li>
                                <li><a href="quincho/reserva_quincho.php">Quincho</a></li>
                                <li><a href="#contacto">Contacto</a></li>
                        
                            </ul>
                        </nav>
                    </div>
</header>


        <hr class="my-0">



<div id="carouselExampleCaptions" class="carousel slide" data-ride="carousel">

  <div class="carousel-inner">
        <div class="carousel-item active">
              <img src="imagenes/cabaña itu/5/cabaña.png" class="d-block w-100" alt="cabaña linda">
            <div class="carousel-caption">
                  <h1>¡Aca vas a poder reservar!</h1>
                  <p>Cabañas en ituzaingo corrientes</p>
                  <a href="reserva_cabaña.php" class="btn-primary">¡Reserva Ahora! <i class="fas fa-leaf"></i></a>
            </div>
        </div>

        <div class="carousel-item">
            <img src="departamentos\fotos\dpto lavalle.png" class="d-block w-100" alt="imagen de mentira">
            <div class="carousel-caption">
                  <h1>¡Aca vas a poder reservar un Departamento!</h1>
                  <p>Por Lavalle, en Posadas Misiones.</p>
                  <a href="departamentos/reserva_departamentos.php" class="btn-primary">¡Reserva Ahora! <i class="fas fa-leaf"></i></a>
            </div>
        </div>

        <div class="carousel-item">
             <img src="quincho/fotos/6.jpeg" class="d-block w-100" alt="...">
             <div class="carousel-caption">
                    <h1>¡Aca vas a poder reservar Quincho!</h1>
                    <p>Por Lavalle, en Posadas Misiones.</p>
                    <a href="quincho/reserva_quincho.php" class="btn-primary">¡Reserva Ahora! <i class="fas fa-leaf"></i></a>
              </div>
         </div>
   </div>
          <button class="carousel-control-prev" type="button" data-target="#carouselExampleCaptions" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previo</span>
          </button>
          <button class="carousel-control-next" type="button" data-target="#carouselExampleCaptions" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Siguiente</span>
          </button>
</div>
           


                    

      


         
<hr class="my-0">

 <section id="contacto" class="info-section">
            <h2>Secretaría de Deportes y Turismo</h2>
            <p>¿Tenés alguna pregunta? ¡Contactanos!</p>
            <p>Teléfono: 3764 35987</p>
            <p>Email: sposm@gmail.com</p>
            <p>Para realizar alguna consulta contactate con: Diego J. Galeano</p>
        </section>


            <footer class="footer">
                 <p>&copy;2025 Sindicato de Obras Sanitarias Misiones. Todos los derechos reservados desarrollado por Ruben D. Galeano Consultor IT - mail: rubengaleano83@gmai.com.</p>
                 <div class="social-buttons">
                    <a href="https://www.facebook.com/gremialessposm.obrassanitariasmisiones/?locale=es_LA" target="_blank" class="social-button facebook">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/5/51/Facebook_f_logo_%282019%29.svg" alt="Facebook">
                    </a>
                    <a href="https://www.youtube.com/@SindicatodelPersonaldeObrasSan" target="_blank" class="social-button youtube">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/0/09/YouTube_full-color_icon_%282017%29.svg" alt="YouTube">
                    </a>
                    <a href="https://www.instagram.com/s.p.o.s.m/" target="_blank" class="social-button instagram">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Instagram_icon.png" alt="Instagram">
                    </a>
                </div>
            </footer>

        <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js" integrity="sha384-+sLIOodYLS7CIrQpBjl+C7nPvqq+FbNUBDunl/OZv93DB7Ln/533i8e/mZXLi/P+" crossorigin="anonymous"></script>
        <!--<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
        no funciona el carrusel con esta version--> 
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
</body>
</html>
