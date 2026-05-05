-- ReUse - Modelo inicial de banco de dados
-- Banco alvo: PostgreSQL

CREATE TABLE usuarios (
    id BIGSERIAL PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    telefone VARCHAR(30),
    bairro VARCHAR(100),
    cidade VARCHAR(100) NOT NULL DEFAULT 'Belo Horizonte',
    saldo_pontos INTEGER NOT NULL DEFAULT 0 CHECK (saldo_pontos >= 0),
    reputacao_media NUMERIC(3,2) DEFAULT 0 CHECK (reputacao_media >= 0 AND reputacao_media <= 5),
    total_avaliacoes INTEGER NOT NULL DEFAULT 0 CHECK (total_avaliacoes >= 0),
    faltas_retirada INTEGER NOT NULL DEFAULT 0 CHECK (faltas_retirada >= 0),
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categorias (
    id BIGSERIAL PRIMARY KEY,
    nome VARCHAR(80) NOT NULL UNIQUE,
    descricao TEXT,
    ativa BOOLEAN NOT NULL DEFAULT TRUE,
    criada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE itens (
    id BIGSERIAL PRIMARY KEY,
    doadora_id BIGINT NOT NULL REFERENCES usuarios(id),
    categoria_id BIGINT NOT NULL REFERENCES categorias(id),
    titulo VARCHAR(140) NOT NULL,
    descricao TEXT NOT NULL,
    condicao VARCHAR(30) NOT NULL CHECK (condicao IN ('novo', 'seminovo', 'usado_bom', 'usado_regular')),
    pontos INTEGER NOT NULL CHECK (pontos > 0),
    bairro VARCHAR(100) NOT NULL,
    cidade VARCHAR(100) NOT NULL DEFAULT 'Belo Horizonte',
    status VARCHAR(30) NOT NULL DEFAULT 'disponivel'
        CHECK (status IN ('disponivel', 'reservado', 'entregue', 'pausado', 'cancelado')),
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE item_fotos (
    id BIGSERIAL PRIMARY KEY,
    item_id BIGINT NOT NULL REFERENCES itens(id) ON DELETE CASCADE,
    url TEXT NOT NULL,
    ordem INTEGER NOT NULL DEFAULT 1 CHECK (ordem BETWEEN 1 AND 5),
    criada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (item_id, ordem)
);

CREATE TABLE reservas (
    id BIGSERIAL PRIMARY KEY,
    item_id BIGINT NOT NULL REFERENCES itens(id),
    receptora_id BIGINT NOT NULL REFERENCES usuarios(id),
    status VARCHAR(30) NOT NULL DEFAULT 'pendente'
        CHECK (status IN ('pendente', 'aceita', 'cancelada', 'expirada', 'entregue', 'no_show')),
    expira_em TIMESTAMP NOT NULL,
    local_retirada VARCHAR(180),
    data_retirada TIMESTAMP,
    observacao TEXT,
    criada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE confirmacoes_entrega (
    id BIGSERIAL PRIMARY KEY,
    reserva_id BIGINT NOT NULL UNIQUE REFERENCES reservas(id) ON DELETE CASCADE,
    codigo VARCHAR(12) NOT NULL,
    confirmado_por_id BIGINT REFERENCES usuarios(id),
    gerado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confirmado_em TIMESTAMP,
    UNIQUE (reserva_id, codigo)
);

CREATE TABLE transacoes_pontos (
    id BIGSERIAL PRIMARY KEY,
    usuario_id BIGINT NOT NULL REFERENCES usuarios(id),
    reserva_id BIGINT REFERENCES reservas(id),
    tipo VARCHAR(20) NOT NULL CHECK (tipo IN ('credito', 'debito', 'ajuste')),
    quantidade INTEGER NOT NULL CHECK (quantidade > 0),
    motivo VARCHAR(180) NOT NULL,
    criada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE avaliacoes (
    id BIGSERIAL PRIMARY KEY,
    reserva_id BIGINT NOT NULL REFERENCES reservas(id) ON DELETE CASCADE,
    avaliador_id BIGINT NOT NULL REFERENCES usuarios(id),
    avaliado_id BIGINT NOT NULL REFERENCES usuarios(id),
    nota INTEGER NOT NULL CHECK (nota BETWEEN 1 AND 5),
    comentario VARCHAR(500),
    criada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (reserva_id, avaliador_id),
    CHECK (avaliador_id <> avaliado_id)
);

CREATE TABLE mensagens_chat (
    id BIGSERIAL PRIMARY KEY,
    reserva_id BIGINT NOT NULL REFERENCES reservas(id) ON DELETE CASCADE,
    remetente_id BIGINT NOT NULL REFERENCES usuarios(id),
    mensagem TEXT NOT NULL,
    lida BOOLEAN NOT NULL DEFAULT FALSE,
    criada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE denuncias (
    id BIGSERIAL PRIMARY KEY,
    denunciante_id BIGINT NOT NULL REFERENCES usuarios(id),
    denunciado_id BIGINT REFERENCES usuarios(id),
    item_id BIGINT REFERENCES itens(id),
    reserva_id BIGINT REFERENCES reservas(id),
    motivo VARCHAR(120) NOT NULL,
    detalhes TEXT,
    evidencia_url TEXT,
    status VARCHAR(30) NOT NULL DEFAULT 'aberta'
        CHECK (status IN ('aberta', 'em_analise', 'resolvida', 'descartada')),
    criada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE notificacoes (
    id BIGSERIAL PRIMARY KEY,
    usuario_id BIGINT NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    titulo VARCHAR(120) NOT NULL,
    mensagem TEXT NOT NULL,
    tipo VARCHAR(40) NOT NULL,
    item_id BIGINT REFERENCES itens(id),
    reserva_id BIGINT REFERENCES reservas(id),
    lida BOOLEAN NOT NULL DEFAULT FALSE,
    criada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_usuarios_email ON usuarios(email);
CREATE INDEX idx_itens_status ON itens(status);
CREATE INDEX idx_itens_categoria ON itens(categoria_id);
CREATE INDEX idx_itens_bairro ON itens(bairro);
CREATE INDEX idx_itens_busca_titulo ON itens USING GIN (to_tsvector('portuguese', titulo || ' ' || descricao));
CREATE INDEX idx_reservas_item ON reservas(item_id);
CREATE INDEX idx_reservas_receptora ON reservas(receptora_id);
CREATE INDEX idx_reservas_status ON reservas(status);
CREATE UNIQUE INDEX idx_reservas_item_ativa ON reservas(item_id)
    WHERE status IN ('pendente', 'aceita');
CREATE INDEX idx_transacoes_usuario ON transacoes_pontos(usuario_id);
CREATE INDEX idx_notificacoes_usuario_lida ON notificacoes(usuario_id, lida);

CREATE OR REPLACE FUNCTION impedir_reserva_do_proprio_item()
RETURNS TRIGGER AS $$
DECLARE
    id_doadora BIGINT;
BEGIN
    SELECT doadora_id INTO id_doadora
    FROM itens
    WHERE id = NEW.item_id;

    IF id_doadora = NEW.receptora_id THEN
        RAISE EXCEPTION 'A doadora nao pode reservar o proprio item.';
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_impedir_reserva_do_proprio_item
BEFORE INSERT OR UPDATE OF item_id, receptora_id ON reservas
FOR EACH ROW
EXECUTE FUNCTION impedir_reserva_do_proprio_item();

INSERT INTO categorias (nome, descricao) VALUES
    ('Roupas', 'Peças de vestuário em bom estado'),
    ('Acessórios', 'Bolsas, bijuterias, cintos e itens similares'),
    ('Livros', 'Livros didáticos, literatura e materiais de estudo'),
    ('Casa', 'Objetos pequenos para casa'),
    ('Infantil', 'Itens infantis reutilizáveis');

CREATE VIEW vw_itens_disponiveis AS
SELECT
    i.id,
    i.titulo,
    i.descricao,
    c.nome AS categoria,
    i.condicao,
    i.pontos,
    i.bairro,
    i.cidade,
    u.nome AS doadora,
    i.criado_em
FROM itens i
JOIN categorias c ON c.id = i.categoria_id
JOIN usuarios u ON u.id = i.doadora_id
WHERE i.status = 'disponivel'
  AND u.ativo = TRUE;

CREATE VIEW vw_extrato_pontos AS
SELECT
    tp.id,
    u.id AS usuario_id,
    u.nome AS usuario,
    tp.tipo,
    tp.quantidade,
    tp.motivo,
    tp.reserva_id,
    tp.criada_em
FROM transacoes_pontos tp
JOIN usuarios u ON u.id = tp.usuario_id;

CREATE VIEW vw_relatorio_impacto_categoria AS
SELECT
    c.nome AS categoria,
    COUNT(i.id) AS itens_entregues,
    COALESCE(SUM(i.pontos), 0) AS pontos_movimentados
FROM categorias c
LEFT JOIN itens i ON i.categoria_id = c.id AND i.status = 'entregue'
GROUP BY c.nome
ORDER BY itens_entregues DESC, c.nome;

CREATE VIEW vw_relatorio_entregas_mensais AS
SELECT
    DATE_TRUNC('month', r.atualizada_em)::DATE AS mes,
    COUNT(*) AS entregas_confirmadas
FROM reservas r
WHERE r.status = 'entregue'
GROUP BY DATE_TRUNC('month', r.atualizada_em)
ORDER BY mes;
