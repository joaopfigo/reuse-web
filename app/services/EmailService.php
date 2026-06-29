<?php

declare(strict_types=1);

class EmailService
{
    private static string $ultimoErro = '';

    public static function enviarConfirmacao(string $destinatario, string $nome, string $link): bool
    {
        self::$ultimoErro = '';

        return self::enviar(
            $destinatario,
            $nome,
            'Confirme seu e-mail no ReUse',
            '<p>Ola, ' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . '.</p>'
                . '<p>Confirme seu e-mail para liberar publicacoes e reservas no ReUse.</p>'
                . '<p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">Confirmar e-mail</a></p>'
                . '<p>Este link expira em 24 horas.</p>',
            "Ola, {$nome}.\n\nConfirme seu e-mail para liberar publicacoes e reservas no ReUse:\n{$link}\n\nEste link expira em 24 horas."
        );
    }

    public static function enviarRedefinicaoSenha(string $destinatario, string $nome, string $link): bool
    {
        self::$ultimoErro = '';

        return self::enviar(
            $destinatario,
            $nome,
            'Redefinicao de senha no ReUse',
            '<p>Ola, ' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . '.</p>'
                . '<p>Recebemos uma solicitacao para redefinir sua senha no ReUse.</p>'
                . '<p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">Criar nova senha</a></p>'
                . '<p>Este link expira em 1 hora. Se voce nao solicitou, ignore esta mensagem.</p>',
            "Ola, {$nome}.\n\nRecebemos uma solicitacao para redefinir sua senha no ReUse:\n{$link}\n\nEste link expira em 1 hora. Se voce nao solicitou, ignore esta mensagem."
        );
    }

    public static function enviarTeste(string $destinatario, string $nome): bool
    {
        self::$ultimoErro = '';

        return self::enviar(
            $destinatario,
            $nome,
            'Teste de e-mail do ReUse',
            '<p>Ola, ' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . '.</p>'
                . '<p>Este e um teste de envio SMTP do ReUse.</p>'
                . '<p>Se voce recebeu esta mensagem, o envio de e-mail esta funcionando.</p>',
            "Ola, {$nome}.\n\nEste e um teste de envio SMTP do ReUse. Se voce recebeu esta mensagem, o envio de e-mail esta funcionando."
        );
    }

    public static function ultimoErro(): string
    {
        return self::$ultimoErro;
    }

    public static function diagnostico(): array
    {
        $autoloadEncontrado = null;
        foreach (self::autoloadPaths() as $autoload) {
            if (is_file($autoload)) {
                $autoloadEncontrado = $autoload;
                require_once $autoload;
                break;
            }
        }

        $credenciaisPath = self::credentialsPath();
        $config = self::configSmtp();

        return [
            'autoloads' => self::autoloadPaths(),
            'autoload_encontrado' => $autoloadEncontrado,
            'phpmailer_carregado' => class_exists(\PHPMailer\PHPMailer\PHPMailer::class),
            'credenciais_path' => $credenciaisPath,
            'credenciais_existem' => is_file($credenciaisPath),
            'credenciais_validas' => is_array($config),
            'host' => is_array($config) ? (string) ($config['host'] ?? '') : '',
            'port' => is_array($config) ? (string) ($config['port'] ?? '') : '',
            'secure' => is_array($config) ? (string) ($config['secure'] ?? '') : '',
            'username' => is_array($config) ? self::mascarar((string) ($config['username'] ?? '')) : '',
            'from_email' => is_array($config) ? self::mascarar((string) ($config['from_email'] ?? '')) : '',
        ];
    }

    private static function enviar(
        string $destinatario,
        string $nome,
        string $assunto,
        string $mensagemHtml,
        string $mensagemTexto
    ): bool {
        if (!self::carregarPhpMailer()) {
            self::$ultimoErro = 'PHPMailer nao foi carregado. Verifique se public_html/vendor/autoload.php existe no servidor.';
            error_log('[ReUse][email] ' . self::$ultimoErro);

            return false;
        }

        try {
            return self::enviarComPhpMailer($destinatario, $nome, $assunto, $mensagemHtml, $mensagemTexto);
        } catch (Throwable $erro) {
            self::$ultimoErro = $erro->getMessage();
            error_log('[ReUse][email] Falha PHPMailer: ' . $erro->getMessage());

            return false;
        }
    }

    private static function carregarPhpMailer(): bool
    {
        foreach (self::autoloadPaths() as $autoload) {
            if (is_file($autoload)) {
                require_once $autoload;
                break;
            }
        }

        return class_exists(\PHPMailer\PHPMailer\PHPMailer::class);
    }

    private static function enviarComPhpMailer(
        string $destinatario,
        string $nome,
        string $assunto,
        string $mensagemHtml,
        string $mensagemTexto
    ): bool {
        $config = self::configSmtp();
        if (!$config) {
            self::$ultimoErro = 'Arquivo private_config/mail.credentials.php ausente ou invalido.';
            error_log('[ReUse][email] ' . self::$ultimoErro);

            return false;
        }

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = (string) $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = (string) $config['username'];
        $mail->Password = (string) $config['password'];
        $mail->Port = (int) ($config['port'] ?? 587);
        $mail->SMTPSecure = (string) ($config['secure'] ?? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS);
        $mail->CharSet = 'UTF-8';

        $mail->setFrom((string) ($config['from_email'] ?? $config['username']), (string) ($config['from_name'] ?? 'ReUse'));
        $mail->addAddress($destinatario, $nome);
        $mail->Subject = $assunto;
        $mail->isHTML(true);
        $mail->Body = $mensagemHtml;
        $mail->AltBody = $mensagemTexto;

        $enviado = $mail->send();
        if (!$enviado) {
            self::$ultimoErro = $mail->ErrorInfo;
            error_log('[ReUse][email] PHPMailer retornou false: ' . $mail->ErrorInfo);
        }

        return $enviado;
    }

    private static function configSmtp(): ?array
    {
        $caminho = self::credentialsPath();
        if (is_file($caminho)) {
            $config = require $caminho;
            return is_array($config) ? $config : null;
        }

        return null;
    }

    private static function autoloadPaths(): array
    {
        return [
            dirname(__DIR__, 3) . '/private_config/vendor/autoload.php',
            dirname(__DIR__, 2) . '/vendor/autoload.php',
        ];
    }

    private static function credentialsPath(): string
    {
        return dirname(__DIR__, 3) . '/private_config/mail.credentials.php';
    }

    private static function mascarar(string $valor): string
    {
        if ($valor === '') {
            return '';
        }

        if (str_contains($valor, '@')) {
            [$usuario, $dominio] = explode('@', $valor, 2);
            return substr($usuario, 0, 2) . '***@' . $dominio;
        }

        return substr($valor, 0, 3) . '***';
    }
}
