<?php
/**
 * Script para criar usuários via linha de comando ou navegador
 * 
 * USO VIA LINHA DE COMANDO:
 * php create_user.php username password role
 * 
 * Exemplo:
 * php create_user.php joao senha123 user
 * php create_user.php maria senha456 admin
 * 
 * USO VIA NAVEGADOR:
 * Acesse: http://seusite.com/imagens/create_user.php
 * (Desative após criar os usuários necessários!)
 */

require_once 'includes/config.php';

// Detecta se é linha de comando ou navegador
$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    // Interface web simples
    echo '<!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Criar Usuário</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: Arial, sans-serif;
                background: linear-gradient(135deg, #FF512F 0%, #F09819 50%, #FF6B35 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                padding: 40px;
                border-radius: 15px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                max-width: 400px;
                width: 100%;
            }
            h1 { color: #FF512F; margin-bottom: 10px; }
            .warning {
                background: #fff3cd;
                color: #856404;
                padding: 10px;
                border-radius: 5px;
                margin-bottom: 20px;
                font-size: 0.9em;
            }
            .form-group { margin-bottom: 20px; }
            label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
                color: #333;
            }
            input, select {
                width: 100%;
                padding: 10px;
                border: 2px solid #ddd;
                border-radius: 5px;
                font-size: 1em;
            }
            input:focus, select:focus {
                outline: none;
                border-color: #FF512F;
            }
            button {
                width: 100%;
                padding: 12px;
                background: linear-gradient(135deg, #FF512F 0%, #F09819 50%, #FF6B35 100%);
                color: white;
                border: none;
                border-radius: 5px;
                font-size: 1.1em;
                font-weight: bold;
                cursor: pointer;
            }
            button:hover {
                opacity: 0.9;
            }
            .success {
                background: #d4edda;
                color: #155724;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
            }
            .error {
                background: #f8d7da;
                color: #721c24;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>👤 Criar Usuário</h1>
            <p style="color: #666; margin-bottom: 20px;">Sistema de Gestão de Imagens</p>
            
            <div class="warning">
                ⚠️ <strong>IMPORTANTE:</strong> Delete ou renomeie este arquivo após criar os usuários necessários!
            </div>';
}

// Processa criação de usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($isCLI && $argc >= 4)) {
    
    if ($isCLI) {
        // Via linha de comando
        $username = $argv[1];
        $password = $argv[2];
        $role = $argv[3];
    } else {
        // Via POST
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';
    }
    
    // Validações
    $errors = [];
    
    if (strlen($username) < 3) {
        $errors[] = 'Usuário deve ter pelo menos 3 caracteres';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'Senha deve ter pelo menos 6 caracteres';
    }
    
    if (!in_array($role, ['user', 'admin'])) {
        $errors[] = 'Tipo deve ser "user" ou "admin"';
    }
    
    // Verifica se usuário já existe
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Usuário já existe';
        }
    }
    
    if (empty($errors)) {
        // Cria usuário
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, role) 
                VALUES (?, ?, ?)
            ");
            
            $stmt->execute([$username, $hashedPassword, $role]);
            
            $message = "✅ Usuário '$username' criado com sucesso! (Tipo: $role)";
            $success = true;
            
            if ($isCLI) {
                echo "\n$message\n\n";
                exit(0);
            }
            
        } catch (PDOException $e) {
            $errors[] = 'Erro ao criar usuário: ' . $e->getMessage();
        }
    }
    
    if (!$isCLI && !empty($errors)) {
        echo '<div class="error">';
        foreach ($errors as $error) {
            echo '❌ ' . htmlspecialchars($error) . '<br>';
        }
        echo '</div>';
    } elseif (!$isCLI && $success) {
        echo '<div class="success">' . htmlspecialchars($message) . '</div>';
    }
    
    if ($isCLI && !empty($errors)) {
        echo "\n❌ ERROS:\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
        echo "\n";
        exit(1);
    }
}

if (!$isCLI) {
    // Exibe formulário
    echo '
            <form method="POST">
                <div class="form-group">
                    <label for="username">Nome de Usuário</label>
                    <input type="text" id="username" name="username" required 
                           placeholder="Mínimo 3 caracteres" minlength="3">
                </div>
                
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Mínimo 6 caracteres" minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="role">Tipo de Usuário</label>
                    <select id="role" name="role">
                        <option value="user">👤 Usuário (Download apenas)</option>
                        <option value="admin">⚙️ Administrador (Upload + Delete)</option>
                    </select>
                </div>
                
                <button type="submit">Criar Usuário</button>
            </form>
            
            <p style="text-align: center; margin-top: 20px;">
                <a href="index.php" style="color: #FF512F; text-decoration: none;">← Voltar para Galeria</a>
            </p>
        </div>
    </body>
    </html>';
} elseif ($isCLI && $argc < 4) {
    // Ajuda para CLI
    echo "\n📚 USO:\n";
    echo "  php create_user.php <usuario> <senha> <tipo>\n\n";
    echo "💡 EXEMPLOS:\n";
    echo "  php create_user.php joao senha123 user\n";
    echo "  php create_user.php maria senha456 admin\n\n";
    echo "📋 TIPOS:\n";
    echo "  user  - Usuário comum (visualização e download)\n";
    echo "  admin - Administrador (upload, delete, acesso total)\n\n";
    exit(1);
}
?>