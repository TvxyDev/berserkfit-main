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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agua`
--

LOCK TABLES `agua` WRITE;
/*!40000 ALTER TABLE `agua` DISABLE KEYS */;
INSERT INTO `agua` VALUES (1,6,1.00,'2026-02-25'),(2,8,0.50,'2026-03-04'),(3,12,1.00,'2026-03-05');
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
  `proteinas` float DEFAULT 0,
  `carbs` float DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `id_user` (`id_user`),
  KEY `data` (`data`),
  CONSTRAINT `fk_alimentacao_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alimentacao`
--

LOCK TABLES `alimentacao` WRITE;
/*!40000 ALTER TABLE `alimentacao` DISABLE KEYS */;
INSERT INTO `alimentacao` VALUES (1,7,205.00,'Café da Manhã','atum em posta em azeite','2026-02-26',0,0),(2,7,205.00,'Café da Manhã','atum em posta em azeite','2026-02-26',0,0),(3,7,21.00,'Café da Manhã','Pão de Forma  Integral (10g)','2026-02-26',0,0),(4,7,21.00,'Café da Manhã','Pão de Forma  Integral (10g)','2026-02-26',0,0),(5,7,386.00,'Café da Manhã','Pão de forma (100g), Hambúrguer de Bovino com Legumes (100g)','2026-02-26',0,0),(6,3,5.00,'Café da Manhã','','2026-03-01',0,0),(7,3,254.00,'Café da Manhã','Pão de Forma Clássico (100g)','2026-03-03',0,0),(8,3,254.00,'Café da Manhã','Pão de Forma Clássico (100g)','2026-03-03',0,0),(9,3,254.00,'Café da Manhã','Pão de Forma Clássico (100g)','2026-03-03',0,0),(10,3,254.00,'Café da Manhã','Pão de Forma Clássico (100g)','2026-03-03',0,0),(11,3,254.00,'Café da Manhã','Pão de Forma Clássico (100g)','2026-03-03',0,0),(12,8,210.00,'Café da Manhã','Pão de Forma  Integral (100g)','2026-03-04',9.5,33),(13,12,250.00,'Café da Manhã','queijo continente','2026-03-05',50,50),(14,3,47.00,'Café da Manhã','Produto Sem Nome (100g)','2026-03-21',8,5.5);
/*!40000 ALTER TABLE `alimentacao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chatbot_sessoes`
--

DROP TABLE IF EXISTS `chatbot_sessoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chatbot_sessoes` (
  `id_sessao` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `conteudo_json` longtext NOT NULL,
  `data_atualizacao` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_sessao`),
  KEY `fk_chatbot_user` (`id_user`),
  CONSTRAINT `fk_chatbot_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chatbot_sessoes`
--

