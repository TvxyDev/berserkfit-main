<?php
require "ligacao.php";

session_start();

// Verifica se o utilizador está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['user_id'];

// Busca os dados do utilizador
$sql = "SELECT tfa_secret, username, nome, tipo_plano, data_expiracao_plano, stripe_customer_id FROM user WHERE id_user = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$tfa_ativo = !empty($user['tfa_secret']);

// Redirecionamento para o Portal de Faturação da Stripe
$msg_erro = '';
if (isset($_GET['action']) && $_GET['action'] === 'portal_stripe') {
    require 'config_stripe.php';
    require 'vendor/autoload.php';

    if (!empty($user['stripe_customer_id'])) {
        try {
            \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
            $session = \Stripe\BillingPortal\Session::create([
                'customer' => $user['stripe_customer_id'],
                'return_url' => BASE_URL . 'configuracoes.php',
            ]);
            header("Location: " . $session->url);
            exit;
        } catch (\Exception $e) {
            $msg_erro = "Erro ao carregar o portal da Stripe: " . $e->getMessage();
        }
    } else {
        $msg_erro = "Ainda não tens uma assinatura ativa no Stripe.";
    }
}

// Mensagem de sucesso ao voltar de alterar_senha.php
$msg_sucesso = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'senha_alterada') {
    $msg_sucesso = '✅ Palavra-passe alterada com sucesso!';
}
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - BerserkFit</title>

    <link rel="stylesheet" href="css/global.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/perfil.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700&family=Inter:wght@400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/configuracoes.css?v=<?= time() ?>">
</head>

