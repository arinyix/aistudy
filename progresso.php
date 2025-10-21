<?php
require_once 'config/database.php';
require_once 'classes/Routine.php';
require_once 'classes/Quiz.php';
require_once 'includes/session.php';

requireLogin();

$user = getCurrentUser();

$database = new Database();
$db = $database->getConnection();
$routine = new Routine($db);
$quiz = new Quiz($db);

// Buscar dados do usuário
$routines = $routine->getUserRoutines($user['id']);
$quizzes = $quiz->getUserQuizzes($user['id']);
$media_quiz = $quiz->getAverageScore($user['id']);

// Calcular estatísticas
$total_routines = count($routines);
$rotinas_ativas = count(array_filter($routines, function($r) { return $r['status'] === 'ativa'; }));
$rotinas_concluidas = count(array_filter($routines, function($r) { return $r['status'] === 'concluida'; }));
$total_quizzes = count($quizzes);
$quizzes_concluidos = count(array_filter($quizzes, function($q) { return $q['status'] === 'concluido'; }));

// Progresso médio das rotinas
$progresso_medio = 0;
if ($total_routines > 0) {
    $soma_progresso = array_sum(array_column($routines, 'progresso'));
    $progresso_medio = $soma_progresso / $total_routines;
}

// Filtro por rotina
$filtro_rotina = $_GET['rotina'] ?? 'todas';
$rotinas_filtradas = $routines;
if ($filtro_rotina !== 'todas') {
    $rotinas_filtradas = array_filter($routines, function($r) use ($filtro_rotina) {
        return $r['id'] == $filtro_rotina;
    });
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIStudy - Progresso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                        <a class="nav-link" href="rotinas.php">
                            <i class="fas fa-list me-1"></i>Minhas Rotinas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="progresso.php">
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
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="text-gradient">Meu Progresso</h1>
                        <p class="text-muted">Acompanhe seu desempenho e evolução nos estudos</p>
                    </div>
                    <div class="d-flex gap-2">
                        <select class="form-select" id="filtroRotina" onchange="filtrarRotina()">
                            <option value="todas" <?php echo $filtro_rotina === 'todas' ? 'selected' : ''; ?>>Todas as Rotinas</option>
                            <?php foreach ($routines as $rotina): ?>
                                <option value="<?php echo $rotina['id']; ?>" <?php echo $filtro_rotina == $rotina['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($rotina['titulo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estatísticas Gerais -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stat-number text-primary"><?php echo $total_routines; ?></div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="stat-label">Total de Rotinas</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stat-number text-success"><?php echo $rotinas_concluidas; ?></div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="stat-label">Rotinas Concluídas</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stat-number text-warning"><?php echo $quizzes_concluidos; ?></div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="stat-label">Quizzes Realizados</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stat-number text-info"><?php echo number_format($media_quiz, 1); ?>%</div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="stat-label">Média nos Quizzes</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row mb-4">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>Progresso das Rotinas
                        </h6>
                    </div>
                    <div class="card-body">
                        <canvas id="progressoChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Desempenho nos Quizzes
                        </h6>
                    </div>
                    <div class="card-body">
                        <canvas id="quizChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Rotinas -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-list me-2"></i>Detalhamento das Rotinas
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($rotinas_filtradas)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-chart-line text-muted fa-3x mb-3"></i>
                                <h5>Nenhuma rotina encontrada</h5>
                                <p class="text-muted">Crie sua primeira rotina para começar a acompanhar seu progresso.</p>
                                <a href="criar-rotina.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Criar Rotina
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Rotina</th>
                                            <th>Tema</th>
                                            <th>Nível</th>
                                            <th>Progresso</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rotinas_filtradas as $rotina): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($rotina['titulo']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($rotina['tema']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $rotina['nivel'] === 'iniciante' ? 'success' : ($rotina['nivel'] === 'intermediario' ? 'warning' : 'danger'); ?>">
                                                        <?php echo ucfirst($rotina['nivel']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress me-2" style="width: 100px; height: 8px;">
                                                            <div class="progress-bar" style="width: <?php echo $rotina['progresso']; ?>%"></div>
                                                        </div>
                                                        <small class="text-muted"><?php echo number_format($rotina['progresso'], 1); ?>%</small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $rotina['status'] === 'ativa' ? 'success' : ($rotina['status'] === 'pausada' ? 'warning' : 'secondary'); ?>">
                                                        <?php echo ucfirst($rotina['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="rotina-detalhada.php?id=<?php echo $rotina['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye me-1"></i>Ver
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gráfico de Progresso das Rotinas
        const progressoCtx = document.getElementById('progressoChart').getContext('2d');
        new Chart(progressoCtx, {
            type: 'doughnut',
            data: {
                labels: ['Concluídas', 'Em Andamento', 'Pausadas'],
                datasets: [{
                    data: [<?php echo $rotinas_concluidas; ?>, <?php echo $rotinas_ativas; ?>, <?php echo $total_routines - $rotinas_concluidas - $rotinas_ativas; ?>],
                    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Gráfico de Desempenho nos Quizzes
        const quizCtx = document.getElementById('quizChart').getContext('2d');
        const quizData = <?php echo json_encode(array_column($quizzes, 'nota')); ?>;
        const quizLabels = <?php echo json_encode(array_map(function($q) { return substr($q['titulo'], 0, 20) . '...'; }, $quizzes)); ?>;
        
        new Chart(quizCtx, {
            type: 'bar',
            data: {
                labels: quizLabels,
                datasets: [{
                    label: 'Nota (%)',
                    data: quizData,
                    backgroundColor: '#6366f1',
                    borderColor: '#4f46e5',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        function filtrarRotina() {
            const rotinaId = document.getElementById('filtroRotina').value;
            window.location.href = 'progresso.php?rotina=' + rotinaId;
        }
    </script>
</body>
</html>
