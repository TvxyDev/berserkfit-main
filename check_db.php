<?php
require 'ligacao.php';
$res = $conn->query("SHOW COLUMNS FROM historico_treino_log");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
$conn->close();
?>
