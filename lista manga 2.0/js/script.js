document.addEventListener("DOMContentLoaded", () => {
  const links = document.querySelectorAll("nav a");
  const sections = document.querySelectorAll(".section");
  const modal = document.getElementById("serieModal");
  const editModal = document.getElementById("editModal");
  const volumeModal = document.getElementById("volumeModal");

  // Navigation
  links.forEach(link => {
    link.addEventListener("click", e => {
      e.preventDefault();
      const targetId = link.getAttribute("href").substring(1);
      
      sections.forEach(sec => sec.classList.remove("active"));
      document.getElementById(targetId).classList.add("active");
      
      links.forEach(l => l.classList.remove("active"));
      link.classList.add("active");
    });
  });

  // Form handling per tipo
  const tipoSelect = document.getElementById("tipo");
  const costoGroup = document.getElementById("costo-group");
  const possedutoGroup = document.getElementById("posseduto-group");
  const volumiPossedutilabel = document.querySelector('label[for="volumi_posseduti"]');

  if (tipoSelect) {
    tipoSelect.addEventListener("change", function() {
      if (this.value === "variant") {
        costoGroup.classList.remove("hidden");
        possedutoGroup.classList.remove("hidden");
        volumiPossedutilabel.textContent = "Numero di copie:";
      } else {
        costoGroup.classList.add("hidden");
        possedutoGroup.classList.add("hidden");
        volumiPossedutilabel.textContent = "Volumi Posseduti:";
      }
    });
  }

  // Search functionality
  const searchInput = document.getElementById("searchInput");
  if (searchInput) {
    let searchTimeout;
    searchInput.addEventListener("input", function() {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        performSearch(this.value);
      }, 200);
    });
  }

  // Filter functionality
  const filterSelects = document.querySelectorAll(".filter-select");
  filterSelects.forEach(select => {
    select.addEventListener("change", function() {
      const sectionId = this.dataset.section;
      applyFilter(sectionId, this.value);
    });
  });

  // Card click handlers per modifica
  document.addEventListener("click", function(e) {
    const card = e.target.closest(".card");
    if (card && !e.target.classList.contains("btn")) {
      const serieId = card.dataset.serieId;
      const variantId = card.dataset.variantId;
      
      if (serieId) {
        openSerieEditModal(serieId);
      } else if (variantId) {
        openVariantEditModal(variantId);
      }
    }
  });

  // Modal functionality
  const closeButtons = document.querySelectorAll(".close");
  closeButtons.forEach(close => {
    close.addEventListener("click", function() {
      this.closest(".modal").style.display = "none";
    });
  });

  window.addEventListener("click", function(e) {
    if (e.target.classList.contains("modal")) {
      e.target.style.display = "none";
    }
  });
});

// Search function
function performSearch(query) { //non puoi cercare con 1 sola lettera
  if (query.length < 2) {
    location.reload(); // Reset view
    return;
  }

  fetch(`../db/ajax.php?action=search&query=${encodeURIComponent(query)}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        displaySearchResults(data.results);
      } else {
        console.error("Search error:", data.message);
      }
    })
    .catch(error => {
      console.error("Search error:", error);
    });
}

// Display search results
function displaySearchResults(results) {
  const sections = ["collezione", "serie-mancanti", "variant-mancanti"];
  
  sections.forEach(sectionId => {
    const section = document.getElementById(sectionId);
    const grid = section.querySelector(".cards-grid");
    grid.innerHTML = "";
    
    results.forEach(item => {
      if (shouldShowInSection(item, sectionId)) {
        const card = createCardElement(item);
        grid.appendChild(card);
      }
    });
  });
}

// Check if item should be shown in section
function shouldShowInSection(item, sectionId) {
  if (item.tipo === "variant") {
    return (sectionId === "collezione" && item.posseduto) ||
           (sectionId === "variant-mancanti" && !item.posseduto);
  } else {
    return (sectionId === "collezione" && item.volumi_posseduti > 0) ||
           (sectionId === "serie-mancanti" && item.volumi_posseduti === 0);
  }
}

// Apply filter
function applyFilter(sectionId, filterType) {
  const currentUrl = new URL(window.location);
  currentUrl.searchParams.set(`${sectionId}_order`, filterType);
  window.location.href = currentUrl.toString();
}

// Open serie edit modal
function openSerieEditModal(serieId) {
  fetch(`../db/ajax.php?action=getSerie&id=${serieId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        populateSerieEditModal(data.serie);
        document.getElementById("editModal").style.display = "block";
      } else {
        alert("Errore nel caricamento della serie: " + data.message);
      }
    })
    .catch(error => {
      console.error("Error:", error);
      alert("Errore nel caricamento della serie");
    });
}

