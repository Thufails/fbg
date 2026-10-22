<?php
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// === URL WEBHOOK BARU DARI N8N ===
$n8nWebhookUrl = 'https://n8n.srv1036161.hstgr.cloud/webhook/acf41d80-991b-4516-8277-badc72181e0d';
// =================================

function debugLog($message) {
    // Simple debug logging
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' - ' . $message . "\n", FILE_APPEND);
}

function sendToN8N($data) {
    global $n8nWebhookUrl;
    
    debugLog("Sending to n8n: " . json_encode($data));
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $n8nWebhookUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'User-Agent: PHP-Webhook-Client/1.0'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 10
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    debugLog("n8n Response - Code: $httpCode, Error: $error, Response: $response");
    
    curl_close($ch);
    
    return [
        'status_code' => $httpCode,
        'body' => $response,
        'error' => $error
    ];
}

try {
    // Get input data
    $inputData = file_get_contents('php://input');
    debugLog("Received input: " . $inputData);
    
    $input = json_decode($inputData, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }
    
    if (!$input) {
        throw new Exception('No data received');
    }
    
    // Basic validation
    $errors = [];
    if (empty($input['start_date'])) $errors[] = 'start_date is required';
    if (empty($input['end_date'])) $errors[] = 'end_date is required';
    
    if (!empty($errors)) {
        throw new Exception('Validation errors: ' . implode(', ', $errors));
    }
    
    // Prepare simple data
    $webhookData = [
        'request_id' => uniqid('DATE_'),
        'timestamp' => date('c'),
        'start_date' => $input['start_date'],
        'end_date' => $input['end_date'],
        'action_type' => $input['action_type'] ?? 'update_data',
        'notes' => $input['notes'] ?? '',
        'source' => 'web_form'
    ];
    
    debugLog("Prepared webhook data: " . json_encode($webhookData));
    
    // Send to n8n
    $result = sendToN8N($webhookData);
    
    // Handle response
    if ($result['status_code'] === 200) {
        $responseBody = $result['body'];
        $n8nResponse = json_decode($responseBody, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            echo json_encode([
                'success' => true,
                'message' => 'Data berhasil dikirim dan diproses oleh n8n!',
                'request_id' => $webhookData['request_id'],
                'n8n_response' => $n8nResponse
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'Data berhasil dikirim ke n8n!',
                'request_id' => $webhookData['request_id'],
                'raw_response' => $responseBody
            ]);
        }
        
    } else {
        throw new Exception('n8n returned error ' . $result['status_code'] . ': ' . $result['body']);
    }
    
} catch (Exception $e) {
    debugLog("Exception: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>