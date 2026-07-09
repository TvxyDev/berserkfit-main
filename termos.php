<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Termos de Serviço - BerserkFit</title>
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
  </style>
</head>
<body>
  <?php include 'navbar.php'; ?>

  <main class="legal-content">
    <h1>Termos de Serviço</h1>
    <p>Ao utilizar o BerserkFit, concorda com os seguintes termos:</p>

    <h2>1. Utilização da Conta</h2>
    <p>É responsável por manter a confidencialidade da sua conta e palavra-passe. Atividades realizadas na sua conta são da sua inteira responsabilidade.</p>

    <h2>2. Uso Adequado</h2>
    <p>O BerserkFit é destinado ao acompanhamento de fitness pessoal. Não é permitido o uso da plataforma para spam, assédio ou atividades ilegais.</p>

    <h2>3. Conteúdo do Utilizador</h2>
    <p>Ao publicar conteúdo (como fotos ou progresso), garante que possui os direitos sobre o mesmo. Reservamo-nos o direito de remover conteúdo que viole as nossas regras.</p>

    <h2>4. Limitação de Responsabilidade</h2>
    <p>O BerserkFit fornece planos de treino sugeridos. Consulte sempre um profissional de saúde antes de iniciar qualquer regime de exercício intenso. Não nos responsabilizamos por lesões resultantes de uso inadequado da app.</p>

    <h2>5. Alterações aos Termos</h2>
    <p>Reservamo-nos o direito de atualizar estes termos periodicamente. O uso continuado da app constitui aceitação dos novos termos.</p>
  </main>

  <?php include 'footer.php'; ?>

  <script src="js/main.js?v=<?= time() ?>"></script>
</body>
</html>
