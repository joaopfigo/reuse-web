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
        <nav>
            <a href="<?= e(app_url('itens/listar.php')) ?>">Itens</a>
            <a href="<?= e(app_url('itens/criar.php')) ?>">Cadastrar item</a>
            <a href="<?= e(app_url('itens/meus.php')) ?>">Meus itens</a>
            <a href="<?= e(app_url('reservas/gerenciar.php')) ?>">Minhas doações</a>
            <a href="<?= e(app_url('pontos/carteira.php')) ?>">Pontos</a>
            <a href="<?= e(app_url('impacto/painel.php')) ?>">Impacto</a>
            <a href="<?= e(app_url('notificacoes/index.php')) ?>">
                Notificações<?= $naoLidas > 0 ? ' (' . $naoLidas . ')' : '' ?>
            </a>
            <a href="<?= e(app_url('perfil.php')) ?>">Perfil</a>
            <span class="muted"><?= e($usuario['nome']) ?></span>
            <a class="btn danger" href="<?= e(app_url('logout.php')) ?>">Sair</a>
        </nav>
    </header>
    <?php
}
