<?php
header('Content-Type: application/json');

// === URL WEBHOOK N8N ANDA ===
$n8nWebhookUrl = 'https://n8n.srv1036161.hstgr.cloud/webhook/62c61460-492e-41af-8fbe-9d9b5bf29d47';
// ============================

function validateInput($input) {
    $required = ['start_date', 'end_date', 'action_type'];
    
    foreach ($required as $field) {
        if (empty($input[$field])) {
            return ['valid' => false, 'message' => "Field $field harus diisi"];
        }
    }
    
    if (!strtotime($input['start_date']) || !strtotime($input['end_date'])) {
        return ['valid' => false, 'message' => 'Format tanggal tidak valid'];
    }
    
    if (strtotime($input['start_date']) > strtotime($input['end_date'])) {
        return ['valid' => false, 'message' => 'Tanggal awal tidak boleh lebih besar dari tanggal akhir'];
    }
    
    return ['valid' => true];
}

function calculateDays($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    return $end->diff($start)->days + 1;
}

function sendToN8N($data) {
    global $n8nWebhookUrl;
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", [
                'Content-Type: 'application/json',
                'X-Webhook-Source: PHP-Date-Form'
            ]),
            'content' => json_encode($data),
            'timeout' => 30,
            'ignore_errors' => true
        ]
    ]);
    
    $response = file_get_contents($n8nWebhookUrl, false, $context);
    
    preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
    $statusCode = $matches[1] ?? 500;
    
    return [
        'status_code' => (int)$statusCode,
        'body' => $response
    ];
}

// Main process
try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Data input tidak valid');
    }
    
    $validation = validateInput($input);
    if (!$validation['valid']) {
        throw new Exception($validation['message']);
    }
    
    $webhookData = [
        'request_id' => uniqid('DATE_'),
        'timestamp' => date('c'),
        'action' => 'update_google_sheet',
        'date_range' => [
            'start_date' => $input['start_date'],
            'end_date' => $input['end_date'],
            'days_count' => calculateDays($input['start_date'], $input['end_date'])
        ],
        'action_type' => $input['action_type'],
        'notes' => $input['notes'] ?? '',
        'metadata' => [
            'source' => $input['source'] ?? 'web_form',
            'submitted_at' => $input['submitted_at'] ?? date('c')
        ]
    ];
    
    $result = sendToN8N($webhookData);
    
    if ($result['status_code'] >= 200 && $result['status_code'] < 300) {
        echo json_encode([
            'success' => true,
            'message' => 'Data berhasil dikirim ke n8n! Proses update Google Sheet sedang berjalan.',
            'request_id' => $webhookData['request_id']
        ]);
    } else {
        throw new Exception('Gagal mengirim data ke n8n. Status: ' . $result['status_code']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>