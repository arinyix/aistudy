<?php
// Configuração da API OpenAI
define('OPENAI_API_KEY', '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'); // Substitua pela sua chave da OpenAI
define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');

class OpenAIService {
    private $api_key;
    private $api_url;
    
    public function __construct() {
        $this->api_key = OPENAI_API_KEY;
        $this->api_url = OPENAI_API_URL;
        
        // Verificar se a chave está definida
        if (empty($this->api_key)) {
            throw new Exception('Chave da API OpenAI não definida');
        }
    }
    
    public function generateStudyPlan($tema, $nivel, $tempoDiario, $diasDisponiveis, $horario) {
        // Buscar vídeos educacionais reais do YouTube
        require_once 'classes/YouTubeService.php';
        $youtubeService = new YouTubeService();
        $videos = $youtubeService->getEducationalVideos($tema, $nivel, 3);
        
        // Determinar número de dias baseado no nível
        $diasPorNivel = [
            'iniciante' => 14, // 2 semanas
            'intermediario' => 21, // 3 semanas  
            'avancado' => 28 // 4 semanas
        ];
        $totalDias = $diasPorNivel[$nivel] ?? 14;
        
        // Preparar vídeos disponíveis para o ChatGPT
        $videosDisponiveis = json_encode($videos);
        
        $prompt = "Crie um plano de estudos COMPLETO para aprender {$tema} no nível {$nivel}. 
        O usuário tem {$tempoDiario} minutos por dia, disponível nos dias: " . implode(', ', $diasDisponiveis) . 
        " no horário {$horario}. 
        
        IMPORTANTE: O tema é '{$tema}' - crie conteúdo ESPECÍFICO para este assunto.
        Se o usuário quer aprender COREANO, crie conteúdo sobre COREANO.
        Se o usuário quer aprender MATEMÁTICA, crie conteúdo sobre MATEMÁTICA.
        Se o usuário quer aprender PROGRAMAÇÃO, crie conteúdo sobre PROGRAMAÇÃO.
        
        CRIE EXATAMENTE {$totalDias} DIAS DE ESTUDO TODOS NO NÍVEL {$nivel}:
        - TODOS os dias devem ser apropriados para o nível {$nivel}
        - NÃO misture níveis diferentes
        - Progressão dentro do nível {$nivel} apenas
        - Conteúdo adequado para quem está no nível {$nivel}
        - FOQUE NO TEMA ESPECÍFICO: {$tema}
        
        ⚠️⚠️⚠️ REGRA CRÍTICA ANTI-REPETIÇÃO - LEIA COM ATENÇÃO ⚠️⚠️⚠️
        - CADA DIA DEVE TER TÓPICOS COMPLETAMENTE DIFERENTES E ÚNICOS
        - PROIBIDO TOTALMENTE usar o mesmo tópico em dias diferentes
        - PROIBIDO usar títulos similares ou variações do mesmo tópico
        - CADA tópico deve ser ESPECÍFICO e COMPLETAMENTE DIFERENTE dos outros
        - Use variações, subtópicos e progressão natural para garantir diversidade
        - NÃO repita o mesmo conteúdo em múltiplos dias
        - Cada dia deve ser uma progressão natural do anterior
        - Exemplo PROIBIDO: 'Operações com Matrizes' em Dia 2 e 'Operações com Matrizes' em Dia 3
        - Exemplo PERMITIDO: Dia 2 = 'Multiplicação de Matrizes', Dia 3 = 'Determinantes e Propriedades'
        
        IMPORTANTE PARA OS TÍTULOS DAS TAREFAS:
        - Use títulos ESPECÍFICOS e DESCRITIVOS do conteúdo sobre {$tema}
        - NÃO use 'Dia X', 'Aula X' ou 'Nível X' nos títulos
        - Use nomes de tópicos REAIS e ESPECÍFICOS relacionados a {$tema}
        - Cada tarefa deve ter um título que descreva exatamente o tópico que será estudado
        - IMPORTANTE: Todos os tópicos devem ser APROPRIADOS para o nível {$nivel}
        - Cada tópico DEVE ser ÚNICO e diferente dos tópicos de outros dias
        - NUNCA repita o mesmo título em dias diferentes
        - Use especificidade: em vez de 'Matrizes', use 'Multiplicação de Matrizes' ou 'Determinantes de Matrizes'
        
        EXEMPLOS DE TÍTULOS POR NÍVEL (VARIAÇÃO OBRIGATÓRIA):
        - INICIANTE: Conceitos básicos, fundamentos, introdução, primeiros passos
        - INTERMEDIÁRIO: Técnicas avançadas, aplicações práticas, métodos profissionais
        - AVANÇADO: Especialização, domínio, técnicas de especialista, aplicações complexas
        
        Exemplos específicos por tema e nível (CADA DIA COMPLETAMENTE DIFERENTE):
          * MATEMÁTICA INICIANTE (ÁLGEBRA LINEAR) - EXEMPLO SEM REPETIÇÕES: 
            - Dia 1: 'Introdução às Matrizes e Tipos'
            - Dia 2: 'Adição e Subtração de Matrizes'
            - Dia 3: 'Multiplicação de Matrizes'
            - Dia 4: 'Determinantes de Matrizes 2x2'
            - Dia 5: 'Sistemas de Equações Lineares'
            - Dia 6: 'Gauss-Jordan e Escalonamento'
            - Dia 7: 'Matrizes Inversas'
            - Dia 8: 'Aplicações Práticas de Matrizes'
          
          * COREANO INICIANTE: 
            - Dia 1: 'Alfabeto Hangul - Vogais Básicas'
            - Dia 2: 'Alfabeto Hangul - Consoantes Básicas'
            - Dia 3: 'Formação de Sílabas em Hangul'
            - Dia 4: 'Cumprimentos e Saudações Básicas'
            - Dia 5: 'Números Coreanos de 1 a 20'
            - Dia 6: 'Pronomes Pessoais e Apresentação'
            - Dia 7: 'Vocabulário da Família'
            - Dia 8: 'Partículas Sujeito 이/가'
          
          * PYTHON INICIANTE: 
            - Dia 1: 'Instalação e Primeiro Programa'
            - Dia 2: 'Variáveis e Tipos de Dados Básicos'
            - Dia 3: 'Operadores Aritméticos e Atribuição'
            - Dia 4: 'Entrada de Dados com input()'
            - Dia 5: 'Estruturas Condicionais if/else'
            - Dia 6: 'Loops for com range()'
            - Dia 7: 'Loops while e Interrupção'
            - Dia 8: 'Funções Básicas com def'
        
        - NUNCA use títulos genéricos como Aula 1, Dia 1, Introdução
        - NUNCA repita o mesmo tópico em dias diferentes
        - TODOS os tópicos devem ser apropriados para o nível {$nivel}
        - TODOS os tópicos devem ser ÚNICOS e COMPLETAMENTE DIFERENTES
        - Use ESPECIFICIDADE - seja específico, não genérico
        
        IMPORTANTE PARA OS VÍDEOS - LEIA COM ATENÇÃO:
        - Você recebeu uma lista de vídeos reais do YouTube em JSON
        - Use SOMENTE esses vídeos reais na resposta
        - NÃO invente IDs de vídeo
        - NÃO use IDs genéricos como 'video_id_especifico_para_este_topico'
        - Use os dados EXATOS dos vídeos fornecidos
        - Vídeos disponíveis: {$videosDisponiveis}
        - Para cada tarefa, distribua os vídeos entre os dias
        - Use até 3 vídeos por tarefa
        - Se houver poucos vídeos, use cada vídeo em múltiplas tarefas se necessário
        - NÃO crie IDs falsos, use os IDs REAIS dos vídeos fornecidos
        
        Retorne um JSON com a seguinte estrutura:
        {
            'titulo': 'Aprender {$tema} - Nível {$nivel}',
            'descricao': 'Plano de {$totalDias} dias para {$tema} no nível {$nivel}',
            'dias': [
                {
                    'dia': 1,
                    'tarefas': [
                        {
                            'titulo': 'Título específico do tópico (ex: Variáveis e Tipos de Dados)',
                            'descricao': 'Descrição detalhada do que será estudado',
                            'material': {
                                'videos': [
                                    {
                                        'id': 'ID_REAL_DO_VIDEO_AQUI',
                                        'title': 'TÍTULO_REAL_DO_VIDEO_AQUI',
                                        'description': 'Descrição real do vídeo',
                                        'thumbnail': 'URL_DA_THUMBNAIL_REAL',
                                        'channel': 'Nome do canal real',
                                        'url': 'https://www.youtube.com/watch?v=ID_REAL_DO_VIDEO_AQUI'
                                    }
                                ],
                                'textos': ['Livro: Nome do Livro - Capítulo 1', 'Artigo: Título do Artigo'],
                                'exercicios': ['Exercício 1: Descrição', 'Exercício 2: Descrição']
                            }
                        }
                    ]
                }
            ]
        }
        
        ⚠️⚠️⚠️ IMPORTANTE FINAL - REGRAS OBRIGATÓRIAS ⚠️⚠️⚠️: 
        - Crie EXATAMENTE {$totalDias} dias de estudo
        - TODOS os dias devem ser do nível {$nivel}
        - Cada dia deve ter 1-3 tarefas apropriadas para {$nivel}
        - Use títulos ESPECÍFICOS e ÚNICOS para cada tarefa (não use 'Dia X' ou 'Nível X')
        - Use vídeos REAIS da lista fornecida - NÃO invente IDs
        - Para textos, use títulos de livros, artigos ou recursos educacionais reais
        - Foque em conteúdo educacional de qualidade sobre {$tema} no nível {$nivel}
        - Progressão dentro do nível {$nivel} apenas
        - *** CRÍTICO: NÃO REPITA TÓPICOS - cada dia deve ser ÚNICO ***
        - *** CADA TÓPICO DEVE SER DIFERENTE DOS OUTROS TÓPICOS ***
        - *** USE ESPECIFICIDADE - Seja ESPECÍFICO nos títulos, não genérico ***
        - *** USE OS VÍDEOS REAIS FORNECIDOS - NÃO INVENTE IDs ***";

        return $this->makeAPICall($prompt, 4000);
    }
    
