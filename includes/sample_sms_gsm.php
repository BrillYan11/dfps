<?php
/**
 * SIM800C GSM Module SMS Controller for PHP
 * Compatible with USB-to-GSM module (CH340T chip)
 * 
 * Requirements: PHP 7.4+, php_serial extension or dio extension for serial communication
 * Alternative: Use socat/screen for serial proxy on Linux
 */

class GSMModule
{
    private $serialPort;
    private $baudRate;
    private $devicePath;
    private $isConnected = false;
    private $debugMode = true;
    private $lastError = '';
    
    // Common baud rates for SIM800C
    const BAUD_RATES = [9600, 19200, 38400, 57600, 115200];
    
    // SMS message formats
    const SMS_MODE_TEXT = 'TEXT';
    const SMS_MODE_PDU = 'PDU';
    
    public function __construct(string $devicePath = '/dev/ttyUSB0', int $baudRate = 9600)
    {
        $this->devicePath = $devicePath;
        $this->baudRate = $baudRate;
    }
    
    /**
     * Initialize serial connection
     */
    public function connect(): bool
    {
        if (!file_exists($this->devicePath)) {
            $this->lastError = "Device not found: {$this->devicePath}";
            return false;
        }
        
        // Method 1: Using dio extension (if available)
        if (extension_loaded('dio')) {
            return $this->connectDio();
        }
        
        // Method 2: Using exec with stty for Linux/Mac
        return $this->connectExec();
    }
    
