<footer>
  <div class="footer-container">
    <div class="footer-column">
      <h3>BerserkFit</h3>
      <p>
        Subscreve a nossa newsletter para te manteres atualizado sobre
        funcionalidades e lançamentos.
      </p>
      <form class="newsletter-form" id="formNewsletter">
        <input type="email" name="email" id="newsletterEmail" placeholder="Insere o teu e-mail" required />
        <button type="submit">Subscrever</button>
      </form>
      <p class="newsletter-disclaimer">
        Ao subscreveres, concordas com a nossa <a href="privacidade.php" style="color: inherit; text-decoration: underline;">Política de Privacidade</a> e
        consentes em receber atualizações da nossa empresa.
      </p>
    </div>
    <div class="footer-column">
      <h4>Navegação</h4>
      <ul>
        <li><a href="index.php#inicio">Início</a></li>
        <li><a href="index.php#funcionalidades">Funcionalidades</a></li>
        <li><a href="index.php#planos">Planos</a></li>
        <li><a href="index.php#sobre">Sobre</a></li>
        <li><a href="index.php#depoimentos">Testemunhos</a></li>
        <li><a href="index.php#contato">Contacto</a></li>
      </ul>
    </div>
    <div class="footer-column">
      <h4>Segue-nos</h4>
      <div class="social-icons">
        <a href="#"><img src="assets/facebook.svg" alt="Facebook" /> Facebook</a>
        <a href="#"><img src="assets/instagram.svg" alt="Instagram" /> Instagram</a>
        <a href="#"><img src="assets/twitter.svg" alt="Twitter" /> Twitter</a>
      </div>
      <div class="footer-apps" style="margin-top: 45px; display: flex; justify-content: flex-start;">
        <a href="javascript:void(0)" id="btnApplePWA_Footer" class="botao-apple" style="padding: 12px 20px !important; font-size: 0.85rem !important; width: 180px !important; min-width: auto !important; height: auto !important; margin: 0 !important; display: flex !important;">
          <i class="fab fa-apple"></i>
          <span>Baixar para Apple</span>
        </a>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <p>© 2024 BerserkFit. Todos os direitos reservados.</p>
    <div class="footer-legal">
      <a href="privacidade.php" class="legal-link">Política de Privacidade</a>
      <a href="termos.php" class="legal-link">Termos de Serviço</a>
      <a href="cookies.php" class="legal-link">Definições de Cookies</a>
    </div>
  </div>
</footer>
<script>
  // Sincronizar modal PWA no footer
  const btnFooter = document.getElementById("btnApplePWA_Footer");
  if (btnFooter) {
    btnFooter.onclick = function() {
      const modal = document.getElementById("modalPWA");
      if (modal) {
        modal.style.display = "flex";
        document.body.style.overflow = "hidden";
      }
    }
  }
</script>
