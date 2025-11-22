<?php
/**
 * Proteção CSRF (Cross-Site Request Forgery)
 * 
 * Uso:
 * 1. Gerar token: $token = generateCSRFToken();
 * 2. Incluir no formulário: <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
 * 3. Validar no processamento: if (!validateCSRFToken($_POST['csrf_token'])) { die('Token inválido'); }
 */

/**
 * Gera um token CSRF e armazena na sessão
 * @return string Token CSRF
 */
function generateCSRFToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida um token CSRF
 * @param string|null $token Token a ser validado
 * @return bool True se válido, False caso contrário
 */
function validateCSRFToken(?string $token): bool {
    if (empty($token) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    // Comparação segura (timing-safe)
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Regenera o token CSRF (útil após uso)
 */
function regenerateCSRFToken(): void {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Obtém o token CSRF atual sem regenerar
 * @return string|null Token CSRF ou null se não existir
 */
function getCSRFToken(): ?string {
    return $_SESSION['csrf_token'] ?? null;
}

