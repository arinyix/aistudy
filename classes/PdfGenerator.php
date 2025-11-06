<?php
/**
 * Classe para gerar PDFs a partir de Markdown
 * Suporta múltiplas bibliotecas PDF
 */
class PdfGenerator {
    
    private $html;
    private $markdown;
    
    /**
     * Converte Markdown para HTML
     */
    private function convertMarkdownToHTML($markdown) {
        // Conversão básica de Markdown para HTML
        $html = $markdown;
        
        // Títulos
        $html = preg_replace('/^# (.*)$/m', '<h1>$1</h1>', $html);
        $html = preg_replace('/^## (.*)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^### (.*)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^#### (.*)$/m', '<h4>$1</h4>', $html);
        
        // Negrito e itálico
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html);
        
        // Listas
        $html = preg_replace('/^\- (.*)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/^\* (.*)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html);
        
        // Parágrafos
        $html = preg_replace('/\n\n/', '</p><p>', $html);
        $html = '<p>' . $html . '</p>';
        
        // Code blocks
        $html = preg_replace('/`(.*?)`/', '<code>$1</code>', $html);
        
        // Quebras de linha
        $html = nl2br($html);
        
        return $html;
    }
    
    /**
     * Adiciona estilos CSS para PDF
     */
    private function addStyles() {
        return '
        <style>
            body { 
                font-family: Arial, sans-serif; 
                max-width: 210mm;
                margin: 0 auto;
                padding: 20mm;
                line-height: 1.8;
                color: #1f2937;
            }
            h1 { 
                color: #1e40af; 
                font-size: 2.5rem;
                font-weight: 700;
                border-bottom: 3px solid #4f46e5;
                padding-bottom: 15px;
                margin-bottom: 30px;
                margin-top: 0;
            }
            h2 { 
                color: #6366f1; 
                font-size: 2rem;
                font-weight: 600;
                margin-top: 40px;
                margin-bottom: 20px;
                border-left: 5px solid #6366f1;
                padding-left: 15px;
            }
            h3 { 
                color: #4f46e5; 
                font-size: 1.5rem;
                font-weight: 600;
                margin-top: 30px;
                margin-bottom: 15px;
            }
            h4 { 
                color: #5b21b6; 
                font-size: 1.25rem;
                font-weight: 600;
                margin-top: 25px;
                margin-bottom: 12px;
            }
            p {
                line-height: 1.8;
                margin-bottom: 16px;
                font-size: 1.05rem;
                text-align: justify;
            }
            strong {
                color: #1e40af;
                font-weight: 600;
            }
            code {
                background: #f3f4f6;
                padding: 4px 8px;
                border-radius: 4px;
                font-family: "Courier New", monospace;
                font-size: 0.95rem;
                color: #dc2626;
            }
            ul, ol {
                margin: 20px 0;
                padding-left: 40px;
            }
            li {
                margin: 10px 0;
                line-height: 1.8;
                font-size: 1.05rem;
            }
            blockquote {
                border-left: 5px solid #4f46e5;
                padding: 15px 20px;
                margin: 25px 0;
                font-style: italic;
                color: #475569;
                background: #f8fafc;
                border-radius: 4px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 25px 0;
                font-size: 1rem;
            }
            table th,
            table td {
                border: 1px solid #d1d5db;
                padding: 12px 15px;
                text-align: left;
            }
            table th {
                background: #6366f1;
                color: white;
                font-weight: 600;
            }
            table tr:nth-child(even) {
                background: #f9fafb;
            }
        </style>';
    }
    
    /**
     * Cria HTML completo com Markdown convertido
     */
    public function createHTMLFromMarkdown($markdown) {
        $this->markdown = $markdown;
        $this->html = $this->convertMarkdownToHTML($markdown);
        
        return '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Resumo Auxiliar</title>
    ' . $this->addStyles() . '
</head>
<body>
' . $this->html . '
</body>
</html>';
    }
    
    /**
     * Tenta gerar PDF usando DomPDF
     */
    public function generatePDFWithDompdf($markdown, $filename) {
        // Verificar se DomPDF está disponível
        if (!class_exists('Dompdf\\Dompdf')) {
            // Tentar carregar manualmente
            if (file_exists(__DIR__ . '/../vendor/dompdf/src/Dompdf.php')) {
                require_once __DIR__ . '/../vendor/dompdf/src/Dompdf.php';
            } else {
                throw new Exception('DomPDF não instalado. Baixe de: https://github.com/dompdf/dompdf');
            }
        }
        
        $html = $this->createHTMLFromMarkdown($markdown);
        
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return $dompdf->output();
    }
    
    /**
     * Retorna HTML formatado para visualização no navegador
     */
    public function getHTML() {
        return $this->createHTMLFromMarkdown($this->markdown);
    }
}

