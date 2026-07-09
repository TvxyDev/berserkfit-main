<?php
/**
 * Navbar Centralizada da Aplicação BerserkFit
 * Este componente é incluído em todas as páginas principais do dashboard.
 */
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
    <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
        <i class="fas fa-home icon"></i>
        <span class="text">Início</span>
    </a>
    <a href="treinos.php" class="nav-link <?php echo ($current_page == 'treinos.php') ? 'active' : ''; ?>">
        <i class="fas fa-dumbbell icon"></i>
        <span class="text">Treinos</span>
    </a>
    <a href="progresso.php" class="nav-link <?php echo ($current_page == 'progresso.php') ? 'active' : ''; ?>">
        <i class="fas fa-chart-line icon"></i>
        <span class="text">Progresso</span>
    </a>
    <a href="chatbot.php" class="nav-link <?php echo ($current_page == 'chatbot.php') ? 'active' : ''; ?>">
        <i class="fas fa-robot icon"></i>
        <span class="text">Chatbot</span>
    </a>
    <a href="perfil.php" class="nav-link <?php echo ($current_page == 'perfil.php') ? 'active' : ''; ?>">
        <i class="fas fa-user icon"></i>
        <span class="text">Perfil</span>
    </a>
</nav>