    public function generateSummaryPDF($topico, $nivel, $descricao) {
        $prompt = "Você é um professor experiente e renomado. Crie um resumo auxiliar EXTREMAMENTE DETALHADO e COMPLETO sobre o tópico: {$topico}
        
        CONTEXTO:
        - Tópico: {$topico}
        - Nível: {$nivel}
        - Descrição: {$descricao}
        
        FORMATO DE SAÍDA:
        Você DEVE retornar APENAS código Markdown formatado para gerar um PDF bonito e bem estruturado.
        
        ESTRUTURA OBRIGATÓRIA (SEJA DETALHADO EM CADA SEÇÃO):
        1. # TÍTULO PRINCIPAL (formatação: # Nome do Tópico)
        2. ## INTRODUÇÃO - Contextualize profundamente o tópico (mínimo 3-4 parágrafos)
        3. ## CONCEITOS FUNDAMENTAIS - Explique TODOS os conceitos principais de forma MUITO detalhada:
           - Liste e explique cada conceito importante
           - Use subtítulos (###) para cada conceito
           - Dê exemplos para cada conceito explicado
           - Mínimo 5 conceitos fundamentais
        4. ## EXEMPLOS PRÁTICOS - Dê exemplos concretos e aplicações reais:
           - Mínimo 3 exemplos detalhados
           - Mostre passo a passo quando aplicável
           - Use code blocks ou listas numeradas para passos
        5. ## EXERCÍCIOS PRÁTICOS - Crie EXATAMENTE 15 exercícios variados:
           - 5 exercícios de múltipla escolha (cada um com explicação das alternativas)
           - 4 exercícios de preenchimento de lacunas
           - 3 exercícios de verdadeiro/falso com explicação detalhada
           - 3 exercícios práticos/criativos
        6. ## GABARITO - Respostas de TODOS os exercícios:
           - Explique o porquê de cada resposta correta
           - Para múltipla escolha, explique por que as outras estão erradas
           - Para verdadeiro/falso, explique detalhadamente cada item
        7. ## DICAS DE ESTUDO - Mínimo 8 dicas práticas e úteis
        8. ## CONCLUSÃO - Síntese final abrangente (mínimo 2 parágrafos)
        
        REGRAS CRÍTICAS:
        - Use Markdown PROFISSIONALMENTE (# para títulos principais, ## para seções, ### para subtópicos, **para negrito**, *para itálico*)
        - Seja EXTREMAMENTE detalhado e didático - este é um material de estudo completo
        - Ajuste o nível de complexidade conforme '{$nivel}'
        - Use emojis ESPARSAMENTE quando apropriado (não exagere)
        - Mantenha o conteúdo 100% focado no tópico '{$topico}'
        - Seja específico, NUNCA genérico
        - Quanto MAIS DETALHADO, MELHOR
        - Use listas, tabelas, code blocks quando apropriado
        - O objetivo é criar um material que o aluno possa estudar completamente sobre o tópico
        
        RETORNE APENAS O MARKDOWN, SEM TEXTO ADICIONAL ANTES OU DEPOIS.";
        
        return $this->makeAPICall($prompt, 8000);
    }
    
    private function makeAPICall($prompt, $maxTokens = 2000) {
        $data = [
            'model' => 'gpt-4o-mini', // Modelo mais rápido e barato
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => $maxTokens,
            'temperature' => 0.7
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_key
        ];
        
        error_log("=== INICIANDO CHAMADA API ===");
        error_log("URL: " . $this->api_url);
        error_log("Model: " . $data['model']);
        error_log("Max Tokens: " . $maxTokens);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutos para requisições longas (resumos)
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); // 30 segundos para conectar
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AIStudy/1.0');
        
        error_log("Enviando requisição...");
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        error_log("HTTP Code: " . $httpCode);
        
        if ($curlError) {
            error_log("Erro cURL: " . $curlError);
            throw new Exception('Erro de conexão: ' . $curlError);
        }
        
        error_log("Resposta recebida (primeiros 200 chars): " . substr($response, 0, 200));
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Erro ao decodificar JSON da API: ' . json_last_error_msg());
            }
            
            if (isset($result['choices'][0]['message']['content'])) {
                return $result['choices'][0]['message']['content'];
            } else {
                throw new Exception('Resposta inválida da API: ' . $response);
            }
        } else {
            $errorData = json_decode($response, true);
            $errorMessage = isset($errorData['error']['message']) ? $errorData['error']['message'] : $response;
            throw new Exception('Erro na API OpenAI (HTTP ' . $httpCode . '): ' . $errorMessage);
        }
    }
    
    public function generateSpecificTopic($tema, $nivel, $dia, $topicosAnteriores = []) {
        // Construir contexto de tópicos já gerados para evitar repetições
        $contextoTopicos = '';
        if (!empty($topicosAnteriores)) {
            $contextoTopicos = "\n\n⚠️ LISTA COMPLETA DE TÓPICOS JÁ GERADOS (NUNCA REPETIR ESTES):\n";
            foreach ($topicosAnteriores as $index => $topico) {
                $contextoTopicos .= ($index + 1) . ". " . $topico . "\n";
            }
            $contextoTopicos .= "\nIMPORTANTE: O novo tópico DEVE ser COMPLETAMENTE DIFERENTE de todos esses tópicos acima.";
        }
        
        $prompt = "Você está gerando o tópico do DIA {$dia} de um plano de estudos para aprender {$tema} no nível {$nivel}.
        
        REGRAS CRÍTICAS:
        1. O tópico deve ser ESPECÍFICO do assunto '{$tema}'
        2. O tópico deve ser APROPRIADO para o nível '{$nivel}'
        3. O tópico DEVE ser COMPLETAMENTE ÚNICO e DIFERENTE de todos os tópicos já gerados
        4. NUNCA repita ou use variações similares de tópicos anteriores
        5. Use nomes de tópicos ESPECÍFICOS e REAIS do assunto
        
        NÍVEIS:
        - INICIANTE: Conceitos básicos, fundamentos, primeiros passos, elementos essenciais
        - INTERMEDIÁRIO: Técnicas avançadas, aplicações práticas, métodos profissionais, especialização
        - AVANÇADO: Domínio, pesquisa, inovação, técnicas de especialista, aplicações complexas
        
        ESTRUTURA PROGRESSIVA:
        - Dia 1: Conceitos mais básicos e fundamentais
        - Dia 2: Aplicação prática dos conceitos básicos
        - Dia 3: Técnicas intermediárias
        - Dia 4: Aplicações práticas avançadas
        - Dia 5: Integração e síntese dos conhecimentos
        
        EXEMPLOS DE TÓPICOS ÚNICOS POR TEMA:
        
        BIOLOGIA (INICIANTE) - CADA TÓPICO DIFERENTE:
        - Dia 1: 'Células Procarióticas e Eucarióticas'
        - Dia 2: 'Membrana Celular e Transporte'
        - Dia 3: 'Organelas Celulares'
        - Dia 4: 'Divisão Celular - Mitose'
        - Dia 5: 'Divisão Celular - Meiose'
        
        MATEMÁTICA (INICIANTE) - CADA TÓPICO DIFERENTE:
        - Dia 1: 'Operações com Números Naturais'
        - Dia 2: 'Frações e Números Decimais'
        - Dia 3: 'Geometria - Ângulos e Polígonos'
        - Dia 4: 'Proporção e Regra de Três'
        - Dia 5: 'Sistema de Unidades de Medida'
        
        PYTHON (INICIANTE) - CADA TÓPICO DIFERENTE:
        - Dia 1: 'Sintaxe Básica do Python'
        - Dia 2: 'Tipos de Dados e Variáveis'
        - Dia 3: 'Operadores Aritméticos e Lógicos'
        - Dia 4: 'Estruturas de Decisão if/elif/else'
        - Dia 5: 'Loops for e while'
        
        COREANO (INICIANTE) - CADA TÓPICO DIFERENTE:
        - Dia 1: 'Alfabeto Hangul - Vogais e Consoantes Básicas'
        - Dia 2: 'Cumprimentos e Saudações Básicas'
        - Dia 3: 'Números Coreanos de 1 a 20'
        - Dia 4: 'Apresentação Pessoal e Pronomes'
        - Dia 5: 'Vocabulário da Família'
        
        {$contextoTopicos}
        
        Para '{$tema}' no nível '{$nivel}', gere um tópico ESPECÍFICO, ÚNICO e DIFERENTE de todos os tópicos anteriores.
        Retorne APENAS o nome do tópico, sem explicações, sem prefixos como 'Dia X:', sem aspas.";
        
        return $this->makeAPICall($prompt);
    }
}
?>
