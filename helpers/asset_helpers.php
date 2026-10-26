<?php
// ฟังก์ชันช่วยเหลือของระบบจัดการครุภัณฑ์ (ต้อง require หลัง helpers/functions.php)
// โครงสร้างข้อมูลแต่ละประเภทอ้างอิงจากไฟล์ Excel 3 ไฟล์ของสำนักงาน
//   registered = ครุภัณฑ์                    (บัญชีรายละเอียดครุภัณฑ์)
//   under10k   = ครุภัณฑ์ไม่เกิน 10,000       (บัญชีรายละเอียดครุภัณฑ์ไม่เกิน 10,000)
//   external   = ครุภัณฑ์นอกทะเบียน          (รายละเอียดครุภัณฑ์นอกทะเบียน มีตัวตนใช้งานอยู่)

function getAssetTypeOptions() {
    return array(
        'registered' => 'ครุภัณฑ์',
        'under10k'   => 'ครุภัณฑ์ไม่เกิน 10,000',
        'external'   => 'ครุภัณฑ์นอกทะเบียน',
    );
}

function assetUnderLimit() {
    return 10000;
}

function assetTypeLabel($key) {
    $o = getAssetTypeOptions();
    return isset($o[$key]) ? $o[$key] : $key;
}

function assetTypeBadgeClass($key) {
    $map = array('registered' => 'badge-blue', 'under10k' => 'badge-amber', 'external' => 'badge-purple');
    return isset($map[$key]) ? $map[$key] : 'badge-slate';
}

// ชื่อหัวรายงาน Excel ของแต่ละประเภท (บรรทัดที่ 2)
function assetReportTitle($type) {
    $map = array(
        'registered' => 'บัญชีรายละเอียดครุภัณฑ์',
        'under10k'   => 'บัญชีรายละเอียดครุภัณฑ์ไม่เกิน 10,000',
        'external'   => 'รายละเอียดครุภัณฑ์นอกทะเบียน (มีตัวตนใช้งานอยู่)',
    );
    return isset($map[$type]) ? $map[$type] : '';
}

// หมวดทรัพย์สินของแต่ละประเภท (ลำดับตามไฟล์ Excel)
function getAssetCategoryOptions($type) {
    $map = array(
        'registered' => array('อาคารสำนักงาน','อาคารที่พักอาศัย','สิ่งปลูกสร้าง','ครุภัณฑ์สำนักงาน','ครุภัณฑ์ยานพาหนะ',
                              'ครุภัณฑ์ไฟฟ้าและวิทยุ','ครุภัณฑ์โฆษณาและเผยแพร่','ครุภัณฑ์คอมพิวเตอร์',
                              'ครุภัณฑ์งานบ้านงานครัว','สินทรัพย์-Software'),
        'under10k'   => array('ครุภัณฑ์สำนักงาน','ครุภัณฑ์คอมพิวเตอร์','ครุภัณฑ์โฆษณาและเผยแพร่',
                              'ครุภัณฑ์ไฟฟ้าและวิทยุ','ครุภัณฑ์งานบ้านงานครัว','โปรแกรมคอมพิวเตอร์'),
        'external'   => array('ครุภัณฑ์สำนักงาน','ครุภัณฑ์เผยแพร่คอมพิวเตอร์และโฆษณา','ครุภัณฑ์คอมพิวเตอร์'),
    );
    return isset($map[$type]) ? $map[$type] : array();
}

// ฟิลด์ที่ใช้ในแต่ละประเภท
//   gf_code=รหัสสินทรัพย์(GF)  quantity=จำนวน+หน่วยนับ  price=มูลค่า/ราคาต่อหน่วย
//   acquired=วันที่ได้มา  method=วิธีการได้มา  costs=รก.ค่าการได้มา + ค่าบ/สิ้นงวด
function assetTypeHas($type, $field) {
    $map = array(
        'registered' => array('gf_code', 'price', 'acquired', 'method', 'costs'),
        'under10k'   => array('quantity', 'price', 'acquired', 'method'),
        'external'   => array('quantity'),
    );
    return isset($map[$type]) && in_array($field, $map[$type]);
}

