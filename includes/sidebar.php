<?php
$itensMenu = [
    ['arquivo' => 'index.php', 'label' => 'Home'],
    ['arquivo' => 'produtos.php', 'label' => 'Produtos'],
    ['arquivo' => 'enderecos.php', 'label' => 'Endereços'],
    ['arquivo' => 'transportadores.php', 'label' => 'Transportadores'],
    ['arquivo' => 'fretes.php', 'label' => 'Fretes'],
    ['arquivo' => 'relatorios.php', 'label' => 'Relatórios'],
];
?>
<aside class="w-64 bg-white border-r border-slate-200 flex flex-col shrink-0">
    <div class="h-16 px-6 flex items-center gap-3 border-b border-slate-100">
        <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold">L</div>
        <span class="text-xl font-bold tracking-tight text-slate-900">logi</span>
    </div>
    <nav class="p-4 space-y-1">
        <?php foreach ($itensMenu as $item): 
            $ativo = ($paginaAtual === $item['arquivo']);
            $classes = $ativo 
                ? 'bg-blue-50 text-blue-600 font-medium' 
                : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900';
        ?>
            <a href="<?= $item['arquivo'] ?>" class="flex items-center px-3 py-2.5 rounded-lg text-sm transition <?= $classes ?>">
                <?= htmlspecialchars($item['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>