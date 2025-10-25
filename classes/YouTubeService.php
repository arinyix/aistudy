<?php
require_once 'config/database.php';

class YouTubeService {
    private $apiKey;
    private $baseUrl = 'https://www.googleapis.com/youtube/v3/';
    private $cacheFile = 'cache/youtube_cache.json';
    
    public function __construct() {
        $this->apiKey = 'AIzaSyD53gr0KoYXYvPNMQ282BIstKoFRIha1Yw';
        
        // Verificar se a chave está definida
        if (empty($this->apiKey)) {
            throw new Exception('Chave da API do YouTube não definida');
        }
        
        // Criar diretório de cache se não existir
        if (!file_exists('cache')) {
            mkdir('cache', 0755, true);
        }
    }
    
    /**
     * Buscar vídeos do YouTube baseado em palavras-chave
     */
    public function searchVideos($query, $maxResults = 10, $order = 'relevance') {
        $url = $this->baseUrl . 'search?' . http_build_query([
            'part' => 'snippet',
            'q' => $query,
            'type' => 'video',
            'maxResults' => $maxResults,
            'order' => $order,
            'relevanceLanguage' => 'pt',
            'regionCode' => 'BR',
            'key' => $this->apiKey
        ]);
        
        $response = $this->makeRequest($url);
        
        if (!$response) {
            return [];
        }
        
        $videos = [];
        if (isset($response['items'])) {
            foreach ($response['items'] as $item) {
                $videos[] = [
                    'id' => $item['id']['videoId'],
                    'title' => $item['snippet']['title'],
                    'description' => $item['snippet']['description'],
                    'thumbnail' => isset($item['snippet']['thumbnails']['medium']['url']) 
                        ? $item['snippet']['thumbnails']['medium']['url'] 
                        : $item['snippet']['thumbnails']['default']['url'],
                    'channel' => $item['snippet']['channelTitle'],
                    'publishedAt' => $item['snippet']['publishedAt'],
                    'url' => 'https://www.youtube.com/watch?v=' . $item['id']['videoId']
                ];
            }
        }
        
        return $videos;
    }
    
    /**
     * Buscar vídeos educacionais específicos
     */
    public function searchEducationalVideos($subject, $level = 'iniciante', $maxResults = 5) {
        // Construir query educacional mais específica
        $educationalTerms = [
            'iniciante' => ['aula', 'introdução', 'básico', 'conceitos', 'primeiros passos'],
            'intermediario' => ['curso', 'tutorial', 'explicação', 'exemplos', 'prático'],
            'avancado' => ['avançado', 'profundo', 'especializado', 'técnico', 'expert']
        ];
        
        $terms = $educationalTerms[$level] ?? $educationalTerms['iniciante'];
        
        // Criar query mais específica baseada no assunto
        $specificQuery = $this->buildSpecificQuery($subject, $level);
        $query = $specificQuery . ' ' . implode(' ', $terms) . ' educação';
        
        return $this->searchVideos($query, $maxResults, 'relevance');
    }
    
    /**
     * Construir query específica baseada no assunto
     */
    private function buildSpecificQuery($subject, $level) {
        // Mapear assuntos para termos mais específicos
        $subjectMappings = [
            'python' => 'python programação linguagem',
            'javascript' => 'javascript programação web',
            'java' => 'java programação orientada objetos',
            'php' => 'php programação web backend',
            'matemática' => 'matemática cálculo álgebra',
            'álgebra linear' => 'álgebra linear vetores matrizes',
            'cálculo' => 'cálculo derivadas integrais',
            'física' => 'física mecânica termodinâmica',
            'química' => 'química orgânica inorgânica',
            'história' => 'história do brasil mundial',
            'geografia' => 'geografia física humana',
            'biologia' => 'biologia celular genética',
            'português' => 'português gramática literatura',
            'inglês' => 'inglês idioma inglês conversação'
        ];
        
        $subjectLower = strtolower($subject);
        
        // Procurar por mapeamento específico
        foreach ($subjectMappings as $key => $mapping) {
            if (strpos($subjectLower, $key) !== false) {
                return $mapping;
            }
        }
        
        // Se não encontrar mapeamento específico, usar o assunto original
        return $subject;
    }
    
