<?php
// Iniciar output buffering para evitar problemas de headers
ob_start();

// Configurar timeouts

require_once 'config/database.php';
require_once 'config/api.php';
require_once 'classes/Routine.php';
require_once 'classes/Task.php';
require_once 'includes/session.php';
require_once 'includes/navbar.php';
require_once 'includes/csrf.php';

// Normalizador de estrutura para reduzir erros de esquema
function normalizeStudyPlan($data) {
    // If it's a JSON string, try to decode
    if (is_string($data)) {
        $decoded = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $data = $decoded;
        }
    }
    if (!is_array($data)) {
        return null;
    }

    // Unwrap common containers
    foreach (['plan', 'plano', 'resultado', 'response', 'data', 'result'] as $k) {
        if (isset($data[$k]) && is_array($data[$k])) {
            $data = $data[$k];
            break;
        }
    }

    // Top-level aliases
    if (isset($data['days']) && !isset($data['dias'])) {
        $data['dias'] = $data['days'];
    }
    if (isset($data['description']) && !isset($data['descricao'])) {
        $data['descricao'] = $data['description'];
    }

    // Detect alternative containers for days (cronograma, schedule, semanas, calendario)
    if (!isset($data['dias'])) {
        foreach (['cronograma', 'schedule', 'semanas', 'calendario', 'programa', 'plan_dias', 'dias_semana', 'week'] as $alt) {
            if (isset($data[$alt]) && is_array($data[$alt])) { 
                $data['dias'] = $data[$alt]; 
                break; 
            }
        }
        // Fallback heuristic: find first top-level array of objects containing tarefas/tasks
        if (!isset($data['dias'])) {
            foreach ($data as $k => $v) {
                if (is_array($v) && !empty($v) && isset($v[0]) && is_array($v[0])) {
                    $first = $v[0];
                    if (isset($first['tarefas']) || isset($first['tasks']) || isset($first['atividades']) || isset($first['items']) || isset($first['tarefa']) || isset($first['task']) || isset($first['dia']) || isset($first['day']) || isset($first['dia_estudo'])) { 
                        $data['dias'] = $v; 
                        break; 
                    }
                }
            }
        }
    }

    $normalized = [
        'titulo' => isset($data['titulo']) ? (string)$data['titulo'] : 'Plano de Estudos',
        'descricao' => isset($data['descricao']) ? (string)$data['descricao'] : '',
        'dias' => []
    ];

    $dias = $data['dias'] ?? [];
    
    // Some models may return an object map {"1": {...}, "2": {...}}
    if (!is_array($dias)) {
        $dias = [];
    }
    
    if (!empty($dias) && array_keys($dias) !== range(0, count($dias) - 1)) {
        // associative map => transform to indexed array ordered by key
        $dias = array_values(array_replace([], $dias));
    }

    foreach ($dias as $idx => $diaRaw) {
        if (!is_array($diaRaw)) $diaRaw = [];
        if (isset($diaRaw['day']) && !isset($diaRaw['dia'])) $diaRaw['dia'] = $diaRaw['day'];
        $diaNum = (int)($diaRaw['dia'] ?? ($idx + 1));
        if ($diaNum < 1) { $diaNum = $idx + 1; }

        // Normalize tasks container
        $tarefasRaw = null;
        foreach (['tarefas', 'tasks', 'atividades', 'items'] as $key) {
            if (isset($diaRaw[$key])) { $tarefasRaw = $diaRaw[$key]; break; }
        }
        if (!is_array($tarefasRaw)) $tarefasRaw = [];

        $tarefas = [];
        foreach ($tarefasRaw as $t) {
            if (!is_array($t)) $t = [];
            $titulo = $t['titulo'] ?? $t['title'] ?? $t['nome'] ?? '';
            $descricao = $t['descricao'] ?? $t['description'] ?? $t['detalhes'] ?? '';
            if (trim((string)$titulo) === '') {
                $titulo = $descricao ? mb_substr(trim((string)$descricao), 0, 80) : 'Tarefa';
                if ($titulo === '') { $titulo = 'Tarefa'; }
            }

            // Normalize material container
            $material = $t['material'] ?? $t['materials'] ?? $t['recursos'] ?? [];
            if (!is_array($material)) $material = [];

            $videos = $material['videos'] ?? $material['video'] ?? [];
            $textos = $material['textos'] ?? $material['texts'] ?? $material['leituras'] ?? [];
            $exercicios = $material['exercicios'] ?? $material['exercises'] ?? $material['praticas'] ?? [];
            if (!is_array($videos)) $videos = [];
            if (!is_array($textos)) $textos = [];
            if (!is_array($exercicios)) $exercicios = [];

            $tarefas[] = [
                'titulo' => (string)$titulo,
                'descricao' => (string)$descricao,
                'material' => [
                    'videos' => $videos,
                    'textos' => $textos,
                    'exercicios' => $exercicios,
                ]
            ];
        }

        $normalized['dias'][] = [
            'dia' => $diaNum,
            'tarefas' => $tarefas
        ];
    }

    return $normalized;
}

