-- Dados iniciais para o AIStudy
USE aistudy;

-- Inserir usuário de exemplo
INSERT INTO users (nome, email, senha_hash) VALUES 
('João Silva', 'joao@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'), -- senha: password
('Maria Santos', 'maria@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'), -- senha: password
('Pedro Costa', 'pedro@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- senha: password

-- Inserir rotinas de exemplo
INSERT INTO routines (user_id, titulo, tema, nivel, tempo_diario, dias_disponiveis, horario_disponivel, progresso, status) VALUES 
(1, 'Aprender Álgebra Linear', 'Matemática', 'intermediario', 60, '["segunda", "terca", "quarta", "quinta", "sexta"]', '19:00:00', 25.50, 'ativa'),
(1, 'Fundamentos de Programação', 'Programação', 'iniciante', 45, '["sabado", "domingo"]', '14:00:00', 0.00, 'ativa'),
(2, 'Machine Learning Avançado', 'Inteligência Artificial', 'avancado', 90, '["segunda", "quarta", "sexta"]', '20:00:00', 60.75, 'ativa');

-- Inserir tarefas de exemplo para a primeira rotina
INSERT INTO tasks (routine_id, titulo, descricao, dia_estudo, ordem, status, material_estudo) VALUES 
(1, 'Introdução aos Vetores', 'Conceitos básicos de vetores em álgebra linear', 1, 1, 'concluida', '{"videos": ["https://youtube.com/watch?v=exemplo1"], "textos": ["Livro: Álgebra Linear - Capítulo 1"]}'),
(1, 'Operações com Vetores', 'Soma, subtração e multiplicação de vetores', 1, 2, 'concluida', '{"videos": ["https://youtube.com/watch?v=exemplo2"], "exercicios": ["Exercícios 1-10 do livro"]}'),
(1, 'Produto Escalar', 'Definição e propriedades do produto escalar', 2, 1, 'pendente', '{"videos": ["https://youtube.com/watch?v=exemplo3"], "textos": ["Livro: Álgebra Linear - Capítulo 2"]}'),
(1, 'Produto Vetorial', 'Definição e aplicações do produto vetorial', 2, 2, 'pendente', '{"videos": ["https://youtube.com/watch?v=exemplo4"], "exercicios": ["Exercícios 11-20 do livro"]}'),
(1, 'Matrizes - Conceitos Básicos', 'Introdução às matrizes e suas propriedades', 3, 1, 'pendente', '{"videos": ["https://youtube.com/watch?v=exemplo5"], "textos": ["Livro: Álgebra Linear - Capítulo 3"]}');

-- Inserir tarefas de exemplo para a segunda rotina
INSERT INTO tasks (routine_id, titulo, descricao, dia_estudo, ordem, status, material_estudo) VALUES 
(2, 'Introdução à Programação', 'Conceitos básicos de programação', 1, 1, 'pendente', '{"videos": ["https://youtube.com/watch?v=exemplo6"], "textos": ["Livro: Programação para Iniciantes"]}'),
(2, 'Variáveis e Tipos de Dados', 'Aprendendo sobre variáveis em programação', 1, 2, 'pendente', '{"videos": ["https://youtube.com/watch?v=exemplo7"], "exercicios": ["Exercícios práticos 1-5"]}');

-- Inserir quiz de exemplo
INSERT INTO quizzes (routine_id, titulo, perguntas_json, status) VALUES 
(1, 'Quiz: Vetores e Operações Básicas', '[
    {
        "pergunta": "O que é um vetor em álgebra linear?",
        "opcoes": ["Um número real", "Uma quantidade que possui magnitude e direção", "Uma matriz", "Uma função"],
        "resposta_correta": 1
    },
    {
        "pergunta": "Como se calcula a soma de dois vetores?",
        "opcoes": ["Multiplicando suas magnitudes", "Somando componente por componente", "Dividindo um pelo outro", "Elevando ao quadrado"],
        "resposta_correta": 1
    },
    {
        "pergunta": "O que é o produto escalar?",
        "opcoes": ["Uma operação que resulta em um vetor", "Uma operação que resulta em um escalar", "Uma operação de divisão", "Uma operação de potenciação"],
        "resposta_correta": 1
    }
]', 'pendente');

-- Inserir progresso diário de exemplo
INSERT INTO daily_progress (user_id, routine_id, data, tarefas_concluidas, total_tarefas) VALUES 
(1, 1, '2024-01-15', 2, 2),
(1, 1, '2024-01-16', 0, 2),
(1, 2, '2024-01-20', 0, 2);
