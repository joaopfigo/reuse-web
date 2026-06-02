<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/DenunciaRepo.php';

$usuario = exigir_login();
$itemId  = (int) ($_GET['item_id'] ?? 0);
$erros   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motivo    = $_POST['motivo'] ?? '';
    $descricao = trim($_POST['descricao'] ?? '');
    $itemIdPost = (int) ($_POST['item_id'] ?? 0);
    $validos   = ['item_falso', 'comportamento', 'no_show', 'outro'];

    if (!in_array($motivo, $validos, true)) {
        $erros[] = 'Selecione um motivo válido.';
    }
    if (strlen($descricao) < 10) {
        $erros[] = 'Descreva a ocorrência com pelo menos 10 caracteres.';
    }

    if (!$erros) {
        DenunciaRepo::criar((int) $usuario['id'], $motivo, $descricao, $itemIdPost ?: null);
        flash_set('success', 'Denúncia registrada. Obrigada por ajudar a manter a comunidade segura.');
        header('Location: ' . app_url('itens/listar.php'));
        exit;
    }
    $itemId = $itemIdPost;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Denunciar</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php render_topbar($usuario); ?>
    <main class="container">
        <section class="panel stack">
            <h1>Denunciar</h1>

            <?php foreach ($erros as $erro): ?>
                <div class="alert error"><?= e($erro) ?></div>
            <?php endforeach; ?>

            <form method="post" class="form-card">
                <input type="hidden" name="item_id" value="<?= (int) $itemId ?>">

                <label>
                    Motivo
                    <select name="motivo" required>
                        <option value="">Selecione...</option>
                        <option value="item_falso">Item falso ou enganoso</option>
                        <option value="comportamento">Comportamento inadequado</option>
                        <option value="no_show">Não compareceu ao encontro</option>
                        <option value="outro">Outro</option>
                    </select>
                </label>
                <label>
                    Descrição
                    <textarea name="descricao" rows="4" required maxlength="500"
                              placeholder="Descreva o que aconteceu..."><?= e($_POST['descricao'] ?? '') ?></textarea>
                </label>

                <button type="submit" class="btn danger">Enviar denúncia</button>
                <a href="javascript:history.back()" class="btn">Cancelar</a>
            </form>
        </section>
    </main>
</body>
</html>
