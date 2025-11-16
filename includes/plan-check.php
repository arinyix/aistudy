<?php
/**
 * Funções para verificação de planos e acesso
 */

require_once dirname(__DIR__) . '/config/database.php';

/**
 * Verifica se o usuário tem acesso a um recurso específico
 * @param int $user_id ID do usuário
 * @param string $recurso Recurso a verificar ('modo_enem', 'modo_concurso', etc.)
 * @return bool
 */
function hasPlanAccess($user_id, $recurso) {
    // Se for rotina geral, sempre permitir
    if ($recurso === 'rotinas_gerais') {
        return true;
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Buscar assinatura ativa do usuário
        $query = "SELECT p.recursos, a.status, a.data_fim 
                  FROM assinaturas a
                  JOIN planos p ON a.plano_id = p.id
                  WHERE a.user_id = :user_id 
                  AND a.status = 'ativo'
                  AND (a.data_fim IS NULL OR a.data_fim > NOW())
                  ORDER BY a.created_at DESC
                  LIMIT 1";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $assinatura = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$assinatura) {
            // Usuário sem assinatura ativa - verificar se tem plano Free
            return hasFreePlanAccess($recurso);
        }
        
        // Decodificar recursos do plano
        $recursos = json_decode($assinatura['recursos'], true) ?? [];
        
        // Verificar se o recurso está disponível no plano
        return in_array($recurso, $recursos);
        
    } catch (Exception $e) {
        error_log("Erro ao verificar acesso ao plano: " . $e->getMessage());
        // Em caso de erro, permitir acesso (fail-open para não bloquear usuários)
        return true;
    }
}

/**
 * Verifica acesso do plano Free
 * @param string $recurso Recurso a verificar
 * @return bool
 */
function hasFreePlanAccess($recurso) {
    // Plano Free só permite rotinas gerais
    return $recurso === 'rotinas_gerais';
}

/**
 * Obtém o plano ativo do usuário
 * @param int $user_id ID do usuário
 * @return array|null Dados do plano ou null se não tiver plano ativo
 */
function getUserActivePlan($user_id) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT p.*, a.status, a.data_inicio, a.data_fim
                  FROM assinaturas a
                  JOIN planos p ON a.plano_id = p.id
                  WHERE a.user_id = :user_id 
                  AND a.status = 'ativo'
                  AND (a.data_fim IS NULL OR a.data_fim > NOW())
                  ORDER BY a.created_at DESC
                  LIMIT 1";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $plano = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($plano) {
            $plano['recursos'] = json_decode($plano['recursos'], true) ?? [];
        }
        
        return $plano ?: null;
        
    } catch (Exception $e) {
        error_log("Erro ao obter plano do usuário: " . $e->getMessage());
        return null;
    }
}

/**
 * Verifica se o usuário tem plano ativo (qualquer plano)
 * @param int $user_id ID do usuário
 * @return bool
 */
function hasActivePlan($user_id) {
    $plano = getUserActivePlan($user_id);
    return $plano !== null;
}

