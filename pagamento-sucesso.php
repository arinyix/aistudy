<?php
require_once 'includes/session.php';
require_once 'classes/PlanService.php';
require_once 'classes/PaymentGateway.php';

requireLogin();
$user = getCurrentUser();

$planService = new PlanService();
$planoSlug = $_GET['plano'] ?? '';
$session_id = $_GET['session_id'] ?? '';

$plano = null;
if ($planoSlug) {
    $plano = $planService->getPlanBySlug($planoSlug);
}

// Se tiver session_id, verificar status do pagamento
if (!empty($session_id)) {
    try {
        $gateway = new PaymentGateway('stripe');
        $sessionInfo = $gateway->getSessionInfo($session_id);
        
        if ($sessionInfo && $sessionInfo['payment_status'] === 'paid') {
            // Pagamento confirmado, atualizar assinatura se necessário
            $subscription_id = $sessionInfo['subscription'] ?? null;
            if ($subscription_id) {
                $planService->updateSubscriptionByExternalId($subscription_id, 'ativo', null);
            }
        }
    } catch (Exception $e) {
        error_log("Erro ao verificar sessão: " . $e->getMessage());
    }
}

$planoAtivo = $planService->getActiveSubscription($user['id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Confirmado - AIStudy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card text-center">
                    <div class="card-body py-5">
                        <div class="mb-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                        </div>
                        <h2 class="mb-3">Pagamento Confirmado!</h2>
                        <p class="text-muted mb-4">
                            <?php if ($plano): ?>
                                Seu plano <strong><?php echo htmlspecialchars($plano['nome']); ?></strong> foi ativado com sucesso!
                            <?php else: ?>
                                Seu plano foi ativado com sucesso!
                            <?php endif; ?>
                        </p>
                        
                        <?php if ($planoAtivo): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Plano Ativo:</strong> <?php echo htmlspecialchars($planoAtivo['plano_nome']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="d-grid gap-2 mt-4">
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i>Ir para Dashboard
                            </a>
                            <?php if ($plano && in_array('modo_enem', $plano['recursos'])): ?>
                                <a href="modo-enem.php" class="btn btn-outline-primary">
                                    <i class="fas fa-graduation-cap me-2"></i>Criar Plano ENEM
                                </a>
                            <?php endif; ?>
                            <?php if ($plano && in_array('modo_concurso', $plano['recursos'])): ?>
                                <a href="modo-concurso.php" class="btn btn-outline-primary">
                                    <i class="fas fa-briefcase me-2"></i>Criar Plano Concurso
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dark-mode.js"></script>
</body>
</html>

