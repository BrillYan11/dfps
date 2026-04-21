<?php
include_once __DIR__ . '/security_config.php';

class EncryptionUtil {
    private static $key;
    private static $cipher;

    public static function init() {
        if (!defined('ENCRYPTION_KEY') || ENCRYPTION_KEY === 'YOUR_STRONG_RANDOM_32_BYTE_KEY_HERE') {
            error_log("ERROR: ENCRYPTION_KEY is not set or is still default. Messages will NOT be securely encrypted/decrypted.");
            // For development, you might choose to throw an exception or return false
            // For production, this should definitely prevent further execution
            self::$key = null; // Ensure no operations if key is invalid
            return false;
        }
        self::$key = base64_decode(ENCRYPTION_KEY);
        self::$cipher = CIPHER_METHOD;
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