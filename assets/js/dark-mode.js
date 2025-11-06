// Dark Mode Toggle - Versão melhorada
(function() {
    'use strict';
    
    // Aplicar tema IMEDIATAMENTE para evitar flash
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const currentTheme = savedTheme || (prefersDark ? 'dark' : 'light');
    
    // Aplicar tema antes mesmo do DOM estar pronto
    document.documentElement.setAttribute('data-theme', currentTheme);
    
    // Função para definir o tema
    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        updateThemeIcon(theme);
    }
    
    // Função para alternar entre temas
    window.toggleTheme = function() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        setTheme(newTheme);
    };
    
    // Atualizar ícone do botão
    function updateThemeIcon(theme) {
        const buttons = document.querySelectorAll('.theme-toggle');
        
        buttons.forEach(button => {
            const icon = button.querySelector('i');
            if (icon) {
                // Remove todos os ícones possíveis
                icon.classList.remove('fa-moon', 'fa-sun');
                
                if (theme === 'dark') {
                    icon.classList.add('fa-sun');
                } else {
                    icon.classList.add('fa-moon');
                }
            }
        });
        
        // O toggle switch mobile é atualizado automaticamente via CSS
        // usando o atributo data-theme no HTML, então não precisa de código adicional
    }
    
    // Inicializar quando o DOM estiver pronto
    document.addEventListener('DOMContentLoaded', function() {
        // Atualizar ícone inicial
        updateThemeIcon(currentTheme);
        
        // Escutar mudanças na preferência do sistema
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('theme')) {
                setTheme(e.matches ? 'dark' : 'light');
            }
        });
    });
})();
