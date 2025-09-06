<?php
require_once '../db/functions.php';

$message = '';
$messageType = '';

// Get filter and search parameters
$orderBy = $_GET['order'] ?? 'titolo';
$search = $_GET['search'] ?? '';

// Gestione form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $tipo = sanitizeInput($_POST['tipo']);
                $titolo = sanitizeInput($_POST['titolo']);
                $immagine_url = sanitizeInput($_POST['immagine_url']);
                $data = sanitizeInput($_POST['data']);
                $volumi_totali = (int)$_POST['volumi_totali'];
                $volumi_posseduti = (int)$_POST['volumi_posseduti'];
                
                if ($tipo === 'variant') {
                    $costo_medio = (float)$_POST['costo_medio'];
                    $posseduto = isset($_POST['posseduto']) ? 1 : 0;
                    
                    if (addVariant($titolo, $immagine_url, $data, $costo_medio, $posseduto)) {
                        $message = 'Variant aggiunta con successo!';
                        $messageType = 'success';
                    } else {
                        $message = 'Errore nell\'aggiunta della variant. Il titolo potrebbe già esistere.';
                        $messageType = 'error';
                    }
                } else {
                    $prezzo_medio = (float)($_POST['prezzo_medio'] ?? 0);
                    if (addSerie($titolo, $immagine_url, $data, $volumi_totali, $volumi_posseduti, $prezzo_medio)) {
                        $message = 'Serie aggiunta con successo!';
                        $messageType = 'success';
                    } else {
                        $message = 'Errore nell\'aggiunta della serie. Il titolo potrebbe già esistere.';
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'remove':
                $id = (int)$_POST['id'];
                $tipo = sanitizeInput($_POST['tipo']);
                
                if ($tipo === 'variant') {
                    if (removeVariant($id)) {
                        $message = 'Variant rimossa con successo!';
                        $messageType = 'success';
                    } else {
                        $message = 'Errore nella rimozione della variant.';
                        $messageType = 'error';
                    }
                } else {
                    if (removeSerie($id)) {
                        $message = 'Serie rimossa con successo!';
                        $messageType = 'success';
                    } else {
                        $message = 'Errore nella rimozione della serie.';
                        $messageType = 'error';
                    }
                }
                break;
        }
    }
}

