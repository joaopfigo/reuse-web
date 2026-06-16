<?php
require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/helpers/layout.php';
require_once __DIR__ . '/app/repositories/AvaliacaoRepo.php';
require_once __DIR__ . '/app/repositories/UsuarioRepo.php';

$usuario = exigir_login();
$erro = '';
$sucesso = '';
$mediaAvaliacao = AvaliacaoRepo::mediaDaUsuaria((int) $usuario['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf_post();
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '') ?: null;
    $bairro = trim($_POST['bairro'] ?? '');
    $cidade = trim($_POST['cidade'] ?? 'Belo Horizonte') ?: 'Belo Horizonte';

    if (!$nome || !$bairro) {
        $erro = 'Nome e bairro são obrigatórios.';
    } else {
        UsuarioRepo::atualizarPerfil((int) $usuario['id'], $nome, $telefone, $bairro, $cidade);
        $usuario = exigir_login();
        $mediaAvaliacao = AvaliacaoRepo::mediaDaUsuaria((int) $usuario['id']);
        $sucesso = 'Perfil atualizado.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Perfil</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php render_topbar($usuario); ?>

    <main class="container dashboard-shell">
        <section class="dashboard-hero dashboard-hero-profile">
            <div class="dashboard-hero-copy">
                <span class="eyebrow">Conta</span>
                <h1><?= e($usuario['nome']) ?></h1>
                <p>Gerencie seus dados, acompanhe sua reputação na comunidade e veja como sua participação evolui ao longo das entregas.</p>
            </div>

            <div class="hero-aside">
                <div class="hero-chip">
                    <span class="hero-chip-label">Base atual</span>
                    <strong><?= e($usuario['bairro']) ?>, <?= e($usuario['cidade']) ?></strong>
                </div>
                <div class="hero-chip">
                    <span class="hero-chip-label">Conta ativa</span>
                    <strong>Pronta para doar e reservar</strong>
                </div>
            </div>
        </section>

        <?php if (!empty($usuario['bloqueada_ate']) && strtotime($usuario['bloqueada_ate']) > time()): ?>
            <div class="alert error dashboard-alert">
                Sua conta está temporariamente bloqueada por não comparecimentos repetidos. A restrição será liberada em
                <strong><?= e(formatar_data_hora($usuario['bloqueada_ate'])) ?></strong>.
            </div>
        <?php endif; ?>

        <section class="metric-grid metric-grid-profile">
            <article class="metric-card metric-card-accent">
                <span class="metric-kicker">Saldo atual</span>
                <strong class="metric-value"><?= (int) $usuario['saldo_pontos'] ?></strong>
                <span class="metric-unit">pontos disponíveis</span>
                <p class="metric-note">Use sua carteira para acompanhar o que já entrou em cada entrega confirmada.</p>
            </article>

            <article class="metric-card">
                <span class="metric-kicker">Avaliação média</span>
                <strong class="metric-value">
                    <?= $mediaAvaliacao !== null ? e(number_format($mediaAvaliacao, 1, ',', '.')) : '--' ?>
                </strong>
                <span class="metric-unit"><?= $mediaAvaliacao !== null ? 'de 5,0' : 'ainda sem avaliações' ?></span>
                <p class="metric-note">As avaliações aparecem depois que as entregas são concluídas e confirmadas.</p>
            </article>

            <article class="metric-card">
                <span class="metric-kicker">Não comparecimentos</span>
                <strong class="metric-value"><?= (int) ($usuario['no_show_count'] ?? 0) ?></strong>
                <span class="metric-unit">registro(s)</span>
                <p class="metric-note">Mantemos esse histórico para incentivar combinações claras e encontros em local público.</p>
            </article>
        </section>

        <section class="profile-layout">
            <form method="post" class="panel profile-form-panel stack">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">Dados pessoais</span>
                        <h2>Informações da conta</h2>
                    </div>
                </div>

                <?= csrf_input() ?>

                <?php if ($erro): ?><div class="alert error"><?= e($erro) ?></div><?php endif; ?>
                <?php if ($sucesso): ?><div class="alert success"><?= e($sucesso) ?></div><?php endif; ?>

                <div class="grid-2">
                    <label>
                        Nome
                        <input type="text" name="nome" value="<?= e($usuario['nome']) ?>" required>
                    </label>

                    <label>
                        Telefone
                        <input type="tel" name="telefone" value="<?= e($usuario['telefone']) ?>" data-phone-mask>
                    </label>
                </div>

                <div class="grid-2">
                    <label>
                        Bairro
                        <input type="text" name="bairro" value="<?= e($usuario['bairro']) ?>" required>
                    </label>

                    <label>
                        Cidade
                        <input type="text" name="cidade" value="<?= e($usuario['cidade']) ?>" required>
                    </label>
                </div>

                <div class="action-row">
                    <button class="btn primary" type="submit">Salvar perfil</button>
                </div>
            </form>

            <aside class="profile-side-panel">
                <section class="panel stack">
                    <div class="section-heading">
                        <div>
                            <span class="eyebrow">Visão rápida</span>
                            <h2>Sua presença no ReUse</h2>
                        </div>
                    </div>

                    <div class="info-list">
                        <div class="info-row">
                            <span>E-mail</span>
                            <strong><?= e($usuario['email']) ?></strong>
                        </div>
                        <div class="info-row">
                            <span>Localidade</span>
                            <strong><?= e($usuario['bairro']) ?>, <?= e($usuario['cidade']) ?></strong>
                        </div>
                        <div class="info-row">
                            <span>Telefone</span>
                            <strong><?= $usuario['telefone'] ? e($usuario['telefone']) : 'Não informado' ?></strong>
                        </div>
                    </div>
                </section>

                <section class="panel stack">
                    <div class="section-heading">
                        <div>
                            <span class="eyebrow">Boas práticas</span>
                            <h2>Segurança e confiança</h2>
                        </div>
                    </div>

                    <ul class="insight-list">
                        <li>Compartilhe apenas bairro e local público para retirada.</li>
                        <li>Use o chat para alinhar o encontro antes da confirmação.</li>
                        <li>Finalize a entrega com o código de confirmação para atualizar os pontos.</li>
                    </ul>
                </section>
            </aside>
        </section>
    </main>
</body>
</html>
