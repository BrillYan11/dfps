<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

function json_out($data) {
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out([
        'success' => false,
        'message' => 'POST required'
    ]);
}

$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if ($phone === '' || $message === '') {
    json_out([
        'success' => false,
        'message' => 'Phone and message are required'
    ]);
}

$payload = json_encode([
    'phone' => $phone,
    'message' => $message
]);

$ch = curl_init('http://127.0.0.1:3001/send-sms');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);

$response = curl_exec($ch);

if ($response === false) {
    $error = curl_error($ch);
    curl_close($ch);

    json_out([
        'success' => false,
        'message' => 'Failed to contact local SMS server',
        'error' => $error
    ]);
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    json_out([
        'success' => false,
        'message' => 'Invalid JSON from SMS server',
        'raw_response' => $response
    ]);
}

http_response_code($httpCode ?: 200);
json_out($data);