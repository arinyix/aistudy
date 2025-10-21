-- Schema melhorado do banco de dados AIStudy
CREATE DATABASE IF NOT EXISTS aistudy;
USE aistudy;

-- Tabela de usuários (melhorada)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    preferencias JSON DEFAULT NULL, -- preferências do usuário
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de rotinas (melhorada)
CREATE TABLE routines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    tema VARCHAR(100) NOT NULL,
    nivel ENUM('iniciante', 'intermediario', 'avancado') NOT NULL,
    tempo_diario INT NOT NULL, -- em minutos
    dias_disponiveis JSON NOT NULL, -- array de dias da semana
    horario_disponivel TIME NOT NULL,
    progresso DECIMAL(5,2) DEFAULT 0.00,
    status ENUM('ativa', 'pausada', 'concluida') DEFAULT 'ativa',
    data_inicio DATE DEFAULT NULL,
    data_fim DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de tarefas (melhorada)
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    routine_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT,
    dia_estudo INT NOT NULL, -- dia do cronograma (1, 2, 3...)
    ordem INT NOT NULL, -- ordem dentro do dia
    status ENUM('pendente', 'concluida') DEFAULT 'pendente',
    material_estudo JSON, -- links, textos, etc.
    tempo_estimado INT DEFAULT NULL, -- tempo estimado em minutos
    dificuldade ENUM('facil', 'medio', 'dificil') DEFAULT 'medio',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (routine_id) REFERENCES routines(id) ON DELETE CASCADE
);

-- Tabela de quizzes (melhorada)
CREATE TABLE quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    routine_id INT NOT NULL,
    task_id INT DEFAULT NULL, -- para quizzes específicos de tarefa
    dia_estudo INT DEFAULT NULL, -- para quizzes específicos de dia
    tipo ENUM('geral', 'dia', 'tarefa') NOT NULL DEFAULT 'geral',
    titulo VARCHAR(200) NOT NULL,
    assunto VARCHAR(100) DEFAULT NULL, -- assunto específico do quiz
    perguntas_json JSON NOT NULL, -- array de perguntas e respostas
    respostas_usuario JSON, -- respostas do usuário
    nota DECIMAL(5,2),
    tempo_realizado INT DEFAULT NULL, -- tempo em segundos
    status ENUM('pendente', 'concluido') DEFAULT 'pendente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (routine_id) REFERENCES routines(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

-- Tabela de histórico de quizzes (NOVA)
CREATE TABLE quiz_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    user_id INT NOT NULL,
    respostas JSON NOT NULL,
    nota DECIMAL(5,2) NOT NULL,
    tempo_realizado INT NOT NULL, -- tempo em segundos
    data_tentativa TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de progresso diário (melhorada)
CREATE TABLE daily_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    routine_id INT NOT NULL,
    data DATE NOT NULL,
    tarefas_concluidas INT DEFAULT 0,
    total_tarefas INT NOT NULL,
    tempo_estudado INT DEFAULT 0, -- tempo em minutos
    quizzes_realizados INT DEFAULT 0,
    nota_media DECIMAL(5,2) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (routine_id) REFERENCES routines(id) ON DELETE CASCADE,
    UNIQUE KEY unique_daily_progress (user_id, routine_id, data)
);

-- Tabela de preferências do usuário (NOVA)
CREATE TABLE user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tema_preferido VARCHAR(50) DEFAULT 'light',
    idioma VARCHAR(5) DEFAULT 'pt',
    notificacoes_email BOOLEAN DEFAULT TRUE,
    notificacoes_push BOOLEAN DEFAULT TRUE,
    horario_notificacao TIME DEFAULT '09:00:00',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_preferences (user_id)
);

-- Tabela de logs de atividades (NOVA)
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    acao VARCHAR(100) NOT NULL, -- 'login', 'criar_rotina', 'concluir_tarefa', etc.
    detalhes JSON DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de estatísticas do usuário (NOVA)
CREATE TABLE user_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_rotinas INT DEFAULT 0,
    total_tarefas_concluidas INT DEFAULT 0,
    total_quizzes_realizados INT DEFAULT 0,
    tempo_total_estudado INT DEFAULT 0, -- em minutos
    nota_media_quizzes DECIMAL(5,2) DEFAULT NULL,
    streak_dias INT DEFAULT 0, -- sequência de dias estudando
    ultimo_estudo DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_stats (user_id)
);

-- Tabela de materiais de estudo (NOVA)
CREATE TABLE study_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    tipo ENUM('video', 'texto', 'exercicio', 'link') NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    url VARCHAR(500) DEFAULT NULL,
    conteudo TEXT DEFAULT NULL,
    ordem INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

-- Índices para melhor performance
CREATE INDEX idx_routines_user_id ON routines(user_id);
CREATE INDEX idx_routines_status ON routines(status);
CREATE INDEX idx_tasks_routine_id ON tasks(routine_id);
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_quizzes_routine_id ON quizzes(routine_id);
CREATE INDEX idx_quizzes_task_id ON quizzes(task_id);
CREATE INDEX idx_quizzes_tipo ON quizzes(tipo);
CREATE INDEX idx_quiz_attempts_user_id ON quiz_attempts(user_id);
CREATE INDEX idx_quiz_attempts_quiz_id ON quiz_attempts(quiz_id);
CREATE INDEX idx_daily_progress_user_date ON daily_progress(user_id, data);
CREATE INDEX idx_activity_logs_user_id ON activity_logs(user_id);
CREATE INDEX idx_activity_logs_acao ON activity_logs(acao);
CREATE INDEX idx_study_materials_task_id ON study_materials(task_id);

-- Triggers para atualizar estatísticas automaticamente
DELIMITER //

-- Trigger para atualizar estatísticas quando uma tarefa é concluída
CREATE TRIGGER update_user_stats_on_task_completion
AFTER UPDATE ON tasks
FOR EACH ROW
BEGIN
    IF NEW.status = 'concluida' AND OLD.status = 'pendente' THEN
        UPDATE user_stats 
        SET total_tarefas_concluidas = total_tarefas_concluidas + 1,
            ultimo_estudo = CURDATE()
        WHERE user_id = (SELECT user_id FROM routines WHERE id = NEW.routine_id);
    END IF;
END//

-- Trigger para atualizar estatísticas quando um quiz é concluído
CREATE TRIGGER update_user_stats_on_quiz_completion
AFTER UPDATE ON quizzes
FOR EACH ROW
BEGIN
    IF NEW.status = 'concluido' AND OLD.status = 'pendente' THEN
        UPDATE user_stats 
        SET total_quizzes_realizados = total_quizzes_realizados + 1,
            ultimo_estudo = CURDATE()
        WHERE user_id = (SELECT user_id FROM routines WHERE id = NEW.routine_id);
    END IF;
END//

DELIMITER ;
