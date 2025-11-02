<?php
require_once 'includes/config.php';
require_once 'includes/i18n.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $language = $_POST['language'] ?? 'pt-br';
    
    // Validações
    if (empty($username) || empty($password) || empty($fullName)) {
        $error = __('register.error.required', 'Todos os campos obrigatórios devem ser preenchidos');
    } elseif (strlen($username) < 3) {
        $error = __('register.error.username_short', 'Usuário deve ter pelo menos 3 caracteres');
    } elseif (strlen($password) < 6) {
        $error = __('register.error.password_short', 'Senha deve ter pelo menos 6 caracteres');
    } elseif ($password !== $confirmPassword) {
        $error = __('register.error.password_mismatch', 'As senhas não coincidem');
    } else {
        // Verifica se usuário existe
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->fetchColumn() > 0) {
            $error = __('register.error.username_exists', 'Este usuário já existe');
        } else {
            // Cria usuário - SEMPRE COMO CLIENTE (registro público)
            try {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Verifica se requer aprovação
                $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'require_approval'");
                $stmt->execute();
                $requireApproval = $stmt->fetchColumn();
                
                $approved = ($requireApproval == '0') ? 1 : 0;
                
                // FORÇADO: role = 'cliente' SEMPRE no registro público
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, password, full_name, email, phone, language, role, approved) 
                    VALUES (?, ?, ?, ?, ?, ?, 'cliente', ?)
                ");
                
                $stmt->execute([$username, $hashedPassword, $fullName, $email, $phone, $language, $approved]);
                
                if ($approved) {
                    // Auto-login se aprovado
                    session_start();
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = 'cliente';
                    $_SESSION['language'] = $language;
                    
                    header('Location: index.php');
                    exit;
                } else {
                    // Aguarda aprovação
                    $success = __('register.pending_approval');
                }
                
            } catch (PDOException $e) {
                $error = __('msg.error');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= i18n::getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('register.title') ?> - Kylin Prime</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #FF512F 0%, #F09819 50%, #FF6B35 100%);
            padding: 20px;
        }

        .register-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
        }

        .brand-logo {
            text-align: center;
            margin-bottom: 10px;
        }

        .brand-logo img {
            width: 120px;
            height: 120px;
            object-fit: contain;
            margin-bottom: 10px;
        }

        .brand-logo-text {
            font-size: 2em;
            font-weight: bold;
            background: linear-gradient(135deg, #FF512F 0%, #F09819 50%, #FF6B35 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: block;
            margin-bottom: 5px;
        }

        .brand-tagline {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 30px;
            text-align: center;
        }

        h1 {
            text-align: center;
            color: #FF512F;
            margin-bottom: 10px;
            font-size: 1.8em;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }

        /* INPUTS PADRONIZADOS - IGUAL AO LOGIN */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        input, select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1em;
            transition: all 0.3s;
            font-family: inherit;
            background: #fafafa;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #FF512F;
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 81, 47, 0.1);
        }

        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23FF512F' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 12px;
            padding-right: 40px;
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #FF512F 0%, #F09819 50%, #FF6B35 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 81, 47, 0.4);
        }

        .error {
            background: #ffe0e0;
            color: #c00;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #FF512F;
            text-decoration: none;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 0.85em;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="brand-logo">
            <?php if (file_exists('assets/logo.png')): ?>
                <img src="assets/logo.png" alt="Kylin Prime">
            <?php else: ?>
                <span style="font-size: 4em;">🐉</span>
            <?php endif; ?>
            <span class="brand-logo-text">Kylin Prime</span>
        </div>
        <div class="brand-tagline"><?= __('brand.tagline') ?></div>

        <?= i18n::languageSelector() ?>

        <h1><?= __('register.title') ?></h1>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="full_name"><?= __('register.full_name') ?> *</label>
                <input type="text" id="full_name" name="full_name" required 
                       placeholder="Digite seu nome completo"
                       value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="username"><?= __('login.username') ?> *</label>
                <input type="text" id="username" name="username" required 
                       minlength="3"
                       placeholder="Mínimo 3 caracteres"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="email"><?= __('register.email') ?></label>
                <input type="email" id="email" name="email" 
                       placeholder="seu@email.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="phone"><?= __('register.phone') ?></label>
                <input type="tel" id="phone" name="phone" 
                       placeholder="(00) 00000-0000"
                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="language"><?= __('register.language') ?> *</label>
                <select id="language" name="language" required>
                    <?php foreach (i18n::getLanguages() as $code => $info): ?>
                        <option value="<?= $code ?>" 
                            <?= (($_POST['language'] ?? i18n::getLanguage()) === $code) ? 'selected' : '' ?>>
                            <?= $info['flag'] ?> <?= $info['name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="password"><?= __('login.password') ?> *</label>
                <input type="password" id="password" name="password" required minlength="6" placeholder="Mínimo 6 caracteres">
            </div>

            <div class="form-group">
                <label for="confirm_password"><?= __('register.password_confirm') ?> *</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Digite a senha novamente">
            </div>

            <button type="submit" class="btn"><?= __('menu.register') ?></button>
        </form>

        <div class="back-link">
            <a href="login.php"><?= __('register.login_link') ?></a>
        </div>
    </div>
</body>
</html>