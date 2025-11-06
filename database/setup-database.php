<?php
// Script para configurar o banco de dados completo
require_once '../config/database.php';

echo "<h2>🚀 Configurando Banco de Dados AIStudy</h2>";

try {
    // Conectar ao MySQL (sem especificar banco)
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<strong>✅ Conectado ao MySQL</strong><br><br>";
    
    // 1. Limpar e criar banco
    echo "<h3>🧹 Limpando banco existente...</h3>";
    $pdo->exec("DROP DATABASE IF EXISTS aistudy");
    $pdo->exec("CREATE DATABASE aistudy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Banco limpo e recriado<br><br>";
    
    // 2. Executar schema
    echo "<h3>📋 Executando schema...</h3>";
    $schema = file_get_contents('schema.sql');
    $statements = explode(';', $schema);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !preg_match('/^(USE|--)/', $statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Ignorar erros de DELIMITER e triggers por enquanto
                if (!strpos($e->getMessage(), 'DELIMITER') && !strpos($e->getMessage(), 'TRIGGER')) {
                    echo "⚠️ Aviso: " . $e->getMessage() . "<br>";
                }
            }
        }
    }
    echo "✅ Schema executado<br><br>";
    
    // 3. Executar seed
    echo "<h3>🌱 Executando seed...</h3>";
    $seed = file_get_contents('seed.sql');
    $statements = explode(';', $seed);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !preg_match('/^(USE|--)/', $statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                echo "⚠️ Aviso: " . $e->getMessage() . "<br>";
            }
        }
    }
    echo "✅ Seed executado<br><br>";
    
    // 4. Verificar estrutura
    echo "<h3>🔍 Verificando estrutura...</h3>";
    $pdo->exec("USE aistudy");
    
    $tables = ['users', 'routines', 'tasks', 'daily_progress', 'user_preferences', 'activity_logs', 'user_stats', 'study_materials'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabela <strong>{$table}</strong>: OK<br>";
        } else {
            echo "❌ Tabela <strong>{$table}</strong>: FALTANDO<br>";
        }
    }
    
    echo "<br>";
    
    // 5. Verificar dados
    echo "<h3>📊 Verificando dados...</h3>";
    $queries = [
        'Usuários' => 'SELECT COUNT(*) as total FROM users',
        'Rotinas' => 'SELECT COUNT(*) as total FROM routines',
        'Tasks' => 'SELECT COUNT(*) as total FROM tasks',
        'Progresso Diário' => 'SELECT COUNT(*) as total FROM daily_progress'
    ];
    
    foreach ($queries as $name => $query) {
        $stmt = $pdo->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "📈 <strong>{$name}:</strong> " . $result['total'] . " registros<br>";
    }
    
    echo "<br><h3>🎉 Banco de dados configurado com sucesso!</h3>";
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Acesse <a href='../login.php'>login.php</a> para testar</li>";
    echo "<li>✅ Use as credenciais: joao@email.com / senha</li>";
    echo "<li>✅ Teste a criação de rotinas</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<strong>❌ Erro:</strong> " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}

echo "<br><a href='../criar-rotina.php'>Testar Criação de Rotina</a>";
?>
