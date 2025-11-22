<?php
// Carregar variáveis de ambiente do arquivo .env
require_once __DIR__ . '/env-loader.php';

// Configuração da API OpenAI
// Se não estiver definido no .env, usar valores padrão (para compatibilidade)
if (!defined('OPENAI_API_KEY')) {
    define('OPENAI_API_KEY', '');
}
if (!defined('OPENAI_API_URL')) {
    define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');
}

class OpenAIService {
    private $api_key;
    private $api_url;
    
    public function __construct() {
        $this->api_key = OPENAI_API_KEY;
        $this->api_url = OPENAI_API_URL;
        
        // Verificar se a chave está definida
        if (empty($this->api_key) || $this->api_key === '' || strpos($this->api_key, 'sua-chave') !== false) {
            throw new Exception('Chave da API OpenAI não definida. Por favor, configure OPENAI_API_KEY no arquivo .env');
        }
        
        // Verificar se a URL está definida e é válida
        if (empty($this->api_url) || !filter_var($this->api_url, FILTER_VALIDATE_URL)) {
            throw new Exception('URL da API OpenAI inválida. Verifique OPENAI_API_URL no arquivo .env');
        }
        
        // Verificar se cURL está disponível
        if (!function_exists('curl_init')) {
            throw new Exception('Extensão cURL não está habilitada. Habilite a extensão curl no PHP.');
        }
    }
    
    public function generateStudyPlan($tema, $nivel, $tempoDiario, $diasDisponiveis, $horario, $numeroDias = null) {
        // Buscar vídeos educacionais reais do YouTube
        require_once 'classes/YouTubeService.php';
        $youtubeService = new YouTubeService();
        $videos = $youtubeService->getEducationalVideos($tema, $nivel, 3);
        
        // Usar número de dias fornecido ou calcular baseado no nível
        if ($numeroDias !== null && $numeroDias > 0) {
            $totalDias = (int)$numeroDias;
        } else {
            // Fallback: determinar número de dias baseado no nível
            $diasPorNivel = [
                'iniciante' => 14, // 2 semanas
                'intermediario' => 21, // 3 semanas  
                'avancado' => 28 // 4 semanas
            ];
            $totalDias = $diasPorNivel[$nivel] ?? 14;
        }
        
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
        - *** USE OS VÍDEOS REAIS FORNECIDOS - NÃO INVENTE IDs ***
        
        🔴🔴🔴 FORMATO DE RESPOSTA CRÍTICO 🔴🔴🔴:
        - Retorne APENAS o JSON válido, SEM texto adicional antes ou depois
        - NÃO use markdown code blocks (```json ou ```)
        - NÃO adicione explicações, comentários ou texto antes do JSON
        - NÃO adicione texto depois do JSON
        - O JSON deve começar com chave de abertura e terminar com chave de fechamento
        - Retorne APENAS o objeto JSON, nada mais, nada menos
        - Exemplo CORRETO: Um objeto JSON válido começando com chave de abertura
        - Exemplo INCORRETO: Adicionar texto antes ou depois do JSON, ou usar markdown";

        // Aumentar tokens baseado no número de dias (mais dias = mais tokens necessários)
        // Estimativa: ~150-200 tokens por dia (considerando tarefas, descrições, materiais)
        // Mínimo 4000, máximo 16000 (limite do modelo)
        $maxTokens = min(16000, max(4000, $totalDias * 150 + 3000));
        
        // Log para debug
        error_log("generateStudyPlan: totalDias={$totalDias}, maxTokens={$maxTokens}");
        
        return $this->makeAPICall($prompt, $maxTokens);
    }
    
