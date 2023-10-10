<!-- resources/views/emails/reset-password.blade.php -->

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
</head>
<body>

    <p>Haz clic en el siguiente enlace para restablecer tu contraseña:</p>

    <a href="{{ $resetLink }}">{{ $resetLink }}</a>

</body>
</html>