// Fallback decoder for pseudo-JSON (single quotes, unquoted keys handled by previous steps)
function flexibleJsonDecode($str) {
    if (!is_string($str) || trim($str) === '') return null;
    $original = $str;
    
    // Remover markdown code blocks (múltiplas variações)
    $str = preg_replace('/```json\s*/i', '', $str);
    $str = preg_replace('/```\s*/', '', $str);
    $str = preg_replace('/```json\s*/i', '', $str);
    $str = preg_replace('/^```\s*/m', '', $str);
    $str = preg_replace('/\s*```$/m', '', $str);
    
    // Remover texto antes do primeiro {
    $first = strpos($str, '{');
    if ($first !== false && $first > 0) {
        $str = substr($str, $first);
    }
    
    // Extrair entre primeiro { e último }
    $last = strrpos($str, '}');
    if ($last !== false && $last > 0) {
        $str = substr($str, 0, $last + 1);
    }
    
    // Tentar decode direto primeiro
    $decoded = json_decode($str, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return $decoded;
    }
    
    // Converter single quotes para double quotes (conservador)
    // Keys: 'key': -> "key":
    $str = preg_replace("/'([A-Za-z0-9_]+)'\s*:/", '"$1":', $str);
    // Values: : 'value' -> : "value" (mas não dentro de strings já com aspas)
    $str = preg_replace("/:\s*'([^'\\\n\r]*)'/", ': "$1"', $str);
    
    // Remover trailing commas
    $str = preg_replace('/,\s*}/', '}', $str);
    $str = preg_replace('/,\s*]/', ']', $str);
    
    // Remover control chars
    $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $str);
    
    // Tentar decode novamente
    $decoded = json_decode($str, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return $decoded;
    }
    
    // Último recurso: substituir todas as single quotes restantes
    $tmp = str_replace("'", '"', $str);
    $decoded = json_decode($tmp, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return $decoded;
    }
    
    return null;
}

requireLogin();

$user = getCurrentUser();
$message = '';

// Detectar tipo de rotina (GET ou POST)
$tipo_rotina = $_GET['tipo'] ?? $_POST['tipo_rotina'] ?? 'geral';
if (!in_array($tipo_rotina, ['geral', 'enem', 'concurso'])) {
    $tipo_rotina = 'geral';
}

