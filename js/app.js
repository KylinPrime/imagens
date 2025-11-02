// Estado global
const state = {
    images: [],
    filteredImages: [],
    currentCategory: '',
    currentSearch: '',
    offset: 0,
    limit: 20,
    hasMore: true,
    isLoading: false
};

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    initFilters();
    initSearch();
    loadImages();
    initImageProtection();
});

// ============ PROTEÇÃO DE IMAGENS ============

function initImageProtection() {
    // Bloqueia clique direito em imagens
    document.addEventListener('contextmenu', function(e) {
        if (e.target.tagName === 'IMG' || e.target.closest('.gallery-item-image') || e.target.closest('.modal-content')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Bloqueia arrastar imagens
    document.addEventListener('dragstart', function(e) {
        if (e.target.tagName === 'IMG') {
            e.preventDefault();
            return false;
        }
    });
    
    // Bloqueia Ctrl+S, Ctrl+C, etc em imagens
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + S (salvar)
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            if (document.querySelector('.image-modal')?.style.display === 'flex') {
                e.preventDefault();
                return false;
            }
        }
        
        // Ctrl/Cmd + C (copiar) quando modal aberto
        if ((e.ctrlKey || e.metaKey) && e.key === 'c') {
            if (document.querySelector('.image-modal')?.style.display === 'flex') {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // Bloqueia inspetor em imagens (F12, Ctrl+Shift+I)
    document.addEventListener('keydown', function(e) {
        // F12
        if (e.key === 'F12') {
            const modal = document.querySelector('.image-modal');
            if (modal && modal.style.display === 'flex') {
                e.preventDefault();
                return false;
            }
        }
        
        // Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+Shift+C
        if (e.ctrlKey && e.shiftKey && ['I', 'J', 'C'].includes(e.key.toUpperCase())) {
            const modal = document.querySelector('.image-modal');
            if (modal && modal.style.display === 'flex') {
                e.preventDefault();
                return false;
            }
        }
    });
}

// ============ FILTROS E BUSCA ============

function initFilters() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            state.currentCategory = btn.dataset.category;
            resetAndLoad();
        });
    });
}

function initSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let timeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                state.currentSearch = e.target.value;
                resetAndLoad();
            }, 500);
        });
    }
}

function resetAndLoad() {
    state.offset = 0;
    state.images = [];
    state.hasMore = true;
    loadImages();
}

// ============ CARREGAMENTO DE IMAGENS ============

