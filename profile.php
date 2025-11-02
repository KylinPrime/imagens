<?php
require_once 'includes/auth.php';
require_once 'includes/i18n.php';

requireLogin();

$success = '';
$error = '';

// Carrega dados do usuário
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Atualizar perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $language = $_POST['language'] ?? 'pt-br';
        
        try {
            // Upload de foto
            $photo = $user['photo'];
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                $fileType = $_FILES['photo']['type'];
                
                if (in_array($fileType, $allowed)) {
                    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                    $photoName = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
                    $photoPath = 'uploads/profiles/' . $photoName;
                    
                    // Cria diretório se não existir
                    if (!is_dir('uploads/profiles')) {
                        mkdir('uploads/profiles', 0755, true);
                    }
                    
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath)) {
                        // Remove foto antiga
                        if ($user['photo'] && file_exists($user['photo'])) {
                            unlink($user['photo']);
                        }
                        $photo = $photoPath;
                    }
                }
            }
            
            $stmt = $pdo->prepare("
                UPDATE users 
                SET full_name = ?, email = ?, phone = ?, bio = ?, language = ?, photo = ?
                WHERE id = ?
            ");
            $stmt->execute([$fullName, $email, $phone, $bio, $language, $photo, $_SESSION['user_id']]);
            
            // Atualiza idioma na sessão
            i18n::setLanguage($language);
            
            $success = __('profile.save_success');
            
            // Recarrega dados
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
        } catch (PDOException $e) {
            $error = __('msg.error');
        }
    }
    
    // Alterar senha
    elseif ($_POST['action'] === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (!password_verify($currentPassword, $user['password'])) {
            $error = __('profile.error.wrong_password', 'Senha atual incorreta');
        } elseif (strlen($newPassword) < 6) {
            $error = __('profile.error.password_short', 'Nova senha deve ter pelo menos 6 caracteres');
        } elseif ($newPassword !== $confirmPassword) {
            $error = __('profile.error.password_mismatch', 'As senhas não coincidem');
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
            
            $success = __('profile.password_changed', 'Senha alterada com sucesso!');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= i18n::getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('profile.title') ?> - Kylin Prime</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* ESTILOS MELHORADOS PARA INPUTS */
        .profile-header {
            background: var(--white);
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            text-align: center;
        }
        .profile-photo-container {
            margin-bottom: 20px;
        }
        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--primary);
            margin: 0 auto;
            display: block;
        }
        .profile-photo-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: var(--gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4em;
            margin: 0 auto;
        }
        .profile-name {
            font-size: 1.8em;
            color: var(--dark);
            margin-bottom: 5px;
        }
        .profile-username {
            color: #666;
            margin-bottom: 10px;
        }
        .profile-role {
            display: inline-block;
            padding: 5px 15px;
            background: var(--gradient);
            color: white;
            border-radius: 20px;
            font-size: 0.9em;
        }
        .profile-sections {
            display: grid;
            gap: 30px;
        }
        .profile-section {
            background: var(--white);
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }
        .profile-section h2 {
            color: var(--primary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        /* INPUTS REDESENHADOS - ESTILO MODERNO */
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.95em;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1em;
            transition: all 0.3s ease;
            font-family: inherit;
            background: #fafafa;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(255, 81, 47, 0.1);
            transform: translateY(-1px);
        }
        
        .form-group input:hover,
        .form-group textarea:hover,
        .form-group select:hover {
            border-color: #ccc;
            background: white;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
            line-height: 1.6;
        }
        
        /* Estilo especial para selects */
        .form-group select {
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23FF512F' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 12px;
            padding-right: 40px;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }
        
        /* File upload melhorado */
        .file-upload-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        
        .file-upload-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }
        
        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 20px;
            background: var(--gradient);
            color: white;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .file-upload-label:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 81, 47, 0.3);
        }
        
        /* Botões melhorados */
        .btn {
            padding: 14px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1em;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--gradient);
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 81, 47, 0.3);
        }
        
        /* Mensagens */
        .success, .error {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>🐉 Kylin Prime</h1>
            <div class="header-actions">
                <?= i18n::languageSelector() ?>
                <span class="user-info">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="index.php" class="btn btn-secondary">← <?= __('menu.gallery') ?></a>
                <?php if (isAdmin()): ?>
                    <a href="admin.php" class="btn btn-primary">⚙️ <?= __('menu.admin') ?></a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-secondary"><?= __('menu.logout') ?></a>
            </div>
        </div>
    </header>

    <main style="padding: 40px 0;">
        <div class="container">
            
            <?php if ($success): ?>
                <div class="success">✅ <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Cabeçalho do Perfil -->
            <div class="profile-header">
                <div class="profile-photo-container">
                    <?php if ($user['photo'] && file_exists($user['photo'])): ?>
                        <img src="<?= htmlspecialchars($user['photo']) ?>" alt="<?= htmlspecialchars($user['full_name']) ?>" class="profile-photo">
                    <?php else: ?>
                        <div class="profile-photo-placeholder">👤</div>
                    <?php endif; ?>
                </div>
                <div class="profile-name"><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></div>
                <div class="profile-username">@<?= htmlspecialchars($user['username']) ?></div>
                <div class="profile-role">
                    <?= $user['role'] === 'admin' ? '⚙️ ' . __('menu.admin') : ($user['role'] === 'cliente' ? '🛒 Cliente' : '👤 ' . __('action.user', 'Usuário')) ?>
                </div>
                <?php if ($user['bio']): ?>
                    <p style="margin-top: 15px; color: #666;"><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
                <?php endif; ?>
            </div>

            <div class="profile-sections">
                <!-- Editar Perfil -->
                <div class="profile-section">
                    <h2><?= __('profile.edit') ?></h2>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label for="photo"><?= __('profile.photo') ?></label>
                            <div class="file-upload-wrapper">
                                <label for="photo" class="file-upload-label">
                                    📁 <?= __('action.choose_file', 'Escolher arquivo') ?>
                                </label>
                                <input type="file" id="photo" name="photo" accept="image/*">
                            </div>
                            <small style="display: block; margin-top: 8px; color: #666;">
                                JPG, PNG ou WEBP - Máximo 2MB
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="full_name"><?= __('register.full_name') ?></label>
                            <input type="text" id="full_name" name="full_name" 
                                   value="<?= htmlspecialchars($user['full_name']) ?>"
                                   placeholder="Digite seu nome completo">
                        </div>

                        <div class="form-group">
                            <label for="email"><?= __('register.email') ?></label>
                            <input type="email" id="email" name="email" 
                                   value="<?= htmlspecialchars($user['email']) ?>"
                                   placeholder="seu@email.com">
                        </div>

                        <div class="form-group">
                            <label for="phone"><?= __('register.phone') ?></label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?= htmlspecialchars($user['phone']) ?>"
                                   placeholder="(00) 00000-0000">
                        </div>

                        <div class="form-group">
                            <label for="bio"><?= __('profile.bio') ?></label>
                            <textarea id="bio" name="bio" rows="4" placeholder="Conte um pouco sobre você..."><?= htmlspecialchars($user['bio']) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="language"><?= __('register.language') ?></label>
                            <select id="language" name="language">
                                <?php foreach (i18n::getLanguages() as $code => $info): ?>
                                    <option value="<?= $code ?>" <?= $user['language'] === $code ? 'selected' : '' ?>>
                                        <?= $info['flag'] ?> <?= $info['name'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">💾 <?= __('action.save') ?></button>
                    </form>
                </div>

                <!-- Alterar Senha -->
                <div class="profile-section">
                    <h2><?= __('profile.change_password') ?></h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password"><?= __('profile.current_password') ?></label>
                            <input type="password" id="current_password" name="current_password" required placeholder="Digite sua senha atual">
                        </div>

                        <div class="form-group">
                            <label for="new_password"><?= __('profile.new_password') ?></label>
                            <input type="password" id="new_password" name="new_password" required minlength="6" placeholder="Mínimo 6 caracteres">
                        </div>

                        <div class="form-group">
                            <label for="confirm_password"><?= __('register.password_confirm') ?></label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Digite a senha novamente">
                        </div>

                        <button type="submit" class="btn btn-primary">🔒 <?= __('profile.change_password') ?></button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>