<body>

    <main class="main-content" style="padding-top: 40px;">
        <div class="settings-container">
            <a href="perfil.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar ao Perfil
            </a>

            <div class="settings-header">
                <h1><i class="fas fa-cog"></i> Configurações</h1>
                <p>Gerencie as suas preferências e segurança da conta</p>
            </div>

            <?php if ($msg_sucesso): ?>
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;padding:14px 18px;border-radius:10px;margin-bottom:20px;font-weight:500;">
                    <?= htmlspecialchars($msg_sucesso) ?>
                </div>
            <?php endif; ?>

            <?php if ($msg_erro): ?>
                <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:14px 18px;border-radius:10px;margin-bottom:20px;font-weight:500;">
                    <?= htmlspecialchars($msg_erro) ?>
                </div>
            <?php endif; ?>

            <!-- Seção de Subscrição -->
            <div class="settings-section">
                <h2><i class="fas fa-credit-card"></i> Subscrição</h2>
                <p>Gere o teu plano e pagamentos do BerserkFit</p>

                <?php 
                $plano_atual = $user['tipo_plano'] ?? 'gratuito'; 
                ?>

                <div class="settings-action">
                    <div class="settings-action-info">
                        <h3>
                            Plano Atual: <span style="text-transform: capitalize; color: var(--cor-acento); font-weight: 700;"><?= htmlspecialchars($plano_atual === 'gratuito' ? 'Spartan (Grátis)' : $plano_atual) ?></span>
                        </h3>
                        <p>Visualiza as vantagens dos planos, subscreve ou gere as tuas faturas.</p>
                    </div>
                    <a href="gerir_subscricao.php" class="btn-primary" style="text-decoration: none;">
                        Gerir Subscrição
                    </a>
                </div>
            </div>

            <!-- Seção de Segurança -->
            <div class="settings-section">
                <h2><i class="fas fa-shield-alt"></i> Segurança</h2>
                <p>Proteja a sua conta com recursos adicionais de segurança</p>

                <!-- 2FA -->
                <div class="settings-action">
                    <div class="settings-action-info">
                        <h3>
                            Autenticação de Dois Fatores (2FA)
                            <?php if ($tfa_ativo): ?>
                                <span class="badge badge-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Inativo</span>
                            <?php endif; ?>
                        </h3>
                        <p>
                            <?php if ($tfa_ativo): ?>
                                A sua conta está protegida com autenticação de dois fatores.
                            <?php else: ?>
                                Adicione uma camada extra de segurança à sua conta.
                            <?php endif; ?>
                        </p>
                    </div>
                    <a href="setup_2fa.php" class="btn-primary">
                        <?php echo $tfa_ativo ? 'Gerir 2FA' : 'Ativar 2FA'; ?>
                    </a>
                </div>

                <!-- Senha -->
                <div class="settings-action">
                    <div class="settings-action-info">
                        <h3>Alterar Palavra-passe</h3>
                        <p>Atualize a sua senha regularmente para manter a conta segura.</p>
                    </div>
                    <a href="alterar_senha.php" class="btn-primary">
                        Alterar Senha
                    </a>
                </div>
            </div>

            <!-- Seção de Conta -->
            <div class="settings-section">
                <h2><i class="fas fa-user-circle"></i> Conta</h2>
                <p>Gerencie as informações da sua conta</p>

                <div class="settings-action">
                    <div class="settings-action-info">
                        <h3>Dados Pessoais</h3>
                        <p>Edite o seu nome, email, telefone e outros dados pessoais.</p>
                    </div>
                    <a href="editar_perfil.php" class="btn-secondary">
                        Editar Perfil
                    </a>
                </div>
            </div>

            <!-- Seção de Privacidade -->
            <div class="settings-section">
                <h2><i class="fas fa-lock"></i> Privacidade</h2>
                <p>Controle quem pode ver suas informações</p>

                <div class="settings-action">
                    <div class="settings-action-info">
                        <h3>Perfil Público</h3>
                        <p>Controle a visibilidade do seu perfil e estatísticas.</p>
                    </div>
                    <button class="btn-secondary" onclick="showCustomAlert('Privacidade', 'Esta funcionalidade de perfil privado está em desenvolvimento.')">
                        Configurar
                    </button>
                </div>
            </div>

            <!-- Seção de Notificações -->
            <div class="settings-section">
                <h2><i class="fas fa-bell"></i> Notificações</h2>
                <p>Escolha como deseja receber notificações</p>

                <div class="settings-action">
                    <div class="settings-action-info">
                        <h3>Preferências de Notificação</h3>
                        <p>Configure email, push e outras notificações.</p>
                    </div>
                    <button class="btn-secondary" onclick="showCustomAlert('Notificações', 'As configurações avançadas de notificação estão em desenvolvimento.')">
                        Configurar
                    </button>
                </div>
            </div>

            <!-- Sair -->
            <div class="settings-section" style="border-color: rgba(239, 68, 68, 0.3);">
                <h2 style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i> Sair</h2>
                <p>Terminar a sessão no dispositivo atual</p>

                <div class="settings-action"
                    style="background: rgba(239, 68, 68, 0.05); border-color: rgba(239, 68, 68, 0.2);">
                    <div class="settings-action-info">
                        <h3 style="color: #ef4444;">Terminar Sessão</h3>
                        <p>Deseja sair da sua conta?</p>
                    </div>
                    <a href="logout.php" class="btn-primary"
                        style="background: #ef4444; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);">
                        Sair
                    </a>
                </div>
            </div>
        </div>
    </main>

    <nav class="navbar">
        <a href="dashboard.php" class="nav-link"><i class="fas fa-home icon"></i><span class="text">Início</span></a>
        <a href="treinos.php" class="nav-link"><i class="fas fa-dumbbell icon"></i><span class="text">Treinos</span></a>
        <a href="progresso.php" class="nav-link"><i class="fas fa-chart-line icon"></i><span class="text">Progresso</span></a>
        <a href="chatbot.php" class="nav-link"><i class="fas fa-robot icon"></i><span class="text">Chatbot</span></a>
        <a href="perfil.php" class="nav-link"><i class="fas fa-user icon"></i><span class="text">Perfil</span></a>
    </nav>
    <script src="js/main.js"></script>
</body>

</html>
