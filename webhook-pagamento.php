<?php
/**
 * Webhook para receber notificações de pagamento do gateway
 * Por enquanto, apenas estrutura básica - em produção seria integrado com Mercado Pago
 */

require_once 'config/database.php';
require_once 'classes/PlanService.php';
require_once 'classes/PaymentGateway.php';

// Log da requisição
error_log("Webhook recebido: " . json_encode($_POST));

$planService = new PlanService();
$gateway = new PaymentGateway('mercado_pago');

// Processar webhook
$data = $_POST ?? json_decode(file_get_contents('php://input'), true) ?? [];

if (!empty($data)) {
    $gateway->processWebhook($data);
    
    // Em produção, aqui seria feita a validação e atualização da assinatura
    // Por enquanto, apenas retornar sucesso
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Dados inválidos']);
}

