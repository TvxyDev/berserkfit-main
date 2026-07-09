<?php
require 'ligacao.php';

$testemunhos_home = [];
try {
    $sql_t = "SELECT t.estrelas, t.texto, u.nome as user_nome, COALESCE(u.tipo_plano, 'gratuito') as user_plano, u.foto as user_foto 
              FROM testemunho t 
              LEFT JOIN user u ON t.id_user = u.id_user 
              WHERE t.aprovado = 1 
              ORDER BY t.data_criacao DESC LIMIT 6";
    $res_t = $conn->query($sql_t);
    if ($res_t) {
        while ($row = $res_t->fetch_assoc()) {
            $testemunhos_home[] = $row;
        }
    }
} catch (Exception $e) {
    // Ignorar erro
}
?>
<!DOCTYPE html>
<html lang="pt-PT">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <!-- SEO Básico -->
  <title>BerserkFit | App de Treino, Evolução e Disciplina</title>
  <meta name="description" content="BerserkFit é a tua aplicação inteligente de ginásio. Acompanha a tua evolução, gera treinos com IA, gere as tuas checklists diárias e constrói disciplina espartana." />
  <meta name="keywords" content="fitness, ginásio, app de treino, plano de treino, IA fitness, BerserkFit, musculação, evolução física, rotina de treino" />
  <meta name="author" content="BerserkFit" />
  <link rel="canonical" href="https://victorsantos.escolahenriquemedina.org/" /> <!-- Adaptar para o domínio real depois -->

  <!-- Open Graph / Facebook / WhatsApp -->
  <meta property="og:type" content="website" />
  <meta property="og:url" content="https://victorsantos.escolahenriquemedina.org/" />
  <meta property="og:title" content="BerserkFit - Forja a Tua Disciplina" />
  <meta property="og:description" content="Junta-te ao BerserkFit. O ginásio não é só para o corpo, é para a mente." />
  <meta property="og:image" content="https://victorsantos.escolahenriquemedina.org/assets/logotipo1.png" />

  <!-- Twitter -->
  <meta property="twitter:card" content="summary_large_image" />
  <meta property="twitter:url" content="https://victorsantos.escolahenriquemedina.org/" />
  <meta property="twitter:title" content="BerserkFit - Forja a Tua Disciplina" />
  <meta property="twitter:description" content="Junta-te ao BerserkFit. O ginásio não é só para o corpo, é para a mente." />
  <meta property="twitter:image" content="https://victorsantos.escolahenriquemedina.org/assets/logotipo1.png" />
  <link rel="stylesheet" href="css/estilo.css?v=<?= time() ?>" />
  <link rel="stylesheet" href="css/footer.css?v=<?= time() ?>" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700&family=Inter&display=swap" rel="stylesheet" />
  <link rel="manifest" href="manifest.json" />
  <meta name="theme-color" content="#1c0c3b" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
  <link rel="apple-touch-icon" href="assets/logotipo1.png" />
</head>

