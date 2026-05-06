<?php
    require_once "encabezado.php"; // hace un requerimiento a archivos para poder usar sus funciones, y si no funciona se queda ahi, no sigue ejecutando. ej base de datos//
?>

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
    margin-left:200px;
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

<section id="inicio" class="inicio-section"></section>
        

                <header class="top-bar">
                    <div class="header-container">
                        <div class="d-flex align-items-center">
                            <img src="logo sindi.jpg" class="rounded-circle me-3 shadow-white logo-img" width="60" height="55">
                            <h3 class="welcome-text mb-0 text-white">S.P.O.S.M</h3>
                        </div>

                                <button class="menu-toggle" id="menuToggle" aria-label="Abrir menú">
                                <i class="fa-solid fa-bars"></i>
                            </button>

                        <nav class="main-nav" id="mainNav">
                            <ul>
                                <li><a href="#inicio"><i class="fas fa-home"></i> Inicio</a></li>
                                <li><a href="Nosotros.php">Nosotros</a></li>
                                <li><a href="#novedades">Novedades</a></li>
                                <li><a href="espacio_de_reservas.php">Reservas</a></li>
                                <li><a href="#contacto">Contacto</a></li>
                        
                            </ul>
                        </nav>
                    </div>
                </header>







</section>

        <hr class="my-0">
        <p>&nbsp;</p> <!-- dejo un parrafo vacio -->
        <p>&nbsp;</p> <!-- dejo un parrafo vacio -->


            <div class="background-image-container">
            <h1>Sindicato del personal de Obras Sanitarias Misiones</h1>
            <p>"Unidos por los Derechos de los Trabajadores y sus Familias"</p>
            </div>
                

      


         <section id="novedades" class="Novedades-section">
            <div class="container">
                <h3>¡Noticias destacadas!</h3>
                <p>&nbsp;</p> <!-- dejo un parrafo vacio -->
                <div class="news-grid">
                
                    <!-- Noticia de Ejemplo 1 -->
                    <div class="news-card">
                        <img src="imagenes/pato.jpeg" alt="Imagen Noticia 1">
                        <div class="card-content">
                            <h3>COMUNICADO OFICIAL - ELECCIONES GENERALES DE RENOVACIÓN DE CARGOS DE COMISIÓN DIRECTIVA - </h3>
                            <p>El Sindicato Del Personal de Obras Sanitarias Misiones informa a todos los afiliados y afiliadas que el día viernes 5 de diciembre se llevaron a cabo las elecciones de Generales para Renovación de Cargos de Comisión Directiva conforme al llamado electoral realizado el pasado 17 de septiembre, cumpliendo en tiempo y forma con lo establecido por nuestro estatuto.
                                Agradecemos especialmente a toda la familia de Obras Sanitarias por dedicar su tiempo y acercarse a votar.
                                Más del 90% de los empadronados emitieron su voto, dónde:  
                                * La LISTA AZUL y BLANCA obtuvo 350 votos que representa el 58% de los empadronados.
                                * La LISTA VERDE obtuvo 186 votos que representa el 30% de los empadronados.
                                * La LISTA BLANCA obtuvo 72 votos que representa el 12% de los empadronados.
                            </p>
                           <!-- <a href="novedades1.php" class="read-more">Leer más</a>-->
                        </div>
                    </div>

                    <!-- Noticia de Ejemplo 2 -->
                    <div class="news-card">
                        <img src="imagenes/novedades 2.jpeg" alt="Compromiso">
                        <div class="card-content">
                         <!--   <h3> ¡Jornada por el Día del Niño!</h3>
                            <p>Hermosa Jornada del dia del Niño organizada junto al Sindicato en la Biblioteca Popular de las Misiones..</p>
                            <a href="#" class="read-more">Leer más</a>-->
                        </div>
                    </div>

                    <!-- Noticia de Ejemplo 3 -->
                    <div class="news-card">
                        <img src="imagenes/torneo de futbol.jpg" alt="dia del trabajador sanitarista">
                        <div class="card-content">
                            <h3>¡Torneo de Futbol 5!</h3>
                            <p>Este 15 de Mayo se realizara un torneo de futbol 5 en el complejo deportivo Mbarete</p>
                            <a href="#" class="read-more">Leer más</a>
                        </div>
                    </div>
                    
                </div>
            </div>

</section>

<hr class="my-0">

 <section id="contacto" class="info-section">
            <h2>Contacto</h2>
            <p>¿Tenés alguna pregunta? ¡Contactanos!</p>
            <p>Teléfono: 4426851 - 4428848</p>
            <p>Email: info@sindicatodelpersonaldeobrassanit.com.ar</p>
            <p>Dirección: Dirección: Tambor de Tacuarí 2807, Posadas Misiones</p>
        </section>
<?php
require_once "pie.php";
 ?>   
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
