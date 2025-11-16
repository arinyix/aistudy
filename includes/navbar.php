<?php
if (!function_exists('render_navbar')) {
    function render_navbar(string $active = ''): void {
        // Expect $user to be available in the including scope
        $userName = isset($GLOBALS['user']['nome']) ? htmlspecialchars($GLOBALS['user']['nome']) : 'Usuário';
        $isActive = function(string $key) use ($active): string {
            return $active === $key ? ' active' : '';
        };
        ?>
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container">
                <a class="navbar-brand" href="dashboard.php">
                    <i class="fas fa-brain text-primary"></i> AIStudy
                </a>
                
                <!-- Container com toggle switch (mobile) e hambúrguer - apenas no mobile -->
                <div class="d-flex align-items-center gap-2 d-lg-none">
                    <button class="theme-toggle-switch" onclick="toggleTheme()" type="button" aria-label="Alternar tema">
                        <span class="theme-toggle-slider"></span>
                    </button>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </div>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link<?php echo $isActive('dashboard'); ?>" href="dashboard.php">
                                <i class="fas fa-home me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo $isActive('rotinas'); ?>" href="rotinas.php">
                                <i class="fas fa-list me-1"></i>Minhas Rotinas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo $isActive('progresso'); ?>" href="progresso.php">
                                <i class="fas fa-chart-line me-1"></i>Progresso
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo $isActive('enem'); ?>" href="modo-enem.php">
                                <i class="fas fa-graduation-cap me-1"></i>Modo ENEM
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo $isActive('concurso'); ?>" href="modo-concurso.php">
                                <i class="fas fa-briefcase me-1"></i>Modo Concurso
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo $isActive('planos'); ?>" href="planos.php">
                                <i class="fas fa-star me-1"></i>Planos
                            </a>
                        </li>
                    </ul>
                    
                    <ul class="navbar-nav">
                        <!-- Botão de tema para desktop -->
                        <li class="nav-item me-3 d-none d-lg-block">
                            <button class="theme-toggle" onclick="toggleTheme()" title="Alternar modo escuro/claro">
                                <i class="fas fa-moon"></i>
                            </button>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?php echo $userName; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="configuracoes.php">
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
        <?php
    }
}
