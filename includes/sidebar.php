<?php
$itensMenu = [
    ['arquivo' => 'index.php', 'label' => 'Home', 'icone' => 'home'],
    ['arquivo' => 'produtos.php', 'label' => 'Produtos', 'icone' => 'produtos'],
    ['arquivo' => 'enderecos.php', 'label' => 'Endereços', 'icone' => 'enderecos'],
    ['arquivo' => 'transportadores.php', 'label' => 'Transportadores', 'icone' => 'transportadores'],
    ['arquivo' => 'fretes.php', 'label' => 'Fretes', 'icone' => 'fretes'],
    ['arquivo' => 'relatorios.php', 'label' => 'Relatórios', 'icone' => 'relatorios'],
];

function renderizarIconeMenu(string $nome): string
{
    return match ($nome) {
        'home' => '<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>',
        'produtos' => '<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>',
        'enderecos' => '<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>',
        'transportadores' => '<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H18.75M2.25 14.25h16.5m0 0V9.375c0-.621-.504-1.125-1.125-1.125h-3.375m0 0V5.625c0-.621-.504-1.125-1.125-1.125H3.375C2.754 4.5 2.25 5.004 2.25 5.625v8.625m11.25-6v6" /></svg>',
        'fretes' => '<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>',
        'relatorios' => '<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>',
        default => '',
    };
}
?>
<aside class="w-64 bg-white border-r border-slate-200 flex flex-col shrink-0">
    <div class="h-16 px-6 flex items-center gap-3 border-b border-slate-100">
        <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold">L</div>
        <span class="text-xl font-bold tracking-tight text-slate-900">logi</span>
    </div>
    <nav class="p-4 space-y-1">
        <?php foreach ($itensMenu as $item):
            $ativo = (($paginaAtual ?? '') === $item['arquivo']);
            $classes = $ativo
                ? 'bg-blue-50 text-blue-600 font-medium'
                : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900';
        ?>
            <a href="<?= htmlspecialchars($item['arquivo']) ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition <?= $classes ?>">
                <?= renderizarIconeMenu($item['icone']) ?>
                <span><?= htmlspecialchars($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>