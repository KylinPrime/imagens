<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Requer login
if (!isLoggedIn()) {
    http_response_code(403);
    die('Acesso negado');
}

$imageId = $_GET['id'] ?? 0;

if (!$imageId) {
    http_response_code(400);
    die('ID não informado');
}

// Busca imagem
$stmt = $pdo->prepare("SELECT * FROM images WHERE id = ?");
$stmt->execute([$imageId]);
$image = $stmt->fetch();

if (!$image) {
    http_response_code(404);
    die('Imagem não encontrada');
}

$filePath = ORIGINAL_DIR . $image['sku'] . '.' . $image['file_extension'];

if (!file_exists($filePath)) {
    http_response_code(404);
    die('Arquivo não encontrado');
}

// Força download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $image['sku'] . '.' . $image['file_extension'] . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache');

readfile($filePath);
exit;
?>