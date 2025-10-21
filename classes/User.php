<?php
require_once 'config/database.php';

class User {
    private $conn;
    private $table_name = "users";
    
    public $id;
    public $nome;
    public $email;
    public $senha_hash;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nome=:nome, email=:email, senha_hash=:senha_hash";
        
        $stmt = $this->conn->prepare($query);
        
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->senha_hash = password_hash($this->senha_hash, PASSWORD_DEFAULT);
        
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":senha_hash", $this->senha_hash);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }
    
    public function login($email, $senha) {
        $query = "SELECT id, nome, email, senha_hash 
                  FROM " . $this->table_name . " 
                  WHERE email = :email";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(password_verify($senha, $row['senha_hash'])) {
                $this->id = $row['id'];
                $this->nome = $row['nome'];
                $this->email = $row['email'];
                return true;
            }
        }
        return false;
    }
    
    public function emailExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nome=:nome, email=:email 
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->email = htmlspecialchars(strip_tags($this->email));
        
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }
    
    public function updatePassword($nova_senha) {
        $query = "UPDATE " . $this->table_name . " 
                  SET senha_hash=:senha_hash 
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $stmt->bindParam(":senha_hash", $senha_hash);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }
    
    public function verifyPassword($senha_atual) {
        $query = "SELECT senha_hash FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return password_verify($senha_atual, $row['senha_hash']);
        }
        return false;
    }
}
?>
