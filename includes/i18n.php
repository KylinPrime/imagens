<?php
/**
 * Sistema de Internacionalização (i18n)
 * Kylin Prime - 2025
 * 
 * CORREÇÃO: SVG das bandeiras agora inline para renderizar corretamente
 */

class i18n {
    private static $language = 'pt-br';
    private static $translations = [];
    private static $initialized = false;
    
    /**
     * Inicializa o sistema de traduções
     */
    public static function init($userLanguage = null) {
        if (self::$initialized) {
            return; // Já inicializado
        }
        
        global $pdo;
        
        // Garante que sessão está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Define idioma
        if ($userLanguage) {
            self::$language = $userLanguage;
        } elseif (isset($_SESSION['language'])) {
            self::$language = $_SESSION['language'];
        } elseif (isset($_COOKIE['language'])) {
            self::$language = $_COOKIE['language'];
        }
        
        // Carrega traduções do banco
        try {
            $stmt = $pdo->query("SELECT key_name, pt_br, zh_cn FROM translations");
            while ($row = $stmt->fetch()) {
                self::$translations[$row['key_name']] = [
                    'pt-br' => $row['pt_br'],
                    'zh-cn' => $row['zh_cn']
                ];
            }
        } catch (PDOException $e) {
            // Falha silenciosa - usa textos em português
        }
        
        self::$initialized = true;
    }
    
    /**
     * Traduz uma chave
     */
    public static function t($key, $default = null) {
        if (isset(self::$translations[$key][self::$language])) {
            return self::$translations[$key][self::$language];
        }
        
        return $default ?? $key;
    }
    
    /**
     * Retorna o idioma atual
     */
    public static function getLanguage() {
        return self::$language;
    }
    
    /**
     * Define o idioma
     */
    public static function setLanguage($lang) {
        if (in_array($lang, ['pt-br', 'zh-cn'])) {
            self::$language = $lang;
            
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $_SESSION['language'] = $lang;
            setcookie('language', $lang, time() + (86400 * 365), '/');
            
            // Atualiza no banco se usuário logado
            if (isset($_SESSION['user_id'])) {
                global $pdo;
                try {
                    $stmt = $pdo->prepare("UPDATE users SET language = ? WHERE id = ?");
                    $stmt->execute([$lang, $_SESSION['user_id']]);
                } catch (PDOException $e) {
                    // Falha silenciosa
                }
            }
        }
    }
    
    /**
     * Retorna array com idiomas disponíveis
     * CORREÇÃO: SVG agora está inline com style para garantir renderização
     */
    public static function getLanguages() {
        return [
            'pt-br' => [
                'name' => 'Português', 
                'flag' => '🇧🇷' // Emoji como fallback garantido
            ],
            'zh-cn' => [
                'name' => '中文', 
                'flag' => '🇨🇳' // Emoji como fallback garantido
            ]
        ];
    }
    
    /**
     * Retorna HTML do seletor de idiomas
     * CORREÇÃO FINAL: CSS embutido para garantir renderização em TODAS as páginas
     */
    public static function languageSelector() {
        $languages = self::getLanguages();
        $current = self::$language;
        
        $html = '<div class="language-selector">';
        
        foreach ($languages as $code => $info) {
            $active = $code === $current ? 'active' : '';
            $opacity = $active ? '1' : '0.5';
            $transform = $active ? 'scale(1.1)' : 'scale(1)';
            
            $html .= sprintf(
                '<a href="?change_lang=%s" class="lang-option %s" title="%s">%s</a>',
                $code,
                $active,
                $info['name'],
                $info['flag']
            );
        }
        
        $html .= '</div>';
        
        // CSS inline para garantir funcionamento em TODAS as páginas
        $html .= '
        <style>
        .language-selector {
            display: inline-flex;
            gap: 10px;
            align-items: center;
            vertical-align: middle;
        }
        .lang-option {
            text-decoration: none;
            font-size: 1.8em;
            line-height: 1;
            opacity: 0.5;
            transition: all 0.3s ease;
            display: inline-block;
            cursor: pointer;
        }
        .lang-option:hover {
            opacity: 0.8;
            transform: scale(1.05);
        }
        .lang-option.active {
            opacity: 1;
            transform: scale(1.15);
        }
        </style>';
        
        return $html;
    }
}

// Processa mudança de idioma
if (isset($_GET['change_lang'])) {
    i18n::setLanguage($_GET['change_lang']);
    
    // Remove o parâmetro da URL e redireciona
    $url = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: $url");
    exit;
}

// Inicializa
$userLang = null;
if (session_status() !== PHP_SESSION_NONE && isset($_SESSION['language'])) {
    $userLang = $_SESSION['language'];
}
i18n::init($userLang);

// Função helper global
if (!function_exists('__')) {
    function __($key, $default = null) {
        return i18n::t($key, $default);
    }
}
?>