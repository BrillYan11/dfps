<?php
session_start();
include '../../includes/db.php';
require_once '../../includes/sample_sms_gsm.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DA', 'DA_SUPER_ADMIN'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $message = trim($_POST['message'] ?? '');
    $device = $_POST['gsm_device'] ?: 'COM3';
    $baud = intval($_POST['gsm_baud'] ?: 9600);

    if (!$user_id || empty($message)) {
        echo json_encode(['success' => false, 'error' => 'User ID and message are required.']);
        exit;
    }

    // Fetch user phone
    $stmt = $conn->prepare("SELECT phone, first_name, last_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || empty($user['phone'])) {
        echo json_encode(['success' => false, 'error' => 'User not found or has no phone number.']);
        exit;
    }

    $gsm = new GSMModule($device, $baud);
    $gsm->setDebug(false);

    if ($gsm->connect()) {
        if ($gsm->initialize()) {
            $res = $gsm->sendSMS($user['phone'], $message);
            $gsm->disconnect();
            
            if ($res['success']) {
                echo json_encode(['success' => true, 'message' => 'SMS sent successfully to ' . $user['first_name'] . '.']);
            } else {
                echo json_encode(['success' => false, 'error' => 'GSM Error: ' . ($res['response'] ?: 'Failed to send')]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to initialize GSM module.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Could not connect to GSM device. ' . $gsm->getLastError()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
