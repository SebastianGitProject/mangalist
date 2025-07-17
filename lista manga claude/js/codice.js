// Variabili globali
let currentManga = null;
let itemToDelete = null;
let deleteType = null;

// Funzioni di navigazione
function showSection(sectionName) {
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });
    document.querySelectorAll('.nav-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    document.getElementById(sectionName).classList.add('active');
    event.target.classList.add('active');
    
    // Carica i dati appropriati per ogni sezione
    switch(sectionName) {
        case 'collezioneserie':
            loadCollezioneData();
            break;
        case 'seriemancanti':
            loadSerieMancanti();
            break;
        case 'variantmancanti':
            loadVariantMancanti();
            break;
        case 'rimuovi':
            loadDeleteData();
            break;
    }
}

// Gestione del form tipo nel form aggiungi
document.getElementById('type').addEventListener('change', function() {
    const variantFields = document.querySelectorAll('[data-variant-only]');
    const isVariant = this.value === 'variant';
    
    variantFields.forEach(field => {
        field.disabled = !isVariant;
        field.required = isVariant;
        if (isVariant) {
            field.style.display = 'block';
        } else {
            field.style.display = 'none';
        }
    });
    
    // Nascondi/mostra i campi volume per le variant
    const volumeFields = document.getElementById('volumeFields');
    if (isVariant) {
        volumeFields.style.display = 'none';
        document.getElementById('totalVolumes').required = false;
    } else {
        volumeFields.style.display = 'block';
        document.getElementById('totalVolumes').required = true;
    }
});

// Carica dati collezione (serie possedute + variant possedute)
async function loadCollezioneData() {
    try {
        const response = await fetch('api.php?action=getCollectionData');
        const data = await response.json();
        
        const mangaList = document.getElementById('manga-list');
        mangaList.innerHTML = '';
        
        // Mostra serie possedute
        data.mymanga.forEach(manga => {
            const ownedVolumes = manga.num_vol_poss ? manga.num_vol_poss.split(',').map(Number) : [];
            const missingVolumes = manga.num_vol_tot - ownedVolumes.length;
            const completionPercentage = (ownedVolumes.length / manga.num_vol_tot) * 100;
            
            const mangaCard = document.createElement('div');
            mangaCard.className = 'manga-card';
            mangaCard.onclick = () => showMangaDetails(manga.id, 'serie');
            
            mangaCard.innerHTML = `
                <img src="${manga.url_foto}" alt="${manga.titolo}" onerror="this.src='https://via.placeholder.com/200x300/ccc/666?text=No+Image'">
                <div class="manga-title">${manga.titolo}</div>
                <div class="manga-progress">
                    ${missingVolumes === 0 ? 
                        '<span class="completed">✅ COMPLETA</span>' : 
                        `Mancano ${missingVolumes} volumi`
                    }
                </div>
                <div class="manga-progress">${ownedVolumes.length}/${manga.num_vol_tot} volumi</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${completionPercentage}%"></div>
                </div>
            `;
            
            mangaList.appendChild(mangaCard);
        });
        
        // Mostra variant possedute
        data.variant.filter(v => v.posseduto).forEach(variant => {
            const variantCard = document.createElement('div');
            variantCard.className = 'manga-card variant-owned';
            variantCard.onclick = () => showVariantDetails(variant.id);
            
            variantCard.innerHTML = `
                <img src="${variant.url_foto}" alt="${variant.titolo}" onerror="this.src='https://via.placeholder.com/200x300/ccc/666?text=No+Image'">
                <div class="manga-title">${variant.titolo}</div>
                <div class="manga-progress">
                    <span class="completed">✅ VARIANT POSSEDUTA</span>
                </div>
                <div class="manga-progress">Rilascio: ${variant.data_rilascio}</div>
                <div class="manga-progress">Costo: €${variant.costo}</div>
            `;
            
            mangaList.appendChild(variantCard);
        });
        
    } catch (error) {
        console.error('Errore caricamento collezione:', error);
    }
}

