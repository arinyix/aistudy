<?php
require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'classes/Routine.php';
require_once 'classes/Calendar.php';
require_once 'includes/plan-check.php';
require_once 'includes/navbar.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$routine = new Routine($db);
$calendar = new Calendar($db);

$user = getCurrentUser();

// Buscar plano ativo do usuário
$planoAtivo = getUserActivePlan($user['id']);

// Buscar rotinas do usuário (todos os tipos)
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

// Próximos dias de estudo - apenas da semana atual
$nextStudyDates = [];
$dayOfWeek = date('w', strtotime($today)); // 0 = domingo, 6 = sábado
$startOfWeek = date('Y-m-d', strtotime($today . ' -' . $dayOfWeek . ' days')); // Domingo da semana
$endOfWeek = date('Y-m-d', strtotime($startOfWeek . ' +6 days')); // Sábado da semana

foreach ($studySchedule as $date => $schedules) {
    // Mostrar apenas dias da semana atual (domingo a sábado)
    if ($date >= $today && $date <= $endOfWeek) {
        $nextStudyDates[] = $date;
    }
}
sort($nextStudyDates);
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
    <?php $active = 'dashboard'; render_navbar($active); ?>
    
    <div class="container mt-5 mb-5">
        <!-- Header -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h1 class="text-gradient mb-3" style="font-size: 2.5rem; font-weight: 800; letter-spacing: -0.02em;">
                            Bem-vindo, <?php echo htmlspecialchars($user['nome']); ?>! 👋
                        </h1>
                    </div>
                    <?php if ($planoAtivo): ?>
                        <div class="text-end">
                            <span class="badge bg-success fs-6 px-3 py-2">
                                <i class="fas fa-crown me-2"></i>
                                <?php echo htmlspecialchars($planoAtivo['nome']); ?>
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="text-end">
                            <a href="planos.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-star me-2"></i>Ver Planos
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <p class="text-muted" style="font-size: 1.1rem;">Aqui está seu resumo de estudos para hoje</p>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="row mb-5">
            <div class="col-md-3 mb-3">
                <div class="stat-card stat-card-primary">
                    <div class="stat-icon-wrapper">
                        <i class="fas fa-list-ul stat-icon"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number text-primary"><?php echo $totalRoutines; ?></div>
                        <div class="stat-label">Total de Rotinas</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card stat-card-success">
                    <div class="stat-icon-wrapper">
                        <i class="fas fa-play-circle stat-icon"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number text-success"><?php echo $activeRoutines; ?></div>
                        <div class="stat-label">Rotinas Ativas</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card stat-card-warning">
                    <div class="stat-icon-wrapper">
                        <i class="fas fa-check-circle stat-icon"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number text-warning"><?php echo $completedToday; ?></div>
                        <div class="stat-label">Concluídas Hoje</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card stat-card-info">
                    <div class="stat-icon-wrapper">
                        <i class="fas fa-tasks stat-icon"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number text-info"><?php echo $totalToday; ?></div>
                        <div class="stat-label">Tarefas Hoje</div>
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
                            <h6 class="text-purple">
                    <i class="fas fa-calendar-week me-2"></i>Semana Atual
                </h6>
                <div class="row">
                    <?php foreach ($nextStudyDates as $date): ?>
                        <?php $tasksGrouped = $calendar->getTasksForSpecificDate($user['id'], $date); ?>
                        <?php if (!empty($tasksGrouped)): ?>
                            <?php 
                            // Pegar rotinas únicas das tarefas
                            $rotinasUnicas = [];
                            foreach ($tasksGrouped as $task) {
                                if (!isset($rotinasUnicas[$task['routine_id']])) {
                                    $rotinasUnicas[$task['routine_id']] = [
                                        'id' => $task['routine_id'],
                                        'titulo' => $task['rotina_titulo'],
                                        'tasks' => []
                                    ];
                                }
                                $rotinasUnicas[$task['routine_id']]['tasks'][] = $task;
                            }
                            
                            // Se houver apenas uma rotina, usar ela. Se houver múltiplas, usar a primeira
                            $rotinasArray = array_values($rotinasUnicas);
                            $primaryRoutine = $rotinasArray[0] ?? null;
                            $routineId = $primaryRoutine['id'] ?? null;
                            $hasMultipleRoutines = count($rotinasUnicas) > 1;
                            ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <a href="rotina-detalhada.php?id=<?php echo $routineId; ?>" class="text-decoration-none study-day-card-link">
                                    <div class="card study-day-card">
                                        <div class="card-header study-day-header">
                                            <h6 class="mb-0">
                                                <i class="fas fa-calendar-day me-2"></i>
                                                <?php echo date('d/m/Y', strtotime($date)); ?>
                                                <span class="badge study-day-badge ms-2"><?php echo count($tasksGrouped); ?> assunto<?php echo count($tasksGrouped) > 1 ? 's' : ''; ?></span>
                                                <?php if ($hasMultipleRoutines): ?>
                                                    <span class="badge ms-2" style="background: rgba(255, 255, 255, 0.25) !important; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3);">
                                                        <i class="fas fa-layer-group me-1"></i><?php echo count($rotinasUnicas); ?> rotina<?php echo count($rotinasUnicas) > 1 ? 's' : ''; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <?php foreach (array_slice($tasksGrouped, 0, 2) as $task): ?>
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-circle study-day-bullet me-2"></i>
                                                    <small class="study-day-text"><?php echo htmlspecialchars($task['titulo']); ?></small>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if (count($tasksGrouped) > 2): ?>
                                                <small class="study-day-text">+<?php echo count($tasksGrouped) - 2; ?> mais...</small>
                                            <?php endif; ?>
                                            <div class="mt-3 pt-2 border-top" style="border-color: var(--border-default) !important;">
                                                <small class="text-muted d-flex align-items-center">
                                                    <i class="fas fa-arrow-right me-1"></i>
                                                    <span>Ver rotina: <?php echo htmlspecialchars($primaryRoutine['titulo'] ?? 'Rotina'); ?></span>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </a>
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
