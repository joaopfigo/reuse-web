<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/validar.php';

class ItemRepo
{
    private const CATEGORIA_COSMETICOS_ID = 3;

    public static function categorias(): array
    {
        return db()->query(
            'SELECT id, nome
             FROM categorias
             WHERE ativa = 1
               AND id IN (1, 2, 3, 4, 5)
             ORDER BY CASE id
                 WHEN 1 THEN 1
                 WHEN 2 THEN 2
                 WHEN 3 THEN 3
                 WHEN 4 THEN 4
                 WHEN 5 THEN 5
                 ELSE 99
             END'
        )->fetchAll();
    }

    public static function criar(array $dados, array $fotoPaths = []): int
    {
        if ((int) $dados['categoria_id'] === self::CATEGORIA_COSMETICOS_ID) {
            $dados['condicao'] = 'novo';
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            $sql = 'INSERT INTO itens (doadora_id, categoria_id, titulo, descricao, condicao, pontos, bairro, cidade)
                    VALUES (:doadora_id, :categoria_id, :titulo, :descricao, :condicao, :pontos, :bairro, :cidade)';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':doadora_id' => $dados['doadora_id'],
                ':categoria_id' => $dados['categoria_id'],
                ':titulo' => $dados['titulo'],
                ':descricao' => $dados['descricao'],
                ':condicao' => $dados['condicao'],
                ':pontos' => $dados['pontos'],
                ':bairro' => $dados['bairro'],
                ':cidade' => $dados['cidade'] ?? 'Belo Horizonte',
            ]);

            $itemId = (int) $pdo->lastInsertId();

            if ($fotoPaths) {
                $temHash = self::itemFotosTemHashPerceptual($pdo);
                $foto = $pdo->prepare(
                    $temHash
                        ? 'INSERT INTO item_fotos (item_id, caminho, hash_perceptual, ordem) VALUES (:item_id, :caminho, :hash_perceptual, :ordem)'
                        : 'INSERT INTO item_fotos (item_id, caminho, ordem) VALUES (:item_id, :caminho, :ordem)'
                );
                foreach (array_values($fotoPaths) as $ordem => $fotoPath) {
                    $caminho = is_array($fotoPath) ? (string) $fotoPath['caminho'] : (string) $fotoPath;
                    $params = [
                        ':item_id' => $itemId,
                        ':caminho' => $caminho,
                        ':ordem' => $ordem + 1,
                    ];

                    if ($temHash) {
                        $params[':hash_perceptual'] = is_array($fotoPath) ? ($fotoPath['hash_perceptual'] ?? null) : null;
                    }

                    $foto->execute($params);
                }
            }

