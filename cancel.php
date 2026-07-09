<?php
/**
 * Página de Cancelamento de Pagamento
 * BerserkFit AI
 */

session_start();
require 'ligacao.php';
require 'config_stripe.php';

// Verificar se o utilizador está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Cancelado - BerserkFit</title>
    
    <link rel="stylesheet" href="css/global.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    
    <style>
        .cancel-wrapper {
            max-width: 550px;
            margin: 40px auto;
            padding: 40px 30px;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: var(--sombra-card);
            text-align: center;
            border: 1px solid var(--cor-secundaria);
        }

        .cancel-icon {
            width: 90px;
            height: 90px;
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            margin: 0 auto 25px;
            animation: scaleIn 0.5s ease-out;
        }

        h1.cancel-title {
            font-family: var(--fonte-titulo);
            font-weight: 800;
            color: var(--cor-destaque);
            font-size: 2.2rem;
            margin-bottom: 15px;
            letter-spacing: -0.02em;
        }

        .cancel-msg {
            color: var(--cor-texto);
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 35px;
        }

        .btn-cancel-action {
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
            margin: 5px;
        }

        .btn-cancel-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(28, 12, 59, 0.35);
            background: var(--cor-acento);
        }

        .btn-cancel-secondary {
            display: inline-block;
            background: transparent;
            color: var(--cor-destaque);
            border: 2px solid var(--cor-destaque);
            font-family: var(--fonte-titulo);
            font-weight: 700;
            padding: 12px 33px;
            border-radius: 20px;
            text-decoration: none;
            transition: all 0.3s;
            margin: 5px;
        }

        .btn-cancel-secondary:hover {
            background: rgba(28, 12, 59, 0.05);
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
        <div class="cancel-wrapper fade-in-element">
            <div class="cancel-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            
            <h1 class="cancel-title">Operação Cancelada</h1>
            <p>O processo de pagamento foi interrompido.</p>
            
            <p class="cancel-msg" style="margin-top: 20px;">
                Não te preocupes, nenhuma cobrança foi efetuada. Podes voltar a tentar quando estiveres pronto para subir de nível e libertar o gladiador que há em ti.
            </p>
            
            <div style="display: flex; justify-content: center; flex-wrap: wrap; gap: 10px;">
                <a href="index.php#planos" class="btn-cancel-action">
                    Ver Planos
                </a>
                <a href="perfil.php" class="btn-cancel-secondary">
                    Ir para Perfil
                </a>
            </div>
        </div>
    </main>

    <?php include 'app_navbar.php'; ?>
</body>
</html>
