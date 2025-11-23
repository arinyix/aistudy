<?php
/**
 * Gerenciamento de sessões seguras
 */

// Configurar segurança de sessão ANTES de iniciar
ini_set('session.cookie_httponly', 1); // Previne acesso via JavaScript
ini_set('session.use_strict_mode', 1); // Previne session fixation
ini_set('session.cookie_samesite', 'Lax'); // Lax permite POST entre páginas do mesmo site

// Se estiver usando HTTPS, habilitar cookie seguro
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

// Configurar timeout de sessão (2 horas)
ini_set('session.gc_maxlifetime', 7200);
ini_set('session.cookie_lifetime', 7200);

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerar ID de sessão periodicamente (a cada 30 minutos)
// NÃO regenerar durante requisições POST para evitar problemas com CSRF tokens
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
        session_regenerate_id(true);
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'nome' => $_SESSION['user_nome'],
        'email' => $_SESSION['user_email']
    ];
}

function login($user) {
    $_SESSION['user_id'] = $user->id;
    $_SESSION['user_nome'] = $user->nome;
    $_SESSION['user_email'] = $user->email;
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Flash messages helpers
function setFlash(string $type, string $message): void {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$type] = $message;
}

function getFlash(?string $type = null) {
    if (!isset($_SESSION['flash'])) {
        return $type ? null : [];
    }
    if ($type === null) {
        $all = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $all;
    }
    $msg = $_SESSION['flash'][$type] ?? null;
    if ($msg !== null) {
        unset($_SESSION['flash'][$type]);
    }
    return $msg;
}
?>
