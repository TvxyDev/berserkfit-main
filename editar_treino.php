<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'ligacao.php';

$user_id = $_SESSION['user_id'];
$id_treino = $_GET['id'] ?? 0;

// Buscar treino
$stmt = $conn->prepare("SELECT * FROM treino WHERE id_treino = ? AND id_user = ?");
$stmt->bind_param("ii", $id_treino, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$treino = $result->fetch_assoc();
$stmt->close();

if (!$treino) {
    header("Location: treinos.php");
    exit;
}

// Buscar exercícios
$stmt = $conn->prepare("SELECT * FROM exercicio WHERE id_treino = ? ORDER BY id_exercicio");
$stmt->bind_param("i", $id_treino);
$stmt->execute();
$result = $stmt->get_result();
$exercicios = [];
while ($row = $result->fetch_assoc()) {
    $exercicios[] = $row;
}
$stmt->close();

// Processar atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_treino = $_POST['nome_treino'] ?? $treino['nome_treino'];
    $foco = $_POST['foco'] ?? $treino['foco'];

    $conn->begin_transaction();

    try {
        // Atualizar treino
        $stmt = $conn->prepare("UPDATE treino SET nome_treino = ?, foco = ? WHERE id_treino = ? AND id_user = ?");
        $stmt->bind_param("ssii", $nome_treino, $foco, $id_treino, $user_id);
        $stmt->execute();
        $stmt->close();

        // Deletar exercícios antigos
        $stmt = $conn->prepare("DELETE FROM exercicio WHERE id_treino = ?");
        $stmt->bind_param("i", $id_treino);
        $stmt->execute();
        $stmt->close();

        // Inserir novos exercícios (com video_url)
        if (isset($_POST['exercicios']) && is_array($_POST['exercicios'])) {
            $stmt_ex = $conn->prepare("INSERT INTO exercicio (id_treino, nome_exercicio, series, repeticoes, grupo_muscular, video_url) VALUES (?, ?, ?, ?, ?, ?)");

            foreach ($_POST['exercicios'] as $ex) {
                if (!empty($ex['nome'])) {
                    $nome      = $ex['nome'];
                    $series    = intval($ex['series'] ?? 3);
                    $repeticoes = intval($ex['repeticoes'] ?? 12);
                    $grupo     = $ex['grupo_muscular'] ?? '';
                    $video_url = $ex['video_url'] ?? '';

                    $stmt_ex->bind_param("isiiss", $id_treino, $nome, $series, $repeticoes, $grupo, $video_url);
                    $stmt_ex->execute();
                }
            }
            $stmt_ex->close();
        }

        $conn->commit();
        header("Location: treinos.php?editado=1");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $erro = "Erro ao atualizar treino.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Treino - BerserkFit</title>
    <link rel="stylesheet" href="css/global.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/treinos.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700;800&display=swap"
        rel="stylesheet">
</head>

<body>

    <header class="fade-in-element">
        <div class="header-top centered">
            <h1 class="app-title">BerserkFit AI</h1>
        </div>
        <div class="header-greeting">
            <h2>Editar Treino</h2>
            <p>Atualize os exercícios e informações do treino.</p>
        </div>
    </header>

    <main>
        <div style="max-width: 800px; margin: 0 auto 15px auto;">
            <a href="treinos.php" style="display: inline-flex; align-items: center; gap: 8px; color: var(--text-muted, #9ca3af); text-decoration: none; font-weight: 500; font-size: 0.95rem; transition: color 0.2s;">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <section class="treinos-container fade-in-element">
            <?php if (isset($erro)): ?>
                <div style="background: #fee; color: #c00; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="editar_treino.php?id=<?= $id_treino ?>"
                style="max-width: 800px; margin: 0 auto;">
                <div class="form-group">
                    <label for="nome_treino">Nome do Treino</label>
                    <input type="text" id="nome_treino" name="nome_treino" class="form-control"
                        value="<?= htmlspecialchars($treino['nome_treino']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="foco">Foco Principal</label>
                    <select id="foco" name="foco" class="form-control">
                        <option value="Hipertrofia" <?= $treino['foco'] === 'Hipertrofia' ? 'selected' : '' ?>>Hipertrofia
                        </option>
                        <option value="Força" <?= $treino['foco'] === 'Força' ? 'selected' : '' ?>>Força</option>
                        <option value="Resistência" <?= $treino['foco'] === 'Resistência' ? 'selected' : '' ?>>Resistência
                        </option>
                        <option value="Perda de Peso" <?= $treino['foco'] === 'Perda de Peso' ? 'selected' : '' ?>>Perda de
                            Peso</option>
                        <option value="Flexibilidade" <?= $treino['foco'] === 'Flexibilidade' ? 'selected' : '' ?>
                            >Flexibilidade</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Exercícios</label>
                    <div id="lista-exercicios" class="exercicios-list">
                        <?php foreach ($exercicios as $index => $ex): ?>
                            <div class="exercicio-item">
                                <div class="exercicio-header">
                                    <span class="exercicio-numero">Exercício #
                                        <?= $index + 1 ?>
                                    </span>
                                    <?php if ($index > 0): ?>
                                        <button type="button" class="btn-remove"
                                            onclick="this.parentElement.parentElement.remove()">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <input type="text" name="exercicios[<?= $index ?>][nome]" class="form-control"
                                        value="<?= htmlspecialchars($ex['nome_exercicio']) ?>" required>
                                </div>
                                <div class="form-group" style="margin-top: 5px;">
                                    <div style="display: flex; gap: 5px;">
                                        <input type="text" name="exercicios[<?= $index ?>][video_url]" class="form-control"
                                               placeholder="URL do Vídeo / GIF (Auto ou Manual)" id="video-input-<?= $index ?>"
                                               value="<?= htmlspecialchars($ex['video_url'] ?? '') ?>">
                                        <button type="button" class="btn-secondary" onclick="buscarVideo(<?= $index ?>)" style="padding: 0 15px;" title="Procurar na biblioteca">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="exercicio-row">
                                    <input type="text" name="exercicios[<?= $index ?>][grupo_muscular]" class="form-control"
                                        placeholder="Grupo Muscular"
                                        value="<?= htmlspecialchars($ex['grupo_muscular'] ?? '') ?>">
                                    <input type="number" name="exercicios[<?= $index ?>][series]" class="form-control"
                                        placeholder="Séries" value="<?= $ex['series'] ?>">
                                    <input type="number" name="exercicios[<?= $index ?>][repeticoes]" class="form-control"
                                        placeholder="Reps" value="<?= $ex['repeticoes'] ?>">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="btn-add-exercicio" class="btn-secondary"
                        style="margin-top: 1rem; width: 100%;">
                        <i class="fas fa-plus"></i> Adicionar Exercício
                    </button>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <a href="treinos.php" class="btn-secondary"
                        style="flex: 1; text-align: center; text-decoration: none; padding: 0.8rem;">
                        Cancelar
                    </a>
                    <button type="submit" class="btn-primary" style="flex: 1;">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </section>
    </main>

    <!-- Modal de Busca de Vídeo -->
    <div id="videoSearchModal" class="modal-berserk">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Procurar Demonstração</h3>
                <button type="button" onclick="closeVideoModal()" style="background:none; border:none; color:#fff; cursor:pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="videoResults" class="modal-body">
                <p style="text-align:center; opacity:0.7;">A carregar biblioteca...</p>
            </div>
        </div>
    </div>

    <style>
        .modal-berserk {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.85);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }
        .modal-berserk.active { display: flex; }
        .modal-content {
            background: #1e1b2e;
            width: 90%;
            max-width: 500px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .modal-header {
            padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05);
            display: flex; justify-content: space-between; align-items: center;
        }
        .modal-body { padding: 15px; max-height: 70vh; overflow-y: auto; }
        .result-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px; border-radius: 8px; cursor: pointer;
            transition: 0.2s; border: 1px solid transparent;
            margin-bottom: 10px;
        }
        .result-item:hover { background: rgba(255,255,255,0.05); border-color: var(--primary-color); }
        .result-item img { width: 60px; height: 60px; border-radius: 6px; object-fit: cover; }
        .result-info { flex: 1; }
        .result-info span { display: block; filter: brightness(0.8); font-size: 0.8rem; }
    </style>

    <nav class="navbar">
        <a href="dashboard.php" class="nav-link"><i class="fas fa-home icon"></i><span class="text">Início</span></a>
        <a href="treinos.php" class="nav-link active"><i class="fas fa-dumbbell icon"></i><span class="text">Treinos</span></a>
        <a href="progresso.php" class="nav-link"><i class="fas fa-chart-line icon"></i><span class="text">Progresso</span></a>
        <a href="chatbot.php" class="nav-link"><i class="fas fa-robot icon"></i><span class="text">Chatbot</span></a>
        <a href="perfil.php" class="nav-link"><i class="fas fa-user icon"></i><span class="text">Perfil</span></a>
    </nav>

    <script>
        let exercicioCount = <?= count($exercicios) ?>;
        let currentTargetIndex = 0;

        // ── Busca na biblioteca de exercícios ─────────────────────────────────
        async function buscarVideo(index) {
            currentTargetIndex = index;
            const nomeInput = document.querySelector(`input[name="exercicios[${index}][nome]"]`);
            const termo = nomeInput ? nomeInput.value.trim() : '';

            if (!termo) { alert('Escreve o nome do exercício primeiro!'); return; }

            document.getElementById('videoSearchModal').classList.add('active');
            const resultsBox = document.getElementById('videoResults');
            resultsBox.innerHTML = '<p style="text-align:center; opacity:0.6;">A pesquisar na biblioteca...</p>';

            try {
                const response = await fetch(`proxy_exercicios.php?q=${encodeURIComponent(termo)}`);
                const data = await response.json();

                if (data && data.length > 0) {
                    resultsBox.innerHTML = '';
                    data.forEach(ex => {
                        const div = document.createElement('div');
                        div.className = 'result-item';
                        div.onclick = () => selectVideo(ex.gifUrl);
                        div.innerHTML = `
                            <img src="${ex.gifUrl}" alt="${ex.name}" loading="lazy">
                            <div class="result-info">
                                <strong>${ex.name}</strong>
                                <span>Equipamento: ${ex.equipment}</span>
                                <span>Músculo: ${ex.target}</span>
                            </div>
                        `;
                        resultsBox.appendChild(div);
                    });
                } else {
                    resultsBox.innerHTML = '<p style="text-align:center;">Nenhum vídeo encontrado. Tenta outro nome.</p>';
                }
            } catch (err) {
                resultsBox.innerHTML = '<p style="text-align:center; color:#f87171;">Erro ao ligar à biblioteca.</p>';
            }
        }

        function selectVideo(url) {
            const input = document.getElementById(`video-input-${currentTargetIndex}`);
            if (input) input.value = url;
            closeVideoModal();
        }

        function closeVideoModal() {
            document.getElementById('videoSearchModal').classList.remove('active');
        }
        // ─────────────────────────────────────────────────────────────────────

        document.getElementById('btn-add-exercicio').addEventListener('click', () => {
            const container = document.getElementById('lista-exercicios');
            const div = document.createElement('div');
            div.className = 'exercicio-item';
            div.innerHTML = `
                <div class="exercicio-header">
                    <span class="exercicio-numero">Exercício #${exercicioCount + 1}</span>
                    <button type="button" class="btn-remove" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <div class="form-group">
                    <input type="text" name="exercicios[${exercicioCount}][nome]" class="form-control"
                           placeholder="Nome do Exercício" required>
                </div>
                <div class="form-group" style="margin-top: 5px;">
                    <div style="display: flex; gap: 5px;">
                        <input type="text" name="exercicios[${exercicioCount}][video_url]" class="form-control"
                               placeholder="URL do Vídeo / GIF" id="video-input-${exercicioCount}">
                        <button type="button" class="btn-secondary" onclick="buscarVideo(${exercicioCount})" style="padding: 0 15px;" title="Procurar na biblioteca">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="exercicio-row">
                    <input type="text" name="exercicios[${exercicioCount}][grupo_muscular]" class="form-control"
                           placeholder="Grupo Muscular (Opcional)">
                    <input type="number" name="exercicios[${exercicioCount}][series]" class="form-control"
                           placeholder="Séries" value="3">
                    <input type="number" name="exercicios[${exercicioCount}][repeticoes]" class="form-control"
                           placeholder="Reps" value="12">
                </div>
            `;
            container.appendChild(div);
            exercicioCount++;
        });
    </script>
</body>

</html>