    /**
     * Filtrar vídeos por relevância específica ao assunto
     */
    public function filterVideosByRelevance($videos, $subject, $level) {
        if (empty($videos)) {
            return [];
        }
        
        // Palavras-chave educacionais
        $educationalKeywords = ['aula', 'curso', 'tutorial', 'explicação', 'educação', 'aprender', 'estudar', 'introdução', 'básico'];
        
        // Palavras-chave específicas do assunto
        $subjectKeywords = $this->getSubjectKeywords($subject);
        
        $filteredVideos = [];
        $scoredVideos = [];
        
        foreach ($videos as $video) {
            $title = strtolower($video['title']);
            $description = strtolower($video['description']);
            $score = 0;
            
            // Pontuar por palavras educacionais
            foreach ($educationalKeywords as $keyword) {
                if (strpos($title, $keyword) !== false) {
                    $score += 2; // Título tem mais peso
                }
                if (strpos($description, $keyword) !== false) {
                    $score += 1;
                }
            }
            
            // Pontuar por palavras específicas do assunto
            foreach ($subjectKeywords as $keyword) {
                if (strpos($title, $keyword) !== false) {
                    $score += 3; // Palavras do assunto têm mais peso
                }
                if (strpos($description, $keyword) !== false) {
                    $score += 2;
                }
            }
            
            // Evitar vídeos irrelevantes
            $irrelevantKeywords = ['deep learning', 'machine learning', 'ia', 'inteligência artificial', 'redes neurais'];
            $isIrrelevant = false;
            foreach ($irrelevantKeywords as $keyword) {
                if (strpos($title, $keyword) !== false && !in_array($keyword, $subjectKeywords)) {
                    $isIrrelevant = true;
                    break;
                }
            }
            
            if (!$isIrrelevant && $score > 0) {
                $scoredVideos[] = ['video' => $video, 'score' => $score];
            }
        }
        
        // Ordenar por pontuação
        usort($scoredVideos, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        // Retornar os melhores vídeos
        $filteredVideos = array_map(function($item) {
            return $item['video'];
        }, array_slice($scoredVideos, 0, 3));
        
        // Se não encontrou vídeos relevantes, retornar os primeiros
        if (empty($filteredVideos)) {
            return array_slice($videos, 0, 3);
        }
        
        return $filteredVideos;
    }
    
    /**
     * Obter palavras-chave específicas do assunto
     */
    private function getSubjectKeywords($subject) {
        $subjectLower = strtolower($subject);
        
        // Mapear assuntos para palavras-chave específicas
        $subjectKeywords = [
            'python' => ['python', 'programação', 'código', 'sintaxe', 'variáveis', 'funções'],
            'javascript' => ['javascript', 'js', 'programação', 'web', 'frontend', 'dom'],
            'java' => ['java', 'programação', 'orientada objetos', 'oop', 'classes'],
            'php' => ['php', 'programação', 'web', 'backend', 'servidor'],
            'matemática' => ['matemática', 'cálculo', 'álgebra', 'geometria', 'trigonometria'],
            'álgebra linear' => ['álgebra linear', 'vetores', 'matrizes', 'espaços vetoriais'],
            'cálculo' => ['cálculo', 'derivadas', 'integrais', 'limites', 'funções'],
            'física' => ['física', 'mecânica', 'termodinâmica', 'eletromagnetismo'],
            'química' => ['química', 'orgânica', 'inorgânica', 'reações', 'moléculas'],
            'história' => ['história', 'brasil', 'mundial', 'guerras', 'revoluções'],
            'geografia' => ['geografia', 'física', 'humana', 'clima', 'relevo'],
            'biologia' => ['biologia', 'celular', 'genética', 'evolução', 'ecossistema'],
            'português' => ['português', 'gramática', 'literatura', 'redação', 'sintaxe'],
            'inglês' => ['inglês', 'idioma', 'conversação', 'gramática', 'vocabulário']
        ];
        
        // Procurar por mapeamento específico
        foreach ($subjectKeywords as $key => $keywords) {
            if (strpos($subjectLower, $key) !== false) {
                return $keywords;
            }
        }
        
        // Se não encontrar mapeamento, usar palavras do assunto original
        return array_filter(explode(' ', $subjectLower), function($word) {
            return strlen($word) > 2; // Filtrar palavras muito curtas
        });
    }
    
    /**
     * Buscar vídeos educacionais com filtro de relevância
     */
    public function getEducationalVideos($subject, $level = 'iniciante', $maxResults = 3) {
        // Verificar cache primeiro
        $cachedResult = $this->getFromCache($subject, $level);
        if ($cachedResult !== null) {
            return $cachedResult;
        }
        
        $allVideos = [];
        
        try {
            // Estratégia 1: Busca direta com o tópico exato
            $videos1 = $this->searchVideos($subject, 5, 'relevance');
            $allVideos = array_merge($allVideos, $videos1);
            
            // Estratégia 2: Busca educacional
            if (count($allVideos) < 3) {
                $videos2 = $this->searchEducationalVideos($subject, $level, 5);
                $allVideos = array_merge($allVideos, $videos2);
            }
            
            // Estratégia 3: Busca com termos mais específicos
            if (count($allVideos) < 3) {
                $specificQuery = $this->buildSpecificQuery($subject, $level);
                $videos3 = $this->searchVideos($specificQuery . ' aula curso', 5, 'relevance');
                $allVideos = array_merge($allVideos, $videos3);
            }
            
            // Estratégia 4: Busca com nível específico
            if (count($allVideos) < 3) {
                $levelQuery = $this->buildLevelQuery($subject, $level);
                $videos4 = $this->searchVideos($levelQuery, 5, 'relevance');
                $allVideos = array_merge($allVideos, $videos4);
            }
            
            // Estratégia 5: Busca mais genérica (última tentativa)
            if (count($allVideos) < 3) {
                $genericQuery = $this->buildGenericQuery($subject);
                $videos5 = $this->searchVideos($genericQuery, 5, 'relevance');
                $allVideos = array_merge($allVideos, $videos5);
            }
            
        } catch (Exception $e) {
            // Log do erro para debug
            error_log('YouTube API Error: ' . $e->getMessage());
            
            // Verificar tipo específico de erro
            $errorMessage = $e->getMessage();
            
            if (strpos($errorMessage, 'quota') !== false || strpos($errorMessage, '403') !== false) {
                return [
                    [
                        'id' => 'quota_exceeded',
                        'title' => 'Quota da API do YouTube excedida',
                        'description' => 'A cota diária da API do YouTube foi excedida. Tente novamente amanhã.',
                        'thumbnail' => '',
                        'channel' => 'Sistema',
                        'url' => '#'
                    ]
                ];
            }
            
            if (strpos($errorMessage, '400') !== false) {
                return [
                    [
                        'id' => 'bad_request',
                        'title' => 'Erro na requisição à API do YouTube',
                        'description' => 'A requisição para a API do YouTube foi inválida. Verifique os parâmetros.',
                        'thumbnail' => '',
                        'channel' => 'Sistema',
                        'url' => '#'
                    ]
                ];
            }
            
            if (strpos($errorMessage, '401') !== false) {
                return [
                    [
                        'id' => 'unauthorized',
                        'title' => 'Chave da API do YouTube inválida',
                        'description' => 'A chave da API do YouTube não é válida ou expirou.',
                        'thumbnail' => '',
                        'channel' => 'Sistema',
                        'url' => '#'
                    ]
                ];
            }
            
            if (strpos($errorMessage, 'timeout') !== false || strpos($errorMessage, 'connection') !== false) {
                return [
                    [
                        'id' => 'connection_error',
                        'title' => 'Erro de conexão com YouTube',
                        'description' => 'Não foi possível conectar com a API do YouTube. Verifique sua conexão.',
                        'thumbnail' => '',
                        'channel' => 'Sistema',
                        'url' => '#'
                    ]
                ];
            }
            
            // Erro genérico com detalhes
            return [
                [
                    'id' => 'api_error',
                    'title' => 'Erro: API do YouTube indisponível',
                    'description' => 'Erro: ' . $errorMessage,
                    'thumbnail' => '',
                    'channel' => 'Sistema',
                    'url' => '#'
                ]
            ];
        }
        
        // Remover duplicatas
        $uniqueVideos = [];
        $seenIds = [];
        foreach ($allVideos as $video) {
            if (!in_array($video['id'], $seenIds)) {
                $uniqueVideos[] = $video;
                $seenIds[] = $video['id'];
            }
        }
        
        if (empty($uniqueVideos)) {
            return [
                [
                    'id' => 'error',
                    'title' => 'Erro: Nenhum vídeo encontrado para ' . $subject,
                    'description' => 'Não foram encontrados vídeos educacionais para este tópico.',
                    'thumbnail' => '',
                    'channel' => 'Erro',
                    'url' => '#'
                ]
            ];
        }
        
        // Filtrar por relevância educacional
        $filteredVideos = $this->filterVideosByRelevance($uniqueVideos, $subject, $level);
        
        if (empty($filteredVideos)) {
            // Se não encontrar vídeos relevantes, usar os primeiros encontrados
            return array_slice($uniqueVideos, 0, $maxResults);
        }
        
        $result = array_slice($filteredVideos, 0, $maxResults);
        
        // Salvar no cache
        $this->saveToCache($subject, $level, $result);
        
        return $result;
    }
    
    /**
     * Construir query baseada no nível
     */
    private function buildLevelQuery($subject, $level) {
        $levelTerms = [
            'iniciante' => 'iniciante básico introdução',
            'intermediario' => 'intermediário médio curso',
            'avancado' => 'avançado expert especializado'
        ];
        
        $terms = $levelTerms[$level] ?? $levelTerms['iniciante'];
        return $subject . ' ' . $terms;
    }
    
    /**
     * Construir query genérica para busca mais ampla
     */
    private function buildGenericQuery($subject) {
        // Extrair palavras-chave principais do tópico
        $words = explode(' ', strtolower($subject));
        $mainWords = array_filter($words, function($word) {
            return strlen($word) > 3; // Filtrar palavras muito curtas
        });
        
        // Usar as palavras principais + termos educacionais
        $mainSubject = implode(' ', array_slice($mainWords, 0, 2)); // Pegar as 2 primeiras palavras principais
        return $mainSubject . ' educação tutorial';
    }
    
    
    /**
     * Fazer requisição HTTP
     */
    private function makeRequest($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AIStudy/1.0');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log('Erro cURL YouTube: ' . $curlError);
            throw new Exception('Erro de conexão com YouTube API: ' . $curlError);
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = 'Erro HTTP YouTube: ' . $httpCode;
            
            if (isset($errorData['error']['message'])) {
                $errorMessage .= ' - ' . $errorData['error']['message'];
            }
            
            error_log($errorMessage);
            throw new Exception($errorMessage);
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Erro JSON YouTube: ' . json_last_error_msg());
            throw new Exception('Erro ao processar resposta da API do YouTube');
        }
        
        return $data;
    }
    
