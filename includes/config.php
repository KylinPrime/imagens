<?php
// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'u220553158_kylin_imagens');
define('DB_USER', 'u220553158_kylin_imagens');
define('DB_PASS', 'Adson1806@#$%');

// Configurações de upload
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('ORIGINAL_DIR', UPLOAD_DIR . 'original/');
define('CAROUSEL_DIR', UPLOAD_DIR . 'carousel/');
define('THUMBNAIL_DIR', UPLOAD_DIR . 'thumbnails/');

// Tamanhos de imagem
define('CAROUSEL_WIDTH', 1200);
define('CAROUSEL_HEIGHT', 800);
define('THUMBNAIL_WIDTH', 300);
define('THUMBNAIL_HEIGHT', 200);

// Conexão com banco de dados
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die(json_encode(['error' => 'Erro de conexão: ' . $e->getMessage()]));
}

// Criar diretórios se não existirem
$dirs = [UPLOAD_DIR, ORIGINAL_DIR, CAROUSEL_DIR, THUMBNAIL_DIR];
foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Função auxiliar para JSON response
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>