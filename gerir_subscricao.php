<?php
/**
 * Página de Gestão de Subscrição
 * BerserkFit AI
 */

session_start();
require 'ligacao.php';

// Verificar se o utilizador está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['user_id'];

// Procurar dados do utilizador
$sql = "SELECT tipo_plano, data_expiracao_plano, stripe_customer_id FROM user WHERE id_user = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$plano_atual = $user['tipo_plano'] ?? 'gratuito';
$expira_em = $user['data_expiracao_plano'] ?? null;
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscrição - BerserkFit</title>
    
    <link rel="stylesheet" href="css/global.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    
    <style>
        .sub-container {
            max-width: 1000px;
            margin: 0 auto;
            padding-bottom: 50px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--cor-destaque);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: opacity 0.2s;
        }

        .back-link:hover {
            opacity: 0.8;
        }

        .sub-header {
            margin-bottom: 30px;
        }

        .sub-header h1 {
            font-family: var(--fonte-titulo);
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--cor-destaque);
            margin: 0 0 8px 0;
            letter-spacing: -0.02em;
        }

        .sub-header p {
            color: var(--text-gray);
            margin: 0;
            font-size: 1.05rem;
        }

        /* Grid de Planos */
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin-top: 30px;
        }

        @media (max-width: 900px) {
            .plans-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        .plan-card {
            background: #ffffff;
            border: 1px solid var(--cor-secundaria);
            border-radius: 24px;
            padding: 35px 25px;
            display: flex;
            flex-direction: column;
            position: relative;
            box-shadow: var(--sombra-card);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(28, 12, 59, 0.15);
        }

        .plan-card.active-plan {
            border: 2px solid var(--cor-acento);
        }

        .plan-card.featured-plan {
            border: 2px solid var(--cor-acento);
            background: linear-gradient(180deg, #ffffff 0%, #faf9ff 100%);
        }

        .plan-badge-top {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--cor-acento);
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 16px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .plan-badge-top.gold {
            background: var(--cor-amarela);
            color: #000;
        }

        .plan-name {
            font-family: var(--fonte-titulo);
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--cor-destaque);
            margin: 0 0 10px 0;
            text-align: center;
        }

        .plan-price {
            font-family: var(--fonte-titulo);
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--cor-destaque);
            text-align: center;
            margin: 10px 0;
        }

        .plan-price span {
            font-size: 1rem;
            color: var(--text-gray);
            font-weight: 500;
        }

        .plan-features {
            list-style: none;
            padding: 0;
            margin: 25px 0 35px 0;
            flex-grow: 1;
        }

        .plan-features li {
            font-size: 0.95rem;
            color: var(--cor-texto);
            margin-bottom: 12px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.4;
        }

        .plan-features li i {
            color: #10b981; /* Verde */
            margin-top: 3px;
            font-size: 0.9rem;
        }

        .plan-btn {
            display: block;
            width: 100%;
            padding: 14px 20px;
            border-radius: 16px;
            text-align: center;
            text-decoration: none;
            font-family: var(--fonte-titulo);
            font-weight: 700;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            box-sizing: border-box;
        }

        .plan-btn.btn-disabled {
            background: var(--cor-secundaria);
            color: var(--text-gray);
            cursor: not-allowed;
        }

        .plan-btn.btn-active {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 2px solid #10b981;
            cursor: default;
        }

        .plan-btn.btn-outline {
            background: transparent;
            border: 2px solid var(--cor-destaque);
            color: var(--cor-destaque);
        }

        .plan-btn.btn-outline:hover {
            background: rgba(28, 12, 59, 0.05);
        }

        .plan-btn.btn-filled {
            background: var(--cor-destaque);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(28, 12, 59, 0.15);
        }

        .plan-btn.btn-filled:hover {
            background: var(--cor-acento);
            transform: translateY(-2px);
        }

        .plan-btn.btn-gold {
            background: var(--cor-amarela);
            color: #000000;
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.2);
        }

        .plan-btn.btn-gold:hover {
            background: #e6c200;
            transform: translateY(-2px);
        }

        /* Painel do Portal de Faturamento */
        .billing-portal-card {
            background: #ffffff;
            border: 1px solid var(--cor-secundaria);
            border-radius: 24px;
            padding: 30px;
            margin-top: 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--sombra-card);
            gap: 20px;
        }

        @media (max-width: 768px) {
            .billing-portal-card {
                flex-direction: column;
                text-align: center;
            }
        }

        .billing-info h3 {
            font-family: var(--fonte-titulo);
            font-size: 1.3rem;
            color: var(--cor-destaque);
            margin: 0 0 6px 0;
            font-weight: 700;
        }

        .billing-info p {
            color: var(--text-gray);
            margin: 0;
            font-size: 0.95rem;
            line-height: 1.5;
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
        <div class="sub-container fade-in-element">
            <a href="configuracoes.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar às Configurações
            </a>

            <div class="sub-header">
                <h1>Gerir Subscrição</h1>
                <p>Escolhe o plano ideal para a tua jornada de evolução ou gere os teus pagamentos ativos.</p>
            </div>

            <!-- Grid de Planos -->
            <div class="plans-grid">
                
                <!-- PLANO SPARTAN -->
                <div class="plan-card <?= ($plano_atual === 'gratuito') ? 'active-plan' : '' ?>">
                    <h2 class="plan-name">Spartan</h2>
                    <div class="plan-price">Grátis<span> / para sempre</span></div>
                    
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Gerador de treinos básico</li>
                        <li><i class="fas fa-check"></i> Checklist diária para 5 tarefas</li>
                        <li><i class="fas fa-check"></i> Acesso à comunidade</li>
                        <li><i class="fas fa-check"></i> Exportação de treinos</li>
                    </ul>

                    <?php if ($plano_atual === 'gratuito'): ?>
                        <div class="plan-btn btn-active"><i class="fas fa-check-circle"></i> Plano Ativo</div>
                    <?php else: ?>
                        <div class="plan-btn btn-disabled">Acesso Gratuito</div>
                    <?php endif; ?>
                </div>

                <!-- PLANO GLADIATOR -->
                <div class="plan-card <?= ($plano_atual === 'gladiator') ? 'active-plan' : '' ?> featured-plan">
                    <div class="plan-badge-top">Mais Popular 🔥</div>
                    <h2 class="plan-name">Gladiator</h2>
                    <div class="plan-price">€19,90<span> / mês</span></div>
                    
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Tudo do plano Spartan</li>
                        <li><i class="fas fa-check"></i> Gerador de treinos com IA avançada</li>
                        <li><i class="fas fa-check"></i> Histórico de consistência completo</li>
                        <li><i class="fas fa-check"></i> Checklist diária ilimitada</li>
                    </ul>

                    <?php if ($plano_atual === 'gladiator'): ?>
                        <div class="plan-btn btn-active"><i class="fas fa-check-circle"></i> Plano Ativo</div>
                    <?php elseif ($plano_atual === 'berserker'): ?>
                        <div class="plan-btn btn-disabled">Incluído no Berserker</div>
                    <?php else: ?>
                        <a href="checkout.php?plano=gladiator" class="plan-btn btn-filled">Subscrever Gladiator</a>
                    <?php endif; ?>
                </div>

                <!-- PLANO BERSERKER -->
                <div class="plan-card <?= ($plano_atual === 'berserker') ? 'active-plan' : '' ?>">
                    <div class="plan-badge-top gold">Lendário ⚡</div>
                    <h2 class="plan-name">Berserker</h2>
                    <div class="plan-price">€39,90<span> / mês</span></div>
                    
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Tudo do plano Gladiator</li>
                        <li><i class="fas fa-check"></i> Notificações motivacionais personalizadas</li>
                        <li><i class="fas fa-check"></i> Suporte prioritário via chat</li>
                        <li><i class="fas fa-check"></i> Acesso antecipado a novas funções</li>
                    </ul>

                    <?php if ($plano_atual === 'berserker'): ?>
                        <div class="plan-btn btn-active"><i class="fas fa-check-circle"></i> Plano Ativo</div>
                    <?php else: ?>
                        <a href="checkout.php?plano=berserker" class="plan-btn btn-gold">Subscrever Berserker</a>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Secção de Gestão de Faturação (Se tiver um plano pago ativo) -->
            <?php if ($plano_atual !== 'gratuito'): ?>
                <div class="billing-portal-card">
                    <div class="billing-info">
                        <h3>Gestão de Faturação Segura</h3>
                        <p>
                            O teu plano <strong><?= ucfirst($plano_atual) ?></strong> está ativo. A próxima cobrança ocorre em <strong><?= date('d/m/Y', strtotime($expira_em)) ?></strong>.
                            Acede ao Portal de Clientes da Stripe para ver faturas, alterar o cartão ou cancelar a assinatura.
                        </p>
                    </div>
                    <a href="configuracoes.php?action=portal_stripe" class="plan-btn btn-outline" style="max-width: 250px; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fas fa-external-link-alt"></i> Gerir Pagamento
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <?php include 'app_navbar.php'; ?>
</body>
</html>
