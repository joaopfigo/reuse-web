<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/DenunciaRepo.php';
require_once __DIR__ . '/../app/repositories/ReservaRepo.php';

$usuario      = exigir_login();
$itemId       = (int) ($_GET['item_id'] ?? 0);
$reservaId    = (int) ($_GET['reserva_id'] ?? 0);
$denunciadaId = (int) ($_GET['denunciada_id'] ?? 0);
$erros        = [];

// Quando vem de uma reserva, valida que a receptora é a usuária logada
$reserva = null;
if ($reservaId > 0) {
    $reserva = ReservaRepo::buscarPorId($reservaId);
    if (!$reserva || (int) $reserva['receptora_id'] !== (int) $usuario['id']) {
        flash_set('error', 'Reserva inválida.');
        header('Location: ' . app_url('reservas/minhas.php'));
        exit;
    }
    $itemId = (int) $reserva['item_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motivo           = $_POST['motivo'] ?? '';
    $descricao        = trim($_POST['descricao'] ?? '');
    $itemIdPost       = (int) ($_POST['item_id'] ?? 0);
    $reservaIdPost    = (int) ($_POST['reserva_id'] ?? 0);
    $denunciadaIdPost = (int) ($_POST['denunciada_id'] ?? 0);
    $validos          = ['item_falso', 'comportamento', 'no_show', 'outro'];

    if (!in_array($motivo, $validos, true)) {
        $erros[] = 'Selecione um motivo válido.';
    }
    if (strlen($descricao) < 10) {
        $erros[] = 'Descreva a ocorrência com pelo menos 10 caracteres.';
    }

    if (!$erros) {
        DenunciaRepo::criar(
            (int) $usuario['id'],
            $motivo,
            $descricao,
            $itemIdPost ?: null,
            $reservaIdPost ?: null,
            $denunciadaIdPost ?: null
        );
        flash_set('success', 'Denúncia registrada. Obrigada por ajudar a manter a comunidade segura.');
        header('Location: ' . app_url('itens/listar.php'));
        exit;
    }

    $itemId       = $itemIdPost;
    $reservaId    = $reservaIdPost;
    $denunciadaId = $denunciadaIdPost;
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

            <?php if ($reserva): ?>
                <div class="alert">
                    Você está denunciando um comportamento relacionado à reserva do item
                    <strong>"<?= e($reserva['titulo']) ?>"</strong>.
                </div>
            <?php endif; ?>

            <?php foreach ($erros as $erro): ?>
                <div class="alert error"><?= e($erro) ?></div>
            <?php endforeach; ?>

            <form method="post" class="form-card">
                <input type="hidden" name="item_id"        value="<?= (int) $itemId ?>">
                <input type="hidden" name="reserva_id"     value="<?= (int) $reservaId ?>">
                <input type="hidden" name="denunciada_id"  value="<?= (int) $denunciadaId ?>">

                <label>
                    Motivo
                    <select name="motivo" required>
                        <option value="">Selecione...</option>
                        <option value="item_falso"    <?= ($_POST['motivo'] ?? '') === 'item_falso'    ? 'selected' : '' ?>>Item falso ou enganoso</option>
                        <option value="comportamento" <?= ($_POST['motivo'] ?? '') === 'comportamento' ? 'selected' : '' ?>>Comportamento inadequado</option>
                        <option value="no_show"       <?= ($_POST['motivo'] ?? '') === 'no_show'       ? 'selected' : '' ?>>Não compareceu ao encontro</option>
                        <option value="outro"         <?= ($_POST['motivo'] ?? '') === 'outro'         ? 'selected' : '' ?>>Outro</option>
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
