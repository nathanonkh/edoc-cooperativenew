<?php

function thaiDate($dateStr, $short = false) {
    if (empty($dateStr) || $dateStr === '0000-00-00 00:00:00' || $dateStr === '0000-00-00') return '-';
    $ts = strtotime($dateStr);
    if ($ts === false) return $dateStr;
    $monthsFull  = array('','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน',
                              'พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม',
                              'กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม');
    $monthsShort = array('','ม.ค.','ก.พ.','มี.ค.','เม.ย.',
                              'พ.ค.','มิ.ย.','ก.ค.','ส.ค.',
                              'ก.ย.','ต.ค.','พ.ย.','ธ.ค.');
    $d   = date('j', $ts);
    $m   = intval(date('n', $ts));
    $y   = intval(date('Y', $ts)) + 543;
    $mon = $short ? $monthsShort[$m] : $monthsFull[$m];
    return $d . ' ' . $mon . ' ' . $y;
}

function thaiYear() {
    return date('Y') + 543;
}

function getFullname($user) {
    $parts = array();
    if (!empty($user['prefix']))    $parts[] = $user['prefix'];
    if (!empty($user['firstname'])) $parts[] = $user['firstname'];
    if (!empty($user['lastname']))  $parts[] = $user['lastname'];
    return implode(' ', $parts);
}

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function redirectWithFlash($url, $type, $message) {
    Session::setFlash($type, $message);
    header('Location: ' . $url);
    exit;
}

function jsonResponse($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// =====================================================
// Static Dropdowns
// =====================================================

function getEmployeeTypeOptions() {
    return array(
        'civil'     => 'ข้าราชการ',
        'contract'  => 'พนักงานราชการ',
        'temporary' => 'ลูกจ้าง',
        'outsource' => 'จ้างเหมาบริการ',
    );
}

function getOfficeOptions() {
    return array(
        'สำนักงานตรวจบัญชีสหกรณ์ที่ 5',
        'สำนักงานตรวจบัญชีสหกรณ์ขอนแก่น',
        'สำนักงานตรวจบัญชีสหกรณ์อุดรธานี',
        'สำนักงานตรวจบัญชีสหกรณ์นครพนม',
        'สำนักงานตรวจบัญชีสหกรณ์สกลนคร',
        'สำนักงานตรวจบัญชีสหกรณ์เลย',
        'สำนักงานตรวจบัญชีสหกรณ์หนองคาย',
        'สำนักงานตรวจบัญชีสหกรณ์บึงกาฬ',
        'สำนักงานตรวจบัญชีสหกรณ์หนองบัวลำภู',
    );
}

function getCooperativeTypeOptions() {
    return array(
        'สหกรณ์การเกษตร',
        'สหกรณ์ออมทรัพย์',
        'สหกรณ์เครดิตยูเนี่ยน',
        'สหกรณ์ร้านค้า',
        'สหกรณ์บริการ',
        'สหกรณ์ประมง',
        'สหกรณ์นิคม',
        'กลุ่มเกษตรกร',
    );
}

function getProvinceOptions() {
    return array(
        'ขอนแก่น','อุดรธานี','นครพนม','สกลนคร','เลย',
        'หนองคาย','บึงกาฬ','หนองบัวลำภู',
    );
}

function formatFiscalYear($value) {
    if (empty($value)) return '-';
    $months = array(
        '01'=>'มกราคม','02'=>'กุมภาพันธ์','03'=>'มีนาคม',
        '04'=>'เมษายน','05'=>'พฤษภาคม','06'=>'มิถุนายน',
        '07'=>'กรกฎาคม','08'=>'สิงหาคม','09'=>'กันยายน',
        '10'=>'ตุลาคม','11'=>'พฤศจิกายน','12'=>'ธันวาคม',
    );
    if (preg_match('/^(\d{1,2})\/(\d{1,2})$/', trim($value), $m)) {
        $day   = intval($m[1]);
        $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
        $mName = isset($months[$month]) ? $months[$month] : $month;
        return $day . ' ' . $mName;
    }
    return $value;
}

function formatThaiDate2($value) {
    if (empty($value)) return '-';
    $months = array(
        '01'=>'มกราคม','02'=>'กุมภาพันธ์','03'=>'มีนาคม',
        '04'=>'เมษายน','05'=>'พฤษภาคม','06'=>'มิถุนายน',
        '07'=>'กรกฎาคม','08'=>'สิงหาคม','09'=>'กันยายน',
        '10'=>'ตุลาคม','11'=>'พฤศจิกายน','12'=>'ธันวาคม',
    );
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', trim($value), $m)) {
        $day   = intval($m[1]);
        $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
        $year  = $m[3];
        $mName = isset($months[$month]) ? $months[$month] : $month;
        return $day . ' ' . $mName . ' ' . $year;
    }
    return $value;
}

