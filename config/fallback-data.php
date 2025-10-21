<?php
// Dados de fallback quando a API não estiver funcionando
require_once 'classes/YouTubeService.php';

class FallbackData {
    
    public static function getStudyPlan($tema, $nivel) {
        // NÃO usar conteúdo pré-definido - deixar a IA gerar tudo
        // Este método só deve ser usado se a API da OpenAI falhar completamente
        
        // Determinar número de dias baseado no nível
        $diasPorNivel = [
            'iniciante' => 14, // 2 semanas
            'intermediario' => 21, // 3 semanas  
            'avancado' => 28 // 4 semanas
        ];
        $totalDias = $diasPorNivel[$nivel] ?? 14;
        
        $dias = [];
        
        for ($i = 1; $i <= $totalDias; $i++) {
            // Gerar tópicos específicos baseados no tema
            $topico = self::getTopicoEspecifico($tema, $nivel, $i);
            
            // Buscar vídeos específicos para este tópico usando API do YouTube
            $videosTopico = self::getVideosPorTopico($topico, $tema, $nivel);
            
            $dias[] = [
                'dia' => $i,
                'tarefas' => [
                    [
                        'titulo' => $topico,
                        'descricao' => 'Aprenda sobre ' . $topico . ' - Nível ' . $nivel,
                        'material' => [
                            'videos' => $videosTopico,
                            'textos' => [
                                'Livro: ' . $topico . ' - Fundamentos',
                                'Artigo: ' . $topico . ' - Guia Prático'
                            ],
                            'exercicios' => [
                                'Exercício: Prática de ' . $topico,
                                'Desafio: Aplicação de ' . $topico
                            ]
                        ]
                    ]
                ]
            ];
        }
        
        return [
            'titulo' => "Aprender " . $tema . " - Curso Completo " . ucfirst($nivel),
            'descricao' => "Plano completo de " . $totalDias . " dias para dominar " . $tema,
            'dias' => $dias
        ];
    }
    
