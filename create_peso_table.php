<?php
require 'ligacao.php';

$sql = "CREATE TABLE IF NOT EXISTS `historico_peso` (
  `id_historico_peso` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `peso` decimal(5,2) NOT NULL,
  `data_registro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_historico_peso`),
  KEY `fk_peso_user` (`id_user`),
  CONSTRAINT `fk_peso_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if ($conn->query($sql) === TRUE) {
    echo "Tabela historico_peso criada com sucesso.";
} else {
    echo "Erro ao criar tabela: " . $conn->error;
}
$conn->close();
?>
