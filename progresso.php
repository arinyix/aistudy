<?php
require_once 'config/database.php';
require_once 'classes/Routine.php';
require_once 'includes/session.php';
require_once 'includes/navbar.php';

requireLogin();

$user = getCurrentUser();

$database = new Database();
$db = $database->getConnection();
$routine = new Routine($db);

// Buscar dados do usuário
$routines = $routine->getUserRoutines($user['id']);

// Calcular estatísticas
$total_routines = count($routines);

// Rotinas concluídas: status = 'concluida' OU progresso >= 100%
$rotinas_concluidas = count(array_filter($routines, function($r) { 
    return $r['status'] === 'concluida' || (float)$r['progresso'] >= 100.0; 
}));

// Rotinas em andamento: status = 'ativa' E progresso < 100%
$rotinas_ativas = count(array_filter($routines, function($r) { 
    return $r['status'] === 'ativa' && (float)$r['progresso'] < 100.0; 
}));

// Rotinas pausadas: status = 'pausada' E progresso < 100%
$rotinas_pausadas = count(array_filter($routines, function($r) { 
    return $r['status'] === 'pausada' && (float)$r['progresso'] < 100.0; 
}));

// Progresso médio das rotinas
$progresso_medio = 0;
if ($total_routines > 0) {
    $soma_progresso = array_sum(array_column($routines, 'progresso'));
    $progresso_medio = $soma_progresso / $total_routines;
}

// Filtro por rotina
$filtro_rotina = $_GET['rotina'] ?? 'todas';
$rotinas_filtradas = $routines;
$rotina_selecionada = null;

if ($filtro_rotina !== 'todas') {
    $rotinas_filtradas = array_filter($routines, function($r) use ($filtro_rotina) {
        return $r['id'] == $filtro_rotina;
    });
    // Buscar a rotina selecionada para mostrar no gráfico
    foreach ($routines as $r) {
        if ($r['id'] == $filtro_rotina) {
            $rotina_selecionada = $r;
            break;
        }
    }
}

// Dados para o gráfico
$chart_labels = [];
$chart_data = [];
$chart_colors = [];

if ($rotina_selecionada) {
    // Se uma rotina específica foi selecionada, mostrar progresso dessa rotina
    $progresso_concluido = (float)$rotina_selecionada['progresso'];
    $progresso_pendente = 100.0 - $progresso_concluido;
    
    $chart_labels = ['Concluído', 'Pendente'];
    $chart_data = [$progresso_concluido, $progresso_pendente];
    $chart_colors = ['#10b981', '#e5e7eb']; // Verde para concluído, cinza para pendente
} else {
    // Se mostrar todas as rotinas, mostrar distribuição geral
    $chart_labels = ['Concluídas', 'Em Andamento', 'Pausadas'];
    $chart_data = [$rotinas_concluidas, $rotinas_ativas, $rotinas_pausadas];
    $chart_colors = ['#10b981', '#3b82f6', '#f59e0b'];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIStudy - Progresso</title>
    
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php $active = 'progresso'; render_navbar($active); ?>

    <div class="container mt-5 mb-5">
        <!-- Header -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="text-gradient mb-3" style="font-size: 2.5rem; font-weight: 800; letter-spacing: -0.02em;">
                            Meu Progresso
                        </h1>
                        <p class="text-muted" style="font-size: 1.1rem;">Acompanhe seu desempenho e evolução nos estudos</p>
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
        <div class="row mb-5">
            <div class="col-md-3 mb-3">
                <div class="stat-card stat-card-primary">
                    <div class="stat-icon-wrapper">
                        <i class="fas fa-list-ul stat-icon"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number text-primary"><?php echo $total_routines; ?></div>
                        <div class="stat-label">Total de Rotinas</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card stat-card-success">
                    <div class="stat-icon-wrapper">
                        <i class="fas fa-check-circle stat-icon"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number text-success"><?php echo $rotinas_concluidas; ?></div>
                        <div class="stat-label">Rotinas Concluídas</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row mb-4">
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>
                            <?php if ($rotina_selecionada): ?>
                                Progresso: <?php echo htmlspecialchars($rotina_selecionada['titulo']); ?>
                            <?php else: ?>
                                Progresso das Rotinas
                            <?php endif; ?>
                        </h6>
                    </div>
                    <div class="card-body" style="padding: 2rem;">
                        <canvas id="progressoChart" width="400" height="200"></canvas>
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
                                                    <?php 
                                                    // Se progresso >= 100%, mostrar como concluída
                                                    $is_concluida = (float)$rotina['progresso'] >= 100.0;
                                                    $status_display = $is_concluida ? 'concluida' : $rotina['status'];
                                                    $status_class = $is_concluida ? 'secondary' : ($rotina['status'] === 'ativa' ? 'success' : ($rotina['status'] === 'pausada' ? 'warning' : 'secondary'));
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_class; ?>">
                                                        <?php echo ucfirst($status_display); ?>
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
    <script src="assets/js/dark-mode.js"></script>
    <script>
        // Gráfico de Progresso das Rotinas
        const progressoCtx = document.getElementById('progressoChart').getContext('2d');
        
        // Dados do gráfico vindos do PHP
        const chartLabels = <?php echo json_encode($chart_labels); ?>;
        const chartData = <?php echo json_encode($chart_data); ?>;
        const chartColors = <?php echo json_encode($chart_colors); ?>;
        
        let progressChart = new Chart(progressoCtx, {
            type: 'doughnut',
            data: {
                labels: chartLabels,
                datasets: [{
                    data: chartData,
                    backgroundColor: chartColors,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.parsed || 0;
                                
                                // Se for rotina específica, mostrar porcentagem
                                <?php if ($rotina_selecionada): ?>
                                    return label + ': ' + value.toFixed(1) + '%';
                                <?php else: ?>
                                    return label + ': ' + value + ' rotina(s)';
                                <?php endif; ?>
                            }
                        }
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
