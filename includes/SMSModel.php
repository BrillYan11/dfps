<?php
/**
 * SMS Model - Handles communication with the Node.js SMS gateway.
 * 
 * BASEMENT REFERENCE: 
 * This model depends on the Node.js SMS server located at:
 * D:\Random DATA\Bri\Mega\Bryan Master\IS_295A\DFPS\sms\sms_server.js
 * 
 * To use this, ensure the Node.js server is running:
 * 1. Open a terminal in the /sms folder.
 * 2. Run: node sms_server.js (or run the .bat file)
 */
class SMSModel {
    /**
     * Get the URL of the Node.js SMS server.
     * Prioritizes the SMS_GATEWAY_URL environment variable (used in Docker/Cloud).
     */
    private static function getApiUrl() {
        return getenv('SMS_GATEWAY_URL') ?: "http://localhost:3001/send-sms";
    }

    /**
     * Sends an SMS via the Node.js server
     * 
     * @param string $phone The recipient's phone number
     * @param string $message The message content
     * @return array Result with 'success' boolean and optional 'error' or 'response'
     */
    public static function sendSMS($phone, $message) {
        $api_url = self::getApiUrl();
        if (empty($phone) || empty($message)) {
            return ['success' => false, 'error' => 'Phone and message are required.'];
        }

        // Clean up phone number: remove non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        $data = [
            'phone' => $phone,
            'message' => $message
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'timeout' => 30,
                'ignore_errors' => true // Allow reading the response body even on 400/500 errors
            ]
        ];

        $context  = stream_context_create($options);
        
        try {
            $result = @file_get_contents($api_url, false, $context);
            
            // Get the HTTP response headers to check status
            $status_line = $http_response_header[0] ?? '';
            preg_match('{HTTP\/\S+\s+(\d+)}', $status_line, $matches);
            $status_code = $matches[1] ?? 'unknown';

            if ($result === FALSE) {
                $error = error_get_last();
                return [
                    'success' => false, 
                    'error' => 'Could not connect to SMS server at ' . $api_url . '. ' . 
                               'Ensure the Node.js gateway in /sms is running (sms_server.js). ' . 
                               ($error ? 'Details: ' . $error['message'] : '')
                ];
            }

            $response = json_decode($result, true);
            
            // If the status code is not 200, return the error message from the body
            if ($status_code != 200) {
                return [
                    'success' => false,
                    'error' => 'SMS Server Error (HTTP ' . $status_code . '): ' . ($response['error'] ?? $result)
                ];
            }

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'error' => 'Invalid JSON response from SMS server: ' . json_last_error_msg()
                ];
            }

            return $response;
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception while calling SMS server: ' . $e->getMessage()
            ];
        }
    }
}
?>
