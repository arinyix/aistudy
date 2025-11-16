<?php
require_once 'config/database.php';

class Routine {
    private $conn;
    private $table_name = "routines";
    
    public $id;
    public $user_id;
    public $titulo;
    public $tema;
    public $tipo; // 'geral', 'enem', 'concurso'
    public $contexto_json; // Dados específicos do tipo (banca, cargo, ano ENEM, etc.)
    public $nivel;
    public $tempo_diario;
    public $dias_disponiveis;
    public $horario_disponivel;
    public $progresso;
    public $status;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create() {
        // Se tipo não foi definido, usar 'geral' como padrão
        if (empty($this->tipo)) {
            $this->tipo = 'geral';
        }
        
        // Se contexto_json é array, converter para JSON
        if (is_array($this->contexto_json)) {
            $this->contexto_json = json_encode($this->contexto_json);
        } elseif (empty($this->contexto_json)) {
            $this->contexto_json = null;
        }
        
        $query = "INSERT INTO " . $this->table_name . " 
                  SET user_id=:user_id, titulo=:titulo, tema=:tema, tipo=:tipo, 
                      contexto_json=:contexto_json, nivel=:nivel, 
                      tempo_diario=:tempo_diario, dias_disponiveis=:dias_disponiveis, 
                      horario_disponivel=:horario_disponivel";
        
        $stmt = $this->conn->prepare($query);
        
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->titulo = htmlspecialchars(strip_tags($this->titulo));
        $this->tema = htmlspecialchars(strip_tags($this->tema));
        $this->tipo = htmlspecialchars(strip_tags($this->tipo));
        $this->nivel = htmlspecialchars(strip_tags($this->nivel));
        $this->tempo_diario = htmlspecialchars(strip_tags($this->tempo_diario));
        $this->dias_disponiveis = json_encode($this->dias_disponiveis);
        $this->horario_disponivel = htmlspecialchars(strip_tags($this->horario_disponivel));
        
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":titulo", $this->titulo);
        $stmt->bindParam(":tema", $this->tema);
        $stmt->bindParam(":tipo", $this->tipo);
        $stmt->bindParam(":contexto_json", $this->contexto_json);
        $stmt->bindParam(":nivel", $this->nivel);
        $stmt->bindParam(":tempo_diario", $this->tempo_diario);
        $stmt->bindParam(":dias_disponiveis", $this->dias_disponiveis);
        $stmt->bindParam(":horario_disponivel", $this->horario_disponivel);
        
        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    public function getUserRoutines($user_id, $tipo = null) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id";
        
        if ($tipo) {
            $query .= " AND tipo = :tipo";
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        
        if ($tipo) {
            $stmt->bindParam(":tipo", $tipo);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getRoutine($id, $user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getTodayTasks($user_id, $date) {
        $query = "SELECT t.*, r.titulo as rotina_titulo 
                  FROM tasks t 
                  JOIN routines r ON t.routine_id = r.id 
                  WHERE r.user_id = :user_id 
                  AND DATE(t.created_at) <= :date 
                  AND t.status = 'pendente'
                  ORDER BY t.dia_estudo, t.ordem";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":date", $date);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateProgress($routine_id) {
        $query = "SELECT COUNT(*) as total, 
                         SUM(CASE WHEN status = 'concluida' THEN 1 ELSE 0 END) as concluidas 
                  FROM tasks WHERE routine_id = :routine_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":routine_id", $routine_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            $progresso = ($result['concluidas'] / $result['total']) * 100;
            
            $updateQuery = "UPDATE " . $this->table_name . " 
                           SET progresso = :progresso 
                           WHERE id = :routine_id";
            
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(":progresso", $progresso);
            $updateStmt->bindParam(":routine_id", $routine_id);
            
            return $updateStmt->execute();
        }
        
        return false;
    }
    
    public function delete($id, $user_id) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":user_id", $user_id);
        
        return $stmt->execute();
    }
}
?>