// Open variant edit modal
function openVariantEditModal(variantId) {
  fetch(`../db/ajax.php?action=getVariant&id=${variantId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        populateVariantEditModal(data.variant);
        document.getElementById("editModal").style.display = "block";
      } else {
        alert("Errore nel caricamento della variant: " + data.message);
      }
    })
    .catch(error => {
      console.error("Error:", error);
      alert("Errore nel caricamento della variant");
    });
}

// Populate serie edit modal
function populateSerieEditModal(serie) {
  const modal = document.getElementById("editModal");
  const form = modal.querySelector("form");
  
  form.innerHTML = `
    <input type="hidden" name="action" value="updateSerie">
    <input type="hidden" name="id" value="${serie.id}">
    
    <div class="form-group">
      <label for="edit_titolo">Titolo:</label>
      <input type="text" id="edit_titolo" name="titolo" value="${serie.titolo}" required>
    </div>
    
    <div class="form-group">
      <label for="edit_immagine_url">URL Immagine:</label>
      <input type="url" id="edit_immagine_url" name="immagine_url" value="${serie.immagine_url || ''}">
    </div>
    
    <div class="form-group">
      <label for="edit_data_pubblicazione">Data di Pubblicazione:</label>
      <input type="date" id="edit_data_pubblicazione" name="data_pubblicazione" value="${serie.data_pubblicazione || ''}">
    </div>
    
    <div class="form-group">
      <label for="edit_volumi_totali">Volumi Totali:</label>
      <input type="number" id="edit_volumi_totali" name="volumi_totali" value="${serie.volumi_totali}" min="1" required>
    </div>
    
    <div class="form-group">
      <label for="edit_prezzo_medio">Prezzo Medio (€):</label>
      <input type="number" id="edit_prezzo_medio" name="prezzo_medio" value="${serie.prezzo_medio || 0}" step="0.01" min="0">
    </div>
    
    <div class="form-actions">
      <button type="submit" class="btn">Aggiorna Serie</button>
      <button type="button" class="btn btn-secondary" onclick="openVolumeModal(${serie.id})">Gestisci Volumi</button>
    </div>
  `;
  
  form.addEventListener("submit", handleSerieUpdate);
}

// Populate variant edit modal
function populateVariantEditModal(variant) {
  const modal = document.getElementById("editModal");
  const form = modal.querySelector("form");
  
  form.innerHTML = `
    <input type="hidden" name="action" value="updateVariant">
    <input type="hidden" name="id" value="${variant.id}">
    
    <div class="form-group">
      <label for="edit_titolo">Titolo:</label>
      <input type="text" id="edit_titolo" name="titolo" value="${variant.titolo}" required>
    </div>
    
    <div class="form-group">
      <label for="edit_immagine_url">URL Immagine:</label>
      <input type="url" id="edit_immagine_url" name="immagine_url" value="${variant.immagine_url || ''}">
    </div>
    
    <div class="form-group">
      <label for="edit_data_rilascio">Data di Rilascio:</label>
      <input type="date" id="edit_data_rilascio" name="data_rilascio" value="${variant.data_rilascio || ''}">
    </div>
    
    <div class="form-group">
      <label for="edit_costo_medio">Costo Medio (€):</label>
      <input type="number" id="edit_costo_medio" name="costo_medio" value="${variant.costo_medio || 0}" step="0.01" min="0" required>
    </div>
    
    <div class="form-group">
      <div class="checkbox-group">
        <input type="checkbox" id="edit_posseduto" name="posseduto" ${variant.posseduto ? 'checked' : ''}>
        <label for="edit_posseduto">Posseduto</label>
      </div>
    </div>
    
    <div class="form-actions">
      <button type="submit" class="btn">Aggiorna Variant</button>
    </div>
  `;
  
  form.addEventListener("submit", handleVariantUpdate);
}

// Handle serie update
function handleSerieUpdate(e) {
  e.preventDefault();
  
  const formData = new FormData(e.target);
  
  fetch("../db/ajax.php", {
    method: "POST",
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert("Serie aggiornata con successo!");
      location.reload();
    } else {
      alert("Errore: " + data.message);
    }
  })
  .catch(error => {
    console.error("Error:", error);
    alert("Errore nell'aggiornamento della serie");
  });
}

// Handle variant update
function handleVariantUpdate(e) {
  e.preventDefault();
  
  const formData = new FormData(e.target);
  
  fetch("../db/ajax.php", {
    method: "POST",
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert("Variant aggiornata con successo!");
      location.reload();
    } else {
      alert("Errore: " + data.message);
    }
  })
  .catch(error => {
    console.error("Error:", error);
    alert("Errore nell'aggiornamento della variant");
  });
}

// Open volume management modal
function openVolumeModal(serieId) {
  fetch(`../db/ajax.php?action=getSerie&id=${serieId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        populateVolumeModal(data.serie);
        document.getElementById("editModal").style.display = "none";
        document.getElementById("volumeModal").style.display = "block";
      } else {
        alert("Errore nel caricamento dei volumi: " + data.message);
      }
    })
    .catch(error => {
      console.error("Error:", error);
      alert("Errore nel caricamento dei volumi");
    });
}

