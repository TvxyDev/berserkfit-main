# 🔧 Correção do Erro "Duplicate entry '' for key 'username'"

## Problema
O erro ocorre porque o índice UNIQUE na coluna `username` não permite múltiplos valores vazios ('').

## Solução
Usar `NULL` em vez de string vazia, pois MySQL permite múltiplos valores NULL em colunas UNIQUE.

## Passos para Corrigir

### Opção 1: Via phpMyAdmin (RECOMENDADO)

1. Abrir phpMyAdmin: http://localhost/phpmyadmin
2. Selecionar base de dados `berserkfit`
3. Clicar na aba "SQL"
4. Copiar e colar o seguinte código:

```sql
-- Remover índice único existente
ALTER TABLE `user` DROP INDEX IF EXISTS `user_username_unique`;

-- Modificar coluna para permitir NULL
ALTER TABLE `user` 
MODIFY COLUMN `username` VARCHAR(50) DEFAULT NULL;

-- Adicionar índice único novamente (NULL não conta como duplicado)
ALTER TABLE `user` 
ADD UNIQUE INDEX `user_username_unique` (`username`);

-- Atualizar usernames vazios existentes para NULL
UPDATE `user` SET `username` = NULL WHERE `username` = '';
```

5. Clicar em "Executar"

### Opção 2: Via MySQL CLI

```bash
cd C:\xampp\htdocs\TESTEGOOGLE\berserkfit-main\berserkfit-main
C:\xampp\mysql\bin\mysql.exe -u root -p berserkfit < sql/fix_username_null.sql
```

### Opção 3: Executar Query Diretamente

Se preferires, podes executar cada comando separadamente no phpMyAdmin:

```sql
-- 1. Remover índice
ALTER TABLE `user` DROP INDEX `user_username_unique`;

-- 2. Modificar coluna
ALTER TABLE `user` MODIFY COLUMN `username` VARCHAR(50) DEFAULT NULL;

-- 3. Adicionar índice novamente
ALTER TABLE `user` ADD UNIQUE INDEX `user_username_unique` (`username`);

-- 4. Limpar dados antigos
UPDATE `user` SET `username` = NULL WHERE `username` = '';
```

## Verificação

Após executar, verifica se funcionou:

```sql
-- Ver estrutura da coluna
DESCRIBE user;

-- Ver dados
SELECT id_user, nome, email, username FROM user;
```

## Resultado Esperado

- ✅ Coluna `username` permite NULL
- ✅ Índice UNIQUE funciona (permite múltiplos NULL)
- ✅ Usernames vazios convertidos para NULL
- ✅ Novo login Google funciona sem erro

## Ficheiros Alterados

1. ✅ `google_callback.php` - Usa NULL em vez de ''
2. ✅ `escolher_username.php` - Verifica NULL corretamente
3. ✅ `sql/fix_username_null.sql` - Script de correção

## Testar

1. Fazer logout
2. Login com Google (conta nova)
3. Deve redirecionar para escolher_username.php SEM ERRO
4. Escolher username
5. Completar onboarding
6. ✅ Sucesso!
