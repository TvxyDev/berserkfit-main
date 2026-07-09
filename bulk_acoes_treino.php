<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
    exit;
}

require 'ligacao.php';
$user_id = $_SESSION['user_id'];

// Obter dados do POST JSON
$input = json_decode(file_get_contents('php://input'), true);
$acao = $input['acao'] ?? '';

if ($acao === 'apagar_selecionados') {
    $ids = $input['ids'] ?? [];
    if (empty($ids) || !is_array($ids)) {
        echo json_encode(['success' => false, 'message' => 'Nenhum treino selecionado.']);
        exit;
    }

    // Higienizar e converter para inteiros
    $ids = array_map('intval', $ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $conn->begin_transaction();
    try {
        // Excluir exercícios pertencentes aos treinos selecionados deste utilizador
        $query_ex = "DELETE e FROM exercicio e 
                     JOIN treino t ON e.id_treino = t.id_treino 
                     WHERE t.id_treino IN ($placeholders) AND t.id_user = ?";
        
        $stmt_ex = $conn->prepare($query_ex);
        
        $types = str_repeat('i', count($ids)) . 'i';
        $params = array_merge($ids, [$user_id]);
        $stmt_ex->bind_param($types, ...$params);
        $stmt_ex->execute();
        $stmt_ex->close();

        // Excluir os treinos
        $query_tr = "DELETE FROM treino WHERE id_treino IN ($placeholders) AND id_user = ?";
        $stmt_tr = $conn->prepare($query_tr);
        $stmt_tr->bind_param($types, ...$params);
        $stmt_tr->execute();
        $stmt_tr->close();

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Erro ao apagar no servidor: ' . $e->getMessage()]);
    }
} elseif ($acao === 'apagar_tudo') {
    $conn->begin_transaction();
    try {
        // Excluir todos os exercícios de todos os treinos do utilizador
        $query_ex = "DELETE e FROM exercicio e 
                     JOIN treino t ON e.id_treino = t.id_treino 
                     WHERE t.id_user = ?";
        $stmt_ex = $conn->prepare($query_ex);
        $stmt_ex->bind_param("i", $user_id);
        $stmt_ex->execute();
        $stmt_ex->close();

        // Excluir todos os treinos do utilizador
        $query_tr = "DELETE FROM treino WHERE id_user = ?";
        $stmt_tr = $conn->prepare($query_tr);
        $stmt_tr->bind_param("i", $user_id);
        $stmt_tr->execute();
        $stmt_tr->close();

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Erro ao limpar no servidor: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
}

$conn->close();
exit;