    public function generateEnemPlan($dadosEnem) {
        // Extrair dados do contexto ENEM
        $anoEnem = $dadosEnem['ano_enem'] ?? date('Y') + 1;
        $notaAlvo = $dadosEnem['nota_alvo'] ?? '700+';
        $areasPrioritarias = $dadosEnem['areas_prioritarias'] ?? [];
        $nivel = $dadosEnem['nivel'] ?? 'intermediario';
        $tempoDiario = $dadosEnem['tempo_diario'] ?? 120;
        $diasDisponiveis = $dadosEnem['dias_disponiveis'] ?? [];
        $horario = $dadosEnem['horario_disponivel'] ?? '09:00';
        $dificuldades = $dadosEnem['dificuldades'] ?? '';
        $disciplinasEnem = trim($dadosEnem['disciplinas_enem'] ?? '');
        $pesosDisciplinas = trim($dadosEnem['pesos_disciplinas'] ?? '');
        $dataProva = $dadosEnem['data_prova'] ?? '';
        $ritmoSimulados = $dadosEnem['ritmo_simulados'] ?? 'nenhum';
        $numeroDias = $dadosEnem['numero_dias'] ?? null;
        
        // Usar número de dias fornecido ou calcular baseado no nível
        if ($numeroDias !== null && $numeroDias > 0) {
            $totalDias = (int)$numeroDias;
        } else {
            // Fallback: determinar número de dias baseado no nível
            $diasPorNivel = [
                'iniciante' => 90, // 3 meses
                'intermediario' => 120, // 4 meses
                'avancado' => 60 // 2 meses (revisão)
            ];
            $totalDias = $diasPorNivel[$nivel] ?? 120;
        }
        
        $areasTexto = !empty($areasPrioritarias) ? implode(', ', $areasPrioritarias) : 'Todas as áreas';
        
        $extras = [];
        if ($disciplinasEnem !== '') { $extras[] = "Disciplinas por área (texto): {$disciplinasEnem}"; }
        if ($pesosDisciplinas !== '') { $extras[] = "Pesos por disciplina (0-5): {$pesosDisciplinas}"; }
        if ($dataProva !== '') { $extras[] = "Data prevista da prova: {$dataProva}"; }
        if ($ritmoSimulados !== 'nenhum') { $extras[] = "Ritmo de simulados: {$ritmoSimulados}"; }
        $extrasTexto = !empty($extras) ? ("\n\nInformações adicionais:\n- " . implode("\n- ", $extras)) : '';
        
        $prompt = "Você é um planejador de estudos especializado em ENEM.\n\nCrie um PLANO DE ESTUDOS COMPLETO em formato JSON estruturado, para um aluno com as seguintes informações:\n\n- Ano do ENEM: {$anoEnem}\n- Nota alvo aproximada: {$notaAlvo}\n- Áreas prioritárias: {$areasTexto}\n- Nível atual: {$nivel} (iniciante, intermediário, avançado)\n- Horas disponíveis por dia: " . round($tempoDiario / 60, 1) . " horas ({$tempoDiario} minutos)\n- Dias da semana disponíveis: " . implode(', ', $diasDisponiveis) . "\n- Horário preferido: {$horario}\n- Dificuldades principais: " . ($dificuldades ?: 'Não especificadas') . "{$extrasTexto}\n\nRegras específicas para ENEM:\n1. Foque na matriz de competências do ENEM\n2. Priorize as áreas indicadas: {$areasTexto}\n3. Inclua estratégias TRI (Teoria de Resposta ao Item)\n4. Divida o estudo por dia, indicando:\n   - Matérias/assuntos específicos do ENEM\n   - Tempo sugerido por atividade\n   - Tipo de atividade (teoria, questões ENEM, revisão, simulado)\n5. Inclua momentos de revisão espaçada (24h, 7 dias, 30 dias)\n6. Foque na lógica do ENEM: interpretação de texto, leitura de gráficos, resolução de questões\n7. Inclua simulados no ritmo definido: {$ritmoSimulados}\n8. Distribua o tempo diário proporcional aos pesos de disciplinas quando fornecidos ({$pesosDisciplinas})\n9. Sugerir temas de redação e lista de exercícios por área quando relevante\n\nÁreas do ENEM:\n- Linguagens, Códigos e suas Tecnologias\n- Ciências Humanas e suas Tecnologias\n- Ciências da Natureza e suas Tecnologias\n- Matemática e suas Tecnologias\n- Redação\n\n🔴🔴🔴 REGRA CRÍTICA - NÚMERO DE DIAS 🔴🔴🔴:\n- Você DEVE criar EXATAMENTE {$totalDias} DIAS DE ESTUDO\n- NÃO crie apenas 7 dias ou uma semana\n- NÃO pare antes de criar todos os {$totalDias} dias\n- O array 'dias' DEVE conter {$totalDias} objetos, um para cada dia (dia 1, dia 2, dia 3... até dia {$totalDias})\n- Cada dia deve ter pelo menos 1 tarefa\n- Distribua as áreas ao longo de TODOS os {$totalDias} dias\n- Priorize as áreas indicadas: {$areasTexto}\n- Inclua revisões regulares ao longo dos {$totalDias} dias\n- Inclua simulados periódicos conforme ritmo\n- Foque em questões estilo ENEM\n- Progressão gradual do conteúdo ao longo dos {$totalDias} dias\n\nRetorne um JSON com a seguinte estrutura:\n{\n    'titulo': 'Plano ENEM {$anoEnem} - Nota Alvo {$notaAlvo}',\n    'descricao': 'Plano de {$totalDias} dias para ENEM {$anoEnem}',\n    'dias': [\n        {\n            'dia': 1,\n            'tarefas': [\n                {\n                    'titulo': 'Título específico do tópico ENEM',\n                    'descricao': 'Descrição detalhada do que será estudado',\n                    'material': {\n                        'videos': [],\n                        'textos': ['Material de estudo específico'],\n                        'exercicios': ['Questões ENEM sobre o tópico']\n                    }\n                }\n            ]\n        },\n        {\n            'dia': 2,\n            'tarefas': [...]\n        },\n        ...\n        {\n            'dia': {$totalDias},\n            'tarefas': [...]\n        }\n    ]\n}\n\n⚠️⚠️⚠️ IMPORTANTE FINAL ⚠️⚠️⚠️:\n- Retorne APENAS o JSON válido, SEM texto adicional\n- NÃO use markdown code blocks\n- O array 'dias' DEVE ter EXATAMENTE {$totalDias} elementos\n- Foque em conteúdo específico do ENEM\n- Use questões e materiais relacionados ao ENEM\n- NÃO crie menos dias que {$totalDias}";

        // Acrescentar regra rígida de campos e estrutura
        $prompt .= "\n\nRegras de estrutura (OBRIGATÓRIO):\n- Use APENAS as chaves: titulo, descricao, dias, dia, tarefas, material, videos, textos, exercicios.\n- NÃO crie campos extras ou diferentes.\n- O JSON final DEVE seguir exatamente o esquema informado.\n- LEMBRE-SE: O array 'dias' DEVE ter EXATAMENTE {$totalDias} elementos.";

        // Aumentar tokens baseado no número de dias (mais dias = mais tokens necessários)
        // Estimativa: ~150-200 tokens por dia (ENEM tem mais conteúdo por dia)
        // Mínimo 4000, máximo 16000 (limite do modelo)
        $maxTokens = min(16000, max(4000, $totalDias * 150 + 3000));
        
        // Log para debug
        error_log("generateEnemPlan: totalDias={$totalDias}, maxTokens={$maxTokens}");
        
        return $this->makeAPICall($prompt, $maxTokens, 0.5);
    }
    