// Populate volume modal
function populateVolumeModal(serie) {
  const modal = document.getElementById("volumeModal");
  const title = modal.querySelector(".modal-title");
  const content = modal.querySelector(".volume-content");
  
  title.textContent = `Gestisci Volumi - ${serie.titolo}`;
  
  let volumeGrid = '<div class="volume-grid">';
  const volumiPosseduti = serie.volumi_dettagli || [];
  
  for (let i = 1; i <= serie.volumi_totali; i++) {
    const volumeInfo = volumiPosseduti.find(v => v.numero_volume === i);
    const isOwned = volumeInfo ? volumeInfo.posseduto : false;
    
    volumeGrid += `
      <div class="volume-item ${isOwned ? 'owned' : 'missing'}" 
           data-volume="${i}" 
           onclick="toggleVolume(this)">
        ${i}
      </div>
    `;
  }
  
  volumeGrid += '</div>';
  
  content.innerHTML = `
    <p>Clicca sui volumi per cambiare il loro stato (posseduto/non posseduto)</p>
    ${volumeGrid}
    <div class="form-actions" style="margin-top: 2rem;">
      <button type="button" class="btn" onclick="saveVolumes(${serie.id})">Salva Modifiche</button>
      <button type="button" class="btn btn-secondary" onclick="selectAllVolumes()">Seleziona Tutti</button>
      <button type="button" class="btn btn-secondary" onclick="deselectAllVolumes()">Deseleziona Tutti</button>
    </div>
  `;
}

// Toggle volume ownership
function toggleVolume(element) {
  if (element.classList.contains("owned")) {
    element.classList.remove("owned");
    element.classList.add("missing");
  } else {
    element.classList.remove("missing");
    element.classList.add("owned");
  }
}

