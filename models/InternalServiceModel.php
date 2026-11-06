<?php
class InternalServiceModel {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // $includeStatus = false ใช้กับการ์ดสรุปตามสถานะ (ไม่กรองสถานะ เพื่อให้เห็นครบทุกสถานะ)
    private function buildFilterClause($user, $filters = array(), $includeStatus = true) {
        $where = buildServiceWhereClause($user);

        if (!empty($filters['office_name'])) {
            $on = $this->db->escape($filters['office_name']);
            $where .= " AND s.office_name = '$on'";
        }
        if ($includeStatus && !empty($filters['status'])) {
            $s = $this->db->escape($filters['status']);
            $where .= " AND s.status = '$s'";
        }
        if (!empty($filters['keyword'])) {
            $kw = $this->db->escape($filters['keyword']);
            $where .= " AND (s.title LIKE '%$kw%' OR s.ticket_code LIKE '%$kw%')";
        }
        $where .= dateRangeClause($this->db, 's.created_at', $filters);

        return $where;
    }

    // จำนวนคำขอแยกตามสถานะ (ใช้ในหน้ารายงาน)
    public function getSummaryByStatus($user, $filters = array()) {
        $where = $this->buildFilterClause($user, $filters, false);
        return $this->db->fetchAll(
            "SELECT s.status, COUNT(*) AS cnt
             FROM internal_services s
             JOIN users su ON su.id = s.submitted_by
             WHERE $where
             GROUP BY s.status"
        );
    }

    public function getList($user, $filters = array()) {
        $where = $this->buildFilterClause($user, $filters);

        $limit = limitClause($filters, 30);

        $sql = "SELECT s.*,
                       CONCAT(su.prefix,' ',su.firstname,' ',su.lastname) AS submitter_name,
                       CONCAT(ru.prefix,' ',ru.firstname,' ',ru.lastname) AS requester_name
                FROM internal_services s
                JOIN users su ON su.id = s.submitted_by
                JOIN users ru ON ru.id = s.requester_id
                WHERE $where
                ORDER BY s.created_at DESC
                $limit";

        return $this->db->fetchAll($sql);
    }

    public function countList($user, $filters = array()) {
        $where = $this->buildFilterClause($user, $filters);

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM internal_services s
             JOIN users su ON su.id = s.submitted_by
             WHERE $where"
        );
        return intval($row['cnt']);
    }

    public function getById($id) {
        $id = intval($id);
        return $this->db->fetchOne(
            "SELECT s.*,
                    CONCAT(su.prefix,' ',su.firstname,' ',su.lastname) AS submitter_name,
                    CONCAT(ru.prefix,' ',ru.firstname,' ',ru.lastname) AS requester_name,
                    ru.position AS requester_position
             FROM internal_services s
             JOIN users su ON su.id = s.submitted_by
             JOIN users ru ON ru.id = s.requester_id
             WHERE s.id = $id"
        );
    }

    // ดึงรายการทั้งหมดที่ตรงกับตัวกรอง (ไม่แบ่งหน้า) สำหรับใช้ตอนส่งออก Excel
    public function getAllForExport($user, $filters = array()) {
        $where = $this->buildFilterClause($user, $filters);
        return $this->db->fetchAll(
            "SELECT s.*,
                    CONCAT(su.prefix,' ',su.firstname,' ',su.lastname) AS submitter_name,
                    CONCAT(ru.prefix,' ',ru.firstname,' ',ru.lastname) AS requester_name,
                    (SELECT MIN(l.created_at) FROM internal_service_logs l
                      WHERE l.service_id = s.id AND l.action = 'รับเรื่องดำเนินการ') AS started_at,
                    (SELECT MAX(l.created_at) FROM internal_service_logs l
                      WHERE l.service_id = s.id AND l.action = 'ดำเนินการเสร็จสิ้น') AS completed_at,
                    (SELECT l.note FROM internal_service_logs l
                      WHERE l.service_id = s.id AND l.action = 'ดำเนินการเสร็จสิ้น'
                      ORDER BY l.id DESC LIMIT 1) AS result_note
             FROM internal_services s
             JOIN users su ON su.id = s.submitted_by
             JOIN users ru ON ru.id = s.requester_id
             WHERE $where
             ORDER BY s.created_at DESC"
        );
    }

    public function getLogs($serviceId) {
        return ActivityLog::get($this->db, 'internal_service_logs', 'service_id', $serviceId);
    }

    public function addLog($serviceId, $action, $actionBy, $note = '') {
        ActivityLog::add($this->db, 'internal_service_logs', 'service_id', $serviceId, $action, $actionBy, $note);
    }
}
