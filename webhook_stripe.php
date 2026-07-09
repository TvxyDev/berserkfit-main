<?php
/**
 * Webhook Seguro do Stripe
 * Escuta eventos da Stripe (pagamentos, renovações e cancelamentos)
 * BerserkFit AI
 */

require 'ligacao.php';
require 'config_stripe.php';
require 'vendor/autoload.php';

// Caminho do log para depuração (tanto local como no servidor remoto)
$log_file = 'stripe_webhook_log.txt';

function log_message($msg) {
    global $log_file;
    $date = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$date] $msg\n", FILE_APPEND);
}

log_message("Webhook recebido.");

// 1. Obter o corpo do pedido e o cabeçalho de assinatura
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$event = null;

try {
    // 2. Verificar a assinatura do webhook (Garante segurança contra falsificações)
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, STRIPE_WEBHOOK_SECRET
    );
} catch(\UnexpectedValueException $e) {
    log_message("❌ ERRO: Payload inválido.");
    http_response_code(400);
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    log_message("❌ ERRO: Assinatura do webhook inválida. Verifica se STRIPE_WEBHOOK_SECRET está correta.");
    http_response_code(400);
    exit();
}

log_message("✅ Assinatura verificada. Tipo de evento: " . $event->type);

// 3. Tratar os diferentes eventos enviados pela Stripe
switch ($event->type) {
    
    // CASO 1: Sessão de checkout concluída (Primeiro pagamento bem-sucedido)
    case 'checkout.session.completed':
        $session = $event->data->object;
        
        $id_user = $session->client_reference_id ?? null;
        $stripe_customer_id = $session->customer ?? null;
        $stripe_subscription_id = $session->subscription ?? null;
        $stripe_session_id = $session->id ?? null;
        $tipo_plano = $session->metadata->tipo_plano ?? null;
        $valor_pago = ($session->amount_total) ? ($session->amount_total / 100) : 0;
        
        log_message("Processando checkout.session.completed para User ID: $id_user, Plano: $tipo_plano, Cliente Stripe: $stripe_customer_id");

        if ($id_user && $tipo_plano) {
            // Evitar duplicados (verifica se a transação já foi processada)
            $stmt = $conn->prepare("SELECT id_pagamento FROM pagamento WHERE stripe_checkout_session_id = ?");
            $stmt->bind_param("s", $stripe_session_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                // Fechar consulta anterior
                $stmt->close();

                // 1. Atualizar o plano e expiração do utilizador (30 dias iniciais)
                $data_expiracao = date('Y-m-d', strtotime('+30 days'));
                
                $update_stmt = $conn->prepare("UPDATE user SET tipo_plano = ?, data_expiracao_plano = ?, stripe_customer_id = ? WHERE id_user = ?");
                $update_stmt->bind_param("sssi", $tipo_plano, $data_expiracao, $stripe_customer_id, $id_user);
                
                if ($update_stmt->execute()) {
                    log_message("Plano do utilizador $id_user atualizado para '$tipo_plano' com expiração em $data_expiracao");
                } else {
                    log_message("❌ ERRO ao atualizar plano do utilizador: " . $conn->error);
                }
                $update_stmt->close();

                // 2. Registar o pagamento na base de dados
                $data_hoje = date('Y-m-d');
                $metodo = 'stripe_card';
                
                $insert_stmt = $conn->prepare("INSERT INTO pagamento (id_user, tipo_plano, valor_pago, data_pagamento, metodo_pagamento, stripe_checkout_session_id, stripe_subscription_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $insert_stmt->bind_param("isdssss", $id_user, $tipo_plano, $valor_pago, $data_hoje, $metodo, $stripe_session_id, $stripe_subscription_id);
                
                if ($insert_stmt->execute()) {
                    log_message("Pagamento registado com sucesso para o utilizador $id_user.");
                } else {
                    log_message("❌ ERRO ao registar pagamento: " . $conn->error);
                }
                $insert_stmt->close();
            } else {
                log_message("⚠️ Transação $stripe_session_id já tinha sido processada anteriormente.");
                $stmt->close();
            }
        }
        break;

    // CASO 2: Renovação de assinatura bem-sucedida (Ocorre mensalmente)
    case 'invoice.payment_succeeded':
        $invoice = $event->data->object;
        $stripe_customer_id = $invoice->customer;
        $stripe_subscription_id = $invoice->subscription;
        $valor_pago = ($invoice->amount_paid) ? ($invoice->amount_paid / 100) : 0;
        
        log_message("Processando invoice.payment_succeeded para Cliente Stripe: $stripe_customer_id");

        if ($stripe_customer_id && $stripe_subscription_id) {
            // Localizar o utilizador através do stripe_customer_id
            $stmt = $conn->prepare("SELECT id_user, tipo_plano FROM user WHERE stripe_customer_id = ?");
            $stmt->bind_param("s", $stripe_customer_id);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($res) {
                $id_user = $res['id_user'];
                $tipo_plano = $res['tipo_plano'];
                
                // Estender por mais 30 dias a partir da data de expiração atual ou de hoje
                $data_expiracao = date('Y-m-d', strtotime('+30 days'));

                $update_stmt = $conn->prepare("UPDATE user SET data_expiracao_plano = ? WHERE id_user = ?");
                $update_stmt->bind_param("si", $data_expiracao, $id_user);
                $update_stmt->execute();
                $update_stmt->close();
                
                log_message("Assinatura do utilizador $id_user renovada. Nova expiração: $data_expiracao");

                // Registar o pagamento de renovação
                $data_hoje = date('Y-m-d');
                $metodo = 'stripe_subscription';
                $stripe_invoice_id = $invoice->id;
                
                $insert_stmt = $conn->prepare("INSERT INTO pagamento (id_user, tipo_plano, valor_pago, data_pagamento, metodo_pagamento, stripe_checkout_session_id, stripe_subscription_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $insert_stmt->bind_param("isdssss", $id_user, $tipo_plano, $valor_pago, $data_hoje, $metodo, $stripe_invoice_id, $stripe_subscription_id);
                $insert_stmt->execute();
                $insert_stmt->close();
            } else {
                log_message("⚠️ Cliente Stripe $stripe_customer_id não encontrado na nossa base de dados.");
            }
        }
        break;

    // CASO 3: Cancelamento de Assinatura (Por falta de pagamento ou cancelado pelo utilizador)
    case 'customer.subscription.deleted':
        $subscription = $event->data->object;
        $stripe_subscription_id = $subscription->id;
        
        log_message("Processando customer.subscription.deleted para a Subscrição: $stripe_subscription_id");

        // Encontrar o utilizador correspondente e reverter para plano gratuito
        $stmt = $conn->prepare("SELECT id_user FROM user WHERE stripe_customer_id = ?");
        $stripe_customer_id = $subscription->customer;
        $stmt->bind_param("s", $stripe_customer_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($res) {
            $id_user = $res['id_user'];
            
            $update_stmt = $conn->prepare("UPDATE user SET tipo_plano = 'gratuito', data_expiracao_plano = NULL WHERE id_user = ?");
            $update_stmt->bind_param("i", $id_user);
            $update_stmt->execute();
            $update_stmt->close();
            
            log_message("Subscrição cancelada. Utilizador $id_user revertido para o plano 'gratuito'.");
        }
        break;

    default:
        log_message("Evento ignorado: " . $event->type);
        break;
}

// 4. Responder com sucesso (HTTP 200) para a Stripe saber que recebemos
http_response_code(200);
echo json_encode(['status' => 'success']);
?>
