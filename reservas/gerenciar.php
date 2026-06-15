<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/ReservaRepo.php';

$usuario = exigir_login();
$reservas = ReservaRepo::minhasDaDoadora((int) $usuario['id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Reservas dos meus itens</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php render_topbar($usuario); ?>
    <main class="container">
        <section class="panel stack">
            <div class="page-header">
                <div>
                    <h1>Reservas dos meus itens</h1>
                    <p class="muted">Veja quem reservou, combine o encontro e acompanhe o código de confirmação.</p>
                </div>
            </div>

            <?= flash_html() ?>

            <?php if (!$reservas): ?>
                <p class="muted empty-state">Nenhuma reserva pendente ou aceita nos seus itens.</p>
            <?php endif; ?>

            <?php foreach ($reservas as $r): ?>
                <article class="panel stack">
                    <div class="page-header">
                        <div>
                            <h2><?= e($r['titulo']) ?></h2>
                            <p class="muted">Receptora: <strong><?= e($r['receptora_nome']) ?></strong> · <?= (int) $r['pontos'] ?> pontos</p>
                        </div>
                        <span class="<?= e(status_badge_class((string) $r['status'])) ?>"><?= e(status_label((string) $r['status'])) ?></span>
                    </div>

                    <p class="muted">Solicitação criada em <?= e(formatar_data_hora($r['criada_em'])) ?> e expira em <?= e(formatar_data_hora($r['expira_em'])) ?>.</p>

                    <?php if ($r['status'] === 'pendente'): ?>
                        <div class="status-callout">
                            A reserva ainda está aguardando sua aprovação com local e horário de retirada.
                        </div>
                    <?php else: ?>
                        <div class="status-callout">
                            <strong>Local:</strong> <?= e((string) $r['local_retirada']) ?><br>
                            <strong>Data:</strong> <?= e(formatar_data_hora($r['data_retirada'])) ?>
                        </div>
                        <?php if (!empty($r['codigo_confirmacao'])): ?>
                            <div class="code-callout">
                                <span class="summary-label">Código de confirmação</span>
                                <code><?= e($r['codigo_confirmacao']) ?></code>
                                <small class="muted">Mostre este código para a receptora apenas no momento da entrega.</small>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="action-row">
                        <?php if ($r['status'] === 'pendente'): ?>
                            <a class="btn primary" href="aceitar.php?id=<?= (int) $r['id'] ?>">Aceitar e combinar retirada</a>
                        <?php endif; ?>

                        <a class="btn" href="chat.php?id=<?= (int) $r['id'] ?>">Mensagens</a>

                        <?php
                        $reuniaoPassou = !empty($r['data_retirada']) && strtotime((string) $r['data_retirada']) < time();
                        $expirou = strtotime((string) $r['expira_em']) < time();
                        if ($r['status'] === 'aceita' && ($reuniaoPassou || $expirou)): ?>
                            <form method="post" action="noshow.php" class="inline-form" onsubmit="return confirm('Confirmar que a receptora não compareceu? Isso pode levar ao bloqueio temporário da conta dela.');">
                                <?= csrf_input() ?>
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <button class="btn danger" type="submit">Registrar não comparecimento</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
