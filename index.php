<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/i18n.php';

// Busca categorias que têm imagens
$stmt = $pdo->query("
    SELECT c.*, COUNT(i.id) as image_count 
    FROM categories c
    LEFT JOIN images i ON c.id = i.category
    GROUP BY c.id, c.name, c.prefix
    HAVING image_count > 0
    ORDER BY c.name
");
$categories = $stmt->fetchAll();

// Conta itens do carrinho se for cliente
$cartCount = 0;
if (isLoggedIn() && isCliente()) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cartCount = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="<?= i18n::getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('gallery.title') ?> - Kylin Prime</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* LOGO DO TOPO AJUSTADA */
        header h1 {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        header h1 img {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }
        
        header h1 .logo-emoji {
            font-size: 1.5em;
            line-height: 1;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>
                <?php if (file_exists('assets/logo.png')): ?>
                    <img src="assets/logo.png" alt="Kylin Prime">
                <?php else: ?>
                    <span class="logo-emoji">🐉</span>
                <?php endif; ?>
                Kylin Prime
            </h1>
            <div class="header-actions">
                <?= i18n::languageSelector() ?>
                <?php if (isLoggedIn()): ?>
                    <a href="profile.php" class="user-info" title="<?= __('profile.title') ?>">
                        👤 <?= htmlspecialchars($_SESSION['username']) ?>
                    </a>
                    <?php if (isCliente() && $cartCount > 0): ?>
                        <a href="cart.php" class="btn btn-cart" title="<?= __('cart.title') ?>">
                            🛒 <span class="cart-badge"><?= $cartCount ?></span>
                        </a>
                    <?php endif; ?>
                    <?php if (isAdmin()): ?>
                        <a href="admin.php" class="btn btn-primary">⚙️ <?= __('menu.admin') ?></a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-secondary"><?= __('menu.logout') ?></a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">🔓 <?= __('menu.login') ?></a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main>
        <!-- Filtros -->
        <section class="filters-section">
            <div class="container">
                <div class="filters">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="🔍 <?= __('gallery.search') ?>">
                    </div>
                    <div class="category-filters">
                        <button class="filter-btn active" data-category=""><?= __('gallery.filter.all') ?></button>
                        <?php foreach ($categories as $cat): ?>
                            <button class="filter-btn" data-category="<?= $cat['id'] ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Lista de Imagens (AVISO DE MARCA D'ÁGUA REMOVIDO) -->
        <section class="gallery-section">
            <div class="container">
                <div class="gallery-grid" id="galleryGrid">
                    <div class="loading"><?= __('gallery.loading') ?></div>
                </div>
                <div class="load-more-container" id="loadMoreContainer" style="display: none;">
                    <button class="btn btn-primary" id="loadMoreBtn"><?= __('gallery.load_more') ?></button>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
    
    <?php include 'includes/cookie_consent.php'; ?>

    <script src="js/app.js"></script>
</body>
</html>