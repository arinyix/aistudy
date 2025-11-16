<?php
require_once dirname(__DIR__) . '/config/database.php';

class PlanService {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Obtém todos os planos disponíveis
     * @return array
     */
    public function getAllPlans() {
        $query = "SELECT * FROM planos WHERE ativo = 1 ORDER BY preco_mensal ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $planos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decodificar recursos JSON
        foreach ($planos as &$plano) {
            $plano['recursos'] = json_decode($plano['recursos'], true) ?? [];
        }
        
        return $planos;
    }
    
    /**
     * Obtém um plano por slug
     * @param string $slug Slug do plano
     * @return array|null
     */
    public function getPlanBySlug($slug) {
        $query = "SELECT * FROM planos WHERE slug = :slug AND ativo = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':slug', $slug);
        $stmt->execute();
        
        $plano = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($plano) {
            $plano['recursos'] = json_decode($plano['recursos'], true) ?? [];
        }
        
        return $plano ?: null;
    }
    
    /**
     * Obtém um plano por ID
     * @param int $id ID do plano
     * @return array|null
     */
    public function getPlanById($id) {
        $query = "SELECT * FROM planos WHERE id = :id AND ativo = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $plano = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($plano) {
            $plano['recursos'] = json_decode($plano['recursos'], true) ?? [];
        }
        
        return $plano ?: null;
    }
    
    /**
     * Cria uma nova assinatura
     * @param int $user_id ID do usuário
     * @param int $plano_id ID do plano
     * @param string $gateway Gateway de pagamento
     * @param string $external_id ID externo da assinatura
     * @param array $dadosPagamento Dados adicionais do pagamento
     * @return int|false ID da assinatura ou false em caso de erro
     */
    public function createSubscription($user_id, $plano_id, $gateway, $external_id = null, $dadosPagamento = []) {
        $query = "INSERT INTO assinaturas 
                  (user_id, plano_id, gateway, status, data_inicio, external_id, dados_pagamento)
                  VALUES 
                  (:user_id, :plano_id, :gateway, 'pendente', NOW(), :external_id, :dados_pagamento)";
        
        $stmt = $this->conn->prepare($query);
        
        $dadosPagamentoJson = json_encode($dadosPagamento);
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':plano_id', $plano_id);
        $stmt->bindParam(':gateway', $gateway);
        $stmt->bindParam(':external_id', $external_id);
        $stmt->bindParam(':dados_pagamento', $dadosPagamentoJson);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Atualiza o status de uma assinatura
     * @param int $assinatura_id ID da assinatura
     * @param string $status Novo status
     * @param string|null $data_fim Data de fim (opcional)
     * @return bool
     */
    public function updateSubscriptionStatus($assinatura_id, $status, $data_fim = null) {
        $query = "UPDATE assinaturas 
                  SET status = :status, data_fim = :data_fim, updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->bindParam(':id', $assinatura_id);
        
        return $stmt->execute();
    }
    
    /**
     * Atualiza assinatura por external_id
     * @param string $external_id ID externo
     * @param string $status Novo status
     * @param string|null $data_fim Data de fim
     * @return bool
     */
    public function updateSubscriptionByExternalId($external_id, $status, $data_fim = null) {
        $query = "UPDATE assinaturas 
                  SET status = :status, data_fim = :data_fim, updated_at = NOW()
                  WHERE external_id = :external_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->bindParam(':external_id', $external_id);
        
        return $stmt->execute();
    }
    
    /**
     * Obtém assinatura ativa do usuário
     * @param int $user_id ID do usuário
     * @return array|null
     */
    public function getActiveSubscription($user_id) {
        $query = "SELECT a.*, p.nome as plano_nome, p.slug as plano_slug, p.preco_mensal
                  FROM assinaturas a
                  JOIN planos p ON a.plano_id = p.id
                  WHERE a.user_id = :user_id 
                  AND a.status = 'ativo'
                  AND (a.data_fim IS NULL OR a.data_fim > NOW())
                  ORDER BY a.created_at DESC
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

