<?php
session_start();
require "ligacao.php";
require_once __DIR__ . '/functions_2fa.php';

// Redireciona se o utilizador já estiver logado
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Redireciona se não houver um ID temporário (vê se o login inicial foi feito)
if (!isset($_SESSION['tfa_user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['tfa_user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $backup_code = trim($_POST['backup_code'] ?? '');

    if (verify_and_use_backup_code($conn, $user_id, $backup_code)) {
        // Sucesso! Carregar dados do utilizador
        $stmt = $conn->prepare("SELECT nome, email, COALESCE(tipo_usuario, 'Usuario') as tipo_usuario, username FROM user WHERE id_user = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Criar Sessão
        $_SESSION['user_id']    = $user_id;
        $_SESSION['user_nome']  = $user['nome'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_tipo']  = $user['tipo_usuario'];
        $_SESSION['username']   = $user['username'] ?? 'Utilizador';

        // Limpar temporários
        unset($_SESSION['tfa_user_id'], $_SESSION['tfa_username'], $_SESSION['tfa_google_login']);

        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Código de recuperação inválido ou já utilizado.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Recuperação de Acesso - BerserkFit</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #06b6d4; --bg: #0f172a; --card: rgba(30, 41, 59, 0.7); }
        body { font-family: 'Outfit', sans-serif; background: var(--bg); color: #f8fafc; height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
        .card { background: var(--card); backdrop-filter: blur(12px); padding: 2rem; border-radius: 1rem; border: 1px solid rgba(255,255,255,0.1); width: 90%; max-width: 420px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
        h1 { font-size: 1.5rem; margin-bottom: 1rem; text-align: center; color: var(--primary); }
        p { font-size: 0.9rem; color: #94a3b8; line-height: 1.5; margin-bottom: 1.5rem; }
        input { width: 100%; padding: 0.8rem; border-radius: 0.5rem; border: 1px solid #334155; background: #0f172a; color: white; border-box: box-sizing; margin-bottom: 1rem; font-size: 1.1rem; text-transform: uppercase; text-align: center; }
        .btn { width: 100%; padding: 0.8rem; border: none; border-radius: 0.5rem; background: var(--primary); color: white; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn:hover { opacity: 0.9; }
        .error { background: #fee2e2; color: #b91c1c; padding: 0.8rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.85rem; text-align: center; }
        .back { display: block; text-align: center; margin-top: 1.5rem; color: #64748b; text-decoration: none; font-size: 0.85rem; }
        @media (max-width: 480px) {
            .card { padding: 1.5rem; max-width: 90%; }
            h1 { font-size: 1.3rem; }
            p { font-size: 0.85rem; }
            input { font-size: 1rem; padding: 0.7rem; }
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔓 Recuperação</h1>
        <p>Introduz um dos códigos de backup que guardaste na configuração do 2FA para recuperar o acesso à tua conta.</p>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="backup_code" placeholder="XXXX-XXXX" maxlength="9" required>
            <button type="submit" class="btn">Entrar na Conta</button>
        </form>

        <a href="login_2fa.php" class="back">Voltar à verificação normal</a>
    </div>
</body>
</html>
