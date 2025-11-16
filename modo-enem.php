<?php
require_once 'includes/session.php';
require_once 'includes/plan-check.php';
require_once 'includes/navbar.php';

requireLogin();
$user = getCurrentUser();

// Verificar se usuário tem plano que permite acesso ao Modo ENEM
if (!hasPlanAccess($user['id'], 'modo_enem')) {
    header('Location: planos.php?erro=precisa_assinar&tipo=enem');
    exit;
}

$message = '';

// Removido redirecionamento em POST; o formulário agora envia direto para criar-rotina.php
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modo ENEM - AIStudy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php $active = 'enem'; render_navbar($active); ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-graduation-cap me-2"></i>Criar Plano de Estudos para ENEM
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        
                        <form method="POST" id="enemForm" action="criar-rotina.php?tipo=enem">
                            <input type="hidden" name="tipo_rotina" value="enem">
                            <input type="hidden" name="return_to" value="modo-enem.php">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="ano_enem" class="form-label">Ano do ENEM *</label>
                                    <input type="number" class="form-control" id="ano_enem" name="ano_enem" placeholder="Ex: 2025" min="2009" max="2100" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="nota_alvo" class="form-label">Nota Alvo *</label>
                                    <input type="text" class="form-control" id="nota_alvo" name="nota_alvo" 
                                           placeholder="Ex: 750+" required>
                                    <small class="form-text text-muted">Nota que você pretende alcançar no ENEM</small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <!-- Campo de data prevista da prova removido a pedido do usuário -->
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Áreas Prioritárias *</label>
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="areas_prioritarias[]" value="Linguagens" id="area_linguagens">
                                            <label class="form-check-label" for="area_linguagens">Linguagens, Códigos e suas Tecnologias</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="areas_prioritarias[]" value="Humanas" id="area_humanas">
                                            <label class="form-check-label" for="area_humanas">Ciências Humanas e suas Tecnologias</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="areas_prioritarias[]" value="Natureza" id="area_natureza">
                                            <label class="form-check-label" for="area_natureza">Ciências da Natureza e suas Tecnologias</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="areas_prioritarias[]" value="Matematica" id="area_matematica">
                                            <label class="form-check-label" for="area_matematica">Matemática e suas Tecnologias</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="areas_prioritarias[]" value="Redacao" id="area_redacao">
                                            <label class="form-check-label" for="area_redacao">Redação</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Campo 'Disciplinas por área' removido a pedido do usuário -->
                            
                            <div class="mb-3">
                                <label for="pesos_disciplinas" class="form-label">Pesos por disciplina (0-5) (opcional)</label>
                                <textarea class="form-control" id="pesos_disciplinas" name="pesos_disciplinas" rows="3"
                                          placeholder="Ex: Matemática:5; Física:3; Química:2"></textarea>
                                <small class="form-text text-muted">Formato: Disciplina:Peso; separados por ponto e vírgula.</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nivel" class="form-label">Nível Atual *</label>
                                    <select class="form-select" id="nivel" name="nivel" required>
                                        <option value="">Selecione o nível</option>
                                        <option value="iniciante">Iniciante</option>
                                        <option value="intermediario">Intermediário</option>
                                        <option value="avancado">Avançado</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tempo_diario" class="form-label">Tempo Diário (minutos) *</label>
                                    <input type="number" class="form-control" id="tempo_diario" name="tempo_diario" 
                                           min="30" max="480" placeholder="Ex: 120" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="horario_disponivel" class="form-label">Horário Disponível *</label>
                                    <input type="time" class="form-control" id="horario_disponivel" name="horario_disponivel" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="dificuldades" class="form-label">Dificuldades Principais</label>
                                    <textarea class="form-control" id="dificuldades" name="dificuldades" rows="2" 
                                              placeholder="Ex: Tenho dificuldade em matemática e física"></textarea>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Dias da Semana Disponíveis *</label>
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
                                <strong>Plano ENEM Personalizado:</strong> Nossa IA criará um plano focado na matriz de competências do ENEM, 
                                estratégias TRI e revisão espaçada para maximizar sua nota.
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Voltar
                                </a>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-magic me-2"></i>Gerar Plano ENEM
                                </button>
                            </div>
                            
                            <div id="loadingOverlay" style="display: none; position: fixed; inset: 0; background: rgba(5,10,25,0.85); backdrop-filter: blur(2px); z-index: 9999; align-items: center; justify-content: center;">
                                <div class="text-white" style="width: 520px; max-width: 92vw; background: var(--card-bg); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.35);">
                                    <div style="padding: 22px 24px 8px 24px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(255,255,255,0.06);">
                                        <div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem;"></div>
                                        <div>
                                            <h5 style="margin:0;">Gerando sua rotina ENEM</h5>
                                            <small class="text-muted">Preparando plano detalhado e materiais</small>
                                        </div>
                                    </div>
                                    <div style="padding: 18px 24px;">
                                        <div id="overlayStep" class="mb-2" style="font-weight: 600;">Etapa 1/3 • Enviando instruções para a IA…</div>
                                        <div class="progress mb-2" style="height: 10px;">
                                            <div id="overlayBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 20%"></div>
                                        </div>
                                        <small class="text-muted" id="overlayTip">Dica: usamos títulos específicos para melhorar as recomendações de vídeo.</small>
                                        <ul class="mt-3 mb-0" style="padding-left: 18px; font-size: 0.92rem; line-height: 1.35;">
                                            <li id="overlayItem1">Preparando estrutura de tarefas…</li>
                                            <li id="overlayItem2" class="text-muted">Buscando vídeos relevantes no YouTube…</li>
                                            <li id="overlayItem3" class="text-muted">Finalizando e salvando sua rotina…</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dark-mode.js"></script>
    <script>
        (function() {
            const tips = [
                'Dica: títulos específicos evitam vídeos genéricos.',
                'Dica: você pode revisar e marcar tarefas concluídas.',
                'Dica: gere resumos rápidos após a criação da tarefa.'
            ];
            let tipIdx = 0;
            function nextTip(){
                const tipEl = document.getElementById('overlayTip');
                if (tipEl){ tipEl.textContent = tips[tipIdx % tips.length]; tipIdx++; }
            }
            setInterval(nextTip, 3500);
        })();

        document.getElementById('enemForm').addEventListener('submit', function(e) {
            const areasSelecionadas = document.querySelectorAll('input[name="areas_prioritarias[]"]:checked');
            const diasSelecionados = document.querySelectorAll('input[name="dias_disponiveis[]"]:checked');
            
            if (areasSelecionadas.length === 0) {
                e.preventDefault();
                alert('Selecione pelo menos uma área prioritária.');
                return;
            }
            
            if (diasSelecionados.length === 0) {
                e.preventDefault();
                alert('Selecione pelo menos um dia da semana disponível.');
                return;
            }
            
            const overlay = document.getElementById('loadingOverlay');
            const bar = document.getElementById('overlayBar');
            const step = document.getElementById('overlayStep');
            const i1 = document.getElementById('overlayItem1');
            const i2 = document.getElementById('overlayItem2');
            const i3 = document.getElementById('overlayItem3');
            const submitBtn = document.getElementById('submitBtn');
            if (overlay && submitBtn) {
                overlay.style.display = 'flex';
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Gerando…';
                setTimeout(function(){ step.textContent='Etapa 2/3 • Processando plano com IA…'; bar.style.width='55%'; i1.classList.add('text-muted'); }, 1800);
                setTimeout(function(){ step.textContent='Etapa 3/3 • Enriquecendo com vídeos e salvando…'; bar.style.width='85%'; i2.classList.remove('text-muted'); }, 4800);
                setTimeout(function(){ bar.style.width='95%'; }, 12000);
            }
        });
    </script>
</body>
</html>

