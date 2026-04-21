<?php
// includes/EncryptionUtil.php

class EncryptionUtil {
    private static $key;
    private static $cipher;

    public static function init() {
        $raw_key = getenv('ENCRYPTION_KEY') ?: 'base64:L7p3p/X9Vz4Y/Q3Z0yG6w7+P2T/E9k8L0M1N2O3P4Q5='; // Default key for dev
        $cipher = getenv('CIPHER_METHOD') ?: 'AES-256-CBC';

        if (strpos($raw_key, 'base64:') === 0) {
            self::$key = base64_decode(substr($raw_key, 7));
        } else {
            self::$key = $raw_key;
        }

        self::$cipher = $cipher;
        
        if (empty(self::$key) || strlen(self::$key) < 16) {
            error_log("ERROR: ENCRYPTION_KEY is too short or not set. Messages will NOT be securely encrypted.");
            self::$key = null;
            return false;
        }
        
        return true;
    }

    public static function encrypt($plaintext) {
        if (!self::$key && !self::init()) {
            return $plaintext; // Fallback to plaintext if key is not set
        }
        $ivlen = openssl_cipher_iv_length(self::$cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext = openssl_encrypt($plaintext, self::$cipher, self::$key, OPENSSL_RAW_DATA, $iv);
        if ($ciphertext === false) {
            error_log("Encryption failed for message: " . $plaintext);
            return $plaintext; // Fallback to plaintext on error
        }
        return base64_encode($iv . $ciphertext);
    }

    public static function decrypt($ciphertext_with_iv) {
        if (empty($ciphertext_with_iv)) return $ciphertext_with_iv;
        
        if (!self::$key && !self::init()) {
            return $ciphertext_with_iv;
        }

        // Try to decode. If it's not valid base64, it's likely plaintext.
        $decoded = base64_decode($ciphertext_with_iv, true);
        if ($decoded === false) {
            return $ciphertext_with_iv;
        }

        $ivlen = openssl_cipher_iv_length(self::$cipher);
        
        // If the decoded string is shorter than the IV length, it can't be encrypted data.
        if (strlen($decoded) <= $ivlen) {
            return $ciphertext_with_iv;
        }

        $iv = substr($decoded, 0, $ivlen);
        $ciphertext = substr($decoded, $ivlen);
        $plaintext = openssl_decrypt($ciphertext, self::$cipher, self::$key, OPENSSL_RAW_DATA, $iv);
        
        if ($plaintext === false) {
            // If decryption fails, it's likely a plaintext string that happened to be valid base64
            return $ciphertext_with_iv;
        }
        return $plaintext;
    }
}

// Initialize the EncryptionUtil when it's included
EncryptionUtil::init();
?>