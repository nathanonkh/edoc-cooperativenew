<?php
/**
 * PasswordHash — รองรับ PHP 5.2 (ไม่มี password_hash)
 *
 * รูปแบบใหม่   : pbkdf2$<รอบ>$<salt>$<hash>   (PBKDF2-HMAC-SHA256)
 * รูปแบบเก่า 1 : <salt>$<hmac-sha256>          (รอบก่อนหน้า)
 * รูปแบบเก่า 2 : sha1 เปล่า ๆ                   (ระบบเดิมสุด)
 * verify() รองรับทั้ง 3 แบบ และ Auth::login() จะอัปเกรดเป็นแบบใหม่อัตโนมัติ
 */
class PasswordHash {

    const ITERATIONS = 10000;

    public static function hash($password) {
        $salt = self::randomHex(16);
        return 'pbkdf2$' . self::ITERATIONS . '$' . $salt . '$' . self::pbkdf2($password, $salt, self::ITERATIONS);
    }

    public static function verify($password, $stored) {
        if (empty($stored) || !is_string($stored)) {
            return false;
        }
        if (strpos($stored, 'pbkdf2$') === 0) {
            $parts = explode('$', $stored);
            if (count($parts) !== 4) return false;
            $iter = intval($parts[1]);
            if ($iter < 1 || $iter > 1000000) return false;
            return self::equals(self::pbkdf2($password, $parts[2], $iter), $parts[3]);
        }
        if (strpos($stored, '$') !== false) {
            list($salt, $hash) = explode('$', $stored, 2);
            return self::equals(hash_hmac('sha256', $password, $salt), $hash);
        }
        return self::equals(sha1($password), $stored);
    }

    public static function needsRehash($stored) {
        if (strpos($stored, 'pbkdf2$') !== 0) return true;
        $parts = explode('$', $stored);
        return (count($parts) !== 4 || intval($parts[1]) < self::ITERATIONS);
    }

    // PBKDF2-HMAC-SHA256 ขนาด 32 ไบต์ (1 block)
    private static function pbkdf2($password, $salt, $iterations) {
        $u     = hash_hmac('sha256', $salt . "\0\0\0\1", $password, true);
        $block = $u;
        for ($i = 1; $i < $iterations; $i++) {
            $u     = hash_hmac('sha256', $u, $password, true);
            $block = $block ^ $u;
        }
        return bin2hex($block);
    }

    // เปรียบเทียบสตริงแบบ constant-time (PHP 5.2 ไม่มี hash_equals)
    public static function equals($a, $b) {
        $a = (string) $a;
        $b = (string) $b;
        $len = strlen($a);
        if ($len !== strlen($b)) return false;
        $r = 0;
        for ($i = 0; $i < $len; $i++) {
            $r |= ord($a[$i]) ^ ord($b[$i]);
        }
        return $r === 0;
    }

    // สุ่มเลขฐานสิบหกยาว $bytes*2 ตัวอักษร โดยใช้แหล่งสุ่มที่ดีที่สุดเท่าที่เครื่องมี
    public static function randomHex($bytes) {
        $raw = '';
        if (function_exists('openssl_random_pseudo_bytes')) {
            $raw = openssl_random_pseudo_bytes($bytes);
        }
        if (strlen($raw) < $bytes && function_exists('mcrypt_create_iv') && defined('MCRYPT_DEV_URANDOM')) {
            $raw = @mcrypt_create_iv($bytes, MCRYPT_DEV_URANDOM);
        }
        if (strlen($raw) < $bytes && @is_readable('/dev/urandom')) {
            $fp = @fopen('/dev/urandom', 'rb');
            if ($fp) { $raw = fread($fp, $bytes); fclose($fp); }
        }
        if (is_string($raw) && strlen($raw) >= $bytes) {
            return bin2hex(substr($raw, 0, $bytes));
        }
        // fallback สุดท้าย: ผสม entropy หลายแหล่ง
        $out = '';
        while (strlen($out) < $bytes * 2) {
            $out .= hash('sha256', uniqid(mt_rand(), true) . microtime(true) . mt_rand() . session_id() . memory_get_usage() . serialize($_SERVER));
        }
        return substr($out, 0, $bytes * 2);
    }
}
