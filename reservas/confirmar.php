<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/ReservaRepo.php';
require_once __DIR__ . '/../app/repositories/NotificacaoRepo.php';
require_once __DIR__ . '/../app/services/PontosService.php';

$usuario = exigir_login();
$reservaId = (int) ($_GET['id'] ?? 0);
$reserva = $reservaId > 0 ? ReservaRepo::buscarPorId($reservaId) : null;

if (!$reserva
    || (int) $reserva['receptora_id'] !== (int) $usuario['id']
    || $reserva['status'] !== 'aceita'
) {
    flash_set('error', 'Reserva inválida ou não está pronta para confirmação.');
    header('Location: ' . app_url('reservas/minhas.php'));
    exit;
}

$erros = [];
$codigo = strtoupper(trim((string) ($_POST['codigo'] ?? '')));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf_post();

    if (!$codigo) {
        $erros[] = 'Digite o código fornecido pela doadora.';
    } elseif ($codigo !== $reserva['codigo_confirmacao']) {
        $erros[] = 'Código incorreto. Confirme novamente com a doadora.';
    } else {
        PontosService::confirmarEntrega($reservaId);

        NotificacaoRepo::criar(
            (int) $reserva['doadora_id'],
            'confirmacao',
            $usuario['nome'] . ' confirmou o recebimento de "' . $reserva['titulo'] . '". Seus pontos foram creditados.',
            $reservaId
        );

        flash_set('success', 'Entrega confirmada com sucesso. Os pontos já foram atualizados.');
        header('Location: ' . app_url('pontos/carteira.php'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Confirmar entrega</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php render_topbar($usuario); ?>
    <main class="container">
        <section class="panel stack">
            <h1>Confirmar entrega</h1>
            <p>Item: <strong><?= e($reserva['titulo']) ?></strong></p>
            <p class="muted">Peça o código de 6 caracteres à doadora no momento da retirada.</p>

            <div class="status-callout">
                <strong>Local:</strong> <?= e((string) $reserva['local_retirada']) ?><br>
                <strong>Data:</strong> <?= e(formatar_data_hora($reserva['data_retirada'])) ?>
            </div>

            <?php foreach ($erros as $erro): ?>
                <div class="alert error"><?= e($erro) ?></div>
            <?php endforeach; ?>

            <form method="post" class="form-card">
                <?= csrf_input() ?>
                <label>
                    Código de confirmação
                    <input type="text" name="codigo" maxlength="6" required
                           placeholder="Ex.: A3F9B2"
                           style="text-transform:uppercase; letter-spacing:.15em"
                           value="<?= e($codigo) ?>">
                </label>
                <div class="action-row">
                    <button type="submit" class="btn primary">Confirmar</button>
                    <a href="minhas.php" class="btn">Voltar</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
