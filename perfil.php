<?php
require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/helpers/layout.php';
require_once __DIR__ . '/app/repositories/AvaliacaoRepo.php';
require_once __DIR__ . '/app/repositories/UsuarioRepo.php';

$usuario = exigir_login();
$erro = '';
$sucesso = '';
$mediaAvaliacao = AvaliacaoRepo::mediaDaUsuaria((int) $usuario['id']);
$resumoConfianca = UsuarioRepo::resumoPublico((int) $usuario['id']) ?? [];
$dadosNivel = array_merge($usuario, $resumoConfianca);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf_post();
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '') ?: null;
    $bairro = trim($_POST['bairro'] ?? '');
    $cidade = trim($_POST['cidade'] ?? 'Belo Horizonte') ?: 'Belo Horizonte';

    if (!$nome || !$bairro) {
        $erro = 'Nome e bairro são obrigatórios.';
    } else {
        try {
            UsuarioRepo::atualizarPerfil((int) $usuario['id'], $nome, $telefone, $bairro, $cidade);
            $usuario = exigir_login();
            $mediaAvaliacao = AvaliacaoRepo::mediaDaUsuaria((int) $usuario['id']);
            $resumoConfianca = UsuarioRepo::resumoPublico((int) $usuario['id']) ?? [];
            $dadosNivel = array_merge($usuario, $resumoConfianca);
            $sucesso = 'Perfil atualizado.';
        } catch (PDOException $e) {
            $erro = ((string) $e->getCode() === '23000')
                ? 'Este telefone ja esta sendo usado em outra conta.'
                : 'Nao foi possivel atualizar o perfil agora.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= favicon_head_tags() ?>
    <title>ReUse | Perfil</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=20260624">
</head>
<body>
    <?php render_topbar($usuario); ?>

    <main class="container dash-page">
        <section class="hero-card">
            <div class="hero-main">
                <span class="hero-tag">Conta</span>
                <h1><?= e($usuario['nome']) ?></h1>
                <p>Gerencie seus dados, acompanhe sua reputação na comunidade e veja como sua participação evolui ao longo das entregas.</p>
            </div>

            <div class="hero-side">
                <div class="hero-pill">
                    <span>Base atual</span>
                    <strong><?= e($usuario['bairro']) ?>, <?= e($usuario['cidade']) ?></strong>
                </div>
                <div class="hero-pill">
                    <span>Conta ativa</span>
                    <strong><?= conta_verificada($usuario) ? 'Pronta para doar e reservar' : 'Aguardando confirmacao de e-mail' ?></strong>
                </div>
            </div>
        </section>

        <?php if (!empty($usuario['bloqueada_ate']) && strtotime($usuario['bloqueada_ate']) > time()): ?>
            <div class="alert error">
                Sua conta está temporariamente bloqueada por não comparecimentos repetidos. A restrição será liberada em
                <strong><?= e(formatar_data_hora($usuario['bloqueada_ate'])) ?></strong>.
            </div>
        <?php endif; ?>

        <?php if (!conta_verificada($usuario)): ?>
            <div class="alert error">
                Sua conta ainda esta no nivel basico. Confirme seu e-mail para publicar itens e fazer reservas. Pontos entram por entregas confirmadas ou compra opcional.
                <form method="post" action="<?= e(app_url('reenviar-confirmacao.php')) ?>" class="inline-form">
                    <?= csrf_input() ?>
                    <button class="btn secondary" type="submit">Reenviar confirmacao</button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert success">
                Nivel da conta: <?= e(nivel_conta($dadosNivel)) ?>. Sua conta esta liberada para publicar itens e fazer reservas.
            </div>
        <?php endif; ?>

        <section class="stats-grid">
            <article class="stat-card featured">
                <span class="stat-label">Saldo atual</span>
                <strong class="stat-value"><?= (int) $usuario['saldo_pontos'] ?></strong>
                <span class="stat-unit">pontos disponíveis</span>
                <p class="stat-note">Use sua carteira para acompanhar o que já entrou em cada entrega confirmada.</p>
            </article>

            <article class="stat-card">
                <span class="stat-label">Avaliação média</span>
                <strong class="stat-value"><?= $mediaAvaliacao !== null ? e(number_format($mediaAvaliacao, 1, ',', '.')) : '--' ?></strong>
                <span class="stat-unit"><?= $mediaAvaliacao !== null ? 'de 5,0' : 'ainda sem avaliações' ?></span>
                <p class="stat-note">As avaliações aparecem depois que as entregas são concluídas e confirmadas.</p>
            </article>

            <article class="stat-card">
                <span class="stat-label">Não comparecimentos</span>
                <strong class="stat-value"><?= (int) ($usuario['no_show_count'] ?? 0) ?></strong>
                <span class="stat-unit">registro(s)</span>
                <p class="stat-note">Esse histórico ajuda a manter combinações claras e encontros mais seguros.</p>
            </article>
        </section>

        <section class="dash-columns">
            <form method="post" class="dash-panel profile-form-grid">
                <div class="section-intro">
                    <span class="section-kicker">Dados pessoais</span>
                    <h2 class="section-title">Informações da conta</h2>
                    <p class="section-copy">Atualize apenas os dados que ajudam a organizar suas doações e retiradas com segurança.</p>
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

            <aside class="dash-stack">
                <section class="dash-panel">
                    <div class="section-intro">
                        <span class="section-kicker">Visão rápida</span>
                        <h2 class="section-title">Sua presença no ReUse</h2>
                    </div>

                    <div class="info-grid">
                        <div class="info-item">
                            <span>E-mail</span>
                            <strong><?= e($usuario['email']) ?></strong>
                        </div>
                        <div class="info-item">
                            <span>Localidade</span>
                            <strong><?= e($usuario['bairro']) ?>, <?= e($usuario['cidade']) ?></strong>
                        </div>
                        <div class="info-item">
                            <span>Telefone</span>
                            <strong><?= $usuario['telefone'] ? e($usuario['telefone']) : 'Não informado' ?></strong>
                        </div>
                    </div>
                </section>

                <section class="dash-panel">
                    <div class="section-intro">
                        <span class="section-kicker">Boas práticas</span>
                        <h2 class="section-title">Segurança e confiança</h2>
                    </div>

                    <ul class="insight-list clean">
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
