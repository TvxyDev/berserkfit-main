<?php
session_start();
date_default_timezone_set('Europe/Lisbon');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Redireciona para o checkout se houver plano pendente no login
if (isset($_SESSION['redirect_checkout_plano'])) {
    $plano_pendente = $_SESSION['redirect_checkout_plano'];
    unset($_SESSION['redirect_checkout_plano']);
    header("Location: checkout.php?plano=" . urlencode($plano_pendente));
    exit;
}

require 'ligacao.php';

$user_id = $_SESSION['user_id'];
$sql_check = "SELECT COUNT(*) as total FROM habito WHERE id_user = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if ($row['total'] == 0) {
    header("Location: onboarding.php");
    exit;
}

$agua_hoje = 0;
$agua_meta = 3.0;

try {
    $sql_meta_agua = "SELECT valor FROM meta_usuario WHERE id_user = ? AND tipo = 'agua'";
    $stmt_meta = $conn->prepare($sql_meta_agua);
    if ($stmt_meta) {
        $stmt_meta->bind_param("i", $user_id);
        $stmt_meta->execute();
        $result_meta = $stmt_meta->get_result();
        if ($row_meta = $result_meta->fetch_assoc()) {
            $agua_meta = floatval($row_meta['valor']);
        }
        $stmt_meta->close();
    }
} catch (Exception $e) {
}

$sql_agua = "SELECT COALESCE(SUM(quantidade), 0) as total FROM agua WHERE id_user = ? AND data = CURDATE()";
$stmt = $conn->prepare($sql_agua);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $agua_hoje = floatval($row['total']);
}
$stmt->close();

$peso_atual = null;
$data_peso = null;
$sql_peso = "SELECT peso, data FROM peso WHERE id_user = ? ORDER BY data DESC LIMIT 1";
$stmt = $conn->prepare($sql_peso);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $peso_atual = floatval($row['peso']);
    $data_peso = $row['data'];
}
$stmt->close();

$calorias_ingeridas = 0;
$calorias_meta = 2000; // Meta padrão de ingestão se não definida
try {
    // Buscar meta personalizada de calorias
    $sql_meta_cal = "SELECT valor FROM meta_usuario WHERE id_user = ? AND tipo = 'calorias'";
    $stmt_meta = $conn->prepare($sql_meta_cal);
    if ($stmt_meta) {
        $stmt_meta->bind_param("i", $user_id);
        $stmt_meta->execute();
        $res_meta = $stmt_meta->get_result();
        if ($row_meta = $res_meta->fetch_assoc()) {
            $calorias_meta = floatval($row_meta['valor']);
        }
        $stmt_meta->close();
    }

    // Buscar ingestão de hoje
    $proteinas_hoje = 0;
    $carbs_hoje = 0;
    $gorduras_hoje = 0;
    
    $sql_calorias = "SELECT COALESCE(SUM(calorias), 0) as total, 
                            COALESCE(SUM(proteinas), 0) as proteinas, 
                            COALESCE(SUM(carbs), 0) as carbs, 
                            COALESCE(SUM(gorduras), 0) as gorduras 
                     FROM alimentacao 
                     WHERE id_user = ? AND data = CURDATE()";
    $stmt = $conn->prepare($sql_calorias);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $calorias_ingeridas = floatval($row['total']);
            $proteinas_hoje = floatval($row['proteinas']);
            $carbs_hoje = floatval($row['carbs']);
            $gorduras_hoje = floatval($row['gorduras']);
        }
        $stmt->close();
    }
} catch (Exception $e) {
}

$minutos_treino = 0;
$minutos_meta = 60;
try {
    $sql_minutos = "SELECT COALESCE(SUM(duracao_minutos), 0) as total FROM treino WHERE id_user = ? AND DATE(data_treino) = CURDATE()";
    $stmt = $conn->prepare($sql_minutos);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $minutos_treino = intval($row['total']);
        }
        $stmt->close();
    }
} catch (Exception $e) {
}

$horas_sono = 0;
$minutos_sono = 0;
$sono_total_minutos = 0;
$sono_meta_minutos = 450;
try {
    $sql_sono = "SELECT horas, minutos FROM sono WHERE id_user = ? AND data = CURDATE()";
    $stmt = $conn->prepare($sql_sono);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $horas_sono = intval($row['horas'] ?? 0);
            $minutos_sono = intval($row['minutos'] ?? 0);
            $sono_total_minutos = ($horas_sono * 60) + $minutos_sono;
        }
        $stmt->close();
    }
} catch (Exception $e) {
}

