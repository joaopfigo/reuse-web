<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/ItemRepo.php';

$usuario = exigir_login();
$categorias = ItemRepo::categorias();
$item = ItemRepo::buscarPorId((int) ($_GET['id'] ?? 0));
$erro = '';
$sucesso = '';
$categoriaCosmeticosId = 3;
$mensagemBioseguranca = 'Por questões de biosegurança, cosméticos e itens de beleza só podem ser publicados quando estiverem novos, lacrados e sem uso. Para proteger quem recebe o item, essa categoria aceita apenas a condição "Novo".';

if (!$item || (int) $item['doadora_id'] !== (int) $usuario['id']) {
    $erro = 'Item não encontrado ou sem permissão para editar.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf_post();

    $dados = [
        'categoria_id' => (int) ($_POST['categoria_id'] ?? 0),
        'titulo' => trim((string) ($_POST['titulo'] ?? '')),
        'descricao' => trim((string) ($_POST['descricao'] ?? '')),
        'condicao' => (string) ($_POST['condicao'] ?? ''),
        'pontos' => (int) ($_POST['pontos'] ?? 0),
        'bairro' => trim((string) ($_POST['bairro'] ?? '')),
    ];

    if (!$dados['titulo'] || !$dados['descricao'] || !$dados['categoria_id'] || !$dados['bairro'] || $dados['pontos'] <= 0) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } else {
        if ((int) $dados['categoria_id'] === $categoriaCosmeticosId) {
            $dados['condicao'] = 'novo';
        }
        ItemRepo::atualizar((int) $item['id'], (int) $usuario['id'], $dados);
        $item = ItemRepo::buscarPorId((int) $item['id']);
        $sucesso = 'Item atualizado.';
    }
}

$condicaoAtual = $item
    ? (((int) $item['categoria_id'] === $categoriaCosmeticosId) ? 'novo' : (string) $item['condicao'])
    : 'novo';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Editar item</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/experience.css?v=20260615">
</head>
<body>
    <?php render_topbar($usuario); ?>

    <main class="container ops-page">
        <section class="ops-hero compact">
            <div class="ops-hero-main">
                <span class="ops-kicker">Edição de anúncio</span>
                <h1 class="ops-title">Editar item</h1>
                <p class="ops-copy">Ajuste as informações do anúncio para manter a publicação clara enquanto o item ainda estiver disponível ou pausado.</p>
            </div>
        </section>

        <section class="form-layout">
            <form method="post" class="form-main">
                <section class="surface-panel">
                    <div class="section-header">
                        <h2>Atualizar informações</h2>
                        <p>Revise os dados do item antes de salvar para evitar desencontro de expectativa na retirada.</p>
                    </div>

                    <?= csrf_input() ?>
                    <?php if ($erro): ?><div class="alert error"><?= e($erro) ?></div><?php endif; ?>
                    <?php if ($sucesso): ?><div class="alert success"><?= e($sucesso) ?></div><?php endif; ?>

                    <?php if ($item && (int) $item['doadora_id'] === (int) $usuario['id']): ?>
                        <div class="grid-2">
                            <label>
                                Título
                                <input type="text" name="titulo" value="<?= e($item['titulo']) ?>" required>
                            </label>
                            <label>
                                Pontos
                                <input type="number" name="pontos" value="<?= (int) $item['pontos'] ?>" min="1" required>
                            </label>
                        </div>

                        <label>
                            Descrição
                            <textarea name="descricao" required><?= e($item['descricao']) ?></textarea>
                        </label>

                        <div class="grid-2">
                            <label>
                                Categoria
                                <select name="categoria_id" required data-category-select data-cosmetic-category-id="<?= $categoriaCosmeticosId ?>">
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?= (int) $categoria['id'] ?>" <?= (int) $categoria['id'] === (int) $item['categoria_id'] ? 'selected' : '' ?>>
                                            <?= e($categoria['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>
                                Condição
                                <select name="condicao" required data-condition-select>
                                    <option value="novo" <?= $condicaoAtual === 'novo' ? 'selected' : '' ?>>Novo</option>
                                    <option value="seminovo" <?= $condicaoAtual === 'seminovo' ? 'selected' : '' ?>>Seminovo</option>
                                    <option value="usado_bom" <?= $condicaoAtual === 'usado_bom' ? 'selected' : '' ?>>Usado em bom estado</option>
                                    <option value="usado_regular" <?= $condicaoAtual === 'usado_regular' ? 'selected' : '' ?>>Usado regular</option>
                                </select>
                                <small class="muted soft-note" data-cosmetic-condition-note hidden><?= e($mensagemBioseguranca) ?></small>
                            </label>
                        </div>

                        <label>
                            Bairro aproximado
                            <input type="text" name="bairro" value="<?= e($item['bairro']) ?>" required>
                        </label>

                        <div class="list-card-actions">
                            <button class="btn primary" type="submit">Salvar alterações</button>
                            <a class="btn" href="detalhe.php?id=<?= (int) $item['id'] ?>">Voltar</a>
                        </div>
                    <?php endif; ?>
                </section>
            </form>

            <aside class="form-side">
                <section class="surface-panel">
                    <div class="section-header">
                        <h2>Revisão rápida</h2>
                        <p>Antes de salvar, confira se o anúncio continua coerente com o item real.</p>
                    </div>
                    <ul class="guideline-list">
                        <li>Mantenha os pontos alinhados com o valor percebido pela comunidade.</li>
                        <li>Atualize a condição se o item mudou desde a primeira publicação.</li>
                        <li>Prefira descrições honestas a descrições muito curtas ou vagas.</li>
                    </ul>
                </section>
            </aside>
        </section>
    </main>
</body>
</html>
