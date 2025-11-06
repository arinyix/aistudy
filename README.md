# 🧠 AIStudy - Plataforma de Estudos Inteligente

Uma plataforma web completa desenvolvida em **PHP**, **CSS**, **JavaScript** e **MySQL** que utiliza **inteligência artificial (ChatGPT)** para criar rotinas de estudos personalizadas e cronogramas inteligentes.

## ✨ Funcionalidades Principais

### 🔐 **Sistema de Autenticação**
- Login e cadastro de usuários
- Gerenciamento de sessões seguro
- Hash de senhas com PHP

### 📚 **Criação de Rotinas Inteligentes**
- Geração automática de planos de estudos via IA
- Cronograma personalizado baseado nos dias escolhidos
- Materiais de estudo com vídeos reais do YouTube
- Sistema de fallback quando API não funciona

### 📅 **Calendário Real**
- Tarefas organizadas por dias específicos
- Cronograma baseado nos dias escolhidos pelo usuário
- Datas reais: 21/10, 22/10, 28/10, etc.
- Horários respeitados pelo sistema

### 📊 **Acompanhamento de Progresso**
- Estatísticas detalhadas de desempenho
- Gráficos interativos com Chart.js
- Relatórios de progresso por rotina
- Visualização de progresso das rotinas

### 📄 **Resumo Auxiliar com IA**
- Geração de resumos detalhados dos tópicos
- Material de estudo completo com exercícios
- Visualização em PDF viewer
- Download de resumos formatados

### 🎨 **Interface Moderna**
- Design responsivo com Bootstrap 5
- Animações suaves e transições
- Pop-ups para materiais de estudo
- Interface intuitiva e amigável

## 📋 Pré-requisitos

