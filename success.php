<?php
/**
 * Página de Sucesso de Pagamento
 * BerserkFit AI
 */

session_start();
require 'ligacao.php';
require 'config_stripe.php';
require 'vendor/autoload.php';

// Verificar se o utilizador está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$session_id = isset($_GET['session_id']) ? $_GET['session_id'] : '';
$plano_nome = "Plano Adquirido";
$id_user = $_SESSION['user_id'];

if (!empty($session_id)) {
    try {
        // Inicializar Stripe com a Secret Key
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        // Buscar a sessão para saber qual foi o plano comprado
        $session = \Stripe\Checkout\Session::retrieve($session_id);
        
        if ($session && isset($session->metadata->tipo_plano)) {
            $plano_key = $session->metadata->tipo_plano;
            $plano_nome = ($plano_key === 'gladiator') ? 'Plano Gladiator 🛡️' : 'Plano Berserker ⚡';

            // ✅ FALLBACK: Atualizar o plano diretamente na BD
            // (Necessário em localhost onde o webhook da Stripe não consegue fazer callback)
            // Em produção, o webhook também irá processar — a verificação de duplicados evita conflitos.
            $stripe_customer_id = $session->customer ?? null;
            $data_expiracao = date('Y-m-d', strtotime('+30 days'));

            $stmt_check = $conn->prepare("SELECT tipo_plano FROM user WHERE id_user = ?");
            $stmt_check->bind_param("i", $id_user);
            $stmt_check->execute();
            $res_check = $stmt_check->get_result()->fetch_assoc();
            $stmt_check->close();

            // Só atualiza se o plano ainda for gratuito (evita sobrescrever se o webhook já atualizou)
            if ($res_check && ($res_check['tipo_plano'] === 'gratuito' || $res_check['tipo_plano'] === null)) {
                $stmt_upd = $conn->prepare("UPDATE user SET tipo_plano = ?, data_expiracao_plano = ?, stripe_customer_id = ? WHERE id_user = ?");
                $stmt_upd->bind_param("sssi", $plano_key, $data_expiracao, $stripe_customer_id, $id_user);
                $stmt_upd->execute();
                $stmt_upd->close();
            }
        }
    } catch (\Exception $e) {
        // Ignorar erros na busca da sessão (o webhook tratará de ativar a conta)
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Concluído - BerserkFit</title>
    
    <link rel="stylesheet" href="css/global.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    
    <style>
        .success-wrapper {
            max-width: 550px;
            margin: 40px auto;
            padding: 40px 30px;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: var(--sombra-card);
            text-align: center;
            border: 1px solid var(--cor-secundaria);
        }

        .success-icon {
            width: 90px;
            height: 90px;
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            margin: 0 auto 25px;
            animation: scaleIn 0.5s ease-out;
        }

        h1.success-title {
            font-family: var(--fonte-titulo);
            font-weight: 800;
            color: var(--cor-destaque);
            font-size: 2.2rem;
            margin-bottom: 15px;
            letter-spacing: -0.02em;
        }

        .plan-badge {
            display: inline-block;
            background: rgba(28, 12, 59, 0.05);
            border: 1px solid var(--cor-secundaria);
            color: var(--cor-destaque);
            font-weight: 700;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 1.1rem;
            margin: 15px 0 25px;
        }

        .success-msg {
            color: var(--cor-texto);
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 35px;
        }

        .btn-success-action {
            display: inline-block;
            background: var(--cor-destaque);
            color: var(--cor-texto-claro);
            font-family: var(--fonte-titulo);
            font-weight: 700;
            padding: 14px 35px;
            border-radius: 20px;
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(28, 12, 59, 0.25);
            transition: all 0.3s;
        }

        .btn-success-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(28, 12, 59, 0.35);
            background: var(--cor-acento);
        }

        @keyframes scaleIn {
            0% { transform: scale(0); }
            80% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>

    <header class="fade-in-element">
        <div class="header-top centered">
            <h1 class="app-title">BerserkFit AI</h1>
        </div>
    </header>

    <main>
        <div class="success-wrapper fade-in-element">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <h1 class="success-title">Vitória, Guerreiro!</h1>
            <p>O teu pagamento foi processado e aprovado.</p>
            
            <div class="plan-badge">
                <?= htmlspecialchars($plano_nome) ?>
            </div>
            
            <p class="success-msg">
                O teu plano está a ser ativado no sistema de forma segura. Em segundos, terás acesso a todas as funcionalidades premium do teu novo arsenal. A nossa IA está pronta para forjar os teus novos limites!
            </p>
            
            <a href="perfil.php" class="btn-success-action">
                Ir para o Perfil
            </a>
        </div>
    </main>

    <?php include 'app_navbar.php'; ?>
</body>
</html>
