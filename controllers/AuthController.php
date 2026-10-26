<?php
class AuthController extends Controller {

    const MAX_USER_FAILS = 5;    // ผิดได้ต่อชื่อผู้ใช้ภายใน 15 นาที
    const MAX_IP_FAILS   = 20;   // ผิดได้ต่อ IP ภายใน 15 นาที

    public function __construct($db) {
        parent::__construct($db);
    }

    public function index() {
        if (!empty($_SESSION['user_id'])) {
            header('Location: ' . APP_URL . '/?page=dashboard');
            exit;
        }
        require_once 'views/auth/login.php';
    }

    // ปลายทางหลังล็อกอิน: ถ้ามาจากการสแกน QR ครุภัณฑ์ให้กลับไปหน้าตรวจนับ มิฉะนั้นไปหน้าหลัก
    private function afterLoginUrl() {
        $to = APP_URL . '/?page=dashboard';
        if (!empty($_SESSION['after_login']) && is_string($_SESSION['after_login'])
            && preg_match('#^/[^/].*page=assets&action=scan&code=[A-Za-z0-9_\-]+$#', $_SESSION['after_login'])) {
            $to = $_SESSION['after_login'];
        }
        unset($_SESSION['after_login']);
        return $to;
    }

    public function login() {
        if (!empty($_SESSION['user_id'])) {
            header('Location: ' . $this->afterLoginUrl());
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::checkCsrf();
            $username = isset($_POST['username']) && is_string($_POST['username']) ? trim($_POST['username']) : '';
            $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
            $back     = APP_URL . '/?page=login';

            if ($username === '' || $password === '') {
                redirectWithFlash($back, 'error', 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน');
            }

            if ($this->isLocked($username)) {
                redirectWithFlash($back, 'error', 'เข้าสู่ระบบผิดพลาดหลายครั้ง กรุณารอ 15 นาทีแล้วลองใหม่');
            }

            if (Auth::login($this->db, $username, $password)) {
                $this->clearAttempts($username);
                header('Location: ' . $this->afterLoginUrl());
                exit;
            }

            $this->recordFailure($username);
            redirectWithFlash($back, 'error', 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
        }

        require_once 'views/auth/login.php';
    }

    private function clientIp() {
        return isset($_SERVER['REMOTE_ADDR']) ? substr($_SERVER['REMOTE_ADDR'], 0, 45) : '';
    }

    // ถ้าตาราง login_attempts ยังไม่ถูกสร้าง (ยังไม่รัน migration.sql) จะไม่ล็อก แต่ระบบยังใช้งานได้
    private function isLocked($username) {
        $ip  = $this->db->escape($this->clientIp());
        $usr = $this->db->escape(strtolower(substr($username, 0, 50)));
        $row = $this->db->fetchOne(
            "SELECT SUM(username = '$usr') AS u_cnt, SUM(ip_address = '$ip') AS ip_cnt
             FROM login_attempts
             WHERE created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
               AND (username = '$usr' OR ip_address = '$ip')"
        );
        if (!$row) return false;
        return intval($row['u_cnt']) >= self::MAX_USER_FAILS || intval($row['ip_cnt']) >= self::MAX_IP_FAILS;
    }

    private function recordFailure($username) {
        $ip  = $this->db->escape($this->clientIp());
        $usr = $this->db->escape(strtolower(substr($username, 0, 50)));
        $this->db->query("INSERT INTO login_attempts (username, ip_address) VALUES ('$usr', '$ip')");
        // ล้างข้อมูลเก่ากว่า 1 วันเป็นครั้งคราว
        if (mt_rand(1, 20) === 1) {
            $this->db->query("DELETE FROM login_attempts WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");
        }
    }

    private function clearAttempts($username) {
        $usr = $this->db->escape(strtolower(substr($username, 0, 50)));
        $this->db->query("DELETE FROM login_attempts WHERE username = '$usr'");
    }
}