function getAcquireMethodOptions() {
    return array('งบประมาณ', 'บริจาค', 'รับบริจาค', 'รับโอน', 'อื่น ๆ');
}

// สภาพสินทรัพย์ (ตามคอลัมน์ "สภาพสินทรัพย์" ใน Excel)
function getAssetStatusOptions() {
    return array(
        'normal'       => 'ปกติ',
        'damaged'      => 'ชำรุด',
        'deteriorated' => 'เสื่อมสภาพ',
        'dilapidated'  => 'ทรุดโทรม',
        'lost'         => 'สูญหาย',
        'disposed'     => 'จำหน่ายแล้ว',
    );
}

function assetStatusLabel($key) {
    $o = getAssetStatusOptions();
    return isset($o[$key]) ? $o[$key] : $key;
}

function assetStatusBadgeClass($key) {
    $map = array('normal' => 'badge-green', 'damaged' => 'badge-amber', 'deteriorated' => 'badge-amber',
                 'dilapidated' => 'badge-amber', 'lost' => 'badge-red', 'disposed' => 'badge-slate');
    return isset($map[$key]) ? $map[$key] : 'badge-slate';
}

// ผลตรวจนับ
function getCheckResultOptions() {
    return array(
        'found_normal'  => 'พบ - สภาพปกติ',
        'found_damaged' => 'พบ - ชำรุด',
        'found_moved'   => 'พบ - อยู่ผิดสถานที่',
    );
}

function checkResultLabel($key) {
    $o = getCheckResultOptions();
    return isset($o[$key]) ? $o[$key] : $key;
}

function checkResultBadgeClass($key) {
    $map = array('found_normal' => 'badge-green', 'found_damaged' => 'badge-amber', 'found_moved' => 'badge-sky');
    return isset($map[$key]) ? $map[$key] : 'badge-slate';
}

// วันที่เก็บเป็น dd/mm/yyyy (พ.ศ.) เหมือนช่องวันที่อื่นในระบบ
function isValidThaiDate($s) {
    if (!preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $s, $m)) return false;
    $y = intval($m[3]) - 543;
    return $y >= 1 && checkdate(intval($m[2]), intval($m[1]), $y);
}

// ปีงบประมาณปัจจุบัน (พ.ศ.) — เริ่ม 1 ต.ค.
function currentFiscalYear() {
    return intval(date('Y')) + 543 + (intval(date('n')) >= 10 ? 1 : 0);
}

// ผู้จัดการครุภัณฑ์ (เพิ่ม/แก้ไข/ลบ/พิมพ์สติ๊กเกอร์/เปิดรอบตรวจนับ): admin และ inspector
function canManageAssets($user) {
    return userHasAnyRole($user, array('admin', 'inspector'));
}

// ดู/สแกนตรวจนับ/ส่งออกได้: admin/ส่วนกลางเห็นทั้งหมด, สำนักงานอื่นเห็นเฉพาะของสำนักงานตนเอง
function canViewAsset($user, $asset) {
    return userHasRole($user, 'admin') || isHQ($user) || $asset['office_name'] === $user['office_name'];
}

function buildAssetWhereClause($user) {
    if (userHasRole($user, 'admin') || isHQ($user)) {
        return 'a.is_active = 1';
    }
    global $db;
    $off = $db->escape($user['office_name']);
    return "a.is_active = 1 AND a.office_name = '$off'";
}

// URL ที่ฝังใน QR Code (ใช้ APP_URL จาก config ต้องเป็นที่อยู่ที่มือถือเข้าถึงได้)
function assetScanUrl($code) {
    return APP_URL . '/?page=assets&action=scan&code=' . rawurlencode($code);
}
