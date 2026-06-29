CREATE DATABASE IF NOT EXISTS reuse
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE reuse;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    email_verificado_em DATETIME NULL,
    senha_hash VARCHAR(255) NOT NULL,
    telefone VARCHAR(30),
    telefone_normalizado VARCHAR(20) UNIQUE,
    bairro VARCHAR(100) NOT NULL,
    cidade VARCHAR(100) NOT NULL DEFAULT 'Belo Horizonte',
    saldo_pontos INT NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(80) NOT NULL UNIQUE,
    descricao TEXT,
    ativa TINYINT(1) NOT NULL DEFAULT 1,
    criada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doadora_id INT NOT NULL,
    categoria_id INT NOT NULL,
    titulo VARCHAR(140) NOT NULL,
    descricao TEXT NOT NULL,
    condicao ENUM('novo', 'seminovo', 'usado_bom', 'usado_regular') NOT NULL,
    pontos INT NOT NULL,
    bairro VARCHAR(100) NOT NULL,
    cidade VARCHAR(100) NOT NULL DEFAULT 'Belo Horizonte',
    status ENUM('disponivel', 'reservado', 'entregue', 'pausado', 'cancelado') NOT NULL DEFAULT 'disponivel',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_itens_doadora FOREIGN KEY (doadora_id) REFERENCES usuarios(id),
    CONSTRAINT fk_itens_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS item_fotos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    caminho VARCHAR(255) NOT NULL,
    hash_perceptual CHAR(16) NULL,
    ordem INT NOT NULL DEFAULT 1,
    criada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_item_fotos_item FOREIGN KEY (item_id) REFERENCES itens(id) ON DELETE CASCADE,
    UNIQUE KEY uk_item_ordem (item_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    receptora_id INT NOT NULL,
    status ENUM('pendente', 'aceita', 'cancelada', 'expirada', 'entregue', 'no_show') NOT NULL DEFAULT 'pendente',
    expira_em DATETIME NOT NULL,
    local_retirada VARCHAR(180),
    data_retirada DATETIME,
    observacao TEXT,
    criada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_reservas_item FOREIGN KEY (item_id) REFERENCES itens(id),
    CONSTRAINT fk_reservas_receptora FOREIGN KEY (receptora_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS transacoes_pontos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    reserva_id INT,
    compra_pontos_id INT NULL,
    tipo ENUM('credito', 'debito', 'ajuste') NOT NULL,
    quantidade INT NOT NULL,
    motivo VARCHAR(180) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transacoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    CONSTRAINT fk_transacoes_reserva FOREIGN KEY (reserva_id) REFERENCES reservas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS compras_pontos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    quantidade_pontos INT NOT NULL,
    valor_base DECIMAL(10,2) NOT NULL,
    taxa DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    valor_total DECIMAL(10,2) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'criada',
    referencia_externa VARCHAR(80) NOT NULL UNIQUE,
    mercado_pago_preference_id VARCHAR(120) NULL,
    mercado_pago_payment_id VARCHAR(120) NULL,
    mercado_pago_status VARCHAR(60) NULL,
    mercado_pago_status_detail VARCHAR(180) NULL,
    mercado_pago_init_point TEXT NULL,
    mercado_pago_sandbox_init_point TEXT NULL,
    aprovado_em DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_compras_pontos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    INDEX idx_compras_pontos_usuario (usuario_id),
    INDEX idx_compras_pontos_status (status),
    INDEX idx_compras_pontos_preference (mercado_pago_preference_id),
    INDEX idx_compras_pontos_payment (mercado_pago_payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE transacoes_pontos
    ADD CONSTRAINT fk_transacoes_compra_pontos
    FOREIGN KEY (compra_pontos_id) REFERENCES compras_pontos(id);

INSERT IGNORE INTO categorias (id, nome, descricao) VALUES
    (1, 'Roupas', 'Pecas de vestuario em bom estado'),
    (2, 'Acessórios', 'Bolsas, bijuterias, cintos e itens similares'),
    (3, 'Cosméticos e itens de beleza (novos/lacrados)', 'Produtos novos, lacrados e apropriados para reutilização segura'),
    (4, 'Livros e materiais de estudo', 'Livros didáticos, literatura e materiais de apoio ao estudo'),
    (5, 'Decoração', 'Objetos decorativos e pequenos itens para casa');

-- Recursos adicionais usados pelo codigo atual
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS no_show_count INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS bloqueada_ate DATETIME NULL;

ALTER TABLE reservas
    ADD COLUMN IF NOT EXISTS codigo_confirmacao CHAR(6) NULL AFTER observacao;

CREATE TABLE IF NOT EXISTS tokens_senha (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT NOT NULL,
    token       CHAR(64) NOT NULL UNIQUE,
    expira_em   DATETIME NOT NULL,
    usado       TINYINT(1) NOT NULL DEFAULT 0,
    criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_token_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS email_confirmacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expira_em DATETIME NOT NULL,
    usado TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_email_confirmacoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_email_confirmacoes_usuario (usuario_id),
    INDEX idx_email_confirmacoes_expira (expira_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS avaliacoes (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    reserva_id    INT NOT NULL,
    avaliadora_id INT NOT NULL,
    avaliada_id   INT NOT NULL,
    nota          TINYINT NOT NULL CHECK (nota BETWEEN 1 AND 5),
    comentario    TEXT,
    criada_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_reserva_avaliadora (reserva_id, avaliadora_id),
    CONSTRAINT fk_aval_reserva FOREIGN KEY (reserva_id) REFERENCES reservas(id),
    CONSTRAINT fk_aval_avaliadora FOREIGN KEY (avaliadora_id) REFERENCES usuarios(id),
    CONSTRAINT fk_aval_avaliada FOREIGN KEY (avaliada_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notificacoes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo       ENUM('reserva','aceite','cancelamento','confirmacao','avaliacao','pontos','noshow') NOT NULL,
    mensagem   TEXT NOT NULL,
    lida       TINYINT(1) NOT NULL DEFAULT 0,
    reserva_id INT NULL,
    criada_em  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_notif_reserva FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS denuncias (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    denunciante_id INT NOT NULL,
    item_id        INT NULL,
    reserva_id     INT NULL,
    denunciada_id  INT NULL,
    motivo         ENUM('item_falso','comportamento','no_show','outro') NOT NULL,
    descricao      TEXT NOT NULL,
    evidencia_caminho VARCHAR(255) NULL,
    criada_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_den_denunciante FOREIGN KEY (denunciante_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS chat_mensagens (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    reserva_id    INT NOT NULL,
    remetente_id  INT NOT NULL,
    mensagem      TEXT NOT NULL,
    criada_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_chat_reserva FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE CASCADE,
    CONSTRAINT fk_chat_remetente FOREIGN KEY (remetente_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
