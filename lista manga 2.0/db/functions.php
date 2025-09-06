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
        
        // Prima ottieni il numero totale di volumi per questa serie
        $stmt = $pdo->prepare("SELECT volumi_totali FROM serie_manga WHERE id = ?");
        $stmt->execute([$serie_id]);
        $serie = $stmt->fetch();
        
        if (!$serie) {
            throw new Exception("Serie non trovata");
        }
        
        // Elimina i record esistenti per questa serie
        $stmt = $pdo->prepare("DELETE FROM volumi_posseduti WHERE serie_id = ?");
        $stmt->execute([$serie_id]);
        
        // Inserisci tutti i volumi (posseduti e non)
        for ($i = 1; $i <= $serie['volumi_totali']; $i++) {
            $posseduto = in_array($i, $volumi_array) ? 1 : 0;
            $stmt = $pdo->prepare("INSERT INTO volumi_posseduti (serie_id, numero_volume, posseduto) VALUES (?, ?, ?)");
            $stmt->execute([$serie_id, $i, $posseduto]);
        }
        
        // Aggiorna il conteggio nella tabella serie_manga
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

// Funzioni per aggiungere elementi aggiornate
function addSerie($titolo, $immagine_url, $data_pubblicazione, $volumi_totali, $volumi_posseduti, $prezzo_medio = 0.00) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO serie_manga (titolo, immagine_url, data_pubblicazione, volumi_totali, volumi_posseduti, prezzo_medio) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$titolo, $immagine_url, $data_pubblicazione, $volumi_totali, $volumi_posseduti, $prezzo_medio]);
        
        $serie_id = $pdo->lastInsertId();
        
        // Crea i record per i volumi individuali
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
        // I volumi_posseduti verranno eliminati automaticamente per via del CASCADE
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

// Funzioni per aggiornare elementi
function updateSerie($id, $titolo, $immagine_url, $data_pubblicazione, $volumi_totali, $prezzo_medio) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        // Aggiorna la serie
        $stmt = $pdo->prepare("UPDATE serie_manga SET titolo = ?, immagine_url = ?, data_pubblicazione = ?, volumi_totali = ?, prezzo_medio = ? WHERE id = ?");
        $stmt->execute([$titolo, $immagine_url, $data_pubblicazione, $volumi_totali, $prezzo_medio, $id]);
        
        // Se il numero totale di volumi è cambiato, aggiorna la tabella volumi_posseduti
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM volumi_posseduti WHERE serie_id = ?");
        $stmt->execute([$id]);
        $current_count = $stmt->fetch()['count'];
        
        if ($current_count != $volumi_totali) {
            // Elimina tutti i record esistenti
            $stmt = $pdo->prepare("DELETE FROM volumi_posseduti WHERE serie_id = ?");
            $stmt->execute([$id]);
            
            // Ricrea i record per tutti i volumi
            for ($i = 1; $i <= $volumi_totali; $i++) {
                $stmt = $pdo->prepare("INSERT INTO volumi_posseduti (serie_id, numero_volume, posseduto) VALUES (?, ?, ?)");
                $stmt->execute([$id, $i, 0]); // Inizialmente non posseduti
            }
            
            // Aggiorna il conteggio nella serie
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
    $stmt = $pdo->prepare("SELECT id, titolo, 'serie' as tipo FROM serie_manga ORDER BY titolo");
    $stmt->execute();
    $serie = $stmt->fetchAll();
    
    // Variant
    $stmt = $pdo->prepare("SELECT id, titolo, 'variant' as tipo FROM variant_manga ORDER BY titolo");
    $stmt->execute();
    $variant = $stmt->fetchAll();
    
    return array_merge($serie, $variant);
}

// Funzione di ricerca globale
function searchItems($query) {
    global $pdo;
    $items = [];
    $searchTerm = "%$query%";
    
    // Cerca nelle serie manga
    $stmt = $pdo->prepare("SELECT id, titolo, 'serie' as tipo, immagine_url, data_pubblicazione as data FROM serie_manga WHERE titolo LIKE ? ORDER BY titolo");
    $stmt->execute([$searchTerm]);
    $serie = $stmt->fetchAll();
    
    // Cerca nelle variant
    $stmt = $pdo->prepare("SELECT id, titolo, 'variant' as tipo, immagine_url, data_rilascio as data FROM variant_manga WHERE titolo LIKE ? ORDER BY titolo");
    $stmt->execute([$searchTerm]);
    $variant = $stmt->fetchAll();
    
    return array_merge($serie, $variant);
}
?>