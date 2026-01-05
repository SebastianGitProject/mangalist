<?php
require_once 'functions.php';

header('Content-Type: application/json');

// Gestione sia GET che POST per le azioni
$action = $_GET['action'] ?? $_POST['action'] ?? null;

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'Azione non specificata']);
    exit;
}

switch ($action) {
    case 'getSerie':
        if (!isset($_GET['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID non specificato']);
            exit;
        }
        
        $serie = getSerieById($_GET['id']);
        if ($serie) {
            // Ottieni anche i volumi posseduti
            $volumi = getVolumiPosseduti($_GET['id']);
            $serie['volumi_dettagli'] = $volumi;
            echo json_encode(['success' => true, 'serie' => $serie]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Serie non trovata']);
        }
        break;
    
    case 'getVariant':
        if (!isset($_GET['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID non specificato']);
            exit;
        }
        
        $variant = getVariantById($_GET['id']);
        if ($variant) {
            echo json_encode(['success' => true, 'variant' => $variant]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Variant non trovata']);
        }
        break;
    
    case 'updateVolumi':
        if (!isset($_POST['serie_id']) || !isset($_POST['volumi'])) {
            echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
            exit;
        }
        
        $serie_id = (int)$_POST['serie_id'];
        $volumi_posseduti = json_decode($_POST['volumi'], true);
        
        if (updateVolumiPosseduti($serie_id, $volumi_posseduti)) {
            echo json_encode(['success' => true, 'message' => 'Volumi aggiornati con successo']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Errore nell\'aggiornamento dei volumi']);
        }
        break;
    
    case 'updateSerie':
        if (!isset($_POST['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID non specificato']);
            exit;
        }
        
        $id = (int)$_POST['id'];
        $titolo = sanitizeInput($_POST['titolo']);
        $immagine_url = sanitizeInput($_POST['immagine_url']);
        $data_pubblicazione = sanitizeInput($_POST['data_pubblicazione']);
        $volumi_totali = (int)$_POST['volumi_totali'];
        $prezzo_medio = (float)$_POST['prezzo_medio'];
        
        if (updateSerie($id, $titolo, $immagine_url, $data_pubblicazione, $volumi_totali, $prezzo_medio)) {
            echo json_encode(['success' => true, 'message' => 'Serie aggiornata con successo']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Errore nell\'aggiornamento della serie']);
        }
        break;
    
    case 'updateVariant':
        if (!isset($_POST['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID non specificato']);
            exit;
        }
        
        $id = (int)$_POST['id'];
        $titolo = sanitizeInput($_POST['titolo']);
        $immagine_url = sanitizeInput($_POST['immagine_url']);
        $data_rilascio = sanitizeInput($_POST['data_rilascio']);
        $costo_medio = (float)$_POST['costo_medio'];
        $posseduto = isset($_POST['posseduto']) ? 1 : 0;
        
        if (updateVariant($id, $titolo, $immagine_url, $data_rilascio, $costo_medio, $posseduto)) {
            echo json_encode(['success' => true, 'message' => 'Variant aggiornata con successo']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Errore nell\'aggiornamento della variant']);
        }
        break;
    
    case 'search':
        if (!isset($_GET['query'])) {
            echo json_encode(['success' => false, 'message' => 'Query di ricerca non specificata']);
            exit;
        }
        
        $results = searchItems($_GET['query']);
        echo json_encode(['success' => true, 'results' => $results]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Azione non valida: ' . $action]);
        break;
}
?>