<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/helpers/validar.php';
require_once __DIR__ . '/../app/repositories/ItemRepo.php';

$usuario = exigir_login();
$categorias = ItemRepo::categorias();
$erro = '';
$sucesso = '';
$aviso = '';
$contaVerificada = conta_verificada($usuario);
$categoriaCosmeticosId = 3;
$mensagemBioseguranca = 'Condicao fixada em "Novo". Por questoes de bioseguranca, cosmeticos e itens de beleza so podem ser publicados novos, lacrados e sem uso.';

$dados = [
    'categoria_id' => (int) ($_POST['categoria_id'] ?? ($categorias[0]['id'] ?? 0)),
    'titulo' => trim((string) ($_POST['titulo'] ?? '')),
    'descricao' => trim((string) ($_POST['descricao'] ?? '')),
    'condicao' => (string) ($_POST['condicao'] ?? 'novo'),
    'pontos' => (int) ($_POST['pontos'] ?? 5),
    'bairro' => trim((string) ($_POST['bairro'] ?? $usuario['bairro'])),
    'cidade' => trim((string) ($_POST['cidade'] ?? $usuario['cidade'] ?? 'Belo Horizonte')),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf_post();

    try {
        if (!$contaVerificada) {
            throw new RuntimeException('Confirme seu e-mail antes de publicar itens.');
        }

        $dados['doadora_id'] = (int) $usuario['id'];

        if (!$dados['titulo'] || !$dados['categoria_id'] || !$dados['bairro'] || !$dados['cidade'] || $dados['pontos'] <= 0) {
            throw new RuntimeException('Preencha todos os campos obrigatorios.');
        }

        if ((int) $dados['categoria_id'] === $categoriaCosmeticosId) {
            $dados['condicao'] = 'novo';
        }

        $duplicidadeTexto = ItemRepo::verificarDuplicidadeTexto($dados);
        if ($duplicidadeTexto && $duplicidadeTexto['tipo'] === 'bloqueio') {
            throw new RuntimeException($duplicidadeTexto['mensagem']);
        }

        $fotoPaths = salvar_uploads_item($_FILES['fotos'] ?? []);
        $duplicidadeImagem = ItemRepo::verificarDuplicidadeImagem((int) $usuario['id'], (int) $dados['categoria_id'], $fotoPaths);
        if ($duplicidadeImagem && $duplicidadeImagem['tipo'] === 'bloqueio') {
            remover_uploads_item($fotoPaths);
            throw new RuntimeException($duplicidadeImagem['mensagem']);
        }

        ItemRepo::criar($dados, $fotoPaths);

        $sucesso = 'Item cadastrado com sucesso. A publicacao nao gera pontos automaticamente; pontos entram por entregas confirmadas ou por compra aprovada.';

        $aviso = $duplicidadeTexto['mensagem'] ?? ($duplicidadeImagem['mensagem'] ?? '');
        $dados['titulo'] = '';
        $dados['descricao'] = '';
        $dados['condicao'] = 'novo';
        $dados['pontos'] = 5;
        $dados['bairro'] = $usuario['bairro'];
        $dados['cidade'] = $usuario['cidade'] ?? 'Belo Horizonte';
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
                <span class="ops-kicker">Nova publicacao</span>
                <h1 class="ops-title">Cadastrar item</h1>
                <p class="ops-copy">Prepare uma publicacao clara, objetiva e segura para facilitar a reserva e a retirada do item.</p>
            </div>
        </section>

        <?php if (!$contaVerificada): ?>
            <div class="alert error">
                Confirme seu e-mail para publicar itens. A verificacao ajuda a reduzir contas duplicadas e aumenta a confianca da comunidade.
                <form method="post" action="<?= e(app_url('reenviar-confirmacao.php')) ?>" class="inline-form">
                    <?= csrf_input() ?>
                    <button class="btn secondary" type="submit">Reenviar confirmacao</button>
                </form>
            </div>
        <?php endif; ?>

        <section class="form-layout">
            <form method="post" enctype="multipart/form-data" class="form-main">
                <section class="surface-panel">
                    <div class="section-header">
                        <h2>Informacoes do item</h2>
                        <p>Informe somente os dados que ajudam a comunidade a entender o estado do item e a organizar a retirada.</p>
                    </div>

                    <?= csrf_input() ?>

                    <?php if ($erro): ?><div class="alert error"><?= e($erro) ?></div><?php endif; ?>
                    <?php if ($sucesso): ?><div class="alert success"><?= e($sucesso) ?></div><?php endif; ?>
                    <?php if ($aviso): ?><div class="alert warning"><?= e($aviso) ?></div><?php endif; ?>

                    <div class="grid-2">
                        <label>
                            Titulo
                            <input type="text" name="titulo" value="<?= e($dados['titulo']) ?>" required <?= !$contaVerificada ? 'disabled' : '' ?>>
                        </label>

                        <label>
                            Pontos
                            <input type="number" name="pontos" min="1" value="<?= (int) $dados['pontos'] ?>" required <?= !$contaVerificada ? 'disabled' : '' ?>>
                        </label>
                    </div>

                    <label>
                        Descricao
                        <textarea name="descricao" <?= !$contaVerificada ? 'disabled' : '' ?>><?= e($dados['descricao']) ?></textarea>
                        <small class="muted">Conte o estado do item, tamanho, marca ou qualquer detalhe util para a retirada.</small>
                    </label>

                    <div class="grid-2">
                        <label>
                            Categoria
                            <select name="categoria_id" required data-category-select data-cosmetic-category-id="<?= $categoriaCosmeticosId ?>" <?= !$contaVerificada ? 'disabled' : '' ?>>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= (int) $categoria['id'] ?>" <?= (int) $categoria['id'] === (int) $dados['categoria_id'] ? 'selected' : '' ?>>
                                        <?= e($categoria['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label>
                            Condicao
                            <select name="condicao" required data-condition-select <?= !$contaVerificada ? 'disabled' : '' ?>>
                                <option value="novo" <?= $dados['condicao'] === 'novo' ? 'selected' : '' ?>>Novo</option>
                                <option value="seminovo" <?= $dados['condicao'] === 'seminovo' ? 'selected' : '' ?>>Seminovo</option>
                                <option value="usado_bom" <?= $dados['condicao'] === 'usado_bom' ? 'selected' : '' ?>>Usado em bom estado</option>
                                <option value="usado_regular" <?= $dados['condicao'] === 'usado_regular' ? 'selected' : '' ?>>Usado regular</option>
                            </select>
                            <small class="muted soft-note" data-cosmetic-condition-note hidden><?= e($mensagemBioseguranca) ?></small>
                        </label>
                    </div>

                    <div class="grid-2">
                        <label>
                            Bairro aproximado
                            <input type="text" name="bairro" value="<?= e($dados['bairro']) ?>" required <?= !$contaVerificada ? 'disabled' : '' ?>>
                        </label>

                        <label>
                            Cidade
                            <input type="text" name="cidade" value="<?= e($dados['cidade']) ?>" required <?= !$contaVerificada ? 'disabled' : '' ?>>
                        </label>
                    </div>

                    <label>
                        Fotos do item (ate 5 imagens JPG, PNG ou WEBP de 2 MB cada)
                        <input type="file" name="fotos[]" accept="image/jpeg,image/png,image/webp" multiple data-image-preview-input <?= !$contaVerificada ? 'disabled' : '' ?>>
                    </label>
                    <div class="image-preview" data-image-preview hidden></div>

                    <div class="list-card-actions">
                        <button class="btn primary" type="submit" <?= !$contaVerificada ? 'disabled' : '' ?>>Publicar</button>
                    </div>
                </section>
            </form>

            <aside class="form-side">
                <section class="surface-panel">
                    <div class="section-header">
                        <h2>Antes de publicar</h2>
                        <p>Use estas orientacoes para deixar o anuncio mais claro e seguro.</p>
                    </div>
                    <ul class="guideline-list">
                        <li>Informe somente o bairro ou regiao aproximada, nunca o endereco completo.</li>
                        <li>Evite criar anuncios repetidos: edite ou pause o item anterior quando for o mesmo objeto.</li>
                        <li>Cosmeticos e itens de beleza so podem entrar como novos e lacrados.</li>
                    </ul>
                </section>
            </aside>
        </section>
    </main>
</body>
</html>
