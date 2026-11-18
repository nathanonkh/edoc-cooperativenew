<?php
// ปลดล็อคข้อมูล (unlock_requests) helper functions
// ไฟล์ใหม่: ต้อง require_once หลัง helpers/functions.php (ใช้ isHQ, adminCanAccessOffice, userHasRole)

if (!function_exists('unlockStatusLabel')) {
    function unlockStatusLabel($status) {
        $labels = array(
            'pending'   => 'รอตรวจสอบ',
            'completed' => 'เสร็จสิ้น',
        );
        return lookupMap($labels, $status, $status);
    }
}

if (!function_exists('unlockStatusBadgeClass')) {
    function unlockStatusBadgeClass($status) {
        $classes = array(
            'pending'   => 'badge-amber',
            'completed' => 'badge-green',
        );
        return lookupMap($classes, $status, 'badge-slate');
    }
}

if (!function_exists('unlockStatusHeaderClass')) {
    function unlockStatusHeaderClass($status) {
        $classes = array(
            'pending'   => 'background:#e65100;',
            'completed' => 'background:#2e7d32;',
        );
        return lookupMap($classes, $status, 'background:#607d8b;');
    }
}

if (!function_exists('unlockStatusIcon')) {
    function unlockStatusIcon($status) {
        $icons = array(
            'pending'   => 'fas fa-clock',
            'completed' => 'fas fa-circle-check',
        );
        return lookupMap($icons, $status, 'fas fa-circle');
    }
}

// สิทธิ์การมองเห็น: ส่วนกลาง (HQ) เห็นทั้งหมด, สำนักงานจังหวัดเห็นเฉพาะของสำนักงานตนเอง
// ใช้ alias ตาราง "ur" (unlock_requests ur)
if (!function_exists('buildUnlockWhereClause')) {
    function buildUnlockWhereClause($user) {
        if (isHQ($user)) {
            return '1=1';
        }
        global $db;
        $offName = $db->escape($user['office_name']);
        return "ur.office_name = '$offName'";
    }
}

if (!function_exists('canViewUnlock')) {
    function canViewUnlock($user, $row) {
        return isHQ($user) || $row['office_name'] === $user['office_name'];
    }
}

// ผู้ที่กด "เสร็จสิ้น" ได้: (1) admin ที่เข้าถึงสำนักงานของคำขอ (กฎ adminCanAccessOffice เดิม)
//                           (2) ผู้ดำเนินการ (operator) ของส่วนกลาง หรือสำนักงานเดียวกับคำขอ
// ทำได้เฉพาะคำขอสถานะ pending (สถานะเดียวที่มีทางไปต่อ)
if (!function_exists('canCompleteUnlock')) {
    function canCompleteUnlock($user, $row) {
        if ($row['status'] !== 'pending') return false;
        if (adminCanAccessOffice($user, $row['office_name'])) return true;
        return userHasRole($user, 'operator') && (isHQ($user) || $row['office_name'] === $user['office_name']);
    }
}
