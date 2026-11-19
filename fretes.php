<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';

$tituloPagina = 'Fretes';
$pdo = getDb();

$erro = '';
$sucesso = $_GET['sucesso'] ?? '';

// Exclusão de frete via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
    $idExcluir = (int) ($_POST['id'] ?? 0);
    if ($idExcluir > 0) {
        $stmt = $pdo->prepare('DELETE FROM fretes WHERE id = :id');
        $stmt->execute([':id' => $idExcluir]);
        header('Location: fretes.php?sucesso=excluido');
        exit;
    }
}

// Criação de novo frete via POST (1 transportador, 1 origem, 1 destino, N produtos)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar') {
    $transportadorId = (int) ($_POST['transportador_id'] ?? 0);
    $origemId = (int) ($_POST['origem_id'] ?? 0);
    $destinoId = (int) ($_POST['destino_id'] ?? 0);
    $dataFrete = trim($_POST['data_frete'] ?? date('Y-m-d'));
    $status = trim($_POST['status'] ?? 'Em trânsito');

    $produtosSelecionados = $_POST['produtos'] ?? [];
    $quantidadesMap = $_POST['quantidades'] ?? [];

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFrete)) {
        $dataFrete = date('Y-m-d');
    }

    $statusPermitidos = ['Em trânsito', 'Aguardando coleta', 'Entregue'];
    if (!in_array($status, $statusPermitidos, true)) {
        $status = 'Em trânsito';
    }

    if ($transportadorId <= 0 || $origemId <= 0 || $destinoId <= 0) {
        $erro = 'Selecione o transportador, o endereço de origem e o endereço de destino.';
    } elseif ($origemId === $destinoId) {
        $erro = 'O endereço de origem e o endereço de destino devem ser diferentes.';
    } elseif (!is_array($produtosSelecionados) || empty($produtosSelecionados)) {
        $erro = 'Selecione pelo menos um produto para vincular ao frete.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmtFrete = $pdo->prepare('
                INSERT INTO fretes (data_frete, transportador_id, origem_id, destino_id, status)
                VALUES (:data_frete, :transportador_id, :origem_id, :destino_id, :status)
            ');
            $stmtFrete->execute([
                ':data_frete' => $dataFrete,
                ':transportador_id' => $transportadorId,
                ':origem_id' => $origemId,
                ':destino_id' => $destinoId,
                ':status' => $status,
            ]);

            $freteId = (int) $pdo->lastInsertId();

            $stmtItem = $pdo->prepare('
                INSERT INTO frete_itens (frete_id, produto_id, quantidade)
                VALUES (:frete_id, :produto_id, :quantidade)
            ');

            $itensInseridos = 0;
            foreach ($produtosSelecionados as $prodIdRaw) {
                $prodId = (int) $prodIdRaw;
                if ($prodId <= 0) {
                    continue;
                }
                $qtd = max(1, (int) ($quantidadesMap[$prodId] ?? 1));
                $stmtItem->execute([
                    ':frete_id' => $freteId,
                    ':produto_id' => $prodId,
                    ':quantidade' => $qtd,
                ]);
                $itensInseridos++;
            }

            if ($itensInseridos === 0) {
                throw new RuntimeException('Nenhum produto válido foi selecionado.');
            }

            $pdo->commit();
            header('Location: fretes.php?sucesso=cadastrado');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $erro = 'Erro ao gerar o frete: ' . $e->getMessage();
        }
    }
}

$modoNovo = (($_GET['acao'] ?? '') === 'novo') || ($erro !== '' && ($_POST['acao'] ?? '') === 'salvar');
$freteDetalheId = isset($_GET['detalhes']) ? (int) $_GET['detalhes'] : 0;

