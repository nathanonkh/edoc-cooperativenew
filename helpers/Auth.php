<?php
class Auth {

    // บทบาทของผู้ใช้ปัจจุบันจาก session (รองรับ session เก่าที่มีเฉพาะ 'role')
    private static function sessionRoles() {
        return isset($_SESSION['roles']) ? $_SESSION['roles'] : array($_SESSION['role']);
    }

    public static function requireLogin() {
        if (empty($_SESSION['user_id'])) {
            Session::setFlash('warning', 'กรุณาเข้าสู่ระบบก่อน');
            header('Location: ' . APP_URL . '/?page=login');
            exit;
        }
    }

    public static function requireRole($roles) {
        self::requireLogin();
        if (!is_array($roles)) {
            $roles = array($roles);
        }
        $userRoles = self::sessionRoles();
        $matched   = false;
        foreach ($roles as $r) {
            if (in_array($r, $userRoles)) { $matched = true; break; }
        }
        if (!$matched) {
            Session::setFlash('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
            header('Location: ' . APP_URL . '/?page=dashboard');
            exit;
        }
    }

    public static function hasRole($role) {
        return in_array($role, self::sessionRoles());
    }

    public static function hasAnyRole($roles) {
        $userRoles = self::sessionRoles();
        foreach ($roles as $r) {
            if (in_array($r, $userRoles)) return true;
        }
        return false;
    }

    public static function checkCsrf() {
        $token = isset($_POST[CSRF_TOKEN_NAME]) ? $_POST[CSRF_TOKEN_NAME] : '';
        if (!Session::validateCsrf($token)) {
            Session::setFlash('error', 'คำขอไม่ถูกต้อง (CSRF) กรุณาลองใหม่');
            header('Location: ' . APP_URL . '/?page=dashboard');
            exit;
        }
    }

    public static function login($db, $username, $password) {
        $usernameEsc = $db->escape($username);
        // ดึงข้อมูลผู้ใช้จาก username อย่างเดียวก่อน แล้วค่อยตรวจรหัสผ่านฝั่ง PHP
        // (เดิมเอา sha1($password) ไปต่อใน SQL WHERE ตรง ๆ ซึ่งใช้ไม่ได้แล้วเมื่อเปลี่ยนมาใช้ salted hash)
        $row = $db->fetchOne(
            "SELECT * FROM users WHERE username = '$usernameEsc' AND is_active = 1"
        );

        if (!$row) {
            return false;
        }

        if (!PasswordHash::verify($password, $row['password'])) {
            return false;
        }

        // อัปเกรด hash รูปแบบเก่า (sha1 ไม่มี salt) เป็นรูปแบบใหม่แบบอัตโนมัติ
        // เมื่อผู้ใช้ login สำเร็จด้วยรหัสผ่านที่ถูกต้อง
        if (PasswordHash::needsRehash($row['password'])) {
            $newHash    = PasswordHash::hash($password);
            $newHashEsc = $db->escape($newHash);
            $db->query("UPDATE users SET password='$newHashEsc' WHERE id=" . intval($row['id']));
        }

        // ป้องกัน Session Fixation: สร้าง session id ใหม่หลัง authenticate สำเร็จ
        session_regenerate_id(true);

        $_SESSION['user_id']     = $row['id'];
        $_SESSION['username']    = $row['username'];
        $_SESSION['prefix']      = $row['prefix'];
        $_SESSION['firstname']   = $row['firstname'];
        $_SESSION['lastname']    = $row['lastname'];
        $rolesRaw = isset($row['roles']) ? $row['roles'] : (isset($row['role']) ? $row['role'] : 'submitter');
        $rolesArr = array_map('trim', explode(',', $rolesRaw));
        $_SESSION['roles']       = $rolesArr;
        $_SESSION['role']        = $rolesArr[0];
        $_SESSION['office_name'] = $row['office_name'];
        Session::generateCsrf();
        return true;
    }

    public static function logout() {
        Session::destroy();
        header('Location: ' . APP_URL . '/?page=login');
        exit;
    }

    public static function currentUser() {
        $roles = self::sessionRoles();
        return array(
            'id'          => $_SESSION['user_id'],
            'username'    => $_SESSION['username'],
            'prefix'      => $_SESSION['prefix'],
            'firstname'   => $_SESSION['firstname'],
            'lastname'    => $_SESSION['lastname'],
            'fullname'    => trim($_SESSION['prefix'] . ' ' . $_SESSION['firstname'] . ' ' . $_SESSION['lastname']),
            'role'        => $_SESSION['role'],
            'roles'       => $roles,
            'office_name' => $_SESSION['office_name'],
        );
    }
}
