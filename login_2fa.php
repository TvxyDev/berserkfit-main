<?php
session_start();
require "ligacao.php";
require_once __DIR__ . '/functions_2fa.php';

// Redireciona se o utilizador já estiver logado (vai para dashboard)
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Redireciona se não houver um ID temporário para 2FA (vai para login)
if (!isset($_SESSION['tfa_user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['tfa_user_id'];
$error = '';

// Obter todos os dados do utilizador necessários para a sessão
$stmt = $conn->prepare("SELECT tfa_secret, username, nome, email, COALESCE(tipo_usuario, 'Usuario') as tipo_usuario FROM user WHERE id_user = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$secret = $user['tfa_secret'] ?? null;
$username = $user['username'] ?? 'Utilizador';

if (!$secret) {
    // Se por algum motivo o segredo não existir, volta para o login
    unset($_SESSION['tfa_user_id']);
    unset($_SESSION['tfa_username']);
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');

    try {
        if (function_exists('verify_tfa_code') && verify_tfa_code($secret, $code)) {
            // Código 2FA Válido! Finaliza o login com todas as variáveis de sessão
            $_SESSION['user_id']    = $user_id;
            $_SESSION['user_nome']  = $user['nome'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_tipo']  = $user['tipo_usuario'];
            $_SESSION['username']   = $username;
            $_SESSION['last_activity'] = time();

            // Guardar flag de login Google antes de limpar
            $veio_do_google = !empty($_SESSION['tfa_google_login']);

            // Limpa TODAS as variáveis temporárias de 2FA (incluindo as do Google)
            unset(
                $_SESSION['tfa_user_id'],
                $_SESSION['tfa_username'],
                $_SESSION['tfa_google_nome'],
                $_SESSION['tfa_google_email'],
                $_SESSION['tfa_google_login']
            );

            // Se veio do Google e não tem username, vai escolher username
            if ($veio_do_google && empty($username)) {
                header("Location: escolher_username.php");
                exit;
            }

            // Verifica se é o primeiro login (sem hábitos criados)
            $sql_check = "SELECT COUNT(*) as total FROM habito WHERE id_user = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("i", $user_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            $row_check = $result_check->fetch_assoc();
            $stmt_check->close();

            if ($row_check['total'] == 0) {
                header("Location: onboarding.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            $error = "Código incorreto. Tente novamente.";
        }
    } catch (Exception $e) {
        $error = "Erro interno. Tente novamente.";
        error_log("2FA Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Verificação 2FA - BerserkFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/login_2fa.css">

</head>

<body>
    <!-- bg-particles mantido para não quebrar o JS existente mas está oculto via CSS -->
    <div class="bg-particles" id="bgParticles"></div>

    <div class="glass-card">

        <div class="brand-label">🔒 BerserkFit</div>

        <div class="text-center">
            <div class="icon-container">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>

            <h1 class="card-title">Verificação em Duas Etapas</h1>
            <p class="card-subtitle">
                Introduz o código de 6 dígitos gerado<br>pelo teu aplicativo autenticador.
            </p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-msg">
                <svg xmlns="http://www.w3.org/2000/svg" class="error-icon" viewBox="0 0 20 20"
                    fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd" />
                </svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" id="otpForm">
            <input type="hidden" name="code" id="fullCode">

            <div class="otp-container">
                <input type="text" class="otp-input" maxlength="1" pattern="\d" inputmode="numeric"
                    autocomplete="one-time-code" required>
                <input type="text" class="otp-input" maxlength="1" pattern="\d" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="\d" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="\d" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="\d" inputmode="numeric" required>
                <input type="text" class="otp-input" maxlength="1" pattern="\d" inputmode="numeric" required>
            </div>

            <button type="submit" class="btn-verify">
                Verificar Identidade
            </button>
            <div style="text-align: center; margin-top: 15px;">
                <a href="recovery_2fa.php" style="color: #64748b; font-size: 0.85rem; text-decoration: none;">Perdi o acesso? Usar código de recuperação</a>
            </div>
        </form>

        <hr class="divider">

        <div class="text-center">
            <a href="logout.php" class="footer-link">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                Voltar para o Login
            </a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const inputs = document.querySelectorAll('.otp-input');
            const form = document.getElementById('otpForm');
            const fullCodeInput = document.getElementById('fullCode');

            // Focus first input on load
            inputs[0].focus();

            inputs.forEach((input, index) => {
                // Handle input
                input.addEventListener('input', (e) => {
                    const value = e.target.value;

                    // Allow only numbers
                    if (!/^\d*$/.test(value)) {
                        e.target.value = '';
                        return;
                    }

                    if (value.length === 1) {
                        // Move to next input
                        if (index < inputs.length - 1) {
                            inputs[index + 1].focus();
                        } else {
                            // If last input, submit or focus out
                            input.blur();
                            submitIfComplete();
                        }
                    }
                });

                // Handle backspace and navigation
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !e.target.value && index > 0) {
                        inputs[index - 1].focus();
                    }
                    if (e.key === 'ArrowLeft' && index > 0) {
                        inputs[index - 1].focus();
                    }
                    if (e.key === 'ArrowRight' && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                });

                // Handle paste
                input.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const pastedData = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);

                    if (pastedData) {
                        pastedData.split('').forEach((char, i) => {
                            if (inputs[i]) {
                                inputs[i].value = char;
                            }
                        });

                        // Focus the next empty input or the last one
                        const nextIndex = Math.min(pastedData.length, inputs.length - 1);
                        inputs[nextIndex].focus();

                        submitIfComplete();
                    }
                });
            });

            function submitIfComplete() {
                const code = Array.from(inputs).map(i => i.value).join('');
                if (code.length === 6) {
                    fullCodeInput.value = code;
                    // Optional: Auto submit
                    // form.submit();
                }
            }

            form.addEventListener('submit', (e) => {
                const code = Array.from(inputs).map(i => i.value).join('');
                fullCodeInput.value = code;

                if (code.length !== 6) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Código Incompleto',
                        text: 'Por favor, digite os 6 dígitos do código.',
                        background: '#0f172a',
                        color: '#cbd5e1',
                        confirmButtonColor: '#06b6d4'
                    });
                }
            });
        });
    </script>

    <script>
        // Gera partículas animadas no fundo
        (function() {
            const container = document.getElementById('bgParticles');
            if (!container) return;
            const colors = ['rgba(139,92,246,0.5)', 'rgba(196,181,253,0.4)', 'rgba(109,40,217,0.4)', 'rgba(167,139,250,0.3)'];
            for (let i = 0; i < 25; i++) {
                const p = document.createElement('div');
                p.className = 'particle';
                const size = Math.random() * 8 + 3;
                p.style.cssText = `
                    width:${size}px; height:${size}px;
                    left:${Math.random()*100}%;
                    bottom:-${size}px;
                    background:${colors[Math.floor(Math.random()*colors.length)]};
                    animation-duration:${Math.random()*15+8}s;
                    animation-delay:${Math.random()*10}s;
                `;
                container.appendChild(p);
            }
        })();
    </script>
</body>

</html>
