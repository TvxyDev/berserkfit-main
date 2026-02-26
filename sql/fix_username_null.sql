-- Atualizar estrutura da coluna username para permitir NULL
-- Isso resolve o problema de duplicate entry com valores vazios

USE `berserkfit`;

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
