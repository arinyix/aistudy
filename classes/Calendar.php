<?php
require_once 'config/database.php';

class Calendar {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function getTasksForDate($user_id, $date) {
        // Converter data para dia da semana
        $dayOfWeek = $this->getDayOfWeek($date);
        $dayOfWeekJson = '"' . $dayOfWeek . '"';
        
        $query = "SELECT t.*, r.titulo as rotina_titulo, r.dias_disponiveis, r.horario_disponivel
                  FROM tasks t 
                  JOIN routines r ON t.routine_id = r.id 
                  WHERE r.user_id = :user_id 
                  AND t.status = 'pendente'
                  AND JSON_CONTAINS(r.dias_disponiveis, :day_of_week)
                  ORDER BY r.horario_disponivel, t.ordem";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":day_of_week", $dayOfWeekJson);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getTasksForWeek($user_id, $startDate) {
        $tasks = [];
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime($startDate . " +$i days"));
            $tasks[$date] = $this->getTasksForDate($user_id, $date);
        }
        return $tasks;
    }
    
    public function getTasksForMonth($user_id, $year, $month) {
        $tasks = [];
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $tasks[$date] = $this->getTasksForDate($user_id, $date);
        }
        
        return $tasks;
    }
    
    private function getDayOfWeek($date) {
        $days = [
            0 => 'domingo',
            1 => 'segunda', 
            2 => 'terca',
            3 => 'quarta',
            4 => 'quinta',
            5 => 'sexta',
            6 => 'sabado'
        ];
        
        $dayNumber = date('w', strtotime($date));
        return $days[$dayNumber];
    }
    
    public function getNextStudyDate($user_id, $currentDate = null) {
        if (!$currentDate) {
            $currentDate = date('Y-m-d');
        }
        
        // Buscar próximos 30 dias
        for ($i = 0; $i < 30; $i++) {
            $date = date('Y-m-d', strtotime($currentDate . " +$i days"));
            $tasks = $this->getTasksForDate($user_id, $date);
            if (!empty($tasks)) {
                return $date;
            }
        }
        
        return null;
    }
    
    public function getStudySchedule($user_id, $startDate = null) {
        if (!$startDate) {
            $startDate = date('Y-m-d');
        }
        
        $schedule = [];
        $routines = $this->getUserRoutines($user_id);
        
        foreach ($routines as $routine) {
            $diasDisponiveis = json_decode($routine['dias_disponiveis'], true);
            $horario = $routine['horario_disponivel'];
            
            // Gerar cronograma para próximos 30 dias
            for ($i = 0; $i < 30; $i++) {
                $date = date('Y-m-d', strtotime($startDate . " +$i days"));
                $dayOfWeek = $this->getDayOfWeek($date);
                
                if (in_array($dayOfWeek, $diasDisponiveis)) {
                    $schedule[$date][] = [
                        'rotina' => $routine,
                        'horario' => $horario
                    ];
                }
            }
        }
        
        return $schedule;
    }
    
    private function getUserRoutines($user_id) {
        $query = "SELECT * FROM routines WHERE user_id = :user_id AND status = 'ativa'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function markTaskCompleted($task_id, $user_id) {
        // Verificar se a tarefa pertence ao usuário
        $checkQuery = "SELECT t.id FROM tasks t 
                      JOIN routines r ON t.routine_id = r.id 
                      WHERE t.id = :task_id AND r.user_id = :user_id";
        
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(":task_id", $task_id);
        $checkStmt->bindParam(":user_id", $user_id);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            return false;
        }
        
        // Marcar como concluída
        $updateQuery = "UPDATE tasks SET status = 'concluida' WHERE id = :task_id";
        $updateStmt = $this->conn->prepare($updateQuery);
        $updateStmt->bindParam(":task_id", $task_id);
        
        return $updateStmt->execute();
    }
    
    public function getNextTaskForRoutine($user_id, $routine_id) {
        $query = "SELECT t.*, r.titulo as rotina_titulo, r.dias_disponiveis, r.horario_disponivel
                  FROM tasks t 
                  JOIN routines r ON t.routine_id = r.id 
                  WHERE r.user_id = :user_id 
                  AND t.routine_id = :routine_id
                  AND t.status = 'pendente'
                  ORDER BY t.ordem ASC
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":routine_id", $routine_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getNextTasksForDate($user_id, $date) {
        // Buscar todas as rotinas ativas do usuário
        $query = "SELECT id FROM routines WHERE user_id = :user_id AND status = 'ativa'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $routines = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $nextTasks = [];
        foreach ($routines as $routine) {
            // Buscar a próxima tarefa pendente para esta rotina
            $nextTask = $this->getNextTaskForRoutine($user_id, $routine['id']);
            if ($nextTask) {
                $nextTasks[] = $nextTask;
            }
        }
        
        return $nextTasks;
    }
    
    public function getTasksForSpecificDate($user_id, $date) {
        // Converter data para dia da semana
        $dayOfWeek = $this->getDayOfWeek($date);
        $dayOfWeekJson = '"' . $dayOfWeek . '"';
        
        // Buscar tarefas para a data específica, mas com progressão sequencial
        $query = "SELECT t.*, r.id as routine_id, r.titulo as rotina_titulo, r.dias_disponiveis, r.horario_disponivel,
                         (SELECT COUNT(*) FROM tasks t2 WHERE t2.routine_id = t.routine_id AND t2.dia_estudo <= t.dia_estudo AND t2.status = 'concluida') as tarefas_concluidas_antes
                  FROM tasks t 
                  JOIN routines r ON t.routine_id = r.id 
                  WHERE r.user_id = :user_id 
                  AND t.status = 'pendente'
                  AND JSON_CONTAINS(r.dias_disponiveis, :day_of_week)
                  AND t.dia_estudo = (
                      SELECT MIN(t3.dia_estudo) 
                      FROM tasks t3 
                      WHERE t3.routine_id = t.routine_id 
                      AND t3.status = 'pendente'
                  )
                  ORDER BY r.horario_disponivel, t.ordem";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":day_of_week", $dayOfWeekJson);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
