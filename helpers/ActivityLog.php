<?php
// บันทึก/อ่านประวัติการดำเนินการ (ใช้ร่วมกันใน document_logs / issue_logs / internal_service_logs)
// ชื่อตารางและคอลัมน์ต้องส่งมาจากโค้ดภายในเท่านั้น ห้ามส่งค่าที่มาจากผู้ใช้
class ActivityLog {

    public static function add($db, $table, $fk, $id, $action, $actionBy, $note = '') {
        $id       = intval($id);
        $actionBy = intval($actionBy);
        $action   = $db->escape($action);
        $note     = $db->escape($note);
        $db->query(
            "INSERT INTO `$table` (`$fk`, action, action_by, note)
             VALUES ($id, '$action', $actionBy, '$note')"
        );
    }

    public static function get($db, $table, $fk, $id) {
        $id = intval($id);
        return $db->fetchAll(
            "SELECT l.*, CONCAT(u.prefix,' ',u.firstname,' ',u.lastname) AS actor_name
             FROM `$table` l
             JOIN users u ON u.id = l.action_by
             WHERE l.`$fk` = $id
             ORDER BY l.created_at ASC"
        );
    }
}
