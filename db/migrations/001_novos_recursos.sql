-- Execute este script em bancos ja existentes antes de publicar o codigo novo.
-- Na Hostinger, remova ou ignore a linha USE e rode com o banco ja selecionado.

USE reuse;

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
