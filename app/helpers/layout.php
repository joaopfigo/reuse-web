<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function render_topbar(array $usuario): void
{
    ?>
    <header class="topbar">
        <a class="logo" href="<?= e(app_url('itens/listar.php')) ?>">ReUse</a>
        <nav>
            <a href="<?= e(app_url('itens/listar.php')) ?>">Itens</a>
            <a href="<?= e(app_url('itens/criar.php')) ?>">Cadastrar item</a>
            <a href="<?= e(app_url('pontos/carteira.php')) ?>">Pontos</a>
            <a href="<?= e(app_url('perfil.php')) ?>">Perfil</a>
            <span class="muted"><?= e($usuario['nome']) ?></span>
            <a class="btn danger" href="<?= e(app_url('logout.php')) ?>">Sair</a>
        </nav>
    </header>
    <?php
}
