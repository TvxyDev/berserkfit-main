<?php
/**
 * Página para Utilizadores Deixarem Testemunhos
 * BerserkFit AI
 */

session_start();
require 'ligacao.php';

// Verificar se o utilizador está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?msg=login_required_testimonial");
    exit;
}

$id_user = $_SESSION['user_id'];
$mensagem = "";
$erro = "";

// Processar o formulário de testemunho
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estrelas = intval($_POST['estrelas'] ?? 0);
    $texto = trim($_POST['texto'] ?? '');

    // Validações
    if ($estrelas < 1 || $estrelas > 5) {
        $erro = "Por favor, selecione uma classificação entre 1 e 5 estrelas.";
    } elseif (empty($texto)) {
        $erro = "Por favor, escreva a sua opinião.";
    } elseif (strlen($texto) < 10) {
        $erro = "A sua opinião deve ter pelo menos 10 caracteres.";
    } else {
        // Inserir testemunho pendente na base de dados
        $sql = "INSERT INTO testemunho (id_user, estrelas, texto, aprovado) VALUES (?, ?, ?, 0)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("iis", $id_user, $estrelas, $texto);
            if ($stmt->execute()) {
                $mensagem = "✅ Muito obrigado pela sua opinião! O seu testemunho foi enviado para moderação e aparecerá na homepage assim que for aprovado pelo administrador.";
            } else {
                $erro = "❌ Ocorreu um erro ao submeter o seu testemunho. Tente novamente.";
            }
            $stmt->close();
        } else {
            $erro = "❌ Erro ao preparar a base de dados.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deixar Testemunho - BerserkFit</title>
    
    <link rel="stylesheet" href="css/global.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    
    <style>
        .testemunho-container {
            max-width: 650px;
            margin: 0 auto;
            padding: 20px;
            padding-bottom: 50px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--cor-destaque);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 25px;
            transition: opacity 0.2s;
        }

        .back-link:hover {
            opacity: 0.8;
        }

        .card-testemunho {
            background: #ffffff;
            border: 1px solid var(--cor-secundaria);
            border-radius: 24px;
            padding: 40px 30px;
            box-shadow: var(--sombra-card);
        }

        .card-testemunho h1 {
            font-family: var(--fonte-titulo);
            font-size: 2rem;
            font-weight: 800;
            color: var(--cor-destaque);
            margin: 0 0 10px 0;
            letter-spacing: -0.02em;
            text-align: center;
        }

        .card-testemunho p.subtitle {
            color: var(--text-gray);
            margin: 0 0 30px 0;
            font-size: 1.05rem;
            text-align: center;
            line-height: 1.5;
        }

        /* Estrelas interativas */
        .rating-stars {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 25px;
            direction: rtl; /* Para animar de volta ao hover */
        }

        .rating-stars input {
            display: none;
        }

        .rating-stars label {
            font-size: 2.5rem;
            color: #d1d5db; /* Cinza claro */
            cursor: pointer;
            transition: color 0.2s, transform 0.2s;
        }

        .rating-stars label:hover,
        .rating-stars label:hover ~ label,
        .rating-stars input:checked ~ label {
            color: var(--cor-amarela);
            transform: scale(1.15);
        }

        .rating-stars label:active {
            transform: scale(0.9);
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 25px;
        }

        .form-group label {
            font-weight: 600;
            color: var(--cor-destaque-escuro);
            font-size: 1rem;
        }

        .form-group textarea {
            padding: 15px;
            border: 1px solid var(--cor-secundaria);
            border-radius: 12px;
            font-size: 1rem;
            font-family: var(--fonte-texto);
            resize: vertical;
            min-height: 150px;
            background: #fafafc;
            color: var(--cor-texto);
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group textarea:focus {
            outline: none;
            border-color: var(--cor-acento);
            box-shadow: 0 0 0 3px rgba(124, 92, 191, 0.15);
            background: #ffffff;
        }

        .btn-submit {
            display: block;
            width: 100%;
            padding: 15px 24px;
            border-radius: 16px;
            background: var(--cor-destaque);
            color: #ffffff;
            font-family: var(--fonte-titulo);
            font-size: 1.1rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 12px rgba(28, 12, 59, 0.15);
        }

        .btn-submit:hover {
            background: var(--cor-acento);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(124, 92, 191, 0.25);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .alert-box {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .alert-box.success {
            background-color: rgba(16, 185, 129, 0.1);
            color: #065f46;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .alert-box.error {
            background-color: rgba(239, 68, 68, 0.1);
            color: #991b1b;
            border: 1px solid rgba(239, 68, 68, 0.2);
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
        <div class="testemunho-container fade-in-element">
            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
            </a>

            <div class="card-testemunho">
                <h1>Deixe a sua Opinião ⚔️</h1>
                <p class="subtitle">A sua opinião é fundamental para forjarmos uma comunidade mais forte. Diga-nos o que acha do BerserkFit!</p>

                <?php if ($mensagem != ""): ?>
                    <div class="alert-box success">
                        <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
                        <?php echo $mensagem; ?>
                    </div>
                <?php endif; ?>

                <?php if ($erro != ""): ?>
                    <div class="alert-box error">
                        <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
                        <?php echo $erro; ?>
                    </div>
                <?php endif; ?>

                <?php if ($mensagem == ""): ?>
                    <form method="POST" action="">
                        <div class="form-group" style="text-align: center;">
                            <label>Classificação (1 a 5 estrelas)</label>
                            <div class="rating-stars">
                                <input type="radio" id="star5" name="estrelas" value="5" required />
                                <label for="star5" title="Excelente"><i class="fas fa-star"></i></label>
                                
                                <input type="radio" id="star4" name="estrelas" value="4" />
                                <label for="star4" title="Muito Bom"><i class="fas fa-star"></i></label>
                                
                                <input type="radio" id="star3" name="estrelas" value="3" />
                                <label for="star3" title="Bom"><i class="fas fa-star"></i></label>
                                
                                <input type="radio" id="star2" name="estrelas" value="2" />
                                <label for="star2" title="Razoável"><i class="fas fa-star"></i></label>
                                
                                <input type="radio" id="star1" name="estrelas" value="1" />
                                <label for="star1" title="Fraco"><i class="fas fa-star"></i></label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="texto">A sua opinião/testemunho</label>
                            <textarea id="texto" name="texto" placeholder="Escreva aqui a sua opinião honesta sobre a plataforma, treinos, chatbot IA, etc..." required minlength="10"></textarea>
                        </div>

                        <button type="submit" class="btn-submit">
                            <i class="fas fa-paper-plane" style="margin-right: 8px;"></i> Enviar Opinião
                        </button>
                    </form>
                <?php else: ?>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="dashboard.php" class="btn-submit" style="display: inline-block; width: auto; text-decoration: none; padding: 12px 30px;">
                            Ir para o Dashboard
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'app_navbar.php'; ?>
</body>
</html>
