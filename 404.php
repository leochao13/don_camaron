<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error 404 - Página no encontrada</title>
    <link rel="stylesheet" href="/css/errorstyle.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="icon" href="/icon.png" type="image/x-icon">

    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background: #dafbfbff;
            margin: 0;
            padding: 0;
            text-align: center;
            color: #333;
        }

        .container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .error-code {
            font-size: 8rem; 
            font-weight: 800;
            color: #00c4a7; 
            margin: 0;
        }

        .camaron-img {
            max-width: 150px;
            margin: 20px 0;
            animation: float 3s ease-in-out infinite;
        }

        h1 {
            font-size: 2rem;
            color: #e74c3c;
            margin: 10px 0;
        }

        p {
            margin-bottom: 20px;
            color: #555;
        }

        .boton button {
            background-color: #00c4a7;
            border: none;
            color: white;
            padding: 12px 25px;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .boton button:hover {
            background-color: #009c85;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>

<body>

    <div class="container">
        
        <div class="error-code">404</div>

        
        <img src="/image/camaron-perdido.png" alt="Camarón perdido" class="camaron-img" style="max-width:150px;">


        
        <h1>¡Ups! El camarón se perdió 🦐</h1>
        <p>La página que buscas no está disponible.</p>

        <div class="boton">
            <button onclick="window.location.href='/index.html'">Volver al inicio</button>
        </div>
    </div>

</body>
</html>
