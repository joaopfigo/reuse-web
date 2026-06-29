<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../repositories/NotificacaoRepo.php';

function render_topbar(array $usuario): void
{
    $naoLidas = NotificacaoRepo::naoLidas((int) $usuario['id']);
    ?>
    <header class="topbar">
        <a class="logo" href="<?= e(app_url('itens/listar.php')) ?>">ReUse</a>

        <button class="nav-toggle" aria-label="Abrir menu" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <nav>
            <a href="<?= e(app_url('itens/listar.php')) ?>">Itens</a>
            <a href="<?= e(app_url('itens/criar.php')) ?>">Cadastrar item</a>
            <a href="<?= e(app_url('itens/meus.php')) ?>">Meus itens</a>
            <a href="<?= e(app_url('reservas/gerenciar.php')) ?>">Minhas doações</a>
            <a href="<?= e(app_url('reservas/minhas.php')) ?>">Minhas reservas</a>
            <a href="<?= e(app_url('pontos/carteira.php')) ?>">Pontos</a>
            <a href="<?= e(app_url('impacto/painel.php')) ?>">Impacto</a>
            <a href="<?= e(app_url('notificacoes/index.php')) ?>">
                Notificações<?php if ($naoLidas > 0): ?><span class="notif-badge"><?= $naoLidas ?></span><?php endif; ?>
            </a>
            <a href="<?= e(app_url('perfil.php')) ?>">Perfil</a>
            <span class="muted"><?= e($usuario['nome']) ?></span>
            <form method="post" action="<?= e(app_url('logout.php')) ?>" class="inline-form">
                <?= csrf_input() ?>
                <button class="btn danger" type="submit">Sair</button>
            </form>
        </nav>
    </header>
    <script src="<?= e(app_url('assets/js/app.js?v=20260624')) ?>" defer></script>
    <script src="<?= e(app_url('assets/js/pwa.js?v=20260624')) ?>" defer></script>
    <?php
}