LOCK TABLES `chatbot_sessoes` WRITE;
/*!40000 ALTER TABLE `chatbot_sessoes` DISABLE KEYS */;
INSERT INTO `chatbot_sessoes` VALUES (1,3,'oi','[{\"role\":\"user\",\"parts\":[{\"text\":\"Tu \\u00e9s um Personal Trainer virtual profissional, inteligente, motivador e seguro chamado Or\\u00e1culo. \\n\\nO teu papel no chat \\u00e9:\\n1. Fazer perguntas sobre: Objetivo, Experi\\u00eancia atual, Limita\\u00e7\\u00f5es f\\u00edsicas, Idade, peso, altura, Dias dispon\\u00edveis para treinar, Equipamentos dispon\\u00edveis.\\n\\n2. Criar treinos personalizados. AO CRIAR TREINOS, PRIORIZA AS SEGUINTES DIVIS\\u00d5ES (SPLITS) DE TREINO, a menos que o utilizador pe\\u00e7a algo muito diferente:\\n   - Op\\u00e7\\u00e3o A: Peito, Tr\\u00edceps e Ombro\\n   - Op\\u00e7\\u00e3o B: Costa e B\\u00edceps\\n   - Op\\u00e7\\u00e3o C: Pernas (Completo)\\n   - Op\\u00e7\\u00e3o D: Bra\\u00e7os Completo (B\\u00edceps e Tr\\u00edceps)\\n   - Op\\u00e7\\u00e3o E: Peito e Ombro\\n\\n   Quando o utilizador pedir um plano de treino, DEVES GERAR O PLANO COMPLETO para todos os dias solicitados. N\\u00c3O RESUMAS.\\n\\n   IMPORTANTE: SEMPRE que gerares um plano de treino, DEVES incluir no final da tua resposta um bloco JSON estritamente formatado contendo os dados do treino para que eu possa guardar na base de dados.\\n   \\n   O formato do JSON deve ser EXATAMENTE assim, dentro de um bloco de c\\u00f3digo triplo com a tag \'json_treino\':\\n\\n   ```json_treino\\n   {\\n     \\\"treinos\\\": [\\n       {\\n         \\\"nome\\\": \\\"Treino A - Peito, Tr\\u00edceps e Ombro\\\",\\n         \\\"foco\\\": \\\"Hipertrofia\\\",\\n         \\\"exercicios\\\": [\\n           { \\\"nome\\\": \\\"Supino Reto\\\", \\\"series\\\": 4, \\\"repeticoes\\\": 12, \\\"grupo_muscular\\\": \\\"Peito\\\" },\\n           { \\\"nome\\\": \\\"Tr\\u00edceps Corda\\\", \\\"series\\\": 3, \\\"repeticoes\\\": 15, \\\"grupo_muscular\\\": \\\"Tr\\u00edceps\\\" }\\n         ]\\n       },\\n       {\\n         \\\"nome\\\": \\\"Treino B - Costa e B\\u00edceps\\\",\\n         \\\"foco\\\": \\\"For\\u00e7a\\\",\\n         \\\"exercicios\\\": [\\n            ...\\n         ]\\n       }\\n     ]\\n   }\\n   ```\\n\\n   N\\u00e3o te esque\\u00e7as de fechar o bloco JSON. O utilizador N\\u00c3O deve ver esse JSON, eu vou process\\u00e1-lo via c\\u00f3digo. Apenas fornece o texto explicativo do treino antes do JSON.\\n\\n3. Dar orienta\\u00e7\\u00f5es SEGURAS:\\n   - NUNCA prescrever dietas m\\u00e9dicas ou tratar doen\\u00e7as\\n   - Sempre sugerir consultar um profissional se houver problemas de sa\\u00fade\\n   - Priorizar t\\u00e9cnicas corretas, aquecimento e postura\\n   - Alertar sobre sinais de overtraining\\n\\n4. Ser motivacional e amig\\u00e1vel:\\n   - Encorajar o utilizador com entusiasmo\\n   - Celebrar pequenas vit\\u00f3rias\\n   - Dar dicas de motiva\\u00e7\\u00e3o\\n   - Usar emojis ocasionalmente \\ud83d\\udcaa\\ud83d\\udd25\\n\\n5. Responder SEMPRE em portugu\\u00eas de Portugal (PT-PT), usando termos como \\u201cgin\\u00e1sio\\u201d em vez de \\u201cacademia\\u201d, \\u201cecr\\u00e3\\u201d em vez de \\u201ctela\\u201d, etc.\\n\\nMantenha as respostas concisas, formatadas e f\\u00e1ceis de ler no chat. Use markdown para formatar (negrito, listas, etc).\"}]},{\"role\":\"model\",\"parts\":[{\"text\":\"Ol\\u00e1! Sou o Or\\u00e1culo, o teu Personal Trainer virtual criado com IA. \\ud83d\\udcaa<br><br>Estou aqui para desenhar o treino perfeito para ti ou tirar todas as tuas d\\u00favidas. O que pretendes atacar hoje?\"}]},{\"role\":\"user\",\"parts\":[{\"text\":\"oi\"}]},{\"role\":\"model\",\"parts\":[{\"text\":\"Ol\\u00e1! Sou o **Or\\u00e1culo**, o teu Personal Trainer virtual. Fico muito contente por estares aqui para dar o pr\\u00f3ximo passo na tua jornada de fitness! \\ud83d\\udcaa\\ud83d\\udd25\\n\\nPara que eu possa criar um plano de treino totalmente personalizado, seguro e eficaz para ti, preciso de conhecer um pouco melhor o teu perfil. Por favor, responde \\u00e0s seguintes quest\\u00f5es:\\n\\n1.  **Objetivo:** O que pretendes alcan\\u00e7ar? (Ex: Hipertrofia\\/Ganhar massa, perda de gordura, aumento de for\\u00e7a, etc.)\\n2.  **Experi\\u00eancia:** Qual o teu n\\u00edvel atual? (Iniciante, interm\\u00e9dio ou avan\\u00e7ado)\\n3.  **Limita\\u00e7\\u00f5es:** Tens alguma les\\u00e3o, dor ou limita\\u00e7\\u00e3o f\\u00edsica que eu deva ter em conta?\\n4.  **Dados F\\u00edsicos:** Qual a tua idade, peso e altura?\\n5.  **Disponibilidade:** Quantos dias por semana tens dispon\\u00edveis para treinar?\\n6.  **Equipamento:** Onde vais treinar? (Num gin\\u00e1sio completo, em casa com alguns halteres, ou apenas com o peso do corpo?)\\n\\nAssim que me enviares estes detalhes, vou desenhar o teu plano de treino completo. Vamos a isto? \\ud83d\\ude80\"}]},{\"role\":\"user\",\"parts\":[{\"text\":\"decida por mim\"}]},{\"role\":\"model\",\"parts\":[{\"text\":\"Com certeza! Se queres que eu tome as r\\u00e9deas, vou desenhar um plano cl\\u00e1ssico e altamente eficaz de **3 dias por semana** (Estrutura A-B-C). Este plano foca-se na **hipertrofia e condi\\u00e7\\u00e3o f\\u00edsica geral**, assumindo que tens acesso a um **gin\\u00e1sio** convencional.\\n\\nComo n\\u00e3o conhe\\u00e7o as tuas limita\\u00e7\\u00f5es, pe\\u00e7o-te que **pares imediatamente se sentires dor aguda** e que garantas sempre um bom aquecimento de 5 a 10 minutos antes de come\\u00e7ar. \\n\\nAqui tens o teu plano de ataque, focado na progress\\u00e3o e na t\\u00e9cnica:\\n\\n### **Treino A: Peito, Tr\\u00edceps e Ombro**\\n*Foco: Empurrar*\\n1. **Supino Reto com Barra:** 4 s\\u00e9ries x 10 repeti\\u00e7\\u00f5es (Peito)\\n2. **Aberturas com Halteres (Banco Inclinado):** 3 s\\u00e9ries x 12 repeti\\u00e7\\u00f5es (Peito)\\n3. **Press Militar com Halteres (Sentado):** 3 s\\u00e9ries x 10 repeti\\u00e7\\u00f5es (Ombro)\\n4. **Eleva\\u00e7\\u00f5es Laterais:** 3 s\\u00e9ries x 15 repeti\\u00e7\\u00f5es (Ombro)\\n5. **Tr\\u00edceps na Corda (Polia):** 4 s\\u00e9ries x 12 repeti\\u00e7\\u00f5es (Tr\\u00edceps)\\n\\n### **Treino B: Costa e B\\u00edceps**\\n*Foco: Puxar*\\n1. **Puxada \\u00e0 Frente (Lat Pulldown):** 4 s\\u00e9ries x 10 repeti\\u00e7\\u00f5es (Costa)\\n2. **Remada com Halter (Unilateral):** 3 s\\u00e9ries x 12 repeti\\u00e7\\u00f5es (Costa)\\n3. **Puxada com Tri\\u00e2ngulo (Polia Baixa):** 3 s\\u00e9ries x 12 repeti\\u00e7\\u00f5es (Costa)\\n4. **Curl de B\\u00edceps com Barra Z:** 3 s\\u00e9ries x 12 repeti\\u00e7\\u00f5es (B\\u00edceps)\\n5. **Curl Martelo com Halteres:** 3 s\\u00e9ries x 12 repeti\\u00e7\\u00f5es (B\\u00edceps)\\n\\n### **Treino C: Pernas (Completo)**\\n*Foco: Membros Inferiores*\\n1. **Agachamento em Goblet (com Halter):** 4 s\\u00e9ries x 12 repeti\\u00e7\\u00f5es (Quadr\\u00edceps\\/Gl\\u00fateo)\\n2. **Leg Press 45\\u00ba:** 3 s\\u00e9ries x 12 repeti\\u00e7\\u00f5es (Pernas Geral)\\n3. **Extensora de Pernas:** 3 s\\u00e9ries x 15 repeti\\u00e7\\u00f5es (Quadr\\u00edceps)\\n4. **Flexora de Pernas (Deitado ou Sentado):** 3 s\\u00e9ries x 15 repeti\\u00e7\\u00f5es (Isquiotibiais)\\n5. **Eleva\\u00e7\\u00e3o de G\\u00e9meos em P\\u00e9:** 4 s\\u00e9ries x 20 repeti\\u00e7\\u00f5es (G\\u00e9meos)\\n\\n---\\n\\n**Dica do Or\\u00e1culo:** Descansa entre 60 a 90 segundos entre s\\u00e9ries. A consist\\u00eancia \\u00e9 o teu superpoder! Se este plano te parecer bem, come\\u00e7a j\\u00e1 amanh\\u00e3. Estarei aqui para celebrar os teus resultados! \\ud83d\\udcaa\\ud83d\\udd25\\n\\n```json_treino\\n{\\n  \\\"treinos\\\": [\\n    {\\n      \\\"nome\\\": \\\"Treino A - Peito, Tr\\u00edceps e Ombro\\\",\\n      \\\"foco\\\": \\\"Hipertrofia\\\",\\n      \\\"exercicios\\\": [\\n        { \\\"nome\\\": \\\"Supino Reto com Barra\\\", \\\"series\\\": 4, \\\"repeticoes\\\": 10, \\\"grupo_muscular\\\": \\\"Peito\\\" },\\n        { \\\"nome\\\": \\\"Aberturas com Halteres Inclinado\\\", \\\"series\\\": 3, \\\"repeticoes\\\": 12, \\\"grupo_muscular\\\": \\\"Peito\\\" },\\n        { \\\"nome\\\": \\\"Press Militar com Halteres\\\", \\\"series\\\": 3, \\\"repeticoes\\\": 10, \\\"grupo_muscular\\\": \\\"Ombro\\\" },\\n        { \\\"nome\\\": \\\"Eleva\\u00e7\\u00f5es Laterais\\\", \\\"series\\\": 3, \\\"repeticoes\\\": 15, \\\"grupo_muscular\\\": \\\"Ombro\\\" },\\n        { \\\"nome\\\": \\\"Tr\\u00edceps na Corda\\\", \\\"series\\\": 4, \\\"repeticoes\\\": 12, \\\"grupo_muscular\\\": \\\"Tr\\u00edceps\\\" }\\n      ]\\n    },\\n    {\\n      \\\"nome\\\": \\\"Treino B - Costa e B\\u00edceps\\\",\\n      \\\"foco\\\": \\\"Hipertrofia\\\",\\n      \\\"exercicios\\\": [\\n        { \\\"nome\\\": \\\"Puxada \\u00e0 Frente\\\", \\\"series\\\": 4, \\\"repeticoes\\\": 10, \\\"grupo_muscular\\\": \\\"Costa\\\" },\\n        { \\\"nome\\\": \\\"Remada com Halter Unilateral\\\", \\\"series\\\": 3, \\\"repeticoes\\\": 12, \\\"grupo_muscular\\\": \\\"Costa\\\" },\\n        { \\\"nome\\\": \\\"Puxada com Tri\\u00e2ngulo\\\", \\\"series\\\": 3, \\\"repeticoes\\\": 12, \\\"grupo_muscular\\\": \\\"Costa\\\" },\\n        { \\\"nome\\\": \\\"Curl de B\\u00edceps com Barra Z\\\", \\\"series\\\": 3, \\\"repeticoes\\\": 12, \\\"grupo_muscular\\\": \\\"B\\u00edceps\\\" },\\n        { \\\"nome\\\": \\\"Curl Martelo com Halteres\\\", \\\"series\\\": 3, \\\"repeticoes\\\": 12, \\\"grupo_muscular\\\": \\\"B\\u00edceps\\\" }\\n      ]\\n    },\\n    {\\n      \\\"nome\\\": \\\"Treino C - Pernas (Completo)\\\",\\n      \\\"foco\\\": \\\"Hipertrofia\\\",\\n      \\\"exercicios\\\": [\\n        { \\\"nome\\\": \\\"Agachamento Goblet\\\", \\\"series\\\": 4, \\\"repeticoes\\\": 12, \\\"grupo_muscular\\\": \\\"Pernas\\\" },\\n        { \\\"nome\\\": \\\"Leg Press 45\\u00ba\\\", \\\"series\\\": 3, \\\"repeticoes\\\": 12, \\\"grupo_muscular\\\": \\\"Pernas\\\" },\\n        { \\\"nome\\\": \\\"Extensora de Pernas\\\", \\\"series\\\": 3, \\\"repeticoes\\\": 15, \\\"grupo_muscular\\\": \\\"Pernas\\\" },\\n        { \\\"nome\\\": \\\"Flexora de Pernas\\\", \\\"series\\\": 3, \\\"repeticoes\\\": 15, \\\"grupo_muscular\\\": \\\"Pernas\\\" },\\n        { \\\"nome\\\": \\\"Eleva\\u00e7\\u00e3o de G\\u00e9meos\\\", \\\"series\\\": 4, \\\"repeticoes\\\": 20, \\\"grupo_muscular\\\": \\\"Pernas\\\" }\\n      ]\\n    }\\n  ]\\n}\\n```\"}]}]','2026-03-22 00:14:15');
/*!40000 ALTER TABLE `chatbot_sessoes` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checklist_diario`
--

LOCK TABLES `checklist_diario` WRITE;
/*!40000 ALTER TABLE `checklist_diario` DISABLE KEYS */;
INSERT INTO `checklist_diario` VALUES (1,10,'2026-03-21',1),(2,11,'2026-03-21',1),(3,12,'2026-03-21',1),(4,13,'2026-03-21',1),(5,55,'2026-03-21',1),(6,10,'2026-03-22',1),(7,11,'2026-03-22',1),(8,12,'2026-03-22',1),(9,13,'2026-03-22',1),(10,55,'2026-03-22',1),(11,10,'2026-03-24',1),(12,11,'2026-03-24',1),(13,12,'2026-03-24',1),(14,55,'2026-03-24',1),(15,13,'2026-03-24',1);
/*!40000 ALTER TABLE `checklist_diario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `day_streak`
--

DROP TABLE IF EXISTS `day_streak`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `day_streak` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `data_streak` date NOT NULL,
  `desafios_concluidos` int(11) NOT NULL DEFAULT 0,
  `streak_valido` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_data` (`id_user`,`data_streak`),
  CONSTRAINT `day_streak_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `day_streak`
--

LOCK TABLES `day_streak` WRITE;
/*!40000 ALTER TABLE `day_streak` DISABLE KEYS */;
INSERT INTO `day_streak` VALUES (1,3,'2026-03-21',5,1),(8,3,'2026-03-22',5,1),(20,3,'2026-03-24',5,1);
/*!40000 ALTER TABLE `day_streak` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=172 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exercicio`
--

LOCK TABLES `exercicio` WRITE;
/*!40000 ALTER TABLE `exercicio` DISABLE KEYS */;
INSERT INTO `exercicio` VALUES (1,1,'Supino com Halteres (Inclinado)',4,8,'Peito'),(2,1,'Peck Deck ou Máquina de Peito Sentado',3,10,'Peito'),(3,1,'Crossover (Polia Alta)',3,12,'Peito'),(4,1,'Press de Ombro com Halteres (Sentado)',3,10,'Ombro'),(5,1,'Elevação Frontal com Halteres (Alternado)',3,12,'Ombro'),(6,1,'Extensão de Tríceps (Polia Alta c/ Corda)',4,10,'Tríceps'),(7,1,'Mergulhos (Dips) na Máquina (Assistida)',3,0,'Tríceps'),(8,2,'Puxada Vertical (Lat Pulldown)',4,8,'Costa'),(9,2,'Remada Sentada (Cabo/Máquina)',4,10,'Costa'),(10,2,'Peso Morto (Romanian Deadlift)',3,8,'Posterior Perna/Lombar'),(11,2,'Hiperextensão Lombar',3,15,'Lombar'),(12,2,'Curl de Bíceps com Halteres (Sentado)',3,10,'Bíceps'),(13,2,'Curl Martelo (Halteres)',3,12,'Bíceps/Antebraço'),(14,3,'Agachamento (Squat) na Máquina Smith',4,8,'Pernas'),(15,3,'Leg Press (Prensa de Pernas)',4,10,'Pernas'),(16,3,'Extensão de Pernas (Leg Extension)',3,15,'Quadríceps'),(17,3,'Flexão de Pernas (Leg Curl - Deitado)',3,12,'Isquiotibiais'),(18,3,'Lunges (Avanços) com Halteres',3,10,'Pernas'),(19,3,'Elevação de Gémeos (Sentado ou em Pé)',4,15,'Gémeos'),(20,4,'Supino na Máquina (Sentado)',3,10,'Peito'),(21,4,'Crossover (Polia Média/Baixa)',3,12,'Peito'),(22,4,'Elevação Lateral (Máquina ou Halteres Leves)',4,15,'Ombro'),(23,4,'Face Pull (Polia)',3,15,'Ombro Posterior'),(24,4,'Extensão Francesa (Barra W ou Halteres)',3,10,'Tríceps'),(25,4,'Tríceps Kickback (Halteres)',3,15,'Tríceps'),(29,5,'supino',3,12,'peito'),(30,5,'oi',3,12,'aaaa'),(31,5,'aaaaa',3,12,'aa'),(32,6,'Supino Reto com Halteres',4,10,'Peito'),(33,6,'Crossover (Cabo Baixo para Alto)',3,15,'Peito'),(34,6,'Peck Deck / Máquina de Peito',3,12,'Peito'),(35,6,'Tríceps Corda (Pushdown)',3,15,'Tríceps'),(36,6,'Tríceps Francês (Haltere sentado)',3,12,'Tríceps'),(37,6,'Elevação Lateral (Máquina/Haltere)',3,20,'Ombro'),(38,7,'Puxada Vertical (Lat Pulldown)',4,10,'Costa'),(39,7,'Remada Curvada com Halteres',4,10,'Costa'),(40,7,'Remada Sentada (Cabo)',3,12,'Costa'),(41,7,'Hiperextensão Lombar',3,15,'Lombar'),(42,7,'Curl de Bíceps com Barra EZ',3,12,'Bíceps'),(43,7,'Curl Martelo (Halteres)',3,15,'Bíceps'),(44,8,'Agachamento Livre (Barra)',4,10,'Pernas'),(45,8,'Leg Press',3,12,'Pernas'),(46,8,'Extensão de Pernas (Máquina)',3,15,'Pernas'),(47,8,'Flexão de Pernas Deitado (Posterior)',3,12,'Pernas'),(48,8,'Peso Morto Romeno (Halteres/Barra)',3,12,'Pernas'),(49,8,'Elevação de Gémeos (Máquina Sentado)',3,20,'Gémeos'),(50,9,'Supino Inclinado com Halteres',4,10,'Peito'),(51,9,'Press de Peito na Máquina (Horizontal)',3,12,'Peito'),(52,9,'Elevação Frontal (Cabo ou Haltere)',3,15,'Ombro'),(53,9,'Press de Ombro com Halteres (Sentado)',3,12,'Ombro'),(54,9,'Voador Inverso (Máquina/Haltere)',3,20,'Ombro'),(55,9,'Abdominais (Cabo ou Máquina)',3,20,'Abdominais'),(56,10,'Curl de Bíceps Concentrado (Sentado)',3,12,'Bíceps'),(57,10,'Tríceps Testa (Barra EZ)',3,10,'Tríceps'),(58,10,'Curl de Bíceps no Cabo (Pega Inversa)',3,15,'Bíceps'),(59,10,'Tríceps Mergulho (Paralelas ou Máquina)',3,12,'Tríceps'),(60,10,'Curl Zottman (Halteres)',3,12,'Bíceps'),(61,10,'Extensão de Tríceps (Cabo - Pega V)',3,20,'Tríceps'),(78,14,'Leg Press 45º',4,12,'Pernas'),(79,14,'Leg Extension',3,15,'Pernas'),(80,14,'Leg Curl',3,15,'Pernas'),(81,14,'Lunges com Halteres',3,10,'Pernas'),(82,14,'Gémeos em pé',4,20,'Gémeos'),(83,15,'Curl Bíceps Inclinado',3,12,'Bíceps'),(84,15,'Tríceps Testa',3,12,'Tríceps'),(85,15,'Curl Concentrado',3,12,'Bíceps'),(86,15,'Mergulhos entre bancos',3,15,'Tríceps'),(87,15,'Curl Bíceps Corda',3,15,'Bíceps'),(88,16,'Supino Inclinado Halteres',3,12,'Peito'),(89,16,'Pec Deck',3,15,'Peito'),(90,16,'Elevações Frontais',3,12,'Ombro'),(91,16,'Elevações Laterais Polia',3,15,'Ombro'),(92,16,'Prancha Abdominal',3,60,'Core'),(93,12,'Supino com Halteres',3,12,'Peito'),(94,12,'Aberturas na Máquina',3,15,'Peito'),(95,12,'Press Militar Halteres',3,10,'Ombro'),(96,12,'Elevações Laterais',3,15,'Ombro'),(97,12,'Tríceps Corda',4,12,'Tríceps'),(98,12,'Tríceps Testa Halteres',3,12,'Tríceps'),(99,13,'Puxada à Frente',3,12,'Costas'),(100,13,'Remada Sentada',3,12,'Costas'),(101,13,'Remada Unilateral5',3,10,'Costas'),(102,13,'Curl Barra Z',3,12,'Bíceps'),(103,13,'Curl Martelo23232',3,12,'Bíceps'),(104,13,'Face Pulls',3,15,'Ombro/Posterior'),(105,17,'supino',3,12,'peito'),(106,18,'Supino Reto com Halteres',3,12,'Peito'),(107,18,'Aberturas com Halteres',3,12,'Peito'),(108,18,'Tríceps na Corda',3,15,'Tríceps'),(109,18,'Elevação Lateral',3,12,'Ombro'),(110,18,'Extensão Tríceps Halter',3,12,'Tríceps'),(111,19,'Puxada à Frente',3,12,'Costas'),(112,19,'Remada Sentada',3,12,'Costas'),(113,19,'Rosca Direta Barra Z',3,12,'Bíceps'),(114,19,'Rosca Martelo',3,12,'Bíceps'),(115,19,'Hiperextensões',3,15,'Lombar'),(116,20,'Leg Press 45º',4,12,'Pernas'),(117,20,'Extensora',3,15,'Pernas'),(118,20,'Flexora',3,15,'Pernas'),(119,20,'Afundos com Halteres',3,10,'Pernas'),(120,20,'Gémeos em pé',4,20,'Gémeos'),(121,21,'Rosca Scott',3,12,'Bíceps'),(122,21,'Tríceps Testa Halteres',3,12,'Tríceps'),(123,21,'Rosca Concentrada',3,12,'Bíceps'),(124,21,'Tríceps Coice Cabo',3,12,'Tríceps'),(125,21,'Flexão de Punhos',3,15,'Antebraço'),(126,22,'Supino Inclinado Halteres',3,12,'Peito'),(127,22,'Peck Deck',3,15,'Peito'),(128,22,'Elevação Frontal Disco',3,12,'Ombro'),(129,22,'Face Pulls',3,15,'Ombro'),(130,23,'Supino Inclinado com Halteres',4,12,'Peito'),(131,23,'Chest Press Máquina',3,12,'Peito'),(132,23,'Pec Deck',3,15,'Peito'),(133,23,'Tríceps Corda',4,15,'Tríceps'),(134,23,'Tríceps Testa Halteres',3,12,'Tríceps'),(135,23,'Elevações Laterais',4,15,'Ombro'),(136,24,'Puxada à Frente',4,12,'Costas'),(137,24,'Remada Curvada Barra',4,10,'Costas'),(138,24,'Remada Unilateral Halter',3,12,'Costas'),(139,24,'Curl Barra EZ',4,10,'Bíceps'),(140,24,'Curl Martelo',3,12,'Bíceps'),(141,24,'Face Pulls',3,15,'Ombro Posterior'),(142,25,'Agachamento Livre',4,10,'Pernas'),(143,25,'Leg Press 45',4,12,'Pernas'),(144,25,'Extensora',3,15,'Pernas'),(145,25,'Flexora',4,12,'Pernas'),(146,25,'Gémeos em pé',4,20,'Pernas'),(147,26,'Dips (Máquina)',3,12,'Tríceps'),(148,26,'Curl Halter Banco Inclinado',3,12,'Bíceps'),(149,26,'Tríceps Pulley Barra V',4,12,'Tríceps'),(150,26,'Curl Concentrado',3,12,'Bíceps'),(151,26,'Tríceps Corda Overhead',3,15,'Tríceps'),(152,27,'Supino Reto Halteres',4,10,'Peito'),(153,27,'Crossover Polia Alta',3,15,'Peito'),(154,27,'Elevações Laterais Polia',4,12,'Ombro'),(155,27,'Elevações Frontais Disco',3,12,'Ombro'),(156,27,'Encolhimentos Halteres',4,15,'Trapézio'),(157,28,'Supino Reto com Barra',4,10,'Peito'),(158,28,'Aberturas com Halteres Inclinado',3,12,'Peito'),(159,28,'Press Militar com Halteres',3,10,'Ombro'),(160,28,'Elevações Laterais',3,15,'Ombro'),(161,28,'Tríceps na Corda',4,12,'Tríceps'),(162,29,'Puxada à Frente',4,10,'Costa'),(163,29,'Remada com Halter Unilateral',3,12,'Costa'),(164,29,'Puxada com Triângulo',3,12,'Costa'),(165,29,'Curl de Bíceps com Barra Z',3,12,'Bíceps'),(166,29,'Curl Martelo com Halteres',3,12,'Bíceps'),(167,30,'Agachamento Goblet',4,12,'Pernas'),(168,30,'Leg Press 45º',3,12,'Pernas'),(169,30,'Extensora de Pernas',3,15,'Pernas'),(170,30,'Flexora de Pernas',3,15,'Pernas'),(171,30,'Elevação de Gémeos',4,20,'Pernas');
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
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `habito`
--

