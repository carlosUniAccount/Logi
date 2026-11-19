<?php
$paginaAtual = basename($_SERVER['PHP_SELF']);
$tituloPagina = $tituloPagina ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logi - <?= htmlspecialchars($tituloPagina) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
<div class="min-h-screen flex">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white border-b border-slate-200 px-8 flex items-center justify-between">
            <span class="font-semibold text-slate-800"><?= htmlspecialchars($tituloPagina) ?></span>
            
        </header>
        <main class="flex-1 p-8 max-w-7xl w-full mx-auto">