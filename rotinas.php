<?php
require_once 'config/database.php';
require_once 'classes/Routine.php';
require_once 'includes/session.php';

requireLogin();

$user = getCurrentUser();

$database = new Database();
$db = $database->getConnection();
$routine = new Routine($db);

// Buscar rotinas do usuário
$routines = $routine->getUserRoutines($user['id']);

// Processar exclusão de rotina
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $routine_id = $_GET['delete'];
    if ($routine->delete($routine_id, $user['id'])) {
        $message = '<div class="alert alert-success">Rotina excluída com sucesso!</div>';
    } else {
        $message = '<div class="alert alert-danger">Erro ao excluir rotina!</div>';
    }
    // Recarregar rotinas após exclusão
    $routines = $routine->getUserRoutines($user['id']);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIStudy - Minhas Rotinas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-brain text-primary"></i> AIStudy
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="rotinas.php">
                            <i class="fas fa-list me-1"></i>Minhas Rotinas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="progresso.php">
                            <i class="fas fa-chart-line me-1"></i>Progresso
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($user['nome']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="configuracoes.php">
                                <i class="fas fa-cog me-2"></i>Configurações
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Sair
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="text-gradient">Minhas Rotinas</h1>
                        <p class="text-muted">Gerencie suas rotinas de estudos</p>
                    </div>
                    <a href="criar-rotina.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Nova Rotina
                    </a>
                </div>
            </div>
        </div>

        <?php echo $message ?? ''; ?>

        <!-- Lista de Rotinas -->
        <div class="row">
            <?php if (empty($routines)): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-book-open text-muted fa-4x mb-3"></i>
                            <h4>Nenhuma rotina encontrada</h4>
                            <p class="text-muted">Que tal criar sua primeira rotina de estudos?</p>
                            <a href="criar-rotina.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Criar Primeira Rotina
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($routines as $rotina): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($rotina['titulo']); ?></h6>
                                    <span class="badge bg-<?php echo $rotina['status'] === 'ativa' ? 'success' : ($rotina['status'] === 'pausada' ? 'warning' : 'secondary'); ?>">
                                        <?php echo ucfirst($rotina['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($rotina['tema']); ?>
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-signal me-1"></i><?php echo ucfirst($rotina['nivel']); ?>
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i><?php echo $rotina['tempo_diario']; ?> min/dia
                                    </small>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="text-muted">Progresso</small>
                                        <small class="text-muted"><?php echo number_format($rotina['progresso'], 1); ?>%</small>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: <?php echo $rotina['progresso']; ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        Dias: <?php echo implode(', ', json_decode($rotina['dias_disponiveis'])); ?>
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        Horário: <?php echo date('H:i', strtotime($rotina['horario_disponivel'])); ?>
                                    </small>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between">
                                    <a href="rotina-detalhada.php?id=<?php echo $rotina['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i>Abrir
                                    </a>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="rotina-detalhada.php?id=<?php echo $rotina['id']; ?>">
                                                <i class="fas fa-eye me-2"></i>Ver Detalhes
                                            </a></li>
                                            <li><a class="dropdown-item" href="editar-rotina.php?id=<?php echo $rotina['id']; ?>">
                                                <i class="fas fa-edit me-2"></i>Editar
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" 
                                                   href="rotinas.php?delete=<?php echo $rotina['id']; ?>" 
                                                   onclick="return confirm('Tem certeza que deseja excluir esta rotina?')">
                                                <i class="fas fa-trash me-2"></i>Excluir
                                            </a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
