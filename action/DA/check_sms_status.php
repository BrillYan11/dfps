<?php
header('Content-Type: application/json');

$api_url = "http://localhost:3001/status";

$context = stream_context_create(['http' => ['timeout' => 2]]);
$result = @file_get_contents($api_url, false, $context);

if ($result === FALSE) {
    echo json_encode(['success' => false, 'error' => 'SMS Server Offline']);
} else {
    echo $result;
}
