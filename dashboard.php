<?php
require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'classes/Routine.php';
require_once 'classes/Calendar.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$routine = new Routine($db);
$calendar = new Calendar($db);

$user = getCurrentUser();

// Buscar rotinas do usuário
$routines = $routine->getUserRoutines($user['id']);

// Buscar tarefas do dia atual
$today = date('Y-m-d');
$todayTasks = $calendar->getTasksForDate($user['id'], $today);

// Buscar cronograma de estudos
$studySchedule = $calendar->getStudySchedule($user['id']);

// Buscar próximas tarefas de cada rotina (uma por rotina)
$todayTasksGrouped = $calendar->getTasksForSpecificDate($user['id'], $today);

// Calcular estatísticas
$totalRoutines = count($routines);
$activeRoutines = count(array_filter($routines, function($r) { return $r['status'] === 'ativa'; }));
$completedToday = count(array_filter($todayTasksGrouped, function($t) { return $t['status'] === 'concluida'; }));
$totalToday = count($todayTasksGrouped);

// Próximos dias de estudo
$nextStudyDates = [];
foreach ($studySchedule as $date => $schedules) {
    if ($date >= $today) {
        $nextStudyDates[] = $date;
    }
}
sort($nextStudyDates);
$nextStudyDates = array_slice($nextStudyDates, 0, 7); // Próximos 7 dias
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIStudy - Dashboard</title>
    
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
                        <a class="nav-link" href="progresso.php">
                            <i class="fas fa-chart-line me-1"></i>Progresso
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item me-3">
                        <button class="theme-toggle" onclick="toggleTheme()" title="Alternar modo escuro/claro">
                            <i class="fas fa-moon"></i>
                        </button>
                    </li>
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
                <h1 class="text-gradient">Bem-vindo, <?php echo htmlspecialchars($user['nome']); ?>!</h1>
                <p class="text-muted">Aqui está seu resumo de estudos para hoje</p>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stat-number text-primary"><?php echo $totalRoutines; ?></div>
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
                            <div class="stat-number text-success"><?php echo $activeRoutines; ?></div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="stat-label">Rotinas Ativas</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stat-number text-warning"><?php echo $completedToday; ?></div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="stat-label">Concluídas Hoje</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stat-number text-info"><?php echo $totalToday; ?></div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="stat-label">Tarefas Hoje</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>Ações Rápidas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <a href="criar-rotina.php" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-2"></i>Criar Nova Rotina
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="rotinas.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-list me-2"></i>Ver Todas as Rotinas
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="progresso.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-chart-line me-2"></i>Ver Progresso
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendário de Estudos -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar me-2"></i>Cronograma de Estudos
                            <span class="badge bg-primary ms-2"><?php echo $completedToday; ?>/<?php echo $totalToday; ?> hoje</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($studySchedule)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times text-muted fa-3x mb-3"></i>
                                <h5>Nenhum cronograma encontrado</h5>
                                <p class="text-muted">Crie uma rotina para ver seu cronograma de estudos.</p>
                                <a href="criar-rotina.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Criar Rotina
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Tarefas de Hoje (Agrupadas por Assunto) -->
                            <?php if (!empty($todayTasksGrouped)): ?>
                                <div class="mb-4">
                                    <h6 class="text-primary">
                                        <i class="fas fa-calendar-day me-2"></i>Hoje - <?php echo date('d/m/Y'); ?>
                                        <small class="text-muted">(<?php echo count($todayTasksGrouped); ?> assunto<?php echo count($todayTasksGrouped) > 1 ? 's' : ''; ?>)</small>
                                    </h6>
                                    <div class="row">
                                        <?php foreach ($todayTasksGrouped as $task): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="task-card card <?php echo $task['status'] === 'concluida' ? 'completed' : ''; ?>">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div class="flex-grow-1">
                                                                <h6 class="task-title mb-1"><?php echo htmlspecialchars($task['titulo']); ?></h6>
                                                                <p class="text-muted small mb-2"><?php echo htmlspecialchars($task['descricao']); ?></p>
                                                                <small class="text-muted">
                                                                    <i class="fas fa-book me-1"></i><?php echo htmlspecialchars($task['rotina_titulo']); ?>
                                                                    <br>
                                                                    <i class="fas fa-clock me-1"></i><?php echo date('H:i', strtotime($task['horario_disponivel'])); ?>
                                                                </small>
                                                            </div>
                                                            <div class="flex-shrink-0">
                                                                <?php if ($task['status'] === 'concluida'): ?>
                                                                    <span class="badge bg-success">
                                                                        <i class="fas fa-check me-1"></i>Concluída
                                                                    </span>
                                                                <?php else: ?>
                                                                    <button class="btn btn-sm btn-outline-primary" onclick="toggleTask(<?php echo $task['id']; ?>)">
                                                                        <i class="fas fa-check me-1"></i>Marcar
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="mt-2">
                                                            <a href="rotina-detalhada.php?id=<?php echo $task['routine_id']; ?>" class="btn btn-sm btn-outline-info">
                                                                <i class="fas fa-eye me-1"></i>Ver Detalhes
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
        <!-- Próximos Dias -->
        <?php if (!empty($nextStudyDates)): ?>
            <div class="mb-4">
                <h6 class="text-success">
                    <i class="fas fa-calendar-week me-2"></i>Próximos Dias de Estudo
                </h6>
                <div class="row">
                    <?php foreach ($nextStudyDates as $date): ?>
                        <?php $tasksGrouped = $calendar->getTasksForSpecificDate($user['id'], $date); ?>
                        <?php if (!empty($tasksGrouped)): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card border-success">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">
                                            <i class="fas fa-calendar-day me-2"></i>
                                            <?php echo date('d/m/Y', strtotime($date)); ?>
                                            <span class="badge bg-success ms-2"><?php echo count($tasksGrouped); ?> assunto<?php echo count($tasksGrouped) > 1 ? 's' : ''; ?></span>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <?php foreach (array_slice($tasksGrouped, 0, 2) as $task): ?>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-circle text-success me-2" style="font-size: 8px;"></i>
                                                <small class="text-muted"><?php echo htmlspecialchars($task['titulo']); ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if (count($tasksGrouped) > 2): ?>
                                            <small class="text-muted">+<?php echo count($tasksGrouped) - 2; ?> mais...</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dark-mode.js"></script>
    <script>
        function toggleTask(taskId) {
            fetch('api/toggle-task.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ task_id: taskId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erro ao atualizar tarefa: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao atualizar tarefa');
            });
        }
    </script>
</body>
</html>
