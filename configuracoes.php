<?php
require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'includes/session.php';

requireLogin();

$user = getCurrentUser();

$database = new Database();
$db = $database->getConnection();
$user_obj = new User($db);
$user_obj->id = $user['id'];

$message = '';

if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $nome = $_POST['nome'] ?? '';
        $email = $_POST['email'] ?? '';
        
        if ($nome && $email) {
            $user_obj->nome = $nome;
            $user_obj->email = $email;
            
            // Verificar se o email já existe em outro usuário
            $checkQuery = "SELECT id FROM users WHERE email = :email AND id != :id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(":email", $email);
            $checkStmt->bindParam(":id", $user['id']);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                $message = '<div class="alert alert-danger">Este email já está sendo usado por outro usuário!</div>';
            } else {
                if ($user_obj->update()) {
                    // Atualizar sessão
                    $_SESSION['user_nome'] = $nome;
                    $_SESSION['user_email'] = $email;
                    $message = '<div class="alert alert-success">Perfil atualizado com sucesso!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Erro ao atualizar perfil!</div>';
                }
            }
        } else {
            $message = '<div class="alert alert-warning">Preencha todos os campos!</div>';
        }
    } elseif ($action === 'change_password') {
        $senha_atual = $_POST['senha_atual'] ?? '';
        $nova_senha = $_POST['nova_senha'] ?? '';
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';
        
        if ($senha_atual && $nova_senha && $confirmar_senha) {
            if ($nova_senha !== $confirmar_senha) {
                $message = '<div class="alert alert-danger">As senhas não coincidem!</div>';
            } elseif (strlen($nova_senha) < 6) {
                $message = '<div class="alert alert-danger">A nova senha deve ter pelo menos 6 caracteres!</div>';
            } else {
                if ($user_obj->verifyPassword($senha_atual)) {
                    if ($user_obj->updatePassword($nova_senha)) {
                        $message = '<div class="alert alert-success">Senha alterada com sucesso!</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Erro ao alterar senha!</div>';
                    }
                } else {
                    $message = '<div class="alert alert-danger">Senha atual incorreta!</div>';
                }
            }
        } else {
            $message = '<div class="alert alert-warning">Preencha todos os campos!</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIStudy - Configurações</title>
    
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
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-brain text-primary"></i> AIStudy
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="rotinas.php">
                            <i class="fas fa-list me-1"></i>Minhas Rotinas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="progresso.php">
                            <i class="fas fa-chart-line me-1"></i>Progresso
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item me-3">
                        <button class="theme-toggle" onclick="toggleTheme()" title="Alternar modo escuro/claro">
                            <i class="fas fa-moon"></i>
                        </button>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($user['nome']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item active" href="configuracoes.php">
                                <i class="fas fa-cog me-2"></i>Configurações
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Sair
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="text-gradient">Configurações</h1>
                <p class="text-muted">Gerencie suas informações pessoais e preferências</p>
            </div>
        </div>

        <?php echo $message; ?>

        <div class="row">
            <!-- Perfil -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-user me-2"></i>Informações Pessoais
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" id="nome" name="nome" 
                                       value="<?php echo htmlspecialchars($user['nome']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Salvar Alterações
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Alterar Senha -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-lock me-2"></i>Alterar Senha
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            <div class="mb-3">
                                <label for="senha_atual" class="form-label">Senha Atual</label>
                                <input type="password" class="form-control" id="senha_atual" name="senha_atual" required>
                            </div>
                            <div class="mb-3">
                                <label for="nova_senha" class="form-label">Nova Senha</label>
                                <input type="password" class="form-control" id="nova_senha" name="nova_senha" 
                                       minlength="6" required>
                                <div class="form-text">A senha deve ter pelo menos 6 caracteres.</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                                <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" 
                                       minlength="6" required>
                            </div>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key me-2"></i>Alterar Senha
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informações da Conta -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Informações da Conta
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted">ID do Usuário</label>
                                    <p class="mb-0"><?php echo $user['id']; ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted">Membro desde</label>
                                    <p class="mb-0"><?php echo date('d/m/Y', strtotime($user['created_at'] ?? 'now')); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Sobre o AIStudy:</strong> Esta é uma plataforma de estudos inteligente que utiliza 
                            inteligência artificial para criar rotinas de estudos personalizadas. Suas informações 
                            são protegidas e utilizadas apenas para melhorar sua experiência de aprendizado.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dark-mode.js"></script>
    <script>
        // Validação de senha
        document.getElementById('nova_senha').addEventListener('input', function() {
            const senha = this.value;
            const confirmar = document.getElementById('confirmar_senha');
            
            if (senha.length < 6) {
                this.setCustomValidity('A senha deve ter pelo menos 6 caracteres');
            } else {
                this.setCustomValidity('');
            }
        });
        
        document.getElementById('confirmar_senha').addEventListener('input', function() {
            const senha = document.getElementById('nova_senha').value;
            const confirmar = this.value;
            
            if (senha !== confirmar) {
                this.setCustomValidity('As senhas não coincidem');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
