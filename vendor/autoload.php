<?php
// Simple autoloader for PDF libraries

// Function to autoload PDF classes
spl_autoload_register(function ($class) {
    // Dompdf namespace
    if (strpos($class, 'Dompdf\\') === 0) {
        $classFile = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 7));
        $file = __DIR__ . '/dompdf/src/' . $classFile . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
    
    // mPDF namespace
    if (strpos($class, 'Mpdf\\') === 0) {
        $classFile = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 5));
        $file = __DIR__ . '/mpdf/' . $classFile . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Markdown parser autoload (if needed later)
if (file_exists(__DIR__ . '/parsedown/Parsedown.php')) {
    require_once __DIR__ . '/parsedown/Parsedown.php';
}

