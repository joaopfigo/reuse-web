<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/ReservaRepo.php';
require_once __DIR__ . '/../app/repositories/AvaliacaoRepo.php';
require_once __DIR__ . '/../app/repositories/NotificacaoRepo.php';

$usuario = exigir_login();
$reservaId = (int) ($_GET['reserva_id'] ?? 0);
$reserva = $reservaId > 0 ? ReservaRepo::buscarPorId($reservaId) : null;

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
    validar_csrf_post();
    $nota = (int) ($_POST['nota'] ?? 0);
    $comentario = trim((string) ($_POST['comentario'] ?? ''));

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

        flash_set('success', 'Avaliação registrada com sucesso.');
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
    <link rel="stylesheet" href="../assets/css/experience.css?v=20260624">
</head>
<body>
    <?php render_topbar($usuario); ?>
    <main class="container ops-page">
        <section class="ops-hero compact">
            <div class="ops-hero-main">
                <span class="ops-kicker">Reputação da comunidade</span>
                <h1 class="ops-title">Avaliar entrega</h1>
                <p class="ops-copy">Conte como foi a experiência da retirada para fortalecer a confiança entre participantes.</p>
            </div>
            <div class="ops-hero-side">
                <div class="ops-side-card">
                    <span>Item recebido</span>
                    <strong><?= e($reserva['titulo']) ?></strong>
                </div>
            </div>
        </section>

        <section class="form-layout">
            <form method="post" class="form-main">
                <section class="surface-panel">
                    <div class="section-header">
                        <h2>Como foi a entrega?</h2>
                        <p>Doador(a): <strong><?= e($reserva['doadora_nome']) ?></strong>. Sua avaliação aparece no histórico público da pessoa avaliada.</p>
                    </div>

                    <?php foreach ($erros as $erro): ?>
                        <div class="alert error"><?= e($erro) ?></div>
                    <?php endforeach; ?>

                    <?= csrf_input() ?>
                    <label>
                        Nota
                        <select name="nota" required>
                            <option value="">Selecione...</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>" <?= ((int) ($_POST['nota'] ?? 0)) === $i ? 'selected' : '' ?>>
                                    <?= $i ?> estrela<?= $i > 1 ? 's' : '' ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </label>
                    <label>
                        Comentário (opcional)
                        <textarea name="comentario" maxlength="300" rows="4" placeholder="Ex.: comunicação clara, item conforme descrito, encontro pontual..."><?= e($_POST['comentario'] ?? '') ?></textarea>
                    </label>
                    <div class="list-card-actions">
                        <button type="submit" class="btn primary">Enviar avaliação</button>
                        <a href="../reservas/minhas.php" class="btn">Cancelar</a>
                    </div>
                </section>
            </form>

            <aside class="form-side">
                <section class="surface-panel">
                    <div class="section-header">
                        <h2>Por que avaliar?</h2>
                        <p>A reputação ajuda outras pessoas a tomar decisões com mais segurança.</p>
                    </div>
                    <ul class="guideline-list">
                        <li>Considere pontualidade, comunicação e fidelidade da descrição.</li>
                        <li>Evite expor dados pessoais ou local exato do encontro.</li>
                        <li>Use a denúncia apenas para situações que exigem análise de segurança.</li>
                    </ul>
                </section>
            </aside>
        </section>
    </main>
</body>
</html>
