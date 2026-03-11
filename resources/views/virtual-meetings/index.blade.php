<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reunioes Virtuais</title>
</head>
<body>
    <h1>Reunioes Virtuais</h1>

    <p>Horario do servidor: {{ $serverTime->format('d/m/Y H:i') }}</p>
    <p>Em andamento: {{ $runningCount }}</p>
    <p>Iniciando em breve: {{ $startingSoonCount }}</p>
    <p>Proximas: {{ $upcomingCount }}</p>
</body>
</html>
