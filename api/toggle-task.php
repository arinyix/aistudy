<?php
// Prevenir qualquer saída antes do JSON
ob_start();

require_once '../config/database.php';
require_once '../classes/Task.php';
require_once '../includes/session.php';

// Limpar buffer e definir header JSON
ob_clean();
header('Content-Type: application/json');

requireLogin();

$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $task_id = $input['task_id'] ?? null;
    
    error_log("API toggle-task.php called with task_id: " . $task_id . ", user_id: " . $user['id']);
    
    if (!$task_id) {
        echo json_encode(['success' => false, 'message' => 'ID da tarefa não fornecido']);
        exit();
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        $task = new Task($db);
        
        error_log("Calling toggleStatus for task_id: " . $task_id . ", user_id: " . $user['id']);
        
        $result = $task->toggleStatus($task_id, $user['id']);
        
        error_log("toggleStatus result: " . ($result ? 'true' : 'false'));
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Status da tarefa atualizado com sucesso']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar tarefa. Verifique se a tarefa pertence a você.']);
        }
    } catch (Exception $e) {
        error_log("Exception in toggle-task.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}

// Encerrar qualquer buffer de saída
ob_end_flush();
?>
