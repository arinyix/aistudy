<?php
/**
 * Configuração do banco de dados
 * 
 * IMPORTANTE: Em produção, use apenas variáveis de ambiente (.env)
 * Não deixe credenciais hardcoded no código.
 */

// Carregar variáveis de ambiente se disponível
require_once __DIR__ . '/env-loader.php';

// Usar variáveis de ambiente ou valores padrão (apenas para desenvolvimento)
define('DB_HOST', defined('DB_HOST') ? DB_HOST : 'localhost');
define('DB_NAME', defined('DB_NAME') ? DB_NAME : 'aistudy');
define('DB_USER', defined('DB_USER') ? DB_USER : 'root');
define('DB_PASS', defined('DB_PASS') ? DB_PASS : '');

// Modo debug (desabilitar em produção)
define('DEBUG', defined('DEBUG') ? DEBUG : false);

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false, // Segurança adicional
                ]
            );
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            // Log do erro (sempre)
            error_log("Erro de conexão com banco de dados: " . $exception->getMessage());
            error_log("Host: " . $this->host . ", Database: " . $this->db_name);
            
            // Mensagem ao usuário (diferente em dev/prod)
            if (DEBUG) {
                // Em desenvolvimento, mostrar erro detalhado
                die("Erro de conexão: " . htmlspecialchars($exception->getMessage()));
            } else {
                // Em produção, mensagem genérica
                die("Erro ao conectar ao banco de dados. Tente novamente mais tarde.");
            }
        }
        
        return $this->conn;
    }
}
?>
