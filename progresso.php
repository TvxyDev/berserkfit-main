<?php
session_start();
date_default_timezone_set('Europe/Lisbon');

// Verifica se o utilizador está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'ligacao.php';

$user_id = $_SESSION['user_id'];
$mensagem = "";

// Processar formulários
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'adicionar_agua') {
        $quantidade = floatval($_POST['quantidade'] ?? 0);
        $data = $_POST['data'] ?? date('Y-m-d');

        // Verifica se já existe registo para hoje
        $sql = "SELECT id, quantidade FROM agua WHERE id_user = ? AND data = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $data);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Atualiza registo existente
            $row = $result->fetch_assoc();
            $nova_quantidade = $row['quantidade'] + $quantidade;
            $update = "UPDATE agua SET quantidade = ? WHERE id = ?";
            $stmt2 = $conn->prepare($update);
            $stmt2->bind_param("di", $nova_quantidade, $row['id']);
            $stmt2->execute();
            $stmt2->close();
            $mensagem = "✅ Água adicionada com sucesso!";
        } else {
            // Cria novo registo
            $insert = "INSERT INTO agua (id_user, quantidade, data) VALUES (?, ?, ?)";
            $stmt2 = $conn->prepare($insert);
            $stmt2->bind_param("ids", $user_id, $quantidade, $data);
            if ($stmt2->execute()) {
                $mensagem = "✅ Água registada com sucesso!";
            } else {
                $mensagem = "❌ Erro ao registar água.";
            }
            $stmt2->close();
        }
        $stmt->close();
    } elseif ($acao === 'adicionar_peso') {
        $peso = floatval($_POST['peso'] ?? 0);
        $data_input = $_POST['data'] ?? date('Y-m-d');
        $data = (strlen($data_input) <= 10) ? $data_input . ' ' . date('H:i:s') : $data_input;

        // Verifica se já existe registo para este dia
        $data_busca = date('Y-m-d', strtotime($data));
        $sql = "SELECT id FROM peso WHERE id_user = ? AND DATE(data) = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $data_busca);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Atualiza registo existente
            $row = $result->fetch_assoc();
            $update = "UPDATE peso SET peso = ?, data = ? WHERE id = ?";
            $stmt2 = $conn->prepare($update);
            $stmt2->bind_param("dsi", $peso, $data, $row['id']);
            $stmt2->execute();
            $stmt2->close();
            $mensagem = "✅ Peso atualizado com sucesso!";
        } else {
            // Cria novo registo
            $insert = "INSERT INTO peso (id_user, peso, data) VALUES (?, ?, ?)";
            $stmt2 = $conn->prepare($insert);
            $stmt2->bind_param("ids", $user_id, $peso, $data);
            if ($stmt2->execute()) {
                $mensagem = "✅ Peso registado com sucesso!";
            } else {
                $mensagem = "❌ Erro ao registar peso.";
            }
            $stmt2->close();
        }
        $stmt->close();
    } elseif ($acao === 'adicionar_alimentacao') {
        $calorias = floatval($_POST['calorias'] ?? 0);
        $proteinas = floatval($_POST['proteinas'] ?? 0);
        $carbs = floatval($_POST['carbs'] ?? 0);
        $refeicao = $_POST['refeicao'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $data = $_POST['data'] ?? date('Y-m-d');

        $insert = "INSERT INTO alimentacao (id_user, calorias, proteinas, carbs, refeicao, descricao, data) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert);
        $stmt->bind_param("idddsss", $user_id, $calorias, $proteinas, $carbs, $refeicao, $descricao, $data);
        if ($stmt->execute()) {
            $mensagem = "✅ Refeição registada com sucesso!";
        } else {
            $mensagem = "❌ Erro ao registar refeição.";
        }
        $stmt->close();
    } elseif ($acao === 'criar_habito') {
        $descricao = $_POST['descricao'] ?? '';
        $meta_diaria = !empty($_POST['meta_diaria']) ? floatval($_POST['meta_diaria']) : null;
        $tipo = $_POST['tipo'] ?? '';

        if (!empty($descricao)) {
            $insert = "INSERT INTO habito (id_user, descricao, meta_diaria, tipo) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert);
            $stmt->bind_param("isds", $user_id, $descricao, $meta_diaria, $tipo);
            if ($stmt->execute()) {
                $mensagem = "✅ Rotina criada com sucesso!";
            } else {
                $mensagem = "❌ Erro ao criar Rotina.";
            }
            $stmt->close();
        }
    } elseif ($acao === 'criar_checklist') {
        $id_habito = intval($_POST['id_habito'] ?? 0);
        $data = $_POST['data'] ?? date('Y-m-d');

        if ($id_habito > 0) {
            // Verifica se já existe checklist para este Rotina nesta data
            $check = "SELECT id_checklist FROM checklist_diario WHERE id_habito = ? AND data = ?";
            $stmt = $conn->prepare($check);
            $stmt->bind_param("is", $id_habito, $data);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                // Cria novo checklist
                $insert = "INSERT INTO checklist_diario (id_habito, data, concluido) VALUES (?, ?, 0)";
                $stmt2 = $conn->prepare($insert);
                $stmt2->bind_param("is", $id_habito, $data);
                if ($stmt2->execute()) {
                    $mensagem = "✅ Desafio adicionado com sucesso!";
                } else {
                    $mensagem = "❌ Erro ao criar desafio.";
                }
                $stmt2->close();
            } else {
                $mensagem = "⚠️ Este desafio já existe para esta data!";
            }
            $stmt->close();
        }
    } elseif ($acao === 'toggle_checklist') {
        $checklist_id = intval($_POST['checklist_id'] ?? 0);
        $concluido = intval($_POST['concluido'] ?? 0);

        $update = "UPDATE checklist_diario SET concluido = ? WHERE id_checklist = ?";
        $stmt = $conn->prepare($update);
        $stmt->bind_param("ii", $concluido, $checklist_id);
        if ($stmt->execute()) {
            $mensagem = $concluido ? "✅ Desafio marcado como completo!" : "✅ Desafio desmarcado!";
        } else {
            $mensagem = "❌ Erro ao atualizar desafio.";
        }
        $stmt->close();
    } elseif ($acao === 'deletar_checklist') {
        $checklist_id = intval($_POST['checklist_id'] ?? 0);

        $delete = "DELETE FROM checklist_diario WHERE id_checklist = ?";
        $stmt = $conn->prepare($delete);
        $stmt->bind_param("i", $checklist_id);
        if ($stmt->execute()) {
            $mensagem = "✅ Desafio removido com sucesso!";
        } else {
            $mensagem = "❌ Erro ao remover desafio.";
        }
        $stmt->close();
    } elseif ($acao === 'deletar_habito') {
        $habito_id = intval($_POST['habito_id'] ?? 0);

        // Primeiro remove os checklists relacionados
        $delete_checklists = "DELETE FROM checklist_diario WHERE id_habito = ?";
        $stmt = $conn->prepare($delete_checklists);
        $stmt->bind_param("i", $habito_id);
        $stmt->execute();
        $stmt->close();

        // Depois remove o Rotina
        $delete = "DELETE FROM habito WHERE id_habito = ? AND id_user = ?";
        $stmt = $conn->prepare($delete);
        $stmt->bind_param("ii", $habito_id, $user_id);
        if ($stmt->execute()) {
            $mensagem = "✅ Rotina removida com sucesso!";
        } else {
            $mensagem = "❌ Erro ao remover Rotina.";
        }
        $stmt->close();
    } elseif ($acao === 'editar_meta_agua') {
        $nova_meta = floatval($_POST['meta_agua'] ?? 3.0);

        try {
            // Verifica se já existe meta para o usuário
            $check = "SELECT id FROM meta_usuario WHERE id_user = ? AND tipo = 'agua'";
            $stmt = $conn->prepare($check);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Atualiza meta existente
                $update = "UPDATE meta_usuario SET valor = ? WHERE id_user = ? AND tipo = 'agua'";
                $stmt2 = $conn->prepare($update);
                $stmt2->bind_param("di", $nova_meta, $user_id);
                if ($stmt2->execute()) {
                    $mensagem = "✅ Meta de água atualizada com sucesso!";
                } else {
                    $mensagem = "❌ Erro ao atualizar meta. Certifique-se de que a tabela 'meta_usuario' existe.";
                }
                $stmt2->close();
            } else {
                // Cria nova meta
                $insert = "INSERT INTO meta_usuario (id_user, tipo, valor) VALUES (?, 'agua', ?)";
                $stmt2 = $conn->prepare($insert);
                $stmt2->bind_param("id", $user_id, $nova_meta);
                if ($stmt2->execute()) {
                    $mensagem = "✅ Meta de água definida com sucesso!";
                } else {
                    $mensagem = "❌ Erro ao definir meta. Certifique-se de que a tabela 'meta_usuario' existe.";
                }
                $stmt2->close();
            }
            $stmt->close();
        } catch (Exception $e) {
            $mensagem = "❌ Erro: A tabela 'meta_usuario' não existe. Execute o arquivo 'sql/criar_tabela_meta.sql' no banco de dados.";
        }
    } elseif ($acao === 'editar_meta_calorias') {
        $nova_meta_cal = floatval($_POST['meta_calorias'] ?? 2000);

        try {
            $check = "SELECT id FROM meta_usuario WHERE id_user = ? AND tipo = 'calorias'";
            $stmt = $conn->prepare($check);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $update = "UPDATE meta_usuario SET valor = ? WHERE id_user = ? AND tipo = 'calorias'";
                $stmt2 = $conn->prepare($update);
                $stmt2->bind_param("di", $nova_meta_cal, $user_id);
                if ($stmt2->execute()) {
                    $mensagem = "✅ Meta de calorias atualizada com sucesso!";
                } else {
                    $mensagem = "❌ Erro ao atualizar meta de calorias.";
                }
                $stmt2->close();
            } else {
                $insert = "INSERT INTO meta_usuario (id_user, tipo, valor) VALUES (?, 'calorias', ?)";
                $stmt2 = $conn->prepare($insert);
                $stmt2->bind_param("id", $user_id, $nova_meta_cal);
                if ($stmt2->execute()) {
                    $mensagem = "✅ Meta de calorias definida com sucesso!";
                } else {
                    $mensagem = "❌ Erro ao definir meta de calorias.";
                }
                $stmt2->close();
            }
            $stmt->close();
        } catch (Exception $e) {
            $mensagem = "❌ Erro ao modificar a meta de calorias.";
        }
    }
}

