<?php
require_once 'includes/session.php';
require_once 'classes/PlanService.php';
require_once 'classes/PaymentGateway.php';

requireLogin();
$user = getCurrentUser();

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
if ($_POST) {
    try {
        // Processar pagamento com Stripe
        $metodo = $_POST['metodo_pagamento'] ?? 'card'; // card, pix, boleto
        $gateway = new PaymentGateway('stripe');
        $result = $gateway->createPaymentOrder($user['id'], $plano['id'], $plano['preco_mensal'], $metodo);
        
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
                header('Location: ' . $result['payment_url']);
                exit;
            } else {
                $message = '<div class="alert alert-danger">Erro ao criar assinatura. Tente novamente.</div>';
            }
        } else {
            $errorMsg = $result['message'] ?? 'Erro ao processar pagamento. Tente novamente.';
            $message = '<div class="alert alert-danger">' . htmlspecialchars($errorMsg) . '</div>';
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-brain me-2"></i>AIStudy
            </a>
        </div>
    </nav>

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
                            <form method="POST">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Modo de Teste:</strong> Você será redirecionado para o Stripe Checkout. 
                                    Use cartões de teste ou PIX para simular o pagamento.
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Método de Pagamento:</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="metodo_pagamento" id="metodo_card" value="card" checked>
                                        <label class="form-check-label" for="metodo_card">
                                            <i class="fas fa-credit-card me-2"></i>Cartão de Crédito ou PIX
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="metodo_pagamento" id="metodo_pix" value="pix">
                                        <label class="form-check-label" for="metodo_pix">
                                            <i class="fas fa-qrcode me-2"></i>Apenas PIX
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Valor de Teste:</strong> R$ <?php echo number_format($plano['preco_mensal'], 2, ',', '.'); ?> 
                                    (valor irrisório para testes)
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-credit-card me-2"></i>Finalizar Pagamento com Stripe
                                    </button>
                                    <a href="planos.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Voltar para Planos
                                    </a>
                                </div>
                            </form>
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

