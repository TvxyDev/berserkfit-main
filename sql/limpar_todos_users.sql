-- ⚠️ PERIGO: ESTE SCRIPT APAGA TODOS OS UTILIZADORES E DADOS ASSOCIADOS ⚠️
-- Executa apenas se tiveres a certeza que queres limpar a base de dados completa.

USE `berserkfit`;

-- 1. Desativar verificação de chaves estrangeiras
SET FOREIGN_KEY_CHECKS = 0;

-- 2. Limpar todas as tabelas dependentes (Ordem não importa com FK=0, mas fica organizado)
TRUNCATE TABLE `agua`;
TRUNCATE TABLE `alimentacao`;
TRUNCATE TABLE `checklist_diario`;
TRUNCATE TABLE `exercicio`;
TRUNCATE TABLE `habito`;
TRUNCATE TABLE `mensagem_motivacional`;
TRUNCATE TABLE `meta_usuario`;
TRUNCATE TABLE `notificacao`;
TRUNCATE TABLE `pagamento`;
TRUNCATE TABLE `peso`;
TRUNCATE TABLE `treino`;

-- 3. Limpar a tabela principal de utilizadores
TRUNCATE TABLE `user`;

-- 4. Reativar verificação de chaves estrangeiras
SET FOREIGN_KEY_CHECKS = 1;

-- (OPCIONAL) Recriar conta de Administrador Padrão
-- Remove os comentários abaixo (--) se quiseres criar o admin automaticamente
-- INSERT INTO `user` (`nome`, `email`, `password_hash`, `tipo_usuario`, `username`) VALUES
-- ('Admin', 'admin@berserkfit.com', '$2y$10$ExemploHashDeSenha123456', 'Admin', 'admin_berserk');

SELECT 'Base de dados limpa com sucesso!' as Mensagem;
