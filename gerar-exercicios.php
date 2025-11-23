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

// Verificar se já existe exercícios salvos NO BANCO DE DADOS
error_log("=== VERIFICANDO SE EXERCÍCIOS JÁ EXISTEM NO BANCO ===");
error_log("Task ID: " . $task_id);
error_log("User ID: " . $user['id']);

$exercicios_existentes = $task->getExercicios($task_id, $user['id']);

// Verificação mais rigorosa: verificar se não é null, não é vazio e tem conteúdo
if ($exercicios_existentes !== null && $exercicios_existentes !== '' && trim($exercicios_existentes) !== '') {
    error_log("=== ✅ EXERCÍCIOS JÁ EXISTEM NO BANCO - RETORNANDO CACHE ===");
    error_log("Task ID: " . $task_id);
    error_log("Tamanho dos exercícios: " . strlen($exercicios_existentes) . " caracteres");
    error_log("Primeiros 100 caracteres: " . substr($exercicios_existentes, 0, 100));
    error_log("⚠️ IMPORTANTE: RETORNANDO EXERCÍCIOS DO BANCO - NÃO VAI CHAMAR A API");
    
    // IMPORTANTE: NÃO CHAMAR A API - RETORNAR DIRETAMENTE OS EXERCÍCIOS DO BANCO
    ob_clean();
    echo json_encode([
        'success' => true, 
        'content' => $exercicios_existentes,
        'filename' => 'exercicios_' . $task_id . '_' . time() . '.html',
        'time_elapsed' => 0,
        'cached' => true,
        'message' => 'Exercícios recuperados do banco de dados (sem chamada à API)',
        'source' => 'database'
    ]);
    ob_end_flush();
    exit();
}

error_log("=== ❌ EXERCÍCIOS NÃO ENCONTRADOS NO BANCO - VAI GERAR NOVO ===");
error_log("Task ID: " . $task_id);
error_log("SERÁ NECESSÁRIO CHAMAR A API DO CHATGPT");

// Buscar rotina para pegar o nível
$rotina = $routine->getRoutine($task_data['routine_id'], $user['id']);
if (!$rotina) {
    echo json_encode(['success' => false, 'message' => 'Rotina não encontrada']);
    exit();
}

try {
    
    // VERIFICAR NOVAMENTE ANTES DE CHAMAR A API (segurança extra)
    $verificacao_final = $task->getExercicios($task_id, $user['id']);
    if ($verificacao_final !== null && $verificacao_final !== '' && trim($verificacao_final) !== '') {
        error_log("=== VERIFICAÇÃO FINAL: EXERCÍCIOS JÁ EXISTEM - CANCELANDO CHAMADA À API ===");
        echo json_encode([
            'success' => true, 
            'content' => $verificacao_final,
            'filename' => 'exercicios_' . $task_id . '_' . time() . '.html',
            'time_elapsed' => 0,
            'cached' => true,
            'message' => 'Exercícios recuperados do banco (evitada chamada à API)'
        ]);
        exit();
    }
    
    error_log("=== CONFIRMADO: EXERCÍCIOS NÃO EXISTEM - INICIANDO GERAÇÃO VIA API ===");
    error_log("Task ID: " . $task_id);
    error_log("Tópico: " . $task_data['titulo']);
    error_log("Nível: " . $rotina['nivel']);
    
    // AGORA SIM, chamar API (apenas se não existir no banco)
    set_time_limit(240); // 4 minutos
    ini_set('max_execution_time', 240);
    
    $openai = new OpenAIService();
    error_log("⚠️ CHAMANDO API OPENAI PARA GERAR EXERCÍCIOS...");
    error_log("Tempo limite: 180 segundos (3 minutos)");
    
    $start_time = microtime(true);
    $markdown_content = $openai->generateExerciciosPDF(
        $task_data['titulo'], 
        $rotina['nivel'], 
        $task_data['descricao']
    );
    $end_time = microtime(true);
    $elapsed = round($end_time - $start_time, 2);
    
    if (empty($markdown_content)) {
        throw new Exception('Exercícios gerados estão vazios');
    }
    
    error_log("✅ Exercícios gerados com sucesso em {$elapsed} segundos (tamanho: " . strlen($markdown_content) . " caracteres)");
    
    // Salvar exercícios no banco de dados
    $saved = $task->saveExercicios($task_id, $user['id'], $markdown_content);
    if ($saved) {
        error_log("✅ Exercícios salvos no banco de dados com sucesso!");
    } else {
        error_log("❌ AVISO: Não foi possível salvar os exercícios no banco de dados, mas serão retornados mesmo assim.");
    }
    
    // Retornar conteúdo markdown
    echo json_encode([
        'success' => true, 
        'content' => $markdown_content,
        'filename' => 'exercicios_' . $task_id . '_' . time() . '.html',
        'time_elapsed' => $elapsed,
        'cached' => false,
        'message' => 'Exercícios gerados via API e salvos no banco'
    ]);
    
} catch (Exception $e) {
    error_log("❌ ERRO ao gerar exercícios: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao gerar exercícios: ' . $e->getMessage()
    ]);
}
ob_end_flush();
?>

