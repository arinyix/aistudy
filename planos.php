<?php
require_once 'includes/session.php';
require_once 'classes/PlanService.php';
require_once 'includes/navbar.php';

requireLogin();
$user = getCurrentUser();

$planService = new PlanService();
$planos = $planService->getAllPlans();
$planoAtivo = $planService->getActiveSubscription($user['id']);

$erro = $_GET['erro'] ?? '';
$tipo = $_GET['tipo'] ?? '';

$message = '';
if ($erro === 'precisa_assinar') {
    $tipoTexto = $tipo === 'enem' ? 'Modo ENEM' : ($tipo === 'concurso' ? 'Modo Concurso' : 'este recurso');
    $message = '<div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Você precisa de uma assinatura ativa para acessar o ' . htmlspecialchars($tipoTexto) . '.
    </div>';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planos - AIStudy</title>
    
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php $active = 'planos'; render_navbar($active); ?>

    <div class="container mt-5 mb-5">
        <?php echo $message; ?>
        
        <div class="text-center mb-4">
            <h2 class="mb-3">Escolha seu Plano</h2>
            <p class="text-muted">Selecione o plano ideal para seus estudos</p>
        </div>
        
        <?php if ($planoAtivo): ?>
            <div class="alert alert-success mb-4">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Plano Ativo:</strong> <?php echo htmlspecialchars($planoAtivo['plano_nome']); ?>
                <?php if ($planoAtivo['data_fim']): ?>
                    <br><small>Válido até: <?php echo date('d/m/Y', strtotime($planoAtivo['data_fim'])); ?></small>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="row g-4 justify-content-center">
            <?php foreach ($planos as $plano): ?>
                <?php
                $isPopular = $plano['slug'] === 'premium';
                $isActive = $planoAtivo && $planoAtivo['plano_slug'] === $plano['slug'];
                ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 <?php echo $isPopular ? 'border-primary border-2' : ''; ?>">
                        <?php if ($isPopular): ?>
                            <div class="card-header bg-primary text-white text-center">
                                <strong>Mais Popular</strong>
                            </div>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($plano['nome']); ?></h5>
                            <div class="mb-3">
                                <span class="h3">R$ <?php echo number_format($plano['preco_mensal'], 2, ',', '.'); ?></span>
                                <small class="text-muted">/mês</small>
                            </div>
                            <p class="card-text text-muted small mb-3"><?php echo htmlspecialchars($plano['descricao']); ?></p>
                            
                            <ul class="list-unstyled mb-4">
                                <?php foreach ($plano['recursos'] as $recurso): ?>
                                    <?php
                                    $recursoTexto = [
                                        'rotinas_gerais' => 'Rotinas Gerais',
                                        'modo_enem' => 'Modo ENEM',
                                        'modo_concurso' => 'Modo Concurso',
                                        'resumos_completos' => 'Resumos Completos',
                                        'suporte_prioritario' => 'Suporte Prioritário',
                                        'recursos_avancados' => 'Recursos Avançados'
                                    ];
                                    ?>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        <?php echo $recursoTexto[$recurso] ?? $recurso; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <div class="mt-auto">
                                <?php if ($isActive): ?>
                                    <button class="btn btn-success w-100" disabled>
                                        <i class="fas fa-check me-2"></i>Plano Ativo
                                    </button>
                                <?php else: ?>
                                    <a href="checkout.php?plano=<?php echo urlencode($plano['slug']); ?>" 
                                       class="btn <?php echo $isPopular ? 'btn-primary' : 'btn-outline-primary'; ?> w-100">
                                        <?php echo $plano['preco_mensal'] > 0 ? 'Assinar' : 'Ativar Grátis'; ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5">
            <p class="text-muted">
                <i class="fas fa-shield-alt me-2"></i>
                Pagamento seguro via Mercado Pago. Cancele quando quiser.
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dark-mode.js"></script>
</body>
</html>

