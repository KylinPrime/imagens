<?php
/**
 * Sistema de Consentimento de Cookies (LGPD) - VERSÃO HÍBRIDA
 * Kylin Prime - 2025
 * 
 * Usa Cookie (preferencial) + localStorage (fallback)
 * Exibe modal bloqueante até aceitar cookies
 */

// CORREÇÃO: Verifica se já aceitou (cookie ou POST)
$cookiesAccepted = false;

// Verifica se existe o cookie
if (isset($_COOKIE['cookies_accepted']) && $_COOKIE['cookies_accepted'] === 'true') {
    $cookiesAccepted = true;
}

// Processa aceitação via POST
if (isset($_POST['accept_cookies'])) {
    // Define cookie por 1 ano
    setcookie('cookies_accepted', 'true', [
        'expires' => time() + (365 * 24 * 60 * 60),
        'path' => '/',
        'secure' => false, // Mude para true se usar HTTPS
        'httponly' => false,
        'samesite' => 'Lax'
    ]);
    
    // Marca como aceito nesta sessão
    $cookiesAccepted = true;
    
    // Redireciona para limpar POST
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}
?>

<?php if (!$cookiesAccepted): ?>
<!-- Modal de Cookies (Bloqueante) -->
<div id="cookieConsent" class="cookie-consent-overlay" style="display: none;">
    <div class="cookie-consent-modal">
        <div class="cookie-consent-icon">🍪</div>
        <h2 class="cookie-consent-title">Uso de Cookies</h2>
        <p class="cookie-consent-text">
            Este site utiliza cookies para melhorar sua experiência de navegação, 
            personalizar conteúdo e analisar nosso tráfego. Ao continuar navegando, 
            você concorda com nossa <a href="#" style="color: var(--primary); text-decoration: underline;">Política de Privacidade</a>.
        </p>
        <p class="cookie-consent-text" style="font-size: 0.9em; color: #666; margin-top: 10px;">
            <strong>Cookies utilizados:</strong><br>
            • Cookies essenciais (login, sessão, idioma)<br>
            • Cookies de preferência (tema, carrinho)<br>
            • Não utilizamos cookies de terceiros ou rastreamento
        </p>
        <form method="POST" id="cookieConsentForm" style="margin-top: 25px;">
            <button type="submit" name="accept_cookies" class="cookie-consent-btn">
                ✓ Aceitar e Continuar
            </button>
        </form>
        <p class="cookie-consent-notice">
            * É necessário aceitar os cookies para utilizar o sistema
        </p>
    </div>
</div>

<style>
/* Modal de Cookies - Bloqueante */
.cookie-consent-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.9);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    animation: fadeIn 0.3s ease;
}

.cookie-consent-modal {
    background: white;
    padding: 40px;
    border-radius: 20px;
    max-width: 550px;
    width: 100%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    text-align: center;
    animation: slideUp 0.4s ease;
}

.cookie-consent-icon {
    font-size: 4em;
    margin-bottom: 20px;
    animation: bounce 1s infinite;
}

.cookie-consent-title {
    font-size: 1.8em;
    color: #333;
    margin-bottom: 20px;
}

.cookie-consent-text {
    color: #666;
    line-height: 1.6;
    margin-bottom: 15px;
    text-align: left;
}

.cookie-consent-btn {
    width: 100%;
    padding: 18px;
    background: linear-gradient(135deg, #FF512F 0%, #F09819 50%, #FF6B35 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1.2em;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.cookie-consent-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(255, 81, 47, 0.4);
}

.cookie-consent-notice {
    margin-top: 15px;
    font-size: 0.85em;
    color: #999;
    font-style: italic;
}

/* Animações */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { 
        opacity: 0;
        transform: translateY(50px); 
    }
    to { 
        opacity: 1;
        transform: translateY(0); 
    }
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

/* Responsivo */
@media (max-width: 768px) {
    .cookie-consent-modal {
        padding: 30px 20px;
    }
    
    .cookie-consent-icon {
        font-size: 3em;
    }
    
    .cookie-consent-title {
        font-size: 1.5em;
    }
}
</style>

<script>
/**
 * Sistema Híbrido de Consentimento
 * Tenta Cookie primeiro, depois localStorage como fallback
 */
(function() {
    'use strict';
    
    const CONSENT_KEY = 'cookies_accepted';
    const modal = document.getElementById('cookieConsent');
    
    /**
     * Verifica se já aceitou (Cookie OU localStorage)
     */
    function hasConsent() {
        // Verifica cookie primeiro
        const cookieConsent = document.cookie
            .split('; ')
            .find(row => row.startsWith(CONSENT_KEY + '='));
        
        if (cookieConsent && cookieConsent.split('=')[1] === 'true') {
            console.log('✓ Consentimento via Cookie');
            return true;
        }
        
        // Fallback: verifica localStorage
        try {
            if (localStorage.getItem(CONSENT_KEY) === 'true') {
                console.log('✓ Consentimento via localStorage (fallback)');
                return true;
            }
        } catch (e) {
            console.warn('localStorage não disponível:', e);
        }
        
        console.log('✗ Consentimento não encontrado');
        return false;
    }
    
    /**
     * Salva consentimento (Cookie + localStorage)
     */
    function saveConsent() {
        // Salva em localStorage (sempre funciona)
        try {
            localStorage.setItem(CONSENT_KEY, 'true');
            console.log('✓ Salvo em localStorage');
        } catch (e) {
            console.error('Erro ao salvar em localStorage:', e);
        }
        
        // Tenta salvar em cookie também
        try {
            const expires = new Date();
            expires.setFullYear(expires.getFullYear() + 1); // 1 ano
            document.cookie = `${CONSENT_KEY}=true; expires=${expires.toUTCString()}; path=/; SameSite=Lax`;
            console.log('✓ Salvo em Cookie');
        } catch (e) {
            console.error('Erro ao salvar cookie:', e);
        }
    }
    
    /**
     * Inicialização
     */
    function init() {
        // Se já aceitou, não mostra modal
        if (hasConsent()) {
            document.body.style.overflow = 'auto';
            return;
        }
        
        // Mostra modal
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Handler do formulário
        const form = document.getElementById('cookieConsentForm');
        form.addEventListener('submit', function(e) {
            // Se localStorage funcionar, previne submit
            try {
                if (typeof(Storage) !== 'undefined') {
                    e.preventDefault();
                    saveConsent();
                    
                    // Esconde modal com animação
                    modal.style.animation = 'fadeOut 0.3s ease';
                    setTimeout(() => {
                        modal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                    }, 300);
                    
                    return false;
                }
            } catch (err) {
                console.warn('localStorage falhou, usando cookie via POST');
                // Deixa o form submeter normalmente (fallback PHP)
            }
        });
    }
    
    // Executa quando DOM carregar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Adiciona animação de fadeOut
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    `;
    document.head.appendChild(style);
})();
</script>
<?php else: ?>
<script>
// Cookies já aceitos no PHP
document.body.style.overflow = 'auto';
console.log('✓ Consentimento verificado no servidor (Cookie PHP)');
</script>
<?php endif; ?>