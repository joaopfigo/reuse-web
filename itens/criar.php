<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/helpers/validar.php';
require_once __DIR__ . '/../app/repositories/ItemRepo.php';

$usuario = exigir_login();
$categorias = ItemRepo::categorias();
$erro = '';
$sucesso = '';
$categoriaCosmeticosId = 3;
$mensagemBioseguranca = 'Condição fixada em "Novo". Por questões de biosegurança, cosméticos e itens de beleza só podem ser publicados novos, lacrados e sem uso.';

$dados = [
    'categoria_id' => (int) ($_POST['categoria_id'] ?? ($categorias[0]['id'] ?? 0)),
    'titulo' => trim((string) ($_POST['titulo'] ?? '')),
    'descricao' => trim((string) ($_POST['descricao'] ?? '')),
    'condicao' => (string) ($_POST['condicao'] ?? 'novo'),
    'pontos' => (int) ($_POST['pontos'] ?? 5),
    'bairro' => trim((string) ($_POST['bairro'] ?? $usuario['bairro'])),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf_post();

    try {
        $dados['doadora_id'] = (int) $usuario['id'];

        if (!$dados['titulo'] || !$dados['descricao'] || !$dados['categoria_id'] || !$dados['bairro'] || $dados['pontos'] <= 0) {
            throw new RuntimeException('Preencha todos os campos obrigatórios.');
        }

        if ((int) $dados['categoria_id'] === $categoriaCosmeticosId) {
            $dados['condicao'] = 'novo';
        }

        $fotoPaths = salvar_uploads_item($_FILES['fotos'] ?? []);
        ItemRepo::criar($dados, $fotoPaths);
        $sucesso = 'Item cadastrado com sucesso.';
        $dados['titulo'] = '';
        $dados['descricao'] = '';
        $dados['condicao'] = 'novo';
        $dados['pontos'] = 5;
        $dados['bairro'] = $usuario['bairro'];
    } catch (Throwable $e) {
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Cadastrar item</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/experience.css?v=20260624">
</head>
<body>
    <?php render_topbar($usuario); ?>

    <main class="container ops-page">
        <section class="ops-hero compact">
            <div class="ops-hero-main">
                <span class="ops-kicker">Nova publicação</span>
                <h1 class="ops-title">Cadastrar item</h1>
                <p class="ops-copy">Prepare uma publicação clara, objetiva e segura para facilitar a reserva e a retirada do item.</p>
            </div>
        </section>

        <section class="form-layout">
            <form method="post" enctype="multipart/form-data" class="form-main">
                <section class="surface-panel">
                    <div class="section-header">
                        <h2>Informações do item</h2>
                        <p>Informe somente os dados que ajudam a comunidade a entender o estado do item e a organizar a retirada.</p>
                    </div>

                    <?= csrf_input() ?>

                    <?php if ($erro): ?><div class="alert error"><?= e($erro) ?></div><?php endif; ?>
                    <?php if ($sucesso): ?><div class="alert success"><?= e($sucesso) ?></div><?php endif; ?>

                    <div class="grid-2">
                        <label>
                            Título
                            <input type="text" name="titulo" value="<?= e($dados['titulo']) ?>" required>
                        </label>

                        <label>
                            Pontos
                            <input type="number" name="pontos" min="1" value="<?= (int) $dados['pontos'] ?>" required>
                        </label>
                    </div>

                    <label>
                        Descrição
                        <textarea name="descricao" required><?= e($dados['descricao']) ?></textarea>
                        <small class="muted">Conte o estado do item, tamanho, marca ou qualquer detalhe útil para a retirada.</small>
                    </label>

                    <div class="grid-2">
                        <label>
                            Categoria
                            <select name="categoria_id" required data-category-select data-cosmetic-category-id="<?= $categoriaCosmeticosId ?>">
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= (int) $categoria['id'] ?>" <?= (int) $categoria['id'] === (int) $dados['categoria_id'] ? 'selected' : '' ?>>
                                        <?= e($categoria['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label>
                            Condição
                            <select name="condicao" required data-condition-select>
                                <option value="novo" <?= $dados['condicao'] === 'novo' ? 'selected' : '' ?>>Novo</option>
                                <option value="seminovo" <?= $dados['condicao'] === 'seminovo' ? 'selected' : '' ?>>Seminovo</option>
                                <option value="usado_bom" <?= $dados['condicao'] === 'usado_bom' ? 'selected' : '' ?>>Usado em bom estado</option>
                                <option value="usado_regular" <?= $dados['condicao'] === 'usado_regular' ? 'selected' : '' ?>>Usado regular</option>
                            </select>
                            <small class="muted soft-note" data-cosmetic-condition-note hidden><?= e($mensagemBioseguranca) ?></small>
                        </label>
                    </div>

                    <label>
                        Bairro aproximado
                        <input type="text" name="bairro" value="<?= e($dados['bairro']) ?>" required>
                    </label>

                    <label>
                        Fotos do item (até 5 imagens JPG, PNG ou WEBP de 2 MB cada)
                        <input type="file" name="fotos[]" accept="image/jpeg,image/png,image/webp" multiple data-image-preview-input>
                    </label>
                    <div class="image-preview" data-image-preview hidden></div>

                    <div class="list-card-actions">
                        <button class="btn primary" type="submit">Publicar</button>
                    </div>
                </section>
            </form>

            <aside class="form-side">
                <section class="surface-panel">
                    <div class="section-header">
                        <h2>Antes de publicar</h2>
                        <p>Use estas orientações para deixar o anúncio mais claro e seguro.</p>
                    </div>
                    <ul class="guideline-list">
                        <li>Informe somente o bairro ou região aproximada, nunca o endereço completo.</li>
                        <li>Use uma descrição com detalhes reais do estado do item.</li>
                        <li>Uma foto boa reduz dúvidas e melhora a chance de reserva.</li>
                    </ul>
                </section>
            </aside>
        </section>
    </main>
</body>
</html>
