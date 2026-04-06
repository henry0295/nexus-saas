<?php

// Debug logger endpoint
header('Content-Type: application/json');

try {
    $errorFile = '/app/storage/logs/current-error.log';
    if (file_exists($errorFile)) {
        echo json_encode([
            'error' => trim(file_get_contents($errorFile)),
            'exists' => true
        ]);
    } else {
        echo json_encode(['error' => 'No error log found', 'exists' => false]);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
