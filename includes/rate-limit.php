<?php
/**
 * Sistema de Rate Limiting
 * 
 * Previne abuso de requisições (brute force, spam, etc.)
 * 
 * Uso:
 * if (!checkRateLimit('login', $_SERVER['REMOTE_ADDR'], 5, 60)) {
 *     die('Muitas tentativas. Tente novamente em alguns minutos.');
 * }
 */

/**
 * Verifica se o limite de requisições foi excedido
 * 
 * @param string $action Ação sendo limitada (ex: 'login', 'register', 'api')
 * @param string $identifier Identificador único (IP, user_id, etc.)
 * @param int $maxAttempts Número máximo de tentativas permitidas
 * @param int $timeWindow Janela de tempo em segundos
 * @return bool True se permitido, False se excedido
 */
function checkRateLimit(string $action, string $identifier, int $maxAttempts = 5, int $timeWindow = 60): bool {
    // Criar chave única para esta ação + identificador
    $key = "rate_limit_{$action}_{$identifier}";
    
    // Inicializar array de tentativas na sessão se não existir
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = [];
    }
    
    // Limpar tentativas antigas (fora da janela de tempo)
    $currentTime = time();
    if (isset($_SESSION['rate_limits'][$key])) {
        $_SESSION['rate_limits'][$key] = array_filter(
            $_SESSION['rate_limits'][$key],
            function($timestamp) use ($currentTime, $timeWindow) {
                return ($currentTime - $timestamp) < $timeWindow;
            }
        );
    } else {
        $_SESSION['rate_limits'][$key] = [];
    }
    
    // Contar tentativas atuais
    $attempts = count($_SESSION['rate_limits'][$key]);
    
    // Se excedeu o limite, retornar false
    if ($attempts >= $maxAttempts) {
        error_log("Rate limit excedido: {$action} para {$identifier} ({$attempts} tentativas em {$timeWindow}s)");
        return false;
    }
    
    // Registrar esta tentativa
    $_SESSION['rate_limits'][$key][] = $currentTime;
    
    return true;
}

/**
 * Obtém o tempo restante até poder tentar novamente
 * 
 * @param string $action Ação sendo limitada
 * @param string $identifier Identificador único
 * @param int $timeWindow Janela de tempo em segundos
 * @return int Segundos restantes (0 se não estiver bloqueado)
 */
function getRateLimitRemainingTime(string $action, string $identifier, int $timeWindow = 60): int {
    $key = "rate_limit_{$action}_{$identifier}";
    
    if (!isset($_SESSION['rate_limits'][$key]) || empty($_SESSION['rate_limits'][$key])) {
        return 0;
    }
    
    $currentTime = time();
    $oldestAttempt = min($_SESSION['rate_limits'][$key]);
    $elapsed = $currentTime - $oldestAttempt;
    
    return max(0, $timeWindow - $elapsed);
}

/**
 * Limpa tentativas de rate limiting para uma ação específica
 * 
 * @param string $action Ação a limpar
 * @param string|null $identifier Identificador (null para limpar todos)
 */
function clearRateLimit(string $action, ?string $identifier = null): void {
    if ($identifier === null) {
        // Limpar todas as tentativas desta ação
        foreach ($_SESSION['rate_limits'] ?? [] as $key => $value) {
            if (strpos($key, "rate_limit_{$action}_") === 0) {
                unset($_SESSION['rate_limits'][$key]);
            }
        }
    } else {
        $key = "rate_limit_{$action}_{$identifier}";
        unset($_SESSION['rate_limits'][$key]);
    }
}

