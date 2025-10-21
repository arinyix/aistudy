<?php
require_once 'config/database.php';

class Quiz {
    private $conn;
    private $table_name = "quizzes";
    
    public $id;
    public $routine_id;
    public $titulo;
    public $perguntas_json;
    public $respostas_usuario;
    public $nota;
    public $status;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET routine_id=:routine_id, titulo=:titulo, perguntas_json=:perguntas_json";
        
        $stmt = $this->conn->prepare($query);
        
        $this->routine_id = htmlspecialchars(strip_tags($this->routine_id));
        $this->titulo = htmlspecialchars(strip_tags($this->titulo));
        $this->perguntas_json = json_encode($this->perguntas_json);
        
        $stmt->bindParam(":routine_id", $this->routine_id);
        $stmt->bindParam(":titulo", $this->titulo);
        $stmt->bindParam(":perguntas_json", $this->perguntas_json);
        
        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    public function getRoutineQuizzes($routine_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE routine_id = :routine_id 
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":routine_id", $routine_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getQuiz($id, $user_id) {
        $query = "SELECT q.*, r.titulo as rotina_titulo 
                  FROM " . $this->table_name . " q
                  JOIN routines r ON q.routine_id = r.id 
                  WHERE q.id = :id AND r.user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function submitAnswers($quiz_id, $respostas, $user_id) {
        // Verificar se o quiz pertence ao usuário
        $checkQuery = "SELECT q.id FROM " . $this->table_name . " q 
                       JOIN routines r ON q.routine_id = r.id 
                       WHERE q.id = :quiz_id AND r.user_id = :user_id";
        
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(":quiz_id", $quiz_id);
        $checkStmt->bindParam(":user_id", $user_id);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            return false;
        }
        
        // Buscar perguntas do quiz
        $quizQuery = "SELECT perguntas_json FROM " . $this->table_name . " WHERE id = :quiz_id";
        $quizStmt = $this->conn->prepare($quizQuery);
        $quizStmt->bindParam(":quiz_id", $quiz_id);
        $quizStmt->execute();
        
        $quiz = $quizStmt->fetch(PDO::FETCH_ASSOC);
        $perguntas = json_decode($quiz['perguntas_json'], true);
        
        // Calcular nota
        $acertos = 0;
        $total = count($perguntas);
        
        foreach ($perguntas as $index => $pergunta) {
            if (isset($respostas[$index]) && $respostas[$index] == $pergunta['resposta_correta']) {
                $acertos++;
            }
        }
        
        $nota = ($acertos / $total) * 100;
        
        // Atualizar quiz
        $updateQuery = "UPDATE " . $this->table_name . " 
                       SET respostas_usuario=:respostas_usuario, nota=:nota, status='concluido' 
                       WHERE id=:quiz_id";
        
        $updateStmt = $this->conn->prepare($updateQuery);
        $updateStmt->bindParam(":respostas_usuario", json_encode($respostas));
        $updateStmt->bindParam(":nota", $nota);
        $updateStmt->bindParam(":quiz_id", $quiz_id);
        
        return $updateStmt->execute();
    }
    
    public function getUserQuizzes($user_id) {
        $query = "SELECT q.*, r.titulo as rotina_titulo 
                  FROM " . $this->table_name . " q
                  JOIN routines r ON q.routine_id = r.id 
                  WHERE r.user_id = :user_id 
                  ORDER BY q.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAverageScore($user_id) {
        $query = "SELECT AVG(nota) as media 
                  FROM " . $this->table_name . " q
                  JOIN routines r ON q.routine_id = r.id 
                  WHERE r.user_id = :user_id AND q.status = 'concluido'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['media'] ? round($result['media'], 2) : 0;
    }
}
?>