    public static function getTopicosPorTema($tema, $nivel, $totalDias) {
        $temaLower = strtolower($tema);
        
        $topicosPorTema = [
            'programacao' => [
                'iniciante' => [
                    'Introdução à Programação',
                    'Variáveis e Tipos de Dados',
                    'Estruturas de Controle',
                    'Funções e Métodos',
                    'Arrays e Listas',
                    'Loops e Iterações',
                    'Condicionais',
                    'Entrada e Saída de Dados',
                    'Debugging e Erros',
                    'Projetos Práticos',
                    'Boas Práticas',
                    'Documentação de Código',
                    'Testes Básicos',
                    'Revisão e Prática'
                ],
                'intermediario' => [
                    'Programação Orientada a Objetos',
                    'Classes e Objetos',
                    'Herança e Polimorfismo',
                    'Encapsulamento',
                    'Interfaces e Abstrações',
                    'Tratamento de Exceções',
                    'Estruturas de Dados',
                    'Algoritmos de Ordenação',
                    'Recursão',
                    'Design Patterns',
                    'Testes Unitários',
                    'Refatoração',
                    'Performance e Otimização',
                    'APIs e Integração',
                    'Bancos de Dados',
                    'Segurança em Código',
                    'Versionamento',
                    'Frameworks',
                    'Arquitetura de Software',
                    'Deploy e Produção',
                    'Revisão Avançada'
                ],
                'avancado' => [
                    'Arquitetura de Microserviços',
                    'Design Patterns Avançados',
                    'Algoritmos Complexos',
                    'Estruturas de Dados Avançadas',
                    'Concorrência e Paralelismo',
                    'Programação Funcional',
                    'Testes de Integração',
                    'CI/CD e DevOps',
                    'Cloud Computing',
                    'Machine Learning',
                    'Inteligência Artificial',
                    'Blockchain',
                    'IoT e Sistemas Embarcados',
                    'Arquitetura Distribuída',
                    'Performance Tuning',
                    'Segurança Avançada',
                    'Monitoramento e Observabilidade',
                    'Escalabilidade',
                    'Arquitetura de Eventos',
                    'Domain-Driven Design',
                    'Clean Architecture',
                    'SOLID Principles',
                    'TDD e BDD',
                    'Code Review Avançado',
                    'Refactoring Avançado',
                    'Legacy Code',
                    'Documentação Técnica',
                    'Mentoria e Liderança Técnica'
                ]
            ],
            'python' => [
                'iniciante' => [
                    'Introdução ao Python',
                    'Variáveis e Tipos',
                    'Strings e Formatação',
                    'Listas e Tuplas',
                    'Dicionários',
                    'Condicionais if/else',
                    'Loops for e while',
                    'Funções',
                    'Módulos e Pacotes',
                    'Arquivos e I/O',
                    'Tratamento de Erros',
                    'List Comprehensions',
                    'Projetos Práticos',
                    'Revisão e Prática'
                ],
                'intermediario' => [
                    'Programação Orientada a Objetos',
                    'Classes e Objetos',
                    'Herança e Polimorfismo',
                    'Decoradores',
                    'Generators',
                    'Context Managers',
                    'Regular Expressions',
                    'JSON e APIs',
                    'Bancos de Dados',
                    'Web Scraping',
                    'NumPy e Pandas',
                    'Matplotlib',
                    'Testes com pytest',
                    'Virtual Environments',
                    'Pip e Requirements',
                    'Frameworks Web',
                    'APIs REST',
                    'Automação',
                    'Data Science Básico',
                    'Machine Learning Intro',
                    'Revisão Intermediária'
                ],
                'avancado' => [
                    'Metaclasses',
                    'Descriptors',
                    'Async/Await',
                    'Concorrência Avançada',
                    'Multiprocessing',
                    'Threading Avançado',
                    'Design Patterns',
                    'Arquitetura de Software',
                    'Microserviços',
                    'Docker e Containers',
                    'Kubernetes',
                    'Cloud Computing',
                    'Machine Learning Avançado',
                    'Deep Learning',
                    'Data Engineering',
                    'Big Data',
                    'Performance Profiling',
                    'C Extensions',
                    'Cython',
                    'Distributed Systems',
                    'Event-Driven Architecture',
                    'Message Queues',
                    'Caching Strategies',
                    'Database Optimization',
                    'Security Best Practices',
                    'Code Quality',
                    'Technical Leadership'
                ]
            ],
            'javascript' => [
                'iniciante' => [
                    'Introdução ao JavaScript',
                    'Variáveis e Tipos',
                    'Operadores',
                    'Condicionais',
                    'Loops',
                    'Funções',
                    'Arrays',
                    'Objetos',
                    'DOM Básico',
                    'Eventos',
                    'Formulários',
                    'Validação',
                    'Projetos Práticos',
                    'Revisão e Prática'
                ],
                'intermediario' => [
                    'ES6+ Features',
                    'Arrow Functions',
                    'Destructuring',
                    'Modules',
                    'Promises',
                    'Async/Await',
                    'Closures',
                    'Prototypes',
                    'Classes',
                    'AJAX e Fetch',
                    'APIs REST',
                    'Node.js Básico',
                    'NPM e Package.json',
                    'Webpack',
                    'Frameworks Frontend',
                    'React/Vue/Angular',
                    'State Management',
                    'Testing',
                    'Debugging Avançado',
                    'Performance',
                    'Revisão Intermediária'
                ],
                'avancado' => [
                    'Advanced ES6+',
                    'Functional Programming',
                    'Design Patterns',
                    'Architecture Patterns',
                    'Microservices',
                    'Serverless',
                    'GraphQL',
                    'WebSockets',
                    'Real-time Applications',
                    'Progressive Web Apps',
                    'Mobile Development',
                    'Desktop Applications',
                    'Cloud Computing',
                    'DevOps',
                    'CI/CD',
                    'Monitoring',
                    'Security',
                    'Performance Optimization',
                    'Code Quality',
                    'Technical Leadership'
                ]
            ],
            'matematica' => [
                'iniciante' => [
                    'Aritmética Básica',
                    'Álgebra Elementar',
                    'Geometria Plana',
                    'Frações e Decimais',
                    'Porcentagem',
                    'Equações Lineares',
                    'Sistemas de Equações',
                    'Geometria Analítica',
                    'Funções',
                    'Trigonometria Básica',
                    'Estatística Descritiva',
                    'Probabilidade Básica',
                    'Revisão e Prática'
                ],
                'intermediario' => [
                    'Álgebra Linear',
                    'Cálculo Diferencial',
                    'Cálculo Integral',
                    'Geometria Analítica',
                    'Trigonometria Avançada',
                    'Números Complexos',
                    'Sequências e Séries',
                    'Limites',
                    'Derivadas',
                    'Integrais',
                    'Equações Diferenciais',
                    'Estatística Inferencial',
                    'Probabilidade Avançada',
                    'Análise Combinatória',
                    'Revisão Intermediária'
                ],
                'avancado' => [
                    'Álgebra Linear Avançada',
                    'Cálculo Multivariável',
                    'Análise Real',
                    'Análise Complexa',
                    'Topologia',
                    'Teoria dos Números',
                    'Álgebra Abstrata',
                    'Geometria Diferencial',
                    'Equações Diferenciais Parciais',
                    'Análise Funcional',
                    'Teoria da Medida',
                    'Probabilidade Avançada',
                    'Estatística Matemática',
                    'Otimização',
                    'Pesquisa Operacional'
                ]
            ]
        ];
        
        $topicos = $topicosPorTema[$temaLower] ?? $topicosPorTema['programacao'];
        $topicosNivel = $topicos[$nivel] ?? $topicos['iniciante'];
        
        // Retornar apenas os tópicos necessários
        return array_slice($topicosNivel, 0, $totalDias);
    }
    