    /**
     * Connect using dio extension
     */
    private function connectDio(): bool
    {
        try {
            $this->serialPort = dio_open($this->devicePath, O_RDWR | O_NOCTTY | O_NONBLOCK);
            
            if (!$this->serialPort) {
                $this->lastError = "Failed to open serial port";
                return false;
            }
            
            // Configure serial parameters
            dio_tcsetattr($this->serialPort, [
                'baud' => $this->baudRate,
                'bits' => 8,
                'stop' => 1,
                'parity' => 0
            ]);
            
            $this->isConnected = true;
            $this->log("Connected via DIO extension");
            return true;
            
        } catch (Exception $e) {
            $this->lastError = "DIO Error: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Connect using exec/stty (Linux/Mac alternative)
     */
    private function connectExec(): bool
    {
        // Configure serial port using stty
        $command = sprintf(
            'stty -F %s %d cs8 -cstopb -parenb -echo raw',
            escapeshellarg($this->devicePath),
            $this->baudRate
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            $this->lastError = "Failed to configure serial port with stty";
            return false;
        }
        
        $this->serialPort = fopen($this->devicePath, 'r+');
        
        if (!$this->serialPort) {
            $this->lastError = "Failed to open serial port stream";
            return false;
        }
        
        stream_set_blocking($this->serialPort, false);
        $this->isConnected = true;
        $this->log("Connected via stream (stty)");
        return true;
    }
    
    /**
     * Send AT command to module
     */
    public function sendCommand(string $command, int $timeout = 2): array
    {
        if (!$this->isConnected) {
            return ['success' => false, 'error' => 'Not connected'];
        }
        
        $fullCommand = "AT" . ($command ? '+' . $command : '') . "\r\n";
        
        $this->log("TX: " . trim($fullCommand));
        
        // Write command
        if (is_resource($this->serialPort) && get_resource_type($this->serialPort) === 'dio') {
            dio_write($this->serialPort, $fullCommand);
        } else {
            fwrite($this->serialPort, $fullCommand);
            fflush($this->serialPort);
        }
        
        // Read response
        usleep($timeout * 1000000); // Convert to microseconds
        $response = $this->readResponse();
        
        $this->log("RX: " . $response);
        
        $success = strpos($response, 'OK') !== false || 
                   strpos($response, '+') === 0 ||
                   strpos($response, '>') !== false;
        
        return [
            'success' => $success,
            'response' => $response,
            'command' => $command
        ];
    }
    
    /**
     * Read response from serial port
     */
    private function readResponse(): string
    {
        $response = '';
        $startTime = microtime(true);
        $timeout = 3; // seconds
        
        while ((microtime(true) - $startTime) < $timeout) {
            if (is_resource($this->serialPort) && get_resource_type($this->serialPort) === 'dio') {
                $data = dio_read($this->serialPort, 1024);
            } else {
                $data = fread($this->serialPort, 1024);
            }
            
            if ($data) {
                $response .= $data;
                // Reset timer if we got data
                $startTime = microtime(true);
            }
            
            usleep(100000); // 100ms
            
            // Check for complete response
            if (strpos($response, 'OK') !== false || 
                strpos($response, 'ERROR') !== false ||
                strpos($response, 'READY') !== false ||
                strpos($response, '>') !== false) {
                break;
            }
        }
        
        return trim($response);
    }
    
    /**
     * Initialize module with basic settings
     */
    public function initialize(): bool
    {
        // Test communication
        $result = $this->sendCommand('');
        if (!$result['success']) {
            return false;
        }
        
        // Disable echo
        $this->sendCommand('E0');
        
        // Set text mode for SMS
        $this->setSMSMode(self::SMS_MODE_TEXT);
        
        // Check network registration
        $result = $this->sendCommand('CREG?');
        if (strpos($result['response'], '+CREG: 0,1') === false && 
            strpos($result['response'], '+CREG: 0,5') === false) {
            $this->log("Warning: Not registered to network yet");
        }
        
        // Set SMS storage to SIM
        $this->sendCommand('CPMS="SM","SM","SM"');
        
        return true;
    }
    
    /**
     * Set SMS message format
     */
    public function setSMSMode(string $mode): bool
    {
        $modeCode = ($mode === self::SMS_MODE_TEXT) ? '1' : '0';
        $result = $this->sendCommand("CMGF={$modeCode}");
        return $result['success'];
    }
    
    /**
     * Send SMS message
     */
    public function sendSMS(string $phoneNumber, string $message): array
    {
        // Ensure phone number format
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        
        // Set recipient
        $result = $this->sendCommand('CMGS="' . $phoneNumber . '"', 1);
        
        if (!$result['success'] || strpos($result['response'], '>') === false) {
            return [
                'success' => false,
                'error' => 'Failed to initiate SMS send',
                'response' => $result['response']
            ];
        }
        
        // Send message content + Ctrl+Z (0x1A)
        $messageWithTerminator = $message . chr(26);
        
        if (is_resource($this->serialPort) && get_resource_type($this->serialPort) === 'dio') {
            dio_write($this->serialPort, $messageWithTerminator);
        } else {
            fwrite($this->serialPort, $messageWithTerminator);
            fflush($this->serialPort);
        }
        
        // Wait for send confirmation
        usleep(3000000); // 3 seconds for network send
        $response = $this->readResponse();
        
        $success = strpos($response, 'OK') !== false || 
                   strpos($response, '+CMGS:') !== false;
        
        return [
            'success' => $success,
            'response' => $response,
            'recipient' => $phoneNumber,
            'message' => $message
        ];
    }
    
    /**
     * Read all SMS messages from SIM card
     */
    public function readAllSMS(): array
    {
        $result = $this->sendCommand('CMGL="ALL"');
        
        if (!$result['success']) {
            return ['success' => false, 'error' => 'Failed to read SMS list'];
        }
        
        return $this->parseSMSList($result['response']);
    }
    
    /**
     * Read unread SMS messages
     */
    public function readUnreadSMS(): array
    {
        $result = $this->sendCommand('CMGL="REC UNREAD"');
        
        if (!$result['success']) {
            return ['success' => false, 'error' => 'Failed to read unread SMS'];
        }
        
        return $this->parseSMSList($result['response']);
    }
    
    /**
     * Parse SMS list from AT response
     */
    private function parseSMSList(string $response): array
    {
        $messages = [];
        $lines = explode("\n", $response);
        $currentMessage = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Match CMGL line: +CMGL: index,status,phone,,date
            if (preg_match('/\+CMGL:\s*(\d+),"([^"]+)","([^"]+)",[^,]*,"([^"]+)"/', $line, $matches)) {
                if ($currentMessage) {
                    $messages[] = $currentMessage;
                }
                
                $currentMessage = [
                    'index' => (int)$matches[1],
                    'status' => $matches[2],
                    'phone' => $matches[3],
                    'date' => $matches[4],
                    'message' => ''
                ];
            } elseif ($currentMessage && $line && $line !== 'OK') {
                $currentMessage['message'] .= $line . "\n";
            }
        }
        
        if ($currentMessage) {
            $currentMessage['message'] = trim($currentMessage['message']);
            $messages[] = $currentMessage;
        }
        
        return [
            'success' => true,
            'count' => count($messages),
            'messages' => $messages
        ];
    }
    
    /**
     * Delete SMS by index
     */
    public function deleteSMS(int $index): bool
    {
        $result = $this->sendCommand("CMGD={$index}");
        return $result['success'];
    }
    
    /**
     * Delete all SMS messages
     */
    public function deleteAllSMS(): bool
    {
        $result = $this->sendCommand('CMGD=1,4'); // Delete all
        return $result['success'];
    }
    
    /**
     * Get signal quality
     */
    public function getSignalQuality(): array
    {
        $result = $this->sendCommand('CSQ');
        
        if (preg_match('/\+CSQ:\s*(\d+),(\d+)/', $result['response'], $matches)) {
            $rssi = (int)$matches[1];
            $ber = (int)$matches[2];
            
            // Convert RSSI to dBm and percentage
            $dbm = ($rssi == 99) ? null : (-113 + ($rssi * 2));
            $percentage = ($rssi == 99) ? 0 : min(100, ($rssi / 31) * 100);
            
            return [
                'success' => true,
                'rssi' => $rssi,
                'ber' => $ber,
                'dbm' => $dbm,
                'percentage' => round($percentage),
                'quality' => $this->getSignalDescription($rssi)
            ];
        }
        
        return ['success' => false, 'error' => 'Failed to parse signal quality'];
    }
    
    private function getSignalDescription(int $rssi): string
    {
        if ($rssi == 99) return 'Unknown';
        if ($rssi >= 20) return 'Excellent';
        if ($rssi >= 15) return 'Good';
        if ($rssi >= 10) return 'Fair';
        if ($rssi >= 5) return 'Poor';
        return 'Very Poor';
    }
    
    /**
     * Get module information
     */
    public function getModuleInfo(): array
    {
        $info = [];
        
        // Manufacturer
        $result = $this->sendCommand('CGMI');
        if (preg_match('/^([^\r\n]+)/m', $result['response'], $m)) {
            $info['manufacturer'] = trim($m[1]);
        }
        
        // Model
        $result = $this->sendCommand('CGMM');
        if (preg_match('/^([^\r\n]+)/m', $result['response'], $m)) {
            $info['model'] = trim($m[1]);
        }
        
        // IMEI
        $result = $this->sendCommand('CGSN');
        if (preg_match('/(\d{15})/', $result['response'], $m)) {
            $info['imei'] = $m[1];
        }
        
        // Software version
        $result = $this->sendCommand('CGMR');
        if (preg_match('/^([^\r\n]+)/m', $result['response'], $m)) {
            $info['version'] = trim($m[1]);
        }
        
        // SIM status
        $result = $this->sendCommand('CPIN?');
        $info['sim_status'] = strpos($result['response'], 'READY') !== false ? 'Ready' : 'Not Ready';
        
        // Network operator
        $result = $this->sendCommand('COPS?');
        if (preg_match('/"([^"]+)"/', $result['response'], $m)) {
            $info['operator'] = $m[1];
        }
        
        return $info;
    }
    
    /**
     * Format phone number (E.164 format)
     */
    private function formatPhoneNumber(string $number): string
    {
        // Remove non-numeric characters
        $number = preg_replace('/[^0-9+]/', '', $number);
        
        // Ensure + prefix for international format
        if (!str_starts_with($number, '+')) {
            // Add country code if missing (assuming local number)
            // Modify this based on your country code
            $number = '+' . $number;
        }
        
        return $number;
    }
    
    /**
     * Enable/disable debug logging
     */
    public function setDebug(bool $enabled): void
    {
        $this->debugMode = $enabled;
    }
    
    /**
     * Log message
     */
    private function log(string $message): void
    {
        if ($this->debugMode) {
            echo "[" . date('Y-m-d H:i:s') . "] {$message}\n";
        }
    }
    
    /**
     * Get last error
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }
    
    /**
     * Close connection
     */
    public function disconnect(): void
    {
        if ($this->isConnected) {
            if (is_resource($this->serialPort)) {
                if (get_resource_type($this->serialPort) === 'dio') {
                    dio_close($this->serialPort);
                } else {
                    fclose($this->serialPort);
                }
            }
            $this->isConnected = false;
            $this->log("Disconnected");
        }
    }
    
    public function __destruct()
    {
        $this->disconnect();
    }
}

// ============================================
// WEB INTERFACE / API ENDPOINT
// ============================================

class SMSWebInterface
{
    private $gsm;
    private $config;
    
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'device' => '/dev/ttyUSB0',
            'baud_rate' => 9600,
            'api_key' => 'your-secret-api-key'
        ], $config);
    }
    
    /**
     * Handle HTTP request
     */
    public function handleRequest(): void
    {
        header('Content-Type: application/json');
        
        $action = $_GET['action'] ?? $_POST['action'] ?? 'status';
        
        // API key check for sensitive operations
        if (in_array($action, ['send', 'delete', 'delete_all'])) {
            $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
            if ($apiKey !== $this->config['api_key']) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }
        }
        
        $this->gsm = new GSMModule($this->config['device'], $this->config['baud_rate']);
        
        if (!$this->gsm->connect()) {
            echo json_encode([
                'success' => false,
                'error' => $this->gsm->getLastError()
            ]);
            return;
        }
        
        $this->gsm->initialize();
        
        switch ($action) {
            case 'send':
                $this->handleSend();
                break;
            case 'read':
                $this->handleRead();
                break;
            case 'delete':
                $this->handleDelete();
                break;
            case 'delete_all':
                $this->handleDeleteAll();
                break;
            case 'signal':
                $this->handleSignal();
                break;
            case 'info':
                $this->handleInfo();
                break;
            case 'status':
            default:
                $this->handleStatus();
                break;
        }
    }
    
    private function handleSend(): void
    {
        $phone = $_POST['phone'] ?? '';
        $message = $_POST['message'] ?? '';
        
        if (empty($phone) || empty($message)) {
            echo json_encode(['error' => 'Phone and message required']);
            return;
        }
        
        $result = $this->gsm->sendSMS($phone, $message);
        echo json_encode($result);
    }
    
    private function handleRead(): void
    {
        $type = $_GET['type'] ?? 'all';
        $result = ($type === 'unread') 
            ? $this->gsm->readUnreadSMS() 
            : $this->gsm->readAllSMS();
        echo json_encode($result);
    }
    
    private function handleDelete(): void
    {
        $index = (int)($_POST['index'] ?? 0);
        $success = $this->gsm->deleteSMS($index);
        echo json_encode(['success' => $success]);
    }
    
    private function handleDeleteAll(): void
    {
        $success = $this->gsm->deleteAllSMS();
        echo json_encode(['success' => $success]);
    }
    
    private function handleSignal(): void
    {
        echo json_encode($this->gsm->getSignalQuality());
    }
    
    private function handleInfo(): void
    {
        echo json_encode($this->gsm->getModuleInfo());
    }
    
    private function handleStatus(): void
    {
        echo json_encode([
            'connected' => true,
            'device' => $this->config['device'],
            'signal' => $this->gsm->getSignalQuality(),
            'info' => $this->gsm->getModuleInfo()
        ]);
    }
}

