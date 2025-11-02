<?php
require_once 'includes/auth.php';
require_once 'includes/i18n.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = login($username, $password);
    
    if ($result === true) {
        header('Location: index.php');
        exit;
    } elseif ($result === 'pending') {
        $error = __('login.pending_approval');
    } else {
        $error = __('login.error');
    }
}
?>
<!DOCTYPE html>
<html lang="<?= i18n::getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('login.title') ?> - Kylin Prime</title>
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

        .login-container {
            background: white;
            padding: 50px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 100%;
        }

        h1 {
            text-align: center;
            color: #FF512F;
            margin-bottom: 10px;
            font-size: 2em;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1em;
            transition: border 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #FF512F;
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
        
        .brand-logo {
            text-align: center;
            font-size: 2.5em;
            font-weight: bold;
            background: linear-gradient(135deg, #FF512F 0%, #F09819 50%, #FF6B35 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .brand-logo-img {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }
        
        .brand-tagline {
            text-align: center;
            color: #666;
            font-size: 0.9em;
            margin-bottom: 20px;
        }
        
        .language-selector {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .lang-option {
            font-size: 1.5em;
            text-decoration: none;
            opacity: 0.5;
            transition: all 0.3s;
        }
        
        .lang-option.active {
            opacity: 1;
            transform: scale(1.2);
        }
        
        .lang-option:hover {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="brand-logo">
            <?php if (file_exists('assets/logo.png')): ?>
                <img src="assets/logo.png" alt="Kylin Prime" class="brand-logo-img">
            <?php else: ?>
                🐉
            <?php endif; ?>
            <span>Kylin Prime</span>
        </div>
        <div class="brand-tagline"><?= __('brand.tagline') ?></div>
        <div class="brand-tagline" style="font-size: 0.8em;"><?= __('brand.founded') ?></div>
        
        <?= i18n::languageSelector() ?>
        
        <h1><?= __('login.title') ?></h1>
        <p class="subtitle"><?= __('gallery.title') ?></p>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username"><?= __('login.username') ?></label>
                <input type="text" id="username" name="username" required autofocus>
            </div>

            <div class="form-group">
                <label for="password"><?= __('login.password') ?></label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn"><?= __('login.submit') ?></button>
        </form>

        <div class="back-link">
            <a href="register.php"><?= __('login.register_link') ?></a>
        </div>
        
        <div class="back-link">
            <a href="index.php">← <?= __('menu.gallery') ?></a>
        </div>
    </div>
</body>
</html>