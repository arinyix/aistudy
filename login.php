<?php
require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'includes/session.php';
require_once 'includes/csrf.php';
require_once 'includes/rate-limit.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$message = '';

// Obter IP do cliente (considerando proxies)
$client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    // Validar CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf_token)) {
        $message = '<div class="alert alert-danger">Token de segurança inválido. Por favor, recarregue a página e tente novamente.</div>';
    } elseif ($action === 'login') {
        // Rate limiting: máximo 5 tentativas por minuto
        if (!checkRateLimit('login', $client_ip, 5, 60)) {
            $remainingTime = getRateLimitRemainingTime('login', $client_ip, 60);
            $message = '<div class="alert alert-warning">Muitas tentativas de login. Tente novamente em ' . $remainingTime . ' segundos.</div>';
        } else {
            $email = $_POST['email'] ?? '';
            $senha = $_POST['senha'] ?? '';
            
            if ($user->login($email, $senha)) {
                // Limpar rate limit em caso de sucesso
                clearRateLimit('login', $client_ip);
                login($user);
                regenerateCSRFToken(); // Regenerar token após login bem-sucedido
                header('Location: dashboard.php');
                exit();
            } else {
                $message = '<div class="alert alert-danger">Email ou senha incorretos!</div>';
            }
        }
    } elseif ($action === 'register') {
        // Rate limiting: máximo 3 cadastros por hora
        if (!checkRateLimit('register', $client_ip, 3, 3600)) {
            $remainingTime = getRateLimitRemainingTime('register', $client_ip, 3600);
            $minutes = ceil($remainingTime / 60);
            $message = '<div class="alert alert-warning">Muitas tentativas de cadastro. Tente novamente em ' . $minutes . ' minuto(s).</div>';
        } else {
            $user->nome = $_POST['nome'] ?? '';
            $user->email = $_POST['email'] ?? '';
            $user->senha_hash = $_POST['senha'] ?? '';
            
            if ($user->emailExists()) {
                $message = '<div class="alert alert-danger">Este email já está cadastrado!</div>';
            } else {
                if ($user->create()) {
                    clearRateLimit('register', $client_ip);
                    regenerateCSRFToken();
                    $message = '<div class="alert alert-success">Cadastro realizado com sucesso! Faça login para continuar.</div>';
                } else {
                    $message = '<div class="alert alert-danger">Erro ao cadastrar usuário!</div>';
                }
            }
        }
    }
}

// Gerar token CSRF para os formulários
$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIStudy - Login</title>
    
    <!-- Aplicar tema ANTES de carregar estilos para evitar flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = savedTheme || (prefersDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="container">
        <div class="row justify-content-center min-vh-100 align-items-center">
            <div class="col-md-6 col-lg-5">
                <div class="position-absolute top-0 end-0 m-4">
                    <button class="theme-toggle" onclick="toggleTheme()" title="Alternar modo escuro/claro">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
                <div class="auth-card">
                    <div class="text-center mb-4">
                        <h1 class="auth-title">
                            <i class="fas fa-brain text-primary"></i>
                            AIStudy
                        </h1>
                        <p class="text-muted">Sua plataforma de estudos inteligente</p>
                    </div>
                    
                    <?php echo $message; ?>
                    
                    <!-- Formulário de Login -->
                    <div id="loginForm">
                        <form method="POST">
                            <input type="hidden" name="action" value="login">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="senha" class="form-label">Senha</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="senha" name="senha" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Entrar
                            </button>
                        </form>
                        <div class="text-center">
                            <p class="mb-0">Não tem conta? 
                                <a href="#" onclick="showRegister()" class="text-primary">Cadastre-se</a>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Formulário de Cadastro -->
                    <div id="registerForm" style="display: none;">
                        <form method="POST">
                            <input type="hidden" name="action" value="register">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome Completo</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="nome" name="nome" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email_register" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email_register" name="email" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="senha_register" class="form-label">Senha</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="senha_register" name="senha" 
                                           minlength="6" required 
                                           placeholder="Digite sua senha">
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted d-block mb-1">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        <strong>Requisitos da senha:</strong>
                                    </small>
                                    <ul class="small text-muted mb-0 ps-3" style="line-height: 1.6;">
                                        <li>Mínimo de <strong>6 caracteres</strong></li>
                                        <li>Recomendado: use letras, números e caracteres especiais para maior segurança</li>
                                    </ul>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success w-100 mb-3">
                                <i class="fas fa-user-plus me-2"></i>Cadastrar
                            </button>
                        </form>
                        <div class="text-center">
                            <p class="mb-0">Já tem conta? 
                                <a href="#" onclick="showLogin()" class="text-primary">Faça login</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dark-mode.js"></script>
    <script>
        function showRegister() {
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('registerForm').style.display = 'block';
        }
        
        function showLogin() {
            document.getElementById('registerForm').style.display = 'none';
            document.getElementById('loginForm').style.display = 'block';
        }
    </script>
</body>
</html>
