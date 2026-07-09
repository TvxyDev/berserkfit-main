-- Tabelas para Registo de Histórico e Séries de Treino

-- 1. Tabela de Cabeçalho da Sessão de Treino
CREATE TABLE IF NOT EXISTS `historico_treino_log` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `id_treino` int(11) NOT NULL,
  `data_fim` datetime DEFAULT current_timestamp(),
  `duracao_segundos` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_log`),
  KEY `fk_log_user` (`id_user`),
  KEY `fk_log_treino` (`id_treino`),
  CONSTRAINT `fk_log_treino` FOREIGN KEY (`id_treino`) REFERENCES `treino` (`id_treino`) ON DELETE CASCADE,
  CONSTRAINT `fk_log_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Tabela de Detalhes das Séries Efetuadas
CREATE TABLE IF NOT EXISTS `historico_exercicio_log` (
  `id_ex_log` int(11) NOT NULL AUTO_INCREMENT,
  `id_log` int(11) NOT NULL,
  `id_exercicio` int(11) NOT NULL,
  `num_serie` int(11) NOT NULL,
  `peso_kg` decimal(8,2) DEFAULT NULL,
  `repeticoes` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_ex_log`),
  KEY `fk_serie_log` (`id_log`),
  KEY `fk_serie_exercicio` (`id_exercicio`),
  CONSTRAINT `fk_serie_exercicio` FOREIGN KEY (`id_exercicio`) REFERENCES `exercicio` (`id_exercicio`) ON DELETE CASCADE,
  CONSTRAINT `fk_serie_log` FOREIGN KEY (`id_log`) REFERENCES `historico_treino_log` (`id_log`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
