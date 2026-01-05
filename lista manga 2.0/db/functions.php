<?php
require_once 'config.php';

// Funzioni per le serie manga con nuove funzionalità
function getSerieCollezione($orderBy = 'titolo', $search = '') {
    global $pdo;
    
    $whereClause = "WHERE sm.volumi_posseduti > 0";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND sm.titolo LIKE ?";
        $params[] = "%$search%";
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY sm.prezzo_medio ASC, sm.titolo ASC",
        'prezzo_desc' => "ORDER BY sm.prezzo_medio DESC, sm.titolo ASC",
        default => "ORDER BY sm.titolo ASC"
    };
    
    $sql = "SELECT sm.*, 
                   COALESCE(vp.volumi_posseduti_count, 0) as volumi_posseduti_actual
            FROM serie_manga sm
            LEFT JOIN (
                SELECT serie_id, COUNT(*) as volumi_posseduti_count
                FROM volumi_posseduti 
                WHERE posseduto = TRUE 
                GROUP BY serie_id
            ) vp ON sm.id = vp.serie_id
            $whereClause
            $orderClause";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getSerieMancanti($orderBy = 'titolo', $search = '') {
    global $pdo;
    
    $whereClause = "WHERE sm.volumi_posseduti = 0";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND sm.titolo LIKE ?";
        $params[] = "%$search%";
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY sm.prezzo_medio ASC, sm.titolo ASC",
        'prezzo_desc' => "ORDER BY sm.prezzo_medio DESC, sm.titolo ASC",
        default => "ORDER BY sm.titolo ASC"
    };
    
    $sql = "SELECT * FROM serie_manga sm $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getVariantCollezione($orderBy = 'titolo', $search = '') {
    global $pdo;
    
    $whereClause = "WHERE posseduto = 1";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND titolo LIKE ?";
        $params[] = "%$search%";
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY costo_medio ASC, titolo ASC",
        'prezzo_desc' => "ORDER BY costo_medio DESC, titolo ASC",
        default => "ORDER BY titolo ASC"
    };
    
    $sql = "SELECT * FROM variant_manga $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getVariantMancanti($orderBy = 'titolo', $search = '') {
    global $pdo;
    
    $whereClause = "WHERE posseduto = 0";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND titolo LIKE ?";
        $params[] = "%$search%";
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY costo_medio ASC, titolo ASC",
        'prezzo_desc' => "ORDER BY costo_medio DESC, titolo ASC",
        default => "ORDER BY titolo ASC"
    };
    
    $sql = "SELECT * FROM variant_manga $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getSerieById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM serie_manga WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getVariantById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM variant_manga WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Funzioni per i volumi individuali
function getVolumiPosseduti($serie_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM volumi_posseduti WHERE serie_id = ? ORDER BY numero_volume");
    $stmt->execute([$serie_id]);
    return $stmt->fetchAll();
}

function updateVolumiPosseduti($serie_id, $volumi_array) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT volumi_totali FROM serie_manga WHERE id = ?");
        $stmt->execute([$serie_id]);
        $serie = $stmt->fetch();
        
        if (!$serie) {
            throw new Exception("Serie non trovata");
        }
        
        $stmt = $pdo->prepare("DELETE FROM volumi_posseduti WHERE serie_id = ?");
        $stmt->execute([$serie_id]);
        
        for ($i = 1; $i <= $serie['volumi_totali']; $i++) {
            $posseduto = in_array($i, $volumi_array) ? 1 : 0;
            $stmt = $pdo->prepare("INSERT INTO volumi_posseduti (serie_id, numero_volume, posseduto) VALUES (?, ?, ?)");
            $stmt->execute([$serie_id, $i, $posseduto]);
        }
        
        $volumi_posseduti_count = count($volumi_array);
        $stmt = $pdo->prepare("UPDATE serie_manga SET volumi_posseduti = ? WHERE id = ?");
        $stmt->execute([$volumi_posseduti_count, $serie_id]);
        
        $pdo->commit();
        return true;
    } catch(Exception $e) {
        $pdo->rollback();
        return false;
    }
}

// NUOVE FUNZIONI PER LE SEZIONI AGGIUNTIVE

// Funzioni per Funko Pop
function getFunkoPop($orderBy = 'nome', $search = '', $posseduto = null) {
    global $pdo;
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND nome LIKE ?";
        $params[] = "%$search%";
    }
    
    if ($posseduto !== null) {
        $whereClause .= " AND posseduto = ?";
        $params[] = $posseduto;
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY prezzo ASC, nome ASC",
        'prezzo_desc' => "ORDER BY prezzo DESC, nome ASC",
        default => "ORDER BY nome ASC"
    };
    
    $sql = "SELECT * FROM funko_pop $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function addFunkoPop($nome, $immagine_url, $data_pubblicazione, $prezzo, $posseduto) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO funko_pop (nome, immagine_url, data_pubblicazione, prezzo, posseduto) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $immagine_url, $data_pubblicazione, $prezzo, $posseduto]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Funzioni per Monster
