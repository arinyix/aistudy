<?php
/**
 * Carregador de variáveis de ambiente do arquivo .env
 * 
 * Este arquivo carrega as variáveis de ambiente do arquivo .env
 * e as disponibiliza como constantes PHP.
 */

if (!function_exists('loadEnv')) {
    function loadEnv($envFile = '.env') {
        $envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . $envFile;
        
        if (!file_exists($envPath)) {
            error_log("Arquivo .env não encontrado em: {$envPath}");
            return false;
        }
        
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Ignorar comentários
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Separar chave e valor
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remover aspas se existirem
                $value = trim($value, '"\'');
                
                // Definir como constante se não existir
                if (!defined($key)) {
                    define($key, $value);
                }
            }
        }
        
        return true;
    }
}

// Carregar .env automaticamente quando este arquivo for incluído
loadEnv();

