<footer class="site-footer">
    <div class="container">
        <div class="footer-content">
            <!-- Logo e Descrição -->
            <div class="footer-section">
                <div class="footer-logo">
                    <?php if (file_exists(__DIR__ . '/../assets/logo.png')): ?>
                        <img src="assets/logo.png" alt="Kylin Prime" class="footer-logo-img">
                    <?php else: ?>
                        <span class="footer-logo-emoji">🐉</span>
                    <?php endif; ?>
                    <span class="footer-logo-text">Kylin Prime</span>
                </div>
                <p class="footer-tagline"><?= __('brand.tagline') ?></p>
                <p class="footer-description">
                    Especializada em estampas exclusivas para camisetas. 
                    Designs únicos que contam histórias e expressam personalidade.
                </p>
                <p class="footer-trademark">
                    <strong>Kylin Prime®</strong> - Marca Registrada<br>
                    <?= __('brand.founded') ?>
                </p>
            </div>

            <!-- Links Rápidos -->
            <div class="footer-section">
                <h3 class="footer-title">Links Rápidos</h3>
                <ul class="footer-links">
                    <li><a href="index.php">🏠 <?= __('menu.gallery') ?></a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="profile.php">👤 <?= __('profile.title') ?></a></li>
                        <?php if (isCliente()): ?>
                            <li><a href="cart.php">🛒 <?= __('cart.title') ?></a></li>
                        <?php endif; ?>
                        <?php if (isAdmin()): ?>
                            <li><a href="admin.php">⚙️ <?= __('menu.admin') ?></a></li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li><a href="login.php">🔓 <?= __('menu.login') ?></a></li>
                        <li><a href="register.php">📝 <?= __('menu.register') ?></a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Categorias -->
            <div class="footer-section">
                <h3 class="footer-title">Categorias</h3>
                <ul class="footer-links footer-categories">
                    <?php
                    $footerCategories = $pdo->query("
                        SELECT c.name, COUNT(i.id) as img_count 
                        FROM categories c
                        LEFT JOIN images i ON c.id = i.category
                        GROUP BY c.id, c.name
                        HAVING img_count > 0
                        ORDER BY c.name 
                        LIMIT 6
                    ")->fetchAll();
                    
                    if (count($footerCategories) > 0):
                        foreach ($footerCategories as $cat):
                    ?>
                        <li><?= htmlspecialchars($cat['name']) ?> (<?= $cat['img_count'] ?>)</li>
                    <?php 
                        endforeach;
                    else:
                    ?>
                        <li style="color: #999;">Nenhuma categoria com imagens</li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Contato e Redes Sociais -->
            <div class="footer-section">
                <h3 class="footer-title">Contato</h3>
                <ul class="footer-contact">
                    <li>
                        <strong>WhatsApp:</strong><br>
                        <?php
                        $whatsappStmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'whatsapp_number'");
                        $whatsappStmt->execute();
                        $whatsappNum = $whatsappStmt->fetchColumn() ?: '5511999999999';
                        $formattedPhone = '+' . substr($whatsappNum, 0, 2) . ' (' . substr($whatsappNum, 2, 2) . ') ' . substr($whatsappNum, 4, 5) . '-' . substr($whatsappNum, 9);
                        ?>
                        <a href="https://wa.me/<?= $whatsappNum ?>" target="_blank" class="whatsapp-link">
                            <?= $formattedPhone ?>
                        </a>
                    </li>
                    <li>
                        <strong>Email:</strong><br>
                        <a href="mailto:contato@kylinprime.com">contato@kylinprime.com</a>
                    </li>
                    <li style="margin-top: 15px;">
                        <strong>CNPJ:</strong><br>
                        00.000.000/0001-00
                    </li>
                </ul>

                <div class="footer-social">
                    <h4 style="margin: 20px 0 10px 0; font-size: 0.95em;">Redes Sociais</h4>
                    <div class="social-buttons">
                        <a href="https://instagram.com/kylinprime" target="_blank" class="social-btn instagram" title="Instagram">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                            </svg>
                        </a>
                        <a href="https://facebook.com/kylinprime" target="_blank" class="social-btn facebook" title="Facebook">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        <a href="https://twitter.com/kylinprime" target="_blank" class="social-btn twitter" title="Twitter/X">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                        </a>
                        <a href="https://wa.me/<?= $whatsappNum ?>" target="_blank" class="social-btn whatsapp" title="WhatsApp">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Copyright -->
        <div class="footer-bottom">
            <p><?= __('brand.copyright') ?></p>
            <p class="footer-slogan">
                "Cada estampa é uma obra de arte. Cada camiseta, uma tela em branco."
            </p>
        </div>
    </div>
</footer>

<style>
.site-footer {
    background: linear-gradient(135deg, #2c2c2c 0%, #1a1a1a 100%);
    color: #fff;
    padding: 60px 0 20px;
    margin-top: 80px;
}

.footer-content {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1.5fr;
    gap: 40px;
    margin-bottom: 40px;
}

.footer-section h3 {
    color: var(--primary);
    margin-bottom: 20px;
    font-size: 1.1em;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.footer-logo {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

/* LOGO AUMENTADO PARA 200x200px */
.footer-logo-img {
    width: 200px;
    height: 200px;
    object-fit: contain;
}

.footer-logo-emoji {
    font-size: 8em; /* Aumentado proporcionalmente */
}

.footer-logo-text {
    font-size: 1.8em;
    font-weight: bold;
    background: var(--gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.footer-tagline {
    font-style: italic;
    color: #FF6B35;
    margin-bottom: 15px;
    font-size: 1.1em;
}

.footer-description {
    color: #ccc;
    line-height: 1.8;
    margin-bottom: 20px;
}

.footer-trademark {
    color: #999;
    font-size: 0.9em;
    line-height: 1.6;
}

.footer-links {
    list-style: none;
    padding: 0;
}

.footer-links li {
    margin-bottom: 12px;
}

.footer-links a {
    color: #ccc;
    text-decoration: none;
    transition: all 0.3s;
    display: inline-block;
}

.footer-links a:hover {
    color: var(--primary);
    transform: translateX(5px);
}

.footer-categories li {
    color: #ccc;
    margin-bottom: 8px;
    padding-left: 15px;
    position: relative;
}

.footer-categories li:before {
    content: "▸";
    position: absolute;
    left: 0;
    color: var(--primary);
}

.footer-contact {
    list-style: none;
    padding: 0;
    color: #ccc;
}

.footer-contact li {
    margin-bottom: 15px;
    line-height: 1.6;
}

.footer-contact a {
    color: #FF6B35;
    text-decoration: none;
    transition: color 0.3s;
}

.footer-contact a:hover {
    color: var(--primary);
}

.whatsapp-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.social-buttons {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.social-btn {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    transition: all 0.3s;
    text-decoration: none;
}

.social-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.social-btn.instagram {
    background: linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%);
}

.social-btn.facebook {
    background: #1877f2;
}

.social-btn.twitter {
    background: #000000;
}

.social-btn.whatsapp {
    background: #25D366;
}

.footer-bottom {
    border-top: 1px solid #444;
    padding-top: 30px;
    text-align: center;
    color: #999;
}

.footer-slogan {
    font-style: italic;
    margin-top: 10px;
    color: #666;
}

@media (max-width: 992px) {
    .footer-content {
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }
}

@media (max-width: 768px) {
    .footer-content {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .site-footer {
        padding: 40px 0 20px;
    }
    
    .footer-logo-img {
        width: 150px;
        height: 150px;
    }
    
    .footer-logo-emoji {
        font-size: 6em;
    }
}
</style>