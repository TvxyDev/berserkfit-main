<?php
header('Content-Type: application/json');
require_once 'ligacao.php';

// Support both JSON input and standard POST
$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? $_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Por favor, insere um e-mail válido.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'O e-mail inserido não é válido.']);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO newsletter (email) VALUES (?)");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Obrigado por subscreveres a nossa newsletter! ⚔️']);
        } else {
            // Check if duplicate entry (error code 1062)
            if ($conn->errno == 1062) {
                echo json_encode(['status' => 'success', 'message' => 'Este e-mail já se encontra subscrito! Obrigado pelo teu apoio.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Erro ao subscrever. Tenta novamente mais tarde.']);
            }
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro interno de base de dados.']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Ocorreu um erro: ' . $e->getMessage()]);
}
?>
