<?php
require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'classes/Task.php';
require_once 'classes/Routine.php';
require_once 'config/api.php';

requireLogin();

$user = getCurrentUser();
$task_id = $_GET['task_id'] ?? null;
$referrer = $_GET['referrer'] ?? 'rotinas.php';

if (!$task_id || !is_numeric($task_id)) {
    die('Task ID inválido');
}

// Sanitizar referrer para segurança
if (!empty($referrer) && $referrer !== 'rotinas.php') {
    $referrer = urldecode($referrer);
    $parsed = parse_url($referrer);
    if ($parsed && isset($parsed['path'])) {
        $pathParts = explode('/', trim($parsed['path'], '/'));
        $filename = end($pathParts);
        if (preg_match('/^[a-zA-Z0-9\-_]+\.php$/', $filename)) {
            $referrer = $filename;
            if (isset($parsed['query'])) {
                parse_str($parsed['query'], $queryParams);
                if (isset($queryParams['id']) && is_numeric($queryParams['id'])) {
                    $referrer .= '?id=' . intval($queryParams['id']);
                }
            }
        } else {
            $referrer = 'rotinas.php';
        }
    } elseif (!preg_match('/^[a-zA-Z0-9\-_]+\.php(\?id=\d+)?$/', $referrer)) {
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

// Buscar conteúdo dos exercícios
$markdown_content = '';
$content_source = 'none';

// Tentar receber via POST primeiro
if (isset($_POST['content'])) {
    $post_content = $_POST['content'];
    if (!empty($post_content) && trim($post_content) !== '') {
        $markdown_content = $post_content;
        $content_source = 'post';
        error_log("✅ CONTEÚDO DE EXERCÍCIOS RECEBIDO VIA POST. Tamanho: " . strlen($markdown_content) . " caracteres");
    }
} 
// Tentar receber via GET (base64) - fallback
elseif (isset($_GET['content']) && !empty($_GET['content'])) {
    $decoded = @base64_decode($_GET['content'], true);
    if ($decoded !== false && !empty($decoded) && trim($decoded) !== '') {
        $markdown_content = $decoded;
        $content_source = 'get';
        error_log("✅ CONTEÚDO DE EXERCÍCIOS RECEBIDO VIA GET (base64). Tamanho: " . strlen($markdown_content) . " caracteres");
    }
}

// Se não tiver conteúdo via POST/GET, verificar no banco
if ($content_source === 'none' || empty($markdown_content)) {
    error_log("=== exercicios-pdf.php: Conteúdo NÃO recebido via POST/GET ===");
    error_log("Verificando no banco de dados para task_id: " . $task_id);
    
    $exercicios_do_banco = $task->getExercicios($task_id, $user['id']);
    
    if ($exercicios_do_banco !== null && $exercicios_do_banco !== '' && trim($exercicios_do_banco) !== '') {
        $markdown_content = $exercicios_do_banco;
        $content_source = 'database';
        error_log("✅ EXERCÍCIOS ENCONTRADOS NO BANCO - USANDO CACHE");
    } else {
        // Se não tiver no banco, redirecionar de volta (não gerar automaticamente)
        error_log("❌ EXERCÍCIOS NÃO ENCONTRADOS NO BANCO - REDIRECIONANDO DE VOLTA");
        error_log("⚠️ IMPORTANTE: Não gerar automaticamente - usuário deve clicar no botão primeiro");
        
        // Redirecionar de volta com mensagem
        header('Location: ' . $referrer . '?erro=exercicios_nao_existem');
        exit();
    }
} else {
    error_log("✅ EXERCÍCIOS RECEBIDOS VIA {$content_source} - USANDO CONTEÚDO RECEBIDO");
}

// Verificar se ainda está vazio após todas as tentativas
if (empty($markdown_content) || trim($markdown_content) === '') {
    error_log("❌ ERRO CRÍTICO: Conteúdo de exercícios está vazio após todas as tentativas");
    header('Location: ' . $referrer . '?erro=exercicios_vazio');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Exercícios - <?php echo htmlspecialchars($task_data['titulo']); ?></title>
    
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
            color: #059669;
            font-size: 2.5rem;
            font-weight: 700;
            border-bottom: 3px solid #10b981;
            padding-bottom: 15px;
            margin-bottom: 30px;
            margin-top: 0;
            line-height: 1.3;
        }

        .pdf-content h2 {
            color: #10b981;
            font-size: 2rem;
            font-weight: 600;
            margin-top: 40px;
            margin-bottom: 20px;
            padding-left: 15px;
            border-left: 5px solid #10b981;
            line-height: 1.4;
        }

        .pdf-content h3 {
            color: #059669;
            font-size: 1.5rem;
            font-weight: 600;
            margin-top: 30px;
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .pdf-content h4 {
            color: #047857;
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
            color: #059669;
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
            border-left: 4px solid #10b981;
        }

        .pdf-content pre code {
            background: none;
            padding: 0;
            color: #1f2937;
            font-size: 0.9rem;
        }

        /* Blockquotes */
        .pdf-content blockquote {
            border-left: 5px solid #10b981;
            margin: 25px 0;
            padding: 15px 20px;
            font-style: italic;
            color: #475569;
            background: #f0fdf4;
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
            background: #10b981;
            color: white;
            font-weight: 600;
        }

        .pdf-content table tr:nth-child(even) {
            background: #f9fafb;
        }

        /* Estilos específicos para exercícios */
        .pdf-content .exercise-container {
            background: #f0fdf4;
            border: 2px solid #10b981;
            border-left: 5px solid #059669;
            padding: 20px;
            margin: 25px 0;
            border-radius: 8px;
        }

        .pdf-content .answer-container {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            border-left: 5px solid #d97706;
            padding: 20px;
            margin: 25px 0;
            border-radius: 8px;
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

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
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
        <button class="btn btn-success" onclick="downloadPDF()">
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
                if (window.opener && !window.opener.closed) {
                    window.opener.focus();
                    window.close();
                } else {
                    window.location.href = referrerUrl;
                }
            } else {
                if (window.opener && !window.opener.closed) {
                    window.close();
                } else if (window.history.length > 1) {
                    window.history.back();
                } else {
                    window.location.href = 'rotinas.php';
                }
            }
        }
        
        // Conteúdo markdown
        const markdownContent = <?php echo json_encode($markdown_content, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
        
        console.log('Markdown content length:', markdownContent ? markdownContent.length : 0);
        
        // Flag para evitar renderização múltipla
        let rendered = false;

        // Função para renderizar markdown
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
                contentDiv.innerHTML = '<div style="padding: 20px; color: red;">Erro: Conteúdo dos exercícios está vazio.</div>';
                return;
            }
            
            // Tentar usar marked.js primeiro
            if (typeof marked !== 'undefined' && marked && typeof marked.parse === 'function') {
                try {
                    console.log('Usando marked.js para renderizar...');
                    
                    if (marked.setOptions) {
                        marked.setOptions({
                            breaks: true,
                            gfm: true,
                            headerIds: false,
                            mangle: false,
                            pedantic: false
                        });
                    }
                    
                    const html = marked.parse(markdownContent);
                    contentDiv.innerHTML = html;
                    rendered = true;
                    console.log('✅ Markdown renderizado com marked.js com sucesso!');
                    return;
                } catch (e) {
                    console.error('❌ Erro ao renderizar com marked.js:', e);
                }
            }
            
            // Fallback robusto
            console.log('Renderizando markdown com fallback...');
            const html = renderMarkdownFallback(markdownContent);
            contentDiv.innerHTML = html;
            rendered = true;
            console.log('✅ Markdown renderizado com fallback!');
        }

        // Função fallback para renderizar markdown (mesma do resumo-pdf.php)
        function renderMarkdownFallback(markdown) {
            if (!markdown || typeof markdown !== 'string') {
                return '';
            }
            
            const codeBlocks = [];
            let codeIndex = 0;
            let processedMarkdown = markdown.replace(/```([\s\S]*?)```/g, function(match, code) {
                const placeholder = `__CODE_BLOCK_${codeIndex}__`;
                codeBlocks[codeIndex] = '<pre><code>' + escapeHtml(code.trim()) + '</code></pre>';
                codeIndex++;
                return '\n' + placeholder + '\n';
            });
            
            const lines = processedMarkdown.split('\n');
            const result = [];
            let inList = false;
            let listType = null;
            let listItems = [];
            let inParagraph = false;
            let paragraphLines = [];
            
            for (let i = 0; i < lines.length; i++) {
                let line = lines[i];
                const trimmedLine = line.trim();
                
                if (trimmedLine.match(/^__CODE_BLOCK_\d+__$/)) {
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
                
                if (trimmedLine.match(/^#{1,6}\s/)) {
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
                    titleText = processInlineFormatting(titleText);
                    result.push('<h' + level + '>' + titleText + '</h' + level + '>');
                    continue;
                }
                
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
                
                if (inList) {
                    result.push('<' + listType + '>' + listItems.join('') + '</' + listType + '>');
                    listItems = [];
                    inList = false;
                    listType = null;
                }
                
                let processedLine = processInlineFormatting(trimmedLine);
                paragraphLines.push(processedLine);
                inParagraph = true;
            }
            
            if (inList) {
                result.push('<' + listType + '>' + listItems.join('') + '</' + listType + '>');
            }
            if (inParagraph) {
                result.push('<p>' + paragraphLines.join(' ') + '</p>');
            }
            
            let html = result.join('\n');
            
            for (let i = 0; i < codeBlocks.length; i++) {
                html = html.replace(`__CODE_BLOCK_${i}__`, codeBlocks[i]);
            }
            
            return html;
        }

        function processInlineFormatting(text) {
            text = text.replace(/`([^`]+)`/g, function(match, code) {
                return '<code>' + code + '</code>';
            });
            
            text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
            
            text = text.replace(/\*([^*\n]+?)\*/g, function(match, content) {
                if (match.includes('<code>') || match.includes('</code>')) {
                    return match;
                }
                return '<em>' + content + '</em>';
            });
            
            return text;
        }

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

        function waitForMarkedAndRender() {
            let attempts = 0;
            const maxAttempts = 50;
            
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

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', waitForMarkedAndRender);
        } else {
            waitForMarkedAndRender();
        }

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
                filename: 'exercicios_<?php echo $task_id; ?>_<?php echo time(); ?>.pdf',
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