    public static function getTopicoEspecifico($tema, $nivel, $dia) {
        // Gerar tópicos específicos baseados no tema
        $topicosPorTema = [
            'coreano' => [
                'iniciante' => [
                    'Alfabeto Hangul - Vogais Básicas',
                    'Alfabeto Hangul - Consoantes Básicas',
                    'Cumprimentos Básicos',
                    'Números de 1 a 10',
                    'Apresentação Pessoal',
                    'Família - Vocabulário Básico',
                    'Cores em Coreano',
                    'Dias da Semana',
                    'Meses do Ano',
                    'Horas e Tempo',
                    'Comida Básica',
                    'Direções Simples',
                    'Profissões Básicas',
                    'Revisão e Prática'
                ],
                'intermediario' => [
                    'Gramática - Partículas Básicas',
                    'Tempos Verbais - Presente',
                    'Tempos Verbais - Passado',
                    'Tempos Verbais - Futuro',
                    'Adjetivos e Advérbios',
                    'Estruturas Condicionais',
                    'Números Avançados',
                    'Vocabulário Acadêmico',
                    'Expressões Idiomáticas',
                    'Conversação Formal',
                    'Leitura de Textos',
                    'Escrita Básica',
                    'Cultura Coreana',
                    'Revisão Intermediária'
                ],
                'avancado' => [
                    'Gramática Avançada - Partículas Complexas',
                    'Estruturas Subordinadas',
                    'Linguagem Formal e Informal',
                    'Vocabulário Técnico',
                    'Literatura Coreana',
                    'História da Coreia',
                    'Negócios em Coreano',
                    'Debates e Discussões',
                    'Tradução e Interpretação',
                    'Linguagem Acadêmica',
                    'Cultura e Sociedade',
                    'Mídia e Notícias',
                    'Especialização Temática',
                    'Revisão Avançada'
                ]
            ],
            'matematica' => [
                'iniciante' => [
                    'Operações Básicas',
                    'Frações Simples',
                    'Decimais Básicos',
                    'Geometria - Formas Básicas',
                    'Medidas e Unidades',
                    'Gráficos Simples',
                    'Problemas de Palavras',
                    'Álgebra Básica',
                    'Equações Simples',
                    'Percentuais',
                    'Probabilidade Básica',
                    'Estatística Descritiva',
                    'Revisão Fundamental',
                    'Aplicações Práticas'
                ],
                'intermediario' => [
                    'Equações do Primeiro Grau',
                    'Sistemas de Equações',
                    'Funções Lineares',
                    'Geometria Analítica',
                    'Trigonometria Básica',
                    'Logaritmos',
                    'Sequências e Séries',
                    'Probabilidade Avançada',
                    'Estatística Inferencial',
                    'Cálculo Diferencial',
                    'Cálculo Integral',
                    'Geometria Espacial',
                    'Revisão Intermediária',
                    'Problemas Complexos'
                ],
                'avancado' => [
                    'Cálculo Multivariável',
                    'Equações Diferenciais',
                    'Álgebra Linear',
                    'Análise Complexa',
                    'Topologia',
                    'Teoria dos Números',
                    'Geometria Diferencial',
                    'Análise Funcional',
                    'Teoria dos Conjuntos',
                    'Lógica Matemática',
                    'Pesquisa Matemática',
                    'Aplicações Avançadas',
                    'Revisão Especializada',
                    'Projetos de Pesquisa'
                ]
            ],
            'fisica' => [
                'iniciante' => [
                    'Mecânica Básica',
                    'Leis de Newton',
                    'Energia Cinética',
                    'Energia Potencial',
                    'Movimento Retilíneo',
                    'Movimento Circular',
                    'Gravitação Universal',
                    'Ondas Mecânicas',
                    'Som e Acústica',
                    'Luz e Óptica',
                    'Eletricidade Básica',
                    'Magnetismo Básico',
                    'Revisão Fundamental',
                    'Experimentos Básicos'
                ],
                'intermediario' => [
                    'Termodinâmica',
                    'Eletromagnetismo',
                    'Ondas Eletromagnéticas',
                    'Física Quântica Básica',
                    'Relatividade Especial',
                    'Física Nuclear',
                    'Física Atômica',
                    'Física Molecular',
                    'Física do Estado Sólido',
                    'Astrofísica Básica',
                    'Física de Partículas',
                    'Revisão Intermediária',
                    'Laboratório Avançado',
                    'Aplicações Tecnológicas'
                ],
                'avancado' => [
                    'Mecânica Quântica',
                    'Relatividade Geral',
                    'Física de Altas Energias',
                    'Cosmologia',
                    'Física Teórica',
                    'Física Computacional',
                    'Física Experimental',
                    'Pesquisa em Física',
                    'Física Aplicada',
                    'Física Interdisciplinar',
                    'Revisão Especializada',
                    'Projetos de Pesquisa',
                    'Publicações Científicas',
                    'Colaborações Internacionais'
                ]
            ]
        ];
        
        // Normalizar tema para minúsculas
        $temaNormalizado = strtolower($tema);
        
        // Verificar se existe o tema
        if (isset($topicosPorTema[$temaNormalizado][$nivel])) {
            $topicos = $topicosPorTema[$temaNormalizado][$nivel];
            $indice = ($dia - 1) % count($topicos);
            return $topicos[$indice];
        }
        
        // Gerar tópicos automáticos baseados no tema
        return self::gerarTopicoAutomatico($tema, $nivel, $dia);
    }
    
