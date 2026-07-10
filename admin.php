<?php
session_start();

// Verifica se o utilizador está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'ligacao.php';

$user_id = $_SESSION['user_id'];
$mensagem = "";

// Verifica se o utilizador é Admin ou SuperAdmin
$sql_check = "SELECT tipo_usuario FROM user WHERE id_user = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard.php");
    exit;
}

$user_data = $result->fetch_assoc();
$stmt->close();

$tipo_usuario_atual = $user_data['tipo_usuario'] ?? 'Usuario';

// Verifica se é Admin ou SuperAdmin
if ($tipo_usuario_atual !== 'Admin' && $tipo_usuario_atual !== 'SuperAdmin') {
    header("Location: dashboard.php");
    exit;
}

// Buscar estatísticas gerais
$total_usuarios = 0;
$total_habitos = 0;
$total_registros_agua = 0;
$total_registros_peso = 0;

try {
    // Total de utilizadores
    $sql = "SELECT COUNT(*) as total FROM user";
    $result = $conn->query($sql);
    if ($row = $result->fetch_assoc()) {
        $total_usuarios = $row['total'];
    }

    // Total de hábitos
    $sql = "SELECT COUNT(*) as total FROM habito";
    $result = $conn->query($sql);
    if ($row = $result->fetch_assoc()) {
        $total_habitos = $row['total'];
    }

    // Total de registos de água
    $sql = "SELECT COUNT(*) as total FROM agua";
    $result = $conn->query($sql);
    if ($row = $result->fetch_assoc()) {
        $total_registros_agua = $row['total'];
    }

    // Total de registos de peso
    $sql = "SELECT COUNT(*) as total FROM peso";
    $result = $conn->query($sql);
    if ($row = $result->fetch_assoc()) {
        $total_registros_peso = $row['total'];
    }
} catch (Exception $e) {
    // Ignora erros se tabelas não existirem
}

// Buscar lista de utilizadores
$usuarios = [];
try {
    $sql = "SELECT id_user, nome, email, tipo_usuario, COALESCE(data_registo, NOW()) as data_registo FROM user ORDER BY data_registo DESC LIMIT 50";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
} catch (Exception $e) {
    // Ignora erro
}