// ============================================
// CLI INTERFACE
// ============================================

class SMSCLI
{
    private $gsm;
    
    public function run(array $argv): void
    {
        if (count($argv) < 2) {
            $this->showHelp();
            return;
        }
        
        $command = $argv[1];
        $device = $argv[2] ?? '/dev/ttyUSB0';
        
        $this->gsm = new GSMModule($device);
        
        if (!$this->gsm->connect()) {
            echo "Error: " . $this->gsm->getLastError() . "\n";
            exit(1);
        }
        
        $this->gsm->initialize();
        
        switch ($command) {
            case 'send':
                if (count($argv) < 4) {
                    echo "Usage: php gsm.php send <device> <phone> <message>\n";
                    exit(1);
                }
                $result = $this->gsm->sendSMS($argv[3], $argv[4] ?? 'Test message');
                echo $result['success'] ? "SMS sent successfully!\n" : "Failed: " . $result['response'] . "\n";
                break;
                
            case 'read':
                $result = $this->gsm->readAllSMS();
                if ($result['success']) {
                    echo "Found {$result['count']} messages:\n";
                    foreach ($result['messages'] as $msg) {
                        echo "--------------------------------\n";
                        echo "From: {$msg['phone']}\n";
                        echo "Date: {$msg['date']}\n";
                        echo "Text: {$msg['message']}\n";
                    }
                }
                break;
                
            case 'signal':
                $signal = $this->gsm->getSignalQuality();
                echo "Signal: {$signal['quality']} ({$signal['percentage']}%)\n";
                break;
                
            case 'info':
                $info = $this->gsm->getModuleInfo();
                print_r($info);
                break;
                
            case 'interactive':
                $this->interactiveMode();
                break;
                
            default:
                $this->showHelp();
        }
    }
    