// =====================================================
// UI helpers (New e-LAAS theme — single source of truth
// for repeated markup fragments: badges, buttons)
// =====================================================

// Renders a small colored pill badge. $colorClasses is one of the
// "badge-*" color classes below (badge-amber, badge-blue, badge-sky,
// badge-purple, badge-red, badge-green, badge-slate).
function uiBadge($label, $colorClasses, $extraClasses = '') {
    return '<span class="badge-pill ' . $colorClasses . ' ' . $extraClasses . '">' . $label . '</span>';
}

// Standard action-button classes for the New e-LAAS theme.
function uiBtnClasses($variant = 'primary', $size = 'sm') {
    $sizeCls = $size === 'sm' ? 'btn-sm' : '';
    $variants = array(
        'primary'         => 'btn-action',
        'success'         => 'btn-action',
        'danger'          => 'btn-danger-btn',
        'warning'         => 'btn-warn-btn',
        'info'            => 'btn-info-btn',
        'purple'          => 'btn-purple-btn',
        'outline'         => 'btn-outline',
        'outline-danger'  => 'btn-danger-outline',
    );
    $v = isset($variants[$variant]) ? $variants[$variant] : $variants['primary'];
    return $v . ' ' . $sizeCls;
}

// =====================================================
// Role Helpers
// =====================================================

function getRoleLabel($role) {
    $map = array(
        'submitter' => 'ผู้นำส่งเอกสาร',
        'inspector' => 'ผู้ตรวจสอบ',
        'approver'  => 'ผู้อนุมัติ',
        'operator'  => 'ผู้ดำเนินการ',
        'admin'     => 'ผู้ดูแลระบบ',
    );
    return isset($map[$role]) ? $map[$role] : $role;
}

function getRoleBadgeClass($role) {
    $map = array(
        'submitter' => 'badge-slate',
        'inspector' => 'badge-blue',
        'approver'  => 'badge-sky',
        'operator'  => 'badge-purple',
        'admin'     => 'badge-red',
    );
    return isset($map[$role]) ? $map[$role] : 'badge-slate';
}

function getRolesFromUser($user) {
    if (isset($user['roles']) && is_array($user['roles'])) {
        return $user['roles'];
    }
    if (isset($user['roles']) && is_string($user['roles'])) {
        return array_map('trim', explode(',', $user['roles']));
    }
    return isset($user['role']) ? array($user['role']) : array();
}

function userHasRole($user, $role) {
    return in_array($role, getRolesFromUser($user));
}

function userHasAnyRole($user, $roles) {
    $userRoles = getRolesFromUser($user);
    foreach ($roles as $r) {
        if (in_array($r, $userRoles)) return true;
    }
    return false;
}

// =====================================================
// Cooperative Status Helpers
// =====================================================

function getCooperativeStatusOptions() {
    return array(
        'active'       => 'ดำเนินธุรกิจ',
        'inactive'     => 'ไม่ดำเนินธุรกิจ',
        'ceased'       => 'หยุดดำเนินธุรกิจ',
        'litigation'   => 'อยู่ระหว่างดำเนินคดี',
        'bankrupt'     => 'ล้มละลาย',
        'receivership' => 'พิทักษ์ทรัพย์',
        'dissolved'    => 'เลิก',
        'liquidation'  => 'ชำระบัญชี',
    );
}

function getCooperativeStatusLabel($status) {
    $opts = getCooperativeStatusOptions();
    return isset($opts[$status]) ? $opts[$status] : $status;
}

function getCooperativeStatusBadge($status) {
    $map = array(
        'active'       => 'badge-green',
        'inactive'     => 'badge-slate',
        'ceased'       => 'badge-amber',
        'litigation'   => 'badge-sky',
        'bankrupt'     => 'badge-slate',
        'receivership' => 'badge-slate',
        'dissolved'    => 'badge-red',
        'liquidation'  => 'badge-slate',
    );
    return isset($map[$status]) ? $map[$status] : 'badge-slate';
}

