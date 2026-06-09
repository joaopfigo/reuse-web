## Banco de dados

O script atual do banco fica em:

```text
src/db/reuse_mysql.sql
```

Ele deve ser importado pelo phpMyAdmin.

Se o banco ja existe e o codigo ganhou recursos novos, aplique tambem:

```text
src/db/migrations/001_novos_recursos.sql
```

Na Hostinger, selecione o banco no phpMyAdmin e remova a linha `USE reuse;` antes de executar a migration.
