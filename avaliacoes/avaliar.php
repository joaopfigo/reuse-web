<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/ReservaRepo.php';
require_once __DIR__ . '/../app/repositories/AvaliacaoRepo.php';
require_once __DIR__ . '/../app/repositories/NotificacaoRepo.php';

$usuario   = exigir_login();
$reservaId = (int) ($_GET['reserva_id'] ?? 0);
$reserva   = $reservaId > 0 ? ReservaRepo::buscarPorId($reservaId) : null;

if (!$reserva
    || (int) $reserva['receptora_id'] !== (int) $usuario['id']
    || $reserva['status'] !== 'entregue'
) {
    flash_set('error', 'Avaliação não disponível para esta reserva.');
    header('Location: ' . app_url('reservas/minhas.php'));
    exit;
}

if (AvaliacaoRepo::jaAvaliou($reservaId, (int) $usuario['id'])) {
    flash_set('error', 'Você já avaliou esta entrega.');
    header('Location: ' . app_url('reservas/minhas.php'));
    exit;
}

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nota       = (int) ($_POST['nota'] ?? 0);
    $comentario = trim($_POST['comentario'] ?? '');

    if ($nota < 1 || $nota > 5) {
        $erros[] = 'Selecione uma nota de 1 a 5.';
    }

    if (!$erros) {
        AvaliacaoRepo::criar(
            $reservaId,
            (int) $usuario['id'],
            (int) $reserva['doadora_id'],
            $nota,
            $comentario
        );

        NotificacaoRepo::criar(
            (int) $reserva['doadora_id'],
            'avaliacao',
            $usuario['nome'] . ' avaliou sua entrega de "' . $reserva['titulo'] . '" com ' . $nota . ' estrela(s).',
            $reservaId
        );

        flash_set('success', 'Avaliação registrada!');
        header('Location: ' . app_url('reservas/minhas.php'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Avaliar entrega</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php render_topbar($usuario); ?>
    <main class="container">
        <section class="panel stack">
            <h1>Avaliar entrega — <?= e($reserva['titulo']) ?></h1>
            <p class="muted">Doadora: <?= e($reserva['doadora_nome']) ?></p>

            <?php foreach ($erros as $erro): ?>
                <div class="alert error"><?= e($erro) ?></div>
            <?php endforeach; ?>

            <form method="post" class="form-card">
                <label>
                    Nota
                    <select name="nota" required>
                        <option value="">Selecione...</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>"
                                <?= ((int) ($_POST['nota'] ?? 0)) === $i ? 'selected' : '' ?>>
                                <?= $i ?> ★
                            </option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label>
                    Comentário (opcional)
                    <textarea name="comentario" maxlength="300" rows="3"><?= e($_POST['comentario'] ?? '') ?></textarea>
                </label>
                <button type="submit" class="btn primary">Enviar avaliação</button>
                <a href="../reservas/minhas.php" class="btn">Cancelar</a>
            </form>
        </section>
    </main>
</body>
</html>
