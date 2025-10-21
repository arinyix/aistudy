<?php
// Dados de fallback quando a API não estiver funcionando
require_once 'classes/YouTubeService.php';

class FallbackData {
    
    public static function getStudyPlan($tema, $nivel) {
        // Buscar vídeos educacionais reais do YouTube
        $youtubeService = new YouTubeService();
        $videos = $youtubeService->getEducationalVideos($tema, $nivel, 3);
        
        // Determinar número de dias baseado no nível
        $diasPorNivel = [
            'iniciante' => 14, // 2 semanas
            'intermediario' => 21, // 3 semanas  
            'avancado' => 28 // 4 semanas
        ];
        $totalDias = $diasPorNivel[$nivel] ?? 14;
        
        $dias = [];
        for ($i = 1; $i <= $totalDias; $i++) {
            // Usar apenas o nível escolhido pelo usuário
            $nivelDia = $nivel;
            
            $dias[] = [
                'dia' => $i,
                'tarefas' => [
                    [
                        'titulo' => $tema . ' - Dia ' . $i . ' (Nível ' . ucfirst($nivel) . ')',
                        'descricao' => 'Conteúdo do dia ' . $i . ' - Nível ' . $nivel,
                        'material' => [
                            'videos' => $videos,
                            'textos' => [
                                'Livro: ' . $tema . ' - Capítulo ' . $i . ' (Nível ' . $nivel . ')',
                                'Artigo: ' . $tema . ' - Nível ' . $nivel
                            ],
                            'exercicios' => [
                                'Exercício ' . $i . ': Prática ' . $nivel,
                                'Exercício ' . ($i + 1) . ': Aplicação ' . $nivel
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