// Buscar lista de pagamentos recentes
$pagamentos = [];
if ($tipo_usuario_atual === 'SuperAdmin') {
    try {
        $sql_pagamentos = "SELECT p.id_pagamento, u.nome as user_nome, u.email as user_email, p.tipo_plano, p.valor_pago, p.data_pagamento, p.stripe_subscription_id 
                           FROM pagamento p 
                           LEFT JOIN user u ON p.id_user = u.id_user 
                           ORDER BY p.data_pagamento DESC LIMIT 50";
        $result_pagamentos = $conn->query($sql_pagamentos);
        while ($row = $result_pagamentos->fetch_assoc()) {
            $pagamentos[] = $row;
        }
    } catch (Exception $e) {
        // Ignora erro
    }
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'alterar_tipo') {
        $user_id_alterar = intval($_POST['user_id'] ?? 0);
        $novo_tipo = $_POST['tipo_usuario'] ?? 'Usuario';

        // Não permite alterar o próprio tipo
        if ($user_id_alterar == $user_id) {
            $mensagem = "❌ Não podes alterar o teu próprio tipo de utilizador!";
        } else {
            // Verificar o tipo atual do utilizador que se quer alterar
            $stmt_target = $conn->prepare("SELECT tipo_usuario FROM user WHERE id_user = ?");
            $stmt_target->bind_param("i", $user_id_alterar);
            $stmt_target->execute();
            $target_data = $stmt_target->get_result()->fetch_assoc();
            $stmt_target->close();
            
            $target_tipo = $target_data['tipo_usuario'] ?? 'Usuario';
            
            // Regras de permissão:
            // Se o admin logado for 'Admin' (não SuperAdmin):
            // - Não pode alterar ninguém que seja 'Admin' ou 'SuperAdmin'
            // - Não pode promover ninguém para 'SuperAdmin'
            if ($tipo_usuario_atual === 'Admin' && ($target_tipo === 'Admin' || $target_tipo === 'SuperAdmin')) {
                $mensagem = "❌ Como Administrador, não podes alterar outros Administradores ou SuperAdmin!";
            } elseif ($tipo_usuario_atual === 'Admin' && $novo_tipo === 'SuperAdmin') {
                $mensagem = "❌ Apenas SuperAdmin podem promover utilizadores a SuperAdmin!";
            } else {
                $update = "UPDATE user SET tipo_usuario = ? WHERE id_user = ?";
                $stmt = $conn->prepare($update);
                $stmt->bind_param("si", $novo_tipo, $user_id_alterar);
                if ($stmt->execute()) {
                    $mensagem = "✅ Tipo de utilizador atualizado com sucesso!";
                    // Atualiza a lista
                    foreach ($usuarios as &$u) {
                        if ($u['id_user'] == $user_id_alterar) {
                            $u['tipo_usuario'] = $novo_tipo;
                        }
                    }
                } else {
                    $mensagem = "❌ Erro ao atualizar tipo de utilizador.";
                }
                $stmt->close();
            }
        }
    } elseif ($acao === 'enviar_email_todos') {
        $assunto = trim($_POST['assunto'] ?? '');
        $corpo = trim($_POST['mensagem'] ?? '');
        $filtro = $_POST['filtro_destinatarios'] ?? 'todos';
        $dias = intval($_POST['quantidade_dias'] ?? 7);

        if (empty($assunto) || empty($corpo)) {
            $mensagem = "❌ O assunto e a mensagem não podem estar vazios!";
        } else {
            // Requerer PHPMailer e ficheiro de configuração
            require_once 'config_email.php';
            require_once 'vendor/autoload.php';
            require_once 'config_stripe.php'; // Para BASE_URL

            $emails = [];

            if ($filtro === 'custom') {
                $emails_input = trim($_POST['emails_personalizados'] ?? '');
                $emails_array = array_filter(array_map('trim', explode(',', $emails_input)));
                foreach ($emails_array as $em) {
                    if (filter_var($em, FILTER_VALIDATE_EMAIL)) {
                        // Tentar obter o nome do banco de dados
                        $stmt_u = $conn->prepare("SELECT nome FROM user WHERE email = ?");
                        $stmt_u->bind_param("s", $em);
                        $stmt_u->execute();
                        $res_u = $stmt_u->get_result()->fetch_assoc();
                        $stmt_u->close();
                        
                        $nome = $res_u['nome'] ?? 'Guerreiro';
                        $emails[] = ['email' => $em, 'nome' => $nome];
                    }
                }
            } else {
                // Construir a consulta com base no filtro
                if ($filtro === 'premium') {
                    $sql_dest = "SELECT email, nome FROM user WHERE (tipo_plano = 'gladiator' OR tipo_plano = 'berserker') AND (data_expiracao_plano >= CURDATE() OR data_expiracao_plano IS NULL)";
                } elseif ($filtro === 'recentes') {
                    $sql_dest = "SELECT email, nome FROM user WHERE data_registo >= DATE_SUB(NOW(), INTERVAL ? DAY)";
                } else {
                    $sql_dest = "SELECT email, nome FROM user";
                }

                $stmt_emails = $conn->prepare($sql_dest);
                if ($filtro === 'recentes') {
                    $stmt_emails->bind_param("i", $dias);
                }
                $stmt_emails->execute();
                $res_emails = $stmt_emails->get_result();
                while ($row = $res_emails->fetch_assoc()) {
                    $emails[] = $row;
                }
                $stmt_emails->close();
            }

            if (empty($emails)) {
                $mensagem = "⚠️ Nenhum utilizador ou endereço de e-mail válido corresponde à seleção.";
            } else {
                $sucesso_envios = 0;
                $erro_envios = 0;
                $log_envios = "";

                $date_log = date('Y-m-d H:i:s');
                $log_envios .= "=== Lançamento de Broadcast em $date_log ===\n";
                $log_envios .= "Filtro: $filtro (Dias: $dias)\n";
                $log_envios .= "Assunto: $assunto\n";
                $log_envios .= "Mensagem: $corpo\n";
                $log_envios .= "-----------------------------------------\n";

                // Gerar URL do testemunho dinamicamente
                $url_testemunho = BASE_URL . 'deixar_testemunho.php';

                // Template HTML Global da Equipa BerserkFit
                $html_template = '
<div style="font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; color: #1c0c3b; line-height: 1.6; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px; background-color: #ffffff;">
  <div style="text-align: center; margin-bottom: 20px;">
    <img src="https://raw.githubusercontent.com/miguelsmuller/dev-berserkfit/main/assets/logotipo1.png" alt="BerserkFit Logo" style="max-height: 60px;">
  </div>
  
  <p style="font-size: 16px; font-weight: bold; margin-bottom: 15px;">Olá [NOME],</p>
  
  <div style="font-size: 15px; margin-bottom: 25px; color: #1f2937;">
    [[CONTEUDO_MENSAGEM]]
  </div>
  
  <!-- Botão de Opinião / Testemunho -->
  <div style="text-align: center; margin: 30px 0;">
    <a href="[[URL_TESTEMUNHO]]" style="display: inline-block; background-color: #1c0c3b; color: #ffd700; font-weight: bold; text-decoration: none; padding: 14px 30px; border-radius: 8px; font-size: 15px; box-shadow: 0 4px 6px rgba(28,12,59,0.15);">
      Deixar o meu Testemunho ⭐⭐⭐⭐⭐
    </a>
  </div>
  
  <p style="font-size: 15px; margin-bottom: 30px;">Desejamos-lhe bons treinos e evolução implacável!</p>
  
  <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 25px 0;">
  
  <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
    <tr>
      <td style="padding-bottom: 15px;">
        <span style="font-size: 16px; font-weight: bold; color: #ef4444; display: block;">Equipa BerserkFit</span>
        <span style="font-weight: bold; color: #1c0c3b; display: block; margin-top: 2px;">Suporte &amp; Comunidade</span>
        <a href="mailto:suporte@berserkfit.pt" style="color: #6d28d9; text-decoration: none; display: block; margin-top: 2px;">suporte@berserkfit.pt</a>
      </td>
    </tr>
    <tr>
      <td style="padding-bottom: 15px;">
        <span style="font-weight: bold; color: #1c0c3b; display: block;">BerserkFit AI Gym &amp; Solutions</span>
        <span style="color: #64748b; display: block; margin-top: 2px;">Av. Central, n.º 123</span>
        <span style="color: #64748b; display: block; margin-top: 2px;">4700-300 Braga, Portugal</span>
      </td>
    </tr>
    <tr>
      <td style="padding-bottom: 15px;">
        <span style="display: block;"><a href="https://www.berserkfit.pt" style="color: #6d28d9; text-decoration: none; font-weight: bold;">www.berserkfit.pt</a></span>
        <span style="display: block; margin-top: 2px;"><a href="mailto:suporte@berserkfit.pt" style="color: #6d28d9; text-decoration: none;">suporte@berserkfit.pt</a></span>
        <span style="color: #64748b; display: block; margin-top: 4px;">(+351) 912 345 678 * (Chamada para rede móvel nacional)</span>
        <span style="color: #64748b; display: block; margin-top: 2px;">(+351) 253 123 456 ** (Chamada para rede fixa nacional)</span>
      </td>
    </tr>
  </table>
  
  <div style="font-size: 10px; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 15px; margin-top: 15px; text-align: justify; line-height: 1.4;">
    <p style="margin: 0 0 8px 0;"><strong>Proteção de dados:</strong> A BerserkFit é responsável pelo tratamento dos seus dados pessoais ao abrigo dos regulamentos em vigor (RGPD), apenas com a finalidade profissional de gestão de utilizadores e subscrições. Os seus dados serão conservados pelo tempo necessário à manutenção da finalidade do tratamento e durante o prazo legalmente estabelecido. Pode exercer os seus direitos de acesso, retificação, eliminação ou oposição enviando o seu pedido para o encarregado de proteção de dados: <a href="mailto:rgpd@berserkfit.pt" style="color: #64748b; text-decoration: underline;">rgpd@berserkfit.pt</a>.</p>
    <p style="margin: 0;"><strong>Aviso legal:</strong> Esta mensagem e os seus anexos são endereçados exclusivamente ao destinatário, podendo conter informações confidenciais sujeitas a sigilo profissional. A sua reprodução ou distribuição não é permitida sem a nossa autorização expressa. Se não for o destinatário final, agradecemos que o elimine e nos informe do ocorrido.</p>
  </div>
</div>';

                foreach ($emails as $dest) {
                    $to = $dest['email'];
                    $nome_dest = $dest['nome'];

                    // Formatar conteúdo digitado com novas linhas em HTML e substituir tags
                    $mensagem_html = nl2br(htmlspecialchars($corpo));
                    $mensagem_html_personalizada = str_replace('[NOME]', $nome_dest, $mensagem_html);

                    // Colocar a mensagem no template global
                    $corpo_email_completo = str_replace('[[CONTEUDO_MENSAGEM]]', $mensagem_html_personalizada, $html_template);
                    $corpo_email_completo = str_replace('[[URL_TESTEMUNHO]]', $url_testemunho, $corpo_email_completo);
                    $corpo_email_completo = str_replace('[NOME]', $nome_dest, $corpo_email_completo);

                    // Configurar PHPMailer
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = MAIL_HOST;
                        $mail->SMTPAuth   = true;
                        $mail->Username   = MAIL_USERNAME;
                        $mail->Password   = MAIL_PASSWORD;
                        $mail->SMTPSecure = (MAIL_ENCRYPTION === 'ssl') ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = MAIL_PORT;
                        $mail->CharSet    = 'UTF-8';

                        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
                        $mail->addAddress($to, $nome_dest);

                        $mail->isHTML(true);
                        $mail->Subject = $assunto;
                        $mail->Body    = $corpo_email_completo;

                        $mail->send();
                        $sucesso_envios++;
                        $log_envios .= "✅ Enviado com PHPMailer para: $nome_dest ($to)\n";
                    } catch (\Exception $e) {
                        $erro_envios++;
                        $log_envios .= "❌ Erro no envio para: $nome_dest ($to). Erro: " . $mail->ErrorInfo . "\n";
                    }
                }

                // Gravar log para testes e auditoria
                file_put_contents('email_broadcast_log.txt', $log_envios, FILE_APPEND);

                if ($erro_envios > 0) {
                    $mensagem = "⚠️ Envio de broadcast concluído: $sucesso_envios e-mails enviados. $erro_envios falhas registadas em 'email_broadcast_log.txt'.";
                } else {
                    $mensagem = "✅ Broadcast enviado com sucesso via PHPMailer para todos os $sucesso_envios destinatários selecionados!";
                }
            }
        }
    } elseif ($acao === 'aprovar_testemunho') {
        if ($tipo_usuario_atual !== 'SuperAdmin') {
            $mensagem = "❌ Apenas SuperAdmin podem moderar testemunhos!";
        } else {
            $id_t = intval($_POST['id_testemunho'] ?? 0);
            $stmt = $conn->prepare("UPDATE testemunho SET aprovado = 1 WHERE id_testemunho = ?");
            $stmt->bind_param("i", $id_t);
            if ($stmt->execute()) {
                $mensagem = "✅ Testemunho aprovado com sucesso!";
            } else {
                $mensagem = "❌ Erro ao aprovar testemunho.";
            }
            $stmt->close();
        }
    } elseif ($acao === 'rejeitar_testemunho') {
        if ($tipo_usuario_atual !== 'SuperAdmin') {
            $mensagem = "❌ Apenas SuperAdmin podem moderar testemunhos!";
        } else {
            $id_t = intval($_POST['id_testemunho'] ?? 0);
            $stmt = $conn->prepare("UPDATE testemunho SET aprovado = 2 WHERE id_testemunho = ?");
            $stmt->bind_param("i", $id_t);
            if ($stmt->execute()) {
                $mensagem = "✅ Testemunho rejeitado com sucesso!";
            } else {
                $mensagem = "❌ Erro ao rejeitar testemunho.";
            }
            $stmt->close();
        }
    } elseif ($acao === 'excluir_testemunho') {
        if ($tipo_usuario_atual !== 'SuperAdmin') {
            $mensagem = "❌ Apenas SuperAdmin podem excluir testemunhos!";
        } else {
            $id_t = intval($_POST['id_testemunho'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM testemunho WHERE id_testemunho = ?");
            $stmt->bind_param("i", $id_t);
            if ($stmt->execute()) {
                $mensagem = "✅ Testemunho excluído com sucesso!";
            } else {
                $mensagem = "❌ Erro ao excluir testemunho.";
            }
            $stmt->close();
        }
    } elseif ($acao === 'excluir_newsletter_email') {
        if ($tipo_usuario_atual !== 'SuperAdmin') {
            $mensagem = "❌ Apenas SuperAdmin podem remover e-mails da newsletter!";
        } else {
            $id_email = intval($_POST['id_email'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM newsletter WHERE id = ?");
            $stmt->bind_param("i", $id_email);
            if ($stmt->execute()) {
                $mensagem = "✅ E-mail removido da newsletter com sucesso!";
            } else {
                $mensagem = "❌ Erro ao remover e-mail da newsletter.";
            }
            $stmt->close();
        }
    }
}

// Buscar testemunhos para moderação e e-mails da newsletter (Apenas SuperAdmin)
$testemunhos_pendentes = [];
$testemunhos_aprovados = [];
$testemunhos_rejeitados = [];
$newsletter_emails = [];

if ($tipo_usuario_atual === 'SuperAdmin') {
    try {
        $sql_t = "SELECT t.id_testemunho, t.estrelas, t.texto, t.aprovado, t.data_criacao, u.nome as user_nome, u.email as user_email 
                  FROM testemunho t 
                  LEFT JOIN user u ON t.id_user = u.id_user 
                  ORDER BY t.data_criacao DESC";
        $result_t = $conn->query($sql_t);
        while ($row = $result_t->fetch_assoc()) {
            if ($row['aprovado'] == 0) {
                $testemunhos_pendentes[] = $row;
            } elseif ($row['aprovado'] == 1) {
                $testemunhos_aprovados[] = $row;
            } else {
                $testemunhos_rejeitados[] = $row;
            }
        }
    } catch (Exception $e) {
        // Ignora se tabela não existir
    }

    try {
        $sql_n = "SELECT id, email, data_subscricao FROM newsletter ORDER BY data_subscricao DESC";
        $result_n = $conn->query($sql_n);
        if ($result_n) {
            while ($row = $result_n->fetch_assoc()) {
                $newsletter_emails[] = $row;
            }
        }
    } catch (Exception $e) {
        // Ignora se tabela não existir
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - BerserkFit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="css/global.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/admin.css?v=<?= time() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&family=Inter:wght@400;700&display=swap"
        rel="stylesheet">
    <style>
        body {
            padding-bottom: 0 !important;
        }
    </style>
</head>

<body>
    <div class="admin-layout">
        <!-- Menu Lateral -->
        <aside class="admin-sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-shield-alt"></i> BerserkFit Admin
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="#" class="menu-item active" data-target="sec-resumo">
                        <i class="fas fa-chart-line"></i> Resumo
                    </a>
                </li>
                <li>
                    <a href="#" class="menu-item" data-target="sec-utilizadores">
                        <i class="fas fa-users"></i> Utilizadores
                    </a>
                </li>
                <?php if ($tipo_usuario_atual === 'SuperAdmin'): ?>
                <li>
                    <a href="#" class="menu-item" data-target="sec-pagamentos">
                        <i class="fas fa-credit-card"></i> Pagamentos
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="#" class="menu-item" data-target="sec-emails">
                        <i class="fas fa-envelope"></i> Enviar Emails
                    </a>
                </li>
                <?php if ($tipo_usuario_atual === 'SuperAdmin'): ?>
                <li>
                    <a href="#" class="menu-item" data-target="sec-testemunhos">
                        <i class="fas fa-comments"></i> Testemunhos
                    </a>
                </li>
                <li>
                    <a href="#" class="menu-item" data-target="sec-newsletter">
                        <i class="fas fa-newspaper"></i> Newsletter
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <div class="sidebar-footer">
                <a href="dashboard.php" class="btn-voltar">
                    <i class="fas fa-arrow-left"></i> Voltar ao Site
                </a>
            </div>
        </aside>

        <!-- Conteúdo Principal -->
        <main class="admin-main-content">
            <div class="admin-header" style="margin-bottom: 25px; text-align: left; background: none; color: inherit; padding: 0;">
                <h1 style="font-family: 'Poppins', sans-serif; font-size: 2em; margin: 0 0 5px 0; color: var(--cor-destaque-escuro);"><i class="fas fa-cog"></i> Painel Administrativo</h1>
                <p style="color: #666; margin: 0;">Nível de acesso: <span style="font-weight: 700; color: var(--cor-destaque);"><?php echo htmlspecialchars($tipo_usuario_atual); ?></span></p>
            </div>

            <?php if ($mensagem != ""): ?>
                <div class="mensagem <?php echo strpos($mensagem, '✅') !== false ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($mensagem); ?>
                </div>
            <?php endif; ?>

            <!-- SECÇÃO 1: RESUMO -->
            <section id="sec-resumo" class="admin-tab-content active">
                <div class="admin-section">
                    <h2><i class="fas fa-chart-bar"></i> Estatísticas Gerais</h2>
                    <div class="stats-grid" style="margin-bottom: 0;">
                        <div class="stat-card">
                            <h3>Total de Utilizadores</h3>
                            <p class="stat-value"><?php echo number_format($total_usuarios); ?></p>
                        </div>
                        <div class="stat-card">
                            <h3>Total de Hábitos</h3>
                            <p class="stat-value"><?php echo number_format($total_habitos); ?></p>
                        </div>
                        <div class="stat-card">
                            <h3>Registos de Água</h3>
                            <p class="stat-value"><?php echo number_format($total_registros_agua); ?></p>
                        </div>
                        <div class="stat-card">
                            <h3>Registos de Peso</h3>
                            <p class="stat-value"><?php echo number_format($total_registros_peso); ?></p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECÇÃO 2: UTILIZADORES -->
            <section id="sec-utilizadores" class="admin-tab-content">
                <div class="admin-section">
                    <h2><i class="fas fa-users"></i> Gestão de Utilizadores</h2>

                    <?php if (!empty($usuarios)): ?>
                        <div class="admin-table-wrapper">
                            <table class="users-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>Email</th>
                                        <th>Tipo</th>
                                        <th>Data de Registo</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <tr>
                                            <td><?php echo $usuario['id_user']; ?></td>
                                            <td>
                                                <a href="admin_usuario.php?id=<?php echo $usuario['id_user']; ?>">
                                                    <?php echo htmlspecialchars($usuario['nome']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                            <td>
                                                <span class="badge <?php echo strtolower($usuario['tipo_usuario'] ?? 'Usuario'); ?>">
                                                    <?php echo htmlspecialchars($usuario['tipo_usuario'] ?? 'Usuario'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                if (!empty($usuario['data_registo'])) {
                                                    try {
                                                        if (is_string($usuario['data_registo'])) {
                                                            $timestamp = strtotime($usuario['data_registo']);
                                                            if ($timestamp !== false) {
                                                                echo date('d/m/Y', $timestamp);
                                                            } else {
                                                                echo date('d/m/Y', strtotime($usuario['data_registo']));
                                                            }
                                                        } else {
                                                            echo date('d/m/Y', $usuario['data_registo']);
                                                        }
                                                    } catch (Exception $e) {
                                                        echo htmlspecialchars($usuario['data_registo']);
                                                    }
                                                } else {
                                                    echo "--";
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($usuario['id_user'] != $user_id): ?>
                                                    <?php 
                                                    $pode_editar = false;
                                                    if ($tipo_usuario_atual === 'SuperAdmin') {
                                                        $pode_editar = true;
                                                    } elseif ($tipo_usuario_atual === 'Admin' && ($usuario['tipo_usuario'] ?? 'Usuario') !== 'Admin' && ($usuario['tipo_usuario'] ?? 'Usuario') !== 'SuperAdmin') {
                                                        $pode_editar = true;
                                                    }
                                                    
                                                    if ($pode_editar): 
                                                    ?>
                                                        <form method="POST" class="form-inline"
                                                            onsubmit="return confirm('Tem a certeza que deseja alterar o tipo deste utilizador?');">
                                                            <input type="hidden" name="acao" value="alterar_tipo">
                                                            <input type="hidden" name="user_id" value="<?php echo $usuario['id_user']; ?>">
                                                            <select name="tipo_usuario" required>
                                                                <option value="Usuario" <?php echo ($usuario['tipo_usuario'] ?? 'Usuario') == 'Usuario' ? 'selected' : ''; ?>>Usuario</option>
                                                                <option value="Admin" <?php echo ($usuario['tipo_usuario'] ?? 'Usuario') == 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                                                <?php if ($tipo_usuario_atual === 'SuperAdmin'): ?>
                                                                    <option value="SuperAdmin" <?php echo ($usuario['tipo_usuario'] ?? 'Usuario') == 'SuperAdmin' ? 'selected' : ''; ?>>SuperAdmin</option>
                                                                <?php endif; ?>
                                                            </select>
                                                            <button type="submit" class="btn-admin">
                                                                <i class="fas fa-save"></i> Atualizar
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span style="color: #ef4444; font-size: 0.85em; font-weight: 600;">Sem Permissão</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span style="color: #999; font-size: 0.9em;">Tu</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; color: #999; padding: 20px;">
                            Nenhum utilizador encontrado.
                        </p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- SECÇÃO 3: PAGAMENTOS -->
            <?php if ($tipo_usuario_atual === 'SuperAdmin'): ?>
            <section id="sec-pagamentos" class="admin-tab-content">
                <div class="admin-section">
                    <h2><i class="fas fa-credit-card"></i> Histórico de Pagamentos (Stripe)</h2>
 
                    <?php if (!empty($pagamentos)): ?>
                        <div class="admin-table-wrapper">
                            <table class="users-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Utilizador</th>
                                        <th>Plano</th>
                                        <th>Valor Pago</th>
                                        <th>Data do Pagamento</th>
                                        <th>ID Subscrição Stripe</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pagamentos as $pagamento): ?>
                                        <tr>
                                            <td><?php echo $pagamento['id_pagamento']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($pagamento['user_nome'] ?? 'Desconhecido'); ?></strong><br>
                                                <small style="color: #666;"><?php echo htmlspecialchars($pagamento['user_email'] ?? '--'); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge" style="background: var(--cor-secundaria); color: var(--cor-destaque); text-transform: uppercase; font-weight: 700; border: 1px solid var(--cor-intermedia);">
                                                    <?php echo htmlspecialchars($pagamento['tipo_plano']); ?>
                                                </span>
                                            </td>
                                            <td>€<?php echo number_format($pagamento['valor_pago'], 2); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($pagamento['data_pagamento'])); ?></td>
                                            <td><code style="font-size: 0.9em; background: #f4f4f5; padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($pagamento['stripe_subscription_id'] ?? '--'); ?></code></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; color: #999; padding: 20px;">
                            Nenhum registo de pagamento encontrado.
                        </p>
                    <?php endif; ?>
                    <!-- Botão para ver logs do webhook -->
                    <div style="margin-top: 25px; padding: 20px; background: rgba(196, 181, 253, 0.05); border: 1px solid rgba(196, 181, 253, 0.15); border-radius: 8px;">
                        <h3 style="color: var(--cor-destaque-escuro); margin-bottom: 10px;"><i class="fas fa-file-alt"></i> Histórico de Logs do Webhook</h3>
                        <p style="font-size: 0.9em; color: #666; margin-bottom: 10px;">Aqui podes acompanhar as tentativas de ligação que a Stripe faz para o servidor da escola para registar os pagamentos.</p>
                        <pre style="background: #18181b; color: #22c55e; padding: 15px; border-radius: 6px; overflow-x: auto; max-height: 250px; font-family: monospace; font-size: 0.85em; white-space: pre-wrap; word-break: break-all;"><?php
                            $log_path = 'stripe_webhook_log.txt';
                            if (file_exists($log_path)) {
                                $logs = file($log_path);
                                $last_logs = array_slice($logs, -30); // Últimas 30 linhas
                                echo htmlspecialchars(implode("", $last_logs));
                            } else {
                                echo "Nenhum log registado até ao momento (ficheiro stripe_webhook_log.txt ainda não foi criado no servidor).";
                            }
                        ?></pre>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- SECÇÃO 4: EMAILS -->
            <section id="sec-emails" class="admin-tab-content">
                <div class="admin-section">
                    <h2><i class="fas fa-envelope"></i> Enviar E-mail Broadcast</h2>
                    <p style="color: #666; margin-bottom: 20px; font-size: 0.95em;">
                        Envie uma mensagem personalizada para a audiência selecionada. Utilize a tag <strong>[NOME]</strong> para personalizar com o nome real do utilizador, e a tag <strong>[[URL_TESTEMUNHO]]</strong> para inserir o link do testemunho.
                    </p>

                    <form method="POST" action="" style="display: flex; flex-direction: column; gap: 15px;">
                        <input type="hidden" name="acao" value="enviar_email_todos">

                        <!-- Filtros de Destinatários -->
                        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 250px; display: flex; flex-direction: column; gap: 5px;">
                                <label style="font-weight: 600; color: var(--cor-destaque-escuro);">Destinatários</label>
                                <select name="filtro_destinatarios" id="filtro_destinatarios" onchange="toggleDiasInput()" style="padding: 10px; border: 1px solid var(--cor-secundaria); border-radius: 8px; font-size: 1rem; width: 100%; box-sizing: border-box;">
                                    <option value="todos">Todos os utilizadores (Membro da Legião)</option>
                                    <option value="premium">Com plano pago ativo (Gladiator &amp; Berserker)</option>
                                    <option value="recentes">Registados há menos de X dias</option>
                                    <option value="custom">Emails específicos (manuais)</option>
                                </select>
                            </div>
                            <div id="dias_container" style="flex: 1; min-width: 200px; display: none; flex-direction: column; gap: 5px;">
                                <label style="font-weight: 600; color: var(--cor-destaque-escuro);">Quantidade de Dias</label>
                                <input type="number" name="quantidade_dias" id="quantidade_dias" min="1" value="7" style="padding: 10px; border: 1px solid var(--cor-secundaria); border-radius: 8px; font-size: 1rem; width: 100%; box-sizing: border-box;">
                            </div>
                            <div id="custom_emails_container" style="flex: 2; min-width: 300px; display: none; flex-direction: column; gap: 5px;">
                                <label style="font-weight: 600; color: var(--cor-destaque-escuro);">Endereços de E-mail (Separados por vírgula)</label>
                                <input type="text" name="emails_personalizados" id="emails_personalizados" placeholder="exemplo1@email.com, exemplo2@email.com" style="padding: 10px; border: 1px solid var(--cor-secundaria); border-radius: 8px; font-size: 1rem; width: 100%; box-sizing: border-box;">
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 5px;">
                            <label style="font-weight: 600; color: var(--cor-destaque-escuro);">Assunto do E-mail</label>
                            <input type="text" name="assunto" required value="Queremos saber a sua opinião sobre o BerserkFit! ⭐" style="padding: 10px; border: 1px solid var(--cor-secundaria); border-radius: 8px; font-size: 1rem; width: 100%; box-sizing: border-box;">
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 5px;">
                            <label style="font-weight: 600; color: var(--cor-destaque-escuro);">Mensagem</label>
                            <textarea name="mensagem" required rows="8" placeholder="Escreva a sua mensagem normal aqui... (ex: Olá [NOME], temos novidades no BerserkFit!)" style="padding: 10px; border: 1px solid var(--cor-secundaria); border-radius: 8px; font-size: 1rem; width: 100%; box-sizing: border-box; font-family: inherit; resize: vertical;"></textarea>
                        </div>

                        <button type="submit" class="btn-admin" style="padding: 12px 24px; font-size: 1rem; width: fit-content; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px; margin-top: 10px;">
                            <i class="fas fa-paper-plane"></i> Enviar Broadcast
                        </button>
                    </form>
                </div>
            </section>

            <!-- SECÇÃO 5: TESTEMUNHOS -->
            <?php if ($tipo_usuario_atual === 'SuperAdmin'): ?>
            <section id="sec-testemunhos" class="admin-tab-content">
                <div class="admin-section">
                    <h2><i class="fas fa-comments"></i> Moderação de Testemunhos</h2>
                    <p style="color: #666; margin-bottom: 25px; font-size: 0.95em;">
                        Gira as opiniões enviadas pelos utilizadores. Os testemunhos aprovados serão exibidos automaticamente na homepage do BerserkFit.
                    </p>

                    <!-- Testemunhos Pendentes -->
                    <div style="margin-bottom: 30px;">
                        <h3 style="border-bottom: 2px solid var(--cor-secundaria); padding-bottom: 8px; margin-bottom: 15px; color: var(--cor-destaque-escuro);">
                            <i class="fas fa-hourglass-half"></i> Pendentes de Aprovação (<?php echo count($testemunhos_pendentes); ?>)
                        </h3>
                        <?php if (!empty($testemunhos_pendentes)): ?>
                            <div class="admin-table-wrapper">
                                <table class="users-table">
                                    <thead>
                                        <tr>
                                            <th>Utilizador</th>
                                            <th>Classificação</th>
                                            <th>Testemunho</th>
                                            <th>Data</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($testemunhos_pendentes as $t): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($t['user_nome'] ?? 'Desconhecido'); ?></strong><br>
                                                    <small style="color: #666;"><?php echo htmlspecialchars($t['user_email'] ?? '--'); ?></small>
                                                </td>
                                                <td style="white-space: nowrap; color: var(--cor-amarela);">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="<?php echo $i <= $t['estrelas'] ? 'fas' : 'far'; ?> fa-star"></i>
                                                    <?php endfor; ?>
                                                </td>
                                                <td><div style="max-width: 350px; white-space: normal; line-height: 1.4;"><?php echo nl2br(htmlspecialchars($t['texto'])); ?></div></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($t['data_criacao'])); ?></td>
                                                <td style="white-space: nowrap;">
                                                    <form method="POST" style="display: inline-block; margin-right: 5px;">
                                                        <input type="hidden" name="acao" value="aprovar_testemunho">
                                                        <input type="hidden" name="id_testemunho" value="<?php echo $t['id_testemunho']; ?>">
                                                        <button type="submit" class="btn-admin" style="background-color: #10b981; color: white; border: none; cursor: pointer;">
                                                            <i class="fas fa-check"></i> Aprovar
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display: inline-block;">
                                                        <input type="hidden" name="acao" value="rejeitar_testemunho">
                                                        <input type="hidden" name="id_testemunho" value="<?php echo $t['id_testemunho']; ?>">
                                                        <button type="submit" class="btn-admin" style="background-color: #ef4444; color: white; border: none; cursor: pointer;" onclick="return confirm('Tem a certeza que deseja rejeitar este testemunho?');">
                                                            <i class="fas fa-times"></i> Rejeitar
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p style="color: #666; font-style: italic; background: var(--cor-primaria); padding: 15px; border-radius: 8px; border: 1px dashed var(--cor-secundaria);">
                                Não existem testemunhos pendentes de aprovação neste momento.
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Testemunhos Aprovados -->
                    <div style="margin-bottom: 30px;">
                        <h3 style="border-bottom: 2px solid var(--cor-secundaria); padding-bottom: 8px; margin-bottom: 15px; color: var(--cor-destaque-escuro);">
                            <i class="fas fa-check-circle"></i> Aprovados (<?php echo count($testemunhos_aprovados); ?>)
                        </h3>
                        <?php if (!empty($testemunhos_aprovados)): ?>
                            <div class="admin-table-wrapper">
                                <table class="users-table">
                                    <thead>
                                        <tr>
                                            <th>Utilizador</th>
                                            <th>Classificação</th>
                                            <th>Testemunho</th>
                                            <th>Data</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($testemunhos_aprovados as $t): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($t['user_nome'] ?? 'Desconhecido'); ?></strong><br>
                                                    <small style="color: #666;"><?php echo htmlspecialchars($t['user_email'] ?? '--'); ?></small>
                                                </td>
                                                <td style="white-space: nowrap; color: var(--cor-amarela);">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="<?php echo $i <= $t['estrelas'] ? 'fas' : 'far'; ?> fa-star"></i>
                                                    <?php endfor; ?>
                                                </td>
                                                <td><div style="max-width: 350px; white-space: normal; line-height: 1.4;"><?php echo nl2br(htmlspecialchars($t['texto'])); ?></div></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($t['data_criacao'])); ?></td>
                                                <td>
                                                    <form method="POST" style="display: inline-block;">
                                                        <input type="hidden" name="acao" value="rejeitar_testemunho">
                                                        <input type="hidden" name="id_testemunho" value="<?php echo $t['id_testemunho']; ?>">
                                                        <button type="submit" class="btn-admin" style="background-color: #ef4444; color: white; border: none; cursor: pointer;" onclick="return confirm('Tem a certeza que deseja remover este testemunho dos aprovados?');">
                                                            <i class="fas fa-undo"></i> Remover
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p style="color: #666; font-style: italic; background: var(--cor-primaria); padding: 15px; border-radius: 8px; border: 1px dashed var(--cor-secundaria);">
                                Nenhum testemunho foi aprovado até ao momento.
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Testemunhos Rejeitados -->
                    <div>
                        <h3 style="border-bottom: 2px solid var(--cor-secundaria); padding-bottom: 8px; margin-bottom: 15px; color: var(--cor-destaque-escuro);">
                            <i class="fas fa-times-circle"></i> Rejeitados (<?php echo count($testemunhos_rejeitados); ?>)
                        </h3>
                        <?php if (!empty($testemunhos_rejeitados)): ?>
                            <div class="admin-table-wrapper">
                                <table class="users-table">
                                    <thead>
                                        <tr>
                                            <th>Utilizador</th>
                                            <th>Classificação</th>
                                            <th>Testemunho</th>
                                            <th>Data</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($testemunhos_rejeitados as $t): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($t['user_nome'] ?? 'Desconhecido'); ?></strong><br>
                                                    <small style="color: #666;"><?php echo htmlspecialchars($t['user_email'] ?? '--'); ?></small>
                                                </td>
                                                <td style="white-space: nowrap; color: var(--cor-amarela);">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="<?php echo $i <= $t['estrelas'] ? 'fas' : 'far'; ?> fa-star"></i>
                                                    <?php endfor; ?>
                                                </td>
                                                <td><div style="max-width: 350px; white-space: normal; line-height: 1.4;"><?php echo nl2br(htmlspecialchars($t['texto'])); ?></div></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($t['data_criacao'])); ?></td>
                                                <td style="white-space: nowrap;">
                                                    <form method="POST" style="display: inline-block; margin-right: 5px;">
                                                        <input type="hidden" name="acao" value="aprovar_testemunho">
                                                        <input type="hidden" name="id_testemunho" value="<?php echo $t['id_testemunho']; ?>">
                                                        <button type="submit" class="btn-admin" style="background-color: #10b981; color: white; border: none; cursor: pointer;">
                                                            <i class="fas fa-check"></i> Restaurar e Aprovar
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display: inline-block;">
                                                        <input type="hidden" name="acao" value="excluir_testemunho">
                                                        <input type="hidden" name="id_testemunho" value="<?php echo $t['id_testemunho']; ?>">
                                                        <button type="submit" class="btn-admin" style="background-color: #374151; color: white; border: none; cursor: pointer;" onclick="return confirm('Tem a certeza que deseja eliminar permanentemente este testemunho?');">
                                                            <i class="fas fa-trash"></i> Eliminar
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p style="color: #666; font-style: italic; background: var(--cor-primaria); padding: 15px; border-radius: 8px; border: 1px dashed var(--cor-secundaria);">
                                Não existem testemunhos rejeitados.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- SECÇÃO 6: NEWSLETTER -->
            <section id="sec-newsletter" class="admin-tab-content">
                <div class="admin-section">
                    <h2><i class="fas fa-newspaper"></i> Subscritores da Newsletter</h2>
                    <p style="color: #666; margin-bottom: 25px; font-size: 0.95em;">
                        Aqui podes consultar todos os endereços de e-mail que subscreveram a newsletter através do rodapé do site.
                    </p>

                    <?php if (!empty($newsletter_emails)): ?>
                        <div class="admin-table-wrapper">
                            <table class="users-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>E-mail do Subscritor</th>
                                        <th>Data de Subscrição</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($newsletter_emails as $n_mail): ?>
                                        <tr>
                                            <td><?php echo $n_mail['id']; ?></td>
                                            <td>
                                                <strong style="font-size: 1.05rem;"><?php echo htmlspecialchars($n_mail['email']); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo date('d/m/Y H:i', strtotime($n_mail['data_subscricao'])); ?>
                                            </td>
                                            <td>
                                                <form method="POST" style="display: inline-block;" onsubmit="return confirm('Tem a certeza que deseja remover este e-mail da newsletter?');">
                                                    <input type="hidden" name="acao" value="excluir_newsletter_email">
                                                    <input type="hidden" name="id_email" value="<?php echo $n_mail['id']; ?>">
                                                    <button type="submit" class="btn-admin" style="background-color: #ef4444; color: white; border: none; cursor: pointer;">
                                                        <i class="fas fa-trash"></i> Remover
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p style="color: #666; font-style: italic; background: var(--cor-primaria); padding: 15px; border-radius: 8px; border: 1px dashed var(--cor-secundaria);">
                            Ainda não existem e-mails registados na newsletter.
                        </p>
                    <?php endif; ?>
                </div>
            </section>
            <?php endif; ?>
        </main>
    </div>

    <!-- Script de Tabs e Exibição Dinâmica -->
    <script>
        function toggleDiasInput() {
            const filtro = document.getElementById('filtro_destinatarios').value;
            const diasContainer = document.getElementById('dias_container');
            const customContainer = document.getElementById('custom_emails_container');
            
            if (filtro === 'recentes') {
                diasContainer.style.display = 'flex';
                customContainer.style.display = 'none';
            } else if (filtro === 'custom') {
                diasContainer.style.display = 'none';
                customContainer.style.display = 'flex';
            } else {
                diasContainer.style.display = 'none';
                customContainer.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            toggleDiasInput();

            const menuItems = document.querySelectorAll('.menu-item');
            const sections = document.querySelectorAll('.admin-tab-content');

            menuItems.forEach(item => {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = item.getAttribute('data-target');

                    // Desativar itens ativos
                    menuItems.forEach(mi => mi.classList.remove('active'));
                    item.classList.add('active');

                    // Ocultar secções ativas
                    sections.forEach(sec => sec.classList.remove('active'));
                    document.getElementById(targetId).classList.add('active');
                });
            });
        });
    </script>
</body>

</html>