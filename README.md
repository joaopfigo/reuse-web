# Código-fonte do ReUse

Esta pasta contém a aplicação PHP/MySQL do ReUse.

## Estrutura

- `app/config`: conexão com banco e exemplos de configuração
- `app/helpers`: autenticação, layout, CSRF, upload e validações
- `app/repositories`: acesso ao banco de dados
- `app/services`: regras de negócio e integrações externas
- `assets`: CSS, JavaScript, ícones e recursos de interface
- `db`: schema, migrations e seed de demonstração
- `itens`: listagem, cadastro, edição e detalhe de itens
- `reservas`: reserva, aceite, chat, confirmação e no-show
- `pontos`: carteira, compra de pontos, retorno e webhook do Mercado Pago
- `usuarios`: perfil público e reputação
- `denuncias`, `avaliacoes`, `notificacoes`, `impacto`: módulos de confiança e acompanhamento
- `uploads`: arquivos enviados por usuários

## Dependências

O envio de e-mail usa PHPMailer instalado via Composer:

```bash
composer require phpmailer/phpmailer
```

O diretório `vendor/` precisa estar disponível no deploy da Hostinger.

## Credenciais

Credenciais reais ficam fora do `public_html`:

```text
private_config/db.credentials.php
private_config/mail.credentials.php
private_config/mercadopago.credentials.php
```

Use os arquivos `*.example.php` em `app/config` como modelo.

## Rotas úteis

- `/login.php`
- `/cadastro.php`
- `/perfil.php`
- `/seguranca.php`
- `/itens/listar.php`
- `/itens/criar.php`
- `/reservas/minhas.php`
- `/reservas/gerenciar.php`
- `/pontos/carteira.php`
- `/pontos/comprar.php`
- `/impacto/painel.php`

## Segurança

- Senhas são armazenadas com `password_hash`.
- Consultas usam PDO com prepared statements.
- Formulários sensíveis usam CSRF.
- Uploads validam tipo, tamanho e dimensões.
- E-mail precisa ser confirmado para publicar e reservar.
- Pontos só são creditados por entrega confirmada ou compra aprovada.
- Arquivos de diagnóstico ficam bloqueados por padrão.
