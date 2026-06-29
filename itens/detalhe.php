<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/repositories/ItemRepo.php';
require_once __DIR__ . '/../app/repositories/UsuarioRepo.php';
require_once __DIR__ . '/../app/repositories/ReservaRepo.php';

$usuario = exigir_login();
$item = ItemRepo::buscarPorId((int) ($_GET['id'] ?? 0));
$fotos = $item ? ItemRepo::fotosDoItem((int) $item['id']) : [];
$doadoraResumo = $item ? UsuarioRepo::resumoPublico((int) $item['doadora_id']) : null;
$saldoDisponivel = ReservaRepo::saldoDisponivel($usuario);
$podeReservar = $item
    && (int) $item['doadora_id'] !== (int) $usuario['id']
    && $item['status'] === 'disponivel'
    && conta_verificada($usuario)
    && $saldoDisponivel >= (int) $item['pontos'];
$avaliacaoMedia = $doadoraResumo['avaliacao_media'] ?? null;
$itensDoados = (int) ($doadoraResumo['itens_doados'] ?? 0);
$noShows = (int) ($doadoraResumo['no_show_count'] ?? 0);
$seloConfianca = $itensDoados >= 3 && $noShows === 0 ? 'Conta confiável' : 'Perfil em construção';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Detalhe do item</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/experience.css?v=20260624">
</head>
<body>
    <?php render_topbar($usuario); ?>

    <main class="container ops-page">
        <?php if (!$item): ?>
            <section class="empty-card">Item não encontrado.</section>
        <?php else: ?>
            <section class="ops-hero compact">
                <div class="ops-hero-main">
                    <span class="ops-kicker">Detalhe do item</span>
                    <h1 class="ops-title"><?= e($item['titulo']) ?></h1>
                    <p class="ops-copy"><?= e($item['bairro']) ?> · Doador(a): <?= e($item['doadora']) ?></p>
                </div>
                <div class="ops-hero-side">
                    <span class="<?= e(status_badge_class((string) $item['status'])) ?>"><?= e(status_label((string) $item['status'])) ?></span>
                </div>
            </section>

            <section class="detail-layout">
                <div class="detail-main">
                    <div class="detail-image-frame">
                        <?php if ($fotos): ?>
                            <img src="../<?= e($fotos[0]['caminho']) ?>" alt="Foto de <?= e($item['titulo']) ?>">
                        <?php else: ?>
                            <div class="detail-placeholder">Sem foto disponível</div>
                        <?php endif; ?>
                    </div>

                    <?php if (count($fotos) > 1): ?>
                        <div class="detail-thumb-grid">
                            <?php foreach ($fotos as $foto): ?>
                                <img src="../<?= e($foto['caminho']) ?>" alt="Foto adicional de <?= e($item['titulo']) ?>">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="content-card stack-note">
                        <div class="section-header">
                            <h2>Descrição</h2>
                        </div>
                        <p><?= nl2br(e($item['descricao'])) ?></p>
                    </div>
                </div>

                <aside class="detail-media">
                    <section class="surface-panel">
                        <div class="section-header">
                            <h2>Resumo do item</h2>
                            <p>Veja rapidamente a categoria, a condição e os pontos necessários para reservar.</p>
                        </div>

                        <div class="pill-row">
                            <span class="meta-pill"><?= e($item['categoria']) ?></span>
                            <span class="meta-pill"><?= e(status_label((string) $item['condicao'])) ?></span>
                            <span class="meta-pill"><?= (int) $item['pontos'] ?> pontos</span>
                        </div>

                        <?php if ((int) $item['doadora_id'] === (int) $usuario['id']): ?>
                            <div class="soft-note">Este item é seu. Você pode editar o anúncio enquanto ele estiver disponível ou pausado.</div>
                            <div class="list-card-actions">
                                <a class="btn secondary" href="editar.php?id=<?= (int) $item['id'] ?>">Editar meu anúncio</a>
                            </div>
                        <?php else: ?>
                            <div class="soft-note">Ao reservar, você passa a acompanhar local, horário e confirmação em "Minhas reservas".</div>
                            <?php if (!$podeReservar): ?>
                                <div class="alert warning">
                                    <?php if (!conta_verificada($usuario)): ?>
                                        Confirme seu e-mail para reservar itens no ReUse.
                                    <?php elseif ($item['status'] !== 'disponivel'): ?>
                                        Este item não está disponível para reserva no momento.
                                    <?php elseif ($saldoDisponivel < (int) $item['pontos']): ?>
                                        Você tem <?= $saldoDisponivel ?> ponto(s) livres, mas este item custa <?= (int) $item['pontos'] ?> ponto(s).
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="list-card-actions">
                                <form method="post" action="../reservas/reservar.php" class="inline-form">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                    <button class="btn primary" type="submit" <?= $podeReservar ? '' : 'disabled' ?>>Reservar item</button>
                                </form>
                                <a class="btn danger" href="../denuncias/reportar.php?item_id=<?= (int) $item['id'] ?>">Denunciar item</a>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="surface-panel">
                        <div class="section-header">
                            <h2>Informações do(a) doador(a)</h2>
                            <p>Um resumo rápido sobre quem publicou este item dentro da rede.</p>
                        </div>

                        <div class="donor-info-grid">
                            <div class="soft-note trust-note">
                                <strong><?= e($seloConfianca) ?></strong>
                                <span>Resumo calculado a partir de entregas confirmadas, avaliações e não comparecimentos registrados.</span>
                            </div>

                            <div class="donor-stat">
                                <span class="donor-stat-label">Nome</span>
                                <strong class="donor-stat-value"><?= e($doadoraResumo['nome'] ?? $item['doadora']) ?></strong>
                            </div>

                            <div class="donor-stat">
                                <span class="donor-stat-label">Avaliação média</span>
                                <strong class="donor-stat-value">
                                    <?php if ($avaliacaoMedia !== null): ?>
                                        <span class="rating-stars" aria-label="Avaliação média <?= e(number_format((float) $avaliacaoMedia, 1, ',', '.')) ?> de 5">★★★★★</span>
                                        <?= number_format((float) $avaliacaoMedia, 1, ',', '.') ?> / 5
                                    <?php else: ?>
                                        Ainda sem avaliações
                                    <?php endif; ?>
                                </strong>
                            </div>

                            <div class="donor-stat">
                                <span class="donor-stat-label">Itens doados</span>
                                <strong class="donor-stat-value"><?= $itensDoados ?></strong>
                            </div>

                            <div class="donor-stat">
                                <span class="donor-stat-label">Não comparecimentos</span>
                                <strong class="donor-stat-value"><?= $noShows ?></strong>
                            </div>
                        </div>
                    </section>

                    <section class="surface-panel">
                        <div class="section-header">
                            <h2>Boas práticas</h2>
                            <p>Alguns cuidados simples ajudam a tornar a retirada mais segura para as duas partes.</p>
                        </div>
                        <ul class="guideline-list">
                            <li>Combine sempre um ponto público e fácil de encontrar.</li>
                            <li>Evite compartilhar endereço residencial em qualquer etapa.</li>
                            <li>Finalize a entrega usando o código de confirmação no encontro.</li>
                        </ul>
                    </section>
                </aside>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