    private function interactiveMode(): void
    {
        echo "GSM Module Interactive Mode\n";
        echo "Commands: send <phone> <msg> | read | signal | info | quit\n";
        
        while (true) {
            echo "> ";
            $line = trim(fgets(STDIN));
            $parts = explode(' ', $line, 3);
            
            switch ($parts[0]) {
                case 'send':
                    if (count($parts) >= 3) {
                        $result = $this->gsm->sendSMS($parts[1], $parts[2]);
                        echo $result['success'] ? "Sent!\n" : "Failed\n";
                    }
                    break;
                case 'read':
                    $result = $this->gsm->readAllSMS();
                    echo "Messages: " . $result['count'] . "\n";
                    break;
                case 'signal':
                    print_r($this->gsm->getSignalQuality());
                    break;
                case 'info':
                    print_r($this->gsm->getModuleInfo());
                    break;
                case 'quit':
                    return;
            }
        }
    }
    
    private function showHelp(): void
    {
        echo "SIM800C GSM Module Controller\n";
        echo "Usage: php gsm.php <command> [device]\n";
        echo "Commands:\n";
        echo "  send <device> <phone> <message>  Send SMS\n";
        echo "  read <device>                    Read all SMS\n";
        echo "  signal <device>                  Check signal strength\n";
        echo "  info <device>                    Get module info\n";
        echo "  interactive <device>             Interactive mode\n";
        echo "\nExamples:\n";
        echo "  php gsm.php send /dev/ttyUSB0 +1234567890 \"Hello World\"\n";
        echo "  php gsm.php read /dev/ttyUSB0\n";
    }
}

// ============================================
// MAIN ENTRY POINT
// ============================================

// Only execute if this file is run directly
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    // Web mode
    if (php_sapi_name() !== 'cli') {
        $web = new SMSWebInterface([
            'device' => '/dev/ttyUSB0',
            'api_key' => $_ENV['GSM_API_KEY'] ?? 'default-key-change-this'
        ]);
        $web->handleRequest();
    } else {
        // CLI mode
        $cli = new SMSCLI();
        $cli->run($argv);
    }
}