// Carica serie mancanti
async function loadSerieMancanti() {
    try {
        const response = await fetch('api.php?action=getSerieMancanti');
        const data = await response.json();
        
        const mangaList = document.getElementById('mangamancanti-list');
        mangaList.innerHTML = '';
        
        data.forEach(manga => {
            const mangaCard = document.createElement('div');
            mangaCard.className = 'manga-card';
            mangaCard.onclick = () => showMangaMancantDetails(manga.id);
            
            mangaCard.innerHTML = `
                <img src="${manga.url_foto}" alt="${manga.titolo}" onerror="this.src='https://via.placeholder.com/200x300/ccc/666?text=No+Image'">
                <div class="manga-title">${manga.titolo}</div>
                <div class="manga-progress">Serie non posseduta</div>
                <div class="manga-progress">Volumi totali: ${manga.num_vol_tot}</div>
            `;
            
            mangaList.appendChild(mangaCard);
        });
        
    } catch (error) {
        console.error('Errore caricamento serie mancanti:', error);
    }
}

// Carica variant mancanti
async function loadVariantMancanti() {
    try {
        const response = await fetch('api.php?action=getVariantMancanti');
        const data = await response.json();
        
        const variantList = document.getElementById('variant-list');
        variantList.innerHTML = '';
        
        data.forEach(variant => {
            const variantCard = document.createElement('div');
            variantCard.className = variant.posseduto ? 'manga-card variant-owned' : 'manga-card variant-missing';
            variantCard.onclick = () => showVariantDetails(variant.id);
            
            variantCard.innerHTML = `
                <img src="${variant.url_foto}" alt="${variant.titolo}" onerror="this.src='https://via.placeholder.com/200x300/ccc/666?text=No+Image'">
                <div class="manga-title">${variant.titolo}</div>
                <div class="manga-progress">
                    ${variant.posseduto ? 
                        '<span class="completed">✅ POSSEDUTA</span>' : 
                        '❌ NON POSSEDUTA'
                    }
                </div>
                <div class="manga-progress">Rilascio: ${variant.data_rilascio}</div>
                <div class="manga-progress">Costo: €${variant.costo}</div>
            `;
            
            variantList.appendChild(variantCard);
        });
        
    } catch (error) {
        console.error('Errore caricamento variant:', error);
    }
}

// Dettagli manga serie
async function showMangaDetails(mangaId, type) {
    try {
        const response = await fetch(`api.php?action=getMangaDetails&id=${mangaId}`);
        const manga = await response.json();
        
        if (!manga) return;
        
        currentManga = manga;
        const ownedVolumes = manga.num_vol_poss ? manga.num_vol_poss.split(',').map(Number) : [];
        const missingVolumes = manga.num_vol_tot - ownedVolumes.length;
        
        const detailsDiv = document.getElementById('manga-details');
        detailsDiv.innerHTML = `
            <div style="text-align: center; margin-bottom: 30px;">
                <img src="${manga.url_foto}" alt="${manga.titolo}" style="width: 200px; height: 300px; object-fit: cover; border-radius: 12px; margin-bottom: 20px;">
                <h2>${manga.titolo}</h2>
                <p style="font-size: 1.2em; margin-bottom: 10px;">
                    ${missingVolumes === 0 ? 
                        '<span class="completed">✅ COLLEZIONE COMPLETA</span>' : 
                        `Mancano ${missingVolumes} volumi per completare la serie`
                    }
                </p>
                <p>Posseduti: ${ownedVolumes.length}/${manga.num_vol_tot} volumi</p>
            </div>
            <h3>Volumi:</h3>
            <div class="volume-grid">
                ${generateVolumeGrid(manga)}
            </div>
        `;
        
        showSection('details');
        
    } catch (error) {
        console.error('Errore caricamento dettagli manga:', error);
    }
}

