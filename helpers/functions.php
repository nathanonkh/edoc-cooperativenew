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

// ชื่อเดือนเต็มภาษาไทย คีย์ '01'..'12' (เดิมประกาศซ้ำใน formatFiscalYear และ formatThaiDate2)
if (!function_exists('thaiMonthNamesByKey')) {
    function thaiMonthNamesByKey() {
        return array(
            '01'=>'มกราคม','02'=>'กุมภาพันธ์','03'=>'มีนาคม',
            '04'=>'เมษายน','05'=>'พฤษภาคม','06'=>'มิถุนายน',
            '07'=>'กรกฎาคม','08'=>'สิงหาคม','09'=>'กันยายน',
            '10'=>'ตุลาคม','11'=>'พฤศจิกายน','12'=>'ธันวาคม',
        );
    }
}

function formatFiscalYear($value) {
    if (empty($value)) return '-';
    $months = thaiMonthNamesByKey();
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
    $months = thaiMonthNamesByKey();
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

function getAllRoleKeys() {
    return array('submitter','inspector','approver','operator','admin');
}

function getRoleDescriptions() {
    return array(
        'submitter' => 'ส่งเอกสาร ติดตามสถานะ และแก้ไขเมื่อถูกส่งกลับ',
        'inspector' => 'ตรวจสอบเอกสาร กรอกเลขรับหนังสือ',
        'approver'  => 'อนุมัติเอกสารที่ผ่านการตรวจสอบ',
        'operator'  => 'บันทึกผลดำเนินการขั้นสุดท้าย',
        'admin'     => 'เข้าถึงได้ทุกส่วน จัดการสมาชิกและสหกรณ์',
    );
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

// admin ส่วนกลางเข้าถึงได้ทุกสำนักงาน admin จังหวัดเข้าถึงได้เฉพาะสำนักงานตนเอง
if (!function_exists('adminCanAccessOffice')) {
    function adminCanAccessOffice($user, $officeName) {
        return userHasRole($user, 'admin') && (isHQ($user) || $officeName === $user['office_name']);
    }
}

// สำนักงานที่ผู้ใช้จัดการได้ในหน้าจัดการสมาชิก/สหกรณ์: ส่วนกลางทุกสำนักงาน, admin จังหวัดเฉพาะสำนักงานตนเอง
function manageableOfficeOptions($user) {
    return isHQ($user) ? getOfficeOptions() : array($user['office_name']);
}

// บทบาทที่ผู้ใช้ปัจจุบันตั้งให้สมาชิกในสำนักงานปลายทางได้ (กฎที่เดียว ใช้ทั้ง controller และ view)
//   admin ส่วนกลาง: สำนักงานส่วนกลางตั้งได้ทุกบทบาท, สำนักงานจังหวัดตั้งได้เฉพาะ submitter/admin
//   admin จังหวัด : ตั้งบทบาทไม่ได้ (คืน array ว่าง)
function assignableRolesFor($actor, $targetOffice) {
    if (!isHQ($actor)) return array();
    return ($targetOffice === HQ_OFFICE) ? getAllRoleKeys() : array('submitter', 'admin');
}

function canActionDocument($user, $doc) {
    if (adminCanAccessOffice($user, $doc['office_name'])) return true;
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

    if (in_array('admin', $roles)) {
        return $hq ? '1=1' : "d.office_name = '$offName'";
    }

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
    0  => 'ระบบสมาชิกและหุ้น 1.7 (สกด.)',
    1  => 'ระบบสินค้า 1.5',
    2  => 'ระบบสมาชิกและหุ้น 1.9',
    3  => 'ระบบเงินให้กู้ 1.9',
    4  => 'ระบบสมาชิกและหุ้น 2.0',
    5  => 'ระบบโปรแกรมเงินให้กู้ 2.0',
    6  => 'ระบบเงินรับฝาก 2.2',
    7  => 'ระบบสินค้า 2.2',
    8  => 'ระบบบัญชีแยกประเภท 2.2',
    9  => 'ระบบเงินรับฝาก 2.3',
    10 => 'ระบบสินค้า 2.3',
    11 => 'ระบบบัญชีแยกประเภท 2.3',
    12 => 'ระบบออมทรัพย์ 3.0',
    13 => 'ระบบจัดการเอกสารรายที่ 3 (isocare)',
    14 => 'SMART ME',
    15 => 'SMART MEMBER',
    16 => 'SMART MANAGE',
    17 => 'SMART MONITOR',
    18 => 'CAT_ACL',
    19 => 'สหกรณ์เครดิตยูเนี่ยนก้าวหน้า',
    20 => 'อื่นๆ',
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
        'revision'     => 'ส่งกลับแก้ไข',
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
        'revision'     => 'badge-red',
    );
    return isset($classes[$status]) ? $classes[$status] : 'badge-slate';
}

function issueStatusHeaderClass($status) {
    $classes = array(
        'pending'      => 'background:#e65100;',
        'sent_central' => 'background:#7b1fa2;',
        'in_progress'  => 'background:#0277bd;',
        'completed'    => 'background:#2e7d32;',
        'revision'     => 'background:#c62828;',
    );
    return isset($classes[$status]) ? $classes[$status] : 'background:#607d8b;';
}

function issueStatusIcon($status) {
    $icons = array(
        'pending'      => 'fas fa-clock',
        'sent_central' => 'fas fa-paper-plane',
        'in_progress'  => 'fas fa-gears',
        'completed'    => 'fas fa-circle-check',
        'revision'     => 'fas fa-rotate-left',
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
    if (isHQ($user)) {
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
        'revision'    => 'ส่งกลับแก้ไข',
    );
    return isset($labels[$status]) ? $labels[$status] : $status;
}

function serviceStatusBadgeClass($status) {
    $classes = array(
        'pending'     => 'badge-amber',
        'in_progress' => 'badge-blue',
        'completed'   => 'badge-green',
        'revision'    => 'badge-red',
    );
    return isset($classes[$status]) ? $classes[$status] : 'badge-slate';
}

function serviceStatusHeaderClass($status) {
    $classes = array(
        'pending'     => 'background:#e65100;',
        'in_progress' => 'background:#0277bd;',
        'completed'   => 'background:#2e7d32;',
        'revision'    => 'background:#c62828;',
    );
    return isset($classes[$status]) ? $classes[$status] : 'background:#607d8b;';
}

function serviceStatusIcon($status) {
    $icons = array(
        'pending'     => 'fas fa-clock',
        'in_progress' => 'fas fa-gears',
        'completed'   => 'fas fa-circle-check',
        'revision'    => 'fas fa-rotate-left',
    );
    return isset($icons[$status]) ? $icons[$status] : 'fas fa-circle';
}

// ผู้ที่มีสิทธิ์ดำเนินการกับคำขอใช้บริการ: เฉพาะผู้ยื่น (ผู้บันทึกคำขอ) ของจังหวัดนั้น — ส่วนกลางดำเนินการไม่ได้
function canHandleService($user, $service) {
    // ส่วนกลางดูได้อย่างเดียว / จังหวัดกดดำเนินการได้เฉพาะ "ผู้ยื่น" (ผู้บันทึกคำขอ) ของสำนักงานตนเองเท่านั้น
    if (isHQ($user)) return false;
    return $service['office_name'] === $user['office_name'] && $service['submitted_by'] == $user['id'];
}

// =====================================================
// กฎ "ส่งกลับไปแก้ไข" ของแต่ละโมดูล (เก็บเป็นข้อมูลที่เดียว ตัวประเมินกลางด้านล่างใช้ร่วมกัน)
// กฎสิทธิ์ของแต่ละโมดูลยังต่างกันจริง จึงเป็นรายการกฎแยกตามโมดูล ไม่รวมเป็นเงื่อนไขเดียว
//   status  = สถานะที่ส่งกลับได้        central = (ไม่บังคับ) 0/1 ตรงกับ handled_by_central
//   actor   = submitter        ผู้นำส่ง/ผู้แจ้งตัวเอง (submitted_by = ผู้ใช้)
//             role:XXX         ผู้ใช้มีบทบาท XXX และเป็นส่วนกลางหรือสำนักงานเดียวกับรายการ (ไม่มีสิทธิ์พิเศษของ admin)
//             hq_admin         admin ส่วนกลางเท่านั้น
//             service_handler  ผู้ยื่นคำขอบริการภายใน (canHandleService)
// =====================================================
function returnForEditRules() {
    return array(
        'documents' => array(
            array('status' => 'pending',   'actor' => 'submitter'),
            array('status' => 'pending',   'actor' => 'role:inspector'),
            array('status' => 'approving', 'actor' => 'role:approver'),
            array('status' => 'operating', 'actor' => 'role:operator'),
        ),
        'issues' => array(
            array('status' => 'pending',     'central' => 0, 'actor' => 'submitter'),
            array('status' => 'in_progress', 'central' => 0, 'actor' => 'submitter'),
            array('status' => 'sent_central',                'actor' => 'hq_admin'),
            array('status' => 'in_progress', 'central' => 1, 'actor' => 'hq_admin'),
        ),
        'internal_services' => array(
            array('status' => 'pending',     'actor' => 'service_handler'),
            array('status' => 'in_progress', 'actor' => 'service_handler'),
        ),
    );
}

// ผู้มีสิทธิ์แก้ไขและส่งใหม่หลังถูกส่งกลับ (documents ใช้ตรรกะเดิมใน DocumentController)
function resubmitActors() {
    return array('issues' => 'submitter', 'internal_services' => 'service_handler');
}

function returnActorAllowed($actor, $user, $row) {
    if ($actor === 'submitter')       return $row['submitted_by'] == $user['id'];
    if ($actor === 'service_handler') return canHandleService($user, $row);
    if ($actor === 'hq_admin')        return userHasRole($user, 'admin') && isHQ($user);
    if (strpos($actor, 'role:') === 0) {
        return userHasRole($user, substr($actor, 5))
            && (isHQ($user) || $row['office_name'] === $user['office_name']);
    }
    return false;
}

// คืนค่า actor ของกฎที่ตรงกับ (โมดูล, สถานะ, ผู้ใช้) หรือ '' ถ้าส่งกลับไม่ได้
function matchReturnRule($module, $user, $row) {
    $rules = returnForEditRules();
    if (!isset($rules[$module])) return '';
    foreach ($rules[$module] as $r) {
        if ($r['status'] !== $row['status']) continue;
        if (isset($r['central']) && (!empty($row['handled_by_central']) ? 1 : 0) !== $r['central']) continue;
        if (returnActorAllowed($r['actor'], $user, $row)) return $r['actor'];
    }
    return '';
}

function canResubmitAfterReturn($module, $user, $row) {
    $actors = resubmitActors();
    return $row['status'] === 'revision' && isset($actors[$module])
        && returnActorAllowed($actors[$module], $user, $row);
}

// สิทธิ์การมองเห็น: ส่วนกลาง (HQ) เห็นทั้งหมด, สำนักงานอื่น (รวม admin) เห็นเฉพาะของสำนักงานตนเอง
// (ฟีเจอร์นี้เป็นบริการ "ภายในสำนักงาน" จึงไม่ผูกกับผู้ยื่นคำขอเหมือน buildIssueWhereClause)
function buildServiceWhereClause($user) {
    if (isHQ($user)) {
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
    // ไม่ใช่ลิงก์ YouTube ที่รู้จัก: ไม่ใส่เป็น iframe src ตรง ๆ (กัน URL แปลก เช่น javascript:/หน้าอื่น)
    if (preg_match('#^https://(www\.youtube\.com|www\.youtube-nocookie\.com)/embed/[A-Za-z0-9_\-]{6,}$#', $url)) {
        return $url;
    }
    return '';
}

// =====================================================
// Compatibility helpers (PHP 5.2.3)
// =====================================================

// แทน http_response_code(403) ซึ่งไม่มีใน PHP < 5.4
if (!function_exists('requireAjax')) {
    function requireAjax() {
        if (!isAjax()) {
            if (!headers_sent()) {
                header('HTTP/1.1 403 Forbidden');
            }
            exit('Forbidden');
        }
    }
}

// mbstring อาจไม่เปิดใน php.ini ของเครื่องจริง: มี fallback ผ่าน PCRE /u แล้วจึง substr/strlen
if (!function_exists('safeStrlen')) {
    function safeStrlen($s) {
        $s = (string) $s;
        if (function_exists('mb_strlen')) {
            return mb_strlen($s, 'UTF-8');
        }
        $n = @preg_match_all('/./su', $s, $m);
        return ($n === false) ? strlen($s) : $n;
    }
}
if (!function_exists('safeSubstr')) {
    function safeSubstr($s, $start, $len) {
        $s = (string) $s;
        if (function_exists('mb_substr')) {
            return mb_substr($s, $start, $len, 'UTF-8');
        }
        if (@preg_match_all('/./su', $s, $m) !== false) {
            return implode('', array_slice($m[0], $start, $len));
        }
        return substr($s, $start, $len);
    }
}

// ตำแหน่งงานของผู้ใช้ (เดิมเขียน SQL เดียวกันซ้ำหลายที่)
if (!function_exists('getUserPosition')) {
    function getUserPosition($db, $userId) {
        $row = $db->fetchOne("SELECT position FROM users WHERE id=" . intval($userId));
        return $row;
    }
}

// escape ค่าเป็น JS string literal ที่วางใน attribute HTML ได้ (json_encode + htmlspecialchars)
if (!function_exists('jsAttr')) {
    function jsAttr($value) {
        return htmlspecialchars(json_encode((string) $value), ENT_QUOTES, 'UTF-8');
    }
}

// escape ค่าเป็น JS string literal ที่วางในบล็อก <script> (ไม่ใช่ attribute) กันหลุดด้วย </script>
if (!function_exists('jsStr')) {
    function jsStr($value) {
        return str_replace(array('<', '>'), array('\\u003C', '\\u003E'), json_encode((string) $value));
    }
}

// กรอง roles[] ที่รับมาจากฟอร์มให้เหลือเฉพาะ role ที่ระบบรู้จัก (whitelist) ถ้าว่างใช้ submitter ตามค่าเดิม
if (!function_exists('sanitizeRolesInput')) {
    function sanitizeRolesInput($input) {
        $valid = getAllRoleKeys();
        $out   = array();
        if (is_array($input)) {
            foreach ($input as $r) {
                if (is_string($r) && in_array($r, $valid, true) && !in_array($r, $out, true)) {
                    $out[] = $r;
                }
            }
        }
        if (empty($out)) {
            $out = array('submitter');
        }
        return $out;
    }
}

// แถว "ไม่พบรายการ" ของตาราง (เดิมเขียน HTML เดียวกันซ้ำ 11 ไฟล์ ต่างกันแค่ colspan และข้อความ)
if (!function_exists('emptyRowHtml')) {
    function emptyRowHtml($cols, $msg) {
        echo '<tr><td colspan="' . intval($cols) . '" style="text-align:center;color:#aaa;padding:40px 0;">' . "\n"
           . '          <i class="fas fa-inbox" style="font-size:30px;display:block;margin-bottom:8px;color:#ddd;"></i>' . $msg . "\n"
           . '        </td></tr>' . "\n";
    }
}


// อายุราชการ: คำนวณจากวันที่บรรจุ (dd/mm/yyyy พ.ศ.) ถึงวันปัจจุบัน ตามปฏิทินจริง
// คืน "X ปี Y เดือน Z วัน" ถ้าไม่มีค่า/รูปแบบผิด/เป็นวันในอนาคต คืน "-"
// ไม่ใช้ DateTime::diff() (ต้อง PHP 5.3) และไม่ใช้ cal_days_in_month() (extension อาจไม่เปิด)
if (!function_exists('calcServiceAge')) {
    function calcServiceAge($thaiDate, $now = null) {
        if (!is_string($thaiDate) || !preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', trim($thaiDate), $m)) {
            return '-';
        }
        $d  = intval($m[1]);
        $mo = intval($m[2]);
        $y  = intval($m[3]) - 543;
        if ($y < 1 || !checkdate($mo, $d, $y)) {
            return '-';
        }
        if ($now === null) {
            $now = time();
        }
        $ty = intval(date('Y', $now));
        $tm = intval(date('n', $now));
        $td = intval(date('j', $now));

        $years  = $ty - $y;
        $months = $tm - $mo;
        $days   = $td - $d;
        // ยืมวันจากเดือนก่อนหน้าทีละเดือนจนกว่าจะไม่ติดลบ (เดือนสั้นอย่าง ก.พ. ต้องยืมมากกว่า 1 ครั้งได้)
        $pm = $tm;
        $py = $ty;
        while ($days < 0) {
            $pm--;
            if ($pm < 1) { $pm = 12; $py--; }
            $days += intval(date('t', mktime(0, 0, 0, $pm, 1, $py)));
            $months--;
        }
        while ($months < 0) {
            $years--;
            $months += 12;
        }
        if ($years < 0) {
            return '-';
        }
        return $years . ' ปี ' . $months . ' เดือน ' . $days . ' วัน';
    }
}

// ตัวเลือกวุฒิการศึกษา (ใช้ทั้งฟอร์มและการตรวจฝั่งเซิร์ฟเวอร์ ค่าที่เก็บ = ข้อความตามนี้)
if (!function_exists('getEducationOptions')) {
    function getEducationOptions() {
        return array('ปริญญาเอก', 'ปริญญาโท', 'ปริญญาตรี', 'ปวส.', 'ปวช.');
    }
}

// วันที่แบบไทยสำหรับ Excel: ว่างเมื่อไม่มีค่า (thaiDate() คืน '-')
if (!function_exists('thaiDateOrBlank')) {
    function thaiDateOrBlank($dateStr) {
        if (empty($dateStr) || strpos($dateStr, '0000-00-00') === 0) return '';
        return thaiDate($dateStr, true);
    }
}

// ระยะเวลาดำเนินการ (วัน) นับตามวันปฏิทิน รวมวันเริ่มและวันเสร็จ (วันเดียวกัน = 1 วัน) ว่างเมื่อข้อมูลไม่ครบ
if (!function_exists('workDaysLabel')) {
    function workDaysLabel($startAt, $endAt) {
        if (empty($startAt) || empty($endAt)) return '';
        if (strpos($startAt, '0000-00-00') === 0 || strpos($endAt, '0000-00-00') === 0) return '';
        $s = explode('-', substr($startAt, 0, 10));
        $e = explode('-', substr($endAt, 0, 10));
        if (count($s) != 3 || count($e) != 3) return '';
        $ds = gmmktime(0, 0, 0, intval($s[1]), intval($s[2]), intval($s[0]));
        $de = gmmktime(0, 0, 0, intval($e[1]), intval($e[2]), intval($e[0]));
        if ($de < $ds) return '';
        return ((int) round(($de - $ds) / 86400) + 1) . ' วัน';
    }
}

// ลิงก์เปิดไฟล์แนบการอบรม (PDF) ของสมาชิก: คืน '' เมื่อไม่มีไฟล์ (เปิดผ่าน action ที่ตรวจสิทธิ์ ไม่ลิงก์ path ตรง)
if (!function_exists('trainingFileLinkHtml')) {
    function trainingFileLinkHtml($userId, $level, $path, $name) {
        if ($path === null || $path === '') return '';
        $label = ($name !== null && $name !== '') ? $name : 'ไฟล์แนบ';
        return '<a href="?page=users&amp;action=view_training_file&amp;id=' . intval($userId) . '&amp;level=' . $level
             . '" target="_blank" style="color:#2e8442;font-size:13px;"><i class="fas fa-file-pdf"></i> ' . e($label) . '</a>';
    }
}

// เงื่อนไขช่วงวันที่ของรายการ (เดิมเขียนซ้ำใน 5 Model) คืนสตริงขึ้นต้นด้วย " AND ..." หรือ '' ถ้าไม่มี filter
if (!function_exists('dateRangeClause')) {
    function dateRangeClause($db, $column, $filters) {
        $out = '';
        if (!empty($filters['date_from'])) {
            $df   = $db->escape($filters['date_from']);
            $out .= " AND DATE($column) >= '$df'";
        }
        if (!empty($filters['date_to'])) {
            $dt   = $db->escape($filters['date_to']);
            $out .= " AND DATE($column) <= '$dt'";
        }
        return $out;
    }
}

// "LIMIT n OFFSET m" จาก per_page/page_num (ค่าเริ่มต้น $default เมื่อไม่มี/น้อยกว่า 1)
if (!function_exists('limitClause')) {
    function limitClause($filters, $default = 30) {
        $perPage = isset($filters['per_page']) ? intval($filters['per_page']) : $default;
        $page    = isset($filters['page_num']) ? intval($filters['page_num']) : 1;
        if ($perPage < 1) $perPage = $default;
        if ($page < 1)    $page    = 1;
        $offset = ($page - 1) * $perPage;
        return "LIMIT $perPage OFFSET $offset";
    }
}

// ช่องกรอกวันที่ + ช่องแสดงตัวอย่างวันที่ที่อ่านง่าย (ใช้คู่กับ setupDateInput() ใน app.js) คืน HTML
// $opts: maxlength (ต้องมี), placeholder, required (true), value (HTML ที่ escape แล้ว),
//        preview (HTML ที่ escape แล้ว ค่าเริ่มต้น '-'), previewMinWidth (ค่าเริ่มต้น 100)
// เดิมเขียน markup นี้ซ้ำในฟอร์มสมาชิก/สหกรณ์/เอกสาร/ครุภัณฑ์ ต่างกันแค่ค่าด้านบน
if (!function_exists('dateInputWidget')) {
    function dateInputWidget($name, $inputId, $previewId, $opts) {
        $ph  = isset($opts['placeholder']) ? ' placeholder="' . $opts['placeholder'] . '"' : '';
        $req = !empty($opts['required']) ? ' required' : '';
        $val = isset($opts['value']) ? $opts['value'] : '';
        $prv = isset($opts['preview']) ? $opts['preview'] : '-';
        $mw  = isset($opts['previewMinWidth']) ? intval($opts['previewMinWidth']) : 100;
        return '<div style="display:flex;border:1px solid #ccc;border-radius:4px;overflow:hidden;">' . "\n"
             . '              <input type="text" name="' . $name . '" id="' . $inputId . '"'
             . ' style="flex:1;min-width:0;border:0;padding:8px 10px;font-size:14px;background:#eef1f6;"'
             . $ph . ' maxlength="' . intval($opts['maxlength']) . '"' . $req . ' value="' . $val . '">' . "\n"
             . '              <span id="' . $previewId . '" style="background:#f4f6f8;border-left:1px solid #ccc;padding:0 10px;display:flex;align-items:center;font-size:12px;color:#777;min-width:' . $mw . 'px;">' . $prv . '</span>' . "\n"
             . '            </div>' . "\n";
    }
}

// หน้า/action ที่เปิดเป็น modal ได้ เป็นแหล่งข้อมูลเดียวของทั้ง PHP (footer.php) และ JS (EDMS_MODAL_MAP ใน app.js)
if (!function_exists('edmsModalMap')) {
    function edmsModalMap() {
        return array(
            'documents'         => array('create', 'edit', 'detail'),
            'issues'            => array('create', 'detail'),
            'internal_services' => array('create', 'detail'),
            'users'             => array('create', 'edit'),
            'cooperatives'      => array('create', 'edit'),
            'announcements'     => array('detail', 'video_detail'),
        );
    }
}

// ช่อง "ผ่านการอบรม" 3 ระดับ (ข้อความ + ไฟล์ PDF) สำหรับฟอร์มสมาชิก คืน HTML ของ .form-group ทั้ง 3 ช่อง
// $user = null สำหรับฟอร์มเพิ่มสมาชิก (ไม่มีค่าเดิม/ไฟล์เดิม) ส่ง array ผู้ใช้สำหรับหน้าแก้ไข/โปรไฟล์
// $labelPrefix เช่น 'ผ่านการอบรม ' (โปรไฟล์) หรือ '' (ฟอร์มสมาชิก)
// ชื่อฟิลด์/ชื่อไฟล์ต้องตรงกับ UserController::trainingFileDefs()
if (!function_exists('trainingFieldsHtml')) {
    function trainingFieldsHtml($user, $labelPrefix = '') {
        $levels = array(
            'basic'        => array('training_basic',        'ระดับต้น'),
            'intermediate' => array('training_intermediate', 'ระดับกลาง'),
            'advanced'     => array('training_advanced',     'ระดับสูง'),
        );
        $edit = is_array($user);
        $out  = '';
        foreach ($levels as $level => $def) {
            $field = $def[0];
            $out .= '          <div class="form-group">' . "\n"
                  . '            <label>' . $labelPrefix . $def[1] . ' <small style="color:#999;font-weight:400;">(ไม่บังคับ)</small></label>' . "\n";
            if ($edit) {
                $val  = isset($user[$field]) ? $user[$field] : '';
                $out .= '            <input type="text" name="' . $field . '" class="form-control" maxlength="255" value="' . e($val) . '">' . "\n";
            } else {
                $out .= '            <input type="text" name="' . $field . '" class="form-control" maxlength="255">' . "\n";
            }
            $out .= '            <input type="file" name="file_' . $field . '" class="form-control training-file-input" accept=".pdf" style="margin-top:6px;">' . "\n"
                  . '            <small style="color:#999;">แนบไฟล์ PDF (ไม่บังคับ)' . ($edit ? ' ไฟล์ใหม่จะแทนที่ไฟล์เดิม' : '') . '</small>' . "\n";
            if ($edit && !empty($user[$field . '_file'])) {
                $fname = isset($user[$field . '_file_name']) ? $user[$field . '_file_name'] : '';
                $out .= '            <div style="margin-top:4px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">' . "\n"
                      . '              ' . trainingFileLinkHtml($user['id'], $level, $user[$field . '_file'], $fname) . "\n"
                      . '              <label style="margin:0;cursor:pointer;font-size:12px;color:#c0392b;"><input type="checkbox" name="remove_' . $field . '_file" value="1"> ลบไฟล์นี้</label>' . "\n"
                      . '            </div>' . "\n";
            }
            $out .= '          </div>' . "\n";
        }
        return $out;
    }
}
