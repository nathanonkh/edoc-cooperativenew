<?php
class InternalServiceModel {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    private function buildFilterClause($user, $filters = array()) {
        $where = buildServiceWhereClause($user);

        if (!empty($filters['status'])) {
            $s = $this->db->escape($filters['status']);
            $where .= " AND s.status = '$s'";
        }
        if (!empty($filters['keyword'])) {
            $kw = $this->db->escape($filters['keyword']);
            $where .= " AND (s.title LIKE '%$kw%' OR s.ticket_code LIKE '%$kw%')";
        }
        if (!empty($filters['date_from'])) {
            $df = $this->db->escape($filters['date_from']);
            $where .= " AND DATE(s.created_at) >= '$df'";
        }
        if (!empty($filters['date_to'])) {
            $dt = $this->db->escape($filters['date_to']);
            $where .= " AND DATE(s.created_at) <= '$dt'";
        }

        return $where;
    }

    public function getList($user, $filters = array()) {
        $where = $this->buildFilterClause($user, $filters);

        $perPage = isset($filters['per_page']) ? intval($filters['per_page']) : 30;
        $page    = isset($filters['page_num']) ? intval($filters['page_num']) : 1;
        if ($perPage < 1) $perPage = 30;
        if ($page < 1)    $page    = 1;
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT s.*,
                       CONCAT(su.prefix,' ',su.firstname,' ',su.lastname) AS submitter_name,
                       CONCAT(ru.prefix,' ',ru.firstname,' ',ru.lastname) AS requester_name
                FROM internal_services s
                JOIN users su ON su.id = s.submitted_by
                JOIN users ru ON ru.id = s.requester_id
                WHERE $where
                ORDER BY s.created_at DESC
                LIMIT $perPage OFFSET $offset";

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
                    CONCAT(ru.prefix,' ',ru.firstname,' ',ru.lastname) AS requester_name
             FROM internal_services s
             JOIN users su ON su.id = s.submitted_by
             JOIN users ru ON ru.id = s.requester_id
             WHERE $where
             ORDER BY s.created_at DESC"
        );
    }

    public function getLogs($serviceId) {
        $serviceId = intval($serviceId);
        return $this->db->fetchAll(
            "SELECT sl.*, CONCAT(u.prefix,' ',u.firstname,' ',u.lastname) AS actor_name
             FROM internal_service_logs sl
             JOIN users u ON u.id = sl.action_by
             WHERE sl.service_id = $serviceId
             ORDER BY sl.created_at ASC"
        );
    }

    public function addLog($serviceId, $action, $actionBy, $note = '') {
        $serviceId = intval($serviceId);
        $actionBy  = intval($actionBy);
        $action    = $this->db->escape($action);
        $note      = $this->db->escape($note);
        $this->db->query(
            "INSERT INTO internal_service_logs (service_id, action, action_by, note)
             VALUES ($serviceId, '$action', $actionBy, '$note')"
        );
    }

    public function generateTicketCode() {
        $year = date('Y') + 543;
        $row  = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM internal_services WHERE ticket_code LIKE 'SRV-$year-%'"
        );
        $seq  = intval($row['cnt']) + 1;
        return 'SRV-' . $year . '-' . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }
}
