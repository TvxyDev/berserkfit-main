<?php
/**
 * Script de Teste de Envio de E-mail
 * BerserkFit AI
 */

header('Content-Type: text/html; charset=utf-8');
require 'config_email.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

echo "<h2>A testar o envio de e-mail através do Brevo SMTP...</h2>";

try {
    // Configuração do Servidor SMTP
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = (MAIL_ENCRYPTION === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = MAIL_PORT;
    $mail->CharSet    = 'UTF-8';

    // Para ver o log detalhado no ecrã durante o teste
    $mail->SMTPDebug  = 2; 

    // Remetente e Destinatário
    $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
    
    // Altera o endereço abaixo para o teu próprio e-mail para testares o envio!
    $email_teste = MAIL_FROM_EMAIL; // Por padrão, envia para o email do remetente
    $mail->addAddress($email_teste, 'Teste de Integração');

    // Conteúdo
    $mail->isHTML(true);
    $mail->Subject = 'Teste de Envio - BerserkFit AI';
    $mail->Body    = '<h1>Sucesso!</h1><p>Esta é uma mensagem de teste enviada a partir do teu servidor local para validar o SMTP do Brevo.</p>';
    $mail->AltBody = 'Sucesso! Esta é uma mensagem de teste enviada a partir do teu servidor local.';

    echo "<p>A tentar enviar e-mail de teste para <strong>$email_teste</strong>...</p>";
    $mail->send();
    echo "<p style='color: green; font-weight: bold;'>✅ E-mail enviado com sucesso! Verifica a tua caixa de correio.</p>";
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Erro ao enviar e-mail: {$mail->ErrorInfo}</p>";
}
?>
