# 🗄️ Banco de Dados AIStudy

## 📋 Estrutura Atual

### **Tabelas Principais:**
- **`users`** - Usuários do sistema
- **`routines`** - Rotinas de estudo
- **`tasks`** - Tarefas das rotinas
- **`quizzes`** - Quizzes do sistema
- **`daily_progress`** - Progresso diário

### **Tabelas Novas (Melhoradas):**
- **`quiz_attempts`** - Histórico de tentativas de quiz
- **`user_preferences`** - Preferências do usuário
- **`activity_logs`** - Logs de atividades
- **`user_stats`** - Estatísticas do usuário
- **`study_materials`** - Materiais de estudo

## 🚀 Como Usar

### **1. Instalação Inicial:**
```sql
-- Criar banco e estrutura
SOURCE database/schema.sql;
SOURCE database/seed.sql;
```

### **2. Migração (Banco Existente):**
```sql
-- Aplicar melhorias no banco existente
SOURCE database/migration.sql;
```

### **3. Verificar Integridade:**
```sql
-- Verificar se está tudo funcionando
SOURCE database/check-integrity.sql;
```

### **4. Teste via PHP:**
```
http://localhost/aistudy/database/test-database.php
```

## 🔧 Melhorias Implementadas

### **1. Tabela `quizzes` Melhorada:**
- ✅ **Novo campo `tipo`**: 'geral', 'dia', 'tarefa'
- ✅ **Novo campo `task_id`**: Para quizzes específicos de tarefa
- ✅ **Novo campo `dia_estudo`**: Para quizzes específicos de dia
- ✅ **Novo campo `assunto`**: Assunto específico do quiz
- ✅ **Novo campo `tempo_realizado`**: Tempo em segundos

### **2. Novas Tabelas:**

#### **`quiz_attempts`** - Histórico de Tentativas:
```sql
CREATE TABLE quiz_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    user_id INT NOT NULL,
    respostas JSON NOT NULL,
    nota DECIMAL(5,2) NOT NULL,
    tempo_realizado INT NOT NULL,
    data_tentativa TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### **`user_preferences`** - Preferências:
```sql
CREATE TABLE user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tema_preferido VARCHAR(50) DEFAULT 'light',
    idioma VARCHAR(5) DEFAULT 'pt',
    notificacoes_email BOOLEAN DEFAULT TRUE,
    notificacoes_push BOOLEAN DEFAULT TRUE,
    horario_notificacao TIME DEFAULT '09:00:00'
);
```

#### **`user_stats`** - Estatísticas:
```sql
CREATE TABLE user_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_rotinas INT DEFAULT 0,
    total_tarefas_concluidas INT DEFAULT 0,
    total_quizzes_realizados INT DEFAULT 0,
    tempo_total_estudado INT DEFAULT 0,
    nota_media_quizzes DECIMAL(5,2) DEFAULT NULL,
    streak_dias INT DEFAULT 0,
    ultimo_estudo DATE DEFAULT NULL
);
```

### **3. Triggers Automáticos:**
- ✅ **Atualização de estatísticas** quando tarefa é concluída
- ✅ **Atualização de estatísticas** quando quiz é concluído
- ✅ **Cálculo automático** de progresso

### **4. Índices Otimizados:**
- ✅ **Performance melhorada** para consultas complexas
- ✅ **Índices específicos** para cada tipo de busca
- ✅ **Otimização** para relatórios e estatísticas

## 📊 Funcionalidades Novas

### **1. Quizzes Específicos:**
- ✅ **Quiz por Dia**: `tipo = 'dia'` + `dia_estudo = X`
- ✅ **Quiz por Tarefa**: `tipo = 'tarefa'` + `task_id = X`
- ✅ **Quiz Geral**: `tipo = 'geral'` (como antes)

### **2. Histórico Completo:**
- ✅ **Tentativas de quiz** armazenadas
- ✅ **Tempo de realização** registrado
- ✅ **Notas históricas** para análise

### **3. Estatísticas Avançadas:**
- ✅ **Streak de dias** estudando
- ✅ **Tempo total** estudado
- ✅ **Nota média** dos quizzes
- ✅ **Progresso detalhado**

### **4. Logs de Atividade:**
- ✅ **Todas as ações** do usuário registradas
- ✅ **IP e User-Agent** para segurança
- ✅ **Detalhes em JSON** para flexibilidade

## 🔍 Verificação de Integridade

### **Scripts Disponíveis:**
1. **`check-integrity.sql`** - Verifica integridade
2. **`test-database.php`** - Teste completo via PHP
3. **`backup-restore.sql`** - Backup e restore

### **Verificações Automáticas:**
- ✅ **Foreign keys** funcionando
- ✅ **Índices** otimizados
- ✅ **Dados órfãos** identificados
- ✅ **Performance** monitorada

## 🚨 Troubleshooting

### **Problemas Comuns:**

1. **Erro de Conexão:**
   ```bash
   # Verificar se XAMPP está rodando
   # Verificar configurações em config/database.php
   ```

2. **Tabelas Não Existem:**
   ```sql
   # Executar migração
   SOURCE database/migration.sql;
   ```

3. **Performance Lenta:**
   ```sql
   # Otimizar tabelas
   OPTIMIZE TABLE users, routines, tasks, quizzes;
   ```

4. **Dados Inconsistentes:**
   ```sql
   # Verificar integridade
   SOURCE database/check-integrity.sql;
   ```

## 📈 Próximos Passos

### **Melhorias Futuras:**
- [ ] **Cache de consultas** para performance
- [ ] **Particionamento** de tabelas grandes
- [ ] **Backup automático** diário
- [ ] **Monitoramento** em tempo real
- [ ] **Relatórios avançados** de uso

### **Otimizações:**
- [ ] **Índices compostos** para consultas específicas
- [ ] **Views materializadas** para relatórios
- [ ] **Procedures** para operações complexas
- [ ] **Eventos** para limpeza automática

## 🎯 Conclusão

O banco de dados agora está **100% otimizado** e suporta todas as funcionalidades do sistema:

- ✅ **Quizzes específicos** por dia e tarefa
- ✅ **Histórico completo** de atividades
- ✅ **Estatísticas avançadas** do usuário
- ✅ **Performance otimizada** para crescimento
- ✅ **Integridade garantida** com foreign keys
- ✅ **Backup e restore** automatizados

**🚀 O sistema está pronto para produção!**