// =====================================================
// Document Helper Functions
// =====================================================

function docStatusLabel($status) {
    $labels = array(
        'pending'    => 'รอผู้ตรวจสอบ',
        'inspecting' => 'ผู้ตรวจสอบดำเนินการ',
        'approving'  => 'รอผู้อนุมัติ',
        'operating'  => 'รอผู้ดำเนินการ',
        'revision'   => 'ส่งกลับแก้ไข',
        'completed'  => 'เสร็จสิ้น',
    );
    return isset($labels[$status]) ? $labels[$status] : $status;
}

function docStatusBadgeClass($status) {
    $classes = array(
        'pending'    => 'badge-amber',
        'inspecting' => 'badge-blue',
        'approving'  => 'badge-sky',
        'operating'  => 'badge-purple',
        'revision'   => 'badge-red',
        'completed'  => 'badge-green',
    );
    return isset($classes[$status]) ? $classes[$status] : 'badge-slate';
}

// Solid header-background variant (banners/panel headers). Returns an
// inline-style-friendly hex so it can be dropped straight into style="background:...".
function docStatusHeaderClass($status) {
    $classes = array(
        'pending'    => 'background:#e65100;',
        'inspecting' => 'background:#0277bd;',
        'approving'  => 'background:#0288d1;',
        'operating'  => 'background:#7b1fa2;',
        'revision'   => 'background:#c62828;',
        'completed'  => 'background:#2e7d32;',
    );
    return isset($classes[$status]) ? $classes[$status] : 'background:#607d8b;';
}

function docStatusIcon($status) {
    $icons = array(
        'pending'    => 'fas fa-clock',
        'inspecting' => 'fas fa-magnifying-glass',
        'approving'  => 'fas fa-user-check',
        'operating'  => 'fas fa-list-check',
        'revision'   => 'fas fa-rotate-left',
        'completed'  => 'fas fa-circle-check',
    );
    return isset($icons[$status]) ? $icons[$status] : 'fas fa-circle';
}

function docFileLabel($num) {
    $labels = array(
        1 => 'หนังสือนำส่ง',
        2 => 'รายงานผู้สอบบัญชี',
        3 => 'รายงานผลการตรวจสอบ',
        4 => 'งบฐานะการเงิน',
    );
    return isset($labels[$num]) ? $labels[$num] : 'เอกสารที่ ' . $num;
}

function timeAgo($dateStr) {
    $ts   = strtotime($dateStr);
    $diff = time() - $ts;
    if ($diff < 60)      return 'เมื่อสักครู่';
    if ($diff < 3600)    return intval($diff / 60) . ' นาทีที่แล้ว';
    if ($diff < 86400)   return intval($diff / 3600) . ' ชั่วโมงที่แล้ว';
    if ($diff < 2592000) return intval($diff / 86400) . ' วันที่แล้ว';
    return thaiDate($dateStr, true);
}

function isHQ($user) {
    return $user['office_name'] === HQ_OFFICE;
}

function canActionDocument($user, $doc) {
    if (userHasRole($user, 'admin')) return true;
    $hq    = isHQ($user);
    $roles = getRolesFromUser($user);
    foreach (array('inspector','approver','operator') as $r) {
        if (in_array($r, $roles)) {
            if ($hq || $doc['office_name'] === $user['office_name']) return true;
        }
    }
    return false;
}

function buildDocumentWhereClause($user) {
    global $db;
    $uid     = intval($user['id']);
    $offName = $db->escape($user['office_name']);
    $hq      = isHQ($user);
    $roles   = getRolesFromUser($user);

    if (in_array('admin', $roles)) return '1=1';

    $conditions = array();

    if (in_array('inspector', $roles)) {
        $conditions[] = $hq ? '1=1' : "d.office_name = '$offName'";
    }
    if (in_array('approver', $roles)) {
        $s = "('approving','operating','completed','revision')";
        $conditions[] = $hq ? "d.status IN $s" : "(d.office_name = '$offName' AND d.status IN $s)";
    }
    if (in_array('operator', $roles)) {
        $s = "('operating','completed','revision')";
        $conditions[] = $hq ? "d.status IN $s" : "(d.office_name = '$offName' AND d.status IN $s)";
    }
    if (in_array('submitter', $roles)) {
        $conditions[] = "d.submitted_by = $uid";
    }

    if (empty($conditions)) return '1=0';
    return '(' . implode(' OR ', $conditions) . ')';
}

