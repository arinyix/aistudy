<?php
require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'classes/Task.php';
require_once 'classes/Routine.php';
require_once 'config/api.php';

requireLogin();

$user = getCurrentUser();
$task_id = $_GET['task_id'] ?? null;
$referrer = $_GET['referrer'] ?? 'rotinas.php'; // Página padrão se não houver referrer

if (!$task_id || !is_numeric($task_id)) {
    die('Task ID inválido');
}

// Sanitizar referrer para segurança e extrair apenas o arquivo
if (!empty($referrer) && $referrer !== 'rotinas.php') {
    // Decodificar URL se necessário
    $referrer = urldecode($referrer);
    
    // Extrair apenas o caminho do arquivo (remover domínio se houver)
    $parsed = parse_url($referrer);
    if ($parsed && isset($parsed['path'])) {
        // Extrair apenas o nome do arquivo
        $pathParts = explode('/', trim($parsed['path'], '/'));
        $filename = end($pathParts);
        
        // Verificar se é um arquivo PHP válido
        if (preg_match('/^[a-zA-Z0-9\-_]+\.php$/', $filename)) {
            // Incluir query string se houver (apenas id, por exemplo)
            $referrer = $filename;
            if (isset($parsed['query'])) {
                // Extrair apenas parâmetros seguros (id)
                parse_str($parsed['query'], $queryParams);
                if (isset($queryParams['id']) && is_numeric($queryParams['id'])) {
                    $referrer .= '?id=' . intval($queryParams['id']);
                }
            }
        } else {
            $referrer = 'rotinas.php';
        }
    } elseif (!preg_match('/^[a-zA-Z0-9\-_]+\.php(\?id=\d+)?$/', $referrer)) {
        // Se não for uma URL válida, usar padrão
        $referrer = 'rotinas.php';
    }
} else {
    $referrer = 'rotinas.php';
}

$database = new Database();
$db = $database->getConnection();
$task = new Task($db);
$routine = new Routine($db);

// Buscar task
$task_data = $task->getTask($task_id, $user['id']);
if (!$task_data) {
    die('Tarefa não encontrada');
}

// Buscar rotina
$rotina = $routine->getRoutine($task_data['routine_id'], $user['id']);
if (!$rotina) {
    die('Rotina não encontrada');
}

// Buscar conteúdo do resumo
$markdown_content = '';
$content_source = 'none'; // 'post', 'get', 'database', 'api'

// Tentar receber via POST primeiro (método preferido)
if (isset($_POST['content'])) {
    $post_content = $_POST['content'];
    // Verificar se não é vazio e tem conteúdo válido
    if (!empty($post_content) && trim($post_content) !== '') {
        $markdown_content = $post_content;
        $content_source = 'post';
        error_log("✅ CONTEÚDO RECEBIDO VIA POST. Tamanho: " . strlen($markdown_content) . " caracteres");
        error_log("⚠️ IMPORTANTE: Usando conteúdo recebido via POST - NÃO VAI BUSCAR NO BANCO E NÃO VAI GERAR NOVO RESUMO");
    } else {
        error_log("⚠️ POST 'content' existe mas está vazio ou inválido");
    }
} 
// Tentar receber via GET (base64) - fallback
elseif (isset($_GET['content']) && !empty($_GET['content'])) {
    // Tentar decodificar base64
    $decoded = @base64_decode($_GET['content'], true);
    if ($decoded !== false && !empty($decoded) && trim($decoded) !== '') {
        $markdown_content = $decoded;
        $content_source = 'get';
        error_log("✅ CONTEÚDO RECEBIDO VIA GET (base64). Tamanho: " . strlen($markdown_content) . " caracteres");
        error_log("⚠️ IMPORTANTE: Usando conteúdo recebido via GET - NÃO VAI BUSCAR NO BANCO E NÃO VAI GERAR NOVO RESUMO");
    } else {
        error_log("⚠️ GET 'content' existe mas está vazio ou inválido após decodificação");
    }
}

