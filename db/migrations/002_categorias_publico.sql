-- Atualiza as categorias principais para o conjunto curado do ReUse.
-- Na Hostinger, execute com o banco ja selecionado.

UPDATE categorias
SET nome = 'Roupas',
    descricao = 'Pecas de vestuario em bom estado',
    ativa = 1
WHERE id = 1;

UPDATE categorias
SET nome = 'Acessórios',
    descricao = 'Bolsas, bijuterias, cintos e itens similares',
    ativa = 1
WHERE id = 2;

UPDATE categorias
SET nome = 'Cosméticos e itens de beleza (novos/lacrados)',
    descricao = 'Produtos novos, lacrados e apropriados para reutilizacao segura',
    ativa = 1
WHERE id = 3;

UPDATE categorias
SET nome = 'Livros e materiais de estudo',
    descricao = 'Livros didaticos, literatura e materiais de apoio ao estudo',
    ativa = 1
WHERE id = 4;

UPDATE categorias
SET nome = 'Decoração',
    descricao = 'Objetos decorativos e pequenos itens para casa',
    ativa = 1
WHERE id = 5;

INSERT INTO categorias (id, nome, descricao, ativa)
VALUES
    (1, 'Roupas', 'Pecas de vestuario em bom estado', 1),
    (2, 'Acessórios', 'Bolsas, bijuterias, cintos e itens similares', 1),
    (3, 'Cosméticos e itens de beleza (novos/lacrados)', 'Produtos novos, lacrados e apropriados para reutilizacao segura', 1),
    (4, 'Livros e materiais de estudo', 'Livros didaticos, literatura e materiais de apoio ao estudo', 1),
    (5, 'Decoração', 'Objetos decorativos e pequenos itens para casa', 1)
ON DUPLICATE KEY UPDATE
    nome = VALUES(nome),
    descricao = VALUES(descricao),
    ativa = VALUES(ativa);

UPDATE categorias
SET ativa = 0
WHERE id NOT IN (1, 2, 3, 4, 5);
