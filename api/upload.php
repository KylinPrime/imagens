<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/image_processor.php';

// Requer autenticação admin
if (!isAdmin()) {
    jsonResponse(['error' => 'Acesso negado'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método não permitido'], 405);
}

$category = $_POST['category'] ?? '';

if (!$category) {
    jsonResponse(['error' => 'Categoria não informada'], 400);
}

if (!isset($_FILES['images'])) {
    jsonResponse(['error' => 'Nenhuma imagem enviada'], 400);
}

$files = $_FILES['images'];
$uploadedImages = [];
$errors = [];

// Normaliza array de arquivos
$fileCount = is_array($files['name']) ? count($files['name']) : 1;

for ($i = 0; $i < $fileCount; $i++) {
    $file = [
        'name' => is_array($files['name']) ? $files['name'][$i] : $files['name'],
        'type' => is_array($files['type']) ? $files['type'][$i] : $files['type'],
        'tmp_name' => is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'],
        'error' => is_array($files['error']) ? $files['error'][$i] : $files['error'],
        'size' => is_array($files['size']) ? $files['size'][$i] : $files['size']
    ];
    
    try {
        $result = ImageProcessor::processUpload($file, $category, $_SESSION['user_id']);
        $uploadedImages[] = $result;
    } catch (Exception $e) {
        $errors[] = [
            'file' => $file['name'],
            'error' => $e->getMessage()
        ];
    }
    
    // Pequeno delay para evitar sobrecarga em hospedagem compartilhada
    usleep(100000); // 0.1 segundo
}

jsonResponse([
    'success' => true,
    'uploaded' => $uploadedImages,
    'errors' => $errors,
    'total' => count($uploadedImages)
]);
?>