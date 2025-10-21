<?php
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
            
            $routine_id = $routine->create();
            
            if ($routine_id) {
                $plano_data = null;
                
                // Tentar gerar plano com IA
                try {
                    $openai = new OpenAIService();
                    $plano = $openai->generateStudyPlan($tema, $nivel, $tempo_diario, $dias_disponiveis, $horario_disponivel);
                    $plano_data = json_decode($plano, true);
                    
                    // Debug: verificar se a API retornou dados válidos
                    if (!$plano_data) {
                        throw new Exception('API retornou dados inválidos');
                    }
                } catch (Exception $e) {
                    // Se a API falhar, usar dados de fallback
                    require_once 'config/fallback-data.php';
                    $plano_data = FallbackData::getStudyPlan($tema, $nivel);
                    
                    // Debug: verificar se o fallback funcionou
                    if (!$plano_data) {
                        $message = '<div class="alert alert-danger">Erro: Nem a API nem o fallback funcionaram. Verifique a configuração.</div>';
                    }
                }
                
                // Debug: verificar estrutura do plano
                if (!$plano_data) {
                    $message = '<div class="alert alert-danger">Erro: Plano de estudos não foi gerado. Verifique se a API está funcionando.</div>';
                } elseif (!isset($plano_data['dias'])) {
                    $message = '<div class="alert alert-danger">Erro: Estrutura do plano inválida. Campo "dias" não encontrado.</div>';
                } elseif (!is_array($plano_data['dias'])) {
                    $message = '<div class="alert alert-danger">Erro: Campo "dias" não é um array.</div>';
                } elseif (empty($plano_data['dias'])) {
                    $message = '<div class="alert alert-danger">Erro: Nenhum dia encontrado no plano.</div>';
                } else {
                    // Estrutura válida, criar tarefas
                    $task = new Task($db);
                    $tarefas_criadas = 0;
                    
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
                            $task->material_estudo = $tarefa['material'] ?? [];
                            
                            if ($task->create()) {
                                $tarefas_criadas++;
                            }
                        }
                    }
                    
                    if ($tarefas_criadas > 0) {
                        header("Location: rotina-detalhada.php?id=" . $routine_id);
                        exit();
                    } else {
                        $message = '<div class="alert alert-danger">Erro: Nenhuma tarefa foi criada.</div>';
                    }
                }
            } else {
                $message = '<div class="alert alert-danger">Erro ao criar rotina. Tente novamente.</div>';
            }
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger">Erro: ' . $e->getMessage() . '</div>';
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
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-magic me-2"></i>Gerar Plano de Estudos
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            const diasSelecionados = document.querySelectorAll('input[name="dias_disponiveis[]"]:checked');
            if (diasSelecionados.length === 0) {
                e.preventDefault();
                alert('Selecione pelo menos um dia da semana disponível.');
            }
        });
    </script>
</body>
</html>
