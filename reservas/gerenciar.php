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
    <link rel="stylesheet" href="../assets/css/experience.css?v=20260615">
</head>
<body>
    <?php render_topbar($usuario); ?>
    <main class="container ops-page">
        <section class="ops-hero compact">
            <div class="ops-hero-main">
                <span class="ops-kicker">Doações em andamento</span>
                <h1 class="ops-title">Minhas doações</h1>
                <p class="ops-copy">Acompanhe quem reservou seus itens, defina local de retirada e gerencie confirmações com mais clareza.</p>
            </div>
        </section>

        <?= flash_html() ?>

        <?php if (!$reservas): ?>
            <div class="empty-card">Nenhuma reserva pendente ou aceita nos seus itens.</div>
        <?php endif; ?>

        <section class="list-grid">
            <?php foreach ($reservas as $r): ?>
                <article class="list-card">
                    <div class="list-card-head">
                        <div>
                            <h2><?= e($r['titulo']) ?></h2>
                            <p class="list-card-meta">Receptora: <strong><?= e($r['receptora_nome']) ?></strong> · <?= (int) $r['pontos'] ?> pontos</p>
                        </div>
                        <span class="<?= e(status_badge_class((string) $r['status'])) ?>"><?= e(status_label((string) $r['status'])) ?></span>
                    </div>

                    <div class="list-card-body">
                        <p class="soft-note">Solicitação criada em <?= e(formatar_data_hora($r['criada_em'])) ?> e expira em <?= e(formatar_data_hora($r['expira_em'])) ?>.</p>

                        <?php if ($r['status'] === 'pendente'): ?>
                            <div class="soft-note">A reserva ainda aguarda sua aprovação com local e horário de retirada.</div>
                        <?php else: ?>
                            <div class="soft-note">
                                <strong>Local:</strong> <?= e((string) $r['local_retirada']) ?><br>
                                <strong>Data:</strong> <?= e(formatar_data_hora($r['data_retirada'])) ?>
                            </div>
                            <?php if (!empty($r['codigo_confirmacao'])): ?>
                                <div class="soft-note">
                                    <strong>Código de confirmação:</strong> <code><?= e($r['codigo_confirmacao']) ?></code><br>
                                    <small class="muted">Mostre este código para a receptora apenas no momento da entrega.</small>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <div class="list-card-actions">
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
