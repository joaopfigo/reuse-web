## Banco de dados

Importe primeiro a estrutura base pelo phpMyAdmin:

```text
src/db/reuse_mysql.sql
```

Depois, se o banco ja existir ou se voce estiver atualizando a Hostinger, execute as migrations em ordem:

```text
src/db/migrations/001_novos_recursos.sql
src/db/migrations/002_categorias_publico.sql
src/db/migrations/003_confianca_performance.sql
src/db/migrations/004_verificacao_duplicidade.sql
src/db/migrations/005_compras_pontos_mercado_pago.sql
```

Na Hostinger, selecione o banco correto no phpMyAdmin antes de executar os scripts. Se algum script antigo tiver `USE reuse;`, remova essa linha, porque a Hostinger nao permite trocar para um banco fora do usuario atual.

Para uma apresentacao com dados prontos, existe um seed opcional:

```text
src/db/seeds/demo_banca.sql
```

Use o seed apenas em ambiente de teste/apresentacao, porque ele cria usuarios e registros demonstrativos.
