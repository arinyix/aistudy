<?php
require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'includes/session.php';
require_once 'includes/navbar.php';

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
        $senha_confirmacao = $_POST['senha_confirmacao'] ?? '';
        
        if ($nome && $email) {
            // Verificar se o email foi alterado
            $email_alterado = $email !== $user['email'];
            
            // Se o email foi alterado, verificar senha
            if ($email_alterado) {
                if (empty($senha_confirmacao)) {
                    $message = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Para alterar o email, é necessário informar sua senha atual!</div>';
                } elseif (!$user_obj->verifyPassword($senha_confirmacao)) {
                    $message = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Senha incorreta! Para alterar o email, você precisa confirmar com sua senha atual.</div>';
                } else {
                    // Senha correta, continuar com a atualização
                    $user_obj->nome = $nome;
                    $user_obj->email = $email;
                    
                    // Verificar se o email já existe em outro usuário
                    $checkQuery = "SELECT id FROM users WHERE email = :email AND id != :id";
                    $checkStmt = $db->prepare($checkQuery);
                    $checkStmt->bindParam(":email", $email);
                    $checkStmt->bindParam(":id", $user['id']);
                    $checkStmt->execute();
                    
                    if ($checkStmt->rowCount() > 0) {
                        $message = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Este email já está sendo usado por outro usuário!</div>';
                    } else {
                        if ($user_obj->update()) {
                            // Atualizar sessão
                            $_SESSION['user_nome'] = $nome;
                            $_SESSION['user_email'] = $email;
                            $message = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Perfil atualizado com sucesso! Seu email foi alterado.</div>';
                            // Recarregar dados do usuário
                            $user = getCurrentUser();
                        } else {
                            $message = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Erro ao atualizar perfil!</div>';
                        }
                    }
                }
            } else {
                // Email não foi alterado, apenas atualizar nome
                $user_obj->nome = $nome;
                
                if ($user_obj->update()) {
                    // Atualizar sessão
                    $_SESSION['user_nome'] = $nome;
                    $message = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Perfil atualizado com sucesso!</div>';
                    // Recarregar dados do usuário
                    $user = getCurrentUser();
                } else {
                    $message = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Erro ao atualizar perfil!</div>';
                }
            }
        } else {
            $message = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Preencha todos os campos obrigatórios!</div>';
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
    <?php $active = ''; render_navbar($active); ?>

    <div class="container mt-5 mb-5">
        <!-- Header Profissional -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="settings-header">
                    <div class="settings-header-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="settings-header-content">
                        <h1 class="settings-title">Configurações</h1>
                        <p class="settings-subtitle">Gerencie suas informações pessoais e preferências da conta</p>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="settings-alert-wrapper">
                        <?php echo $message; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Perfil -->
            <div class="col-lg-6">
                <div class="settings-card">
                    <div class="settings-card-header settings-card-header-primary">
                        <div class="settings-card-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <h3 class="settings-card-title">Informações Pessoais</h3>
                            <p class="settings-card-subtitle">Atualize seu nome e email</p>
                        </div>
                    </div>
                    <div class="settings-card-body">
                        <form method="POST" id="profileForm">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="settings-form-group">
                                <label for="nome" class="settings-label">
                                    <i class="fas fa-user-circle"></i>
                                    <span>Nome Completo</span>
                                </label>
                                <div class="settings-input-wrapper">
                                    <input type="text" class="settings-input" id="nome" name="nome" 
                                           value="<?php echo htmlspecialchars($user['nome']); ?>" required
                                           placeholder="Seu nome completo">
                                </div>
                            </div>
                            
                            <div class="settings-form-group">
                                <label for="email" class="settings-label">
                                    <i class="fas fa-envelope"></i>
                                    <span>Email</span>
                                </label>
                                <div class="settings-input-wrapper">
                                    <input type="email" class="settings-input" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required
                                           placeholder="seu@email.com">
                                </div>
                                <div class="settings-form-help">
                                    <i class="fas fa-info-circle"></i>
                                    Se você alterar o email, será necessário confirmar com sua senha.
                                </div>
                            </div>
                            
                            <!-- Campo de senha (aparece quando email é alterado) -->
                            <div class="settings-form-group" id="senhaConfirmacaoContainer" style="display: none;">
                                <label for="senha_confirmacao" class="settings-label">
                                    <i class="fas fa-lock"></i>
                                    <span>Senha Atual (para alterar email)</span>
                                </label>
                                <div class="settings-input-wrapper">
                                    <input type="password" class="settings-input settings-input-warning" 
                                           id="senha_confirmacao" name="senha_confirmacao" 
                                           placeholder="Digite sua senha">
                                </div>
                                <div class="settings-form-help settings-form-help-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Obrigatório:</strong> Para alterar o email, confirme com sua senha atual.
                                </div>
                            </div>
                            
                            <div class="settings-form-actions">
                                <button type="submit" class="settings-btn settings-btn-primary">
                                    <i class="fas fa-save"></i>
                                    <span>Salvar Alterações</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Alterar Senha -->
            <div class="col-lg-6">
                <div class="settings-card">
                    <div class="settings-card-header settings-card-header-warning">
                        <div class="settings-card-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div>
                            <h3 class="settings-card-title">Alterar Senha</h3>
                            <p class="settings-card-subtitle">Mantenha sua conta segura</p>
                        </div>
                    </div>
                    <div class="settings-card-body">
                        <form method="POST" id="passwordForm">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="settings-form-group">
                                <label for="senha_atual" class="settings-label">
                                    <i class="fas fa-key"></i>
                                    <span>Senha Atual</span>
                                </label>
                                <div class="settings-input-wrapper">
                                    <input type="password" class="settings-input" id="senha_atual" 
                                           name="senha_atual" required placeholder="Digite sua senha atual">
                                </div>
                            </div>
                            
                            <div class="settings-form-group">
                                <label for="nova_senha" class="settings-label">
                                    <i class="fas fa-lock"></i>
                                    <span>Nova Senha</span>
                                </label>
                                <div class="settings-input-wrapper">
                                    <input type="password" class="settings-input" id="nova_senha" 
                                           name="nova_senha" minlength="6" required placeholder="Mínimo 6 caracteres">
                                </div>
                                <div class="settings-form-help">
                                    <i class="fas fa-shield-alt"></i>
                                    A senha deve ter pelo menos 6 caracteres.
                                </div>
                            </div>
                            
                            <div class="settings-form-group">
                                <label for="confirmar_senha" class="settings-label">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Confirmar Nova Senha</span>
                                </label>
                                <div class="settings-input-wrapper">
                                    <input type="password" class="settings-input" id="confirmar_senha" 
                                           name="confirmar_senha" minlength="6" required 
                                           placeholder="Digite a senha novamente">
                                </div>
                            </div>
                            
                            <div class="settings-form-actions">
                                <button type="submit" class="settings-btn settings-btn-warning">
                                    <i class="fas fa-key"></i>
                                    <span>Alterar Senha</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informações da Conta -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="settings-card">
                    <div class="settings-card-header settings-card-header-info">
                        <div class="settings-card-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div>
                            <h3 class="settings-card-title">Informações da Conta</h3>
                            <p class="settings-card-subtitle">Detalhes sobre sua conta</p>
                        </div>
                    </div>
                    <div class="settings-card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="settings-info-card">
                                    <div class="settings-info-icon settings-info-icon-primary">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div class="settings-info-content">
                                        <label class="settings-info-label">Membro desde</label>
                                        <p class="settings-info-value">
                                            <?php 
                                            $data_criacao = $user['created_at'] ?? date('Y-m-d H:i:s');
                                            echo date('d/m/Y', strtotime($data_criacao)); 
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="settings-info-card">
                                    <div class="settings-info-icon settings-info-icon-success">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                    <div class="settings-info-content">
                                        <label class="settings-info-label">Status da Conta</label>
                                        <p class="settings-info-value">
                                            <span class="settings-badge settings-badge-success">
                                                <i class="fas fa-check-circle"></i>
                                                Ativa
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-info-box">
                            <div class="settings-info-box-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="settings-info-box-content">
                                <h4 class="settings-info-box-title">Sobre o AIStudy</h4>
                                <p class="settings-info-box-text">
                                    Esta é uma plataforma de estudos inteligente que utiliza inteligência artificial 
                                    para criar rotinas de estudos personalizadas. Suas informações são protegidas e 
                                    utilizadas apenas para melhorar sua experiência de aprendizado.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dark-mode.js"></script>
    <script>
        // Email original para comparar
        const emailOriginal = '<?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?>';
        const emailInput = document.getElementById('email');
        const senhaConfirmacaoContainer = document.getElementById('senhaConfirmacaoContainer');
        const senhaConfirmacaoInput = document.getElementById('senha_confirmacao');
        
        // Mostrar/ocultar campo de senha quando email é alterado
        emailInput.addEventListener('input', function() {
            if (this.value !== emailOriginal) {
                senhaConfirmacaoContainer.style.display = 'block';
                senhaConfirmacaoInput.required = true;
            } else {
                senhaConfirmacaoContainer.style.display = 'none';
                senhaConfirmacaoInput.required = false;
                senhaConfirmacaoInput.value = '';
            }
        });
        
        // Validação do formulário de perfil
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const emailAtual = emailInput.value;
            const emailFoiAlterado = emailAtual !== emailOriginal;
            
            if (emailFoiAlterado && !senhaConfirmacaoInput.value) {
                e.preventDefault();
                alert('⚠️ Para alterar o email, é necessário informar sua senha atual!');
                senhaConfirmacaoInput.focus();
                return false;
            }
        });
        
        // Validação de senha
        document.getElementById('nova_senha').addEventListener('input', function() {
            const senha = this.value;
            const confirmar = document.getElementById('confirmar_senha');
            
            if (senha.length < 6) {
                this.setCustomValidity('A senha deve ter pelo menos 6 caracteres');
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
            
            // Verificar se as senhas coincidem
            if (confirmar.value && senha !== confirmar.value) {
                confirmar.setCustomValidity('As senhas não coincidem');
                confirmar.classList.remove('is-valid');
                confirmar.classList.add('is-invalid');
            } else if (confirmar.value && senha === confirmar.value) {
                confirmar.setCustomValidity('');
                confirmar.classList.remove('is-invalid');
                confirmar.classList.add('is-valid');
            }
        });
        
        document.getElementById('confirmar_senha').addEventListener('input', function() {
            const senha = document.getElementById('nova_senha').value;
            const confirmar = this.value;
            
            if (senha !== confirmar) {
                this.setCustomValidity('As senhas não coincidem');
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            } else if (senha.length >= 6) {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
        
        // Limpar formulário de senha após sucesso
        <?php if (isset($_POST['action']) && $_POST['action'] === 'change_password' && strpos($message, 'sucesso') !== false): ?>
        document.getElementById('passwordForm').reset();
        <?php endif; ?>
    </script>
</body>
</html>
