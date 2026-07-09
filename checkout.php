<?php
/**
 * Iniciar o pagamento na Stripe
 * BerserkFit AI
 */

session_start();
require 'ligacao.php';
require 'config_stripe.php';
require 'vendor/autoload.php'; // Carrega o SDK do Stripe instalado via composer

// 1. Verificar se o plano foi selecionado e se é válido
$plano_key = isset($_GET['plano']) ? strtolower($_GET['plano']) : '';

if (!array_key_exists($plano_key, $planos_config)) {
    // Se o plano for inválido, redireciona para a página inicial
    header("Location: index.php#planos");
    exit;
}

// 2. Verificar se o utilizador está logado
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_checkout_plano'] = $plano_key;
    header("Location: login.php?msg=login_required_checkout");
    exit;
}

$id_user = $_SESSION['user_id'];
$plano_selecionado = $planos_config[$plano_key];

// 3. Obter o email do utilizador da base de dados para pré-preencher no Stripe Checkout
$email_user = '';
$stmt = $conn->prepare("SELECT email FROM user WHERE id_user = ?");
if ($stmt) {
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $email_user = $res['email'] ?? '';
    $stmt->close();
}

// 4. Inicializar o cliente Stripe com a Secret Key
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {
    // 5. Criar a sessão de pagamento na Stripe
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'], // Podes adicionar outros métodos suportados na tua conta Stripe (ex: 'mbway', 'multibanco')
        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => $plano_selecionado['nome'],
                    'description' => $plano_selecionado['descricao'],
                ],
                'unit_amount' => $plano_selecionado['preco_centimos'],
                'recurring' => [
                    'interval' => 'month',
                ],
            ],
            'quantity' => 1,
        ]],
        'mode' => 'subscription',
        'customer_email' => $email_user,
        'client_reference_id' => $id_user, // ID do utilizador no nosso sistema para sincronizar no webhook
        'metadata' => [
            'tipo_plano' => $plano_key
        ],
        'success_url' => BASE_URL . 'success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => BASE_URL . 'cancel.php',
    ]);

    // 6. Redirecionar para o portal seguro da Stripe
    header("HTTP/1.1 303 See Other");
    header("Location: " . $session->url);
    exit;

} catch (\Exception $e) {
    // Caso ocorra algum erro (ex: chaves inválidas)
    echo "<h1>Erro ao iniciar o pagamento com o Stripe</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<a href='index.php#planos'>Voltar aos Planos</a>";
}
?>
