<?php
class UnlockRequestModel {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // $includeStatus = false ใช้กับการ์ดสรุปตามสถานะ (ไม่กรองสถานะ เพื่อให้เห็นครบทุกสถานะ)
    private function buildFilterClause($user, $filters = array(), $includeStatus = true) {
        $where = buildUnlockWhereClause($user);

        if (!empty($filters['office_name'])) {
            $on = $this->db->escape($filters['office_name']);
            $where .= " AND ur.office_name = '$on'";
        }
        if ($includeStatus && !empty($filters['status'])
            && in_array($filters['status'], array('pending', 'completed'), true)) {
            $s = $this->db->escape($filters['status']);
            $where .= " AND ur.status = '$s'";
        }
        if (!empty($filters['keyword'])) {
            $kw = $this->db->escape($filters['keyword']);
            $where .= " AND (ur.title LIKE '%$kw%' OR ur.ticket_code LIKE '%$kw%'
                        OR ur.cooperative_name LIKE '%$kw%' OR ur.cooperative_code LIKE '%$kw%'
                        OR CONCAT(au.firstname,' ',au.lastname) LIKE '%$kw%')";
        }
        $where .= dateRangeClause($this->db, 'ur.created_at', $filters);

        return $where;
    }

    // จำนวนคำขอแยกตามสถานะ (ใช้ในหน้ารายงาน)
    public function getSummaryByStatus($user, $filters = array()) {
        $where = $this->buildFilterClause($user, $filters, false);
        return $this->db->fetchAll(
            "SELECT ur.status, COUNT(*) AS cnt
             FROM unlock_requests ur
             JOIN users su ON su.id = ur.submitted_by
             LEFT JOIN users au ON au.id = ur.auditor_id
             WHERE $where
             GROUP BY ur.status"
        );
    }

    public function getList($user, $filters = array()) {
        $where = $this->buildFilterClause($user, $filters);
        $limit = limitClause($filters, 30);

        return $this->db->fetchAll(
            "SELECT ur.*,
                    CONCAT(su.prefix,' ',su.firstname,' ',su.lastname) AS submitter_name,
                    CONCAT(au.prefix,' ',au.firstname,' ',au.lastname) AS auditor_name
             FROM unlock_requests ur
             JOIN users su ON su.id = ur.submitted_by
             LEFT JOIN users au ON au.id = ur.auditor_id
             WHERE $where
             ORDER BY ur.created_at DESC, ur.id DESC
             $limit"
        );
    }

    public function countList($user, $filters = array()) {
        $where = $this->buildFilterClause($user, $filters);

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt
             FROM unlock_requests ur
             JOIN users su ON su.id = ur.submitted_by
             LEFT JOIN users au ON au.id = ur.auditor_id
             WHERE $where"
        );
        return $row ? intval($row['cnt']) : 0;
    }

    public function getById($id) {
        $id = intval($id);
        return $this->db->fetchOne(
            "SELECT ur.*,
                    CONCAT(su.prefix,' ',su.firstname,' ',su.lastname) AS submitter_name,
                    CONCAT(au.prefix,' ',au.firstname,' ',au.lastname) AS auditor_name,
                    au.position AS auditor_position
             FROM unlock_requests ur
             JOIN users su ON su.id = ur.submitted_by
             LEFT JOIN users au ON au.id = ur.auditor_id
             WHERE ur.id = $id"
        );
    }

    // ดึงรายการทั้งหมดที่ตรงกับตัวกรอง (ไม่แบ่งหน้า) สำหรับส่งออก Excel
    public function getAllForExport($user, $filters = array()) {
        $where = $this->buildFilterClause($user, $filters);
        return $this->db->fetchAll(
            "SELECT ur.*,
                    CONCAT(su.prefix,' ',su.firstname,' ',su.lastname) AS submitter_name,
                    CONCAT(au.prefix,' ',au.firstname,' ',au.lastname) AS auditor_name,
                    (SELECT MAX(l.created_at) FROM unlock_request_logs l
                      WHERE l.unlock_id = ur.id AND l.action = 'ดำเนินการเสร็จสิ้น') AS completed_at,
                    (SELECT l.note FROM unlock_request_logs l
                      WHERE l.unlock_id = ur.id AND l.action = 'ดำเนินการเสร็จสิ้น'
                      ORDER BY l.id DESC LIMIT 1) AS result_note
             FROM unlock_requests ur
             JOIN users su ON su.id = ur.submitted_by
             LEFT JOIN users au ON au.id = ur.auditor_id
             WHERE $where
             ORDER BY ur.created_at DESC, ur.id DESC"
        );
    }

    public function getLogs($unlockId) {
        return ActivityLog::get($this->db, 'unlock_request_logs', 'unlock_id', $unlockId);
    }

    public function addLog($unlockId, $action, $actionBy, $note = '') {
        ActivityLog::add($this->db, 'unlock_request_logs', 'unlock_id', $unlockId, $action, $actionBy, $note);
    }
}
