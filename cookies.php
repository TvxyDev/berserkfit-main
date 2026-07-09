<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Definições de Cookies - BerserkFit</title>
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
    .cookie-option {
      background: #f9f9f9;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border: 1px solid #eee;
    }
    .cookie-info h4 { margin: 0 0 5px; color: var(--cor-destaque); }
    .cookie-info p { margin: 0; font-size: 0.9rem; color: #666; }
    
    /* Toggle Switch */
    .switch {
      position: relative;
      display: inline-block;
      width: 50px;
      height: 24px;
    }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider {
      position: absolute;
      cursor: pointer;
      top: 0; left: 0; right: 0; bottom: 0;
      background-color: #ccc;
      transition: .4s;
      border-radius: 24px;
    }
    .slider:before {
      position: absolute;
      content: "";
      height: 18px; width: 18px;
      left: 3px; bottom: 3px;
      background-color: white;
      transition: .4s;
      border-radius: 50%;
    }
    input:checked + .slider { background-color: var(--cor-destaque); }
    input:checked + .slider:before { transform: translateX(26px); }
  </style>
</head>
<body>
  <?php include 'navbar.php'; ?>

  <main class="legal-content">
    <h1>Definições de Cookies</h1>
    <p>Utilizamos cookies para melhorar a sua experiência. Pode gerir as suas preferências abaixo:</p>

    <div class="cookie-option">
      <div class="cookie-info">
        <h4>Cookies Necessários</h4>
        <p>Essenciais para o login e segurança da conta. Não podem ser desativados.</p>
      </div>
      <div><strong>Sempre Ativos</strong></div>
    </div>

    <div class="cookie-option">
      <div class="cookie-info">
        <h4>Cookies de Performance</h4>
        <p>Ajudam-nos a entender como os utilizadores interagem com a app.</p>
      </div>
      <label class="switch">
        <input type="checkbox" checked>
        <span class="slider"></span>
      </label>
    </div>

    <div class="cookie-option">
      <div class="cookie-info">
        <h4>Cookies de Personalização</h4>
        <p>Lembram as suas preferências, como o tema e idioma.</p>
      </div>
      <label class="switch">
        <input type="checkbox" checked>
        <span class="slider"></span>
      </label>
    </div>

    <button onclick="alert('Definições guardadas!')" style="background: var(--cor-destaque); color: white; border: none; padding: 12px 30px; border-radius: 5px; cursor: pointer; font-weight: bold; margin-top: 20px;">
      Guardar Preferências
    </button>
  </main>

  <?php include 'footer.php'; ?>

  <script src="js/main.js?v=<?= time() ?>"></script>
</body>
</html>
