<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilizador não logado.']);
    exit;
}

require 'ligacao.php';

$user_id = $_SESSION['user_id'];
$calorias = $_POST['calorias'] ?? 0;
$proteinas = $_POST['proteinas'] ?? 0;
$carbs = $_POST['carbs'] ?? 0;
$gorduras = $_POST['gorduras'] ?? 0;
$refeicao = $_POST['refeicao'] ?? 'Outro';
$descricao = $_POST['descricao'] ?? '';
$data = $_POST['data'] ?? date('Y-m-d');

$sql = "INSERT INTO alimentacao (id_user, calorias, proteinas, carbs, gorduras, refeicao, descricao, data) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("idddssss", $user_id, $calorias, $proteinas, $carbs, $gorduras, $refeicao, $descricao, $data);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao executar no banco de dados.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Erro na preparação da query.']);
}

$conn->close();
?>
