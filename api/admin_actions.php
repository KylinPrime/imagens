<?php
/**
 * API de Ações Administrativas
 * Kylin Prime - 2025
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';

// Apenas admins
if (!isLoggedIn() || !isAdmin()) {
    jsonResponse(['error' => 'Access denied'], 403);
}

// Pega dados
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'approve_user':
        approveUser($data);
        break;
        
    case 'reject_user':
        rejectUser($data);
        break;
        
    case 'create_user':
        createUser($data);
        break;
        
    case 'get_user':
        getUser();
        break;
        
    case 'update_user':
        updateUser($data);
        break;
        
    case 'toggle_user_status':
        toggleUserStatus($data);
        break;
        
    case 'add_category':
        addCategory($data);
        break;
        
    case 'delete_category':
        deleteCategory($data);
        break;
        
    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}

/**
 * Aprova usuário
 */
function approveUser($data) {
    global $pdo;
    
    $userId = $data['user_id'] ?? 0;
    
    if (!$userId) {
        jsonResponse(['error' => 'User ID required'], 400);
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET approved = TRUE, 
                approved_by = ?, 
                approved_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $userId]);
        
        jsonResponse([
            'success' => true,
            'message' => 'User approved successfully'
        ]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}

/**
 * Rejeita e deleta usuário
 */