// =====================================================
// Issue (แจ้งปัญหาการใช้งานโปรแกรม) Helper Functions
// =====================================================

function getIssueTypeOptions() {
    return array(
        'login'   => 'เข้าใช้งานระบบไม่ได้ / ลืมรหัสผ่าน',
        'error'   => 'โปรแกรมทำงานผิดพลาด / ข้อมูลผิดพลาด',
        'slow'    => 'ระบบช้า / ค้าง',
        'howto'   => 'สอบถามวิธีการใช้งาน',
        'request' => 'ขอสิทธิ์การใช้งาน / ขอเพิ่มข้อมูล',
        'other'   => 'อื่น ๆ',
    );
}

function getProgramOptions() {
    return array(
        'fas'     => 'โปรแกรมระบบบัญชีสหกรณ์ (FAS)',
        'member'  => 'โปรแกรมทะเบียนสมาชิกและหุ้น',
        'loan'    => 'โปรแกรมสินเชื่อ',
        'deposit' => 'โปรแกรมเงินฝาก',
        'audit'   => 'โปรแกรมสอบบัญชี',
        'edms'    => 'ระบบนำส่งเอกสารอิเล็กทรอนิกส์ (eDMS)',
        'other'   => 'อื่น ๆ',
    );
}

function issueTypeLabel($key) {
    $opts = getIssueTypeOptions();
    return isset($opts[$key]) ? $opts[$key] : $key;
}

function programLabel($key) {
    $opts = getProgramOptions();
    return isset($opts[$key]) ? $opts[$key] : $key;
}

function issueStatusLabel($status, $isCentral = false) {
    $labels = array(
        'pending'      => 'รอตรวจสอบ',
        'sent_central' => 'ส่งส่วนกลาง',
        'in_progress'  => 'กำลังดำเนินการ',
        'completed'    => 'สำเร็จ',
    );
    $label = isset($labels[$status]) ? $labels[$status] : $status;
    if ($isCentral && in_array($status, array('in_progress', 'completed'))) {
        $label .= '/ส่วนกลาง';
    }
    return $label;
}

function issueStatusBadgeClass($status) {
    $classes = array(
        'pending'      => 'badge-amber',
        'sent_central' => 'badge-purple',
        'in_progress'  => 'badge-blue',
        'completed'    => 'badge-green',
    );
    return isset($classes[$status]) ? $classes[$status] : 'badge-slate';
}

function issueStatusHeaderClass($status) {
    $classes = array(
        'pending'      => 'background:#e65100;',
        'sent_central' => 'background:#7b1fa2;',
        'in_progress'  => 'background:#0277bd;',
        'completed'    => 'background:#2e7d32;',
    );
    return isset($classes[$status]) ? $classes[$status] : 'background:#607d8b;';
}

function issueStatusIcon($status) {
    $icons = array(
        'pending'      => 'fas fa-clock',
        'sent_central' => 'fas fa-paper-plane',
        'in_progress'  => 'fas fa-gears',
        'completed'    => 'fas fa-circle-check',
    );
    return isset($icons[$status]) ? $icons[$status] : 'fas fa-circle';
}

// ผู้ที่มีสิทธิ์ดำเนินการกับรายการแจ้งปัญหา (รับเรื่อง/ส่งต่อ/ปิดงาน) - เจ้าหน้าที่ตามสำนักงาน
function canManageIssue($user) {
    return userHasAnyRole($user, array('admin', 'inspector'));
}

// ผู้ที่มีสิทธิ์ตัดสินใจ/ดำเนินการกับเรื่องแจ้งปัญหา ณ ขั้นตอนที่ยังไม่ถึงส่วนกลาง
function canHandleIssue($user, $issue) {
    if ($issue['submitted_by'] == $user['id']) return true;
    return canManageIssue($user) && (isHQ($user) || $issue['office_name'] === $user['office_name']);
}

