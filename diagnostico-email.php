<?php
require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/helpers/layout.php';
require_once __DIR__ . '/app/services/EmailService.php';

$usuario = exigir_login();
$diagnostico = EmailService::diagnostico();
$resultado = '';
$tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf_post();
    $enviado = EmailService::enviarTeste((string) $usuario['email'], (string) $usuario['nome']);
    $tipo = $enviado ? 'success' : 'error';
    $resultado = $enviado
        ? 'E-mail de teste enviado. Verifique a caixa de entrada e o spam.'
        : 'Falha ao enviar: ' . EmailService::ultimoErro();
    $diagnostico = EmailService::diagnostico();
}

function sim_nao(bool $valor): string
{
    return $valor ? 'SIM' : 'NAO';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Diagnostico de e-mail</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/experience.css?v=20260624">
</head>
<body>
    <?php render_topbar($usuario); ?>

    <main class="container ops-page">
        <section class="surface-panel">
            <div class="section-header">
                <span class="ops-kicker">SMTP</span>
                <h1 class="ops-title">Diagnostico de e-mail</h1>
                <p>Use esta tela para descobrir por que a confirmacao de conta nao esta chegando.</p>
            </div>

            <?php if ($resultado): ?>
                <div class="alert <?= e($tipo) ?>"><?= e($resultado) ?></div>
            <?php endif; ?>

            <div class="info-grid">
                <div class="info-item">
                    <span>PHPMailer carregado</span>
                    <strong><?= e(sim_nao((bool) $diagnostico['phpmailer_carregado'])) ?></strong>
                </div>
                <div class="info-item">
                    <span>Autoload encontrado</span>
                    <strong><?= $diagnostico['autoload_encontrado'] ? 'SIM' : 'NAO' ?></strong>
                </div>
                <div class="info-item">
                    <span>Credenciais existem</span>
                    <strong><?= e(sim_nao((bool) $diagnostico['credenciais_existem'])) ?></strong>
                </div>
                <div class="info-item">
                    <span>Credenciais validas</span>
                    <strong><?= e(sim_nao((bool) $diagnostico['credenciais_validas'])) ?></strong>
                </div>
                <div class="info-item">
                    <span>Host SMTP</span>
                    <strong><?= e((string) $diagnostico['host']) ?></strong>
                </div>
                <div class="info-item">
                    <span>Porta/seguranca</span>
                    <strong><?= e((string) $diagnostico['port']) ?> / <?= e((string) $diagnostico['secure']) ?></strong>
                </div>
                <div class="info-item">
                    <span>Usuario SMTP</span>
                    <strong><?= e((string) $diagnostico['username']) ?></strong>
                </div>
                <div class="info-item">
                    <span>Remetente</span>
                    <strong><?= e((string) $diagnostico['from_email']) ?></strong>
                </div>
            </div>

            <div class="soft-note">
                <strong>Caminho esperado das credenciais:</strong>
                <span><?= e((string) $diagnostico['credenciais_path']) ?></span>
            </div>

            <div class="soft-note">
                <strong>Caminhos testados para PHPMailer:</strong>
                <span><?= e(implode(' | ', $diagnostico['autoloads'])) ?></span>
            </div>

            <form method="post" class="action-row">
                <?= csrf_input() ?>
                <button class="btn primary" type="submit">Enviar e-mail de teste para minha conta</button>
                <a class="btn secondary" href="perfil.php">Voltar ao perfil</a>
            </form>
        </section>
    </main>
</body>
</html>