$habitos_user = [];
try {
    $sql_habitos = "SELECT h.id_habito, h.descricao, h.meta_diaria, h.tipo, COALESCE(c.concluido, 0) as concluido 
                    FROM habito h 
                    LEFT JOIN checklist_diario c ON h.id_habito = c.id_habito AND c.data = CURDATE() 
                    WHERE h.id_user = ?";
    $stmt_habitos = $conn->prepare($sql_habitos);
    if ($stmt_habitos) {
        $stmt_habitos->bind_param("i", $user_id);
        $stmt_habitos->execute();
        $result_habitos = $stmt_habitos->get_result();
        while ($row_habito = $result_habitos->fetch_assoc()) {
            $habitos_user[] = $row_habito;
        }
        $stmt_habitos->close();
    }
} catch (Exception $e) {
}

$is_admin = false;
try {
    $sql_admin = "SELECT COALESCE(tipo_usuario, 'Usuario') as tipo_usuario FROM user WHERE id_user = ?";
    $stmt_admin = $conn->prepare($sql_admin);
    $stmt_admin->bind_param("i", $user_id);
    $stmt_admin->execute();
    $result_admin = $stmt_admin->get_result();
    if ($row_admin = $result_admin->fetch_assoc()) {
        $tipo_usr = $row_admin['tipo_usuario'] ?? 'Usuario';
        $is_admin = ($tipo_usr === 'Admin' || $tipo_usr === 'SuperAdmin');
    }
    $stmt_admin->close();
} catch (Exception $e) {
}
// --- Início: Logs para Gráficos ---
$historico_peso = [];
$sql_hist_peso = "SELECT data, peso FROM peso WHERE id_user = ? ORDER BY data DESC LIMIT 30";
$stmt_hp = $conn->prepare($sql_hist_peso);
$stmt_hp->bind_param("i", $user_id);
$stmt_hp->execute();
$res_hp = $stmt_hp->get_result();
while ($row = $res_hp->fetch_assoc()) {
    $historico_peso[] = $row;
}
$stmt_hp->close();

$datas_peso_js = [];
$valores_peso_js = [];
$hist_peso_reverse = array_reverse($historico_peso);
foreach ($hist_peso_reverse as $hp) {
    if (isset($hp['data']) && isset($hp['peso'])) {
        $datas_peso_js[] = date('d/m', strtotime($hp['data']));
        $valores_peso_js[] = floatval($hp['peso']);
    }
}

$exercicios_com_hist = [];
$sql_ex_hist = "
    SELECT DISTINCT e.id_exercicio, e.nome_exercicio 
    FROM exercicio e
    JOIN treino t ON e.id_treino = t.id_treino
    WHERE t.id_user = ?
    UNION
    SELECT DISTINCT e.id_exercicio, e.nome_exercicio 
    FROM historico_exercicio_log hel
    JOIN exercicio e ON hel.id_exercicio = e.id_exercicio
    JOIN historico_treino_log htl ON hel.id_log = htl.id_log
    WHERE htl.id_user = ?
    ORDER BY nome_exercicio ASC
";
$stmt_eh = $conn->prepare($sql_ex_hist);
$stmt_eh->bind_param("ii", $user_id, $user_id);
$stmt_eh->execute();
$res_eh = $stmt_eh->get_result();
while ($row = $res_eh->fetch_assoc()) {
    $exercicios_com_hist[] = $row;
}
$stmt_eh->close();

// Buscando o Volume de Carga (Força Global) Agrupado por Semana
$historico_forca_global = [];
$sql_forca_global = "
    SELECT 
        YEARWEEK(htl.data_treino, 1) as semana,
        MIN(DATE(htl.data_treino)) as inicio_semana,
        SUM(hel.peso_kg * hel.repeticoes) as volume_total
    FROM historico_treino_log htl
    JOIN historico_exercicio_log hel ON htl.id_log = hel.id_log
    WHERE htl.id_user = ? 
      AND htl.data_treino >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK)
    GROUP BY semana
    ORDER BY semana ASC
";
$stmt_fg = $conn->prepare($sql_forca_global);
if ($stmt_fg) {
    $stmt_fg->bind_param("i", $user_id);
    $stmt_fg->execute();
    $res_fg = $stmt_fg->get_result();
    while ($row = $res_fg->fetch_assoc()) {
        $historico_forca_global[] = $row;
    }
    $stmt_fg->close();
}

// Calcular % de aumento (comparando última e penúltima semana disponíveis)
$aumento_percentual = 0;
$icone_aumento = "";
$cor_aumento = "#9ca3af";
if (count($historico_forca_global) >= 2) {
    $ultima_semana = end($historico_forca_global);
    $penultima_semana = prev($historico_forca_global);
    if ($penultima_semana['volume_total'] > 0) {
        $aumento_percentual = (($ultima_semana['volume_total'] - $penultima_semana['volume_total']) / $penultima_semana['volume_total']) * 100;
        if ($aumento_percentual > 0) {
            $icone_aumento = "<i class='fas fa-arrow-up'></i>";
            $cor_aumento = "#22c55e"; // verde
        } elseif ($aumento_percentual < 0) {
            $icone_aumento = "<i class='fas fa-arrow-down'></i>";
            $cor_aumento = "#ef4444"; // vermelho
        }
    }
}

