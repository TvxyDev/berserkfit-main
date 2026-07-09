<?php
session_start();
require "ligacao.php";

setlocale(LC_TIME, 'pt_PT.UTF-8', 'pt_PT', 'portuguese');


// Verifica se o utilizador está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['user_id'];
$mensagem = "";

// Busca os dados do utilizador
$sql = "SELECT * FROM user WHERE id_user = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>Erro: Utilizador não encontrado.</p>";
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// --- CALCULAR ESTATÍSTICAS ---
// 1. Total Concluído (Checklists)
$sql_xp = "SELECT COUNT(*) as total FROM checklist_diario c 
           JOIN habito h ON c.id_habito = h.id_habito 
           WHERE h.id_user = ? AND c.concluido = 1";
$stmt = $conn->prepare($sql_xp);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$res_xp = $stmt->get_result();
$total_concluido = $res_xp->fetch_assoc()['total'];
$stmt->close();

// 2. Metas
$sql_metas = "SELECT COUNT(*) as total FROM meta_usuario WHERE id_user = ?";
$stmt = $conn->prepare($sql_metas);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$res_metas = $stmt->get_result();
$total_metas = $res_metas->fetch_assoc()['total'];
$stmt->close();

// 3. Treinos Concluídos (Real)
$total_treinos = 0;
// Conta apenas treinos que foram executados e concluídos de verdade
$sql_treinos = "SELECT COUNT(*) as total FROM historico_treino_log WHERE id_user = ?";
$stmt = $conn->prepare($sql_treinos);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$res_treinos = $stmt->get_result();
$total_treinos = $res_treinos->fetch_assoc()['total'];
$stmt->close();

// 4. Configuração de Estatísticas Reais da BD
$plano = ucfirst($user['tipo_plano']);
$streak = $global_streak ?? 0;

$seguidores = 0;
$seguindo = 0;
$biografia = "Guerreiro em ascensão. Focado na disciplina e na conquista diária de objetivos.";
$current_league = "Liga " . ($user['league'] ?? 'Renegado');

// --- ATUALIZAÇÃO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['nova_foto_perfil']) && !empty($_FILES['nova_foto_perfil']['name'])) {
    
    // Configurações
    $diretorio = "assets/fotos/";
    if (!file_exists($diretorio)) {
        mkdir($diretorio, 0777, true);
    }

    $fotoNome = time() . "_" . basename($_FILES['nova_foto_perfil']['name']);
    $fotoTmp = $_FILES['nova_foto_perfil']['tmp_name'];
    $caminhoFoto = $diretorio . $fotoNome;

    // Tenta mover o arquivo
    if (move_uploaded_file($fotoTmp, $caminhoFoto)) {
        
        // Remove a antiga se não for default
        $fotoAntiga = $user['foto'];
        if (!empty($fotoAntiga) && file_exists($fotoAntiga) && strpos($fotoAntiga, 'default') === false) {
             unlink($fotoAntiga);
        }

        // Atualiza no banco
        $update = "UPDATE user SET foto=? WHERE id_user=?";
        $stmt = $conn->prepare($update);
        $stmt->bind_param("si", $caminhoFoto, $id_user);
        
        if ($stmt->execute()) {
            header("Location: perfil.php?msg=foto_ok");
            exit;
        } else {
            $mensagem = "❌ Erro ao atualizar no banco.";
        }
    } else {
        $uploadError = $_FILES['nova_foto_perfil']['error'];
        $mensagem = "❌ Erro ao enviar foto. Código: " . $uploadError;
    }
} else if (isset($_GET['msg']) && $_GET['msg'] == 'foto_ok') {
    $mensagem = "✅ Foto de perfil atualizada com sucesso!";
}


// Tratamento de display
$handle = "@" . strtolower(explode(' ', trim($user['username']))[0]);
$data_entrada = !empty($user['data_registo'])
    ? strftime('%B %Y', strtotime($user['data_registo']))
    : 'erro';

