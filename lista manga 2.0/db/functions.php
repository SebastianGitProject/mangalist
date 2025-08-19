<?php
require_once 'config.php';

// Funzioni per le serie manga
function getSerieCollezione() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM serie_manga WHERE volumi_posseduti > 0 ORDER BY titolo");
    $stmt->execute();
    return $stmt->fetchAll();
}

function getSerieMancanti() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM serie_manga WHERE volumi_posseduti = 0 ORDER BY titolo");
    $stmt->execute();
    return $stmt->fetchAll();
}

function getSerieById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM serie_manga WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Funzioni per le variant
function getVariantCollezione() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM variant_manga WHERE posseduto = 1 ORDER BY titolo");
    $stmt->execute();
    return $stmt->fetchAll();
}

function getVariantMancanti() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM variant_manga WHERE posseduto = 0 ORDER BY titolo");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Funzioni per aggiungere elementi
function addSerie($titolo, $immagine_url, $data_pubblicazione, $volumi_totali, $volumi_posseduti) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO serie_manga (titolo, immagine_url, data_pubblicazione, volumi_totali, volumi_posseduti) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$titolo, $immagine_url, $data_pubblicazione, $volumi_totali, $volumi_posseduti]);
        return true;
    } catch(PDOException $e) {
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

// Funzioni per aggiornare
function updateSerie($id, $titolo, $immagine_url, $data_pubblicazione, $volumi_totali, $volumi_posseduti) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE serie_manga SET titolo = ?, immagine_url = ?, data_pubblicazione = ?, volumi_totali = ?, volumi_posseduti = ? WHERE id = ?");
        $stmt->execute([$titolo, $immagine_url, $data_pubblicazione, $volumi_totali, $volumi_posseduti, $id]);
        return true;
    } catch(PDOException $e) {
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
?>