    public static function gerarTopicoAutomatico($tema, $nivel, $dia) {
        // GERAR TÓPICOS ESPECÍFICOS AUTOMATICAMENTE PARA QUALQUER ASSUNTO
        // SEMPRE usar a API do ChatGPT - ZERO predefinições - TUDO AUTOMATIZADO
        
        try {
            require_once 'config/api.php';
            $openaiService = new OpenAIService();
            
            // SEMPRE consultar a API para QUALQUER assunto - TUDO AUTOMATIZADO
            $resposta = $openaiService->generateSpecificTopic($tema, $nivel, $dia);
            
            if ($resposta && !empty(trim($resposta))) {
                return trim($resposta);
            }
            
        } catch (Exception $e) {
            // Se API falhar, tentar novamente com prompt mais específico
            try {
                $resposta = $openaiService->generateSpecificTopic($tema, $nivel, $dia);
                
                if ($resposta && !empty(trim($resposta))) {
                    return trim($resposta);
                }
                
            } catch (Exception $e2) {
                // Se falhar completamente, tentar uma última vez
                try {
                    $resposta = $openaiService->generateSpecificTopic($tema, $nivel, $dia);
                    
                    if ($resposta && !empty(trim($resposta))) {
                        return trim($resposta);
                    }
                } catch (Exception $e3) {
                    // Se falhar completamente, retornar erro
                    return "Erro: Não foi possível gerar tópico para {$tema}";
                }
            }
        }
        
        // Se chegou aqui, algo deu errado
        return "Erro: Não foi possível gerar tópico para {$tema}";
    }
    
