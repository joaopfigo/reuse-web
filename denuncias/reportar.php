<?php
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/layout.php';
require_once __DIR__ . '/../app/helpers/validar.php';
require_once __DIR__ . '/../app/repositories/DenunciaRepo.php';
require_once __DIR__ . '/../app/repositories/ItemRepo.php';
require_once __DIR__ . '/../app/repositories/ReservaRepo.php';

$usuario = exigir_login();
$itemId = (int) ($_GET['item_id'] ?? 0);
$reservaId = (int) ($_GET['reserva_id'] ?? 0);
$denunciadaId = null;
$erros = [];
$item = null;
$contextoTitulo = '';

$reserva = null;
if ($reservaId > 0) {
    $reserva = ReservaRepo::buscarPorId($reservaId);
    $ehReceptora = $reserva && (int) $reserva['receptora_id'] === (int) $usuario['id'];
    $ehDoadora = $reserva && (int) $reserva['doadora_id'] === (int) $usuario['id'];

    if (!$reserva || (!$ehReceptora && !$ehDoadora)) {
        flash_set('error', 'Reserva inválida.');
        header('Location: ' . app_url('reservas/minhas.php'));
        exit;
    }

    $itemId = (int) $reserva['item_id'];
    $denunciadaId = $ehReceptora ? (int) $reserva['doadora_id'] : (int) $reserva['receptora_id'];
    $contextoTitulo = 'Reserva do item "' . $reserva['titulo'] . '"';
} elseif ($itemId > 0) {
    $item = ItemRepo::buscarPorId($itemId);

    if (!$item) {
        flash_set('error', 'Item não encontrado.');
        header('Location: ' . app_url('itens/listar.php'));
        exit;
    }

    if ((int) $item['doadora_id'] === (int) $usuario['id']) {
        flash_set('error', 'Você não pode denunciar seu próprio item.');
        header('Location: ' . app_url('itens/detalhe.php?id=' . $itemId));
        exit;
    }

    $denunciadaId = (int) $item['doadora_id'];
    $contextoTitulo = 'Item "' . $item['titulo'] . '"';
} else {
    flash_set('error', 'Informe um item ou reserva para denúncia.');
    header('Location: ' . app_url('itens/listar.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf_post();
    $motivo = (string) ($_POST['motivo'] ?? '');
    $descricao = trim((string) ($_POST['descricao'] ?? ''));
    $validos = ['item_falso', 'comportamento', 'no_show', 'outro'];

    if (!in_array($motivo, $validos, true)) {
        $erros[] = 'Selecione um motivo válido.';
    }
    if (strlen($descricao) < 10) {
        $erros[] = 'Descreva a ocorrência com pelo menos 10 caracteres.';
    }

    if (!$erros) {
        try {
            $evidenciaPath = salvar_upload_denuncia($_FILES['evidencia'] ?? []);

            DenunciaRepo::criar(
                (int) $usuario['id'],
                $motivo,
                $descricao,
                $itemId ?: null,
                $reservaId ?: null,
                $denunciadaId,
                $evidenciaPath
            );
            flash_set('success', 'Denúncia registrada. Obrigada por ajudar a manter a comunidade segura.');
            header('Location: ' . app_url($reserva ? 'reservas/minhas.php' : 'itens/listar.php'));
            exit;
        } catch (Throwable $erroUpload) {
            $erros[] = $erroUpload->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse | Denunciar</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/experience.css?v=20260624">
</head>
<body>
    <?php render_topbar($usuario); ?>
    <main class="container ops-page">
        <section class="ops-hero compact">
            <div class="ops-hero-main">
                <span class="ops-kicker">Segurança da comunidade</span>
                <h1 class="ops-title">Registrar denúncia</h1>
                <p class="ops-copy">Use este canal para relatar item enganoso, comportamento inadequado ou problemas no encontro de retirada.</p>
            </div>
            <div class="ops-hero-side">
                <div class="ops-side-card">
                    <span>Contexto</span>
                    <strong><?= e($contextoTitulo) ?></strong>
                </div>
            </div>
        </section>

        <section class="form-layout">
            <form method="post" enctype="multipart/form-data" class="form-main">
                <section class="surface-panel">
                    <div class="section-header">
                        <h2>Detalhes da ocorrência</h2>
                        <p>Descreva apenas fatos relevantes. A denúncia fica registrada para análise do histórico de segurança.</p>
                    </div>

                    <?php foreach ($erros as $erro): ?>
                        <div class="alert error"><?= e($erro) ?></div>
                    <?php endforeach; ?>

                    <?= csrf_input() ?>

                    <label>
                        Motivo
                        <select name="motivo" required>
                            <option value="">Selecione...</option>
                            <option value="item_falso" <?= ($_POST['motivo'] ?? '') === 'item_falso' ? 'selected' : '' ?>>Item falso ou enganoso</option>
                            <option value="comportamento" <?= ($_POST['motivo'] ?? '') === 'comportamento' ? 'selected' : '' ?>>Comportamento inadequado</option>
                            <option value="no_show" <?= ($_POST['motivo'] ?? '') === 'no_show' ? 'selected' : '' ?>>Não compareceu ao encontro</option>
                            <option value="outro" <?= ($_POST['motivo'] ?? '') === 'outro' ? 'selected' : '' ?>>Outro</option>
                        </select>
                    </label>

                    <label>
                        Descrição
                        <textarea name="descricao" rows="5" required maxlength="500" placeholder="Descreva o que aconteceu, quando ocorreu e quais combinados foram descumpridos."><?= e($_POST['descricao'] ?? '') ?></textarea>
                    </label>

                    <label>
                        Evidência opcional (JPG, PNG ou WEBP até 4 MB)
                        <input type="file" name="evidencia" accept="image/jpeg,image/png,image/webp" data-image-preview-input>
                    </label>
                    <div class="image-preview" data-image-preview hidden></div>

                    <div class="list-card-actions">
                        <button type="submit" class="btn danger">Enviar denúncia</button>
                        <a href="<?= e($reserva ? app_url('reservas/minhas.php') : app_url('itens/detalhe.php?id=' . $itemId)) ?>" class="btn">Cancelar</a>
                    </div>
                </section>
            </form>

            <aside class="form-side">
                <section class="surface-panel">
                    <div class="section-header">
                        <h2>Como analisamos</h2>
                        <p>A denúncia não expõe seus dados no feed e fica vinculada ao contexto da troca.</p>
                    </div>
                    <ul class="guideline-list">
                        <li>Evite incluir endereço completo ou dados pessoais sensíveis.</li>
                        <li>Anexe imagem apenas quando ela ajudar a comprovar a situação.</li>
                        <li>Relatos objetivos ajudam a manter a rede mais segura.</li>
                    </ul>
                </section>
            </aside>
        </section>
    </main>
</body>
</html>