function getMonster($orderBy = 'nome', $search = '', $posseduto = null) {
    global $pdo;
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND nome LIKE ?";
        $params[] = "%$search%";
    }
    
    if ($posseduto !== null) {
        $whereClause .= " AND posseduto = ?";
        $params[] = $posseduto;
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY prezzo ASC, nome ASC",
        'prezzo_desc' => "ORDER BY prezzo DESC, nome ASC",
        default => "ORDER BY nome ASC"
    };
    
    $sql = "SELECT * FROM monster $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function addMonster($nome, $immagine_url, $data_pubblicazione, $prezzo, $posseduto) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO monster (nome, immagine_url, data_pubblicazione, prezzo, posseduto) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $immagine_url, $data_pubblicazione, $prezzo, $posseduto]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Funzioni per Artbooks Anime
function getArtbooksAnime($orderBy = 'nome', $search = '', $posseduto = null) {
    global $pdo;
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND nome LIKE ?";
        $params[] = "%$search%";
    }
    
    if ($posseduto !== null) {
        $whereClause .= " AND posseduto = ?";
        $params[] = $posseduto;
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY prezzo ASC, nome ASC",
        'prezzo_desc' => "ORDER BY prezzo DESC, nome ASC",
        default => "ORDER BY nome ASC"
    };
    
    $sql = "SELECT * FROM artbooks_anime $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function addArtbooksAnime($nome, $immagine_url, $data_pubblicazione, $prezzo, $posseduto) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO artbooks_anime (nome, immagine_url, data_pubblicazione, prezzo, posseduto) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $immagine_url, $data_pubblicazione, $prezzo, $posseduto]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Funzioni per Gameboys
