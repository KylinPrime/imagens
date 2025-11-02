<?php
/**
 * TEMPLATE PARA ADICIONAR NO admin.php
 * Cole estas seções no seu admin.php existente
 */

// No início do admin.php, adicionar:
require_once 'includes/i18n.php';

// Buscar usuários pendentes
$stmt = $pdo->query("
    SELECT id, username, full_name, email, created_at 
    FROM users 
    WHERE approved = FALSE 
    ORDER BY created_at DESC
");
$pendingUsers = $stmt->fetchAll();

// Buscar todas as categorias
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$allCategories = $stmt->fetchAll();
?>

<!-- ADICIONAR ANTES DA SEÇÃO DE UPLOAD -->

<!-- Usuários Pendentes -->
<?php if (count($pendingUsers) > 0): ?>
<div class="pending-users-section">
    <h2>⏳ <?= __('admin.pending_users') ?> (<?= count($pendingUsers) ?>)</h2>
    <div class="pending-users-list">
        <?php foreach ($pendingUsers as $user): ?>
            <div class="pending-user-card">
                <div class="pending-user-info">
                    <strong><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></strong>
                    <small>@<?= htmlspecialchars($user['username']) ?></small>
                    <?php if ($user['email']): ?>
                        <small>📧 <?= htmlspecialchars($user['email']) ?></small>
                    <?php endif; ?>
                    <small>📅 <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></small>
                </div>
                <div class="pending-user-actions">
                    <button class="btn btn-success" onclick="approveUser(<?= $user['id'] ?>)">
                        ✓ <?= __('admin.approve') ?>
                    </button>
                    <button class="btn btn-delete" onclick="rejectUser(<?= $user['id'] ?>)">
                        ✗ <?= __('admin.reject') ?>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Cadastro Manual de Usuário -->
<div class="upload-section">
    <h2>👤 <?= __('admin.manage_users') ?></h2>
    <form id="createUserForm" onsubmit="createUser(event)">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label><?= __('register.full_name') ?> *</label>
                <input type="text" name="full_name" required>
            </div>
            <div class="form-group">
                <label><?= __('login.username') ?> *</label>
                <input type="text" name="username" required minlength="3">
            </div>
            <div class="form-group">
                <label><?= __('register.email') ?></label>
                <input type="email" name="email">
            </div>
            <div class="form-group">
                <label><?= __('register.phone') ?></label>
                <input type="tel" name="phone">
            </div>
            <div class="form-group">
                <label><?= __('login.password') ?> *</label>
                <input type="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label>Tipo de Usuário *</label>
                <select name="role" required>
                    <option value="user">👤 <?= __('role.user') ?></option>
                    <option value="admin">⚙️ <?= __('role.admin') ?></option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top: 20px;">
            ➕ Criar Usuário
        </button>
    </form>
</div>

<!-- Gerenciar Categorias -->
<div class="upload-section">
    <h2>🏷️ <?= __('admin.manage_categories') ?></h2>
    
    <!-- Adicionar Nova Categoria -->
    <form id="addCategoryForm" onsubmit="addCategory(event)" style="margin-bottom: 30px;">
        <div style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">
            <div class="form-group" style="margin: 0;">
                <label><?= __('admin.category_name') ?> *</label>
                <input type="text" name="name" required placeholder="Ex: Dragões">
            </div>
            <div class="form-group" style="margin: 0;">
                <label><?= __('admin.category_prefix') ?> *</label>
                <input type="text" name="prefix" required maxlength="3" placeholder="Ex: DG" style="text-transform: uppercase;">
            </div>
            <button type="submit" class="btn btn-primary">
                ➕ <?= __('admin.add_category') ?>
            </button>
        </div>
    </form>
    
    <!-- Lista de Categorias -->
    <div class="categories-grid">
        <?php foreach ($allCategories as $cat): ?>
            <div class="category-card" data-category-id="<?= $cat['id'] ?>">
                <div class="category-info">
                    <strong><?= htmlspecialchars($cat['name']) ?></strong>
                    <span class="category-prefix"><?= htmlspecialchars($cat['prefix']) ?></span>
                </div>
                <button class="btn btn-delete btn-small" onclick="deleteCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name']) ?>')">
                    🗑️
                </button>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
/* Estilos para as novas seções */
.pending-users-section {
    background: #fff3cd;
    padding: 30px;
    border-radius: var(--radius);
    margin-bottom: 30px;
    border-left: 5px solid #ffc107;
}

.pending-users-list {
    margin-top: 20px;
}

.pending-user-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.pending-user-info {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.pending-user-info small {
    color: #666;
}

.pending-user-actions {
    display: flex;
    gap: 10px;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
}

.category-card {
    background: var(--light);
    padding: 15px;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 2px solid #e0e0e0;
    transition: all 0.3s;
}

.category-card:hover {
    border-color: var(--primary);
    background: white;
}

.category-info {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.category-prefix {
    display: inline-block;
    padding: 3px 8px;
    background: var(--primary);
    color: white;
    border-radius: 4px;
    font-size: 0.85em;
    font-weight: 600;
}

.btn-small {
    padding: 6px 12px;
    font-size: 0.9em;
}
</style>

<script>
// Aprovar usuário
async function approveUser(userId) {
    if (!confirm('Aprovar este usuário?')) return;
    
    try {
        const response = await fetch('api/admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'approve_user', user_id: userId})
        });
        
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert('Erro: ' + (data.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Erro ao aprovar usuário');
    }
}

// Rejeitar usuário
async function rejectUser(userId) {
    if (!confirm('Rejeitar e deletar este cadastro?')) return;
    
    try {
        const response = await fetch('api/admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'reject_user', user_id: userId})
        });
        
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert('Erro: ' + (data.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Erro ao rejeitar usuário');
    }
}

// Criar usuário
async function createUser(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    data.action = 'create_user';
    
    try {
        const response = await fetch('api/admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        if (result.success) {
            alert('✓ Usuário criado com sucesso!');
            e.target.reset();
        } else {
            alert('Erro: ' + (result.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Erro ao criar usuário');
    }
}

// Adicionar categoria
async function addCategory(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    data.action = 'add_category';
    data.prefix = data.prefix.toUpperCase();
    
    try {
        const response = await fetch('api/admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        if (result.success) {
            location.reload();
        } else {
            alert('Erro: ' + (result.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Erro ao adicionar categoria');
    }
}

// Deletar categoria
async function deleteCategory(catId, catName) {
    if (!confirm(`Deletar categoria "${catName}"?\n\nAVISO: Todas as imagens desta categoria ficarão sem categoria!`)) return;
    
    try {
        const response = await fetch('api/admin_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'delete_category', category_id: catId})
        });
        
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert('Erro: ' + (data.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Erro ao deletar categoria');
    }
}
</script>