# 🧠 AIStudy - Plataforma de Estudos Inteligente

Aluna: Maria de Fatima Mota da Silva

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
- **Composer** (Gerenciador de dependências PHP) - [Baixar aqui](https://getcomposer.org/download/)
- **Chave de API da OpenAI** (ChatGPT) - [Obter aqui](https://platform.openai.com/api-keys)
- **Chave de API do YouTube Data API v3** - [Obter aqui](https://console.cloud.google.com/)
- **Chave de API do Stripe** (Para pagamentos) - [Obter aqui](https://dashboard.stripe.com/test/apikeys)
- **Stripe CLI** (Para webhooks locais - opcional) - [Instruções abaixo](#-configuração-do-stripe-cli)
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

#### **📦 Bibliotecas PHP via Composer:**
- **Stripe PHP SDK** (stripe/stripe-php ^19.0) - Integração com gateway de pagamento
- Instalado via: `composer install`

#### **⚠️ APIs Externas (Requerem Configuração):**
- OpenAI API (ChatGPT)
- YouTube Data API v3
- Stripe API (Para pagamentos e assinaturas)

#### **📄 Opcional:**
- DomPDF (para PDFs - ver `INSTALAR_PDF.txt`)
- Stripe CLI (para testar webhooks localmente)

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

### **Passo 3: Instalar Dependências com Composer**

**⚠️ IMPORTANTE:** O projeto usa Composer para gerenciar dependências PHP (Stripe SDK).

1. **Instalar Composer** (se ainda não tiver):
   - **Windows:** Baixe e execute: https://getcomposer.org/Composer-Setup.exe
   - **Linux/Mac:** 
     ```bash
     curl -sS https://getcomposer.org/installer | php
     sudo mv composer.phar /usr/local/bin/composer
     ```

2. **Instalar Dependências:**
   ```bash
   cd /opt/lampp/htdocs/aistudy  # ou C:\xampp\htdocs\aistudy no Windows
   composer install
   ```

   Isso instalará automaticamente:
   - `stripe/stripe-php` (SDK do Stripe para pagamentos)

3. **Verificar Instalação:**
   ```bash
   composer show
   ```
   
   Deve mostrar: `stripe/stripe-php`

### **Passo 4: Configurar Chaves de API**

**⚠️ IMPORTANTE:** Você precisa configurar 3 APIs para o sistema funcionar completamente.

#### **4.1. Criar arquivo `.env`:**

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

#### **4.2. Obter e Configurar OpenAI API Key (ChatGPT):**

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

#### **4.3. Obter e Configurar YouTube Data API v3 Key:**

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

#### **4.4. Obter e Configurar Stripe API Keys:**

1. **Obter Chaves:**
   - Acesse: https://dashboard.stripe.com/test/apikeys
   - Faça login na sua conta Stripe (ou crie uma conta gratuita)
   - Na seção **"Test mode"**, copie:
     - **Secret key** (começa com `sk_test_...`)
     - **Publishable key** (começa com `pk_test_...`)

2. **Configurar no `.env`:**
   - Abra o arquivo `.env`
   - Encontre as linhas:
     ```env
     STRIPE_SECRET_KEY=sk_test_sua-chave-secreta-stripe-aqui
     STRIPE_PUBLISHABLE_KEY=pk_test_sua-chave-publica-stripe-aqui
     ```
   - Substitua pelas suas chaves reais do Stripe

3. **Webhook Secret (Opcional para desenvolvimento):**
   - Veja a seção [Configuração do Stripe CLI](#-configuração-do-stripe-cli) abaixo
   - Ou deixe vazio durante desenvolvimento: `STRIPE_WEBHOOK_SECRET=`

**📖 Para instruções detalhadas, veja o arquivo `CONFIGURAR_ENV.md`**

### **Passo 5: Instalar Arquivos do Sistema**

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

### **Passo 6: Configurar Stripe CLI (Opcional - Para Testar Webhooks Localmente)**

**⚠️ OPCIONAL:** Necessário apenas se quiser testar webhooks localmente. O sistema funciona sem isso.

#### **6.1. Instalar Stripe CLI:**

O Stripe CLI já está incluído no projeto em `bin/stripe`. Se precisar reinstalar:

**Linux/Mac:**
```bash
cd /opt/lampp/htdocs/aistudy/bin
curl -L "https://github.com/stripe/stripe-cli/releases/download/v1.21.9/stripe_1.21.9_linux_x86_64.tar.gz" -o stripe-cli.tar.gz
tar -xzf stripe-cli.tar.gz
chmod +x stripe
```

**Windows:**
- Baixe de: https://github.com/stripe/stripe-cli/releases/latest
- Extraia e coloque `stripe.exe` em `bin/stripe.exe`

#### **6.2. Fazer Login no Stripe:**

```bash
cd /opt/lampp/htdocs/aistudy
./bin/stripe login
```

Isso abrirá seu navegador para autenticação.

#### **6.3. Iniciar Túnel de Webhook:**

Em um terminal, execute:

```bash
cd /opt/lampp/htdocs/aistudy
./bin/stripe-webhook.sh
```

OU diretamente:

```bash
./bin/stripe listen --forward-to http://localhost/aistudy/webhook-pagamento.php
```

#### **6.4. Copiar Webhook Secret:**

Quando o túnel iniciar, você verá:

```
> Ready! Your webhook signing secret is whsec_xxxxx (^C to quit)
```

Copie esse `whsec_xxxxx` e adicione no `.env`:

```env
STRIPE_WEBHOOK_SECRET=whsec_xxxxx
```

**📖 Para mais detalhes, veja: `STRIPE_SETUP.md`**

### **Passo 7: Testar Instalação**

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
├── 📁 bin/                       # Binários e scripts
│   ├── 📄 stripe                # Stripe CLI (instalado)
│   └── 📄 stripe-webhook.sh     # Script helper para webhooks
│
├── 📁 classes/                   # Classes PHP (Modelo MVC)
│   ├── 📄 User.php              # Gerenciamento de usuários
│   ├── 📄 Routine.php           # Gerenciamento de rotinas
│   ├── 📄 Task.php              # Gerenciamento de tarefas
│   ├── 📄 Calendar.php          # Sistema de calendário real
│   ├── 📄 PaymentGateway.php    # Integração com Stripe
│   └── 📄 PlanService.php       # Gerenciamento de planos
│
├── 📁 vendor/                    # Dependências Composer
│   └── 📁 stripe/               # Stripe PHP SDK
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
├── 📄 composer.json             # Dependências PHP (Composer)
├── 📄 .env.example              # Exemplo de variáveis de ambiente
├── 📄 checkout.php              # Página de checkout Stripe
├── 📄 pagamento-sucesso.php     # Página de confirmação de pagamento
├── 📄 webhook-pagamento.php     # Endpoint para webhooks do Stripe
├── 📄 planos.php                # Página de seleção de planos
├── 📄 modo-enem.php             # Modo ENEM
├── 📄 modo-concurso.php         # Modo Concurso
├── 📄 setup-stripe.sh           # Script de configuração do Stripe
├── 📄 STRIPE_SETUP.md           # Guia completo do Stripe
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
- **`Calendar.php`** - Sistema de calendário real
- **`PaymentGateway.php`** - Integração com Stripe (pagamentos)
- **`PlanService.php`** - Gerenciamento de planos e assinaturas
- **`YouTubeService.php`** - Busca de vídeos educacionais

#### **⚙️ Configurações:**
- **`database.php`** - Conexão com MySQL
- **`api.php`** - Integração com OpenAI ChatGPT
- **`env-loader.php`** - Carregador de variáveis de ambiente (.env)
- **`fallback-data.php`** - Dados quando API falha

#### **💳 Pagamentos:**
- **`checkout.php`** - Página de checkout com Stripe
- **`pagamento-sucesso.php`** - Confirmação de pagamento
- **`webhook-pagamento.php`** - Endpoint para webhooks do Stripe
- **`planos.php`** - Seleção e visualização de planos

## 💳 Sistema de Pagamentos com Stripe

### **Funcionalidades de Pagamento:**

O sistema está integrado com **Stripe** para processar pagamentos de planos:

- ✅ **Assinaturas Recorrentes** (Cartão de Crédito)
- ✅ **Pagamento Único** (PIX e Boleto)
- ✅ **Webhooks Automáticos** para atualização de status
- ✅ **Valores de Teste** (R$ 0,01) configurados no seed.sql

### **Como Funciona:**

1. **Usuário seleciona um plano** em `planos.php`
2. **Redireciona para checkout** em `checkout.php`
3. **Escolhe método de pagamento** (Cartão/PIX ou apenas PIX)
4. **É redirecionado para Stripe Checkout** (página segura do Stripe)
5. **Após pagamento**, retorna para `pagamento-sucesso.php`
6. **Webhook atualiza** status da assinatura automaticamente

### **Configuração do Stripe CLI (Para Desenvolvimento Local):**

Como o domínio ainda não está no ar, você pode testar webhooks localmente usando o Stripe CLI:

#### **1. Fazer Login:**
```bash
cd /opt/lampp/htdocs/aistudy
./bin/stripe login
```

#### **2. Iniciar Túnel de Webhook:**
```bash
./bin/stripe-webhook.sh
```

Ou diretamente:
```bash
./bin/stripe listen --forward-to http://localhost/aistudy/webhook-pagamento.php
```

#### **3. Copiar Webhook Secret:**
Quando o túnel iniciar, copie o `whsec_xxxxx` que aparecer e adicione no `.env`:
```env
STRIPE_WEBHOOK_SECRET=whsec_xxxxx
```

#### **4. Testar Eventos (Opcional):**
Em outro terminal:
```bash
./bin/stripe trigger checkout.session.completed
./bin/stripe trigger customer.subscription.created
```

### **Cartões de Teste do Stripe:**

Para testar pagamentos, use estes cartões de teste do Stripe:

#### **✅ Cartões que Funcionam (Pagamento Aprovado):**

| Número do Cartão | CVV | Data de Validade | Descrição |
|------------------|-----|------------------|-----------|
| `4242 4242 4242 4242` | Qualquer 3 dígitos (ex: 123) | Qualquer data futura (ex: 12/25) | Cartão Visa padrão - sempre aprovado |
| `5555 5555 5555 4444` | Qualquer 3 dígitos (ex: 123) | Qualquer data futura (ex: 12/25) | Cartão Mastercard padrão - sempre aprovado |
| `4000 0566 5566 5556` | Qualquer 3 dígitos (ex: 123) | Qualquer data futura (ex: 12/25) | Cartão Visa - sempre aprovado |

#### **❌ Cartões que Falham (Pagamento Recusado):**

| Número do Cartão | CVV | Data de Validade | Descrição |
|------------------|-----|------------------|-----------|
| `4000 0000 0000 0002` | Qualquer 3 dígitos (ex: 123) | Qualquer data futura (ex: 12/25) | Cartão recusado genérico |
| `4000 0000 0000 9995` | Qualquer 3 dígitos (ex: 123) | Qualquer data futura (ex: 12/25) | Cartão recusado por fundos insuficientes |
| `4000 0000 0000 0069` | Qualquer 3 dígitos (ex: 123) | Qualquer data futura (ex: 12/25) | Cartão expirado |

#### **💳 Cartões para Testar Cenários Específicos:**

| Número do Cartão | CVV | Data de Validade | Descrição |
|------------------|-----|------------------|-----------|
| `4000 0025 0000 3155` | Qualquer 3 dígitos (ex: 123) | Qualquer data futura (ex: 12/25) | Requer autenticação 3D Secure |
| `4000 0000 0000 3220` | Qualquer 3 dígitos (ex: 123) | Qualquer data futura (ex: 12/25) | Requer autenticação 3D Secure (falha) |

#### **📝 Informações Adicionais para Teste:**

- **Nome no Cartão:** Qualquer nome (ex: João Silva)
- **CEP:** Qualquer CEP válido (ex: 01310-100)
- **Endereço:** Qualquer endereço válido
- **CVV:** Qualquer 3 dígitos (ex: 123, 456, 789)
- **Data de Validade:** Qualquer data futura (ex: 12/25, 06/26)

**💡 Dica:** Use sempre o cartão `4242 4242 4242 4242` para testes rápidos - ele sempre funciona!

### **Valores de Teste:**

Os planos estão configurados com valores irrisórios para facilitar testes:

- **Free:** R$ 0,00 (gratuito)
- **ENEM+:** R$ 0,01 (teste)
- **Concurso+:** R$ 0,01 (teste)
- **Premium:** R$ 0,01 (teste)

### **Arquivos Relacionados:**

- `classes/PaymentGateway.php` - Integração com Stripe
- `checkout.php` - Página de checkout
- `webhook-pagamento.php` - Endpoint para receber webhooks
- `pagamento-sucesso.php` - Página de confirmação
- `bin/stripe` - Stripe CLI (já incluído)
- `bin/stripe-webhook.sh` - Script helper para webhooks

**📖 Para mais detalhes, veja: `STRIPE_SETUP.md`**

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

### **❌ Erro ao Processar Pagamento**

**Problema:** "Erro ao criar sessão de pagamento" ou "Chave do Stripe não configurada"
**Solução:**
1. Verifique se `STRIPE_SECRET_KEY` está configurado no `.env`
2. Confirme se as chaves são de **teste** (`sk_test_...` e `pk_test_...`)
3. Verifique se o Composer instalou as dependências: `composer install`
4. Confirme se a pasta `vendor/` existe e contém `stripe/stripe-php`

### **❌ Webhook não Funciona**

**Problema:** Webhooks não estão sendo recebidos
**Solução:**
1. Para desenvolvimento local, use o Stripe CLI:
   ```bash
   ./bin/stripe listen --forward-to http://localhost/aistudy/webhook-pagamento.php
   ```
2. Copie o webhook secret e adicione no `.env`
3. Mantenha o túnel aberto enquanto testa
4. Para produção, configure webhook real no Dashboard do Stripe

## 📝 Dados de Exemplo

O arquivo `seed.sql` inclui usuários de teste:

| Email | Senha | Nome |
|-------|-------|------|
| joao@email.com | password | João Silva |
| maria@email.com | password | Maria Santos |
| pedro@email.com | password | Pedro Costa |

**Rotinas de Exemplo:**
- Python - Nível Iniciante (João)
- JavaScript - Nível Intermediário (João)
- Coreano - Nível Iniciante (Maria)
- Matemática - Nível Avançado (Pedro)

**Planos de Teste:**
- **Free:** R$ 0,00 (gratuito)
- **ENEM+:** R$ 0,01 (teste)
- **Concurso+:** R$ 0,01 (teste)
- **Premium:** R$ 0,01 (teste)

**💡 Dica:** Use os valores de R$ 0,01 para testar pagamentos sem gastar dinheiro real!

## 🚀 Funcionalidades Futuras

### **Próximas Implementações:**
- 📧 **Notificações por email** para lembretes
- 🏆 **Sistema de badges** e conquistas
- 💬 **Chat com IA** para dúvidas
- 📊 **Exportação de relatórios** em PDF
- 📱 **App mobile** para Android/iOS
- 🔔 **Notificações push** no navegador
- 💳 **Mais gateways de pagamento** (Mercado Pago, PagSeguro)

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
2. ✅ Composer instalado e dependências instaladas (`composer install`)
3. ✅ Banco `aistudy` criado e populado (schema.sql + seed.sql)
4. ✅ Arquivo `.env` criado e configurado
5. ✅ Chaves de API configuradas (OpenAI, YouTube, Stripe)
6. ✅ Arquivos em `C:\xampp\htdocs\aistudy\` (Windows) ou `/opt/lampp/htdocs/aistudy` (Linux)
7. ✅ Acesso a http://localhost/aistudy
8. ✅ Stripe CLI configurado (opcional, para webhooks locais)

### **Contato:**
- 📧 **Email:** suporte@aistudy.com
- 💬 **Discord:** AIStudy Community
- 📖 **Wiki:** aistudy.com/docs

---

## 🎉 **AIStudy** - Transformando o aprendizado com inteligência artificial! 

**Desenvolvido com ❤️ em PHP, JavaScript e IA**

*Versão 1.0 - Sistema completo de estudos inteligentes* 🧠✨
#

