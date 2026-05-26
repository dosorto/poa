<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado de requisicion actualizado</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #111827; line-height: 1.5;">
    <h1 style="font-size: 20px; margin-bottom: 12px;">Estado de requisicion actualizado</h1>

    <p>Hola {{ $requisicion->creador->name ?? 'usuario' }},</p>

    <p>La requisicion {{ $requisicion->correlativo }} cambio de estado.</p>

    <p>
        <strong>Nuevo estado:</strong> {{ $nuevoEstado }}<br>
        <strong>Departamento:</strong> {{ $requisicion->departamento->name ?? '-' }}<br>
        <strong>Descripcion:</strong> {{ $requisicion->descripcion ?? '-' }}
    </p>
</body>
</html>