// Dettagli variant
async function showVariantDetails(variantId) {
    try {
        const response = await fetch(`api.php?action=getVariantDetails&id=${variantId}`);
        const variant = await response.json();
        
        if (!variant) return;
        
        const detailsDiv = document.getElementById('manga-details');
        detailsDiv.innerHTML = `
            <div style="text-align: center; margin-bottom: 30px;">
                <img src="${variant.url_foto}" alt="${variant.titolo}" style="width: 200px; height: 300px; object-fit: cover; border-radius: 12px; margin-bottom: 20px;">
                <h2>${variant.titolo}</h2>
                <p style="font-size: 1.2em; margin-bottom: 10px;">
                    <span class="${variant.posseduto ? 'completed' : ''}">${variant.posseduto ? '✅ POSSEDUTA' : '❌ NON POSSEDUTA'}</span>
                </p>
                <p>Data rilascio: ${variant.data_rilascio}</p>
                <p>Costo medio: €${variant.costo}</p>
                <button class="btn ${variant.posseduto ? 'btn-danger' : ''}" onclick="toggleVariantPosseduto(${variant.id}, ${!variant.posseduto})">
                    ${variant.posseduto ? 'Rimuovi dal posseduto' : 'Segna come posseduta'}
                </button>
            </div>
        `;
        
        showSection('details');
        
    } catch (error) {
        console.error('Errore caricamento dettagli variant:', error);
    }
}

// Genera griglia volumi
function generateVolumeGrid(manga) {
    const ownedVolumes = manga.num_vol_poss ? manga.num_vol_poss.split(',').map(Number) : [];
    let html = '';
    
    for (let i = 1; i <= manga.num_vol_tot; i++) {
        const owned = ownedVolumes.includes(i);
        html += `
            <div class="volume-item ${owned ? 'owned' : 'missing'}" 
                 onclick="toggleVolume(${manga.id}, ${i})"
                 title="Volume ${i} - ${owned ? 'Posseduto' : 'Mancante'}">
                ${i}
            </div>
        `;
    }
    return html;
}

// Toggle volume
async function toggleVolume(mangaId, volumeNumber) {
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'toggleVolume',
                mangaId: mangaId,
                volumeNumber: volumeNumber
            })
        });
        
        const result = await response.json();
        if (result.success) {
            showMangaDetails(mangaId, 'serie');
        }
        
    } catch (error) {
        console.error('Errore toggle volume:', error);
    }
}

// Toggle variant posseduto
async function toggleVariantPosseduto(variantId, posseduto) {
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'toggleVariantPosseduto',
                variantId: variantId,
                posseduto: posseduto
            })
        });
        
        const result = await response.json();
        if (result.success) {
            showVariantDetails(variantId);
        }
        
    } catch (error) {
        console.error('Errore toggle variant:', error);
    }
}

// Gestione volumi nel form
document.getElementById('totalVolumes').addEventListener('input', function() {
    const totalVolumes = parseInt(this.value);
    const checkboxDiv = document.getElementById('volumeCheckboxes');
    
    if (totalVolumes && totalVolumes > 0) {
        let html = '';
        for (let i = 1; i <= totalVolumes; i++) {
            html += `
                <div class="checkbox-item">
                    <input type="checkbox" id="vol${i}" name="volumes" value="${i}">
                    <label for="vol${i}">Vol ${i}</label>
                </div>
            `;
        }
        checkboxDiv.innerHTML = html;
    } else {
        checkboxDiv.innerHTML = '';
    }
});

// Gestione form aggiungi
document.getElementById('addMangaForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const type = formData.get('type');
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('add-message').innerHTML = `
                <div class="alert alert-success">
                    ✅ ${type === 'variant' ? 'Variant' : 'Serie'} "${formData.get('title')}" aggiunta con successo!
                </div>
            `;
            
            this.reset();
            document.getElementById('volumeCheckboxes').innerHTML = '';
            
            // Riattiva i controlli del tipo
            document.getElementById('type').dispatchEvent(new Event('change'));
            
        } else {
            document.getElementById('add-message').innerHTML = `
                <div class="alert alert-error">
                    ❌ Errore: ${result.message}
                </div>
            `;
        }
        
        setTimeout(() => {
            document.getElementById('add-message').innerHTML = '';
        }, 3000);
        
    } catch (error) {
        console.error('Errore aggiunta:', error);
    }
});

