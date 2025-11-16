<?php
// Gerenciamento de sessões
session_start();

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