// Recupero dati per le diverse sezioni
$serieCollezione = getSerieCollezione($orderBy, $search);
$variantCollezione = getVariantCollezione($orderBy, $search);
$serieMancanti = getSerieMancanti($orderBy, $search);
$variantMancanti = getVariantMancanti($orderBy, $search);
$tuttiGliElementi = getAllItems();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collezione Manga</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        /* Additional styles for new features */
        .search-filter-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-input {
            flex: 1;
            min-width: 200px;
            padding: 0.75rem;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .filter-select {
            padding: 0.75rem;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            font-size: 1rem;
            min-width: 150px;
        }
        
        .volume-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(50px, 1fr));
            gap: 0.5rem;
            margin: 1rem 0;
        }
        
        .volume-item {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #bdc3c7;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            user-select: none;
        }
        
        .volume-item.owned {
            background-color: #27ae60;
            color: white;
            border-color: #27ae60;
        }
        
        .volume-item.missing {
            background-color: #ecf0f1;
            color: #7f8c8d;
            border-color: #bdc3c7;
        }
        
        .volume-item:hover {
            transform: scale(1.05);
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .btn-secondary {
            background-color: #95a5a6;
        }
        
        .btn-secondary:hover {
            background-color: #7f8c8d;
        }
        
        .modal-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #2c3e50;
        }
        
        .volume-content {
            max-height: 60vh;
            overflow-y: auto;
        }
        
        .edit-form-container {
            max-width: 500px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">📚 Collezione Manga</div>
                <nav>
                    <ul>
                        <li><a href="#collezione" class="active">Collezione Completa</a></li>
                        <li><a href="#serie-mancanti">Serie Mancanti</a></li>
                        <li><a href="#variant-mancanti">Variant Mancanti</a></li>
                        <li><a href="#aggiungi">Aggiungi</a></li>
                        <li><a href="#rimuovi">Rimuovi</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Sezione Collezione Completa -->
            <section id="collezione" class="section active">
                <h1 class="section-title">Collezione Completa</h1>
                
                <div class="search-filter-bar">
                    <input type="text" 
                           id="searchInput" 
                           class="search-input" 
                           placeholder="Cerca manga..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    
                    <select class="filter-select" data-section="collezione" onchange="applyFilter('collezione', this.value)">
                        <option value="titolo" <?php echo $orderBy === 'titolo' ? 'selected' : ''; ?>>Ordine Alfabetico</option>
                        <option value="prezzo_asc" <?php echo $orderBy === 'prezzo_asc' ? 'selected' : ''; ?>>Prezzo: Dal più basso</option>
                        <option value="prezzo_desc" <?php echo $orderBy === 'prezzo_desc' ? 'selected' : ''; ?>>Prezzo: Dal più alto</option>
                    </select>
                </div>
                
                <div class="cards-grid">
                    <?php foreach ($serieCollezione as $serie): ?>
                        <div class="card" data-serie-id="<?php echo $serie['id']; ?>">
                            <img src="<?php echo htmlspecialchars($serie['immagine_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($serie['titolo']); ?>" 
                                 class="card-image"
                                 onerror="this.src='https://via.placeholder.com/250x300?text=No+Image'">
                            <div class="card-content">
                                <h3 class="card-title"><?php echo htmlspecialchars($serie['titolo']); ?></h3>
                                <p class="card-date"><?php echo formatDate($serie['data_pubblicazione']); ?></p>
                                <div class="card-progress">
                                    <?php 
                                    $volumi_actual = $serie['volumi_posseduti_actual'] ?? $serie['volumi_posseduti'];
                                    if ($volumi_actual == $serie['volumi_totali']): ?>
                                        <span class="complete-badge">Serie Completa</span>
                                    <?php else: ?>
                                        <span class="card-volumes">
                                            Volumi: <?php echo $volumi_actual; ?>/<?php echo $serie['volumi_totali']; ?> - 
                                            Mancanti: <?php echo $serie['volumi_totali'] - $volumi_actual; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($serie['prezzo_medio'] > 0): ?>
                                    <div class="card-price"><?php echo formatPrice($serie['prezzo_medio']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php foreach ($variantCollezione as $variant): ?>
                        <div class="card" data-variant-id="<?php echo $variant['id']; ?>">
                            <img src="<?php echo htmlspecialchars($variant['immagine_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($variant['titolo']); ?>" 
                                 class="card-image"
                                 onerror="this.src='https://via.placeholder.com/250x300?text=No+Image'">
                            <div class="card-content">
                                <h3 class="card-title"><?php echo htmlspecialchars($variant['titolo']); ?></h3>
                                <p class="card-date"><?php echo formatDate($variant['data_rilascio']); ?></p>
                                <div class="card-progress">
                                    <span class="complete-badge">Variant Posseduta</span>
                                </div>
                                <div class="card-price"><?php echo formatPrice($variant['costo_medio']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Sezione Serie Mancanti -->
            <section id="serie-mancanti" class="section">
                <h1 class="section-title">Serie Mancanti</h1>
                
                <div class="search-filter-bar">
                    <input type="text" 
                           class="search-input" 
                           placeholder="Cerca serie mancanti..." 
                           onkeyup="performSearch(this.value)">
                    
                    <select class="filter-select" data-section="serie-mancanti" onchange="applyFilter('serie-mancanti', this.value)">
                        <option value="titolo">Ordine Alfabetico</option>
                        <option value="prezzo_asc">Prezzo: Dal più basso</option>
                        <option value="prezzo_desc">Prezzo: Dal più alto</option>
                    </select>
                </div>
                
                <div class="cards-grid">
                    <?php foreach ($serieMancanti as $serie): ?>
                        <div class="card missing" data-serie-id="<?php echo $serie['id']; ?>">
                            <img src="<?php echo htmlspecialchars($serie['immagine_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($serie['titolo']); ?>" 
                                 class="card-image"
                                 onerror="this.src='https://via.placeholder.com/250x300?text=No+Image'">
                            <div class="card-content">
                                <h3 class="card-title"><?php echo htmlspecialchars($serie['titolo']); ?></h3>
                                <p class="card-date"><?php echo formatDate($serie['data_pubblicazione']); ?></p>
                                <div class="card-progress">
                                    <span class="card-volumes">
                                        Volumi totali: <?php echo $serie['volumi_totali']; ?> - 
                                        Nessun volume posseduto
                                    </span>
                                </div>
                                <?php if ($serie['prezzo_medio'] > 0): ?>
                                    <div class="card-price"><?php echo formatPrice($serie['prezzo_medio']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Sezione Variant Mancanti -->
            <section id="variant-mancanti" class="section">
                <h1 class="section-title">Variant Mancanti</h1>
                
                <div class="search-filter-bar">
                    <input type="text" 
                           class="search-input" 
                           placeholder="Cerca variant mancanti..." 
                           onkeyup="performSearch(this.value)">
                    
                    <select class="filter-select" data-section="variant-mancanti" onchange="applyFilter('variant-mancanti', this.value)">
                        <option value="titolo">Ordine Alfabetico</option>
                        <option value="prezzo_asc">Prezzo: Dal più basso</option>
                        <option value="prezzo_desc">Prezzo: Dal più alto</option>
                    </select>
                </div>
                
                <div class="cards-grid">
                    <?php foreach ($variantMancanti as $variant): ?>
                        <div class="card missing" data-variant-id="<?php echo $variant['id']; ?>">
                            <img src="<?php echo htmlspecialchars($variant['immagine_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($variant['titolo']); ?>" 
                                 class="card-image"
                                 onerror="this.src='https://via.placeholder.com/250x300?text=No+Image'">
                            <div class="card-content">
                                <h3 class="card-title"><?php echo htmlspecialchars($variant['titolo']); ?></h3>
                                <p class="card-date"><?php echo formatDate($variant['data_rilascio']); ?></p>
                                <div class="card-progress">
                                    <span class="card-volumes">Variant non posseduta</span>
                                </div>
                                <div class="card-price"><?php echo formatPrice($variant['costo_medio']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Sezione Aggiungi -->
            <section id="aggiungi" class="section">
                <h1 class="section-title">Aggiungi Elemento</h1>
                <div class="form-container">
                    <form id="addForm" method="POST">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-group">
                            <label for="tipo">Tipo:</label>
                            <select id="tipo" name="tipo" required>
                                <option value="">Seleziona tipo...</option>
                                <option value="serie">Serie Manga</option>
                                <option value="variant">Variant</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="titolo">Titolo:</label>
                            <input type="text" id="titolo" name="titolo" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="immagine_url">URL Immagine:</label>
                            <input type="url" id="immagine_url" name="immagine_url">
                        </div>
                        
                        <div class="form-group">
                            <label for="data">Data di Pubblicazione/Rilascio:</label>
                            <input type="date" id="data" name="data">
                        </div>
                        
                        <div class="form-group hidden" id="costo-group">
                            <label for="costo_medio">Costo Medio (€):</label>
                            <input type="number" id="costo_medio" name="costo_medio" step="0.01" min="0">
                        </div>
                        
                        <div class="form-group" id="prezzo-group">
                            <label for="prezzo_medio">Prezzo Medio (€):</label>
                            <input type="number" id="prezzo_medio" name="prezzo_medio" step="0.01" min="0" value="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="volumi_totali">Volumi Totali:</label>
                            <input type="number" id="volumi_totali" name="volumi_totali" min="1" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="volumi_posseduti">Volumi Posseduti:</label>
                            <input type="number" id="volumi_posseduti" name="volumi_posseduti" min="0" value="0" required>
                        </div>
                        
                        <div class="form-group hidden" id="posseduto-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="posseduto" name="posseduto">
                                <label for="posseduto">Posseduto</label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn">Aggiungi</button>
                    </form>
                </div>
            </section>

            <!-- Sezione Rimuovi -->
            <section id="rimuovi" class="section">
                <h1 class="section-title">Rimuovi Elemento</h1>
                <div class="remove-list">
                    <?php foreach ($tuttiGliElementi as $elemento): ?>
                        <div class="remove-item">
                            <div class="remove-item-info">
                                <div class="remove-item-title"><?php echo htmlspecialchars($elemento['titolo']); ?></div>
                                <div class="remove-item-type"><?php echo ucfirst($elemento['tipo']); ?></div>
                            </div>
                            <button class="btn btn-danger" 
                                    onclick="confirmRemove(<?php echo $elemento['id']; ?>, '<?php echo $elemento['tipo']; ?>', '<?php echo htmlspecialchars($elemento['titolo']); ?>')">
                                Rimuovi
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- Modal per modifica elementi -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 class="modal-title">Modifica Elemento</h2>
            <div class="edit-form-container">
                <form id="editForm" method="POST">
                    <!-- Il contenuto verrà popolato dinamicamente da JavaScript -->
                </form>
            </div>
        </div>
    </div>

    <!-- Modal per gestione volumi -->
    <div id="volumeModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 class="modal-title">Gestisci Volumi</h2>
            <div class="volume-content">
                <!-- Il contenuto verrà popolato dinamicamente da JavaScript -->
            </div>
        </div>
    </div>

    <script src="../js/script.js?version=2"></script>
    <script>
        // Enhanced form handling
        document.addEventListener("DOMContentLoaded", function() {
            const tipoSelect = document.getElementById("tipo");
            const costoGroup = document.getElementById("costo-group");
            const prezzoGroup = document.getElementById("prezzo-group");
            const possedutoGroup = document.getElementById("posseduto-group");
            const volumiPossedutilabel = document.querySelector('label[for="volumi_posseduti"]');

            if (tipoSelect) {
                tipoSelect.addEventListener("change", function() {
                    if (this.value === "variant") {
                        costoGroup.classList.remove("hidden");
                        possedutoGroup.classList.remove("hidden");
                        prezzoGroup.classList.add("hidden");
                        volumiPossedutilabel.textContent = "Numero di copie:";
                    } else if (this.value === "serie") {
                        costoGroup.classList.add("hidden");
                        possedutoGroup.classList.add("hidden");
                        prezzoGroup.classList.remove("hidden");
                        volumiPossedutilabel.textContent = "Volumi Posseduti:";
                    } else {
                        costoGroup.classList.add("hidden");
                        possedutoGroup.classList.add("hidden");
                        prezzoGroup.classList.add("hidden");
                    }
                });
            }
        });
        
        // Apply filter function
        function applyFilter(section, orderBy) {
            const url = new URL(window.location);
            url.searchParams.set('order', orderBy);
            window.location.href = url.toString();
        }
        
        // Search function with debouncing
        let searchTimeout;
        function performSearch(query) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (query.length >= 2) {
                    const url = new URL(window.location);
                    url.searchParams.set('search', query);
                    window.location.href = url.toString();
                } else if (query.length === 0) {
                    const url = new URL(window.location);
                    url.searchParams.delete('search');
                    window.location.href = url.toString();
                }
            }, 500);
        }
        
        // Set search input value on page load
        document.addEventListener("DOMContentLoaded", function() {
            const searchInputs = document.querySelectorAll('.search-input');
            const urlParams = new URLSearchParams(window.location.search);
            const searchQuery = urlParams.get('search');
            
            if (searchQuery) {
                searchInputs.forEach(input => {
                    input.value = searchQuery;
                });
            }
            
            // Add search event listeners
            searchInputs.forEach(input => {
                input.addEventListener('input', function() {
                    performSearch(this.value);
                });
            });
        });
    </script>
</body>
</html>