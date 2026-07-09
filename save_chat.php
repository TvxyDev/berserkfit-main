<?php
session_start();
require 'ligacao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$id_sessao = isset($data['id_sessao']) && !empty($data['id_sessao']) ? intval($data['id_sessao']) : null;
$titulo = isset($data['titulo']) ? trim($data['titulo']) : 'Nova Conversa';
$conteudo = isset($data['history']) ? json_encode($data['history']) : null;

if (!$conteudo) {
    echo json_encode(['success' => false, 'error' => 'Conteúdo vazio']);
    exit;
}

if ($id_sessao) {
    $stmt = $conn->prepare("UPDATE chatbot_sessoes SET conteudo_json = ?, titulo = ? WHERE id_sessao = ? AND id_user = ?");
    if (!$stmt) echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
    $stmt->bind_param("ssii", $conteudo, $titulo, $id_sessao, $user_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id_sessao' => $id_sessao, 'titulo' => $titulo]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    $stmt = $conn->prepare("INSERT INTO chatbot_sessoes (id_user, titulo, conteudo_json) VALUES (?, ?, ?)");
    if (!$stmt) echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
    $stmt->bind_param("iss", $user_id, $titulo, $conteudo);
    if ($stmt->execute()) {
        $novo_id = $conn->insert_id;
        echo json_encode(['success' => true, 'id_sessao' => $novo_id, 'titulo' => $titulo]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao inserir: ' . $stmt->error]);
    }
    $stmt->close();
}
$conn->close();
?>
