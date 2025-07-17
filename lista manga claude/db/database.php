<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "listamanga";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Errore connessione: " . $e->getMessage());
}

// Creazione database e tabelle se non esistono
$sql = "
DROP DATABASE IF EXISTS listamanga;
CREATE DATABASE IF NOT EXISTS listamanga;
USE listamanga;

CREATE TABLE mymanga (
    id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    titolo VARCHAR(255) NOT NULL UNIQUE,
    num_vol_tot INT NOT NULL,
    num_vol_poss TEXT NOT NULL,
    num_vol_mancanti TEXT NOT NULL,
    url_foto VARCHAR(255) NOT NULL,
    completato BOOLEAN NOT NULL
);

CREATE TABLE mangamancanti (
    id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    titolo VARCHAR(255) NOT NULL UNIQUE,
    num_vol_tot INT NOT NULL,
    url_foto VARCHAR(255) NOT NULL
);

CREATE TABLE variant (
    id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    titolo VARCHAR(255) NOT NULL UNIQUE,
    data_rilascio TEXT NOT NULL,
    costo DECIMAL(10,2) NOT NULL,
    url_foto VARCHAR(255) NOT NULL,
    posseduto BOOLEAN NOT NULL
);
";

// Funzioni per la gestione dei dati
function getAllManga() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM mymanga ORDER BY titolo");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getMangaMancanti() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM mangamancanti ORDER BY titolo");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getVariant() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM variant ORDER BY titolo");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addManga($titolo, $num_vol_tot, $num_vol_poss, $url_foto) {
    global $pdo;
    
    $vol_poss_array = explode(',', $num_vol_poss);
    $vol_mancanti = [];
    
    for ($i = 1; $i <= $num_vol_tot; $i++) {
        if (!in_array($i, $vol_poss_array)) {
            $vol_mancanti[] = $i;
        }
    }
    
    $completato = (count($vol_poss_array) == $num_vol_tot) ? 1 : 0;
    $vol_mancanti_str = implode(',', $vol_mancanti);
    
    $stmt = $pdo->prepare("INSERT INTO mymanga (titolo, num_vol_tot, num_vol_poss, num_vol_mancanti, url_foto, completato) VALUES (?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$titolo, $num_vol_tot, $num_vol_poss, $vol_mancanti_str, $url_foto, $completato]);
}

function addMangaMancante($titolo, $num_vol_tot, $url_foto) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO mangamancanti (titolo, num_vol_tot, url_foto) VALUES (?, ?, ?)");
    return $stmt->execute([$titolo, $num_vol_tot, $url_foto]);
}

function addVariant($titolo, $data_rilascio, $costo, $url_foto, $posseduto) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO variant (titolo, data_rilascio, costo, url_foto, posseduto) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$titolo, $data_rilascio, $costo, $url_foto, $posseduto]);
}

function deleteManga($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM mymanga WHERE id = ?");
    return $stmt->execute([$id]);
}

function deleteMangaMancante($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM mangamancanti WHERE id = ?");
    return $stmt->execute([$id]);
}

function deleteVariant($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM variant WHERE id = ?");
    return $stmt->execute([$id]);
}

function updateMangaVolumes($id, $num_vol_poss) {
    global $pdo;
    
    // Ottieni i dati attuali del manga
    $stmt = $pdo->prepare("SELECT * FROM mymanga WHERE id = ?");
    $stmt->execute([$id]);
    $manga = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$manga) return false;
    
    $vol_poss_array = explode(',', $num_vol_poss);
    $vol_mancanti = [];
    
    for ($i = 1; $i <= $manga['num_vol_tot']; $i++) {
        if (!in_array($i, $vol_poss_array)) {
            $vol_mancanti[] = $i;
        }
    }
    
    $completato = (count($vol_poss_array) == $manga['num_vol_tot']) ? 1 : 0;
    $vol_mancanti_str = implode(',', $vol_mancanti);
    
    // Se non possiede più nessun volume, sposta a mangamancanti
    if (empty($num_vol_poss)) {
        addMangaMancante($manga['titolo'], $manga['num_vol_tot'], $manga['url_foto']);
        deleteManga($id);
        return true;
    }
    
    $stmt = $pdo->prepare("UPDATE mymanga SET num_vol_poss = ?, num_vol_mancanti = ?, completato = ? WHERE id = ?");
    return $stmt->execute([$num_vol_poss, $vol_mancanti_str, $completato, $id]);
}

function moveMangaToCollection($id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM mangamancanti WHERE id = ?");
    $stmt->execute([$id]);
    $manga = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$manga) return false;
    
    // Sposta a mymanga con 0 volumi posseduti
    addManga($manga['titolo'], $manga['num_vol_tot'], '', $manga['url_foto']);
    deleteMangaMancante($id);
    
    return true;
}

function updateVariantPosseduto($id, $posseduto) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE variant SET posseduto = ? WHERE id = ?");
    return $stmt->execute([$posseduto, $id]);
}
?>