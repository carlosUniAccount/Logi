<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';

$tituloPagina = 'Endereços';
$pdo = getDb();

$erro = '';
$sucesso = $_GET['sucesso'] ?? '';

$ufs = [
    'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO',
    'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI',
    'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
];

// Exclusão de endereço via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
    $idExcluir = (int) ($_POST['id'] ?? 0);
    if ($idExcluir > 0) {
        try {
            $stmt = $pdo->prepare('DELETE FROM enderecos WHERE id = :id');
            $stmt->execute([':id' => $idExcluir]);
            header('Location: enderecos.php?sucesso=excluido');
            exit;
        } catch (PDOException $e) {
            $erro = 'Não foi possível excluir este endereço porque ele está vinculado a um frete (origem ou destino).';
        }
    }
}

// Criação ou edição de endereço via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar') {
    $id = (int) ($_POST['id'] ?? 0);
    $cep = trim($_POST['cep'] ?? '');
    $logradouro = trim($_POST['logradouro'] ?? '');
    $numero = trim($_POST['numero'] ?? '');
    $bairro = trim($_POST['bairro'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $uf = strtoupper(trim($_POST['uf'] ?? ''));

    if ($cep === '' || $logradouro === '' || $numero === '' || $bairro === '' || $cidade === '' || !in_array($uf, $ufs, true)) {
        $erro = 'Preencha todos os campos obrigatórios e selecione uma UF válida.';
    } else {
        if ($id > 0) {
            $stmt = $pdo->prepare('
                UPDATE enderecos
                SET cep = :cep, logradouro = :logradouro, numero = :numero,
                    bairro = :bairro, cidade = :cidade, uf = :uf
                WHERE id = :id
            ');
            $stmt->execute([
                ':cep' => $cep,
                ':logradouro' => $logradouro,
                ':numero' => $numero,
                ':bairro' => $bairro,
                ':cidade' => $cidade,
                ':uf' => $uf,
                ':id' => $id,
            ]);
            header('Location: enderecos.php?sucesso=editado');
            exit;
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO enderecos (cep, logradouro, numero, bairro, cidade, uf)
                VALUES (:cep, :logradouro, :numero, :bairro, :cidade, :uf)
            ');
            $stmt->execute([
                ':cep' => $cep,
                ':logradouro' => $logradouro,
                ':numero' => $numero,
                ':bairro' => $bairro,
                ':cidade' => $cidade,
                ':uf' => $uf,
            ]);
            header('Location: enderecos.php?sucesso=cadastrado');
            exit;
        }
    }
}

$modoFormulario = false;
$enderecoEdicao = [
    'id' => 0,
    'cep' => '',
    'logradouro' => '',
    'numero' => '',
    'bairro' => '',
    'cidade' => '',
    'uf' => '',
];

if (isset($_GET['editar'])) {
    $idEditar = (int) $_GET['editar'];
    $stmt = $pdo->prepare('SELECT id, cep, logradouro, numero, bairro, cidade, uf FROM enderecos WHERE id = :id');
    $stmt->execute([':id' => $idEditar]);
    $encontrado = $stmt->fetch();

    if ($encontrado) {
        $modoFormulario = true;
        $enderecoEdicao = [
            'id' => (int) $encontrado['id'],
            'cep' => (string) $encontrado['cep'],
            'logradouro' => (string) $encontrado['logradouro'],
            'numero' => (string) $encontrado['numero'],
            'bairro' => (string) $encontrado['bairro'],
            'cidade' => (string) $encontrado['cidade'],
            'uf' => (string) $encontrado['uf'],
        ];
    } else {
        $erro = 'Endereço não encontrado.';
    }
} elseif (($_GET['acao'] ?? '') === 'novo') {
    $modoFormulario = true;
}

if ($erro !== '' && ($_POST['acao'] ?? '') === 'salvar') {
    $modoFormulario = true;
    $enderecoEdicao = [
        'id' => (int) ($_POST['id'] ?? 0),
        'cep' => (string) ($_POST['cep'] ?? ''),
        'logradouro' => (string) ($_POST['logradouro'] ?? ''),
        'numero' => (string) ($_POST['numero'] ?? ''),
        'bairro' => (string) ($_POST['bairro'] ?? ''),
        'cidade' => (string) ($_POST['cidade'] ?? ''),
        'uf' => (string) ($_POST['uf'] ?? ''),
    ];
}

$enderecos = [];
if (!$modoFormulario) {
    $enderecos = $pdo->query('SELECT id, cep, logradouro, numero, bairro, cidade, uf FROM enderecos ORDER BY id ASC')->fetchAll();
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($erro !== ''): ?>
    <div class="mb-6 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
        <?= htmlspecialchars($erro) ?>
    </div>
<?php endif; ?>

<?php
$mensagemSucesso = match ($sucesso) {
    'cadastrado' => 'Endereço cadastrado com sucesso.',
    'editado' => 'Endereço atualizado com sucesso.',
    'excluido' => 'Endereço excluído com sucesso.',
    default => '',
};
?>
<?php if ($mensagemSucesso !== ''): ?>
    <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
        <?= htmlspecialchars($mensagemSucesso) ?>
    </div>
<?php endif; ?>

<?php if ($modoFormulario): ?>
    <?php $ehEdicao = ((int) $enderecoEdicao['id'] > 0); ?>

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-slate-400 mb-3">
        <a href="enderecos.php" class="hover:text-slate-600 transition">Endereços</a>
        <span>/</span>
        <span class="text-slate-600 font-medium"><?= $ehEdicao ? 'Editar endereço' : 'Novo endereço' ?></span>
    </nav>

    <!-- Cabeçalho do formulário -->
    <div class="mb-7">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
            <?= $ehEdicao ? 'Editar Endereço' : 'Cadastrar Endereço' ?>
        </h1>
        <p class="text-sm text-slate-500 mt-1">Cadastre um ponto que poderá ser usado como origem ou destino.</p>
    </div>

    <!-- Card do formulário -->
    <form method="POST" action="enderecos.php" class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= (int) $enderecoEdicao['id'] ?>">

        <div class="p-6 sm:p-8 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Informações do endereço</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Informe os dados completos do local de coleta ou entrega.</p>
                </div>
                <span class="text-xs text-slate-400">* Campos obrigatórios</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="cep" class="block text-sm font-medium text-slate-700 mb-2">
                        CEP *
                    </label>
                    <input
                        type="text"
                        id="cep"
                        name="cep"
                        required
                        value="<?= htmlspecialchars($enderecoEdicao['cep']) ?>"
                        placeholder="Ex.: 01310-100"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/15"
                    >
                </div>

                <div>
                    <label for="logradouro" class="block text-sm font-medium text-slate-700 mb-2">
                        Logradouro / Rua *
                    </label>
                    <input
                        type="text"
                        id="logradouro"
                        name="logradouro"
                        required
                        value="<?= htmlspecialchars($enderecoEdicao['logradouro']) ?>"
                        placeholder="Ex.: Avenida Paulista"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/15"
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="numero" class="block text-sm font-medium text-slate-700 mb-2">
                        Número *
                    </label>
                    <input
                        type="text"
                        id="numero"
                        name="numero"
                        required
                        value="<?= htmlspecialchars($enderecoEdicao['numero']) ?>"
                        placeholder="Ex.: 1578"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/15"
                    >
                </div>

                <div>
                    <label for="bairro" class="block text-sm font-medium text-slate-700 mb-2">
                        Bairro *
                    </label>
                    <input
                        type="text"
                        id="bairro"
                        name="bairro"
                        required
                        value="<?= htmlspecialchars($enderecoEdicao['bairro']) ?>"
                        placeholder="Ex.: Bela Vista"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/15"
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="cidade" class="block text-sm font-medium text-slate-700 mb-2">
                        Cidade *
                    </label>
                    <input
                        type="text"
                        id="cidade"
                        name="cidade"
                        required
                        value="<?= htmlspecialchars($enderecoEdicao['cidade']) ?>"
                        placeholder="Ex.: São Paulo"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/15"
                    >
                </div>

                <div>
                    <label for="uf" class="block text-sm font-medium text-slate-700 mb-2">
                        UF (Estado) *
                    </label>
                    <select
                        id="uf"
                        name="uf"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/15"
                    >
                        <option value="">Selecione o estado</option>
                        <?php foreach ($ufs as $sigla): ?>
                            <option value="<?= $sigla ?>" <?= ($enderecoEdicao['uf'] === $sigla) ? 'selected' : '' ?>>
                                <?= $sigla ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-slate-50/70 border-t border-slate-200 px-6 sm:px-8 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <p class="text-xs text-slate-400">O CEP pode ser digitado livremente, com ou sem hífen.</p>
            <div class="flex items-center justify-end gap-3">
                <a
                    href="enderecos.php"
                    class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50 transition"
                >
                    Cancelar
                </a>
                <button
                    type="submit"
                    class="px-5 py-2 rounded-lg bg-blue-600 text-sm font-medium text-white hover:bg-blue-700 transition cursor-pointer"
                >
                    <?= $ehEdicao ? 'Atualizar Endereço' : 'Salvar Endereço' ?>
                </button>
            </div>
        </div>
    </form>

    <p class="text-xs text-slate-400 mt-4">
        Este endereço ficará disponível para seleção ao gerar um frete.
    </p>

<?php else: ?>
    <!-- Cabeçalho da listagem -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-7">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Endereços (Origem e Destino)</h1>
            <p class="text-sm text-slate-500 mt-1">Organize os pontos de coleta e entrega da sua operação.</p>
        </div>
        <a
            href="enderecos.php?acao=novo"
            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 text-sm font-medium text-white hover:bg-blue-700 transition self-start"
        >
            <span>+</span>
            <span>Novo Endereço</span>
        </a>
    </div>

    <!-- Card da tabela -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-100">
            <div class="relative w-full sm:w-80">
                <input
                    type="text"
                    id="buscaEndereco"
                    placeholder="Buscar por CEP, rua ou cidade..."
                    class="w-full rounded-lg border border-slate-300 pl-3.5 pr-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/15"
                >
            </div>
            <span id="contadorEnderecos" class="text-xs text-slate-400">
                <?= count($enderecos) ?> <?= count($enderecos) === 1 ? 'endereço cadastrado' : 'endereços cadastrados' ?>
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-6">ID</th>
                        <th class="py-3.5 px-6">CEP</th>
                        <th class="py-3.5 px-6">Logradouro</th>
                        <th class="py-3.5 px-6">Número</th>
                        <th class="py-3.5 px-6">Cidade/UF</th>
                        <th class="py-3.5 px-6 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if (empty($enderecos)): ?>
                        <tr>
                            <td colspan="6" class="py-10 px-6 text-center text-slate-400">
                                Nenhum endereço cadastrado ainda. Clique em <strong>+ Novo Endereço</strong> para adicionar.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($enderecos as $endereco):
                            $idFormatado = str_pad((string) $endereco['id'], 3, '0', STR_PAD_LEFT);
                            $cidadeUf = $endereco['cidade'] . ' / ' . $endereco['uf'];
                            $textoBusca = mb_strtolower(
                                $idFormatado . ' ' . $endereco['id'] . ' ' . $endereco['cep'] . ' ' .
                                $endereco['logradouro'] . ' ' . $endereco['bairro'] . ' ' . $cidadeUf
                            );
                        ?>
                            <tr
                                class="linha-endereco hover:bg-slate-50/70 transition"
                                data-busca="<?= htmlspecialchars($textoBusca) ?>"
                            >
                                <td class="py-4 px-6 text-slate-400 font-mono text-xs"><?= htmlspecialchars($idFormatado) ?></td>
                                <td class="py-4 px-6 text-slate-600"><?= htmlspecialchars($endereco['cep']) ?></td>
                                <td class="py-4 px-6 font-medium text-slate-900"><?= htmlspecialchars($endereco['logradouro']) ?></td>
                                <td class="py-4 px-6 text-slate-600"><?= htmlspecialchars($endereco['numero']) ?></td>
                                <td class="py-4 px-6 text-slate-600"><?= htmlspecialchars($cidadeUf) ?></td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center justify-end gap-3">
                                        <a
                                            href="enderecos.php?editar=<?= (int) $endereco['id'] ?>"
                                            title="Editar endereço"
                                            class="text-slate-400 hover:text-blue-600 transition"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                            </svg>
                                        </a>

                                        <form
                                            method="POST"
                                            action="enderecos.php"
                                            onsubmit="return confirm('Deseja realmente excluir este endereço?');"
                                            class="inline"
                                        >
                                            <input type="hidden" name="acao" value="excluir">
                                            <input type="hidden" name="id" value="<?= (int) $endereco['id'] ?>">
                                            <button
                                                type="submit"
                                                title="Excluir endereço"
                                                class="text-red-400 hover:text-red-600 transition cursor-pointer"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
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
            const inputBusca = document.getElementById('buscaEndereco');
            const linhas = document.querySelectorAll('.linha-endereco');
            const contador = document.getElementById('contadorEnderecos');

            if (!inputBusca || linhas.length === 0) return;

            inputBusca.addEventListener('input', function () {
                const termo = this.value.trim().toLowerCase();
                let visiveis = 0;

                linhas.forEach((linha) => {
                    const texto = linha.getAttribute('data-busca') || '';
                    const corresponde = termo === '' || texto.includes(termo);
                    linha.style.display = corresponde ? '' : 'none';
                    if (corresponde) visiveis++;
                });

                if (contador) {
                    contador.textContent = visiveis === 1
                        ? '1 endereço encontrado'
                        : `${visiveis} endereços encontrados`;
                }
            });
        })();
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>