<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta final de entrega</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #111827; line-height: 1.5;">
    <h1 style="font-size: 20px; margin-bottom: 12px;">Acta final de entrega</h1>

    <p>Hola {{ $requisicion->creador->name ?? 'usuario' }},</p>

    <p>La requisicion {{ $requisicion->correlativo }} fue finalizada correctamente.</p>

    <p>
        <strong>Acta:</strong> {{ $acta->correlativo }}<br>
        <strong>Departamento:</strong> {{ $requisicion->departamento->name ?? '-' }}<br>
        <strong>Estado:</strong> {{ $requisicion->estado->estado ?? 'Finalizado' }}<br>
        <strong>Fecha de acta:</strong> {{ optional($acta->fecha_extendida)->format('d/m/Y') ?? '-' }}
    </p>

    <p>Adjunto encontraras el PDF del acta final de entrega.</p>
</body>
</html>
