<?php
session_start();
require 'ligacao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

$user_id = $_SESSION['user_id'];
$id_habito = intval($_POST['id_habito'] ?? 0);
$concluido = intval($_POST['concluido'] ?? 0);
$data = date('Y-m-d');

if ($id_habito <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

// 1. Verificar se o hábito pertence ao utilizador
$check_habito = $conn->prepare("SELECT id_habito FROM habito WHERE id_habito = ? AND id_user = ?");
$check_habito->bind_param("ii", $id_habito, $user_id);
$check_habito->execute();
$res_habito = $check_habito->get_result();

if ($res_habito->num_rows == 0) {
    echo json_encode(['success' => false, 'error' => 'Hábito não encontrado ou não pertence ao utilizador']);
    $check_habito->close();
    $conn->close();
    exit;
}
$check_habito->close();

// 2. Verificar se já existe um checklist para hoje
$check_cl = $conn->prepare("SELECT id_checklist FROM checklist_diario WHERE id_habito = ? AND data = ?");
$check_cl->bind_param("is", $id_habito, $data);
$check_cl->execute();
$res_cl = $check_cl->get_result();

if ($res_cl->num_rows > 0) {
    // Atualizar existente
    $row = $res_cl->fetch_assoc();
    $stmt = $conn->prepare("UPDATE checklist_diario SET concluido = ? WHERE id_checklist = ?");
    $stmt->bind_param("ii", $concluido, $row['id_checklist']);
    $stmt->execute();
    $stmt->close();
} else {
    // Inserir novo se não existia ainda
    $stmt = $conn->prepare("INSERT INTO checklist_diario (id_habito, data, concluido) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $id_habito, $data, $concluido);
    $stmt->execute();
    $stmt->close();
}
$check_cl->close();

// -- NOVIDADE: Atualizar também o streak pois houve alteração nos checklists de hoje! --
// Primeiro buscar todos os hábitos para saber o total cadastrado
$stmt_hab = $conn->prepare("SELECT COUNT(*) as total FROM habito WHERE id_user = ?");
$stmt_hab->bind_param("i", $user_id);
$stmt_hab->execute();
$total_habitos = $stmt_hab->get_result()->fetch_assoc()['total'];
$stmt_hab->close();

// E contar os desafios marcados como concluídos hoje (com INNER JOIN no habito para garantir autoria)
$stmt_conclusoes = $conn->prepare("SELECT SUM(c.concluido) as total_concluidos 
                                   FROM checklist_diario c 
                                   INNER JOIN habito h ON c.id_habito = h.id_habito 
                                   WHERE h.id_user = ? AND c.data = ?");
$stmt_conclusoes->bind_param("is", $user_id, $data);
$stmt_conclusoes->execute();
$res_conc = $stmt_conclusoes->get_result()->fetch_assoc();
$desafios_concluidos_hoje = $res_conc['total_concluidos'] ?? 0;
$stmt_conclusoes->close();

// Atualizar a linha de day streak se o user registar desafios hoje
$streak_hoje_valido = ($desafios_concluidos_hoje >= 5 && $total_habitos >= 5) ? 1 : 0;
$stmt_s = $conn->prepare("INSERT INTO day_streak (id_user, data_streak, desafios_concluidos, streak_valido)
    VALUES (?, CURDATE(), ?, ?)
    ON DUPLICATE KEY UPDATE desafios_concluidos = VALUES(desafios_concluidos), streak_valido = VALUES(streak_valido)");
if ($stmt_s) {
    $stmt_s->bind_param("iii", $user_id, $desafios_concluidos_hoje, $streak_hoje_valido);
    $stmt_s->execute();
    $stmt_s->close();
}

$conn->close();

echo json_encode([
    'success' => true,
    'desafios_totais' => $total_habitos,
    'desafios_concluidos' => $desafios_concluidos_hoje,
    'streak_valido' => $streak_hoje_valido
]);
