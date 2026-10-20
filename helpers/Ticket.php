<?php
// สร้างเลขที่รายการถัดไปจาก MAX(ลำดับ)+1 (เดิมใช้ COUNT(*)+1 ซึ่งซ้ำได้เมื่อมีการลบ/ส่งพร้อมกัน)
// $prefix เช่น 'ISS-2569-'  -> ได้ 'ISS-2569-00001'
// ผู้เรียกควรเช็คผล INSERT แล้ว retry เมื่อชน UNIQUE KEY
class Ticket {
    public static function next($db, $table, $prefix) {
        $p   = $db->escape($prefix);
        $len = strlen($prefix) + 1;
        $row = $db->fetchOne(
            "SELECT MAX(CAST(SUBSTRING(ticket_code, $len) AS UNSIGNED)) AS mx
             FROM `$table` WHERE ticket_code LIKE '{$p}%'"
        );
        $seq = ($row && $row['mx'] !== null) ? intval($row['mx']) + 1 : 1;
        return $prefix . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }

    // $code เช่น 'ISS' -> 'ISS-2569-' (ปีพุทธศักราชปัจจุบัน)
    public static function prefix($code) {
        return $code . '-' . (date('Y') + 543) . '-';
    }
}
