<?php
require_once 'functions.php';

header('Content-Type: application/json');

if (!isset($_GET['action'])) {
    echo json_encode(['success' => false, 'message' => 'Azione non specificata']);
    exit;
}

switch ($_GET['action']) {
    case 'getSerie':
        if (!isset($_GET['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID non specificato']);
            exit;
        }
        
        $serie = getSerieById($_GET['id']);
        if ($serie) {
            echo json_encode(['success' => true, 'serie' => $serie]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Serie non trovata']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Azione non valida']);
        break;
}
?>