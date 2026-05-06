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
            

<header class="top-bar">
                    <div class="header-container">
                        <div class="d-flex align-items-center">
                            <img src="logo sindi.jpg" class="rounded-circle me-3 shadow-white logo-img" width="60" height="55">
                            <h3 class="welcome-text mb-0 text-white">NOSOTROS</h3>
                        </div>

                                <button class="menu-toggle" id="menuToggle" aria-label="Abrir menú">
                                <i class="fa-solid fa-bars"></i>
                            </button>

                        <nav class="main-nav" id="mainNav">
                            <ul>
                                <li><a href="index.php"><i class="fas fa-home"></i> Inicio</a></li>
                                <li><a href="#contacto">Contacto</a></li>
                        
                            </ul>
                        </nav>
                    </div>
                </header>

s


        <hr class="my-0">

   <p>&nbsp;</p> <!-- dejo un parrafo vacio -->
   <p>&nbsp;</p> <!-- dejo un parrafo vacio -->



      
   
   
   <div class="container my-5">
    <div class="card bg-dark text-white">
        <img src="logo sindi.jpg" class="card-img" alt="Imagen del sindicato">
        <img src="logo sindi2.jpg" class="card-img" alt="Imagen del sindicato">
        
        <div class="card-img-overlay p-4">
            <h5 class="card-title">¿QUIENES SOMOS?</h5>
            <p class="card-text">El Sindicato del Personal de Obras Sanitarias Misiones, fundado en el año 1980, es una organización que representa y defiende los derechos, intereses y bienestar de los trabajadores del sector de obras sanitarias en la provincia de Misiones. Nuestro objetivo es garantizar la representatividad, protección y promoción de todos nuestros afiliados, trabajando por sus derechos laborales, condiciones de trabajo y calidad de vida. Nos esforzamos por ser una voz activa y efectiva en la defensa de los trabajadores y en la búsqueda de soluciones que beneficien a todos. Además, nos comprometemos a mantener una gestión transparente, honesta y responsable, brindando información clara y oportuna sobre nuestras acciones y decisiones, para generar confianza y participación activa de nuestros afiliados. Historia De Obras Sanitarias El 9 de abril de 1956, nace en Rosario la Federación Nacional de Trabajadores de Obras Sanitarias, siendo sus principales propulsores los Cros. Ulises Callegari (Sindicato Rosario) como primer secretario general, y Edmundo Frassoni (Sindicato Santa Fe), como primer secretario administrativo. Hasta 1955, el Personal de la Administración Pública Nacional se nucleaba sindicalmente en dos organizaciones: ATE (Asociación de Trabajadores del Estado) y UPCN (Unión del Personal Civil de la Nación). Producido el golpe militar del 16 de septiembre de 1955, que dio paso a la dictadura de Aramburu-Rojas, eclosiona la necesidad de los trabajadores de organizarse por actividad. Nacen entre otros gremios: FOECyT (Personal de Correos), Vialidad Nacional, Salud Pública y nuestra FeNTOS, nucleando a todos los trabajadores de Obras Sanitarias de la Nación (OSN)..</p>
            <h5 class="card-title mt-4">MISION</h5>
            <p class="card-text">Representar, unificar y defender los derechos laborales y sociales de los trabajadores del sector de Obras Sanitarias en la provincia, promoviendo la organización sindical por actividad, la solidaridad y el compromiso con la mejora continua de las condiciones laborales.</p>
            <h5 class="card-title mt-4">VISION</h5>
            <p class="card-text">Ser una organización sindical sólida, democrática y federal, reconocida a nivel nacional por su compromiso histórico con los trabajadores, su capacidad de representación, y su rol fundamental en la defensa de los derechos laborales y en la construcción de una sociedad más justa.</p>
            <h5 class="card-title mt-4">VALORES</h5>
                       <p class="card-text">Unidad: Creemos en la fuerza de la organización colectiva para lograr conquistas laborales.
                                Solidaridad: Fomentamos el compañerismo y la ayuda mutua entre trabajadores.
                                Democracia sindical: Promovemos la participación activa y equitativa de todos los afiliados.
                                Compromiso: Mantenemos una defensa constante e inquebrantable de los derechos de los trabajadores.
                                Memoria histórica: Honramos a quienes lucharon por la creación de una organización propia, autónoma y comprometida con su sector.
                                Justicia social: Trabajamos por una provincia con equidad, dignidad laboral y oportunidades para todos.</p>
            <h5 class="card-title mt-4">COMISION DIRECTIVA</h5>

                <p class="card-text"> Secretario general Zanivan Eduardo Daniel DNI N° 30.255.468</p>
                <p> Secretario adjunto Galeano Diego Javier DNI N° 28.403.742</p>
                <p>Secretario Gremial Franchini Oscar Domingo DNI N° 17.630.425</p>
                <p>Secretaría de la mujer y de acción social Da Rosa Marta Manuela DNI N° 21.566.736 </p>
                <p>Secretaría de actas Villa Dora Emilia DNI N° 13.004.787</p>
                <p>Secretario de hacienda Asselborn Julio Fernando DNI N° 14.721.051 </p>
                <p> Secretario de organización y capacitación Lezcano José Manuel DNI N° 28.739.655</p>
                <p>Secretario técnico profesional Eleuterio Jorge Oscar DNI N° 24.130.889</p>
                <p>Secretario del interior Zarza Ramón Bernabé DNI N°  13.732.025</p>
                <p>Secretario de acción política gremial Sosa Ramón DNI N°  16.241.964</p>
                <p>Secretario de prensa y difusión Ayala Iván Maximiliano DNI N° 38.137.850</p>
                <p>Secretario administrativo Pinto Héctor Fabián DNI N° 28.354.393</p>
                <p> Secretario de deportes y turismo Garay Orlando Richard DNI N° 23.383.358</p>
                <p> Vocal titular Escalante Guillermo Adrián DNI N° 25.637.020</p>
                <p> Vocal titular Lezcano Carlos Martín DNI N° 29.596.775</p>
                <p>Vocal titular Cardozo José Luis DNI N° 21.566.663</p>
                <p>Vocal titular Noguera elvio Oribe DNI N° 17.412.454</p>
                <p>Vocal suplente Acosta Mario Alberto DNI N° 27.233.442</p>
                <p> Vocal suplente Wrublewski Gladys Adriana DNI N° 16.331.074</p>
                <p>Vocal suplente González Héctor Daniel DNI N° 26.292.405</p>
                <p>Vocal suplente Feltan Patricia Viviana DNI N° 18.618.556</p>
                <p>Revisor de cuentas titular Cabral Diego Alejandro DNI N° 30.215.583</p>
                <p>Revisor de cuentas titular Leites Julio Víctor DNI N° 31.330.610</p>
                <p>Revisor de cuentas titular Viera Simón Ángel DNI N° 16.933.940</p>
                <p>Revisor de cuentas suplente Rodríguez Osmar DNI N° 14.623.949</p>
                <p>Revisora de cuentas suplente Antunez Andrea DNI N° 32.884.881</p>
                <p>Revisora de cuentas suplente Quiñones Natalia Isabel DNI N° 32.850.789</p>

        
        </div>
    </div>
</div>
   
<section id="contacto" class="info-section">
            <h2>Contacto</h2>
            <p>¿Tenés alguna pregunta? ¡Contactanos!</p>
            <p>Teléfono: 4426851 - 4428848</p>
            <p>Email: sposm@gmail.com</p>
            <p>Dirección: Dirección: Tambor de Tacuarí 2807, Posadas Misiones</p>
</section>

<hr class="my-0">
            

        <!-- <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-+sLIOodYLS7CIrQpBjl+C7nPvqq+FbNUBDunl/OZv93DB7Ln/533i8e/mZXLi/P+" crossorigin="anonymous"></script>
       <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
        -->

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
