-- Dados iniciais corrigidos
USE aistudy;

-- Inserir usuários de exemplo
INSERT INTO users (nome, email, senha_hash, bio) VALUES 
('João Silva', 'joao@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Apaixonado por aprender novas tecnologias'),
('Maria Santos', 'maria@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Estudante de idiomas e programação'),
('Pedro Costa', 'pedro@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Focado em matemática e ciências');

-- Inserir preferências dos usuários
INSERT INTO user_preferences (user_id, tema_preferido, idioma, notificacoes_email, notificacoes_push, horario_notificacao) VALUES 
(1, 'light', 'pt', TRUE, TRUE, '19:00:00'),
(2, 'dark', 'pt', TRUE, FALSE, '14:00:00'),
(3, 'light', 'en', FALSE, TRUE, '20:00:00');

-- Inserir estatísticas dos usuários
INSERT INTO user_stats (user_id, total_rotinas, total_tarefas_concluidas, tempo_total_estudado, streak_dias, ultimo_estudo) VALUES 
(1, 0, 0, 0, 0, NULL),
(2, 0, 0, 0, 0, NULL),
(3, 0, 0, 0, 0, NULL);

-- Inserir rotinas de exemplo
INSERT INTO routines (user_id, titulo, tema, nivel, tempo_diario, dias_disponiveis, horario_disponivel, progresso, status, data_inicio) VALUES 
(1, 'Aprender Python - Nível Iniciante', 'Python', 'iniciante', 60, '["segunda", "terca", "quarta", "quinta", "sexta"]', '19:00:00', 25.50, 'ativa', '2024-01-15'),
(1, 'Aprender JavaScript - Nível Intermediário', 'JavaScript', 'intermediario', 45, '["sabado", "domingo"]', '14:00:00', 0.00, 'ativa', '2024-01-20'),
(2, 'Aprender Coreano - Nível Iniciante', 'Coreano', 'iniciante', 90, '["segunda", "quarta", "sexta"]', '20:00:00', 60.75, 'ativa', '2024-01-10'),
(3, 'Aprender Matemática - Nível Avançado', 'Matemática', 'avancado', 120, '["terca", "quinta", "sabado"]', '18:00:00', 40.25, 'ativa', '2024-01-05');

-- Inserir tarefas de exemplo para a primeira rotina (Python)
INSERT INTO tasks (routine_id, titulo, descricao, dia_estudo, ordem, status, material_estudo, tempo_estimado, dificuldade) VALUES 
(1, 'Introdução ao Python', 'Conceitos básicos e instalação do Python', 1, 1, 'concluida', '{"videos": [{"id": "kqtD5dpn9C8", "title": "Python para Iniciantes", "url": "https://www.youtube.com/watch?v=kqtD5dpn9C8"}], "textos": ["Livro: Python para Iniciantes - Capítulo 1"], "exercicios": ["Exercício 1: Instalar Python", "Exercício 2: Primeiro programa"]}', 30, 'facil'),
(1, 'Variáveis e Tipos de Dados', 'Aprendendo sobre variáveis em Python', 1, 2, 'concluida', '{"videos": [{"id": "B7xai5u_tnk", "title": "Variáveis em Python", "url": "https://www.youtube.com/watch?v=B7xai5u_tnk"}], "textos": ["Livro: Python para Iniciantes - Capítulo 2"], "exercicios": ["Exercício 3: Criar variáveis", "Exercício 4: Tipos de dados"]}', 30, 'facil'),
(1, 'Estruturas de Controle', 'If, else, for e while em Python', 2, 1, 'pendente', '{"videos": [{"id": "dQw4w9WgXcQ", "title": "Estruturas de Controle Python", "url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ"}], "textos": ["Livro: Python para Iniciantes - Capítulo 3"], "exercicios": ["Exercício 5: Condicionais", "Exercício 6: Loops"]}', 45, 'medio'),
(1, 'Funções em Python', 'Criando e usando funções', 2, 2, 'pendente', '{"videos": [{"id": "kqtD5dpn9C8", "title": "Funções Python", "url": "https://www.youtube.com/watch?v=kqtD5dpn9C8"}], "textos": ["Livro: Python para Iniciantes - Capítulo 4"], "exercicios": ["Exercício 7: Criar funções", "Exercício 8: Parâmetros"]}', 45, 'medio'),
(1, 'Listas e Dicionários', 'Estruturas de dados em Python', 3, 1, 'pendente', '{"videos": [{"id": "B7xai5u_tnk", "title": "Listas Python", "url": "https://www.youtube.com/watch?v=B7xai5u_tnk"}], "textos": ["Livro: Python para Iniciantes - Capítulo 5"], "exercicios": ["Exercício 9: Trabalhar com listas", "Exercício 10: Dicionários"]}', 60, 'medio');

