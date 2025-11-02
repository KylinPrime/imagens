<?php
/**
 * Ações em Lote para Imagens
 * Kylin Prime - 2025
 * 
 * Funcionalidades:
 * - Mudança de categoria (gera novo SKU)
 * - Delete múltiplo
 * - Marcar como indisponível
 * - Reativar imagens
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/image_processor.php';

// Apenas admins
if (!isLoggedIn() || !isAdmin()) {
    jsonResponse(['error' => 'Access denied'], 403);
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$imageIds = $data['image_ids'] ?? [];

if (empty($imageIds) || !is_array($imageIds)) {
    jsonResponse(['error' => 'No images selected'], 400);
}

switch ($action) {
    case 'change_category':
        changeCategory($data);
        break;
        
    case 'delete_multiple':
        deleteMultiple($imageIds);
        break;
        
    case 'mark_unavailable':
        markUnavailable($imageIds, true);
        break;
        
    case 'mark_available':
        markUnavailable($imageIds, false);
        break;
        
    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}

/**
 * Muda categoria de múltiplas imagens (gera novo SKU)
 */
function changeCategory($data) {
    global $pdo;
    
    $imageIds = $data['image_ids'] ?? [];
    $newCategoryId = $data['new_category'] ?? 0;
    
    if (!$newCategoryId) {
        jsonResponse(['error' => 'Category required'], 400);
    }
    
    // Busca prefixo da nova categoria
    $stmt = $pdo->prepare("SELECT prefix FROM categories WHERE id = ?");
    $stmt->execute([$newCategoryId]);
    $category = $stmt->fetch();
    
    if (!$category) {
        jsonResponse(['error' => 'Category not found'], 404);
    }
    
    $updated = 0;
    $errors = [];
    
    try {
        foreach ($imageIds as $imageId) {
            // Busca imagem
            $stmt = $pdo->prepare("SELECT * FROM images WHERE id = ?");
            $stmt->execute([$imageId]);
            $image = $stmt->fetch();
            
            if (!$image) {
                $errors[] = "Imagem ID {$imageId} não encontrada";
                continue;
            }
            
            // Gera novo SKU
            $newSku = ImageProcessor::generateSKU($category['prefix']);
            
            // Renomeia arquivos físicos
            $oldSku = $image['sku'];
            $ext = $image['file_extension'];
            
            $files = [
                'original' => [ORIGINAL_DIR . $oldSku . '.' . $ext, ORIGINAL_DIR . $newSku . '.' . $ext],
                'carousel' => [CAROUSEL_DIR . $oldSku . '.webp', CAROUSEL_DIR . $newSku . '.webp'],
                'thumbnail' => [THUMBNAIL_DIR . $oldSku . '.webp', THUMBNAIL_DIR . $newSku . '.webp']
            ];
            
            foreach ($files as $type => $paths) {
                if (file_exists($paths[0])) {
                    rename($paths[0], $paths[1]);
                }
            }
            
            // Atualiza banco
            $stmt = $pdo->prepare("UPDATE images SET category = ?, sku = ? WHERE id = ?");
            $stmt->execute([$newCategoryId, $newSku, $imageId]);
            
            $updated++;
        }
        
        jsonResponse([
            'success' => true,
            'updated' => $updated,
            'errors' => $errors,
            'message' => "{$updated} imagem(ns) atualizada(s)"
        ]);
        
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error: ' . $e->getMessage()], 500);
    }
}

/**
 * Deleta múltiplas imagens
 */
function deleteMultiple($imageIds) {
    global $pdo;
    
    $deleted = 0;
    $errors = [];
    
    try {
        foreach ($imageIds as $imageId) {
            // Busca imagem
            $stmt = $pdo->prepare("SELECT * FROM images WHERE id = ?");
            $stmt->execute([$imageId]);
            $image = $stmt->fetch();
            
            if (!$image) {
                $errors[] = "Imagem ID {$imageId} não encontrada";
                continue;
            }
            
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
            
            $deleted++;
        }
        
        jsonResponse([
            'success' => true,
            'deleted' => $deleted,
            'errors' => $errors,
            'message' => "{$deleted} imagem(ns) deletada(s)"
        ]);
        
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error: ' . $e->getMessage()], 500);
    }
}

/**
 * Marca como indisponível (ou disponível)
 */
function markUnavailable($imageIds, $unavailable) {
    global $pdo;
    
    $updated = 0;
    
    try {
        $stmt = $pdo->prepare("UPDATE images SET available = ? WHERE id = ?");
        
        foreach ($imageIds as $imageId) {
            $stmt->execute([$unavailable ? 0 : 1, $imageId]);
            if ($stmt->rowCount() > 0) {
                $updated++;
            }
        }
        
        $status = $unavailable ? 'indisponíveis' : 'disponíveis';
        
        jsonResponse([
            'success' => true,
            'updated' => $updated,
            'message' => "{$updated} imagem(ns) marcada(s) como {$status}"
        ]);
        
    } catch (Exception $e) {
        jsonResponse(['error' => 'Error: ' . $e->getMessage()], 500);
    }
}
?>