<?php
/**
 * Script para alterar senha de usuários
 * 
 * ⚠️ IMPORTANTE: Delete este arquivo após usar!
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validações
    if (empty($username) || empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Todos os campos são obrigatórios';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Nova senha deve ter pelo menos 6 caracteres';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'As senhas não coincidem';
    } else {
        // Verifica usuário e senha atual
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error = 'Usuário não encontrado';
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $error = 'Senha atual incorreta';
        } else {
            // Atualiza senha
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $user['id']]);
            
            $message = 'Senha alterada com sucesso!';
            
            // Se for o próprio usuário logado, faz logout para relogar
            if (isLoggedIn() && $_SESSION['username'] === $username) {
                $message .= ' Por segurança, faça login novamente.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Senha</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 450px;
            width: 100%;
        }
        
        h1 {
            color: #FF512F;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 0.9em;
            border-left: 4px solid #ffc107;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: border 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #FF512F;
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #FF512F 0%, #F09819 50%, #FF6B35 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 81, 47, 0.4);
        }
        
        .links {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .links a {
            color: #FF512F;
            text-decoration: none;
            margin: 0 10px;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        .password-requirements {
            font-size: 0.85em;
            color: #666;
            margin-top: 8px;
        }
        
        .password-requirements ul {
            margin-left: 20px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Alterar Senha</h1>
        <p class="subtitle">Sistema de Gestão de Imagens</p>
        
        <div class="warning">
            <strong>⚠️ ATENÇÃO:</strong> Por segurança, delete este arquivo após alterar as senhas necessárias!
        </div>
        
        <?php if ($message): ?>
            <div class="success">✅ <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Nome de Usuário</label>
                <input type="text" id="username" name="username" required 
                       placeholder="Digite seu nome de usuário"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="current_password">Senha Atual</label>
                <input type="password" id="current_password" name="current_password" required 
                       placeholder="Digite sua senha atual">
            </div>
            
            <div class="form-group">
                <label for="new_password">Nova Senha</label>
                <input type="password" id="new_password" name="new_password" required 
                       placeholder="Digite a nova senha" minlength="6">
                <div class="password-requirements">
                    <strong>Requisitos da senha:</strong>
                    <ul>
                        <li>Mínimo de 6 caracteres</li>
                        <li>Recomendado: letras, números e símbolos</li>
                    </ul>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmar Nova Senha</label>
                <input type="password" id="confirm_password" name="confirm_password" required 
                       placeholder="Digite a nova senha novamente" minlength="6">
            </div>
            
            <button type="submit">Alterar Senha</button>
        </form>
        
        <div class="links">
            <a href="index.php">← Voltar para Galeria</a>
            <?php if (isLoggedIn()): ?>
                <a href="logout.php">Sair</a>
            <?php else: ?>
                <a href="login.php">Fazer Login</a>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Validação no cliente
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('As senhas não coincidem!');
                return false;
            }
            
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('A senha deve ter pelo menos 6 caracteres!');
                return false;
            }
        });
    </script>
</body>
</html>