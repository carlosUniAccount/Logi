<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';

$tituloPagina = 'Dashboard';
$pdo = getDb();

// Contadores para os cards superiores
$totalProdutos = (int) $pdo->query('SELECT COUNT(*) FROM produtos')->fetchColumn();
$totalTransportadores = (int) $pdo->query('SELECT COUNT(*) FROM transportadores')->fetchColumn();

$hoje = date('Y-m-d');
$stmtHoje = $pdo->prepare('SELECT COUNT(*) FROM fretes WHERE data_frete = :hoje');
$stmtHoje->execute([':hoje' => $hoje]);
$fretesHoje = (int) $stmtHoje->fetchColumn();

// Últimos 4 fretes cadastrados
$stmtUltimos = $pdo->query("
    SELECT
        f.id,
        eo.cidade || ' / ' || eo.uf AS origem,
        ed.cidade || ' / ' || ed.uf AS destino,
        t.nome AS transportador,
        COALESCE(f.status, 'Em trânsito') AS status
    FROM fretes f
    JOIN enderecos eo ON eo.id = f.origem_id
    JOIN enderecos ed ON ed.id = f.destino_id
    JOIN transportadores t ON t.id = f.transportador_id
    ORDER BY f.id DESC
    LIMIT 4
");
$ultimosFretes = $stmtUltimos->fetchAll();

// Data formatada em português para o badge superior direito
$meses = [
    1 => 'jan.', 2 => 'fev.', 3 => 'mar.', 4 => 'abr.',
    5 => 'mai.', 6 => 'jun.', 7 => 'jul.', 8 => 'ago.',
    9 => 'set.', 10 => 'out.', 11 => 'nov.', 12 => 'dez.'
];
$dataFormatada = date('d') . ' ' . $meses[(int) date('n')] . ' ' . date('Y');

require_once __DIR__ . '/includes/header.php';
?>

<!-- Cabeçalho de boas-vindas -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Bem-vindo ao Gerenciador Logístico</h1>
        <p class="text-sm text-slate-500 mt-1">Acompanhe sua operação e mantenha tudo em movimento.</p>
    </div>
    <div class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-600 shadow-2xs self-start">
        Hoje, <?= htmlspecialchars($dataFormatada) ?>
    </div>
</div>

<!-- Cards de resumo (KPIs) -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <div class="flex items-center justify-between text-slate-500 mb-3">
            <span class="text-sm font-medium">Total de Produtos Cadastrados</span>
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
            </svg>
        </div>
        <p class="text-3xl font-bold text-slate-900"><?= $totalProdutos ?></p>
        <p class="text-xs text-slate-400 mt-2">Produtos disponíveis no catálogo</p>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <div class="flex items-center justify-between text-slate-500 mb-3">
            <span class="text-sm font-medium">Transportadores Ativos</span>
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
            </svg>
        </div>
        <p class="text-3xl font-bold text-slate-900"><?= $totalTransportadores ?></p>
        <p class="text-xs text-slate-400 mt-2">Parceiros prontos para transportar</p>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <div class="flex items-center justify-between text-slate-500 mb-3">
            <span class="text-sm font-medium">Fretes Gerados Hoje</span>
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
            </svg>
        </div>
        <p class="text-3xl font-bold text-slate-900"><?= $fretesHoje ?></p>
        <p class="text-xs text-slate-400 mt-2">Sua operação em movimento</p>
    </div>
</div>

<!-- Tabela de Últimos Fretes -->
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden mb-8">
    <div class="p-6 flex items-center justify-between border-b border-slate-100">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Últimos fretes</h2>
            <p class="text-xs text-slate-500 mt-0.5">As movimentações mais recentes da sua operação.</p>
        </div>
        <a href="fretes.php" class="text-sm font-medium text-blue-600 hover:text-blue-700 inline-flex items-center gap-1">
            Ver todos os fretes
            <span aria-hidden="true">→</span>
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                    <th class="py-3.5 px-6">Frete</th>
                    <th class="py-3.5 px-6">Origem</th>
                    <th class="py-3.5 px-6">Destino</th>
                    <th class="py-3.5 px-6">Transportador</th>
                    <th class="py-3.5 px-6">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                <?php if (empty($ultimosFretes)): ?>
                    <tr>
                        <td colspan="5" class="py-8 px-6 text-center text-slate-400">
                            Nenhum frete registrado ainda. Use os atalhos abaixo para começar.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ultimosFretes as $frete): 
                        $codigo = '#FRT-' . str_pad((string) $frete['id'], 3, '0', STR_PAD_LEFT);
                        $badgeClass = match ($frete['status']) {
                            'Entregue' => 'bg-emerald-50 text-emerald-600',
                            'Aguardando coleta' => 'bg-amber-50 text-amber-600',
                            default => 'bg-blue-50 text-blue-600',
                        };
                    ?>
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-4 px-6 font-medium text-slate-900"><?= htmlspecialchars($codigo) ?></td>
                            <td class="py-4 px-6 text-slate-600"><?= htmlspecialchars($frete['origem']) ?></td>
                            <td class="py-4 px-6 text-slate-600"><?= htmlspecialchars($frete['destino']) ?></td>
                            <td class="py-4 px-6 text-slate-600"><?= htmlspecialchars($frete['transportador']) ?></td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?= $badgeClass ?>">
                                    <?= htmlspecialchars($frete['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Seção Comece por aqui -->
<div>
    <h2 class="text-sm font-semibold text-slate-800 mb-3">Comece por aqui</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="produtos.php?acao=novo" class="group bg-white border border-slate-200 rounded-xl p-5 flex items-center justify-between hover:border-blue-300 hover:shadow-2xs transition">
            <div>
                <p class="text-sm font-semibold text-slate-900 group-hover:text-blue-600 transition">Cadastrar produto</p>
                <p class="text-xs text-slate-500 mt-0.5">Organize seu catálogo</p>
            </div>
            <span class="text-blue-600 group-hover:translate-x-0.5 transition-transform">→</span>
        </a>

        <a href="enderecos.php?acao=novo" class="group bg-white border border-slate-200 rounded-xl p-5 flex items-center justify-between hover:border-blue-300 hover:shadow-2xs transition">
            <div>
                <p class="text-sm font-semibold text-slate-900 group-hover:text-blue-600 transition">Adicionar endereço</p>
                <p class="text-xs text-slate-500 mt-0.5">Defina origens e destinos</p>
            </div>
            <span class="text-blue-600 group-hover:translate-x-0.5 transition-transform">→</span>
        </a>

        <a href="fretes.php?acao=novo" class="group bg-white border border-slate-200 rounded-xl p-5 flex items-center justify-between hover:border-blue-300 hover:shadow-2xs transition">
            <div>
                <p class="text-sm font-semibold text-slate-900 group-hover:text-blue-600 transition">Gerar um frete</p>
                <p class="text-xs text-slate-500 mt-0.5">Prepare a próxima entrega</p>
            </div>
            <span class="text-blue-600 group-hover:translate-x-0.5 transition-transform">→</span>
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>