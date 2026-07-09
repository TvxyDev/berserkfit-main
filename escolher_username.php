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
$erro = "";

// Verificar se o usuário já tem username
$sql_check = "SELECT username FROM user WHERE id_user = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

// Se já tem username válido, redireciona
if (!empty($user_data['username']) && $user_data['username'] !== NULL) {
    header("Location: onboarding.php");
    exit;
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');

    // Validações
    if (empty($username)) {
        $erro = "Por favor, escolhe um nome de utilizador.";
    } elseif (strlen($username) < 3) {
        $erro = "O nome de utilizador deve ter pelo menos 3 caracteres.";
    } elseif (strlen($username) > 20) {
        $erro = "O nome de utilizador não pode ter mais de 20 caracteres.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $erro = "O nome de utilizador só pode conter letras, números e underscore (_).";
    } else {
        // Verificar se o username já existe
        $sql_check_username = "SELECT id_user FROM user WHERE username = ?";
        $stmt = $conn->prepare($sql_check_username);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $erro = "Este nome de utilizador já está em uso. Por favor, escolhe outro.";
        } else {
            // Atualizar username
            $sql_update = "UPDATE user SET username = ? WHERE id_user = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("si", $username, $user_id);

            if ($stmt_update->execute()) {
                $_SESSION['username'] = $username;
                $stmt_update->close();
                $conn->close();
                header("Location: onboarding.php");
                exit;
            } else {
                $erro = "Erro ao guardar o nome de utilizador. Tenta novamente.";
            }
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escolher Username - BerserkFit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="css/estilo.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&family=Inter:wght@400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/escolher_username.css">
</head>

<body class="login-page">
    <main class="main-login">
        <div class="username-container">
            <div class="username-box">
                <i class="fas fa-user-circle icon-user"></i>
                <h1>Escolhe o teu Username 🎯</h1>
                <p>Bem-vindo ao BerserkFit! Para começares, escolhe um nome de utilizador único que te represente.</p>
                escolhe um nome de utilizador único que te represente.</p>
                <?php if (!empty($erro)): ?>
                    <div class="mensagem error"><i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($erro); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="escolher_username.php" id="formUsername">
                    <div class="form-group"><label for="username"><i class="fas fa-at"></i>Nome de Utilizador
                        </label><input type="text" id="username" name="username" placeholder="ex: guerreiro_fit"
                            required minlength="3" maxlength="20" pattern="[a-zA-Z0-9_]+" autocomplete="off">
                        <div class="username-hint"><i class="fas fa-info-circle"></i>3-20 caracteres,
                            apenas letras,
                            números e underscore (_) </div>
                    </div>
                    <div class="username-preview" id="preview" style="display: none;"><i
                            class="fas fa-eye"></i>Pré-visualização: <span id="previewText">@</span></div><button
                        type="submit" class="btn-submit"><i class="fas fa-check-circle"></i>Confirmar Username </button>
                </form>
            </div>
        </div>
    </main>
    <script>const usernameInput = document.getElementById('username');
        const preview = document.getElementById('preview');
        const previewText = document.getElementById('previewText');

        usernameInput.addEventListener('input', function () {
            const value = this.value.trim();

            if (value.length > 0) {
                preview.style.display = 'block';
                previewText.textContent = '@' + value;
            }

            else {
                preview.style.display = 'none';
            }
        });

        // Validação em tempo real
        usernameInput.addEventListener('input', function () {
            this.value = this.value.replace(/[^a-zA-Z0-9_]/g, '');
        });
    </script>
</body>

</html>