### **Sistema Necessário:**
- **XAMPP** (Apache, MySQL, PHP 7.4+) - [Baixar aqui](https://www.apachefriends.org/)
- **Chave de API da OpenAI** (ChatGPT) - [Obter aqui](https://platform.openai.com/api-keys)
- **Chave de API do YouTube Data API v3** - [Obter aqui](https://console.cloud.google.com/)
- **Navegador web moderno** (Chrome, Firefox, Safari, Edge)
- **Conexão com internet** (para APIs externas e CDNs)

### **📚 Resumo Rápido das Bibliotecas:**

#### **✅ Bibliotecas JavaScript (CDN - Automático):**
- Bootstrap 5.1.3 (CSS + JS)
- Font Awesome 6.0.0
- Chart.js
- marked.js

#### **✅ Bibliotecas PHP (Nativas):**
- PDO
- cURL
- JSON

#### **⚠️ APIs Externas (Requerem Configuração):**
- OpenAI API (ChatGPT)
- YouTube Data API v3

#### **📄 Opcional:**
- DomPDF (para PDFs - ver `INSTALAR_PDF.txt`)

**📖 Para detalhes completos, veja a seção [📚 Bibliotecas e Dependências](#-bibliotecas-e-dependências) abaixo.**

### **Extensões PHP Necessárias:**
- **PDO** (habilitado por padrão no XAMPP)
- **PDO MySQL** (habilitado por padrão no XAMPP)
- **cURL** (habilitado por padrão no XAMPP)
- **JSON** (habilitado por padrão no XAMPP)
- **OpenSSL** (para requisições HTTPS)

**Verificar Extensões:**
```php
// Criar arquivo test-extensions.php
<?php
echo "PDO: " . (extension_loaded('pdo') ? '✅' : '❌') . "<br>";
echo "cURL: " . (extension_loaded('curl') ? '✅' : '❌') . "<br>";
echo "JSON: " . (extension_loaded('json') ? '✅' : '❌') . "<br>";
?>
```

## 📚 Bibliotecas e Dependências

### **📦 Bibliotecas JavaScript (via CDN - Não Requer Instalação):**

#### **1. Bootstrap 5.1.3**
- **Uso:** Framework CSS/JS para interface responsiva
- **Onde é usado:** Todas as páginas do sistema
- **CDN CSS:** `https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css`
- **CDN JS:** `https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js`
- **Páginas que usam:** `dashboard.php`, `rotinas.php`, `criar-rotina.php`, `rotina-detalhada.php`, `progresso.php`, `configuracoes.php`, `login.php`

#### **2. Font Awesome 6.0.0**
- **Uso:** Biblioteca de ícones
- **Onde é usado:** Todas as páginas do sistema
- **CDN:** `https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css`
- **Páginas que usam:** Todas as páginas principais

#### **3. Chart.js**
- **Uso:** Gráficos interativos para visualização de progresso
- **Onde é usado:** `progresso.php`
- **CDN:** `https://cdn.jsdelivr.net/npm/chart.js`
- **Funcionalidade:** Gráficos de pizza e barras para progresso das rotinas

#### **4. marked.js**
- **Uso:** Conversor de Markdown para HTML (para Resumo Auxiliar)
- **Onde é usado:** `rotina-detalhada.php`
- **CDN:** `https://cdn.jsdelivr.net/npm/marked/marked.min.js`
- **Funcionalidade:** Renderiza conteúdo Markdown dos resumos auxiliares gerados pela IA

### **🔧 Bibliotecas PHP (Nativas - Não Requer Instalação):**

#### **1. PDO (PHP Data Objects)**
- **Uso:** Conexão com banco de dados MySQL
- **Status:** Nativo do PHP 7.4+
- **Arquivos que usam:** `config/database.php`, todas as classes em `classes/`

#### **2. cURL**
- **Uso:** Requisições HTTP para APIs (OpenAI, YouTube)
- **Status:** Nativo do PHP (geralmente habilitado)
- **Arquivos que usam:** `config/api.php`, `classes/YouTubeService.php`

#### **3. JSON**
- **Uso:** Codificação/decodificação de dados JSON
- **Status:** Nativo do PHP (sempre habilitado)
- **Arquivos que usam:** Todos os arquivos que lidam com APIs

### **🌐 APIs Externas:**

#### **1. OpenAI API (ChatGPT)**
- **Uso:** Geração de planos de estudo e resumos auxiliares
- **Chave:** Configurada em `config/api.php`
- **Endpoint:** `https://api.openai.com/v1/chat/completions`
- **Modelo usado:** `gpt-4o-mini`
- **Como obter:** [https://platform.openai.com/api-keys](https://platform.openai.com/api-keys)
- **Arquivos que usam:** `config/api.php`, `criar-rotina.php`, `gerar-resumo.php`

#### **2. YouTube Data API v3**
- **Uso:** Busca de vídeos educacionais para materiais de estudo
- **Chave:** Configurada em `classes/YouTubeService.php`
- **Endpoint:** `https://www.googleapis.com/youtube/v3/`
- **Como obter:** 
  1. Acesse [Google Cloud Console](https://console.cloud.google.com/)
  2. Crie um projeto
  3. Habilite "YouTube Data API v3"
  4. Crie uma chave de API
- **Arquivos que usam:** `classes/YouTubeService.php`, `criar-rotina.php`

### **📄 Bibliotecas Opcionais:**

#### **1. DomPDF (Opcional - Para Geração de PDFs)**
- **Uso:** Conversão de HTML/Markdown para PDF
- **Status:** Opcional (sistema funciona sem ela)
- **Instalação Manual:**
  1. Baixe de: [https://github.com/dompdf/dompdf/releases](https://github.com/dompdf/dompdf/releases)
  2. Extraia para: `vendor/dompdf/`
- **Instalação via Composer:**
  ```bash
  composer require dompdf/dompdf
  ```
- **Arquivos relacionados:** `classes/PdfGenerator.php`, `gerar-resumo.php`
- **Instruções completas:** Ver `INSTALAR_PDF.txt`

### **📁 Arquivos JavaScript Locais:**

#### **1. dark-mode.js**
- **Localização:** `assets/js/dark-mode.js`
- **Uso:** Sistema de modo escuro/claro
- **Páginas que usam:** Todas as páginas principais
- **Funcionalidade:** Toggle de tema, persistência via localStorage

## 🔧 Configuração de APIs

### **Passo 1: Configurar OpenAI API**

1. **Obter Chave:**
   - Acesse: https://platform.openai.com/api-keys
   - Faça login na sua conta OpenAI
   - Clique em **"Create new secret key"**
   - Copie a chave gerada (começa com `sk-`)

2. **Configurar no Sistema:**
   - Abra: `config/api.php`
   - Encontre a linha:
     ```php
     define('OPENAI_API_KEY', 'sua-chave-api-aqui');
     ```
   - Substitua por:
     ```php
     define('OPENAI_API_KEY', 'sk-sua-chave-real-aqui');
     ```

### **Passo 2: Configurar YouTube Data API**

1. **Obter Chave:**
   - Acesse: https://console.cloud.google.com/
   - Crie um novo projeto (ou selecione existente)
   - No menu, vá em **"APIs e Serviços" > "Biblioteca"**
   - Procure por **"YouTube Data API v3"**
   - Clique em **"Ativar"**
   - Vá em **"Credenciais" > "Criar credenciais" > "Chave de API"**
   - Copie a chave gerada

2. **Configurar no Sistema:**
   - Abra: `classes/YouTubeService.php`
   - Encontre a linha:
     ```php
     private $apiKey = 'SUA_CHAVE_AQUI';
     ```
   - Substitua por sua chave da API do YouTube

### **Passo 3: Verificar Extensões PHP**

No XAMPP, as extensões geralmente já vêm habilitadas. Para verificar:

1. **Criar arquivo:** `test-extensions.php`
2. **Adicionar código:**
   ```php
   <?php
   phpinfo();
   ?>
   ```
3. **Acessar:** http://localhost/aistudy/test-extensions.php
4. **Verificar:** Procure por "curl", "pdo", "json" na página

**Se alguma extensão estiver faltando:**

1. Abra: `C:\xampp\php\php.ini`
2. Procure pelas linhas e remova o `;` (ponto e vírgula) do início:
   ```ini
   extension=curl
   extension=pdo_mysql
   extension=openssl
   ```
3. Reinicie o Apache no XAMPP

## 🛠️ Instalação Passo a Passo

### **Passo 1: Instalar e Configurar XAMPP**

1. **Baixar XAMPP:**
   - Acesse: https://www.apachefriends.org/
   - Baixe a versão para Windows
   - Instale normalmente

2. **Iniciar Serviços:**
   - Abra o XAMPP Control Panel
   - Clique em **Start** para **Apache**
   - Clique em **Start** para **MySQL**
   - ✅ Ambos devem ficar verdes

3. **Verificar Funcionamento:**
   - Acesse: http://localhost
   - Deve aparecer a página do XAMPP

### **Passo 2: Configurar Banco de Dados MySQL**

1. **Acessar phpMyAdmin:**
   - Vá para: http://localhost/phpmyadmin
   - Login: **root** (sem senha)

2. **Criar Banco de Dados:**
   - Clique em **"Novo"** no menu lateral
   - Nome do banco: **`aistudy`**
   - Clique em **"Criar"**

3. **Importar Estrutura:**
   - Selecione o banco **`aistudy`**
   - Clique na aba **"Importar"**
   - Clique em **"Escolher arquivo"**
   - Selecione o arquivo **`schema.sql`**
   - Clique em **"Executar"**

4. **Importar Dados Iniciais:**
   - Na mesma aba **"Importar"**
   - Selecione o arquivo **`seed.sql`**
   - Clique em **"Executar"**

### **Passo 3: Configurar Chaves de API**

**⚠️ IMPORTANTE:** Você precisa configurar 2 APIs para o sistema funcionar completamente.

#### **3.1. Criar arquivo `.env`:**

1. **Copiar o arquivo de exemplo:**
   ```bash
   # Windows (PowerShell)
   Copy-Item .env.example .env
   
   # Linux/Mac
   cp .env.example .env
   ```

2. **Editar o arquivo `.env`:**
   - Abra o arquivo `.env` na raiz do projeto
   - Preencha com suas chaves reais (veja os passos abaixo)

#### **3.2. Obter e Configurar OpenAI API Key (ChatGPT):**

1. **Obter Chave:**
   - Acesse: https://platform.openai.com/api-keys
   - Faça login na sua conta OpenAI
   - Clique em **"Create new secret key"**
   - Copie a chave gerada (começa com `sk-`)

2. **Configurar no `.env`:**
   - Abra o arquivo `.env`
   - Encontre a linha: `OPENAI_API_KEY=sk-sua-chave-openai-aqui`
   - Substitua por sua chave real:
     ```env
     OPENAI_API_KEY=sk-sua-chave-real-aqui
     ```

#### **3.3. Obter e Configurar YouTube Data API v3 Key:**

1. **Obter Chave:**
   - Acesse: https://console.cloud.google.com/
   - Crie um novo projeto (ou selecione existente)
   - No menu, vá em **"APIs e Serviços" > "Biblioteca"**
   - Procure por **"YouTube Data API v3"**
   - Clique em **"Ativar"**
   - Vá em **"Credenciais" > "Criar credenciais" > "Chave de API"**
   - Copie a chave gerada

2. **Configurar no `.env`:**
   - Abra o arquivo `.env`
   - Encontre a linha: `YOUTUBE_API_KEY=sua-chave-youtube-aqui`
   - Substitua por sua chave real:
     ```env
     YOUTUBE_API_KEY=sua-chave-real-aqui
     ```

**📖 Para instruções detalhadas, veja o arquivo `CONFIGURAR_ENV.md`**

### **Passo 4: Instalar Arquivos do Sistema**

1. **Copiar Arquivos:**
   - Copie toda a pasta `aistudy` para: `C:\xampp\htdocs\`
   - Caminho final: `C:\xampp\htdocs\aistudy\`

2. **Verificar Estrutura:**
   ```
   C:\xampp\htdocs\aistudy\
   ├── assets/
   ├── classes/
   ├── config/
   ├── includes/
   ├── api/
   ├── *.php
   ├── *.sql
   └── README.md
   ```

### **Passo 5: Testar Instalação**

1. **Acessar o Sistema:**
   - Abra o navegador
   - Vá para: http://localhost/aistudy
   - Deve aparecer a página de login

2. **Testar Login:**
   - Use os dados de exemplo:
     - **Email:** joao@email.com
     - **Senha:** password

## 🎯 Tutorial de Uso Completo

### **1. Primeiro Acesso ao Sistema**

1. **Acessar o Sistema:**
   - Abra o navegador
   - Vá para: http://localhost/aistudy
   - Você verá a página de login

2. **Criar Conta:**
   - Clique em **"Não tem conta? Cadastre-se"**
   - Preencha: Nome, Email, Senha
   - Clique em **"Cadastrar"**

3. **Fazer Login:**
   - Use suas credenciais
   - Ou use os dados de exemplo:
     - **Email:** joao@email.com
     - **Senha:** password

### **2. Criando sua Primeira Rotina de Estudos**

1. **Acessar Criação:**
   - No Dashboard, clique em **"Criar Nova Rotina"**
   - Ou use o botão **"Nova Rotina"** no menu

2. **Preencher Dados:**
   - **Tema:** "Matemática", "Programação", "Física", etc.
   - **Nível:** Iniciante, Intermediário ou Avançado
   - **Tempo Diário:** 30, 60, 90 minutos
   - **Dias Disponíveis:** Marque terça, quarta, etc.
   - **Horário:** 19:00, 20:00, etc.

3. **Gerar Plano:**
   - Clique em **"Gerar Plano de Estudos"**
   - A IA criará um cronograma personalizado
   - Sistema funciona mesmo sem API (fallback)

### **3. Usando o Cronograma de Estudos**

1. **Ver Tarefas de Hoje:**
   - Dashboard mostra tarefas do dia atual
   - Só aparecem se hoje for dia de estudo
   - Horário específico é respeitado

2. **Acessar Rotina Detalhada:**
   - Clique em **"Minhas Rotinas"**
   - Selecione uma rotina
   - Veja o cronograma completo

3. **Estudar com Materiais:**
   - Clique em **"Ver Materiais"** em qualquer tarefa
   - Pop-up abre com vídeos do YouTube
   - Links para textos e exercícios

4. **Marcar Tarefas Concluídas:**
   - Clique em **"Marcar"** quando terminar
   - Progresso é atualizado automaticamente
   - Sistema calcula percentual de conclusão

### **4. Usando Resumo Auxiliar**

1. **Gerar Resumo:**
   - Na rotina detalhada, clique em **"Resumo Auxiliar"** em qualquer tarefa
   - Sistema gera resumo completo com IA
   - Inclui: conceitos fundamentais, exemplos práticos e 15 exercícios

2. **Visualizar Conteúdo:**
   - Resumo é exibido em modal fullscreen
   - Formatação profissional tipo PDF
   - Navegação fácil pelo conteúdo

3. **Download/Imprimir:**
   - Clique em **"Imprimir/Salvar PDF"** para salvar como PDF
   - Ou **"Download HTML"** para salvar arquivo HTML
   - Conteúdo formatado e pronto para estudo

### **5. Acompanhando seu Progresso**

1. **Dashboard com Estatísticas:**
   - Total de rotinas
   - Rotinas ativas
   - Tarefas concluídas hoje
   - Progresso geral

2. **Página de Progresso:**
   - Gráficos de desempenho com Chart.js
   - Progresso visualizado por rotina
   - Relatórios detalhados
   - Filtros por período

3. **Cronograma Visual:**
   - Próximos dias de estudo
   - Datas reais: 21/10, 22/10, etc.
   - Horários organizados
   - Tarefas pendentes

## 📁 Estrutura Completa do Projeto

```
aistudy/
├── 📁 assets/                    # Recursos estáticos
│   └── 📁 css/
│       └── 📄 style.css          # Estilos principais (Bootstrap + custom)
│
├── 📁 classes/                   # Classes PHP (Modelo MVC)
│   ├── 📄 User.php              # Gerenciamento de usuários
│   ├── 📄 Routine.php           # Gerenciamento de rotinas
│   ├── 📄 Task.php              # Gerenciamento de tarefas
│   ├── 📄 Quiz.php              # Gerenciamento de quizzes
│   ├── 📄 Calendar.php          # Sistema de calendário real
│   └── 📄 YouTubeSearch.php     # Busca de vídeos educacionais
│
├── 📁 config/                   # Configurações do sistema
│   ├── 📄 database.php          # Configuração do banco MySQL
│   ├── 📄 api.php               # Configuração da API OpenAI
│   └── 📄 fallback-data.php     # Dados de fallback (sem API)
│
├── 📁 includes/                 # Arquivos de inclusão
│   └── 📄 session.php           # Gerenciamento de sessões
│
├── 📁 api/                      # APIs REST
│   └── 📄 toggle-task.php       # API para marcar tarefas
│
├── 📄 index.php                 # Página inicial (redireciona)
├── 📄 login.php                 # Login e cadastro
├── 📄 dashboard.php             # Dashboard principal
├── 📄 rotinas.php               # Lista de rotinas
├── 📄 criar-rotina.php          # Criação de rotinas
├── 📄 rotina-detalhada.php      # Detalhes da rotina
├── 📄 gerar-quiz.php            # Geração de quiz
├── 📄 quiz.php                  # Página do quiz
├── 📄 progresso.php             # Página de progresso
├── 📄 configuracoes.php         # Configurações do usuário
├── 📄 logout.php                # Logout
│
├── 📄 schema.sql                # Estrutura do banco MySQL
├── 📄 seed.sql                  # Dados iniciais (usuários exemplo)
└── 📄 README.md                 # Este arquivo
```

### **📋 Descrição dos Arquivos Principais:**

#### **🔐 Autenticação:**
- **`login.php`** - Página de login/cadastro com validação
- **`includes/session.php`** - Gerenciamento de sessões seguras

#### **🏠 Interface Principal:**
- **`dashboard.php`** - Dashboard com calendário real e estatísticas
- **`rotinas.php`** - Lista de rotinas com cards visuais
- **`criar-rotina.php`** - Formulário de criação com IA

#### **📚 Sistema de Estudos:**
- **`rotina-detalhada.php`** - Cronograma detalhado com materiais
- **`gerar-quiz.php`** - Geração de quiz com IA
- **`quiz.php`** - Interface do quiz com correção automática

#### **📊 Acompanhamento:**
- **`progresso.php`** - Gráficos e estatísticas de desempenho
- **`configuracoes.php`** - Configurações do usuário

#### **🔧 Classes PHP:**
- **`User.php`** - CRUD de usuários, autenticação
- **`Routine.php`** - CRUD de rotinas, progresso
- **`Task.php`** - CRUD de tarefas, status
- **`Quiz.php`** - CRUD de quizzes, correção
- **`Calendar.php`** - Sistema de calendário real
- **`YouTubeSearch.php`** - Busca de vídeos educacionais

#### **⚙️ Configurações:**
- **`database.php`** - Conexão com MySQL
- **`api.php`** - Integração com OpenAI ChatGPT
- **`fallback-data.php`** - Dados quando API falha

## 🔧 Configurações Avançadas

### **Personalizar Interface**

Edite o arquivo `assets/css/style.css` para personalizar:
- **Cores**: Gradientes, botões, cards
- **Fontes**: Tamanhos, estilos
- **Layout**: Espaçamentos, bordas
- **Animações**: Transições, hover effects

### **Configurar Banco de Dados**

Se usar configurações diferentes do XAMPP padrão, edite `config/database.php`:

```php
define('DB_HOST', 'localhost');        // Servidor MySQL
define('DB_NAME', 'aistudy');         // Nome do banco
define('DB_USER', 'root');            // Usuário MySQL
define('DB_PASS', '');                // Senha MySQL
```

### **Configurar API OpenAI**

Para usar versão diferente da API, edite `config/api.php`:

```php
define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');
define('OPENAI_API_KEY', 'sk-sua-chave-aqui');
```

### **Adicionar Mais Vídeos**

Edite `classes/YouTubeSearch.php` para adicionar:
- Novos canais educacionais
- Vídeos por tema específico
- Links de recursos externos

## 🐛 Solução de Problemas

### **❌ Erro de Conexão com Banco**

**Problema:** "Connection failed"
**Solução:**
1. Verifique se MySQL está rodando no XAMPP
2. Confirme credenciais em `config/database.php`
3. Teste conexão no phpMyAdmin

### **❌ Erro na API do ChatGPT**

**Problema:** "API Error" ou quiz não gera
**Solução:**
1. Verifique se chave da API está correta
2. Confirme se tem créditos na conta OpenAI
3. Sistema tem fallback automático (funciona sem API)

### **❌ Páginas não Carregam**

**Problema:** "404 Not Found" ou erro PHP
**Solução:**
1. Verifique se Apache está rodando
2. Confirme se arquivos estão em `C:\xampp\htdocs\aistudy\`
3. Verifique logs de erro do Apache

### **❌ Dashboard com Erro**

**Problema:** "Fatal error" no dashboard
**Solução:**
1. Verifique se banco foi criado corretamente
2. Confirme se tabelas existem
3. Teste com dados de exemplo

### **❌ Quiz não Funciona**

**Problema:** Quiz não gera ou não carrega
**Solução:**
1. Sistema tem fallback automático
2. Deve funcionar mesmo sem API
3. Verifique se há erros no console

## 📝 Dados de Exemplo

O arquivo `seed.sql` inclui usuários de teste:

| Email | Senha | Nome |
|-------|-------|------|
| joao@email.com | password | João Silva |
| maria@email.com | password | Maria Santos |
| pedro@email.com | password | Pedro Costa |

**Rotinas de Exemplo:**
- Álgebra Linear (Intermediário)
- Programação Python (Iniciante)
- Machine Learning (Avançado)

## 🚀 Funcionalidades Futuras

### **Próximas Implementações:**
- 📧 **Notificações por email** para lembretes
- 🏆 **Sistema de badges** e conquistas
- 💬 **Chat com IA** para dúvidas
- 📊 **Exportação de relatórios** em PDF
- 📱 **App mobile** para Android/iOS
- 🔔 **Notificações push** no navegador

### **Melhorias Planejadas:**
- 🎨 **Temas personalizáveis** (claro/escuro)
- 🌍 **Múltiplos idiomas** (inglês, espanhol)
- 📈 **Analytics avançados** de progresso
- 🤝 **Sistema colaborativo** entre usuários
- 🎯 **Gamificação** com pontos e rankings

## 📞 Suporte e Ajuda

### **Documentação:**
1. **README.md** - Este arquivo com instruções completas
2. **Logs do Apache** - Para erros de servidor
3. **Console do navegador** - Para erros JavaScript

### **Verificações Básicas:**
1. ✅ XAMPP rodando (Apache + MySQL)
2. ✅ Banco `aistudy` criado
3. ✅ Arquivos em `C:\xampp\htdocs\aistudy\`
4. ✅ Chave da API configurada
5. ✅ Acesso a http://localhost/aistudy

### **Contato:**
- 📧 **Email:** suporte@aistudy.com
- 💬 **Discord:** AIStudy Community
- 📖 **Wiki:** aistudy.com/docs

---

## 🎉 **AIStudy** - Transformando o aprendizado com inteligência artificial! 

**Desenvolvido com ❤️ em PHP, JavaScript e IA**

*Versão 1.0 - Sistema completo de estudos inteligentes* 🧠✨
#