// Preparar dados do gráfico Volume Global
$datas_forca_js = [];
$valores_forca_js = [];
foreach ($historico_forca_global as $fg) {
    $datas_forca_js[] = 'Sem. ' . date('d/m', strtotime($fg['inicio_semana']));
    $valores_forca_js[] = floatval($fg['volume_total']);
}
// --- Fim: Logs para Gráficos ---

$conn->close();

$agua_percentual = $agua_meta > 0 ? min(100, ($agua_hoje / $agua_meta) * 100) : 0;
$calorias_percentual = $calorias_meta > 0 ? min(100, ($calorias_ingeridas / $calorias_meta) * 100) : 0;
$minutos_percentual = $minutos_meta > 0 ? min(100, ($minutos_treino / $minutos_meta) * 100) : 0;
$sono_percentual = $sono_meta_minutos > 0 ? min(100, ($sono_total_minutos / $sono_meta_minutos) * 100) : 0;

$habitos_total = count($habitos_user);
$habitos_concluidos = array_sum(array_column($habitos_user, 'concluido'));
?>
<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BerserkFit</title>
    <link rel="stylesheet" href="css/global.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Syne:wght@700;800&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

    <!-- ══════════ HEADER ══════════ -->
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

        <div class="calendar">
            <?php
            $dias_semana = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'];
            for ($i = 6; $i >= 0; $i--) {
                $timestamp = strtotime("-$i days");
                $dia_semana_num = date('w', $timestamp);
                $dia_mes = date('d', $timestamp);
                $is_hoje = date('Y-m-d', $timestamp) === date('Y-m-d');
                ?>
                <div class="calendar-day <?php echo $is_hoje ? 'active' : ''; ?>">
                    <span><?php echo $dias_semana[$dia_semana_num]; ?></span>
                    <span><?php echo $dia_mes; ?></span>
                </div>
            <?php } ?>
        </div>

        <div class="header-greeting">
            <h2>Bom dia, <?php echo htmlspecialchars($_SESSION['user_nome']); ?>!</h2>
            <p>Pronto para conquistar o dia?</p>
        </div>
    </header>

    <!-- ══════════ MAIN ══════════ -->
    <main>

        <!-- Resumo do Dia -->
        <section id="resumo-dia">
            <h2 class="section-title">Resumo do Dia</h2>
            <div class="grade-resumo">

                <!-- Água -->
                <div class="card-resumo fade-in-element" style="--card-accent: #60a5fa;">
                    <span class="card-resumo-icon">💧</span>
                    <h3>Água ingerida</h3>
                    <div class="valor">
                        <?php echo number_format($agua_hoje, 1); ?>
                        <span class="sub">/ <?php echo $agua_meta; ?>L</span>
                    </div>
                    <div class="progresso-bar">
                        <div class="progresso" style="width: <?php echo $agua_percentual; ?>%;"></div>
                    </div>
                    <div class="progresso-label"><?php echo round($agua_percentual); ?>%</div>
                </div>

                <!-- Calorias -->
                <div class="card-resumo fade-in-element" style="--card-accent: #fb923c;">
                    <span class="card-resumo-icon">🥗</span>
                    <h3>Calorias ingeridas</h3>
                    <div class="valor">
                        <?php echo number_format($calorias_ingeridas, 0); ?>
                        <span class="sub">/ <?php echo $calorias_meta; ?> kcal</span>
                    </div>
                    <div class="progresso-bar">
                        <div class="progresso" style="width: <?php echo $calorias_percentual; ?>%;"></div>
                    </div>
                    <div class="progresso-label"><?php echo round($calorias_percentual); ?>%</div>
                </div>

                <!-- Treino -->
                <div class="card-resumo fade-in-element" style="--card-accent: #a78bfa;">
                    <span class="card-resumo-icon">⏱️</span>
                    <h3>Minutos de treino</h3>
                    <div class="valor">
                        <?php echo $minutos_treino; ?>
                        <span class="sub">min</span>
                    </div>
                    <div class="progresso-bar">
                        <div class="progresso" style="width: <?php echo $minutos_percentual; ?>%;"></div>
                    </div>
                    <div class="progresso-label"><?php echo round($minutos_percentual); ?>%</div>
                </div>

                <!-- Macronutrientes -->
                <div class="card-resumo fade-in-element" style="--card-accent: #f43f5e;">
                    <span class="card-resumo-icon">🥩</span>
                    <div style="margin-top: 10px; display: flex; flex-direction: column; gap: 6px; padding-bottom: 5px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85em; background: rgba(196, 181, 253, 0.05); padding: 6px 10px; border-radius: 6px; border: 1px solid rgba(196, 181, 253, 0.15);">
                            <span style="color: var(--cor-destaque); font-weight: 600;">Proteínas</span>
                            <span style="color: var(--cor-destaque); font-weight: 800; font-family: var(--fonte-titulo);"><?= round($proteinas_hoje) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85em; background: rgba(196, 181, 253, 0.05); padding: 6px 10px; border-radius: 6px; border: 1px solid rgba(196, 181, 253, 0.15);">
                            <span style="color: var(--cor-destaque); font-weight: 600;">Hidratos de Carbono</span>
                            <span style="color: var(--cor-destaque); font-weight: 800; font-family: var(--fonte-titulo);"><?= round($carbs_hoje) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85em; background: rgba(196, 181, 253, 0.05); padding: 6px 10px; border-radius: 6px; border: 1px solid rgba(196, 181, 253, 0.15);">
                            <span style="color: var(--cor-destaque); font-weight: 600;">Gorduras</span>
                            <span style="color: var(--cor-destaque); font-weight: 800; font-family: var(--fonte-titulo);"><?= round($gorduras_hoje) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Peso Corporal Destaque -->
        <section id="peso-destaque" class="fade-in-element">
            <div class="card-peso">
                <div class="card-peso-left">
                    <div class="card-peso-label">Peso Corporal</div>
                    <div class="card-peso-valor">
                        <?php echo $peso_atual ? number_format($peso_atual, 1) : '--'; ?>
                        <span class="unidade">kg</span>
                    </div>
                    <div class="card-peso-footer">
                        <?php if ($data_peso):
                            $data_diff_segundos = time() - strtotime($data_peso);
                            $data_diff_horas = abs($data_diff_segundos) / 3600;
                            
                            if ($data_diff_horas < 1) {
                                $minutos = round(abs($data_diff_segundos) / 60);
                                echo "Registado há " . $minutos . " min";
                            } elseif ($data_diff_horas < 24) {
                                echo "Registado há " . round($data_diff_horas) . " horas";
                            } else {
                                $dias = round($data_diff_horas / 24);
                                echo "Registado há " . $dias . " dia" . ($dias > 1 ? "s" : "");
                            }
                        else:
                            echo "Nenhum registo ainda";
                        endif; ?>
                    </div>
                </div>
                <div class="card-peso-icon">
                    <i class="fas fa-weight-scale"></i>
                </div>
            </div>
        </section>

        <!-- Módulos -->
        <section id="modulos" class="fade-in-element">
            <h2 class="section-title">Módulos</h2>
            <div class="grid-modulos">

                <!-- Objetivos -->
                <div class="modulo-card dark" onclick="openObjetivosModal()" style="cursor:pointer;">
                    <div class="modulo-icon"><i class="fas fa-bullseye"></i></div>
                    <div>
                        <p class="modulo-nome">Objetivos</p>
                        <p class="modulo-sub"><?php echo $habitos_concluidos; ?>/<?php echo $habitos_total; ?>
                            concluídos</p>
                    </div>
                    <?php if ($habitos_total > 0): ?>
                        <div class="desafios-progress">
                            <div class="desafios-progress-fill"
                                style="width: <?php echo round($habitos_total > 0 ? ($habitos_concluidos / $habitos_total) * 100 : 0); ?>%;">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Progresso -->
                <div class="modulo-card" onclick="openProgressoModal()" style="cursor:pointer;">
                    <div class="modulo-icon"><i class="fas fa-chart-line"></i></div>
                    <div>
                        <p class="modulo-nome">Progresso</p>
                        <p class="modulo-sub">2 gráficos de evolução</p>
                    </div>
                </div>

                <!-- Dica do Dia -->
                <div class="modulo-card">
                    <div class="modulo-icon"><i class="fas fa-lightbulb"></i></div>
                    <div id="dica-container">
                        <p class="modulo-nome">Dica do Dia</p>
                        <p class="modulo-sub" id="dica-texto">Carregando dica...</p>
                    </div>
                </div>

                <!-- Nutrição -->
                <div class="modulo-card accent" onclick="openNutricaoModal()" style="cursor:pointer;">
                    <div class="modulo-icon"><i class="fas fa-apple-whole"></i></div>
                    <div>
                        <p class="modulo-nome">Nutrição</p>
                        <p class="modulo-sub">Pesquisar alimentos</p>
                    </div>
                </div>

            </div>
        </section>
    </main>

    <!-- ══════════ NAVBAR ══════════ -->
    <?php include 'app_navbar.php'; ?>

    <!-- ══════════ MODAL OBJETIVOS ══════════ -->
    <div class="modal-overlay" id="objetivosModal">
        <div class="modal-content">
            <div class="modal-handle"></div>
            <div class="modal-header">
                <h2>Objetivos Diários</h2>
                <button class="close-btn" onclick="closeObjetivosModal()"><i class="fas fa-times"></i></button>
            </div>

            <div class="objetivos-section">
                <h3>Metas de Água e Exercício</h3>
                <div class="objetivo-item">
                    <input type="checkbox" id="obj-agua">
                    <label for="obj-agua">Beber <?php echo $agua_meta; ?>L de Água</label>
                </div>
                <div class="objetivo-item">
                    <input type="checkbox" id="obj-calorias">
                    <label for="obj-calorias">Queimar <?php echo $calorias_meta; ?> kcal</label>
                </div>
            </div>

            <div class="objetivos-section">
                <h3>Meus Desafios</h3>
                <?php if (!empty($habitos_user)): ?>
                    <div class="desafios-lista">
                        <?php foreach ($habitos_user as $habito): ?>
                            <div class="desafio-card" id="card-habito-<?php echo $habito['id_habito']; ?>">
                                <div style="display: flex; align-items: flex-start; gap: 12px;">
                                    <input type="checkbox" id="habito-<?php echo $habito['id_habito']; ?>"
                                        onchange="toggleHabit(<?php echo $habito['id_habito']; ?>, this.checked)" <?php echo $habito['concluido'] ? 'checked' : ''; ?>
                                        style="margin-top:4px; width:18px; height:18px; accent-color:var(--cor-destaque); cursor:pointer;">
                                    <div style="flex:1;">
                                        <h4 style="<?php echo $habito['concluido'] ? 'text-decoration:line-through;opacity:0.5;' : ''; ?>"
                                            id="desc-habito-<?php echo $habito['id_habito']; ?>">
                                            <?php echo htmlspecialchars($habito['descricao']); ?>
                                        </h4>
                                        <div class="desafio-detalhes" id="detalhes-habito-<?php echo $habito['id_habito']; ?>"
                                            style="<?php echo $habito['concluido'] ? 'opacity:0.5;' : ''; ?>">
                                            <span class="badge-tipo">
                                                <i class="fa-solid fa-tag" style="font-size:0.8em;"></i>
                                                <?php echo htmlspecialchars($habito['tipo']); ?>
                                            </span>
                                            <span class="meta-texto">Meta:
                                                <?php echo htmlspecialchars($habito['meta_diaria']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="font-size:0.9em; color:#64748b;">Ainda não tens desafios adicionados.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- ══════════ MODAL NUTRIÇÃO EXPANDIDO ══════════ -->
    <div class="modal-overlay" id="nutricaoModal">
        <div class="modal-content modal-nutricao-full">
            <div class="modal-handle"></div>
            <div class="modal-header">
                <h2>Registo Nutricional</h2>
                <button class="close-btn" onclick="closeNutricaoModal()"><i class="fas fa-times"></i></button>
            </div>

            <div class="modal-body">
                <div class="search-section">
                    <div class="search-bar">
                        <input type="text" id="food-search-input" placeholder="Pesquisar alimento ou marca...">
                        <button onclick="searchFood()"><i class="fas fa-search"></i></button>
                    </div>
                    <div id="loading-food" style="display:none; text-align:center; padding:10px;">
                        <i class="fas fa-spinner fa-spin"></i> A procurar...
                    </div>
                    <div class="food-results" id="food-results-container"></div>
                </div>

                <div class="selection-section">
                    <h3>Lista da Refeição <span id="items-count" class="badge">0</span></h3>
                    <div id="selected-foods-list" class="selected-foods-list">
                        <!-- Itens selecionados aparecerão aqui -->
                        <div class="empty-selection">Nenhum alimento selecionado</div>
                    </div>
                    
                    <div class="selection-footer" id="selection-footer" style="display:none; padding-bottom: 20px;">
                        <div class="total-macros-summary">
                            <div><strong>Total:</strong> <span id="total-calories-display">0</span> kcal</div>
                            <div class="macros-grid-mini">
                                <span>P: <span id="total-prot-display">0</span>g</span>
                                <span>C: <span id="total-carb-display">0</span>g</span>
                                <span>G: <span id="total-fat-display">0</span>g</span>
                            </div>
                        </div>
                        
                        <div class="meal-registration-form">
                            <select id="meal-type-select" class="input-modern">
                                <option value="Pequeno-almoço">Pequeno-almoço</option>
                                <option value="Almoço">Almoço</option>
                                <option value="Lanche">Lanche</option>
                                <option value="Jantar">Jantar</option>
                                <option value="Outro">Outro</option>
                            </select>
                            <button onclick="saveMeal()" class="btn-save-meal">
                                <i class="fas fa-check"></i> Registar Refeição
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ MODAL PROGRESSO (CHART.JS) ══════════ -->
    <div class="modal-overlay" id="progressoModal">
        <div class="modal-content modal-nutricao-full" style="max-width: 800px;">
            <div class="modal-handle"></div>
            <div class="modal-header">
                <h2>Evolução Visual</h2>
                <button class="close-btn" onclick="closeProgressoModal()"><i class="fas fa-times"></i></button>
            </div>

            <div class="modal-body" style="grid-template-columns: 1fr; display: flex; flex-direction: column; gap: 20px; overflow-y: auto;">
                <div class="card-progresso" style="padding: 15px; background: rgba(196, 181, 253, 0.05); border-radius: 12px; border: 1px solid rgba(196, 181, 253, 0.15);">
                    <h4 style="margin: 0 0 10px; color: var(--cor-destaque);">Evolução do Peso Corporal</h4>
                    <div style="height: 250px; width: 100%;">
                        <canvas id="weightChart"></canvas>
                    </div>
                </div>

                <!-- NOVO GRÁFICO: Volume Global Levantado -->
                <div class="card-progresso" style="padding: 15px; background: rgba(196, 181, 253, 0.05); border-radius: 12px; border: 1px solid rgba(196, 181, 253, 0.15);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; gap: 10px; flex-wrap: wrap;">
                        <h4 style="margin: 0; color: var(--cor-destaque);">Volume Semanal (Força)</h4>
                        <?php if($aumento_percentual !== 0): ?>
                            <div style="font-size: 0.85em; font-weight: bold; color: <?= $cor_aumento ?>; background: rgba(0,0,0,0.2); padding: 4px 8px; border-radius: 6px; border: 1px solid <?= $cor_aumento ?>40;">
                                <?= $icone_aumento ?> <?= number_format(abs($aumento_percentual), 1) ?>% vs sem. ant.
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="height: 250px; width: 100%;">
                        <?php if (empty($historico_forca_global)): ?>
                            <div style="display:flex; align-items:center; justify-content:center; height:100%; color:#94a3b8; font-size:0.9em; text-align:center;">
                                <p>Sem treinos suficientes.</p>
                            </div>
                        <?php else: ?>
                            <canvas id="globalStrengthChart"></canvas>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-progresso" style="padding: 15px; background: rgba(196, 181, 253, 0.05); border-radius: 12px; border: 1px solid rgba(196, 181, 253, 0.15);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; gap: 10px;">
                        <h4 style="margin:0; color: var(--cor-destaque);">Força por Exercício</h4>
                        <select id="select-exercicio-grafico" class="input-modern" style="padding: 4px 8px; font-size: 0.85em; width: auto; border: 1px solid rgba(196, 181, 253, 0.2); border-radius: 6px; background: white;" onchange="updateStrengthChart(this.value)">
                            <option value="">Selecionar exercício...</option>
                            <?php foreach($exercicios_com_hist as $ex_h): ?>
                                <option value="<?= $ex_h['id_exercicio'] ?>"><?= htmlspecialchars($ex_h['nome_exercicio']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="height: 250px; width: 100%;">
                        <canvas id="strengthChart"></canvas>
                        <div id="no-strength-data" style="display:flex; align-items:center; justify-content:center; height:100%; color:#94a3b8; font-size:0.9em;">
                            Selecione um exercício para ver o gráfico
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ JS INíCIO ══════════ -->
    <script>
        // ══════════ OBJETIVOS MODAL ══════════
        function openObjetivosModal() {
            document.getElementById('objetivosModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeObjetivosModal() {
            document.getElementById('objetivosModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        // ══════════ DICA DO DIA ══════════
        async function loadDica() {
            try {
                const response = await fetch('API/dicas.json');
                const dicas = await response.json();
                const dicaAleatoria = dicas[Math.floor(Math.random() * dicas.length)];
                document.getElementById('dica-texto').textContent = dicaAleatoria.texto;
            } catch (error) {
                document.getElementById('dica-texto').textContent = "Treina com consistência!";
            }
        }
        loadDica();

        // ══════════ NUTRIÇÃO AVANÇADA ══════════
        let selectedFoods = [];

        function openNutricaoModal() {
            document.getElementById('nutricaoModal').classList.add('active');
            document.body.style.overflow = 'hidden';
            setTimeout(() => document.getElementById('food-search-input').focus(), 100);
        }

        function closeNutricaoModal() {
            document.getElementById('nutricaoModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        async function searchFood() {
            const query = document.getElementById('food-search-input').value.trim();
            if (!query) return;

            const resultsContainer = document.getElementById('food-results-container');
            const loader = document.getElementById('loading-food');

            resultsContainer.innerHTML = '';
            loader.style.display = 'block';

            try {
                const response = await fetch(`proxy_food.php?search_terms=${encodeURIComponent(query)}`);
                const data = await response.json();
                loader.style.display = 'none';

                if (data.products && data.products.length > 0) {
                    data.products.forEach(product => {
                        if (!product.product_name) return;

                        const name = product.product_name;
                        const brand = product.brands ? product.brands.split(',')[0] : 'Marca genérica';
                        const image = product.image_front_small_url || product.image_small_url || 'assets/fotos/default-food.png';
                        
                        // Nutrientes por 100g
                        const cals = product.nutriments?.['energy-kcal_100g'] || product.nutriments?.['energy-kcal_value'] || 0;
                        const prot = product.nutriments?.['proteins_100g'] || 0;
                        const carbs = product.nutriments?.['carbohydrates_100g'] || 0;
                        const fat = product.nutriments?.['fat_100g'] || 0;

                        const card = document.createElement('div');
                        card.className = 'food-search-card';
                        card.innerHTML = `
                            <img src="${image}" alt="${name}" onerror="this.src='assets/fotos/default-food.png'">
                            <div class="food-info">
                                <strong>${name}</strong>
                                <span>${brand} • ${Math.round(cals)} kcal/100g</span>
                            </div>
                            <button class="btn-add-item" onclick="addFoodToSelection('${name.replace(/'/g, "\\'")}', ${cals}, ${prot}, ${carbs}, ${fat}, '${image}')">
                                <i class="fas fa-plus"></i>
                            </button>
                        `;
                        resultsContainer.appendChild(card);
                    });
                } else {
                    resultsContainer.innerHTML = '<p class="no-results">Nenhum alimento encontrado.</p>';
                }
            } catch (error) {
                loader.style.display = 'none';
                resultsContainer.innerHTML = '<p class="error-msg">Erro na pesquisa. Tenta novamente.</p>';
            }
        }

        function addFoodToSelection(name, cals, prot, carbs, fat, image) {
            const id = Date.now();
            selectedFoods.push({ id, name, calsPer100: cals, protPer100: prot, carbsPer100: carbs, fatPer100: fat, weight: 100, image });
            updateSelectedFoodsUI();
        }

        function updateSelectedFoodsUI() {
            const container = document.getElementById('selected-foods-list');
            const footer = document.getElementById('selection-footer');
            const countBadge = document.getElementById('items-count');

            if (selectedFoods.length === 0) {
                container.innerHTML = '<div class="empty-selection">Nenhum alimento selecionado</div>';
                footer.style.display = 'none';
                countBadge.textContent = '0';
                return;
            }

            footer.style.display = 'block';
            countBadge.textContent = selectedFoods.length;
            container.innerHTML = '';

            let totalCals = 0, totalProt = 0, totalCarbs = 0, totalFat = 0;

            selectedFoods.forEach(food => {
                const ratio = food.weight / 100;
                const cals = food.calsPer100 * ratio;
                const prot = food.protPer100 * ratio;
                const carbs = food.carbsPer100 * ratio;
                const fat = food.fatPer100 * ratio;

                totalCals += cals;
                totalProt += prot;
                totalCarbs += carbs;
                totalFat += fat;

                const item = document.createElement('div');
                item.className = 'selected-food-item';
                item.innerHTML = `
                    <div class="item-main">
                        <strong>${food.name}</strong>
                        <div class="item-controls">
                            <input type="number" value="${food.weight}" min="1" onchange="updateFoodWeight(${food.id}, this.value)">
                            <span>g</span>
                        </div>
                    </div>
                    <div class="item-details">
                        ${Math.round(cals)} kcal | P: ${prot.toFixed(1)}g | C: ${carbs.toFixed(1)}g | G: ${fat.toFixed(1)}g
                        <button class="btn-remove" onclick="removeFood(${food.id})"><i class="fas fa-trash"></i></button>
                    </div>
                `;
                container.appendChild(item);
            });

            document.getElementById('total-calories-display').textContent = Math.round(totalCals);
            document.getElementById('total-prot-display').textContent = totalProt.toFixed(1);
            document.getElementById('total-carb-display').textContent = totalCarbs.toFixed(1);
            document.getElementById('total-fat-display').textContent = totalFat.toFixed(1);
        }

        function updateFoodWeight(id, weight) {
            const food = selectedFoods.find(f => f.id === id);
            if (food) {
                food.weight = parseFloat(weight) || 0;
                updateSelectedFoodsUI();
            }
        }

        function removeFood(id) {
            selectedFoods = selectedFoods.filter(f => f.id !== id);
            updateSelectedFoodsUI();
        }

        async function saveMeal() {
            if (selectedFoods.length === 0) return;

            const mealType = document.getElementById('meal-type-select').value;
            const totalCals = document.getElementById('total-calories-display').textContent;
            const totalProt = document.getElementById('total-prot-display').textContent;
            const totalCarbs = document.getElementById('total-carb-display').textContent;
            const totalFat = document.getElementById('total-fat-display').textContent;
            
            const description = selectedFoods.map(f => `${f.name} (${f.weight}g)`).join(', ');

            try {
                const formData = new FormData();
                formData.append('calorias', totalCals);
                formData.append('proteinas', totalProt);
                formData.append('carbs', totalCarbs);
                formData.append('gorduras', totalFat);
                formData.append('refeicao', mealType);
                formData.append('descricao', description);
                formData.append('data', new Date().toISOString().split('T')[0]);

                const response = await fetch('salvar_alimentacao.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    alert('Refeição registada com sucesso!');
                    location.reload();
                } else {
                    alert('Erro ao guardar: ' + result.message);
                }
            } catch (error) {
                alert('Erro na ligação ao servidor.');
            }
        }

        document.getElementById('food-search-input').addEventListener('keydown', e => {
            if (e.key === 'Enter') searchFood();
        });

        // ══════════ GRÁFICOS (CHART.JS) ══════════
        function openProgressoModal() {
            document.getElementById('progressoModal').classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Inicializar o gráfico de peso se ainda não existir
            if (!window.weightChartInstance) {
                const ctxWeight = document.getElementById('weightChart').getContext('2d');
                window.weightChartInstance = new Chart(ctxWeight, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode($datas_peso_js) ?>,
                        datasets: [{
                            label: 'Peso (kg)',
                            data: <?= json_encode($valores_peso_js) ?>,
                            borderColor: '#a78bfa',
                            backgroundColor: 'rgba(167, 139, 250, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#a78bfa',
                            pointRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: false, grid: { color: 'rgba(0,0,0,0.05)' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            // Inicializar o gráfico de Volume Global
            const canvasGlobal = document.getElementById('globalStrengthChart');
            if (canvasGlobal && !window.globalStrengthChartInstance) {
                const ctxGlobal = canvasGlobal.getContext('2d');
                
                window.globalStrengthChartInstance = new Chart(ctxGlobal, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($datas_forca_js) ?>,
                        datasets: [{
                            label: 'Volume Total (kg x reps)',
                            data: <?= json_encode($valores_forca_js) ?>,
                            backgroundColor: 'rgba(34, 197, 94, 0.6)',
                            borderColor: '#22c55e',
                            borderWidth: 1,
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.raw.toLocaleString('pt-PT') + ' kg movidos';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }
        }

        function closeProgressoModal() {
            document.getElementById('progressoModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        async function updateStrengthChart(idEx) {
            const canvas = document.getElementById('strengthChart');
            const noData = document.getElementById('no-strength-data');
            
            if (!idEx) {
                canvas.style.display = 'none';
                noData.style.display = 'flex';
                return;
            }

            try {
                const response = await fetch(`get_ex_history.php?id_exercicio=${idEx}`);
                const data = await response.json();

                if (data.success && data.history.length > 0) {
                    noData.style.display = 'none';
                    canvas.style.display = 'block';

                    const labels = data.history.map(h => h.data);
                    const values = data.history.map(h => h.max_peso);

                    if (window.strengthChartInstance) {
                        window.strengthChartInstance.destroy();
                    }

                    window.strengthChartInstance = new Chart(canvas.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Peso Máximo (kg)',
                                data: values,
                                borderColor: '#fb7185',
                                backgroundColor: 'rgba(251, 113, 133, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.3,
                                pointBackgroundColor: '#fb7185'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true },
                                x: { grid: { display: false } }
                            }
                        }
                    });
                } else {
                    canvas.style.display = 'none';
                    noData.style.display = 'flex';
                    noData.textContent = "Sem histórico suficiente para este exercício.";
                }
            } catch (error) {
                console.error("Erro ao carregar histórico:", error);
            }
        }

        async function toggleHabit(idHabito, concluido) {
            const desc = document.getElementById('desc-habito-' + idHabito);
            const detalhes = document.getElementById('detalhes-habito-' + idHabito);
            if (concluido) {
                desc.style.textDecoration = 'line-through';
                desc.style.opacity = '0.5';
                detalhes.style.opacity = '0.5';
            } else {
                desc.style.textDecoration = 'none';
                desc.style.opacity = '1';
                detalhes.style.opacity = '1';
            }
            try {
                const formData = new FormData();
                formData.append('id_habito', idHabito);
                formData.append('concluido', concluido ? 1 : 0);
                const response = await fetch('update_checklist.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (!result.success) alert("Erro ao atualizar o desafio. Tenta novamente.");
            } catch (error) {
                console.error('Erro:', error);
            }
        }

        document.getElementById('food-search-input').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') searchFood();
        });
    </script>
</body>

</html>