    public function generateConcursoPlan($dadosConcurso) {
        // Extrair dados do contexto Concurso
        $tipoConcurso = $dadosConcurso['tipo_concurso'] ?? '';
        $banca = $dadosConcurso['banca'] ?? '';
        $nivel = $dadosConcurso['nivel'] ?? 'intermediario';
        $tempoDiario = $dadosConcurso['tempo_diario'] ?? 120;
        $diasDisponiveis = $dadosConcurso['dias_disponiveis'] ?? [];
        $horario = $dadosConcurso['horario_disponivel'] ?? '09:00';
        $dificuldades = $dadosConcurso['dificuldades'] ?? '';
        $numeroDias = $dadosConcurso['numero_dias'] ?? null;

        // Buscar vídeos educacionais reais do YouTube (MESMO PADRÃO DOS OUTROS MÉTODOS)
        require_once 'classes/YouTubeService.php';
        $youtubeService = new YouTubeService();
        $videos = $youtubeService->getEducationalVideos($tipoConcurso, $nivel, 3);
        
        // Usar número de dias fornecido ou calcular baseado no nível
        if ($numeroDias !== null && $numeroDias > 0) {
            $totalDias = (int)$numeroDias;
        } else {
            // Fallback: número de dias por nível
            $diasPorNivel = [ 'iniciante' => 90, 'intermediario' => 120, 'avancado' => 60 ];
            $totalDias = $diasPorNivel[$nivel] ?? 120;
        }
        
        // Preparar vídeos disponíveis para o ChatGPT (MESMO PADRÃO DOS OUTROS MÉTODOS)
        $videosDisponiveis = json_encode($videos);

        $prompt = "Você é um especialista em concursos públicos no Brasil. Crie um PLANO DE ESTUDOS COMPLETO em formato JSON para o concurso {$tipoConcurso} (banca {$banca}).
        
        CONTEXTO:
        - Tema/Área: {$tipoConcurso}
        - Banca: {$banca}
        - Nível: {$nivel}
        - Tempo diário: " . round($tempoDiario / 60, 1) . " horas ({$tempoDiario} minutos)
        - Dias disponíveis: " . implode(', ', $diasDisponiveis) . "
        - Horário: {$horario}
        - Dificuldades: " . ($dificuldades ?: 'Não especificadas') . "
        
        INSTRUÇÕES CRÍTICAS:
        1. INFIRA automaticamente as disciplinas que caem em '{$tipoConcurso}' na banca '{$banca}'.
        2. Crie EXATAMENTE {$totalDias} dias de estudo.
        3. Cada dia deve ter 1-3 tarefas.
        4. Cada tarefa DEVE ter título no formato: \"Disciplina: Subtema — [{$banca}]\"
        5. Use subtemas REAIS e ESPECÍFICOS (ex: ICMS, Regência Verbal, Balanço Patrimonial, Atos Administrativos).
        6. PROIBIDO usar títulos genéricos como 'Teoria aplicada', 'Questões da banca', 'Revisão guiada'.
        7. Cada tarefa deve ser ESPECÍFICA e ÚNICA - não repita os mesmos subtemas.
        
        🔴🔴🔴 REGRA CRÍTICA - NÚMERO DE DIAS 🔴🔴🔴:
        - Você DEVE criar EXATAMENTE {$totalDias} DIAS DE ESTUDO
        - NÃO crie apenas alguns dias ou pare antes de criar todos os {$totalDias} dias
        - O array 'dias' DEVE conter {$totalDias} objetos, um para cada dia (dia 1, dia 2, dia 3... até dia {$totalDias})
        - Cada dia deve ter pelo menos 1 tarefa
        - Distribua as disciplinas ao longo de TODOS os {$totalDias} dias
        - Progressão gradual do conteúdo ao longo dos {$totalDias} dias
        
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
        
        Retorne um JSON com a seguinte estrutura EXATA:
        {
            \"titulo\": \"Plano Concurso - {$tipoConcurso}\",
            \"descricao\": \"Plano de {$totalDias} dias para {$tipoConcurso} (banca {$banca})\",
            \"dias\": [
                {
                    \"dia\": 1,
                    \"tarefas\": [
                        {
                            \"titulo\": \"Disciplina: Subtema — [{$banca}]\",
                            \"descricao\": \"Descrição detalhada do que será estudado\",
                            \"material\": {
                                \"videos\": [
                                    {
                                        \"id\": \"ID_REAL_DO_VIDEO_AQUI\",
                                        \"title\": \"TÍTULO_REAL_DO_VIDEO_AQUI\",
                                        \"description\": \"Descrição real do vídeo\",
                                        \"thumbnail\": \"URL_DA_THUMBNAIL_REAL\",
                                        \"channel\": \"Nome do canal real\",
                                        \"url\": \"https://www.youtube.com/watch?v=ID_REAL_DO_VIDEO_AQUI\"
                                    }
                                ],
                                \"textos\": [\"Livro/Manual de Direito Administrativo\"],
                                \"exercicios\": [\"Questões da {$banca} sobre Subtema\"]
                            }
                        }
                    ]
                },
                {
                    \"dia\": 2,
                    \"tarefas\": [...]
                },
                ...
                {
                    \"dia\": {$totalDias},
                    \"tarefas\": [...]
                }
            ]
        }
        
        ⚠️⚠️⚠️ REGRAS OBRIGATÓRIAS ⚠️⚠️⚠️:
        - Crie EXATAMENTE {$totalDias} dias de estudo
        - Use APENAS as chaves: titulo, descricao, dias, dia, tarefas, material, videos, textos, exercicios
        - Dias começam em 1 (nunca 0)
        - Títulos: \"Disciplina: Subtema — [{$banca}]\" (PROIBIDO: títulos genéricos)
        - Use vídeos REAIS da lista fornecida - NÃO invente IDs
        - Cada tarefa deve ser ESPECÍFICA e ÚNICA
        - INFIRA as disciplinas baseado em {$tipoConcurso} e {$banca}
        - Use subtemas REAIS e ESPECÍFICOS, não genéricos
        - O array 'dias' DEVE ter EXATAMENTE {$totalDias} elementos
        
        🔴🔴🔴 FORMATO DE RESPOSTA CRÍTICO 🔴🔴🔴:
        - Retorne APENAS o JSON válido, SEM texto adicional antes ou depois
        - NÃO use markdown code blocks (```json ou ```)
        - NÃO adicione explicações, comentários ou texto antes do JSON
        - NÃO adicione texto depois do JSON
        - O JSON deve começar com chave de abertura { e terminar com chave de fechamento }
        - Retorne APENAS o objeto JSON, nada mais, nada menos
        - Use APENAS aspas duplas (\") para chaves e valores de string
        - NÃO crie menos dias que {$totalDias}";

        // Aumentar tokens baseado no número de dias (mais dias = mais tokens necessários)
        // Estimativa: ~150-200 tokens por dia (Concurso tem mais conteúdo por dia)
        // Mínimo 4000, máximo 16000 (limite do modelo)
        $maxTokens = min(16000, max(4000, $totalDias * 150 + 3000));
        
        // Log para debug
        error_log("generateConcursoPlan: totalDias={$totalDias}, maxTokens={$maxTokens}");
        
        return $this->makeAPICall($prompt, $maxTokens, 0.5, 'json');
    }
    
    public function generateSummaryPDF($topico, $nivel, $descricao) {
        $prompt = "Crie um resumo auxiliar DETALHADO sobre: {$topico}
        
        Nível: {$nivel}
        Descrição: {$descricao}
        
        Retorne APENAS Markdown formatado (sem texto adicional).
        
        ESTRUTURA:
        1. # {$topico}
        2. ## INTRODUÇÃO (2-3 parágrafos)
        3. ## CONCEITOS FUNDAMENTAIS (4-5 conceitos com subtítulos ###)
        4. ## EXEMPLOS PRÁTICOS (2-3 exemplos)
        5. ## EXERCÍCIOS (10 exercícios: 4 múltipla escolha, 3 preenchimento, 2 V/F, 1 prático)
        6. ## GABARITO (respostas explicadas)
        7. ## DICAS DE ESTUDO (5 dicas)
        8. ## CONCLUSÃO (1-2 parágrafos)
        
        Use Markdown: # títulos, ## seções, ### subtópicos, **negrito**, *itálico*, - listas, 1. numeradas.
        Seja específico e detalhado sobre {$topico} no nível {$nivel}.";
        
        // Resumo deve vir em Markdown, não JSON
        return $this->makeAPICall($prompt, 5000, 0.7, 'markdown');
    }
    
    private function makeAPICall($prompt, $maxTokens = 2000, $temperature = 0.7, $mode = 'json') {
        // Mensagem de sistema de acordo com o modo desejado
        if ($mode === 'markdown') {
            $systemMessage = "Você é um assistente que retorna APENAS conteúdo em Markdown bem formatado (sem JSON). NÃO use blocos ```json, apenas Markdown puro com títulos, listas, etc.";
        } else {
            $systemMessage = "Você é um assistente especializado em retornar APENAS JSON válido. REGRAS CRÍTICAS:\n1. Retorne SOMENTE o objeto JSON, nada mais.\n2. NUNCA adicione texto, explicações ou comentários antes ou depois do JSON.\n3. NUNCA use markdown code blocks (```json ou ```).\n4. O JSON deve começar EXATAMENTE com { e terminar EXATAMENTE com }.\n5. Use APENAS aspas duplas (\") para chaves e valores de string.\n6. NÃO adicione quebras de linha ou espaços antes do { ou depois do }.\n\nExemplo CORRETO: {\"chave\": \"valor\"}\nExemplo INCORRETO: ```json\n{\"chave\": \"valor\"}\n```\n\nSiga essas regras RIGOROSAMENTE.";
        }
        
        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                [ 'role' => 'system', 'content' => $systemMessage ],
                [ 'role' => 'user', 'content' => $prompt ]
            ],
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'stream' => false
        ];
        
        // NÃO forçar response_format para evitar HTTP 400 em alguns provedores/versões
        // if ($mode === 'json') {
        //     $data['response_format'] = ['type' => 'json_object'];
        // }
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_key
        ];
        
        $ch = curl_init();
        if ($ch === false) {
            throw new Exception('Não foi possível inicializar cURL. Verifique se a extensão cURL está habilitada no PHP.');
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->api_url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 300, // 5 minutos total (aumentado)
            CURLOPT_CONNECTTIMEOUT => 30, // 30 segundos para conectar (aumentado)
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_USERAGENT => 'AIStudy/1.0',
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_ENCODING => '', // Aceita compressão automática
            CURLOPT_VERBOSE => false
        ]);
        
        $response = curl_exec($ch);
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        
        if ($curlError || $curlErrno !== 0) {
            // Mensagens mais específicas baseadas no código de erro
            $userMessage = 'Erro de conexão com a API.';
            // Códigos de erro cURL (valores numéricos para compatibilidade)
            $CURLE_COULDNT_CONNECT = defined('CURLE_COULDNT_CONNECT') ? CURLE_COULDNT_CONNECT : 7;
            $CURLE_COULDNT_RESOLVE_HOST = defined('CURLE_COULDNT_RESOLVE_HOST') ? CURLE_COULDNT_RESOLVE_HOST : 6;
            $CURLE_OPERATION_TIMEOUTED = defined('CURLE_OPERATION_TIMEOUTED') ? CURLE_OPERATION_TIMEOUTED : 28;
            $CURLE_TIMEOUT = defined('CURLE_TIMEOUT') ? CURLE_TIMEOUT : 28;
            $CURLE_SSL_CONNECT_ERROR = defined('CURLE_SSL_CONNECT_ERROR') ? CURLE_SSL_CONNECT_ERROR : 35;
            
            if ($curlErrno === $CURLE_COULDNT_CONNECT || $curlErrno === $CURLE_COULDNT_RESOLVE_HOST) {
                $userMessage = 'Não foi possível conectar à API. Verifique sua conexão com a internet e a URL da API.';
            } elseif ($curlErrno === $CURLE_OPERATION_TIMEOUTED || $curlErrno === $CURLE_TIMEOUT) {
                $userMessage = 'Timeout na conexão com a API. A requisição demorou muito para responder. Tente novamente.';
            } elseif ($curlErrno === $CURLE_SSL_CONNECT_ERROR) {
                $userMessage = 'Erro SSL na conexão com a API.';
            }
            
            curl_close($ch);
            throw new Exception($userMessage . ' Detalhes: ' . $curlError);
        }
        
        curl_close($ch);
        
        if ($response === false) {
            throw new Exception('Resposta vazia da API. Verifique sua conexão e tente novamente.');
        }
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("ERRO API: JSON inválido na resposta. Resposta completa: " . substr($response, 0, 1000));
                throw new Exception('Erro ao decodificar JSON da API: ' . json_last_error_msg());
            }
            
            if (isset($result['choices'][0]['message']['content'])) {
                $content = $result['choices'][0]['message']['content'];
                
                // Verificar se o conteúdo não está vazio
                if (empty(trim($content))) {
                    error_log("ERRO API: Conteúdo vazio na resposta. Estrutura: " . json_encode($result));
                    throw new Exception('A API retornou uma resposta vazia. Tente novamente.');
                }
                
                // Verificar se a resposta foi truncada (finish_reason = 'length')
                $finishReason = $result['choices'][0]['finish_reason'] ?? null;
                if ($finishReason === 'length') {
                    error_log("AVISO API: Resposta truncada (finish_reason=length). Tamanho: " . strlen($content) . " caracteres. Considere aumentar max_tokens ou reduzir número de dias.");
                    // Não lançar exceção, mas logar aviso - pode funcionar mesmo truncado
                }
                
                // Log do tamanho do conteúdo retornado
                error_log("API retornou conteúdo de " . strlen($content) . " caracteres (finish_reason: " . ($finishReason ?? 'null') . ")");
                
                return $content;
            } else {
                error_log("ERRO API: Estrutura de resposta inválida. Resposta completa: " . substr($response, 0, 2000));
                error_log("Chaves disponíveis: " . (is_array($result) ? implode(', ', array_keys($result)) : 'não é array'));
                throw new Exception('Resposta inválida da API. A estrutura da resposta não contém o conteúdo esperado.');
            }
        } else {
            $errorData = json_decode($response, true);
            $errorMessage = isset($errorData['error']['message']) ? $errorData['error']['message'] : $response;
            error_log("ERRO API HTTP {$httpCode}: " . $errorMessage);
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
