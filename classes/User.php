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
    
    /**
     * Cria um novo usuário
     * @return bool True se criado com sucesso, False caso contrário
     */
    public function create(): bool {
        // Validar campos obrigatórios
        if (empty($this->nome) || empty($this->email) || empty($this->senha_hash)) {
            error_log("Tentativa de criar usuário com campos vazios");
            return false;
        }
        
        // Validar formato de email
        $this->email = filter_var(trim($this->email), FILTER_SANITIZE_EMAIL);
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            error_log("Email inválido: " . $this->email);
            return false;
        }
        
        // Sanitizar nome (remover tags HTML, limitar tamanho)
        $this->nome = filter_var(trim($this->nome), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $this->nome = mb_substr($this->nome, 0, 100); // Limitar tamanho
        
        // Validar senha (mínimo 6 caracteres)
        if (strlen($this->senha_hash) < 6) {
            error_log("Senha muito curta");
            return false;
        }
        
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nome=:nome, email=:email, senha_hash=:senha_hash";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            // Hash da senha
            $senha_hash = password_hash($this->senha_hash, PASSWORD_DEFAULT);
            
            $stmt->bindParam(":nome", $this->nome);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":senha_hash", $senha_hash);
            
            if ($stmt->execute()) {
                $this->senha_hash = $senha_hash; // Atualizar propriedade
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erro ao criar usuário: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Autentica um usuário
     * @param string $email Email do usuário
     * @param string $senha Senha do usuário
     * @return bool True se autenticado com sucesso, False caso contrário
     */
    public function login(string $email, string $senha): bool {
        // Validar entrada
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("Tentativa de login com email inválido: " . $email);
            return false;
        }
        
        if (empty($senha)) {
            error_log("Tentativa de login com senha vazia");
            return false;
        }
        
        try {
            $query = "SELECT id, nome, email, senha_hash 
                      FROM " . $this->table_name . " 
                      WHERE email = :email 
                      LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verificar senha (timing-safe)
                if (password_verify($senha, $row['senha_hash'])) {
                    $this->id = (int)$row['id'];
                    $this->nome = $row['nome'];
                    $this->email = $row['email'];
                    return true;
                } else {
                    // Log de tentativa de login falhada (sem expor detalhes)
                    error_log("Tentativa de login falhada para email: " . $email);
                }
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erro ao fazer login: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se o email já existe no banco
     * @return bool True se existe, False caso contrário
     */
    public function emailExists(): bool {
        if (empty($this->email)) {
            return false;
        }
        
        // Validar email antes de consultar
        $email = filter_var(trim($this->email), FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        try {
            $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Erro ao verificar email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza dados do usuário
     * @return bool True se atualizado com sucesso, False caso contrário
     */
    public function update(): bool {
        if (empty($this->id)) {
            error_log("Tentativa de atualizar usuário sem ID");
            return false;
        }
        
        // Validar e sanitizar dados
        $this->nome = filter_var(trim($this->nome), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $this->nome = mb_substr($this->nome, 0, 100);
        
        $this->email = filter_var(trim($this->email), FILTER_SANITIZE_EMAIL);
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            error_log("Email inválido ao atualizar: " . $this->email);
            return false;
        }
        
        try {
            $query = "UPDATE " . $this->table_name . " 
                      SET nome=:nome, email=:email 
                      WHERE id=:id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":nome", $this->nome);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao atualizar usuário: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza a senha do usuário
     * @param string $nova_senha Nova senha
     * @return bool True se atualizada com sucesso, False caso contrário
     */
    public function updatePassword(string $nova_senha): bool {
        if (empty($this->id)) {
            error_log("Tentativa de atualizar senha sem ID");
            return false;
        }
        
        // Validar senha (mínimo 6 caracteres)
        if (strlen($nova_senha) < 6) {
            error_log("Senha muito curta ao atualizar");
            return false;
        }
        
        try {
            $query = "UPDATE " . $this->table_name . " 
                      SET senha_hash=:senha_hash 
                      WHERE id=:id";
            
            $stmt = $this->conn->prepare($query);
            
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmt->bindParam(":senha_hash", $senha_hash);
            $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao atualizar senha: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se a senha atual está correta
     * @param string $senha_atual Senha a ser verificada
     * @return bool True se correta, False caso contrário
     */
    public function verifyPassword(string $senha_atual): bool {
        if (empty($this->id) || empty($senha_atual)) {
            return false;
        }
        
        try {
            $query = "SELECT senha_hash FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return password_verify($senha_atual, $row['senha_hash']);
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erro ao verificar senha: " . $e->getMessage());
            return false;
        }
    }
}
?>
