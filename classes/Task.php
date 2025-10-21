<?php
require_once 'config/database.php';

class Task {
    private $conn;
    private $table_name = "tasks";
    
    public $id;
    public $routine_id;
    public $titulo;
    public $descricao;
    public $dia_estudo;
    public $ordem;
    public $status;
    public $material_estudo;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET routine_id=:routine_id, titulo=:titulo, descricao=:descricao, 
                      dia_estudo=:dia_estudo, ordem=:ordem, material_estudo=:material_estudo";
        
        $stmt = $this->conn->prepare($query);
        
        $this->routine_id = htmlspecialchars(strip_tags($this->routine_id));
        $this->titulo = htmlspecialchars(strip_tags($this->titulo));
        $this->descricao = htmlspecialchars(strip_tags($this->descricao));
        $this->dia_estudo = htmlspecialchars(strip_tags($this->dia_estudo));
        $this->ordem = htmlspecialchars(strip_tags($this->ordem));
        
        // Se material_estudo já é string JSON, não codificar novamente
        if (is_string($this->material_estudo)) {
            // Já é JSON, usar como está
            if (json_decode($this->material_estudo) === null) {
                // Se não é JSON válido, codificar como array vazio
                $this->material_estudo = json_encode([]);
            }
        } else {
            // É array, codificar para JSON
            $this->material_estudo = json_encode($this->material_estudo);
        }
        
        $stmt->bindParam(":routine_id", $this->routine_id);
        $stmt->bindParam(":titulo", $this->titulo);
        $stmt->bindParam(":descricao", $this->descricao);
        $stmt->bindParam(":dia_estudo", $this->dia_estudo);
        $stmt->bindParam(":ordem", $this->ordem);
        $stmt->bindParam(":material_estudo", $this->material_estudo);
        
        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        } else {
            // Debug: mostrar erro específico
            $errorInfo = $stmt->errorInfo();
            error_log("Erro ao criar task: " . $errorInfo[2]);
            error_log("Query: " . $query);
            error_log("Dados: routine_id={$this->routine_id}, titulo={$this->titulo}, descricao={$this->descricao}, dia_estudo={$this->dia_estudo}, ordem={$this->ordem}");
            return false;
        }
    }
    
    public function getRoutineTasks($routine_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE routine_id = :routine_id 
                  ORDER BY dia_estudo, ordem";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":routine_id", $routine_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function toggleStatus($task_id, $user_id) {
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
        
        // Alternar status da tarefa
        $query = "UPDATE " . $this->table_name . " 
                  SET status = CASE 
                      WHEN status = 'pendente' THEN 'concluida'
                      ELSE 'pendente'
                  END
                  WHERE id = :task_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":task_id", $task_id);
        
        if ($stmt->execute()) {
            // Atualizar progresso da rotina
            $routineQuery = "SELECT routine_id FROM " . $this->table_name . " WHERE id = :task_id";
            $routineStmt = $this->conn->prepare($routineQuery);
            $routineStmt->bindParam(":task_id", $task_id);
            $routineStmt->execute();
            $routine = $routineStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($routine) {
                $this->updateRoutineProgress($routine['routine_id']);
            }
            
            return true;
        }
        
        return false;
    }
    
    private function updateRoutineProgress($routine_id) {
        $query = "SELECT COUNT(*) as total, 
                         SUM(CASE WHEN status = 'concluida' THEN 1 ELSE 0 END) as concluidas 
                  FROM tasks WHERE routine_id = :routine_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":routine_id", $routine_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            $progresso = ($result['concluidas'] / $result['total']) * 100;
            
            $updateQuery = "UPDATE routines 
                           SET progresso = :progresso 
                           WHERE id = :routine_id";
            
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(":progresso", $progresso);
            $updateStmt->bindParam(":routine_id", $routine_id);
            
            $updateStmt->execute();
        }
    }
    
    public function getTask($id, $user_id) {
        $query = "SELECT t.*, r.titulo as rotina_titulo 
                  FROM tasks t 
                  JOIN routines r ON t.routine_id = r.id 
                  WHERE t.id = :id AND r.user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
