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
        
        IMPORTANTE PARA OS TÍTULOS DAS TAREFAS:
        - Use títulos ESPECÍFICOS e DESCRITIVOS do conteúdo sobre {$tema}
        - NÃO use 'Dia X', 'Aula X' ou 'Nível X' nos títulos
        - Use nomes de tópicos REAIS e ESPECÍFICOS relacionados a {$tema}
        - Cada tarefa deve ter um título que descreva exatamente o tópico que será estudado
        - IMPORTANTE: Todos os tópicos devem ser APROPRIADOS para o nível {$nivel}
        
        EXEMPLOS DE TÍTULOS POR NÍVEL:
        - INICIANTE: Conceitos básicos, fundamentos, introdução, primeiros passos
        - INTERMEDIÁRIO: Técnicas avançadas, aplicações práticas, métodos profissionais
        - AVANÇADO: Especialização, domínio, técnicas de especialista, aplicações complexas
        
        Exemplos específicos por tema e nível:
          * COREANO INICIANTE: Alfabeto Hangul, Cumprimentos Básicos, Números de 1 a 10
          * COREANO INTERMEDIÁRIO: Gramática Avançada, Conversação Formal, Leitura de Textos
          * COREANO AVANÇADO: Literatura Coreana, Tradução, Debates e Discussões
          
          * MATEMÁTICA INICIANTE: Operações Básicas, Frações Simples, Geometria Básica
          * MATEMÁTICA INTERMEDIÁRIO: Cálculo Diferencial, Álgebra Linear, Estatística
          * MATEMÁTICA AVANÇADO: Análise Complexa, Topologia, Pesquisa Matemática
          
          * FÍSICA INICIANTE: Mecânica Básica, Leis de Newton, Energia Cinética
          * FÍSICA INTERMEDIÁRIO: Termodinâmica, Eletromagnetismo, Física Quântica
          * FÍSICA AVANÇADO: Relatividade Geral, Física de Partículas, Cosmologia
        
        - NUNCA use títulos genéricos como Aula 1, Dia 1, Introdução
        - TODOS os tópicos devem ser apropriados para o nível {$nivel}
        
        IMPORTANTE PARA OS VÍDEOS:
        - Cada tarefa deve ter vídeos ESPECÍFICOS para o tópico sobre {$tema}
        - NÃO use os mesmos vídeos para todas as tarefas
        - Busque vídeos diferentes para cada tópico específico de {$tema}
        - Use os vídeos fornecidos como base, mas adapte para cada tópico de {$tema}
        
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
                                        'id': 'video_id_especifico_para_este_topico',
                                        'title': 'Título específico do vídeo para este tópico',
                                        'url': 'https://www.youtube.com/watch?v=video_id_especifico'
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
        
        IMPORTANTE: 
        - Crie EXATAMENTE {$totalDias} dias de estudo
        - TODOS os dias devem ser do nível {$nivel}
        - Cada dia deve ter 1-3 tarefas apropriadas para {$nivel}
        - Use títulos ESPECÍFICOS para cada tarefa (não use 'Dia X' ou 'Nível X')
        - Use vídeos DIFERENTES para cada tarefa/tópico
        - Para textos, use títulos de livros, artigos ou recursos educacionais reais
        - Foque em conteúdo educacional de qualidade sobre {$tema} no nível {$nivel}
        - Progressão dentro do nível {$nivel} apenas";

        return $this->makeAPICall($prompt);
    }
    
    public function generateQuiz($tema, $nivel, $conteudo) {
        $prompt = "Crie um quiz com 5 perguntas sobre {$tema} no nível {$nivel}. 
        Baseado no conteúdo: {$conteudo}
        
        Retorne APENAS um JSON válido com a seguinte estrutura:
        {
            'perguntas': [
                {
                    'pergunta': 'Texto da pergunta',
                    'opcoes': ['Opção A', 'Opção B', 'Opção C', 'Opção D'],
                    'resposta_correta': 0
                }
            ]
        }
        
        IMPORTANTE: 
        - Retorne APENAS o JSON, sem texto adicional
        - As perguntas devem ser desafiadoras mas apropriadas para o nível {$nivel}
        - Use resposta_correta como índice (0, 1, 2, ou 3)
        - Certifique-se de que o JSON seja válido";

        return $this->makeAPICall($prompt);
    }
    
    private function makeAPICall($prompt) {
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 2000,
            'temperature' => 0.7
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_key
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AIStudy/1.0');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception('Erro de conexão: ' . $curlError);
        }
        
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
            $contextoTopicos = "\n\nTÓPICOS JÁ GERADOS (NÃO REPETIR):\n" . implode("\n", $topicosAnteriores);
        }
        
        $prompt = "Gere um tópico ESPECÍFICO e ÚNICO para o dia {$dia} de estudo de {$tema} no nível {$nivel}.
        
        IMPORTANTE - O tópico deve ser:
        - ESPECÍFICO do assunto {$tema} (não genérico)
        - APROPRIADO para o nível {$nivel}
        - ÚNICO (não repetir tópicos anteriores)
        - Um tópico REAL e educacional
        - NÃO use 'Fundamentos de', 'Conceitos de', 'Introdução ao'
        - Use nomes de tópicos ESPECÍFICOS do assunto
        - DIFERENTE dos tópicos já gerados
        - ESTRUTURADO como um plano de estudos progressivo
        
        NÍVEIS E SUAS CARACTERÍSTICAS:
        - INICIANTE: Conceitos básicos, fundamentos, primeiros passos, elementos essenciais
        - INTERMEDIÁRIO: Técnicas avançadas, aplicações práticas, métodos profissionais, especialização
        - AVANÇADO: Domínio, pesquisa, inovação, técnicas de especialista, aplicações complexas
        
        ESTRUTURA DE ESTUDOS PROGRESSIVA:
        - Dia 1: Conceitos mais básicos e fundamentais
        - Dia 2: Aplicação prática dos conceitos básicos
        - Dia 3: Técnicas intermediárias
        - Dia 4: Aplicações práticas avançadas
        - Dia 5: Integração e síntese dos conhecimentos
        
        EXEMPLOS DE ESTRUTURA PROGRESSIVA:
        
        BIOLOGIA (INICIANTE):
        - Dia 1: 'Animais Mamíferos'
        - Dia 2: 'Sistema Digestivo dos Mamíferos'
        - Dia 3: 'Reprodução dos Mamíferos'
        - Dia 4: 'Adaptações dos Mamíferos'
        - Dia 5: 'Classificação dos Mamíferos'
        
        MATEMÁTICA (INICIANTE):
        - Dia 1: 'Operações Básicas'
        - Dia 2: 'Frações Simples'
        - Dia 3: 'Decimais Básicos'
        - Dia 4: 'Geometria Plana'
        - Dia 5: 'Problemas de Palavras'
        
        PYTHON (INICIANTE):
        - Dia 1: 'Variáveis e Tipos de Dados'
        - Dia 2: 'Operadores e Expressões'
        - Dia 3: 'Estruturas Condicionais'
        - Dia 4: 'Loops e Repetições'
        - Dia 5: 'Funções Básicas'
        
        {$contextoTopicos}
        
        Para {$tema} no nível {$nivel}, gere um tópico ESPECÍFICO e ÚNICO seguindo a estrutura progressiva.
        Retorne APENAS o nome do tópico, sem explicações.";
        
        return $this->makeAPICall($prompt);
    }
}
?>
