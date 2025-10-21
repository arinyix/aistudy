<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../classes/Task.php';
require_once '../classes/Calendar.php';
require_once '../includes/session.php';

requireLogin();

$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $task_id = $input['task_id'] ?? null;
    
    if (!$task_id) {
        echo json_encode(['success' => false, 'message' => 'ID da tarefa não fornecido']);
        exit();
    }
    
    $database = new Database();
    $db = $database->getConnection();
    $calendar = new Calendar($db);
    
    if ($calendar->markTaskCompleted($task_id, $user['id'])) {
        echo json_encode(['success' => true, 'message' => 'Tarefa marcada como concluída']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar tarefa']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>
