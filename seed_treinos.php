<?php
require 'ligacao.php';

$email = 'papvictorsantos@gmail.com';
$stmt = $conn->prepare("SELECT id_user FROM user WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

if (!$user) {
    die("Utilizador $email não encontrado.");
}
$user_id = $user['id_user'];
echo "User ID: $user_id\n";

// Verificar o esquema da tabela historico_treino_log
$result = $conn->query("DESCRIBE historico_treino_log");
$has_data_treino = false;
while ($row = $result->fetch_assoc()) {
    if ($row['Field'] === 'data_treino') {
        $has_data_treino = true;
    }
}
if (!$has_data_treino) {
    $conn->query("ALTER TABLE historico_treino_log CHANGE data_fim data_treino DATETIME DEFAULT CURRENT_TIMESTAMP");
    echo "Coluna data_fim renomeada para data_treino\n";
} else {
    echo "Coluna data_treino já existe\n";
}

// 1. Criar um Treino
$conn->query("INSERT INTO treino (id_user, nome_treino, foco, data_criacao) VALUES ($user_id, 'Treino A - Força Bruta', 'Hipertrofia e Força', CURDATE())");
$id_treino = $conn->insert_id;

// 2. Criar Exercícios para este treino
$conn->query("INSERT INTO exercicio (id_treino, nome_exercicio, series, repeticoes, grupo_muscular) VALUES ($id_treino, 'Supino Reto', 4, 10, 'Peito')");
$id_ex1 = $conn->insert_id;
$conn->query("INSERT INTO exercicio (id_treino, nome_exercicio, series, repeticoes, grupo_muscular) VALUES ($id_treino, 'Agachamento Livre', 4, 10, 'Pernas')");
$id_ex2 = $conn->insert_id;
$conn->query("INSERT INTO exercicio (id_treino, nome_exercicio, series, repeticoes, grupo_muscular) VALUES ($id_treino, 'Levantamento Terra', 3, 8, 'Costas')");
$id_ex3 = $conn->insert_id;

// 3. Simular execução deste treino nos últimos 15 dias para ter gráficos bonitos (Volume Global e Força por Exercicio)
for ($i = 15; $i >= 0; $i -= 3) {
    $data_exec = date('Y-m-d H:i:s', strtotime("-$i days"));
    
    // Inserir log do treino
    $conn->query("INSERT INTO historico_treino_log (id_user, id_treino, data_treino, duracao_segundos) VALUES ($user_id, $id_treino, '$data_exec', 3600)");
    $id_log = $conn->insert_id;
    
    // Inserir exercícios executados com progressão de carga (peso aumenta com o passar do tempo)
    // Mais antigo = menos peso, Mais recente = mais peso (progressão de 1kg a 2kg por treino)
    
    $progresso = 15 - $i; // Quanto menor o $i, mais progresso
    
    // Exercicio 1: Supino
    $peso1 = 60 + ($progresso * 1.5);
    for($s = 1; $s <= 4; $s++) {
        $conn->query("INSERT INTO historico_exercicio_log (id_log, id_exercicio, num_serie, peso_kg, repeticoes) VALUES ($id_log, $id_ex1, $s, $peso1, 10)");
    }
    
    // Exercicio 2: Agachamento
    $peso2 = 80 + ($progresso * 2);
    for($s = 1; $s <= 4; $s++) {
        $conn->query("INSERT INTO historico_exercicio_log (id_log, id_exercicio, num_serie, peso_kg, repeticoes) VALUES ($id_log, $id_ex2, $s, $peso2, 10)");
    }
    
    // Exercicio 3: Terra
    $peso3 = 100 + ($progresso * 2.5);
    for($s = 1; $s <= 3; $s++) {
        $conn->query("INSERT INTO historico_exercicio_log (id_log, id_exercicio, num_serie, peso_kg, repeticoes) VALUES ($id_log, $id_ex3, $s, $peso3, 8)");
    }
}

echo "Dados de treino inseridos com sucesso para user ID $user_id.\n";