    /**
     * Gerar thumbnail do YouTube
     */
    public function getThumbnail($videoId, $quality = 'medium') {
        $qualities = [
            'default' => 'default',
            'medium' => 'mqdefault',
            'high' => 'hqdefault',
            'standard' => 'sddefault',
            'maxres' => 'maxresdefault'
        ];
        
        $quality = $qualities[$quality] ?? 'mqdefault';
        return "https://img.youtube.com/vi/{$videoId}/{$quality}.jpg";
    }
    
    /**
     * Gerar URL do vídeo
     */
    public function getVideoUrl($videoId) {
        return "https://www.youtube.com/watch?v={$videoId}";
    }
    
    /**
     * Gerar URL de embed
     */
    public function getEmbedUrl($videoId) {
        return "https://www.youtube.com/embed/{$videoId}";
    }
    
    /**
     * Obter resultado do cache
     */
    private function getFromCache($subject, $level) {
        if (!file_exists($this->cacheFile)) {
            return null;
        }
        
        $cache = json_decode(file_get_contents($this->cacheFile), true);
        if (!$cache) {
            return null;
        }
        
        $cacheKey = md5($subject . $level);
        $cachedData = $cache[$cacheKey] ?? null;
        
        if ($cachedData && isset($cachedData['timestamp'])) {
            // Cache válido por 24 horas
            if (time() - $cachedData['timestamp'] < 86400) {
                return $cachedData['videos'];
            }
        }
        
        return null;
    }
    
    /**
     * Salvar resultado no cache
     */
    private function saveToCache($subject, $level, $videos) {
        $cache = [];
        if (file_exists($this->cacheFile)) {
            $cache = json_decode(file_get_contents($this->cacheFile), true) ?: [];
        }
        
        $cacheKey = md5($subject . $level);
        $cache[$cacheKey] = [
            'timestamp' => time(),
            'videos' => $videos
        ];
        
        // Manter apenas os últimos 100 resultados no cache
        if (count($cache) > 100) {
            $cache = array_slice($cache, -100, null, true);
        }
        
        file_put_contents($this->cacheFile, json_encode($cache));
    }
}
?>
