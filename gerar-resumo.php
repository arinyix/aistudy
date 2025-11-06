<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'config/api.php';
require_once 'classes/Routine.php';
require_once 'classes/Task.php';
require_once 'includes/session.php';

requireLogin();

$user = getCurrentUser();

// Ler dados JSON do body
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$task_id = $data['task_id'] ?? null;

$database = new Database();
$db = $database->getConnection();
$task = new Task($db);
$routine = new Routine($db);

// Output limpo
ob_clean();
header('Content-Type: application/json');

// Validar e processar
if (!$task_id || !is_numeric($task_id)) {
    echo json_encode(['success' => false, 'message' => 'Task ID inválido']);
    exit();
}

// Buscar task e verificar se pertence ao usuário
$task_data = $task->getTask($task_id, $user['id']);
if (!$task_data) {
    echo json_encode(['success' => false, 'message' => 'Tarefa não encontrada']);
    exit();
}

// Buscar rotina para pegar o nível
$rotina = $routine->getRoutine($task_data['routine_id'], $user['id']);
if (!$rotina) {
    echo json_encode(['success' => false, 'message' => 'Rotina não encontrada']);
    exit();
}

try {
    
    error_log("=== INICIANDO GERAÇÃO DE RESUMO ===");
    error_log("Task ID: " . $task_id);
    error_log("Tópico: " . $task_data['titulo']);
    error_log("Nível: " . $rotina['nivel']);
    
    // Chamar API
    set_time_limit(240); // 4 minutos
    ini_set('max_execution_time', 240);
    
    $openai = new OpenAIService();
    error_log("Chamando API OpenAI para gerar resumo...");
    error_log("Tempo limite: 180 segundos (3 minutos)");
    
    $start_time = microtime(true);
    $markdown_content = $openai->generateSummaryPDF(
        $task_data['titulo'], 
        $rotina['nivel'], 
        $task_data['descricao']
    );
    $end_time = microtime(true);
    $elapsed = round($end_time - $start_time, 2);
    
    if (empty($markdown_content)) {
        throw new Exception('Resumo gerado está vazio');
    }
    
    error_log("Resumo gerado com sucesso em {$elapsed} segundos (tamanho: " . strlen($markdown_content) . " caracteres)");
    
    // Retornar conteúdo markdown
    echo json_encode([
        'success' => true, 
        'content' => $markdown_content,
        'filename' => 'resumo_' . $task_id . '_' . time() . '.html',
        'time_elapsed' => $elapsed
    ]);
    
} catch (Exception $e) {
    error_log("ERRO ao gerar resumo: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao gerar resumo: ' . $e->getMessage()
    ]);
}
ob_end_flush();
?>

