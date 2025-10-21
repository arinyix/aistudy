<?php
require_once 'config/database.php';
require_once 'classes/Quiz.php';
require_once 'includes/session.php';

requireLogin();

$user = getCurrentUser();
$quiz_id = $_GET['id'] ?? null;

if (!$quiz_id || !is_numeric($quiz_id)) {
    header('Location: rotinas.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$quiz = new Quiz($db);

// Buscar quiz
$quiz_data = $quiz->getQuiz($quiz_id, $user['id']);

if (!$quiz_data) {
    header('Location: rotinas.php');
    exit();
}

$perguntas = json_decode($quiz_data['perguntas_json'], true);
$message = '';

// Processar submissão do quiz
if ($_POST && $quiz_data['status'] === 'pendente') {
    $respostas = [];
    for ($i = 0; $i < count($perguntas); $i++) {
        $respostas[$i] = $_POST['pergunta_' . $i] ?? null;
    }
    
    if ($quiz->submitAnswers($quiz_id, $respostas, $user['id'])) {
        $message = '<div class="alert alert-success">Quiz concluído com sucesso!</div>';
        // Recarregar dados do quiz
        $quiz_data = $quiz->getQuiz($quiz_id, $user['id']);
    } else {
        $message = '<div class="alert alert-danger">Erro ao salvar respostas. Tente novamente.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIStudy - <?php echo htmlspecialchars($quiz_data['titulo']); ?></title>
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
                        <h1 class="text-gradient"><?php echo htmlspecialchars($quiz_data['titulo']); ?></h1>
                        <p class="text-muted">
                            <i class="fas fa-book me-1"></i><?php echo htmlspecialchars($quiz_data['rotina_titulo']); ?>
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="rotina-detalhada.php?id=<?php echo $quiz_data['routine_id']; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Voltar
                        </a>
                        <a href="progresso.php" class="btn btn-outline-primary">
                            <i class="fas fa-chart-line me-2"></i>Progresso
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php echo $message; ?>

        <?php if ($quiz_data['status'] === 'concluido'): ?>
            <!-- Resultado do Quiz -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header text-center">
                            <h5 class="mb-0">
                                <i class="fas fa-trophy me-2"></i>Resultado do Quiz
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="mb-4">
                                <i class="fas fa-star text-warning fa-4x mb-3"></i>
                                <h2 class="text-primary"><?php echo number_format($quiz_data['nota'], 1); ?>%</h2>
                                <p class="text-muted">
                                    <?php 
                                    $nota = $quiz_data['nota'];
                                    if ($nota >= 80) {
                                        echo "Excelente! Você domina o assunto!";
                                    } elseif ($nota >= 60) {
                                        echo "Bom trabalho! Continue estudando!";
                                    } elseif ($nota >= 40) {
                                        echo "Precisa revisar mais o conteúdo.";
                                    } else {
                                        echo "Recomendamos revisar o material antes de continuar.";
                                    }
                                    ?>
                                </p>
                            </div>
                            
                            <div class="d-flex justify-content-center gap-3">
                                <a href="rotina-detalhada.php?id=<?php echo $quiz_data['routine_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-arrow-left me-2"></i>Voltar para Rotina
                                </a>
                                <a href="progresso.php" class="btn btn-outline-primary">
                                    <i class="fas fa-chart-line me-2"></i>Ver Progresso
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Formulário do Quiz -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <form method="POST">
                        <?php foreach ($perguntas as $index => $pergunta): ?>
                            <div class="quiz-question mb-4">
                                <h6 class="mb-3">
                                    <span class="badge bg-primary me-2"><?php echo $index + 1; ?></span>
                                    <?php echo htmlspecialchars($pergunta['pergunta']); ?>
                                </h6>
                                
                                <div class="row">
                                    <?php foreach ($pergunta['opcoes'] as $opcao_index => $opcao): ?>
                                        <div class="col-md-6 mb-2">
                                            <div class="quiz-option" onclick="selectOption(this, <?php echo $index; ?>, <?php echo $opcao_index; ?>)">
                                                <input type="radio" name="pergunta_<?php echo $index; ?>" 
                                                       value="<?php echo $opcao_index; ?>" id="pergunta_<?php echo $index; ?>_<?php echo $opcao_index; ?>" 
                                                       style="display: none;">
                                                <label for="pergunta_<?php echo $index; ?>_<?php echo $opcao_index; ?>" class="mb-0">
                                                    <?php echo htmlspecialchars($opcao); ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-check me-2"></i>Concluir Quiz
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectOption(element, perguntaIndex, opcaoIndex) {
            // Remover seleção anterior da mesma pergunta
            const perguntaContainer = element.closest('.quiz-question');
            perguntaContainer.querySelectorAll('.quiz-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            // Selecionar nova opção
            element.classList.add('selected');
            element.querySelector('input[type="radio"]').checked = true;
        }
        
        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            const totalPerguntas = <?php echo count($perguntas); ?>;
            let respostasPreenchidas = 0;
            
            for (let i = 0; i < totalPerguntas; i++) {
                if (document.querySelector(`input[name="pergunta_${i}"]:checked`)) {
                    respostasPreenchidas++;
                }
            }
            
            if (respostasPreenchidas < totalPerguntas) {
                e.preventDefault();
                alert('Por favor, responda todas as perguntas antes de concluir o quiz.');
            }
        });
    </script>
</body>
</html>