// Buscar dados de água de hoje
$agua_hoje = 0;
$agua_meta = 3.0; // Meta padrão de 3L

// Buscar meta personalizada do usuário
try {
    $sql_meta = "SELECT valor FROM meta_usuario WHERE id_user = ? AND tipo = 'agua'";
    $stmt = $conn->prepare($sql_meta);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $agua_meta = floatval($row['valor']);
        }
        $stmt->close();
    }
} catch (Exception $e) {
    // Tabela não existe ainda, usar valor padrão
    $agua_meta = 3.0;
}

// Buscar meta de calorias do usuário
$calorias_meta = 2000; // Valor padrão
try {
    $sql_meta_cal = "SELECT valor FROM meta_usuario WHERE id_user = ? AND tipo = 'calorias'";
    $stmt_cal = $conn->prepare($sql_meta_cal);
    if ($stmt_cal) {
        $stmt_cal->bind_param("i", $user_id);
        $stmt_cal->execute();
        $result_cal = $stmt_cal->get_result();
        if ($row_cal = $result_cal->fetch_assoc()) {
            $calorias_meta = floatval($row_cal['valor']);
        }
        $stmt_cal->close();
    }
} catch (Exception $e) {
    // Tabela não existe ainda, usar valor padrão
}

// Buscar consumo de hoje
$sql_agua = "SELECT COALESCE(SUM(quantidade), 0) as total FROM agua WHERE id_user = ? AND data = CURDATE()";
$stmt = $conn->prepare($sql_agua);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $agua_hoje = floatval($row['total']);
}
$stmt->close();

// Buscar peso mais recente
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

// Buscar calorias de hoje
$calorias_hoje = 0;
$sql_calorias = "SELECT COALESCE(SUM(calorias), 0) as total FROM alimentacao WHERE id_user = ? AND data = CURDATE()";
$stmt = $conn->prepare($sql_calorias);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $calorias_hoje = floatval($row['total']);
}
$stmt->close();

// Buscar histórico de refeições de hoje
$refeicoes_hoje = [];
$sql_refeicoes = "SELECT id, refeicao, descricao, calorias, proteinas, carbs FROM alimentacao WHERE id_user = ? AND data = CURDATE() ORDER BY id DESC";
$stmt = $conn->prepare($sql_refeicoes);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_refeicoes = $stmt->get_result();
while ($row = $result_refeicoes->fetch_assoc()) {
    $refeicoes_hoje[] = $row;
}
$stmt->close();

// Buscar Rotinas do usuário
$habitos = [];
$sql_habitos = "SELECT id_habito, descricao, meta_diaria, tipo FROM habito WHERE id_user = ? ORDER BY id_habito DESC";
$stmt = $conn->prepare($sql_habitos);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $habitos[] = $row;
}
$stmt->close();

// Buscar checklists diários de hoje (com JOIN para pegar dados do Rotina)
$checklists = [];
$data_hoje = date('Y-m-d');
$sql_checklist = "SELECT c.id_checklist, c.id_habito, c.data, c.concluido, h.descricao, h.meta_diaria, h.tipo 
                  FROM checklist_diario c 
                  INNER JOIN habito h ON c.id_habito = h.id_habito 
                  WHERE h.id_user = ? AND c.data = ? 
                  ORDER BY c.id_checklist DESC";
$stmt = $conn->prepare($sql_checklist);
$stmt->bind_param("is", $user_id, $data_hoje);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $checklists[] = $row;
}
$stmt->close();