// Select all volumes
function selectAllVolumes() {
  const volumes = document.querySelectorAll(".volume-item");
  volumes.forEach(vol => {
    vol.classList.remove("missing");
    vol.classList.add("owned");
  });
}

// Deselect all volumes
function deselectAllVolumes() {
  const volumes = document.querySelectorAll(".volume-item");
  volumes.forEach(vol => {
    vol.classList.remove("owned");
    vol.classList.add("missing");
  });
}

// Save volume changes
function saveVolumes(serieId) {
  const ownedVolumes = [];
  const volumes = document.querySelectorAll(".volume-item.owned");
  
  volumes.forEach(vol => {
    ownedVolumes.push(parseInt(vol.dataset.volume));
  });
  
  const formData = new FormData();
  formData.append("serie_id", serieId);
  formData.append("volumi", JSON.stringify(ownedVolumes));
  
  fetch("../db/ajax.php?action=updateVolumi", {
    method: "POST",
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert("Volumi aggiornati con successo!");
      location.reload();
    } else {
      alert("Errore: " + data.message);
    }
  })
  .catch(error => {
    console.error("Error:", error);
    alert("Errore nell'aggiornamento dei volumi");
  });
}

// Create card element for search results
function createCardElement(item) {
  const card = document.createElement("div");
  card.className = "card";
  
  if (item.tipo === "serie") {
    card.dataset.serieId = item.id;
    if (item.volumi_posseduti === 0) {
      card.classList.add("missing");
    }
  } else {
    card.dataset.variantId = item.id;
    if (!item.posseduto) {
      card.classList.add("missing");
    }
  }
  
  const imageUrl = item.immagine_url || 'https://via.placeholder.com/250x300?text=No+Image';
  const date = formatDate(item.data);
  
  card.innerHTML = `
    <img src="${imageUrl}" 
         alt="${item.titolo}" 
         class="card-image"
         onerror="this.src='https://via.placeholder.com/250x300?text=No+Image'">
    <div class="card-content">
      <h3 class="card-title">${item.titolo}</h3>
      <p class="card-date">${date}</p>
      <div class="card-progress">
        ${getCardProgressHTML(item)}
      </div>
      ${getCardPriceHTML(item)}
    </div>
  `;
  
  return card;
}

// Get card progress HTML
function getCardProgressHTML(item) {
  if (item.tipo === "variant") {
    return item.posseduto ? 
      '<span class="complete-badge">Variant Posseduta</span>' : 
      '<span class="card-volumes">Variant non posseduta</span>';
  } else {
    if (item.volumi_posseduti === item.volumi_totali) {
      return '<span class="complete-badge">Serie Completa</span>';
    } else if (item.volumi_posseduti > 0) {
      return `<span class="card-volumes">Volumi: ${item.volumi_posseduti}/${item.volumi_totali} - Mancanti: ${item.volumi_totali - item.volumi_posseduti}</span>`;
    } else {
      return `<span class="card-volumes">Volumi totali: ${item.volumi_totali} - Nessun volume posseduto</span>`;
    }
  }
}

// Get card price HTML
function getCardPriceHTML(item) {
  const price = item.tipo === "variant" ? item.costo_medio : item.prezzo_medio;
  return price > 0 ? `<div class="card-price">${formatPrice(price)}</div>` : '';
}

// Format date helper
function formatDate(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('it-IT');
}

// Format price helper
function formatPrice(price) {
  return new Intl.NumberFormat('it-IT', {
    style: 'currency',
    currency: 'EUR'
  }).format(price);
}

// Confirm remove function
function confirmRemove(id, tipo, titolo) {
  if (confirm(`Sei sicuro di voler rimuovere "${titolo}"?`)) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
      <input type="hidden" name="action" value="remove">
      <input type="hidden" name="id" value="${id}">
      <input type="hidden" name="tipo" value="${tipo}">
    `;
    document.body.appendChild(form);
    form.submit();
  }
}