// Carica dati per eliminazione
async function loadDeleteData() {
    try {
        const response = await fetch('api.php?action=getAllForDelete');
        const data = await response.json();
        
        const deleteList = document.getElementById('deleteList');
        deleteList.innerHTML = '';
        
        // Serie possedute
        data.mymanga.forEach(manga => {
            const ownedVolumes = manga.num_vol_poss ? manga.num_vol_poss.split(',').length : 0;
            const deleteItem = document.createElement('div');
            deleteItem.className = 'delete-item';
            deleteItem.innerHTML = `
                <div>
                    <strong>${manga.titolo}</strong>
                    <br>
                    <small>Serie - ${ownedVolumes}/${manga.num_vol_tot} volumi</small>
                </div>
                <button class="btn btn-danger" onclick="deleteItem(${manga.id}, 'mymanga')">
                    🗑️ Elimina
                </button>
            `;
            deleteList.appendChild(deleteItem);
        });
        
        // Serie mancanti
        data.mangamancanti.forEach(manga => {
            const deleteItem = document.createElement('div');
            deleteItem.className = 'delete-item';
            deleteItem.innerHTML = `
                <div>
                    <strong>${manga.titolo}</strong>
                    <br>
                    <small>Serie mancante - ${manga.num_vol_tot} volumi</small>
                </div>
                <button class="btn btn-danger" onclick="deleteItem(${manga.id}, 'mangamancanti')">
                    🗑️ Elimina
                </button>
            `;
            deleteList.appendChild(deleteItem);
        });
        
        // Variant
        data.variant.forEach(variant => {
            const deleteItem = document.createElement('div');
            deleteItem.className = 'delete-item';
            deleteItem.innerHTML = `
                <div>
                    <strong>${variant.titolo}</strong>
                    <br>
                    <small>Variant - ${variant.posseduto ? 'Posseduta' : 'Non posseduta'}</small>
                </div>
                <button class="btn btn-danger" onclick="deleteItem(${variant.id}, 'variant')">
                    🗑️ Elimina
                </button>
            `;
            deleteList.appendChild(deleteItem);
        });
        
    } catch (error) {
        console.error('Errore caricamento dati eliminazione:', error);
    }
}

// Elimina item
function deleteItem(id, type) {
    itemToDelete = id;
    deleteType = type;
    document.getElementById('confirmModal').style.display = 'block';
}

// Conferma eliminazione
async function confirmDelete() {
    if (itemToDelete && deleteType) {
        try {
            const response = await fetch('api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'deleteItem',
                    id: itemToDelete,
                    type: deleteType
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                document.getElementById('delete-message').innerHTML = `
                    <div class="alert alert-success">
                        ✅ Elemento eliminato con successo!
                    </div>
                `;
                
                loadDeleteData();
                closeModal();
                
            } else {
                document.getElementById('delete-message').innerHTML = `
                    <div class="alert alert-error">
                        ❌ Errore nell'eliminazione: ${result.message}
                    </div>
                `;
            }
            
            setTimeout(() => {
                document.getElementById('delete-message').innerHTML = '';
            }, 3000);
            
        } catch (error) {
            console.error('Errore eliminazione:', error);
        }
    }
}

// Chiudi modal
function closeModal() {
    document.getElementById('confirmModal').style.display = 'none';
    itemToDelete = null;
    deleteType = null;
}

// Gestione modal
document.querySelector('.close').onclick = closeModal;
window.onclick = function(event) {
    const modal = document.getElementById('confirmModal');
    if (event.target === modal) {
        closeModal();
    }
}

// Inizializzazione
document.addEventListener('DOMContentLoaded', function() {
    loadCollezioneData();
});