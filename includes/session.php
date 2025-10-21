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
?>
