#!/bin/bash
# Script helper para iniciar o túnel de webhook do Stripe

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
STRIPE_BIN="$SCRIPT_DIR/stripe"

# Verificar se Stripe CLI está instalado
if [ ! -f "$STRIPE_BIN" ]; then
    echo "❌ Stripe CLI não encontrado em $STRIPE_BIN"
    exit 1
fi

# Verificar se está logado
if ! "$STRIPE_BIN" config --get api_key > /dev/null 2>&1; then
    echo "🔐 Você precisa fazer login no Stripe primeiro."
    echo "Execute: $STRIPE_BIN login"
    echo ""
    echo "Isso abrirá seu navegador para autenticação."
    exit 1
fi

# URL do webhook local
WEBHOOK_URL="http://localhost/aistudy/webhook-pagamento.php"

echo "🚀 Iniciando túnel de webhook do Stripe..."
echo "📍 URL do webhook: $WEBHOOK_URL"
echo ""
echo "⚠️  IMPORTANTE:"
echo "1. Copie o 'webhook signing secret' que aparecer abaixo"
echo "2. Adicione no arquivo .env como STRIPE_WEBHOOK_SECRET"
echo "3. Mantenha este terminal aberto enquanto testar"
echo ""
echo "Pressione Ctrl+C para parar o túnel"
echo ""

# Iniciar o túnel
"$STRIPE_BIN" listen --forward-to "$WEBHOOK_URL"

