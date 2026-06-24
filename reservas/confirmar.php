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
        $erros[] = 'Digite o código fornecido pelo(a) doador(a).';
    } elseif ($codigo !== $reserva['codigo_confirmacao']) {
        $erros[] = 'Código incorreto. Confirme novamente com o(a) doador(a).';
    } else {
        try {
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
        } catch (Throwable $erroConfirmacao) {
            $erros[] = $erroConfirmacao->getMessage();
        }
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
    <link rel="stylesheet" href="../assets/css/experience.css?v=20260624">
</head>
<body>
    <?php render_topbar($usuario); ?>
    <main class="container ops-page">
        <section class="ops-hero compact">
            <div class="ops-hero-main">
                <span class="ops-kicker">Confirmação da entrega</span>
                <h1 class="ops-title"><?= e($reserva['titulo']) ?></h1>
                <p class="ops-copy">Finalize a retirada com o código informado pelo(a) doador(a) para atualizar corretamente os pontos do sistema.</p>
            </div>
        </section>

        <section class="form-layout">
            <form method="post" class="form-main">
                <section class="surface-panel">
                    <div class="section-header">
                        <h2>Inserir código</h2>
                        <p>Use o código de 6 caracteres fornecido no encontro para concluir a entrega.</p>
                    </div>

                    <div class="soft-note">
                        <strong>Local:</strong> <?= e((string) $reserva['local_retirada']) ?><br>
                        <strong>Data:</strong> <?= e(formatar_data_hora($reserva['data_retirada'])) ?>
                    </div>

                    <?php foreach ($erros as $erro): ?>
                        <div class="alert error"><?= e($erro) ?></div>
                    <?php endforeach; ?>

                    <?= csrf_input() ?>

                    <label>
                        Código de confirmação
                        <input class="code-input" type="text" name="codigo" maxlength="6" required placeholder="Ex.: A3F9B2" value="<?= e($codigo) ?>">
                    </label>

                    <div class="list-card-actions">
                        <button type="submit" class="btn primary">Confirmar entrega</button>
                        <a href="minhas.php" class="btn">Voltar</a>
                    </div>
                </section>
            </form>

            <aside class="form-side">
                <section class="surface-panel">
                    <div class="section-header">
                        <h2>Antes de confirmar</h2>
                        <p>Faça esta checagem rápida para evitar erro de registro.</p>
                    </div>
                    <ul class="guideline-list">
                        <li>Confira se o item foi realmente entregue no encontro combinado.</li>
                        <li>Peça o código diretamente ao(à) doador(a) no momento da retirada.</li>
                        <li>Depois da confirmação, os pontos são atualizados automaticamente.</li>
                    </ul>
                </section>
            </aside>
        </section>
    </main>
</body>
</html>
