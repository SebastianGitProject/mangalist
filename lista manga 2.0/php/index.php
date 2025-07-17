<?php
require_once '../db/functions.php';

$message = '';
$messageType = '';

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
                    if (addSerie($titolo, $immagine_url, $data, $volumi_totali, $volumi_posseduti)) {
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
$serieCollezione = getSerieCollezione();
$variantCollezione = getVariantCollezione();
$serieMancanti = getSerieMancanti();
$variantMancanti = getVariantMancanti();
$tuttiGliElementi = getAllItems();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collezione Manga</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
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
                <div class="cards-grid">
                    <?php foreach ($serieCollezione as $serie): ?>
                        <div class="card" data-serie-id="<?php echo $serie['id']; ?>">
                            <img src="<?php echo htmlspecialchars($serie['immagine_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($serie['titolo']); ?>" 
                                 class="card-image">
                            <div class="card-content">
                                <h3 class="card-title"><?php echo htmlspecialchars($serie['titolo']); ?></h3>
                                <p class="card-date"><?php echo formatDate($serie['data_pubblicazione']); ?></p>
                                <div class="card-progress">
                                    <?php if ($serie['volumi_posseduti'] == $serie['volumi_totali']): ?>
                                        <span class="complete-badge">Serie Completa</span>
                                    <?php else: ?>
                                        <span class="card-volumes">
                                            Volumi: <?php echo $serie['volumi_posseduti']; ?>/<?php echo $serie['volumi_totali']; ?> - 
                                            Mancanti: <?php echo $serie['volumi_totali'] - $serie['volumi_posseduti']; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php foreach ($variantCollezione as $variant): ?>
                        <div class="card">
                            <img src="<?php echo htmlspecialchars($variant['immagine_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($variant['titolo']); ?>" 
                                 class="card-image">
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
                <div class="cards-grid">
                    <?php foreach ($serieMancanti as $serie): ?>
                        <div class="card missing">
                            <img src="<?php echo htmlspecialchars($serie['immagine_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($serie['titolo']); ?>" 
                                 class="card-image">
                            <div class="card-content">
                                <h3 class="card-title"><?php echo htmlspecialchars($serie['titolo']); ?></h3>
                                <p class="card-date"><?php echo formatDate($serie['data_pubblicazione']); ?></p>
                                <div class="card-progress">
                                    <span class="card-volumes">
                                        Volumi totali: <?php echo $serie['volumi_totali']; ?> - 
                                        Nessun volume posseduto
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Sezione Variant Mancanti -->
            <section id="variant-mancanti" class="section">
                <h1 class="section-title">Variant Mancanti</h1>
                <div class="cards-grid">
                    <?php foreach ($variantMancanti as $variant): ?>
                        <div class="card missing">
                            <img src="<?php echo htmlspecialchars($variant['immagine_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($variant['titolo']); ?>" 
                                 class="card-image">
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

    <!-- Modal per dettagli serie -->
    <div id="serieModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalTitle"></h2>
            <div style="display: flex; gap: 2rem; margin-top: 1rem;">
                <div style="flex: 0 0 200px;">
                    <img id="modalImage" style="width: 100%; border-radius: 8px;">
                </div>
                <div style="flex: 1;">
                    <p><strong>Data di pubblicazione:</strong> <span id="modalDate"></span></p>
                    <div id="modalProgress" style="margin: 1rem 0;"></div>
                    <h3>Volumi:</h3>
                    <div id="volumeGrid" class="volume-grid"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>