// Processamento apenas via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf_token)) {
        setFlash('error', 'Token de segurança inválido. Por favor, recarregue a página e tente novamente.');
        header('Location: criar-rotina.php?tipo=' . urlencode($tipo_rotina), true, 303);
        exit;
    }
    
    $returnTo = $_POST['return_to'] ?? ($tipo_rotina === 'enem' ? 'modo-enem.php' : ($tipo_rotina === 'concurso' ? 'modo-concurso.php' : 'criar-rotina.php'));

    $tema = $_POST['tema'] ?? '';
    $nivel = $_POST['nivel'] ?? '';
    $tempo_diario = $_POST['tempo_diario'] ?? '';
    $dias_disponiveis = $_POST['dias_disponiveis'] ?? [];
    $horario_disponivel = $_POST['horario_disponivel'] ?? '';
    $numero_dias = isset($_POST['numero_dias']) ? (int)$_POST['numero_dias'] : null;
    
    // Validar número de dias
    if ($numero_dias === null || $numero_dias < 1 || $numero_dias > 365) {
        setFlash('error', 'Número de dias inválido. Deve ser entre 1 e 365 dias.');
        header('Location: ' . $returnTo, true, 303);
        exit;
    }
    
    // Validar campos básicos
    $camposValidos = ($tema || $tipo_rotina !== 'geral') && $nivel && $tempo_diario && !empty($dias_disponiveis) && $horario_disponivel && $numero_dias;
    
    // Validações específicas por tipo
    if ($tipo_rotina === 'enem') {
        $camposValidos = $camposValidos && !empty($_POST['ano_enem']) && !empty($_POST['nota_alvo']) && !empty($_POST['areas_prioritarias']);
    } elseif ($tipo_rotina === 'concurso') {
        // Para concurso, com formulário enxuto: exigir apenas tema e banca
        $camposValidos = $camposValidos && !empty($_POST['tema']) && !empty($_POST['banca']);
    }
    
    if (!$camposValidos) {
        setFlash('error', 'Preencha os campos obrigatórios.');
        header('Location: ' . $returnTo, true, 303);
        exit;
    }
    
    try {
        // Aumentar timeout para permitir geração do plano
        set_time_limit(300); // 5 minutos
        ini_set('max_execution_time', 300);
        ini_set('max_input_time', 300);
        
        $database = new Database();
        $db = $database->getConnection();
        
        // Preparar contexto JSON baseado no tipo
        $contexto_json = [];
        if ($tipo_rotina === 'enem') {
            $contexto_json = [
                'ano_enem' => $_POST['ano_enem'] ?? '',
                'nota_alvo' => $_POST['nota_alvo'] ?? '',
                'areas_prioritarias' => $_POST['areas_prioritarias'] ?? [],
                'pesos_disciplinas' => $_POST['pesos_disciplinas'] ?? '',
                'dificuldades' => $_POST['dificuldades'] ?? ''
            ];
            $tema = "ENEM " . ($_POST['ano_enem'] ?? date('Y') + 1);
        } elseif ($tipo_rotina === 'concurso') {
            $tipoConcursoForm = $_POST['tema'] ?? ($_POST['tipo_concurso'] ?? '');
            $contexto_json = [
                'tipo_concurso' => $tipoConcursoForm,
                'banca' => $_POST['banca'] ?? '',
                'dificuldades' => $_POST['dificuldades'] ?? ''
            ];
            $tema = ($tipoConcursoForm ?: 'Concurso');
        }
        
        // Criar rotina
        $routine = new Routine($db);
        $routine->user_id = $user['id'];
        $routine->titulo = $tipo_rotina === 'enem' ? "Plano ENEM " . ($_POST['ano_enem'] ?? '') : 
                          ($tipo_rotina === 'concurso' ? "Plano Concurso - " . ($tipoConcursoForm ?? $tema) : "Aprender " . $tema);
        $routine->tema = $tema;
        $routine->tipo = $tipo_rotina;
        $routine->contexto_json = $contexto_json;
        $routine->nivel = $nivel;
        $routine->tempo_diario = $tempo_diario;
        $routine->dias_disponiveis = $dias_disponiveis;
        $routine->horario_disponivel = $horario_disponivel;
        
        $routine_id = $routine->create();
        
        if (!$routine_id) {
            throw new Exception('Falha ao salvar rotina.');
        }
        
        $plano_data = null;
        
        // Tentar gerar plano com IA
        try {
            // Validar dados antes de chamar API
            if ($tipo_rotina === 'concurso') {
                if (empty($contexto_json['tipo_concurso']) || empty($contexto_json['banca'])) {
                    throw new Exception('Dados do concurso incompletos: tipo_concurso ou banca faltando.');
                }
            }
            
            $openai = new OpenAIService();
            
            // Usar método específico baseado no tipo
            if ($tipo_rotina === 'enem') {
                $dadosEnem = array_merge($contexto_json, [
                    'nivel' => $nivel,
                    'tempo_diario' => $tempo_diario,
                    'dias_disponiveis' => $dias_disponiveis,
                    'horario_disponivel' => $horario_disponivel,
                    'numero_dias' => $numero_dias
                ]);
                $plano = $openai->generateEnemPlan($dadosEnem);
            } elseif ($tipo_rotina === 'concurso') {
                $dadosConcurso = array_merge($contexto_json, [
                    'nivel' => $nivel,
                    'tempo_diario' => $tempo_diario,
                    'dias_disponiveis' => $dias_disponiveis,
                    'horario_disponivel' => $horario_disponivel,
                    'numero_dias' => $numero_dias
                ]);
                $plano = $openai->generateConcursoPlan($dadosConcurso);
            } else {
                $plano = $openai->generateStudyPlan($tema, $nivel, $tempo_diario, $dias_disponiveis, $horario_disponivel, $numero_dias);
            }
            
            // Verificar se a resposta não está vazia
            if (empty($plano) || !is_string($plano)) {
                error_log("ERRO: Resposta da API está vazia ou não é string (tipo: {$tipo_rotina})");
                throw new Exception('A API retornou uma resposta vazia. Verifique sua conexão e tente novamente.');
            }
            
            // Log do tamanho da resposta
            error_log("TAMANHO DA RESPOSTA (tipo: {$tipo_rotina}): " . strlen($plano) . " caracteres");
            
            // Limpar resposta de possíveis markdown code blocks
            $planoLimpo = $plano;
            // Remover markdown code blocks (```json ou ```)
            $planoLimpo = preg_replace('/```json\s*/i', '', $planoLimpo);
            $planoLimpo = preg_replace('/```\s*/', '', $planoLimpo);
            // Remover texto antes do primeiro {
            $firstBrace = strpos($planoLimpo, '{');
            if ($firstBrace !== false && $firstBrace > 0) {
                $planoLimpo = substr($planoLimpo, $firstBrace);
            }
            // Remover texto depois do último }
            $lastBrace = strrpos($planoLimpo, '}');
            if ($lastBrace !== false) {
                $planoLimpo = substr($planoLimpo, 0, $lastBrace + 1);
            }
            
            // Log da resposta bruta (primeiros 1000 caracteres) para debug
            error_log("RESPOSTA IA BRUTA (tipo: {$tipo_rotina}, primeiros 1000 chars): " . substr($plano, 0, 1000));
            error_log("RESPOSTA IA LIMPA (tipo: {$tipo_rotina}, primeiros 500 chars): " . substr($planoLimpo, 0, 500));
            
            // Verificar se planoLimpo não está vazio após limpeza
            if (empty(trim($planoLimpo))) {
                error_log("ERRO: planoLimpo está vazio após limpeza (tipo: {$tipo_rotina})");
                throw new Exception('A resposta da API está vazia após processamento. Tente novamente.');
            }
            
            // Tentar decodificar JSON diretamente (usar versão limpa)
            $plano_data = json_decode($planoLimpo, true);
            $jsonError = json_last_error();
            
            if ($jsonError !== JSON_ERROR_NONE || !is_array($plano_data)) {
                error_log("ERRO JSON DECODE (tipo: {$tipo_rotina}): " . json_last_error_msg());
                error_log("RESPOSTA LIMPA COMPLETA (últimos 2000 chars): " . substr($planoLimpo, -2000));
                
                // Tentar reparar JSON truncado (adicionar chaves de fechamento faltantes)
                $planoReparado = $plano;
                $abreChaves = substr_count($planoReparado, '{');
                $fechaChaves = substr_count($planoReparado, '}');
                $abreColchetes = substr_count($planoReparado, '[');
                $fechaColchetes = substr_count($planoReparado, ']');
                
                error_log("CONTAGEM DE CHAVES: {={$abreChaves}, }={$fechaChaves}, [={$abreColchetes}, ]={$fechaColchetes}");
                
                if ($abreChaves > $fechaChaves || $abreColchetes > $fechaColchetes) {
                    // Adicionar fechamentos faltantes
                    while ($fechaColchetes < $abreColchetes) {
                        $planoReparado .= ']';
                        $fechaColchetes++;
                    }
                    while ($fechaChaves < $abreChaves) {
                        $planoReparado .= '}';
                        $fechaChaves++;
                    }
                    $plano_data = json_decode($planoReparado, true);
                    if (json_last_error() !== JSON_ERROR_NONE || !is_array($plano_data)) {
                        // Tentar decoder flexível se existir
                        if (function_exists('flexibleJsonDecode')) {
                            $plano_data = flexibleJsonDecode($planoLimpo);
                        } else {
                            error_log("ERRO: flexibleJsonDecode não existe");
                            throw new Exception('Erro ao decodificar resposta da API. A resposta pode estar incompleta ou malformada.');
                        }
                    }
                } else {
                    // Tentar decoder flexível (usar versão limpa)
                    if (function_exists('flexibleJsonDecode')) {
                        $plano_data = flexibleJsonDecode($planoLimpo);
                    } else {
                        error_log("ERRO: flexibleJsonDecode não existe e JSON não pode ser decodificado");
                        throw new Exception('Erro ao decodificar resposta da API. Verifique os logs para mais detalhes.');
                    }
                }
                
                // Se ainda não conseguiu decodificar
                if (!is_array($plano_data) || empty($plano_data)) {
                    error_log("ERRO CRÍTICO: Não foi possível decodificar JSON após todas as tentativas");
                    throw new Exception('Não foi possível processar a resposta da API. A resposta pode estar incompleta ou malformada. Tente novamente com menos dias ou verifique sua conexão.');
                }
            }
            
            // Log de sucesso
            error_log("SUCESSO: JSON decodificado (tipo: {$tipo_rotina}, número de dias esperado: {$numero_dias})");
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            
            // Mensagem mais específica baseada no tipo de erro
            $userMessage = 'Erro ao gerar plano com IA.';
            if (strpos($errorMsg, 'Chave da API') !== false) {
                $userMessage = 'Erro de configuração: Chave da API OpenAI não está configurada.';
            } elseif (strpos($errorMsg, 'conexão') !== false || strpos($errorMsg, 'timeout') !== false) {
                $userMessage = 'Erro de conexão com a API. Verifique sua internet e tente novamente.';
            } elseif (strpos($errorMsg, 'HTTP') !== false) {
                $userMessage = 'Erro na API OpenAI. Verifique os logs do servidor para mais detalhes.';
            } elseif (strpos($errorMsg, 'incompletos') !== false) {
                $userMessage = 'Dados do formulário incompletos. Preencha todos os campos obrigatórios.';
            }
            
            setFlash('error', $userMessage . ' Tente novamente.');
            header('Location: ' . $returnTo, true, 303);
            exit;
        }
        
        // Normalizar estrutura antes de validar campos essenciais
        $plano_original = $plano_data; // Guardar original para fallback
        if ($plano_data) {
            $plano_data = normalizeStudyPlan($plano_data);
            if (!$plano_data) {
                // Se normalizeStudyPlan retornou null, tentar usar dados originais diretamente
                if ($plano_original && is_array($plano_original) && isset($plano_original['dias']) && is_array($plano_original['dias']) && !empty($plano_original['dias'])) {
                    $plano_data = $plano_original;
                }
            } else {
                // Tentar última tentativa: verificar se há algum array que possa ser dias
                if (!isset($plano_data['dias'])) {
                    foreach ($plano_data as $key => $value) {
                        if (is_array($value) && !empty($value) && isset($value[0]) && is_array($value[0])) {
                            $firstItem = $value[0];
                            if (isset($firstItem['dia']) || isset($firstItem['day']) || isset($firstItem['tarefas']) || isset($firstItem['tasks'])) {
                                $plano_data['dias'] = $value;
                                break;
                            }
                        }
                    }
                }
            }
        }
        
        // Validação mais flexível: aceitar se tiver pelo menos 1 dia válido
        $diasValidos = 0;
        $debugInfo = [];
        
        if (!$plano_data) {
            $debugInfo[] = 'plano_data é null ou false';
        } elseif (!is_array($plano_data)) {
            $debugInfo[] = 'plano_data não é array (tipo: ' . gettype($plano_data) . ')';
        } else {
            if (!isset($plano_data['dias'])) {
                $debugInfo[] = 'chave "dias" não existe no plano_data';
                $debugInfo[] = 'chaves disponíveis: ' . implode(', ', array_keys($plano_data));
            } elseif (!is_array($plano_data['dias'])) {
                $debugInfo[] = 'plano_data["dias"] não é array (tipo: ' . gettype($plano_data['dias']) . ')';
            } else {
                $totalDiasRecebidos = count($plano_data['dias']);
                $debugInfo[] = "total de dias recebidos: {$totalDiasRecebidos}";
                
                foreach ($plano_data['dias'] as $index => $dia) {
                    if (!is_array($dia)) {
                        $debugInfo[] = "dia {$index} não é array";
                        continue;
                    }
                    if (!isset($dia['tarefas'])) {
                        $debugInfo[] = "dia {$index} não tem chave 'tarefas'";
                        continue;
                    }
                    if (!is_array($dia['tarefas'])) {
                        $debugInfo[] = "dia {$index}['tarefas'] não é array";
                        continue;
                    }
                    if (empty($dia['tarefas'])) {
                        $debugInfo[] = "dia {$index} tem array de tarefas vazio";
                        continue;
                    }
                    $diasValidos++;
                }
            }
        }
        
        if (!$plano_data || !is_array($plano_data) || !isset($plano_data['dias']) || !is_array($plano_data['dias']) || $diasValidos === 0) {
            // Log detalhado para debug (apenas em desenvolvimento)
            error_log("ERRO VALIDAÇÃO PLANO - Tipo: {$tipo_rotina}");
            error_log("Debug info: " . implode(' | ', $debugInfo));
            if ($plano_data && is_array($plano_data)) {
                error_log("Estrutura recebida (primeiros 500 chars): " . substr(json_encode($plano_data), 0, 500));
            }
            
            // Mensagem de erro mais específica
            $errorMsg = 'Estrutura do plano inválida. A IA não retornou a estrutura exigida. ';
            
            if (!$plano_data) {
                $errorMsg = 'A API não retornou dados válidos. ';
            } elseif (!is_array($plano_data)) {
                $errorMsg = 'A resposta da API não está no formato esperado. ';
            } elseif (!isset($plano_data['dias'])) {
                $errorMsg = 'A resposta da API não contém o campo "dias". ';
            } elseif (!is_array($plano_data['dias'])) {
                $errorMsg = 'O campo "dias" não é um array válido. ';
            } elseif ($diasValidos === 0) {
                $errorMsg = 'Nenhum dia válido foi encontrado na resposta. ';
            }
            
            if (!empty($debugInfo)) {
                $errorMsg .= 'Detalhes: ' . implode(', ', array_slice($debugInfo, 0, 3)) . '. ';
            }
            
            $errorMsg .= 'Sugestão: Tente novamente com menos dias (ex: 7-14 dias) ou verifique sua conexão com a internet.';
            
            setFlash('error', $errorMsg);
            header('Location: ' . $returnTo, true, 303);
            exit;
        }
        
        // Criar tarefas
        $task = new Task($db);
        $tarefas_criadas = 0;
        // Preparar serviço do YouTube uma única vez
        $youtubeService = null;
        try {
            require_once 'classes/YouTubeService.php';
            $youtubeService = new YouTubeService();
        } catch (Throwable $ytEx) {
            // YouTubeService indisponível, continuar sem vídeos
        }
        foreach ($plano_data['dias'] as $dia) {
            if (!isset($dia['tarefas']) || !is_array($dia['tarefas'])) {
                continue;
            }
            foreach ($dia['tarefas'] as $index => $tarefa) {
                $task->routine_id = $routine_id;
                $task->titulo = $tarefa['titulo'] ?? 'Tarefa sem título';
                $task->descricao = $tarefa['descricao'] ?? 'Descrição não disponível';
                $task->dia_estudo = $dia['dia'] ?? 1;
                $task->ordem = $index + 1;
                $material = $tarefa['material'] ?? [];
                if (!is_array($material)) $material = [];

                // Enriquecer SEMPRE com pesquisa específica: "tema: título da tarefa"
                if ($youtubeService) {
                    $topico = trim(($tema ?? '') . ': ' . $task->titulo);
                    try {
                        $videosReais = $youtubeService->getEducationalVideos($topico, $nivel, 3);
                        if (!empty($videosReais)) {
                            $material['videos'] = $videosReais; // substituir por vídeos específicos
                        }
                    } catch (Throwable $e) {
                        // Falha na busca YouTube, continuar sem vídeos
                        if (empty($material['videos'])) $material['videos'] = [];
                    }
                } else {
                    if (empty($material['videos'])) $material['videos'] = [];
                }

                $task->material_estudo = $material;
                if ($task->create()) {
                    $tarefas_criadas++;
                }
            }
        }
        
        setFlash('success', 'Plano criado com sucesso!');
        header('Location: rotina-detalhada.php?id=' . urlencode($routine_id), true, 303);
        exit;
        
    } catch (Exception $e) {
        setFlash('error', 'Não foi possível criar a rotina.');
        $returnTo = $_POST['return_to'] ?? 'criar-rotina.php';
        header('Location: ' . $returnTo, true, 303);
        exit;
    }
}

