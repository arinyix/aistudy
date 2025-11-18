# 🚀 Guia de Configuração do Stripe CLI

## ✅ Stripe CLI Instalado

O Stripe CLI foi instalado em: `/opt/lampp/htdocs/aistudy/bin/stripe`

## 📋 Passos para Configurar

### 1. Fazer Login no Stripe

Execute no terminal:

```bash
cd /opt/lampp/htdocs/aistudy
./bin/stripe login
```

Isso abrirá seu navegador para autenticação. Após autenticar, você estará logado.

### 2. Iniciar Túnel de Webhook

Execute o script helper:

```bash
cd /opt/lampp/htdocs/aistudy
./bin/stripe-webhook.sh
```

OU execute diretamente:

```bash
./bin/stripe listen --forward-to http://localhost/aistudy/webhook-pagamento.php
```

### 3. Copiar Webhook Secret

Quando iniciar o túnel, você verá algo como:

```
> Ready! Your webhook signing secret is whsec_xxxxx (^C to quit)
```

**Copie esse `whsec_xxxxx` e adicione no arquivo `.env`:**

```env
STRIPE_WEBHOOK_SECRET=whsec_xxxxx
```

### 4. Testar Webhook

Em outro terminal, teste eventos:

```bash
cd /opt/lampp/htdocs/aistudy
./bin/stripe trigger checkout.session.completed
```

## 🔧 Comandos Úteis

### Verificar versão:
```bash
./bin/stripe --version
```

### Ver status de login:
```bash
./bin/stripe config --get api_key
```

### Listar eventos recebidos:
```bash
./bin/stripe events list
```

### Testar evento específico:
```bash
./bin/stripe trigger checkout.session.completed
./bin/stripe trigger customer.subscription.created
./bin/stripe trigger invoice.payment_succeeded
```

## ⚠️ Importante

- **Mantenha o túnel aberto** enquanto estiver testando pagamentos
- O túnel só funciona enquanto estiver rodando
- Para produção, configure webhook real no Dashboard do Stripe
- Use sempre chaves de **teste** (`sk_test_...`) durante desenvolvimento

## 🎯 Próximos Passos

1. Configure as chaves do Stripe no arquivo `.env`
2. Inicie o túnel de webhook
3. Teste um pagamento no sistema
4. Verifique os logs para confirmar que os webhooks estão chegando