LOCK TABLES `habito` WRITE;
/*!40000 ALTER TABLE `habito` DISABLE KEYS */;
INSERT INTO `habito` VALUES (1,1,'Beber 2L de água','2','Saúde'),(2,1,'Fazer 10 flexões','10','Exercício'),(3,1,'Fazer 20 polichinelos','20','Exercício'),(4,1,'Caminhar 5.000 passos','5000','Exercício'),(5,2,'Beber 2.5L de água','2.5','Saúde'),(6,2,'Fazer 25 flexões','25','Exercício'),(7,2,'Fazer 50 polichinelos','50','Exercício'),(8,2,'Caminhar 8.000 passos','8000','Exercício'),(9,2,'Correr 3km','3','Exercício'),(10,3,'Beber 2L de água','2','Saúde'),(11,3,'Fazer 10 flexões','10','Exercício'),(12,3,'Fazer 20 polichinelos','20','Exercício'),(13,3,'Caminhar 5.000 passos','5000','Exercício'),(14,4,'Beber 3L de água','3','Saúde'),(15,4,'Fazer 50 flexões','50','Exercício'),(16,4,'Fazer 100 polichinelos','100','Exercício'),(17,4,'Caminhar 12.000 passos','12000','Exercício'),(18,4,'Correr 5km','5','Exercício'),(19,4,'Treinar 45 minutos','45','Exercício'),(20,6,'Beber 3L de água','3','Saúde'),(21,6,'Fazer 50 flexões','50','Exercício'),(22,6,'Fazer 100 polichinelos','100','Exercício'),(23,6,'Caminhar 12.000 passos','12000','Exercício'),(24,6,'Correr 5km','5','Exercício'),(25,6,'Treinar 45 minutos','45','Exercício'),(26,7,'Beber 3L de água','3','Saúde'),(27,7,'Fazer 50 flexões','50','Exercício'),(28,7,'Fazer 100 polichinelos','100','Exercício'),(29,7,'Caminhar 12.000 passos','12000','Exercício'),(30,7,'Correr 5km','5','Exercício'),(31,7,'Treinar 45 minutos','45','Exercício'),(32,8,'Beber 3.5L de água','3.5','Saúde'),(33,8,'Fazer 100 flexões','100','Exercício'),(34,8,'Fazer 200 polichinelos','200','Exercício'),(35,8,'Caminhar 15.000 passos','15000','Exercício'),(36,8,'Correr 10km','10','Exercício'),(37,8,'Treinar 60 minutos','60','Exercício'),(38,8,'Fazer abdominais (3 séries de 20)','60','Exercício'),(39,8,'apresentação','2','apresentar'),(40,9,'Beber 3L de água','3','Saúde'),(41,9,'Fazer 50 flexões','50','Exercício'),(42,9,'Fazer 100 polichinelos','100','Exercício'),(43,9,'Caminhar 12.000 passos','12000','Exercício'),(44,9,'Correr 5km','5','Exercício'),(45,9,'Treinar 45 minutos','45','Exercício'),(46,11,'Beber 2L de água','2','Saúde'),(47,11,'Fazer 10 flexões','10','Exercício'),(48,11,'Fazer 20 polichinelos','20','Exercício'),(49,11,'Caminhar 5.000 passos','5000','Exercício'),(50,12,'Beber 2L de água','2','Saúde'),(51,12,'Fazer 10 flexões','10','Exercício'),(52,12,'Fazer 20 polichinelos','20','Exercício'),(53,12,'Caminhar 5.000 passos','5000','Exercício'),(54,12,'pap123','5','exercicio'),(55,3,'teste1','1','saude');
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meta_usuario`
--

LOCK TABLES `meta_usuario` WRITE;
/*!40000 ALTER TABLE `meta_usuario` DISABLE KEYS */;
INSERT INTO `meta_usuario` VALUES (1,1,'agua',2.00),(2,2,'agua',2.50),(3,3,'agua',5.00),(4,4,'agua',3.00),(5,6,'agua',9.00),(6,7,'agua',3.00),(7,3,'calorias',6000.00),(8,8,'agua',5.00),(9,8,'calorias',4500.00),(10,9,'agua',3.00),(11,11,'agua',2.00),(12,12,'agua',5.00);
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
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `token` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
INSERT INTO `password_reset_tokens` VALUES (1,3,'598858','2026-03-12 16:49:06',1),(2,3,'226392','2026-03-12 16:53:08',1),(3,3,'289158','2026-03-12 16:53:14',1),(4,3,'404389','2026-03-19 13:04:19',1),(5,3,'187456','2026-03-19 16:42:30',1);
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
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
INSERT INTO `peso` VALUES (1,6,65.00,'2026-02-25'),(2,8,120.00,'2026-03-04'),(3,12,120.00,'2026-03-05');
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
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `treino`
--

LOCK TABLES `treino` WRITE;
/*!40000 ALTER TABLE `treino` DISABLE KEYS */;
INSERT INTO `treino` VALUES (1,3,'Dia 1 - Treino A: Peito, Tríceps e Ombro (Foco Peito)','Hipertrofia','2026-01-22','(CURRENT_DATE)'),(2,3,'Dia 2 - Treino B: Costa e Bíceps','Hipertrofia','2026-01-22','(CURRENT_DATE)'),(3,3,'Dia 4 - Treino C: Pernas (Completo)','Hipertrofia','2026-01-22','(CURRENT_DATE)'),(4,3,'Dia 5 - Treino D: Peito e Ombro (Foco Ombro/Tríceps)','Hipertrofia','2026-01-22','(CURRENT_DATE)'),(5,2,'peito','Hipertrofia','2026-01-29','(CURRENT_DATE)'),(6,2,'Dia 1: Peito, Tríceps e Ombro (A)','Hipertrofia','2026-01-29','(CURRENT_DATE)'),(7,2,'Dia 2: Costa e Bíceps (B)','Hipertrofia','2026-01-29','(CURRENT_DATE)'),(8,2,'Dia 3: Pernas (C)','Hipertrofia','2026-01-29','(CURRENT_DATE)'),(9,2,'Dia 5: Peito e Ombro (E)','Hipertrofia','2026-01-29','(CURRENT_DATE)'),(10,2,'Dia 6: Braços Completo (D)','Hipertrofia','2026-01-29','(CURRENT_DATE)'),(12,8,'Treino A - Peito, Tríceps e Ombro (pap)','Hipertrofia','2026-03-04','(CURRENT_DATE)'),(13,8,'Treino B - Costa e Bíceps','Hipertrofia','2026-03-04','(CURRENT_DATE)'),(14,8,'Treino C - Pernas (Completo)','Hipertrofia','2026-03-04','(CURRENT_DATE)'),(15,8,'Treino D - Braços Completo','Hipertrofia/Volume','2026-03-04','(CURRENT_DATE)'),(16,8,'Treino E - Peito e Ombro','Hipertrofia','2026-03-04','(CURRENT_DATE)'),(17,12,'teste1','Hipertrofia','2026-03-05','(CURRENT_DATE)'),(18,3,'Treino A - Peito, Tríceps e Ombro','Hipertrofia / Segurança Articular','2026-03-05','(CURRENT_DATE)'),(19,3,'Treino B - Costa e Bíceps','Hipertrofia','2026-03-05','(CURRENT_DATE)'),(20,3,'Treino C - Pernas (Completo)','Hipertrofia','2026-03-05','(CURRENT_DATE)'),(21,3,'Treino D - Braços Completo','Hipertrofia','2026-03-05','(CURRENT_DATE)'),(22,3,'Treino E - Peito e Ombro','Hipertrofia','2026-03-05','(CURRENT_DATE)'),(23,3,'Treino A - Peito, Tríceps e Ombro','Hipertrofia e Estabilidade','2026-03-22','(CURRENT_DATE)'),(24,3,'Treino B - Costa e Bíceps','Hipertrofia','2026-03-22','(CURRENT_DATE)'),(25,3,'Treino C - Pernas Completo','Hipertrofia','2026-03-22','(CURRENT_DATE)'),(26,3,'Treino D - Braços Completo','Hipertrofia/Pump','2026-03-22','(CURRENT_DATE)'),(27,3,'Treino E - Peito e Ombro','Hipertrofia/Deltoides','2026-03-22','(CURRENT_DATE)'),(28,3,'Treino A - Peito, Tríceps e Ombro','Hipertrofia','2026-03-22','(CURRENT_DATE)'),(29,3,'Treino B - Costa e Bíceps','Hipertrofia','2026-03-22','(CURRENT_DATE)'),(30,3,'Treino C - Pernas (Completo)','Hipertrofia','2026-03-22','(CURRENT_DATE)');
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
  `username` varchar(50) DEFAULT NULL,
  `tfa_secret` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `user_email_unique` (`email`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `user_username_unique` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'Saude em sintonia','contato.saudeemsintonia@gmail.com','$2y$10$wuFN8x.6RXIX12IgmHzsIezGHWpT5VdTwHE9PGJlJ49UMbelFO4ka','2026-01-16 09:21:47','gratuito',NULL,NULL,NULL,NULL,NULL,'Usuario',NULL,'assets/fotos/default-user.png',0,'Renegado','victor2609',NULL),(2,'discord gg','discordggts8@gmail.com','$2y$10$KYSCAtvrxdx.MjTlq5Ma4Oov9YzeneH2CxmS0snR4Czgc2aBDYIU.','2026-01-16 09:23:37','gratuito',NULL,NULL,NULL,NULL,NULL,'Admin',NULL,'assets/fotos/default-user.png',0,'Renegado','teste123',NULL),(3,'Victor Hugo','victorhugoribeiro2609@gmail.com','$2y$10$DYFdg5O5JxKI0onZIDN7Ye1aHklVNbThAHeqSnrkIwkUvrySjUKDu','2026-01-16 09:55:40','gratuito',NULL,'','','0000-00-00','','Admin','assets/fotos/1774345821_wp3616395-audi-4k-wallpapers.jpg','assets/fotos/default-user.png',0,'Renegado','victor260906','7DW6WBNY77NTB4B5LQE42FH5X2WRAICU'),(4,'G','vitoriaribesantos@gmail.com','$2y$10$NEcUIG4a7WsMymY2bXX9ZundykTJjCcujfiWg1ZcJJT8xzXs6JMXS','2026-02-05 14:37:59','gratuito',NULL,NULL,NULL,NULL,NULL,'Usuario',NULL,'assets/fotos/default-user.png',0,'Renegado',NULL,NULL),(6,'tsiuki sokosks','tsukisteam2609@gmail.com','$2y$10$oF6jU0MuO3JzEPgSNGmdE.R3B0f/PjW9QRXhZdPJZ2qSQsXl4vhAu','2026-02-25 16:01:48','gratuito',NULL,'','','0000-00-00','','Usuario','assets/fotos/1772035730_unnamed (2).jpg','assets/fotos/default-user.png',0,'Renegado','testeokjte','OE2ITOIQR6X64EJ7KACRVXPUCBWNX5NR'),(7,'Victor Santos','victorhugoribeiro992609@gmail.com','$2y$10$dW7DgrfqyGUls6KwkUaI4uoItYo2mZtpvWrx4cVDgPC3QQoDA/k66','2026-02-26 14:56:43','gratuito',NULL,'351','910877269','2006-09-26','Feminino','Usuario',NULL,'assets/fotos/default-user.png',0,'Renegado','Howl7Z',NULL),(8,'victor12','victor123@gmail.com','$2y$10$xJkyFFHGXcw.W4eFpNSGAOWnVqJTgMa6NPOat7K1Bk0V8uWubk/Ea','2026-03-04 10:29:08','gratuito',NULL,'351','911111155','2006-09-26','Masculino','Usuario','assets/fotos/1772621590_IMG_7810.jpeg','assets/fotos/default-user.png',0,'Renegado','victor123','YQKFBO52HL6CLXL7L3HLE67YWDXJOKWI'),(9,'Pap Victor','papvictorsantos@gmail.com','$2y$10$8T6GmTOpIQf3KhPqxJO7c.nbgqNkwN.4vgWKl4AAAar0hJsXLt9O2','2026-03-04 10:58:25','gratuito',NULL,'','','0000-00-00','','Usuario',NULL,'assets/fotos/default-user.png',0,'Renegado','victorpap',NULL),(10,'VICTOR SANTOSPAP','PAP123@GMAIL.COM','$2y$10$LXmEG1a7/6GAVSifA51k4.zBxmG9H5P5QsuhoUOanK24fC8TdHptO','2026-03-05 11:23:16','gratuito',NULL,'351','910877269','2006-09-26','Masculino','Usuario',NULL,'assets/fotos/default-user.png',0,'Renegado','victorpap123',NULL),(11,'Victor Santos','victor123pap1@gmail.com','$2y$10$xZ79yNWX9Y804zYmNFu6eO0t2sCf5Qefrrw2MzlBzjoTamd4hNjMK','2026-03-05 11:24:06','gratuito',NULL,'351','910877269','2006-09-26','Masculino','Usuario',NULL,'assets/fotos/default-user.png',0,'Renegado','victor123papteste',NULL),(12,'Papintermedia','papintermedia@gmail.com','$2y$10$D5LLBEzXPWboviBkUrTKkOMxB4ikMr2b.LGQbIGqYhTkFFZWsdUSG','2026-03-05 11:25:21','gratuito',NULL,'351','918087728','2006-09-26','Masculino','Usuario','assets/fotos/1772710694_FOTOPAP.jpeg','assets/fotos/default-user.png',0,'Renegado','victorapresentacao','MFPPU3QSHY7N74H2BTLVVGESYVF7KPA2');
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'berserkfit'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-24 10:34:49
