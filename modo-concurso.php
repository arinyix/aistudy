<?php
require_once 'includes/session.php';
require_once 'includes/plan-check.php';
require_once 'includes/navbar.php';
require_once 'includes/csrf.php';

requireLogin();
$user = getCurrentUser();

// Gerar token CSRF para o formulário
$csrf_token = generateCSRFToken();

// Verificar se usuário tem plano que permite acesso ao Modo Concurso
if (!hasPlanAccess($user['id'], 'modo_concurso')) {
    header('Location: planos.php?erro=precisa_assinar&tipo=concurso');
    exit;
}

$message = '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modo Concurso - AIStudy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        /* Landing Page Styles */
        .landing-hero {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
            border-radius: 24px;
            padding: 60px 40px;
            margin: 40px 0;
            position: relative;
            overflow: hidden;
        }

        .landing-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.1) 0%, transparent 70%);
            animation: pulse 8s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 800px;
        }

        .hero-icon {
            font-size: 5rem;
            color: var(--primary-color);
            margin-bottom: 30px;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--text-secondary);
            margin-bottom: 40px;
            line-height: 1.8;
        }

        .start-btn {
            padding: 18px 48px;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .start-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .start-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .start-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(99, 102, 241, 0.4);
        }

        .start-btn:active {
            transform: translateY(-1px);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-top: 60px;
        }

        .feature-card {
            background: var(--card-bg);
            border: 1px solid var(--border-default);
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .feature-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .feature-description {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* Form Container Styles */
        .form-container {
            display: none;
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-container.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
            animation: slideInUp 0.5s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-card {
            background: var(--card-bg);
            border: 1px solid var(--border-default);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .form-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            text-align: center;
        }

        .form-header h2 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }

        .form-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }

        .form-body {
            padding: 40px;
        }

        .form-section {
            margin-bottom: 35px;
            padding-bottom: 35px;
            border-bottom: 1px solid var(--border-default);
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-control, .form-select {
            border-radius: 12px;
            border: 2px solid var(--border-default);
            padding: 12px 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-back {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 500;
        }

        .btn-submit {
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 600;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        }

        /* Loading Overlay Styles - Prevenir piscar */
        #loadingOverlay {
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100% !important;
            height: 100% !important;
            position: fixed !important;
            z-index: 99999 !important;
            pointer-events: auto !important;
            user-select: none !important;
            -webkit-user-select: none !important;
            -moz-user-select: none !important;
            -ms-user-select: none !important;
        }

        #loadingOverlay * {
            pointer-events: auto !important;
        }

        body.overlay-active {
            overflow: hidden !important;
        }

        body.overlay-active #loadingOverlay,
        body.overlay-active #loadingOverlay * {
            pointer-events: auto !important;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            .hero-subtitle {
                font-size: 1rem;
            }
            .form-body {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <?php $active = 'concurso'; render_navbar($active); ?>

    <div class="container mt-4">
        <!-- Landing Page -->
        <div id="landingPage" class="landing-hero">
            <div class="hero-content">
                <div class="hero-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <h1 class="hero-title">Plano de Estudos para Concurso</h1>
                <p class="hero-subtitle">
                    Crie um plano de estudos inteligente e personalizado para concursos públicos. 
                    Nossa IA analisa o tema, a banca examinadora e automaticamente infere as disciplinas 
                    cobradas, criando tarefas específicas com subtemas reais para cada área.
                </p>
                <button class="btn start-btn" id="startBtn">
                    <i class="fas fa-rocket me-2"></i>
                    Começar Agora
                </button>

                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-robot"></i>
                        </div>
                        <h3 class="feature-title">IA Automatizada</h3>
                        <p class="feature-description">
                            A IA infere automaticamente as disciplinas cobradas baseado no tema e banca
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-list-check"></i>
                        </div>
                        <h3 class="feature-title">Tarefas Específicas</h3>
                        <p class="feature-description">
                            Criação automática de tarefas com subtemas reais de cada disciplina
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <h3 class="feature-title">Múltiplas Bancas</h3>
                        <p class="feature-description">
                            Suporte para CESPE, FGV, FCC, VUNESP, IBFC e outras bancas principais
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-target"></i>
                        </div>
                        <h3 class="feature-title">Foco Estratégico</h3>
                        <p class="feature-description">
                            Plano adaptado ao estilo de cada banca examinadora
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Container -->
        <div id="formContainer" class="form-container">
            <div class="form-card">
                <div class="form-header">
                    <h2><i class="fas fa-briefcase me-2"></i>Criar Plano de Estudos para Concurso Público</h2>
                    <p>Preencha os dados abaixo para gerar seu plano personalizado</p>
                </div>
                <div class="form-body">
                    <?php echo $message; ?>
                    
                    <form method="POST" id="concursoForm" action="criar-rotina.php?tipo=concurso">
                        <input type="hidden" name="tipo_rotina" value="concurso">
                        <input type="hidden" name="return_to" value="modo-concurso.php">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-info-circle"></i>
                                Informações do Concurso
                            </h3>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="tema" class="form-label">Tema/Área do Concurso *</label>
                                    <input type="text" class="form-control" id="tema" name="tema" 
                                           placeholder="Ex: Receita Federal, Tribunais, Polícia, Fiscal" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="banca" class="form-label">Banca Principal *</label>
                                    <input type="text" class="form-control" id="banca" name="banca" 
                                           list="bancas-list" 
                                           placeholder="Selecione ou digite a banca" 
                                           autocomplete="off" 
                                           required>
                                    <datalist id="bancas-list">
                                        <option value="CESPE/Cebraspe">CESPE/Cebraspe</option>
                                        <option value="FGV">FGV</option>
                                        <option value="FCC">FCC</option>
                                        <option value="VUNESP">VUNESP</option>
                                        <option value="IBFC">IBFC</option>
                                        <option value="Quadrix">Quadrix</option>
                                        <option value="CESGRANRIO">CESGRANRIO</option>
                                        <option value="ESAF">ESAF</option>
                                        <option value="FUNRIO">FUNRIO</option>
                                        <option value="AOCP">AOCP</option>
                                        <option value="IADES">IADES</option>
                                        <option value="NUCEPE">NUCEPE</option>
                                    </datalist>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Selecione uma banca da lista ou digite uma banca personalizada
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-sliders-h"></i>
                                Configurações de Estudo
                            </h3>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="nivel" class="form-label">Nível Atual *</label>
                                    <select class="form-select" id="nivel" name="nivel" required>
                                        <option value="">Selecione o nível</option>
                                        <option value="iniciante">Iniciante</option>
                                        <option value="intermediario">Intermediário</option>
                                        <option value="avancado">Avançado</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="tempo_diario" class="form-label">Tempo Diário (minutos) *</label>
                                    <input type="number" class="form-control" id="tempo_diario" name="tempo_diario" 
                                           min="30" max="480" placeholder="Ex: 180" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="horario_disponivel" class="form-label">Horário Disponível *</label>
                                    <input type="time" class="form-control" id="horario_disponivel" name="horario_disponivel" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="numero_dias" class="form-label">Número de Dias *</label>
                                    <input type="number" class="form-control" id="numero_dias" name="numero_dias" 
                                           min="1" max="365" value="120" placeholder="Ex: 90" required>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Quantos dias de estudo você deseja?
                                    </small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="dificuldades" class="form-label">Dificuldades Principais (opcional)</label>
                                    <textarea class="form-control" id="dificuldades" name="dificuldades" rows="3" 
                                              placeholder="Ex: Tenho dificuldade em Direito Constitucional"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-calendar-alt"></i>
                                Dias da Semana Disponíveis
                            </h3>
                            <div class="row">
                                <div class="col-md-3 col-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="dias_disponiveis[]" value="segunda" id="segunda">
                                        <label class="form-check-label" for="segunda">Segunda</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="dias_disponiveis[]" value="terca" id="terca">
                                        <label class="form-check-label" for="terca">Terça</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="dias_disponiveis[]" value="quarta" id="quarta">
                                        <label class="form-check-label" for="quarta">Quarta</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="dias_disponiveis[]" value="quinta" id="quinta">
                                        <label class="form-check-label" for="quinta">Quinta</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="dias_disponiveis[]" value="sexta" id="sexta">
                                        <label class="form-check-label" for="sexta">Sexta</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="dias_disponiveis[]" value="sabado" id="sabado">
                                        <label class="form-check-label" for="sabado">Sábado</label>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="dias_disponiveis[]" value="domingo" id="domingo">
                                        <label class="form-check-label" for="domingo">Domingo</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Plano Concurso Automatizado:</strong> Nossa IA irá inferir automaticamente as disciplinas cobradas para este tema/área e banca, 
                            criando tarefas específicas com subtemas reais. Você só precisa informar o tema, a banca e suas preferências de estudo.
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-outline-secondary btn-back" id="backBtn">
                                <i class="fas fa-arrow-left me-2"></i>Voltar
                            </button>
                            <button type="submit" class="btn btn-primary btn-submit" id="submitBtn">
                                <i class="fas fa-magic me-2"></i>Gerar Plano Concurso
                            </button>
                        </div>
                        
                        <div id="loadingOverlay" style="display: none; position: fixed; inset: 0; background: rgba(5,10,25,0.85); backdrop-filter: blur(2px); z-index: 9999; align-items: center; justify-content: center; pointer-events: auto;">
                            <div class="text-white" style="width: 520px; max-width: 92vw; background: var(--card-bg); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.35);">
                                <div style="padding: 22px 24px 8px 24px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(255,255,255,0.06);">
                                    <div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem;"></div>
                                    <div>
                                        <h5 style="margin:0;">Gerando sua rotina para Concurso</h5>
                                        <small class="text-muted">Inferindo disciplinas e criando tarefas específicas</small>
                                    </div>
                                </div>
                                <div style="padding: 18px 24px;">
                                    <div id="overlayStep" class="mb-2" style="font-weight: 600;">Etapa 1/3 • Analisando tema e banca…</div>
                                    <div class="progress mb-2" style="height: 10px;">
                                        <div id="overlayBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 20%"></div>
                                    </div>
                                    <small class="text-muted" id="overlayTip">A IA está inferindo as disciplinas cobradas para este concurso.</small>
                                    <ul class="mt-3 mb-0" style="padding-left: 18px; font-size: 0.92rem; line-height: 1.35;">
                                        <li id="overlayItem1">Inferindo disciplinas do concurso…</li>
                                        <li id="overlayItem2" class="text-muted">Criando tarefas específicas por disciplina…</li>
                                        <li id="overlayItem3" class="text-muted">Buscando vídeos e finalizando…</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dark-mode.js"></script>
    <script>
        // Controlar exibição da landing page e formulário
        const landingPage = document.getElementById('landingPage');
        const formContainer = document.getElementById('formContainer');
        const startBtn = document.getElementById('startBtn');
        const backBtn = document.getElementById('backBtn');

        // Mostrar formulário ao clicar em "Começar Agora"
        startBtn.addEventListener('click', function() {
            landingPage.style.display = 'none';
            formContainer.classList.add('show');
            // Scroll suave para o topo
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Voltar para landing page
        if (backBtn) {
            backBtn.addEventListener('click', function() {
                formContainer.classList.remove('show');
                setTimeout(() => {
                    formContainer.style.display = 'none';
                    landingPage.style.display = 'flex';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }, 300);
            });
        }

        // Dicas rotativas no overlay
        (function() {
            const tips = [
                'A IA está analisando o tema e a banca para inferir as disciplinas.',
                'Criando tarefas específicas com subtemas reais de cada disciplina.',
                'Cada tarefa terá formato: Disciplina: Subtema — [Banca].'
            ];
            let tipIdx = 0;
            function nextTip(){
                const tipEl = document.getElementById('overlayTip');
                if (tipEl){ tipEl.textContent = tips[tipIdx % tips.length]; tipIdx++; }
            }
            setInterval(nextTip, 3500);
        })();

        // Validação e envio do formulário
        document.getElementById('concursoForm').addEventListener('submit', function(e) {
            const diasSelecionados = document.querySelectorAll('input[name="dias_disponiveis[]"]:checked');
            const tema = document.getElementById('tema').value.trim();
            if (!tema) { 
                e.preventDefault(); 
                alert('Informe o tema/área do concurso.'); 
                return; 
            }
            if (diasSelecionados.length === 0) { 
                e.preventDefault(); 
                alert('Selecione pelo menos um dia da semana disponível.'); 
                return; 
            }
            
            // Não prevenir o envio - deixar o formulário ser submetido normalmente
            const overlay = document.getElementById('loadingOverlay');
            const bar = document.getElementById('overlayBar');
            const step = document.getElementById('overlayStep');
            const i1 = document.getElementById('overlayItem1');
            const i2 = document.getElementById('overlayItem2');
            const i3 = document.getElementById('overlayItem3');
            const submitBtn = document.getElementById('submitBtn');
            
            if (overlay && submitBtn) {
                // Desabilitar botão imediatamente para evitar múltiplos cliques
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Gerando…';
                
                // Mostrar overlay imediatamente (o formulário já está sendo enviado)
                overlay.style.display = 'flex';
                
                // Bloquear interações após um pequeno delay para garantir que o submit foi processado
                setTimeout(function() {
                    document.body.classList.add('overlay-active');
                }, 100);
                
                setTimeout(function(){ step.textContent='Etapa 2/3 • Criando plano com tarefas específicas…'; bar.style.width='55%'; i1.classList.add('text-muted'); }, 1800);
                setTimeout(function(){ step.textContent='Etapa 3/3 • Enriquecendo com vídeos e salvando…'; bar.style.width='85%'; i2.classList.remove('text-muted'); }, 4800);
                setTimeout(function(){ bar.style.width='95%'; }, 12000);
            }
        });
    </script>
</body>
</html>
