<?php
require "ligacao.php";
require_once __DIR__ . '/functions_2fa.php';

session_start();

// Verifica se o utilizador está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$secret = null;
$qr_code_url = null;

// 1. Obter o segredo atual do utilizador
$stmt = $conn->prepare("SELECT tfa_secret, username FROM user WHERE id_user = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$current_secret = $user['tfa_secret'];
$username = $user['username'];

// 2. Lógica para desativar 2FA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disable_tfa'])) {
    $stmt = $conn->prepare("UPDATE user SET tfa_secret = NULL, tfa_backup_codes = NULL WHERE id_user = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    $success = "Autenticação de Dois Fatores desativada com sucesso.";
    $current_secret = null;
}

// 2b. Lógica para regenerar códigos de backup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['regenerate_codes'])) {
    $backup_codes = generate_backup_codes(8);
    save_backup_codes($conn, $user_id, $backup_codes);
    $_SESSION['tfa_just_activated_codes'] = $backup_codes;
    $success = "Novos códigos de recuperação gerados com sucesso!";
}

// 3. Lógica para ativar/configurar 2FA
if (!$current_secret) {
    // Gerar um segredo temporário para a sessão
    if (!isset($_SESSION['tfa_temp_secret'])) {
        $tfa = get_tfa_instance();
        $secret = $tfa->createSecret();
        $_SESSION['tfa_temp_secret'] = $secret;

        // Salvar temporariamente na base de dados
        $stmt = $conn->prepare("UPDATE user SET tfa_secret = ? WHERE id_user = ?");
        $stmt->bind_param("si", $secret, $user_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $secret = $_SESSION['tfa_temp_secret'];
    }

    $qr_code_url = get_tfa_qr_code_url($secret, $username);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
        $code = trim($_POST['code']);
        if (verify_tfa_code($secret, $code)) {
            // O segredo já foi guardado na base de dados
            unset($_SESSION['tfa_temp_secret']);
            
            // Gerar códigos de backup pela primeira vez
            $backup_codes = generate_backup_codes(8);
            save_backup_codes($conn, $user_id, $backup_codes);
            $_SESSION['tfa_just_activated_codes'] = $backup_codes;
            
            $success = "Autenticação de Dois Fatores ativada com sucesso!";
            $current_secret = $secret; // Atualiza o estado
        } else {
            $error = "Código de verificação inválido. Tente novamente.";
        }
    }
}

// Se o 2FA estiver ativo, o segredo é o da base de dados
if ($current_secret) {
    $secret = $current_secret;
}

?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Configurar 2FA - BerserkFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/global.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/setup_2fa.css?v=<?= time() ?>">
</head>

<body>

    <div class="container">
        <div class="card">
            <div class="header">
                <div class="logo-icon">🔑</div>
                <h1 class="title">Autenticação de Dois Fatores (2FA)</h1>
            </div>

            <?php if (!empty($error)): ?>
                <div class="message error">
                    <span>⚠️</span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="message success">
                    <span>✅</span>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
                
                <?php if (isset($_SESSION['tfa_just_activated_codes'])): ?>
                    <div class="backup-codes-section" style="margin-top: 20px; padding: 15px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                        <h3 style="color: #0f172a; font-size: 1rem; margin-bottom: 10px;">⚠️ IMPORTANTE: Códigos de Recuperação</h3>
                        <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 15px;">Guarda estes códigos num local seguro. Se perderes o acesso ao teu telemóvel, só conseguirás entrar com um destes códigos.</p>
                        <div class="codes-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <?php foreach ($_SESSION['tfa_just_activated_codes'] as $bcode): ?>
                                <code style="background: #fff; padding: 5px; border: 1px solid #cbd5e1; border-radius: 4px; text-align: center; font-weight: bold;"><?= $bcode ?></code>
                            <?php endforeach; ?>
                        </div>
                        <button onclick="window.print()" class="btn btn-secondary" style="margin-top: 15px; font-size: 0.8rem; padding: 8px;">🖨️ Imprimir / Guardar PDF</button>
                    </div>
                    <?php unset($_SESSION['tfa_just_activated_codes']); ?>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($current_secret): ?>
                <!-- 2FA ATIVO -->
                <div class="status-box">
                    <p class="status-title success-text">2FA Ativado com Sucesso</p>
                    <p class="status-desc">A sua conta está protegida com a Autenticação de Dois Fatores.</p>
                </div>
                <form method="POST" style="margin-bottom: 10px;">
                    <input type="hidden" name="regenerate_codes" value="1">
                    <button type="submit" class="btn btn-primary btn-spaced"
                        onclick="return confirm('Tens a certeza? Os códigos antigos vão ser substituídos.');">
                        🔄 Regenerar Códigos de Recuperação
                    </button>
                </form>
                <form method="POST">
                    <input type="hidden" name="disable_tfa" value="1">
                    <button type="submit" class="btn btn-danger btn-spaced"
                        onclick="return confirm('Tem certeza que deseja desativar o 2FA?');">
                        ❌ Desativar 2FA
                    </button>
                </form>

            <?php else: ?>
                <!-- 2FA INATIVO - PASSO 1: Exibir QR Code -->
                <div class="status-box">
                    <p class="status-title primary-text">Ativar Proteção 2FA</p>
                    <p class="status-desc">Siga os passos abaixo para configurar:</p>
                </div>

                <ol class="steps-list">
                    <li>Instale uma aplicação de autenticação (ex: Google Authenticator, Authy).</li>
                    <li>Digitalize o código QR abaixo com a aplicação.</li>
                    <li>Introduza o código de 6 dígitos para confirmar.</li>
                </ol>

                <div class="qr-container">
                    <img src="<?= htmlspecialchars($qr_code_url) ?>" alt="QR Code 2FA">
                </div>

                <div class="manual-code-box">
                    <p class="manual-code-label">Ou insira o código manualmente:</p>
                    <code class="manual-code select-all"><?= htmlspecialchars($secret) ?></code>
                </div>

                <!-- PASSO 2: Verificar Código -->
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Código de 6 Dígitos</label>
                        <input type="text" name="code" placeholder="000 000" required maxlength="6" pattern="\d{6}">
                    </div>
                    <input type="hidden" name="verify_code" value="1">
                    <button type="submit" class="btn btn-primary">
                        ✅ Verificar e Ativar 2FA
                    </button>
                </form>

            <?php endif; ?>

            <div class="footer-spaced">
                <a href="configuracoes.php" class="footer-link">⬅ Voltar às Configurações</a>
            </div>
        </div>
    </div>
</body>

</html>