// สิทธิ์การมองเห็น: ส่วนกลาง (HQ) เห็นทั้งหมด, สำนักงานจังหวัดเห็นเฉพาะของตนเอง
function buildIssueWhereClause($user) {
    if (userHasRole($user, 'admin') || isHQ($user)) {
        return '1=1';
    }
    global $db;
    $offName = $db->escape($user['office_name']);
    $uid     = intval($user['id']);
    return "(i.office_name = '$offName' OR i.submitted_by = $uid)";
}

// =====================================================
// Internal Service (การให้บริการภายในสำนักงาน) Helper Functions
// =====================================================

function getServiceTypeOptions() {
    return array(
        'it_support'   => 'สนับสนุนด้านไอที/คอมพิวเตอร์',
        'document'     => 'ขอเอกสาร/สำเนาเอกสาร',
        'equipment'    => 'ขอใช้/ยืมอุปกรณ์',
        'meeting_room' => 'ขอใช้ห้องประชุม',
        'vehicle'      => 'ขอใช้รถราชการ',
        'other'        => 'อื่น ๆ',
    );
}

function serviceTypeLabel($key) {
    $opts = getServiceTypeOptions();
    return isset($opts[$key]) ? $opts[$key] : $key;
}

function serviceStatusLabel($status) {
    $labels = array(
        'pending'     => 'รอตรวจสอบ',
        'in_progress' => 'กำลังดำเนินการ',
        'completed'   => 'เสร็จสิ้น',
    );
    return isset($labels[$status]) ? $labels[$status] : $status;
}

function serviceStatusBadgeClass($status) {
    $classes = array(
        'pending'     => 'badge-amber',
        'in_progress' => 'badge-blue',
        'completed'   => 'badge-green',
    );
    return isset($classes[$status]) ? $classes[$status] : 'badge-slate';
}

function serviceStatusHeaderClass($status) {
    $classes = array(
        'pending'     => 'background:#e65100;',
        'in_progress' => 'background:#0277bd;',
        'completed'   => 'background:#2e7d32;',
    );
    return isset($classes[$status]) ? $classes[$status] : 'background:#607d8b;';
}

function serviceStatusIcon($status) {
    $icons = array(
        'pending'     => 'fas fa-clock',
        'in_progress' => 'fas fa-gears',
        'completed'   => 'fas fa-circle-check',
    );
    return isset($icons[$status]) ? $icons[$status] : 'fas fa-circle';
}

// ผู้ที่มีสิทธิ์ดำเนินการกับคำขอใช้บริการ: ผู้บันทึกคำขอเอง หรือเจ้าหน้าที่ (admin/inspector)
// ของสำนักงานเดียวกัน (หรือส่วนกลาง) — ไม่มีขั้น "ส่งส่วนกลาง" เหมือนแจ้งปัญหา
function canHandleService($user, $service) {
    if ($service['submitted_by'] == $user['id']) return true;
    return userHasAnyRole($user, array('admin', 'inspector')) && (isHQ($user) || $service['office_name'] === $user['office_name']);
}

// สิทธิ์การมองเห็น: ส่วนกลาง (HQ)/admin เห็นทั้งหมด, สำนักงานอื่นเห็นเฉพาะของสำนักงานตนเอง
// (ฟีเจอร์นี้เป็นบริการ "ภายในสำนักงาน" จึงไม่ผูกกับผู้ยื่นคำขอเหมือน buildIssueWhereClause)
function buildServiceWhereClause($user) {
    if (userHasRole($user, 'admin') || isHQ($user)) {
        return '1=1';
    }
    global $db;
    $offName = $db->escape($user['office_name']);
    return "s.office_name = '$offName'";
}

// รายชื่อสมาชิกในสำนักงานเดียวกัน ใช้เป็นตัวเลือกใน dropdown "ผู้ขอใช้บริการ"
function getOfficeMembersOptions($db, $officeName) {
    $off = $db->escape($officeName);
    return $db->fetchAll(
        "SELECT id, prefix, firstname, lastname, position
         FROM users
         WHERE office_name = '$off' AND is_active = 1
         ORDER BY firstname ASC"
    );
}

// =====================================================
// Announcement / Video Helper Functions (หน้าหลัก)
// =====================================================

function youtubeEmbedUrl($url) {
    $url = trim($url);
    if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([A-Za-z0-9_\-]{6,})/', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    return $url;
}
