<?php
/**
 * Importação de Categorias via CSV/Excel
 * Kylin Prime - 2025
 * 
 * Formato CSV esperado:
 * nome,prefixo
 * Dragões,DG
 * Florais,FL
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';

// Apenas admins
if (!isLoggedIn() || !isAdmin()) {
    jsonResponse(['error' => 'Access denied'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

if (!isset($_FILES['csv_file'])) {
    jsonResponse(['error' => 'No file uploaded'], 400);
}

$file = $_FILES['csv_file'];

// Validações
if ($file['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['error' => 'File upload error'], 400);
}

$allowedTypes = ['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/csv'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes) && !preg_match('/\.(csv|txt)$/i', $file['name'])) {
    jsonResponse(['error' => 'Invalid file type. Please upload CSV file'], 400);
}

// Processa CSV
$imported = 0;
$errors = [];
$skipped = 0;

try {
    $handle = fopen($file['tmp_name'], 'r');
    
    if ($handle === false) {
        jsonResponse(['error' => 'Could not read file'], 500);
    }
    
    // Pula header (primeira linha)
    $header = fgetcsv($handle, 1000, ',');
    
    // Valida header
    if (!$header || count($header) < 2) {
        fclose($handle);
        jsonResponse(['error' => 'Invalid CSV format. Expected: nome,prefixo'], 400);
    }
    
    $lineNumber = 1;
    
    while (($data = fgetcsv($handle, 1000, ',')) !== false) {
        $lineNumber++;
        
        // Ignora linhas vazias
        if (empty($data[0]) && empty($data[1])) {
            continue;
        }
        
        $name = trim($data[0] ?? '');
        $prefix = strtoupper(trim($data[1] ?? ''));
        
        // Validações
        if (empty($name) || empty($prefix)) {
            $errors[] = "Linha {$lineNumber}: Nome e prefixo são obrigatórios";
            continue;
        }
        
        if (strlen($prefix) < 2 || strlen($prefix) > 3) {
            $errors[] = "Linha {$lineNumber}: Prefixo '{$prefix}' deve ter 2-3 caracteres";
            continue;
        }
        
        if (!preg_match('/^[A-Z]+$/', $prefix)) {
            $errors[] = "Linha {$lineNumber}: Prefixo '{$prefix}' deve conter apenas letras";
            continue;
        }
        
        // Verifica duplicatas
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ? OR prefix = ?");
        $stmt->execute([$name, $prefix]);
        
        if ($stmt->fetchColumn() > 0) {
            $skipped++;
            $errors[] = "Linha {$lineNumber}: Categoria '{$name}' ou prefixo '{$prefix}' já existe (ignorado)";
            continue;
        }
        
        // Insere categoria
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, prefix) VALUES (?, ?)");
            $stmt->execute([$name, $prefix]);
            $imported++;
        } catch (PDOException $e) {
            $errors[] = "Linha {$lineNumber}: Erro ao inserir '{$name}' - " . $e->getMessage();
        }
    }
    
    fclose($handle);
    
    jsonResponse([
        'success' => true,
        'imported' => $imported,
        'skipped' => $skipped,
        'errors' => $errors,
        'message' => "{$imported} categoria(s) importada(s) com sucesso"
    ]);
    
} catch (Exception $e) {
    jsonResponse(['error' => 'Error processing file: ' . $e->getMessage()], 500);
}
?>