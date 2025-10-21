<?php
require_once 'config/database.php';

class YouTubeService {
    private $apiKey;
    private $baseUrl = 'https://www.googleapis.com/youtube/v3/';
    
    public function __construct() {
        $this->apiKey = 'AIzaSyD53gr0KoYXYvPNMQ282BIstKoFRIha1Yw';
        
        // Verificar se a chave está definida
        if (empty($this->apiKey)) {
            throw new Exception('Chave da API do YouTube não definida');
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
        // Buscar vídeos com múltiplas queries para melhor relevância
        $allVideos = [];
        
        // Query 1: Busca usando o título exato da task (prioridade máxima)
        $videos1 = $this->searchVideos($subject, 5, 'relevance');
        $allVideos = array_merge($allVideos, $videos1);
        
        // Query 2: Busca educacional com o título exato
        $videos2 = $this->searchEducationalVideos($subject, $level, 5);
        $allVideos = array_merge($allVideos, $videos2);
        
        // Query 3: Busca com termos mais específicos (apenas se necessário)
        if (count($allVideos) < 3) {
            $specificQuery = $this->buildSpecificQuery($subject, $level);
            $videos3 = $this->searchVideos($specificQuery . ' aula curso', 5, 'relevance');
            $allVideos = array_merge($allVideos, $videos3);
        }
        
        // Query 4: Busca com nível específico (apenas se necessário)
        if (count($allVideos) < 3) {
            $levelQuery = $this->buildLevelQuery($subject, $level);
            $videos4 = $this->searchVideos($levelQuery, 5, 'relevance');
            $allVideos = array_merge($allVideos, $videos4);
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
            return $this->getFallbackVideos($subject);
        }
        
        // Filtrar por relevância educacional
        $filteredVideos = $this->filterVideosByRelevance($uniqueVideos, $subject, $level);
        
        if (empty($filteredVideos)) {
            return $this->getFallbackVideos($subject);
        }
        
        return $filteredVideos;
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
     * Vídeos de fallback caso a API falhe
     */
    private function getFallbackVideos($subject) {
        // Vídeos educacionais específicos por assunto
        $fallbackVideos = [
            'python' => [
                'id' => 'kqtD5dpn9C8',
                'title' => 'Python para Iniciantes - Curso Completo',
                'description' => 'Aprenda Python do zero com este curso completo',
                'thumbnail' => 'https://img.youtube.com/vi/kqtD5dpn9C8/mqdefault.jpg',
                'channel' => 'Curso em Vídeo',
                'url' => 'https://www.youtube.com/watch?v=kqtD5dpn9C8'
            ],
            'javascript' => [
                'id' => 'B7xai5u_tnk',
                'title' => 'JavaScript para Iniciantes - Curso Completo',
                'description' => 'Aprenda JavaScript do zero com este curso completo',
                'thumbnail' => 'https://img.youtube.com/vi/B7xai5u_tnk/mqdefault.jpg',
                'channel' => 'Curso em Vídeo',
                'url' => 'https://www.youtube.com/watch?v=B7xai5u_tnk'
            ],
            'matemática' => [
                'id' => 'dQw4w9WgXcQ',
                'title' => 'Matemática Básica - Aula Completa',
                'description' => 'Aprenda matemática básica com esta aula completa',
                'thumbnail' => 'https://img.youtube.com/vi/dQw4w9WgXcQ/mqdefault.jpg',
                'channel' => 'Canal Educacional',
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
            ]
        ];
        
        $subjectLower = strtolower($subject);
        
        // Procurar por vídeo específico do assunto
        foreach ($fallbackVideos as $key => $video) {
            if (strpos($subjectLower, $key) !== false) {
                return [$video];
            }
        }
        
        // Vídeo genérico se não encontrar específico
        return [
            [
                'id' => 'dQw4w9WgXcQ',
                'title' => 'Vídeo Educacional - ' . $subject,
                'description' => 'Conteúdo educacional sobre ' . $subject,
                'thumbnail' => 'https://img.youtube.com/vi/dQw4w9WgXcQ/mqdefault.jpg',
                'channel' => 'Canal Educacional',
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
            ]
        ];
    }
    
    /**
     * Fazer requisição HTTP
     */
    private function makeRequest($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AIStudy/1.0');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log('Erro cURL YouTube: ' . $curlError);
            return false;
        }
        
        if ($httpCode !== 200) {
            error_log('Erro HTTP YouTube: ' . $httpCode . ' - ' . $response);
            return false;
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Erro JSON YouTube: ' . json_last_error_msg());
            return false;
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
}
?>