?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - BerserkFit</title>

    <link rel="stylesheet" href="css/global.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/perfil.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/perfil_extra.css?v=<?= time() ?>">
    <style>
        /* ═══ FORÇAR POSICIONAMENTO DO MODAL NO CENTRO DA TELA ═══ */
        .modal-berserk {
            display: none !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background: rgba(8, 4, 20, 0.85) !important;
            z-index: 100000 !important;
            align-items: center !important;
            justify-content: center !important;
            backdrop-filter: blur(15px) !important;
            -webkit-backdrop-filter: blur(15px) !important;
            pointer-events: auto !important;
        }

        .modal-berserk.active {
            display: flex !important;
        }

        .modal-berserk .modal-content {
            background: #1c0c3b !important;
            width: 95% !important;
            max-width: 450px !important;
            margin: auto !important;
            border-radius: 28px !important;
            overflow: hidden !important;
            border: 1px solid rgba(196, 181, 253, 0.2) !important;
            box-shadow: 0 40px 100px rgba(0,0,0,0.8) !important;
            display: flex !important;
            flex-direction: column !important;
            max-height: 85vh !important;
            box-sizing: border-box !important;
        }

        .modal-berserk .modal-header {
            padding: 20px 24px !important;
            border-bottom: 1px solid rgba(196, 181, 253, 0.08) !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            background: rgba(255, 255, 255, 0.02) !important;
            flex-shrink: 0 !important;
        }

        .modal-berserk .modal-header h3 {
            font-family: 'Syne', sans-serif !important;
            font-size: 1.15rem !important;
            font-weight: 800 !important;
            color: #fff !important;
            margin: 0 !important;
        }

        .modal-berserk .modal-body {
            padding: 20px 24px !important;
            color: rgba(255, 255, 255, 0.9) !important;
            display: block !important;
            width: 100% !important;
            box-sizing: border-box !important;
            flex: 1 !important;
            overflow-y: auto !important;
        }

        .modal-berserk .modal-footer {
            padding: 16px 24px 24px 24px !important;
            display: flex !important;
            justify-content: center !important;
            border-top: 1px solid rgba(196, 181, 253, 0.05) !important;
            flex-shrink: 0 !important;
        }
    </style>
</head>

