<?php
/**
 * Processador de Marca D'água - VERSÃO MELHORADA
 * Kylin Prime - 2025
 * 
 * Melhorias:
 * - Tamanho da fonte se adapta ao tamanho da imagem
 * - Marca d'água mais visível (opacidade ajustada)
 * - Contorno mais forte
 * - Espaçamento proporcional
 */

class Watermark {
    
    /**
     * Aplica marca d'água em padrão diagonal repetido
     * VERSÃO MELHORADA - Mais visível em qualquer tamanho
     * 
     * @param string $sourcePath Caminho da imagem original
     * @param string $text Texto da marca d'água
     * @param string $outputPath Caminho de saída (opcional)
     * @return string|bool Caminho da imagem com marca d'água ou false em caso de erro
     */
    public static function applyDiagonalPattern($sourcePath, $text = 'Kylin Prime © 2025', $outputPath = null) {
        $source = self::loadImage($sourcePath);
        if (!$source) return false;
        
        $width = imagesx($source);
        $height = imagesy($source);
        
        // Calcula tamanho da fonte baseado no tamanho da imagem
        // Sistema adaptativo: pequenas = 18px, médias = 24px, grandes = 32px
        $minDimension = min($width, $height);
        
        if ($minDimension < 400) {
            $fontSize = 18; // Imagens pequenas (thumbnails)
        } elseif ($minDimension < 800) {
            $fontSize = 24; // Imagens médias
        } elseif ($minDimension < 1200) {
            $fontSize = 28; // Imagens grandes
        } else {
            $fontSize = 32; // Imagens muito grandes
        }
        
        $angle = 45; // Ângulo diagonal
        
        // Cor branca com MELHOR opacidade (mais visível)
        // 50 = mais visível (0 = opaco, 127 = transparente)
        $textColor = imagecolorallocatealpha($source, 255, 255, 255, 50);
        
        // Contorno escuro MAIS FORTE para melhor visibilidade
        $outlineColor = imagecolorallocatealpha($source, 0, 0, 0, 50);
        
        // Calcula dimensões do texto
        $bbox = imagettfbbox($fontSize, $angle, self::getFont(), $text);
        if (!$bbox) {
            // Fallback se não tiver fonte TTF
            $textWidth = strlen($text) * ($fontSize / 1.5);
            $textHeight = $fontSize;
        } else {
            $textWidth = abs($bbox[4] - $bbox[0]);
            $textHeight = abs($bbox[5] - $bbox[1]);
        }
        
        // Espaçamento entre marcas (proporcional mas MENOR para cobrir mais)
        $spacingX = $textWidth + ($width / 15);  // Espaçamento horizontal reduzido
        $spacingY = $textHeight + ($height / 15); // Espaçamento vertical reduzido
        
        // Aplica marca d'água em padrão diagonal DENSO
        for ($y = -$height; $y < $height * 2; $y += $spacingY) {
            for ($x = -$width; $x < $width * 2; $x += $spacingX) {
                if ($bbox) {
                    // Desenha contorno MAIS GROSSO (2px ao invés de 1px)
                    for ($ox = -2; $ox <= 2; $ox++) {
                        for ($oy = -2; $oy <= 2; $oy++) {
                            if ($ox != 0 || $oy != 0) {
                                imagettftext($source, $fontSize, $angle, $x + $ox, $y + $oy, $outlineColor, self::getFont(), $text);
                            }
                        }
                    }
                    // Desenha texto principal
                    imagettftext($source, $fontSize, $angle, $x, $y, $textColor, self::getFont(), $text);
                } else {
                    // Fallback simples
                    imagestring($source, 5, $x, $y, $text, $textColor);
                }
            }
        }
        
        // Salva imagem
        if (!$outputPath) {
            $outputPath = str_replace('.', '_watermarked.', $sourcePath);
        }
        
        $result = self::saveImage($source, $outputPath);
        imagedestroy($source);
        
        return $result ? $outputPath : false;
    }
    
    /**
     * Retorna caminho da fonte TTF
     */
    private static function getFont() {
        // Tenta várias fontes comuns do sistema
        $fonts = [
            __DIR__ . '/../assets/fonts/arial.ttf',
            __DIR__ . '/../assets/fonts/Arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf', // Bold para melhor visibilidade
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            'C:/Windows/Fonts/arialbd.ttf', // Arial Bold
            'C:/Windows/Fonts/arial.ttf'
        ];
        
        foreach ($fonts as $font) {
            if (file_exists($font)) {
                return $font;
            }
        }
        
        // Retorna path padrão mesmo se não existir (fallback funcionará)
        return $fonts[0];
    }
    
    /**
     * Aplica marca d'água em uma imagem
     * 
     * @param string $sourcePath Caminho da imagem original
     * @param string $logoPath Caminho do logo
     * @param string $outputPath Caminho de saída (opcional)
     * @return string|bool Caminho da imagem com marca d'água ou false em caso de erro
     */
    public static function apply($sourcePath, $logoPath = null, $outputPath = null) {
        // Usa padrão diagonal sempre
        return self::applyDiagonalPattern($sourcePath, 'Kylin Prime © 2025', $outputPath);
    }
    
    /**
     * Gera marca d'água dinâmica com texto (se não houver logo)
     */
    public static function applyTextWatermark($sourcePath, $text = 'Kylin Prime', $outputPath = null) {
        // Usa o padrão diagonal sempre
        return self::applyDiagonalPattern($sourcePath, $text, $outputPath);
    }
    
    /**
     * Carrega imagem de qualquer formato
     */
    private static function loadImage($path) {
        $info = @getimagesize($path);
        if (!$info) return false;
        
        switch ($info['mime']) {
            case 'image/jpeg':
                return @imagecreatefromjpeg($path);
            case 'image/png':
                return @imagecreatefrompng($path);
            case 'image/webp':
                return @imagecreatefromwebp($path);
            default:
                return false;
        }
    }
    
    /**
     * Salva imagem
     */
    private static function saveImage($image, $path) {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                return imagejpeg($image, $path, 90);
            case 'png':
                return imagepng($image, $path, 9);
            case 'webp':
                return imagewebp($image, $path, 90);
            default:
                return imagewebp($image, $path, 90);
        }
    }
    
    /**
     * Verifica se usuário precisa de marca d'água
     */
    public static function needsWatermark() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Não logado = precisa marca d'água
        if (!isset($_SESSION['user_id'])) {
            return true;
        }
        
        // Cliente = precisa marca d'água
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'cliente') {
            return true;
        }
        
        // Admin e user = não precisa marca d'água
        return false;
    }
    
    /**
     * Verifica se usuário está logado para servir imagem
     */
    public static function serveProtectedImage($imagePath, $requireLogin = true) {
        if (self::needsWatermark()) {
            // Redireciona para imagem com marca d'água
            $watermarkedPath = self::getWatermarkedPath($imagePath);
            
            if (!file_exists($watermarkedPath)) {
                // Gera marca d'água se não existir
                self::applyDiagonalPattern($imagePath, 'Kylin Prime © 2025', $watermarkedPath);
            }
            
            $imagePath = $watermarkedPath;
        }
        
        // Serve imagem
        if (file_exists($imagePath)) {
            $mime = mime_content_type($imagePath);
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($imagePath));
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');
            readfile($imagePath);
            exit;
        }
        
        http_response_code(404);
        exit;
    }
    
    /**
     * Retorna caminho para versão com marca d'água
     */
    private static function getWatermarkedPath($imagePath) {
        $dir = dirname($imagePath);
        $filename = basename($imagePath);
        return $dir . '/watermarked_' . $filename;
    }
}
?>