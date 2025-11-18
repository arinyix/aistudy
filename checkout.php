<?php
require_once 'includes/session.php';
require_once 'classes/PlanService.php';
require_once 'classes/PaymentGateway.php';
require_once 'includes/navbar.php';

requireLogin();
$user = getCurrentUser();
// Tornar $user disponível globalmente para o navbar
$GLOBALS['user'] = $user;

$planService = new PlanService();
$planoSlug = $_GET['plano'] ?? '';

if (empty($planoSlug)) {
    header('Location: planos.php?erro=plano_nao_encontrado');
    exit;
}

$plano = $planService->getPlanBySlug($planoSlug);

if (!$plano) {
    header('Location: planos.php?erro=plano_nao_encontrado');
    exit;
}

// Se o plano for gratuito, ativar diretamente
if ($plano['preco_mensal'] == 0) {
    // Criar assinatura gratuita diretamente (sem passar pelo Stripe)
    $external_id = 'FREE_' . time() . '_' . $user['id'];
    $assinatura_id = $planService->createSubscription(
        $user['id'],
        $plano['id'],
        'free',
        $external_id
    );
    
    if ($assinatura_id) {
        // Ativar assinatura gratuita
        $planService->updateSubscriptionStatus($assinatura_id, 'ativo');
        header('Location: pagamento-sucesso.php?plano=' . urlencode($planoSlug));
        exit;
    }
}

$message = '';
if ($_POST && isset($_POST['processar_pagamento'])) {
    // Log para debug
    error_log("Checkout POST recebido - User ID: {$user['id']}, Plano ID: {$plano['id']}, Valor: {$plano['preco_mensal']}");
    
    try {
        // Processar pagamento com Stripe (apenas cartão de crédito)
        $metodo = 'card'; // Sempre usa cartão de crédito para assinaturas recorrentes
        $gateway = new PaymentGateway('stripe');
        error_log("Criando ordem de pagamento no Stripe...");
        $result = $gateway->createPaymentOrder($user['id'], $plano['id'], $plano['preco_mensal'], $metodo);
        error_log("Resultado do Stripe: " . json_encode($result));
        
        if ($result['success']) {
            // Criar assinatura pendente
            $assinatura_id = $planService->createSubscription(
                $user['id'],
                $plano['id'],
                'stripe',
                $result['session_id'],
                [
                    'valor' => $plano['preco_mensal'],
                    'metodo' => $metodo,
                    'session_id' => $result['session_id']
                ]
            );
            
            if ($assinatura_id) {
                // Redirecionar para checkout do Stripe
                error_log("Redirecionando para Stripe Checkout: " . $result['payment_url']);
                header('Location: ' . $result['payment_url']);
                exit;
            } else {
                error_log("Erro: Assinatura não foi criada");
                $message = '<div class="alert alert-danger">Erro ao criar assinatura. Tente novamente.</div>';
            }
        } else {
            $errorMsg = $result['message'] ?? 'Erro ao processar pagamento. Tente novamente.';
            $errorDetails = '';
            
            // Adicionar detalhes do erro se disponível (apenas em desenvolvimento)
            if (isset($result['error_code'])) {
                $errorDetails = '<br><small class="text-muted">Código: ' . htmlspecialchars($result['error_code']) . '</small>';
            }
            
            $message = '<div class="alert alert-danger">' . htmlspecialchars($errorMsg) . $errorDetails . '</div>';
            
            // Log detalhado do erro
            error_log("Erro no checkout - Método: {$metodo}, Erro: " . ($result['error'] ?? 'Desconhecido'));
        }
    } catch (Exception $e) {
        error_log("Erro no checkout: " . $e->getMessage());
        $message = '<div class="alert alert-danger">Erro ao processar pagamento: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - AIStudy</title>
    
    <!-- Aplicar tema ANTES de carregar estilos para evitar flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = savedTheme || (prefersDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php $active = 'planos'; render_navbar($active); ?>

    <div class="container mt-4 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-shopping-cart me-2"></i>Checkout
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        
                        <div class="mb-4">
                            <h6>Plano Selecionado:</h6>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5><?php echo htmlspecialchars($plano['nome']); ?></h5>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($plano['descricao']); ?></p>
                                    <h4 class="mt-3 mb-0">
                                        R$ <?php echo number_format($plano['preco_mensal'], 2, ',', '.'); ?>
                                        <small class="text-muted">/mês</small>
                                    </h4>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($plano['preco_mensal'] > 0): ?>
                            <form method="POST" action="" id="formCheckout">
                                <input type="hidden" name="processar_pagamento" value="1">
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Modo de Teste:</strong> Você será redirecionado para o Stripe Checkout. 
                                    Use cartões de teste para simular o pagamento.
                                </div>
                                
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Valor de Teste:</strong> R$ <?php echo number_format($plano['preco_mensal'], 2, ',', '.'); ?> 
                                    (valor irrisório para testes)
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg" id="btnFinalizarPagamento">
                                        <i class="fas fa-credit-card me-2"></i>Finalizar Pagamento com Stripe
                                    </button>
                                    <a href="planos.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Voltar para Planos
                                    </a>
                                </div>
                            </form>
                            
                            <script>
                            // Prevenir múltiplos cliques e mostrar loading
                            document.addEventListener('DOMContentLoaded', function() {
                                const form = document.getElementById('formCheckout');
                                const btn = document.getElementById('btnFinalizarPagamento');
                                
                                if (form && btn) {
                                    form.addEventListener('submit', function(e) {
                                        // Desabilitar botão e mostrar loading
                                        btn.disabled = true;
                                        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processando...';
                                        
                                        // Permitir que o formulário seja submetido normalmente
                                        return true;
                                    });
                                }
                            });
                            </script>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                Plano gratuito! Clique no botão abaixo para ativar.
                            </div>
                            <form method="POST">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-check me-2"></i>Ativar Plano Grátis
                                    </button>
                                    <a href="planos.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Voltar para Planos
                                    </a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dark-mode.js"></script>
</body>
</html>

