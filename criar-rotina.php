<?php
// Iniciar output buffering para evitar problemas de headers
ob_start();

// Configurar timeouts e error reporting
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'config/api.php';
require_once 'classes/Routine.php';
require_once 'classes/Task.php';
require_once 'includes/session.php';

requireLogin();

$user = getCurrentUser();
$message = '';

if ($_POST) {
    $tema = $_POST['tema'] ?? '';
    $nivel = $_POST['nivel'] ?? '';
    $tempo_diario = $_POST['tempo_diario'] ?? '';
    $dias_disponiveis = $_POST['dias_disponiveis'] ?? [];
    $horario_disponivel = $_POST['horario_disponivel'] ?? '';
    
    if ($tema && $nivel && $tempo_diario && !empty($dias_disponiveis) && $horario_disponivel) {
        try {
            // Aumentar timeout para permitir geração do plano
            set_time_limit(300); // 5 minutos
            ini_set('max_execution_time', 300);
            ini_set('max_input_time', 300);
            
            error_log("=== INICIANDO CRIAÇÃO DE ROTINA ===");
            error_log("Tema: {$tema}, Nível: {$nivel}, Tempo: {$tempo_diario}min");
            
            $database = new Database();
            $db = $database->getConnection();
            
            // Criar rotina
            $routine = new Routine($db);
            $routine->user_id = $user['id'];
            $routine->titulo = "Aprender " . $tema;
            $routine->tema = $tema;
            $routine->nivel = $nivel;
            $routine->tempo_diario = $tempo_diario;
            $routine->dias_disponiveis = $dias_disponiveis;
            $routine->horario_disponivel = $horario_disponivel;
            
            error_log("Criando rotina no banco de dados...");
            $routine_id = $routine->create();
            
            if ($routine_id) {
                error_log("Rotina criada com sucesso! ID: {$routine_id}");
                $plano_data = null;
                
                // Tentar gerar plano com IA
                try {
                    error_log("Iniciando geração de plano via API OpenAI...");
                    $openai = new OpenAIService();
                    
                    $start_time = microtime(true);
                    $plano = $openai->generateStudyPlan($tema, $nivel, $tempo_diario, $dias_disponiveis, $horario_disponivel);
                    $end_time = microtime(true);
                    $elapsed = round($end_time - $start_time, 2);
                    
                    error_log("Plano gerado em {$elapsed} segundos");
                    
                    // Log para debug
                    error_log("Resposta da API (primeiros 500 chars): " . substr($plano, 0, 500));
                    
                    $plano_data = json_decode($plano, true);
                    
                    // Verificar erro de JSON
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log("Erro JSON: " . json_last_error_msg());
                        error_log("Resposta completa: " . $plano);
                        // Tentar extrair JSON da resposta
                        preg_match('/\{.*\}/s', $plano, $matches);
                        if (!empty($matches)) {
                            $plano_data = json_decode($matches[0], true);
                        }
                    }
                    
                    // Debug: verificar se a API retornou dados válidos
                    if (!$plano_data) {
                        throw new Exception('API retornou dados inválidos. JSON error: ' . json_last_error_msg());
                    }
                } catch (Exception $e) {
                    // Log do erro completo
                    error_log("❌ ERRO ao gerar plano: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                    
                    // Se a rotina foi criada mas o plano falhou, deletar a rotina órfã
                    if ($routine_id) {
                        try {
                            $deleteQuery = "DELETE FROM routines WHERE id = :routine_id";
                            $deleteStmt = $db->prepare($deleteQuery);
                            $deleteStmt->bindParam(":routine_id", $routine_id);
                            $deleteStmt->execute();
                            error_log("Rotina órfã deletada (ID: {$routine_id})");
                        } catch (Exception $deleteError) {
                            error_log("Erro ao deletar rotina órfã: " . $deleteError->getMessage());
                        }
                    }
                    
                    // Mostrar erro ao usuário
                    $errorMsg = htmlspecialchars($e->getMessage());
                    if (strpos($errorMsg, 'timeout') !== false || strpos($errorMsg, 'timed out') !== false) {
                        $message = '<div class="alert alert-danger">
                            <strong>Erro de Timeout:</strong> A requisição demorou muito tempo. 
                            <br><br>
                            <strong>Possíveis causas:</strong>
                            <ul>
                                <li>A API do ChatGPT está lenta ou sobrecarregada</li>
                                <li>Sua conexão com a internet está lenta</li>
                                <li>O servidor está sobrecarregado</li>
                            </ul>
                            <br>
                            <strong>Solução:</strong> Tente novamente em alguns minutos. Se o problema persistir, verifique sua conexão com a internet.
                        </div>';
                    } else {
                        $message = '<div class="alert alert-danger">
                            <strong>Erro ao gerar plano com IA:</strong> ' . $errorMsg . '
                            <br><br>
                            <strong>Tente novamente mais tarde.</strong>
                        </div>';
                    }
                }
                
                // Debug: verificar estrutura do plano
                if (!$plano_data) {
                    error_log("Plano vazio ou null");
                    $message = '<div class="alert alert-danger">Erro: Plano de estudos não foi gerado. Verifique se a API está funcionando.</div>';
                } elseif (!isset($plano_data['dias'])) {
                    error_log("Campo 'dias' não encontrado no plano");
                    error_log("Estrutura do plano: " . print_r($plano_data, true));
                    $message = '<div class="alert alert-danger">Erro: Estrutura do plano inválida. Campo "dias" não encontrado.</div>';
                } elseif (!is_array($plano_data['dias'])) {
                    error_log("Campo 'dias' não é array: " . gettype($plano_data['dias']));
                    $message = '<div class="alert alert-danger">Erro: Campo "dias" não é um array.</div>';
                } elseif (empty($plano_data['dias'])) {
                    error_log("Array 'dias' está vazio");
                    $message = '<div class="alert alert-danger">Erro: Nenhum dia encontrado no plano.</div>';
                } else {
                    error_log("Plano válido com " . count($plano_data['dias']) . " dias");
                    // Estrutura válida, criar tarefas
                    $task = new Task($db);
                    $tarefas_criadas = 0;
                    $topicosJaCriados = []; // Array para rastrear tópicos já criados
                    
                    foreach ($plano_data['dias'] as $dia) {
                        if (!isset($dia['tarefas']) || !is_array($dia['tarefas'])) {
                            continue; // Pular dias sem tarefas
                        }
                        
                        foreach ($dia['tarefas'] as $index => $tarefa) {
                            $task->routine_id = $routine_id;
                            $task->titulo = $tarefa['titulo'] ?? 'Tarefa sem título';
                            $task->descricao = $tarefa['descricao'] ?? 'Descrição não disponível';
                            $task->dia_estudo = $dia['dia'] ?? 1;
                            $task->ordem = $index + 1;
                            
                            // Verificar se o tópico já foi criado
                            $tituloLower = strtolower($task->titulo);
                            if (in_array($tituloLower, $topicosJaCriados)) {
                                // Adicionar número ao tópico duplicado
                                $task->titulo = $task->titulo . ' (Versão ' . (time() . rand(1, 999)) . ')';
                                $tituloLower = strtolower($task->titulo);
                            }
                            
                            // Adicionar o tópico à lista
                            $topicosJaCriados[] = $tituloLower;
                            
                            // Verificar se há vídeos válidos no material
                            $material = $tarefa['material'] ?? [];
                            $temVideosValidos = false;
                            
                            // Verificar se há vídeos válidos
                            if (!empty($material['videos'])) {
                                foreach ($material['videos'] as $video) {
                                    if (isset($video['id']) && strlen($video['id']) == 11) {
                                        // ID válido do YouTube (11 caracteres)
                                        $temVideosValidos = true;
                                        break;
                                    }
                                }
                            }
                            
                            // SEMPRE buscar vídeos do YouTube ESPECÍFICOS para o tópico atual
                            // Esta é a forma AUTOMATIZADA de garantir vídeos relevantes para cada tópico
                            try {
                                require_once 'classes/YouTubeService.php';
                                $youtubeService = new YouTubeService();
                                $topico = $task->titulo; // Usar o título exato da tarefa como query
                                
                                // Buscar vídeos específicos para ESTE tópico exato
                                // Adicionar timeout para evitar travamento
                                $videosReais = $youtubeService->getEducationalVideos($topico, $nivel, 3);
                                
                                if (!empty($videosReais)) {
                                    // Sempre substituir pelos vídeos encontrados para este tópico
                                    $material['videos'] = $videosReais;
                                    error_log("Vídeos encontrados para tópico '{$topico}': " . count($videosReais));
                                } else {
                                    error_log("Nenhum vídeo encontrado para tópico '{$topico}'");
                                }
                            } catch (Exception $e) {
                                // Se falhar, tentar manter os vídeos que já existem (se houver)
                                error_log("Erro ao buscar vídeos para tópico '{$topico}': " . $e->getMessage());
                                // Continuar sem vídeos do YouTube se houver erro
                            }
                            
                            // Garantir que sempre há textos e exercícios
                            if (!isset($material['textos'])) {
                                $material['textos'] = [];
                            }
                            if (!isset($material['exercicios'])) {
                                $material['exercicios'] = [];
                            }
                            
                            $task->material_estudo = $material;
                            
                            if ($task->create()) {
                                $tarefas_criadas++;
                            }
                        }
                    }
                    
                    if ($tarefas_criadas > 0) {
                        error_log("✅ Rotina criada com sucesso! {$tarefas_criadas} tarefas criadas.");
                        ob_end_clean(); // Limpar buffer antes de redirecionar
                        header("Location: rotina-detalhada.php?id=" . $routine_id);
                        exit();
                    } else {
                        error_log("❌ Nenhuma tarefa foi criada para a rotina ID: {$routine_id}");
                        $message = '<div class="alert alert-danger">Erro: Nenhuma tarefa foi criada.</div>';
                    }
                }
            } else {
                $message = '<div class="alert alert-danger">Erro ao criar rotina. Tente novamente.</div>';
            }
        } catch (Exception $e) {
            error_log("❌ ERRO GERAL na criação de rotina: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $message = '<div class="alert alert-danger">
                <strong>Erro ao criar rotina:</strong> ' . htmlspecialchars($e->getMessage()) . '
                <br><br>
                Verifique os logs do servidor para mais detalhes.
            </div>';
        }
    } else {
        $message = '<div class="alert alert-warning">Preencha todos os campos obrigatórios.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIStudy - Criar Nova Rotina</title>
    
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-plus me-2"></i>Criar Nova Rotina de Estudos
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="tema" class="form-label">Tema/Assunto *</label>
                                    <input type="text" class="form-control" id="tema" name="tema" 
                                           placeholder="Ex: Álgebra Linear, Programação Python, História do Brasil" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="nivel" class="form-label">Nível *</label>
                                    <select class="form-select" id="nivel" name="nivel" required>
                                        <option value="">Selecione o nível</option>
                                        <option value="iniciante">Iniciante</option>
                                        <option value="intermediario">Intermediário</option>
                                        <option value="avancado">Avançado</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="tempo_diario" class="form-label">Tempo Diário (minutos) *</label>
                                    <input type="number" class="form-control" id="tempo_diario" name="tempo_diario" 
                                           min="15" max="300" placeholder="Ex: 60" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="horario_disponivel" class="form-label">Horário Disponível *</label>
                                    <input type="time" class="form-control" id="horario_disponivel" name="horario_disponivel" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Dias da Semana Disponíveis *</label>
                                <div class="row">
                                    <div class="col-md-3 col-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="dias_disponiveis[]" value="segunda" id="segunda">
                                            <label class="form-check-label" for="segunda">Segunda</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="dias_disponiveis[]" value="terca" id="terca">
                                            <label class="form-check-label" for="terca">Terça</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="dias_disponiveis[]" value="quarta" id="quarta">
                                            <label class="form-check-label" for="quarta">Quarta</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="dias_disponiveis[]" value="quinta" id="quinta">
                                            <label class="form-check-label" for="quinta">Quinta</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="dias_disponiveis[]" value="sexta" id="sexta">
                                            <label class="form-check-label" for="sexta">Sexta</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="dias_disponiveis[]" value="sabado" id="sabado">
                                            <label class="form-check-label" for="sabado">Sábado</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="dias_disponiveis[]" value="domingo" id="domingo">
                                            <label class="form-check-label" for="domingo">Domingo</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Como funciona:</strong> Nossa IA irá criar um plano de estudos personalizado baseado nas suas preferências. 
                                O plano incluirá tarefas diárias, materiais de estudo e cronograma otimizado para seu aprendizado.
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Voltar
                                </a>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-magic me-2"></i>Gerar Plano de Estudos
                                </button>
                            </div>
                            
                            <div id="loadingOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; justify-content: center; align-items: center;">
                                <div class="text-center text-white p-5" style="background: var(--card-bg); border-radius: 15px; max-width: 500px; margin: 20px;">
                                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                        <span class="visually-hidden">Carregando...</span>
                                    </div>
                                    <h4>Gerando Plano de Estudos</h4>
                                    <p class="mb-2">Isso pode levar 30-90 segundos...</p>
                                    <p class="small text-muted">Não feche esta página!</p>
                                    <div class="progress mt-3" style="height: 8px;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dark-mode.js"></script>
    <script>
        // Validação do formulário e loading overlay
        document.querySelector('form').addEventListener('submit', function(e) {
            const diasSelecionados = document.querySelectorAll('input[name="dias_disponiveis[]"]:checked');
            if (diasSelecionados.length === 0) {
                e.preventDefault();
                alert('Selecione pelo menos um dia da semana disponível.');
                return;
            }
            
            // Mostrar loading overlay
            const loadingOverlay = document.getElementById('loadingOverlay');
            const submitBtn = document.getElementById('submitBtn');
            
            if (loadingOverlay && submitBtn) {
                loadingOverlay.style.display = 'flex';
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Gerando...';
            }
            
            // Timeout de segurança - se demorar mais de 5 minutos, mostrar mensagem
            setTimeout(function() {
                if (loadingOverlay && loadingOverlay.style.display !== 'none') {
                    const loadingContent = loadingOverlay.querySelector('.text-center');
                    if (loadingContent) {
                        loadingContent.innerHTML = `
                            <h4>⏱️ Ainda processando...</h4>
                            <p class="mb-2">A geração do plano está demorando mais que o esperado.</p>
                            <p class="small text-muted">Por favor, aguarde mais alguns instantes...</p>
                            <div class="progress mt-3" style="height: 8px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                            </div>
                        `;
                    }
                }
            }, 180000); // 3 minutos
        });
    </script>
</body>
</html>
