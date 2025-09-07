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
                $nome = sanitizeInput($_POST['nome'] ?? $_POST['titolo']);
                $immagine_url = sanitizeInput($_POST['immagine_url']);
                $data = sanitizeInput($_POST['data']);
                $prezzo = (float)($_POST['prezzo'] ?? $_POST['costo_medio'] ?? $_POST['prezzo_medio'] ?? 0);
                $posseduto = isset($_POST['posseduto']) ? 1 : 0;
                
                $success = false;
                
                switch ($tipo) {
                    case 'serie':
                        $volumi_totali = (int)$_POST['volumi_totali'];
                        $volumi_posseduti = (int)$_POST['volumi_posseduti'];
                        $success = addSerie($nome, $immagine_url, $data, $volumi_totali, $volumi_posseduti, $prezzo);
                        break;
                        
                    case 'variant':
                        $success = addVariant($nome, $immagine_url, $data, $prezzo, $posseduto);
                        break;
                        
                    case 'funko_pop':
                        $success = addFunkoPop($nome, $immagine_url, $data, $prezzo, $posseduto);
                        break;
                        
                    case 'monster':
                        $success = addMonster($nome, $immagine_url, $data, $prezzo, $posseduto);
                        break;
                        
                    case 'artbooks_anime':
                        $success = addArtbooksAnime($nome, $immagine_url, $data, $prezzo, $posseduto);
                        break;
                        
                    case 'gameboys':
                        $links = $_POST['links'] ?? [];
                        $links = array_filter($links); // Rimuovi links vuoti
                        $success = addGameboys($nome, $immagine_url, $data, $prezzo, $links, $posseduto);
                        break;
                        
                    case 'pokemon_game':
                        $links = $_POST['links'] ?? [];
                        $links = array_filter($links); // Rimuovi links vuoti
                        $success = addPokemonGame($nome, $immagine_url, $data, $prezzo, $links, $posseduto);
                        break;
                        
                    case 'numeri_yugioh':
                        $codice = sanitizeInput($_POST['codice']);
                        if (strlen($codice) !== 11) {
                            $message = 'Il codice deve essere di esattamente 11 cifre.';
                            $messageType = 'error';
                            break;
                        }
                        $success = addNumeriYugioh($nome, $codice, $immagine_url, $data, $prezzo, $posseduto);
                        break;
                        
                    case 'duel_masters':
                        $is_box = isset($_POST['is_box']) ? 1 : 0;
                        $success = addDuelMasters($nome, $immagine_url, $data, $prezzo, $is_box, $posseduto);
                        break;
                }
                
                if ($success) {
                    $message = ucfirst(str_replace('_', ' ', $tipo)) . ' aggiunto con successo!';
                    $messageType = 'success';
                } else if (empty($message)) {
                    $message = 'Errore nell\'aggiunta. Il nome potrebbe già esistere.';
                    $messageType = 'error';
                }
                break;
                
            case 'remove':
                $id = (int)$_POST['id'];
                $tipo = sanitizeInput($_POST['tipo']);
                
                $success = false;
                if ($tipo === 'variant') {
                    $success = removeVariant($id);
                } else if ($tipo === 'serie') {
                    $success = removeSerie($id);
                } else {
                    $success = removeByTable($tipo, $id);
                }
                
                if ($success) {
                    $message = ucfirst(str_replace('_', ' ', $tipo)) . ' rimosso con successo!';
                    $messageType = 'success';
                } else {
                    $message = 'Errore nella rimozione.';
                    $messageType = 'error';
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

// Nuove sezioni
$funkoPop = getFunkoPop($orderBy, $search);
$monster = getMonster($orderBy, $search);
$artbooksAnime = getArtbooksAnime($orderBy, $search);
$gameboys = getGameboys($orderBy, $search);
$pokemonGame = getPokemonGame($orderBy, $search);
$numeriYugioh = getNumeriYugioh($orderBy, $search);
$duelMasters = getDuelMasters($orderBy, $search);

$tuttiGliElementi = getAllItems();

// Contatori
$serieMancantiFIltered = getSerieMancanti('', $search);
$countSerieMancanti = count($serieMancantiFIltered);
$countYugiohMancanti = countYugiohMancanti();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collezione Manga & More</title>
    <link rel="stylesheet" href="../css/styles.css?version=1">
    <style>
        /* Additional styles for new features */
        .spin-wheel-container {
            text-align: center;
            margin: 2rem 0;
        }
        
        .counter-badge {
            background-color: #e74c3c;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .wheel-container {
            position: relative;
            width: 200px;
            height: 200px;
            margin: 0 auto 2rem;
        }
        
        .wheel {
            width: 200px;
            height: 200px;
            border: 8px solid #3498db;
            border-radius: 50%;
            background: conic-gradient(
                #ff6b6b 0deg 60deg,
                #4ecdc4 60deg 120deg,
                #45b7d1 120deg 180deg,
                #f9ca24 180deg 240deg,
                #f0932b 240deg 300deg,
                #eb4d4b 300deg 360deg
            );
            transition: transform 3s cubic-bezier(0.25, 0.1, 0.25, 1);
            position: relative;
        }
        
        .wheel::after {
            content: '';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 10px solid transparent;
            border-right: 10px solid transparent;
            border-top: 20px solid #2c3e50;
        }
        
        .spin-result-card {
            text-align: center;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .spin-result-card img {
            border-radius: 4px;
        }
        
        .link-input-group {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .link-input-group input {
            flex: 1;
        }
        
        .card-links {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        
        .card-link {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.8rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            transition: background-color 0.3s ease;
        }
        
        .card-link:hover {
            background-color: #2980b9;
        }
        
        .card-code {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">📚 Collezione Manga & More</div>
                <nav>
                    <ul>
                        <li><a href="#collezione" class="active">Collezione Completa</a></li>
                        <li><a href="#serie-mancanti">Serie Mancanti</a></li>
                        <li><a href="#variant-mancanti">Variant Mancanti</a></li>
                        <li><a href="#funko-pop">Funko Pop</a></li>
                        <li><a href="#monster">Monster Energy</a></li>
                        <li><a href="#artbooks-anime">Artbooks Anime</a></li>
                        <li><a href="#gameboys">Gameboys AD-SP</a></li>
                        <li><a href="#pokemon-game">Pokemon Game</a></li>
                        <li><a href="#numeri-yugioh">Numeri Yu-Gi-Oh</a></li>
                        <li><a href="#duel-masters">Duel Masters</a></li>
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
                           placeholder="Cerca..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    
                    <select class="filter-select" data-section="collezione" onchange="applyFilter('collezione', this.value)">
                        <option value="titolo" <?php echo $orderBy === 'titolo' ? 'selected' : ''; ?>>Ordine Alfabetico</option>
                        <option value="prezzo_asc" <?php echo $orderBy === 'prezzo_asc' ? 'selected' : ''; ?>>Prezzo: Dal più basso</option>
                        <option value="prezzo_desc" <?php echo $orderBy === 'prezzo_desc' ? 'selected' : ''; ?>>Prezzo: Dal più alto</option>
                    </select>
                </div>
                
                <div class="cards-grid">
                    <?php 
                    $allCollezione = array_merge($serieCollezione, $variantCollezione);
                    foreach ($funkoPop as $item) {
                        if ($item['posseduto']) $allCollezione[] = $item;
                    }
                    foreach ($monster as $item) {
                        if ($item['posseduto']) $allCollezione[] = $item;
                    }
                    foreach ($artbooksAnime as $item) {
                        if ($item['posseduto']) $allCollezione[] = $item;
                    }
                    foreach ($gameboys as $item) {
                        if ($item['posseduto']) $allCollezione[] = $item;
                    }
                    foreach ($pokemonGame as $item) {
                        if ($item['posseduto']) $allCollezione[] = $item;
                    }
                    foreach ($numeriYugioh as $item) {
                        if ($item['posseduto']) $allCollezione[] = $item;
                    }
                    foreach ($duelMasters as $item) {
                        if ($item['posseduto']) $allCollezione[] = $item;
                    }
                    
                    foreach ($allCollezione as $item): 
                        $isCard = isset($item['titolo']);
                        $nome = $isCard ? $item['titolo'] : $item['nome'];
                        $data = $isCard ? ($item['data_pubblicazione'] ?? $item['data_rilascio']) : $item['data_pubblicazione'];
                    ?>
                        <div class="card" 
                             <?php if ($isCard): ?>
                                <?php if (isset($item['volumi_totali'])): ?>
                                    data-serie-id="<?php echo $item['id']; ?>"
                                <?php else: ?>
                                    data-variant-id="<?php echo $item['id']; ?>"
                                <?php endif; ?>
                             <?php else: ?>
                                data-item-type="<?php echo getItemType($item); ?>" 
                                data-item-id="<?php echo $item['id']; ?>"
                             <?php endif; ?>>
                            
                            <img src="<?php echo htmlspecialchars($item['immagine_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($nome); ?>" 
                                 class="card-image"
                                 onerror="this.src='https://via.placeholder.com/250x300?text=No+Image'">
                            <div class="card-content">
                                <h3 class="card-title"><?php echo htmlspecialchars($nome); ?></h3>
                                <p class="card-date"><?php echo formatDate($data); ?></p>
                                
                                <?php if (isset($item['codice'])): ?>
                                    <p class="card-code"><strong>Codice:</strong> <?php echo htmlspecialchars($item['codice']); ?></p>
                                <?php endif; ?>
                                
                                <div class="card-progress">
                                    <?php if ($isCard): ?>
                                        <?php if (isset($item['volumi_totali'])): ?>
                                            <?php 
                                            $volumi_actual = $item['volumi_posseduti_actual'] ?? $item['volumi_posseduti'];
                                            if ($volumi_actual == $item['volumi_totali']): ?>
                                                <span class="complete-badge">Serie Completa</span>
                                            <?php else: ?>
                                                <span class="card-volumes">
                                                    Volumi: <?php echo $volumi_actual; ?>/<?php echo $item['volumi_totali']; ?> - 
                                                    Mancanti: <?php echo $item['volumi_totali'] - $volumi_actual; ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="complete-badge">Variant Posseduta</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="complete-badge">Posseduto</span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php 
                                $prezzo = 0;
                                if ($isCard) {
                                    $prezzo = $item['prezzo_medio'] ?? $item['costo_medio'] ?? 0;
                                } else {
                                    $prezzo = $item['prezzo'] ?? 0;
                                }
                                if ($prezzo > 0): 
                                ?>
                                    <div class="card-price"><?php echo formatPrice($prezzo); ?></div>
                                <?php endif; ?>
                                
                                <?php if (isset($item['links']) && !empty($item['links'])): ?>
                                    <?php $links = json_decode($item['links'], true); ?>
                                    <?php if ($links && count($links) > 0): ?>
                                        <div class="card-links">
                                            <?php foreach ($links as $index => $link): ?>
                                                <a href="<?php echo htmlspecialchars($link); ?>" target="_blank" class="card-link">
                                                    Link <?php echo $index + 1; ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Sezione Serie Mancanti -->
            <section id="serie-mancanti" class="section">
                <h1 class="section-title">Serie Mancanti</h1>
                
                <div class="spin-wheel-container">
                    <div class="counter-badge">
                        Serie Mancanti: <?php echo $countSerieMancanti; ?>
                    </div>
                    <button class="btn" onclick="openSpinWheel()">🎯 Ruota della Fortuna</button>
                </div>
                
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

            <?php
            // Funzione per creare sezioni generiche
            function createGenericSection($id, $title, $items, $counter = null) {
                echo "<section id=\"$id\" class=\"section\">";
                echo "<h1 class=\"section-title\">$title</h1>";
                
                if ($counter !== null) {
                    echo "<div class=\"spin-wheel-container\">";
                    echo "<div class=\"counter-badge\">$counter</div>";
                    echo "</div>";
                }
                
                echo '<div class="search-filter-bar">
                        <input type="text" class="search-input" placeholder="Cerca..." onkeyup="performSearch(this.value)">
                        <select class="filter-select" data-section="' . $id . '" onchange="applyFilter(\'' . $id . '\', this.value)">
                            <option value="nome">Ordine Alfabetico</option>
                            <option value="prezzo_asc">Prezzo: Dal più basso</option>
                            <option value="prezzo_desc">Prezzo: Dal più alto</option>
                        </select>
                      </div>';
                
                echo '<div class="cards-grid">';
                foreach ($items as $item) {
                    $cardClass = $item['posseduto'] ? 'card' : 'card missing';
                    $itemType = getItemType($item);
                    
                    echo "<div class=\"$cardClass\" data-item-type=\"$itemType\" data-item-id=\"{$item['id']}\">";
                    echo '<img src="' . htmlspecialchars($item['immagine_url']) . '" alt="' . htmlspecialchars($item['nome']) . '" class="card-image" onerror="this.src=\'https://via.placeholder.com/250x300?text=No+Image\'">';
                    echo '<div class="card-content">';
                    echo '<h3 class="card-title">' . htmlspecialchars($item['nome']) . '</h3>';
                    echo '<p class="card-date">' . formatDate($item['data_pubblicazione']) . '</p>';
                    
                    if (isset($item['codice'])) {
                        echo '<p class="card-code"><strong>Codice:</strong> ' . htmlspecialchars($item['codice']) . '</p>';
                    }
                    
                    echo '<div class="card-progress">';
                    echo $item['posseduto'] ? '<span class="complete-badge">Posseduto</span>' : '<span class="card-volumes">Non posseduto</span>';
                    echo '</div>';
                    
                    if ($item['prezzo'] > 0) {
                        echo '<div class="card-price">' . formatPrice($item['prezzo']) . '</div>';
                    }
                    
                    // Links per gameboys e pokemon_game
                    if (isset($item['links']) && !empty($item['links'])) {
                        $links = json_decode($item['links'], true);
                        if ($links && count($links) > 0) {
                            echo '<div class="card-links">';
                            foreach ($links as $index => $link) {
                                echo '<a href="' . htmlspecialchars($link) . '" target="_blank" class="card-link">Link ' . ($index + 1) . '</a>';
                            }
                            echo '</div>';
                        }
                    }
                    
                    echo '</div>';
                    echo '</div>';
                }
                echo '</div>';
                echo '</section>';
            }
            
            // Crea le sezioni per le nuove categorie
            createGenericSection('funko-pop', 'Funko Pop', $funkoPop);
            createGenericSection('monster', 'Monster', $monster);
            createGenericSection('artbooks-anime', 'Artbooks Anime', $artbooksAnime);
            createGenericSection('gameboys', 'Gameboys', $gameboys);
            createGenericSection('pokemon-game', 'Pokemon Game', $pokemonGame);
            createGenericSection('numeri-yugioh', 'Numeri Yu-Gi-Oh', $numeriYugioh, "Numeri Mancanti: $countYugiohMancanti");
            createGenericSection('duel-masters', 'Duel Masters', $duelMasters);
            ?>

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
                                <option value="funko_pop">Funko Pop</option>
                                <option value="monster">Monster</option>
                                <option value="artbooks_anime">Artbooks Anime</option>
                                <option value="gameboys">Gameboys</option>
                                <option value="pokemon_game">Pokemon Game</option>
                                <option value="numeri_yugioh">Numeri Yu-Gi-Oh</option>
                                <option value="duel_masters">Duel Masters</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="nome">Nome/Titolo:</label>
                            <input type="text" id="nome" name="nome" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="immagine_url">URL Immagine:</label>
                            <input type="url" id="immagine_url" name="immagine_url">
                        </div>
                        
                        <div class="form-group">
                            <label for="data">Data di Pubblicazione/Rilascio:</label>
                            <input type="date" id="data" name="data">
                        </div>
                        
                        <!-- Codice per Yu-Gi-Oh -->
                        <div class="form-group hidden" id="codice-group">
                            <label for="codice">Codice (11 cifre):</label>
                            <input type="text" id="codice" name="codice" maxlength="11" pattern="[0-9]{11}">
                        </div>
                        
                        <!-- Box per Duel Masters -->
                        <div class="form-group hidden" id="box-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_box" name="is_box">
                                <label for="is_box">È un Box</label>
                            </div>
                        </div>
                        
                        <!-- Links per Gameboys e Pokemon Game -->
                        <div class="form-group hidden" id="links-group">
                            <label>Links:</label>
                            <div id="linksContainer">
                                <!-- Links dinamici verranno aggiunti qui -->
                            </div>
                            <button type="button" id="addLinkBtn" class="btn btn-secondary">Aggiungi Link</button>
                        </div>
                        
                        <div class="form-group hidden" id="costo-group">
                            <label for="costo_medio">Costo Medio (€):</label>
                            <input type="number" id="costo_medio" name="costo_medio" step="0.01" min="0">
                        </div>
                        
                        <div class="form-group" id="prezzo-group">
                            <label for="prezzo">Prezzo (€):</label>
                            <input type="number" id="prezzo" name="prezzo" step="0.01" min="0" value="0">
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
                                <div class="remove-item-title"><?php echo htmlspecialchars($elemento['nome']); ?></div>
                                <div class="remove-item-type"><?php echo ucfirst(str_replace('_', ' ', $elemento['tipo'])); ?></div>
                            </div>
                            <button class="btn btn-danger" 
                                    onclick="confirmRemove(<?php echo $elemento['id']; ?>, '<?php echo $elemento['tipo']; ?>', '<?php echo htmlspecialchars($elemento['nome']); ?>')">
                                Rimuovi
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- Modal per la Ruota della Fortuna -->
    <div id="spinWheelModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 class="modal-title">🎯 Ruota della Fortuna - Serie Mancanti</h2>
            <div class="wheel-container">
                <div id="wheel" class="wheel"></div>
            </div>
            <button id="spinBtn" class="btn" onclick="spinWheel()">Gira la Ruota!</button>
            <div id="spinResult"></div>
        </div>
    </div>

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

    <script src="../js/script.js?version=3"></script>
    <script>
        // Enhanced form handling
        document.addEventListener("DOMContentLoaded", function() {
            const tipoSelect = document.getElementById("tipo");
            
            if (tipoSelect) {
                tipoSelect.addEventListener("change", handleTipoChange);
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

<?php
// Funzione helper per determinare il tipo di item
function getItemType($item) {
    // Controlla se l'array contiene chiavi specifiche per determinare il tipo
    if (isset($item['volumi_totali'])) return 'serie';
    if (isset($item['costo_medio'])) return 'variant';
    if (isset($item['codice'])) return 'numeri_yugioh';
    if (isset($item['is_box'])) return 'duel_masters';
    
    // Controlla dalla tabella di origine se disponibile
    global $pdo;
    $tables = ['funko_pop', 'monster', 'artbooks_anime', 'gameboys', 'pokemon_game'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SELECT id FROM $table WHERE id = ? LIMIT 1");
        $stmt->execute([$item['id']]);
        if ($stmt->fetch()) {
            return $table;
        }
    }
    
    return 'unknown';
}
?>