// A partir daqui, segue o HTML do formulário geral (GET)
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIStudy - Criar Nova Rotina</title>
    
    <!-- Aplicar tema ANTES de carregar estilos para evitar flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = savedTheme || (prefersDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php $active = ''; render_navbar($active); ?>
    
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-plus me-2"></i>Criar Nova Rotina de Estudos
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        
                        <?php 
                        // Gerar token CSRF para o formulário
                        if (!isset($csrf_token)) {
                            $csrf_token = generateCSRFToken();
                        }
                        ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="tema" class="form-label">Tema/Assunto *</label>
                                    <input type="text" class="form-control" id="tema" name="tema" 
                                           placeholder="Ex: Álgebra Linear, Programação Python, História do Brasil" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="nivel" class="form-label">Nível *</label>
                                    <select class="form-select" id="nivel" name="nivel" required>
                                        <option value="">Selecione o nível</option>
                                        <option value="iniciante">Iniciante</option>
                                        <option value="intermediario">Intermediário</option>
                                        <option value="avancado">Avançado</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="tempo_diario" class="form-label">Tempo Diário (minutos) *</label>
                                    <input type="number" class="form-control" id="tempo_diario" name="tempo_diario" 
                                           min="15" max="300" placeholder="Ex: 60" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="horario_disponivel" class="form-label">Horário Disponível *</label>
                                    <input type="time" class="form-control" id="horario_disponivel" name="horario_disponivel" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="numero_dias" class="form-label">Número de Dias *</label>
                                    <input type="number" class="form-control" id="numero_dias" name="numero_dias" 
                                           min="1" max="365" value="14" placeholder="Ex: 7" required>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Quantos dias de estudo você deseja?
                                    </small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Dias da Semana Disponíveis *</label>
                                <div class="row">
                                    <div class="col-md-3 col-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="dias_disponiveis[]" value="segunda" id="segunda">
                                            <label class="form-check-label" for="segunda">Segunda</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="dias_disponiveis[]" value="terca" id="terca">
                                            <label class="form-check-label" for="terca">Terça</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="dias_disponiveis[]" value="quarta" id="quarta">
                                            <label class="form-check-label" for="quarta">Quarta</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="dias_disponiveis[]" value="quinta" id="quinta">
                                            <label class="form-check-label" for="quinta">Quinta</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="dias_disponiveis[]" value="sexta" id="sexta">
                                            <label class="form-check-label" for="sexta">Sexta</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="dias_disponiveis[]" value="sabado" id="sabado">
                                            <label class="form-check-label" for="sabado">Sábado</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="dias_disponiveis[]" value="domingo" id="domingo">
                                            <label class="form-check-label" for="domingo">Domingo</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Como funciona:</strong> Nossa IA irá criar um plano de estudos personalizado baseado nas suas preferências. 
                                O plano incluirá tarefas diárias, materiais de estudo e cronograma otimizado para seu aprendizado.
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Voltar
                                </a>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-magic me-2"></i>Gerar Plano de Estudos
                                </button>
                            </div>
                            
                            <div id="loadingOverlay" style="display: none; position: fixed; inset: 0; background: rgba(5,10,25,0.85); backdrop-filter: blur(2px); z-index: 9999; align-items: center; justify-content: center;">
                                <div class="text-white" style="width: 520px; max-width: 92vw; background: var(--card-bg); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.35);">
                                    <div style="padding: 22px 24px 8px 24px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(255,255,255,0.06);">
                                        <div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem;"></div>
                                        <div>
                                            <h5 style="margin:0;">Gerando sua rotina profissional</h5>
                                            <small class="text-muted">Estamos preparando seu plano e materiais</small>
                                        </div>
                                    </div>
                                    <div style="padding: 18px 24px;">
                                        <div id="overlayStep" class="mb-2" style="font-weight: 600;">Etapa 1/3 • Enviando instruções para a IA…</div>
                                        <div class="progress mb-2" style="height: 10px;">
                                            <div id="overlayBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 20%"></div>
                                        </div>
                                        <small class="text-muted" id="overlayTip">Dica: mantemos os títulos das tarefas específicos para melhorar a precisão dos vídeos.</small>
                                        <ul class="mt-3 mb-0" style="padding-left: 18px; font-size: 0.92rem; line-height: 1.35;">
                                            <li id="overlayItem1">Preparando estrutura de tarefas…</li>
                                            <li id="overlayItem2" class="text-muted">Buscando vídeos relevantes no YouTube…</li>
                                            <li id="overlayItem3" class="text-muted">Finalizando e salvando sua rotina…</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dark-mode.js"></script>
    <script>
        (function() {
            const tips = [
                'Dica: usamos títulos específicos para melhorar as recomendações de vídeo.',
                'Dica: removemos variações para evitar tópicos repetidos.',
                'Dica: você pode exportar o plano em PDF depois de criado.',
                'Dica: vídeos são combinados com o tema e o título da tarefa.'
            ];
            let tipIdx = 0;
            function nextTip(){
                const tipEl = document.getElementById('overlayTip');
                if (tipEl){ tipEl.textContent = tips[tipIdx % tips.length]; tipIdx++; }
            }
            setInterval(nextTip, 3500);
        })();

        document.querySelector('form').addEventListener('submit', function(e) {
            const diasSelecionados = document.querySelectorAll('input[name="dias_disponiveis[]"]:checked');
            if (diasSelecionados.length === 0) {
                e.preventDefault();
                alert('Selecione pelo menos um dia da semana disponível.');
                return;
            }
            const overlay = document.getElementById('loadingOverlay');
            const bar = document.getElementById('overlayBar');
            const step = document.getElementById('overlayStep');
            const i1 = document.getElementById('overlayItem1');
            const i2 = document.getElementById('overlayItem2');
            const i3 = document.getElementById('overlayItem3');
            const submitBtn = document.getElementById('submitBtn');
            if (overlay && submitBtn) {
                overlay.style.display = 'flex';
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Gerando…';
                // animação de etapas
                setTimeout(function(){ step.textContent='Etapa 2/3 • Processando plano com IA…'; bar.style.width='55%'; i1.classList.add('text-muted'); }, 1800);
                setTimeout(function(){ step.textContent='Etapa 3/3 • Enriquecendo com vídeos e salvando…'; bar.style.width='85%'; i2.classList.remove('text-muted'); }, 4800);
                // caso demore muito
                setTimeout(function(){ bar.style.width='95%'; }, 12000);
            }
        });
    </script>
</body>
</html>
