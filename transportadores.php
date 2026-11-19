<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';

$tituloPagina = 'Transportadores';
$pdo = getDb();

$erro = '';
$sucesso = $_GET['sucesso'] ?? '';

// Exclusão de transportador via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
    $idExcluir = (int) ($_POST['id'] ?? 0);
    if ($idExcluir > 0) {
        try {
            $stmt = $pdo->prepare('DELETE FROM transportadores WHERE id = :id');
            $stmt->execute([':id' => $idExcluir]);
            header('Location: transportadores.php?sucesso=excluido');
            exit;
        } catch (PDOException $e) {
            $erro = 'Não foi possível excluir este transportador porque ele está vinculado a um frete.';
        }
    }
}

// Criação ou edição de transportador via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar') {
    $id = (int) ($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $documento = trim($_POST['documento'] ?? '');
    $veiculo = trim($_POST['veiculo'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');

    if ($nome === '' || $documento === '' || $veiculo === '' || $telefone === '') {
        $erro = 'Preencha todos os campos obrigatórios do transportador.';
    } else {
        if ($id > 0) {
            $stmt = $pdo->prepare('
                UPDATE transportadores
                SET nome = :nome, documento = :documento, veiculo = :veiculo, telefone = :telefone
                WHERE id = :id
            ');
            $stmt->execute([
                ':nome' => $nome,
                ':documento' => $documento,
                ':veiculo' => $veiculo,
                ':telefone' => $telefone,
                ':id' => $id,
            ]);
            header('Location: transportadores.php?sucesso=editado');
            exit;
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO transportadores (nome, documento, veiculo, telefone)
                VALUES (:nome, :documento, :veiculo, :telefone)
            ');
            $stmt->execute([
                ':nome' => $nome,
                ':documento' => $documento,
                ':veiculo' => $veiculo,
                ':telefone' => $telefone,
            ]);
            header('Location: transportadores.php?sucesso=cadastrado');
            exit;
        }
    }
}

$modoFormulario = false;
$transportadorEdicao = [
    'id' => 0,
    'nome' => '',
    'documento' => '',
    'veiculo' => '',
    'telefone' => '',
];

if (isset($_GET['editar'])) {
    $idEditar = (int) $_GET['editar'];
    $stmt = $pdo->prepare('SELECT id, nome, documento, veiculo, telefone FROM transportadores WHERE id = :id');
    $stmt->execute([':id' => $idEditar]);
    $encontrado = $stmt->fetch();

    if ($encontrado) {
        $modoFormulario = true;
        $transportadorEdicao = [
            'id' => (int) $encontrado['id'],
            'nome' => (string) $encontrado['nome'],
            'documento' => (string) $encontrado['documento'],
            'veiculo' => (string) $encontrado['veiculo'],
            'telefone' => (string) $encontrado['telefone'],
        ];
    } else {
        $erro = 'Transportador não encontrado.';
    }
} elseif (($_GET['acao'] ?? '') === 'novo') {
    $modoFormulario = true;
}

if ($erro !== '' && ($_POST['acao'] ?? '') === 'salvar') {
    $modoFormulario = true;
    $transportadorEdicao = [
        'id' => (int) ($_POST['id'] ?? 0),
        'nome' => (string) ($_POST['nome'] ?? ''),
        'documento' => (string) ($_POST['documento'] ?? ''),
        'veiculo' => (string) ($_POST['veiculo'] ?? ''),
        'telefone' => (string) ($_POST['telefone'] ?? ''),
    ];
}

$transportadores = [];
if (!$modoFormulario) {
    $transportadores = $pdo->query('SELECT id, nome, documento, veiculo, telefone FROM transportadores ORDER BY id ASC')->fetchAll();
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
    'cadastrado' => 'Transportador cadastrado com sucesso.',
    'editado' => 'Transportador atualizado com sucesso.',
    'excluido' => 'Transportador excluído com sucesso.',
    default => '',
};
?>
<?php if ($mensagemSucesso !== ''): ?>
    <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
        <?= htmlspecialchars($mensagemSucesso) ?>
    </div>
<?php endif; ?>

<?php if ($modoFormulario): ?>
    <?php $ehEdicao = ((int) $transportadorEdicao['id'] > 0); ?>

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-slate-400 mb-3">
        <a href="transportadores.php" class="hover:text-slate-600 transition">Transportadores</a>
        <span>/</span>
        <span class="text-slate-600 font-medium"><?= $ehEdicao ? 'Editar transportador' : 'Novo transportador' ?></span>
    </nav>

    <!-- Cabeçalho do formulário -->
    <div class="mb-7">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
            <?= $ehEdicao ? 'Editar Transportador' : 'Cadastrar Transportador' ?>
        </h1>
        <p class="text-sm text-slate-500 mt-1">Adicione um motorista ou uma empresa para realizar seus fretes.</p>
    </div>

    <!-- Card do formulário -->
    <form method="POST" action="transportadores.php" class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= (int) $transportadorEdicao['id'] ?>">

        <div class="p-6 sm:p-8 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Informações do transportador</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Preencha os dados de identificação, veículo e contato.</p>
                </div>
                <span class="text-xs text-slate-400">* Campos obrigatórios</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="nome" class="block text-sm font-medium text-slate-700 mb-2">
                        Nome do Motorista ou Empresa *
                    </label>
                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        required
                        value="<?= htmlspecialchars($transportadorEdicao['nome']) ?>"
                        placeholder="Ex.: TransLog"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/15"
                    >
                </div>

                <div>
                    <label for="documento" class="block text-sm font-medium text-slate-700 mb-2">
                        CPF ou CNPJ *
                    </label>
                    <input
                        type="text"
                        id="documento"
                        name="documento"
                        required
                        value="<?= htmlspecialchars($transportadorEdicao['documento']) ?>"
                        placeholder="Digite o CPF ou CNPJ"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/15"
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="veiculo" class="block text-sm font-medium text-slate-700 mb-2">
                        Tipo de Veículo *
                    </label>
                    <input
                        type="text"
                        id="veiculo"
                        name="veiculo"
                        required
                        value="<?= htmlspecialchars($transportadorEdicao['veiculo']) ?>"
                        placeholder="Ex.: Carreta, Van, Fiorino"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/15"
                    >
                    <p class="text-xs text-slate-400 mt-1.5">Informe o veículo utilizado no transporte.</p>
                </div>

                <div>
                    <label for="telefone" class="block text-sm font-medium text-slate-700 mb-2">
                        Telefone / Contato *
                    </label>
                    <input
                        type="text"
                        id="telefone"
                        name="telefone"
                        required
                        value="<?= htmlspecialchars($transportadorEdicao['telefone']) ?>"
                        placeholder="Ex.: (11) 3333-0101"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/15"
                    >
                    <p class="text-xs text-slate-400 mt-1.5">Inclua o DDD do telefone.</p>
                </div>
            </div>
        </div>

        <div class="bg-slate-50/70 border-t border-slate-200 px-6 sm:px-8 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <p class="text-xs text-slate-400">Os dados poderão ser editados após o cadastro.</p>
            <div class="flex items-center justify-end gap-3">
                <a
                    href="transportadores.php"
                    class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50 transition"
                >
                    Cancelar
                </a>
                <button
                    type="submit"
                    class="px-5 py-2 rounded-lg bg-blue-600 text-sm font-medium text-white hover:bg-blue-700 transition cursor-pointer"
                >
                    <?= $ehEdicao ? 'Atualizar Transportador' : 'Salvar Transportador' ?>
                </button>
            </div>
        </div>
    </form>

    <p class="text-xs text-slate-400 mt-4">
        Após salvar, o transportador estará disponível para seleção ao gerar um frete.
    </p>

<?php else: ?>
    <!-- Cabeçalho da listagem -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-7">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Transportadores</h1>
            <p class="text-sm text-slate-500 mt-1">Gerencie os motoristas e empresas parceiras para seus fretes.</p>
        </div>
        <a
            href="transportadores.php?acao=novo"
            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 text-sm font-medium text-white hover:bg-blue-700 transition self-start"
        >
            <span>+</span>
            <span>Novo Transportador</span>
        </a>
    </div>

    <!-- Card da tabela -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-100">
            <div class="relative w-full sm:w-80">
                <input
                    type="text"
                    id="buscaTransportador"
                    placeholder="Buscar por nome, documento ou veículo..."
                    class="w-full rounded-lg border border-slate-300 pl-3.5 pr-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/15"
                >
            </div>
            <span id="contadorTransportadores" class="text-xs text-slate-400">
                <?= count($transportadores) ?> <?= count($transportadores) === 1 ? 'transportador cadastrado' : 'transportadores cadastrados' ?>
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-6">ID</th>
                        <th class="py-3.5 px-6">Nome / Empresa</th>
                        <th class="py-3.5 px-6">CPF / CNPJ</th>
                        <th class="py-3.5 px-6">Veículo</th>
                        <th class="py-3.5 px-6">Telefone</th>
                        <th class="py-3.5 px-6 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if (empty($transportadores)): ?>
                        <tr>
                            <td colspan="6" class="py-10 px-6 text-center text-slate-400">
                                Nenhum transportador cadastrado ainda. Clique em <strong>+ Novo Transportador</strong> para adicionar.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transportadores as $t):
                            $idFormatado = str_pad((string) $t['id'], 3, '0', STR_PAD_LEFT);
                            $textoBusca = mb_strtolower(
                                $idFormatado . ' ' . $t['id'] . ' ' . $t['nome'] . ' ' .
                                $t['documento'] . ' ' . $t['veiculo'] . ' ' . $t['telefone']
                            );
                        ?>
                            <tr
                                class="linha-transportador hover:bg-slate-50/70 transition"
                                data-busca="<?= htmlspecialchars($textoBusca) ?>"
                            >
                                <td class="py-4 px-6 text-slate-400 font-mono text-xs"><?= htmlspecialchars($idFormatado) ?></td>
                                <td class="py-4 px-6 font-medium text-slate-900"><?= htmlspecialchars($t['nome']) ?></td>
                                <td class="py-4 px-6 text-slate-600"><?= htmlspecialchars($t['documento']) ?></td>
                                <td class="py-4 px-6 text-slate-600"><?= htmlspecialchars($t['veiculo']) ?></td>
                                <td class="py-4 px-6 text-slate-600"><?= htmlspecialchars($t['telefone']) ?></td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center justify-end gap-3">
                                        <a
                                            href="transportadores.php?editar=<?= (int) $t['id'] ?>"
                                            title="Editar transportador"
                                            class="text-slate-400 hover:text-blue-600 transition"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                            </svg>
                                        </a>

                                        <form
                                            method="POST"
                                            action="transportadores.php"
                                            onsubmit="return confirm('Deseja realmente excluir este transportador?');"
                                            class="inline"
                                        >
                                            <input type="hidden" name="acao" value="excluir">
                                            <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                                            <button
                                                type="submit"
                                                title="Excluir transportador"
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
            const inputBusca = document.getElementById('buscaTransportador');
            const linhas = document.querySelectorAll('.linha-transportador');
            const contador = document.getElementById('contadorTransportadores');

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
                        ? '1 transportador encontrado'
                        : `${visiveis} transportadores encontrados`;
                }
            });
        })();
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>