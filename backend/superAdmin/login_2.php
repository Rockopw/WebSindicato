<!DOCTYPE html>
<html lang="es">
<head>
     <link rel="icon" href="imagenes/super admin.ico">
    <meta charset="UTF-8">
    <title>Acceso Administrador</title>

    <style>
        /* RESET */
        * {
            box-sizing: border-box;
            font-family: 'Courier New', monospace;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: black;
            color: #00ff41;

            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Fondo tipo matrix */
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background:
              repeating-linear-gradient(
                180deg,
                rgba(0,255,65,0.15) 0px,
                rgba(0,255,65,0.15) 1px,
                transparent 1px,
                transparent 4px
              );
            pointer-events: none;
        }

        /* Card */
        .login-card {
            position: relative;
            background: rgba(0, 0, 0, 0.85);
            padding: 40px 35px;
            width: 100%;
            max-width: 380px;

            border: 1px solid #00ff41;
            border-radius: 8px;

            box-shadow:
              0 0 15px #00ff41,
              inset 0 0 10px rgba(0,255,65,0.3);

            animation: flicker 3s infinite;
        }

        /* Efecto parpadeo */
        @keyframes flicker {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.95; }
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 0 0 10px #00ff41;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;

            background: black;
            border: 1px solid #00ff41;
            color: #00ff41;

            outline: none;
            box-shadow: inset 0 0 5px rgba(0,255,65,0.4);
        }

        input:focus {
            box-shadow:
              0 0 10px #00ff41,
              inset 0 0 10px rgba(0,255,65,0.5);
        }

        /* Botón */
        button {
            width: 100%;
            padding: 12px;

            background: transparent;
            color: #00ff41;
            border: 1px solid #00ff41;

            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;

            box-shadow: 0 0 10px #00ff41;
            transition: all 0.3s ease;
        }

        button:hover {
            background: #00ff41;
            color: black;
            box-shadow: 0 0 20px #00ff41;
        }

        /* Texto inferior opcional */
        .matrix-text {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            opacity: 0.7;
        }
    </style>
</head>

<body>

    <div class="login-card">
        <h2>Acceso Super Administrador</h2>

        <form action="validar_admin2.php" method="POST">
            <label>DNI</label>
            <input type="text" name="dni" required>

            <label>Contraseña</label>
            <input type="password" name="password" required>

            <button type="submit">Ingresar</button>
        </form>

        <div class="matrix-text">
            Wake up, Ruben...
        </div>
    </div>

</body>
</html>
