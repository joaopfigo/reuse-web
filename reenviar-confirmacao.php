<?php
require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/services/AuthService.php';

$usuario = exigir_login();
exigir_post();
validar_csrf_post();

if (!empty($usuario['email_verificado_em'])) {
    flash_set('success', 'Seu e-mail ja esta confirmado.');
    header('Location: ' . app_url('perfil.php'));
    exit;
}

try {
    $enviado = AuthService::reenviarConfirmacao($usuario);
    flash_set(
        $enviado ? 'success' : 'error',
        $enviado
            ? 'Enviamos um novo link de confirmacao para o seu e-mail.'
            : 'Nao foi possivel enviar o e-mail agora. Verifique a configuracao SMTP.'
    );
} catch (Throwable $e) {
    flash_set('error', 'Nao foi possivel gerar um novo link de confirmacao agora.');
}

header('Location: ' . app_url('perfil.php'));
exit;
