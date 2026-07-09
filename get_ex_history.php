<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

require 'ligacao.php';

$user_id = $_SESSION['user_id'];
$id_ex = intval($_GET['id_exercicio'] ?? 0);

if ($id_ex <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de exercício inválido']);
    exit;
}

// Buscar o peso máximo por dia para este exercício e utilizador
$sql = "SELECT DATE(htl.data_treino) as data_bruta, MAX(hel.peso_kg) as max_peso 
        FROM historico_exercicio_log hel
        JOIN historico_treino_log htl ON hel.id_log = htl.id_log
        WHERE htl.id_user = ? AND hel.id_exercicio = ?
        GROUP BY DATE(htl.data_treino)
        ORDER BY data_bruta ASC
        LIMIT 20";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ii", $user_id, $id_ex);
    $stmt->execute();
    $result = $stmt->get_result();
    $history = [];
    
    while ($row = $result->fetch_assoc()) {
        $history[] = [
            'data' => date('d/m', strtotime($row['data_bruta'])),
            'max_peso' => floatval($row['max_peso'])
        ];
    }
    
    echo json_encode(['success' => true, 'history' => $history]);
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Erro na database']);
}

$conn->close();
?>