<body>
  <?php include 'navbar.php'; ?>

  <main>
    <section id="inicio" class="hero-section1">
      <div class="heroi-container">
        <div class="heroi-texto">
          <h1 class="fade-in-element">BerserkFit</h1>
          <p class="subtitulo-heroi fade-in-element">
            A app de treino que acompanha a tua evolução, planeia os teus treinos
            e mantém a tua disciplina no ginásio.
          </p>
          <ul class="heroi-lista fade-in-element">
            <li>
              <img src="assets/checkmark.svg" alt="Checkmark" />
              Acompanhamento do progresso
            </li>
            <li>
              <img src="assets/checkmark.svg" alt="Checkmark" /> Planeador de
              treinos
            </li>
            <li>
              <img src="assets/checkmark.svg" alt="Checkmark" /> Disciplina e
              motivação
            </li>
          </ul>
          <div class="botoes-heroi">
            <a href="login.php" class="botao-heroi fade-in-element">Começar Agora</a>
            <a href="javascript:void(0)" class="botao-apple fade-in-element" id="btnApplePWA">
              <i class="fab fa-apple"></i>
              <span>Baixar para Apple</span>
            </a>
          </div>
        </div>
        <div class="heroi-imagem fade-in-element">
          <img src="assets/pessoa.png" alt="Pessoa a exercitar-se" />
        </div>
      </div>
    </section>

    <!-- Modal PWA Apple -->
    <div id="modalPWA" class="modal-pwa">
      <div class="modal-pwa-content">
        <span class="close-pwa">&times;</span>
        <i class="fab fa-apple" style="font-size: 3rem; margin-bottom: 20px;"></i>
        <h2>BerserkFit no seu iPhone</h2>
        <p>Instale o BerserkFit como uma aplicação nativa seguindo estes passos:</p>
        <ol class="pwa-steps">
          <li>Toque no ícone de <strong>Partilhar</strong> <i class="fa-solid fa-square-up-right"></i> na barra inferior do Safari.</li>
          <li>Deslize para baixo e selecione <strong>"Ecrã Principal"</strong> <i class="fa-solid fa-plus-square"></i>.</li>
          <li>Toque em <strong>Adicionar</strong> no canto superior direito.</li>
        </ol>
        <button class="btn-pwa-entendi">Entendi</button>
      </div>
    </div>

    <section id="funcionalidades" class="fade-in-element">
      <h2>Um Arsenal Para a Tua Evolução</h2>
      <p class="subtitulo">
        Ferramentas inteligentes para um treino disciplinado e motivador.
      </p>
      <div class="grade-funcionalidades">
        <div class="cartao fade-in-element">
          <div class="cartao-icone">
            <i class="fas fa-clipboard-list" style="font-size: 40px; color: var(--cor-amarela);"></i>
          </div>
          <h3>Checklist Diária</h3>
          <p>
            Cria e personaliza a tua checklist diária para forjar disciplina e
            acompanhar o teu progresso.
          </p>
        </div>
        <div class="cartao fade-in-element">
          <div class="cartao-icone">
            <i class="fas fa-robot" style="font-size: 40px; color: var(--cor-amarela);"></i>
          </div>
          <h3>Gerador de Treinos IA</h3>
          <p>
            Obtém treinos personalizados com o nosso chatbot, adaptados ao teu
            nível e equipamento.
          </p>
        </div>
        <div class="cartao fade-in-element">
          <div class="cartao-icone">
            <i class="fas fa-bell" style="font-size: 40px; color: var(--cor-amarela);"></i>
          </div>
          <h3>Notificações Motivacionais</h3>
          <p>
            Recebe lembretes e mensagens de encorajamento para manter o foco
            nos teus objetivos.
          </p>
        </div>
      </div>
    </section>

    <section id="planos" class="fade-in-element">
      <h2>Planos para Guerreiros</h2>
      <p class="subtitulo">
        Escolhe o arsenal que te levará à vitória. Começa grátis.
      </p>
      <div class="grade-precos">
        <div class="cartao-preco fade-in-element">
          <h3>Spartan</h3>
          <p class="preco">Grátis</p>
          <p class="periodo-preco">Para sempre</p>
          <ul>
            <li>
              <img src="assets/checkmark.svg" alt="Checkmark" /> Gerador de
              treinos básico
            </li>
            <li>
              <img src="assets/checkmark.svg" alt="Checkmark" /> Checklist
              diária para 5 tarefas
            </li>
            <li>
              <img src="assets/checkmark.svg" alt="Checkmark" /> Acesso à
              comunidade
            </li>
            <li>
              <img src="assets/checkmark.svg" alt="Checkmark" /> Exportação de
              treinos
            </li>
          </ul>
          <button onclick="window.location.href='login.php'">Começa Grátis</button>
        </div>
        <div class="cartao-preco destacado fade-in-element">
          <h3>Gladiator</h3>
          <p class="preco">€19,90<span class="preco-mes">/mês</span></p>
          <ul>
            <li>
              <img src="assets/checkmark.svg" alt="Checkmark" /> Tudo do plano
              Spartan
            </li>
            <li>
              <img src="assets/checkmark.svg" alt="Checkmark" /> Gerador de
              treinos com IA avançada
            </li>
            <li>
              <img src="assets/checkmark.svg" alt="Checkmark" /> Histórico de
              consistência completo
            </li>
            <li>
              <img src="assets/checkmark.svg" alt="Checkmark" /> Checklist
              diária ilimitada
            </li>
          </ul>
          <button class="botao-destacado" onclick="window.location.href='checkout.php?plano=gladiator'">Sê um Gladiador</button>
        </div>
        <div class="cartao-preco fade-in-element">
          <h3>Berserker</h3>
          <p class="preco">€39,90<span class="preco-mes">/mês</span></p>
          <ul>
            <li>
              <img src="assets/checkmark.svg" alt="Checkmark" /> Tudo do plano
              Gladiator
            </li>
            <li>
              <img src="assets/checkmark.svg" alt="Checkmark" /> Notificações
              motivacionais personalizadas
            </li>
            <li>
              <img src="assets/checkmark.svg" alt="Checkmark" /> Suporte
              prioritário via chat
            </li>
            <li>
              <img src="assets/checkmark.svg" alt="Checkmark" /> Acesso
              antecipado a novas funcionalidades
            </li>
          </ul>
          <button onclick="window.location.href='checkout.php?plano=berserker'">Liberta o Berserker</button>
        </div>
      </div>
    </section>

    <section id="sobre" class="fade-in-element">
      <div class="sobre-container">
        <div class="missao fade-in-element">
          <h2>A Nossa Missão</h2>
          <p>
            Na BerserkFit, acreditamos que a verdadeira força nasce da
            disciplina. A nossa missão é fornecer as ferramentas para que
            construas não apenas um físico poderoso, mas uma mente resiliente
            como a de um guerreiro espartano.
          </p>
          <p>
            Combinamos treino inteligente a motivação implacável para que
            possas superar os teus limites e alcançar a evolução contínua.
            Junta-te a nós nesta jornada de autotransformação.
          </p>
        </div>
        <div class="legiao fade-in-element">
          <h2>A Nossa Legião</h2>
          <div class="grade-legiao">
            <div class="membro-legiao fade-in-element">
              <img
                src="assets/about2.jpg"
                alt="Retrato de Victor Santos, o fundador do BerserkFit" />
              <h3>Victor Santos</h3>
              <p>Fundador &<br />Desenvolvedor</p>
            </div>
          </div>
        </div>
    </section>

    <section id="depoimentos" class="fade-in-element">
      <h2>O Que Dizem Os Nossos Guerreiros</h2>
      <div class="depoimentos-carousel-wrapper">
        <button class="carousel-btn prev-btn" aria-label="Anterior"><i class="fas fa-chevron-left"></i></button>
        <div class="depoimentos-carousel-track-container">
          <div class="grade-depoimentos carousel-track">
            <?php if (!empty($testemunhos_home)): ?>
              <?php foreach ($testemunhos_home as $index => $t): ?>
                <?php 
                  $avatar_url = (!empty($t['user_foto']) && file_exists($t['user_foto'])) ? $t['user_foto'] : "https://i.pravatar.cc/100?u=" . urlencode($t['user_nome']);
                  $plano_nome = 'Spartan';
                  if (strtolower($t['user_plano']) === 'gladiator') {
                      $plano_nome = 'Gladiador';
                  } elseif (strtolower($t['user_plano']) === 'berserker') {
                      $plano_nome = 'Berserker';
                  }
                ?>
                <div class="cartao-depoimento fade-in-element">
                  <img src="<?php echo $avatar_url; ?>" alt="Avatar de <?php echo htmlspecialchars($t['user_nome']); ?>" />
                  <div style="color: var(--cor-amarela); margin-bottom: 10px; font-size: 0.95rem; text-align: center;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <i class="<?php echo $i <= $t['estrelas'] ? 'fas' : 'far'; ?> fa-star"></i>
                    <?php endfor; ?>
                  </div>
                  <p>
                    "<?php echo htmlspecialchars($t['texto']); ?>"
                  </p>
                  <h3><?php echo htmlspecialchars($t['user_nome']); ?></h3>
                  <span><?php echo $plano_nome; ?></span>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="cartao-depoimento fade-in-element">
                <img src="https://i.pravatar.cc/100?u=1" alt="Avatar de um utilizador" />
                <p>
                  "O BerserkFit mudou o meu jogo. A disciplina que eu precisava, na
                  palma da minha mão."
                </p>
                <h3>Marcus</h3>
                <span>Gladiador</span>
              </div>
              <div class="cartao-depoimento fade-in-element">
                <img src="https://i.pravatar.cc/100?u=2" alt="Avatar de um utilizador" />
                <p>
                  "Finalmente uma app que entende a mentalidade de um atleta. Os
                  treinos são insanos!"
                </p>
                <h3>Helena</h3>
                <span>Berserker</span>
              </div>
              <div class="cartao-depoimento fade-in-element">
                <img src="https://i.pravatar.cc/100?u=3" alt="Avatar de um utilizador" />
                <p>
                  "Comecei com o plano Spartan e já sinto a diferença. A comunidade
                  é um grande apoio."
                </p>
                <h3>Gael</h3>
                <span>Spartan</span>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <button class="carousel-btn next-btn" aria-label="Seguinte"><i class="fas fa-chevron-right"></i></button>
      </div>
      <div class="carousel-dots"></div>
    </section>
  </main>

  <?php include 'footer.php'; ?>

  <script src="js/main.js?v=<?= time() ?>"></script>
  <script>
    // Registro do Service Worker
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js')
          .then(reg => console.log('Service Worker registrado!', reg))
          .catch(err => console.log('Erro no registro do SW:', err));
      });
    }

    // Modal PWA Apple
    const modal = document.getElementById("modalPWA");
    const btn = document.getElementById("btnApplePWA");
    const span = document.getElementsByClassName("close-pwa")[0];
    const btnEntendi = document.querySelector(".btn-pwa-entendi");

    if (btn && modal) {
      btn.onclick = function() {
        modal.style.display = "flex";
        document.body.style.overflow = "hidden";
      }
    }

    if (span) {
      span.onclick = function() {
        modal.style.display = "none";
        document.body.style.overflow = "auto";
      }
    }

    if (btnEntendi) {
      btnEntendi.onclick = function() {
        modal.style.display = "none";
        document.body.style.overflow = "auto";
      }
    }

    window.onclick = function(event) {
      if (event.target == modal) {
        modal.style.display = "none";
        document.body.style.overflow = "auto";
      }
    }
  </script>
</body>

</html>