<?php
session_start();

// Verifica se o utilizador está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'ligacao.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$user_id = $_SESSION['user_id'];
$mensagem = '';
$tipo_mensagem = 'success'; // 'success' ou 'error'

// ─── Criar tabela de tokens se não existir ───────────────────────────────────

// ─── Buscar dados do utilizador ───────────────────────────────────────────────
$stmt = $conn->prepare("SELECT nome, email FROM user WHERE id_user = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$user_email = $user['email'];
$user_nome = $user['nome'];

// ─── Determinar passo atual ───────────────────────────────────────────────────
// Passo 1: enviar código | Passo 2: verificar código | Passo 3: nova senha
if (!isset($_SESSION['reset_step'])) {
    $_SESSION['reset_step'] = 1;
}
$passo = $_SESSION['reset_step'];

// ─── Processar ações POST ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // ── PASSO 1: Enviar código por email ─────────────────────────────────────
    if ($acao === 'enviar_codigo') {
        // Invalidar tokens anteriores do utilizador
        $stmt = $conn->prepare("UPDATE password_reset_tokens SET used = 1 WHERE id_user = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // Gerar código de 6 dígitos
        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        // Guardar na BD
        $stmt = $conn->prepare("INSERT INTO password_reset_tokens (id_user, token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $codigo, $expires);
        $stmt->execute();
        $stmt->close();

        // Enviar email com PHPMailer
        $mail = new PHPMailer(true);

        // ════════════════════════════════════════════════════════════
        // BREVO SMTP
        // ════════════════════════════════════════════════════════════
        define('SMTP_EMAIL',    'a571fb001@smtp-brevo.com');
        define('SMTP_PASSWORD', 'xsmtpsib-13610b4475aab7e3693ff0bea8415cab8363c70e602e6c51fd10533205843e9f-cH3A0MgAvffydzgL');
        // ════════════════════════════════════════════════════════════

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp-relay.brevo.com';
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_EMAIL;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            // ── Remetente e destinatário ─────────────────────────────────────
            $mail->setFrom('contato.berserkfit@gmail.com', 'BerserkFit');
            $mail->addAddress($user_email, $user_nome);

            // ── Conteúdo do email ─────────────────────────────────────────────
            $mail->isHTML(true);
            $mail->Subject = 'BerserkFit — Código para Alterar Palavra-passe';
            $mail->Body = "
            <div style='font-family: Inter, sans-serif; max-width: 500px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #1a0533, #2d1058); padding: 30px; border-radius: 16px 16px 0 0; text-align: center;'>
                    <h1 style='color: #c4b5fd; font-size: 2em; margin: 0;'>🔒 BerserkFit</h1>
                </div>
                <div style='background: #ffffff; padding: 30px; border-radius: 0 0 16px 16px; border: 1px solid #e5e7eb;'>
                    <p style='color: #374151; font-size: 1rem;'>Olá, <strong>{$user_nome}</strong>!</p>
                    <p style='color: #374151;'>Recebemos um pedido para alterar a tua palavra-passe. Usa o seguinte código para confirmar:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <span style='background: #f3f0ff; color: #6d28d9; font-size: 2.5rem; font-weight: 800; letter-spacing: 10px; padding: 15px 25px; border-radius: 12px; border: 2px solid #c4b5fd;'>
                            {$codigo}
                        </span>
                    </div>
                    <p style='color: #6b7280; font-size: 0.9rem;'>⏱ Este código é válido durante <strong>10 minutos</strong>.</p>
                    <p style='color: #6b7280; font-size: 0.9rem;'>Se não pediste esta alteração, ignora este email.</p>
                </div>
            </div>";
            $mail->AltBody = "BerserkFit — Código para alterar palavra-passe: {$codigo} (válido 10 minutos)";

            $mail->send();
            $_SESSION['reset_step'] = 2;
            $passo = 2;
            $mensagem = '✅ Código enviado para o teu email! Verifica a caixa de entrada (e o spam).';
        } catch (Exception $e) {
            // ── Modo DEBUG (localhost) ────────────────────────────────────────
            // Quando o email falha, mostra o código no ecrã para testes.
            // REMOVE este bloco em produção (deixa só o erro).
            $is_localhost = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);
            if ($is_localhost) {
                $_SESSION['reset_step'] = 2;
                $passo = 2;
                $mensagem = "⚠️ Email não enviado (SMTP não configurado). Código para debug: {$codigo}";
                $tipo_mensagem = 'success'; // avança mesmo assim
            } else {
                $tipo_mensagem = 'error';
                $mensagem = '❌ Erro ao enviar email. Por favor tenta novamente mais tarde.';
            }
        }
    }

    // ── PASSO 2: Verificar código ─────────────────────────────────────────────
    elseif ($acao === 'verificar_codigo') {
        $codigo_inserido = trim($_POST['codigo'] ?? '');

        $stmt = $conn->prepare("
            SELECT id FROM password_reset_tokens
            WHERE id_user = ? AND token = ? AND used = 0 AND expires_at > NOW()
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->bind_param("is", $user_id, $codigo_inserido);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            $token_row = $result->fetch_assoc();
            $_SESSION['reset_token_id'] = $token_row['id'];
            $_SESSION['reset_step'] = 3;
            $passo = 3;
            $mensagem = '✅ Código correto! Define agora a tua nova palavra-passe.';
        } else {
            $tipo_mensagem = 'error';
            $mensagem = '❌ Código inválido ou expirado. Tenta novamente.';
        }
    }

    // ── PASSO 3: Definir nova senha ──────────────────────────────────────────
    elseif ($acao === 'nova_senha') {
        $nova = $_POST['nova_senha'] ?? '';
        $confirmar = $_POST['confirmar_senha'] ?? '';
        $token_id = $_SESSION['reset_token_id'] ?? 0;

        if (strlen($nova) < 8) {
            $tipo_mensagem = 'error';
            $mensagem = '❌ A palavra-passe deve ter pelo menos 8 caracteres.';
        } elseif ($nova !== $confirmar) {
            $tipo_mensagem = 'error';
            $mensagem = '❌ As palavras-passe não coincidem.';
        } elseif (!$token_id) {
            $tipo_mensagem = 'error';
            $mensagem = '❌ Sessão inválida. Reinicia o processo.';
        } else {
            // Atualizar senha
            $hash = password_hash($nova, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE user SET password_hash = ? WHERE id_user = ?");
            $stmt->bind_param("si", $hash, $user_id);
            $stmt->execute();
            $stmt->close();

            // Invalidar token
            $stmt = $conn->prepare("UPDATE password_reset_tokens SET used = 1 WHERE id = ?");
            $stmt->bind_param("i", $token_id);
            $stmt->execute();
            $stmt->close();

            // Limpar sessão temporária
            unset($_SESSION['reset_step'], $_SESSION['reset_token_id']);

            header("Location: configuracoes.php?msg=senha_alterada");
            exit;
        }
    }

    // Cancelar
    elseif ($acao === 'cancelar') {
        unset($_SESSION['reset_step'], $_SESSION['reset_token_id']);
        header("Location: configuracoes.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Palavra-passe — BerserkFit</title>
    <link rel="stylesheet" href="css/global.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/perfil.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700&family=Inter:wght@400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/alterar_senha.css">
</head>

<body>
    <main class="main-content" style="padding-top: 40px;">
        <div class="senha-container">
            <a href="configuracoes.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar às Configurações
            </a>

            <div class="senha-card">

                <!-- Indicador de passos -->
                <div class="passos-indicador">
                    <div class="passo-dot <?= $passo >= 1 ? ($passo > 1 ? 'feito' : 'ativo') : '' ?>"></div>
                    <div class="passo-linha <?= $passo > 1 ? 'feita' : '' ?>"></div>
                    <div class="passo-dot <?= $passo >= 2 ? ($passo > 2 ? 'feito' : 'ativo') : '' ?>"></div>
                    <div class="passo-linha <?= $passo > 2 ? 'feita' : '' ?>"></div>
                    <div class="passo-dot <?= $passo >= 3 ? 'ativo' : '' ?>"></div>
                </div>

                <!-- Mensagem de feedback -->
                <?php if ($mensagem): ?>
                    <div class="alerta <?= $tipo_mensagem ?>">
                        <?= htmlspecialchars($mensagem) ?>
                    </div>
                <?php endif; ?>

                <?php if ($passo === 1): ?>
                    <!-- ═══════════════════════════════════════════════════════ -->
                    <!-- PASSO 1 — Enviar código                                -->
                    <!-- ═══════════════════════════════════════════════════════ -->
                    <div class="passo-header">
                        <div class="passo-icone">📧</div>
                        <h1>Verificar Identidade</h1>
                        <p>Vamos enviar um código de verificação para o teu email.</p>
                    </div>

                    <div class="email-card">
                        <i class="fas fa-envelope"></i>
                        <span><?= htmlspecialchars($user_email) ?></span>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="acao" value="enviar_codigo">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-paper-plane"></i>&nbsp; Enviar Código por Email
                        </button>
                    </form>

                    <form method="POST" style="margin-top: 10px;">
                        <input type="hidden" name="acao" value="cancelar">
                        <button type="submit" class="btn-secondary-link">Cancelar</button>
                    </form>

                <?php elseif ($passo === 2): ?>
                    <!-- ═══════════════════════════════════════════════════════ -->
                    <!-- PASSO 2 — Inserir código                               -->
                    <!-- ═══════════════════════════════════════════════════════ -->
                    <div class="passo-header">
                        <div class="passo-icone">🔢</div>
                        <h1>Código de Verificação</h1>
                        <p>Introduz o código de 6 dígitos enviado para <span
                                class="email-highlight"><?= htmlspecialchars($user_email) ?></span></p>
                    </div>

                    <form method="POST" id="formCodigo">
                        <input type="hidden" name="acao" value="verificar_codigo">
                        <input type="hidden" name="codigo" id="codigoCompleto">

                        <div class="otp-wrapper">
                            <?php for ($i = 0; $i < 6; $i++): ?>
                                <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="\d"
                                    autocomplete="<?= $i === 0 ? 'one-time-code' : 'off' ?>">
                            <?php endfor; ?>
                        </div>

                        <button type="submit" class="btn-primary">
                            <i class="fas fa-check-circle"></i>&nbsp; Verificar Código
                        </button>
                    </form>

                    <form method="POST" style="margin-top: 10px;">
                        <input type="hidden" name="acao" value="enviar_codigo">
                        <button type="submit" class="btn-secondary-link">Não recebi o código — reenviar</button>
                    </form>

                <?php elseif ($passo === 3): ?>
                    <!-- ═══════════════════════════════════════════════════════ -->
                    <!-- PASSO 3 — Nova senha                                   -->
                    <!-- ═══════════════════════════════════════════════════════ -->
                    <div class="passo-header">
                        <div class="passo-icone">🔑</div>
                        <h1>Nova Palavra-passe</h1>
                        <p>Escolhe uma palavra-passe segura com pelo menos 8 caracteres.</p>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="acao" value="nova_senha">

                        <div class="form-grupo">
                            <label for="nova_senha"><i class="fas fa-lock"></i> Nova Palavra-passe</label>
                            <input type="password" id="nova_senha" name="nova_senha" placeholder="Mínimo 8 caracteres"
                                required oninput="avaliarForca(this.value)">
                            <div class="forca-senha" id="forcaBarra"></div>
                            <span class="forca-texto" id="forcaTexto">Digita para ver a força</span>
                        </div>

                        <div class="form-grupo">
                            <label for="confirmar_senha"><i class="fas fa-lock"></i> Confirmar Palavra-passe</label>
                            <input type="password" id="confirmar_senha" name="confirmar_senha"
                                placeholder="Repete a nova palavra-passe" required>
                        </div>

                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i>&nbsp; Guardar Nova Palavra-passe
                        </button>
                    </form>

                <?php endif; ?>

            </div><!-- /senha-card -->
        </div><!-- /senha-container -->
    </main>

    <nav class="navbar">
        <a href="dashboard.php" class="nav-link"><i class="fas fa-home icon"></i><span class="text">Início</span></a>
        <a href="treinos.php" class="nav-link"><i class="fas fa-dumbbell icon"></i><span class="text">Treinos</span></a>
        <a href="progresso.php" class="nav-link"><i class="fas fa-chart-line icon"></i><span class="text">Progresso</span></a>
        <a href="chatbot.php" class="nav-link"><i class="fas fa-robot icon"></i><span class="text">Chatbot</span></a>
        <a href="perfil.php" class="nav-link"><i class="fas fa-user icon"></i><span class="text">Perfil</span></a>
    </nav>

    <script>
        // ── OTP Input navigation ──────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', () => {
            const digits = document.querySelectorAll('.otp-digit');
            if (!digits.length) return;

            digits[0].focus();

            digits.forEach((input, idx) => {
                input.addEventListener('input', e => {
                    const val = e.target.value.replace(/\D/g, '');
                    e.target.value = val.slice(-1);
                    if (val && idx < digits.length - 1) digits[idx + 1].focus();
                    checkSubmit();
                });

                input.addEventListener('keydown', e => {
                    if (e.key === 'Backspace' && !e.target.value && idx > 0) digits[idx - 1].focus();
                    if (e.key === 'ArrowLeft' && idx > 0) digits[idx - 1].focus();
                    if (e.key === 'ArrowRight' && idx < digits.length - 1) digits[idx + 1].focus();
                });

                input.addEventListener('paste', e => {
                    e.preventDefault();
                    const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
                    pasted.split('').forEach((ch, i) => { if (digits[i]) digits[i].value = ch; });
                    const next = Math.min(pasted.length, digits.length - 1);
                    digits[next].focus();
                    checkSubmit();
                });
            });

            function checkSubmit() {
                const code = Array.from(digits).map(d => d.value).join('');
                const hidden = document.getElementById('codigoCompleto');
                if (hidden) hidden.value = code;
            }

            document.getElementById('formCodigo')?.addEventListener('submit', e => {
                const code = Array.from(digits).map(d => d.value).join('');
                document.getElementById('codigoCompleto').value = code;
                if (code.length !== 6) {
                    e.preventDefault();
                    showCustomAlert('Verificação', 'Por favor, introduz os 6 dígitos do código.');
                }
            });
        });

        // ── Avaliador de força da senha ────────────────────────────────────────
        function avaliarForca(senha) {
            const barra = document.getElementById('forcaBarra');
            const texto = document.getElementById('forcaTexto');
            if (!barra || !texto) return;

            let pontos = 0;
            if (senha.length >= 8) pontos++;
            if (senha.length >= 12) pontos++;
            if (/[A-Z]/.test(senha)) pontos++;
            if (/[0-9]/.test(senha)) pontos++;
            if (/[^A-Za-z0-9]/.test(senha)) pontos++;

            const niveis = [
                { cor: '#ef4444', label: 'Muito fraca', pct: '20%' },
                { cor: '#f97316', label: 'Fraca', pct: '40%' },
                { cor: '#eab308', label: 'Razoável', pct: '60%' },
                { cor: '#22c55e', label: 'Forte', pct: '80%' },
                { cor: '#16a34a', label: 'Muito forte', pct: '100%' },
            ];

            const nivel = niveis[Math.min(pontos, 4)];
            barra.style.background = nivel.cor;
            barra.style.width = nivel.pct;
            texto.textContent = nivel.label;
            texto.style.color = nivel.cor;
        }
    </script>
    <script src="js/main.js"></script>
</body>

</html>
