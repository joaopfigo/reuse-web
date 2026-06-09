<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/ReservaRepo.php';
require_once __DIR__ . '/../app/repositories/NotificacaoRepo.php';

$usuario   = exigir_login();
$reservaId = (int) ($_GET['id'] ?? 0);
$reserva   = $reservaId > 0 ? ReservaRepo::buscarPorId($reservaId) : null;

if (!$reserva
    || (int) $reserva['doadora_id'] !== (int) $usuario['id']
    || $reserva['status'] !== 'pendente'
) {
    flash_set('error', 'Reserva inválida ou já processada.');
    header('Location: ' . app_url('reservas/gerenciar.php'));
    exit;
}

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $local = trim($_POST['local_retirada'] ?? '');
    $data  = trim($_POST['data_retirada'] ?? '');

    if (!$local) {
        $erros[] = 'Informe o local de retirada.';
    }
    if (!$data) {
        $erros[] = 'Informe a data e horário.';
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
            flash_set('success', 'Reserva aceita! A receptora foi notificada.');
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
</head>
<body>
    <?php render_topbar($usuario); ?>
    <main class="container">
        <section class="panel stack">
            <h1>Aceitar reserva — <?= e($reserva['titulo']) ?></h1>
            <p class="muted">Receptora: <strong><?= e($reserva['receptora_nome']) ?></strong></p>

            <?php foreach ($erros as $erro): ?>
                <div class="alert error"><?= e($erro) ?></div>
            <?php endforeach; ?>

            <form method="post" class="form-card">
                <label>
                    Local de retirada (local público — ex.: Shopping X, entrada principal)
                    <input type="text" name="local_retirada" required maxlength="180"
                           value="<?= e($_POST['local_retirada'] ?? '') ?>">
                </label>
                <label>
                    Data e horário
                    <input type="datetime-local" name="data_retirada" required
                           value="<?= e($_POST['data_retirada'] ?? '') ?>">
                </label>
                <button type="submit" class="btn primary">Confirmar aceite</button>
                <a href="gerenciar.php" class="btn">Voltar</a>
            </form>
        </section>
    </main>
</body>
</html>
