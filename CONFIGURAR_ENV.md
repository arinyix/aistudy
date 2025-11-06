# 🔐 Configuração de Chaves de API

Este projeto utiliza um arquivo `.env` para armazenar as chaves de API de forma segura.

## 📋 Passo a Passo

### 1. **Criar o arquivo `.env`**

Copie o arquivo de exemplo:

```bash
# Windows (PowerShell)
Copy-Item .env.example .env

# Linux/Mac
cp .env.example .env
```

### 2. **Editar o arquivo `.env`**

Abra o arquivo `.env` e preencha com suas chaves reais:

```env
OPENAI_API_KEY=sk-sua-chave-openai-aqui
YOUTUBE_API_KEY=sua-chave-youtube-aqui
```

### 3. **Obter as Chaves**

#### **OpenAI API Key (ChatGPT):**
1. Acesse: https://platform.openai.com/api-keys
2. Faça login na sua conta OpenAI
3. Clique em **"Create new secret key"**
4. Copie a chave gerada (começa com `sk-`)
5. Cole no arquivo `.env` na linha `OPENAI_API_KEY=`

#### **YouTube Data API v3 Key:**
1. Acesse: https://console.cloud.google.com/
2. Crie um novo projeto (ou selecione existente)
3. No menu, vá em **"APIs e Serviços" > "Biblioteca"**
4. Procure por **"YouTube Data API v3"**
5. Clique em **"Ativar"**
6. Vá em **"Credenciais" > "Criar credenciais" > "Chave de API"**
7. Copie a chave gerada
8. Cole no arquivo `.env` na linha `YOUTUBE_API_KEY=`

### 4. **Salvar o arquivo**

Salve o arquivo `.env` após preencher as chaves.

### 5. **Verificar**

Teste o sistema acessando a aplicação. Se houver erros relacionados a API keys, verifique:
- Se o arquivo `.env` está na raiz do projeto
- Se as chaves estão corretas (sem espaços extras)
- Se não há aspas nas chaves (exceto se necessário)

## ⚠️ IMPORTANTE

- **NUNCA** commite o arquivo `.env` no Git!
- O arquivo `.env` já está no `.gitignore` para sua proteção
- Use apenas o arquivo `.env.example` como referência no Git
- Se você compartilhar o código, não inclua suas chaves reais

## 🔄 Para o Professor

Se você está rodando este código pela primeira vez:

1. Copie `.env.example` para `.env`
2. Edite `.env` e adicione suas próprias chaves de API
3. O sistema funcionará automaticamente

O arquivo `.env.example` serve apenas como template e não contém chaves reais.

