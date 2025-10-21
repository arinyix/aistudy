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
        
        CRIE EXATAMENTE {$totalDias} DIAS DE ESTUDO TODOS NO NÍVEL {$nivel}:
        - TODOS os dias devem ser apropriados para o nível {$nivel}
        - NÃO misture níveis diferentes
        - Progressão dentro do nível {$nivel} apenas
        - Conteúdo adequado para quem está no nível {$nivel}
        
        Retorne um JSON com a seguinte estrutura:
        {
            'titulo': 'Aprender {$tema} - Nível {$nivel}',
            'descricao': 'Plano de {$totalDias} dias para {$tema} no nível {$nivel}',
            'dias': [
                {
                    'dia': 1,
                    'tarefas': [
                        {
                            'titulo': 'Título da tarefa',
                            'descricao': 'Descrição detalhada',
                            'material': {
                                'videos': " . json_encode($videos) . ",
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
        - Use os vídeos fornecidos acima que são reais e educacionais
        - Para textos, use títulos de livros, artigos ou recursos educacionais reais
        - Foque em conteúdo educacional de qualidade sobre {$tema} no nível {$nivel}
        - Use vídeos diferentes para cada dia/tarefa
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
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception('Erro de conexão: ' . $curlError);
        }
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
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
}
?>
