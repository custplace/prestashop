<?php
/**
 * Custplace.com
 *
 * @author    Custplace <support@custplace.com> - https://fr.custplace.com
 * @copyright THIRD VOICE 2023 - https://fr.custplace.com
 * @license   see file: LICENSE.txt
 *
 * @version   1.2.0
 */

namespace Custplace\Service;

class EncryptionService
{
    private const ENCRYPTION_PREFIX = 'CUSTPLACE_ENC:';
    
    /**
     * Encrypt sensitive data
     *
     * @param string $data
     * @return string
     */
    public function encrypt(string $data): string
    {
        if (empty($data)) {
            return '';
        }
        
        // Use PrestaShop's cookie key as encryption key
        $key = _COOKIE_KEY_;
        $iv = openssl_random_pseudo_bytes(16);
        
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        
        // Prepend IV and add our prefix to identify encrypted values
        return self::ENCRYPTION_PREFIX . base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     *
     * @param string $encryptedData
     * @return string
     */
    public function decrypt(string $encryptedData): string
    {
        if (empty($encryptedData)) {
            return '';
        }
        
        // Check if data is encrypted by our service
        if (!$this->isEncrypted($encryptedData)) {
            // Return as-is (plain text for backward compatibility)
            return $encryptedData;
        }
        
        // Remove prefix and decode
        $data = base64_decode(substr($encryptedData, strlen(self::ENCRYPTION_PREFIX)));
        
        if ($data === false || strlen($data) < 16) {
            return '';
        }
        
        // Extract IV and encrypted data
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        // Use PrestaShop's cookie key as encryption key
        $key = _COOKIE_KEY_;
        
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
        
        return $decrypted !== false ? $decrypted : '';
    }
    
    /**
     * Check if data is encrypted by our service
     *
     * @param string $data
     * @return bool
     */
    public function isEncrypted(string $data): bool
    {
        return strpos($data, self::ENCRYPTION_PREFIX) === 0;
    }
    
    /**
     * Mask sensitive data for display (show only last 4 characters)
     *
     * @param string $data
     * @return string
     */
    public function maskForDisplay(string $data): string
    {
        if (empty($data) || strlen($data) < 8) {
            return '****';
        }
        
        return str_repeat('*', strlen($data) - 4) . substr($data, -4);
    }
}