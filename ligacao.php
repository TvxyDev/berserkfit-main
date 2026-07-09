<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "berserkfit";

$conn = null;
try {
    // A partir do PHP 8.1, o mysqli lança exceções por predefinição. 
    // Tentamos fazer a ligação num bloco try-catch para evitar o erro 500.
    mysqli_report(MYSQLI_REPORT_OFF); // Desativar exceções automáticas temporariamente para esta ligação
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Falhou a ligação à base de dados. Por favor, verifique as credenciais no ficheiro ligacao.php. Erro: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Falhou a ligação à base de dados. Por favor, verifique as credenciais no ficheiro ligacao.php. Erro: " . $e->getMessage());
}
date_default_timezone_set('Europe/Lisbon');
$conn->query("SET time_zone = '+01:00'");
// ================================
// CÁLCULO GLOBAL DO DAY STREAK
// ================================
$global_streak = 0;
if (isset($_SESSION['user_id'])) {
    $uid_streak = $_SESSION['user_id'];
    // Verifica se a tabela já existe
    $tabela_existe = $conn->query("SHOW TABLES LIKE 'day_streak'")->num_rows > 0;
    
    if ($tabela_existe) {
        $global_streak = 0;
        
        $hoje = date('Y-m-d');
        $ontem = date('Y-m-d', strtotime('-1 day'));

        // Verifica se completou HOJE
        $stmt_sk = $conn->prepare("SELECT streak_valido FROM day_streak WHERE id_user = ? AND data_streak = ?");
        if ($stmt_sk) {
            $stmt_sk->bind_param("is", $uid_streak, $hoje);
            $stmt_sk->execute();
            $res_hoje = $stmt_sk->get_result()->fetch_assoc();
            if ($res_hoje && $res_hoje['streak_valido'] == 1) {
                $global_streak++;
            }
            $stmt_sk->close();
        }

        // Agora conta ininterruptamente de ONTEM para trás
        $chk_date = new DateTime('yesterday');
        while (true) {
            $ds_p = $chk_date->format('Y-m-d');
            $stmt_sk = $conn->prepare("SELECT streak_valido FROM day_streak WHERE id_user = ? AND data_streak = ?");
            if ($stmt_sk) {
                $stmt_sk->bind_param("is", $uid_streak, $ds_p);
                $stmt_sk->execute();
                $res_sk = $stmt_sk->get_result()->fetch_assoc();
                $stmt_sk->close();
                
                if ($res_sk && $res_sk['streak_valido'] == 1) {
                    $global_streak++;
                    $chk_date->modify('-1 day');
                } else {
                    // Se não completou este dia passado, a streak parou aí.
                    break;
                }
            } else {
                break;
            }
        }

        // ── Determinar a liga correspondente com base no streak ──
        $nova_liga = 'Renegado';
        if ($global_streak >= 60) {
            $nova_liga = 'Ragnarok';
        } elseif ($global_streak >= 30) {
            $nova_liga = 'Berserker';
        } elseif ($global_streak >= 15) {
            $nova_liga = 'Jarl';
        } elseif ($global_streak >= 7) {
            $nova_liga = 'Huscarl';
        } elseif ($global_streak >= 3) {
            $nova_liga = 'Viking';
        }

        // Buscar dados atuais do user na BD para ver se precisa de UPDATE
        $stmt_user = $conn->prepare("SELECT day_streak, league FROM user WHERE id_user = ?");
        if ($stmt_user) {
            $stmt_user->bind_param("i", $uid_streak);
            $stmt_user->execute();
            $res_usr = $stmt_user->get_result()->fetch_assoc();
            $stmt_user->close();
            
            if ($res_usr) {
                $streak_atual_bd = intval($res_usr['day_streak'] ?? 0);
                $liga_atual_bd = $res_usr['league'] ?? 'Renegado';
                
                // Se o streak ou a liga mudaram, atualizamos a base de dados
                if ($streak_atual_bd !== $global_streak || $liga_atual_bd !== $nova_liga) {
                    $stmt_upd = $conn->prepare("UPDATE user SET day_streak = ?, league = ? WHERE id_user = ?");
                    if ($stmt_upd) {
                        $stmt_upd->bind_param("isi", $global_streak, $nova_liga, $uid_streak);
                        $stmt_upd->execute();
                        $stmt_upd->close();
                    }
                }
            }
        }
    }
}

// Cria tabela newsletter se não existir
$conn->query("CREATE TABLE IF NOT EXISTS `newsletter` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `data_subscricao` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
?>
