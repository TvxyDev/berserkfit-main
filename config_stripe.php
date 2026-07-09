<?php
/**
 * Ficheiro de Configuração do Stripe
 * BerserkFit AI
 */

// 1. Detetar se o ambiente é local (localhost) ou remoto (servidor em produção)
$is_local = (!isset($_SERVER['HTTP_HOST']) || $_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1' || (isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] === '127.0.0.1'));

// 2. Configurar chaves API do Stripe com base no ambiente
// IMPORTANTE: Substitui pelas tuas chaves reais da consola do Stripe (https://dashboard.stripe.com/apikeys)
if ($is_local) {
    // chaves de teste locais
    define('STRIPE_PUBLIC_KEY', 'pk_test_51Sae2tA5VjcWtCSfNvB6gbDUnIAkRBWPcheLdVYaNCnQpXNBcWtNxepftHKdTlfwa2ix7TUQ4KRQTvw5BZ9xoYmf00GqJbj2oD'); 
    define('STRIPE_SECRET_KEY', 'sk_test_51Sae2tA5VjcWtCSfEfCRW9LRUv2MfxY0nURilDjC6Tghh1HHexwefGwl9Pg00XRn9FEgESX2WHIuwYnDTWvtIZ2b00DsFO41ck');
    // Secret do Webhook Local (obtida ao correr o comando: stripe listen --forward-to localhost/.../webhook_stripe.php)
    define('STRIPE_WEBHOOK_SECRET', 'whsec_d1ee71444e503851687c160bc46b7579cfb45fdf2463aac512cd101af9a1a9c5'); 
} else {
    // chaves do servidor remoto (pode ser teste ou produção/live)
    define('STRIPE_PUBLIC_KEY', 'pk_test_51Sae2tA5VjcWtCSfNvB6gbDUnIAkRBWPcheLdVYaNCnQpXNBcWtNxepftHKdTlfwa2ix7TUQ4KRQTvw5BZ9xoYmf00GqJbj2oD'); 
    define('STRIPE_SECRET_KEY', 'sk_test_51Sae2tA5VjcWtCSfEfCRW9LRUv2MfxY0nURilDjC6Tghh1HHexwefGwl9Pg00XRn9FEgESX2WHIuwYnDTWvtIZ2b00DsFO41ck');
    // Secret do Webhook Remoto (configurada diretamente no painel do Stripe apontando para o teu domínio https://seusite.com/webhook_stripe.php)
    define('STRIPE_WEBHOOK_SECRET', 'whsec_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'); 
}

// 3. Detetar dinamicamente a URL Base do projeto para funcionar em qualquer servidor
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Captura a pasta atual dinamicamente (trata caminhos locais como /TESTEGOOGLE/berserkfit-main/berserkfit-main/)
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$dir = dirname($script_name);
$dir = str_replace('\\', '/', $dir); // Normalizar barras no Windows
if (substr($dir, -1) !== '/') {
    $dir .= '/';
}

define('BASE_URL', $protocol . $host . $dir);

// 4. Configuração dos Preços dos Planos (valores em cêntimos de Euro para a API do Stripe)
$planos_config = [
    'gladiator' => [
        'nome' => 'Plano Gladiator',
        'descricao' => 'Gerador de treinos com IA avançada, Histórico completo de consistência, Checklist diária ilimitada',
        'preco_centimos' => 1990, // €19.90
        'frequencia' => 'month', // Mensal
    ],
    'berserker' => [
        'nome' => 'Plano Berserker',
        'descricao' => 'Tudo do Gladiator, Notificações motivacionais personalizadas, Suporte prioritário via chat, Acesso antecipado',
        'preco_centimos' => 3990, // €39.90
        'frequencia' => 'month', // Mensal
    ]
];
?>
