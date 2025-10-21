<?php
require_once 'config/database.php';
require_once 'classes/Routine.php';
require_once 'classes/Task.php';
require_once 'includes/session.php';

requireLogin();

$user = getCurrentUser();
$routine_id = $_GET['id'] ?? null;

if (!$routine_id || !is_numeric($routine_id)) {
    header('Location: rotinas.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$routine = new Routine($db);
$task = new Task($db);

// Buscar rotina
$rotina = $routine->getRoutine($routine_id, $user['id']);

if (!$rotina) {
    header('Location: rotinas.php');
    exit();
}

// Buscar tarefas da rotina
$tasks = $task->getRoutineTasks($routine_id);

// Agrupar tarefas por dia
$tasks_por_dia = [];
foreach ($tasks as $task_item) {
    $dia = $task_item['dia_estudo'];
    if (!isset($tasks_por_dia[$dia])) {
        $tasks_por_dia[$dia] = [];
    }
    $tasks_por_dia[$dia][] = $task_item;
}

$message = '';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIStudy - <?php echo htmlspecialchars($rotina['titulo']); ?></title>
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
                        <h1 class="text-gradient"><?php echo htmlspecialchars($rotina['titulo']); ?></h1>
                        <p class="text-muted">
                            <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($rotina['tema']); ?> • 
                            <i class="fas fa-signal me-1"></i><?php echo ucfirst($rotina['nivel']); ?> • 
                            <i class="fas fa-clock me-1"></i><?php echo $rotina['tempo_diario']; ?> min/dia
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="rotinas.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Voltar
                        </a>
                        <button class="btn btn-primary" onclick="gerarQuiz()">
                            <i class="fas fa-question-circle me-2"></i>Fazer Quiz
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progresso -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Progresso da Rotina</h6>
                            <span class="badge bg-primary"><?php echo number_format($rotina['progresso'], 1); ?>%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?php echo $rotina['progresso']; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cronograma -->
        <div class="row">
            <?php if (empty($tasks_por_dia)): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-calendar-times text-muted fa-3x mb-3"></i>
                            <h4>Nenhuma tarefa encontrada</h4>
                            <p class="text-muted">Esta rotina ainda não possui tarefas cadastradas.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($tasks_por_dia as $dia => $tarefas_dia): ?>
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-calendar-day me-2"></i>Dia <?php echo $dia; ?>
                                        <span class="badge bg-primary ms-2">
                                            <?php 
                                            $concluidas = count(array_filter($tarefas_dia, function($t) { return $t['status'] === 'concluida'; }));
                                            echo $concluidas . '/' . count($tarefas_dia);
                                            ?>
                                        </span>
                                    </h5>
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="gerarQuizDia(<?php echo $dia; ?>, '<?php echo htmlspecialchars($tarefas_dia[0]['titulo']); ?>')">
                                            <i class="fas fa-question-circle me-1"></i>Quiz do Dia
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php foreach ($tarefas_dia as $tarefa): ?>
                                    <div class="task-card card mb-3 <?php echo $tarefa['status'] === 'concluida' ? 'completed' : ''; ?>">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <div class="d-flex align-items-start">
                                                        <div class="flex-shrink-0 me-3">
                                                            <?php if ($tarefa['status'] === 'concluida'): ?>
                                                                <i class="fas fa-check-circle text-success fa-lg"></i>
                                                            <?php else: ?>
                                                                <i class="fas fa-circle text-muted fa-lg"></i>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <h6 class="task-title mb-1"><?php echo htmlspecialchars($tarefa['titulo']); ?></h6>
                                                            <p class="text-muted mb-2"><?php echo htmlspecialchars($tarefa['descricao']); ?></p>
                                                            
                                                            <?php if ($tarefa['material_estudo']): ?>
                                                                <?php $material = json_decode($tarefa['material_estudo'], true); ?>
                                                                <div class="mb-2">
                                                                    <button class="btn btn-sm btn-outline-info me-2" 
                                                                            onclick="showMaterials(<?php echo htmlspecialchars(json_encode($material)); ?>)">
                                                                        <i class="fas fa-book-open me-1"></i>Ver Materiais
                                                                    </button>
                                                                    <button class="btn btn-sm btn-outline-warning" 
                                                                            onclick="gerarQuizAssunto('<?php echo htmlspecialchars($tarefa['titulo']); ?>', <?php echo $tarefa['id']; ?>)">
                                                                        <i class="fas fa-question-circle me-1"></i>Quiz do Assunto
                                                                    </button>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 text-end">
                                                    <?php if ($tarefa['status'] === 'concluida'): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check me-1"></i>Concluída
                                                        </span>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-outline-primary" 
                                                                onclick="toggleTask(<?php echo $tarefa['id']; ?>)">
                                                            <i class="fas fa-check me-1"></i>Marcar como Concluída
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para Materiais -->
    <div class="modal fade" id="materialsModal" tabindex="-1" aria-labelledby="materialsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="materialsModalLabel">
                        <i class="fas fa-book-open me-2"></i>Materiais de Estudo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="materialsContent">
                    <!-- Conteúdo será inserido via JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
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
        
        function gerarQuiz() {
            if (confirm('Deseja gerar um quiz sobre esta rotina? Isso irá criar 5 perguntas baseadas no conteúdo estudado.')) {
                window.location.href = 'gerar-quiz.php?routine_id=<?php echo $routine_id; ?>';
            }
        }
        
        function gerarQuizDia(dia, assunto) {
            if (confirm('Deseja gerar um quiz específico para o Dia ' + dia + ' sobre "' + assunto + '"?')) {
                window.location.href = 'gerar-quiz-dia.php?routine_id=<?php echo $routine_id; ?>&dia=' + dia + '&assunto=' + encodeURIComponent(assunto);
            }
        }
        
        function gerarQuizAssunto(assunto, taskId) {
            if (confirm('Deseja gerar um quiz específico sobre "' + assunto + '"?')) {
                window.location.href = 'gerar-quiz-assunto.php?routine_id=<?php echo $routine_id; ?>&task_id=' + taskId + '&assunto=' + encodeURIComponent(assunto);
            }
        }
        
        function showMaterials(material) {
            let content = '';
            
            // Vídeos
            if (material.videos && material.videos.length > 0) {
                content += '<div class="mb-4">';
                content += '<h6><i class="fas fa-video text-danger me-2"></i>Vídeos Educacionais</h6>';
                content += '<div class="row">';
                material.videos.forEach((video, index) => {
                    // Se o vídeo é um objeto com propriedades
                    if (typeof video === 'object' && video.id) {
                        const thumbnail = `https://img.youtube.com/vi/${video.id}/mqdefault.jpg`;
                        const videoUrl = `https://www.youtube.com/watch?v=${video.id}`;
                        
                        content += '<div class="col-md-6 mb-3">';
                        content += '<div class="card">';
                        content += '<div class="card-body p-2">';
                        content += '<img src="' + thumbnail + '" class="img-fluid rounded mb-2" style="width: 100%; height: 120px; object-fit: cover;">';
                        content += '<h6 class="card-title small">' + (video.title || 'Vídeo Educacional') + '</h6>';
                        if (video.channel) {
                            content += '<p class="card-text small text-muted">' + video.channel + '</p>';
                        }
                        content += '<a href="' + videoUrl + '" target="_blank" class="btn btn-danger btn-sm w-100">';
                        content += '<i class="fab fa-youtube me-2"></i>Assistir Vídeo ' + (index + 1);
                        content += '</a>';
                        content += '</div>';
                        content += '</div>';
                        content += '</div>';
                    } else {
                        // Se é uma string URL
                        const videoId = video.includes('watch?v=') ? video.split('watch?v=')[1].split('&')[0] : '';
                        const thumbnail = videoId ? `https://img.youtube.com/vi/${videoId}/mqdefault.jpg` : '';
                        
                        content += '<div class="col-md-6 mb-3">';
                        content += '<div class="card">';
                        content += '<div class="card-body p-2">';
                        if (thumbnail) {
                            content += '<img src="' + thumbnail + '" class="img-fluid rounded mb-2" style="width: 100%; height: 120px; object-fit: cover;">';
                        }
                        content += '<a href="' + video + '" target="_blank" class="btn btn-danger btn-sm w-100">';
                        content += '<i class="fab fa-youtube me-2"></i>Assistir Vídeo ' + (index + 1);
                        content += '</a>';
                        content += '</div>';
                        content += '</div>';
                        content += '</div>';
                    }
                });
                content += '</div>';
                content += '</div>';
            }
            
            // Textos
            if (material.textos && material.textos.length > 0) {
                content += '<div class="mb-4">';
                content += '<h6><i class="fas fa-book text-primary me-2"></i>Leituras</h6>';
                content += '<ul class="list-group">';
                material.textos.forEach(texto => {
                    content += '<li class="list-group-item d-flex align-items-center">';
                    content += '<i class="fas fa-file-text text-primary me-2"></i>';
                    content += '<span>' + texto + '</span>';
                    content += '</li>';
                });
                content += '</ul>';
                content += '</div>';
            }
            
            // Exercícios
            if (material.exercicios && material.exercicios.length > 0) {
                content += '<div class="mb-4">';
                content += '<h6><i class="fas fa-tasks text-success me-2"></i>Exercícios</h6>';
                content += '<ul class="list-group">';
                material.exercicios.forEach(exercicio => {
                    content += '<li class="list-group-item d-flex align-items-center">';
                    content += '<i class="fas fa-pencil-alt text-success me-2"></i>';
                    content += '<span>' + exercicio + '</span>';
                    content += '</li>';
                });
                content += '</ul>';
                content += '</div>';
            }
            
            if (content === '') {
                content = '<div class="text-center py-4">';
                content += '<i class="fas fa-book text-muted fa-3x mb-3"></i>';
                content += '<h5>Nenhum material disponível</h5>';
                content += '<p class="text-muted">Esta tarefa não possui materiais de estudo.</p>';
                content += '</div>';
            }
            
            document.getElementById('materialsContent').innerHTML = content;
            new bootstrap.Modal(document.getElementById('materialsModal')).show();
        }
    </script>
</body>
</html>
