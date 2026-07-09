<?php
session_start();
require 'ligacao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilizador não autenticado.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['treinos'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit;
}

$actionType = $input['actionType'] ?? 'append'; // 'replace' ou 'append'

// ── Busca GIF para exercício: usa cache + tradução PT→EN ──────────────────────
function fetchExerciseGif(string $nomeExercicio): string {
    $translations = [
        'supino'      => 'bench press',   'agachamento'  => 'squat',
        'puxada'      => 'lat pulldown',  'peso morto'   => 'deadlift',
        'flexões'     => 'push up',       'abdominais'   => 'crunch',
        'lunges'      => 'lunge',         'prancha'      => 'plank',
        'biceps'      => 'biceps curl',   'bíceps'       => 'biceps curl',
        'triceps'     => 'triceps extension', 'tríceps'  => 'triceps extension',
        'ombros'      => 'shoulder press','peito'        => 'chest',
        'costas'      => 'back',          'pernas'       => 'legs',
        'glúteos'     => 'glutes',        'remada'       => 'row',
        'rosca'       => 'curl',          'desenvolvimento' => 'overhead press',
        'leg press'   => 'leg press',     'cadeira'      => 'leg extension',
        'panturrilha' => 'calf raise',
    ];
    $searchTerm = strtolower($nomeExercicio);
    foreach ($translations as $pt => $en) {
        if (strpos($searchTerm, $pt) !== false) { $searchTerm = $en; break; }
    }

    $cacheDir  = __DIR__ . '/cache_exercicios';
    $cacheFile = $cacheDir . '/ex_' . md5($searchTerm) . '.json';
    $cacheTime = 86400 * 7;

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (!empty($cached[0]['gifUrl'])) return $cached[0]['gifUrl'];
    }

    // Ler a chave da mesma config do proxy
    $keyFile = __DIR__ . '/proxy_exercicios.php';
    $rapidApiKey = '';
    if (file_exists($keyFile)) {
        $src = file_get_contents($keyFile);
        if (preg_match('/\$rapidApiKey\s*=\s*"([^"]+)"/', $src, $m)) {
            $rapidApiKey = $m[1];
        }
    }
    if (empty($rapidApiKey) || $rapidApiKey === 'SUA_CHAVE_AQUI') return '';

    $ch = curl_init("https://exercisedb.p.rapidapi.com/exercises/name/" . urlencode($searchTerm) . "?limit=3&offset=0");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 6,
        CURLOPT_HTTPHEADER     => [
            "X-RapidAPI-Host: exercisedb.p.rapidapi.com",
            "X-RapidAPI-Key: $rapidApiKey",
        ],
    ]);
    $response = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200 && $response) {
        $data = json_decode($response, true);
        if (!empty($data[0]['gifUrl'])) {
            if (!is_dir($cacheDir)) mkdir($cacheDir, 0777, true);
            file_put_contents($cacheFile, $response);
            return $data[0]['gifUrl'];
        }
    }
    return '';
}
// ─────────────────────────────────────────────────────────────────────────────

$treinos = $input['treinos'];
$conn->begin_transaction();

try {
    // Se for replace, apagar todos os treinos anteriores do utilizador
    if ($actionType === 'replace') {
        // Primeiro apagar exercícios (devido à falta de CASCADE em algumas instâncias)
        $stmt_del_ex = $conn->prepare("DELETE FROM exercicio WHERE id_treino IN (SELECT id_treino FROM treino WHERE id_user = ?)");
        $stmt_del_ex->bind_param("i", $user_id);
        $stmt_del_ex->execute();
        $stmt_del_ex->close();

        // Depois apagar os treinos
        $stmt_del = $conn->prepare("DELETE FROM treino WHERE id_user = ?");
        $stmt_del->bind_param("i", $user_id);
        $stmt_del->execute();
        $stmt_del->close();
    }

    foreach ($treinos as $treino) {
        $nome_treino  = $treino['nome'] ?? 'Treino Personalizado';
        
        // Se for append, adicionar uma marcação para distinguir dos antigos
        if ($actionType === 'append') {
            $nome_treino = "(Oráculo) " . $nome_treino;
        }

        $foco         = $treino['foco'] ?? 'Geral';
        $data_criacao = date('Y-m-d');

        // Inserir Treino
        $stmt = $conn->prepare("INSERT INTO treino (id_user, nome_treino, foco, data_criacao, origem) VALUES (?, ?, ?, ?, 'Chatbot')");
        $stmt->bind_param("isss", $user_id, $nome_treino, $foco, $data_criacao);
        $stmt->execute();
        $id_treino = $conn->insert_id;
        $stmt->close();

        // Inserir Exercícios c/ GIF automático
        if (isset($treino['exercicios']) && is_array($treino['exercicios'])) {
            $stmt_ex = $conn->prepare(
                "INSERT INTO exercicio (id_treino, nome_exercicio, series, repeticoes, grupo_muscular, video_url) VALUES (?, ?, ?, ?, ?, ?)"
            );
            foreach ($treino['exercicios'] as $ex) {
                $nome_ex    = $ex['nome'];
                $series     = intval($ex['series']);
                $repeticoes = intval($ex['repeticoes']);
                $grupo      = $ex['grupo_muscular'] ?? null;
                $video_url  = fetchExerciseGif($nome_ex);   // ← busca automática!

                $stmt_ex->bind_param("isiiss", $id_treino, $nome_ex, $series, $repeticoes, $grupo, $video_url);
                $stmt_ex->execute();
            }
            $stmt_ex->close();
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Treinos guardados com sucesso!']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Erro ao guardar treinos: ' . $e->getMessage()]);
}

$conn->close();
?>