// ============================================================
// DAY STREAK - Lógica completa
// ============================================================
// Garante que a tabela day_streak existe
$conn->query("CREATE TABLE IF NOT EXISTS day_streak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    data_streak DATE NOT NULL,
    desafios_concluidos INT NOT NULL DEFAULT 0,
    streak_valido TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY unique_user_data (id_user, data_streak),
    FOREIGN KEY (id_user) REFERENCES user(id_user) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Total de desafios cadastrados do utilizador
$total_habitos_cadastrados = count($habitos);

// Contar desafios concluídos HOJE para validar o streak do dia
$desafios_concluidos_hoje = 0;
$total_desafios_hoje = 0;
foreach ($checklists as $cl) {
    $total_desafios_hoje++;
    if ($cl['concluido']) $desafios_concluidos_hoje++;
}

// Atualiza/insere o registo de streak de hoje se tiver dados
if ($total_desafios_hoje > 0) {
    $streak_hoje_valido = ($desafios_concluidos_hoje >= 5 && $total_habitos_cadastrados >= 5) ? 1 : 0;
    $stmt_s = $conn->prepare("INSERT INTO day_streak (id_user, data_streak, desafios_concluidos, streak_valido)
        VALUES (?, CURDATE(), ?, ?)
        ON DUPLICATE KEY UPDATE desafios_concluidos = VALUES(desafios_concluidos), streak_valido = VALUES(streak_valido)");
    $stmt_s->bind_param("iii", $user_id, $desafios_concluidos_hoje, $streak_hoje_valido);
    $stmt_s->execute();
    $stmt_s->close();
}

// Calcular streak atual (dias consecutivos VÁLIDOS até hoje)
$streak_atual = 0;
$data_check = new DateTime('today');
while (true) {
    $data_str = $data_check->format('Y-m-d');
    $stmt_s = $conn->prepare("SELECT streak_valido FROM day_streak WHERE id_user = ? AND data_streak = ?");
    $stmt_s->bind_param("is", $user_id, $data_str);
    $stmt_s->execute();
    $res_s = $stmt_s->get_result();
    $row_s = $res_s->fetch_assoc();
    $stmt_s->close();

    if ($row_s && $row_s['streak_valido'] == 1) {
        $streak_atual++;
        $data_check->modify('-1 day');
    } else {
        break;
    }
}

// Histórico dos últimos 60 dias (para o mapa de calor)
$historico_streak = [];
$stmt_h = $conn->prepare("SELECT data_streak, desafios_concluidos, streak_valido
    FROM day_streak WHERE id_user = ? AND data_streak >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
    ORDER BY data_streak ASC");
$stmt_h->bind_param("i", $user_id);
$stmt_h->execute();
$res_h = $stmt_h->get_result();
while ($row_h = $res_h->fetch_assoc()) {
    $historico_streak[$row_h['data_streak']] = $row_h;
}
$stmt_h->close();

// Desafios concluídos nos últimos 30 dias (para a lista)
$desafios_concluidos_lista = [];
$stmt_dc = $conn->prepare(
    "SELECT c.data, h.descricao, h.tipo, COUNT(*) as total_concluidos
     FROM checklist_diario c
     INNER JOIN habito h ON c.id_habito = h.id_habito
     WHERE h.id_user = ? AND c.concluido = 1 AND c.data >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY c.data, h.descricao, h.tipo
     ORDER BY c.data DESC"
);
$stmt_dc->bind_param("i", $user_id);
$stmt_dc->execute();
$res_dc = $stmt_dc->get_result();
while ($row_dc = $res_dc->fetch_assoc()) {
    $desafios_concluidos_lista[] = $row_dc;
}
$stmt_dc->close();


// Calcular melhor streak via PHP
$stmt_ms = $conn->prepare("SELECT desafios_concluidos FROM day_streak 
    WHERE id_user = ? AND streak_valido = 1 ORDER BY data_streak");
$stmt_ms->bind_param("i", $user_id);
$stmt_ms->execute();
$res_ms = $stmt_ms->get_result();
$all_valid_days = [];
while ($r = $res_ms->fetch_assoc()) {
    $all_valid_days[] = $r;
}
$stmt_ms->close();
// Calcular melhor streak via PHP
$melhor_streak = 0;
$stmt_ms2 = $conn->prepare("SELECT data_streak, streak_valido FROM day_streak WHERE id_user = ? ORDER BY data_streak");
$stmt_ms2->bind_param("i", $user_id);
$stmt_ms2->execute();
$res_ms2 = $stmt_ms2->get_result();
$all_streak_days = [];
while ($r = $res_ms2->fetch_assoc()) {
    $all_streak_days[] = $r;
}
$stmt_ms2->close();
$run = 0;
foreach ($all_streak_days as $sd) {
    if ($sd['streak_valido'] == 1) {
        $run++;
        if ($run > $melhor_streak) $melhor_streak = $run;
    } else {
        $run = 0;
    }
}

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

// Buscando Histórico de Água
$historico_agua = [];
$sql_hist_agua = "SELECT data, quantidade FROM agua WHERE id_user = ? AND data >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ORDER BY data DESC";
$stmt_ha = $conn->prepare($sql_hist_agua);
$stmt_ha->bind_param("i", $user_id);
$stmt_ha->execute();
$res_ha = $stmt_ha->get_result();
while ($row = $res_ha->fetch_assoc()) {
    $historico_agua[] = $row;
}
$stmt_ha->close();

// Buscando Histórico de Peso
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

// Buscando Histórico Geral de Alimentação (totais por dia)
$historico_alim_geral = [];
$sql_hist_alim = "SELECT data, SUM(calorias) as tot_calorias, SUM(proteinas) as tot_prot, SUM(carbs) as tot_carbs 
                  FROM alimentacao WHERE id_user = ? AND data >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                  GROUP BY data ORDER BY data DESC";
$stmt_halp = $conn->prepare($sql_hist_alim);
$stmt_halp->bind_param("i", $user_id);
$stmt_halp->execute();
$res_halp = $stmt_halp->get_result();
while ($row = $res_halp->fetch_assoc()) {
    $historico_alim_geral[] = $row;
}
$stmt_halp->close();

// Dados para os Gráficos
$datas_peso_js = [];
$valores_peso_js = [];
$hist_peso_reverse = array_reverse($historico_peso);
foreach ($hist_peso_reverse as $hp) {
    if (isset($hp['data']) && isset($hp['peso'])) {
        $datas_peso_js[] = date('d/m', strtotime($hp['data']));
        $valores_peso_js[] = floatval($hp['peso']);
    }
}

// Exercícios com histórico para o gráfico de força
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
    // Ex: Sem. 01/10
    $datas_forca_js[] = 'Sem. ' . date('d/m', strtotime($fg['inicio_semana']));
    $valores_forca_js[] = floatval($fg['volume_total']);
}

$conn->close();

?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progresso - BerserkFit</title>
    <link rel="stylesheet" href="css/global.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/progresso.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Syne:wght@700;800&display=swap"
        rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <h2>Meu Progresso</h2>
            <p>Acompanhe a sua evolução diária.</p>
        </div>
    </header>

    <main>
        <div class="progresso-container">
            <?php if ($mensagem != ""): ?>
                <div class="mensagem <?php echo strpos($mensagem, '✅') !== false ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($mensagem); ?>
                </div>
            <?php endif; ?>

            <!-- Seção Evolução Visual (Gráficos) -->
            <div class="categoria-item fade-in-element">
                <div class="categoria-header" onclick="toggleCategoria(this)">
                    <h3><i class="fas fa-chart-area"></i> Evolução Visual</h3>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="categoria-content">
                    <div class="grid-progresso" style="grid-template-columns: 1fr;">
                        <div class="card-progresso chart-large">
                            <h4>Evolução do Peso Corporal</h4>
                            <div class="chart-container" style="height: 250px; width: 100%;">
                                <canvas id="weightChart"></canvas>
                            </div>
                        </div>

                        <!-- NOVO GRÁFICO: Volume Global Levantado -->
                        <div class="card-progresso chart-large">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; gap: 10px; flex-wrap: wrap;">
                                <h4 style="margin:0;">Volume Global Levantado (Por Semana)</h4>
                                <?php if($aumento_percentual !== 0): ?>
                                    <div style="font-size: 0.9em; font-weight: bold; color: <?= $cor_aumento ?>; background: rgba(0,0,0,0.1); padding: 4px 8px; border-radius: 6px; border: 1px solid <?= $cor_aumento ?>40;">
                                        <?= $icone_aumento ?> <?= number_format(abs($aumento_percentual), 1) ?>% vs semana anterior
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="chart-container" style="height: 250px; width: 100%;">
                                <?php if (empty($historico_forca_global)): ?>
                                    <div style="display:flex; align-items:center; justify-content:center; height:100%; color:#94a3b8; font-size:0.9em; text-align:center; padding: 0 20px;">
                                        <p>Sem registos de treino suficientes. Conclui treinos com peso para veres o teu aumento de força!</p>
                                    </div>
                                <?php else: ?>
                                    <canvas id="globalStrengthChart"></canvas>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card-progresso chart-large">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; gap: 10px;">
                                <h4 style="margin:0;">Força por Exercício</h4>
                                <select id="select-exercicio-grafico" class="input-modern" style="padding: 4px 8px; font-size: 0.85em; width: auto;" onchange="updateStrengthChart(this.value)">
                                    <option value="">Selecionar exercício...</option>
                                    <?php foreach($exercicios_com_hist as $ex_h): ?>
                                        <option value="<?= $ex_h['id_exercicio'] ?>"><?= htmlspecialchars($ex_h['nome_exercicio']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="chart-container" style="height: 250px; width: 100%;">
                                <canvas id="strengthChart"></canvas>
                                <div id="no-strength-data" style="display:flex; align-items:center; justify-content:center; height:100%; color:#94a3b8; font-size:0.9em;">
                                    Selecione um exercício para ver o gráfico
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seção Água -->
            <div class="categoria-item fade-in-element">
                <div class="categoria-header" onclick="toggleCategoria(this)">
                    <h3><i class="fas fa-tint"></i> Água</h3>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="categoria-content">
                    <div class="grid-progresso">
                        <div class="card-progresso">
                            <h4>Consumo de Hoje</h4>
                            <div class="valor-destaque">
                                <?php echo number_format($agua_hoje, 1); ?><span class="unidade">L</span>
                            </div>
                            <div class="progresso-bar">
                                <div class="progresso"
                                    style="width: <?php echo min(100, ($agua_meta > 0 ? ($agua_hoje / $agua_meta) * 100 : 0)); ?>%;">
                                </div>
                            </div>
                            <div class="meta-header">
                                <div class="progresso-percentual">
                                    Meta: <?php echo $agua_meta; ?>L
                                    (<?php echo number_format(($agua_meta > 0 ? ($agua_hoje / $agua_meta) * 100 : 0), 0); ?>%)
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <button type="button" class="btn-editar-meta" onclick="abrirModalMeta()">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button type="button" class="btn-editar-meta" onclick="abrirModalHistoricoAgua()" style="color: var(--cor-intermedia);">
                                        <i class="fas fa-history"></i> Histórico
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-progresso">
                            <h4>Adicionar Água</h4>
                            <form method="POST" class="form-progresso">
                                <input type="hidden" name="acao" value="adicionar_agua">
                                <div class="form-group">
                                    <label for="quantidade_agua">Quantidade (L)</label>
                                    <input type="number" id="quantidade_agua" name="quantidade" step="0.1" min="0"
                                        max="5" value="0.5" required>
                                </div>
                                <div class="form-group">
                                    <label for="data_agua">Data</label>
                                    <input type="date" id="data_agua" name="data" value="<?php echo date('Y-m-d'); ?>"
                                        required>
                                </div>
                                <button type="submit" class="btn-adicionar">
                                    <i class="fas fa-plus"></i> Adicionar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seção Peso -->
            <div class="categoria-item fade-in-element">
                <div class="categoria-header" onclick="toggleCategoria(this)">
                    <h3><i class="fas fa-weight"></i> Peso</h3>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="categoria-content">
                    <div class="grid-progresso">
                        <div class="card-progresso">
                            <h4>Peso Atual</h4>
                            <?php if ($peso_atual): ?>
                                <div class="valor-destaque">
                                    <?php echo number_format($peso_atual, 1); ?><span class="unidade">kg</span>
                                </div>
                                <div class="progresso-percentual" style="display: flex; justify-content: space-between; align-items: center; width: 100%; margin-top: 10px;">
                                    <span>Registado em: <?php echo date('d/m/Y', strtotime($data_peso)); ?></span>
                                    <button type="button" class="btn-editar-meta" onclick="abrirModalHistoricoPeso()" style="color: var(--cor-intermedia);">
                                        <i class="fas fa-history"></i> Histórico
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="valor-destaque">
                                    --<span class="unidade">kg</span>
                                </div>
                                <div class="progresso-percentual">
                                    Nenhum registo ainda
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-progresso">
                            <h4>Registar Peso</h4>
                            <form method="POST" class="form-progresso">
                                <input type="hidden" name="acao" value="adicionar_peso">
                                <div class="form-group">
                                    <label for="peso">Peso (kg)</label>
                                    <input type="number" id="peso" name="peso" step="0.1" min="0" max="500" required>
                                </div>
                                <div class="form-group">
                                    <label for="data_peso">Data</label>
                                    <input type="date" id="data_peso" name="data" value="<?php echo date('Y-m-d'); ?>"
                                        required>
                                </div>
                                <button type="submit" class="btn-adicionar">
                                    <i class="fas fa-save"></i> Registar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seção Alimentação -->
            <div class="categoria-item fade-in-element">
                <div class="categoria-header" onclick="toggleCategoria(this)">
                    <h3><i class="fas fa-utensils"></i> Alimentação</h3>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="categoria-content">
                    <div class="grid-progresso">
                        <div class="card-progresso">
                            <h4>Calorias de Hoje</h4>
                            <div class="valor-destaque">
                                <?php echo number_format($calorias_hoje, 0); ?><span class="unidade">kcal</span>
                            </div>
                            <div class="progresso-bar">
                                <div class="progresso"
                                    style="width: <?php echo min(100, ($calorias_meta > 0 ? ($calorias_hoje / $calorias_meta) * 100 : 0)); ?>%;">
                                </div>
                            </div>
                            <div class="meta-header">
                                <div class="progresso-percentual">
                                    Meta: <?php echo number_format($calorias_meta, 0); ?> kcal
                                    (<?php echo number_format(($calorias_meta > 0 ? ($calorias_hoje / $calorias_meta) * 100 : 0), 0); ?>%)
                                </div>
                                <button type="button" class="btn-editar-meta" onclick="abrirModalMetaCalorias()">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                            </div>

                            <!-- Histórico de Refeições de Hoje -->
                            <div class="historico-alimentacao"
                                style="margin-top: 25px; border-top: 1px solid var(--cor-secundaria); padding-top: 15px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                    <h5 style="margin: 0; color: var(--cor-texto);"><i class="fas fa-history"
                                            style="color: var(--cor-intermedia);"></i> O que comi hoje</h5>
                                    <button type="button" class="btn-editar-meta" onclick="abrirModalHistoricoAlimentacao()" style="color: var(--cor-intermedia); font-size: 0.85em;">
                                        <i class="fas fa-calendar-alt"></i> Histórico Geral
                                    </button>
                                </div>

                                <?php if (!empty($refeicoes_hoje)): ?>
                                    <div class="lista-historico"
                                        style="display: flex; flex-direction: column; gap: 10px; max-height: 300px; overflow-y: auto; padding-right: 5px;">
                                        <?php foreach ($refeicoes_hoje as $ref): ?>
                                            <div class="item-historico"
                                                style="background: var(--cor-primaria); border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; display: flex; justify-content: space-between; align-items: center;">
                                                <div style="flex: 1; min-width: 0; padding-right: 10px;">
                                                    <h6 style="margin: 0 0 4px 0; color: var(--cor-destaque); font-size: 14px;">
                                                        <?php echo htmlspecialchars($ref['refeicao']); ?>
                                                    </h6>
                                                    <p
                                                        style="margin: 0 0 4px 0; font-size: 13px; color: #4b5563; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                        <?php echo htmlspecialchars($ref['descricao']); ?>
                                                    </p>
                                                    <div style="display: flex; gap: 8px; font-size: 11px; color: #6b7280;">
                                                        <span title="Proteínas"><i class="fas fa-drumstick-bite"
                                                                style="color: #ef4444;"></i>
                                                            <?php echo number_format($ref['proteinas'], 1); ?>g</span>
                                                        <span title="Hidratos de Carbono"><i class="fas fa-bread-slice"
                                                                style="color: #eab308;"></i>
                                                            <?php echo number_format($ref['carbs'], 1); ?>g</span>
                                                    </div>
                                                </div>
                                                <div
                                                    style="text-align: right; background: #fff7ed; padding: 6px 12px; border-radius: 6px; border: 1px solid #ffedd5; flex-shrink: 0;">
                                                    <span style="display: block; font-weight: bold; color: #ea580c;">
                                                        <?php echo number_format($ref['calorias'], 0); ?>
                                                    </span>
                                                    <span style="font-size: 11px; color: #ea580c;">kcal</span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p style="text-align: center; color: #9ca3af; font-size: 14px; padding: 20px 0;">
                                        <i class="fas fa-utensils"
                                            style="display: block; font-size: 24px; margin-bottom: 8px; opacity: 0.5;"></i>
                                        Ainda não registaste nenhuma refeição hoje.
                                    </p>
                                <?php endif; ?>
                            </div>

                        </div>
                        <div class="card-progresso">
                            <h4>Registar Refeição</h4>

                            <!-- Barra de pesquisa da Open Food Facts com Imagens -->
                            <div class="form-group"
                                style="margin-bottom: 20px; border-bottom: 2px solid var(--cor-secundaria); padding-bottom: 15px;">
                                <label for="food-search-progresso"><i class="fas fa-search"></i> Pesquisar Alimento
                                    (Preenchimento Automático)</label>
                                <div style="display: flex; gap: 8px; align-items: stretch;">
                                    <input type="text" id="food-search-progresso"
                                        placeholder="Ex: Banana, Leite, Pão..."
                                        style="flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 8px; min-width: 0;">
                                    <button type="button" onclick="searchFoodProgresso()"
                                        style="background: var(--cor-destaque); color: var(--cor-primaria); border: none; padding: 10px 12px; border-radius: 8px; cursor: pointer; flex-shrink: 0;"
                                        title="Pesquisar por nome"><i class="fas fa-search"></i></button>
                                    <button type="button" onclick="startBarcodeScanner()"
                                        style="background: var(--cor-intermedia); color: var(--cor-destaque); border: none; padding: 10px 12px; border-radius: 8px; cursor: pointer; font-weight: bold; flex-shrink: 0; display: flex; align-items: center; gap: 4px;"
                                        title="Ler Código de Barras"><i class="fas fa-barcode"></i> <span class="ler-text">LER</span></button>
                                </div>
                                <div id="barcode-reader-container"
                                    style="display: none; margin-top: 15px; background: #0f0721; border-radius: 12px; overflow: hidden; border: 2px solid #b8a8f5; padding: 12px; position: relative;">
                                    <div
                                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                        <div>
                                            <h5 style="margin: 0 0 2px; color: #b8a8f5;"><i class="fas fa-barcode"></i> Scanner de Código de Barras</h5>
                                            <p style="margin:0; font-size:11px; color:#94a3b8;">Aponte a câmara traseira ao código — mantenha-o <strong style="color:#fff;">horizontal</strong> e com boa iluminação</p>
                                        </div>
                                        <button type="button" onclick="stopBarcodeScanner()"
                                            style="background: rgba(239,68,68,0.15); border: 1px solid #ef4444; border-radius: 6px; padding: 6px 10px; cursor: pointer; color: #ef4444; font-size: 0.85em; font-weight:600;">
                                            <i class="fas fa-times"></i> Fechar
                                        </button>
                                    </div>
                                    <div id="reader" style="width: 100%; min-height: 200px; border-radius: 8px; overflow: hidden;"></div>
                                </div>
                                <div id="food-results-progresso"
                                    style="margin-top: 10px; max-height: 300px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px;">
                                    <!-- Resultados aparecerão aqui -->
                                </div>

                                <!-- Container para Lista de Selecionados -->
                                <div id="selected-foods-container"
                                    style="margin-top: 15px; display: none; flex-direction: column; gap: 10px;">
                                    <h5 style="margin: 0; color: var(--cor-destaque);"><i
                                            class="fas fa-shopping-basket"></i> Alimentos Selecionados:</h5>
                                    <div id="selected-foods-list"
                                        style="display: flex; flex-direction: column; gap: 8px;"></div>
                                    <div
                                        style="display: flex; justify-content: space-between; align-items: center; background: #e2e8f0; padding: 10px; border-radius: 8px;">
                                        <div style="display: flex; gap: 10px;">
                                            <div style="flex: 1; text-align: center;">
                                                <strong
                                                    style="display: block; font-size: 11px; color: #666;">Calorias</strong>
                                                <span id="selected-foods-total-cals"
                                                    style="color: #ea580c; font-weight: bold; font-size: 1.1em;">0
                                                    kcal</span>
                                            </div>
                                            <div
                                                style="flex: 1; text-align: center; border-left: 1px solid #cbd5e1; padding-left: 10px;">
                                                <strong
                                                    style="display: block; font-size: 11px; color: #666;">Proteínas</strong>
                                                <span id="selected-foods-total-prot"
                                                    style="color: #ef4444; font-weight: bold; font-size: 1.1em;">0
                                                    g</span>
                                            </div>
                                            <div
                                                style="flex: 1; text-align: center; border-left: 1px solid #cbd5e1; padding-left: 10px;">
                                                <strong
                                                    style="display: block; font-size: 11px; color: #666;">Hidratos</strong>
                                                <span id="selected-foods-total-carbs"
                                                    style="color: #eab308; font-weight: bold; font-size: 1.1em;">0
                                                    g</span>
                                            </div>
                                        </div>
                                        <button type="button" onclick="confirmSelectedFoods()"
                                            style="background: #22c55e; color: white; border: none; padding: 10px; border-radius: 8px; cursor: pointer; font-weight: 600; text-align: center;"><i
                                                class="fas fa-check"></i> Confirmar</button>
                                    </div>
                                </div>

                                <form method="POST" class="form-progresso">
                                    <input type="hidden" name="acao" value="adicionar_alimentacao">
                                    <div class="form-group">
                                        <label for="refeicao">Refeição</label>
                                        <select id="refeicao" name="refeicao" required>
                                            <option value="Café da Manhã">Pequeno-almoço</option>
                                            <option value="Lanche da Manhã">Lanche da Manhã</option>
                                            <option value="Almoço">Almoço</option>
                                            <option value="Lanche da Tarde">Lanche da Tarde</option>
                                            <option value="Jantar">Jantar</option>
                                            <option value="Ceia">Ceia</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="descricao">Descrição</label>
                                        <input type="text" id="descricao" name="descricao"
                                            placeholder="Ex: Arroz, frango, salada">
                                    </div>
                                    <div class="form-row-nutri" style="display: flex; gap: 10px; margin-bottom: 15px;">
                                        <div class="form-group-sub" style="flex: 1; display: flex; flex-direction: column; gap: 5px;">
                                            <label for="calorias" style="font-size: 0.85em; font-weight: 600; color: var(--cor-texto);">Calorias</label>
                                            <input type="number" id="calorias" name="calorias" step="1" min="0" required style="width: 100%; border-radius: 8px; border: 1px solid var(--cor-secundaria); padding: 10px;">
                                        </div>
                                        <div class="form-group-sub" style="flex: 1; display: flex; flex-direction: column; gap: 5px;">
                                            <label for="proteinas" style="font-size: 0.85em; font-weight: 600; color: var(--cor-texto);">Proteínas (g)</label>
                                            <input type="number" id="proteinas" name="proteinas" step="0.1" min="0" value="0" style="width: 100%; border-radius: 8px; border: 1px solid var(--cor-secundaria); padding: 10px;">
                                        </div>
                                        <div class="form-group-sub" style="flex: 1; display: flex; flex-direction: column; gap: 5px;">
                                            <label for="carbs" style="font-size: 0.85em; font-weight: 600; color: var(--cor-texto);">Hidratos (g)</label>
                                            <input type="number" id="carbs" name="carbs" step="0.1" min="0" value="0" style="width: 100%; border-radius: 8px; border: 1px solid var(--cor-secundaria); padding: 10px;">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="data_alimentacao">Data</label>
                                        <input type="date" id="data_alimentacao" name="data"
                                            value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <button type="submit" class="btn-adicionar">
                                        <i class="fas fa-plus"></i> Adicionar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seção Desafios (Checklists Diários) -->
            <div class="categoria-item fade-in-element">
                    <div class="categoria-header" onclick="toggleCategoria(this)">
                        <h3><i class="fas fa-tasks"></i> Desafios Diários</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="categoria-content">

                        <!-- Formulário para criar nova Rotina -->

                        <div class="card-progresso" style="margin-bottom: 25px;">
                            <h4>Criar Nova Rotina</h4>
                            <form method="POST" class="form-progresso">
                                <input type="hidden" name="acao" value="criar_habito">
                                <div class="form-group">
                                    <label for="descricao_habito">Descrição da Rotina</label>
                                    <input type="text" id="descricao_habito" name="descricao"
                                        placeholder="Ex: Beber 3L de água" required>
                                </div>
                                <div class="form-group">
                                    <label for="tipo_habito">Tipo (opcional)</label>
                                    <input type="text" id="tipo_habito" name="tipo"
                                        placeholder="Ex: Saúde, Exercício, Alimentação">
                                </div>
                                <div class="form-group">
                                    <label for="meta_diaria">Meta Diária (opcional)</label>
                                    <input type="number" id="meta_diaria" name="meta_diaria" step="0.1" min="0"
                                        placeholder="Ex: 3.0">
                                </div>
                                <button type="submit" class="btn-adicionar">
                                    <i class="fas fa-plus"></i> Criar Rotina
                                </button>
                            </form>
                        </div>

                        <!-- Lista de Rotinas/desafios permanentes -->
                        <?php if (!empty($habitos)): ?>
                            <div style="margin-bottom: 25px;">
                                <h4 style="margin-bottom: 15px;">Meus Desafios</h4>
                                <?php foreach ($habitos as $habito): ?>
                                    <div class="checklist-item"
                                        style="background: var(--cor-primaria); border: 1px solid var(--cor-secundaria);">
                                        <div class="checklist-content" style="flex: 1;">
                                            <h5 class="checklist-titulo"><?php echo htmlspecialchars($habito['descricao']); ?>
                                            </h5>
                                            <?php if (!empty($habito['tipo'])): ?>
                                                <p class="checklist-descricao"
                                                    style="font-size: 0.85em; color: var(--cor-intermedia);">
                                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($habito['tipo']); ?>
                                                </p>
                                            <?php endif; ?>
                                            <?php if (!empty($habito['meta_diaria'])): ?>
                                                <p class="checklist-descricao" style="font-size: 0.85em;">
                                                    Meta: <?php echo $habito['meta_diaria']; ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="checklist-actions">
                                            <form method="POST" style="display: inline;"
                                                onsubmit="return confirm('Tem a certeza que deseja remover este desafio?');">
                                                <input type="hidden" name="acao" value="deletar_habito">
                                                <input type="hidden" name="habito_id"
                                                    value="<?php echo $habito['id_habito']; ?>">
                                                <button type="submit" class="btn-icon delete" title="Remover Desafio">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="text-align: center; color: var(--cor-texto); opacity: 0.7; padding: 20px;">
                                Nenhum desafio criado ainda. Crie uma Rotina acima ou complete o onboarding para receber
                                desafios
                                automáticos!
                            </p>
                        <?php endif; ?>

                    </div>
                </div>

            <!-- ===== NOVA ABA: DAY STREAK ===== -->

            <div class="categoria-item fade-in-element">
                <div class="categoria-header" onclick="toggleCategoria(this)">
                    <h3><i class="fas fa-fire" style="color:#f97316;"></i> Day Streak</h3>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="categoria-content">

                    <?php if ($total_habitos_cadastrados < 5): ?>
                    <div class="streak-aviso">
                        <div class="streak-aviso-icon">⚠️</div>
                        <div>
                            <strong>Atenção — Streak Inativo</strong>
                            <p>Para o Day Streak contar, precisas de ter pelo menos <strong>5 desafios cadastrados</strong> e concluir 5 ou mais no mesmo dia.<br>
                            Atualmente tens <strong><?= $total_habitos_cadastrados ?> desafio(s)</strong>. Vai aos <em>Desafios Diários</em> e adiciona mais <?= max(0, 5 - $total_habitos_cadastrados) ?>!</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="streak-aviso streak-aviso-ok">
                        <div class="streak-aviso-icon">✅</div>
                        <div>
                            <strong>Tens <?= $total_habitos_cadastrados ?> desafios cadastrados!</strong>
                            <p>Conclui <strong>5 ou mais desafios por dia</strong> para manter o teu streak a crescer. Bom treino! 💪</p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Cards de estatísticas -->
                    <div class="streak-stats-grid">
                        <div class="streak-stat-card">
                            <div class="streak-stat-fire">🔥</div>
                            <div class="streak-stat-valor"><?= $streak_atual ?></div>
                            <div class="streak-stat-label">Streak Atual</div>
                            <div class="streak-stat-sub">dias seguidos</div>
                        </div>
                        <div class="streak-stat-card">
                            <div class="streak-stat-fire">🏆</div>
                            <div class="streak-stat-valor"><?= $melhor_streak ?></div>
                            <div class="streak-stat-label">Melhor Streak</div>
                            <div class="streak-stat-sub">máximo histórico</div>
                        </div>
                        <div class="streak-stat-card">
                            <div class="streak-stat-fire">✅</div>
                            <div class="streak-stat-valor"><?= count(array_unique(array_column($desafios_concluidos_lista, 'data'))) ?></div>
                            <div class="streak-stat-label">Dias Ativos</div>
                            <div class="streak-stat-sub">últimos 30 dias</div>
                        </div>
                        <div class="streak-stat-card">
                            <div class="streak-stat-fire">📋</div>
                            <div class="streak-stat-valor"><?= $total_habitos_cadastrados ?></div>
                            <div class="streak-stat-label">Desafios</div>
                            <div class="streak-stat-sub">cadastrados</div>
                        </div>
                    </div>

                    <!-- Mapa de calor -->
                    <div class="card-progresso" style="margin-top:20px;">
                        <h4><i class="fas fa-calendar-check" style="color:var(--cor-destaque);"></i> Mapa de Atividade — Este Mês</h4>
                        <div class="heatmap-legenda">
                            <span><span class="heatmap-dot" style="background:#22c55e;"></span> Válido (≥5)</span>
                            <span><span class="heatmap-dot" style="background:#fbbf24;"></span> Parcial</span>
                            <span><span class="heatmap-dot" style="background:#e5e7eb;"></span> Inativo</span>
                        </div>
                        <div class="heatmap-grid">
                            <?php
                            $hoje_hm = new DateTime('today');
                            $inicio_hm = new DateTime('first day of this month');
                            $fim_hm = new DateTime('last day of this month');
                            $cur_hm = clone $inicio_hm;
                            while ($cur_hm <= $fim_hm):
                                $ds = $cur_hm->format('Y-m-d');
                                $label_hm = $cur_hm->format('d/m');
                                if (isset($historico_streak[$ds])) {
                                    $dia_hm = $historico_streak[$ds];
                                    if ($dia_hm['streak_valido']) {
                                        $cor_hm = '#22c55e';
                                        $title_hm = "✅ $label_hm — {$dia_hm['desafios_concluidos']} concluídos (válido)";
                                    } else {
                                        $cor_hm = '#fbbf24';
                                        $title_hm = "⚡ $label_hm — {$dia_hm['desafios_concluidos']} concluídos (parcial)";
                                    }
                                } else {
                                    $cor_hm = '#e5e7eb'; // cinza para vazios/futuros
                                    if ($cur_hm > $hoje_hm) {
                                        $title_hm = "📅 $label_hm — Futuro";
                                        // Pode querer cor levemente mais clara ou a mesma:
                                        $cor_hm = 'rgba(229, 231, 235, 0.4)';
                                    } else {
                                        $title_hm = "📅 $label_hm — sem atividade";
                                    }
                                }
                            ?>
                            <div class="heatmap-day" style="background:<?= $cor_hm ?>;" title="<?= htmlspecialchars($title_hm) ?>"></div>
                            <?php $cur_hm->modify('+1 day'); endwhile; ?>
                        </div>
                    </div>

                    <!-- Lista de desafios concluídos -->
                    <div class="card-progresso" style="margin-top:20px;">
                        <h4><i class="fas fa-medal" style="color:#f59e0b;"></i> Histórico — últimos 30 dias</h4>
                        <?php if (!empty($desafios_concluidos_lista)): ?>
                            <div class="desafios-concluidos-lista">
                                <?php
                                $agrupado_dc = [];
                                foreach ($desafios_concluidos_lista as $dc) {
                                    $agrupado_dc[$dc['data']][] = $dc;
                                }
                                foreach ($agrupado_dc as $data_dc => $items_dc):
                                    $is_valido_dc = isset($historico_streak[$data_dc]) && $historico_streak[$data_dc]['streak_valido'];
                                ?>
                                <div class="dc-dia-grupo">
                                    <div class="dc-dia-header">
                                        <span class="dc-data"><?= date('d/m/Y', strtotime($data_dc)) ?></span>
                                        <?php if ($is_valido_dc): ?>
                                            <span class="dc-badge-valido">🔥 Streak</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php foreach ($items_dc as $dc_item): ?>
                                    <div class="dc-item">
                                        <i class="fas fa-check-circle" style="color:#22c55e; flex-shrink:0;"></i>
                                        <span class="dc-descricao"><?= htmlspecialchars($dc_item['descricao']) ?></span>
                                        <?php if (!empty($dc_item['tipo'])): ?>
                                            <span class="dc-tipo-tag"><?= htmlspecialchars($dc_item['tipo']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div style="text-align:center; padding:30px; color:#9ca3af;">
                                <i class="fas fa-fire-alt" style="font-size:2rem; display:block; margin-bottom:10px; opacity:.4;"></i>
                                Nenhum desafio concluído nos últimos 30 dias.<br>
                                <small>Começa hoje a construir o teu streak! 💪</small>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
            <!-- ===== FIM DAY STREAK ===== -->

        </div>
    </main>

    <!-- Modal para editar meta de água -->
    <div id="modalMeta" class="modal" onclick="fecharModalMeta(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h4>Editar Meta de Água</h4>
                <button type="button" class="btn-fechar" onclick="fecharModalMeta(event)">&times;</button>
            </div>
            <form method="POST" class="form-progresso">
                <input type="hidden" name="acao" value="editar_meta_agua">
                <div class="form-group">
                    <label for="meta_agua">Meta Diária (L)</label>
                    <input type="number" id="meta_agua" name="meta_agua" step="0.1" min="0" max="10"
                        value="<?php echo $agua_meta; ?>" required>
                </div>
                <button type="submit" class="btn-adicionar">
                    <i class="fas fa-save"></i> Guardar Meta
                </button>
            </form>
        </div>
    </div>

    <!-- Modal para editar meta de calorias -->
    <div id="modalMetaCalorias" class="modal" onclick="fecharModalMetaCalorias(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h4>Editar Meta de Calorias</h4>
                <button type="button" class="btn-fechar" onclick="fecharModalMetaCalorias(event)">&times;</button>
            </div>
            <form method="POST" class="form-progresso">
                <input type="hidden" name="acao" value="editar_meta_calorias">
                <div class="form-group">
                    <label for="meta_calorias">Meta Diária (kcal)</label>
                    <input type="number" id="meta_calorias" name="meta_calorias" step="10" min="100" max="10000"
                        value="<?php echo $calorias_meta; ?>" required>
                </div>
                <button type="submit" class="btn-adicionar">
                    <i class="fas fa-save"></i> Guardar Meta
                </button>
            </form>
        </div>
    </div>

    <!-- Modais de Histórico -->
    <div id="modalHistoricoAgua" class="modal" onclick="fecharModalHistoricoAgua(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h4><i class="fas fa-tint" style="color:#60a5fa;"></i> Histórico de Água</h4>
                <button type="button" class="btn-fechar" onclick="fecharModalHistoricoAgua(event)">&times;</button>
            </div>
            <div style="max-height: 300px; overflow-y: auto; padding-right: 5px;">
                <?php if (!empty($historico_agua)): ?>
                    <?php foreach ($historico_agua as $ha): ?>
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(0,0,0,0.05); padding: 10px 0;">
                            <span style="font-weight: 600; color: var(--cor-texto);"><?= date('d/m/Y', strtotime($ha['data'])) ?></span>
                            <span style="color: #60a5fa; font-weight: bold;"><?= number_format($ha['quantidade'], 1) ?> L</span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align:center; color:#9ca3af; padding: 15px;">Sem dados de histórico.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="modalHistoricoPeso" class="modal" onclick="fecharModalHistoricoPeso(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h4><i class="fas fa-weight" style="color:var(--cor-destaque);"></i> Histórico de Peso</h4>
                <button type="button" class="btn-fechar" onclick="fecharModalHistoricoPeso(event)">&times;</button>
            </div>
            <div style="max-height: 300px; overflow-y: auto; padding-right: 5px;">
                <?php if (!empty($historico_peso)): ?>
                    <?php foreach ($historico_peso as $hp): ?>
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(0,0,0,0.05); padding: 10px 0;">
                            <span style="font-weight: 600; color: var(--cor-texto);"><?= date('d/m/Y', strtotime($hp['data'])) ?></span>
                            <span style="color: var(--cor-destaque); font-weight: bold;"><?= number_format($hp['peso'], 1) ?> kg</span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align:center; color:#9ca3af; padding: 15px;">Sem dados de histórico.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="modalHistoricoAlimentacao" class="modal" onclick="fecharModalHistoricoAlimentacao(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h4><i class="fas fa-utensils" style="color:#fb923c;"></i> Histórico Geral Alimentação</h4>
                <button type="button" class="btn-fechar" onclick="fecharModalHistoricoAlimentacao(event)">&times;</button>
            </div>
            <div style="max-height: 350px; overflow-y: auto; padding-right: 5px;">
                <?php if (!empty($historico_alim_geral)): ?>
                    <?php foreach ($historico_alim_geral as $hga): ?>
                        <div style="background: var(--cor-secundaria); border-radius: 8px; margin-bottom: 10px; padding: 12px; border: 1px solid #e5e7eb;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <strong style="color: var(--cor-destaque); font-size: 1.1em;"><?= date('d/m/Y', strtotime($hga['data'])) ?></strong>
                                <span style="background: #fff7ed; color: #ea580c; padding: 4px 8px; border-radius: 6px; font-weight: bold; font-size: 0.9em;"><?= number_format($hga['tot_calorias'], 0) ?> kcal</span>
                            </div>
                            <div style="display: flex; gap: 15px; font-size: 0.85em; color: #555;">
                                <span><i class="fas fa-drumstick-bite" style="color: #ef4444;"></i> <?= number_format($hga['tot_prot'], 1) ?>g Prot</span>
                                <span><i class="fas fa-bread-slice" style="color: #eab308;"></i> <?= number_format($hga['tot_carbs'], 1) ?>g Carbs</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align:center; color:#9ca3af; padding: 15px;">Sem dados de histórico de alimentação este mês.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleCategoria(header) {
            const content = header.nextElementSibling;
            const isActive = content.classList.contains('active');

            // Fecha todas as outras categorias
            document.querySelectorAll('.categoria-content').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelectorAll('.categoria-header').forEach(item => {
                item.classList.remove('active');
            });

            // Abre/fecha a categoria clicada
            if (!isActive) {
                content.classList.add('active');
                header.classList.add('active');
            }
        }

        function abrirModalMeta() {
            document.getElementById('modalMeta').classList.add('active');
        }

        function fecharModalMeta(event) {
            if (event) {
                event.stopPropagation();
            }
            document.getElementById('modalMeta').classList.remove('active');
        }

        function abrirModalMetaCalorias() {
            document.getElementById('modalMetaCalorias').classList.add('active');
        }

        function fecharModalMetaCalorias(event) {
            if (event) event.stopPropagation();
            document.getElementById('modalMetaCalorias').classList.remove('active');
        }

        // --- Funções dos Modais Histórico ---
        function abrirModalHistoricoAgua() { document.getElementById('modalHistoricoAgua').classList.add('active'); }
        function fecharModalHistoricoAgua(e) { if(e) e.stopPropagation(); document.getElementById('modalHistoricoAgua').classList.remove('active'); }

        function abrirModalHistoricoPeso() { document.getElementById('modalHistoricoPeso').classList.add('active'); }
        function fecharModalHistoricoPeso(e) { if(e) e.stopPropagation(); document.getElementById('modalHistoricoPeso').classList.remove('active'); }

        function abrirModalHistoricoAlimentacao() { document.getElementById('modalHistoricoAlimentacao').classList.add('active'); }
        function fecharModalHistoricoAlimentacao(e) { if(e) e.stopPropagation(); document.getElementById('modalHistoricoAlimentacao').classList.remove('active'); }

        // Fecha modal ao pressionar ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                fecharModalMeta();
                fecharModalMetaCalorias();
                fecharModalHistoricoAgua();
                fecharModalHistoricoPeso();
                fecharModalHistoricoAlimentacao();
            }
        });

        // Não abre nenhuma categoria por padrão, para manter a página limpa
        /*
        document.addEventListener('DOMContentLoaded', function () {
            const firstCategory = document.querySelector('.categoria-header');
            if (firstCategory) {
                toggleCategoria(firstCategory);
            }
        });
        */

        // ── Base de dados local de alimentos comuns (fallback imediato) ──
        const LOCAL_FOODS_DB = [
            // Carnes
            { name: 'Frango grelhado (peito)', cal: 165, prot: 31, carbs: 0, fat: 3.6, tags: ['frango','peito','grelhado','carne'] },
            { name: 'Frango cozido (peito)', cal: 155, prot: 30, carbs: 0, fat: 3.2, tags: ['frango','peito','cozido','carne'] },
            { name: 'Frango assado (coxa)', cal: 210, prot: 24, carbs: 0, fat: 12, tags: ['frango','coxa','assado','carne'] },
            { name: 'Carne de vaca magra grelhada', cal: 217, prot: 26, carbs: 0, fat: 12, tags: ['carne','vaca','boi','grelhada','magra'] },
            { name: 'Carne de porco (lombo)', cal: 188, prot: 27, carbs: 0, fat: 8, tags: ['carne','porco','lombo'] },
            { name: 'Peru grelhado (peito)', cal: 135, prot: 30, carbs: 0, fat: 1, tags: ['peru','peito','grelhado','carne'] },
            { name: 'Atum ao natural (lata)', cal: 108, prot: 24, carbs: 0, fat: 1, tags: ['atum','lata','peixe'] },
            { name: 'Salmão grelhado', cal: 208, prot: 20, carbs: 0, fat: 13, tags: ['salmão','salmon','peixe','grelhado'] },
            { name: 'Bacalhau cozido', cal: 105, prot: 22, carbs: 0, fat: 1, tags: ['bacalhau','peixe','cozido'] },
            // Ovos e laticínios
            { name: 'Ovo inteiro (cozido)', cal: 155, prot: 13, carbs: 1.1, fat: 11, tags: ['ovo','ovos','cozido'] },
            { name: 'Clara de ovo (100g)', cal: 52, prot: 11, carbs: 0.7, fat: 0.2, tags: ['clara','ovo','ovos'] },
            { name: 'Queijo fresco (magro)', cal: 95, prot: 12, carbs: 2, fat: 4, tags: ['queijo','fresco','magro'] },
            { name: 'Iogurte grego natural', cal: 97, prot: 9, carbs: 4, fat: 5, tags: ['iogurte','grego','natural'] },
            { name: 'Leite meio gordo (100ml)', cal: 47, prot: 3.4, carbs: 4.8, fat: 1.6, tags: ['leite','meio','gordo'] },
            { name: 'Queijo mozarela', cal: 280, prot: 28, carbs: 2, fat: 17, tags: ['queijo','mozzarella','mozarela'] },
            // Cereais e hidratos
            { name: 'Arroz branco cozido (100g)', cal: 130, prot: 2.7, carbs: 28, fat: 0.3, tags: ['arroz','branco','cozido'] },
            { name: 'Arroz integral cozido (100g)', cal: 111, prot: 2.6, carbs: 23, fat: 0.9, tags: ['arroz','integral'] },
            { name: 'Batata cozida', cal: 87, prot: 1.9, carbs: 20, fat: 0.1, tags: ['batata','cozida'] },
            { name: 'Batata doce cozida', cal: 86, prot: 1.6, carbs: 20, fat: 0.1, tags: ['batata','doce','cozida'] },
            { name: 'Massa cozida (esparguete)', cal: 158, prot: 5.8, carbs: 31, fat: 0.9, tags: ['massa','esparguete','macarrão','cozida'] },
            { name: 'Pão de forma (fatia 30g)', cal: 79, prot: 2.5, carbs: 15, fat: 0.9, tags: ['pão','forma','bread'] },
            { name: 'Aveia (100g)', cal: 389, prot: 17, carbs: 66, fat: 7, tags: ['aveia','oats','flocos'] },
            { name: 'Flocos de aveia cozidos (100g)', cal: 71, prot: 2.5, carbs: 12, fat: 1.4, tags: ['aveia','flocos','cozidos','porridge'] },
            // Leguminosas
            { name: 'Feijão cozido (100g)', cal: 127, prot: 8.7, carbs: 22, fat: 0.5, tags: ['feijão','bean','cozido'] },
            { name: 'Grão-de-bico cozido (100g)', cal: 164, prot: 8.9, carbs: 27, fat: 2.6, tags: ['grão','grão-de-bico','chickpea','cozido'] },
            { name: 'Lentilhas cozidas (100g)', cal: 116, prot: 9, carbs: 20, fat: 0.4, tags: ['lentilhas','cozidas'] },
            // Vegetais
            { name: 'Brócolis cozido (100g)', cal: 35, prot: 2.4, carbs: 7, fat: 0.4, tags: ['brócolis','broculos','brócolos','legume','vegetal'] },
            { name: 'Espinafres cozidos (100g)', cal: 23, prot: 2.5, carbs: 3.8, fat: 0.3, tags: ['espinafres','espinafre','vegetal'] },
            { name: 'Cenoura crua (100g)', cal: 41, prot: 0.9, carbs: 10, fat: 0.2, tags: ['cenoura','legume','vegetal'] },
            { name: 'Tomate (100g)', cal: 18, prot: 0.9, carbs: 3.9, fat: 0.2, tags: ['tomate','vegetal'] },
            { name: 'Alface (100g)', cal: 14, prot: 1.4, carbs: 2.1, fat: 0.2, tags: ['alface','salada','vegetal'] },
            // Frutas
            { name: 'Banana (100g)', cal: 89, prot: 1.1, carbs: 23, fat: 0.3, tags: ['banana','fruta'] },
            { name: 'Maçã (100g)', cal: 52, prot: 0.3, carbs: 14, fat: 0.2, tags: ['maçã','maça','fruta'] },
            { name: 'Laranja (100g)', cal: 47, prot: 0.9, carbs: 12, fat: 0.1, tags: ['laranja','fruta'] },
            { name: 'Morango (100g)', cal: 32, prot: 0.7, carbs: 7.7, fat: 0.3, tags: ['morango','strawberry','fruta'] },
            // Gorduras e oleaginosas
            { name: 'Azeite (1 colher sopa / 14g)', cal: 119, prot: 0, carbs: 0, fat: 14, tags: ['azeite','olive','oil'] },
            { name: 'Amendoim (100g)', cal: 567, prot: 26, carbs: 16, fat: 49, tags: ['amendoim','peanut'] },
            { name: 'Amêndoa (100g)', cal: 579, prot: 21, carbs: 22, fat: 50, tags: ['amêndoa','almond','amendoa'] },
            { name: 'Manteiga de amendoim (20g)', cal: 118, prot: 5, carbs: 3.6, fat: 10, tags: ['manteiga','amendoim','peanut','butter'] },
            // Suplementos comuns
            { name: 'Whey Protein (30g)', cal: 120, prot: 24, carbs: 3, fat: 1.5, tags: ['whey','proteína','protein','suplemento'] },
            { name: 'Creatina (5g)', cal: 0, prot: 0, carbs: 0, fat: 0, tags: ['creatina','creatine','suplemento'] },
        ];

        function searchLocalFoods(query) {
            const q = query.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            return LOCAL_FOODS_DB.filter(food => {
                const nameNorm = food.name.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                const tagMatch = food.tags.some(tag => {
                    const tagNorm = tag.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                    return tagNorm.includes(q) || q.includes(tagNorm);
                });
                return nameNorm.includes(q) || tagMatch;
            });
        }

        function renderFoodResult(resultsContainer, name, cal, prot, carbs, imageUrl, badgeLabel) {
            const resultDiv = document.createElement('div');
            resultDiv.style.cssText = 'display: flex; gap: 12px; align-items: center; padding: 10px; background: var(--cor-secundaria); border-radius: 8px; cursor: pointer; border: 1px solid #ddd; transition: all 0.2s; margin-bottom: 6px;';
            resultDiv.onmouseover = () => resultDiv.style.background = '#e2e8f0';
            resultDiv.onmouseout = () => resultDiv.style.background = 'var(--cor-secundaria)';
            resultDiv.onclick = () => addFoodToSelection(name, cal, prot, carbs, imageUrl);

            const badge = badgeLabel ? `<span style="background:#1c0c3b;color:#fff;font-size:9px;padding:2px 6px;border-radius:10px;font-weight:600;margin-left:6px;">${badgeLabel}</span>` : '';

            resultDiv.innerHTML = `
                <img src="${imageUrl}" alt="${name}" style="width:48px;height:48px;object-fit:cover;border-radius:8px;background:#f1f5f9;border:1px solid #e2e8f0;">
                <div style="flex:1;min-width:0;">
                    <h5 style="margin:0 0 3px;font-size:13px;color:var(--cor-destaque);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${name}${badge}</h5>
                    <p style="margin:0;font-size:11px;color:#555;">
                        <i class="fas fa-fire" style="color:#ea580c;"></i> ${cal} kcal/100g &nbsp;
                        <span style="color:#6b7280;">P: ${prot}g | H: ${carbs}g</span>
                    </p>
                </div>
            `;
            resultsContainer.appendChild(resultDiv);
        }

        // Função de pesquisa com imagens para o progresso
        async function searchFoodProgresso() {
            const query = document.getElementById('food-search-progresso').value.trim();
            if (!query) return;

            const resultsContainer = document.getElementById('food-results-progresso');
            resultsContainer.innerHTML = '';

            // 1. Pesquisa LOCAL imediata (alimentos básicos)
            const localResults = searchLocalFoods(query);
            if (localResults.length > 0) {
                const localHeader = document.createElement('p');
                localHeader.style.cssText = 'font-size:11px;color:#9ca3af;margin:0 0 6px;padding:2px 0;text-transform:uppercase;letter-spacing:.5px;';
                localHeader.textContent = '⚡ Resultados rápidos';
                resultsContainer.appendChild(localHeader);
                localResults.slice(0, 6).forEach(f => {
                    renderFoodResult(resultsContainer, f.name, f.cal, f.prot, f.carbs, 'https://via.placeholder.com/60?text=🥗', 'Local');
                });
            }

            // 2. Pesquisa na API Open Food Facts (produtos embalados)
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000);

                const response = await fetch(`proxy_food.php?search_terms=${encodeURIComponent(query)}`, {
                    signal: controller.signal
                });
                clearTimeout(timeoutId);
                const data = await response.json();

                if (data.products && data.products.length > 0) {
                    // Filtrar produtos com nome e calorias válidas
                    const validProducts = data.products.filter(p =>
                        p.product_name && p.nutriments && (
                            p.nutriments['energy-kcal_100g'] !== undefined ||
                            p.nutriments['energy-kcal_value'] !== undefined
                        )
                    );

                    if (validProducts.length > 0) {
                        const apiHeader = document.createElement('p');
                        apiHeader.style.cssText = 'font-size:11px;color:#9ca3af;margin:8px 0 6px;padding:2px 0;text-transform:uppercase;letter-spacing:.5px;';
                        apiHeader.textContent = '🛒 Produtos embalados';
                        resultsContainer.appendChild(apiHeader);

                        validProducts.slice(0, 5).forEach(product => {
                            const productName = product.product_name;
                            let cal = 0, prot = 0, carbs = 0;

                            if (product.nutriments['energy-kcal_100g'] !== undefined) cal = Math.round(product.nutriments['energy-kcal_100g']);
                            else if (product.nutriments['energy-kcal_value'] !== undefined) cal = Math.round(product.nutriments['energy-kcal_value']);
                            if (product.nutriments['proteins_100g'] !== undefined) prot = parseFloat(product.nutriments['proteins_100g']) || 0;
                            if (product.nutriments['carbohydrates_100g'] !== undefined) carbs = parseFloat(product.nutriments['carbohydrates_100g']) || 0;

                            const imageUrl = product.image_front_small_url || product.image_url || 'https://via.placeholder.com/60?text=📦';
                            renderFoodResult(resultsContainer, productName, cal, prot, carbs, imageUrl, '');
                        });
                    }
                }

                if (resultsContainer.children.length === 0) {
                    resultsContainer.innerHTML = '<p style="font-size:0.88em;color:#64748b;padding:10px;">Nenhum resultado encontrado. Tenta o <b>código de barras</b> ou uma palavra diferente.</p>';
                }

            } catch (error) {
                // Se a API falhar, os resultados locais já estão visíveis
                if (localResults.length === 0) {
                    resultsContainer.innerHTML = '<p style="font-size:0.9em;color:#ef4444;">Não encontramos resultados. Tenta uma palavra diferente ou usa o scanner de código de barras.</p>';
                }
            }
        }

        // ── Scanner de Código de Barras (html5-qrcode baixo nível) ──
        let html5QrcodeScanner = null;

        function stopBarcodeScanner() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.stop().then(() => {
                    html5QrcodeScanner.clear();
                    html5QrcodeScanner = null;
                }).catch(err => {
                    console.warn('Erro ao parar scanner:', err);
                    html5QrcodeScanner = null;
                });
            }
            document.getElementById('barcode-reader-container').style.display = 'none';
            document.getElementById('reader').innerHTML = '';
        }

        function startBarcodeScanner() {
            const container = document.getElementById('barcode-reader-container');
            container.style.display = 'block';

            if (html5QrcodeScanner !== null) return; // já ativo

            // Formatos suportados: todos os códigos de barras 1D (supermercado) + QR
            const formatsToSupport = [
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.EAN_8,
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.CODE_39,
                Html5QrcodeSupportedFormats.CODE_93,
                Html5QrcodeSupportedFormats.UPC_A,
                Html5QrcodeSupportedFormats.UPC_E,
                Html5QrcodeSupportedFormats.ITF,
                Html5QrcodeSupportedFormats.QR_CODE,
                Html5QrcodeSupportedFormats.DATA_MATRIX
            ];

            html5QrcodeScanner = new Html5Qrcode("reader", {
                formatsToSupport: formatsToSupport,
                verbose: false
            });

            const config = {
                fps: 15,
                qrbox: { width: 300, height: 120 },
                aspectRatio: 1.5,
                disableFlip: false
            };

            html5QrcodeScanner.start(
                { facingMode: "environment" }, // câmara traseira
                config,
                onScanSuccess,
                onScanFailure
            ).catch(err => {
                console.error('Erro ao iniciar câmara:', err);
                const resultsContainer = document.getElementById('food-results-progresso');
                resultsContainer.innerHTML = '<p style="color:#ef4444; padding:10px; text-align:center;"><i class="fas fa-exclamation-triangle"></i> Não foi possível aceder à câmara. Verifique as permissões do browser.</p>';
                html5QrcodeScanner = null;
                document.getElementById('barcode-reader-container').style.display = 'none';
            });
        }

        async function onScanSuccess(decodedText, decodedResult) {
            // Parar scanner
            stopBarcodeScanner();

            const resultsContainer = document.getElementById('food-results-progresso');
            resultsContainer.innerHTML = '<p style="text-align:center; padding: 10px; color: var(--cor-destaque);">A analisar o código de barras ' + decodedText + '...</p>';

            try {
                // Pesquisar produto por código de barras via proxy local
                const response = await fetch(`proxy_food.php?barcode=${encodeURIComponent(decodedText)}`);
                const data = await response.json();

                if (data.status === 1 && data.product) {
                    const product = data.product;
                    const productName = product.product_name || 'Produto Sem Nome';
                    let calories = 0;
                    let protein = 0;
                    let carbs = 0;

                    if (product.nutriments) {
                        if (product.nutriments['energy-kcal_100g'] !== undefined) {
                            calories = Math.round(product.nutriments['energy-kcal_100g']);
                        } else if (product.nutriments['energy-kcal_value'] !== undefined) {
                            calories = Math.round(product.nutriments['energy-kcal_value']);
                        }

                        if (product.nutriments['proteins_100g'] !== undefined) protein = parseFloat(product.nutriments['proteins_100g']) || 0;
                        if (product.nutriments['carbohydrates_100g'] !== undefined) carbs = parseFloat(product.nutriments['carbohydrates_100g']) || 0;
                    }

                    const imageUrl = product.image_front_small_url || product.image_url || 'https://via.placeholder.com/60?text=Sem+Foto';

                    addFoodToSelection(productName, calories, protein, carbs, imageUrl);

                    resultsContainer.innerHTML = '<p style="text-align:center; padding: 10px; color: #22c55e;"><i class="fas fa-check-circle"></i> Produto adicionado com sucesso!</p>';
                    setTimeout(() => { resultsContainer.innerHTML = ''; }, 3000);
                } else {
                    resultsContainer.innerHTML = '<p style="text-align:center; padding: 10px; color: #ea580c;">Produto não encontrado na base de dados com esse código de barras.</p>';
                    setTimeout(() => { resultsContainer.innerHTML = ''; }, 4000);
                }
            } catch (error) {
                console.error('Erro na pesquisa por código de barras:', error);
                resultsContainer.innerHTML = '<p style="text-align:center; padding: 10px; color: red;">Ocorreu um erro ao comunicar com a base de dados.</p>';
                setTimeout(() => { resultsContainer.innerHTML = ''; }, 3000);
            }
        }

        function onScanFailure(error) {
            // Pode ignorar o erro do scan failure contínuo (fps)
        }

        // --- Lógica para múltiplos alimentos (Lista de Compras/Refeição) ---
        let selectedFoodsList = [];

        function addFoodToSelection(name, caloriesPer100g, proteinPer100g, carbsPer100g, imageUrl) {
            selectedFoodsList.push({
                id: Date.now(),
                name: name,
                calPer100g: caloriesPer100g > 0 ? caloriesPer100g : 0,
                protPer100g: proteinPer100g > 0 ? proteinPer100g : 0,
                carbsPer100g: carbsPer100g > 0 ? carbsPer100g : 0,
                grams: 100,
                img: imageUrl
            });
            renderSelectedFoods();

            document.getElementById('food-search-progresso').value = '';
            document.getElementById('food-results-progresso').innerHTML = '';
        }

        function updateFoodGrams(id, newGrams) {
            const food = selectedFoodsList.find(f => f.id === id);
            if (food) {
                food.grams = parseInt(newGrams) || 0;
                renderSelectedFoods();
            }
        }

        function removeFoodFromSelection(id) {
            selectedFoodsList = selectedFoodsList.filter(f => f.id !== id);
            renderSelectedFoods();
        }

        function renderSelectedFoods() {
            const container = document.getElementById('selected-foods-container');
            const list = document.getElementById('selected-foods-list');
            const totalSpanCals = document.getElementById('selected-foods-total-cals');
            const totalSpanProt = document.getElementById('selected-foods-total-prot');
            const totalSpanCarbs = document.getElementById('selected-foods-total-carbs');

            if (selectedFoodsList.length === 0) {
                container.style.display = 'none';
                return;
            }

            container.style.display = 'flex';
            list.innerHTML = '';

            let totalCals = 0;
            let totalProt = 0;
            let totalCarbs = 0;

            selectedFoodsList.forEach(food => {
                const itemCals = Math.round((food.calPer100g / 100) * food.grams);
                const itemProt = ((food.protPer100g / 100) * food.grams);
                const itemCarbs = ((food.carbsPer100g / 100) * food.grams);

                totalCals += itemCals;
                totalProt += itemProt;
                totalCarbs += itemCarbs;

                const div = document.createElement('div');
                div.style.cssText = 'display: flex; align-items: center; gap: 10px; background: white; padding: 8px; border-radius: 8px; border: 1px solid #ddd;';

                div.innerHTML = `
                    <img src="${food.img}" style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover; border: 1px solid #eee;">
                    <div style="flex: 1; min-width: 0;">
                        <p style="margin: 0; font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${food.name}</p>
                        <p style="margin: 0; font-size: 11px; color: #666;">${food.calPer100g} kcal/100g | P:${food.protPer100g}g | H:${food.carbsPer100g}g</p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <input type="number" value="${food.grams}" min="1" max="5000" style="width: 60px; padding: 5px; border: 1px solid #ccc; border-radius: 4px; font-family: var(--fonte-texto);" onchange="updateFoodGrams(${food.id}, this.value)" onkeyup="updateFoodGrams(${food.id}, this.value)">
                        <span style="font-size: 12px; color: #555;">g</span>
                    </div>
                    <div style="width: 65px; text-align: right; font-weight: bold; font-size: 13px; color: var(--cor-destaque);">
                        ${itemCals} <span style="font-size: 10px; font-weight: normal;">kcal</span>
                    </div>
                    <button type="button" onclick="removeFoodFromSelection(${food.id})" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 5px;"><i class="fas fa-trash"></i></button>
                `;
                list.appendChild(div);
            });

            totalSpanCals.textContent = totalCals + ' kcal';
            totalSpanProt.textContent = totalProt.toFixed(1) + ' g';
            totalSpanCarbs.textContent = totalCarbs.toFixed(1) + ' g';
        }

        function confirmSelectedFoods() {
            if (selectedFoodsList.length === 0) return;

            let descriptions = [];
            let totalCalories = 0;
            let totalProteins = 0;
            let totalCarbs = 0;

            selectedFoodsList.forEach(food => {
                const itemCals = Math.round((food.calPer100g / 100) * food.grams);
                const itemProt = ((food.protPer100g / 100) * food.grams);
                const itemCarbs = ((food.carbsPer100g / 100) * food.grams);

                totalCalories += itemCals;
                totalProteins += itemProt;
                totalCarbs += itemCarbs;

                descriptions.push(`${food.name} (${food.grams}g)`);
            });

            // Preencher Formulário
            document.getElementById('descricao').value = descriptions.join(', ');
            document.getElementById('calorias').value = totalCalories;
            document.getElementById('proteinas').value = totalProteins.toFixed(1);
            document.getElementById('carbs').value = totalCarbs.toFixed(1);

            // Mostrar feedback visual
            const resultsContainer = document.getElementById('food-results-progresso');
            resultsContainer.innerHTML = '<div style="background: #f0fdf4; color: #166534; padding: 10px; border-radius: 8px; border-left: 4px solid #22c55e;"><i class="fas fa-check-circle"></i> Refeição pronta para ser adicionada no formulário abaixo!</div>';

            // Limpar lista
            selectedFoodsList = [];
            renderSelectedFoods();

            setTimeout(() => {
                resultsContainer.innerHTML = '';
            }, 3000);
        }

        const foodSearchEl = document.getElementById('food-search-progresso');
        if (foodSearchEl) {
            foodSearchEl.addEventListener('keydown', e => {
                if (e.key === 'Enter') searchFoodProgresso();
            });
        }

        // ══════════ GRÁFICOS (CHART.JS) ══════════
        
        // Função para criar gradientes
        function createGradient(ctx, colorStart, colorEnd) {
            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, colorStart);
            gradient.addColorStop(1, colorEnd);
            return gradient;
        }

        // Gráfico de Peso
        const ctxWeight = document.getElementById('weightChart').getContext('2d');
        const weightGradient = createGradient(ctxWeight, 'rgba(167, 139, 250, 0.4)', 'rgba(167, 139, 250, 0)');
        
        const weightChart = new Chart(ctxWeight, {
            type: 'line',
            data: {
                labels: <?= json_encode($datas_peso_js) ?>,
                datasets: [{
                    label: 'Peso (kg)',
                    data: <?= json_encode($valores_peso_js) ?>,
                    borderColor: '#a78bfa',
                    backgroundColor: weightGradient,
                    borderWidth: 4,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#a78bfa',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1c0c3b',
                        titleFont: { family: 'Syne', size: 14 },
                        bodyFont: { family: 'Inter', size: 13 },
                        padding: 12,
                        cornerRadius: 10,
                        displayColors: false
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: false, 
                        grid: { color: 'rgba(28, 12, 59, 0.06)', drawBorder: false },
                        ticks: { color: 'rgba(28, 12, 59, 0.55)', font: { size: 11 } }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { color: 'rgba(28, 12, 59, 0.55)', font: { size: 11 } }
                    }
                }
            }
        });

        // Gráfico de Volume Global (Força Semanal)
        const canvasGlobal = document.getElementById('globalStrengthChart');
        if (canvasGlobal) {
            const ctxGlobal = canvasGlobal.getContext('2d');
            const globalGradient = createGradient(ctxGlobal, 'rgba(34, 197, 94, 0.8)', 'rgba(34, 197, 94, 0.2)');
            
            new Chart(ctxGlobal, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($datas_forca_js) ?>,
                    datasets: [{
                        label: 'Volume Total (kg x reps)',
                        data: <?= json_encode($valores_forca_js) ?>,
                        backgroundColor: globalGradient,
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
                            backgroundColor: '#1c0c3b',
                            titleFont: { family: 'Syne', size: 14 },
                            bodyFont: { family: 'Inter', size: 13 },
                            padding: 12,
                            cornerRadius: 10,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return context.raw.toLocaleString('pt-PT') + ' kg movidos';
                                }
                            }
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { color: 'rgba(28, 12, 59, 0.06)', drawBorder: false },
                            ticks: { color: 'rgba(28, 12, 59, 0.55)', font: { size: 11 } }
                        },
                        x: { 
                            grid: { display: false },
                            ticks: { color: 'rgba(28, 12, 59, 0.55)', font: { size: 11 } }
                        }
                    }
                }
            });
        }

        // Gráfico de Força
        let strengthChartInstance = null;

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

                    if (strengthChartInstance) {
                        strengthChartInstance.destroy();
                    }

                    const ctxStrength = canvas.getContext('2d');
                    const strengthGradient = createGradient(ctxStrength, 'rgba(251, 113, 133, 0.4)', 'rgba(251, 113, 133, 0)');

                    strengthChartInstance = new Chart(ctxStrength, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Peso Máximo (kg)',
                                data: values,
                                borderColor: '#fb7185',
                                backgroundColor: strengthGradient,
                                borderWidth: 4,
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: '#fff',
                                pointBorderColor: '#fb7185',
                                pointBorderWidth: 2,
                                pointRadius: 5,
                                pointHoverRadius: 7
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { 
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: '#1c0c3b',
                                    titleFont: { family: 'Syne', size: 14 },
                                    bodyFont: { family: 'Inter', size: 13 },
                                    padding: 12,
                                    cornerRadius: 10,
                                    displayColors: false
                                }
                            },
                            scales: {
                                y: { 
                                    beginAtZero: true,
                                    grid: { color: 'rgba(28, 12, 59, 0.06)', drawBorder: false },
                                    ticks: { color: 'rgba(28, 12, 59, 0.55)', font: { size: 11 } }
                                },
                                x: { 
                                    grid: { display: false },
                                    ticks: { color: 'rgba(28, 12, 59, 0.55)', font: { size: 11 } }
                                }
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
    </script>

    <?php include 'app_navbar.php'; ?>

</body>

</html>