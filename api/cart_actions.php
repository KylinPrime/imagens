<?php
/**
 * API de Ações do Carrinho
 * Kylin Prime - 2025
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';

// Apenas clientes podem usar carrinho
if (!isLoggedIn() || !isCliente()) {
    jsonResponse(['error' => 'Access denied'], 403);
}

// Pega dados
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

switch ($action) {
    case 'add':
        addToCart($data);
        break;
        
    case 'remove':
        removeFromCart($data);
        break;
        
    case 'update':
        updateQuantity($data);
        break;
        
    case 'clear':
        clearCart();
        break;
        
    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}

/**
 * Adiciona item ao carrinho
 */
function addToCart($data) {
    global $pdo;
    
    $imageId = $data['image_id'] ?? 0;
    $quantity = $data['quantity'] ?? 1;
    
    if (!$imageId) {
        jsonResponse(['error' => 'Image ID required'], 400);
    }
    
    try {
        // Verifica se imagem existe
        $stmt = $pdo->prepare("SELECT id FROM images WHERE id = ?");
        $stmt->execute([$imageId]);
        if (!$stmt->fetch()) {
            jsonResponse(['error' => 'Image not found'], 404);
        }
        
        // Verifica se já existe no carrinho
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND image_id = ?");
        $stmt->execute([$_SESSION['user_id'], $imageId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Atualiza quantidade
            $newQuantity = $existing['quantity'] + $quantity;
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $stmt->execute([$newQuantity, $existing['id']]);
        } else {
            // Adiciona novo item
            $stmt = $pdo->prepare("
                INSERT INTO cart (user_id, image_id, quantity) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$_SESSION['user_id'], $imageId, $quantity]);
        }
        
        // Conta total de itens no carrinho
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $cartCount = $stmt->fetchColumn();
        
        jsonResponse([
            'success' => true,
            'message' => 'Item added to cart',
            'cart_count' => $cartCount
        ]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}

/**
 * Remove item do carrinho
 */
function removeFromCart($data) {
    global $pdo;
    
    $cartId = $data['cart_id'] ?? 0;
    
    if (!$cartId) {
        jsonResponse(['error' => 'Cart ID required'], 400);
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cartId, $_SESSION['user_id']]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Item removed from cart'
        ]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}

/**
 * Atualiza quantidade
 */
function updateQuantity($data) {
    global $pdo;
    
    $cartId = $data['cart_id'] ?? 0;
    $quantity = $data['quantity'] ?? 1;
    
    if (!$cartId || $quantity < 1) {
        jsonResponse(['error' => 'Invalid data'], 400);
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$quantity, $cartId, $_SESSION['user_id']]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Quantity updated'
        ]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}

/**
 * Limpa carrinho
 */
function clearCart() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Cart cleared'
        ]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}
?>