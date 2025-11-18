<?php
/**
 * Classe para integração com gateways de pagamento (Stripe)
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/env-loader.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;

class PaymentGateway {
    protected $conn;
    protected $gateway_name;
    protected $stripe_secret_key;
    
    public function __construct($gateway_name = 'stripe') {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->gateway_name = $gateway_name;
        
        // Carregar chave do Stripe
        $this->stripe_secret_key = defined('STRIPE_SECRET_KEY') ? STRIPE_SECRET_KEY : '';
        
        if (empty($this->stripe_secret_key)) {
            throw new Exception('Chave secreta do Stripe não configurada. Configure STRIPE_SECRET_KEY no arquivo .env');
        }
        
        // Configurar Stripe
        Stripe::setApiKey($this->stripe_secret_key);
    }
    
    /**
     * Cria uma ordem de pagamento com Stripe Checkout
     * @param int $user_id ID do usuário
     * @param int $plano_id ID do plano
     * @param float $valor Valor do pagamento em reais
     * @param string $metodo Método de pagamento ('card', 'pix', 'boleto')
     * @return array Dados da ordem de pagamento
     */
    public function createPaymentOrder($user_id, $plano_id, $valor, $metodo = 'card') {
        try {
            // Buscar dados do usuário
            $userQuery = "SELECT nome, email FROM users WHERE id = :user_id";
            $userStmt = $this->conn->prepare($userQuery);
            $userStmt->bindParam(':user_id', $user_id);
            $userStmt->execute();
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception('Usuário não encontrado');
            }
            
            // Buscar dados do plano
            $planoQuery = "SELECT nome, slug FROM planos WHERE id = :plano_id";
            $planoStmt = $this->conn->prepare($planoQuery);
            $planoStmt->bindParam(':plano_id', $plano_id);
            $planoStmt->execute();
            $plano = $planoStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$plano) {
                throw new Exception('Plano não encontrado');
            }
            
            // Converter valor para centavos (Stripe trabalha com centavos)
            $valorCentavos = (int)($valor * 100);
            
            // URL base para callbacks
            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') 
                      . '://' . $_SERVER['HTTP_HOST'] 
                      . dirname($_SERVER['SCRIPT_NAME']);
            
            // Configurar métodos de pagamento baseado no parâmetro
            $paymentMethodTypes = [];
            $mode = 'subscription'; // Padrão: assinatura recorrente
            
            if ($metodo === 'pix') {
                // PIX é pagamento único, não assinatura recorrente
                $paymentMethodTypes = ['pix'];
                $mode = 'payment';
            } elseif ($metodo === 'boleto') {
                // Boleto também é pagamento único
                $paymentMethodTypes = ['boleto'];
                $mode = 'payment';
            } else {
                // Padrão: cartão (assinatura recorrente) e PIX (pagamento único)
                // Para assinatura recorrente, só cartão funciona
                $paymentMethodTypes = ['card'];
                $mode = 'subscription';
            }
            
            // Preparar line_items baseado no modo
            if ($mode === 'subscription') {
                // Assinatura recorrente mensal
                $lineItems = [[
                    'price_data' => [
                        'currency' => 'brl',
                        'product_data' => [
                            'name' => $plano['nome'],
                            'description' => 'Assinatura mensal - ' . $plano['nome'],
                        ],
                        'unit_amount' => $valorCentavos,
                        'recurring' => [
                            'interval' => 'month',
                        ],
                    ],
                    'quantity' => 1,
                ]];
            } else {
                // Pagamento único (PIX/Boleto)
                $lineItems = [[
                    'price_data' => [
                        'currency' => 'brl',
                        'product_data' => [
                            'name' => $plano['nome'],
                            'description' => 'Assinatura mensal - ' . $plano['nome'] . ' (Pagamento único)',
                        ],
                        'unit_amount' => $valorCentavos,
                    ],
                    'quantity' => 1,
                ]];
            }
            
            // Criar sessão de checkout do Stripe
            $session = Session::create([
                'payment_method_types' => $paymentMethodTypes,
                'line_items' => $lineItems,
                'mode' => $mode,
                'success_url' => $baseUrl . '/pagamento-sucesso.php?session_id={CHECKOUT_SESSION_ID}&plano=' . urlencode($plano['slug']),
                'cancel_url' => $baseUrl . '/planos.php?cancelado=1',
                'customer_email' => $user['email'],
                'metadata' => [
                    'user_id' => $user_id,
                    'plano_id' => $plano_id,
                    'plano_nome' => $plano['nome'],
                    'modo_pagamento' => $mode,
                ],
                'locale' => 'pt-BR',
            ]);
            
            return [
                'success' => true,
                'external_id' => $session->id,
                'payment_url' => $session->url,
                'status' => 'pending',
                'session_id' => $session->id,
            ];
            
        } catch (ApiErrorException $e) {
            error_log("Erro Stripe API: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erro ao criar sessão de pagamento. Tente novamente.'
            ];
        } catch (Exception $e) {
            error_log("Erro ao criar pagamento: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erro ao processar pagamento. Tente novamente.'
            ];
        }
    }
    
    /**
     * Processa webhook do Stripe
     * @param array|object $data Dados do webhook (pode ser array ou Stripe\Event)
     * @return bool
     */
    public function processWebhook($data) {
        try {
            // Converter para array se for objeto Stripe
            if (is_object($data)) {
                $eventType = $data->type ?? null;
                $eventData = $data->data->object ?? null;
                
                if ($eventType && $eventData) {
                    // Converter objeto para array recursivamente
                    $session = json_decode(json_encode($eventData), true);
                    $event = ['type' => $eventType, 'data' => ['object' => $session]];
                } else {
                    $event = json_decode(json_encode($data), true);
                }
            } else {
                $event = $data;
            }
            
            // Verificar tipo de evento
            $eventType = $event['type'] ?? null;
            
            switch ($eventType) {
                case 'checkout.session.completed':
                    $session = $event['data']['object'] ?? [];
                    $this->handleCheckoutCompleted($session);
                    break;
                    
                case 'customer.subscription.created':
                case 'customer.subscription.updated':
                    $subscription = $event['data']['object'] ?? [];
                    $this->handleSubscriptionUpdate($subscription);
                    break;
                    
                case 'customer.subscription.deleted':
                    $subscription = $event['data']['object'] ?? [];
                    $this->handleSubscriptionDeleted($subscription);
                    break;
                    
                case 'invoice.payment_succeeded':
                    $invoice = $event['data']['object'] ?? [];
                    $this->handleInvoicePaymentSucceeded($invoice);
                    break;
                    
                case 'invoice.payment_failed':
                    $invoice = $event['data']['object'] ?? [];
                    $this->handleInvoicePaymentFailed($invoice);
                    break;
                    
                default:
                    error_log("Tipo de evento não processado: " . $eventType);
            }
            
            error_log("Webhook processado: " . $eventType);
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao processar webhook: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Manipula checkout completado
     */
    private function handleCheckoutCompleted($session) {
        $metadata = $session['metadata'] ?? [];
        $user_id = $metadata['user_id'] ?? null;
        $plano_id = $metadata['plano_id'] ?? null;
        $modo_pagamento = $metadata['modo_pagamento'] ?? 'subscription';
        $subscription_id = $session['subscription'] ?? null;
        $payment_intent_id = $session['payment_intent'] ?? null;
        
        if ($user_id && $plano_id) {
            require_once dirname(__DIR__) . '/classes/PlanService.php';
            $planService = new PlanService();
            
            // Para pagamento único (PIX/Boleto), usar payment_intent_id
            // Para assinatura recorrente, usar subscription_id
            $external_id = ($modo_pagamento === 'subscription' && $subscription_id) 
                         ? $subscription_id 
                         : ($payment_intent_id ?? $session['id']);
            
            // Verificar se já existe assinatura com este external_id
            $query = "SELECT id FROM assinaturas WHERE external_id = :external_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':external_id', $external_id);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                // Criar nova assinatura
                $planService->createSubscription(
                    $user_id,
                    $plano_id,
                    'stripe',
                    $external_id,
                    [
                        'session_id' => $session['id'],
                        'customer_id' => $session['customer'] ?? null,
                        'subscription_id' => $subscription_id,
                        'payment_intent_id' => $payment_intent_id,
                        'modo_pagamento' => $modo_pagamento,
                    ]
                );
            }
            
            // Atualizar status para ativo
            $planService->updateSubscriptionByExternalId($external_id, 'ativo', null);
        }
    }
    
    /**
     * Manipula atualização de assinatura
     */
    private function handleSubscriptionUpdate($subscription) {
        require_once dirname(__DIR__) . '/classes/PlanService.php';
        $planService = new PlanService();
        
        // Converter para array se necessário
        if (is_object($subscription)) {
            $subscription = json_decode(json_encode($subscription), true);
        }
        
        $subscription_id = $subscription['id'] ?? null;
        if (!$subscription_id) {
            return;
        }
        
        $status = ($subscription['status'] ?? '') === 'active' ? 'ativo' : 'pendente';
        $dataFim = null;
        
        if (isset($subscription['cancel_at']) && $subscription['cancel_at']) {
            $dataFim = is_numeric($subscription['cancel_at']) 
                     ? date('Y-m-d H:i:s', $subscription['cancel_at']) 
                     : $subscription['cancel_at'];
        }
        
        $planService->updateSubscriptionByExternalId($subscription_id, $status, $dataFim);
    }
    
    /**
     * Manipula exclusão de assinatura
     */
    private function handleSubscriptionDeleted($subscription) {
        require_once dirname(__DIR__) . '/classes/PlanService.php';
        $planService = new PlanService();
        
        // Converter para array se necessário
        if (is_object($subscription)) {
            $subscription = json_decode(json_encode($subscription), true);
        }
        
        $subscription_id = $subscription['id'] ?? null;
        if ($subscription_id) {
            $planService->updateSubscriptionByExternalId($subscription_id, 'cancelado', date('Y-m-d H:i:s'));
        }
    }
    
    /**
     * Manipula pagamento de invoice bem-sucedido
     */
    private function handleInvoicePaymentSucceeded($invoice) {
        // Converter para array se necessário
        if (is_object($invoice)) {
            $invoice = json_decode(json_encode($invoice), true);
        }
        
        $subscription_id = $invoice['subscription'] ?? null;
        if ($subscription_id) {
            require_once dirname(__DIR__) . '/classes/PlanService.php';
            $planService = new PlanService();
            $planService->updateSubscriptionByExternalId($subscription_id, 'ativo', null);
        }
    }
    
    /**
     * Manipula falha no pagamento de invoice
     */
    private function handleInvoicePaymentFailed($invoice) {
        // Converter para array se necessário
        if (is_object($invoice)) {
            $invoice = json_decode(json_encode($invoice), true);
        }
        
        $subscription_id = $invoice['subscription'] ?? null;
        if ($subscription_id) {
            require_once dirname(__DIR__) . '/classes/PlanService.php';
            $planService = new PlanService();
            $planService->updateSubscriptionByExternalId($subscription_id, 'pendente', null);
        }
    }
    
    /**
     * Verifica status de um pagamento via session_id
     * @param string $session_id ID da sessão do Stripe
     * @return array Status do pagamento
     */
    public function checkPaymentStatus($session_id) {
        try {
            $session = Session::retrieve($session_id);
            
            $status = 'pending';
            if ($session->payment_status === 'paid') {
                $status = 'approved';
            } elseif ($session->payment_status === 'unpaid') {
                $status = 'pending';
            }
            
            return [
                'status' => $status,
                'external_id' => $session_id,
                'payment_status' => $session->payment_status,
                'subscription_id' => $session->subscription ?? null,
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao verificar status do pagamento: " . $e->getMessage());
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Recupera informações de uma sessão de checkout
     * @param string $session_id ID da sessão
     * @return array|null
     */
    public function getSessionInfo($session_id) {
        try {
            $session = Session::retrieve($session_id);
            return [
                'id' => $session->id,
                'payment_status' => $session->payment_status,
                'subscription' => $session->subscription,
                'customer_email' => $session->customer_email,
                'metadata' => $session->metadata->toArray(),
            ];
        } catch (Exception $e) {
            error_log("Erro ao recuperar sessão: " . $e->getMessage());
            return null;
        }
    }
}

