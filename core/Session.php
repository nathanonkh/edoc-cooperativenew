<?php
class Session {

    public static function generateCsrf() {
        $_SESSION[CSRF_TOKEN_NAME] = PasswordHash::randomHex(32);
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    public static function getCsrf() {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            self::generateCsrf();
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    public static function validateCsrf($token) {
        if (empty($token) || !is_string($token) || empty($_SESSION[CSRF_TOKEN_NAME])) {
            return false;
        }
        return PasswordHash::equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }

    public static function setFlash($type, $message) {
        $_SESSION['flash'] = array('type' => $type, 'message' => $message);
    }

    public static function getFlash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    public static function destroy() {
        session_unset();
        // ลบ cookie ของ session ฝั่งเบราว์เซอร์ด้วย (เดิมลบเฉพาะฝั่งเซิร์ฟเวอร์)
        if (!headers_sent() && isset($_COOKIE[session_name()])) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure']);
        }
        session_destroy();
    }
}
