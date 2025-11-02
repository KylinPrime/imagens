<?php
require_once 'config.php';

class ImageProcessor {
    
    // Gera SKU único baseado na categoria
    public static function generateSKU($categoryPrefix) {
        global $pdo;
        
        do {
            $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
            $sku = $categoryPrefix . '-' . $random;
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM images WHERE sku = ?");
            $stmt->execute([$sku]);
            $exists = $stmt->fetchColumn() > 0;
        } while ($exists);
        
        return $sku;
    }
    
    // Detecta se a imagem fica melhor com fundo claro ou escuro
    public static function detectBackgroundType($imagePath) {
        $image = self::loadImage($imagePath);
        if (!$image) return 'light';
        
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Analisa as bordas da imagem
        $brightness = 0;
        $samples = 0;
        
        // Amostra pixels das bordas
        for ($x = 0; $x < $width; $x += 10) {
            for ($y = 0; $y < $height; $y += 10) {
                if ($x < 50 || $x > $width - 50 || $y < 50 || $y > $height - 50) {
                    $rgb = imagecolorat($image, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    $brightness += ($r + $g + $b) / 3;
                    $samples++;
                }
            }
        }
        
        imagedestroy($image);
        
        $avgBrightness = $brightness / $samples;
        return $avgBrightness > 127 ? 'dark' : 'light';
    }
    
    // Carrega imagem de qualquer formato
    private static function loadImage($path) {
        $info = getimagesize($path);
        
        switch ($info['mime']) {
            case 'image/jpeg':
                return imagecreatefromjpeg($path);
            case 'image/png':
                return imagecreatefrompng($path);
            case 'image/webp':
                return imagecreatefromwebp($path);
            default:
                return false;
        }
    }
    
    // Redimensiona e salva imagem
    public static function resizeImage($sourcePath, $destPath, $maxWidth, $maxHeight, $quality = 85) {
        $source = self::loadImage($sourcePath);
        if (!$source) return false;
        
        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);
        
        // Calcula proporções
        $ratio = min($maxWidth / $srcWidth, $maxHeight / $srcHeight);
        $newWidth = round($srcWidth * $ratio);
        $newHeight = round($srcHeight * $ratio);
        
        // Cria nova imagem
        $dest = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserva transparência para PNG
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        
        // Redimensiona
        imagecopyresampled(
            $dest, $source,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $srcWidth, $srcHeight
        );
        
        // Salva como WebP para melhor performance
        $result = imagewebp($dest, $destPath, $quality);
        
        imagedestroy($source);
        imagedestroy($dest);
        
        return $result;
    }
    
    // Processa upload de imagem
    public static function processUpload($file, $category, $userId) {
        global $pdo;
        
        // Validações
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erro no upload do arquivo');
        }
        
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception('Arquivo muito grande');
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, ALLOWED_TYPES)) {
            throw new Exception('Tipo de arquivo não permitido');
        }
        
        // Busca prefixo da categoria
        $stmt = $pdo->prepare("SELECT prefix FROM categories WHERE id = ?");
        $stmt->execute([$category]);
        $categoryData = $stmt->fetch();
        
        if (!$categoryData) {
            throw new Exception('Categoria inválida');
        }
        
        // Gera SKU
        $sku = self::generateSKU($categoryData['prefix']);
        
        // Salva imagem original
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $originalPath = ORIGINAL_DIR . $sku . '.' . $extension;
        
        if (!move_uploaded_file($file['tmp_name'], $originalPath)) {
            throw new Exception('Erro ao salvar arquivo');
        }
        
        // Gera versões otimizadas
        $carouselPath = CAROUSEL_DIR . $sku . '.webp';
        $thumbnailPath = THUMBNAIL_DIR . $sku . '.webp';
        
        self::resizeImage($originalPath, $carouselPath, CAROUSEL_WIDTH, CAROUSEL_HEIGHT);
        self::resizeImage($originalPath, $thumbnailPath, THUMBNAIL_WIDTH, THUMBNAIL_HEIGHT);
        
        // Detecta tipo de fundo
        $backgroundType = self::detectBackgroundType($carouselPath);
        
        // Salva no banco
        $stmt = $pdo->prepare("
            INSERT INTO images (sku, category, original_name, file_extension, background_type, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $sku,
            $category,
            $file['name'],
            $extension,
            $backgroundType,
            $userId
        ]);
        
        return [
            'sku' => $sku,
            'background_type' => $backgroundType
        ];
    }
}
?>