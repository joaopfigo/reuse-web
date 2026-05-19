<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/helpers/validar.php';
require_once __DIR__ . '/../app/repositories/ItemRepo.php';

$usuario = exigir_login();
$categorias = ItemRepo::categorias();
$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dados = [
            'doadora_id' => (int) $usuario['id'],
            'categoria_id' => (int) ($_POST['categoria_id'] ?? 0),
            'titulo' => trim($_POST['titulo'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'condicao' => $_POST['condicao'] ?? '',
            'pontos' => (int) ($_POST['pontos'] ?? 0),
            'bairro' => trim($_POST['bairro'] ?? ''),
        ];

        if (!$dados['titulo'] || !$dados['descricao'] || !$dados['categoria_id'] || !$dados['bairro'] || $dados['pontos'] <= 0) {
            throw new RuntimeException('Preencha todos os campos obrigatorios.');
        }

        $fotoPath = salvar_upload_item($_FILES['foto'] ?? []);
        ItemRepo::criar($dados, $fotoPath);
        $sucesso = 'Item cadastrado com sucesso.';
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
</head>
<body>
    <?php render_topbar($usuario); ?>

    <main class="container">
        <form method="post" enctype="multipart/form-data" class="panel stack">
            <h1>Cadastrar item</h1>
            <p class="muted">Informe apenas a regiao/bairro aproximado, nunca endereco completo.</p>

            <?php if ($erro): ?><div class="alert error"><?= e($erro) ?></div><?php endif; ?>
            <?php if ($sucesso): ?><div class="alert success"><?= e($sucesso) ?></div><?php endif; ?>

            <div class="grid-2">
                <label>
                    Titulo
                    <input type="text" name="titulo" required>
                </label>

                <label>
                    Pontos
                    <input type="number" name="pontos" min="1" value="5" required>
                </label>
            </div>

            <label>
                Descricao
                <textarea name="descricao" required></textarea>
            </label>

            <div class="grid-2">
                <label>
                    Categoria
                    <select name="categoria_id" required>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= (int) $categoria['id'] ?>"><?= e($categoria['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Condicao
                    <select name="condicao" required>
                        <option value="novo">Novo</option>
                        <option value="seminovo">Seminovo</option>
                        <option value="usado_bom">Usado em bom estado</option>
                        <option value="usado_regular">Usado regular</option>
                    </select>
                </label>
            </div>

            <label>
                Bairro aproximado
                <input type="text" name="bairro" value="<?= e($usuario['bairro']) ?>" required>
            </label>

            <label>
                Foto do item (JPG, PNG ou WEBP ate 2MB)
                <input type="file" name="foto" accept="image/jpeg,image/png,image/webp">
            </label>

            <button class="btn primary" type="submit">Publicar</button>
        </form>
    </main>
</body>
</html>
