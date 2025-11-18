#!/bin/bash
# Script completo de configuração do Stripe

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STRIPE_BIN="$SCRIPT_DIR/bin/stripe"
ENV_FILE="$SCRIPT_DIR/.env"

echo "🚀 Configuração do Stripe CLI"
echo "================================"
echo ""

# Verificar se Stripe CLI está instalado
if [ ! -f "$STRIPE_BIN" ]; then
    echo "❌ Stripe CLI não encontrado!"
    exit 1
fi

echo "✅ Stripe CLI encontrado: $STRIPE_BIN"
echo ""

# Verificar se está logado
if ! "$STRIPE_BIN" config --get api_key > /dev/null 2>&1; then
    echo "🔐 Você precisa fazer login no Stripe primeiro."
    echo ""
    echo "Execute o seguinte comando:"
    echo "  $STRIPE_BIN login"
    echo ""
    echo "Isso abrirá seu navegador para autenticação."
    echo "Após autenticar, execute este script novamente."
    exit 1
fi

echo "✅ Você está logado no Stripe!"
echo ""

# Verificar arquivo .env
if [ ! -f "$ENV_FILE" ]; then
    echo "📝 Criando arquivo .env a partir do .env.example..."
    if [ -f "$SCRIPT_DIR/.env.example" ]; then
        cp "$SCRIPT_DIR/.env.example" "$ENV_FILE"
        echo "✅ Arquivo .env criado!"
    else
        echo "⚠️  Arquivo .env.example não encontrado!"
    fi
    echo ""
fi

echo "📋 Próximos passos:"
echo ""
echo "1. Configure suas chaves do Stripe no arquivo .env:"
echo "   - STRIPE_SECRET_KEY=sk_test_..."
echo "   - STRIPE_PUBLISHABLE_KEY=pk_test_..."
echo ""
echo "2. Para iniciar o túnel de webhook, execute:"
echo "   ./bin/stripe-webhook.sh"
echo ""
echo "   OU diretamente:"
echo "   ./bin/stripe listen --forward-to http://localhost/aistudy/webhook-pagamento.php"
echo ""
echo "3. Quando o túnel iniciar, copie o 'webhook signing secret' (whsec_...)"
echo "   e adicione no .env como STRIPE_WEBHOOK_SECRET"
echo ""
echo "4. Para testar eventos, em outro terminal execute:"
echo "   ./bin/stripe trigger checkout.session.completed"
echo ""
echo "📖 Para mais informações, veja: STRIPE_SETUP.md"
echo ""

