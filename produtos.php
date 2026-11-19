<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';

$tituloPagina = 'Produtos';
$pdo = getDb();

$erro = '';
$sucesso = $_GET['sucesso'] ?? '';

// Exclusão de produto via POST (mais seguro que GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
    $idExcluir = (int) ($_POST['id'] ?? 0);
    if ($idExcluir > 0) {
        try {
            $stmt = $pdo->prepare('DELETE FROM produtos WHERE id = :id');
            $stmt->execute([':id' => $idExcluir]);
            header('Location: produtos.php?sucesso=excluido');
            exit;
        } catch (PDOException $e) {
            $erro = 'Não foi possível excluir este produto porque ele está vinculado a um frete.';
        }
    }
}

// Criação ou edição de produto via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar') {
    $id = (int) ($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');

    // Aceita vírgula ou ponto nos campos numéricos
    $precoRaw = str_replace(',', '.', trim($_POST['preco'] ?? '0'));
    $pesoRaw = str_replace(',', '.', trim($_POST['peso_kg'] ?? '0'));

    $preco = is_numeric($precoRaw) ? (float) $precoRaw : -1;
    $pesoKg = is_numeric($pesoRaw) ? (float) $pesoRaw : -1;

    if ($nome === '' || $preco < 0 || $pesoKg < 0) {
        $erro = 'Preencha o nome do produto e informe preço e peso válidos (maiores ou iguais a zero).';
    } else {
        if ($id > 0) {
            $stmt = $pdo->prepare('
                UPDATE produtos
                SET nome = :nome, preco = :preco, peso_kg = :peso_kg
                WHERE id = :id
            ');
            $stmt->execute([
                ':nome' => $nome,
                ':preco' => $preco,
                ':peso_kg' => $pesoKg,
                ':id' => $id,
            ]);
            header('Location: produtos.php?sucesso=editado');
            exit;
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO produtos (nome, preco, peso_kg)
                VALUES (:nome, :preco, :peso_kg)
            ');
            $stmt->execute([
                ':nome' => $nome,
                ':preco' => $preco,
                ':peso_kg' => $pesoKg,
            ]);
            header('Location: produtos.php?sucesso=cadastrado');
            exit;
        }
    }
}

// Determina se está na tela de formulário (novo ou editar) ou na listagem
$modoFormulario = false;
$produtoEdicao = [
    'id' => 0,
    'nome' => '',
    'preco' => '',
    'peso_kg' => '',
];

if (isset($_GET['editar'])) {
    $idEditar = (int) $_GET['editar'];
    $stmt = $pdo->prepare('SELECT id, nome, preco, peso_kg FROM produtos WHERE id = :id');
    $stmt->execute([':id' => $idEditar]);
    $encontrado = $stmt->fetch();

    if ($encontrado) {
        $modoFormulario = true;
        $produtoEdicao = [
            'id' => (int) $encontrado['id'],
            'nome' => (string) $encontrado['nome'],
            'preco' => number_format((float) $encontrado['preco'], 2, ',', ''),
            'peso_kg' => number_format((float) $encontrado['peso_kg'], 2, ',', ''),
        ];
    } else {
        $erro = 'Produto não encontrado.';
    }
} elseif (($_GET['acao'] ?? '') === 'novo') {
    $modoFormulario = true;
}

// Se houve erro no POST, mantém o que o usuário digitou no formulário
if ($erro !== '' && ($_POST['acao'] ?? '') === 'salvar') {
    $modoFormulario = true;
    $produtoEdicao = [
        'id' => (int) ($_POST['id'] ?? 0),
        'nome' => (string) ($_POST['nome'] ?? ''),
        'preco' => (string) ($_POST['preco'] ?? ''),
        'peso_kg' => (string) ($_POST['peso_kg'] ?? ''),
    ];
}

$produtos = [];
if (!$modoFormulario) {
    $produtos = $pdo->query('SELECT id, nome, preco, peso_kg FROM produtos ORDER BY id ASC')->fetchAll();
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
    'cadastrado' => 'Produto cadastrado com sucesso.',
    'editado' => 'Produto atualizado com sucesso.',
    'excluido' => 'Produto excluído com sucesso.',
    default => '',
};
?>
<?php if ($mensagemSucesso !== ''): ?>
    <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
        <?= htmlspecialchars($mensagemSucesso) ?>
    </div>
<?php endif; ?>

<?php if ($modoFormulario): ?>
    <?php $ehEdicao = ((int) $produtoEdicao['id'] > 0); ?>

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-slate-400 mb-3">
        <a href="produtos.php" class="hover:text-slate-600 transition">Produtos</a>
        <span>/</span>
        <span class="text-slate-600 font-medium"><?= $ehEdicao ? 'Editar produto' : 'Novo produto' ?></span>
    </nav>

    <!-- Cabeçalho do formulário -->
    <div class="mb-7">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
            <?= $ehEdicao ? 'Editar Produto' : 'Cadastrar Novo Produto' ?>
        </h1>
        <p class="text-sm text-slate-500 mt-1">
            <?= $ehEdicao
                ? 'Atualize as informações do produto selecionado.'
                : 'Adicione um produto ao catálogo para utilizá-lo nos seus fretes.' ?>
        </p>
    </div>

    <!-- Card do formulário -->
    <form method="POST" action="produtos.php" class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= (int) $produtoEdicao['id'] ?>">

        <div class="p-6 sm:p-8 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Informações do produto</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Preencha o nome, o preço e o peso do produto.</p>
                </div>
                <span class="text-xs text-slate-400">* Campos obrigatórios</span>
            </div>

            <div>
                <label for="nome" class="block text-sm font-medium text-slate-700 mb-2">
                    Nome do Produto *
                </label>
                <input
                    type="text"
                    id="nome"
                    name="nome"
                    required
                    value="<?= htmlspecialchars($produtoEdicao['nome']) ?>"
                    placeholder="Ex.: Caixa de papelão reforçada"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/15"
                >
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="preco" class="block text-sm font-medium text-slate-700 mb-2">
                        Preço (R$) *
                    </label>
                    <input
                        type="text"
                        id="preco"
                        name="preco"
                        required
                        inputmode="decimal"
                        value="<?= htmlspecialchars($produtoEdicao['preco']) ?>"
                        placeholder="0,00"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/15"
                    >
                    <p class="text-xs text-slate-400 mt-1.5">Informe o valor unitário do produto.</p>
                </div>

                <div>
                    <label for="peso_kg" class="block text-sm font-medium text-slate-700 mb-2">
                        Peso (kg) *
                    </label>
                    <input
                        type="text"
                        id="peso_kg"
                        name="peso_kg"
                        required
                        inputmode="decimal"
                        value="<?= htmlspecialchars($produtoEdicao['peso_kg']) ?>"
                        placeholder="0,00"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/15"
                    >
                    <p class="text-xs text-slate-400 mt-1.5">Informe o peso em quilogramas.</p>
                </div>
            </div>
        </div>

        <div class="bg-slate-50/70 border-t border-slate-200 px-6 sm:px-8 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <p class="text-xs text-slate-400">Os dados poderão ser editados após o cadastro.</p>
            <div class="flex items-center justify-end gap-3">
                <a
                    href="produtos.php"
                    class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50 transition"
                >
                    Cancelar
                </a>
                <button
                    type="submit"
                    class="px-5 py-2 rounded-lg bg-blue-600 text-sm font-medium text-white hover:bg-blue-700 transition cursor-pointer"
                >
                    <?= $ehEdicao ? 'Atualizar Produto' : 'Salvar Produto' ?>
                </button>
            </div>
        </div>
    </form>

    <p class="text-xs text-slate-400 mt-4">
        Após salvar, o produto ficará disponível na listagem de produtos.
    </p>

<?php else: ?>
    <!-- Cabeçalho da listagem -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-7">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Produtos</h1>
            <p class="text-sm text-slate-500 mt-1">Gerencie os produtos utilizados nas suas operações logísticas.</p>
        </div>
        <a
            href="produtos.php?acao=novo"
            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 text-sm font-medium text-white hover:bg-blue-700 transition self-start"
        >
            <span>+</span>
            <span>Novo Produto</span>
        </a>
    </div>

    <!-- Card da tabela -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-100">
            <div class="relative w-full sm:w-80">
                <input
                    type="text"
                    id="buscaProduto"
                    placeholder="Buscar por nome ou ID..."
                    class="w-full rounded-lg border border-slate-300 pl-3.5 pr-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/15"
                >
            </div>
            <span id="contadorProdutos" class="text-xs text-slate-400">
                <?= count($produtos) ?> <?= count($produtos) === 1 ? 'produto cadastrado' : 'produtos cadastrados' ?>
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-6">ID</th>
                        <th class="py-3.5 px-6">Nome do Produto</th>
                        <th class="py-3.5 px-6">Preço (R$)</th>
                        <th class="py-3.5 px-6">Peso (kg)</th>
                        <th class="py-3.5 px-6 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody id="tabelaProdutosBody" class="divide-y divide-slate-100 text-sm">
                    <?php if (empty($produtos)): ?>
                        <tr id="linhaVazia">
                            <td colspan="5" class="py-10 px-6 text-center text-slate-400">
                                Nenhum produto cadastrado ainda. Clique em <strong>+ Novo Produto</strong> para adicionar.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($produtos as $produto):
                            $idFormatado = str_pad((string) $produto['id'], 3, '0', STR_PAD_LEFT);
                            $precoFormatado = 'R$ ' . number_format((float) $produto['preco'], 2, ',', '.');
                            $pesoFormatado = number_format((float) $produto['peso_kg'], 2, ',', '.') . ' kg';
                        ?>
                            <tr
                                class="linha-produto hover:bg-slate-50/70 transition"
                                data-busca="<?= htmlspecialchars(mb_strtolower($idFormatado . ' ' . $produto['id'] . ' ' . $produto['nome'])) ?>"
                            >
                                <td class="py-4 px-6 text-slate-400 font-mono text-xs"><?= htmlspecialchars($idFormatado) ?></td>
                                <td class="py-4 px-6 font-medium text-slate-900"><?= htmlspecialchars($produto['nome']) ?></td>
                                <td class="py-4 px-6 text-slate-700"><?= htmlspecialchars($precoFormatado) ?></td>
                                <td class="py-4 px-6 text-slate-600"><?= htmlspecialchars($pesoFormatado) ?></td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center justify-end gap-3">
                                        <a
                                            href="produtos.php?editar=<?= (int) $produto['id'] ?>"
                                            title="Editar produto"
                                            class="text-slate-400 hover:text-blue-600 transition"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                            </svg>
                                        </a>

                                        <form
                                            method="POST"
                                            action="produtos.php"
                                            onsubmit="return confirm('Deseja realmente excluir este produto?');"
                                            class="inline"
                                        >
                                            <input type="hidden" name="acao" value="excluir">
                                            <input type="hidden" name="id" value="<?= (int) $produto['id'] ?>">
                                            <button
                                                type="submit"
                                                title="Excluir produto"
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
            const inputBusca = document.getElementById('buscaProduto');
            const linhas = document.querySelectorAll('.linha-produto');
            const contador = document.getElementById('contadorProdutos');

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
                        ? '1 produto encontrado'
                        : `${visiveis} produtos encontrados`;
                }
            });
        })();
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>