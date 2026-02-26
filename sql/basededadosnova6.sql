CREATE DATABASE  IF NOT EXISTS `berserkfit` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;
USE `berserkfit`;
-- MySQL dump 10.13  Distrib 8.0.38, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: berserkfit
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `agua`
--

DROP TABLE IF EXISTS `agua`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agua` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `quantidade` decimal(5,2) NOT NULL,
  `data` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_user` (`id_user`),
  KEY `data` (`data`),
  CONSTRAINT `fk_agua_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agua`
--

LOCK TABLES `agua` WRITE;
/*!40000 ALTER TABLE `agua` DISABLE KEYS */;
INSERT INTO `agua` VALUES (1,4,9.50,'2025-11-16'),(2,7,1.00,'2025-11-21'),(3,9,0.50,'2025-12-05'),(4,8,0.50,'2026-01-08');
/*!40000 ALTER TABLE `agua` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `alimentacao`
--

DROP TABLE IF EXISTS `alimentacao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alimentacao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `calorias` decimal(8,2) NOT NULL,
  `refeicao` varchar(50) NOT NULL,
  `descricao` text DEFAULT NULL,
  `data` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_user` (`id_user`),
  KEY `data` (`data`),
  CONSTRAINT `fk_alimentacao_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alimentacao`
--

LOCK TABLES `alimentacao` WRITE;
/*!40000 ALTER TABLE `alimentacao` DISABLE KEYS */;
INSERT INTO `alimentacao` VALUES (1,5,155.00,'Café da Manhã','pao e agua','2025-11-16'),(2,5,155.00,'Café da Manhã','pao e agua','2025-11-16'),(3,5,155.00,'Café da Manhã','pao e agua','2025-11-16'),(4,7,500.00,'Café da Manhã','arroz','2025-11-21'),(5,7,150.00,'Lanche da Manhã','frango','2025-11-21'),(6,7,150.00,'Lanche da Manhã','frango','2025-11-21');
/*!40000 ALTER TABLE `alimentacao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checklist_diario`
--

DROP TABLE IF EXISTS `checklist_diario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `checklist_diario` (
  `id_checklist` int(11) NOT NULL AUTO_INCREMENT,
  `id_habito` int(11) NOT NULL,
  `data` date DEFAULT current_timestamp(),
  `concluido` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_checklist`),
  KEY `id_habito` (`id_habito`),
  CONSTRAINT `checklist_diario_ibfk_1` FOREIGN KEY (`id_habito`) REFERENCES `habito` (`id_habito`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checklist_diario`
--

LOCK TABLES `checklist_diario` WRITE;
/*!40000 ALTER TABLE `checklist_diario` DISABLE KEYS */;
/*!40000 ALTER TABLE `checklist_diario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exercicio`
--

DROP TABLE IF EXISTS `exercicio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exercicio` (
  `id_exercicio` int(11) NOT NULL AUTO_INCREMENT,
  `id_treino` int(11) NOT NULL,
  `nome_exercicio` varchar(100) NOT NULL,
  `series` int(11) DEFAULT NULL,
  `repeticoes` int(11) DEFAULT NULL,
  `grupo_muscular` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_exercicio`),
  KEY `exercicio_id_treino_foreign` (`id_treino`),
  CONSTRAINT `exercicio_id_treino_foreign` FOREIGN KEY (`id_treino`) REFERENCES `treino` (`id_treino`)
) ENGINE=InnoDB AUTO_INCREMENT=124 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exercicio`
--

LOCK TABLES `exercicio` WRITE;
/*!40000 ALTER TABLE `exercicio` DISABLE KEYS */;
INSERT INTO `exercicio` VALUES (1,1,'Supino Máquina (ou Halteres Inclinado)',4,10,'Peito'),(2,1,'Peck Deck ou Crossover Baixo',3,15,'Peito'),(3,1,'Elevação Lateral (Halteres)',4,12,'Ombro'),(4,1,'Desenvolvimento Máquina (ou Halteres)',3,10,'Ombro'),(5,1,'Extensão de Tríceps na Corda (Pulley)',3,15,'Tríceps'),(6,1,'Tríceps Francês com Halter (Sentado)',3,10,'Tríceps'),(7,2,'Puxada Vertical (Lat Pulldown)',4,12,'Costa'),(8,2,'Remada Curvada (Halteres ou Máquina)',4,10,'Costa'),(9,2,'Remada Cavalinho (T-Bar Row)',3,12,'Costa'),(10,2,'Hiperextensão Lombar (ou Bom Dia)',3,15,'Lombar'),(11,2,'Rosca Direta (Barra W ou Halteres)',3,12,'Bíceps'),(12,2,'Rosca Martelo (Halteres)',3,15,'Bíceps'),(13,3,'Agachamento Livre (ou Hack Machine)',4,10,'Pernas'),(14,3,'Leg Press 45º',4,12,'Pernas'),(15,3,'Extensora de Joelhos (Cadeira)',3,20,'Pernas'),(16,3,'Mesa Flexora (Deitado)',4,12,'Pernas'),(17,3,'Stiff (com Halteres ou Barra)',3,12,'Pernas'),(18,3,'Elevação de Panturrilha (Sentado ou em Pé)',4,20,'Panturrilhas'),(19,4,'Supino Inclinado Máquina',4,10,'Peito'),(20,4,'Fly Máquina (Pec Deck)',3,15,'Peito'),(21,4,'Elevação Frontal (Cabo ou Halteres)',3,12,'Ombro'),(22,4,'Remada Alta com Halteres (Seguro)',3,12,'Ombro'),(23,4,'Rotadores Externos (Cabo - Terapia)',3,20,'Ombro'),(24,4,'Abdominais (Escolha Livre)',3,0,'Core'),(25,5,'Rosca Scott (Máquina ou Banco)',3,12,'Bíceps'),(26,5,'Rosca Concentrada',3,12,'Bíceps'),(27,5,'Tríceps Testa (Barra W)',3,12,'Tríceps'),(28,5,'Tríceps Coice (Cabo ou Halteres)',3,15,'Tríceps'),(29,5,'Rosca Inversa (Antebraço/Bíceps)',3,15,'Antebraço'),(30,5,'Flexão Fechada (no chão ou máquina Smith)',3,0,'Tríceps'),(31,6,'Supino com Halteres (Inclinado)',4,10,'Peito'),(32,6,'Peck Deck (Máquina de Peito)',3,15,'Peito'),(33,6,'Press de Ombro com Halteres (Sentado - Amplitude Limitada)',3,10,'Ombro'),(34,6,'Elevação Lateral (Halteres Ligeiros)',3,15,'Ombro'),(35,6,'Extensão de Tríceps com Corda (Pushdown)',3,12,'Tríceps'),(36,6,'Tríceps Francês (Sentado com Haltere)',3,10,'Tríceps'),(37,7,'Puxada Vertical (Lat Pulldown)',4,10,'Costa'),(38,7,'Remada Sentada (Cabo)',3,10,'Costa'),(39,7,'Hiperextensão Lombar (Máquina/Banco)',3,15,'Lombar'),(40,7,'Curl de Bíceps com Barra',3,10,'Bíceps'),(41,7,'Curl Martelo com Halteres',3,12,'Bíceps'),(42,8,'Agachamento na Máquina Smith (ou Leg Press)',4,10,'Quadríceps'),(43,8,'Extensão de Pernas (Leg Extension)',3,12,'Quadríceps'),(44,8,'Flexão de Pernas (Leg Curl)',3,10,'Isquiotibiais'),(45,8,'Lunge com Halteres (Passada)',3,10,'Quadríceps'),(46,8,'Elevação de Gémeos (Sentado ou em Pé)',3,15,'Gémeos'),(47,9,'Super-Série: Tríceps Pushdown (V-Bar)',3,12,'Tríceps'),(48,9,'Super-Série: Curl de Bíceps (Halteres)',3,12,'Bíceps'),(49,9,'Tríceps Kickback com Halteres',3,15,'Tríceps'),(50,9,'Extensão de Tríceps com Cabo Acima da Cabeça',3,10,'Tríceps'),(51,9,'Curl Concentrado com Halteres (Sentado)',3,10,'Bíceps'),(52,9,'Curl Invertido com Barra (Pegada Pronada)',3,12,'Antebraço'),(53,10,'Crossover de Cabos (Baixo para Cima)',3,15,'Peito'),(54,10,'Press de Ombro na Máquina',3,12,'Ombro'),(55,10,'Leg Press',3,15,'Quadríceps'),(56,10,'Flexão de Pernas (Deitado)',3,12,'Isquiotibiais'),(57,11,'Supino Inclinado com Halteres',4,8,'Peito'),(58,11,'Peck Deck (Fly Máquina)',4,12,'Peito'),(59,11,'Supino Declinado (Máquina/Halteres)',3,10,'Peito'),(60,11,'Crucifixo Inclinado com Halteres',3,12,'Peito'),(61,11,'Tríceps Corda (Pushdown)',4,10,'Tríceps'),(62,11,'Tríceps Testa (Skullcrushers) com Halteres',3,8,'Tríceps'),(63,11,'Extensão de Tríceps por cima da Cabeça (Cabo)',3,12,'Tríceps'),(64,12,'Puxada Aberta (Lat Pulldown)',4,8,'Costa'),(65,12,'Remada Sentada (Cabo, pega fechada)',4,10,'Costa'),(66,12,'Remada com Haltere (Unilateral)',3,10,'Costa'),(67,12,'Hiperextensões (Lombares)',3,15,'Lombar'),(68,12,'Curl de Bíceps com Barra (ou Halteres)',4,8,'Bíceps'),(69,12,'Curl Martelo com Halteres',3,10,'Bíceps'),(70,12,'Curl Concentrado (Sentado)',3,12,'Bíceps'),(71,13,'Extensão de Pernas (Leg Extension)',4,15,'Quads'),(72,13,'Agachamento Livre (Barra)',4,6,'Pernas'),(73,13,'Leg Press Inclinado',4,10,'Pernas'),(74,13,'Avanços (Lunges) com Halteres',3,10,'Pernas'),(75,13,'Flexão de Pernas (Deitado/Sentado)',4,10,'Isquiotibiais'),(76,13,'Elevação de Gémeos (Sentado)',4,15,'Gémeos'),(77,13,'Elevação de Gémeos (Em pé)',4,12,'Gémeos'),(78,14,'Elevação Lateral com Halteres',5,12,'Ombro'),(79,14,'Elevação Posterior (Pec Dec Invertido)',4,15,'Ombro'),(80,14,'Face Pull (Cabo)',3,15,'Ombro'),(81,14,'Press de Ombro (Máquina, se seguro)',3,10,'Ombro'),(82,14,'Supersérie (A1) Curl no Cabo (EZ Bar)',3,10,'Bíceps'),(83,14,'Supersérie (A2) Extensão de Tríceps (Reverse Grip Pushdown)',3,10,'Tríceps'),(84,14,'Supersérie (B1) Curl Inclinado (Halteres)',3,10,'Bíceps'),(85,14,'Supersérie (B2) Kickbacks (Tríceps)',3,12,'Tríceps'),(86,15,'Peso Morto Romeno (Halteres ou Barra)',4,8,'Isquiotibiais'),(87,15,'Máquina de Abdução (Glúteo lateral)',4,15,'Glúteos'),(88,15,'Ponte de Glúteos (Barra/Máquina)',4,10,'Glúteos'),(89,15,'Hack Squat (Pés mais altos)',3,10,'Pernas'),(90,15,'Flexão de Pernas Sentado',3,10,'Isquiotibiais'),(91,15,'Gémeos na Leg Press',4,15,'Gémeos'),(92,16,'Supino Inclinado com Halteres',4,8,'Peito'),(93,16,'Supino Reto com Barra',3,10,'Peito'),(94,16,'Crucifixo na Máquina ou Cabo',3,12,'Peito'),(95,16,'Desenvolvimento Militar (Halteres/Máquina)',3,10,'Ombro'),(96,16,'Elevação Lateral com Halteres',3,12,'Ombro'),(97,16,'Extensão de Tríceps com Cabo (Overhead)',3,12,'Tríceps'),(98,16,'Tríceps Testa (Barra EZ)',3,10,'Tríceps'),(99,17,'Puxada Aberta (Lat Pulldown)',4,8,'Costa'),(100,17,'Remada Curvada com Barra (Pendlay Row)',3,8,'Costa'),(101,17,'Remada Sentada (Cabo, pega neutra)',3,10,'Costa'),(102,17,'Hiperextensões (Lombares)',3,15,'Lombar'),(103,17,'Curl de Bíceps com Barra (Straight Bar)',3,8,'Bíceps'),(104,17,'Curl Martelo (Halteres)',3,10,'Bíceps'),(105,17,'Curl Concentrado (Alternado)',3,12,'Bíceps'),(106,18,'Agachamento Livre com Barra',4,6,'Pernas'),(107,18,'Leg Press',3,10,'Pernas'),(108,18,'Extensão de Pernas (Cadeira Extensora)',3,12,'Pernas'),(109,18,'Peso Morto Romeno',3,10,'Isquiotibiais'),(110,18,'Flexão de Pernas (Máquina)',3,12,'Isquiotibiais'),(111,18,'Elevação de Gémeos (Máquina em pé)',4,15,'Gémeos'),(112,19,'Supersérie: Tríceps Corda',3,15,'Tríceps'),(113,19,'Supersérie: Curl Bíceps Cabo',3,15,'Bíceps'),(114,19,'Tríceps Coice (Kickback) com Halteres',3,12,'Tríceps'),(115,19,'Tríceps Mergulhos (Dips) na Máquina ou Banco',3,0,'Tríceps'),(116,19,'Curl Inverso com Barra EZ',3,12,'Antebraço'),(117,19,'Curl de Bíceps no Banco Scott (Preacher Curl)',3,8,'Bíceps'),(118,20,'Pec Deck (Máquina de Abertura)',3,15,'Peito'),(119,20,'Press de Ombro com Halteres (Sentado)',3,10,'Ombro'),(120,20,'Supino Declinado com Halteres',3,10,'Peito'),(121,20,'Elevação Frontal com Halteres (Alternada)',3,12,'Ombro'),(122,20,'Face Pulls (Cabo)',3,15,'Ombro Posterior'),(123,20,'Push-ups (Flexões)',3,0,'Peito');
/*!40000 ALTER TABLE `exercicio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `habito`
--

DROP TABLE IF EXISTS `habito`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `habito` (
  `id_habito` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `descricao` varchar(150) DEFAULT NULL,
  `meta_diaria` varchar(100) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_habito`),
  KEY `habito_id_user_foreign` (`id_user`),
  CONSTRAINT `habito_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `habito`
--

LOCK TABLES `habito` WRITE;
/*!40000 ALTER TABLE `habito` DISABLE KEYS */;
INSERT INTO `habito` VALUES (1,4,'Beber 3L de agua','5','Saude'),(2,5,'Beber 3.5L de água','3.5','Saúde'),(3,5,'Fazer 100 flexões','100','Exercício'),(4,5,'Fazer 200 polichinelos','200','Exercício'),(5,5,'Caminhar 15.000 passos','15000','Exercício'),(6,5,'Correr 10km','10','Exercício'),(7,5,'Treinar 60 minutos','60','Exercício'),(8,5,'Fazer abdominais (3 séries de 20)','60','Exercício'),(9,4,'1011','5','1515215'),(10,7,'Beber 3.5L de água','3.5','Saúde'),(11,7,'Fazer 100 flexões','100','Exercício'),(12,7,'Fazer 200 polichinelos','200','Exercício'),(13,7,'Caminhar 15.000 passos','15000','Exercício'),(14,7,'Correr 10km','10','Exercício'),(15,7,'Treinar 60 minutos','60','Exercício'),(16,7,'Fazer abdominais (3 séries de 20)','60','Exercício'),(17,8,'Beber 3L de água','3','Saúde'),(18,8,'Fazer 50 flexões','50','Exercício'),(19,8,'Fazer 100 polichinelos','100','Exercício'),(20,8,'Caminhar 12.000 passos','12000','Exercício'),(21,8,'Correr 5km','5','Exercício'),(22,8,'Treinar 45 minutos','45','Exercício'),(23,9,'Beber 2.5L de água','2.5','Saúde'),(24,9,'Fazer 25 flexões','25','Exercício'),(25,9,'Fazer 50 polichinelos','50','Exercício'),(26,9,'Caminhar 8.000 passos','8000','Exercício'),(27,9,'Correr 3km','3','Exercício'),(28,10,'Beber 2.5L de água','2.5','Saúde'),(29,10,'Fazer 25 flexões','25','Exercício'),(30,10,'Fazer 50 polichinelos','50','Exercício'),(31,10,'Caminhar 8.000 passos','8000','Exercício'),(32,10,'Correr 3km','3','Exercício'),(33,17,'Beber 2L de água','2','Saúde'),(34,17,'Fazer 10 flexões','10','Exercício'),(35,17,'Fazer 20 polichinelos','20','Exercício'),(36,17,'Caminhar 5.000 passos','5000','Exercício'),(37,1,'Beber 2L de água','2','Saúde'),(38,1,'Fazer 10 flexões','10','Exercício'),(39,1,'Fazer 20 polichinelos','20','Exercício'),(40,1,'Caminhar 5.000 passos','5000','Exercício');
/*!40000 ALTER TABLE `habito` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mensagem_motivacional`
--

DROP TABLE IF EXISTS `mensagem_motivacional`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mensagem_motivacional` (
  `id_mensagem` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `conteudo` text DEFAULT NULL,
  `meio_envio` varchar(50) DEFAULT NULL,
  `data_envio` datetime DEFAULT NULL,
  PRIMARY KEY (`id_mensagem`),
  KEY `mensagem_motivacional_id_user_foreign` (`id_user`),
  CONSTRAINT `mensagem_motivacional_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mensagem_motivacional`
--

LOCK TABLES `mensagem_motivacional` WRITE;
/*!40000 ALTER TABLE `mensagem_motivacional` DISABLE KEYS */;
/*!40000 ALTER TABLE `mensagem_motivacional` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meta_usuario`
--

DROP TABLE IF EXISTS `meta_usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `meta_usuario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_meta` (`id_user`,`tipo`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `fk_meta_usuario_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meta_usuario`
--

LOCK TABLES `meta_usuario` WRITE;
/*!40000 ALTER TABLE `meta_usuario` DISABLE KEYS */;
INSERT INTO `meta_usuario` VALUES (1,4,'agua',10.00),(2,5,'agua',3.50),(3,7,'agua',3.50),(4,8,'agua',3.00),(5,9,'agua',2.50),(6,10,'agua',2.50),(7,17,'agua',2.00),(8,1,'agua',2.00);
/*!40000 ALTER TABLE `meta_usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notificacao`
--

DROP TABLE IF EXISTS `notificacao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notificacao` (
  `id_notificacao` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `titulo` varchar(150) DEFAULT NULL,
  `mensagem` text DEFAULT NULL,
  `data_envio` datetime DEFAULT NULL,
  `enviada` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id_notificacao`),
  KEY `notificacao_id_user_foreign` (`id_user`),
  CONSTRAINT `notificacao_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificacao`
--

LOCK TABLES `notificacao` WRITE;
/*!40000 ALTER TABLE `notificacao` DISABLE KEYS */;
/*!40000 ALTER TABLE `notificacao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagamento`
--

DROP TABLE IF EXISTS `pagamento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagamento` (
  `id_pagamento` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `tipo_plano` varchar(50) DEFAULT NULL,
  `valor_pago` decimal(10,2) DEFAULT NULL,
  `data_pagamento` date DEFAULT NULL,
  `metodo_pagamento` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_pagamento`),
  KEY `pagamento_id_user_foreign` (`id_user`),
  CONSTRAINT `pagamento_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagamento`
--

LOCK TABLES `pagamento` WRITE;
/*!40000 ALTER TABLE `pagamento` DISABLE KEYS */;
/*!40000 ALTER TABLE `pagamento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `peso`
--

DROP TABLE IF EXISTS `peso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `peso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `peso` decimal(5,2) NOT NULL,
  `data` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_user` (`id_user`),
  KEY `data` (`data`),
  CONSTRAINT `fk_peso_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `peso`
--

LOCK TABLES `peso` WRITE;
/*!40000 ALTER TABLE `peso` DISABLE KEYS */;
INSERT INTO `peso` VALUES (1,4,65.00,'2025-11-16'),(2,7,56.00,'2025-11-21'),(3,9,56.00,'2025-12-05');
/*!40000 ALTER TABLE `peso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `treino`
--

DROP TABLE IF EXISTS `treino`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `treino` (
  `id_treino` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `nome_treino` varchar(100) DEFAULT NULL,
  `foco` varchar(100) DEFAULT NULL,
  `data_criacao` date DEFAULT NULL,
  `ficheiro_gerado` varchar(255) DEFAULT '(CURRENT_DATE)',
  PRIMARY KEY (`id_treino`),
  KEY `treino_id_user_foreign` (`id_user`),
  CONSTRAINT `treino_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `treino`
--

LOCK TABLES `treino` WRITE;
/*!40000 ALTER TABLE `treino` DISABLE KEYS */;
INSERT INTO `treino` VALUES (1,8,'Treino A - Peito, Tríceps e Ombro (Foco em Segurança)','Hipertrofia','2025-12-05','(CURRENT_DATE)'),(2,8,'Treino B - Costa e Bíceps','Hipertrofia','2025-12-05','(CURRENT_DATE)'),(3,8,'Treino C - Pernas (Completo)','Hipertrofia','2025-12-05','(CURRENT_DATE)'),(4,8,'Treino D - Peito e Ombro (Alto Volume, Foco na Máquina)','Hipertrofia','2025-12-05','(CURRENT_DATE)'),(5,8,'Treino E - Braços Completo (Bíceps e Tríceps)','Hipertrofia','2025-12-05','(CURRENT_DATE)'),(6,8,'Dia 1: Peito, Tríceps e Ombro (Cuidado Ombro)','Hipertrofia','2026-01-08','(CURRENT_DATE)'),(7,8,'Dia 2: Costa e Bíceps','Hipertrofia','2026-01-08','(CURRENT_DATE)'),(8,8,'Dia 3: Pernas (Completo)','Hipertrofia','2026-01-08','(CURRENT_DATE)'),(9,8,'Dia 5: Braços (Bíceps e Tríceps)','Hipertrofia','2026-01-08','(CURRENT_DATE)'),(10,8,'Dia 6: Peito e Perna (Leve)','Volume','2026-01-08','(CURRENT_DATE)'),(11,17,'Dia 1: Peito e Tríceps','Hipertrofia','2026-01-09','(CURRENT_DATE)'),(12,17,'Dia 2: Costa e Bíceps','Hipertrofia','2026-01-09','(CURRENT_DATE)'),(13,17,'Dia 3: Pernas (Quads Dominantes)','Hipertrofia','2026-01-09','(CURRENT_DATE)'),(14,17,'Dia 5: Ombro e Braços Completo','Hipertrofia','2026-01-09','(CURRENT_DATE)'),(15,17,'Dia 6: Pernas (Cadeia Posterior Dominante)','Hipertrofia','2026-01-09','(CURRENT_DATE)'),(16,1,'Treino A - Peito, Tríceps e Ombro','Hipertrofia','2026-01-15','(CURRENT_DATE)'),(17,1,'Treino B - Costa e Bíceps','Hipertrofia','2026-01-15','(CURRENT_DATE)'),(18,1,'Treino C - Pernas (Completo)','Hipertrofia','2026-01-15','(CURRENT_DATE)'),(19,1,'Treino D - Braços Completo','Hipertrofia','2026-01-15','(CURRENT_DATE)'),(20,1,'Treino E - Peito e Ombro (Volume/Resistência)','Hipertrofia','2026-01-15','(CURRENT_DATE)');
/*!40000 ALTER TABLE `treino` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `data_registo` datetime NOT NULL DEFAULT current_timestamp(),
  `tipo_plano` varchar(50) DEFAULT 'gratuito',
  `data_expiracao_plano` date DEFAULT NULL,
  `ddd` varchar(3) DEFAULT NULL,
  `telefone` varchar(9) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `genero` enum('Masculino','Feminino','Outro') DEFAULT NULL,
  `tipo_usuario` varchar(20) DEFAULT 'Usuario',
  `foto` varchar(255) DEFAULT NULL,
  `usercol` varchar(45) DEFAULT 'assets/fotos/default-user.png',
  `day_streak` int(11) DEFAULT 0,
  `league` enum('Renegado','Viking','Huscarl','Jarl','Berserker','Ragnarok') DEFAULT 'Renegado',
  `username` varchar(50) NOT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `user_email_unique` (`email`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'Victor Santos','victorhugoribeiro2609@gmail.com','$2y$10$zmoJovE4ylINa/nnDWXQOuGkDexgPGIYWv0pptbZpTRpu91C0rhDy','0000-00-00 00:00:00','gratuito',NULL,NULL,NULL,NULL,NULL,'Admin',NULL,'assets/img/defaultimg.png',0,'Renegado','user_1'),(3,'Victor Santos','victorribeirosantos2609@gmail.com','$2y$10$q4HIiNIK/csyy/llb5d4Gu8Q9dvXm5KOt5S3/cmTNna7PBRbN8E6G','0000-00-00 00:00:00','gratuito',NULL,NULL,NULL,NULL,NULL,'Usuario',NULL,'assets/img/defaultimg.png',0,'Renegado','user_3'),(4,'Victor Santos','geral@positivesphere.pt','$2y$10$JXoZJ1A/1UqUZH8fpYKYx.v0wN21v4ajuC5TMkyzQj2LUYBEfIO3u','0000-00-00 00:00:00','gratuito',NULL,'351','910877269','2006-09-26','Masculino','Admin','assets/fotos/1763241976_8c8dfea5d7c7f981e39c5d214a31530c.jpg','assets/img/defaultimg.png',0,'Renegado','user_4'),(5,'Victor Santos','demo@email.com','$2y$10$LRRHuQ0CLhyOV0UkZk.mcuNF7xKqsgM42Syknec2QUzZoHvf46U1W','0000-00-00 00:00:00','gratuito',NULL,'351','910877555','2015-09-26','Masculino','Usuario',NULL,'assets/fotos/default-user.png',0,'Renegado','user_5'),(7,'vitor','dinisquintas44@gmail.com','$2y$10$bZtllXMjgUSYFNlOb2TSE.GdubXja0mmIiqYXuu5bsXrKn.wOluzq','0000-00-00 00:00:00','gratuito',NULL,'351','222222222','2006-05-26','','Usuario','assets/fotos/1763720860_Paises (1).png','assets/fotos/default-user.png',0,'Renegado','user_7'),(8,'Victor Santos','luizabille@yahoo.com.br','$2y$10$bmJx.m94T3SdvefkBlOdjeh./IQEx20IVxU5TIvSKgvIxYL2HbpdW','0000-00-00 00:00:00','gratuito',NULL,'351','910877255','2006-09-26','Feminino','Admin','assets/fotos/1765530854_7654cf2527f96a42d8f96d81630bb78c.jpg','assets/fotos/default-user.png',0,'Renegado','user_8'),(9,'Ana paula','anapaula12@gmail.com','$2y$10$p8QVxEoFakV4Wu4uIrlEy.vl3maARoNv26n3hDlphBFZnCBOLuqiK','0000-00-00 00:00:00','gratuito',NULL,'351','910877233','2006-09-26','Masculino','Usuario',NULL,'assets/fotos/default-user.png',0,'Renegado','user_9'),(10,'Victor Santosss','vitoteste@gmail.com','$2y$10$kW0nmTUAjzq.4NH6f.xsbuIuY8lP6Nbz7Rd/Hdd0tEXfbWAYSwHOC','2026-01-09 08:45:28','gratuito',NULL,'351','910877269','2006-09-26','Feminino','Usuario',NULL,'assets/fotos/default-user.png',0,'Renegado',''),(12,'Victor Santos','vitorteste23@gmail.com','victor260906','2026-01-09 09:00:47','gratuito',NULL,'$2y','351','0000-00-00','','Usuario',NULL,'assets/fotos/default-user.png',0,'Renegado','Masculino'),(13,'Victor Santos','victor260906@gmail.com','victorteste','2026-01-09 09:01:26','gratuito',NULL,'$2y','351','0000-00-00','','Usuario',NULL,'assets/fotos/default-user.png',0,'Renegado','Feminino'),(17,'Victor Santos','victor260999@gmail.com','$2y$10$t2ABhFAUWAKuHcq8umuA0uO54KL68P02G9IdP.vyZ7TpXAobZ87Um','2026-01-09 09:18:28','gratuito',NULL,'351','910877269','2006-09-26','Masculino','Usuario',NULL,'assets/fotos/default-user.png',0,'Renegado','victor2622425');
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-16  8:51:37
