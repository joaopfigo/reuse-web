<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/ReservaRepo.php';
require_once __DIR__ . '/../app/repositories/NotificacaoRepo.php';

$usuario = exigir_login();
$reservaId = (int) ($_GET['id'] ?? 0);
$reserva = $reservaId > 0 ? ReservaRepo::buscarPorId($reservaId) : null;

if (!$reserva
    || (int) $reserva['doadora_id'] !== (int) $usuario['id']
    || $reserva['status'] !== 'pendente'
) {
    flash_set('error', 'Reserva inválida ou já processada.');
    header('Location: ' . app_url('reservas/gerenciar.php'));
    exit;
}

$erros = [];
$local = trim((string) ($_POST['local_retirada'] ?? ''));
$data = trim((string) ($_POST['data_retirada'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf_post();

    if (!$local) {
        $erros[] = 'Informe o local de retirada.';
    }
    if (!$data) {
        $erros[] = 'Informe a data e o horário.';
    } elseif (strtotime($data) !== false && strtotime($data) < time()) {
        $erros[] = 'Escolha uma data e horário futuros para a retirada.';
    }

    if (!$erros) {
        $aceito = ReservaRepo::aceitar($reservaId, (int) $usuario['id'], $local, $data);

        if ($aceito) {
            NotificacaoRepo::criar(
                (int) $reserva['receptora_id'],
                'aceite',
                $usuario['nome'] . ' aceitou sua reserva de "' . $reserva['titulo'] . '". Local: ' . $local . ' | Horário: ' . $data . '.',
                $reservaId
            );
            flash_set('success', 'Reserva aceita. O(a) receptor(a) já foi notificado(a).');
            header('Location: ' . app_url('reservas/gerenciar.php'));
            exit;
        }

        $erros[] = 'Não foi possível aceitar a reserva. Tente novamente.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Aceitar reserva</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/experience.css?v=20260624">
</head>
<body>
    <?php render_topbar($usuario); ?>
    <main class="container ops-page">
        <section class="ops-hero compact">
            <div class="ops-hero-main">
                <span class="ops-kicker">Aceite da reserva</span>
                <h1 class="ops-title"><?= e($reserva['titulo']) ?></h1>
                <p class="ops-copy">Defina um local público e um horário claro para organizar a retirada com o(a) receptor(a).</p>
            </div>
            <div class="ops-hero-side">
                <div class="ops-side-card">
                    <span>Receptor(a)</span>
                    <strong><?= e($reserva['receptora_nome']) ?></strong>
                </div>
            </div>
        </section>

        <section class="form-layout">
            <form method="post" class="form-main">
                <section class="surface-panel">
                    <div class="section-header">
                        <h2>Combinar retirada</h2>
                        <p>Escolha um ponto seguro e descreva o encontro com o máximo de clareza possível.</p>
                    </div>

                    <?php foreach ($erros as $erro): ?>
                        <div class="alert error"><?= e($erro) ?></div>
                    <?php endforeach; ?>

                    <?= csrf_input() ?>

                    <label>
                        Local de retirada
                        <input type="text" name="local_retirada" required maxlength="180" value="<?= e($local) ?>" placeholder="Ex.: estação de metrô, portaria principal, praça movimentada">
                        <small class="muted">Use um ponto público, conhecido e fácil de localizar.</small>
                    </label>

                    <label>
                        Data e horário
                        <input type="datetime-local" name="data_retirada" required value="<?= e($data) ?>">
                    </label>

                    <div class="list-card-actions">
                        <button type="submit" class="btn primary">Confirmar aceite</button>
                        <a href="gerenciar.php" class="btn">Voltar</a>
                    </div>
                </section>
            </form>

            <aside class="form-side">
                <section class="surface-panel">
                    <div class="section-header">
                        <h2>Dicas para o encontro</h2>
                        <p>Pequenos cuidados ajudam a evitar desencontros e tornam a retirada mais tranquila.</p>
                    </div>
                    <ul class="guideline-list">
                        <li>Prefira locais com circulação de pessoas e acesso fácil.</li>
                        <li>Use referências claras, como entrada principal ou praça central.</li>
                        <li>Depois da entrega, o(a) receptor(a) confirma com o código de 6 caracteres.</li>
                    </ul>
                </section>
            </aside>
        </section>
    </main>
</body>
</html>
