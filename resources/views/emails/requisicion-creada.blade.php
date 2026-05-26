<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Requisicion creada</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #111827; line-height: 1.5;">
    <h1 style="font-size: 20px; margin-bottom: 12px;">Requisicion creada</h1>

    <p>Hola {{ $requisicion->creador->name ?? 'usuario' }},</p>

    <p>Tu requisicion fue creada correctamente.</p>

    <p>
        <strong>Correlativo:</strong> {{ $requisicion->correlativo }}<br>
        <strong>Departamento:</strong> {{ $requisicion->departamento->name ?? '-' }}<br>
        <strong>Estado:</strong> {{ $requisicion->estado->estado ?? 'Presentado' }}<br>
        <strong>Fecha requerida:</strong> {{ $requisicion->fechaRequerido ?? '-' }}
    </p>

    <p>Adjunto encontraras el PDF de la requisicion.</p>
</body>
</html>