async function loadImages() {
    if (state.isLoading || !state.hasMore) return;
    
    state.isLoading = true;
    const galleryGrid = document.getElementById('galleryGrid');
    const loadMoreContainer = document.getElementById('loadMoreContainer');
    
    if (state.offset === 0) {
        galleryGrid.innerHTML = '<div class="loading">Carregando...</div>';
    }
    
    try {
        const params = new URLSearchParams({
            offset: state.offset,
            limit: state.limit
        });
        
        if (state.currentCategory) params.append('category', state.currentCategory);
        if (state.currentSearch) params.append('search', state.currentSearch);
        
        const response = await fetch(`api/list.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            if (state.offset === 0) {
                state.images = data.images;
            } else {
                state.images = [...state.images, ...data.images];
            }
            
            state.hasMore = data.has_more;
            state.offset += state.limit;
            
            renderGallery();
            
            if (loadMoreContainer) {
                loadMoreContainer.style.display = state.hasMore ? 'block' : 'none';
            }
        }
    } catch (error) {
        console.error('Erro ao carregar imagens:', error);
        galleryGrid.innerHTML = '<div class="loading">Erro ao carregar imagens</div>';
    } finally {
        state.isLoading = false;
    }
}

// ============ RENDERIZAÇÃO DA GALERIA ============

function renderGallery() {
    const galleryGrid = document.getElementById('galleryGrid');
    if (!galleryGrid) return;
    
    if (state.images.length === 0) {
        galleryGrid.innerHTML = '<div class="loading">Nenhuma imagem encontrada</div>';
        return;
    }
    
    galleryGrid.innerHTML = state.images.map(img => createGalleryItem(img)).join('');
    
    // Adiciona event listeners
    attachGalleryEvents();
}

function createGalleryItem(img) {
    // CORREÇÃO CRÍTICA: Detecta tipo de usuário corretamente
    const isLoggedIn = document.querySelector('.user-info') !== null;
    const isAdmin = document.querySelector('a[href="admin.php"]') !== null;
    
    // BUG CORRIGIDO: Verifica se TEM carrinho na página (= é cliente)
    const isCliente = document.querySelector('.btn-cart') !== null || document.querySelector('a[href="cart.php"]') !== null;
    
    // Determina se precisa fundo escuro (imagens muito claras)
    const needsDarkBg = img.background_type === 'dark';
    
    return `
        <div class="gallery-item" data-id="${img.id}">
            <div class="gallery-item-image ${needsDarkBg ? 'dark-bg' : ''}" style="cursor: pointer;" onclick="openModal(${img.id}, '${img.sku}')">
                <img src="api/serve_image.php?id=${img.id}&type=thumbnail" alt="${img.sku}" loading="lazy" onload="checkImageBrightness(this)">
            </div>
            <div class="gallery-item-info">
                <div class="gallery-item-sku">${img.sku}</div>
                <div class="gallery-item-category">${img.category}</div>
                <div class="gallery-item-actions">
                    ${isCliente ? `
                        <button class="btn btn-primary" onclick="addToCart(${img.id}, '${img.sku}')">
                            🛒 Adicionar
                        </button>
                    ` : ''}
                    ${isLoggedIn && !isCliente ? `
                        <a href="api/download.php?id=${img.id}" class="btn btn-download" download>
                            📥 Download
                        </a>
                    ` : ''}
                    ${isAdmin ? `
                        <button class="btn btn-delete" data-id="${img.id}">
                            🗑️ Deletar
                        </button>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
}

// Verifica brilho da imagem e ajusta fundo se necessário
function checkImageBrightness(img) {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    
    canvas.width = img.width;
    canvas.height = img.height;
    
    try {
        ctx.drawImage(img, 0, 0);
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const data = imageData.data;
        
        let totalBrightness = 0;
        let pixelCount = 0;
        
        // Amostra pixels (a cada 10 para performance)
        for (let i = 0; i < data.length; i += 40) {
            const r = data[i];
            const g = data[i + 1];
            const b = data[i + 2];
            const brightness = (r + g + b) / 3;
            totalBrightness += brightness;
            pixelCount++;
        }
        
        const avgBrightness = totalBrightness / pixelCount;
        
        // Se imagem muito clara (quase branca), adiciona fundo escuro
        if (avgBrightness > 240) {
            img.closest('.gallery-item-image').classList.add('dark-bg');
        }
    } catch (e) {
        // Ignora erro de CORS
        console.debug('Could not check image brightness');
    }
}

function attachGalleryEvents() {
    // Botões de deletar
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', handleDelete);
    });
}

// ============ DELETAR IMAGEM ============

async function handleDelete(e) {
    const imageId = e.target.dataset.id;
    
    if (!confirm('Tem certeza que deseja deletar esta imagem?')) {
        return;
    }
    
    try {
        const response = await fetch('api/delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: imageId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Remove da lista
            state.images = state.images.filter(img => img.id != imageId);
            
            // Re-renderiza
            renderGallery();
            
            showNotification('✅ Imagem removida com sucesso!', 'success');
        } else {
            showNotification('❌ Erro ao remover imagem: ' + data.error, 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showNotification('❌ Erro ao remover imagem', 'error');
    }
}

// ============ CARREGAR MAIS ============

const loadMoreBtn = document.getElementById('loadMoreBtn');
if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', loadImages);
}

// ============ CARRINHO DE COMPRAS ============

async function addToCart(imageId, sku) {
    try {
        const response = await fetch('api/cart_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'add', image_id: imageId, quantity: 1})
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Atualiza badge do carrinho
            const cartBadge = document.querySelector('.cart-badge');
            if (cartBadge && data.cart_count) {
                cartBadge.textContent = data.cart_count;
            }
            
            // Mostra mensagem
            showNotification(`✅ ${sku} adicionado ao carrinho!`, 'success');
        } else {
            showNotification('❌ Erro ao adicionar ao carrinho', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('❌ Erro ao adicionar ao carrinho', 'error');
    }
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${type === 'success' ? '#d4edda' : '#f8d7da'};
        color: ${type === 'success' ? '#155724' : '#721c24'};
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ============ MODAL DE VISUALIZAÇÃO ============

function openModal(imageId, sku) {
    // Cria modal se não existir
    let modal = document.getElementById('imageModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'imageModal';
        modal.className = 'image-modal';
        modal.innerHTML = `
            <div class="modal-overlay" onclick="closeModal()"></div>
            <div class="modal-content">
                <button class="modal-close" onclick="closeModal()">×</button>
                <img id="modalImage" src="" alt="" oncontextmenu="return false;" ondragstart="return false;">
                <div class="modal-info">
                    <h3 id="modalSKU"></h3>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Carrega imagem ORIGINAL (serve_image já aplica marca d'água se necessário)
    const modalImage = document.getElementById('modalImage');
    const modalSKU = document.getElementById('modalSKU');
    
    modalImage.src = `api/serve_image.php?id=${imageId}&type=original`;
    modalSKU.textContent = sku;
    
    // Mostra modal
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Fecha com ESC
    document.addEventListener('keydown', handleEscKey);
}

function closeModal() {
    const modal = document.getElementById('imageModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    document.removeEventListener('keydown', handleEscKey);
}

function handleEscKey(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
}