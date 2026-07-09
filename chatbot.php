<?php
session_start();
require "ligacao.php";

// Verifica se o utilizador está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_nome = $_SESSION['user_nome'] ?? 'Guerreiro';

// Obter sessões de histórico
$sessoes = [];
$stmt = $conn->prepare("SELECT id_sessao, titulo, DATE_FORMAT(data_atualizacao, '%d/%m %H:%i') as data_f FROM chatbot_sessoes WHERE id_user = ? ORDER BY data_atualizacao DESC");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res_sessoes = $stmt->get_result();
    while ($row = $res_sessoes->fetch_assoc()) {
        $sessoes[] = $row;
    }
    $stmt->close();
}

// Verificar se foi solicitado um histórico específico
$loaded_session_id = null;
$loaded_history = null;
$loaded_titulo = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT titulo, conteudo_json FROM chatbot_sessoes WHERE id_sessao = ? AND id_user = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $loaded_session_id = $id;
            $loaded_history = $row['conteudo_json'];
            $loaded_titulo = $row['titulo'];
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot - BerserkFit AI</title>
    <link rel="stylesheet" href="css/global.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/chatbot.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700;800&display=swap"
        rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"
        integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>

<body>
    <!-- Topo Padrão do Projeto -->
    <header class="fade-in-element">
        <?php
        $is_admin = false;
        try {
            $sql_adm = "SELECT COALESCE(tipo_usuario, 'Usuario') as tipo_usuario FROM user WHERE id_user = ?";
            $stmt_adm = $conn->prepare($sql_adm);
            $stmt_adm->bind_param("i", $_SESSION['user_id']);
            $stmt_adm->execute();
            if ($row_adm = $stmt_adm->get_result()->fetch_assoc()) {
                $tipo_usr = $row_adm['tipo_usuario'] ?? 'Usuario';
                $is_admin = ($tipo_usr === 'Admin' || $tipo_usr === 'SuperAdmin');
            }
        } catch (Exception $e) {}
        ?>
        <div class="header-top">
            <h1 class="app-title">BerserkFit AI</h1>
            <div class="header-actions">
                <?php if ($is_admin): ?>
                    <a href="admin.php" class="btn-admin">
                        <i class="fas fa-shield-alt"></i> Admin
                    </a>
                <?php endif; ?>
                <div class="streak-counter">
                    <i class="fa-solid fa-fire"></i>
                    <span><?= $global_streak ?? 0 ?></span>
                </div>
            </div>
        </div>
        <div class="header-greeting">
            <h2>Personal Trainer AI 💪</h2>
            <p>O teu treinador virtual está pronto para ajudar!</p>
        </div>
    </header>

    <main>
        <section id="chatbot-section" class="fade-in-element">
            <div class="chatbot-container">
                
                <!-- Coluna Esquerda: Histórico -->
                <aside class="chat-sidebar" id="sidebar">
                    <div class="sidebar-header">
                        <h2><i class="fas fa-history"></i> Histórico</h2>
                        <a href="chatbot.php" class="btn-nova-conversa"><i class="fas fa-plus"></i> Nova</a>
                    </div>
                    <div class="sidebar-content">
                        <?php if (empty($sessoes)): ?>
                            <div style="padding:15px; text-align:center; opacity:0.6; font-size:0.8rem; color:var(--cor-texto);">
                                Nenhuma conversa guardada.
                            </div>
                        <?php else: ?>
                            <?php foreach ($sessoes as $s): ?>
                                <a href="chatbot.php?id=<?= $s['id_sessao'] ?>" class="history-item <?= ($loaded_session_id == $s['id_sessao']) ? 'active' : '' ?>" style="text-decoration: none;">
                                    <i class="far fa-comment-alt"></i>
                                    <div style="display:flex; flex-direction:column;">
                                        <span><?= htmlspecialchars($s['titulo']) ?></span>
                                        <span style="font-size:0.7rem; opacity:0.6; margin-top:3px;"><?= $s['data_f'] ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </aside>

                <!-- Coluna Direita: Chat Ativo -->
                <div class="chat-active-area">
                    <!-- Header do Chat Interno -->
                    <div class="chat-header">
                        <button id="mobile-sidebar-toggle">
                            <i class="fas fa-bars"></i>
                        </button>
                        <div class="robot-avatar">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="chat-header-info">
                            <h2>Oráculo</h2>
                            <span>Personal Trainer AI 💪</span>
                        </div>
                    </div>

                    <!-- Área de Mensagens (Scroll) -->
                    <div class="chat-messages" id="chat-box">
                        <div class="message bot-message">
                            Olá, <?php echo htmlspecialchars($user_nome); ?>! 👋<br>
                            Sou o <strong>Oráculo</strong>, o teu Personal Trainer virtual criado com IA. 💪<br><br>
                            Estou aqui para desenhar o treino perfeito para ti ou tirar todas as tuas dúvidas. O que pretendes atacar hoje?
                        </div>

                        <div class="typing-indicator" id="typing-indicator">
                            <div class="dot"></div>
                            <div class="dot"></div>
                            <div class="dot"></div>
                        </div>
                    </div>

                    <!-- Footer: Input Area -->
                    <div class="chat-footer">
                        <div class="chat-input-wrapper">
                            <input type="text" id="user-input" placeholder="Escreve a tua mensagem aqui..." autocomplete="off">
                            <div class="chat-input-buttons">
                                <button id="send-btn" class="btn-enviar" title="Enviar Mensagem">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>
    </main>

    <!-- Modal Guardar Treino (Substituir / Adicionar / Cancelar) -->
    <div id="modalGuardarTreino" class="modal-berserk" role="dialog" aria-modal="true">
        <div class="modal-content modal-guardar-treino-content">
            <div class="modal-guardar-icon">
                <i class="fas fa-dumbbell"></i>
            </div>
            <h3 class="modal-guardar-titulo">Guardar Plano de Treino</h3>
            <p class="modal-guardar-subtitulo">Como queres guardar este plano gerado pelo Oráculo?</p>

            <div class="modal-guardar-opcoes">
                <button id="btn-substituir" class="btn-guardar-opcao btn-substituir">
                    <div class="opcao-icon"><i class="fas fa-sync-alt"></i></div>
                    <div class="opcao-texto">
                        <strong>Substituir todos os treinos</strong>
                        <span>Remove os treinos existentes e guarda este novo plano</span>
                    </div>
                </button>

                <button id="btn-adicionar" class="btn-guardar-opcao btn-adicionar">
                    <div class="opcao-icon"><i class="fas fa-plus-circle"></i></div>
                    <div class="opcao-texto">
                        <strong>Adicionar aos existentes</strong>
                        <span>Mantém os treinos atuais e adiciona este novo plano</span>
                    </div>
                </button>

                <button id="btn-cancelar-guardar" class="btn-guardar-opcao btn-cancelar-guardar">
                    <div class="opcao-icon"><i class="fas fa-times"></i></div>
                    <div class="opcao-texto">
                        <strong>Cancelar</strong>
                        <span>Não guardar nada</span>
                    </div>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Demonstração de Exercício -->
    <div id="demoExercicioModal" class="modal-berserk">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="demoModalTitle">Demonstração</h3>
                <button type="button" onclick="closeDemoModal()" style="background:none; border:none; color:#fff; cursor:pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="demoModalBody">
                <p style="text-align:center; opacity:0.6;">A carregar...</p>
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
            align-items: center; justify-content: center;
            backdrop-filter: blur(5px);
        }
        .modal-berserk.active { display: flex; }
        .modal-content {
            background: #1e1b2e; width: 90%; max-width: 450px;
            border-radius: 12px; overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
            animation: modalFadeUp 0.3s ease;
        }
        @keyframes modalFadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-header {
            padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05);
            display: flex; justify-content: space-between; align-items: center;
        }
        .modal-body { padding: 20px; }
        .demo-gif { width: 100%; border-radius: 8px; margin-bottom: 15px; }
        
        /* Botão Play Inline */
        .btn-demo-inline {
            background: none; border: none; color: var(--primary-color);
            cursor: pointer; padding: 2px 5px; font-size: 1.1rem;
            vertical-align: middle; transition: 0.2s;
            opacity: 0.8;
        }
        .btn-demo-inline:hover { opacity: 1; transform: scale(1.2); }

        /* ══ MODAL GUARDAR TREINO ══ */
        .modal-guardar-treino-content {
            background: #fff;
            width: 92%;
            max-width: 420px;
            border-radius: 20px;
            overflow: visible;
            border: none;
            padding: 32px 28px 28px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(28,12,59,0.25);
        }

        .modal-guardar-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #a78bfa, #7c5cbf);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            color: #fff;
            margin: 0 auto 18px;
            box-shadow: 0 8px 20px rgba(167,139,250,0.35);
        }

        .modal-guardar-titulo {
            font-family: var(--fonte-titulo, 'Syne', sans-serif);
            font-size: 1.25rem;
            font-weight: 800;
            color: #1c0c3b;
            margin: 0 0 6px;
        }

        .modal-guardar-subtitulo {
            font-size: 0.85rem;
            color: #6b7280;
            margin: 0 0 24px;
            line-height: 1.4;
        }

        .modal-guardar-opcoes {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-guardar-opcao {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            border-radius: 12px;
            border: 2px solid transparent;
            cursor: pointer;
            text-align: left;
            transition: all 0.2s ease;
            font-family: var(--fonte-texto, 'Inter', sans-serif);
        }

        .btn-guardar-opcao .opcao-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .btn-guardar-opcao .opcao-texto {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .btn-guardar-opcao .opcao-texto strong {
            font-size: 0.92rem;
            font-weight: 700;
        }

        .btn-guardar-opcao .opcao-texto span {
            font-size: 0.78rem;
            opacity: 0.7;
        }

        /* Substituir */
        .btn-substituir {
            background: #fff5f5;
            border-color: #fecaca;
            color: #991b1b;
        }
        .btn-substituir .opcao-icon {
            background: #fee2e2;
            color: #dc2626;
        }
        .btn-substituir:hover {
            background: #fee2e2;
            border-color: #dc2626;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220,38,38,0.15);
        }

        /* Adicionar */
        .btn-adicionar {
            background: #f0fdf4;
            border-color: #bbf7d0;
            color: #14532d;
        }
        .btn-adicionar .opcao-icon {
            background: #dcfce7;
            color: #16a34a;
        }
        .btn-adicionar:hover {
            background: #dcfce7;
            border-color: #16a34a;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(22,163,74,0.15);
        }

        /* Cancelar */
        .btn-cancelar-guardar {
            background: #f9fafb;
            border-color: #e5e7eb;
            color: #374151;
        }
        .btn-cancelar-guardar .opcao-icon {
            background: #f3f4f6;
            color: #6b7280;
        }
        .btn-cancelar-guardar:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
            transform: translateY(-1px);
        }
    </style>

    <!-- Navbar Padrão -->
    <?php include 'app_navbar.php'; ?>

    <script>
        const PHP_LOADED_SESSION_ID = <?= $loaded_session_id ? $loaded_session_id : 'null' ?>;
        const PHP_LOADED_HISTORY = <?= $loaded_history ? $loaded_history : 'null' ?>;
    </script>
    <script src="js/chatbot.js?v=<?= time() ?>"></script>
    <script>
        // Toggle mobile sidebar
        const sidebarToggleBtn = document.getElementById('mobile-sidebar-toggle');
        if (sidebarToggleBtn) {
            sidebarToggleBtn.addEventListener('click', function() {
                const sidebar = document.getElementById('sidebar');
                sidebar.classList.toggle('active');
            });
        }
    </script>
</body>

</html>