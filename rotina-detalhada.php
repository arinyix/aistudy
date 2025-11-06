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
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js" onload="console.log('marked.js carregado com sucesso!')" onerror="console.error('Erro ao carregar marked.js!')"></script>
    <script>
        // Garantir que marked.js esteja disponível globalmente
        window.markedReady = false;
        if (typeof marked !== 'undefined') {
            window.markedReady = true;
            console.log('marked.js já disponível no carregamento da página');
        } else {
            // Aguardar marked.js carregar
            window.addEventListener('load', function() {
                if (typeof marked !== 'undefined') {
                    window.markedReady = true;
                    console.log('marked.js disponível após load');
                } else {
                    console.error('marked.js NÃO está disponível após load!');
                }
            });
        }
    </script>
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

    <div class="container mt-5 mb-5">
        <!-- Header -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="text-gradient mb-3" style="font-size: 2.5rem; font-weight: 800; letter-spacing: -0.02em;">
                            <?php echo htmlspecialchars($rotina['titulo']); ?>
                        </h1>
                        <p class="text-muted" style="font-size: 1.1rem;">
                            <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($rotina['tema']); ?> • 
                            <i class="fas fa-signal me-1"></i><?php echo ucfirst($rotina['nivel']); ?> • 
                            <i class="fas fa-clock me-1"></i><?php echo $rotina['tempo_diario']; ?> min/dia
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="rotinas.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Voltar
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progresso -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 fw-bold" style="font-size: 1.1rem;">Progresso da Rotina</h6>
                            <span class="badge bg-primary" style="font-size: 1rem; padding: 0.5rem 1rem;">
                                <?php echo number_format($rotina['progresso'], 1); ?>%
                            </span>
                        </div>
                        <div class="progress" style="height: 16px;">
                            <div class="progress-bar" style="width: <?php echo $rotina['progresso']; ?>%; background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));"></div>
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
                        <div class="card day-card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 fw-bold">
                                        <i class="fas fa-calendar-day me-2"></i>Dia <?php echo $dia; ?>
                                        <span class="badge ms-2" style="background: rgba(255, 255, 255, 0.2) !important; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3);">
                                            <?php 
                                            $concluidas = count(array_filter($tarefas_dia, function($t) { return $t['status'] === 'concluida'; }));
                                            echo $concluidas . '/' . count($tarefas_dia);
                                            ?>
                                        </span>
                                    </h5>
                                    <div>
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
                                                                    <button class="btn btn-sm btn-outline-primary" 
                                                                            onclick="gerarResumoAuxiliar(<?php echo $tarefa['id']; ?>, '<?php echo htmlspecialchars($tarefa['titulo']); ?>')">
                                                                        <i class="fas fa-file-pdf me-1"></i>Resumo Auxiliar
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

    <!-- Modal para Resumo Auxiliar (Fullscreen) -->
    <div class="modal fade" id="resumoModal" tabindex="-1" aria-labelledby="resumoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content resumo-modal-content">
                <div class="modal-header resumo-modal-header">
                    <h5 class="modal-title" id="resumoModalLabel">
                        <i class="fas fa-file-pdf me-2"></i>Resumo Auxiliar
                    </h5>
                    <button type="button" class="btn-close resumo-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="resumoContent" style="padding: 2rem;">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Gerando resumo...</span>
                        </div>
                        <p class="mt-3 fs-5">Gerando resumo auxiliar... Isso pode levar alguns segundos.</p>
                    </div>
                </div>
                <div class="modal-footer resumo-modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Fechar
                    </button>
                    <button type="button" class="btn btn-success" id="downloadPDFBtn" style="display:none;" onclick="window.print()">
                        <i class="fas fa-print me-1"></i>Imprimir/Salvar PDF
                    </button>
                    <button type="button" class="btn btn-primary" id="downloadHTMLBtn" style="display:none;">
                        <i class="fas fa-download me-1"></i>Download HTML
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Verificar se marked.js está carregado
        window.addEventListener('DOMContentLoaded', function() {
            if (typeof marked === 'undefined') {
                console.error('marked.js não foi carregado! Carregando...');
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/marked/marked.min.js';
                script.onload = function() {
                    console.log('marked.js carregado com sucesso!');
                };
                script.onerror = function() {
                    console.error('Erro ao carregar marked.js!');
                };
                document.head.appendChild(script);
            } else {
                console.log('marked.js já está disponível');
            }
        });
        
        function toggleTask(taskId) {
            console.log('toggleTask called with taskId:', taskId);
            
            fetch('api/toggle-task.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ task_id: taskId })
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.text();
            })
            .then(text => {
                console.log('Response text:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erro ao atualizar tarefa: ' + data.message);
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    console.error('Raw response:', text);
                    alert('Erro ao processar resposta do servidor. Veja o console para detalhes.');
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                alert('Erro ao atualizar tarefa: ' + error.message);
            });
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
        
        function gerarResumoAuxiliar(taskId, topico) {
            console.log('Gerando resumo auxiliar para task:', taskId);
            
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('resumoModal'));
            modal.show();
            
            // Resetar conteúdo com feedback visual melhorado
            document.getElementById('resumoContent').innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Gerando resumo...</span>
                    </div>
                    <h5 class="mt-4 mb-2">Gerando Resumo Auxiliar</h5>
                    <p class="text-muted mb-3">Isso pode levar 30-90 segundos...</p>
                    <div class="progress" style="max-width: 400px; margin: 0 auto;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                    </div>
                    <p class="mt-3 small text-muted" id="loadingStatus">Aguardando resposta da API...</p>
                </div>
            `;
            document.getElementById('downloadPDFBtn').style.display = 'none';
            
            // Atualizar status de loading
            let statusMessages = [
                'Aguardando resposta da API...',
                'Processando conteúdo...',
                'Gerando resumo detalhado...',
                'Quase finalizado...'
            ];
            let statusIndex = 0;
            const statusInterval = setInterval(() => {
                const statusEl = document.getElementById('loadingStatus');
                if (statusEl) {
                    statusEl.textContent = statusMessages[statusIndex % statusMessages.length];
                    statusIndex++;
                }
            }, 2000);
            
            // Armazenar interval para limpar depois
            window.resumoLoadingInterval = statusInterval;
            
            // Fazer requisição
            // Criar AbortController para timeout customizado (3 minutos)
            const controller = new AbortController();
            const timeoutId = setTimeout(() => {
                controller.abort();
                // Limpar interval de status
                if (window.resumoLoadingInterval) {
                    clearInterval(window.resumoLoadingInterval);
                }
                // Mostrar mensagem de timeout
                document.getElementById('resumoContent').innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-clock me-2"></i>
                        <strong>A requisição está demorando mais que o esperado.</strong>
                        <br><br>
                        <p>A API pode estar sobrecarregada ou sua conexão está lenta.</p>
                        <button class="btn btn-sm btn-primary" onclick="gerarResumoAuxiliar(${taskId}, '${topico}')">
                            <i class="fas fa-redo me-1"></i>Tentar Novamente
                        </button>
                    </div>
                `;
            }, 180000); // 3 minutos
           
           fetch('gerar-resumo.php', {
               method: 'POST',
               headers: {
                   'Content-Type': 'application/json',
               },
               body: JSON.stringify({ task_id: taskId }),
               signal: controller.signal
           })
            .then(response => {
               clearTimeout(timeoutId);
               // Limpar interval de status
               if (window.resumoLoadingInterval) {
                   clearInterval(window.resumoLoadingInterval);
               }
               
               // Atualizar status
               const statusEl = document.getElementById('loadingStatus');
               if (statusEl) {
                   statusEl.textContent = 'Recebendo resposta...';
               }
               
               return response.text();
           })
            .then(text => {
                console.log('Response text:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);
                    
                    if (data.success && data.content) {
                        console.log('Conteúdo recebido com sucesso. Tamanho:', data.content.length, 'caracteres');
                        
                        // Atualizar status
                        const statusEl = document.getElementById('loadingStatus');
                        if (statusEl) {
                            statusEl.textContent = 'Abrindo visualizador...';
                        }
                        
                        // Sempre usar POST para enviar o conteúdo (mais seguro e sem limite de tamanho)
                        // Passar referrer para poder voltar depois
                        const currentUrl = window.location.href;
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'resumo-pdf.php?task_id=' + taskId + '&referrer=' + encodeURIComponent(currentUrl);
                        form.target = '_blank';
                        form.style.display = 'none';
                        
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'content';
                        input.value = data.content;
                        
                        form.appendChild(input);
                        document.body.appendChild(form);
                        
                        // Submeter formulário
                        form.submit();
                        
                        // Remover formulário após um delay
                        setTimeout(() => {
                            if (form.parentNode) {
                                document.body.removeChild(form);
                            }
                        }, 1000);
                        
                        // Fechar modal
                        setTimeout(() => {
                            modal.hide();
                        }, 500);
                    } else {
                        document.getElementById('resumoContent').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Erro ao gerar resumo: ${data.message || 'Erro desconhecido'}
                            </div>
                        `;
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    document.getElementById('resumoContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Erro ao processar resposta. Verifique o console para detalhes.
                        </div>
                    `;
                }
            })
                       .catch(error => {
               clearTimeout(timeoutId);
               // Limpar interval de status
               if (window.resumoLoadingInterval) {
                   clearInterval(window.resumoLoadingInterval);
               }
               
               console.error('Erro na requisição:', error);
               
               let errorMessage = 'Erro ao gerar resumo. ';
               if (error.name === 'AbortError') {
                   errorMessage += 'A requisição demorou muito tempo (mais de 6 minutos). Isso pode acontecer se a API estiver lenta. Tente novamente.';
               } else if (error.message) {
                   errorMessage += error.message;
               } else {
                   errorMessage += 'Erro desconhecido. Verifique sua conexão e tente novamente.';
               }
               
               document.getElementById('resumoContent').innerHTML = `
                   <div class="alert alert-danger">
                       <i class="fas fa-exclamation-triangle me-2"></i>
                       <strong>Erro:</strong> ${errorMessage}
                       <br><br>
                       <button class="btn btn-sm btn-primary" onclick="gerarResumoAuxiliar(${taskId}, '${topico}')">
                           <i class="fas fa-redo me-1"></i>Tentar Novamente
                       </button>
                   </div>
               `;
           });
        }
        
        function renderMarkdown(markdown) {
            console.log('renderMarkdown chamada, markdown length:', markdown ? markdown.length : 0);
            console.log('marked disponível?', typeof marked !== 'undefined');
            
            if (!markdown || typeof markdown !== 'string') {
                console.error('Markdown vazio ou inválido!');
                return;
            }
            
            // Usar marked.js para converter Markdown para HTML se disponível
            let html;
            let useMarked = false;
            
            // Verificar se marked está disponível E se tem o método parse
            if (typeof marked !== 'undefined' && marked) {
                try {
                    // Tentar diferentes formas de chamar marked
                    if (typeof marked.parse === 'function') {
                        // marked v4+
                        marked.setOptions({
                            breaks: true,
                            gfm: true,
                            headerIds: false,
                            mangle: false
                        });
                        html = marked.parse(markdown);
                        useMarked = true;
                        console.log('Markdown renderizado com marked.parse(), HTML length:', html ? html.length : 0);
                    } else if (typeof marked === 'function') {
                        // marked v3 ou anterior
                        marked.setOptions({
                            breaks: true,
                            gfm: true,
                            headerIds: false,
                            mangle: false
                        });
                        html = marked(markdown);
                        useMarked = true;
                        console.log('Markdown renderizado com marked(), HTML length:', html ? html.length : 0);
                    }
                } catch (e) {
                    console.error('Erro ao renderizar com marked.js:', e);
                    useMarked = false;
                }
            }
            
            // Se marked.js não funcionou, usar fallback
            if (!useMarked || !html) {
                console.log('Usando fallback para renderizar markdown');
                html = convertMarkdownFallback(markdown);
            }
            
            // Envolver em container de documento
            html = '<div class="document-container">' + html + '</div>';
            const contentElement = document.getElementById('resumoContent');
            if (contentElement) {
                // Limpar conteúdo anterior
                contentElement.innerHTML = '';
                
                // Inserir HTML renderizado
                contentElement.innerHTML = html;
                console.log('Conteúdo renderizado no DOM');
                console.log('HTML gerado (primeiros 500 chars):', html.substring(0, 500));
                
                // Forçar reflow para garantir que o CSS seja aplicado
                contentElement.offsetHeight;
                
                // Verificar se há elementos HTML renderizados
                const h1Elements = contentElement.querySelectorAll('h1');
                const h2Elements = contentElement.querySelectorAll('h2');
                const pElements = contentElement.querySelectorAll('p');
                const ulElements = contentElement.querySelectorAll('ul');
                const olElements = contentElement.querySelectorAll('ol');
                
                console.log('Elementos renderizados:', {
                    h1: h1Elements.length,
                    h2: h2Elements.length,
                    p: pElements.length,
                    ul: ulElements.length,
                    ol: olElements.length
                });
                
                // Se não há elementos renderizados, pode ser que o markdown não foi convertido
                if (h1Elements.length === 0 && h2Elements.length === 0 && pElements.length === 0) {
                    console.error('NENHUM elemento HTML foi renderizado! O markdown pode não ter sido convertido.');
                    console.log('Markdown original (primeiros 500 chars):', markdown.substring(0, 500));
                }
            } else {
                console.error('Elemento resumoContent não encontrado!');
            }
        }
        
        function convertMarkdownFallback(markdown) {
            if (!markdown || typeof markdown !== 'string') {
                return '';
            }
            
            let html = markdown;
            
            // PRIMEIRO: Converter blocos de código (```) para não interferir com outros processamentos
            let codeBlocks = [];
            let codeBlockIndex = 0;
            html = html.replace(/```([\s\S]*?)```/g, function(match, code) {
                let placeholder = `__CODE_BLOCK_${codeBlockIndex}__`;
                codeBlocks[codeBlockIndex] = '<pre><code>' + code.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</code></pre>';
                codeBlockIndex++;
                return placeholder;
            });
            
            // Converter código inline (antes de processar outras coisas)
            html = html.replace(/`([^`\n]+)`/g, '<code>$1</code>');
            
            // Separar o conteúdo em linhas
            let lines = html.split('\n');
            let result = [];
            let inList = false;
            let listType = null; // 'ul' ou 'ol'
            let listItems = [];
            let inParagraph = false;
            let paragraphLines = [];
            
            for (let i = 0; i < lines.length; i++) {
                let line = lines[i];
                let trimmedLine = line.trim();
                
                // Pular linhas que são placeholders de código
                if (trimmedLine.match(/^__CODE_BLOCK_\d+__$/)) {
                    // Fechar lista se estiver aberta
                    if (inList) {
                        result.push('<' + listType + '>' + listItems.join('') + '</' + listType + '>');
                        listItems = [];
                        inList = false;
                        listType = null;
                    }
                    // Fechar parágrafo se estiver aberto
                    if (inParagraph) {
                        result.push('<p>' + paragraphLines.join(' ') + '</p>');
                        paragraphLines = [];
                        inParagraph = false;
                    }
                    result.push(trimmedLine);
                    continue;
                }
                
                // Converter títulos (# ## ### #### ##### ######)
                if (trimmedLine.match(/^#{1,6}\s/)) {
                    // Fechar lista se estiver aberta
                    if (inList) {
                        result.push('<' + listType + '>' + listItems.join('') + '</' + listType + '>');
                        listItems = [];
                        inList = false;
                        listType = null;
                    }
                    // Fechar parágrafo se estiver aberto
                    if (inParagraph) {
                        result.push('<p>' + paragraphLines.join(' ') + '</p>');
                        paragraphLines = [];
                        inParagraph = false;
                    }
                    
                    let level = trimmedLine.match(/^(#{1,6})/)[1].length;
                    let titleText = trimmedLine.replace(/^#{1,6}\s+/, '');
                    // Processar formatação no título
                    titleText = titleText.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                    titleText = titleText.replace(/\*([^*]+?)\*/g, '<em>$1</em>');
                    result.push('<h' + level + '>' + titleText + '</h' + level + '>');
                    continue;
                }
                
                // Detectar listas não ordenadas (linhas começando com - ou * ou +)
                if (trimmedLine.match(/^[\*\-\+]\s/)) {
                    // Fechar parágrafo se estiver aberto
                    if (inParagraph) {
                        result.push('<p>' + paragraphLines.join(' ') + '</p>');
                        paragraphLines = [];
                        inParagraph = false;
                    }
                    
                    if (!inList || listType !== 'ul') {
                        if (inList) {
                            result.push('<' + listType + '>' + listItems.join('') + '</' + listType + '>');
                            listItems = [];
                        }
                        inList = true;
                        listType = 'ul';
                    }
                    let itemText = trimmedLine.replace(/^[\*\-\+]\s+/, '');
                    // Processar negrito primeiro (para não conflitar com itálico)
                    itemText = itemText.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                    // Processar itálico - apenas se não for parte de negrito
                    itemText = itemText.replace(/\*([^*]+?)\*/g, '<em>$1</em>');
                    // Processar código inline
                    itemText = itemText.replace(/`([^`]+)`/g, '<code>$1</code>');
                    listItems.push('<li>' + itemText + '</li>');
                    continue;
                }
                
                // Detectar listas ordenadas (linhas começando com número seguido de ponto)
                if (trimmedLine.match(/^\d+\.\s/)) {
                    // Fechar parágrafo se estiver aberto
                    if (inParagraph) {
                        result.push('<p>' + paragraphLines.join(' ') + '</p>');
                        paragraphLines = [];
                        inParagraph = false;
                    }
                    
                    if (!inList || listType !== 'ol') {
                        if (inList) {
                            result.push('<' + listType + '>' + listItems.join('') + '</' + listType + '>');
                            listItems = [];
                        }
                        inList = true;
                        listType = 'ol';
                    }
                    let itemText = trimmedLine.replace(/^\d+\.\s+/, '');
                    itemText = itemText.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                    itemText = itemText.replace(/\*([^*]+?)\*/g, '<em>$1</em>');
                    itemText = itemText.replace(/`([^`]+)`/g, '<code>$1</code>');
                    listItems.push('<li>' + itemText + '</li>');
                    continue;
                }
                
                // Linha vazia - fechar lista ou parágrafo se estiver aberto
                if (trimmedLine === '') {
                    if (inList) {
                        result.push('<' + listType + '>' + listItems.join('') + '</' + listType + '>');
                        listItems = [];
                        inList = false;
                        listType = null;
                    }
                    if (inParagraph) {
                        result.push('<p>' + paragraphLines.join(' ') + '</p>');
                        paragraphLines = [];
                        inParagraph = false;
                    }
                    result.push('');
                    continue;
                }
                
                // Linha normal - fechar lista se estiver aberta
                if (inList) {
                    result.push('<' + listType + '>' + listItems.join('') + '</' + listType + '>');
                    listItems = [];
                    inList = false;
                    listType = null;
                }
                
                // Processar formatação na linha
                let processedLine = trimmedLine;
                // Negrito primeiro
                processedLine = processedLine.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                // Itálico depois
                processedLine = processedLine.replace(/\*([^*]+?)\*/g, '<em>$1</em>');
                // Código inline já foi processado antes, mas garantir
                processedLine = processedLine.replace(/`([^`]+)`/g, '<code>$1</code>');
                
                // Adicionar à linha do parágrafo atual ou iniciar novo parágrafo
                if (processedLine) {
                    paragraphLines.push(processedLine);
                    inParagraph = true;
                }
            }
            
            // Fechar lista se ainda estiver aberta
            if (inList) {
                result.push('<' + listType + '>' + listItems.join('') + '</' + listType + '>');
            }
            
            // Fechar parágrafo se ainda estiver aberto
            if (inParagraph) {
                result.push('<p>' + paragraphLines.join(' ') + '</p>');
            }
            
            html = result.join('\n');
            
            // Restaurar blocos de código
            for (let i = 0; i < codeBlocks.length; i++) {
                html = html.replace(`__CODE_BLOCK_${i}__`, codeBlocks[i]);
            }
            
            return html;
        }
        
        function downloadHTML(filename, markdown) {
            // Converter markdown para HTML completo
            let html = markdown;
            if (typeof marked !== 'undefined') {
                marked.setOptions({
                    breaks: true,
                    gfm: true
                });
                html = marked.parse(markdown);
            }
            
            // Adicionar estilos completos com impressão
            const fullHTML = `<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumo Auxiliar</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: Arial, sans-serif; 
            max-width: 210mm;
            margin: 0 auto;
            padding: 20mm;
            line-height: 1.8;
            color: #1f2937;
            background: white !important;
        }
        
        .document-container {
            background: white !important;
            color: #1f2937;
        }
        
        h1 { 
            color: #1e40af !important; 
            font-size: 2.5rem;
            font-weight: 700;
            border-bottom: 3px solid #4f46e5;
            padding-bottom: 15px;
            margin-bottom: 30px;
            margin-top: 0;
            background: white !important;
            page-break-after: avoid;
        }
        
        h2 { 
            color: #6366f1 !important; 
            font-size: 2rem;
            font-weight: 600;
            margin-top: 40px;
            margin-bottom: 20px;
            border-left: 5px solid #6366f1;
            padding-left: 15px;
            background: white !important;
            page-break-after: avoid;
        }
        
        h3 { 
            color: #4f46e5 !important; 
            font-size: 1.5rem; 
            font-weight: 600; 
            margin-top: 30px;
            margin-bottom: 15px;
            background: white !important;
            page-break-after: avoid;
        }
        
        h4 { 
            color: #5b21b6 !important; 
            font-size: 1.25rem; 
            font-weight: 600; 
            margin-top: 25px;
            margin-bottom: 12px;
            background: white !important;
            page-break-after: avoid;
        }
        
        p { 
            line-height: 1.8; 
            margin-bottom: 16px; 
            font-size: 1.05rem; 
            text-align: justify;
            color: #1f2937 !important;
            background: white !important;
            page-break-inside: avoid;
        }
        
        strong { 
            color: #1e40af; 
            font-weight: 600;
        }
        
        em {
            font-style: italic;
        }
        
        code { 
            background: #f3f4f6 !important; 
            padding: 4px 8px; 
            border-radius: 4px; 
            font-family: "Courier New", monospace; 
            font-size: 0.95rem;
            color: #dc2626 !important;
            display: inline-block;
        }
        
        pre {
            background: #f3f4f6 !important;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 20px 0;
            page-break-inside: avoid;
        }
        
        pre code {
            background: transparent !important;
            padding: 0;
        }
        
        ul, ol { 
            margin: 20px 0; 
            padding-left: 40px;
        }
        
        li { 
            margin: 10px 0; 
            line-height: 1.8;
            color: #1f2937 !important;
            background: white !important;
            page-break-inside: avoid;
        }
        
        blockquote { 
            border-left: 5px solid #4f46e5; 
            padding: 15px 20px; 
            margin: 25px 0; 
            background: #f8fafc !important;
            font-style: italic;
            color: #475569;
            border-radius: 4px;
            page-break-inside: avoid;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 25px 0;
            page-break-inside: auto;
        }
        
        table th, table td { 
            border: 1px solid #d1d5db; 
            padding: 12px 15px; 
            text-align: left;
        }
        
        table th { 
            background: #6366f1 !important; 
            color: white !important; 
            font-weight: 600;
        }
        
        table tr {
            page-break-inside: avoid;
            background: white !important;
        }
        
        table tr:nth-child(even) {
            background: #f9fafb !important;
        }
        
        hr {
            border: none;
            border-top: 2px solid #e5e7eb;
            margin: 30px 0;
        }
        
        /* Estilos especiais para exercícios */
        .exercise-container {
            background: #fef3c7 !important;
            border-left: 5px solid #f59e0b;
            padding: 20px;
            margin: 30px 0;
            border-radius: 8px;
            page-break-inside: avoid;
        }
        
        .exercise-container h4 {
            color: #d97706 !important;
            margin-top: 0;
        }
        
        .answer-container {
            background: #d1fae5 !important;
            border-left: 5px solid #10b981;
            padding: 20px;
            margin: 30px 0;
            border-radius: 8px;
            page-break-inside: avoid;
        }
        
        .answer-container h4 {
            color: #059669 !important;
            margin-top: 0;
        }
        
        /* Estilos de impressão */
        @media print {
            @page {
                size: A4;
                margin: 20mm;
            }
            
            body {
                background: white !important;
                color: black !important;
                padding: 0;
                margin: 0;
            }
            
            .document-container {
                background: white !important;
                margin: 0;
                padding: 0;
            }
            
            h1, h2, h3, h4 {
                page-break-after: avoid;
                break-after: avoid;
            }
            
            p, li, blockquote, .exercise-container, .answer-container {
                page-break-inside: avoid;
                break-inside: avoid;
            }
            
            table {
                page-break-inside: auto;
            }
            
            tr {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
<div class="document-container">
${html}
</div>
</body>
</html>`;
            
            // Download
            const blob = new Blob([fullHTML], { type: 'text/html;charset=utf-8' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = (filename || 'resumo') + '.html';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dark-mode.js"></script>
</body>
</html>