$transportadores = $pdo->query('SELECT id, nome, veiculo FROM transportadores ORDER BY nome ASC')->fetchAll();
$enderecos = $pdo->query('SELECT id, logradouro, numero, cidade, uf FROM enderecos ORDER BY cidade ASC, logradouro ASC')->fetchAll();
$produtos = $pdo->query('SELECT id, nome, preco, peso_kg FROM produtos ORDER BY id ASC')->fetchAll();

// Detalhes de um frete específico (se solicitado)
$freteDetalhe = null;
$itensFreteDetalhe = [];
if ($freteDetalheId > 0 && !$modoNovo) {
    $stmtDet = $pdo->prepare("
        SELECT
            f.*,
            t.nome AS transportador_nome,
            t.veiculo AS transportador_veiculo,
            eo.logradouro || ', ' || eo.numero || ' • ' || eo.cidade || ' / ' || eo.uf AS origem_desc,
            ed.logradouro || ', ' || ed.numero || ' • ' || ed.cidade || ' / ' || ed.uf AS destino_desc
        FROM fretes f
        JOIN transportadores t ON t.id = f.transportador_id
        JOIN enderecos eo ON eo.id = f.origem_id
        JOIN enderecos ed ON ed.id = f.destino_id
        WHERE f.id = :id
    ");
    $stmtDet->execute([':id' => $freteDetalheId]);
    $freteDetalhe = $stmtDet->fetch() ?: null;

    if ($freteDetalhe) {
        $stmtItens = $pdo->prepare('
            SELECT
                fi.quantidade,
                p.id AS produto_id,
                p.nome AS produto_nome,
                p.preco,
                p.peso_kg
            FROM frete_itens fi
            JOIN produtos p ON p.id = fi.produto_id
            WHERE fi.frete_id = :frete_id
            ORDER BY p.nome ASC
        ');
        $stmtItens->execute([':frete_id' => $freteDetalheId]);
        $itensFreteDetalhe = $stmtItens->fetchAll();
    }
}

// Listagem geral de fretes
$fretes = [];
if (!$modoNovo) {
    $fretes = $pdo->query("
        SELECT
            f.id,
            f.data_frete,
            f.status,
            t.nome AS transportador,
            eo.cidade || ' / ' || eo.uf AS origem,
            ed.cidade || ' / ' || ed.uf AS destino,
            COALESCE(SUM(fi.quantidade), 0) AS qtd_produtos
        FROM fretes f
        JOIN transportadores t ON t.id = f.transportador_id
        JOIN enderecos eo ON eo.id = f.origem_id
        JOIN enderecos ed ON ed.id = f.destino_id
        LEFT JOIN frete_itens fi ON fi.frete_id = f.id
        GROUP BY f.id
        ORDER BY f.id DESC
    ")->fetchAll();
}

$hojeIso = date('Y-m-d');
$stmtHoje = $pdo->prepare('SELECT COUNT(*) FROM fretes WHERE data_frete = :hoje');
$stmtHoje->execute([':hoje' => $hojeIso]);
$fretesHoje = (int) $stmtHoje->fetchColumn();

$meses = [
    1 => 'jan.', 2 => 'fev.', 3 => 'mar.', 4 => 'abr.',
    5 => 'mai.', 6 => 'jun.', 7 => 'jul.', 8 => 'ago.',
    9 => 'set.', 10 => 'out.', 11 => 'nov.', 12 => 'dez.'
];
$dataHojeLabel = date('d') . ' ' . $meses[(int) date('n')] . ' ' . date('Y');

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($erro !== ''): ?>
    <div class="mb-6 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
        <?= htmlspecialchars($erro) ?>
    </div>
<?php endif; ?>

<?php if ($sucesso === 'cadastrado'): ?>
    <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
        Frete gerado e vinculado aos produtos com sucesso.
    </div>
<?php elseif ($sucesso === 'excluido'): ?>
    <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
        Frete excluído com sucesso.
    </div>
<?php endif; ?>

<?php if ($modoNovo): ?>
    <?php
    $selTransportador = (int) ($_POST['transportador_id'] ?? 0);
    $selOrigem = (int) ($_POST['origem_id'] ?? 0);
    $selDestino = (int) ($_POST['destino_id'] ?? 0);
    $selData = (string) ($_POST['data_frete'] ?? date('Y-m-d'));
    $selStatus = (string) ($_POST['status'] ?? 'Em trânsito');
    $marcados = array_map('intval', $_POST['produtos'] ?? []);
    $qtdsPost = $_POST['quantidades'] ?? [];
    ?>

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-slate-400 mb-3">
        <a href="fretes.php" class="hover:text-slate-600 transition">Fretes</a>
        <span>/</span>
        <span class="text-slate-600 font-medium">Novo frete</span>
    </nav>

    <!-- Cabeçalho -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-7">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Novo Frete</h1>
            <p class="text-sm text-slate-500 mt-1">Vincule um transportador, rota e múltiplos produtos em um único frete.</p>
        </div>
        <span class="text-xs text-slate-400">Um transportador, uma rota e vários produtos.</span>
    </div>

    <?php if (empty($transportadores) || count($enderecos) < 2 || empty($produtos)): ?>
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-sm text-amber-800 space-y-2">
            <p class="font-semibold">Cadastros necessários antes de gerar um frete:</p>
            <ul class="list-disc list-inside space-y-1 text-xs">
                <?php if (empty($transportadores)): ?>
                    <li>Cadastre ao menos 1 <a href="transportadores.php?acao=novo" class="underline font-medium">transportador</a>.</li>
                <?php endif; ?>
                <?php if (count($enderecos) < 2): ?>
                    <li>Cadastre ao menos 2 <a href="enderecos.php?acao=novo" class="underline font-medium">endereços</a> (origem e destino).</li>
                <?php endif; ?>
                <?php if (empty($produtos)): ?>
                    <li>Cadastre ao menos 1 <a href="produtos.php?acao=novo" class="underline font-medium">produto</a>.</li>
                <?php endif; ?>
            </ul>
        </div>
    <?php else: ?>
        <form method="POST" action="fretes.php" id="formNovoFrete" class="space-y-6">
            <input type="hidden" name="acao" value="salvar">

            <!-- 1. Selecione o Transportador -->
            <div class="bg-white border border-slate-200 rounded-xl p-6 sm:p-7">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    <div class="lg:col-span-5">
                        <h2 class="text-base font-semibold text-slate-900">1. Selecione o Transportador</h2>
                        <p class="text-xs text-slate-500 mt-1">Escolha quem realizará esta entrega.</p>
                    </div>
                    <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label for="transportador_id" class="block text-sm font-medium text-slate-700 mb-2">
                                Transportador *
                            </label>
                            <select
                                id="transportador_id"
                                name="transportador_id"
                                required
                                class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/15"
                            >
                                <option value="">Selecione um transportador</option>
                                <?php foreach ($transportadores as $t): ?>
                                    <option
                                        value="<?= (int) $t['id'] ?>"
                                        data-nome="<?= htmlspecialchars((string) $t['nome']) ?>"
                                        <?= ($selTransportador === (int) $t['id']) ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($t['nome'] . ' • ' . $t['veiculo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="data_frete" class="block text-xs font-medium text-slate-600 mb-1.5">
                                Data do Frete *
                            </label>
                            <input
                                type="date"
                                id="data_frete"
                                name="data_frete"
                                required
                                value="<?= htmlspecialchars($selData) ?>"
                                class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/15"
                            >
                        </div>

                        <div>
                            <label for="status" class="block text-xs font-medium text-slate-600 mb-1.5">
                                Status inicial
                            </label>
                            <select
                                id="status"
                                name="status"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/15"
                            >
                                <?php foreach (['Em trânsito', 'Aguardando coleta', 'Entregue'] as $st): ?>
                                    <option value="<?= $st ?>" <?= ($selStatus === $st) ? 'selected' : '' ?>><?= $st ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Defina a rota -->
            <div class="bg-white border border-slate-200 rounded-xl p-6 sm:p-7">
                <h2 class="text-base font-semibold text-slate-900 mb-5">2. Defina a rota</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="origem_id" class="block text-sm font-medium text-slate-700 mb-2">
                            Endereço de Origem *
                        </label>
                        <select
                            id="origem_id"
                            name="origem_id"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/15"
                        >
                            <option value="">Selecione a origem</option>
                            <?php foreach ($enderecos as $end):
                                $rotulo = $end['logradouro'] . ', ' . $end['numero'] . ' • ' . $end['cidade'] . ' / ' . $end['uf'];
                            ?>
                                <option
                                    value="<?= (int) $end['id'] ?>"
                                    data-cidade="<?= htmlspecialchars((string) $end['cidade']) ?>"
                                    <?= ($selOrigem === (int) $end['id']) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($rotulo) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="destino_id" class="block text-sm font-medium text-slate-700 mb-2">
                            Endereço de Destino *
                        </label>
                        <select
                            id="destino_id"
                            name="destino_id"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/15"
                        >
                            <option value="">Selecione o destino</option>
                            <?php foreach ($enderecos as $end):
                                $rotulo = $end['logradouro'] . ', ' . $end['numero'] . ' • ' . $end['cidade'] . ' / ' . $end['uf'];
                            ?>
                                <option
                                    value="<?= (int) $end['id'] ?>"
                                    data-cidade="<?= htmlspecialchars((string) $end['cidade']) ?>"
                                    <?= ($selDestino === (int) $end['id']) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($rotulo) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 3. Vincular Produtos (múltiplos produtos + quantidade) -->
            <div class="bg-white border border-slate-200 rounded-xl p-6 sm:p-7">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-5">
                    <h2 class="text-base font-semibold text-slate-900">3. Vincular Produtos</h2>
                    <span class="text-xs text-slate-400">Selecione um ou mais produtos para este frete.</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    <?php foreach ($produtos as $prod):
                        $pid = (int) $prod['id'];
                        $codigoProd = str_pad((string) $pid, 3, '0', STR_PAD_LEFT);
                        $estaMarcado = in_array($pid, $marcados, true);
                        $qtdAtual = max(1, (int) ($qtdsPost[$pid] ?? 1));
                        $pesoUnit = (float) $prod['peso_kg'];
                    ?>
                        <div
                            class="card-produto flex items-center justify-between gap-3 rounded-xl border px-4 py-3 transition <?= $estaMarcado ? 'border-blue-300 bg-blue-50/40' : 'border-slate-200 bg-white hover:border-slate-300' ?>"
                        >
                            <label class="flex items-center gap-3 flex-1 min-w-0 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="produtos[]"
                                    value="<?= $pid ?>"
                                    data-peso="<?= htmlspecialchars((string) $pesoUnit) ?>"
                                    <?= $estaMarcado ? 'checked' : '' ?>
                                    class="chk-produto h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-600"
                                >
                                <span class="text-xs font-mono text-slate-400 shrink-0"><?= htmlspecialchars($codigoProd) ?></span>
                                <span class="text-sm font-medium truncate <?= $estaMarcado ? 'text-blue-700' : 'text-slate-800' ?>">
                                    <?= htmlspecialchars($prod['nome']) ?>
                                </span>
                            </label>

                            <div class="flex items-center gap-2 shrink-0">
                                <input
                                    type="number"
                                    name="quantidades[<?= $pid ?>]"
                                    min="1"
                                    value="<?= $qtdAtual ?>"
                                    title="Quantidade deste produto"
                                    class="qtd-produto w-16 rounded-lg border border-slate-300 bg-white px-2 py-1 text-center text-xs text-slate-800 focus:border-blue-600 focus:outline-none"
                                >
                                <span class="text-xs text-slate-400 w-16 text-right">
                                    <?= htmlspecialchars(number_format($pesoUnit, 2, ',', '.')) ?> kg
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-5 pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs">
                    <span id="resumoSelecao" class="font-medium text-blue-600">
                        0 de <?= count($produtos) ?> produtos selecionados
                    </span>
                    <span id="resumoPeso" class="text-slate-500">
                        Peso total selecionado: 0,00 kg
                    </span>
                </div>
            </div>

            <!-- Barra inferior de resumo e ação -->
            <div class="bg-white border border-slate-200 rounded-xl px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p id="resumoRotaTitulo" class="text-sm font-semibold text-slate-900">
                        Selecione transportador e rota
                    </p>
                    <p id="resumoRotaSubtitulo" class="text-xs text-slate-400 mt-0.5">
                        0 unidades vinculadas ao mesmo frete
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <a
                        href="fretes.php"
                        class="px-4 py-2.5 rounded-lg border border-slate-300 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50 transition"
                    >
                        Cancelar
                    </a>
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-blue-600 text-sm font-medium text-white hover:bg-blue-700 transition cursor-pointer"
                    >
                        <span>Gerar Frete</span>
                        <span aria-hidden="true">→</span>
                    </button>
                </div>
            </div>
        </form>

        <script>
            (function () {
                const selectTransp = document.getElementById('transportador_id');
                const selectOrigem = document.getElementById('origem_id');
                const selectDestino = document.getElementById('destino_id');
                const cards = document.querySelectorAll('.card-produto');
                const resumoSelecao = document.getElementById('resumoSelecao');
                const resumoPeso = document.getElementById('resumoPeso');
                const resumoRotaTitulo = document.getElementById('resumoRotaTitulo');
                const resumoRotaSubtitulo = document.getElementById('resumoRotaSubtitulo');
                const totalCatalogo = cards.length;

                function atualizarResumo() {
                    let produtosMarcados = 0;
                    let unidadesTotais = 0;
                    let pesoTotal = 0;

                    cards.forEach((card) => {
                        const chk = card.querySelector('.chk-produto');
                        const qtdInput = card.querySelector('.qtd-produto');
                        const labelSpan = card.querySelector('label span:last-child');
                        if (!chk || !qtdInput) return;

                        let qtd = parseInt(qtdInput.value, 10);
                        if (isNaN(qtd) || qtd < 1) {
                            qtd = 1;
                            qtdInput.value = '1';
                        }

                        const pesoUnit = parseFloat(chk.getAttribute('data-peso') || '0') || 0;

                        if (chk.checked) {
                            produtosMarcados++;
                            unidadesTotais += qtd;
                            pesoTotal += pesoUnit * qtd;
                            card.classList.remove('border-slate-200', 'bg-white');
                            card.classList.add('border-blue-300', 'bg-blue-50/40');
                            if (labelSpan) {
                                labelSpan.classList.remove('text-slate-800');
                                labelSpan.classList.add('text-blue-700');
                            }
                        } else {
                            card.classList.remove('border-blue-300', 'bg-blue-50/40');
                            card.classList.add('border-slate-200', 'bg-white');
                            if (labelSpan) {
                                labelSpan.classList.remove('text-blue-700');
                                labelSpan.classList.add('text-slate-800');
                            }
                        }
                    });

                    if (resumoSelecao) {
                        resumoSelecao.textContent = `${produtosMarcados} de ${totalCatalogo} produtos selecionados`;
                    }
                    if (resumoPeso) {
                        const pesoFormatado = pesoTotal.toFixed(2).replace('.', ',');
                        resumoPeso.textContent = `Peso total selecionado: ${pesoFormatado} kg`;
                    }

                    const optTransp = selectTransp && selectTransp.selectedIndex > 0
                        ? selectTransp.options[selectTransp.selectedIndex].getAttribute('data-nome')
                        : '';
                    const optOrigem = selectOrigem && selectOrigem.selectedIndex > 0
                        ? selectOrigem.options[selectOrigem.selectedIndex].getAttribute('data-cidade')
                        : '';
                    const optDestino = selectDestino && selectDestino.selectedIndex > 0
                        ? selectDestino.options[selectDestino.selectedIndex].getAttribute('data-cidade')
                        : '';

                    const partesRota = [];
                    if (optTransp) partesRota.push(optTransp);
                    if (optOrigem || optDestino) {
                        partesRota.push(`${optOrigem || 'Origem'} → ${optDestino || 'Destino'}`);
                    }

                    if (resumoRotaTitulo) {
                        resumoRotaTitulo.textContent = partesRota.length > 0
                            ? partesRota.join(' • ')
                            : 'Selecione transportador e rota';
                    }
                    if (resumoRotaSubtitulo) {
                        resumoRotaSubtitulo.textContent = unidadesTotais === 1
                            ? '1 unidade vinculada ao mesmo frete'
                            : `${unidadesTotais} unidades vinculadas ao mesmo frete`;
                    }
                }

                cards.forEach((card) => {
                    const chk = card.querySelector('.chk-produto');
                    const qtdInput = card.querySelector('.qtd-produto');
                    if (chk) chk.addEventListener('change', atualizarResumo);
                    if (qtdInput) {
                        qtdInput.addEventListener('input', () => {
                            if (chk && !chk.checked && parseInt(qtdInput.value, 10) > 1) {
                                chk.checked = true;
                            }
                            atualizarResumo();
                        });
                    }
                });

                if (selectTransp) selectTransp.addEventListener('change', atualizarResumo);
                if (selectOrigem) selectOrigem.addEventListener('change', atualizarResumo);
                if (selectDestino) selectDestino.addEventListener('change', atualizarResumo);

                atualizarResumo();
            })();
        </script>
    <?php endif; ?>

<?php else: ?>
    <!-- Cabeçalho da listagem de fretes -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-7">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Gerenciamento de Fretes</h1>
            <p class="text-sm text-slate-500 mt-1">Organize transportadores, rotas e produtos em um só lugar.</p>
        </div>
        <a
            href="fretes.php?acao=novo"
            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 text-sm font-medium text-white hover:bg-blue-700 transition self-start"
        >
            <span>+</span>
            <span>Gerar Novo Frete</span>
        </a>
    </div>

    <?php if ($freteDetalhe): ?>
        <div class="bg-white border border-blue-200 rounded-xl p-6 mb-6">
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4 mb-4">
                <div>
                    <span class="text-xs font-mono text-blue-600 font-semibold">
                        #FRT-<?= str_pad((string) $freteDetalhe['id'], 3, '0', STR_PAD_LEFT) ?>
                    </span>
                    <h2 class="text-base font-bold text-slate-900 mt-0.5">
                        <?= htmlspecialchars($freteDetalhe['transportador_nome']) ?>
                        <span class="text-xs font-normal text-slate-500">
                            (<?= htmlspecialchars($freteDetalhe['transportador_veiculo']) ?> • Data: <?= htmlspecialchars($freteDetalhe['data_frete']) ?>)
                        </span>
                    </h2>
                    <p class="text-xs text-slate-600 mt-1">
                        <strong>Origem:</strong> <?= htmlspecialchars($freteDetalhe['origem_desc']) ?> →
                        <strong>Destino:</strong> <?= htmlspecialchars($freteDetalhe['destino_desc']) ?>
                    </p>
                </div>
                <a href="fretes.php" class="text-xs font-medium text-slate-500 hover:text-slate-800">Fechar ×</a>
            </div>

            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Produtos vinculados</p>
            <ul class="divide-y divide-slate-100 text-sm">
                <?php foreach ($itensFreteDetalhe as $item): ?>
                    <li class="py-2 flex items-center justify-between">
                        <span class="text-slate-800"><?= htmlspecialchars($item['produto_nome']) ?></span>
                        <span class="text-xs text-slate-500">
                            <?= (int) $item['quantidade'] ?> un. × <?= htmlspecialchars(number_format((float) $item['peso_kg'], 2, ',', '.')) ?> kg
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Card da tabela -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-100">
            <div class="relative w-full sm:w-80">
                <input
                    type="text"
                    id="buscaFrete"
                    placeholder="Buscar por frete, transportador ou cidade..."
                    class="w-full rounded-lg border border-slate-300 pl-3.5 pr-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/15"
                >
            </div>
            <span class="text-xs text-slate-400">
                <?= htmlspecialchars($dataHojeLabel) ?> • <?= $fretesHoje ?> <?= $fretesHoje === 1 ? 'frete gerado hoje' : 'fretes gerados hoje' ?>
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-6">ID Frete</th>
                        <th class="py-3.5 px-6">Transportador</th>
                        <th class="py-3.5 px-6">Origem</th>
                        <th class="py-3.5 px-6">Destino</th>
                        <th class="py-3.5 px-6">Qtd. Produtos</th>
                        <th class="py-3.5 px-6 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if (empty($fretes)): ?>
                        <tr>
                            <td colspan="6" class="py-10 px-6 text-center text-slate-400">
                                Nenhum frete gerado ainda. Clique em <strong>+ Gerar Novo Frete</strong> para começar.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($fretes as $frete):
                            $codigoFrete = '#FRT-' . str_pad((string) $frete['id'], 3, '0', STR_PAD_LEFT);
                            $textoBusca = mb_strtolower(
                                $codigoFrete . ' ' . $frete['id'] . ' ' . $frete['transportador'] . ' ' .
                                $frete['origem'] . ' ' . $frete['destino'] . ' ' . $frete['data_frete']
                            );
                        ?>
                            <tr
                                class="linha-frete hover:bg-slate-50/70 transition"
                                data-busca="<?= htmlspecialchars($textoBusca) ?>"
                            >
                                <td class="py-4 px-6 font-mono text-xs text-slate-500"><?= htmlspecialchars($codigoFrete) ?></td>
                                <td class="py-4 px-6 font-medium text-slate-900"><?= htmlspecialchars($frete['transportador']) ?></td>
                                <td class="py-4 px-6 text-slate-600"><?= htmlspecialchars($frete['origem']) ?></td>
                                <td class="py-4 px-6 text-slate-600"><?= htmlspecialchars($frete['destino']) ?></td>
                                <td class="py-4 px-6 text-slate-700"><?= (int) $frete['qtd_produtos'] ?></td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center justify-end gap-4">
                                        <a
                                            href="fretes.php?detalhes=<?= (int) $frete['id'] ?>"
                                            class="text-xs font-medium text-blue-600 hover:text-blue-700 transition"
                                        >
                                            Detalhes
                                        </a>
                                        <form
                                            method="POST"
                                            action="fretes.php"
                                            onsubmit="return confirm('Deseja realmente excluir este frete?');"
                                            class="inline"
                                        >
                                            <input type="hidden" name="acao" value="excluir">
                                            <input type="hidden" name="id" value="<?= (int) $frete['id'] ?>">
                                            <button
                                                type="submit"
                                                class="text-xs font-medium text-red-500 hover:text-red-700 transition cursor-pointer"
                                            >
                                                Excluir
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        (function () {
            const inputBusca = document.getElementById('buscaFrete');
            const linhas = document.querySelectorAll('.linha-frete');
            if (!inputBusca || linhas.length === 0) return;

            inputBusca.addEventListener('input', function () {
                const termo = this.value.trim().toLowerCase();
                linhas.forEach((linha) => {
                    const texto = linha.getAttribute('data-busca') || '';
                    linha.style.display = (termo === '' || texto.includes(termo)) ? '' : 'none';
                });
            });
        })();
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>