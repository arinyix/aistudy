<?php
require_once 'config/database.php';
require_once 'config/api.php';
require_once 'classes/Routine.php';
require_once 'classes/Quiz.php';
require_once 'includes/session.php';

requireLogin();

$user = getCurrentUser();
$routine_id = $_GET['routine_id'] ?? null;
$dia = $_GET['dia'] ?? null;
$assunto = $_GET['assunto'] ?? null;

if (!$routine_id || !is_numeric($routine_id) || !$dia || !$assunto) {
    header('Location: rotinas.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$routine = new Routine($db);
$quiz = new Quiz($db);

// Verificar se a rotina pertence ao usuário
$rotina = $routine->getRoutine($routine_id, $user['id']);
if (!$rotina) {
    header('Location: rotinas.php');
    exit();
}

$message = '';

if ($_POST) {
    try {
        $quiz_json = null;
        
        // Log para debugging
        error_log("Iniciando geração de quiz para dia: " . $dia . " - Assunto: " . $assunto);
        
        // Tentar gerar quiz com IA específico para o dia
        try {
            $openai = new OpenAIService();
            $quiz_data = $openai->generateQuiz($assunto, $rotina['nivel'], "Dia " . $dia . " - " . $assunto);
            
            error_log("Resposta da API: " . substr($quiz_data, 0, 200) . "...");
            
            // Limpar resposta da IA se necessário
            $cleanJson = preg_replace('/^[^{]*/', '', $quiz_data);
            $cleanJson = preg_replace('/[^}]*$/', '', $cleanJson);
            
            $quiz_json = json_decode($cleanJson, true);
            
            // Se ainda não funcionou, tentar decodificar a resposta original
            if (!$quiz_json) {
                $quiz_json = json_decode($quiz_data, true);
            }
            
            error_log("Quiz JSON decodificado: " . (is_array($quiz_json) ? "Sucesso" : "Falha"));
            
        } catch (Exception $e) {
            error_log("Erro na API: " . $e->getMessage());
            // Se a API falhar, usar dados de fallback
            require_once 'config/fallback-data.php';
            $quiz_json = FallbackData::getQuiz($assunto, $rotina['nivel']);
        }
        
        // Se ainda não tem quiz_json, usar fallback
        if (!$quiz_json) {
            error_log("Usando dados de fallback");
            require_once 'config/fallback-data.php';
            $quiz_json = FallbackData::getQuiz($assunto, $rotina['nivel']);
        }
        
        if ($quiz_json && isset($quiz_json['perguntas'])) {
            // Criar quiz no banco
            $quiz->routine_id = $routine_id;
            $quiz->titulo = "Quiz Dia " . $dia . ": " . $assunto;
            $quiz->perguntas_json = $quiz_json['perguntas'];
            
            $quiz_id = $quiz->create();
            
            if ($quiz_id) {
                error_log("Quiz criado com sucesso. ID: " . $quiz_id);
                header("Location: quiz.php?id=" . $quiz_id);
                exit();
            } else {
                error_log("Erro ao criar quiz no banco");
                $message = '<div class="alert alert-danger">Erro ao criar quiz. Tente novamente.</div>';
            }
        } else {
            error_log("Erro: Quiz JSON inválido ou sem perguntas");
            $message = '<div class="alert alert-danger">Erro ao gerar perguntas. Tente novamente.</div>';
        }
    } catch (Exception $e) {
        error_log("Erro geral: " . $e->getMessage());
        $message = '<div class="alert alert-danger">Erro: ' . $e->getMessage() . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIStudy - Gerar Quiz do Dia</title>
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
                            <i class="fas fa-question-circle me-2"></i>Gerar Quiz do Dia
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        
                        <div class="text-center mb-4">
                            <i class="fas fa-calendar-day text-primary fa-4x mb-3"></i>
                            <h4>Quiz do Dia <?php echo $dia; ?></h4>
                            <p class="text-muted">
                                Quiz específico sobre: <strong><?php echo htmlspecialchars($assunto); ?></strong>
                            </p>
                            <p class="text-muted">
                                Nível: <strong><?php echo ucfirst($rotina['nivel']); ?></strong>
                            </p>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Como funciona:</strong> A IA criará 5 perguntas específicas sobre 
                            <strong><?php echo htmlspecialchars($assunto); ?></strong> do Dia <?php echo $dia; ?>, 
                            apropriadas para o nível <strong><?php echo ucfirst($rotina['nivel']); ?></strong>.
                        </div>
                        
                        <form method="POST">
                            <div class="d-flex justify-content-between">
                                <a href="rotina-detalhada.php?id=<?php echo $routine_id; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Voltar
                                </a>
                                <button type="submit" class="btn btn-primary" id="generateBtn">
                                    <i class="fas fa-magic me-2"></i>Gerar Quiz do Dia
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
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded');
            const form = document.querySelector('form');
            const btn = document.getElementById('generateBtn');
            
            console.log('Form found:', form);
            console.log('Button found:', btn);
            
            if (form && btn) {
                form.addEventListener('submit', function(e) {
                    console.log('Form submitted');
                    
                    console.log('Setting loading state');
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Gerando Quiz...';
                    btn.disabled = true;
                    btn.classList.add('btn-loading');
                });
            } else {
                console.error('Form or button not found');
                console.error('Form:', form);
                console.error('Button:', btn);
            }
        });
    </script>
</body>
</html>
