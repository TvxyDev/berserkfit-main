<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'ligacao.php';

$user_id = $_SESSION['user_id'];
$id_treino = $_GET['id'] ?? 0;

// Buscar treino e exercícios
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

// Processar finalização do treino
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $duracao_segundos = intval($_POST['duracao_segundos'] ?? 0);
    $sets_data_json = $_POST['sets_data'] ?? '[]';
    $sets_data = json_decode($sets_data_json, true);

    // 1. Criar registo no histórico de treino
    $sql_log = "INSERT INTO historico_treino_log (id_user, id_treino, duracao_segundos) VALUES (?, ?, ?)";
    $stmt_log = $conn->prepare($sql_log);
    $stmt_log->bind_param("iii", $user_id, $id_treino, $duracao_segundos);
    
    if ($stmt_log->execute()) {
        $id_log = $stmt_log->insert_id;
        $stmt_log->close();

        // 2. Criar registos por cada série enviada
        if (!empty($sets_data)) {
            $sql_ex_log = "INSERT INTO historico_exercicio_log (id_log, id_exercicio, num_serie, peso_kg, repeticoes) VALUES (?, ?, ?, ?, ?)";
            $stmt_ex_log = $conn->prepare($sql_ex_log);
            
            foreach ($sets_data as $ex_id => $series) {
                foreach ($series as $serie_idx => $data) {
                    $num_serie = $serie_idx + 1;
                    $peso = floatval($data['peso'] ?? 0);
                    $reps = intval($data['reps'] ?? 0);
                    
                    if ($peso > 0 || $reps > 0) {
                        $stmt_ex_log->bind_param("iiidi", $id_log, $ex_id, $num_serie, $peso, $reps);
                        $stmt_ex_log->execute();
                    }
                }
            }
            $stmt_ex_log->close();
        }
    }

    $conn->close();
    header("Location: treinos.php?concluido=1");
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executar Treino - BerserkFit</title>
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/treinos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/executar_treino.css?v=<?= time() ?>">
</head>