    public static function getVideosPorTopico($topico, $tema, $nivel) {
        // SEMPRE usar a API do YouTube para vídeos reais baseados no tópico específico
        // TUDO AUTOMATIZADO - ZERO predefinições
        
        try {
            require_once 'classes/YouTubeService.php';
            $youtubeService = new YouTubeService();
            
            // Buscar vídeos específicos para o tópico exato - TUDO AUTOMATIZADO
            $videos = $youtubeService->getEducationalVideos($topico, $nivel, 3);
            
            if (!empty($videos)) {
                return $videos;
            }
        } catch (Exception $e) {
            // Se falhar, tentar busca mais específica
            try {
                // Tentar busca com termos mais específicos baseados no tópico
                $termosEspecificos = $topico . ' ' . $tema . ' ' . $nivel;
                $videos = $youtubeService->getEducationalVideos($termosEspecificos, $nivel, 3);
                
                if (!empty($videos)) {
                    return $videos;
                }
            } catch (Exception $e2) {
                // Se falhar, tentar uma última vez
                try {
                    $videos = $youtubeService->getEducationalVideos($topico, $nivel, 3);
                    
                    if (!empty($videos)) {
                        return $videos;
                    }
                } catch (Exception $e3) {
                    // Se falhar completamente, retornar erro
                    return [
                        ['id' => 'error', 'title' => 'Erro: Não foi possível buscar vídeos para ' . $topico, 'url' => '#']
                    ];
                }
            }
        }
        
        // Se chegou aqui, algo deu errado
        return [
            ['id' => 'error', 'title' => 'Erro: Não foi possível buscar vídeos para ' . $topico, 'url' => '#']
        ];
    }
    
    public static function getTermosEspecificos($topico) {
        // Gerar termos de busca mais específicos baseados no tópico
        $termosEspecificos = [
            'Introdução à Programação' => 'programação para iniciantes conceitos básicos',
            'Variáveis e Tipos de Dados' => 'variáveis programação tipos de dados',
            'Estruturas de Controle' => 'estruturas de controle programação if else',
            'Funções e Métodos' => 'funções programação métodos',
            'Introdução ao Python' => 'python para iniciantes tutorial básico',
            'Variáveis e Tipos' => 'python variáveis tipos de dados',
            'Strings e Formatação' => 'python strings formatação f-strings',
            'Listas e Tuplas' => 'python listas tuplas arrays',
            'Dicionários' => 'python dicionários dict',
            'Condicionais if/else' => 'python if else condicionais',
            'Loops for e while' => 'python loops for while',
            'Funções' => 'python funções def',
            'Módulos e Pacotes' => 'python módulos pacotes import',
            'Arquivos e I/O' => 'python arquivos entrada saída',
            'Tratamento de Erros' => 'python try except tratamento erros',
            'List Comprehensions' => 'python list comprehensions',
            'Projetos Práticos' => 'python projetos práticos exercícios',
            'Revisão e Prática' => 'python revisão prática exercícios'
        ];
        
        return $termosEspecificos[$topico] ?? $topico;
    }
    