-- Inserir tarefas de exemplo para a segunda rotina (JavaScript)
INSERT INTO tasks (routine_id, titulo, descricao, dia_estudo, ordem, status, material_estudo, tempo_estimado, dificuldade) VALUES 
(2, 'JavaScript Intermediário - DOM', 'Manipulação do DOM com JavaScript', 1, 1, 'pendente', '{"videos": [{"id": "B7xai5u_tnk", "title": "JavaScript DOM", "url": "https://www.youtube.com/watch?v=B7xai5u_tnk"}], "textos": ["Livro: JavaScript Avançado - Capítulo 1"], "exercicios": ["Exercício 1: Selecionar elementos", "Exercício 2: Modificar conteúdo"]}', 45, 'medio'),
(2, 'Eventos em JavaScript', 'Trabalhando com eventos', 1, 2, 'pendente', '{"videos": [{"id": "kqtD5dpn9C8", "title": "JavaScript Events", "url": "https://www.youtube.com/watch?v=kqtD5dpn9C8"}], "textos": ["Livro: JavaScript Avançado - Capítulo 2"], "exercicios": ["Exercício 3: Adicionar eventos", "Exercício 4: Remover eventos"]}', 45, 'medio');

-- Inserir tarefas de exemplo para a terceira rotina (Coreano)
INSERT INTO tasks (routine_id, titulo, descricao, dia_estudo, ordem, status, material_estudo, tempo_estimado, dificuldade) VALUES 
(3, 'Alfabeto Hangul', 'Aprendendo o alfabeto coreano', 1, 1, 'concluida', '{"videos": [{"id": "dQw4w9WgXcQ", "title": "Hangul Básico", "url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ"}], "textos": ["Livro: Coreano para Iniciantes - Capítulo 1"], "exercicios": ["Exercício 1: Praticar Hangul", "Exercício 2: Sons básicos"]}', 60, 'facil'),
(3, 'Cumprimentos Básicos', 'Saudações em coreano', 1, 2, 'concluida', '{"videos": [{"id": "kqtD5dpn9C8", "title": "Cumprimentos Coreanos", "url": "https://www.youtube.com/watch?v=kqtD5dpn9C8"}], "textos": ["Livro: Coreano para Iniciantes - Capítulo 2"], "exercicios": ["Exercício 3: Praticar cumprimentos", "Exercício 4: Pronúncia"]}', 30, 'facil'),
(3, 'Números em Coreano', 'Aprendendo números de 1 a 100', 2, 1, 'pendente', '{"videos": [{"id": "B7xai5u_tnk", "title": "Números Coreanos", "url": "https://www.youtube.com/watch?v=B7xai5u_tnk"}], "textos": ["Livro: Coreano para Iniciantes - Capítulo 3"], "exercicios": ["Exercício 5: Contar em coreano", "Exercício 6: Números grandes"]}', 45, 'medio');

-- Inserir tarefas de exemplo para a quarta rotina (Matemática)
INSERT INTO tasks (routine_id, titulo, descricao, dia_estudo, ordem, status, material_estudo, tempo_estimado, dificuldade) VALUES 
(4, 'Cálculo Avançado - Integrais', 'Integrais complexas e aplicações', 1, 1, 'pendente', '{"videos": [{"id": "dQw4w9WgXcQ", "title": "Integrais Avançadas", "url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ"}], "textos": ["Livro: Cálculo Avançado - Capítulo 1"], "exercicios": ["Exercício 1: Integrais por partes", "Exercício 2: Substituição trigonométrica"]}', 90, 'dificil'),
(4, 'Álgebra Linear - Espaços Vetoriais', 'Conceitos avançados de álgebra linear', 1, 2, 'pendente', '{"videos": [{"id": "kqtD5dpn9C8", "title": "Espaços Vetoriais", "url": "https://www.youtube.com/watch?v=kqtD5dpn9C8"}], "textos": ["Livro: Álgebra Linear Avançada - Capítulo 1"], "exercicios": ["Exercício 3: Base e dimensão", "Exercício 4: Transformações lineares"]}', 90, 'dificil');

-- Inserir progresso diário de exemplo
INSERT INTO daily_progress (user_id, routine_id, data, tarefas_concluidas, total_tarefas, tempo_estudado) VALUES 
(1, 1, '2024-01-15', 2, 2, 60),
(1, 1, '2024-01-16', 0, 2, 0),
(2, 3, '2024-01-10', 2, 2, 90),
(2, 3, '2024-01-12', 0, 1, 0);

-- Inserir logs de atividade de exemplo
INSERT INTO activity_logs (user_id, acao, detalhes, ip_address, user_agent) VALUES 
(1, 'login', '{"timestamp": "2024-01-15 19:00:00"}', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(1, 'criar_rotina', '{"rotina_id": 1, "tema": "Python"}', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(2, 'login', '{"timestamp": "2024-01-10 20:00:00"}', '192.168.1.101', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(2, 'criar_rotina', '{"rotina_id": 3, "tema": "Coreano"}', '192.168.1.101', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

-- Popular tabela planos com planos padrão
INSERT INTO planos (nome, slug, preco_mensal, descricao, recursos, ativo) VALUES
('Free', 'free', 0.00, 'Plano gratuito com funcionalidades básicas', 
 '["rotinas_gerais", "resumos_basicos"]', TRUE),
('ENEM+', 'enem_plus', 29.90, 'Plano completo para preparação ENEM', 
 '["rotinas_gerais", "modo_enem", "resumos_completos", "suporte_prioritario"]', TRUE),
('Concurso+', 'concurso_plus', 39.90, 'Plano completo para concursos públicos', 
 '["rotinas_gerais", "modo_concurso", "resumos_completos", "suporte_prioritario"]', TRUE),
('Premium', 'premium', 49.90, 'Plano completo com todos os recursos', 
 '["rotinas_gerais", "modo_enem", "modo_concurso", "resumos_completos", "suporte_prioritario", "recursos_avancados"]', TRUE);