<body>

    <header class="fade-in-element">
        <div class="header-top centered">
            <h1 class="app-title">BerserkFit AI</h1>
        </div>
        <div class="header-greeting">
            <h2><?= htmlspecialchars($treino['nome_treino']) ?></h2>
            <p>Foco Principal: <span><?= htmlspecialchars($treino['foco']) ?></span></p>
        </div>
    </header>

    <main>
        <div style="max-width: 800px; margin: 0 auto 15px auto;">
            <a href="treinos.php" style="display: inline-flex; align-items: center; gap: 8px; color: var(--text-muted, #9ca3af); text-decoration: none; font-weight: 500; font-size: 0.95rem; transition: color 0.2s;">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
        
        <section class="treinos-container fade-in-element">

            <!-- Cronômetro -->
            <div class="timer-container">
                <h3>Tempo de Treino</h3>
                <div class="timer-display" id="timer">00:00:00</div>
                <div class="timer-controls">
                    <button class="btn-timer btn-start" id="btn-start">
                        <i class="fas fa-play"></i> Iniciar
                    </button>
                    <button class="btn-timer btn-pause" id="btn-pause" style="display: none;">
                        <i class="fas fa-pause"></i> Pausar
                    </button>
                    <button class="btn-timer btn-stop" id="btn-stop" style="display: none;">
                        <i class="fas fa-stop"></i> Finalizar
                    </button>
                </div>
            </div>

            <!-- Progresso -->
            <div class="progresso-treino">
                <h3>Progresso</h3>
                <p id="progresso-texto">0 de
                    <?= count($exercicios) ?> exercícios concluídos
                </p>
                <div class="progresso-bar-container">
                    <div class="progresso-bar-fill" id="progresso-bar" style="width: 0%;">0%</div>
                </div>
            </div>

            <!-- Lista de Exercícios -->
            <h3>Exercícios</h3>
            <div class="exercicio-lista">
                <?php foreach ($exercicios as $index => $ex): ?>
                    <div class="exercicio-card" data-index="<?= $index ?>">
                        <!-- Coluna do GIF / Preview -->
                        <div class="exercicio-gif-col" data-name="<?= htmlspecialchars($ex['nome_exercicio']) ?>" data-url="<?= htmlspecialchars($ex['video_url'] ?? '') ?>">
                            <div class="exercicio-gif-placeholder">
                                <i class="fas fa-circle-notch fa-spin" style="font-size: 1.2rem;"></i>
                            </div>
                        </div>

                        <!-- Coluna de Informações -->
                        <div class="exercicio-info">
                            <div class="exercicio-header-row">
                                <div class="exercicio-nome">
                                    <?= htmlspecialchars($ex['nome_exercicio']) ?>
                                </div>
                                <input type="checkbox" class="exercicio-checkbox" id="ex-<?= $ex['id_exercicio'] ?>" title="Marcar como concluído">
                            </div>
                            
                            <div class="exercicio-detalhes">
                                <?= $ex['series'] ?> séries × <?= $ex['repeticoes'] ?> repetições
                                <?php if (!empty($ex['grupo_muscular'])): ?>
                                    • <?= htmlspecialchars($ex['grupo_muscular']) ?>
                                <?php endif; ?>
                            </div>

                            <div class="exercicio-btns-row">
                                <!-- Botão para abrir o registo de séries -->
                                <button type="button" class="btn-log-sets" id="btn-sets-<?= $ex['id_exercicio'] ?>" 
                                        onclick="openSetsModal(<?= $ex['id_exercicio'] ?>, '<?= addslashes($ex['nome_exercicio']) ?>', <?= !empty($ex['series']) ? $ex['series'] : 3 ?>)">
                                    <i class="fas fa-edit"></i> Registar Séries
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Formulário oculto para enviar dados -->
            <form method="POST" id="form-finalizar" style="display: none;">
                <input type="hidden" name="duracao_segundos" id="duracao_segundos">
                <input type="hidden" name="sets_data" id="sets_data_field">
            </form>

        </section>
    </main>

    <!-- Modal de Registo de Séries -->
    <div id="modalSets" class="modal-berserk">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Registar Séries: <span id="modalExName" style="color: var(--cor-intermedia);"></span></h3>
                <button type="button" onclick="closeSetsModal()" style="background:none; border:none; color:#fff; cursor:pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="modal-sets-list" class="sets-modal-list">
                    <!-- Gerado via JS -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-save-modal" onclick="saveSetsModal()">
                    <i class="fas fa-check"></i> Guardar Dados
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Finalização -->
    <div id="confirmFinalizarModal" class="modal-berserk">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h3>Finalizar Treino 🏆</h3>
                <button type="button" onclick="closeConfirmModal()" style="background:none; border:none; color:#fff; cursor:pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" style="text-align: center; padding: 30px 20px;">
                <div style="font-size: 3rem; margin-bottom: 15px;">💪</div>
                <p style="margin: 0 0 20px 0; font-size: 1.1rem; line-height: 1.5; color: rgba(255,255,255,0.9);">
                    Guerreiro, desejas finalizar a tua sessão de treino e guardar todo o teu progresso na base de dados?
                </p>
            </div>
            <div class="modal-footer" style="gap: 15px; display: flex; justify-content: center; padding: 15px 20px 25px 20px;">
                <button type="button" class="btn-cancelar-modal" onclick="closeConfirmModal()" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 12px 24px; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                    Cancelar
                </button>
                <button type="button" class="btn-save-modal" onclick="submitFinalizarTreino()" style="background: linear-gradient(135deg, var(--cor-intermedia), var(--cor-destaque)); border: none; color: #fff; padding: 12px 24px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 15px rgba(139,92,246,0.25);">
                    <i class="fas fa-check-circle"></i> Sim, Finalizar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Vídeo -->
    <div id="videoModal" class="modal-berserk">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="videoModalTitle">Demonstração</h3>
                <button type="button" onclick="closeExerciseVideo()" style="background:none; border:none; color:#fff; cursor:pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" style="text-align:center;">
                <img id="exerciseGif" src="" style="width: 100%; max-width: 400px; border-radius: 8px; display:none;">
                <video id="exerciseVideo" controls style="width: 100%; max-width: 400px; border-radius: 8px; display:none;"></video>
                <div id="youtubeContainer" style="display:none; position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden;">
                   <iframe id="youtubeIframe" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border:0;" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Botão de demonstração */
        .btn-demo-ex {
            background: rgba(196, 181, 253, 0.1) !important;
            border: 1px solid rgba(196, 181, 253, 0.2) !important;
            color: var(--cor-intermedia) !important;
            font-size: 1.1rem;
            transition: all 0.2s;
        }
        .btn-cancelar-modal:hover {
            background: rgba(255, 255, 255, 0.15) !important;
            border-color: rgba(255, 255, 255, 0.3) !important;
        }
    </style>

    <script>
        let segundos = 0;
        let intervalo = null;
        let rodando = false;
        const totalExercicios = <?= count($exercicios) ?>;
        let concluidos = 0;

        const timerDisplay = document.getElementById('timer');
        const btnStart = document.getElementById('btn-start');
        const btnPause = document.getElementById('btn-pause');
        const btnStop = document.getElementById('btn-stop');
        const progressoTexto = document.getElementById('progresso-texto');
        const progressoBar = document.getElementById('progresso-bar');

        function formatarTempo(seg) {
            const h = Math.floor(seg / 3600).toString().padStart(2, '0');
            const m = Math.floor((seg % 3600) / 60).toString().padStart(2, '0');
            const s = (seg % 60).toString().padStart(2, '0');
            return `${h}:${m}:${s}`;
        }

        function atualizarTimer() {
            segundos++;
            timerDisplay.textContent = formatarTempo(segundos);
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Carregar GIFs na coluna da esquerda
            document.querySelectorAll('.exercicio-gif-col').forEach(async col => {
                let url = col.dataset.url;
                const name = col.dataset.name;

                if (!url) {
                    try {
                        const response = await fetch(`proxy_exercicios.php?q=${encodeURIComponent(name)}`);
                        const data = await response.json();
                        if (data && data.length > 0) {
                            url = data[0].gifUrl || '';
                        }
                    } catch(e) {
                        console.error('Erro ao buscar demo para ' + name);
                    }
                }

                if (!url) {
                    // Fallback Youtube Search se tudo falhar
                    url = 'https://www.youtube.com/results?search_query=' + encodeURIComponent(name + ' exercise tutorial');
                }

                renderGifColumn(col, url, name);
            });
        });

        function formatGithubImg(name) {
            // Converter "barbell bench press" -> "Barbell_Bench_Press"
            if (!name) return '';
            return name.split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join('_');
        }

        function renderGifColumn(col, url, name) {
            col.innerHTML = ''; // limpar loader
            
            // Se o URL for uma pesquisa YouTube (porque a API falhou em dar o GIF), 
            // ou se estiver vazio, tentamos buscar a imagem correspondente no repositório estático.
            if (!url || url.includes('youtube.com/results') || url.includes('youtu.be/results')) {
                const gitName = formatGithubImg(name);
                url = `https://raw.githubusercontent.com/yuhonas/free-exercise-db/main/exercises/${gitName}/images/0.jpg`;
            }

            if (url.includes('youtube.com') || url.includes('youtu.be')) {
                // Link YouTube direto (caso o user tenha inserido um vídeo específico à mão)
                col.innerHTML = `
                    <a href="${url}" target="_blank" rel="noopener" class="exercicio-gif-yt" title="Ver tutorial no YouTube">
                        <i class="fab fa-youtube"></i>
                        <span>Tutorial</span>
                    </a>
                `;
            } else if (url.match(/\.(gif|jpg|jpeg|png|webp)/i)) {
                // Imagem/GIF inline com fallback para o placeholder se a imagem não existir no Github
                col.innerHTML = `
                    <img src="${url}" alt="${name}" class="exercicio-gif-inline" loading="lazy" 
                         onerror="this.outerHTML='<div class=\\'exercicio-gif-placeholder\\'><i class=\\'fas fa-dumbbell\\'></i></div>'"
                         onclick="openExerciseVideo('${url}', '${name.replace(/'/g, "\\'")}')" style="cursor:pointer;" title="Clique para ampliar">
                `;
            } else {
                col.innerHTML = `<div class="exercicio-gif-placeholder"><i class="fas fa-dumbbell"></i></div>`;
            }
        }

        btnStart.addEventListener('click', () => {
            if (!rodando) {
                intervalo = setInterval(atualizarTimer, 1000);
                rodando = true;
                btnStart.style.display = 'none';
                btnPause.style.display = 'inline-block';
                btnStop.style.display = 'inline-block';
            }
        });

        btnPause.addEventListener('click', () => {
            clearInterval(intervalo);
            rodando = false;
            btnStart.style.display = 'inline-block';
            btnPause.style.display = 'none';
        });

        btnStop.addEventListener('click', () => {
            openConfirmModal();
        });

        function openConfirmModal() {
            document.getElementById('confirmFinalizarModal').classList.add('active');
        }

        function closeConfirmModal() {
            document.getElementById('confirmFinalizarModal').classList.remove('active');
        }

        function submitFinalizarTreino() {
            clearInterval(intervalo);
            document.getElementById('duracao_segundos').value = segundos;
            
            // Os dados já estão guardados no objeto workoutData conforme o utilizador foi preenchendo
            document.getElementById('sets_data_field').value = JSON.stringify(workoutData);
            document.getElementById('form-finalizar').submit();
        }

        // --- Lógica do Novo Modal de Séries ---
        let workoutData = {}; // Objeto para guardar dados de todos os exercícios: { exId: [{peso, reps}, ...] }
        let currentEditingExId = null;

        function openSetsModal(exId, exNome, numSeries) {
            // Garantir que nenhum outro modal está a interferir (especialmente o de vídeo)
            document.getElementById('videoModal').classList.remove('active');
            
            currentEditingExId = exId;
            document.getElementById('modalExName').textContent = exNome;
            const list = document.getElementById('modal-sets-list');
            list.innerHTML = '';
            
            const existingData = workoutData[exId] || [];
            const iterCount = Math.max(numSeries, existingData.length);

            for (let i = 0; i < iterCount; i++) {
                const data = existingData[i] || { peso: '', reps: '' };
                const row = document.createElement('div');
                row.className = 'set-row-modal';
                row.innerHTML = `
                    <div class="set-num">Série ${i + 1}</div>
                    <div class="set-field">
                        <label>Peso (kg)</label>
                        <input type="number" step="0.5" class="input-modal-peso" value="${data.peso}" placeholder="0">
                    </div>
                    <div class="set-field">
                        <label>Reps</label>
                        <input type="number" class="input-modal-reps" value="${data.reps}" placeholder="0">
                    </div>
                `;
                list.appendChild(row);
            }
            
            document.getElementById('modalSets').classList.add('active');
        }

        function closeSetsModal() {
            document.getElementById('modalSets').classList.remove('active');
            currentEditingExId = null;
        }

        function saveSetsModal() {
            if (!currentEditingExId) return;
            
            const list = document.getElementById('modal-sets-list');
            const rows = list.querySelectorAll('.set-row-modal');
            const sets = [];
            
            rows.forEach(row => {
                const peso = row.querySelector('.input-modal-peso').value;
                const reps = row.querySelector('.input-modal-reps').value;
                
                // Apenas guardar se houver algum dado inserido
                if (peso !== '' || reps !== '') {
                    sets.push({ peso, reps });
                }
            });
            
            workoutData[currentEditingExId] = sets;
            
            // Feedback visual no botão do exercício
            const btn = document.getElementById(`btn-sets-${currentEditingExId}`);
            if (sets.length > 0) {
                btn.innerHTML = `<i class="fas fa-check-circle"></i> ${sets.length} Séries Registadas`;
                btn.style.background = 'rgba(16, 185, 129, 0.1)';
                btn.style.color = '#10b981';
                btn.style.borderColor = 'rgba(16, 185, 129, 0.3)';
            } else {
                btn.innerHTML = '<i class="fas fa-edit"></i> Registar Séries';
                btn.style.background = '';
                btn.style.color = '';
                btn.style.borderColor = '';
            }
            
            closeSetsModal();
        }

        // Marcar exercícios como concluídos
        document.querySelectorAll('.exercicio-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                const card = this.closest('.exercicio-card');

                if (this.checked) {
                    card.classList.add('concluido');
                    concluidos++;
                } else {
                    card.classList.remove('concluido');
                    concluidos--;
                }

                const percentual = Math.round((concluidos / totalExercicios) * 100);
                progressoTexto.textContent = `${concluidos} de ${totalExercicios} exercícios concluídos`;
                progressoBar.style.width = percentual + '%';
                progressoBar.textContent = percentual + '%';
            });
        });

        function openExerciseVideo(url, nome) {
            const modal     = document.getElementById('videoModal');
            const title     = document.getElementById('videoModalTitle');
            const gifImg    = document.getElementById('exerciseGif');
            const videoElem = document.getElementById('exerciseVideo');
            const ytCont    = document.getElementById('youtubeContainer');
            const ytIframe  = document.getElementById('youtubeIframe');

            title.textContent = nome;
            modal.classList.add('active');

            // Reset all
            gifImg.style.display    = 'none';
            videoElem.style.display = 'none';
            ytCont.style.display    = 'none';
            ytIframe.src            = '';

            // Remover card anterior se existir
            const oldCard = document.getElementById('yt-search-card');
            if (oldCard) oldCard.remove();

            if (!url || url === '') {
                // Sem URL – gerar pesquisa YouTube
                url = 'https://www.youtube.com/results?search_query=' + encodeURIComponent(nome + ' exercise tutorial');
            }

            if (url.includes('youtube.com/results') || url.includes('youtu.be/results')) {
                // URL de pesquisa YouTube – mostrar botão para abrir no browser
                const card = document.createElement('div');
                card.id = 'yt-search-card';
                card.style.cssText = 'text-align:center; padding: 20px;';
                card.innerHTML = `
                    <div style="font-size:3rem; margin-bottom:15px;">▶️</div>
                    <p style="margin-bottom:15px; opacity:0.8;">Clica para ver tutoriais de <strong>${nome}</strong> no YouTube</p>
                    <a href="${url}" target="_blank" rel="noopener"
                       style="display:inline-block; padding:12px 25px; background: linear-gradient(135deg,#ff0000,#cc0000);
                              color:#fff; border-radius:8px; text-decoration:none; font-weight:600; font-size:1rem;">
                        <i class="fas fa-play"></i> Abrir no YouTube
                    </a>
                    <p style="margin-top:12px; font-size:0.75rem; opacity:0.5;">Abre numa nova aba do browser</p>
                `;
                document.querySelector('#videoModal .modal-body').appendChild(card);

            } else if (url.includes('youtube.com/watch') || url.includes('youtu.be/')) {
                // URL directa YouTube – incorporar iframe
                let videoId = '';
                if (url.includes('v=')) videoId = url.split('v=')[1].split('&')[0];
                else videoId = url.split('/').pop().split('?')[0];
                ytIframe.src = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
                ytCont.style.display = 'block';

            } else if (url.match(/\.(gif|jpg|jpeg|png|webp)/i)) {
                gifImg.src = url;
                gifImg.style.display = 'inline-block';

            } else {
                videoElem.src = url;
                videoElem.style.display = 'inline-block';
            }
        }

        function closeExerciseVideo() {
            const modal = document.getElementById('videoModal');
            const ytIframe = document.getElementById('youtubeIframe');
            ytIframe.src = '';
            modal.classList.remove('active');
        }

        // Busca ao vivo na biblioteca quando não há URL guardado
        async function fetchAndOpenDemo(nomePT) {
            const modal    = document.getElementById('videoModal');
            const title    = document.getElementById('videoModalTitle');
            const gifImg   = document.getElementById('exerciseGif');
            const videoElem = document.getElementById('exerciseVideo');
            const ytCont   = document.getElementById('youtubeContainer');

            title.textContent = nomePT;
            modal.classList.add('active');
            gifImg.style.display   = 'none';
            videoElem.style.display = 'none';
            ytCont.style.display   = 'none';
            gifImg.src = '';
            gifImg.alt = 'A carregar demonstração...';
            // Mostrar loading
            gifImg.style.display   = 'inline-block';
            gifImg.src = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3C/svg%3E';

            try {
                const response = await fetch(`proxy_exercicios.php?q=${encodeURIComponent(nomePT)}`);
                const data = await response.json();

                if (data && data.length > 0) {
                    openExerciseVideo(data[0].gifUrl, nomePT);
                } else {
                    gifImg.style.display = 'none';
                    videoElem.style.display = 'none';
                    ytCont.style.display = 'none';
                    // Mostrar mensagem no modal
                    videoElem.style.display = 'block';
                    videoElem.outerHTML = '<p style="color:rgba(255,255,255,0.6); text-align:center;">Demonstração não encontrada para este exercício.</p>';
                }
            } catch(e) {
                console.error('Erro ao buscar demo:', e);
            }
        }
    </script>
</body>

</html>
