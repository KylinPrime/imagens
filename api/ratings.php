<?php
/**
 * API de Avaliações de Imagens
 * Kylin Prime - 2025
 * 
 * Permite avaliadores e admins avaliarem imagens
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';

// Apenas avaliadores e admins
if (!isLoggedIn() || (!isAvaliador() && !isAdmin())) {
    jsonResponse(['error' => 'Access denied'], 403);
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'rate':
        rateImage($data);
        break;
        
    case 'get_rating':
        getMyRating();
        break;
        
    case 'get_stats':
        getImageStats();
        break;
        
    case 'list_ratings':
        listAllRatings();
        break;
        
    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}

/**
 * Avalia uma imagem
 */
function rateImage($data) {
    global $pdo;
    
    $imageId = $data['image_id'] ?? 0;
    $rating = $data['rating'] ?? ''; // gostei, nao_gostei, problemas, outro
    $comment = trim($data['comment'] ?? '');
    
    if (!$imageId || !$rating) {
        jsonResponse(['error' => 'Missing required fields'], 400);
    }
    
    // Valida tipo de avaliação
    $validRatings = ['gostei', 'nao_gostei', 'problemas', 'outro'];
    if (!in_array($rating, $validRatings)) {
        jsonResponse(['error' => 'Invalid rating type'], 400);
    }
    
    // Se for "outro", comentário é obrigatório
    if ($rating === 'outro' && empty($comment)) {
        jsonResponse(['error' => 'Comment required for "outro" rating'], 400);
    }
    
    try {
        // Verifica se imagem existe
        $stmt = $pdo->prepare("SELECT id FROM images WHERE id = ?");
        $stmt->execute([$imageId]);
        if (!$stmt->fetch()) {
            jsonResponse(['error' => 'Image not found'], 404);
        }
        
        // Verifica se já avaliou
        $stmt = $pdo->prepare("SELECT id FROM ratings WHERE image_id = ? AND user_id = ?");
        $stmt->execute([$imageId, $_SESSION['user_id']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Atualiza avaliação existente
            $stmt = $pdo->prepare("
                UPDATE ratings 
                SET rating = ?, comment = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$rating, $comment, $existing['id']]);
            $message = 'Rating updated successfully';
        } else {
            // Cria nova avaliação
            $stmt = $pdo->prepare("
                INSERT INTO ratings (image_id, user_id, rating, comment) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$imageId, $_SESSION['user_id'], $rating, $comment]);
            $message = 'Rating added successfully';
        }
        
        jsonResponse([
            'success' => true,
            'message' => $message
        ]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}

/**
 * Busca minha avaliação de uma imagem
 */
function getMyRating() {
    global $pdo;
    
    $imageId = $_GET['image_id'] ?? 0;
    
    if (!$imageId) {
        jsonResponse(['error' => 'Image ID required'], 400);
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT rating, comment, created_at, updated_at 
            FROM ratings 
            WHERE image_id = ? AND user_id = ?
        ");
        $stmt->execute([$imageId, $_SESSION['user_id']]);
        $rating = $stmt->fetch();
        
        jsonResponse([
            'success' => true,
            'rating' => $rating
        ]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}

/**
 * Estatísticas de uma imagem (apenas admin)
 */
function getImageStats() {
    global $pdo;
    
    if (!isAdmin()) {
        jsonResponse(['error' => 'Admin only'], 403);
    }
    
    $imageId = $_GET['image_id'] ?? 0;
    
    if (!$imageId) {
        jsonResponse(['error' => 'Image ID required'], 400);
    }
    
    try {
        // Conta por tipo de avaliação
        $stmt = $pdo->prepare("
            SELECT 
                rating,
                COUNT(*) as count
            FROM ratings
            WHERE image_id = ?
            GROUP BY rating
        ");
        $stmt->execute([$imageId]);
        $stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Busca comentários
        $stmt = $pdo->prepare("
            SELECT r.*, u.username, u.full_name 
            FROM ratings r
            JOIN users u ON r.user_id = u.id
            WHERE r.image_id = ? AND r.comment != ''
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$imageId]);
        $comments = $stmt->fetchAll();
        
        jsonResponse([
            'success' => true,
            'stats' => [
                'gostei' => $stats['gostei'] ?? 0,
                'nao_gostei' => $stats['nao_gostei'] ?? 0,
                'problemas' => $stats['problemas'] ?? 0,
                'outro' => $stats['outro'] ?? 0,
                'total' => array_sum($stats)
            ],
            'comments' => $comments
        ]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}

/**
 * Lista todas as avaliações (admin + filtros)
 */
function listAllRatings() {
    global $pdo;
    
    if (!isAdmin()) {
        jsonResponse(['error' => 'Admin only'], 403);
    }
    
    $filter = $_GET['filter'] ?? 'all'; // all, gostei, nao_gostei, problemas, outro
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    
    try {
        $query = "
            SELECT r.*, i.sku, u.username, u.full_name
            FROM ratings r
            JOIN images i ON r.image_id = i.id
            JOIN users u ON r.user_id = u.id
        ";
        
        $params = [];
        
        if ($filter !== 'all') {
            $query .= " WHERE r.rating = ?";
            $params[] = $filter;
        }
        
        $query .= " ORDER BY r.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $ratings = $stmt->fetchAll();
        
        jsonResponse([
            'success' => true,
            'ratings' => $ratings,
            'has_more' => count($ratings) === $limit
        ]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}
?>