<body>

    <header class="fade-in-element">
        <?php 
        $tipo_usr = $user['tipo_usuario'] ?? 'Usuario';
        $is_admin = ($tipo_usr === 'Admin' || $tipo_usr === 'SuperAdmin'); 
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
                    <span><?= $streak ?? 0 ?></span>
                </div>
            </div>
        </div>
        <div class="header-greeting">
            <h2>O Meu Perfil</h2>
            <p>O teu centro de guerreiro.</p>
        </div>
    </header>

    <main class="main-content" style="padding-top: 10px; max-width: 800px; margin: 0 auto;">

        <?php if ($mensagem != ""): ?>
            <div class="mensagem-float <?php echo strpos($mensagem, '✅') !== false ? 'success' : 'error'; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <div class="profile-container-grid">
            <!-- Coluna Esquerda: Cartão de Perfil -->
            <div class="profile-card">
                <!-- Botão Configurações acima da foto -->
                <div class="settings-icon-container">
                    <a href="configuracoes.php" class="btn-settings" title="Configurações">
                        <i class="fas fa-cog"></i>
                    </a>
                </div>

                <div class="profile-header-img">
                    <?php
                    $fotoDisplay = (!empty($user['foto']) && file_exists($user['foto'])) ? $user['foto'] : 'assets/fotos/default-user.png';
                    ?>
                    <img src="<?php echo htmlspecialchars($fotoDisplay); ?>" alt="Foto de Perfil" id="imgPerfilDisplay">

                    <!-- Formulário oculto para upload direto -->
                    <form action="" method="POST" enctype="multipart/form-data" id="formFotoPerfil">
                        <input type="file" name="nova_foto_perfil" id="inputFotoPerfil" style="display: none;"
                            accept="image/*" onchange="document.getElementById('formFotoPerfil').submit()">
                    </form>

                    <div class="edit-photo-overlay" onclick="document.getElementById('inputFotoPerfil').click()"
                        title="Alterar Foto">
                        <i class="fas fa-camera"></i>
                    </div>
                </div>
                <h2><?php echo htmlspecialchars($user['nome']); ?></h2>
                <p class="handle"><?php echo $handle; ?></p>

                <div class="social-counts" style="margin-bottom: 25px; font-size: 0.95rem; display: flex; justify-content: center; gap: 20px;">
                    <span style="cursor: pointer;"><strong><?php echo $seguindo; ?></strong> Seguindo</span>
                    <span style="cursor: pointer;"><strong><?php echo $seguidores; ?></strong> Seguidores</span>
                </div>

                <p class="joined"><i class="far fa-calendar-alt"></i> Membro desde <?php echo $data_entrada; ?></p>
            </div>

            <!-- Coluna Direita: Informações Públicas (Padrão) -->
            <div class="settings-panel" id="public-panel">
                <h3><i class="fas fa-id-card"></i> Visão Geral</h3>

                <div class="bio-section">
                    <h4 style="margin-bottom: 10px; color: var(--cor-texto);">Sobre Mim</h4>
                    <p class="bio-text">"<?php echo $biografia; ?>"</p>
                </div>

                <h4 style="margin-bottom: 15px; color: var(--cor-texto);">Estatísticas de Guerreiro</h4>
                <div class="public-stats-grid">
                    <!-- Treinos Concluídos -->
                    <div class="stat-box">
                        <i class="fas fa-dumbbell"></i>
                        <div>
                            <span class="stat-val"><?php echo $total_treinos; ?></span>
                            <span class="stat-title">Treinos Feitos</span>
                        </div>
                    </div>

                    <!-- Day Streak -->
                    <div class="stat-box">
                        <i class="fas fa-fire" style="color: #ff9600;"></i>
                        <div>
                            <span class="stat-val"><?php echo $streak; ?></span>
                            <span class="stat-title">Dias Seguidos</span>
                        </div>
                    </div>

                    <!-- League Rank -->
                    <div class="stat-box">
                        <i class="fas fa-trophy" style="color: #ffc800;"></i>
                        <div>
                            <span class="stat-val"><?php echo $current_league; ?></span>
                            <span class="stat-title">Liga Atual</span>
                            <a href="javascript:void(0)" onclick="abrirModalLiga()" style="color: var(--cor-intermedia); font-size: 0.8rem; text-decoration: underline; display: block; margin-top: 4px; font-weight: 600;">Ver mais</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        </div>
        </div>

    </main>

    <!-- Modal Detalhes da Liga -->
    <div id="modalLiga" class="modal-berserk" style="display: none;">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h3>Ligas de Guerreiro 🏆</h3>
                <button type="button" onclick="fecharModalLiga()" style="background:none; border:none; color:#fff; cursor:pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" style="padding: 20px 25px;">
                <p style="margin: 0 0 20px 0; font-size: 0.95rem; line-height: 1.5; color: rgba(255,255,255,0.7); text-align: center;">
                    Mantém o teu Day Streak ativo completando 5+ desafios diários para subir na hierarquia dos guerreiros.
                </p>
                <div class="ligas-requisitos-lista" style="display: flex; flex-direction: column; gap: 10px;">
                    <?php
                    $ligas_req = [
                        'Renegado' => ['dias' => '0-2 dias', 'icone' => '🛡️', 'min' => 0],
                        'Viking' => ['dias' => '3-6 dias', 'icone' => '🪓', 'min' => 3],
                        'Huscarl' => ['dias' => '7-14 dias', 'icone' => '⚔️', 'min' => 7],
                        'Jarl' => ['dias' => '15-29 dias', 'icone' => '👑', 'min' => 15],
                        'Berserker' => ['dias' => '30-59 dias', 'icone' => '⚡', 'min' => 30],
                        'Ragnarok' => ['dias' => '60+ dias', 'icone' => '🔥', 'min' => 60]
                    ];
                    
                    $liga_sem_prefixo = str_replace('Liga ', '', $current_league);
                    $proxima_liga = '';
                    $dias_restantes = 0;
                    
                    if ($streak < 3) {
                        $proxima_liga = 'Viking';
                        $dias_restantes = 3 - $streak;
                    } elseif ($streak < 7) {
                        $proxima_liga = 'Huscarl';
                        $dias_restantes = 7 - $streak;
                    } elseif ($streak < 15) {
                        $proxima_liga = 'Jarl';
                        $dias_restantes = 15 - $streak;
                    } elseif ($streak < 30) {
                        $proxima_liga = 'Berserker';
                        $dias_restantes = 30 - $streak;
                    } elseif ($streak < 60) {
                        $proxima_liga = 'Ragnarok';
                        $dias_restantes = 60 - $streak;
                    }
                    
                    foreach ($ligas_req as $nome_l => $dados_l):
                        $is_atual = ($liga_sem_prefixo === $nome_l);
                    ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; background: <?= $is_atual ? 'rgba(196, 181, 253, 0.15)' : 'rgba(255,255,255,0.03)' ?>; border: 1px solid <?= $is_atual ? 'var(--cor-intermedia)' : 'rgba(255,255,255,0.06)' ?>; border-radius: 12px; transition: all 0.2s;">
                            <span style="font-weight: bold; color: <?= $is_atual ? '#fff' : 'rgba(255,255,255,0.8)' ?>; display: flex; align-items: center; gap: 10px; font-size: 1rem;">
                                <span><?= $dados_l['icone'] ?></span>
                                <span><?= $nome_l ?></span>
                                <?php if ($is_atual): ?>
                                    <span style="background: var(--cor-intermedia); color: #fff; font-size: 0.7rem; padding: 2px 8px; border-radius: 8px; font-weight: 800; text-transform: uppercase;">Atual</span>
                                <?php endif; ?>
                            </span>
                            <span style="font-size: 0.9rem; font-weight: 600; color: <?= $is_atual ? 'var(--cor-intermedia)' : 'rgba(255,255,255,0.5)' ?>;"><?= $dados_l['dias'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($proxima_liga !== ''): ?>
                    <div style="margin-top: 20px; background: rgba(16, 185, 129, 0.1); border: 1px dashed rgba(16, 185, 129, 0.3); border-radius: 12px; padding: 15px; text-align: center;">
                        <span style="font-weight: 800; color: #10b981; display: block; font-size: 1.1rem; margin-bottom: 5px;">🔥 Rumo à Liga <?= $proxima_liga ?>!</span>
                        <span style="font-size: 0.9rem; color: rgba(255,255,255,0.8);">
                            Faltam apenas <strong><?= $dias_restantes ?> dia(s) seguidos</strong> para subires de escalão. Continua firme!
                        </span>
                    </div>
                <?php else: ?>
                    <div style="margin-top: 20px; background: rgba(245, 158, 11, 0.1); border: 1px dashed rgba(245, 158, 11, 0.3); border-radius: 12px; padding: 15px; text-align: center;">
                        <span style="font-weight: 800; color: #f59e0b; display: block; font-size: 1.15rem; margin-bottom: 5px;">🔥 Nível Divino Alcançado!</span>
                        <span style="font-size: 0.9rem; color: rgba(255,255,255,0.8);">
                            Estás na liga lendária <strong>Ragnarok</strong>. A tua resiliência é digna de um Deus nórdico! ⚔️
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer" style="padding: 15px 20px 25px 20px;">
                <button type="button" class="btn-save-modal" onclick="fecharModalLiga()" style="width: 100%; justify-content: center;">
                    Entendido, vamos treinar!
                </button>
            </div>
        </div>
    </div>

    <?php include 'app_navbar.php'; ?>

    <script>
        function abrirModalLiga() {
            const modal = document.getElementById('modalLiga');
            modal.style.display = 'flex';
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function fecharModalLiga() {
            const modal = document.getElementById('modalLiga');
            modal.style.display = 'none';
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Fechar ao pressionar ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                fecharModalLiga();
            }
        });
    </script>
</body>

</html>