// CRÍTICO: Se já tem conteúdo via POST/GET, NÃO fazer mais nada - pular verificação do banco e geração
// Se não tiver conteúdo via POST/GET, verificar no banco primeiro
if ($content_source === 'none' || empty($markdown_content)) {
    error_log("=== resumo-pdf.php: Conteúdo NÃO recebido via POST/GET ===");
    error_log("Verificando no banco de dados para task_id: " . $task_id);
    
    // Tentar buscar do banco de dados
    $resumo_do_banco = $task->getResumo($task_id, $user['id']);
    
    // Verificação rigorosa
    if ($resumo_do_banco !== null && $resumo_do_banco !== '' && trim($resumo_do_banco) !== '') {
        $markdown_content = $resumo_do_banco;
        $content_source = 'database';
        error_log("✅ RESUMO ENCONTRADO NO BANCO - USANDO CACHE (SEM CHAMAR API)");
        error_log("Tamanho do resumo: " . strlen($resumo_do_banco) . " caracteres");
    } else {
        // Se não tiver no banco, gerar agora (ULTIMA OPÇÃO - SÓ SE NÃO EXISTIR)
        error_log("❌ RESUMO NÃO ENCONTRADO NO BANCO - SERÁ NECESSÁRIO GERAR VIA API");
        error_log("Task ID: " . $task_id);
        
        try {
            $openai = new OpenAIService();
            set_time_limit(360);
            ini_set('max_execution_time', 360);
            
            error_log("⚠️ CHAMANDO API OPENAI PARA GERAR RESUMO (ÚLTIMA OPÇÃO - NÃO EXISTE NO BANCO)...");
            
            $markdown_content = $openai->generateSummaryPDF(
                $task_data['titulo'],
                $rotina['nivel'],
                $task_data['descricao']
            );
            
            if (empty($markdown_content)) {
                throw new Exception('Resumo gerado está vazio');
            }
            
            $content_source = 'api';
            error_log("✅ Resumo gerado via API com sucesso. Tamanho: " . strlen($markdown_content) . " caracteres");
            
            // Salvar no banco de dados
            $saved = $task->saveResumo($task_id, $user['id'], $markdown_content);
            if ($saved) {
                error_log("✅ Resumo salvo no banco de dados com sucesso!");
            } else {
                error_log("❌ AVISO: Não foi possível salvar o resumo no banco de dados.");
            }
        } catch (Exception $e) {
            error_log("❌ ERRO ao gerar resumo: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            die('Erro ao gerar resumo: ' . htmlspecialchars($e->getMessage()));
        }
    }
} else {
    // Se já tem conteúdo via POST/GET, NÃO fazer nada mais - usar o conteúdo recebido
    // NÃO verificar banco, NÃO gerar novo resumo - usar o que foi recebido
    error_log("✅ RESUMO RECEBIDO VIA {$content_source} - USANDO CONTEÚDO RECEBIDO");
    error_log("⚠️ PULANDO VERIFICAÇÃO DO BANCO E GERAÇÃO DE NOVO RESUMO");
    error_log("Fonte: " . $content_source);
    error_log("Tamanho: " . strlen($markdown_content) . " caracteres");
    error_log("✅ NÃO VAI CHAMAR A API - USANDO CONTEÚDO RECEBIDO VIA {$content_source}");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumo Auxiliar - <?php echo htmlspecialchars($task_data['titulo']); ?></title>
    
    <!-- Biblioteca para conversão HTML para PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <!-- Biblioteca para renderizar Markdown -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js" onload="window.markedLoaded = true;" onerror="console.error('Erro ao carregar marked.js');"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            line-height: 1.8;
        }

        .pdf-container {
            max-width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: white;
            padding: 40mm 30mm;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .pdf-content {
            color: #1f2937;
        }

        /* Títulos */
        .pdf-content h1 {
            color: #1e40af;
            font-size: 2.5rem;
            font-weight: 700;
            border-bottom: 3px solid #4f46e5;
            padding-bottom: 15px;
            margin-bottom: 30px;
            margin-top: 0;
            line-height: 1.3;
        }

        .pdf-content h2 {
            color: #6366f1;
            font-size: 2rem;
            font-weight: 600;
            margin-top: 40px;
            margin-bottom: 20px;
            padding-left: 15px;
            border-left: 5px solid #6366f1;
            line-height: 1.4;
        }

        .pdf-content h3 {
            color: #4f46e5;
            font-size: 1.5rem;
            font-weight: 600;
            margin-top: 30px;
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .pdf-content h4 {
            color: #5b21b6;
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: 25px;
            margin-bottom: 12px;
            line-height: 1.4;
        }

        /* Parágrafos */
        .pdf-content p {
            line-height: 1.8;
            margin-bottom: 16px;
            font-size: 1.05rem;
            color: #1f2937;
            text-align: justify;
            word-wrap: break-word;
        }

        /* Formatação de texto */
        .pdf-content strong {
            color: #1e40af;
            font-weight: 600;
        }

        .pdf-content em {
            color: #475569;
            font-style: italic;
        }

        /* Listas */
        .pdf-content ul,
        .pdf-content ol {
            margin: 20px 0;
            padding-left: 40px;
            line-height: 1.8;
        }

        .pdf-content li {
            margin: 8px 0;
            font-size: 1.05rem;
            color: #1f2937;
        }

        /* Código */
        .pdf-content code {
            background: #f3f4f6;
            padding: 3px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            color: #dc2626;
        }

        .pdf-content pre {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 20px 0;
            border-left: 4px solid #6366f1;
        }

        .pdf-content pre code {
            background: none;
            padding: 0;
            color: #1f2937;
            font-size: 0.9rem;
        }

        /* Blockquotes */
        .pdf-content blockquote {
            border-left: 5px solid #4f46e5;
            margin: 25px 0;
            padding: 15px 20px;
            font-style: italic;
            color: #475569;
            background: #f8fafc;
            border-radius: 4px;
        }

        /* Tabelas */
        .pdf-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            font-size: 1rem;
        }

        .pdf-content table th,
        .pdf-content table td {
            border: 1px solid #d1d5db;
            padding: 12px 15px;
            text-align: left;
        }

        .pdf-content table th {
            background: #6366f1;
            color: white;
            font-weight: 600;
        }

        .pdf-content table tr:nth-child(even) {
            background: #f9fafb;
        }

        /* Botões de ação */
        .action-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .btn-primary {
            background: #6366f1;
            color: white;
        }

        .btn-primary:hover {
            background: #4f46e5;
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0,0,0,0.15);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .loading {
            text-align: center;
            padding: 50px;
            font-size: 1.2rem;
            color: #6b7280;
        }

        /* Estilos para impressão */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .action-buttons {
                display: none;
            }

            .pdf-container {
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Botões de ação -->
    <div class="action-buttons">
        <button class="btn btn-primary" onclick="downloadPDF()">
            📥 Download PDF
        </button>
        <button class="btn btn-secondary" onclick="window.print()">
            🖨️ Imprimir
        </button>
        <button class="btn btn-secondary" onclick="goBack()">
            ✕ Fechar
        </button>
    </div>

    <!-- Container do PDF -->
    <div class="pdf-container" id="pdfContent">
        <div class="pdf-content" id="pdfContentInner">
            <div class="loading">Carregando conteúdo...</div>
        </div>
    </div>

    <script>
        // URL de referência para voltar
        const referrerUrl = <?php echo json_encode($referrer, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        
        // Função para voltar à página de origem
        function goBack() {
            console.log('goBack chamado. Referrer:', referrerUrl);
            
            if (referrerUrl && referrerUrl !== '' && referrerUrl !== 'rotinas.php') {
                // Se a janela foi aberta em nova aba/janela, tentar fechar
                if (window.opener && !window.opener.closed) {
                    // Fechar esta janela e focar na janela que abriu
                    window.opener.focus();
                    window.close();
                } else {
                    // Redirecionar para a página de origem
                    window.location.href = referrerUrl;
                }
            } else {
                // Fallback: tentar voltar no histórico ou fechar
                if (window.opener && !window.opener.closed) {
                    window.close();
                } else if (window.history.length > 1) {
                    window.history.back();
                } else {
                    // Último recurso: ir para rotinas
                    window.location.href = 'rotinas.php';
                }
            }
        }
        
        // Conteúdo markdown
        const markdownContent = <?php echo json_encode($markdown_content, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
        
        console.log('Markdown content length:', markdownContent ? markdownContent.length : 0);
        console.log('Primeiros 200 caracteres:', markdownContent ? markdownContent.substring(0, 200) : 'vazio');
        console.log('Referrer URL:', referrerUrl);
        
        // Flag para evitar renderização múltipla
        let rendered = false;

        // Função para renderizar markdown com fallback robusto
        function renderMarkdown() {
            if (rendered) {
                console.log('Já renderizado, ignorando...');
                return;
            }
            
            const contentDiv = document.getElementById('pdfContentInner');
            
            if (!contentDiv) {
                console.error('Elemento pdfContentInner não encontrado!');
                return;
            }
            
            if (!markdownContent || markdownContent.length === 0) {
                console.error('Markdown content está vazio!');
                contentDiv.innerHTML = '<div style="padding: 20px; color: red;">Erro: Conteúdo do resumo está vazio.</div>';
                return;
            }
            
            // Tentar usar marked.js primeiro
            if (typeof marked !== 'undefined' && marked && typeof marked.parse === 'function') {
                try {
                    console.log('Usando marked.js para renderizar...');
                    
                    // Configurar marked.js
                    if (marked.setOptions) {
                        marked.setOptions({
                            breaks: true,
                            gfm: true,
                            headerIds: false,
                            mangle: false,
                            pedantic: false
                        });
                    }
                    
                    // Renderizar
                    const html = marked.parse(markdownContent);
                    contentDiv.innerHTML = html;
                    rendered = true;
                    console.log('✅ Markdown renderizado com marked.js com sucesso!');
                    return;
                } catch (e) {
                    console.error('❌ Erro ao renderizar com marked.js:', e);
                    console.log('Tentando usar fallback...');
                }
            } else {
                console.warn('⚠️ marked.js não disponível, usando fallback robusto');
            }
            
            // Fallback robusto - renderizar markdown manualmente
            console.log('Renderizando markdown com fallback...');
            const html = renderMarkdownFallback(markdownContent);
            contentDiv.innerHTML = html;
            rendered = true;
            console.log('✅ Markdown renderizado com fallback!');
        }

        // Função fallback robusta para renderizar markdown
        function renderMarkdownFallback(markdown) {
            if (!markdown || typeof markdown !== 'string') {
                return '';
            }
            
            // Processar blocos de código primeiro (para não interferir com outros)
            const codeBlocks = [];
            let codeIndex = 0;
            let processedMarkdown = markdown.replace(/```([\s\S]*?)```/g, function(match, code) {
                const placeholder = `__CODE_BLOCK_${codeIndex}__`;
                codeBlocks[codeIndex] = '<pre><code>' + escapeHtml(code.trim()) + '</code></pre>';
                codeIndex++;
                return '\n' + placeholder + '\n';
            });
            
            // Separar em linhas
            const lines = processedMarkdown.split('\n');
            const result = [];
            let inList = false;
            let listType = null; // 'ul' ou 'ol'
            let listItems = [];
            let inParagraph = false;
            let paragraphLines = [];
            
            for (let i = 0; i < lines.length; i++) {
                let line = lines[i];
                const trimmedLine = line.trim();
                
                // Pular placeholders de código (serão processados depois)
                if (trimmedLine.match(/^__CODE_BLOCK_\d+__$/)) {
                    // Fechar lista/parágrafo se necessário
                    if (inList) {
                        result.push('<' + listType + '>' + listItems.join('') + '</' + listType + '>');
                        listItems = [];
                        inList = false;
                        listType = null;
                    }
                    if (inParagraph) {
                        result.push('<p>' + paragraphLines.join(' ') + '</p>');
                        paragraphLines = [];
                        inParagraph = false;
                    }
                    result.push(trimmedLine);
                    continue;
                }
                
                // Processar títulos
                if (trimmedLine.match(/^#{1,6}\s/)) {
                    // Fechar lista/parágrafo
                    if (inList) {
                        result.push('<' + listType + '>' + listItems.join('') + '</' + listType + '>');
                        listItems = [];
                        inList = false;
                        listType = null;
                    }
                    if (inParagraph) {
                        result.push('<p>' + paragraphLines.join(' ') + '</p>');
                        paragraphLines = [];
                        inParagraph = false;
                    }
                    
                    const level = trimmedLine.match(/^(#{1,6})/)[1].length;
                    let titleText = trimmedLine.replace(/^#{1,6}\s+/, '');
                    // Processar formatação no título
                    titleText = processInlineFormatting(titleText);
                    result.push('<h' + level + '>' + titleText + '</h' + level + '>');
                    continue;
                }
                
                // Processar listas não ordenadas (-, *, +)
                if (trimmedLine.match(/^[\*\-\+]\s/)) {
                    if (inParagraph) {
                        result.push('<p>' + paragraphLines.join(' ') + '</p>');
                        paragraphLines = [];
                        inParagraph = false;
                    }
                    
                    if (!inList || listType !== 'ul') {
                        if (inList) {
                            result.push('<' + listType + '>' + listItems.join('') + '</' + listType + '>');
                            listItems = [];
                        }
                        inList = true;
                        listType = 'ul';
                    }
                    let itemText = trimmedLine.replace(/^[\*\-\+]\s+/, '');
                    itemText = processInlineFormatting(itemText);
                    listItems.push('<li>' + itemText + '</li>');
                    continue;
                }
                
                // Processar listas ordenadas (1. 2. etc)
                if (trimmedLine.match(/^\d+\.\s/)) {
                    if (inParagraph) {
                        result.push('<p>' + paragraphLines.join(' ') + '</p>');
                        paragraphLines = [];
                        inParagraph = false;
                    }
                    
                    if (!inList || listType !== 'ol') {
                        if (inList) {
                            result.push('<' + listType + '>' + listItems.join('') + '</' + listType + '>');
                            listItems = [];
                        }
                        inList = true;
                        listType = 'ol';
                    }
                    let itemText = trimmedLine.replace(/^\d+\.\s+/, '');
                    itemText = processInlineFormatting(itemText);
                    listItems.push('<li>' + itemText + '</li>');
                    continue;
                }
                
                // Linha vazia
                if (trimmedLine === '') {
                    if (inList) {
                        result.push('<' + listType + '>' + listItems.join('') + '</' + listType + '>');
                        listItems = [];
                        inList = false;
                        listType = null;
                    }
                    if (inParagraph) {
                        result.push('<p>' + paragraphLines.join(' ') + '</p>');
                        paragraphLines = [];
                        inParagraph = false;
                    }
                    continue;
                }
                
                // Fechar lista se necessário
                if (inList) {
                    result.push('<' + listType + '>' + listItems.join('') + '</' + listType + '>');
                    listItems = [];
                    inList = false;
                    listType = null;
                }
                
                // Adicionar ao parágrafo
                let processedLine = processInlineFormatting(trimmedLine);
                paragraphLines.push(processedLine);
                inParagraph = true;
            }
            
            // Fechar listas/parágrafos pendentes
            if (inList) {
                result.push('<' + listType + '>' + listItems.join('') + '</' + listType + '>');
            }
            if (inParagraph) {
                result.push('<p>' + paragraphLines.join(' ') + '</p>');
            }
            
            let html = result.join('\n');
            
            // Restaurar blocos de código
            for (let i = 0; i < codeBlocks.length; i++) {
                html = html.replace(`__CODE_BLOCK_${i}__`, codeBlocks[i]);
            }
            
            return html;
        }

        // Função para processar formatação inline (negrito, itálico, código)
        function processInlineFormatting(text) {
            // Código inline primeiro (não processar formatação dentro de código)
            text = text.replace(/`([^`]+)`/g, function(match, code) {
                return '<code>' + code + '</code>';
            });
            
            // Negrito (**texto**)
            text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
            
            // Itálico (*texto*) - só processar se não for parte de negrito
            // Processar após negrito, assim só pega asteriscos simples
            text = text.replace(/\*([^*\n]+?)\*/g, function(match, content) {
                // Verificar se não está dentro de código
                if (match.includes('<code>') || match.includes('</code>')) {
                    return match;
                }
                return '<em>' + content + '</em>';
            });
            
            return text;
        }

        // Função para escapar HTML
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        // Aguardar marked.js carregar e renderizar
        function waitForMarkedAndRender() {
            let attempts = 0;
            const maxAttempts = 50; // 5 segundos máximo
            
            const checkMarked = setInterval(function() {
                attempts++;
                
                if (typeof marked !== 'undefined' && marked && typeof marked.parse === 'function') {
                    clearInterval(checkMarked);
                    console.log('marked.js carregado após ' + (attempts * 100) + 'ms');
                    renderMarkdown();
                } else if (attempts >= maxAttempts) {
                    clearInterval(checkMarked);
                    console.log('marked.js não carregou após 5 segundos, usando fallback');
                    renderMarkdown();
                }
            }, 100);
        }

        // Iniciar quando DOM estiver pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', waitForMarkedAndRender);
        } else {
            waitForMarkedAndRender();
        }

        // Também tentar quando window carregar completamente
        window.addEventListener('load', function() {
            if (!rendered) {
                console.log('Window carregado, tentando renderizar novamente...');
                renderMarkdown();
            }
        });

        function downloadPDF() {
            const element = document.getElementById('pdfContent');
            const opt = {
                margin: [15, 15, 15, 15],
                filename: 'resumo_<?php echo $task_id; ?>_<?php echo time(); ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { 
                    scale: 2,
                    useCORS: true,
                    logging: false
                },
                jsPDF: { 
                    unit: 'mm', 
                    format: 'a4', 
                    orientation: 'portrait' 
                }
            };

            // Mostrar loading
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ Gerando PDF...';
            btn.disabled = true;

            html2pdf().set(opt).from(element).save().then(function() {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }).catch(function(error) {
                console.error('Erro ao gerar PDF:', error);
                alert('Erro ao gerar PDF. Tente usar a opção de Imprimir.');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }
    </script>
</body>
</html>

