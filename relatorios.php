<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';

$pdo = getDb();

$dataSelecionada = trim($_GET['data'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataSelecionada)) {
    $dataSelecionada = date('Y-m-d');
}

// Download do relatório da data selecionada em CSV (compatível com Excel PT-BR)
if (isset($_GET['baixar']) && $_GET['baixar'] === '1') {
    $sql = "
        SELECT
            f.id AS frete_id,
            f.data_frete,
            f.status,
            t.nome AS transportador,
            t.veiculo,
            eo.cidade || ' / ' || eo.uf AS origem,
            ed.cidade || ' / ' || ed.uf AS destino,
            p.nome AS produto,
            fi.quantidade,
            p.peso_kg,
            (fi.quantidade * p.peso_kg) AS peso_total_item,
            p.preco,
            (fi.quantidade * p.preco) AS valor_total_item
        FROM fretes f
        JOIN transportadores t ON t.id = f.transportador_id
        JOIN enderecos eo ON eo.id = f.origem_id
        JOIN enderecos ed ON ed.id = f.destino_id
        JOIN frete_itens fi ON fi.frete_id = f.id
        JOIN produtos p ON p.id = fi.produto_id
        WHERE f.data_frete = :data_frete
        ORDER BY f.id ASC, p.nome ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':data_frete' => $dataSelecionada]);
    $linhas = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="relatorio_fretes_' . $dataSelecionada . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    // UTF-8 BOM para manter acentuação correta ao abrir no Excel
    fwrite($out, "\xEF\xBB\xBF");

    fputcsv($out, [
        'ID Frete',
        'Data',
        'Status',
        'Transportador',
        'Veículo',
        'Origem',
        'Destino',
        'Produto',
        'Quantidade',
        'Peso Unit. (kg)',
        'Peso Total (kg)',
        'Preço Unit. (R$)',
        'Valor Total (R$)',
    ], ';');

    foreach ($linhas as $linha) {
        $codigoFrete = '#FRT-' . str_pad((string) $linha['frete_id'], 3, '0', STR_PAD_LEFT);
        fputcsv($out, [
            $codigoFrete,
            $linha['data_frete'],
            $linha['status'],
            $linha['transportador'],
            $linha['veiculo'],
            $linha['origem'],
            $linha['destino'],
            $linha['produto'],
            (int) $linha['quantidade'],
            number_format((float) $linha['peso_kg'], 2, ',', ''),
            number_format((float) $linha['peso_total_item'], 2, ',', ''),
            number_format((float) $linha['preco'], 2, ',', ''),
            number_format((float) $linha['valor_total_item'], 2, ',', ''),
        ], ';');
    }

    fclose($out);
    exit;
}

$tituloPagina = 'Relatórios';

// Prévia rápida de quantos fretes existem na data selecionada
$stmtResumo = $pdo->prepare('SELECT COUNT(*) FROM fretes WHERE data_frete = :data_frete');
$stmtResumo->execute([':data_frete' => $dataSelecionada]);
$totalFretesData = (int) $stmtResumo->fetchColumn();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Cabeçalho -->
<div class="mb-7">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Relatório de Fretes Diários</h1>
    <p class="text-sm text-slate-500 mt-1">Consulte as movimentações de uma data e exporte o relatório da operação.</p>
</div>

<!-- Card central -->
<div class="max-w-xl mx-auto mt-6">
    <div class="bg-white border border-slate-200 rounded-2xl p-8 sm:p-10 text-center">
        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-5">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
        </div>

        <h2 class="text-xl font-bold text-slate-900">Exporte sua operação do dia</h2>
        <p class="text-xs text-slate-500 mt-1.5">
            Todos os fretes da data selecionada em um único arquivo para download.
        </p>

        <form method="GET" action="relatorios.php" id="formRelatorio" class="mt-7 text-left space-y-5">
            <div>
                <label for="data" class="block text-xs font-medium text-slate-700 mb-2">
                    Selecione a data do relatório:
                </label>
                <input
                    type="date"
                    id="data"
                    name="data"
                    required
                    value="<?= htmlspecialchars($dataSelecionada) ?>"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/15"
                >
                <p id="infoFretesDia" class="text-xs text-slate-400 mt-1.5">
                    <?= $totalFretesData === 1
                        ? '1 frete encontrado para esta data.'
                        : "{$totalFretesData} fretes encontrados para esta data." ?>
                </p>
            </div>

            <button
                type="submit"
                name="baixar"
                value="1"
                class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <span>Baixar Relatório do Dia</span>
            </button>
        </form>

        <p class="text-[11px] text-slate-400 mt-6 leading-relaxed">
            Formato CSV (compatível com Excel) • Fretes, transportadores, rotas e produtos<br>
            O relatório considera apenas a data selecionada.
        </p>
    </div>
</div>

<script>
    (function () {
        const inputData = document.getElementById('data');
        const form = document.getElementById('formRelatorio');
        if (!inputData || !form) return;

        // Ao trocar a data, recarrega a página sem baixar para atualizar a contagem daquele dia
        inputData.addEventListener('change', function () {
            if (this.value) {
                window.location.href = 'relatorios.php?data=' + encodeURIComponent(this.value);
            }
        });
    })();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>