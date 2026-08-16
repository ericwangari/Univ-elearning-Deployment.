<?php
/**
 * CookieSessionHandler.php
 * Encrypted Cookie Session Handler for PHP (AES-256-CBC + HMAC-SHA256)
 * Enables 100% stateless, reliable session management on Vercel Serverless / multi-container hosts.
 */

class CookieSessionHandler implements SessionHandlerInterface {
    private $secretKey;
    private $cookieName;
    private $cookiePath;
    private $cookieDomain;
    private $secure;
    private $httponly;
    private $sameSite;

    public function __construct($secretKey, $cookieName = 'UNIV_SESS', $lifetime = 604800) {
        $this->secretKey = hash('sha256', $secretKey, true);
        $this->cookieName = $cookieName;
        $this->cookiePath = '/';
        $this->cookieDomain = '';
        $this->secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $this->httponly = true;
        $this->sameSite = 'Lax';
    }

    public function open($savePath, $sessionName): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function read($id) {
        if (!isset($_COOKIE[$this->cookieName]) || empty($_COOKIE[$this->cookieName])) {
            return '';
        }

        $raw = $_COOKIE[$this->cookieName];
        $decoded = base64_decode($raw, true);
        if ($decoded === false || strlen($decoded) < 48) {
            return '';
        }

        $iv = substr($decoded, 0, 16);
        $hmac = substr($decoded, 16, 32);
        $ciphertext = substr($decoded, 48);

        $calculatedHmac = hash_hmac('sha256', $iv . $ciphertext, $this->secretKey, true);
        if (!hash_equals($hmac, $calculatedHmac)) {
            return '';
        }

        $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $this->secretKey, OPENSSL_RAW_DATA, $iv);
        return $decrypted !== false ? $decrypted : '';
    }

    #[\ReturnTypeWillChange]
    public function write($id, $data) {
        if (headers_sent()) {
            return false;
        }

        if (empty($data)) {
            $this->destroy($id);
            return true;
        }

        $iv = openssl_random_pseudo_bytes(16);
        $ciphertext = openssl_encrypt($data, 'aes-256-cbc', $this->secretKey, OPENSSL_RAW_DATA, $iv);
        if ($ciphertext === false) {
            return false;
        }

        $hmac = hash_hmac('sha256', $iv . $ciphertext, $this->secretKey, true);
        $payload = base64_encode($iv . $hmac . $ciphertext);

        // Safe cookie payload length limit (~4000 bytes)
        if (strlen($payload) > 4000) {
            error_log("Cookie session payload exceeds maximum safe cookie length limit.");
            return false;
        }

        if (PHP_VERSION_ID >= 70300) {
            @setcookie($this->cookieName, $payload, [
                'expires' => time() + 604800,
                'path' => $this->cookiePath,
                'domain' => $this->cookieDomain,
                'secure' => $this->secure,
                'httponly' => $this->httponly,
                'samesite' => $this->sameSite
            ]);
        } else {
            @setcookie($this->cookieName, $payload, time() + 604800, $this->cookiePath, $this->cookieDomain, $this->secure, $this->httponly);
        }

        return true;
    }

    #[\ReturnTypeWillChange]
    public function destroy($id) {
        if (headers_sent()) {
            return false;
        }

        if (PHP_VERSION_ID >= 70300) {
            @setcookie($this->cookieName, '', [
                'expires' => time() - 3600,
                'path' => $this->cookiePath,
                'domain' => $this->cookieDomain,
                'secure' => $this->secure,
                'httponly' => $this->httponly,
                'samesite' => $this->sameSite
            ]);
        } else {
            @setcookie($this->cookieName, '', time() - 3600, $this->cookiePath, $this->cookieDomain, $this->secure, $this->httponly);
        }
        if (isset($_COOKIE[$this->cookieName])) {
            unset($_COOKIE[$this->cookieName]);
        }
        return true;
    }

    #[\ReturnTypeWillChange]
    public function gc($maxlifetime) {
        return 0;
    }
}
