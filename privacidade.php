<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Política de Privacidade - BerserkFit</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700&family=Inter&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="css/estilo.css?v=<?= time() ?>">
  <link rel="stylesheet" href="css/footer.css?v=<?= time() ?>">
  <link rel="stylesheet" href="css/global.css?v=<?= time() ?>">
  <link rel="stylesheet" href="css/responsive.css?v=<?= time() ?>">
  <style>
    .legal-content {
      max-width: 800px;
      margin: 120px auto 80px;
      padding: 0 20px;
      line-height: 1.6;
      color: var(--cor-texto);
    }
    .legal-content h1 {
      font-family: var(--fonte-titulo);
      font-size: 2.5rem;
      color: var(--cor-destaque);
      margin-bottom: 30px;
    }
    .legal-content h2 {
      font-family: var(--fonte-titulo);
      font-size: 1.5rem;
      color: var(--cor-destaque);
      margin-top: 40px;
      margin-bottom: 15px;
    }
    .legal-content p {
      margin-bottom: 15px;
    }
    .legal-content ul {
      margin-bottom: 20px;
      padding-left: 20px;
    }
    .legal-content li {
      margin-bottom: 10px;
    }
  </style>
</head>
<body>
  <?php include 'navbar.php'; ?>

  <main class="legal-content">
    <h1>Política de Privacidade</h1>
    <p>Última atualização: Março de 2024</p>
    
    <p>No BerserkFit, a sua privacidade é a nossa prioridade. Esta política explica como recolhemos, usamos e protegemos os seus dados pessoais.</p>

    <h2>1. Recolha de Dados</h2>
    <p>Recolhemos informações quando se regista na nossa aplicação, tais como:</p>
    <ul>
      <li>Nome e endereço de e-mail;</li>
      <li>Dados de perfil (objetivos de treino, peso, altura);</li>
      <li>Logs de atividade física carregados pelo utilizador.</li>
    </ul>

    <h2>2. Uso dos Dados</h2>
    <p>Os seus dados são utilizados para:</p>
    <ul>
      <li>Personalizar a sua experiência de treino;</li>
      <li>Processar inscrições em planos;</li>
      <li>Enviar notificações importantes sobre a sua conta;</li>
      <li>Melhorar as funcionalidades da aplicação.</li>
    </ul>

    <h2>3. Proteção de Informação</h2>
    <p>Implementamos uma variedade de medidas de segurança para manter a segurança das suas informações pessoais, incluindo encriptação de dados e autenticação de dois fatores (2FA).</p>

    <h2>4. Partilha com Terceiros</h2>
    <p>Não vendemos, trocamos ou transferimos os seus dados pessoais para terceiros, exceto quando necessário para o funcionamento da app (ex: processamento de pagamentos seguro).</p>

    <h2>5. Os Seus Direitos</h2>
    <p>Tem o direito de aceder, corrigir ou eliminar os seus dados pessoais a qualquer momento através das definições da sua conta.</p>
  </main>

  <?php include 'footer.php'; ?>

  <script src="js/main.js?v=<?= time() ?>"></script>
</body>
</html>
