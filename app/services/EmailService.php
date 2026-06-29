<?php

declare(strict_types=1);

class EmailService
{
    public static function enviarConfirmacao(string $destinatario, string $nome, string $link): bool
    {
        $assunto = 'Confirme seu e-mail no ReUse';
        $mensagemTexto = "Olá, {$nome}.\n\nConfirme seu e-mail para liberar publicações e reservas no ReUse:\n{$link}\n\nEste link expira em 24 horas.";
        $mensagemHtml = '<p>Olá, ' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . '.</p>'
            . '<p>Confirme seu e-mail para liberar publicações e reservas no ReUse.</p>'
            . '<p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">Confirmar e-mail</a></p>'
            . '<p>Este link expira em 24 horas.</p>';

        if (self::carregarPhpMailer()) {
            try {
                return self::enviarComPhpMailer($destinatario, $nome, $assunto, $mensagemHtml, $mensagemTexto);
            } catch (Throwable) {
                return false;
            }
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/plain; charset=UTF-8',
            'From: ReUse <noreply@reuse.local>',
        ];

        return mail($destinatario, $assunto, $mensagemTexto, implode("\r\n", $headers));
    }

    private static function carregarPhpMailer(): bool
    {
        $autoloads = [
            dirname(__DIR__, 3) . '/private_config/vendor/autoload.php',
            dirname(__DIR__, 2) . '/vendor/autoload.php',
        ];

        foreach ($autoloads as $autoload) {
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

        return $mail->send();
    }

    private static function configSmtp(): ?array
    {
        $caminho = dirname(__DIR__, 3) . '/private_config/mail.credentials.php';
        if (is_file($caminho)) {
            $config = require $caminho;
            return is_array($config) ? $config : null;
        }

        return null;
    }
}