            $pdo->commit();
            return $itemId;
        } catch (Throwable $erro) {
            $pdo->rollBack();
            throw $erro;
        }
    }

    public static function listar(array $filtros = []): array
    {
        [$where, $params] = self::montarFiltros($filtros);
        $limite = max(1, min(48, (int) ($filtros['limite'] ?? 12)));
        $offset = max(0, (int) ($filtros['offset'] ?? 0));
        $ordenacao = self::ordenacaoSql((string) ($filtros['ordem'] ?? 'recentes'));

        $sql = 'SELECT i.*, c.nome AS categoria, u.nome AS doadora, f.caminho AS foto
                FROM itens i
                JOIN categorias c ON c.id = i.categoria_id
                JOIN usuarios u ON u.id = i.doadora_id
                LEFT JOIN item_fotos f ON f.item_id = i.id AND f.ordem = 1
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY ' . $ordenacao . '
                LIMIT ' . $limite . ' OFFSET ' . $offset;

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function contar(array $filtros = []): int
    {
        [$where, $params] = self::montarFiltros($filtros);

        $sql = 'SELECT COUNT(*)
                FROM itens i
                JOIN categorias c ON c.id = i.categoria_id
                JOIN usuarios u ON u.id = i.doadora_id
                WHERE ' . implode(' AND ', $where);

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private static function montarFiltros(array $filtros): array
    {
        $where = ['i.status = "disponivel"'];
        $params = [];

        if (!empty($filtros['q'])) {
            $termos = preg_split('/\s+/', trim((string) $filtros['q'])) ?: [];
            $condicoesBusca = [];

            foreach ($termos as $indice => $termo) {
                if ($termo === '') {
                    continue;
                }

                $tituloParametro = ':q' . $indice . '_titulo';
                $descricaoParametro = ':q' . $indice . '_descricao';
                $bairroParametro = ':q' . $indice . '_bairro';
                $cidadeParametro = ':q' . $indice . '_cidade';
                $valorBusca = '%' . $termo . '%';

                $condicoesBusca[] = '(
                    i.titulo LIKE ' . $tituloParametro . '
                    OR i.descricao LIKE ' . $descricaoParametro . '
                    OR i.bairro LIKE ' . $bairroParametro . '
                    OR i.cidade LIKE ' . $cidadeParametro . '
                )';
                $params[$tituloParametro] = $valorBusca;
                $params[$descricaoParametro] = $valorBusca;
                $params[$bairroParametro] = $valorBusca;
                $params[$cidadeParametro] = $valorBusca;
            }

            if ($condicoesBusca) {
                $where[] = '(' . implode(' AND ', $condicoesBusca) . ')';
            }
        }

        if (!empty($filtros['categoria_id'])) {
            $where[] = 'i.categoria_id = :categoria_id';
            $params[':categoria_id'] = $filtros['categoria_id'];
        }

        if (!empty($filtros['condicao'])) {
            $where[] = 'i.condicao = :condicao';
            $params[':condicao'] = $filtros['condicao'];
        }

        if (!empty($filtros['local'])) {
            $where[] = '(i.bairro LIKE :local_bairro OR i.cidade LIKE :local_cidade)';
            $params[':local_bairro'] = '%' . trim((string) $filtros['local']) . '%';
            $params[':local_cidade'] = '%' . trim((string) $filtros['local']) . '%';
        }

        return [$where, $params];
    }

    private static function ordenacaoSql(string $ordem): string
    {
        return match ($ordem) {
            'pontos_menor' => 'i.pontos ASC, i.criado_em DESC',
            'pontos_maior' => 'i.pontos DESC, i.criado_em DESC',
            'titulo' => 'i.titulo ASC, i.criado_em DESC',
            default => 'i.criado_em DESC',
        };
    }

    public static function buscarPorId(int $id): ?array
    {
        $sql = 'SELECT i.*, c.nome AS categoria, u.nome AS doadora, f.caminho AS foto
                FROM itens i
                JOIN categorias c ON c.id = i.categoria_id
                JOIN usuarios u ON u.id = i.doadora_id
                LEFT JOIN item_fotos f ON f.item_id = i.id AND f.ordem = 1
                WHERE i.id = :id';

        $stmt = db()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch();

        return $item ?: null;
    }

    public static function fotosDoItem(int $id): array
    {
        $stmt = db()->prepare(
            'SELECT caminho, ordem
             FROM item_fotos
             WHERE item_id = :id
             ORDER BY ordem ASC, id ASC'
        );
        $stmt->execute([':id' => $id]);

        return $stmt->fetchAll();
    }

    public static function atualizar(int $id, int $doadoraId, array $dados): bool
    {
        if ((int) $dados['categoria_id'] === self::CATEGORIA_COSMETICOS_ID) {
            $dados['condicao'] = 'novo';
        }

        $sql = 'UPDATE itens
                SET categoria_id = :categoria_id,
                    titulo = :titulo,
                    descricao = :descricao,
                    condicao = :condicao,
                    pontos = :pontos,
                    bairro = :bairro,
                    cidade = :cidade,
                    atualizado_em = NOW()
                WHERE id = :id AND doadora_id = :doadora_id';

        $stmt = db()->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':doadora_id' => $doadoraId,
            ':categoria_id' => $dados['categoria_id'],
            ':titulo' => $dados['titulo'],
            ':descricao' => $dados['descricao'],
            ':condicao' => $dados['condicao'],
            ':pontos' => $dados['pontos'],
            ':bairro' => $dados['bairro'],
            ':cidade' => $dados['cidade'] ?? 'Belo Horizonte',
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function meusItens(int $doadoraId): array
    {
        $sql = 'SELECT i.*, c.nome AS categoria, f.caminho AS foto
                FROM itens i
                JOIN categorias c ON c.id = i.categoria_id
                LEFT JOIN item_fotos f ON f.item_id = i.id AND f.ordem = 1
                WHERE i.doadora_id = :doadora_id AND i.status <> "cancelado"
                ORDER BY i.criado_em DESC';

        $stmt = db()->prepare($sql);
        $stmt->execute([':doadora_id' => $doadoraId]);

        return $stmt->fetchAll();
    }

    public static function pausar(int $id, int $doadoraId): bool
    {
        $stmt = db()->prepare(
            'UPDATE itens SET status = "pausado", atualizado_em = NOW()
             WHERE id = :id AND doadora_id = :doadora_id AND status = "disponivel"'
        );
        $stmt->execute([':id' => $id, ':doadora_id' => $doadoraId]);

        return $stmt->rowCount() > 0;
    }

    public static function reativar(int $id, int $doadoraId): bool
    {
        $stmt = db()->prepare(
            'UPDATE itens SET status = "disponivel", atualizado_em = NOW()
             WHERE id = :id AND doadora_id = :doadora_id AND status = "pausado"'
        );
        $stmt->execute([':id' => $id, ':doadora_id' => $doadoraId]);

        return $stmt->rowCount() > 0;
    }

    public static function excluir(int $id, int $doadoraId): bool
    {
        // Soft-delete: marca como cancelado, preservando o historico de
        // reservas/pontos. So permite excluir itens nao reservados nem entregues.
        $stmt = db()->prepare(
            'UPDATE itens SET status = "cancelado", atualizado_em = NOW()
             WHERE id = :id AND doadora_id = :doadora_id AND status IN ("disponivel","pausado")'
        );
        $stmt->execute([':id' => $id, ':doadora_id' => $doadoraId]);

        return $stmt->rowCount() > 0;
    }

    public static function verificarDuplicidadeTexto(array $dados): ?array
    {
        $stmt = db()->prepare(
            'SELECT id, titulo, descricao, categoria_id, condicao, bairro, cidade
             FROM itens
             WHERE doadora_id = :doadora_id
               AND status IN ("disponivel", "reservado", "pausado")
               AND categoria_id = :categoria_id
               AND condicao = :condicao'
        );
        $stmt->execute([
            ':doadora_id' => $dados['doadora_id'],
            ':categoria_id' => $dados['categoria_id'],
            ':condicao' => $dados['condicao'],
        ]);

        $tituloNovo = normalizar_texto_item((string) $dados['titulo']);
        $descricaoNova = normalizar_texto_item((string) ($dados['descricao'] ?? ''));
        $bairroNovo = normalizar_texto_item((string) $dados['bairro']);
        $cidadeNova = normalizar_texto_item((string) ($dados['cidade'] ?? ''));

        foreach ($stmt->fetchAll() as $item) {
            $tituloExistente = normalizar_texto_item((string) $item['titulo']);
            $descricaoExistente = normalizar_texto_item((string) $item['descricao']);
            $mesmoLocal = $bairroNovo === normalizar_texto_item((string) $item['bairro'])
                && $cidadeNova === normalizar_texto_item((string) $item['cidade']);

            if ($tituloNovo !== '' && $tituloNovo === $tituloExistente) {
                return [
                    'tipo' => 'bloqueio',
                    'mensagem' => 'Encontramos um item parecido ja cadastrado por voce. Edite o anuncio existente ou pause o item anterior antes de criar outro anuncio igual.',
                ];
            }

            $similaridadeTitulo = similaridade_texto_item($tituloNovo, $tituloExistente);
            $similaridadeDescricao = similaridade_texto_item($descricaoNova, $descricaoExistente);

            if ($mesmoLocal && ($similaridadeTitulo >= 0.88 || ($similaridadeTitulo >= 0.76 && $similaridadeDescricao >= 0.72))) {
                return [
                    'tipo' => 'alerta',
                    'mensagem' => 'Encontramos um item parecido ja cadastrado por voce. Edite o anuncio existente ou pause o item anterior antes de criar outro anuncio igual.',
                ];
            }
        }

        return null;
    }

    public static function verificarDuplicidadeImagem(int $doadoraId, int $categoriaId, array $fotos): ?array
    {
        $hashes = array_values(array_filter(array_map(
            static fn ($foto) => is_array($foto) ? ($foto['hash_perceptual'] ?? null) : null,
            $fotos
        )));

        if (!$hashes || !self::itemFotosTemHashPerceptual(db())) {
            return null;
        }

        $stmt = db()->prepare(
            'SELECT f.hash_perceptual
             FROM item_fotos f
             JOIN itens i ON i.id = f.item_id
             WHERE i.doadora_id = :doadora_id
               AND i.categoria_id = :categoria_id
               AND i.status IN ("disponivel", "reservado", "pausado")
               AND f.hash_perceptual IS NOT NULL'
        );
        $stmt->execute([
            ':doadora_id' => $doadoraId,
            ':categoria_id' => $categoriaId,
        ]);

        $menorDistancia = PHP_INT_MAX;
        foreach ($stmt->fetchAll() as $fotoExistente) {
            foreach ($hashes as $hashNovo) {
                $menorDistancia = min($menorDistancia, distancia_hamming_hash((string) $hashNovo, (string) $fotoExistente['hash_perceptual']));
            }
        }

        if ($menorDistancia <= 5) {
            return [
                'tipo' => 'bloqueio',
                'mensagem' => 'Encontramos uma foto muito parecida em outro item seu ja cadastrado. Edite o anuncio existente ou pause o item anterior antes de criar outro anuncio igual.',
            ];
        }

        if ($menorDistancia <= 10) {
            return [
                'tipo' => 'alerta',
                'mensagem' => 'Encontramos uma foto semelhante em outro anuncio seu. Verifique se nao e o mesmo item antes de continuar.',
            ];
        }

        return null;
    }

    private static function itemFotosTemHashPerceptual(PDO $pdo): bool
    {
        static $temColuna = null;
        if ($temColuna !== null) {
            return $temColuna;
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM item_fotos LIKE 'hash_perceptual'");
        $temColuna = (bool) $stmt->fetch();

        return $temColuna;
    }
}
