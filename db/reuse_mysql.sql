CREATE DATABASE IF NOT EXISTS reuse
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE reuse;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    telefone VARCHAR(30),
    bairro VARCHAR(100) NOT NULL,
    cidade VARCHAR(100) NOT NULL DEFAULT 'Belo Horizonte',
    saldo_pontos INT NOT NULL DEFAULT 20,
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
    tipo ENUM('credito', 'debito', 'ajuste') NOT NULL,
    quantidade INT NOT NULL,
    motivo VARCHAR(180) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transacoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    CONSTRAINT fk_transacoes_reserva FOREIGN KEY (reserva_id) REFERENCES reservas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO categorias (id, nome, descricao) VALUES
    (1, 'Roupas', 'Pecas de vestuario em bom estado'),
    (2, 'Acessorios', 'Bolsas, bijuterias, cintos e itens similares'),
    (3, 'Livros', 'Livros didaticos, literatura e materiais de estudo'),
    (4, 'Casa', 'Objetos pequenos para casa'),
    (5, 'Infantil', 'Itens infantis reutilizaveis');
