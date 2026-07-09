<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'ligacao.php';

$user_id = $_SESSION['user_id'];
$nome_usuario = $_SESSION['user_nome'] ?? 'Visitante';

// Buscar treinos do usuário
$sql = "SELECT t.*, COUNT(e.id_exercicio) as qtd_exercicios 
        FROM treino t 
        LEFT JOIN exercicio e ON t.id_treino = e.id_treino 
        WHERE t.id_user = ? 
        GROUP BY t.id_treino 
        ORDER BY t.data_criacao DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$treinos = [];
while ($row = $result->fetch_assoc()) {
    $treinos[] = $row;
}
$stmt->close();

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

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Treinos - BerserkFit</title>
    <link rel="stylesheet" href="css/global.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/treinos.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Syne:wght@700;800&display=swap"
        rel="stylesheet">
</head>

<body>

    <header class="fade-in-element">
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
            <h2>Meus Treinos</h2>
            <p>Gerencie seus planos de treino personalizados.</p>
        </div>
    </header>

    <main>
        <!-- Filtros serão movidos para dentro da seção de treinos -->

        <section class="treinos-container fade-in-element">
            <div class="header-section">
                <h3>Seus Treinos Salvos</h3>
                
                <div class="header-actions-group">
                    <div id="selection-controls" style="display: none;">
                        <label class="select-all-container">
                            <input type="checkbox" id="select-all" class="treino-checkbox-selectall"> 
                            <span>Selecionar Tudo</span>
                        </label>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button id="btn-gerir" class="btn-secondary" style="padding: 0.8rem 1.2rem; height: 100%;">
                            <i class="fas fa-tasks"></i> Gerir
                        </button>
                        <a href="criar_treino.php" class="btn-primary">
                            <i class="fas fa-plus"></i> Novo Treino
                        </a>
                    </div>
                </div>
            </div>

            <!-- Filtros Reposicionados -->
            <div class="filters-container fade-in-element" style="padding: 0; margin-bottom: 2rem; justify-content: flex-start;">
                <div class="filters-group">
                    <button class="filter-btn active" data-filter="all">Todos</button>
                    <button class="filter-btn" data-filter="Manual">Manuais</button>
                    <button class="filter-btn" data-filter="Chatbot"><i class="fas fa-robot"></i> IA Oráculo</button>
                </div>
            </div>

            <div id="lista-treinos" class="treinos-grid">
                <?php if (empty($treinos)): ?>
                    <div class="empty-state" style="grid-column: 1/-1;">
                        <i class="fas fa-dumbbell"></i>
                        <h3>Nenhum treino encontrado</h3>
                        <p>Comece criando seu primeiro treino personalizado com o botão acima!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($treinos as $treino): ?>
                        <div class="treino-card" data-origem="<?= htmlspecialchars($treino['origem'] ?? 'Manual') ?>">
                            <!-- Checkbox de Seleção (Invisível por padrão) -->
                            <div class="selection-checkbox-container">
                                <input type="checkbox" class="treino-checkbox" data-id="<?= $treino['id_treino'] ?>">
                            </div>

                            <div class="card-header">
                                <div>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <h3 class="card-title"><?= htmlspecialchars($treino['nome_treino']) ?></h3>
                                        <?php if (($treino['origem'] ?? '') === 'Chatbot'): ?>
                                            <span class="ai-tag" title="Gerado por IA"><i class="fas fa-robot"></i></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-foco">
                                        <i class="fas fa-bullseye"></i> <?= htmlspecialchars($treino['foco']) ?>
                                    </div>
                                </div>
                                <div class="card-options">
                                    <a href="editar_treino.php?id=<?= $treino['id_treino'] ?>" class="btn-icon" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn-icon btn-excluir btn-delete-single-card"
                                        title="Excluir" data-id="<?= $treino['id_treino'] ?>" style="background:none; border:none; cursor:pointer;">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-stats">
                                <div class="stat-item">
                                    <i class="fas fa-list-ul"></i>
                                    <span><?= $treino['qtd_exercicios'] ?> Exercícios</span>
                                </div>
                                <div class="stat-item">
                                    <i class="far fa-calendar-alt"></i>
                                    <span><?= date('d/m/Y', strtotime($treino['data_criacao'])) ?></span>
                                </div>
                            </div>
                            <a href="executar_treino.php?id=<?= $treino['id_treino'] ?>" class="btn-primary">
                                <i class="fas fa-play"></i> Iniciar Treino
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Barra de Ações em Massa (Flutuante) -->
    <div id="bulk-actions-bar" class="bulk-actions-bar">
        <div class="bulk-info">
            <span id="selected-count">0</span> selecionados
        </div>
        <div class="bulk-buttons">
            <button id="btn-delete-selected" class="btn-bulk-delete">
                <i class="fas fa-trash-alt"></i> Apagar Selecionados
            </button>
            <button id="btn-delete-all" class="btn-bulk-all">
                <i class="fas fa-broom"></i> Limpar Tudo
            </button>
        </div>
    </div>

    <!-- Modal Confirmação Personalizado -->
    <div id="modalConfirmacaoTreino" class="modal-confirmacao-treino" role="dialog" aria-modal="true">
        <div class="modal-confirm-content">
            <div class="modal-confirm-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 id="confirm-modal-title" class="modal-confirm-title">Confirmar Ação</h3>
            <p id="confirm-modal-msg" class="modal-confirm-msg">Tem a certeza que deseja realizar esta ação?</p>
            <div class="modal-confirm-buttons">
                <button id="btn-confirm-yes" class="btn-confirm-action btn-confirm-yes">Sim, apagar</button>
                <button id="btn-confirm-no" class="btn-confirm-action btn-confirm-no">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- Navbar centralizada -->
    <?php include 'app_navbar.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btnGerir = document.getElementById('btn-gerir');
            const selectionControls = document.getElementById('selection-controls');
            const selectAllCheck = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.treino-checkbox');
            const bulkBar = document.getElementById('bulk-actions-bar');
            const selectedCountSpan = document.getElementById('selected-count');
            const treinosGrid = document.getElementById('lista-treinos');
            
            let isSelectionMode = false;

            // --- Lógica do Modo Gerir ---
            const toggleSelectionMode = () => {
                isSelectionMode = !isSelectionMode;
                if (isSelectionMode) {
                    btnGerir.innerHTML = '<i class="fas fa-times"></i> Cancelar';
                    btnGerir.classList.add('btn-danger');
                    selectionControls.style.display = 'flex';
                    treinosGrid.classList.add('selection-mode-active');
                } else {
                    btnGerir.innerHTML = '<i class="fas fa-tasks"></i> Gerir';
                    btnGerir.classList.remove('btn-danger');
                    selectionControls.style.display = 'none';
                    treinosGrid.classList.remove('selection-mode-active');
                    // Resetar seleções ao sair
                    selectAllCheck.checked = false;
                    checkboxes.forEach(cb => cb.checked = false);
                    updateBulkBar();
                }
            };

            if(btnGerir) btnGerir.addEventListener('click', toggleSelectionMode);

            const updateBulkBar = () => {
                const checked = document.querySelectorAll('.treino-checkbox:checked');
                selectedCountSpan.textContent = checked.length;
                
                if (isSelectionMode && checked.length > 0) {
                    bulkBar.classList.add('active');
                } else {
                    bulkBar.classList.remove('active');
                }
                
                if(selectAllCheck) selectAllCheck.checked = (checked.length === checkboxes.length && checkboxes.length > 0);
            };

            if(selectAllCheck) {
                selectAllCheck.addEventListener('change', () => {
                    checkboxes.forEach(cb => {
                        const card = cb.closest('.treino-card');
                        if (card.style.display !== 'none') {
                            cb.checked = selectAllCheck.checked;
                        }
                    });
                    updateBulkBar();
                });
            }

            checkboxes.forEach(cb => {
                cb.addEventListener('change', updateBulkBar);
            });

            // --- Modal de Confirmação Personalizado (Promise) ---
            const modalConfirm = document.getElementById('modalConfirmacaoTreino');
            const confirmTitle = document.getElementById('confirm-modal-title');
            const confirmMsg = document.getElementById('confirm-modal-msg');
            const btnConfirmYes = document.getElementById('btn-confirm-yes');
            const btnConfirmNo = document.getElementById('btn-confirm-no');

            function pedirConfirmacao(titulo, mensagem, textoSim = "Sim, apagar") {
                return new Promise((resolve) => {
                    confirmTitle.textContent = titulo;
                    confirmMsg.textContent = mensagem;
                    btnConfirmYes.textContent = textoSim;
                    
                    modalConfirm.classList.add('active');

                    function fechar(resultado) {
                        modalConfirm.classList.remove('active');
                        btnConfirmYes.onclick = null;
                        btnConfirmNo.onclick = null;
                        resolve(resultado);
                    }

                    btnConfirmYes.onclick = () => fechar(true);
                    btnConfirmNo.onclick = () => fechar(false);
                    modalConfirm.onclick = (e) => { if (e.target === modalConfirm) fechar(false); };
                });
            }

            // --- Ação: Apagar Treino Individual ---
            document.querySelectorAll('.btn-delete-single-card').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    const id = btn.dataset.id;
                    const confirmado = await pedirConfirmacao(
                        'Apagar Treino',
                        'Tem a certeza que deseja excluir este treino? Esta ação não pode ser desfeita.'
                    );
                    if (confirmado) {
                        window.location.href = `excluir_treino.php?id=${id}`;
                    }
                });
            });

            // --- Ação: Apagar Selecionados ---
            document.getElementById('btn-delete-selected')?.addEventListener('click', async () => {
                const checked = document.querySelectorAll('.treino-checkbox:checked');
                const ids = Array.from(checked).map(cb => cb.dataset.id);

                if (ids.length === 0) return;

                const confirmado = await pedirConfirmacao(
                    'Apagar Selecionados',
                    `Tem a certeza que deseja apagar os ${ids.length} treinos selecionados?`
                );
                if (!confirmado) return;

                try {
                    const response = await fetch('bulk_acoes_treino.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ acao: 'apagar_selecionados', ids: ids })
                    });
                    const res = await response.json();
                    if (res.success) {
                        location.reload();
                    } else {
                        alert('Erro ao apagar: ' + res.message);
                    }
                } catch (e) { alert('Erro na ligação ao servidor.'); }
            });

            // --- Ação: Limpar Tudo (IA + Manuais) ---
            document.getElementById('btn-delete-all')?.addEventListener('click', async () => {
                const confirmado = await pedirConfirmacao(
                    'Limpar Todos os Treinos',
                    'AVISO: Isto irá apagar TODOS os seus treinos (Manuais e IA). Esta ação não pode ser desfeita. Deseja continuar?'
                );
                if (!confirmado) return;

                try {
                    const response = await fetch('bulk_acoes_treino.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ acao: 'apagar_tudo' })
                    });
                    const res = await response.json();
                    if (res.success) {
                        location.reload();
                    } else {
                        alert('Erro ao limpar: ' + res.message);
                    }
                } catch (e) { alert('Erro na ligação.'); }
            });

            // --- Lógica de Filtros ---
            const filterBtns = document.querySelectorAll('.filter-btn');
            filterBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    
                    const filterValue = btn.dataset.filter;
                    const cards = document.querySelectorAll('.treino-card');
                    
                    cards.forEach(card => {
                        if (filterValue === 'all' || card.dataset.origem === filterValue) {
                            card.style.display = 'flex';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                    // Resetar seleção ao filtrar para evitar confusão
                    checkboxes.forEach(cb => cb.checked = false);
                    if(selectAllCheck) selectAllCheck.checked = false;
                    updateBulkBar();
                });
            });

            // --- Efeito de carregamento suave ---
            const cards = document.querySelectorAll('.treino-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(15px)';
                card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 50);
            });
        });
    </script>
</body>


</html>