function getGameboys($orderBy = 'nome', $search = '', $posseduto = null) {
    global $pdo;
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND nome LIKE ?";
        $params[] = "%$search%";
    }
    
    if ($posseduto !== null) {
        $whereClause .= " AND posseduto = ?";
        $params[] = $posseduto;
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY prezzo ASC, nome ASC",
        'prezzo_desc' => "ORDER BY prezzo DESC, nome ASC",
        default => "ORDER BY nome ASC"
    };
    
    $sql = "SELECT * FROM gameboys $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function addGameboys($nome, $immagine_url, $data_pubblicazione, $prezzo, $links, $posseduto) {
    global $pdo;
    try {
        $links_json = json_encode($links);
        $stmt = $pdo->prepare("INSERT INTO gameboys (nome, immagine_url, data_pubblicazione, prezzo, links, posseduto) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $immagine_url, $data_pubblicazione, $prezzo, $links_json, $posseduto]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Funzioni per Pokemon Game
function getPokemonGame($orderBy = 'nome', $search = '', $posseduto = null) {
    global $pdo;
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND nome LIKE ?";
        $params[] = "%$search%";
    }
    
    if ($posseduto !== null) {
        $whereClause .= " AND posseduto = ?";
        $params[] = $posseduto;
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY prezzo ASC, nome ASC",
        'prezzo_desc' => "ORDER BY prezzo DESC, nome ASC",
        default => "ORDER BY nome ASC"
    };
    
    $sql = "SELECT * FROM pokemon_game $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function addPokemonGame($nome, $immagine_url, $data_pubblicazione, $prezzo, $links, $posseduto) {
    global $pdo;
    try {
        $links_json = json_encode($links);
        $stmt = $pdo->prepare("INSERT INTO pokemon_game (nome, immagine_url, data_pubblicazione, prezzo, links, posseduto) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $immagine_url, $data_pubblicazione, $prezzo, $links_json, $posseduto]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Funzioni per Numeri Yu-Gi-Oh
function getNumeriYugioh($orderBy = 'nome', $search = '', $posseduto = null) {
    global $pdo;
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND (nome LIKE ? OR codice LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($posseduto !== null) {
        $whereClause .= " AND posseduto = ?";
        $params[] = $posseduto;
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY prezzo ASC, nome ASC",
        'prezzo_desc' => "ORDER BY prezzo DESC, nome ASC",
        default => "ORDER BY nome ASC"
    };
    
    $sql = "SELECT * FROM numeri_yugioh $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function addNumeriYugioh($nome, $codice, $immagine_url, $data_pubblicazione, $prezzo, $posseduto) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO numeri_yugioh (nome, codice, immagine_url, data_pubblicazione, prezzo, posseduto) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $codice, $immagine_url, $data_pubblicazione, $prezzo, $posseduto]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Funzioni per Duel Masters
function getDuelMasters($orderBy = 'nome', $search = '', $posseduto = null) {
    global $pdo;
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND nome LIKE ?";
        $params[] = "%$search%";
    }
    
    if ($posseduto !== null) {
        $whereClause .= " AND posseduto = ?";
        $params[] = $posseduto;
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY prezzo ASC, nome ASC",
        'prezzo_desc' => "ORDER BY prezzo DESC, nome ASC",
        default => "ORDER BY nome ASC"
    };
    
    $sql = "SELECT * FROM duel_masters $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function addDuelMasters($nome, $immagine_url, $data_pubblicazione, $prezzo, $is_box, $posseduto) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO duel_masters (nome, immagine_url, data_pubblicazione, prezzo, is_box, posseduto) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $immagine_url, $data_pubblicazione, $prezzo, $is_box, $posseduto]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Funzioni per aggiungere elementi aggiornate
function addSerie($titolo, $immagine_url, $data_pubblicazione, $volumi_totali, $volumi_posseduti, $prezzo_medio = 0.00) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO serie_manga (titolo, immagine_url, data_pubblicazione, volumi_totali, volumi_posseduti, prezzo_medio) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$titolo, $immagine_url, $data_pubblicazione, $volumi_totali, $volumi_posseduti, $prezzo_medio]);
        
        $serie_id = $pdo->lastInsertId();
        
        for ($i = 1; $i <= $volumi_totali; $i++) {
            $posseduto = ($i <= $volumi_posseduti) ? 1 : 0;
            $stmt = $pdo->prepare("INSERT INTO volumi_posseduti (serie_id, numero_volume, posseduto) VALUES (?, ?, ?)");
            $stmt->execute([$serie_id, $i, $posseduto]);
        }
        
        $pdo->commit();
        return true;
    } catch(PDOException $e) {
        $pdo->rollback();
        return false;
    }
}

function addVariant($titolo, $immagine_url, $data_rilascio, $costo_medio, $posseduto) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO variant_manga (titolo, immagine_url, data_rilascio, costo_medio, posseduto) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$titolo, $immagine_url, $data_rilascio, $costo_medio, $posseduto]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Funzioni per rimuovere elementi
function removeSerie($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM serie_manga WHERE id = ?");
        $stmt->execute([$id]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

function removeVariant($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM variant_manga WHERE id = ?");
        $stmt->execute([$id]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Funzioni generiche di rimozione per le nuove sezioni
function removeByTable($table, $id) {
    global $pdo;
    try {
        $allowedTables = ['funko_pop', 'monster', 'artbooks_anime', 'gameboys', 'pokemon_game', 'numeri_yugioh', 'duel_masters'];
        if (!in_array($table, $allowedTables)) {
            return false;
        }
        
        $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Funzioni per aggiornare elementi
function updateSerie($id, $titolo, $immagine_url, $data_pubblicazione, $volumi_totali, $prezzo_medio) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("UPDATE serie_manga SET titolo = ?, immagine_url = ?, data_pubblicazione = ?, volumi_totali = ?, prezzo_medio = ? WHERE id = ?");
        $stmt->execute([$titolo, $immagine_url, $data_pubblicazione, $volumi_totali, $prezzo_medio, $id]);
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM volumi_posseduti WHERE serie_id = ?");
        $stmt->execute([$id]);
        $current_count = $stmt->fetch()['count'];
        
        if ($current_count != $volumi_totali) {
            $stmt = $pdo->prepare("DELETE FROM volumi_posseduti WHERE serie_id = ?");
            $stmt->execute([$id]);
            
            for ($i = 1; $i <= $volumi_totali; $i++) {
                $stmt = $pdo->prepare("INSERT INTO volumi_posseduti (serie_id, numero_volume, posseduto) VALUES (?, ?, ?)");
                $stmt->execute([$id, $i, 0]);
            }
            
            $stmt = $pdo->prepare("UPDATE serie_manga SET volumi_posseduti = 0 WHERE id = ?");
            $stmt->execute([$id]);
        }
        
        $pdo->commit();
        return true;
    } catch(PDOException $e) {
        $pdo->rollback();
        return false;
    }
}

function updateVariant($id, $titolo, $immagine_url, $data_rilascio, $costo_medio, $posseduto) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE variant_manga SET titolo = ?, immagine_url = ?, data_rilascio = ?, costo_medio = ?, posseduto = ? WHERE id = ?");
        $stmt->execute([$titolo, $immagine_url, $data_rilascio, $costo_medio, $posseduto, $id]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Funzione per ottenere tutti gli elementi per la rimozione
function getAllItems() {
    global $pdo;
    $items = [];
    
    // Serie manga
    $stmt = $pdo->prepare("SELECT id, titolo as nome, 'serie' as tipo FROM serie_manga ORDER BY titolo");
    $stmt->execute();
    $items = array_merge($items, $stmt->fetchAll());
    
    // Variant
    $stmt = $pdo->prepare("SELECT id, titolo as nome, 'variant' as tipo FROM variant_manga ORDER BY titolo");
    $stmt->execute();
    $items = array_merge($items, $stmt->fetchAll());
    
    // Nuove sezioni
    $tables = [
        'funko_pop' => 'Funko Pop',
        'monster' => 'Monster',
        'artbooks_anime' => 'Artbooks Anime',
        'gameboys' => 'Gameboys',
        'pokemon_game' => 'Pokemon Game',
        'numeri_yugioh' => 'Numeri Yu-Gi-Oh',
        'duel_masters' => 'Duel Masters'
    ];
    
    foreach ($tables as $table => $tipo) {
        $stmt = $pdo->prepare("SELECT id, nome, '$table' as tipo FROM $table ORDER BY nome");
        $stmt->execute();
        $results = $stmt->fetchAll();
        foreach ($results as $result) {
            $result['tipo_display'] = $tipo;
            $items[] = $result;
        }
    }
    
    return $items;
}

// Funzione di ricerca globale
function searchItems($query) {
    global $pdo;
    $items = [];
    $searchTerm = "%$query%";
    
    // Cerca nelle serie manga
    $stmt = $pdo->prepare("SELECT id, titolo as nome, 'serie' as tipo, immagine_url, data_pubblicazione as data FROM serie_manga WHERE titolo LIKE ? ORDER BY titolo");
    $stmt->execute([$searchTerm]);
    $items = array_merge($items, $stmt->fetchAll());
    
    // Cerca nelle variant
    $stmt = $pdo->prepare("SELECT id, titolo as nome, 'variant' as tipo, immagine_url, data_rilascio as data FROM variant_manga WHERE titolo LIKE ? ORDER BY titolo");
    $stmt->execute([$searchTerm]);
    $items = array_merge($items, $stmt->fetchAll());
    
    // Cerca nelle nuove sezioni
    $tables = [
        'funko_pop' => 'funko_pop',
        'monster' => 'monster', 
        'artbooks_anime' => 'artbooks_anime',
        'gameboys' => 'gameboys',
        'pokemon_game' => 'pokemon_game',
        'numeri_yugioh' => 'numeri_yugioh',
        'duel_masters' => 'duel_masters'
    ];
    
    foreach ($tables as $table => $tipo) {
        if ($table === 'numeri_yugioh') {
            $stmt = $pdo->prepare("SELECT id, nome, '$tipo' as tipo, immagine_url, data_pubblicazione as data, codice FROM $table WHERE nome LIKE ? OR codice LIKE ? ORDER BY nome");
            $stmt->execute([$searchTerm, $searchTerm]);
        } else {
            $stmt = $pdo->prepare("SELECT id, nome, '$tipo' as tipo, immagine_url, data_pubblicazione as data FROM $table WHERE nome LIKE ? ORDER BY nome");
            $stmt->execute([$searchTerm]);
        }
        $items = array_merge($items, $stmt->fetchAll());
    }
    
    return $items;
}

// Funzione per ottenere una serie casuale per la ruota della fortuna
function getRandomSerieMancante() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM serie_manga WHERE volumi_posseduti = 0 ORDER BY RAND() LIMIT 1");
        $stmt->execute();
        return $stmt->fetch();
    } catch(PDOException $e) {
        return false;
    }
}

// Funzione per contare serie mancanti
function countSerieMancanti() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM serie_manga WHERE volumi_posseduti = 0");
    $stmt->execute();
    return $stmt->fetch()['count'];
}

// Funzione per contare numeri Yu-Gi-Oh mancanti
function countYugiohMancanti() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM numeri_yugioh WHERE posseduto = 0");
    $stmt->execute();
    return $stmt->fetch()['count'];
}

// Funzione generica per ottenere elementi da una tabella specifica
function getItemById($table, $id) {
    global $pdo;
    $allowedTables = ['funko_pop', 'monster', 'artbooks_anime', 'gameboys', 'pokemon_game', 'numeri_yugioh', 'duel_masters'];
    if (!in_array($table, $allowedTables)) {
        return false;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}
?>