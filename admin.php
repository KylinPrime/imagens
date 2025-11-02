<?php
require_once 'includes/auth.php';
require_once 'includes/i18n.php';

requireAdmin();

// Busca categorias
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Busca usuários pendentes
$stmt = $pdo->query("
    SELECT id, username, full_name, email, phone, created_at 
    FROM users 
    WHERE approved = FALSE 
    ORDER BY created_at DESC
");
$pendingUsers = $stmt->fetchAll();

// Estatísticas
$totalImages = $pdo->query("SELECT COUNT(*) FROM images")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE approved = TRUE")->fetchColumn();
$totalClientes = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'cliente' AND approved = TRUE")->fetchColumn();
$totalCategories = count($categories);
?>
<!DOCTYPE html>
<html lang="<?= i18n::getLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('admin.title') ?> - Kylin Prime</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="admin-body">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-logo">
            <?php if (file_exists('assets/logo.png')): ?>
                <img src="assets/logo.png" alt="Kylin Prime">
            <?php else: ?>
                <span class="logo-emoji">🐉</span>
            <?php endif; ?>
            <span class="logo-text">Kylin Prime</span>
            <small class="logo-admin">Painel Admin</small>
        </div>

        <nav class="admin-nav">
            <a href="#dashboard" class="admin-nav-item active" data-section="dashboard">
                <span class="nav-icon">📊</span>
                <span class="nav-text">Dashboard</span>
            </a>
            
            <?php if (count($pendingUsers) > 0): ?>
            <a href="#pending-users" class="admin-nav-item" data-section="pending-users">
                <span class="nav-icon">⏳</span>
                <span class="nav-text">Usuários Pendentes</span>
                <span class="nav-badge"><?= count($pendingUsers) ?></span>
            </a>
            <?php endif; ?>
            
            <a href="#create-user" class="admin-nav-item" data-section="create-user">
                <span class="nav-icon">👤</span>
                <span class="nav-text">Cadastrar Usuário</span>
            </a>
            
            <a href="#manage-users" class="admin-nav-item" data-section="manage-users">
                <span class="nav-icon">👥</span>
                <span class="nav-text">Gerenciar Usuários</span>
            </a>
            
            <a href="#categories" class="admin-nav-item" data-section="categories">
                <span class="nav-icon">🏷️</span>
                <span class="nav-text">Categorias</span>
            </a>
            
            <a href="#upload" class="admin-nav-item" data-section="upload">
                <span class="nav-icon">📤</span>
                <span class="nav-text">Upload de Imagens</span>
            </a>

            <div class="nav-divider"></div>

            <a href="index.php" class="admin-nav-item">
                <span class="nav-icon">🏠</span>
                <span class="nav-text">Voltar para Galeria</span>
            </a>

            <a href="profile.php" class="admin-nav-item">
                <span class="nav-icon">⚙️</span>
                <span class="nav-text">Meu Perfil</span>
            </a>

            <a href="logout.php" class="admin-nav-item">
                <span class="nav-icon">🚪</span>
                <span class="nav-text">Sair</span>
            </a>
        </nav>

        <div class="admin-user-info">
            <?= i18n::languageSelector() ?>
            <div class="user-detail">
                <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
                <small>Administrador</small>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <!-- Dashboard Section -->
        <section id="section-dashboard" class="admin-section active">
            <div class="admin-header">
                <h1>📊 Dashboard</h1>
                <p>Visão geral do sistema</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🖼️</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $totalImages ?></div>
                        <div class="stat-label">Total de Imagens</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🏷️</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $totalCategories ?></div>
                        <div class="stat-label">Categorias</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $totalUsers ?></div>
                        <div class="stat-label">Usuários Ativos</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🛒</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $totalClientes ?></div>
                        <div class="stat-label">Clientes</div>
                    </div>
                </div>
            </div>

            <?php if (count($pendingUsers) > 0): ?>
            <div class="alert alert-warning" style="margin-top: 30px;">
                <strong>⏳ Atenção!</strong> Você tem <?= count($pendingUsers) ?> usuário(s) aguardando aprovação.
                <a href="#pending-users" class="btn btn-sm" onclick="switchSection('pending-users')" style="margin-left: 15px;">Ver Pendentes</a>
            </div>
            <?php endif; ?>
        </section>

        <!-- Pending Users Section -->
        <?php if (count($pendingUsers) > 0): ?>
        <section id="section-pending-users" class="admin-section">
            <div class="admin-header">
                <h1>⏳ Usuários Pendentes</h1>
                <p>Aprovar ou rejeitar novos cadastros</p>
            </div>

            <div class="pending-users-list">
                <?php foreach ($pendingUsers as $user): ?>
                    <div class="pending-user-card">
                        <div class="pending-user-info">
                            <strong><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></strong>
                            <small>@<?= htmlspecialchars($user['username']) ?></small>
                            <?php if ($user['email']): ?>
                                <small>📧 <?= htmlspecialchars($user['email']) ?></small>
                            <?php endif; ?>
                            <?php if ($user['phone']): ?>
                                <small>📱 <?= htmlspecialchars($user['phone']) ?></small>
                            <?php endif; ?>
                            <small>📅 <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></small>
                        </div>
                        <div class="pending-user-actions">
                            <button class="btn btn-success" onclick="approveUser(<?= $user['id'] ?>)">
                                ✓ Aprovar
                            </button>
                            <button class="btn btn-danger" onclick="rejectUser(<?= $user['id'] ?>)">
                                ✗ Rejeitar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Create User Section -->
        <section id="section-create-user" class="admin-section">
            <div class="admin-header">
                <h1>👤 Cadastrar Novo Usuário</h1>
                <p>Criar usuário, admin ou cliente manualmente</p>
            </div>

            <div class="admin-form-container">
                <form id="createUserForm" onsubmit="createUser(event)" class="admin-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Nome Completo *</label>
                            <input type="text" id="full_name" name="full_name" required>
                        </div>
                        <div class="form-group">
                            <label for="username">Nome de Usuário *</label>
                            <input type="text" id="username" name="username" required minlength="3">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">E-mail</label>
                            <input type="email" id="email" name="email">
                        </div>
                        <div class="form-group">
                            <label for="phone">Telefone</label>
                            <input type="tel" id="phone" name="phone">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Senha *</label>
                            <input type="password" id="password" name="password" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label for="role">Tipo de Usuário *</label>
                            <select id="role" name="role" required>
                                <option value="cliente">🛒 Cliente (vê marca d'água + carrinho)</option>
                                <option value="user">👤 Usuário (sem marca d'água + download)</option>
                                <option value="admin">⚙️ Administrador (acesso total)</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg">
                        ➕ Criar Usuário
                    </button>
                </form>
            </div>
        </section>

        <!-- Manage Users Section -->
        <section id="section-manage-users" class="admin-section">
            <div class="admin-header">
                <h1>👥 Gerenciar Usuários</h1>
                <p>Editar, desativar ou reativar usuários e clientes</p>
            </div>

            <div class="users-list">
                <?php
                $stmtUsers = $pdo->query("
                    SELECT id, username, full_name, email, phone, role, approved, 
                           created_at, language,
                           (SELECT COUNT(*) FROM images WHERE uploaded_by = users.id) as uploaded_count
                    FROM users 
                    ORDER BY created_at DESC
                ");
                $allUsers = $stmtUsers->fetchAll();
                
                foreach ($allUsers as $user):
                    $roleLabels = [
                        'admin' => '⚙️ Admin',
                        'user' => '👤 Usuário',
                        'cliente' => '🛒 Cliente'
                    ];
                ?>
                    <div class="user-card" data-user-id="<?= $user['id'] ?>">
                        <div class="user-card-header">
                            <div class="user-card-info">
                                <strong><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></strong>
                                <small>@<?= htmlspecialchars($user['username']) ?></small>
                            </div>
                            <div class="user-card-badges">
                                <span class="badge badge-<?= $user['role'] ?>"><?= $roleLabels[$user['role']] ?></span>
                                <?php if (!$user['approved']): ?>
                                    <span class="badge badge-inactive">❌ Desativado</span>
                                <?php else: ?>
                                    <span class="badge badge-active">✓ Ativo</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="user-card-details">
                            <?php if ($user['email']): ?>
                                <span>📧 <?= htmlspecialchars($user['email']) ?></span>
                            <?php endif; ?>
                            <?php if ($user['phone']): ?>
                                <span>📱 <?= htmlspecialchars($user['phone']) ?></span>
                            <?php endif; ?>
                            <span>🗓️ Desde <?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
                            <?php if ($user['uploaded_count'] > 0): ?>
                                <span>📸 <?= $user['uploaded_count'] ?> upload(s)</span>
                            <?php endif; ?>
                        </div>

                        <div class="user-card-actions">
                            <button class="btn btn-sm btn-primary" onclick="editUser(<?= $user['id'] ?>)">
                                ✏️ Editar
                            </button>
                            <?php if ($user['approved']): ?>
                                <button class="btn btn-sm btn-secondary" onclick="toggleUserStatus(<?= $user['id'] ?>, false)">
                                    ❌ Desativar
                                </button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-success" onclick="toggleUserStatus(<?= $user['id'] ?>, true)">
                                    ✓ Reativar
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Categories Section -->
        <section id="section-categories" class="admin-section">
            <div class="admin-header">
                <h1>🏷️ Gerenciar Categorias</h1>
                <p>Adicionar e remover categorias de estampas</p>
            </div>

            <div class="admin-form-container" style="margin-bottom: 30px;">
                <form id="addCategoryForm" onsubmit="addCategory(event)" class="admin-form">
                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label for="category_name">Nome da Categoria *</label>
                            <input type="text" id="category_name" name="name" required placeholder="Ex: Dragões">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="category_prefix">Prefixo (2-3 letras) *</label>
                            <input type="text" id="category_prefix" name="prefix" required maxlength="3" placeholder="Ex: DG" style="text-transform: uppercase;">
                        </div>
                        <div class="form-group" style="flex: 0 0 auto; align-self: flex-end;">
                            <button type="submit" class="btn btn-primary">➕ Adicionar</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="categories-grid">
                <?php foreach ($categories as $cat): ?>
                    <?php
                    // Conta imagens da categoria
                    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM images WHERE category = ?");
                    $stmtCount->execute([$cat['id']]);
                    $imageCount = $stmtCount->fetchColumn();
                    ?>
                    <div class="category-card" data-category-id="<?= $cat['id'] ?>">
                        <div class="category-info">
                            <strong><?= htmlspecialchars($cat['name']) ?></strong>
                            <span class="category-prefix"><?= htmlspecialchars($cat['prefix']) ?></span>
                            <small><?= $imageCount ?> imagem(ns)</small>
                        </div>
                        <button class="btn btn-danger btn-sm" onclick="deleteCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['name'])) ?>')" <?= $imageCount > 0 ? 'disabled title="Não é possível deletar categoria com imagens"' : '' ?>>
                            🗑️
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Upload Section -->
        <section id="section-upload" class="admin-section">
            <div class="admin-header">
                <h1>📤 Upload de Imagens</h1>
                <p>Faça upload de até 10 imagens por vez</p>
            </div>

            <div class="admin-form-container">
                <form id="uploadForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="category">Categoria *</label>
                        <select id="category" name="category" required>
                            <option value="">Selecione uma categoria</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>">
                                    <?= htmlspecialchars($cat['name']) ?> (<?= $cat['prefix'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="dropzone" id="dropzone">
                        <div class="dropzone-content">
                            <div class="dropzone-icon">📁</div>
                            <p class="dropzone-text">Arraste imagens aqui ou clique para selecionar</p>
                            <p class="dropzone-hint">PNG, JPG ou WEBP - Máximo 10MB por arquivo</p>
                            <input type="file" id="fileInput" name="images[]" multiple accept="image/jpeg,image/png,image/webp" style="display: none;">
                        </div>
                        <div class="preview-container" id="previewContainer"></div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="clearBtn">Limpar</button>
                        <button type="submit" class="btn btn-primary" id="uploadBtn" disabled>Fazer Upload</button>
                    </div>
                </form>

                <div class="progress-container" id="progressContainer" style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                    <p class="progress-text" id="progressText">0%</p>
                </div>

                <div class="results" id="results" style="display: none;"></div>
            </div>
        </section>
    </main>

    <script src="js/app.js"></script>
    <script src="js/upload.js"></script>
    <script>
        // Navegação entre seções
        function switchSection(sectionId) {
            // Remove active de todos
            document.querySelectorAll('.admin-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.admin-nav-item').forEach(n => n.classList.remove('active'));
            
            // Adiciona active no selecionado
            document.getElementById('section-' + sectionId).classList.add('active');
            document.querySelector(`[data-section="${sectionId}"]`).classList.add('active');
        }

        // Event listeners para navegação
        document.querySelectorAll('.admin-nav-item[data-section]').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const section = item.dataset.section;
                switchSection(section);
                
                // Atualiza URL sem recarregar
                history.pushState(null, '', `#${section}`);
            });
        });

        // Carrega seção pela URL
        if (window.location.hash) {
            const section = window.location.hash.substring(1);
            switchSection(section);
        }

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
                    showNotification('✓ Usuário aprovado!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('✗ Erro: ' + (data.error || 'Erro desconhecido'), 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('✗ Erro ao aprovar usuário', 'error');
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
                    showNotification('✓ Usuário rejeitado', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('✗ Erro: ' + (data.error || 'Erro desconhecido'), 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('✗ Erro ao rejeitar usuário', 'error');
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
                    showNotification('✓ Usuário criado com sucesso!', 'success');
                    e.target.reset();
                } else {
                    showNotification('✗ Erro: ' + (result.error || 'Erro desconhecido'), 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('✗ Erro ao criar usuário', 'error');
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
                    showNotification('✓ Categoria adicionada!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('✗ Erro: ' + (result.error || 'Erro desconhecido'), 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('✗ Erro ao adicionar categoria', 'error');
            }
        }

        // Deletar categoria
        async function deleteCategory(catId, catName) {
            if (!confirm(`Deletar categoria "${catName}"?\n\nSó é possível deletar categorias SEM imagens!`)) return;
            
            try {
                const response = await fetch('api/admin_actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'delete_category', category_id: catId})
                });
                
                const data = await response.json();
                if (data.success) {
                    showNotification('✓ Categoria deletada!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('✗ Erro: ' + (data.error || 'Erro desconhecido'), 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('✗ Erro ao deletar categoria', 'error');
            }
        }

        // Editar usuário
        async function editUser(userId) {
            try {
                const response = await fetch(`api/admin_actions.php?action=get_user&user_id=${userId}`);
                const data = await response.json();
                
                if (data.success) {
                    const user = data.user;
                    
                    // Cria modal de edição
                    const modal = document.createElement('div');
                    modal.className = 'modal-overlay active';
                    modal.innerHTML = `
                        <div class="modal-box">
                            <div class="modal-header">
                                <h2>✏️ Editar Usuário</h2>
                                <button class="modal-close" onclick="this.closest('.modal-overlay').remove()">×</button>
                            </div>
                            <form id="editUserForm" class="admin-form">
                                <input type="hidden" name="user_id" value="${user.id}">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Nome Completo *</label>
                                        <input type="text" name="full_name" value="${user.full_name || ''}" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Nome de Usuário *</label>
                                        <input type="text" name="username" value="${user.username}" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>E-mail</label>
                                        <input type="email" name="email" value="${user.email || ''}">
                                    </div>
                                    <div class="form-group">
                                        <label>Telefone</label>
                                        <input type="tel" name="phone" value="${user.phone || ''}">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Tipo de Usuário *</label>
                                        <select name="role" required>
                                            <option value="cliente" ${user.role === 'cliente' ? 'selected' : ''}>🛒 Cliente</option>
                                            <option value="user" ${user.role === 'user' ? 'selected' : ''}>👤 Usuário</option>
                                            <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>⚙️ Admin</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Nova Senha (deixe em branco para não alterar)</label>
                                        <input type="password" name="password" minlength="6">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-lg">💾 Salvar Alterações</button>
                            </form>
                        </div>
                    `;
                    
                    document.body.appendChild(modal);
                    
                    // Handler do formulário
                    document.getElementById('editUserForm').addEventListener('submit', async (e) => {
                        e.preventDefault();
                        const formData = new FormData(e.target);
                        const updateData = Object.fromEntries(formData);
                        updateData.action = 'update_user';
                        
                        try {
                            const response = await fetch('api/admin_actions.php', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify(updateData)
                            });
                            
                            const result = await response.json();
                            if (result.success) {
                                showNotification('✓ Usuário atualizado!', 'success');
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                showNotification('✗ Erro: ' + (result.error || 'Erro desconhecido'), 'error');
                            }
                        } catch (error) {
                            showNotification('✗ Erro ao atualizar usuário', 'error');
                        }
                    });
                }
            } catch (error) {
                showNotification('✗ Erro ao carregar dados do usuário', 'error');
            }
        }

        // Ativar/Desativar usuário
        async function toggleUserStatus(userId, activate) {
            const action = activate ? 'ativar' : 'desativar';
            if (!confirm(`Tem certeza que deseja ${action} este usuário?`)) return;
            
            try {
                const response = await fetch('api/admin_actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'toggle_user_status',
                        user_id: userId,
                        approved: activate
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    showNotification(`✓ Usuário ${activate ? 'ativado' : 'desativado'}!`, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('✗ Erro: ' + (data.error || 'Erro desconhecido'), 'error');
                }
            } catch (error) {
                showNotification('✗ Erro ao alterar status', 'error');
            }
        }
    </script>
</body>
</html>