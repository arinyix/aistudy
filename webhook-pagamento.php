<?php
/**
 * Webhook para receber notificações de pagamento do Stripe
 */

require_once 'config/database.php';
require_once 'config/env-loader.php';
require_once 'classes/PlanService.php';
require_once 'classes/PaymentGateway.php';
require_once 'vendor/autoload.php';

use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

// Obter payload do webhook
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Log da requisição (sem dados sensíveis)
error_log("Webhook Stripe recebido - Signature: " . substr($sig_header, 0, 20) . "...");

$webhook_secret = defined('STRIPE_WEBHOOK_SECRET') ? STRIPE_WEBHOOK_SECRET : '';

try {
    // Verificar assinatura do webhook (se configurado)
    if (!empty($webhook_secret) && !empty($sig_header)) {
        try {
            $event = Webhook::constructEvent($payload, $sig_header, $webhook_secret);
        } catch (SignatureVerificationException $e) {
            error_log("Erro na verificação da assinatura do webhook: " . $e->getMessage());
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Assinatura inválida']);
            exit;
        }
    } else {
        // Se não tiver webhook secret configurado, apenas decodificar JSON
        $event = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON inválido no webhook');
        }
    }
    
    // Processar webhook
    $gateway = new PaymentGateway('stripe');
    $result = $gateway->processWebhook($event);
    
    if ($result) {
        http_response_code(200);
        echo json_encode(['status' => 'ok', 'processed' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Erro ao processar webhook']);
    }
    
} catch (Exception $e) {
    error_log("Erro ao processar webhook Stripe: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

