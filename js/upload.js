// Estado do upload
const uploadState = {
    files: [],
    maxFiles: 10,
    maxSize: 10 * 1024 * 1024 // 10MB
};

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    initDropzone();
    initUploadForm();
});

// ============ DROPZONE ============

function initDropzone() {
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('fileInput');
    
    if (!dropzone || !fileInput) return;
    
    // Click para selecionar
    dropzone.addEventListener('click', (e) => {
        if (e.target === dropzone || e.target.closest('.dropzone-content')) {
            fileInput.click();
        }
    });
    
    // Drag & Drop
    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('dragover');
    });
    
    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('dragover');
    });
    
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    });
    
    // Seleção de arquivos
    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });
}

function handleFiles(fileList) {
    const newFiles = Array.from(fileList);
    
    // Valida limite de arquivos
    if (uploadState.files.length + newFiles.length > uploadState.maxFiles) {
        alert(`Você pode fazer upload de no máximo ${uploadState.maxFiles} imagens por vez`);
        return;
    }
    
    // Valida cada arquivo
    const validFiles = newFiles.filter(file => {
        // Verifica tipo
        if (!file.type.match(/^image\/(jpeg|png|webp)$/)) {
            alert(`Arquivo ${file.name} não é uma imagem válida (PNG, JPG ou WEBP)`);
            return false;
        }
        
        // Verifica tamanho
        if (file.size > uploadState.maxSize) {
            alert(`Arquivo ${file.name} é muito grande (máximo 10MB)`);
            return false;
        }
        
        return true;
    });
    
    // Adiciona arquivos válidos
    uploadState.files.push(...validFiles);
    updatePreview();
    updateUploadButton();
}

function updatePreview() {
    const previewContainer = document.getElementById('previewContainer');
    const dropzoneContent = document.querySelector('.dropzone-content');
    
    if (!previewContainer) return;
    
    if (uploadState.files.length === 0) {
        previewContainer.innerHTML = '';
        dropzoneContent.style.display = 'block';
        return;
    }
    
    dropzoneContent.style.display = 'none';
    previewContainer.innerHTML = '';
    
    uploadState.files.forEach((file, index) => {
        const reader = new FileReader();
        
        reader.onload = (e) => {
            const preview = document.createElement('div');
            preview.className = 'preview-item';
            preview.innerHTML = `
                <img src="${e.target.result}" alt="${file.name}">
                <button type="button" class="preview-remove" data-index="${index}">×</button>
            `;
            
            preview.querySelector('.preview-remove').addEventListener('click', () => {
                removeFile(index);
            });
            
            previewContainer.appendChild(preview);
        };
        
        reader.readAsDataURL(file);
    });
}

function removeFile(index) {
    uploadState.files.splice(index, 1);
    updatePreview();
    updateUploadButton();
}

function updateUploadButton() {
    const uploadBtn = document.getElementById('uploadBtn');
    const category = document.getElementById('category');
    
    if (uploadBtn) {
        uploadBtn.disabled = uploadState.files.length === 0 || !category.value;
    }
}

// ============ FORMULÁRIO DE UPLOAD ============

function initUploadForm() {
    const form = document.getElementById('uploadForm');
    const clearBtn = document.getElementById('clearBtn');
    const category = document.getElementById('category');
    
    if (!form) return;
    
    // Submit
    form.addEventListener('submit', handleUpload);
    
    // Limpar
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            uploadState.files = [];
            updatePreview();
            updateUploadButton();
            document.getElementById('fileInput').value = '';
        });
    }
    
    // Categoria
    if (category) {
        category.addEventListener('change', updateUploadButton);
    }
}

async function handleUpload(e) {
    e.preventDefault();
    
    const category = document.getElementById('category').value;
    const uploadBtn = document.getElementById('uploadBtn');
    const progressContainer = document.getElementById('progressContainer');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    const results = document.getElementById('results');
    
    if (!category || uploadState.files.length === 0) {
        alert('Selecione uma categoria e adicione pelo menos uma imagem');
        return;
    }
    
    // Desabilita botão
    uploadBtn.disabled = true;
    uploadBtn.textContent = 'Enviando...';
    
    // Mostra progress bar
    progressContainer.style.display = 'block';
    results.style.display = 'none';
    
    // Prepara FormData
    const formData = new FormData();
    formData.append('category', category);
    
    uploadState.files.forEach(file => {
        formData.append('images[]', file);
    });
    
    try {
        // Simula progresso (já que não temos progresso real de upload)
        let progress = 0;
        const progressInterval = setInterval(() => {
            progress += 5;
            if (progress >= 90) {
                clearInterval(progressInterval);
            }
            updateProgress(progress);
        }, 200);
        
        // Faz upload
        const response = await fetch('api/upload.php', {
            method: 'POST',
            body: formData
        });
        
        clearInterval(progressInterval);
        updateProgress(100);
        
        const data = await response.json();
        
        // Mostra resultados
        setTimeout(() => {
            displayResults(data);
            
            if (data.uploaded && data.uploaded.length > 0) {
                // Limpa formulário
                uploadState.files = [];
                updatePreview();
                document.getElementById('fileInput').value = '';
                document.getElementById('category').value = '';
            }
            
            // Reabilita botão
            uploadBtn.disabled = false;
            uploadBtn.textContent = 'Fazer Upload';
            
            // Esconde progress bar
            setTimeout(() => {
                progressContainer.style.display = 'none';
            }, 2000);
        }, 500);
        
    } catch (error) {
        console.error('Erro no upload:', error);
        alert('Erro ao fazer upload das imagens');
        
        uploadBtn.disabled = false;
        uploadBtn.textContent = 'Fazer Upload';
        progressContainer.style.display = 'none';
    }
}

function updateProgress(percent) {
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    
    if (progressFill) {
        progressFill.style.width = percent + '%';
        progressFill.textContent = percent + '%';
    }
    
    if (progressText) {
        progressText.textContent = `${percent}% completo`;
    }
}

function displayResults(data) {
    const results = document.getElementById('results');
    if (!results) return;
    
    results.style.display = 'block';
    results.innerHTML = '<h3>Resultados do Upload</h3>';
    
    // Sucessos
    if (data.uploaded && data.uploaded.length > 0) {
        results.innerHTML += `<p><strong>✅ ${data.uploaded.length} imagem(ns) enviada(s) com sucesso:</strong></p>`;
        data.uploaded.forEach(img => {
            results.innerHTML += `
                <div class="result-item success">
                    ✓ SKU: ${img.sku} (Fundo: ${img.background_type === 'light' ? 'Claro' : 'Escuro'})
                </div>
            `;
        });
    }
    
    // Erros
    if (data.errors && data.errors.length > 0) {
        results.innerHTML += `<p><strong>❌ ${data.errors.length} erro(s):</strong></p>`;
        data.errors.forEach(err => {
            results.innerHTML += `
                <div class="result-item error">
                    ✗ ${err.file}: ${err.error}
                </div>
            `;
        });
    }
}