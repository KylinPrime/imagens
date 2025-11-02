<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Requer autenticação admin
if (!isAdmin()) {
    jsonResponse(['error' => 'Acesso negado'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método não permitido'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$imageId = $data['id'] ?? 0;

if (!$imageId) {
    jsonResponse(['error' => 'ID da imagem não informado'], 400);
}

// Busca imagem
$stmt = $pdo->prepare("SELECT * FROM images WHERE id = ?");
$stmt->execute([$imageId]);
$image = $stmt->fetch();

if (!$image) {
    jsonResponse(['error' => 'Imagem não encontrada'], 404);
}

try {
    // Remove arquivos
    $sku = $image['sku'];
    $ext = $image['file_extension'];
    
    $files = [
        ORIGINAL_DIR . $sku . '.' . $ext,
        CAROUSEL_DIR . $sku . '.webp',
        THUMBNAIL_DIR . $sku . '.webp'
    ];
    
    foreach ($files as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    // Remove do banco
    $stmt = $pdo->prepare("DELETE FROM images WHERE id = ?");
    $stmt->execute([$imageId]);
    
    jsonResponse(['success' => true, 'message' => 'Imagem removida com sucesso']);
    
} catch (Exception $e) {
    jsonResponse(['error' => 'Erro ao remover imagem: ' . $e->getMessage()], 500);
}
?>