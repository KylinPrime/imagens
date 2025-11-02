<?php
/**
 * Servidor de Imagens Protegidas
 * Serve imagens com marca d'água para usuários não logados
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/watermark.php';

// Obtém parâmetros
$imageId = $_GET['id'] ?? 0;
$type = $_GET['type'] ?? 'carousel'; // carousel, thumbnail, ou original

if (!$imageId) {
    http_response_code(400);
    exit('Invalid request');
}

// Busca imagem no banco
$stmt = $pdo->prepare("SELECT * FROM images WHERE id = ?");
$stmt->execute([$imageId]);
$image = $stmt->fetch();

if (!$image) {
    http_response_code(404);
    exit('Image not found');
}

// Define caminho baseado no tipo
switch ($type) {
    case 'thumbnail':
        $imagePath = THUMBNAIL_DIR . $image['sku'] . '.webp';
        break;
    case 'original':
        $imagePath = ORIGINAL_DIR . $image['sku'] . '.' . $image['file_extension'];
        break;
    case 'carousel':
    default:
        $imagePath = CAROUSEL_DIR . $image['sku'] . '.webp';
        break;
}

// Verifica se arquivo existe
if (!file_exists($imagePath)) {
    http_response_code(404);
    exit('File not found');
}

// Usuários que não precisam marca d'água (admin e user)
if (isLoggedIn() && in_array($_SESSION['role'], ['admin', 'user'])) {
    // Serve imagem sem marca d'água
    $mime = mime_content_type($imagePath);
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($imagePath));
    header('Cache-Control: private, max-age=3600');
    readfile($imagePath);
    exit;
}

// Usuários não logados ou clientes recebem versão com marca d'água diagonal
$watermarkedDir = dirname($imagePath) . '/watermarked/';
if (!is_dir($watermarkedDir)) {
    mkdir($watermarkedDir, 0755, true);
}

$watermarkedPath = $watermarkedDir . basename($imagePath);

// Gera marca d'água se não existir
if (!file_exists($watermarkedPath)) {
    Watermark::applyDiagonalPattern($imagePath, 'Kylin Prime © 2025', $watermarkedPath);
}

// Serve imagem com marca d'água
if (file_exists($watermarkedPath)) {
    $mime = mime_content_type($watermarkedPath);
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($watermarkedPath));
    header('Cache-Control: public, max-age=3600');
    header('X-Watermarked: true');
    readfile($watermarkedPath);
} else {
    // Fallback: serve original se falhar
    $mime = mime_content_type($imagePath);
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($imagePath));
    readfile($imagePath);
}

exit;
?>