function rejectUser($data) {
    global $pdo;
    
    $userId = $data['user_id'] ?? 0;
    
    if (!$userId) {
        jsonResponse(['error' => 'User ID required'], 400);
    }
    
    try {
        // Não permite deletar admin
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $role = $stmt->fetchColumn();
        
        if ($role === 'admin') {
            jsonResponse(['error' => 'Cannot delete admin users'], 403);
        }
        
        // Deleta usuário
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        jsonResponse([
            'success' => true,
            'message' => 'User rejected and deleted'
        ]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}

/**
 * Cria usuário manualmente
 */
function createUser($data) {
    global $pdo;
    
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    $fullName = trim($data['full_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $role = $data['role'] ?? 'user';
    
    // Validações
    if (!$username || !$password || !$fullName) {
        jsonResponse(['error' => 'Missing required fields'], 400);
    }
    
    if (strlen($username) < 3) {
        jsonResponse(['error' => 'Username too short'], 400);
    }
    
    if (strlen($password) < 6) {
        jsonResponse(['error' => 'Password too short'], 400);
    }
    
    if (!in_array($role, ['user', 'admin', 'cliente'])) {
        jsonResponse(['error' => 'Invalid role'], 400);
    }
    
    try {
        // Verifica se username existe
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            jsonResponse(['error' => 'Username already exists'], 409);
        }
        
        // Cria usuário
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, full_name, email, phone, role, language, approved, approved_by, approved_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'pt-br', TRUE, ?, NOW())
        ");
        
        $stmt->execute([
            $username, 
            $hashedPassword, 
            $fullName, 
            $email, 
            $phone, 
            $role,
            $_SESSION['user_id']
        ]);
        
        jsonResponse([
            'success' => true,
            'message' => 'User created successfully',
            'user_id' => $pdo->lastInsertId()
        ]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
    }
}

/**
 * Busca dados de um usuário (para edição)
 */
function getUser() {
    global $pdo;
    
    $userId = $_GET['user_id'] ?? 0;
    
    if (!$userId) {
        jsonResponse(['error' => 'User ID required'], 400);
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, username, full_name, email, phone, role, language, approved 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            jsonResponse(['error' => 'User not found'], 404);
        }
        
        jsonResponse([
            'success' => true,
            'user' => $user
        ]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}

/**
 * Atualiza dados de um usuário
 */
function updateUser($data) {
    global $pdo;
    
    $userId = $data['user_id'] ?? 0;
    $username = trim($data['username'] ?? '');
    $fullName = trim($data['full_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $role = $data['role'] ?? 'user';
    $password = $data['password'] ?? '';
    
    if (!$userId) {
        jsonResponse(['error' => 'User ID required'], 400);
    }
    
    // Validações
    if (!$username || !$fullName) {
        jsonResponse(['error' => 'Missing required fields'], 400);
    }
    
    if (strlen($username) < 3) {
        jsonResponse(['error' => 'Username too short'], 400);
    }
    
    if (!in_array($role, ['user', 'admin', 'cliente'])) {
        jsonResponse(['error' => 'Invalid role'], 400);
    }
    
    try {
        // Verifica se username já existe em outro usuário
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $userId]);
        if ($stmt->fetchColumn() > 0) {
            jsonResponse(['error' => 'Username already exists'], 409);
        }
        
        // Monta query de atualização
        if (!empty($password)) {
            // Atualiza com nova senha
            if (strlen($password) < 6) {
                jsonResponse(['error' => 'Password too short'], 400);
            }
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username = ?, full_name = ?, email = ?, phone = ?, role = ?, password = ?
                WHERE id = ?
            ");
            $stmt->execute([$username, $fullName, $email, $phone, $role, $hashedPassword, $userId]);
        } else {
            // Atualiza sem alterar senha
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username = ?, full_name = ?, email = ?, phone = ?, role = ?
                WHERE id = ?
            ");
            $stmt->execute([$username, $fullName, $email, $phone, $role, $userId]);
        }
        
        jsonResponse([
            'success' => true,
            'message' => 'User updated successfully'
        ]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
    }
}

/**
 * Ativa/Desativa usuário (mantém cadastro)
 */
function toggleUserStatus($data) {
    global $pdo;
    
    $userId = $data['user_id'] ?? 0;
    $approved = $data['approved'] ?? false;
    
    if (!$userId) {
        jsonResponse(['error' => 'User ID required'], 400);
    }
    
    try {
        // Não permite desativar admin
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $role = $stmt->fetchColumn();
        
        if ($role === 'admin' && !$approved) {
            jsonResponse(['error' => 'Cannot deactivate admin users'], 403);
        }
        
        // Atualiza status
        $stmt = $pdo->prepare("UPDATE users SET approved = ? WHERE id = ?");
        $stmt->execute([$approved ? 1 : 0, $userId]);
        
        jsonResponse([
            'success' => true,
            'message' => $approved ? 'User activated' : 'User deactivated'
        ]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}

/**
 * Adiciona categoria
 */
function addCategory($data) {
    global $pdo;
    
    $name = trim($data['name'] ?? '');
    $prefix = strtoupper(trim($data['prefix'] ?? ''));
    
    // Validações
    if (!$name || !$prefix) {
        jsonResponse(['error' => 'Name and prefix required'], 400);
    }
    
    if (strlen($prefix) < 2 || strlen($prefix) > 3) {
        jsonResponse(['error' => 'Prefix must be 2-3 characters'], 400);
    }
    
    if (!preg_match('/^[A-Z]+$/', $prefix)) {
        jsonResponse(['error' => 'Prefix must contain only letters'], 400);
    }
    
    try {
        // Verifica duplicatas
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ? OR prefix = ?");
        $stmt->execute([$name, $prefix]);
        if ($stmt->fetchColumn() > 0) {
            jsonResponse(['error' => 'Category name or prefix already exists'], 409);
        }
        
        // Adiciona categoria
        $stmt = $pdo->prepare("INSERT INTO categories (name, prefix) VALUES (?, ?)");
        $stmt->execute([$name, $prefix]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Category added successfully',
            'category_id' => $pdo->lastInsertId()
        ]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}

/**
 * Deleta categoria
 */
function deleteCategory($data) {
    global $pdo;
    
    $categoryId = $data['category_id'] ?? 0;
    
    if (!$categoryId) {
        jsonResponse(['error' => 'Category ID required'], 400);
    }
    
    try {
        // Verifica se há imagens usando esta categoria
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM images WHERE category = ?");
        $stmt->execute([$categoryId]);
        $imageCount = $stmt->fetchColumn();
        
        if ($imageCount > 0) {
            jsonResponse([
                'error' => "Cannot delete category with {$imageCount} images. Delete or reassign images first."
            ], 409);
        }
        
        // Deleta categoria
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$categoryId]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}
?>