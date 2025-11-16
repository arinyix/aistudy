<?php
/**
 * Classe base para integração com gateways de pagamento
 * Por enquanto, implementação básica - pode ser expandida para Mercado Pago, Stripe, etc.
 */

require_once dirname(__DIR__) . '/config/database.php';

class PaymentGateway {
    protected $conn;
    protected $gateway_name;
    
    public function __construct($gateway_name = 'mercado_pago') {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->gateway_name = $gateway_name;
    }
    
    /**
     * Cria uma ordem de pagamento
     * @param int $user_id ID do usuário
     * @param int $plano_id ID do plano
     * @param float $valor Valor do pagamento
     * @return array Dados da ordem de pagamento
     */
    public function createPaymentOrder($user_id, $plano_id, $valor) {
        // Por enquanto, retorna dados simulados
        // Em produção, aqui seria feita a integração real com o gateway
        
        $external_id = 'TEST_' . time() . '_' . rand(1000, 9999);
        
        return [
            'success' => true,
            'external_id' => $external_id,
            'payment_url' => 'pagamento-sucesso.php?order=' . $external_id . '&plano=' . $plano_id,
            'status' => 'pending'
        ];
    }
    
    /**
     * Processa webhook do gateway
     * @param array $data Dados do webhook
     * @return bool
     */
    public function processWebhook($data) {
        // Por enquanto, apenas log
        error_log("Webhook recebido do gateway {$this->gateway_name}: " . json_encode($data));
        
        // Em produção, aqui seria feita a validação e atualização da assinatura
        return true;
    }
    
    /**
     * Verifica status de um pagamento
     * @param string $external_id ID externo do pagamento
     * @return array Status do pagamento
     */
    public function checkPaymentStatus($external_id) {
        // Por enquanto, retorna status simulado
        return [
            'status' => 'approved',
            'external_id' => $external_id
        ];
    }
}