    public static function getFallbackVideos($tema, $nivel) {
        // Vídeos de fallback baseados no tema
        $fallbackVideos = [
            'python' => [
                ['id' => 'kqtD5dpn9C8', 'title' => 'Python para Iniciantes', 'url' => 'https://www.youtube.com/watch?v=kqtD5dpn9C8'],
                ['id' => 'B7xai5u_tnk', 'title' => 'Variáveis em Python', 'url' => 'https://www.youtube.com/watch?v=B7xai5u_tnk'],
                ['id' => 'dQw4w9WgXcQ', 'title' => 'Estruturas de Controle Python', 'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']
            ],
            'javascript' => [
                ['id' => 'B7xai5u_tnk', 'title' => 'JavaScript DOM', 'url' => 'https://www.youtube.com/watch?v=B7xai5u_tnk'],
                ['id' => 'kqtD5dpn9C8', 'title' => 'JavaScript Events', 'url' => 'https://www.youtube.com/watch?v=kqtD5dpn9C8'],
                ['id' => 'dQw4w9WgXcQ', 'title' => 'JavaScript Avançado', 'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']
            ],
            'coreano' => [
                ['id' => 'dQw4w9WgXcQ', 'title' => 'Hangul Básico', 'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
                ['id' => 'kqtD5dpn9C8', 'title' => 'Cumprimentos Coreanos', 'url' => 'https://www.youtube.com/watch?v=kqtD5dpn9C8'],
                ['id' => 'B7xai5u_tnk', 'title' => 'Números Coreanos', 'url' => 'https://www.youtube.com/watch?v=B7xai5u_tnk']
            ],
            'matematica' => [
                ['id' => 'dQw4w9WgXcQ', 'title' => 'Integrais Avançadas', 'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
                ['id' => 'kqtD5dpn9C8', 'title' => 'Espaços Vetoriais', 'url' => 'https://www.youtube.com/watch?v=kqtD5dpn9C8'],
                ['id' => 'B7xai5u_tnk', 'title' => 'Cálculo Diferencial', 'url' => 'https://www.youtube.com/watch?v=B7xai5u_tnk']
            ]
        ];
        
        $temaLower = strtolower($tema);
        $videos = $fallbackVideos[$temaLower] ?? $fallbackVideos['python'];
        
        // Garantir que retorna um array
        return is_array($videos) ? $videos : [];
    }
    
    public static function getQuiz($tema, $nivel) {
        return [
            'perguntas' => [
                [
                    'pergunta' => 'Qual é o conceito básico de ' . $tema . '?',
                    'opcoes' => [
                        'Opção A: Conceito fundamental',
                        'Opção B: Conceito secundário',
                        'Opção C: Conceito avançado',
                        'Opção D: Conceito desnecessário'
                    ],
                    'resposta_correta' => 0
                ],
                [
                    'pergunta' => 'Como aplicar ' . $tema . ' na prática?',
                    'opcoes' => [
                        'Opção A: Seguindo os passos básicos',
                        'Opção B: Pulando etapas',
                        'Opção C: Sem planejamento',
                        'Opção D: Ignorando fundamentos'
                    ],
                    'resposta_correta' => 0
                ],
                [
                    'pergunta' => 'Qual a importância de estudar ' . $tema . '?',
                    'opcoes' => [
                        'Opção A: Desenvolvimento pessoal',
                        'Opção B: Apenas para provas',
                        'Opção C: Sem importância',
                        'Opção D: Perda de tempo'
                    ],
                    'resposta_correta' => 0
                ],
                [
                    'pergunta' => 'Como manter o foco nos estudos de ' . $tema . '?',
                    'opcoes' => [
                        'Opção A: Criando uma rotina',
                        'Opção B: Estudando esporadicamente',
                        'Opção C: Sem organização',
                        'Opção D: Deixando para depois'
                    ],
                    'resposta_correta' => 0
                ],
                [
                    'pergunta' => 'Qual o próximo passo após aprender ' . $tema . '?',
                    'opcoes' => [
                        'Opção A: Aplicar na prática',
                        'Opção B: Esquecer tudo',
                        'Opção C: Não fazer nada',
                        'Opção D: Parar de estudar'
                    ],
                    'resposta_correta' => 0
                ]
            ]
        ];
    }
}
?>