<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
    <style>
        /* Importar estilos de Tailwind CSS en línea */
        @import url('https://cdn.jsdelivr.net/npm/tailwindcss@2.2.16/dist/tailwind.min.css');
    </style>
</head>
<body style="font-family: 'Arial', sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px;">

    <div class="max-w-screen-sm mx-auto bg-white p-8 rounded-md shadow-md">
        <h1 class="text-center text-2xl mb-4">Restablecer Contraseña</h1>
        <p class="text-gray-700 text-lg mb-6">Haz clic en el siguiente enlace para restablecer tu contraseña:</p>

        <a href="{{ $resetLink }}" class="block text-center py-2 px-4 bg-blue-500 text-white rounded hover:bg-blue-600">{{ $resetLink }}</a>
    </div>

</body>
</html>
