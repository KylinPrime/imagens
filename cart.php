<?php
require_once 'includes/auth.php';
require_once 'includes/i18n.php';

// Apenas clientes podem acessar
if (!isLoggedIn() || !isCliente()) {
    header('Location: index.php');
    exit;
}

// Busca itens do carrinho
$stmt = $pdo->prepare("
    SELECT c.*, i.sku, i.file_extension, cat.name as category_name, i.id as image_id
    FROM cart c
    JOIN images i ON c.image_id = i.id
    JOIN categories cat ON i.category = cat.id
    WHERE c.user_id = ?
    ORDER BY c.added_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

// Busca configurações
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('whatsapp_number', 'whatsapp_message')");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$whatsappNumber = $settings['whatsapp_number'] ?? '5511999999999';
$whatsappMessage = $settings['whatsapp_message'] ?? 'Olá! Gostaria de fazer um pedido:';

// Calcula total
$totalItems = array_sum(array_column($cartItems, 'quantity'));
?>
<!DOCTYPE html>
<html lang="<?= i18n::getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('cart.title') ?> - Kylin Prime</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .cart-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .cart-item {
            background: var(--white);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .cart-item-image {
            width: 120px;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
        }
        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .cart-item-info {
            flex: 1;
        }
        .cart-item-sku {
            font-size: 1.2em;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }
        .cart-item-category {
            color: #666;
            margin-bottom: 15px;
        }
        .cart-item-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--light);
            padding: 5px 15px;
            border-radius: 25px;
        }
        .quantity-btn {
            background: var(--white);
            border: 2px solid var(--primary);
            color: var(--primary);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        .quantity-btn:hover {
            background: var(--primary);
            color: var(--white);
        }
        .quantity-value {
            min-width: 30px;
            text-align: center;
            font-weight: 600;
        }
        .cart-summary {
            background: var(--white);
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            position: sticky;
            top: 100px;
        }
        .cart-summary-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .cart-summary-row:last-child {
            border-bottom: none;
            font-size: 1.3em;
            font-weight: bold;
            color: var(--primary);
        }
        .cart-empty {
            text-align: center;
            padding: 80px 20px;
        }
        .cart-empty-icon {
            font-size: 5em;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        .checkout-btn {
            width: 100%;
            padding: 18px;
            background: #25D366;
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 1.2em;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
            text-decoration: none;
        }
        .checkout-btn:hover {
            background: #20BA5A;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(37, 211, 102, 0.4);
        }
        @media (max-width: 768px) {
            .cart-item {
                flex-direction: column;
            }
            .cart-item-image {
                width: 100%;
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>
                <?php if (file_exists('assets/logo.png')): ?>
                    <img src="assets/logo.png" alt="Kylin Prime" style="width: 40px; height: 40px; vertical-align: middle; margin-right: 10px;">
                <?php else: ?>
                    🐉
                <?php endif; ?>
                Kylin Prime
            </h1>
            <div class="header-actions">
                <?= i18n::languageSelector() ?>
                <a href="profile.php" class="user-info">👤 <?= htmlspecialchars($_SESSION['username']) ?></a>
                <a href="index.php" class="btn btn-secondary">← <?= __('cart.continue_shopping') ?></a>
                <a href="logout.php" class="btn btn-secondary"><?= __('menu.logout') ?></a>
            </div>
        </div>
    </header>

    <main class="cart-container">
        <div class="cart-header">
            <h1><?= __('cart.title') ?> 🛒</h1>
        </div>

        <?php if (empty($cartItems)): ?>
            <div class="cart-empty">
                <div class="cart-empty-icon">🛒</div>
                <h2><?= __('cart.empty') ?></h2>
                <p style="color: #666; margin: 20px 0;"><?= __('cart.empty_message', 'Adicione designs à sua cesta para começar!') ?></p>
                <a href="index.php" class="btn btn-primary"><?= __('cart.continue_shopping') ?></a>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: 1fr 350px; gap: 30px; align-items: start;">
                <div class="cart-items">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item" data-cart-id="<?= $item['id'] ?>">
                            <div class="cart-item-image">
                                <img src="api/serve_image.php?id=<?= $item['image_id'] ?>&type=thumbnail" alt="<?= $item['sku'] ?>">
                            </div>
                            <div class="cart-item-info">
                                <div class="cart-item-sku"><?= htmlspecialchars($item['sku']) ?></div>
                                <div class="cart-item-category"><?= htmlspecialchars($item['category_name']) ?></div>
                                <div class="cart-item-actions">
                                    <div class="quantity-control">
                                        <button class="quantity-btn" onclick="updateQuantity(<?= $item['id'] ?>, -1)">−</button>
                                        <span class="quantity-value" id="qty-<?= $item['id'] ?>"><?= $item['quantity'] ?></span>
                                        <button class="quantity-btn" onclick="updateQuantity(<?= $item['id'] ?>, 1)">+</button>
                                    </div>
                                    <button class="btn btn-delete" onclick="removeItem(<?= $item['id'] ?>)">
                                        🗑️ <?= __('cart.remove') ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <h2 style="margin-bottom: 20px;">Resumo</h2>
                    <div class="cart-summary-row">
                        <span><?= __('cart.total_items') ?></span>
                        <span id="total-items"><?= $totalItems ?></span>
                    </div>
                    <div class="cart-summary-row">
                        <span>Total de Designs</span>
                        <span id="total-designs"><?= count($cartItems) ?></span>
                    </div>
                    
                    <a href="#" id="checkout-btn" class="checkout-btn" style="margin-top: 20px;">
                        📱 <?= __('cart.checkout') ?>
                    </a>
                    
                    <p style="margin-top: 15px; font-size: 0.85em; color: #666; text-align: center;">
                        <?= __('cart.whatsapp_info', 'Você será redirecionado para o WhatsApp para finalizar') ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        // Atualiza quantidade
        async function updateQuantity(cartId, change) {
            const qtyElement = document.getElementById(`qty-${cartId}`);
            const currentQty = parseInt(qtyElement.textContent);
            const newQty = Math.max(1, currentQty + change);
            
            if (newQty === currentQty) return;
            
            try {
                const response = await fetch('api/cart_actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'update', cart_id: cartId, quantity: newQty})
                });
                
                const data = await response.json();
                if (data.success) {
                    qtyElement.textContent = newQty;
                    updateTotals();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        // Remove item
        async function removeItem(cartId) {
            if (!confirm('<?= __('msg.confirm_delete') ?>')) return;
            
            try {
                const response = await fetch('api/cart_actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'remove', cart_id: cartId})
                });
                
                const data = await response.json();
                if (data.success) {
                    document.querySelector(`[data-cart-id="${cartId}"]`).remove();
                    updateTotals();
                    
                    // Recarrega se vazio
                    if (document.querySelectorAll('.cart-item').length === 0) {
                        location.reload();
                    }
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        // Atualiza totais
        function updateTotals() {
            const quantities = Array.from(document.querySelectorAll('.quantity-value'))
                .map(el => parseInt(el.textContent));
            
            const totalItems = quantities.reduce((a, b) => a + b, 0);
            const totalDesigns = quantities.length;
            
            document.getElementById('total-items').textContent = totalItems;
            document.getElementById('total-designs').textContent = totalDesigns;
        }
        
        // Finalizar pedido pelo WhatsApp
        document.getElementById('checkout-btn')?.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Monta mensagem
            let message = '<?= addslashes($whatsappMessage) ?>\n\n';
            
            document.querySelectorAll('.cart-item').forEach(item => {
                const sku = item.querySelector('.cart-item-sku').textContent;
                const qty = item.querySelector('.quantity-value').textContent;
                message += `• ${sku} - Quantidade: ${qty}\n`;
            });
            
            message += '\nAguardo retorno. Obrigado!';
            
            // Abre WhatsApp
            const whatsappUrl = `https://wa.me/<?= $whatsappNumber ?>?text=${encodeURIComponent(message)}`;
            window.open(whatsappUrl, '_blank');
        });
    </script>
</body>
</html>